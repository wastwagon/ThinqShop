-- Migration: Add Email Templates, Email Verification Tokens, and Support Tickets Tables
-- Date: 2025-01-XX

-- Email Templates Table
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(100) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `variables` text DEFAULT NULL COMMENT 'JSON array of available variables',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Verification Tokens Table
CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `email_verification_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support Tickets Table
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('technical','billing','account','shipping','general') DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','waiting_user','resolved','closed') DEFAULT 'open',
  `description` text NOT NULL,
  `assigned_to` int(11) DEFAULT NULL COMMENT 'Admin user ID',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `assigned_to` (`assigned_to`),
  KEY `category` (`category`),
  KEY `priority` (`priority`),
  CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `support_tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support Ticket Replies/Messages Table
CREATE TABLE IF NOT EXISTS `support_ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'User who replied (if from user)',
  `admin_id` int(11) DEFAULT NULL COMMENT 'Admin who replied (if from admin)',
  `message` text NOT NULL,
  `attachments` text DEFAULT NULL COMMENT 'JSON array of attachment file paths',
  `is_internal` tinyint(1) DEFAULT 0 COMMENT 'Internal note visible only to admins',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `support_ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `support_ticket_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_ticket_messages_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Notification Settings Table (for enabling/disabling specific email types)
CREATE TABLE IF NOT EXISTS `email_notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(100) NOT NULL,
  `notification_name` varchar(255) NOT NULL,
  `send_to_user` tinyint(1) DEFAULT 1,
  `send_to_admin` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_type` (`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default email templates
INSERT INTO `email_templates` (`template_key`, `template_name`, `subject`, `body`, `variables`) VALUES
('email_verification', 'Email Verification', 'Verify Your Email Address - {{APP_NAME}}', 
'<h2>Welcome to {{APP_NAME}}!</h2>
<p>Thank you for registering. Please verify your email address by clicking the link below:</p>
<p><a href="{{VERIFICATION_URL}}">Verify Email Address</a></p>
<p>Or copy and paste this link into your browser:</p>
<p>{{VERIFICATION_URL}}</p>
<p>This link will expire in 24 hours.</p>
<p>If you did not create an account, please ignore this email.</p>',
'["APP_NAME","VERIFICATION_URL","USER_NAME"]'),

('welcome', 'Welcome Email', 'Welcome to {{APP_NAME}}!', 
'<h2>Welcome to {{APP_NAME}}, {{USER_NAME}}!</h2>
<p>Thank you for joining us. Your account has been successfully verified.</p>
<p>You can now access all our services:</p>
<ul>
<li>Shop for products</li>
<li>Send money to China</li>
<li>Book parcel deliveries</li>
<li>Request procurement services</li>
</ul>
<p>Visit your dashboard: <a href="{{APP_URL}}/user/dashboard.php">Dashboard</a></p>',
'["APP_NAME","USER_NAME","APP_URL"]'),

('order_confirmation', 'Order Confirmation', 'Order Confirmation - Order #{{ORDER_NUMBER}}', 
'<h2>Thank you for your order!</h2>
<p>Your order #{{ORDER_NUMBER}} has been confirmed.</p>
<p><strong>Order Details:</strong></p>
{{ORDER_DETAILS}}
<p><strong>Total Amount:</strong> {{ORDER_TOTAL}}</p>
<p>You can track your order status in your dashboard.</p>',
'["APP_NAME","ORDER_NUMBER","ORDER_DETAILS","ORDER_TOTAL","USER_NAME"]'),

('transfer_token', 'Money Transfer Token', 'Your Money Transfer Token - {{TRANSFER_NUMBER}}', 
'<h2>Money Transfer Token</h2>
<p>Your transfer request #{{TRANSFER_NUMBER}} has been processed.</p>
<p><strong>Token:</strong> <strong style="font-size: 24px;">{{TOKEN}}</strong></p>
<p><strong>Amount:</strong> {{AMOUNT}}</p>
<p><strong>Recipient:</strong> {{RECIPIENT_NAME}}</p>
<p>Please keep this token secure and share it only with the recipient.</p>',
'["APP_NAME","TRANSFER_NUMBER","TOKEN","AMOUNT","RECIPIENT_NAME"]'),

('shipment_update', 'Shipment Update', 'Shipment Update - {{TRACKING_NUMBER}}', 
'<h2>Shipment Update</h2>
<p>Your shipment {{TRACKING_NUMBER}} status has been updated.</p>
<p><strong>Current Status:</strong> {{STATUS}}</p>
<p><strong>Location:</strong> {{LOCATION}}</p>
<p>{{MESSAGE}}</p>
<p>Track your shipment: <a href="{{TRACKING_URL}}">View Tracking</a></p>',
'["APP_NAME","TRACKING_NUMBER","STATUS","LOCATION","MESSAGE","TRACKING_URL"]'),

('password_reset', 'Password Reset', 'Reset Your Password - {{APP_NAME}}', 
'<h2>Password Reset Request</h2>
<p>You requested to reset your password. Click the link below to reset it:</p>
<p><a href="{{RESET_URL}}">Reset Password</a></p>
<p>Or copy and paste this link into your browser:</p>
<p>{{RESET_URL}}</p>
<p>This link will expire in 1 hour.</p>
<p>If you did not request this, please ignore this email.</p>',
'["APP_NAME","RESET_URL","USER_NAME"]'),

