# Quick Setup: Resend + PayMongo for Production

## 🚀 Quick Start (5 Minutes)

### Part 1: Resend Email Setup

#### 1. Get Resend API Key
```
1. Go to: https://resend.com/signup
2. Sign up / Log in
3. Go to: https://resend.com/api-keys
4. Click "Create API Key"
5. Copy the key (starts with re_)
```

#### 2. Add to Render
```
Render Dashboard → Your Service → Environment → Add Variable

MAIL_MAILER=resend
RESEND_API_KEY=re_YOUR_KEY_HERE
MAIL_FROM_ADDRESS=noreply@questdorm.onresend.com
MAIL_FROM_NAME=Quest Building
```

✅ **Done!** Emails will now send via Resend.

---

### Part 2: PayMongo Production Webhook

#### 1. Update Webhook URL
```
1. Go to: https://dashboard.paymongo.com/developers/webhooks
2. Delete the old ngrok webhook
3. Click "Add Webhook"
4. URL: https://questdorm.onrender.com/paymongo/webhook
5. Events: Select all payment events
6. Create
7. Copy the webhook secret
```

#### 2. Update Render Environment
```
Render Dashboard → Your Service → Environment

Update this variable:
PAYMONGO_WEBHOOK_SECRET=whsk_YOUR_NEW_SECRET_HERE

Also verify these exist:
PAYMONGO_PUBLIC_KEY=pk_test_...
PAYMONGO_SECRET_KEY=sk_test_...
APP_URL=https://questdorm.onrender.com
```

#### 3. Save & Deploy
```
Click "Save Changes" in Render
Wait for automatic redeploy (~5-10 minutes)
```

---

## 🧪 Testing

### Test Email
1. Log into your app (on Render)
2. Go to Email Notifications
3. Send a test email
4. Check https://resend.com/emails for delivery status

### Test PayMongo
1. Log in as Owner
2. Create a payment link from any billing statement
3. Complete payment with test card: `4120 0000 0000 007`
4. Check Render logs for: `PayMongo webhook: payment recorded`
5. Verify payment shows in billing statement

---

## 📋 Environment Variables Checklist

Copy these to **Render Environment**:

```env
# Core
APP_NAME="Quest Building"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://questdorm.onrender.com

# Database (Clever Cloud)
DB_CONNECTION=mysql
DB_HOST=bio0zbdnyef0m1cqyjl6-mysql.services.clever-cloud.com
DB_PORT=3306
DB_DATABASE=bio0zbdnyef0m1cqyjl6
DB_USERNAME=[from Clever Cloud]
DB_PASSWORD=[from Clever Cloud]

# Email (Resend)
MAIL_MAILER=resend
RESEND_API_KEY=re_[your_key]
MAIL_FROM_ADDRESS=noreply@questdorm.onresend.com
MAIL_FROM_NAME="Quest Building"

# PayMongo
PAYMONGO_PUBLIC_KEY=pk_test_[your_key]
PAYMONGO_SECRET_KEY=sk_test_[your_key]
PAYMONGO_WEBHOOK_SECRET=whsk_[your_secret]

# Logging
LOG_CHANNEL=errorlog
LOG_LEVEL=error
```

---

## 🔍 Troubleshooting

### Emails Not Sending?
- Check RESEND_API_KEY is correct
- Check MAIL_FROM_ADDRESS is valid
- Go to https://resend.com/emails - see any errors?
- Check Render logs for email errors

### PayMongo Webhook Failing?
- Check webhook URL: `https://questdorm.onrender.com/paymongo/webhook`
- Check PAYMONGO_WEBHOOK_SECRET matches
- Go to PayMongo Dashboard → Webhooks → Logs
- Check Render logs for webhook errors
- Verify app is not sleeping (Render free tier sleeps after 15 min inactivity)

### App Shows "Service Unavailable"?
- Render free tier sleeps after inactivity
- First request wakes it up (takes 30-60 seconds)
- Keep app awake with a cron job (or upgrade to paid)

---

## 🎯 Success Checklist

After setup, verify:

- [ ] Can send emails (check Resend dashboard)
- [ ] Emails arrive in inbox (not spam)
- [ ] Can create PayMongo payment links
- [ ] Test payment completes successfully  
- [ ] Webhook fires (check Render logs)
- [ ] Payment recorded in app
- [ ] Billing statement status updates

---

## 📞 Where to Get Help

- **Resend Docs**: https://resend.com/docs
- **PayMongo Docs**: https://developers.paymongo.com/docs
- **Render Docs**: https://render.com/docs

## 🔗 Quick Links

- Resend Dashboard: https://resend.com/
- PayMongo Dashboard: https://dashboard.paymongo.com/
- Render Dashboard: https://dashboard.render.com/
- Your App: https://questdorm.onrender.com
