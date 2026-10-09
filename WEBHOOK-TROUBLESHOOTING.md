# PayMongo Webhook Not Working - Troubleshooting Guide

## Current Issue

✅ **Payment link generation**: Working  
✅ **Tenant makes payment**: Working (PayMongo shows payment received)  
❌ **Webhook notification**: NOT reaching Laravel  
❌ **Database update**: Not happening (payment still shows unpaid)

---

## Why This Happens

When a tenant pays via PayMongo:
1. PayMongo processes the payment ✅
2. PayMongo tries to send a webhook to your app ❌ **FAILING HERE**
3. Your app receives webhook and updates database (never happens)

The webhook is **not reaching your Laravel application**.

---

## Most Common Causes

### 1. Ngrok URL Changed

**Problem**: Every time you restart ngrok (free plan), you get a NEW URL.

**Check**:
```powershell
# In your ngrok terminal, look for:
Forwarding    https://abc123.ngrok-free.app -> http://localhost:8000
```

**Solution**:
1. Copy the NEW ngrok URL
2. Go to: https://dashboard.paymongo.com/developers/webhooks
3. Edit your webhook
4. Change URL to: `https://YOUR-NEW-NGROK-URL.ngrok-free.app/paymongo/webhook`
5. Save

**This is the #1 reason webhooks stop working!**

---

### 2. Webhook Not Configured in PayMongo

**Check**: Go to PayMongo dashboard → Developers → Webhooks

**You should see**:
- URL: `https://YOUR-NGROK-URL.ngrok-free.app/paymongo/webhook`
- Events: `payment.paid` or `link.payment.paid`
- Status: Active

**If missing**: Create a new webhook with these settings.

---

### 3. Test Mode vs Live Mode Mismatch

**Problem**: Your webhook is configured for "Live" mode but you're making test payments.

**Check**: Both your API keys and webhook must be in the same mode (test or live).

---

### 4. Laravel Not Running

**Check**:
```powershell
# Make sure this is running:
php artisan serve --port=8000
```

**Also check**:
```powershell
# Make sure ngrok is pointing to the correct port:
ngrok http 8000
```

---

## Step-by-Step Verification

### Step 1: Check if Laravel is accessible

1. Open browser
2. Go to your ngrok URL: `https://YOUR-NGROK-URL.ngrok-free.app`
3. You should see your Laravel app login page

**If you see "ngrok not found" or error**: Laravel not running or ngrok not connected.

---

### Step 2: Test Webhook Endpoint Manually

```powershell
# In PowerShell, test if the webhook endpoint exists:
Invoke-WebRequest -Uri "https://YOUR-NGROK-URL.ngrok-free.app/paymongo/webhook" -Method POST
```

**Expected**: Should return 401 (invalid signature) or 200  
**If 404**: Route not defined or Laravel not running

---

### Step 3: Check Ngrok Requests

In the ngrok terminal window, you should see:
```
POST /paymongo/webhook  200 OK
```

**If you DON'T see this**: PayMongo is not sending webhooks to your ngrok URL.

---

### Step 4: Check PayMongo Webhook Logs

1. Go to: https://dashboard.paymongo.com/developers/webhooks
2. Click on your webhook
3. Check "Recent Deliveries" or "Logs"
4. Look for failed deliveries

**Common errors**:
- "Connection timeout" → Laravel not running
- "404 Not Found" → Wrong URL or route not defined
- "Connection refused" → Ngrok not running

---

## How to Fix (Most Likely Solution)

### Quick Fix Checklist

```powershell
# 1. Make sure Laravel is running
php artisan serve --port=8000
# Keep this terminal open!

# 2. In a NEW terminal, start ngrok
ngrok http 8000

# 3. Copy the ngrok URL shown (https://abc123.ngrok-free.app)

# 4. Update PayMongo webhook URL
# Go to: https://dashboard.paymongo.com/developers/webhooks
# Edit webhook → Change URL to: https://abc123.ngrok-free.app/paymongo/webhook
# Save

# 5. Test by making a new payment
```

---

## Verify It's Working

After fixing, check Laravel logs:

```powershell
Get-Content storage\logs\laravel.log -Tail 20 -Wait
```

**You should see**:
```
[timestamp] local.INFO: PayMongo webhook received
[timestamp] local.INFO: PayMongo webhook verified
[timestamp] local.INFO: PayMongo: TenantPayment found
[timestamp] local.INFO: PayMongo webhook: payment recorded
```

**If you see these logs**: ✅ Webhook is working!

---

## Alternative: Use Ngrok Paid Plan

Free ngrok has limitations:
- ❌ URL changes every restart
- ❌ 2-hour timeout
- ❌ Limited connections

Paid ngrok ($8/month):
- ✅ Fixed URL (never changes)
- ✅ No timeout
- ✅ Can set webhook ONCE and forget it

OR use a production server with a real domain name.

---

## Testing Without Webhook

If you need to test immediately without fixing webhooks:

### Manual Database Update

```powershell
php artisan tinker
```

```php
// Find the tenant payment
$tp = \App\Models\TenantPayment::where('paymongo_link_id', 'link_c30cb9e5352ad75235e00abc')->first();

// Mark as paid
$tp->update([
    'status' => 'paid',
    'amount_paid' => $tp->share_amount,
    'paid_at' => now(),
]);

// Recompute billing statement status
$tp->invoice->recomputeStatus();

echo "✅ Manually marked as paid!";
```

**This simulates what the webhook would do.**

---

## Summary

Your issue is **NOT with your code**. The code is working correctly.

The issue is that **PayMongo webhooks are not reaching your app** because:
- Most likely: Ngrok URL changed and webhook URL is outdated
- Or: Webhook not configured in PayMongo dashboard
- Or: Laravel/ngrok not running

**Solution**: Update the webhook URL in PayMongo dashboard with your current ngrok URL every time you restart ngrok.

**Long-term solution**: Use ngrok paid plan OR deploy to a production server.
