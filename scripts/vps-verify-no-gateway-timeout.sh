#!/usr/bin/env bash
#
# VPS verification: best-practice checks to avoid gateway timeout after deploy.
# Run on your VPS (e.g. after deploy): bash scripts/vps-verify-no-gateway-timeout.sh
#
# Optional: WEB_CONTAINER=... MYSQL_CONTAINER=... (auto-detected if on Coolify)
#

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS=0
FAIL=0
WARN=0

pass() { echo -e "${GREEN}[PASS]${NC} $*"; PASS=$((PASS+1)); }
fail() { echo -e "${RED}[FAIL]${NC} $*"; FAIL=$((FAIL+1)); }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; WARN=$((WARN+1)); }

# --- Auto-detect web and mysql containers (Coolify-style or default names) ---
if [ -z "$WEB_CONTAINER" ] || [ -z "$MYSQL_CONTAINER" ]; then
  WEB_CANDIDATES=$(docker ps -a --format '{{.Names}}' 2>/dev/null | grep -E '^web-|thinqshopping_web' || true)
  MYSQL_CANDIDATES=$(docker ps -a --format '{{.Names}}' 2>/dev/null | grep -E '^mysql-|thinqshopping_mysql' || true)
  if [ -z "$WEB_CONTAINER" ]; then
    WEB_CONTAINER=$(echo "$WEB_CANDIDATES" | head -1)
    [ -z "$WEB_CONTAINER" ] && WEB_CONTAINER="thinqshopping_web"
  fi
  if [ -z "$MYSQL_CONTAINER" ]; then
    MYSQL_CONTAINER=$(echo "$MYSQL_CANDIDATES" | head -1)
    [ -z "$MYSQL_CONTAINER" ] && MYSQL_CONTAINER="thinqshopping_mysql"
  fi
fi

echo "=============================================="
echo " Gateway timeout prevention – config check"
echo " $(date -Iseconds 2>/dev/null || date)"
echo "=============================================="
echo " Web container:  $WEB_CONTAINER"
echo " MySQL container: $MYSQL_CONTAINER"
echo ""

# --- 1. MySQL container exists and is running ---
echo "--- 1. MySQL container ---"
if ! docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${MYSQL_CONTAINER}$"; then
  fail "MySQL container '$MYSQL_CONTAINER' not found. Set MYSQL_CONTAINER=..."
else
  MYSQL_STATUS=$(docker inspect -f '{{.State.Status}}' "$MYSQL_CONTAINER" 2>/dev/null || echo "unknown")
  if [ "$MYSQL_STATUS" = "running" ]; then
    pass "MySQL container is running"
  else
    fail "MySQL container is not running (status: $MYSQL_STATUS)"
  fi

  # --- 2. MySQL has healthcheck and is healthy ---
  HC=$(docker inspect -f '{{.State.Health.Status}}' "$MYSQL_CONTAINER" 2>/dev/null || echo "none")
  if [ "$HC" = "none" ] || [ -z "$HC" ]; then
    fail "MySQL has no healthcheck. Add healthcheck in docker-compose so web can use depends_on: condition: service_healthy"
  elif [ "$HC" = "healthy" ]; then
    pass "MySQL healthcheck: healthy"
  else
    fail "MySQL healthcheck: $HC (should be healthy)"
  fi
fi
echo ""

# --- 3. Web container exists and is running ---
echo "--- 2. Web container ---"
if ! docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${WEB_CONTAINER}$"; then
  fail "Web container '$WEB_CONTAINER' not found. Set WEB_CONTAINER=..."
else
  WEB_STATUS=$(docker inspect -f '{{.State.Status}}' "$WEB_CONTAINER" 2>/dev/null || echo "unknown")
  if [ "$WEB_STATUS" = "running" ]; then
    pass "Web container is running"
  else
    fail "Web container is not running (status: $WEB_STATUS)"
  fi

  # --- 4. Web listens on port 80 ---
  if [ "$WEB_STATUS" = "running" ]; then
    if docker exec "$WEB_CONTAINER" sh -c 'command -v ss >/dev/null 2>&1 && ss -tlnp | grep -q ":80 " || netstat -tlnp 2>/dev/null | grep -q ":80 "'; then
      pass "Web container is listening on port 80"
    else
      # Fallback: check if apache is running
      if docker exec "$WEB_CONTAINER" pgrep -x apache2 >/dev/null 2>&1; then
        pass "Apache is running (port 80 assumed)"
      else
        fail "Port 80 not listening and Apache not running – migration may still be running or startup failed"
      fi
    fi
  fi
fi
echo ""

