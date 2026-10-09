# Deployment Checklist

Use this checklist when setting up on your teammate's laptop for presentation.

---

## 📦 Before Transferring Files

### On Your Machine:

- [ ] Stop all running processes (`.\stop-app.ps1`)
- [ ] Clean up temporary files:
  ```powershell
  Remove-Item storage/logs/*.log -ErrorAction SilentlyContinue
  Remove-Item node_modules -Recurse -Force -ErrorAction SilentlyContinue
  Remove-Item vendor -Recurse -Force -ErrorAction SilentlyContinue
  ```
- [ ] Ensure `.env` has test credentials (no personal info)
- [ ] Verify all scripts are present:
  - [ ] setup.ps1
  - [ ] start-app.ps1
  - [ ] stop-app.ps1
  - [ ] reset-demo.ps1
- [ ] Verify documentation files are present:
  - [ ] README.md
  - [ ] SETUP_GUIDE.md
  - [ ] PRESENTATION_GUIDE.md
  - [ ] QUICK_REFERENCE.md
  - [ ] DEPLOYMENT_CHECKLIST.md
- [ ] Create backup of database:
  ```powershell
  # Export from phpMyAdmin or:
  mysqldump -u root -p dormitory_db > backup.sql
  ```

---

## 💾 Transfer Method

Choose ONE method:

### Method 1: USB Drive (Recommended)
- [ ] Copy entire project folder to USB drive
- [ ] Verify all files copied (check file count)
- [ ] Safely eject USB

### Method 2: Cloud Storage (Google Drive, OneDrive)
- [ ] Compress project folder to ZIP
- [ ] Upload to cloud storage
- [ ] Share link with teammate
- [ ] Verify download completes

### Method 3: GitHub (If using version control)
- [ ] Add `.gitignore` (exclude `.env`, `node_modules`, `vendor`)
- [ ] Commit and push:
  ```bash
  git add .
  git commit -m "Ready for presentation"
  git push
  ```
- [ ] Teammate clones repo

---

## 🖥️ On Teammate's Laptop

### Day Before Presentation:

#### 1. Prerequisites Installation (30-45 minutes)

- [ ] **Install PHP 8.2+**
  - Download: https://windows.php.net/download/
  - Extract to `C:\php\`
  - Add to PATH environment variable
  - Verify: `php -v`

- [ ] **Install Composer**
  - Download: https://getcomposer.org/Composer-Setup.exe
  - Run installer
  - Verify: `composer --version`

- [ ] **Install Node.js & npm**
  - Download: https://nodejs.org/ (LTS version)
  - Run installer
  - Verify: `node -v` and `npm -v`

- [ ] **Install XAMPP (or MySQL)**
  - Download: https://www.apachefriends.org/download.html
  - Install with MySQL component
  - Start MySQL service

- [ ] **Install ngrok**
  - Download: https://ngrok.com/download
  - Extract to `C:\ngrok\`
  - Sign up for free account: https://dashboard.ngrok.com/signup
  - Copy authtoken

#### 2. Project Setup (15-20 minutes)

- [ ] Extract/copy project to: `C:\Projects\dorm_manager\`
- [ ] Open PowerShell in project folder
- [ ] Run setup script:
  ```powershell
  .\setup.ps1
  ```
- [ ] Enter ngrok authtoken when prompted
- [ ] Enter MySQL password when prompted (usually empty)
- [ ] Wait for setup to complete

#### 3. Database Setup (5 minutes)

- [ ] Start XAMPP → Start MySQL
- [ ] Open phpMyAdmin: http://localhost/phpmyadmin
- [ ] Create database: `dormitory_db`
- [ ] If you have backup.sql:
  ```powershell
  mysql -u root -p dormitory_db < backup.sql
  ```
- [ ] Otherwise, seed demo data:
  ```powershell
  php artisan migrate --force
  php artisan db:seed --force
  ```

#### 4. Configuration (5 minutes)

- [ ] Open `.env` file in text editor
- [ ] Verify database settings:
  ```
  DB_DATABASE=dormitory_db
  DB_USERNAME=root
  DB_PASSWORD=your_password
  ```
- [ ] Clear config cache:
  ```powershell
  php artisan config:clear
  ```

#### 5. Initial Test (10 minutes)

- [ ] Start application:
  ```powershell
  .\start-app.ps1
  ```
- [ ] Note the ngrok URL (e.g., https://xyz.ngrok-free.dev)
- [ ] Open in browser: http://127.0.0.1:8000
- [ ] Test admin login:
  - Email: admin@dorm.test
  - Password: password
- [ ] Test tenant login (incognito/another browser):
  - Email: tenant1@dorm.test
  - Password: password

#### 6. PayMongo Configuration (10 minutes)

- [ ] Go to: https://dashboard.paymongo.com/login
- [ ] Login with credentials: _______________ (write it down!)
- [ ] Go to: Developers → Webhooks
- [ ] Delete any old webhooks (if needed)
- [ ] Create new webhook:
  - URL: `https://YOUR-NGROK-URL.ngrok-free.dev/webhooks/paymongo`
  - Events: `link.payment.paid`, `payment.paid`, `checkout_session.payment.paid`
  - Click Create
