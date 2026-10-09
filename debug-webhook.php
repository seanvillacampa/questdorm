<?php
/**
 * PayMongo Webhook Debugging Script
 * 
 * Usage: Run this after a test payment to check:
 * 1. Recent log entries
 * 2. Invoice status
 * 3. Payment records
 * 4. TenantPayment status
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== PayMongo Webhook Debug Report ===\n\n";

// 1. Check recent log entries
echo "1. Recent Laravel Log Entries (last 50 lines):\n";
echo str_repeat('-', 60) . "\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -50);
    foreach ($recent as $line) {
        if (stripos($line, 'paymongo') !== false || stripos($line, 'webhook') !== false) {
            echo $line;
        }
    }
} else {
    echo "Log file not found at: $logFile\n";
}
echo "\n";

// 2. Check recent invoices
echo "2. Recent Unpaid Invoices:\n";
echo str_repeat('-', 60) . "\n";
$invoices = \App\Models\Invoice::whereNotIn('status', ['paid', 'void'])
    ->with(['contract.room', 'tenantPayments'])
    ->latest()
    ->take(5)
    ->get();

foreach ($invoices as $inv) {
    echo "Invoice: {$inv->invoice_number}\n";
    echo "  Status: {$inv->status}\n";
    echo "  Total: ₱" . number_format($inv->total_amount, 2) . "\n";
    echo "  Paid: ₱" . number_format($inv->amount_paid, 2) . "\n";
    echo "  Balance: ₱" . number_format($inv->balanceDue(), 2) . "\n";
    echo "  Room: {$inv->contract->room->room_number}\n";
    echo "  PayMongo Link ID: " . ($inv->paymongo_link_id ?? 'none') . "\n";
    echo "  TenantPayments:\n";
    foreach ($inv->tenantPayments as $tp) {
        echo "    - Tenant #{$tp->tenant_id}: ₱{$tp->share_amount} (paid: ₱{$tp->amount_paid}) - {$tp->status}\n";
        echo "      Link ID: " . ($tp->paymongo_link_id ?? 'none') . "\n";
    }
    echo "\n";
}

// 3. Check recent payments
echo "3. Recent Payment Records:\n";
echo str_repeat('-', 60) . "\n";
$payments = \App\Models\Payment::with('invoice')
    ->latest()
    ->take(10)
    ->get();

if ($payments->isEmpty()) {
    echo "No payments found in database.\n";
} else {
    foreach ($payments as $payment) {
        echo "Payment ID: {$payment->id}\n";
        echo "  Invoice: {$payment->invoice->invoice_number}\n";
        echo "  Amount: ₱" . number_format($payment->amount, 2) . "\n";
        echo "  Method: {$payment->method}\n";
        echo "  Reference: {$payment->reference}\n";
        echo "  Date: {$payment->received_at}\n";
        echo "  Notes: {$payment->notes}\n";
        echo "\n";
    }
}

// 4. Check .env configuration
echo "4. PayMongo Configuration:\n";
echo str_repeat('-', 60) . "\n";
echo "PAYMONGO_SECRET_KEY: " . (config('paymongo.secret_key') ? 'SET (starts with: ' . substr(config('paymongo.secret_key'), 0, 12) . '...)' : 'NOT SET') . "\n";
echo "PAYMONGO_PUBLIC_KEY: " . (config('paymongo.public_key') ? 'SET (starts with: ' . substr(config('paymongo.public_key'), 0, 12) . '...)' : 'NOT SET') . "\n";
echo "PAYMONGO_WEBHOOK_SECRET: " . (config('paymongo.webhook_secret') ? 'SET (starts with: ' . substr(config('paymongo.webhook_secret'), 0, 12) . '...)' : 'NOT SET') . "\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "Webhook URL: " . config('app.url') . "/webhooks/paymongo\n";
echo "\n";

// 5. Test webhook endpoint accessibility
echo "5. Webhook Endpoint Test:\n";
echo str_repeat('-', 60) . "\n";
echo "Testing if webhook endpoint is accessible...\n";
$webhookUrl = config('app.url') . '/webhooks/paymongo/ping';
echo "Ping URL: $webhookUrl\n";

try {
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✓ Webhook endpoint is accessible (HTTP $httpCode)\n";
        echo "Response: $response\n";
    } else {
        echo "✗ Webhook endpoint returned HTTP $httpCode\n";
        echo "Response: $response\n";
    }
} catch (\Exception $e) {
    echo "✗ Could not reach webhook endpoint: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== End of Debug Report ===\n";
echo "\nNext Steps:\n";
echo "1. Check if webhook logs appear in section 1\n";
echo "2. Verify your invoice has a paymongo_link_id (section 2)\n";
echo "3. Check if Payment records are created (section 3)\n";
echo "4. Ensure webhook endpoint is accessible from internet (section 5)\n";
echo "5. Check PayMongo dashboard for webhook delivery status\n";
echo "\n";
