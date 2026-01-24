# ThinQShopping - URL Reference Guide

## 🌐 **Base URL Configuration**

The base URL is configured in `.env` file:
```
APP_URL=http://localhost/ThinQShopping
```

**For Production (cPanel):**
```
APP_URL=https://yourdomain.com
```

---

## 🏠 **Frontend URLs**

### **Homepage / Main Site:**
```
http://localhost/ThinQShopping/
or
http://localhost/ThinQShopping/index.php
```

### **Key Frontend Pages:**
- **Shop/Products:** `/shop.php`
- **Product Detail:** `/product-detail.php?slug={product-slug}`
- **Cart:** `/modules/ecommerce/cart/`
- **Checkout:** `/modules/ecommerce/checkout/`
- **Register:** `/register.php`
- **User Login:** `/login.php` ✅
- **Track Order/Transfer/Parcel:** `/public/track.php`

### **Service Pages:**
- **Money Transfer Form:** `/modules/money-transfer/transfer-form/`
- **Money Transfer (Receive from China):** `/modules/money-transfer/receive-from-china/`
- **Logistics Booking:** `/modules/logistics/booking/`
- **Procurement Request:** `/modules/procurement/request/`

---

## 👤 **User URLs**

### **User Login:**
```
http://localhost/ThinQShopping/login.php
```

### **User Registration:**
```
http://localhost/ThinQShopping/register.php
```

### **User Dashboard:**
```
http://localhost/ThinQShopping/user/dashboard.php
```

### **User Account Pages:**
- **Profile:** `/user/profile.php`
- **Orders:** `/user/orders/`
- **Order Details:** `/user/orders/view.php?id={order_id}`
- **Money Transfers:** `/user/transfers/`
- **Shipments:** `/user/shipments/`
- **Procurement:** `/user/procurement/`
- **Wallet:** `/user/wallet.php`

### **User Logout:**
```
http://localhost/ThinQShopping/logout.php
```

---

## 🔐 **Admin URLs**

### **Admin Login:**
```
http://localhost/ThinQShopping/admin/login.php
```

### **Admin Dashboard:**
```
http://localhost/ThinQShopping/admin/dashboard.php
```

### **Admin Management Pages:**
- **Products:** `/admin/ecommerce/products.php`
- **Product Edit:** `/admin/ecommerce/products-edit.php?id={product_id}`
- **Orders:** `/admin/ecommerce/orders.php`
- **Order View:** `/admin/ecommerce/orders.php?id={order_id}`
- **Money Transfers:** `/admin/money-transfer/transfers.php`
- **Shipments:** `/admin/logistics/shipments.php`
- **Procurement:** `/admin/procurement/requests.php`
- **Users:** `/admin/users/manage.php`
- **Payments:** `/admin/payments/transactions.php`
- **Settings:** `/admin/settings/general.php`

### **Admin Logout:**
```
http://localhost/ThinQShopping/admin/logout.php
```

---

## 📋 **Quick URL Summary**

### **Main URLs:**

| Type | URL |
|------|-----|
| **Frontend Homepage** | `/` or `/index.php` |
| **User Login** | `/login.php` ✅ |
| **User Registration** | `/register.php` |
| **Admin Login** | `/admin/login.php` ✅ |
| **User Dashboard** | `/user/dashboard.php` |
| **Admin Dashboard** | `/admin/dashboard.php` |

---

## 🔧 **URL Configuration**

### **Local Development (XAMPP):**
```
http://localhost/ThinQShopping/
http://localhost/ThinQShopping/login.php
http://localhost/ThinQShopping/admin/login.php
```

### **Production (cPanel):**
```
https://yourdomain.com/
https://yourdomain.com/login.php
https://yourdomain.com/admin/login.php
```

### **Configuration File:**
The base URL is set in:
- `.env` file: `APP_URL=http://localhost/ThinQShopping`
- Or defaults to: `http://localhost/ThinQShopping` if `.env` not configured

---

## 🔗 **Important Notes:**

1. **All URLs use `BASE_URL` constant** from `config/constants.php`
2. **BASE_URL** is read from `.env` file or defaults to localhost
3. **Trailing slashes** are automatically handled
4. **URLs are relative** to the BASE_URL, so they work in any environment
5. **Login redirects** automatically handle return URLs

---

## 📱 **Public Tracking URLs:**

- **Unified Tracking:** `/public/track.php`
- **Track Money Transfer:** `/public/track-transfer.php?token={token}`
- **Track Parcel:** `/public/track-parcel.php?tracking={tracking_number}`

---

## ✅ **Default Credentials** (if set in database):

**Admin Login:** `/admin/login.php`
- Username: (check database admin_users table)
- Password: (check database admin_users table)

**User Login:** `/login.php`
- Email: (user registered email)
- Password: (user registered password)

---

**Note:** Make sure to update `APP_URL` in `.env` file when deploying to production!

