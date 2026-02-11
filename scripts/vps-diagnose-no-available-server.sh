#!/usr/bin/env bash
#
# VPS "No available server" Diagnostic Script
# For Coolify + Traefik: checks proxy config, router labels, and web container.
#
# Run on your VPS (where Coolify runs):
#   curl -sSL https://raw.githubusercontent.com/wastwagon/ThinqShop/main/scripts/vps-diagnose-no-available-server.sh | bash
# Or copy the script to the VPS and run:
#   chmod +x vps-diagnose-no-available-server.sh && ./vps-diagnose-no-available-server.sh
#

set -e
REPORT_DIR="${REPORT_DIR:-./no-available-server-report}"
mkdir -p "$REPORT_DIR"
REPORT_FILE="$REPORT_DIR/diagnostic-$(date +%Y%m%d-%H%M%S).txt"
exec > >(tee "$REPORT_FILE") 2>&1

echo "=============================================="
echo " No available server – Coolify/Traefik diagnostic"
echo " $(date -Iseconds 2>/dev/null || date)"
echo "=============================================="
echo ""

# --- 1. Proxy container (Traefik / Coolify) ---
echo "--- 1. PROXY CONTAINER (Traefik/Coolify) ---"
PROXY_CONTAINER=""
for name in coolify-proxy traefik coolify_traefik proxy; do
  if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${name}$"; then
    PROXY_CONTAINER="$name"
    break
  fi
done
if [ -z "$PROXY_CONTAINER" ]; then
  # Fallback: any running container using traefik image
  PROXY_CONTAINER=$(docker ps --filter "ancestor=traefik" --format '{{.Names}}' 2>/dev/null | head -1)
fi
if [ -z "$PROXY_CONTAINER" ]; then
  PROXY_CONTAINER=$(docker ps --format '{{.Names}} {{.Image}}' 2>/dev/null | grep -i traefik | head -1 | awk '{print $1}')
fi
if [ -z "$PROXY_CONTAINER" ]; then
  echo "No standard proxy name found. All running containers:"
  docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Image}}\t{{.Ports}}"
  echo ""
  echo "If your proxy has another name, run: PROXY_CONTAINER=that_name $0"
else
  echo "Proxy container: $PROXY_CONTAINER"
  echo "Status: $(docker inspect -f '{{.State.Status}}' "$PROXY_CONTAINER" 2>/dev/null)"
  echo "Networks: $(docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$PROXY_CONTAINER" 2>/dev/null)"
  echo ""
  echo "Last 40 lines of proxy log (look for 'no available server', 'error', 'router'):"
  docker logs "$PROXY_CONTAINER" --tail 40 2>&1 || true
fi
echo ""

# --- 2. Coolify network ---
echo "--- 2. COOLIFY NETWORK ---"
if docker network ls --format '{{.Name}}' | grep -q '^coolify$'; then
  echo "Network 'coolify' exists."
  echo "Containers on coolify network:"
  docker network inspect coolify --format '{{range .Containers}}{{.Name}} {{end}}' 2>/dev/null || true
else
  echo "WARNING: Network 'coolify' NOT FOUND. Traefik cannot reach your app without it."
  echo "Existing networks:"
  docker network ls --format "table {{.Name}}\t{{.Driver}}"
fi
echo ""

# --- 3. Containers with Traefik labels (your app) ---
echo "--- 3. CONTAINERS WITH TRAEFIK ENABLED ---"
echo "Looking for containers with label traefik.enable=true..."
TRAEFIK_CONTAINERS=$(docker ps -a --filter "label=traefik.enable=true" --format '{{.Names}}' 2>/dev/null || true)
if [ -z "$TRAEFIK_CONTAINERS" ]; then
  echo "None found. Listing all containers (Names + Status):"
  docker ps -a --format "table {{.Names}}\t{{.Status}}"
else
  for c in $TRAEFIK_CONTAINERS; do
    echo ""
    echo "Container: $c"
    echo "  Status: $(docker inspect -f '{{.State.Status}}' "$c" 2>/dev/null)"
    echo "  Networks: $(docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$c" 2>/dev/null)"
    ON_COOLIFY="no"
    docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$c" 2>/dev/null | grep -q 'coolify' && ON_COOLIFY="yes"
    echo "  On 'coolify' network: $ON_COOLIFY"
    echo "  All Traefik-related labels:"
    docker inspect "$c" --format '{{range $k, $v := .Config.Labels}}{{$k}}={{$v}}{{"\n"}}{{end}}' 2>/dev/null | grep -i traefik || true
    echo "  All labels (full):"
    docker inspect "$c" --format '{{range $k, $v := .Config.Labels}}{{$k}}={{$v}}{{"\n"}}{{end}}' 2>/dev/null || true
  done
fi
echo ""

# --- 4. Router rule check (empty Host = cause of 'no available server') ---
echo "--- 4. ROUTER RULES (Host / PathPrefix) ---"
for c in $TRAEFIK_CONTAINERS; do
  echo "Container: $c"
  RULES=$(docker inspect "$c" --format '{{range $k, $v := .Config.Labels}}{{$k}}={{$v}}{{"\n"}}{{end}}' 2>/dev/null | grep 'traefik.http.routers.*\.rule=' || true)
  if [ -z "$RULES" ]; then
    echo "  No router rule labels found."
  else
    echo "$RULES" | while read -r line; do echo "  $line"; done
    echo "$RULES" | grep -q 'Host(``)' && echo "  >>> PROBLEM: Empty Host() detected. This causes 'no available server' or bad routing."
    echo "$RULES" | grep -q 'PathPrefix.*https://' && echo "  >>> PROBLEM: PathPrefix should not contain full URL."
  fi
  echo ""
done
echo ""

# --- 5. Service / loadbalancer port ---
echo "--- 5. TRAEFIK SERVICE / LOADBALANCER PORT ---"
for c in $TRAEFIK_CONTAINERS; do
  echo "Container: $c"
  docker inspect "$c" --format '{{range $k, $v := .Config.Labels}}{{$k}}={{$v}}{{"\n"}}{{end}}' 2>/dev/null | grep -E 'traefik\.http\.(services|routers)' || true
  echo ""
done
echo ""

# --- 6. Web container reachability (port 80) ---
echo "--- 6. WEB CONTAINER LISTENING ON PORT 80? ---"
for c in $TRAEFIK_CONTAINERS; do
  echo "Container: $c"
  docker exec "$c" cat /etc/apache2/ports.conf 2>/dev/null | head -5 || true
  docker exec "$c" ss -tlnp 2>/dev/null | grep -E ':80|:443' || docker exec "$c" netstat -tlnp 2>/dev/null | grep -E ':80|:443' || echo "  (could not list ports)"
  echo ""
done
echo ""

# --- 7. Traefik API / dashboard (if enabled) ---
echo "--- 7. QUICK FIX CHECKLIST ---"
echo "1. Web container must be on network 'coolify' (same as proxy)."
echo "2. Labels must include: traefik.enable=true"
echo "3. Router rule must be Host(\`thinqshopping.app\`) and Host(\`www.thinqshopping.app\`) – NOT Host(``)."
echo "4. Service must set loadbalancer.server.port=80 (or the port Apache listens on)."
echo "5. If Coolify overwrote labels, redeploy after pushing docker-compose.yml with correct labels."
echo ""
echo "Report saved to: $REPORT_FILE"
echo "=============================================="
