# ThinQShopping - Complete Setup Guide

## Quick Start Checklist

- [ ] XAMPP installed and running
- [ ] Project files copied to htdocs
- [ ] Apache and MySQL services started
- [ ] Database created in phpMyAdmin
- [ ] Database schema imported
- [ ] .env file created and configured
- [ ] Paystack test keys added
- [ ] Application accessible in browser
- [ ] Admin login tested

## Detailed Setup Instructions

### 1. XAMPP Installation

**Mac:**
```bash
# Download from https://www.apachefriends.org/
# Install .dmg file
# Open XAMPP Control Panel from Applications
```

**Windows:**
```bash
# Download from https://www.apachefriends.org/
# Run installer
# Install to C:\xampp
# Open XAMPP Control Panel
```

**Verify Installation:**
- Open `http://localhost` - Should see XAMPP welcome page
- Open `http://localhost/phpmyadmin` - Should see phpMyAdmin

### 2. Project Setup

**Location:**
- Copy entire `ThinQShopping` folder to:
  - Mac: `/Applications/XAMPP/htdocs/ThinQShopping`
  - Windows: `C:\xampp\htdocs\ThinQShopping`

**Verify:**
- Open `http://localhost/ThinQShopping` - Should see homepage (may show errors if database not set up yet)

### 3. Database Setup

**Create Database:**
1. Open `http://localhost/phpmyadmin`
2. Click **New** in left sidebar
3. Database name: `thinqshopping_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click **Create**

**Import Schema:**
1. Click on `thinqshopping_db` database
2. Click **Import** tab
3. Click **Choose File**
4. Select `database/schema.sql`
5. Click **Go** at bottom
6. Wait for "Import has been successfully finished" message

**Verify:**
- You should see all tables created
- Check `admin_users` table - should have default admin user

### 4. Environment Configuration

**Create .env file:**
```bash
# In project root directory
cp .env.example .env
```

**Edit .env file** with your text editor:
```env
# Database (XAMPP default)
DB_HOST=localhost
DB_NAME=thinqshopping_db
DB_USER=root
DB_PASS=

# Application
APP_URL=http://localhost/ThinQShopping
APP_ENV=development
APP_DEBUG=true

# Paystack (get from https://dashboard.paystack.com/#/settings/developer)
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxxxxxxx
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxx
PAYSTACK_MODE=test

# KeyCDN (optional for now)
CDN_ENABLED=false

# Email (optional for now)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
```

### 5. PHP Configuration

**Locate php.ini:**
- Mac: `/Applications/XAMPP/xamppfiles/etc/php.ini`
- Windows: `C:\xampp\php\php.ini`

**Verify extensions are enabled** (remove semicolon `;` if present):
```ini
extension=pdo_mysql
extension=gd
extension=curl
extension=openssl
extension=mbstring
```

**Restart Apache** after making changes

### 6. Paystack Setup

1. **Sign up:** Go to https://paystack.com and create account
2. **Get Test Keys:**
   - Login to dashboard
   - Go to Settings → API Keys & Webhooks
   - Copy **Test Public Key** and **Test Secret Key**
   - Paste into `.env` file
3. **Set Webhook URL** (for production):
   - URL: `https://yourdomain.com/api/paystack/webhook.php`
   - Select events: All events

### 7. Testing

**Test Homepage:**
- Open: `http://localhost/ThinQShopping`
- Should see homepage without errors

**Test Admin Login:**
- Open: `http://localhost/ThinQShopping/admin/login.php`
- Username: `admin`
- Password: `admin123`
- **⚠️ Change password immediately after first login!**

**Test Database Connection:**
- If you see database errors, check:
  - MySQL service is running
  - Database name matches `.env`
  - User permissions are correct

### 8. Common Issues & Solutions

**Issue: "Database connection failed"**
- ✅ Check MySQL service is running
- ✅ Verify database name in `.env`
- ✅ Check database user has permissions

**Issue: "404 Not Found"**
- ✅ Check file is in correct location
- ✅ Verify Apache is running
- ✅ Check URL path is correct

**Issue: "Call to undefined function"**
- ✅ Check PHP extensions are enabled
- ✅ Restart Apache after php.ini changes

**Issue: "Permission denied"**
- ✅ Check file/folder permissions
- ✅ Ensure Apache user can read files

**Issue: "Paystack errors"**
- ✅ Verify API keys are correct
- ✅ Check you're using test keys in test mode
- ✅ Verify Paystack account is activated

### 9. Production Deployment Checklist

**Pre-Deployment:**
- [ ] Test all features locally
- [ ] Change admin password
- [ ] Update `.env` with production values
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Update database credentials
- [ ] Use Paystack live keys

**Upload:**
- [ ] Upload all files via FTP/cPanel File Manager
- [ ] Maintain directory structure
- [ ] Set file permissions (755 for folders, 644 for files)
- [ ] Set `.env` permissions to 600

**Database:**
- [ ] Export local database
- [ ] Create production database in cPanel
- [ ] Import database via phpMyAdmin
- [ ] Update database credentials in `.env`

**SSL:**
- [ ] Install SSL certificate (Let's Encrypt via cPanel)
- [ ] Uncomment HTTPS redirect in `.htaccess`
- [ ] Update `APP_URL` to use `https://`

**KeyCDN:**
- [ ] Create Pull Zone
- [ ] Configure origin URL
- [ ] Update `CDN_URL` in `.env`
- [ ] Set `CDN_ENABLED=true`

**Testing:**
- [ ] Test homepage loads
- [ ] Test user registration
- [ ] Test payment flow (with test card first)
- [ ] Test admin login
- [ ] Test file uploads
- [ ] Test email sending

### 10. Next Steps

After setup is complete:

1. **Customize:**
   - Update business information in `.env`
   - Add your logo files
   - Customize colors in `assets/css/main.css`

2. **Configure:**
   - Set up email templates
   - Configure shipping zones
   - Set exchange rates
   - Add initial products

3. **Secure:**
   - Change admin password
   - Set secure file permissions
   - Enable HTTPS
   - Regular backups

4. **Develop:**
   - Build additional features
   - Customize design
   - Add more products
   - Configure all services

---

**Need Help?** Check `PROJECT_REVIEW_AND_RECOMMENDATIONS.md` for detailed documentation.

