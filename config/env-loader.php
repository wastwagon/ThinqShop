<?php
/**
 * Environment Variables Loader
 * Loads .env file or uses defaults
 */

if (!function_exists('loadEnv')) {
    function loadEnv($file) {
        if (!file_exists($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Remove quotes from value if present
            $value = trim($value, '"\'');

            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }
            if (!array_key_exists($name, $_SERVER)) {
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    loadEnv($envFile);
} else {
    // Use defaults for local development
    $_ENV['APP_NAME'] = $_ENV['APP_NAME'] ?? 'ThinQShopping';
    $_ENV['APP_URL'] = $_ENV['APP_URL'] ?? 'http://localhost/ThinQShopping';
    $_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'development';
    $_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? true;
    $_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
    $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'thinqshopping_db';
    $_ENV['DB_USER'] = $_ENV['DB_USER'] ?? 'root';
    $_ENV['DB_PASS'] = $_ENV['DB_PASS'] ?? '';
}

