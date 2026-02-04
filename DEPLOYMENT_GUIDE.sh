#!/bin/bash
# Deployment Guide for Coolify
# This script helps you deploy to Coolify with database migrations

echo "========================================="
echo "ThinQShop Deployment Guide for Coolify"
echo "========================================="
echo ""

# Step 1: Commit Status
echo "✅ Step 1: Git Commit Status"
echo "Your changes have been committed:"
echo "Commit: bca118a - feat: Implement Apple App Store compliance fixes"
echo "Files changed: 9 files, 861 insertions(+), 22 deletions(-)"
echo ""

# Step 2: Push to GitHub
echo "📤 Step 2: Push to GitHub"
echo "Run this command to push your changes:"
echo ""
echo "  git push origin main"
echo ""
read -p "Press Enter after you've pushed to GitHub..."
echo ""

# Step 3: Database Migration
echo "🗄️  Step 3: Database Migration"
echo ""
echo "⚠️  IMPORTANT: The database migration needs to be run MANUALLY on your Coolify deployment."
echo ""
echo "The auto_migrate.php script only handles the main SQL dump, NOT the migrations folder."
echo ""
echo "You have TWO OPTIONS:"
echo ""
echo "OPTION A: Run migration via Coolify SSH (RECOMMENDED)"
echo "  1. Go to Coolify dashboard"
echo "  2. Navigate to your ThinQShop application"
echo "  3. Click 'Terminal' or 'SSH'"
echo "  4. Run this command:"
echo ""
echo "     docker exec thinqshopping_web php /var/www/html/database/migrations/add_account_deletion_fields.sql"
echo ""
echo "  OR use the setup script:"
echo ""
echo "     docker exec thinqshopping_web bash /var/www/html/setup-appstore-fixes.sh"
echo ""
echo ""
echo "OPTION B: Run migration via MySQL client"
echo "  1. Access your MySQL database via Coolify or phpMyAdmin"
echo "  2. Run the SQL file: database/migrations/add_account_deletion_fields.sql"
echo ""
echo ""

# Step 4: Cron Job Setup
echo "⏰ Step 4: Set Up Cron Job (REQUIRED)"
echo ""
echo "You MUST set up a cron job to process account deletions after 30 days."
echo ""
echo "In Coolify:"
echo "  1. Go to your application settings"
echo "  2. Find 'Scheduled Tasks' or 'Cron Jobs'"
echo "  3. Add this cron job:"
echo ""
echo "     Schedule: 0 2 * * *"
echo "     Command: docker exec thinqshopping_web php /var/www/html/scripts/process-account-deletions.php"
echo ""
echo "This runs daily at 2 AM to permanently delete accounts after the grace period."
echo ""

# Step 5: Verification
echo "✅ Step 5: Verify Deployment"
echo ""
echo "After deployment, verify:"
echo "  1. Registration works without phone/WhatsApp"
echo "  2. Account deletion option appears in user profile"
echo "  3. Database has new columns (deletion_requested_at, deletion_scheduled_for, deletion_token)"
echo "  4. account_deletion_logs table exists"
echo ""

# Step 6: App Store Resubmission
echo "📱 Step 6: App Store Resubmission"
echo ""
echo "Use the message in APPSTORE_RESUBMISSION.md when resubmitting to Apple."
echo "Include screenshots from the walkthrough document."
echo ""

echo "========================================="
echo "Deployment checklist complete!"
echo "========================================="
