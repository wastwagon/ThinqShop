#!/bin/bash
# =============================================================================
# PASTE THIS ENTIRE BLOCK INTO YOUR VPS TERMINAL (as root or user with docker)
# It creates and runs the Coolify/Traefik "no available server" diagnostic.
# =============================================================================
cat << 'ENDOFSCRIPT' > /tmp/coolify-traefik-diagnose.sh
#!/usr/bin/env bash
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

echo "--- 1. PROXY CONTAINER (Traefik/Coolify) ---"
PROXY_CONTAINER=""
for name in coolify-proxy traefik coolify_traefik proxy; do
  if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q "^${name}$"; then
    PROXY_CONTAINER="$name"
    break
  fi
done
if [ -z "$PROXY_CONTAINER" ]; then
  PROXY_CONTAINER=$(docker ps --filter "ancestor=traefik" --format '{{.Names}}' 2>/dev/null | head -1)
fi
if [ -z "$PROXY_CONTAINER" ]; then
  PROXY_CONTAINER=$(docker ps --format '{{.Names}} {{.Image}}' 2>/dev/null | grep -i traefik | head -1 | awk '{print $1}')
fi
if [ -z "$PROXY_CONTAINER" ]; then
  echo "No standard proxy name found. All running containers:"
  docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Image}}\t{{.Ports}}"
  echo ""
  echo "Set proxy manually: PROXY_CONTAINER=your_proxy_name $0"
else
  echo "Proxy container: $PROXY_CONTAINER"
  echo "Status: $(docker inspect -f '{{.State.Status}}' "$PROXY_CONTAINER" 2>/dev/null)"
  echo "Networks: $(docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$PROXY_CONTAINER" 2>/dev/null)"
  echo ""
  echo "Last 40 lines of proxy log:"
  docker logs "$PROXY_CONTAINER" --tail 40 2>&1 || true
fi
echo ""

echo "--- 2. COOLIFY NETWORK ---"
if docker network ls --format '{{.Name}}' | grep -q '^coolify$'; then
  echo "Network 'coolify' exists."
  echo "Containers on coolify network:"
  docker network inspect coolify --format '{{range .Containers}}{{.Name}} {{end}}' 2>/dev/null || true
else
  echo "WARNING: Network 'coolify' NOT FOUND."
  docker network ls --format "table {{.Name}}\t{{.Driver}}"
fi
echo ""

echo "--- 3. CONTAINERS WITH TRAEFIK ENABLED ---"
TRAEFIK_CONTAINERS=$(docker ps -a --filter "label=traefik.enable=true" --format '{{.Names}}' 2>/dev/null || true)
if [ -z "$TRAEFIK_CONTAINERS" ]; then
  echo "None found. All containers:"
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
    echo "  Traefik labels:"
    docker inspect "$c" --format '{{range $k, $v := .Config.Labels}}{{$k}}={{$v}}{{"\n"}}{{end}}' 2>/dev/null | grep -i traefik || true
    echo "  All labels:"
    docker inspect "$c" --format '{{range $k, $v := .Config.Labels}}{{$k}}={{$v}}{{"\n"}}{{end}}' 2>/dev/null || true
  done
fi
echo ""

echo "--- 4. ROUTER RULES ---"
for c in $TRAEFIK_CONTAINERS; do
  echo "Container: $c"
  RULES=$(docker inspect "$c" --format '{{range $k, $v := .Config.Labels}}{{$k}}={{$v}}{{"\n"}}{{end}}' 2>/dev/null | grep 'traefik.http.routers.*\.rule=' || true)
  if [ -z "$RULES" ]; then echo "  No router rule labels."; else echo "$RULES" | while read -r line; do echo "  $line"; done; fi
  echo "$RULES" | grep -q 'Host(``)' 2>/dev/null && echo "  >>> PROBLEM: Empty Host()"
  echo ""
done
echo ""

echo "--- 5. SERVICE / LOADBALANCER PORT ---"
for c in $TRAEFIK_CONTAINERS; do
  echo "Container: $c"
  docker inspect "$c" --format '{{range $k, $v := .Config.Labels}}{{$k}}={{$v}}{{"\n"}}{{end}}' 2>/dev/null | grep -E 'traefik\.http\.(services|routers)' || true
  echo ""
done
echo ""

echo "--- 6. WEB CONTAINER PORT 80? ---"
for c in $TRAEFIK_CONTAINERS; do
  echo "Container: $c"
  (docker exec "$c" ss -tlnp 2>/dev/null || docker exec "$c" netstat -tlnp 2>/dev/null) | grep -E ':80|:443' || echo "  (skip or not Apache)" || true
  echo ""
done

echo "--- 7. CHECKLIST ---"
echo "1. Web container must be on network 'coolify'."
echo "2. Router rule: Host(\`thinqshopping.app\`) and Host(\`www.thinqshopping.app\`) – NOT Host(``)."
echo "3. Service loadbalancer.server.port=80."
echo ""
echo "Report saved to: $REPORT_FILE"
echo "=============================================="
ENDOFSCRIPT
chmod +x /tmp/coolify-traefik-diagnose.sh
echo "Running diagnostic..."
/tmp/coolify-traefik-diagnose.sh
echo ""
echo "Done. Report also in: $(pwd)/no-available-server-report/"
