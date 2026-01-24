<?php
/**
 * Paystack Payment Gateway Configuration
 * ThinQShopping Platform
 */

require_once __DIR__ . '/env-loader.php';

class PaystackConfig {
    public static function getPublicKey() {
        return $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';
    }

    public static function getSecretKey() {
        return $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
    }

    public static function getMode() {
        return $_ENV['PAYSTACK_MODE'] ?? 'test';
    }

    public static function isTestMode() {
        return self::getMode() === 'test';
    }

    public static function getApiUrl() {
        return 'https://api.paystack.co';
    }

    /**
     * Initialize Paystack transaction
     */
    public static function initializeTransaction($email, $amount, $reference, $callback_url, $metadata = []) {
        $url = self::getApiUrl() . '/transaction/initialize';
        
        $fields = [
            'email' => $email,
            'amount' => $amount * 100, // Convert to kobo/pesewas
            'reference' => $reference,
            'callback_url' => $callback_url,
            'metadata' => $metadata
        ];

        return self::makeRequest($url, $fields);
    }

    /**
     * Verify Paystack transaction
     */
    public static function verifyTransaction($reference) {
        $url = self::getApiUrl() . '/transaction/verify/' . $reference;
        return self::makeRequest($url, [], 'GET');
    }

    /**
     * Make HTTP request to Paystack API
     */
    private static function makeRequest($url, $fields = [], $method = 'POST') {
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . self::getSecretKey(),
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => false, 'message' => $error];
        }

        return json_decode($response, true);
    }

    /**
     * Verify webhook signature
     */
    public static function verifyWebhookSignature($payload, $signature) {
        $hash = hash_hmac('sha512', $payload, self::getSecretKey());
        return hash_equals($hash, $signature);
    }

    /**
     * Generate unique transaction reference
     */
    public static function generateReference($prefix = 'TXN') {
        return $prefix . '_' . time() . '_' . uniqid();
    }
}

