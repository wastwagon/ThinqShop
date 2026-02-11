# Two Sites on Same VPS – Shared Proxy Fix (Coolify)

Your VPS runs **two sites** behind one **coolify-proxy** (Traefik):

1. **ThinQShopping** – thinqshopping.app (PHP/Apache/MySQL, this repo)
2. **Juelle Hair** – juellehairgh.com (NestJS/PostgreSQL, different app)

They **do not** slow each other down directly. The problem is **shared proxy configuration**: both apps have **broken Traefik rules**, so both can get gateway errors or bad routing until the proxy config is fixed.

---

## What the proxy logs show

### 1. Empty Host and wrong PathPrefix (main cause)

Traefik is receiving **invalid rules** from Coolify:

- **ThinQShopping**  
  - `Host(``)` → **empty host**  
  - `PathPrefix(` https://www.thinqshopp`)` → **full URL used as path** (with space and truncated)  
  So the router for ThinQ is misconfigured.

- **Juelle Hair**  
  - `Host(``)` → **empty host**  
  - `PathPrefix(`www.juellehairgh.com`)` or `PathPrefix(`juellehairgh.com`)` → **domain used as path**  
  So the router for Juelle Hair is also misconfigured.

When `Host()` is empty, Traefik cannot match requests by domain and may fail or fall back in a way that leads to 502/504 or wrong backend.

### 2. Other log lines (secondary)

- **ACME “Cannot retrieve the ACME challenge”**  
  Let’s Encrypt is trying to validate your domains, but the HTTP challenge request is being served the wrong path (e.g. `install.php`, `wp-login.php`) instead of `/.well-known/acme-challenge/...`. That often happens when domain/path in Coolify are wrong (same root cause as above).

- **“Error while peeking client hello bytes” (i/o timeout)**  
  TLS handshake timeouts from a client IP. Can be slow clients or network; less critical than the rule errors.

- **2 zombie processes**  
  Unrelated to the two sites; can be reaped by a reboot or by fixing the parent process.

---

## Does one site affect the other?

- **Performance**: They share CPU/RAM on the same server, so under heavy load one could affect the other. Your current numbers (e.g. 15% memory, low load) don’t suggest that.
- **Stability**: The **same proxy** is used for both. **Broken Traefik rules** (empty Host, wrong PathPrefix) affect **both** apps. Fixing the proxy configuration fixes routing (and often timeouts) for **both**.

So: fix the **shared proxy configuration** first; that addresses the main cause for both sites.

---

## Fix in Coolify (both applications)

Coolify is sending **domain/path** in a way that produces:

- Empty `Host(...)`
- PathPrefix set to a full URL or domain instead of a path

You need to correct the **domain** (and optionally path) so Traefik gets valid rules.

### For each application in Coolify

1. Open the **ThinQShopping** service in Coolify.
2. Go to **Domain / FQDN** (or similar) and set:
   - **Domain**: `thinqshopping.app` (and add `www.thinqshopping.app` if you use www).
   - **No** `https://`, **no** path (e.g. no `/` or `/app`) unless you really want a path prefix.
3. Save and **Redeploy** (or restart the proxy) so Traefik reloads labels.

Repeat for **Juelle Hair**:

1. Open the **Juelle Hair** service.
2. Set **Domain** to `juellehairgh.com` and, if needed, `www.juellehairgh.com`.
3. No `https://`, no path unless required.
4. Save and Redeploy.

### What you should see after fix

In Traefik, rules should look like:

- `Host(`thinqshopping.app`)` and `Host(`www.thinqshopping.app`)` for ThinQ.
- `Host(`juellehairgh.com`)` and `Host(`www.juellehairgh.com`)` for Juelle Hair.

**No** `Host(``)` and **no** `PathPrefix(`https://...`)` or `PathPrefix(`domain.com`)`.

If Coolify has a field like “Path” or “URL” that currently contains `https://www.thinqshopping.app` or the domain name, clear it or set it to a path-only value (e.g. `/` or leave empty). The **domain** field should contain only the hostname(s).

