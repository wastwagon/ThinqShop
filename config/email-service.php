<?php
/**
 * Enhanced Email Service using PHPMailer
 * ThinQShopping Platform
 */

require_once __DIR__ . '/env-loader.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

class EmailService {
    private static $db;
    private static $conn;
    private static $phpmailerLoaded = null;
    
    /**
     * Check if PHPMailer is available
     */
    private static function isPHPMailerAvailable() {
        if (self::$phpmailerLoaded === null) {
            // Try standard composer autoloader
            $autoloadPath = __DIR__ . '/../vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                self::$phpmailerLoaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
            }
            
            // Try alternative autoloader location
            if (!self::$phpmailerLoaded) {
                $altAutoloadPath = __DIR__ . '/../vendor-autoload.php';
                if (file_exists($altAutoloadPath)) {
                    require_once $altAutoloadPath;
                    self::$phpmailerLoaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
                }
            }
            
            // Try direct include from multiple possible locations
            if (!self::$phpmailerLoaded) {
                $possiblePaths = [
                    __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
                    __DIR__ . '/../vendor/PHPMailer-master/src/PHPMailer.php',
                    __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php',
                ];
                
                foreach ($possiblePaths as $phpmailerFile) {
                    if (file_exists($phpmailerFile)) {
                        require_once $phpmailerFile;
                        require_once dirname($phpmailerFile) . '/SMTP.php';
                        require_once dirname($phpmailerFile) . '/Exception.php';
                        self::$phpmailerLoaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
                        if (self::$phpmailerLoaded) {
                            break;
                        }
                    }
                }
            }
            
            // Try direct loader if available
            if (!self::$phpmailerLoaded) {
                $loaderPath = __DIR__ . '/../includes/phpmailer-loader.php';
                if (file_exists($loaderPath)) {
                    require_once $loaderPath;
                    self::$phpmailerLoaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
                }
            }
            
            // Final check
            if (self::$phpmailerLoaded === null) {
                self::$phpmailerLoaded = false;
            }
        }
        return self::$phpmailerLoaded;
    }
    
    /**
     * Initialize email service
     */
    public static function init() {
        self::$db = new Database();
        self::$conn = self::$db->getConnection();
    }
    
    /**
     * Send email using PHPMailer or fallback to mail()
     */
    public static function send($to, $subject, $message, $isHTML = true, $attachments = []) {
        self::init();
        
        // Get email settings from database
        $settings = self::getEmailSettings();
        
        if (!$settings['smtp_enabled']) {
            return ['status' => false, 'message' => 'SMTP is disabled'];
        }
        
        if (self::isPHPMailerAvailable()) {
            return self::sendWithPHPMailer($to, $subject, $message, $isHTML, $attachments, $settings);
        } else {
            return self::sendWithMail($to, $subject, $message, $isHTML, $settings);
        }
    }
    
    /**
     * Send email using PHPMailer
     */
    private static function sendWithPHPMailer($to, $subject, $message, $isHTML, $attachments, $settings) {
        try {
            // Validate SMTP credentials
            if (empty($settings['smtp_username']) || empty($settings['smtp_password'])) {
                return ['status' => false, 'message' => 'SMTP username and password are required. Please configure your email settings.'];
            }
            
            if (empty($settings['smtp_host'])) {
                return ['status' => false, 'message' => 'SMTP host is required. Please configure your email settings.'];
            }
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Enable verbose debug output for troubleshooting
            $mail->SMTPDebug = 2; // Show detailed SMTP conversation
            $debugOutput = '';
            $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
                $debugOutput .= $str . "\n";
                error_log("PHPMailer Debug: $str");
            };
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_username'];
            $mail->Password = $settings['smtp_password'];
            $mail->SMTPSecure = $settings['smtp_encryption'] === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = intval($settings['smtp_port']);
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 30; // Increase timeout for slow connections
            
            // Recipients
            $mail->setFrom($settings['from_email'], $settings['from_name']);
            $mail->addAddress($to);
            
            if (!empty($settings['reply_to_email'])) {
                $mail->addReplyTo($settings['reply_to_email'], $settings['from_name']);
            }
            
            // Attachments
            foreach ($attachments as $attachment) {
                if (is_array($attachment) && file_exists($attachment['path'])) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? basename($attachment['path']));
                } elseif (is_string($attachment) && file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            if (!$isHTML) {
                $mail->AltBody = strip_tags($message);
            }
            
            $mail->send();
            
            // Log successful send
            error_log("Email sent successfully to: $to via SMTP");
            
            return [
                'status' => true, 
                'message' => 'Email sent successfully',
                'debug' => $debugOutput // Include debug info for troubleshooting
            ];
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $errorMsg = 'SMTP Error: ' . $e->getMessage();
            // Log detailed error for debugging
            error_log("Email sending failed: " . $errorMsg);
            error_log("SMTP Debug Output: " . $debugOutput);
            return [
                'status' => false, 
                'message' => $errorMsg,
                'debug' => $debugOutput
            ];
        } catch (\Exception $e) {
            $errorMsg = 'Error: ' . $e->getMessage();
            error_log("Email sending failed: " . $errorMsg);
            return [
                'status' => false, 
                'message' => $errorMsg,
                'debug' => $debugOutput ?? ''
            ];
        }
    }
    
    /**
     * Send email using PHP mail() function (fallback)
     */
    private static function sendWithMail($to, $subject, $message, $isHTML, $settings) {
        $headers = [];
        $headers[] = 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>';
        $headers[] = 'Reply-To: ' . ($settings['reply_to_email'] ?? $settings['from_email']);
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        if ($isHTML) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }
        
        $result = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if ($result) {
            return ['status' => true, 'message' => 'Email sent successfully'];
        } else {
            return ['status' => false, 'message' => 'Failed to send email'];
        }
    }
    
    /**
     * Get email settings from database
     */
    public static function getEmailSettings() {
        self::init();
        
        $defaults = [
            'smtp_enabled' => '1',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_username' => '',
            'smtp_password' => '',
            'from_email' => defined('BUSINESS_EMAIL') ? BUSINESS_EMAIL : 'noreply@thinqshopping.com',
            'from_name' => defined('BUSINESS_NAME') ? BUSINESS_NAME : 'ThinQShopping',
            'reply_to_email' => defined('BUSINESS_EMAIL') ? BUSINESS_EMAIL : 'noreply@thinqshopping.com'
        ];
        
        try {
            $stmt = self::$conn->query("SELECT setting_key, setting_value FROM email_settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return array_merge($defaults, $settings);
        } catch (Exception $e) {
            // Table might not exist yet, return defaults
            return $defaults;
        }
    }
    
    /**
     * Send email using template
     */
    public static function sendTemplate($to, $templateKey, $variables = []) {
        self::init();
        
        try {
            $stmt = self::$conn->prepare("SELECT * FROM email_templates WHERE template_key = ? AND is_active = 1");
            $stmt->execute([$templateKey]);
            $template = $stmt->fetch();
            
            if (!$template) {
                return ['status' => false, 'message' => 'Template not found or inactive'];
            }
            
            // Replace variables in subject and body
            $subject = $template['subject'];
            $body = $template['body'];
            
            // Replace common variables first
            $commonVars = [
                'APP_NAME' => defined('APP_NAME') ? APP_NAME : 'ThinQShopping',
                'BUSINESS_NAME' => defined('BUSINESS_NAME') ? BUSINESS_NAME : 'ThinQShopping',
                'BUSINESS_EMAIL' => defined('BUSINESS_EMAIL') ? BUSINESS_EMAIL : 'noreply@thinqshopping.com',
                'CURRENT_YEAR' => date('Y')
            ];
            
            foreach ($commonVars as $key => $value) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
                $body = str_replace('{{' . $key . '}}', $value, $body);
            }
            
            // Replace custom variables
            foreach ($variables as $key => $value) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
                $body = str_replace('{{' . $key . '}}', $value, $body);
            }
            
            return self::send($to, $subject, $body, true);
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error loading template: ' . $e->getMessage()];
        }
    }
}

