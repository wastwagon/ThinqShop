<?php
/**
 * Email Configuration using PHPMailer
 * ThinQShopping Platform
 */

require_once __DIR__ . '/env-loader.php';
require_once __DIR__ . '/constants.php';

// Load PHPMailer (you'll need to install via Composer or include manually)
// For now, using basic mail() function - replace with PHPMailer later

class EmailConfig {
    private static $smtpHost;
    private static $smtpPort;
    private static $smtpUser;
    private static $smtpPass;
    private static $smtpFromEmail;
    private static $smtpFromName;
    private static $smtpEncryption;

    public static function init() {
        self::$smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        self::$smtpPort = intval($_ENV['SMTP_PORT'] ?? 587);
        self::$smtpUser = $_ENV['SMTP_USER'] ?? '';
        self::$smtpPass = $_ENV['SMTP_PASS'] ?? '';
        self::$smtpFromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? BUSINESS_EMAIL;
        self::$smtpFromName = $_ENV['SMTP_FROM_NAME'] ?? BUSINESS_NAME;
        self::$smtpEncryption = $_ENV['SMTP_ENCRYPTION'] ?? 'tls';
    }

    /**
     * Send email (basic implementation - upgrade to PHPMailer)
     */
    public static function send($to, $subject, $message, $isHTML = true) {
        $headers = [];
        
        if ($isHTML) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }
        
        $headers[] = 'From: ' . self::$smtpFromName . ' <' . self::$smtpFromEmail . '>';
        $headers[] = 'Reply-To: ' . self::$smtpFromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        $headersString = implode("\r\n", $headers);

        if (mail($to, $subject, $message, $headersString)) {
            return ['status' => true, 'message' => 'Email sent successfully'];
        } else {
            return ['status' => false, 'message' => 'Failed to send email'];
        }
    }

    /**
     * Load email template
     */
    public static function loadTemplate($templateName, $variables = []) {
        $templatePath = BASE_PATH . '/email-templates/' . $templateName . '.html';
        
        if (!file_exists($templatePath)) {
            return false;
        }

        $template = file_get_contents($templatePath);
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        // Replace common variables
        $template = str_replace('{{APP_NAME}}', APP_NAME, $template);
        $template = str_replace('{{BUSINESS_NAME}}', BUSINESS_NAME, $template);
        $template = str_replace('{{BUSINESS_EMAIL}}', BUSINESS_EMAIL, $template);
        $template = str_replace('{{BUSINESS_PHONE}}', BUSINESS_PHONE, $template);
        $template = str_replace('{{BUSINESS_WHATSAPP}}', BUSINESS_WHATSAPP, $template);
        $template = str_replace('{{APP_URL}}', APP_URL, $template);
        $template = str_replace('{{CURRENT_YEAR}}', date('Y'), $template);

        return $template;
    }

    /**
     * Send order confirmation email
     */
    public static function sendOrderConfirmation($userEmail, $orderData) {
        $template = self::loadTemplate('order-confirmation', $orderData);
        if ($template) {
            return self::send($userEmail, 'Order Confirmation - ' . APP_NAME, $template);
        }
        return ['status' => false, 'message' => 'Template not found'];
    }

    /**
     * Send transfer token email
     */
    public static function sendTransferToken($userEmail, $transferData) {
        $template = self::loadTemplate('transfer-token', $transferData);
        if ($template) {
            return self::send($userEmail, 'Money Transfer Token - ' . APP_NAME, $template);
        }
        return ['status' => false, 'message' => 'Template not found'];
    }

    /**
     * Send shipment tracking update
     */
    public static function sendShipmentUpdate($userEmail, $shipmentData) {
        $template = self::loadTemplate('shipment-tracking', $shipmentData);
        if ($template) {
            return self::send($userEmail, 'Shipment Update - ' . APP_NAME, $template);
        }
        return ['status' => false, 'message' => 'Template not found'];
    }
}

// Initialize email config
EmailConfig::init();

