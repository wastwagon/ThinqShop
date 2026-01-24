# XAMPP Setup Instructions for ThinQShopping

## 🚨 **Current Issue: 404 Error**

If you're seeing a 404 error when accessing `http://localhost/ThinQShopping`, follow these steps:

---

## ✅ **Step 1: Check XAMPP Installation**

1. **Verify XAMPP is installed:**
   - Location: `/Applications/XAMPP/` (Mac) or `C:\xampp\` (Windows)
   - Check if Apache and MySQL are running in XAMPP Control Panel

2. **Start XAMPP Services:**
   - Open XAMPP Control Panel
   - Click **Start** for **Apache**
   - Click **Start** for **MySQL**

---

## ✅ **Step 2: Move Project to XAMPP htdocs**

The project needs to be in XAMPP's `htdocs` directory:

### **On Mac:**
```bash
# Move project to XAMPP htdocs
sudo cp -R /Users/OceanCyber/Downloads/ThinQShopping /Applications/XAMPP/htdocs/

# OR create a symbolic link (recommended - keeps original location)
sudo ln -s /Users/OceanCyber/Downloads/ThinQShopping /Applications/XAMPP/htdocs/ThinQShopping
```

### **On Windows:**
```
Move the ThinQShopping folder from Downloads to:
C:\xampp\htdocs\ThinQShopping
```

### **Verify:**
- After moving, your project should be at: `/Applications/XAMPP/htdocs/ThinQShopping/` (Mac) or `C:\xampp\htdocs\ThinQShopping\` (Windows)
- The `index.php` file should be directly accessible

---

## ✅ **Step 3: Enable Apache Modules**

Make sure these Apache modules are enabled:

1. Open XAMPP Control Panel
2. Click **Config** → **httpd.conf** (or edit manually)
3. Ensure these lines are uncommented (remove `#`):
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   LoadModule php_module modules/libphp.so
   ```
4. Save and restart Apache

---

## ✅ **Step 4: Set Correct Permissions (Mac/Linux)**

```bash
# Navigate to project
cd /Applications/XAMPP/htdocs/ThinQShopping

# Set correct permissions
chmod -R 755 .
chmod -R 777 assets/images/uploads/
chmod -R 777 assets/images/products/
```

---

## ✅ **Step 5: Configure .env File**

1. **Copy the example file:**
   ```bash
   cd /Applications/XAMPP/htdocs/ThinQShopping
   cp .env.example .env
   ```

2. **Edit `.env` file:**
   ```env
   APP_URL=http://localhost/ThinQShopping
   DB_HOST=localhost
   DB_NAME=thinq_shopping
   DB_USER=root
   DB_PASS=
   ```

---

## ✅ **Step 6: Setup Database**

1. **Access phpMyAdmin:**
   - Open browser: `http://localhost/phpmyadmin`
   - Login with username: `root` (password usually empty)

2. **Create Database:**
   ```sql
   CREATE DATABASE thinq_shopping CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Schema:**
   - Click on `thinq_shopping` database
   - Go to **Import** tab
   - Choose file: `database/schema.sql`
   - Click **Go**

---

## ✅ **Step 7: Test Access**

After setup, try accessing:

1. **Homepage:**
   ```
   http://localhost/ThinQShopping/
   ```

2. **User Login:**
   ```
   http://localhost/ThinQShopping/login.php
   ```

3. **Admin Login:**
   ```
   http://localhost/ThinQShopping/admin/login.php
   ```

---

## 🔧 **Alternative: Use Different Port**

If port 80 is busy, use a different port:

1. **Edit Apache config:**
   - Open: `/Applications/XAMPP/etc/httpd.conf`
   - Change: `Listen 80` to `Listen 8080`
   - Restart Apache

2. **Access via:**
   ```
   http://localhost:8080/ThinQShopping/
   ```

---

## 🐛 **Troubleshooting**

### **Issue: Still getting 404**
- ✅ Check Apache is running
- ✅ Verify project is in `htdocs/ThinQShopping/`
- ✅ Check `index.php` exists in project root
- ✅ Try: `http://localhost/ThinQShopping/index.php` (explicit filename)

### **Issue: Database connection error**
- ✅ Check MySQL is running in XAMPP
- ✅ Verify database credentials in `.env`
- ✅ Check database exists in phpMyAdmin

### **Issue: Permission denied**
- ✅ Set correct file permissions (Step 4)
- ✅ Check Apache user has read access

### **Issue: .htaccess not working**
- ✅ Enable mod_rewrite (Step 3)
- ✅ Check AllowOverride is set to All in httpd.conf

---

## 📝 **Quick Setup Checklist**

- [ ] XAMPP installed and running
- [ ] Apache and MySQL services started
- [ ] Project copied to `htdocs/ThinQShopping/`
- [ ] mod_rewrite enabled
- [ ] File permissions set correctly
- [ ] `.env` file created and configured
- [ ] Database created and schema imported
- [ ] Access `http://localhost/ThinQShopping/`

---

## 🎯 **Recommended Setup (Symlink Method)**

If you want to keep working on the project in Downloads folder but make it accessible via XAMPP:

```bash
# Create symlink
sudo ln -s /Users/OceanCyber/Downloads/ThinQShopping /Applications/XAMPP/htdocs/ThinQShopping

# Verify
ls -la /Applications/XAMPP/htdocs/ | grep ThinQShopping
```

This way, changes in Downloads folder are immediately reflected in XAMPP.

---

**After completing these steps, the 404 error should be resolved!**

