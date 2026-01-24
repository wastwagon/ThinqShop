# Docker Local Development Setup Guide
## ThinQShopping Platform

---

## 🐳 **What This Docker Setup Includes**

### **Services:**
1. **Web Server** (PHP 8.1 + Apache)
   - Runs your PHP application
   - Access: http://localhost:8080

2. **MySQL Database** (MySQL 8.0)
   - Your database with all data
   - Port: 3306
   - Auto-imports `thinjupz_db.sql`

3. **phpMyAdmin** (Database Manager)
   - Visual database management
   - Access: http://localhost:8081
   - Login: root / root

4. **Redis** (Cache - Optional)
   - For future performance optimization
   - Port: 6379

---

## 📋 **Prerequisites**

Before starting, make sure you have:
- ✅ Docker Desktop installed
- ✅ Docker Desktop is running
- ✅ `thinjupz_db.sql` file in project root
- ✅ Terminal/Command Prompt access

---

## 🚀 **Quick Start Guide**

### **Step 1: Start Docker Desktop**
Make sure Docker Desktop is running on your computer.

### **Step 2: Open Terminal**
Navigate to your project folder:
```bash
cd /Users/OceanCyber/Downloads/thingappmobile-enhancement
```

### **Step 3: Start All Services**
```bash
docker-compose up -d
```

**What this does:**
- Downloads required Docker images (first time only)
- Creates containers for PHP, MySQL, phpMyAdmin, Redis
- Imports your database automatically
- Starts all services in background

**First time**: Takes 2-5 minutes (downloading images)  
**After that**: Takes 10-30 seconds

### **Step 4: Wait for Database Import**
```bash
# Check if database is ready
docker-compose logs mysql | grep "ready for connections"
```

When you see "ready for connections", your database is imported!

### **Step 5: Access Your Application**
Open browser and go to:
- **Website**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Database**: localhost:3306

---

## 🔑 **Database Credentials**

### **For Application (.env file):**
```
DB_HOST=mysql
DB_NAME=thinjupz_db
DB_USER=thinquser
DB_PASSWORD=thinqpass
```

### **For phpMyAdmin:**
```
Server: mysql
Username: root
Password: root
```

---

## 📝 **Common Commands**

### **Start Services**
```bash
docker-compose up -d
```

### **Stop Services**
```bash
docker-compose down
```

### **View Logs**
```bash
# All services
docker-compose logs

# Specific service
docker-compose logs web
docker-compose logs mysql
```

### **Restart Services**
```bash
docker-compose restart
```

### **Rebuild (if you change docker-compose.yml)**
```bash
docker-compose up -d --build
```

### **Stop and Remove Everything**
```bash
docker-compose down -v
```
⚠️ **Warning**: This deletes database data!

---

## 🔧 **Troubleshooting**

### **Problem: Port Already in Use**

**Error**: "Port 8080 is already allocated"

**Solution 1**: Stop other services using that port
```bash
# On Mac/Linux
lsof -ti:8080 | xargs kill -9

# On Windows
netstat -ano | findstr :8080
taskkill /PID <PID_NUMBER> /F
```

**Solution 2**: Change port in `docker-compose.yml`
```yaml
web:
  ports:
    - "8090:80"  # Change 8080 to 8090
```

### **Problem: Database Not Importing**

**Check logs:**
```bash
docker-compose logs mysql
```

**Manually import:**
```bash
docker exec -i thinqshopping_mysql mysql -uroot -proot thinjupz_db < thinjupz_db.sql
```

### **Problem: Can't Access Website**

**Check if containers are running:**
```bash
docker-compose ps
```

**Should show:**
```
NAME                        STATUS
thinqshopping_web          Up
thinqshopping_mysql        Up
thinqshopping_phpmyadmin   Up
thinqshopping_redis        Up
```

**Restart if needed:**
```bash
docker-compose restart web
```

### **Problem: PHP Extensions Missing**

