# 🚀 Quick Start - Docker Commands

## Start Development Environment
```bash
docker-compose up -d
```
✅ Starts: PHP, MySQL, phpMyAdmin, Redis  
⏱️ First time: 2-5 minutes | After: 10-30 seconds

---

## Access Your Application
- **Website**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081 (root/root)
- **Database**: localhost:3306

---

## Stop Development Environment
```bash
docker-compose down
```

---

## View Logs (if something's wrong)
```bash
docker-compose logs -f
```

---

## Check Status
```bash
docker-compose ps
```
Should show 4 containers running ✅

---

## Restart Everything
```bash
docker-compose restart
```

---

## Database Credentials

### For Application (.env):
```
DB_HOST=mysql
DB_NAME=thinjupz_db
DB_USER=thinquser
DB_PASSWORD=thinqpass
```

### For phpMyAdmin:
```
Username: root
Password: root
```

---

## Common Issues

### Port 8080 already in use?
Change in `docker-compose.yml`:
```yaml
ports:
  - "8090:80"  # Use 8090 instead
```

### Database not importing?
```bash
docker-compose logs mysql
```

### Website not loading?
```bash
docker-compose restart web
```

---

## Full Reset (⚠️ Deletes database data!)
```bash
docker-compose down -v
docker-compose up -d
```

---

## That's It! 🎉

**Start**: `docker-compose up -d`  
**Stop**: `docker-compose down`  
**Access**: http://localhost:8080

For detailed guide, see: `DOCKER_SETUP.md`
