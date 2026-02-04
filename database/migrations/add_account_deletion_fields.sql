-- Migration: Add Account Deletion Support
-- Date: 2026-02-04
-- Purpose: Add fields to support account deletion with grace period

-- Allow NULL phone numbers (Apple App Store requirement)
ALTER TABLE users MODIFY COLUMN phone VARCHAR(20) NULL;

-- Allow NULL WhatsApp numbers (Apple App Store requirement)
ALTER TABLE user_profiles MODIFY COLUMN whatsapp_number VARCHAR(20) NULL;

-- Add account deletion tracking fields
ALTER TABLE users 
ADD COLUMN deletion_requested_at DATETIME NULL COMMENT 'When user requested account deletion',
ADD COLUMN deletion_scheduled_for DATETIME NULL COMMENT 'When permanent deletion will occur (30 days after request)',
ADD COLUMN deletion_token VARCHAR(64) NULL COMMENT 'Unique token for cancellation link',
ADD INDEX idx_deletion_scheduled (deletion_scheduled_for);

-- Create audit log table for account deletions
CREATE TABLE IF NOT EXISTS account_deletion_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    user_name VARCHAR(255),
    deletion_requested_at DATETIME NOT NULL,
    deletion_completed_at DATETIME NULL,
    deletion_cancelled_at DATETIME NULL,
    cancelled_by_user BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_deletion_completed (deletion_completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
