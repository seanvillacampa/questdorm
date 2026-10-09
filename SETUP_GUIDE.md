# Dorm Management System - Setup Guide

## 📋 Prerequisites

Before starting, ensure you have these installed:

1. **PHP 8.2 or higher**
   - Download: https://windows.php.net/download/
   - Add to PATH environment variable

2. **Composer** (PHP Package Manager)
   - Download: https://getcomposer.org/download/

3. **MySQL/MariaDB**
   - XAMPP (recommended): https://www.apachefriends.org/download.html
   - OR MySQL standalone: https://dev.mysql.com/downloads/installer/

4. **Node.js & npm** (for frontend assets)
   - Download: https://nodejs.org/ (LTS version)

5. **ngrok** (for webhook testing)
   - Download: https://ngrok.com/download
   - Create free account: https://dashboard.ngrok.com/signup

---

## 🚀 Quick Setup (Automated)

### Option 1: Run the Setup Script

1. **Extract the project folder** to your desired location (e.g., `C:\Projects\dorm_manager\`)

2. **Open PowerShell** in the project folder (Shift + Right-click → "Open PowerShell window here")

3. **Run the setup script:**
   ```powershell
   .\setup.ps1
   ```

4. **Follow the prompts:**
   - Enter your ngrok authtoken
   - Enter database password (leave empty if no password)
   - Enter email credentials for notifications

5. **Start the application:**
   ```powershell
   .\start-app.ps1
   ```

The script will:
- ✅ Install all PHP dependencies
- ✅ Install all Node.js dependencies
- ✅ Create database and run migrations
- ✅ Seed demo data
- ✅ Configure ngrok
- ✅ Build frontend assets

---

## 🔧 Manual Setup (Step-by-Step)

### Step 1: Database Setup

1. **Start MySQL** (via XAMPP or standalone)

2. **Create database:**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create new database named: `dormitory_db`
   - Collation: `utf8mb4_unicode_ci`

### Step 2: Configure Environment

1. **Copy environment file:**
   ```powershell
   Copy-Item .env.example .env
   ```

2. **Edit `.env` file** with your settings:
   ```
   DB_DATABASE=dormitory_db
   DB_USERNAME=root
   DB_PASSWORD=your_password_here
   
   MAIL_USERNAME=your_email@gmail.com
   MAIL_PASSWORD=your_app_password_here
   ```

### Step 3: Install Dependencies

```powershell
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Generate application key
php artisan key:generate
```

### Step 4: Database Migration & Seeding

```powershell
# Run migrations
php artisan migrate

# Seed demo data (optional but recommended for testing)
php artisan db:seed
```

### Step 5: Build Frontend Assets

```powershell
npm run build
```

### Step 6: Setup ngrok

1. **Download ngrok:** https://ngrok.com/download

2. **Extract to:** `C:\ngrok\` (or any folder)

3. **Add to PATH** (optional):
   - Open System Properties → Environment Variables
   - Edit PATH, add: `C:\ngrok\`

4. **Authenticate ngrok:**
   ```powershell
   C:\ngrok\ngrok.exe config add-authtoken YOUR_AUTHTOKEN_HERE
   ```

### Step 7: Configure PayMongo Webhooks

1. **Start the application** (see "Running the Application" below)

2. **Get your ngrok URL** (shown in terminal or visit http://localhost:4040)

3. **Add webhook in PayMongo:**
   - Visit: https://dashboard.paymongo.com/developers
   - Click "Create webhook"
   - URL: `https://your-ngrok-url.ngrok-free.dev/webhooks/paymongo`
   - Events: `link.payment.paid`, `payment.paid`, `checkout_session.payment.paid`
   - Save and copy the **Webhook Signing Secret** (starts with `whsk_`)

4. **Update `.env` file:**
   ```
   PAYMONGO_WEBHOOK_SECRET=whsk_your_secret_here
   ```

5. **Clear config cache:**
   ```powershell
   php artisan config:clear
   ```

---

## 🎮 Running the Application

### Easy Way (Recommended):

```powershell
.\start-app.ps1
```

This will:
- Start Laravel development server (http://127.0.0.1:8000)
- Start ngrok tunnel (public HTTPS URL)
- Display all URLs you need

### Manual Way:

Open **2 separate PowerShell windows**:

**Window 1 - Laravel:**
```powershell
php artisan serve
```

**Window 2 - ngrok:**
```powershell
C:\ngrok\ngrok.exe http 8000
```

---

## 👥 Demo Accounts

After seeding, these accounts are available:

### Admin Account:
- **Email:** admin@dorm.test
- **Password:** password
- **Access:** Full system access

### Staff Account:
- **Email:** staff@dorm.test
- **Password:** password
- **Access:** Limited admin features

### Tenant Accounts:
- **Email Pattern:** tenant{1-20}@dorm.test
- **Password:** password
- **Access:** Tenant portal only

---

## 🧪 Testing PayMongo Webhooks

### Test Mode Credentials:
Use PayMongo's test mode (already configured in `.env.example`):

- **Test Cards:** https://developers.paymongo.com/docs/testing
- **GCash Test:** Use any mobile number in test mode
- **QR Ph Test:** Use test QR codes

### Testing Steps:

1. **Login as tenant** (e.g., tenant1@dorm.test)

2. **View "My Bill"**

3. **Click "Pay with PayMongo"**

4. **Complete test payment** using test credentials

5. **Watch the logs** (optional):
   ```powershell
   Get-Content storage\logs\laravel.log -Wait -Tail 20
   ```

6. **Verify payment recorded:**
   - Refresh tenant portal
   - Check admin panel → Payments

### Monitor Webhooks:
- **ngrok Dashboard:** http://localhost:4040
- Shows all incoming webhook requests in real-time
- Useful for debugging

---

## 📱 Accessing the Application

### Local URLs:
- **Application:** http://127.0.0.1:8000
- **Admin Panel:** http://127.0.0.1:8000/dashboard
- **Tenant Portal:** http://127.0.0.1:8000/tenant-portal

### Public URL (for PayMongo webhooks):
- **ngrok URL:** Check terminal output or http://localhost:4040
- Example: https://your-unique-id.ngrok-free.dev

### Important Note on ngrok Free Plan:
- First-time visitors see a warning page (must click "Visit Site")
- This is normal for free ngrok accounts
- For production, upgrade to paid plan ($8/month) to remove warning

---

## 🔒 Important Security Notes

### For Presentation:

1. **Use Test Mode:**
   - All PayMongo credentials in `.env` are test keys (starting with `pk_test_` and `sk_test_`)
   - No real money will be charged

2. **Demo Data:**
   - Database seeder creates fake tenant data
   - Safe for demonstration purposes

3. **Email Notifications:**
   - Configure real Gmail account for email testing
   - Use Gmail App Password, not your regular password
   - Guide: https://support.google.com/accounts/answer/185833

### For Production (After Presentation):

1. **Change all passwords**
2. **Use PayMongo live keys**
3. **Setup permanent domain** (not ngrok)
4. **Enable HTTPS** on your server
5. **Set `APP_DEBUG=false`** in `.env`
6. **Set `APP_ENV=production`** in `.env`

---

## 🛠️ Troubleshooting

### Issue: "SQLSTATE[HY000] [2002] No connection could be made"
**Solution:** Start MySQL/XAMPP first, then run the application

### Issue: "Class 'XXX' not found"
**Solution:** 
```powershell
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: Webhook not received
**Solutions:**
1. Check ngrok is running
2. Verify webhook URL in PayMongo dashboard
3. Check webhook secret in `.env` matches PayMongo
4. View ngrok dashboard: http://localhost:4040

### Issue: "npm command not found"
**Solution:** Install Node.js and restart PowerShell

### Issue: Port 8000 already in use
**Solution:** 
```powershell
# Find and kill process using port 8000
netstat -ano | findstr :8000
taskkill /PID <PID_NUMBER> /F

# Or use different port
php artisan serve --port=8001
```

### Issue: Frontend styles not loading
**Solution:**
```powershell
npm run build
php artisan view:clear
```

---

## 📞 Support

For issues during setup, check:
1. `storage/logs/laravel.log` - Application logs
2. http://localhost:4040 - ngrok request inspector
3. Browser Developer Console (F12) - Frontend errors

---

## ✅ Pre-Presentation Checklist

- [ ] Database seeded with demo data
- [ ] All dependencies installed
- [ ] Frontend assets built
- [ ] ngrok configured and running
- [ ] PayMongo webhook added and secret updated
- [ ] Test payment successful
- [ ] Admin login working
- [ ] Tenant portal working
- [ ] Email notifications configured (optional)
- [ ] All passwords documented

Good luck with your presentation! 🎉
