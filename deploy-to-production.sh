#!/bin/bash

# Production Deployment Script for Kelompok Sahabat Obor Mas Backend
# Usage: ./deploy-to-production.sh

echo "🚀 Starting Production Deployment..."

# Configuration
BACKEND_PATH="/var/www/html/backend/backend-app"
BACKUP_PATH="/var/www/html/backend/backups"

# Create backup directory if not exists
mkdir -p $BACKUP_PATH

echo "📦 Creating backup..."
BACKUP_NAME="backup-$(date +%Y%m%d-%H%M%S)"
cp -r $BACKEND_PATH $BACKUP_PATH/$BACKUP_NAME
echo "✅ Backup created: $BACKUP_PATH/$BACKUP_NAME"

echo "📥 Pulling latest code..."
cd $BACKEND_PATH
git stash
git pull origin main

echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo "🔄 Restarting services..."
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx

echo "✅ Checking service status..."
if systemctl is-active --quiet php8.3-fpm; then
    echo "✅ PHP-FPM: Running"
else
    echo "❌ PHP-FPM: Failed"
    exit 1
fi

if systemctl is-active --quiet nginx; then
    echo "✅ Nginx: Running"
else
    echo "❌ Nginx: Failed"
    exit 1
fi

echo "🧪 Testing API..."
API_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" http://localhost/obormas/api/data-penghasilan)
if [ "$API_RESPONSE" = "401" ]; then
    echo "✅ API: Responding correctly (401 Unauthenticated)"
else
    echo "⚠️ API: Unexpected response code: $API_RESPONSE"
fi

echo ""
echo "🎉 Deployment completed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Clear mobile app cache"
echo "2. Test mobile app login"
echo "3. Verify CRUD operations"
echo ""
echo "🔍 If issues occur:"
echo "- Check logs: tail -f storage/logs/laravel.log"
echo "- Rollback: cp -r $BACKUP_PATH/$BACKUP_NAME/* $BACKEND_PATH/"