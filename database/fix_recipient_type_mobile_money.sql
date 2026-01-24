-- Fix recipient_type to include mobile_money option
-- Run this in phpMyAdmin or via MySQL command line

USE thinqshop_db;

-- Update money_transfers table
ALTER TABLE `money_transfers` 
MODIFY COLUMN `recipient_type` ENUM('bank_account','alipay','wechat_pay','mobile_money') NOT NULL;

-- Update saved_recipients table
ALTER TABLE `saved_recipients` 
MODIFY COLUMN `recipient_type` ENUM('bank_account','alipay','wechat_pay','mobile_money') NOT NULL;








