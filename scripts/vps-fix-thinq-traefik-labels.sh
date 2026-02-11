#!/usr/bin/env bash
#
# One-time fix on VPS: recreate ThinQShopping web container with correct Traefik
# Host() rules so the site stops showing "no available server".
# Coolify overwrites labels from docker-compose; this script reapplies the fix.
# Run after each Coolify deploy until you add custom labels in Coolify UI.
#
# Requires: docker, jq (apt-get install -y jq)
# Run as root. Usage: $0 [-y] [container_name]
#

set -e
NONINTERACTIVE=""
[ "${1:-}" = "-y" ] && { NONINTERACTIVE=1; shift; }
WEB_NAME="${1:-}"
if [ -z "$WEB_NAME" ]; then
  WEB_NAME=$(docker ps --format '{{.Names}}' | grep -E '^web-lsosss448cg4o84kgsksw0o8-' | head -1)
fi
if [ -z "$WEB_NAME" ]; then
  echo "ThinQ web container not found. Expected name like web-lsosss448cg4o84kgsksw0o8-*"
  echo "Usage: $0 [-y] [container_name]"
  exit 1
fi
if ! command -v jq &>/dev/null; then
  echo "jq is required. Install with: apt-get update && apt-get install -y jq"
  exit 1
fi

echo "Fixing Traefik labels for container: $WEB_NAME"
echo "This will recreate the container with correct Host(thinqshopping.app) rules."
if [ -z "$NONINTERACTIVE" ]; then
  read -p "Continue? [y/N] " -n 1 -r
  echo
  if [[ ! $REPLY =~ ^[yY]$ ]]; then
    echo "Aborted."
    exit 0
  fi
fi

# Get current config
IMAGE=$(docker inspect -f '{{.Config.Image}}' "$WEB_NAME")
CMD=$(docker inspect -f '{{json .Config.Cmd}}' "$WEB_NAME")
ENV=$(docker inspect -f '{{range .Config.Env}}{{.}} {{end}}' "$WEB_NAME")
RESTART=$(docker inspect -f '{{.HostConfig.RestartPolicy.Name}}' "$WEB_NAME")
NETWORKS=$(docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$WEB_NAME")

# Build label args: same labels but fix the two router rules
LABELS_JSON=$(docker inspect -f '{{json .Config.Labels}}' "$WEB_NAME")
LABELS_JSON=$(echo "$LABELS_JSON" | jq -c '
  .["traefik.http.routers.http-0-lsosss448cg4o84kgsksw0o8-web.rule"] = "Host(`thinqshopping.app`)"
  | .["traefik.http.routers.http-1-lsosss448cg4o84kgsksw0o8-web.rule"] = "Host(`www.thinqshopping.app`)"
')
LABEL_ARGS=()
while IFS= read -r line; do
  key="${line%%=*}"
  val="${line#*=}"
  LABEL_ARGS+=(--label "${key}=${val}")
done < <(echo "$LABELS_JSON" | jq -r 'to_entries[] | "\(.key)=\(.value)"')

# Stop and remove
docker stop "$WEB_NAME" || true
docker rm "$WEB_NAME" || true

# First network for run, rest we connect after
NET_LIST=()
for net in $NETWORKS; do [ -n "$net" ] && NET_LIST+=("$net"); done
FIRST_NET="${NET_LIST[0]:-coolify}"
ENV_OPTS=()
for e in $ENV; do
  [ -n "$e" ] && ENV_OPTS+=(-e "$e")
done
# ThinQ web startup command
CMD_ARGS=(sh -c "php /var/www/html/database/auto_migrate_enhanced.php && apache2-foreground")

echo "Creating new container with fixed labels (network: $FIRST_NET)..."
docker run -d \
  --name "$WEB_NAME" \
  --restart "$RESTART" \
  --network "$FIRST_NET" \
  "${ENV_OPTS[@]}" \
  "${LABEL_ARGS[@]}" \
  "$IMAGE" \
  "${CMD_ARGS[@]}"

# Attach to remaining networks
for i in $(seq 1 $((${#NET_LIST[@]} - 1))); do
  docker network connect "${NET_LIST[$i]}" "$WEB_NAME" 2>/dev/null || true
done

echo "Done. Container $WEB_NAME recreated with correct Traefik rules."
echo "Check: curl -sI http://thinqshopping.app (or open in browser)."
echo "Note: After next Coolify deploy you may need to run this script again, or add Custom Labels in Coolify."
