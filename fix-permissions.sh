#!/bin/bash
# Fix permissions for vendor directory

echo "Fixing permissions for ThinQShopping vendor directory..."

cd /Applications/XAMPP/xamppfiles/htdocs/ThinQShopping

# Create vendor directory if it doesn't exist
mkdir -p vendor

# Set permissions
chmod -R 755 vendor
chmod -R 755 .

# If running as root/sudo, set ownership to web server user
if [ "$EUID" -eq 0 ]; then
    chown -R _www:staff vendor
    chown -R _www:staff .
    echo "✅ Permissions fixed (as root)"
else
    echo "✅ Permissions fixed"
    echo "If you still have permission issues, run: sudo ./fix-permissions.sh"
fi

echo "Done! Now try installing PHPMailer again."