('ticket_created', 'Support Ticket Created', 'Support Ticket Created - {{TICKET_NUMBER}}', 
'<h2>Support Ticket Created</h2>
<p>Your support ticket #{{TICKET_NUMBER}} has been created.</p>
<p><strong>Subject:</strong> {{TICKET_SUBJECT}}</p>
<p><strong>Category:</strong> {{CATEGORY}}</p>
<p>We will review your ticket and respond as soon as possible.</p>
<p>View your ticket: <a href="{{TICKET_URL}}">View Ticket</a></p>',
'["APP_NAME","TICKET_NUMBER","TICKET_SUBJECT","CATEGORY","TICKET_URL"]'),

('ticket_reply', 'Support Ticket Reply', 'New Reply to Ticket #{{TICKET_NUMBER}}', 
'<h2>New Reply to Your Support Ticket</h2>
<p>A new reply has been added to your ticket #{{TICKET_NUMBER}}.</p>
<p><strong>Subject:</strong> {{TICKET_SUBJECT}}</p>
<p><strong>Reply:</strong></p>
<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">{{REPLY_MESSAGE}}</div>
<p>View full conversation: <a href="{{TICKET_URL}}">View Ticket</a></p>',
'["APP_NAME","TICKET_NUMBER","TICKET_SUBJECT","REPLY_MESSAGE","TICKET_URL"]'),

('admin_new_order', 'Admin: New Order Notification', 'New Order Received - Order #{{ORDER_NUMBER}}', 
'<h2>New Order Received</h2>
<p>A new order has been placed:</p>
<p><strong>Order Number:</strong> {{ORDER_NUMBER}}</p>
<p><strong>Customer:</strong> {{CUSTOMER_NAME}} ({{CUSTOMER_EMAIL}})</p>
<p><strong>Total Amount:</strong> {{ORDER_TOTAL}}</p>
<p>View order: <a href="{{ADMIN_ORDER_URL}}">View Order</a></p>',
'["APP_NAME","ORDER_NUMBER","CUSTOMER_NAME","CUSTOMER_EMAIL","ORDER_TOTAL","ADMIN_ORDER_URL"]'),

('admin_new_transfer', 'Admin: New Transfer Notification', 'New Money Transfer Request - {{TRANSFER_NUMBER}}', 
'<h2>New Money Transfer Request</h2>
<p>A new money transfer has been requested:</p>
<p><strong>Transfer Number:</strong> {{TRANSFER_NUMBER}}</p>
<p><strong>Customer:</strong> {{CUSTOMER_NAME}}</p>
<p><strong>Amount:</strong> {{AMOUNT}}</p>
<p>View transfer: <a href="{{ADMIN_TRANSFER_URL}}">View Transfer</a></p>',
'["APP_NAME","TRANSFER_NUMBER","CUSTOMER_NAME","AMOUNT","ADMIN_TRANSFER_URL"]'),

('admin_new_shipment', 'Admin: New Shipment Notification', 'New Shipment Booking - {{TRACKING_NUMBER}}', 
'<h2>New Shipment Booking</h2>
<p>A new shipment has been booked:</p>
<p><strong>Tracking Number:</strong> {{TRACKING_NUMBER}}</p>
<p><strong>Customer:</strong> {{CUSTOMER_NAME}}</p>
<p><strong>Total Price:</strong> {{TOTAL_PRICE}}</p>
<p>View shipment: <a href="{{ADMIN_SHIPMENT_URL}}">View Shipment</a></p>',
'["APP_NAME","TRACKING_NUMBER","CUSTOMER_NAME","TOTAL_PRICE","ADMIN_SHIPMENT_URL"]'),

('admin_new_ticket', 'Admin: New Support Ticket', 'New Support Ticket - {{TICKET_NUMBER}}', 
'<h2>New Support Ticket</h2>
<p>A new support ticket has been created:</p>
<p><strong>Ticket Number:</strong> {{TICKET_NUMBER}}</p>
<p><strong>Customer:</strong> {{CUSTOMER_NAME}} ({{CUSTOMER_EMAIL}})</p>
<p><strong>Subject:</strong> {{TICKET_SUBJECT}}</p>
<p><strong>Category:</strong> {{CATEGORY}}</p>
<p><strong>Priority:</strong> {{PRIORITY}}</p>
<p>View ticket: <a href="{{ADMIN_TICKET_URL}}">View Ticket</a></p>',
'["APP_NAME","TICKET_NUMBER","CUSTOMER_NAME","CUSTOMER_EMAIL","TICKET_SUBJECT","CATEGORY","PRIORITY","ADMIN_TICKET_URL"]');

-- Insert default email notification settings
INSERT INTO `email_notification_settings` (`notification_type`, `notification_name`, `send_to_user`, `send_to_admin`) VALUES
('user_registration', 'User Registration', 1, 1),
('email_verification', 'Email Verification', 1, 0),
('order_placed', 'Order Placed', 1, 1),
('order_status_change', 'Order Status Change', 1, 0),
('transfer_requested', 'Money Transfer Requested', 1, 1),
('transfer_completed', 'Transfer Completed', 1, 0),
('shipment_booked', 'Shipment Booked', 1, 1),
('shipment_status_update', 'Shipment Status Update', 1, 0),
('ticket_created', 'Support Ticket Created', 1, 1),
('ticket_reply', 'Support Ticket Reply', 1, 0),
('password_reset', 'Password Reset', 1, 0),
('procurement_requested', 'Procurement Requested', 1, 1);






