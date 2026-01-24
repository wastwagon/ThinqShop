# ThinQShopping Platform

A comprehensive multi-service platform for Ghana offering E-Commerce, Ghana-China Money Transfer, Logistics & Parcel Delivery, and Procurement services.

## 🚀 Features

- **E-Commerce Service** - Full-featured online store with product management, shopping cart, and order tracking
- **Money Transfer** - Secure token-based money transfer system between Ghana and China
- **Logistics & Delivery** - Door-to-door parcel delivery with real-time tracking
- **Procurement Service** - Request-based procurement system for items from China
- **Unified Wallet** - Single wallet system across all services
- **Paystack Integration** - Payment gateway supporting cards, mobile money, and bank transfers
- **Mobile-First Design** - Responsive design optimized for mobile devices
- **KeyCDN Integration** - Fast content delivery via KeyCDN

## 📋 Requirements

- **PHP:** 7.4 or higher (8.x recommended)
- **MySQL:** 5.7 or higher (8.0 recommended)
- **Apache:** With mod_rewrite enabled
- **XAMPP:** For local development (Mac, Windows, or Linux)
- **cPanel Shared Hosting:** For production deployment

## 🛠️ Local Development Setup (XAMPP)

### Step 1: Install XAMPP

If you haven't already, download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)

### Step 2: Clone/Copy Project

1. Copy the entire `ThinQShopping` folder to your XAMPP `htdocs` directory:
   - **Mac:** `/Applications/XAMPP/htdocs/ThinQShopping`
   - **Windows:** `C:\xampp\htdocs\ThinQShopping`
   - **Linux:** `/opt/lampp/htdocs/ThinQShopping`

### Step 3: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service

### Step 4: Configure PHP

Edit `php.ini` file (usually in `/Applications/XAMPP/xamppfiles/etc/php.ini` on Mac):

Enable these extensions:
```ini
extension=pdo_mysql
extension=gd
extension=curl
extension=openssl
extension=mbstring
extension=session
```

### Step 5: Create Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database: `thinqshopping_db`
3. Import the schema:
   - Click on `thinqshopping_db` database
   - Go to **Import** tab
   - Choose file: `database/schema.sql`
   - Click **Go**

### Step 6: Configure Environment

1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` file and update these values:
   ```env
   DB_HOST=localhost
   DB_NAME=thinqshopping_db
   DB_USER=root
   DB_PASS=
   
   APP_URL=http://localhost/ThinQShopping
   
   PAYSTACK_PUBLIC_KEY=your_test_public_key
   PAYSTACK_SECRET_KEY=your_test_secret_key
   PAYSTACK_MODE=test
   ```

### Step 7: Access Application

Open your browser and navigate to:
```
http://localhost/ThinQShopping
```

### Step 8: Default Admin Login

After importing the database, you can login with:
- **Username:** `admin`
- **Password:** `admin123` (⚠️ **CHANGE THIS IMMEDIATELY!**)

Admin login URL: `http://localhost/ThinQShopping/admin/login.php`

## 📁 Project Structure

```
ThinQShopping/
├── assets/              # Static assets (CSS, JS, images)
├── config/              # Configuration files
├── database/            # Database schema and migrations
├── includes/            # Reusable PHP includes
├── modules/             # Service-specific modules
│   ├── ecommerce/
│   ├── money-transfer/
│   ├── logistics/
│   └── procurement/
├── admin/               # Admin dashboard
├── user/                # User dashboard
├── api/                 # API endpoints
├── email-templates/     # Email templates
└── index.php           # Homepage
```

## 🔧 Configuration

### Paystack Setup

