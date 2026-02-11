#!/usr/bin/env bash
#
# VPS Gateway Timeout Diagnostic Script
# For ThinQShopping on Coolify (Traefik + PHP/Apache)
#
# Run on your VPS: bash vps-diagnose-gateway-timeout.sh
# Or: chmod +x scripts/vps-diagnose-gateway-timeout.sh && ./scripts/vps-diagnose-gateway-timeout.sh
#

set -e
REPORT_DIR="${REPORT_DIR:-./gateway-timeout-report}"
mkdir -p "$REPORT_DIR"
REPORT_FILE="$REPORT_DIR/diagnostic-$(date +%Y%m%d-%H%M%S).txt"
exec > >(tee -a "$REPORT_FILE") 2>&1

echo "=============================================="
echo " Gateway Timeout Diagnostic Report"
echo " $(date -Iseconds 2>/dev/null || date)"
echo "=============================================="
echo ""

# --- 1. System overview ---
echo "--- 1. SYSTEM OVERVIEW ---"
echo "Uptime: $(uptime 2>/dev/null || echo 'N/A')"
echo "Load: $(cat /proc/loadavg 2>/dev/null || echo 'N/A')"
echo "Memory:"
free -h 2>/dev/null || echo "free not available"
echo ""

# --- 2. Docker & containers ---
echo "--- 2. DOCKER & CONTAINERS ---"
if ! command -v docker &>/dev/null; then
    echo "Docker not found. Is Docker installed and in PATH?"
else
    docker --version
    echo ""
    echo "Running containers:"
    docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || docker ps -a
    echo ""
    echo "Container resource usage (last 5s):"
    docker stats --no-stream 2>/dev/null || echo "docker stats not available"
fi
echo ""

# --- 3. Web container (PHP/Apache) ---
WEB_CONTAINER="${WEB_CONTAINER:-thinqshopping_web}"
echo "--- 3. WEB CONTAINER: $WEB_CONTAINER ---"
if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${WEB_CONTAINER}$"; then
    echo "Status: $(docker inspect -f '{{.State.Status}}' "$WEB_CONTAINER" 2>/dev/null)"
    echo "Started: $(docker inspect -f '{{.State.StartedAt}}' "$WEB_CONTAINER" 2>/dev/null)"
    echo ""
    echo "PHP/Apache config (relevant for timeouts):"
    docker exec "$WEB_CONTAINER" php -i 2>/dev/null | grep -E "max_execution_time|memory_limit|default_socket_timeout" || true
    echo ""
    echo "Apache MPM and Timeout:"
    docker exec "$WEB_CONTAINER" cat /etc/apache2/apache2.conf 2>/dev/null | grep -E "Timeout|KeepAlive|MaxRequestWorkers" || true
    echo ""
    echo "Last 30 lines of web container log (startup/migration):"
    docker logs "$WEB_CONTAINER" --tail 30 2>&1 || true
else
    echo "Container '$WEB_CONTAINER' not found. Set WEB_CONTAINER=your_web_container_name if different."
fi
echo ""

# --- 4. MySQL connectivity from web ---
echo "--- 4. MYSQL CONNECTIVITY FROM WEB CONTAINER ---"
if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${WEB_CONTAINER}$"; then
    echo "Testing DB connection from web container..."
    docker exec "$WEB_CONTAINER" php -r "
    \$h = getenv('DB_HOST') ?: 'mysql';
    \$d = getenv('DB_NAME') ?: 'thinjupz_db';
    \$u = getenv('DB_USER') ?: 'thinquser';
    \$p = getenv('DB_PASS') ?: 'thinqpass';
    \$t = microtime(true);
    try {
        new PDO(\"mysql:host=\$h;dbname=\$d\", \$u, \$p, [PDO::ATTR_TIMEOUT => 5]);
        echo 'OK - Connected in ' . round((microtime(true)-\$t)*1000) . ' ms' . PHP_EOL;
    } catch (Exception \$e) {
        echo 'FAIL: ' . \$e->getMessage() . PHP_EOL;
        exit(1);
    }
    " 2>/dev/null || echo "Could not run PHP in container (container may be down)"
else
    echo "Skipped (web container not found)"
fi
echo ""

# --- 5. Traefik / Coolify proxy ---
echo "--- 5. PROXY (TRAEFIK/COOLIFY) ---"
PROXY_CONTAINER=""
for name in traefik coolify-proxy caddy; do
    if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "$name"; then
        PROXY_CONTAINER=$(docker ps -a --format '{{.Names}}' 2>/dev/null | grep "$name" | head -1)
        break
    fi
done
if [ -n "$PROXY_CONTAINER" ]; then
    echo "Proxy container: $PROXY_CONTAINER"
    docker logs "$PROXY_CONTAINER" --tail 50 2>&1 || true
else
    echo "No Traefik/Coolify proxy container found by name (traefik, coolify-proxy, caddy)."
    echo "Listing all containers again for manual check:"
    docker ps -a --format "{{.Names}}" 2>/dev/null || true
fi
echo ""

# --- 6. Disk & network ---
echo "--- 6. DISK SPACE ---"
df -h 2>/dev/null || true
echo ""
echo "--- 7. RECENT APACHE ERRORS (inside web container) ---"
if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${WEB_CONTAINER}$"; then
    docker exec "$WEB_CONTAINER" tail -50 /var/log/apache2/error.log 2>/dev/null || echo "No error log or container not running"
fi
echo ""

# --- 8. Recommendations ---
echo "=============================================="
echo " RECOMMENDATIONS FOR GATEWAY TIMEOUT"
echo "=============================================="
echo ""
echo "1) Increase proxy timeout (Coolify/Traefik)"
echo "   In your app's docker-compose or Coolify service labels, add:"
echo '   - "traefik.http.middlewares.timeout-headers.headers.customrequestheaders.X-Forwarded-Timeout=300"'
echo "   Or in Traefik static config set:"
echo "   transport.respondingTimeouts.readTimeout=300s"
echo ""
echo "2) Increase PHP execution time (in Dockerfile or php.ini):"
echo "   RUN echo 'max_execution_time = 300' >> /usr/local/etc/php/conf.d/timeouts.ini"
echo "   RUN echo 'default_socket_timeout = 60' >> /usr/local/etc/php/conf.d/timeouts.ini"
echo ""
echo "3) Apache Timeout (in Dockerfile):"
echo "   RUN echo 'Timeout 300' >> /etc/apache2/conf-available/timeout.conf"
echo "   RUN a2enconf timeout"
echo ""
echo "4) Startup: Your web container runs auto_migrate on every start (up to 50s wait for DB)."
echo "   If MySQL is slow to start, the app will not accept requests until migration finishes."
echo "   Consider: healthcheck on MySQL before starting web, or run migrations in a one-off job."
echo ""
echo "5) Slow queries: Homepage runs multiple queries with subqueries. Consider adding indexes"
echo "   (e.g. on product_reviews.product_id, is_approved) and caching (Redis) for heavy pages."
echo ""
echo "Report saved to: $REPORT_FILE"
echo "Done."
