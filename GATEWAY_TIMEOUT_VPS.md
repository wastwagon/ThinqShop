# Gateway Timeout on VPS (Coolify) – Diagnosis & Fix

Your app runs on Coolify with **Traefik** as the reverse proxy and **PHP 8.1 + Apache** in Docker. A **504 Gateway Timeout** means the proxy gave up waiting for a response from your web container.

## Quick diagnosis on the VPS

From your project directory on the VPS (or wherever the app is deployed):

```bash
# One-off full diagnostic (containers, PHP/Apache config, DB connectivity, proxy logs)
bash scripts/vps-diagnose-gateway-timeout.sh
```

Report is written to `./gateway-timeout-report/diagnostic-<timestamp>.txt`.

## Capture logs while the timeout happens

Run this **before** you trigger the timeout (e.g. open the site and wait for 504), then stop with **Ctrl+C** after the error occurs:

```bash
# Capture for 10 minutes (default)
bash scripts/vps-capture-timeout-logs.sh

# Capture for 30 minutes
bash scripts/vps-capture-timeout-logs.sh 30
```

Logs are saved under `./gateway-timeout-report/logs-<timestamp>/`:

- **proxy.log** – Traefik/Coolify (look for 504, timeout, backend errors)
- **web-container.log** – Apache/PHP stdout
- **apache-error.log** – Apache error log (PHP errors, timeouts)
- **mysql.log** – MySQL (connection/slow query issues)
- **docker-stats.log** – CPU/memory every 30s

If your container names differ (e.g. Coolify-generated names), set them:

```bash
WEB_CONTAINER=your_web_container_name \
MYSQL_CONTAINER=your_mysql_container_name \
bash scripts/vps-diagnose-gateway-timeout.sh
```

## What usually causes this in your stack

1. **Proxy timeout too short**  
   Traefik’s default read timeout can be 90s or less. If PHP/Apache take longer (e.g. slow DB or heavy page), the proxy returns 504.

2. **PHP / Apache limits**  
   `max_execution_time` or Apache `Timeout` may be low; the script prints current values.

3. **Slow or failing MySQL**  
   If the DB is slow or not ready at startup, requests can hang. The diagnostic script tests DB connectivity from the web container.

4. **Startup delay**  
   The web container runs `database/auto_migrate_enhanced.php` on every start and waits up to 50 seconds for MySQL. Until that finishes, Apache isn’t ready and the proxy can time out on early requests.

5. **Heavy homepage queries**  
   `index.php` runs several queries with subqueries (reviews, deals, trending). Without indexes or under load, they can exceed the proxy timeout.

## Fixes to apply

### 1. Increase proxy (Traefik) timeout

In Coolify, for this service, add a label or middleware so the proxy waits longer, e.g.:

- **Responding timeouts** in Traefik (if you have access to Traefik static/dynamic config): set `respondingTimeouts.readTimeout` to something like `300s`.

Or in your app’s **docker-compose** (if Coolify uses it), you can add a middleware and attach it to the router, for example:

```yaml
labels:
  - "traefik.http.middlewares.app-timeout.forwardauth.responseForwarding.flushInterval=1s"
  # And in Traefik static config: transport.respondingTimeouts.readTimeout=300s
```

Coolify’s UI may expose “Timeout” or “Read timeout” for the service; set it to **300** (seconds) and redeploy.

### 2. Increase PHP and Apache timeouts (in Dockerfile)

Add before the final `EXPOSE 80`:

```dockerfile
# Timeouts to avoid 504 when pages are slow
RUN echo 'max_execution_time = 300' >> /usr/local/etc/php/conf.d/timeouts.ini
RUN echo 'default_socket_timeout = 60' >> /usr/local/etc/php/conf.d/timeouts.ini
RUN echo 'Timeout 300' >> /etc/apache2/conf-available/timeout.conf && a2enconf timeout
```

Then rebuild and redeploy the web service.

### 3. Ensure MySQL is ready before accepting traffic

- In Coolify/Docker, set a **healthcheck** on the MySQL service and make the web service **depend on** that health (e.g. `depends_on: mysql` with healthcheck).
- Optionally run migrations as a one-off job instead of on every web container start, so the first request isn’t blocked by migration.

### 4. Optimize slow pages

- Add indexes (e.g. on `product_reviews(product_id, is_approved)`).
- Consider caching heavy homepage data (e.g. Redis) so some requests don’t hit the DB at all.

After changes, run the diagnostic script again and, if timeouts persist, use the log-capture script while reproducing the 504 and inspect `proxy.log` and `apache-error.log` for the exact moment of the timeout.
