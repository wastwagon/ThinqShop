# Coolify deploy – thinqshopping.app

## Permanent fix (do once)

**Use this so you can deploy frequently without running a script every time.**

→ See **[COOLIFY_PERMANENT_FIX.md](COOLIFY_PERMANENT_FIX.md)** and apply **Option 1 (Traefik dynamic config)**:

1. In Coolify: **Server** → **Proxy** → **Dynamic Configurations** → **Add**.
2. Paste the YAML from **`coolify/traefik-thinqshopping-app.yaml`** (or from the doc).
3. Save. Traefik will route thinqshopping.app and www.thinqshopping.app on every deploy.

After that, you can commit, push, and deploy as usual; the site will stay up.

---

## If you haven’t set the permanent fix yet

After each Coolify deploy the site may show “no available server” until you either:

- Apply the **permanent fix** above, or  
- Run the **fix script** on the VPS (see [COOLIFY_PERMANENT_FIX.md](COOLIFY_PERMANENT_FIX.md) Option 3).

Script (run on VPS): use **`scripts/vps-fix-thinqshopping-app-only-paste.sh`** or the full paste block in COOLIFY_PERMANENT_FIX.md.

---

## Gateway timeout right after deploy

If the site returns **504 Gateway Timeout** for 1–2 minutes after a deploy, the web container is often still starting: it runs the DB migration and only then starts Apache. The repo’s **docker-compose** now makes the web service wait for **MySQL to be healthy** before starting, so the migration usually finishes quickly and Apache starts sooner. If you still see a short timeout, wait 1–2 minutes and reload.

**Verify config on the VPS:** run `bash scripts/vps-verify-no-gateway-timeout.sh` (from the repo or with `WEB_CONTAINER` / `MYSQL_CONTAINER` set if your names differ). It checks MySQL healthcheck, web listening on 80, and startup order.

---

## What’s in this repo

| File | Purpose |
|------|--------|
| **COOLIFY_PERMANENT_FIX.md** | Permanent fix options (dynamic config, custom labels, script). |
| **coolify/traefik-thinqshopping-app.yaml** | Traefik dynamic config to add in Coolify Proxy. |
| **scripts/vps-fix-thinqshopping-app-only-paste.sh** | One-off fix script (HTTP + HTTPS) if you haven’t set the permanent fix. |
| **scripts/vps-verify-no-gateway-timeout.sh** | Run on VPS to verify config (MySQL health, web listening, startup order). |
| **docker-compose.yml** | Correct labels; Coolify overwrites them, so the permanent fix is still needed. |
