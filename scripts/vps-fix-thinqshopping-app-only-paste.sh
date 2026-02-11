#!/bin/bash
# =============================================================================
# PASTE THIS ENTIRE BLOCK ON YOUR VPS (root). Fixes "no available server" for
# thinqshopping.app only by recreating the ThinQ web container with correct
# Traefik Host() rules.
# =============================================================================
apt-get update -qq && apt-get install -y -qq jq >/dev/null 2>&1
WEB_NAME=$(docker ps --format '{{.Names}}' | grep -E '^web-lsosss448cg4o84kgsksw0o8-' | head -1)
if [ -z "$WEB_NAME" ]; then echo "ThinQ web container not found."; exit 1; fi
echo "Fixing thinqshopping.app for container: $WEB_NAME"
IMAGE=$(docker inspect -f '{{.Config.Image}}' "$WEB_NAME")
RESTART=$(docker inspect -f '{{.HostConfig.RestartPolicy.Name}}' "$WEB_NAME"); NETWORKS=$(docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$WEB_NAME")
LABELS_JSON=$(docker inspect -f '{{json .Config.Labels}}' "$WEB_NAME")
LABELS_JSON=$(echo "$LABELS_JSON" | jq -c '
  .["traefik.http.routers.http-0-lsosss448cg4o84kgsksw0o8-web.rule"] = "Host(`thinqshopping.app`)"
  | .["traefik.http.routers.http-1-lsosss448cg4o84kgsksw0o8-web.rule"] = "Host(`www.thinqshopping.app`)"
  | .["traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.rule"] = "Host(`thinqshopping.app`)"
  | .["traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.entrypoints"] = "https"
  | .["traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.service"] = "thinq-web"
  | .["traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.tls"] = "true"
  | .["traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.tls.certresolver"] = "letsencrypt"
  | .["traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.rule"] = "Host(`www.thinqshopping.app`)"
  | .["traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.entrypoints"] = "https"
  | .["traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.service"] = "thinq-web"
  | .["traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.tls"] = "true"
  | .["traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.tls.certresolver"] = "letsencrypt"
')
LABEL_ARGS=()
while IFS= read -r line; do
  key="${line%%=*}"; val="${line#*=}"
  LABEL_ARGS+=(--label "${key}=${val}")
done < <(echo "$LABELS_JSON" | jq -r 'to_entries[] | "\(.key)=\(.value)"')
ENV_OPTS=()
while IFS= read -r e; do [ -n "$e" ] && ENV_OPTS+=(-e "$e"); done < <(docker inspect -f '{{json .Config.Env}}' "$WEB_NAME" | jq -r '.[]')
docker stop "$WEB_NAME" 2>/dev/null || true
docker rm "$WEB_NAME" 2>/dev/null || true
NET_LIST=($NETWORKS)
FIRST_NET="${NET_LIST[0]:-coolify}"
docker run -d --name "$WEB_NAME" --restart "$RESTART" --network "$FIRST_NET" "${ENV_OPTS[@]}" "${LABEL_ARGS[@]}" "$IMAGE" sh -c "php /var/www/html/database/auto_migrate_enhanced.php && apache2-foreground"
for i in $(seq 1 $((${#NET_LIST[@]} - 1))); do docker network connect "${NET_LIST[$i]}" "$WEB_NAME" 2>/dev/null || true; done
echo "Done. Test: curl -sI https://thinqshopping.app"
