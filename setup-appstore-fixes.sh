#!/bin/bash
# Setup script for Apple App Store compliance fixes
# Run this script to apply database migrations

echo "=== Apple App Store Compliance Setup ==="
echo ""

# Check if we're in the correct directory
if [ ! -f "config/database.php" ]; then
    echo "Error: Please run this script from the project root directory"
    exit 1
fi

echo "This script will:"
echo "1. Apply database migrations for optional phone numbers"
echo "2. Add account deletion tracking fields"
echo "3. Create account deletion audit log table"
echo ""
read -p "Continue? (y/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Setup cancelled."
    exit 0
fi

# Load database credentials from .env
if [ -f ".env" ]; then
    export $(cat .env | grep -v '^#' | xargs)
else
    echo "Error: .env file not found"
    exit 1
fi

echo ""
echo "Applying database migrations..."
echo ""

# Run the migration
mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < database/migrations/add_account_deletion_fields.sql

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ Database migrations applied successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Test registration without phone/WhatsApp"
    echo "2. Test account deletion flow"
    echo "3. Set up cron job for permanent deletions:"
    echo "   0 2 * * * /usr/bin/php $(pwd)/scripts/process-account-deletions.php"
    echo ""
    echo "Ready for App Store resubmission!"
else
    echo ""
    echo "✗ Error applying migrations. Please check your database credentials."
    exit 1
fi