1. Sign up at [Paystack](https://paystack.com)
2. Get your API keys from the dashboard
3. Update `.env` file with your keys
4. Use test keys for development

### KeyCDN Setup

1. Sign up at [KeyCDN](https://www.keycdn.com) (14-day free trial)
2. Create a Pull Zone
3. Configure origin URL
4. Update `.env` file:
   ```env
   KEYCDN_API_KEY=your_api_key
   KEYCDN_ZONE_ID=your_zone_id
   CDN_URL=https://your-zone-hexid.kxcdn.com
   CDN_ENABLED=true
   ```

### Email Configuration

Update SMTP settings in `.env`:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
SMTP_FROM_EMAIL=noreply@thinqshopping.com
```

For Gmail, you'll need to:
1. Enable 2-Factor Authentication
2. Generate an App Password
3. Use the app password in `SMTP_PASS`

## 🚀 Production Deployment (cPanel)

### Step 1: Upload Files

1. Upload all files to `public_html` (or your domain's root directory)
2. Maintain the directory structure

### Step 2: Create Database

1. Go to cPanel → MySQL Databases
2. Create a new database: `yourusername_thinqshopping_db`
3. Create a database user
4. Assign user to database with ALL PRIVILEGES

### Step 3: Import Database

1. Go to phpMyAdmin in cPanel
2. Select your database
3. Import `database/schema.sql`

### Step 4: Configure Environment

1. Create `.env` file in root directory
2. Update with production values:
   ```env
   DB_HOST=localhost
   DB_NAME=yourusername_thinqshopping_db
   DB_USER=yourusername_dbuser
   DB_PASS=your_database_password
   
   APP_URL=https://yourdomain.com
   APP_ENV=production
   APP_DEBUG=false
   
   PAYSTACK_MODE=live
   PAYSTACK_PUBLIC_KEY=your_live_public_key
   PAYSTACK_SECRET_KEY=your_live_secret_key
   ```

### Step 5: Set Permissions

Set proper file permissions:
- Folders: `755`
- Files: `644`
- `.env` file: `600` (more secure)

### Step 6: Configure KeyCDN

1. Set up KeyCDN Pull Zone pointing to your domain
2. Update `CDN_URL` in `.env`
3. Set `CDN_ENABLED=true`

### Step 7: SSL Certificate

1. Install SSL certificate (Let's Encrypt free via cPanel)
2. Force HTTPS in `.htaccess` (uncomment HTTPS redirect)

## 🔒 Security Checklist

- [ ] Change default admin password
- [ ] Use strong database passwords
- [ ] Enable HTTPS/SSL
- [ ] Set `.env` file permissions to 600
- [ ] Disable `APP_DEBUG` in production
- [ ] Regularly update PHP and MySQL
- [ ] Keep backups of database
- [ ] Use Paystack webhook signature verification

## 📱 Mobile-First Design

The platform is built with mobile-first principles:
- Bottom navigation menu for mobile devices
- Responsive layouts for all screen sizes
- Touch-friendly buttons and interactions
- Optimized images for fast loading

## 🧪 Testing

### Local Testing

1. Test all payment flows (use Paystack test mode)
2. Test file uploads
3. Test email sending
4. Test database operations
5. Test on different browsers
6. Test on mobile devices

### Paystack Test Cards

- **Successful:** 4084 0840 8408 4081
- **Insufficient Funds:** 4084 0840 8408 4085
- **Expired Card:** 4084 0840 8408 4089

## 📚 Documentation

- Full project documentation: `PROJECT_REVIEW_AND_RECOMMENDATIONS.md`
- Paystack API: [https://paystack.com/docs](https://paystack.com/docs)
- KeyCDN API: [https://www.keycdn.com/api](https://www.keycdn.com/api)
- Bootstrap 5: [https://getbootstrap.com/docs/5.3/](https://getbootstrap.com/docs/5.3/)

## 🤝 Support

For support, contact:
- **Email:** <?php echo BUSINESS_EMAIL; ?>
- **Phone:** <?php echo BUSINESS_PHONE; ?>
- **WhatsApp:** <?php echo BUSINESS_WHATSAPP; ?>

## 📝 License

Proprietary - All rights reserved

## 🔄 Updates & Maintenance

- Keep PHP and MySQL updated
- Regularly backup database
- Monitor KeyCDN usage
- Review Paystack transactions
- Update dependencies as needed

---

**Built with ❤️ for Ghana**

