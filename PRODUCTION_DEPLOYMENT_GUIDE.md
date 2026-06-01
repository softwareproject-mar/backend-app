# 🚀 PRODUCTION DEPLOYMENT GUIDE

## ✅ STATUS SAAT INI:
- **Local Development**: ✅ Working (frontend + backend)
- **Production Server**: ❌ Needs backend code update
- **Mobile App**: ❌ Still using old backend code

## 📋 DEPLOYMENT OPTIONS:

### **OPTION 1: UPDATE BACKEND ONLY (RECOMMENDED)**
Deploy hanya backend code yang sudah diupdate, frontend mobile app tetap sama.

### **OPTION 2: FULL DEPLOYMENT**
Deploy backend + build frontend baru untuk production.

---

## 🎯 OPTION 1: UPDATE BACKEND ONLY

### **Step 1: Deploy Backend Code to Production Server**

#### **1.1 SSH ke Production Server:**
```bash
ssh root@103.253.212.105
# atau gunakan tool SSH client seperti PuTTY
```

#### **1.2 Navigate ke Backend Directory:**
```bash
cd /var/www/html/backend/backend-app
# atau path yang sesuai dengan struktur server
```

#### **1.3 Backup Current Code:**
```bash
# Backup sebelum update
cp -r . ../backup-$(date +%Y%m%d-%H%M%S)
```

#### **1.4 Pull Latest Code:**
```bash
git status
git stash  # jika ada perubahan local
git pull origin main
```

#### **1.5 Update Dependencies:**
```bash
composer install --no-dev --optimize-autoloader
```

#### **1.6 Clear All Caches:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

#### **1.7 Restart Services:**
```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Nginx
sudo systemctl reload nginx

# Check status
sudo systemctl status php8.3-fpm
sudo systemctl status nginx
```

### **Step 2: Verify Deployment**

#### **2.1 Test API Endpoints:**
```bash
# Test from server
curl -H "Accept: application/json" http://localhost/obormas/api/data-penghasilan
# Should return: {"message":"Unauthenticated."}
```

#### **2.2 Check Logs:**
```bash
# Check PHP logs
tail -f /var/log/php8.3-fpm.log

# Check Nginx logs  
tail -f /var/log/nginx/error.log
```

---

## 🎯 OPTION 2: FULL DEPLOYMENT (Frontend + Backend)

### **Step 1: Build Frontend for Production**

#### **1.1 Update Frontend .env:**
```bash
# Change back to production API
VITE_API_BASE_URL=http://103.253.212.105/obormas/api
```

#### **1.2 Build Frontend:**
```bash
cd "C:\Users\galih\Documents\ui\Kelompok Sahabat Obor Mas\Kelompok Sahabat Obor Mas"
npm run build
```

#### **1.3 Deploy Frontend:**
```bash
# Upload dist folder to server
# atau sync dengan server frontend directory
```

### **Step 2: Deploy Backend (same as Option 1)**

---

## 🔧 BACKEND RESTART COMMANDS

### **Manual Restart (when needed):**
```bash
# SSH ke server
ssh root@103.253.212.105

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Nginx
sudo systemctl reload nginx

# Clear Laravel caches
cd /var/www/html/backend/backend-app
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### **Quick Restart Script:**
```bash
#!/bin/bash
# Save as restart-backend.sh

echo "Restarting Backend Services..."

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear  
php artisan route:clear
php artisan view:clear

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx

echo "Backend restarted successfully!"
```

---

## 📱 MOBILE APP TESTING

### **After Backend Deployment:**

#### **1. Clear Mobile App:**
```
Android: Settings > Apps > Kelompok Sahabat Obor Mas > Storage > Clear Data
iOS: Delete and reinstall app
```

#### **2. Test Login:**
```
Email: galih.ario2014@gmail.com
Password: [your password]
```

#### **3. Expected Results:**
- ✅ Login successful
- ✅ Data loads without "Resource not found"
- ✅ User sees only own data
- ✅ CRUD operations work
- ✅ NO_AGT field required in forms

---

## 🚨 TROUBLESHOOTING

### **If Deployment Fails:**
```bash
# Check git status
git status
git log --oneline -5

# Check file permissions
ls -la
sudo chown -R www-data:www-data .
sudo chmod -R 755 .

# Check PHP version
php -v

# Check composer
composer --version
```

### **If Services Don't Start:**
```bash
# Check service status
sudo systemctl status php8.3-fpm
sudo systemctl status nginx

# Check logs
journalctl -u php8.3-fpm -f
journalctl -u nginx -f
```

### **If API Still Fails:**
```bash
# Test API directly on server
curl -H "Accept: application/json" http://localhost/obormas/api/auth/login

# Check Laravel logs
tail -f storage/logs/laravel.log
```

---

## 🎯 RECOMMENDED APPROACH:

### **For Quick Fix:**
1. **Deploy backend only** (Option 1)
2. **Clear mobile app cache**
3. **Test mobile app**

### **For Complete Update:**
1. **Deploy backend** (Option 1)
2. **Build and deploy frontend** (Option 2)
3. **Test both web and mobile**

---

**🚀 NEXT STEPS:**
1. Choose deployment option
2. Follow the steps above
3. Test mobile app after deployment
4. Report results