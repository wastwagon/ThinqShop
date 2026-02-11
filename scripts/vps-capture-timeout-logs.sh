#!/usr/bin/env bash
#
# VPS Log Capture Script - Capture logs when gateway timeouts occur
# For ThinQShopping on Coolify (Traefik + PHP/Apache)
#
# Run on VPS and leave running while you reproduce the timeout.
# After a timeout, stop with Ctrl+C and inspect the saved logs.
#
# Usage:
#   ./scripts/vps-capture-timeout-logs.sh              # capture for 10 minutes
#   ./scripts/vps-capture-timeout-logs.sh 30           # capture for 30 minutes
#   CAPTURE_MINUTES=60 ./scripts/vps-capture-timeout-logs.sh
#

WEB_CONTAINER="${WEB_CONTAINER:-thinqshopping_web}"
REPORT_DIR="${REPORT_DIR:-./gateway-timeout-report}"
CAPTURE_MINUTES="${CAPTURE_MINUTES:-$1}"
CAPTURE_MINUTES="${CAPTURE_MINUTES:-10}"
LOG_DIR="$REPORT_DIR/logs-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$LOG_DIR"

echo "=============================================="
echo " Timeout log capture started"
echo " Duration: $CAPTURE_MINUTES minutes"
echo " Log dir:  $LOG_DIR"
echo " Stop with Ctrl+C when done (or after timeout occurs)"
echo "=============================================="

cleanup() {
    echo ""
    echo "Stopping capture. Logs saved in: $LOG_DIR"
    kill ${TAIL_PIDS[@]} 2>/dev/null || true
    exit 0
}
trap cleanup SIGINT SIGTERM

# Find proxy container
PROXY_CONTAINER=""
for name in traefik coolify-proxy caddy; do
    if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "$name"; then
        PROXY_CONTAINER=$(docker ps -a --format '{{.Names}}' 2>/dev/null | grep "$name" | head -1)
        break
    fi
done

TAIL_PIDS=()

# Web container logs (Apache + migration output)
if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${WEB_CONTAINER}$"; then
    echo "Tailing web container: $WEB_CONTAINER -> $LOG_DIR/web-container.log"
    docker logs -f "$WEB_CONTAINER" 2>&1 >> "$LOG_DIR/web-container.log" &
    TAIL_PIDS+=($!)
    ( while true; do docker exec "$WEB_CONTAINER" tail -20 /var/log/apache2/error.log 2>/dev/null; sleep 5; done ) >> "$LOG_DIR/apache-error.log" 2>&1 &
    TAIL_PIDS+=($!)
fi

# Proxy logs (Traefik/Coolify - gateway timeouts often appear here)
if [ -n "$PROXY_CONTAINER" ]; then
    echo "Tailing proxy: $PROXY_CONTAINER -> $LOG_DIR/proxy.log"
    docker logs -f "$PROXY_CONTAINER" 2>&1 >> "$LOG_DIR/proxy.log" &
    TAIL_PIDS+=($!)
fi

# MySQL container (connection issues, slow queries)
MYSQL_CONTAINER="${MYSQL_CONTAINER:-thinqshopping_mysql}"
if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${MYSQL_CONTAINER}$"; then
    echo "Tailing MySQL: $MYSQL_CONTAINER -> $LOG_DIR/mysql.log"
    docker logs -f "$MYSQL_CONTAINER" 2>&1 >> "$LOG_DIR/mysql.log" &
    TAIL_PIDS+=($!)
fi

# Snapshot of container stats every 30 seconds
(
    for i in $(seq 1 $((CAPTURE_MINUTES * 2))); do
        echo "=== $(date -Iseconds) ===" >> "$LOG_DIR/docker-stats.log"
        docker stats --no-stream 2>/dev/null >> "$LOG_DIR/docker-stats.log"
        sleep 30
    done
) &
TAIL_PIDS+=($!)

# Optional: one-off diagnostic at start
echo "Running one-off diagnostic..." >> "$LOG_DIR/diagnostic-snapshot.txt"
(
    echo "--- at $(date -Iseconds) ---"
    docker ps -a
    echo ""
    docker stats --no-stream 2>/dev/null || true
) >> "$LOG_DIR/diagnostic-snapshot.txt" 2>&1

echo ""
echo "Capturing... (Ctrl+C to stop)"
sleep $((CAPTURE_MINUTES * 60))
cleanup
