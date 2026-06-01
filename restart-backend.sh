#!/bin/bash

# Backend Restart Script for Kelompok Sahabat Obor Mas
# Usage: ./restart-backend.sh

echo "🔄 Restarting Backend Services..."

# Navigate to backend directory
BACKEND_PATH="/var/www/html/backend/backend-app"
cd $BACKEND_PATH

echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo "🔄 Restarting PHP-FPM..."
sudo systemctl restart php8.3-fpm

echo "🔄 Reloading Nginx..."
sudo systemctl reload nginx

echo "✅ Checking service status..."

# Check PHP-FPM
if systemctl is-active --quiet php8.3-fpm; then
    echo "✅ PHP-FPM: Running"
else
    echo "❌ PHP-FPM: Failed to start"
    echo "📋 Check logs: journalctl -u php8.3-fpm -f"
    exit 1
fi

# Check Nginx
if systemctl is-active --quiet nginx; then
    echo "✅ Nginx: Running"
else
    echo "❌ Nginx: Failed to start"
    echo "📋 Check logs: journalctl -u nginx -f"
    exit 1
fi

echo "🧪 Testing API endpoint..."
API_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" http://localhost/obormas/api/data-penghasilan)

if [ "$API_RESPONSE" = "401" ]; then
    echo "✅ API: Responding correctly (401 Unauthenticated)"
elif [ "$API_RESPONSE" = "200" ]; then
    echo "✅ API: Responding correctly (200 OK)"
else
    echo "⚠️ API: Unexpected response code: $API_RESPONSE"
    echo "📋 Check API logs: tail -f storage/logs/laravel.log"
fi

echo ""
echo "🎉 Backend restart completed!"
echo ""
echo "📋 Services restarted:"
echo "- PHP-FPM: Restarted"
echo "- Nginx: Reloaded"
echo "- Laravel caches: Cleared"
echo ""
echo "🔍 If issues persist:"
echo "- Check PHP logs: tail -f /var/log/php8.3-fpm.log"
echo "- Check Nginx logs: tail -f /var/log/nginx/error.log"
echo "- Check Laravel logs: tail -f storage/logs/laravel.log"