- [ ] Copy the Webhook Signing Secret (starts with `whsk_`)
- [ ] Update `.env` file:
  ```
  PAYMONGO_WEBHOOK_SECRET=whsk_xxxxxxxxxxxxx
  ```
- [ ] Clear config:
  ```powershell
  php artisan config:clear
  ```

#### 7. End-to-End Test (15 minutes)

- [ ] Login as tenant (tenant1@dorm.test)
- [ ] Go to "My Bill"
- [ ] Note the balance due
- [ ] Click "Pay with PayMongo"
- [ ] Complete test payment:
  - Select GCash
  - Mobile: 09123456789
  - OTP: 123456 (any 6 digits)
- [ ] Verify webhook received:
  - Open: http://localhost:4040
  - Check for POST to /webhooks/paymongo
  - Status should be 200 OK
- [ ] Refresh tenant portal
- [ ] Verify balance cleared
- [ ] Login as admin
- [ ] Go to Payments
- [ ] Verify payment recorded

#### 8. Backup Screenshots (10 minutes)

Take screenshots in case of demo issues:

- [ ] Dashboard overview
- [ ] Rooms page with statuses
- [ ] Create billing statement page
- [ ] Tenant bill page
- [ ] PayMongo payment success page
- [ ] ngrok dashboard with webhook
- [ ] Admin panel showing payment recorded
- [ ] Billing statement status changed to "paid"

---

## 📋 Presentation Day (30 minutes before)

### Setup:

- [ ] Connect to stable WiFi (or prepare mobile hotspot backup)
- [ ] Close all unnecessary applications
- [ ] Disable notifications (Focus Assist / Do Not Disturb)
- [ ] Increase screen brightness
- [ ] Set power plan to "High Performance"
- [ ] Plug in laptop charger

### Start Application:

- [ ] Start XAMPP → Start MySQL
- [ ] Run: `.\start-app.ps1`
- [ ] Note the ngrok URL
- [ ] **IMPORTANT:** Check if ngrok URL changed
  - If changed, update PayMongo webhook!

### Browser Setup:

- [ ] Open Chrome/Edge
- [ ] Clear cache (Ctrl+Shift+Delete)
- [ ] Zoom level: 110-125% (for visibility)
- [ ] Open tabs:
  - Tab 1: Admin panel (logged in)
  - Tab 2: Tenant portal (logged in, incognito)
  - Tab 3: ngrok dashboard (http://localhost:4040)
- [ ] Have PayMongo dashboard ready on phone/tablet

### Final Checks:

- [ ] Test admin login
- [ ] Test tenant login
- [ ] Navigate to each major feature
- [ ] Make one test payment
- [ ] Verify webhook working
- [ ] Check backup screenshots are accessible

---

## 🎯 Common Issues & Quick Fixes

### Issue: ngrok URL changed from yesterday

**Fix:**
```powershell
# Get new URL from start-app.ps1 output
# Update PayMongo webhook (2 minutes)
# Update .env with new webhook secret
php artisan config:clear
```

### Issue: Port 8000 already in use

**Fix:**
```powershell
.\stop-app.ps1
# Wait 5 seconds
.\start-app.ps1
```

### Issue: Database connection error

**Fix:**
```powershell
# Check MySQL is running in XAMPP
# Verify .env credentials
php artisan config:clear
```

### Issue: Webhook not working

**Fix:**
1. Check ngrok running: http://localhost:4040
2. Check webhook URL in PayMongo
3. Check webhook secret in `.env`
4. Test manually:
   ```powershell
   curl http://127.0.0.1:8000/webhooks/paymongo
   ```

### Issue: White screen / 500 error

**Fix:**
```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
composer dump-autoload
```

### Issue: Need fresh data

**Fix:**
```powershell
.\reset-demo.ps1
# Type 'yes' to confirm
```

---

## 📞 Emergency Contacts

**Important Numbers:**

- Teammate 1: _________________
- Teammate 2: _________________
- You (if available): _________________

**Backup Plans:**

- [ ] Pre-recorded demo video
- [ ] Screenshots folder ready
- [ ] Printed code snippets
- [ ] Presentation slides with diagrams

---

## ✅ Final Pre-Presentation Checklist

**5 Minutes Before:**

- [ ] Battery: 100% + plugged in
- [ ] Internet: Connected + backup hotspot ready
- [ ] Application: Running smoothly
- [ ] Browsers: Logged in (admin + tenant)
- [ ] ngrok: Dashboard open
- [ ] Credentials: Written down nearby
- [ ] Backup screenshots: Accessible
- [ ] Deep breath: Taken ✓

---

## 🎉 Post-Presentation

- [ ] Stop application: `.\stop-app.ps1`
- [ ] Backup database (if needed):
  ```powershell
  # Export from phpMyAdmin
  ```
- [ ] Save any feedback/questions
- [ ] Celebrate! 🎊

---

**Good luck with your presentation!** 

You've got this! 💪
