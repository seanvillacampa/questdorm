# PayMongo Integration Setup Guide

## 🔧 Complete Setup Steps

### 1. Start Your Laravel Development Server

```powershell
# Open PowerShell in your project directory
php artisan serve --host=127.0.0.1 --port=8000
```

**Keep this running!** Don't close this terminal.

---

### 2. Start Ngrok to Create Public URL

```powershell
# Open a NEW PowerShell window
ngrok http 8000
```

**Output will show:**
```
Forwarding    https://abc123.ngrok-free.app -> http://localhost:8000
```

**Copy the `https://abc123.ngrok-free.app` URL** - this changes every time you restart ngrok!

---

### 3. Configure PayMongo Webhook

1. Go to: https://dashboard.paymongo.com/developers/webhooks
2. Click "Add Webhook"
3. **Webhook URL:** `https://abc123.ngrok-free.app/paymongo/webhook`
   - Replace `abc123` with your actual ngrok subdomain
4. **Events to listen:** Select `payment.paid` or all payment events
5. Click "Create"
6. **Copy the Webhook Secret** (starts with `whsec_`)

---

### 4. Update .env File

```env
PAYMONGO_SECRET_KEY=sk_test_YOUR_SECRET_KEY_HERE
PAYMONGO_PUBLIC_KEY=pk_test_YOUR_PUBLIC_KEY_HERE
PAYMONGO_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET_HERE
```

After updating `.env`:
```powershell
php artisan config:clear
```

---

### 5. Test Payment Flow

#### A. Generate Payment Link (as Owner)
1. Login as owner/admin
2. Go to Billing Statements → Select a billing statement
3. Click "Generate Payment Link"
4. A PayMongo link will be created

#### B. Pay as Tenant
1. Open the PayMongo link (or login as tenant and click "Pay Now")
2. Complete test payment using:
   - **Card:** `4120 0000 0000 007`
   - **Expiry:** Any future date
   - **CVC:** Any 3 digits

#### C. Webhook Triggers Automatically
- PayMongo sends webhook to your ngrok URL
- Your Laravel app receives it at `/paymongo/webhook`
- Payment is recorded in database

---

## 🐛 Troubleshooting: Why Payment Not Showing?

### Check 1: Is Ngrok Running?
```powershell
# In the ngrok window, you should see:
# POST /paymongo/webhook  200 OK
```

If you DON'T see this, the webhook never reached your app!

### Check 2: Check Laravel Logs
```powershell
# Open a NEW PowerShell window
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

**Look for:**
- ✅ `PayMongo webhook received`
- ✅ `PayMongo webhook verified`
- ✅ `PayMongo: TenantPayment found`
- ✅ `PayMongo webhook: payment recorded`

**Red flags:**
- ❌ `Invalid signature` → Wrong webhook secret in .env
- ❌ `TenantPayment NOT found` → Link not properly associated
- ❌ `could not match billing statement` → Billing statement matching failed

### Check 3: Verify Database Changes
```powershell
php artisan tinker
```

```php
// Check if TenantPayment was marked as paid
$tp = \App\Models\TenantPayment::latest()->first();
echo "Status: " . $tp->status . "\n";
echo "Amount Paid: " . $tp->amount_paid . "\n";
echo "Paid At: " . $tp->paid_at . "\n";

// Check if Payment record was created
$payment = \App\Models\Payment::latest()->first();
echo "Amount: " . $payment->amount . "\n";
echo "Method: " . $payment->method . "\n";
echo "Billing Statement ID: " . $payment->invoice_id . "\n";

// Check billing statement status
$invoice = \App\Models\Invoice::find($payment->invoice_id);
echo "Billing Statement Status: " . $invoice->status . "\n";
```

---

## 🔄 Common Issues & Solutions

### Issue 1: "Payment showing in PayMongo but not in app"

**Cause:** Webhook not reaching your Laravel app

**Solution:**
1. Check if ngrok is still running (it times out after 2 hours on free plan)
2. If you restarted ngrok, the URL changed - update PayMongo webhook!
3. Check Laravel logs - if no "webhook received" log, webhook isn't reaching app

---

### Issue 2: "Webhook received but payment not recorded"

**Cause:** Billing statement matching failed

**Solution:**
Run this debug script:

```powershell
php debug-paymongo-payment.php
```

Create `debug-paymongo-payment.php`:
```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TenantPayment;
use App\Models\Invoice;
use App\Models\Payment;

// Get the last payment link created
$tenantPay = TenantPayment::whereNotNull('paymongo_link_id')
    ->latest()
    ->first();

