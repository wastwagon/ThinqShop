# ThinQShopping - Login Credentials

## 🔐 **Default Admin Login**

**URL:** `http://localhost/ThinQShopping/admin/login.php`

**Credentials:**
- **Username:** `admin`
- **Password:** `admin123`

⚠️ **IMPORTANT:** Change this password immediately after first login!

---

## 👤 **User Login**

**URL:** `http://localhost/ThinQShopping/login.php`

**Existing User:**
- **Email:** `flygonpriest@oceancyber.net`
- **Password:** (Your registered password)

**To Create New User:**
1. Go to: `http://localhost/ThinQShopping/register.php`
2. Fill in registration form
3. Use your email and create a password

---

## 📝 **How to Change Admin Password**

1. Login to admin panel
2. Go to Settings or Profile section
3. Update password (if feature available)
4. Or update directly in database:

```sql
-- Generate new password hash
-- Use PHP: password_hash('your_new_password', PASSWORD_BCRYPT)

UPDATE admin_users 
SET password = '$2y$10$NEW_HASH_HERE' 
WHERE username = 'admin';
```

---

## 🔗 **Quick Links**

- **Admin Login:** `/admin/login.php`
- **User Login:** `/login.php`
- **User Registration:** `/register.php`
- **Admin Dashboard:** `/admin/dashboard.php`
- **User Dashboard:** `/user/dashboard.php`

---

## ⚠️ **Security Notes**

1. **Change default admin password immediately**
2. Use strong passwords (min 8 characters, mixed case, numbers, symbols)
3. Never share login credentials
4. Use different passwords for admin and user accounts
5. Enable two-factor authentication if available

---

**Last Updated:** $(date)
