<?php
/**
 * Application Constants
 * ThinQShopping Platform
 */

// Load environment variables
require_once __DIR__ . '/env-loader.php';

// Application Info
define('APP_NAME', $_ENV['APP_NAME'] ?? 'ThinQShopping');
// Remove quotes and trailing slashes from APP_URL
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/ThinQShopping';
$appUrl = trim($appUrl, '"\'');
$appUrl = rtrim($appUrl, '/');
define('APP_URL', $appUrl);
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Base Paths
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', APP_URL);
define('ASSETS_URL', BASE_URL . '/assets');

// Asset URLs
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMAGES_URL', ASSETS_URL . '/images');

// File Paths
define('UPLOAD_PATH', BASE_PATH . '/assets/images/uploads/');
define('PRODUCT_IMAGE_PATH', BASE_PATH . '/assets/images/products/');
define('LOGO_PATH', BASE_PATH . '/assets/images/logos/');

// Session
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'thinq_shopping_session');
define('SESSION_LIFETIME', intval($_ENV['SESSION_LIFETIME'] ?? 7200));

// Security
define('CSRF_TOKEN_NAME', $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token');

// Upload Settings
define('UPLOAD_MAX_SIZE', intval($_ENV['UPLOAD_MAX_SIZE'] ?? 5242880)); // 5MB
define('UPLOAD_ALLOWED_TYPES', explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,webp,gif'));

// Currency
define('CURRENCY_CODE', $_ENV['CURRENCY_CODE'] ?? 'GHS');
define('CURRENCY_SYMBOL', $_ENV['CURRENCY_SYMBOL'] ?? '₵');
define('VAT_RATE', floatval($_ENV['VAT_RATE'] ?? 0.00));

// Money Transfer
define('MIN_TRANSFER_AMOUNT', floatval($_ENV['MIN_TRANSFER_AMOUNT'] ?? 1.00));
define('MAX_TRANSFER_AMOUNT', floatval($_ENV['MAX_TRANSFER_AMOUNT'] ?? 50000.00));
define('TOKEN_EXPIRY_DAYS', intval($_ENV['TOKEN_EXPIRY_DAYS'] ?? 30));

// Shipping Zones (in GHS)
define('SHIPPING_ZONE_1_BASE', floatval($_ENV['SHIPPING_ZONE_1_BASE_PRICE'] ?? 20.00));
define('SHIPPING_ZONE_2_BASE', floatval($_ENV['SHIPPING_ZONE_2_BASE_PRICE'] ?? 35.00));
define('SHIPPING_ZONE_3_BASE', floatval($_ENV['SHIPPING_ZONE_3_BASE_PRICE'] ?? 50.00));
define('SHIPPING_ZONE_4_BASE', floatval($_ENV['SHIPPING_ZONE_4_BASE_PRICE'] ?? 80.00));

// Business Info
define('BUSINESS_NAME', $_ENV['BUSINESS_NAME'] ?? 'ThinQShopping');
define('BUSINESS_EMAIL', $_ENV['BUSINESS_EMAIL'] ?? 'info@thinqshopping.com');
define('BUSINESS_PHONE', $_ENV['BUSINESS_PHONE'] ?? '+233 XX XXX XXXX');
define('BUSINESS_WHATSAPP', $_ENV['BUSINESS_WHATSAPP'] ?? '+233 XX XXX XXXX');

// Order Statuses
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_PROCESSING', 'processing');
define('ORDER_STATUS_PACKED', 'packed');
define('ORDER_STATUS_SHIPPED', 'shipped');
define('ORDER_STATUS_OUT_FOR_DELIVERY', 'out_for_delivery');
define('ORDER_STATUS_DELIVERED', 'delivered');
define('ORDER_STATUS_CANCELLED', 'cancelled');

// Transfer Statuses
define('TRANSFER_STATUS_PAYMENT_RECEIVED', 'payment_received');
define('TRANSFER_STATUS_PROCESSING', 'processing');
define('TRANSFER_STATUS_SENT_TO_PARTNER', 'sent_to_partner');
define('TRANSFER_STATUS_COMPLETED', 'completed');
define('TRANSFER_STATUS_FAILED', 'failed');
define('TRANSFER_STATUS_CANCELLED', 'cancelled');

// Shipment Statuses
define('SHIPMENT_STATUS_BOOKED', 'booked');
define('SHIPMENT_STATUS_PICKUP_SCHEDULED', 'pickup_scheduled');
define('SHIPMENT_STATUS_PICKED_UP', 'picked_up');
define('SHIPMENT_STATUS_IN_TRANSIT', 'in_transit');
define('SHIPMENT_STATUS_OUT_FOR_DELIVERY', 'out_for_delivery');
define('SHIPMENT_STATUS_DELIVERED', 'delivered');

// Payment Methods
define('PAYMENT_METHOD_CARD', 'card');
define('PAYMENT_METHOD_MOBILE_MONEY', 'mobile_money');
define('PAYMENT_METHOD_BANK_TRANSFER', 'bank_transfer');
define('PAYMENT_METHOD_WALLET', 'wallet');
define('PAYMENT_METHOD_COD', 'cod');

// Payment Statuses
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_SUCCESS', 'success');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');