if (!$tenantPay) {
    echo "❌ No payment links found in database!\n";
    exit;
}

echo "Last Payment Link Created:\n";
echo "  TenantPayment ID: {$tenantPay->id}\n";
echo "  Tenant ID: {$tenantPay->tenant_id}\n";
echo "  Billing Statement ID: {$tenantPay->invoice_id}\n";
echo "  Link ID: {$tenantPay->paymongo_link_id}\n";
echo "  Status: {$tenantPay->status}\n";
echo "  Amount Paid: {$tenantPay->amount_paid}\n";
echo "  Paid At: {$tenantPay->paid_at}\n\n";

// Check if payment was recorded
$payment = Payment::where('invoice_id', $tenantPay->invoice_id)
    ->latest()
    ->first();

if ($payment) {
    echo "✅ Payment Record Found:\n";
    echo "  Amount: ₱{$payment->amount}\n";
    echo "  Method: {$payment->method}\n";
    echo "  Reference: {$payment->reference}\n";
} else {
    echo "❌ No payment record found for this billing statement!\n";
}

// Check billing statement status
$invoice = Invoice::find($tenantPay->invoice_id);
echo "\nBilling Statement Status: {$invoice->status}\n";
echo "Billing Statement Number: {$invoice->invoice_number}\n";
```

---

### Issue 3: "Payment recorded but status still 'pending'"

**Cause:** Billing statement status not recomputed, or TenantPayment.status not updated

**Solution:**
```powershell
php artisan invoices:resync-statuses
```

This will recompute all billing statement statuses from their TenantPayment records.

---

### Issue 4: "Paid in PayMongo but showing in tenant portal as unpaid"

**Cause:** The tenant portal shows the OLDEST unpaid billing statement, not the one just paid

**Check:**
1. Does this tenant have older unpaid billing statements?
2. If yes, the system correctly shows the oldest one first
3. The billing statement you just paid might be for a newer month

**To verify:**
```powershell
php check-tenant-billing-statements.php
```

Create `check-tenant-billing-statements.php`:
```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenantId = 1; // Change this to your tenant ID

$tenantPayments = \App\Models\TenantPayment::where('tenant_id', $tenantId)
    ->with('invoice')
    ->join('invoices', 'tenant_payments.invoice_id', '=', 'invoices.id')
    ->orderBy('invoices.billing_month', 'desc')
    ->select('tenant_payments.*')
    ->get();

echo "All billing statements for Tenant #{$tenantId}:\n\n";
foreach ($tenantPayments as $tp) {
    echo "{$tp->invoice->billing_month} - {$tp->status} - ₱{$tp->share_amount}\n";
    echo "  Amount Paid: ₱" . ($tp->amount_paid ?? 0) . "\n";
    echo "  Billing Statement Status: {$tp->invoice->status}\n\n";
}
```

---

## 📋 Every Time You Setup (Daily Workflow)

1. ✅ Start Laravel: `php artisan serve --port=8000`
2. ✅ Start Ngrok: `ngrok http 8000`
3. ✅ Copy new ngrok URL (it changes every restart!)
4. ✅ Update PayMongo webhook URL in dashboard
5. ✅ Test a payment
6. ✅ Check logs: `Get-Content storage\logs\laravel.log -Tail 20`

---

## 🎯 Quick Verification Checklist

After making a test payment:

- [ ] Ngrok showed: `POST /paymongo/webhook 200 OK`
- [ ] Laravel log shows: `PayMongo webhook: payment recorded`
- [ ] Database: TenantPayment.status = 'paid'
- [ ] Database: TenantPayment.amount_paid = share_amount
- [ ] Database: Payment record created
- [ ] Database: Invoice.status updated (paid/partial)
- [ ] Owner portal: Payment shows in billing statement details
- [ ] Tenant portal: Paid billing statement no longer shows as unpaid

---

## 🔑 Important Notes

1. **Ngrok URL changes every restart** - always update PayMongo webhook!
2. **Webhook secret is different from API secret** - don't confuse them
3. **Free ngrok times out after 2 hours** - need to restart and update URL
4. **Tenant portal shows OLDEST unpaid first** - not the most recent
5. **Cascade payment logic** - paying latest billing statement marks all older as paid if carry_over_balance exists
6. **Always check logs first** - they tell you exactly what happened

---

## 📞 Need More Help?

Run the comprehensive debug script:

```powershell
php debug-paymongo-system.php
```

This will check:
- Environment variables
- Database state
- Recent webhook activity
- Payment records
- Billing statement statuses