---

## Why fixing the domain in the UI didn't fix it

Coolify has a **known bug** ([GitHub #6877](https://github.com/coollabsio/coolify/issues/6877)): it generates **malformed** Traefik labels (empty `Host()`, domain in `PathPrefix`) regardless of what you enter in "Domains for web". So correcting the domain does **not** fix routing. Your deploy logs also show `SERVICE_FQDN_WEB=` (empty); Coolify uses that for `Host()`, so you get `Host(``)` and "empty args for matcher Host".

---

## Fix that works: correct labels in the compose Coolify uses

Coolify deploys from your **GitHub** repo (`wastwagon/ThinqShop`) and runs `docker compose -f .../docker-compose.yml up -d` **without** rewriting the compose. So the **Traefik labels in `docker-compose.yml`** are what get applied.

This repo's `docker-compose.yml` already has the correct overrides for ThinQ. For them to take effect on the VPS, **that same compose must be in the repo Coolify pulls from**.

### Steps to apply the fix

1. **Push the fixed `docker-compose.yml` to the repo Coolify uses**  
   If you develop in `thingappmobile-enhancement` and deploy from `wastwagon/ThinqShop`, push the current `docker-compose.yml` (with the Traefik labels) to the **main** branch of `wastwagon/ThinqShop`.

2. **Redeploy in Coolify**  
   Trigger a **Redeploy** so Coolify pulls the latest commit and runs the updated compose.

3. **Confirm**  
   Visit `https://thinqshopping.app` and `https://www.thinqshopping.app`. On the VPS:  
   `docker inspect <web-container-name> | grep traefik.http.routers.*rule`  
   You should see `Host(\`thinqshopping.app\`)` and `Host(\`www.thinqshopping.app\`)`, not `Host(``)`.

### If Coolify overwrites labels later

- Fix the **domain** (and path) in the **Coolify UI** as above; then redeploy.
- If Coolify has a "Custom Traefik labels" or "Advanced" section, add the rule overrides there.
- See [Coolify #6877](https://github.com/coollabsio/coolify/issues/6877) for a workaround (Traefik dynamic config or script after each deploy).

After the proxy rules are correct, both sites should route properly. If timeouts continue, use the diagnostic and log-capture scripts for the app that still times out.

---

## Deployment build failed: exit code 255

If Coolify shows **Deployment failed: Command execution failed (exit code 255)** during `docker compose build`, the exit code is generic (something went wrong), not a specific error. Common causes:

1. **Out of memory (OOM)** on the VPS during build (PHP image + apt + composer can use a lot of RAM).  
   - **Fix:** Add swap, or increase memory; in Coolify you can try building with fewer parallel jobs if available.  
   - On the VPS: `free -h` and check if the build fails when memory is full.

2. **Composer install failing** (network timeout, rate limit, or a dependency requiring a missing PHP extension).  
   - **Fix:** In Coolify deployment logs, scroll to the **last lines** before the failure to see the actual error (e.g. composer message or a failed `RUN` step).  
   - The Dockerfile uses `composer install --no-interaction --prefer-dist`; if you see a composer error, fix the dependency or add the required PHP extension in the Dockerfile.

3. **Docker daemon or disk** (disk full, or Docker socket/daemon issue).  
   - On the VPS: `df -h` and `docker system df`; clean with `docker system prune -a` if needed (removes unused images).

4. **Build context too large** (e.g. huge `assets/` or `node_modules/` sent to the daemon).  
   - Ensure `.dockerignore` excludes unneeded folders (e.g. `node_modules/`, `vendor/`, `.git/`). This repo’s `.dockerignore` already does that.

**What to do:** In Coolify, open the **full deployment log**, scroll to the bottom, and look for the **last error or failed step** (e.g. “RUN composer install …” or “COPY . .”). That line (and the few above it) usually identifies the real cause. Then apply the fix above or adjust the Dockerfile/compose accordingly.