# --- 5. Web can reach MySQL ---
echo "--- 3. Web → MySQL connectivity ---"
if [ "$WEB_STATUS" = "running" ] 2>/dev/null; then
  if docker exec "$WEB_CONTAINER" php -r "
    \$h = getenv('DB_HOST') ?: 'mysql';
    \$d = getenv('DB_NAME') ?: 'thinjupz_db';
    \$u = getenv('DB_USER') ?: 'thinquser';
    \$p = getenv('DB_PASS') ?: 'thinqpass';
    try {
      new PDO(\"mysql:host=\$h;dbname=\$d\", \$u, \$p, [PDO::ATTR_TIMEOUT => 5]);
      exit(0);
    } catch (Exception \$e) { exit(1); }
  " 2>/dev/null; then
    pass "Web container can connect to MySQL"
  else
    fail "Web cannot connect to MySQL (check DB_* env and network)"
  fi
else
  warn "Skipped (web not running)"
fi
echo ""

# --- 6. HTTP response from web ---
echo "--- 4. HTTP response from web ---"
if [ "$WEB_STATUS" = "running" ] 2>/dev/null; then
  CODE=""
  WEB_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$WEB_CONTAINER" 2>/dev/null | tr -d '\n')
  if [ -n "$WEB_IP" ]; then
    CODE=$(curl -s -o /dev/null -w '%{http_code}' --connect-timeout 5 "http://${WEB_IP}/" 2>/dev/null || echo "000")
  fi
  CODE="${CODE:0:3}"
  if [ "$CODE" = "200" ] || [ "$CODE" = "302" ] || [ "$CODE" = "301" ]; then
    pass "HTTP GET to web container returns $CODE"
  elif [ -z "$CODE" ] || [ "$CODE" = "000" ]; then
    # Host often can't reach container IP on Coolify; try from inside container
    CODE2=$(docker exec "$WEB_CONTAINER" curl -s -o /dev/null -w '%{http_code}' --connect-timeout 3 http://127.0.0.1/ 2>/dev/null || echo "000")
    CODE2="${CODE2:0:3}"
    if [ "$CODE2" = "200" ] || [ "$CODE2" = "302" ] || [ "$CODE2" = "301" ]; then
      pass "HTTP GET to web container returns $CODE2 (from inside container; host cannot reach container IP)"
    else
      # Fallback: PHP can reach localhost (no curl in image)
      if docker exec "$WEB_CONTAINER" php -r "\$c=@stream_context_create(['http'=>['timeout'=>3]]); exit(@file_get_contents('http://127.0.0.1/',false,\$c)!==false?0:1);" 2>/dev/null; then
        pass "HTTP GET to web container OK (from inside container via PHP; host cannot reach container IP)"
      else
        fail "No HTTP response from web (timeout or connection refused)"
      fi
    fi
  else
    warn "HTTP GET returned $CODE (expected 200/302)"
  fi
else
  warn "Skipped (web not running)"
fi
echo ""

# --- 7. Startup order (web started after mysql) – best practice ---
echo "--- 5. Startup order (best practice) ---"
if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${MYSQL_CONTAINER}$" && \
   docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${WEB_CONTAINER}$"; then
  MYSQL_STARTED=$(docker inspect -f '{{.State.StartedAt}}' "$MYSQL_CONTAINER" 2>/dev/null || echo "0001-01-01")
  WEB_STARTED=$(docker inspect -f '{{.State.StartedAt}}' "$WEB_CONTAINER" 2>/dev/null || echo "9999-12-31")
  if [ -n "$MYSQL_STARTED" ] && [ -n "$WEB_STARTED" ]; then
    # Compare timestamps (format: 2026-02-11T15:58:57.123456789Z)
    if [ "$WEB_STARTED" \> "$MYSQL_STARTED" ] 2>/dev/null || [ "$MYSQL_STARTED" = "0001-01-01" ]; then
      pass "Web started after MySQL (good for depends_on: service_healthy)"
    else
      warn "Web started at or before MySQL – ensure docker-compose uses depends_on mysql condition: service_healthy"
    fi
  fi
fi
echo ""

# --- 8. Traefik/Coolify proxy reachable (optional) ---
echo "--- 6. Proxy (Traefik) ---"
PROXY=$(docker ps -a --format '{{.Names}}' 2>/dev/null | grep -E 'traefik|coolify-proxy' | head -1 || true)
if [ -n "$PROXY" ]; then
  PSTATUS=$(docker inspect -f '{{.State.Status}}' "$PROXY" 2>/dev/null || echo "unknown")
  if [ "$PSTATUS" = "running" ]; then
    pass "Proxy container '$PROXY' is running"
  else
    warn "Proxy '$PROXY' status: $PSTATUS"
  fi
else
  warn "No Traefik/Coolify proxy container found by name"
fi
echo ""

# --- Summary ---
echo "=============================================="
echo " Summary"
echo "=============================================="
echo -e " ${GREEN}PASS: $PASS${NC}  ${RED}FAIL: $FAIL${NC}  ${YELLOW}WARN: $WARN${NC}"
echo ""

if [ "$FAIL" -gt 0 ]; then
  echo "Fix any [FAIL] items to avoid gateway timeouts. Re-run this script after changes or next deploy."
  exit 1
fi

if [ "$WARN" -gt 0 ]; then
  echo "Review [WARN] items; configuration is likely OK but worth checking."
  exit 0
fi

echo "Configuration looks good for avoiding gateway timeout after deploy."
exit 0