**Install additional extensions:**
```bash
docker-compose exec web docker-php-ext-install gd zip
docker-compose restart web
```

---

## 🗂️ **File Structure**

```
/thingappmobile-enhancement/
├── docker-compose.yml          # Docker configuration
├── .env.local                  # Local environment variables
├── .env                        # Production environment (don't commit!)
├── thinjupz_db.sql            # Your database schema
├── config/
│   └── database.php           # Database connection
├── user/                       # User pages
├── admin/                      # Admin pages (via subdirectories)
└── ... (rest of your files)
```

---

## 🔄 **Development Workflow**

### **Daily Workflow:**

1. **Start Docker**
   ```bash
   docker-compose up -d
   ```

2. **Make Changes**
   - Edit PHP files
   - Changes appear immediately (no restart needed)

3. **Test in Browser**
   - http://localhost:8080

4. **Check Database**
   - http://localhost:8081 (phpMyAdmin)

5. **Stop Docker** (when done)
   ```bash
   docker-compose down
   ```

---

## 📊 **Accessing Services**

### **Website**
```
URL: http://localhost:8080
```

### **phpMyAdmin**
```
URL: http://localhost:8081
Server: mysql
Username: root
Password: root
```

### **Database (from app)**
```php
// In your config/database.php
$host = 'mysql';  // NOT 'localhost'!
$dbname = 'thinjupz_db';
$username = 'thinquser';
$password = 'thinqpass';
```

### **Database (external tools like MySQL Workbench)**
```
Host: localhost (or 127.0.0.1)
Port: 3306
Username: root
Password: root
Database: thinjupz_db
```

---

## 🎯 **Testing Checklist**

After starting Docker, test these:

### **1. Website Loads**
- [ ] http://localhost:8080 shows homepage
- [ ] No errors in browser console
- [ ] Images load correctly

### **2. Database Connection**
- [ ] Can access phpMyAdmin
- [ ] See all tables (46 tables)
- [ ] Data is present

### **3. User Features**
- [ ] User registration works
- [ ] Login/logout works
- [ ] Can browse products
- [ ] Cart functionality
- [ ] Checkout process

### **4. Admin Features**
- [ ] Admin login works
- [ ] Dashboard loads
- [ ] Can view orders
- [ ] Can manage products

---

## 🔐 **Environment Variables**

### **Update config/database.php**

Change from:
```php
$host = 'localhost';
```

To:
```php
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'thinjupz_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
```

This allows switching between Docker and production easily!

---

## 📦 **Database Backup & Restore**

### **Backup Database**
```bash
docker exec thinqshopping_mysql mysqldump -uroot -proot thinjupz_db > backup_$(date +%Y%m%d).sql
```

### **Restore Database**
```bash
docker exec -i thinqshopping_mysql mysql -uroot -proot thinjupz_db < backup_20260120.sql
```

---

## 🚀 **Next Steps**

After Docker is running:

1. ✅ **Test Current System**
   - Verify everything works
   - Test all features
   - Check for errors

2. ✅ **Apply Modernization**
   - Review CSS changes
   - Test updated pages
   - Get your feedback

3. ✅ **Prepare for Production**
   - Backup production
   - Deploy changes
   - Monitor

---

## ❓ **Need Help?**

### **Check Container Status**
```bash
docker-compose ps
```

### **View Real-time Logs**
```bash
docker-compose logs -f
```

### **Access Container Shell**
```bash
# Web container
docker-compose exec web bash

# MySQL container
docker-compose exec mysql bash
```

### **Reset Everything**
```bash
docker-compose down -v
docker-compose up -d
```

---

## 🎉 **You're Ready!**

Your Docker development environment is set up with:
- ✅ PHP 8.1 + Apache
- ✅ MySQL 8.0 with your data
- ✅ phpMyAdmin for database management
- ✅ Redis for caching (future)
- ✅ Auto-import of database
- ✅ Hot-reload (changes appear immediately)

**Start developing with confidence!** 🚀
