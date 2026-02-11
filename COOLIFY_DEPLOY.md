# Coolify deploy – fix “no available server”

Coolify has a bug that overwrites Traefik labels and causes **“no available server”** for thinqshopping.app. The app will deploy, but the site will not load until you apply the fix below.

## After each Coolify deploy (do one of these)

### Option 1: Run fix script on the VPS (recommended)

SSH into your VPS and run:

```bash
apt-get update && apt-get install -y jq
cd /root   # or where you have the repo
# If you have the repo cloned, run:
./scripts/vps-fix-thinq-traefik-labels.sh -y
# Or paste the script from scripts/vps-fix-thinq-traefik-labels.sh and run it.
```

Then open https://thinqshopping.app and https://www.thinqshopping.app to confirm.

### Option 2: Add custom labels in Coolify UI

In Coolify → **ThinQShopping** app → find **Custom labels** / **Docker labels** / **Advanced** and add:

| Key | Value |
|-----|--------|
| `traefik.http.routers.http-0-lsosss448cg4o84kgsksw0o8-web.rule` | `Host(\`thinqshopping.app\`)` |
| `traefik.http.routers.http-1-lsosss448cg4o84kgsksw0o8-web.rule` | `Host(\`www.thinqshopping.app\`)` |

Save and redeploy (or restart the proxy). If your Coolify version applies these labels, the site will work after every deploy.

---

## What’s in this repo

- **docker-compose.yml** – Contains correct Traefik labels; Coolify overwrites them at deploy time, so the fix above is still required.
- **COOLIFY_TWO_SITES_FIX.md** – Full explanation of the Coolify/Traefik bug and both fix options.
- **scripts/vps-fix-thinq-traefik-labels.sh** – Recreates the web container with correct labels (requires `jq` on the VPS).
- **scripts/vps-diagnose-no-available-server.sh** – Run on the VPS to inspect proxy and container labels.
