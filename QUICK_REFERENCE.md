# Quick Reference Card

## 🚀 Commands

```powershell
# First time setup
.\setup.ps1

# Start application
.\start-app.ps1

# Stop application
.\stop-app.ps1

# Reset database
php artisan migrate:fresh --seed

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 🔑 Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@dorm.test | password |
| Staff | staff@dorm.test | password |
| Tenant | tenant1@dorm.test | password |
| Tenant | tenant2@dorm.test | password |

---

## 🌐 URLs

| Service | URL |
|---------|-----|
| Application | http://127.0.0.1:8000 |
| Admin Panel | http://127.0.0.1:8000/dashboard |
| Tenant Portal | http://127.0.0.1:8000/tenant-portal |
| ngrok Dashboard | http://localhost:4040 |

---

## 💳 PayMongo Test Credentials

### Test Cards:
- **Success:** 4343434343434345
- **CVV:** Any 3 digits
- **Expiry:** Any future date

### GCash Test:
- **Mobile:** Any 11-digit number (09XXXXXXXXX)
- **OTP:** Any 6 digits

---

## 🔧 Troubleshooting

### Port Already in Use:
```powershell
.\stop-app.ps1
.\start-app.ps1
```

### Database Connection Failed:
1. Check MySQL is running (XAMPP)
2. Check credentials in `.env`

### Webhook Not Working:
1. Check ngrok is running: http://localhost:4040
2. Check webhook URL in PayMongo matches ngrok URL
3. Check webhook secret in `.env` matches PayMongo
4. View logs: `storage/logs/laravel.log`

### White Screen/Errors:
```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
npm run build
```

---

## 📁 Important Files

| File | Purpose |
|------|---------|
| `.env` | Configuration (database, PayMongo keys) |
| `storage/logs/laravel.log` | Application logs |
| `database/seeders/` | Demo data seeders |
| `app/Http/Controllers/PayMongoController.php` | Webhook handler |

---

## 🎯 Demo Flow Checklist

- [ ] Start application
- [ ] Note ngrok URL
- [ ] Login as admin
- [ ] Login as tenant (different browser/incognito)
- [ ] Open ngrok dashboard
- [ ] Show unpaid billing statement
- [ ] Make payment
- [ ] Show webhook received
- [ ] Show payment recorded
- [ ] Show billing statement updated

---

## 📞 Emergency Contacts

**If something breaks during presentation:**

1. Check logs: `storage/logs/laravel.log`
2. Check ngrok: http://localhost:4040
3. Reset database: `php artisan migrate:fresh --seed`
4. Restart app: `.\stop-app.ps1` then `.\start-app.ps1`

---

## 💡 Key Features to Highlight

✅ Real-time payment webhooks
✅ Multi-tenant room sharing
✅ Automatic billing calculations
✅ Meter reading tracking
✅ Email notifications
✅ Audit logging
✅ Financial reports

---

**Print this card and keep it handy during presentation!**
