<?php
/**
 * Insert Default Email Templates
 * Run this if templates are missing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Insert Default Templates</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;}";
echo "h2{border-bottom:2px solid #333;padding-bottom:10px;}";
echo "</style></head><body>";

echo "<h1>Insert Default Email Templates</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if templates already exist (but don't exit - we'll update them)
    $stmt = $conn->query("SELECT COUNT(*) as count FROM email_templates");
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        echo "<p class='info'>ℹ️ Found $count existing templates. Will insert/update all templates...</p>";
    }
    
    echo "<h2>Inserting Default Templates...</h2>";
    
    // Define default templates
    $templates = [
        [
            'template_key' => 'user_registration',
            'template_name' => 'Email Verification Required',
            'subject' => 'Verify Your Email Address - {{APP_NAME}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Verify Your Email - {{APP_NAME}}</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Verify Your Email Address</h2><p>Hello {{USER_NAME}},</p><p>Thank you for registering with {{APP_NAME}}! To complete your registration and gain access to your account, please verify your email address by clicking the button below.</p><p style="text-align: center; margin: 30px 0;"><a href="{{VERIFICATION_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: 600;">Verify Email Address</a></p><p>If the button doesn\'t work, copy and paste this link into your browser:</p><p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;">{{VERIFICATION_LINK}}</p><p><strong>Important:</strong> This verification link will expire in 24 hours. You must verify your email before you can log in to your account.</p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when a new user registers - contains email verification link (required before login)',
            'variables' => '["USER_NAME", "VERIFICATION_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'email_verified',
            'template_name' => 'Email Verification Success',
            'subject' => 'Email Verified Successfully',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Email Verified</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #198754;">Email Verified Successfully!</h2><p>Hello {{USER_NAME}},</p><p>Your email address has been successfully verified. You can now access all features of {{APP_NAME}}.</p><p style="text-align: center; margin: 30px 0;"><a href="{{LOGIN_LINK}}" style="background-color: #198754; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Login Now</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when user verifies their email',
            'variables' => '["USER_NAME", "LOGIN_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'order_confirmation',
            'template_name' => 'Order Confirmation',
            'subject' => 'Order Confirmation - Order #{{ORDER_NUMBER}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Confirmation</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Order Confirmation</h2><p>Hello {{USER_NAME}},</p><p>Thank you for your order! We have received your order and it is being processed.</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Order Number:</strong> {{ORDER_NUMBER}}</p><p><strong>Order Date:</strong> {{ORDER_DATE}}</p><p><strong>Total Amount:</strong> {{ORDER_TOTAL}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{ORDER_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Order</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when an order is placed',
            'variables' => '["USER_NAME", "ORDER_NUMBER", "ORDER_DATE", "ORDER_TOTAL", "ORDER_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'ticket_created',
            'template_name' => 'Ticket Created',
            'subject' => 'Support Ticket Created - #{{TICKET_NUMBER}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ticket Created</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Support Ticket Created</h2><p>Hello {{USER_NAME}},</p><p>Your support ticket has been created successfully. We will respond to your inquiry as soon as possible.</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Ticket Number:</strong> {{TICKET_NUMBER}}</p><p><strong>Subject:</strong> {{TICKET_SUBJECT}}</p><p><strong>Category:</strong> {{TICKET_CATEGORY}}</p><p><strong>Priority:</strong> {{TICKET_PRIORITY}}</p><p><strong>Status:</strong> {{TICKET_STATUS}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{TICKET_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Ticket</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when a ticket is created',
            'variables' => '["USER_NAME", "TICKET_NUMBER", "TICKET_SUBJECT", "TICKET_CATEGORY", "TICKET_PRIORITY", "TICKET_STATUS", "TICKET_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'ticket_reply',
            'template_name' => 'Ticket Reply',
            'subject' => 'New Reply on Ticket #{{TICKET_NUMBER}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ticket Reply</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">New Reply on Your Ticket</h2><p>Hello {{USER_NAME}},</p><p>You have received a new reply on your support ticket:</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Ticket Number:</strong> {{TICKET_NUMBER}}</p><p><strong>Subject:</strong> {{TICKET_SUBJECT}}</p><p><strong>Reply:</strong></p><div style="background: white; padding: 10px; border-left: 3px solid #0d6efd; margin-top: 10px;">{{REPLY_MESSAGE}}</div></div><p style="text-align: center; margin: 30px 0;"><a href="{{TICKET_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Ticket</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when there is a reply on a ticket',
            'variables' => '["USER_NAME", "TICKET_NUMBER", "TICKET_SUBJECT", "REPLY_MESSAGE", "TICKET_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'order_status_update',
            'template_name' => 'Order Status Update',
            'subject' => 'Order #{{ORDER_NUMBER}} Status Update',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Status Update</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Order Status Update</h2><p>Hello {{USER_NAME}},</p><p>Your order status has been updated:</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Order Number:</strong> {{ORDER_NUMBER}}</p><p><strong>New Status:</strong> {{ORDER_STATUS}}</p><p><strong>Update Date:</strong> {{UPDATE_DATE}}</p>{{STATUS_NOTES}}</div><p style="text-align: center; margin: 30px 0;"><a href="{{ORDER_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Order</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when order status changes',
            'variables' => '["USER_NAME", "ORDER_NUMBER", "ORDER_STATUS", "UPDATE_DATE", "STATUS_NOTES", "ORDER_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'order_shipped',
            'template_name' => 'Order Shipped',
            'subject' => 'Your Order #{{ORDER_NUMBER}} Has Been Shipped',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Shipped</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #198754;">Your Order Has Been Shipped!</h2><p>Hello {{USER_NAME}},</p><p>Great news! Your order has been shipped and is on its way to you.</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Order Number:</strong> {{ORDER_NUMBER}}</p><p><strong>Tracking Number:</strong> {{TRACKING_NUMBER}}</p><p><strong>Shipping Date:</strong> {{SHIP_DATE}}</p><p><strong>Estimated Delivery:</strong> {{ESTIMATED_DELIVERY}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{TRACKING_LINK}}" style="background-color: #198754; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Track Your Order</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when an order is shipped',
            'variables' => '["USER_NAME", "ORDER_NUMBER", "TRACKING_NUMBER", "SHIP_DATE", "ESTIMATED_DELIVERY", "TRACKING_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'order_delivered',
            'template_name' => 'Order Delivered',
            'subject' => 'Your Order #{{ORDER_NUMBER}} Has Been Delivered',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Delivered</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #198754;">Order Delivered Successfully!</h2><p>Hello {{USER_NAME}},</p><p>Your order has been delivered successfully.</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Order Number:</strong> {{ORDER_NUMBER}}</p><p><strong>Delivery Date:</strong> {{DELIVERY_DATE}}</p><p><strong>Delivery Address:</strong> {{DELIVERY_ADDRESS}}</p></div><p>We hope you enjoy your purchase! If you have any questions or concerns, please don\'t hesitate to contact us.</p><p style="text-align: center; margin: 30px 0;"><a href="{{ORDER_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Order</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when an order is delivered',
            'variables' => '["USER_NAME", "ORDER_NUMBER", "DELIVERY_DATE", "DELIVERY_ADDRESS", "ORDER_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'transfer_confirmation',
            'template_name' => 'Money Transfer Confirmation',
            'subject' => 'Money Transfer Confirmation - Token #{{TRANSFER_TOKEN}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Transfer Confirmation</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Transfer Confirmation</h2><p>Hello {{USER_NAME}},</p><p>Your money transfer has been initiated successfully.</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Transfer Token:</strong> {{TRANSFER_TOKEN}}</p><p><strong>Amount:</strong> {{TRANSFER_AMOUNT}}</p><p><strong>Recipient:</strong> {{RECIPIENT_NAME}}</p><p><strong>Recipient Account:</strong> {{RECIPIENT_ACCOUNT}}</p><p><strong>Transfer Date:</strong> {{TRANSFER_DATE}}</p><p><strong>Status:</strong> {{TRANSFER_STATUS}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{TRANSFER_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Track Transfer</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when a money transfer is initiated',
            'variables' => '["USER_NAME", "TRANSFER_TOKEN", "TRANSFER_AMOUNT", "RECIPIENT_NAME", "RECIPIENT_ACCOUNT", "TRANSFER_DATE", "TRANSFER_STATUS", "TRANSFER_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'transfer_status_update',
            'template_name' => 'Money Transfer Status Update',
            'subject' => 'Transfer #{{TRANSFER_TOKEN}} Status Update',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Transfer Status Update</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Transfer Status Update</h2><p>Hello {{USER_NAME}},</p><p>Your money transfer status has been updated:</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Transfer Token:</strong> {{TRANSFER_TOKEN}}</p><p><strong>Amount:</strong> {{TRANSFER_AMOUNT}}</p><p><strong>New Status:</strong> {{TRANSFER_STATUS}}</p><p><strong>Update Date:</strong> {{UPDATE_DATE}}</p>{{STATUS_NOTES}}</div><p style="text-align: center; margin: 30px 0;"><a href="{{TRANSFER_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Track Transfer</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when transfer status changes',
            'variables' => '["USER_NAME", "TRANSFER_TOKEN", "TRANSFER_AMOUNT", "TRANSFER_STATUS", "UPDATE_DATE", "STATUS_NOTES", "TRANSFER_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'transfer_completed',
            'template_name' => 'Money Transfer Completed',
            'subject' => 'Transfer #{{TRANSFER_TOKEN}} Completed Successfully',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Transfer Completed</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #198754;">Transfer Completed Successfully!</h2><p>Hello {{USER_NAME}},</p><p>Your money transfer has been completed successfully.</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Transfer Token:</strong> {{TRANSFER_TOKEN}}</p><p><strong>Amount:</strong> {{TRANSFER_AMOUNT}}</p><p><strong>Recipient:</strong> {{RECIPIENT_NAME}}</p><p><strong>Completed Date:</strong> {{COMPLETED_DATE}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{TRANSFER_LINK}}" style="background-color: #198754; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Transfer Details</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when a transfer is completed',
            'variables' => '["USER_NAME", "TRANSFER_TOKEN", "TRANSFER_AMOUNT", "RECIPIENT_NAME", "COMPLETED_DATE", "TRANSFER_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'shipment_created',
            'template_name' => 'Shipment Created',
            'subject' => 'Shipment Created - Tracking #{{TRACKING_NUMBER}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Shipment Created</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Shipment Created</h2><p>Hello {{USER_NAME}},</p><p>Your shipment has been created and is being prepared for dispatch.</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Tracking Number:</strong> {{TRACKING_NUMBER}}</p><p><strong>Origin:</strong> {{ORIGIN}}</p><p><strong>Destination:</strong> {{DESTINATION}}</p><p><strong>Weight:</strong> {{WEIGHT}}</p><p><strong>Status:</strong> {{SHIPMENT_STATUS}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{TRACKING_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Track Shipment</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when a shipment is created',
            'variables' => '["USER_NAME", "TRACKING_NUMBER", "ORIGIN", "DESTINATION", "WEIGHT", "SHIPMENT_STATUS", "TRACKING_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'shipment_status_update',
            'template_name' => 'Shipment Status Update',
            'subject' => 'Shipment Update - Tracking #{{TRACKING_NUMBER}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Shipment Update</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Shipment Update</h2><p>Hello {{USER_NAME}},</p><p>Your shipment status has been updated:</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Tracking Number:</strong> {{TRACKING_NUMBER}}</p><p><strong>New Status:</strong> {{SHIPMENT_STATUS}}</p><p><strong>Current Location:</strong> {{CURRENT_LOCATION}}</p><p><strong>Update Date:</strong> {{UPDATE_DATE}}</p>{{STATUS_NOTES}}</div><p style="text-align: center; margin: 30px 0;"><a href="{{TRACKING_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Track Shipment</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when shipment status changes',
            'variables' => '["USER_NAME", "TRACKING_NUMBER", "SHIPMENT_STATUS", "CURRENT_LOCATION", "UPDATE_DATE", "STATUS_NOTES", "TRACKING_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'shipment_delivered',
            'template_name' => 'Shipment Delivered',
            'subject' => 'Shipment Delivered - Tracking #{{TRACKING_NUMBER}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Shipment Delivered</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #198754;">Shipment Delivered!</h2><p>Hello {{USER_NAME}},</p><p>Your shipment has been delivered successfully.</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Tracking Number:</strong> {{TRACKING_NUMBER}}</p><p><strong>Delivery Date:</strong> {{DELIVERY_DATE}}</p><p><strong>Delivered To:</strong> {{DELIVERY_ADDRESS}}</p><p><strong>Recipient:</strong> {{RECIPIENT_NAME}}</p></div><p>Thank you for using our logistics services!</p><p style="text-align: center; margin: 30px 0;"><a href="{{TRACKING_LINK}}" style="background-color: #198754; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Shipment Details</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent when a shipment is delivered',
            'variables' => '["USER_NAME", "TRACKING_NUMBER", "DELIVERY_DATE", "DELIVERY_ADDRESS", "RECIPIENT_NAME", "TRACKING_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'activity_summary',
            'template_name' => 'Activity Summary',
            'subject' => 'Your {{APP_NAME}} Activity Summary - {{PERIOD}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Activity Summary</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #0d6efd;">Your Activity Summary</h2><p>Hello {{USER_NAME}},</p><p>Here\'s a summary of your recent activities on {{APP_NAME}} for {{PERIOD}}:</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><h3 style="margin-top: 0;">Summary</h3><p><strong>Orders Placed:</strong> {{ORDERS_COUNT}}</p><p><strong>Money Transfers:</strong> {{TRANSFERS_COUNT}}</p><p><strong>Shipments:</strong> {{SHIPMENTS_COUNT}}</p><p><strong>Total Spent:</strong> {{TOTAL_SPENT}}</p></div><div style="margin: 20px 0;">{{ACTIVITY_DETAILS}}</div><p style="text-align: center; margin: 30px 0;"><a href="{{DASHBOARD_LINK}}" style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Dashboard</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent as a summary of user activities (daily/weekly/monthly)',
            'variables' => '["USER_NAME", "PERIOD", "ORDERS_COUNT", "TRANSFERS_COUNT", "SHIPMENTS_COUNT", "TOTAL_SPENT", "ACTIVITY_DETAILS", "DASHBOARD_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'new_order_admin',
            'template_name' => 'New Order Notification (Admin)',
            'subject' => 'New Order Received - Order #{{ORDER_NUMBER}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>New Order</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #dc3545;">New Order Received</h2><p>A new order has been placed:</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Order Number:</strong> {{ORDER_NUMBER}}</p><p><strong>Customer:</strong> {{CUSTOMER_NAME}} ({{CUSTOMER_EMAIL}})</p><p><strong>Order Date:</strong> {{ORDER_DATE}}</p><p><strong>Total Amount:</strong> {{ORDER_TOTAL}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{ORDER_LINK}}" style="background-color: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Order</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent to admin when a new order is placed',
            'variables' => '["ORDER_NUMBER", "CUSTOMER_NAME", "CUSTOMER_EMAIL", "ORDER_DATE", "ORDER_TOTAL", "ORDER_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'new_transfer_admin',
            'template_name' => 'New Transfer Notification (Admin)',
            'subject' => 'New Money Transfer - Token #{{TRANSFER_TOKEN}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>New Transfer</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #dc3545;">New Money Transfer</h2><p>A new money transfer has been initiated:</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Transfer Token:</strong> {{TRANSFER_TOKEN}}</p><p><strong>Customer:</strong> {{CUSTOMER_NAME}} ({{CUSTOMER_EMAIL}})</p><p><strong>Amount:</strong> {{TRANSFER_AMOUNT}}</p><p><strong>Recipient:</strong> {{RECIPIENT_NAME}}</p><p><strong>Transfer Date:</strong> {{TRANSFER_DATE}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{TRANSFER_LINK}}" style="background-color: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Transfer</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent to admin when a new money transfer is initiated',
            'variables' => '["TRANSFER_TOKEN", "CUSTOMER_NAME", "CUSTOMER_EMAIL", "TRANSFER_AMOUNT", "RECIPIENT_NAME", "TRANSFER_DATE", "TRANSFER_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ],
        [
            'template_key' => 'new_shipment_admin',
            'template_name' => 'New Shipment Notification (Admin)',
            'subject' => 'New Shipment Created - Tracking #{{TRACKING_NUMBER}}',
            'body' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>New Shipment</title></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;"><div style="max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #dc3545;">New Shipment Created</h2><p>A new shipment has been created:</p><div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;"><p><strong>Tracking Number:</strong> {{TRACKING_NUMBER}}</p><p><strong>Customer:</strong> {{CUSTOMER_NAME}} ({{CUSTOMER_EMAIL}})</p><p><strong>Origin:</strong> {{ORIGIN}}</p><p><strong>Destination:</strong> {{DESTINATION}}</p><p><strong>Weight:</strong> {{WEIGHT}}</p><p><strong>Created Date:</strong> {{CREATED_DATE}}</p></div><p style="text-align: center; margin: 30px 0;"><a href="{{SHIPMENT_LINK}}" style="background-color: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Shipment</a></p><hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;"><p style="font-size: 12px; color: #666;">© {{CURRENT_YEAR}} {{BUSINESS_NAME}}. All rights reserved.</p></div></body></html>',
            'description' => 'Sent to admin when a new shipment is created',
            'variables' => '["TRACKING_NUMBER", "CUSTOMER_NAME", "CUSTOMER_EMAIL", "ORIGIN", "DESTINATION", "WEIGHT", "CREATED_DATE", "SHIPMENT_LINK", "APP_NAME", "BUSINESS_NAME", "CURRENT_YEAR"]'
        ]
    ];
    
    $insertStmt = $conn->prepare("
        INSERT INTO email_templates (template_key, template_name, subject, body, description, variables, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            template_name = VALUES(template_name),
            subject = VALUES(subject),
            body = VALUES(body),
            description = VALUES(description),
            variables = VALUES(variables),
            updated_at = NOW()
    ");
    
    $inserted = 0;
    foreach ($templates as $template) {
        try {
            $insertStmt->execute([
                $template['template_key'],
                $template['template_name'],
                $template['subject'],
                $template['body'],
                $template['description'],
                $template['variables']
            ]);
            $inserted++;
            echo "<p class='success'>✅ Inserted/Updated: " . htmlspecialchars($template['template_name']) . "</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error inserting " . htmlspecialchars($template['template_name']) . ": " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Insert default notification settings
    echo "<h2>Inserting Notification Settings...</h2>";
    $notificationSettings = [
        ['user_registered', 1, 1, 1, 1, 'New user registration'],
        ['email_verified', 1, 0, 1, 1, 'Email verification'],
        ['order_placed', 1, 1, 1, 1, 'New order placed'],
        ['order_status_changed', 1, 0, 1, 1, 'Order status update'],
        ['order_shipped', 1, 0, 1, 1, 'Order shipped'],
        ['order_delivered', 1, 0, 1, 1, 'Order delivered'],
        ['transfer_initiated', 1, 1, 1, 1, 'Money transfer initiated'],
        ['transfer_status_changed', 1, 0, 1, 1, 'Transfer status update'],
        ['transfer_completed', 1, 0, 1, 1, 'Transfer completed'],
        ['shipment_created', 1, 1, 1, 1, 'Shipment created'],
        ['shipment_status_changed', 1, 0, 1, 1, 'Shipment status update'],
        ['shipment_delivered', 1, 0, 1, 1, 'Shipment delivered'],
        ['activity_summary', 1, 0, 1, 1, 'Activity summary notification'],
        ['ticket_created', 1, 1, 1, 1, 'Support ticket created'],
        ['ticket_replied', 1, 1, 1, 1, 'Ticket reply']
    ];
    
    $notifStmt = $conn->prepare("
        INSERT INTO notification_settings (notification_type, send_to_user, send_to_admin, send_email, send_in_app, description)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            send_to_user = VALUES(send_to_user),
            send_to_admin = VALUES(send_to_admin),
            send_email = VALUES(send_email),
            send_in_app = VALUES(send_in_app),
            description = VALUES(description),
            updated_at = NOW()
    ");
    
    foreach ($notificationSettings as $setting) {
        try {
            $notifStmt->execute($setting);
            echo "<p class='success'>✅ Inserted/Updated notification setting: " . htmlspecialchars($setting[0]) . "</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error inserting notification setting: " . htmlspecialchars($setting[0]) . " - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Insert default email settings
    echo "<h2>Inserting Email Settings...</h2>";
    $emailSettings = [
        ['smtp_enabled', '1', 'Enable SMTP email sending'],
        ['smtp_host', 'smtp.gmail.com', 'SMTP server host'],
        ['smtp_port', '587', 'SMTP server port'],
        ['smtp_encryption', 'tls', 'SMTP encryption (tls/ssl)'],
        ['smtp_username', '', 'SMTP username'],
        ['smtp_password', '', 'SMTP password'],
        ['from_email', defined('BUSINESS_EMAIL') ? BUSINESS_EMAIL : '', 'Default from email address'],
        ['from_name', defined('BUSINESS_NAME') ? BUSINESS_NAME : 'ThinQShopping', 'Default from name'],
        ['reply_to_email', defined('BUSINESS_EMAIL') ? BUSINESS_EMAIL : '', 'Reply-to email address']
    ];
    
    $emailStmt = $conn->prepare("
        INSERT INTO email_settings (setting_key, setting_value, description)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            description = VALUES(description),
            updated_at = NOW()
    ");
    
    foreach ($emailSettings as $setting) {
        try {
            $emailStmt->execute($setting);
            echo "<p class='success'>✅ Inserted/Updated email setting: " . htmlspecialchars($setting[0]) . "</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error inserting email setting: " . htmlspecialchars($setting[0]) . " - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Verify
    $stmt = $conn->query("SELECT COUNT(*) as count FROM email_templates");
    $count = $stmt->fetch()['count'];
    
    echo "<h2>Verification</h2>";
    echo "<p>Total templates in database: <strong>$count</strong></p>";
    
    if ($count > 0) {
        echo "<p class='success'><strong>✅ Success! Templates are now available.</strong></p>";
        echo "<p><a href='admin/settings/email-templates.php' style='background:#0d6efd;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Email Templates</a></p>";
    } else {
        echo "<p class='error'><strong>❌ No templates found. Please check the errors above.</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
