# Presentation Guide - Dorm Management System

## 📋 Pre-Presentation Checklist

### Day Before Presentation:

- [ ] Run `.\setup.ps1` on presentation laptop
- [ ] Verify database has demo data (`php artisan db:seed`)
- [ ] Test admin login works
- [ ] Test tenant login works
- [ ] Configure PayMongo webhook with ngrok URL
- [ ] Make at least 1 successful test payment
- [ ] Take screenshots as backup (in case of internet issues)
- [ ] Note down all passwords
- [ ] Clear browser cache and cookies
- [ ] Charge laptop fully!

### 30 Minutes Before Presentation:

- [ ] Connect to stable WiFi/hotspot
- [ ] Run `.\start-app.ps1`
- [ ] Note the ngrok URL
- [ ] Verify PayMongo webhook is configured correctly
- [ ] Open these tabs in browser:
  - Admin panel (http://127.0.0.1:8000/dashboard)
  - Tenant portal (http://127.0.0.1:8000/tenant-portal)
  - ngrok dashboard (http://localhost:4040)
  - PayMongo dashboard (for showing webhook logs)
- [ ] Login to both admin and tenant accounts
- [ ] Have backup screenshots ready

---

## 🎬 Presentation Flow (Recommended)

### 1. Introduction (2 minutes)

**Show:** Dashboard home page

**Say:**
> "This is a comprehensive Dorm Management System designed to automate and streamline dormitory operations. It includes tenant management, automated billing, payment processing via PayMongo, and real-time reporting."

**Key Points:**
- Built with Laravel 12 (modern PHP framework)
- Real-time payment integration with PayMongo
- Separate portals for admin and tenants

---

### 2. Room & Tenant Management (3 minutes)

**Show:** 
1. Rooms page → Show grid view with room statuses
2. Add/Edit room → Show capacity tracking
3. Tenants page → Show tenant list
4. Create contract → Show multi-tenant selection with capacity warning

**Key Features to Demonstrate:**
- ✅ Visual room status (vacant, occupied, pending)
- ✅ Room capacity enforcement (try adding more tenants than capacity)
- ✅ Automatic status updates when contracts added
- ✅ Contract management with start/end dates

**Demo Script:**
```
1. Go to Rooms → Show room 101 is vacant
2. Go to Contracts → Create New
3. Select Room 101, try to add 3 tenants for a 2-bed room
4. Show capacity warning appears
5. Remove one tenant, create contract
6. Go back to Rooms → Room 101 now shows as "Occupied"
```

---

### 3. Billing & Billing Statements (4 minutes)

**Show:**
1. Create manual billing statement for a tenant
2. Show billing statement details with breakdown (rent + utilities)
3. Show billing statement status lifecycle (pending → partial → paid)

**Key Features:**
- ✅ Automatic rent calculation based on room sharing
- ✅ Utility billing based on meter readings
- ✅ Carried forward unpaid balances
- ✅ Multiple payment methods support

**Demo Script:**
```
1. Go to Meter Readings → Show recorded readings for current month
2. Go to Billing Statements → Create Billing Statement
3. Select a contract, show calculation breakdown
4. Show rent divided by number of tenants
5. Show electricity calculated from meter readings
6. Create billing statement and show it in list
```

---

### 4. Payment Processing with PayMongo (5-7 minutes)

**This is the highlight! Take your time here.**

**Show:**
1. Tenant portal → My Bill page
2. Click "Pay with PayMongo"
3. Complete payment using test credentials
4. Show webhook received in ngrok dashboard
5. Show payment automatically recorded in system
6. Show billing statement status updated to "paid"
7. Show admin notification of payment

**Key Features:**
- ✅ Multiple payment methods (GCash, Card, QR Ph)
- ✅ Real-time webhook integration
- ✅ Automatic payment matching to billing statements
- ✅ Instant updates on both tenant and admin sides

**Demo Script:**
```
PREPARE:
- Have tenant1@dorm.test logged in on one browser
- Have admin@dorm.test logged in on another browser/tab
- Have ngrok dashboard open (http://localhost:4040)

DEMONSTRATE:
1. Tenant Portal:
   - "Here's the tenant's view of their bill"
   - Show balance due: ₱X,XXX.XX
   - Click "Pay ₱X,XXX.XX with PayMongo"
   
2. PayMongo Payment:
   - "The system generates a secure payment link"
   - Select payment method (GCash for best demo)
   - Enter test mobile number: 09123456789
   - Click "Send OTP" → Enter any 6 digits (test mode)
   - Complete payment
   - "Payment successful on PayMongo's end"

3. Webhook Magic:
   - Switch to ngrok dashboard (http://localhost:4040)
   - "PayMongo immediately sends a webhook to our system"
   - Show the POST request to /webhooks/paymongo
   - Show status: 200 OK
   - Click on request to show JSON payload

4. Auto-Recording:
   - Refresh tenant portal
   - "Notice the balance is now cleared automatically"
   - Switch to admin panel
   - Go to Payments
   - "The payment was recorded automatically"
   - Show payment details with PayMongo reference

5. Billing Statement Update:
   - Go to Billing Statements
   - Show billing statement status changed to "paid"
   - Show amount paid updated
```

**If Internet Fails:**
- Show pre-recorded video or screenshots
- Explain the webhook flow using ngrok dashboard screenshots
- Show the code that handles webhooks (app/Http/Controllers/PayMongoController.php)

---

### 5. Reports & Analytics (2 minutes)

**Show:**
1. Dashboard → Bar chart showing monthly revenue
2. Reports page → Occupancy report
3. Reports → Arrears report (overdue payments)

**Key Features:**
- ✅ Visual financial analytics
- ✅ Occupancy tracking
- ✅ Overdue payment monitoring
- ✅ Export capabilities

---

### 6. Additional Features (Quick Tour - 2 minutes)

**Show quickly:**
- ✅ Laundry order management
- ✅ Deposit tracking
- ✅ Email notifications (show sent emails)
- ✅ Audit logs (show payment recording logged)
- ✅ User management (staff accounts with limited access)

---

### 7. Technical Highlights (2 minutes)

**For technical audience, mention:**

**Architecture:**
- Laravel 12 (latest version)
- MySQL database
- Blade templates with Alpine.js
- Tailwind CSS for styling

**Integration:**
- PayMongo REST API
- Webhook signature verification
- Real-time tunnel with ngrok
- Gmail SMTP for notifications

**Security:**
- CSRF protection (except webhooks)
- Webhook signature validation
- Role-based access control
- Encrypted payment references

**Best Practices:**
- Database transactions for payment recording
- Automatic billing statement status computation
- Comprehensive audit logging
- Input validation and error handling

---

## 🎤 Talking Points

### What Makes This Special:

1. **Real Payment Integration**
   - "Most student projects use fake payments, ours uses actual PayMongo API"
   - "Webhooks enable real-time, automatic recording"

2. **Production-Ready Features**
   - Role-based access (admin vs tenant)
   - Email notifications
   - Audit logging
   - Error handling

3. **User Experience**
   - Simple, clean interface
   - Mobile-responsive design
   - Real-time updates
   - Helpful warnings and validations

---

## ⚠️ Common Issues & Solutions

### Issue: Payment Doesn't Record

**If this happens during demo:**
1. Stay calm!
2. Check ngrok dashboard → Show webhook was received
3. Check Laravel logs → Show payment logged
4. Explain: "The payment was recorded, let me show you in the database"
5. Run: `php artisan tinker` → Show payment in DB
6. Explain this is a refresh issue, not a system failure

**Prevention:**
- Test the entire flow 30 minutes before
- Have a successful payment already recorded as backup
- Take screenshots of working flow

### Issue: ngrok URL Changed

**If laptop was restarted:**
1. Get new ngrok URL from `start-app.ps1` output
2. Update PayMongo webhook (takes 2 minutes)
3. Explain: "This is why production systems use custom domains"

### Issue: Internet Connectivity

**Backup plans:**
1. Use mobile hotspot as backup internet
2. Show pre-recorded video of payment flow
3. Show screenshots of successful webhook
4. Explain the flow using code walkthrough

### Issue: Database Errors

**If something breaks:**
```powershell
# Reset database to demo state
php artisan migrate:fresh --seed
```

---

## 🎯 Key Demo Scenarios

### Scenario 1: Happy Path (5 minutes)
1. Show vacant room
2. Add contract with tenants
3. Record meter reading
4. Generate billing statement
5. Tenant pays via PayMongo
6. Show payment recorded automatically

### Scenario 2: Payment Verification (3 minutes)
1. Show unpaid billing statement
2. Make payment
3. Show webhook in ngrok dashboard
4. Show payment in admin panel
5. Show billing statement status updated

### Scenario 3: Admin Features (3 minutes)
1. Show room capacity enforcement
2. Show meter reading validation
3. Show reports and analytics
4. Show audit logs

---

## 📸 Backup Screenshots to Prepare

In case of demo failure, have these ready:

1. ✅ Successful PayMongo payment screen
2. ✅ ngrok dashboard showing webhook POST request
3. ✅ Payment recorded in admin panel
4. ✅ Billing statement status changed to "paid"
5. ✅ Tenant portal showing cleared balance
6. ✅ Email notification received
7. ✅ Full dashboard view with data

---

## 💡 Pro Tips

1. **Practice the flow 5+ times** before presentation
2. **Have 2 browsers open** (admin + tenant) for quick switching
3. **Use zoom/large text** so audience can see
4. **Narrate what you're doing** as you click
5. **Slow down** - audience needs time to process
6. **Emphasize the webhook magic** - this is your differentiator
7. **Have code ready** to show if technical questions arise
8. **Know your numbers** - how many tables, lines of code, etc.
9. **Smile and be confident** - you built something real!

---

## 🎓 Expected Questions & Answers

**Q: "Is this using real PayMongo or just fake?"**
A: "Real PayMongo API in test mode. We're using actual API keys and webhooks. In production, we'd just switch to live keys."

**Q: "What if the webhook fails?"**
A: "We log everything. Admin can manually record payment if needed, but webhooks have been 100% reliable in our testing."

**Q: "How do you handle security?"**
A: "Webhook signature verification using PayMongo's signing secret, CSRF protection, encrypted connections via HTTPS, and role-based access control."

**Q: "Can it handle multiple dormitories?"**
A: "Currently single dormitory, but architecture supports multi-tenancy with minor modifications."

**Q: "What about scalability?"**
A: "Built on Laravel which powers millions of users. Could handle hundreds of tenants easily. For larger scale, we'd add Redis caching and queue processing."

**Q: "Why Laravel?"**
A: "Modern, secure, well-documented framework with huge community. Built-in features for auth, validation, and database handling saved development time."

---

## ✅ Final Checks

**5 Minutes Before:**
- [ ] Close unnecessary applications
- [ ] Close unnecessary browser tabs
- [ ] Check battery level
- [ ] Check internet connection
- [ ] Verify both services running (Laravel + ngrok)
- [ ] Have credentials written down
- [ ] Take a deep breath!

**Good luck! You've got this! 🚀**
