<?php
/**
 * Update .env file for cPanel production
 */

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    die("Error: .env file not found!");
}

// Read the file
$lines = file($envFile, FILE_IGNORE_NEW_LINES);

$updated = false;
foreach ($lines as $index => $line) {
    // Update APP_URL
    if (preg_match('/^APP_URL=/', $line)) {
        $lines[$index] = 'APP_URL="https://thinqshopping.app"';
        $updated = true;
    }
    // Update APP_ENV
    if (preg_match('/^APP_ENV=/', $line)) {
        $lines[$index] = 'APP_ENV="production"';
        $updated = true;
    }
    // Update APP_DEBUG
    if (preg_match('/^APP_DEBUG=/', $line)) {
        $lines[$index] = 'APP_DEBUG="false"';
        $updated = true;
    }
    // Update DB_NAME
    if (preg_match('/^DB_NAME=/', $line)) {
        $lines[$index] = 'DB_NAME=thinjupz_db';
        $updated = true;
    }
    // Update DB_USER
    if (preg_match('/^DB_USER=/', $line)) {
        $lines[$index] = 'DB_USER=thinjupz_user';
        $updated = true;
    }
}

// Write back
file_put_contents($envFile, implode("\n", $lines));

echo "✓ .env file updated successfully!\n\n";
echo "Updated values:\n";
echo "- APP_URL: https://thinqshopping.app\n";
echo "- APP_ENV: production\n";
echo "- APP_DEBUG: false\n";
echo "- DB_NAME: thinjupz_db\n";
echo "- DB_USER: thinjupz_user\n\n";
echo "IMPORTANT: You need to upload this updated .env file to your server!\n";
echo "Upload it to: /public_html/.env (or wherever your site root is)\n";

