# Permanent fix: thinqshopping.app after every deploy

Coolify overwrites Traefik labels on deploy, so the site can show "no available server" after each push. Use **one** of the options below so you can commit, push, and deploy without running a script each time.

---

## Option 1: Traefik dynamic config (recommended)

Add a **dynamic configuration** in Coolify so Traefik always routes thinqshopping.app and www.thinqshopping.app to your app, regardless of container labels.

### Steps

1. In **Coolify**, go to **Server** → **Proxy** → **Dynamic Configurations** (sidebar).
2. Click **Add** (or **New**).
3. **Name:** e.g. `thinqshopping-app`.
4. **Content:** paste the YAML from this repo: **`coolify/traefik-thinqshopping-app.yaml`** (or copy below).
5. Save. Traefik will reload automatically.

### YAML to paste

```yaml
http:
  routers:
    thinq-app-https:
      rule: "Host(`thinqshopping.app`)"
      entryPoints:
        - https
      service: thinq-web@docker
      tls:
        certResolver: letsencrypt
    thinq-www-https:
      rule: "Host(`www.thinqshopping.app`)"
      entryPoints:
        - https
      service: thinq-web@docker
      tls:
        certResolver: letsencrypt
    thinq-app-http:
      rule: "Host(`thinqshopping.app`)"
      entryPoints:
        - http
      service: thinq-web@docker
    thinq-www-http:
      rule: "Host(`www.thinqshopping.app`)"
      entryPoints:
        - http
      service: thinq-web@docker
```

### How it works

- The config defines **routers** that match your domains and use the **service** `thinq-web@docker`.
- That service is created by Traefik’s Docker provider from your ThinQ web container (label `traefik.http.services.thinq-web.loadbalancer.server.port=80`).
- On every deploy, the container is recreated but keeps that service label, so the dynamic config keeps working.

### If the site still returns 503

The Docker service name might be different (e.g. with a project prefix). Check the **Traefik dashboard** (if enabled) or run on the VPS:

```bash
docker exec coolify-proxy wget -qO- http://localhost:8080/api/http/services 2>/dev/null | jq -r 'keys[]' | grep -i thinq
```

Use the name shown (e.g. `lsosss448cg4o84kgsksw0o8-thinq-web@docker`) in the YAML as `service: <that-name>` for all four routers, then update the dynamic config in Coolify and save again.

---

## Option 2: Custom labels in Coolify (if available)

If your Coolify version lets you add **custom Docker/Traefik labels** for the application, you can override the broken ones so every deploy gets the correct rules.

1. Open the **ThinQShopping** application in Coolify.
2. Find **Custom labels**, **Docker labels**, **Advanced**, or **Traefik** in the app or service settings.
3. Add these labels (exact keys; values with backticks as below):

| Key | Value |
|-----|--------|
| `traefik.http.routers.http-0-lsosss448cg4o84kgsksw0o8-web.rule` | `Host(\`thinqshopping.app\`)` |
| `traefik.http.routers.http-1-lsosss448cg4o84kgsksw0o8-web.rule` | `Host(\`www.thinqshopping.app\`)` |
| `traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.rule` | `Host(\`thinqshopping.app\`)` |
| `traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.entrypoints` | `https` |
| `traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.service` | `thinq-web` |
| `traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.tls` | `true` |
| `traefik.http.routers.https-0-lsosss448cg4o84kgsksw0o8-web.tls.certresolver` | `letsencrypt` |
| `traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.rule` | `Host(\`www.thinqshopping.app\`)` |
| `traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.entrypoints` | `https` |
| `traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.service` | `thinq-web` |
| `traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.tls` | `true` |
| `traefik.http.routers.https-1-lsosss448cg4o84kgsksw0o8-web.tls.certresolver` | `letsencrypt` |

4. Save and redeploy the app (or restart the proxy if needed).

If Coolify merges custom labels after generated ones, the site will work after every deploy. If your UI does not have a custom-labels field, use Option 1.

---

## Option 3: Run fix script after each deploy (fallback)

If you prefer not to use the dynamic config or custom labels, run the fix script on the VPS after each deploy.

1. Save the script once on the VPS, e.g.:
   ```bash
   # Copy content from scripts/vps-fix-thinqshopping-app-only-paste.sh into:
   nano /root/fix-thinq.sh
   chmod +x /root/fix-thinq.sh
   ```
2. After each **Deploy** in Coolify, SSH in and run:
   ```bash
   /root/fix-thinq.sh
   ```

This is not automatic but avoids editing the proxy; use Option 1 for a set-once permanent fix.

---

## Summary

| Option | Effort | Permanent? |
|--------|--------|------------|
| **1. Dynamic config** | Add one YAML in Coolify Proxy | Yes – survives all deploys |
| **2. Custom labels** | Add labels in app settings (if UI allows) | Yes – applied on every deploy |
| **3. Fix script** | Run `/root/fix-thinq.sh` after each deploy | No – manual each time |

Recommendation: use **Option 1** (Traefik dynamic config) so you can commit, push, and deploy without any extra steps.
