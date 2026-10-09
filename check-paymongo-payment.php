<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TenantPayment;

echo "=== PayMongo Payment Diagnostic ===\n\n";

// Get the most recent PayMongo payment
$recentPayment = Payment::where('method', 'like', '%paymongo%')
    ->orWhere('method', 'gcash')
    ->orWhere('method', 'maya')
    ->orWhere('method', 'card')
    ->orderByDesc('received_at')
    ->first();

if (!$recentPayment) {
    echo "❌ No PayMongo payments found in the database.\n";
    echo "\nPossible issues:\n";
    echo "  1. Webhook is not being received\n";
    echo "  2. Webhook signature verification is failing\n";
    echo "  3. Invoice matching is failing\n";
    exit;
}

echo "✅ Found recent PayMongo payment:\n";
echo "   Payment ID: {$recentPayment->id}\n";
echo "   Amount: ₱" . number_format($recentPayment->amount, 2) . "\n";
echo "   Method: {$recentPayment->method}\n";
echo "   Reference: {$recentPayment->reference}\n";
echo "   Received: {$recentPayment->received_at}\n";
echo "   Invoice: {$recentPayment->invoice->invoice_number}\n\n";

// Check the invoice
$invoice = $recentPayment->invoice;
echo "Invoice details:\n";
echo "   Status: {$invoice->status}\n";
echo "   Total: ₱" . number_format($invoice->total_amount, 2) . "\n";
echo "   Paid: ₱" . number_format($invoice->amount_paid, 2) . "\n";
echo "   Balance: ₱" . number_format($invoice->total_amount - $invoice->amount_paid, 2) . "\n";
echo "   Billing month: {$invoice->billing_month}\n";
echo "   Tenant count: {$invoice->tenant_count}\n\n";

// Check TenantPayment records
$tenantPayments = TenantPayment::where('invoice_id', $invoice->id)->get();

echo "Tenant payment records ({$tenantPayments->count()}):\n";
foreach ($tenantPayments as $tp) {
    echo "   Tenant #{$tp->tenant_id}: ";
    echo "₱" . number_format($tp->share_amount, 2) . " (share) ";
    echo "| ₱" . number_format($tp->amount_paid, 2) . " (paid) ";
    echo "| Status: {$tp->status} ";
    if ($tp->paid_at) {
        echo "| Paid at: {$tp->paid_at->format('Y-m-d H:i:s')}";
    }
    if ($tp->paymongo_link_id) {
        echo "\n      Link ID: {$tp->paymongo_link_id}";
    }
    echo "\n";
}

echo "\n=== Analysis ===\n";

// Check if any tenant payment was updated
$paidTenants = $tenantPayments->where('status', 'paid');
if ($paidTenants->isEmpty()) {
    echo "❌ PROBLEM: No tenant payment records are marked as 'paid'\n";
    echo "   This means the webhook received the payment but didn't update TenantPayment status.\n\n";
    echo "Possible causes:\n";
    echo "  1. paymongo_link_id not matching (webhook can't find which tenant paid)\n";
    echo "  2. Webhook code not updating TenantPayment.status\n";
    echo "  3. Transaction rolled back due to error\n\n";
    
    // Check if link IDs are set
    $hasLinkIds = $tenantPayments->where('paymongo_link_id', '!=', null)->count();
    echo "Tenant payments with link_id: {$hasLinkIds}/{$tenantPayments->count()}\n";
    
} else {
    echo "✅ Found {$paidTenants->count()} tenant(s) marked as paid\n";
    foreach ($paidTenants as $tp) {
        echo "   Tenant #{$tp->tenant_id}: ₱" . number_format($tp->amount_paid, 2) . " paid on {$tp->paid_at->format('M d, Y')}\n";
    }
}

echo "\n";

// Check webhook logs
echo "=== Recent Webhook Logs ===\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    preg_match_all('/PayMongo webhook.*$/m', $logs, $matches);
    $recentLogs = array_slice($matches[0], -10);
    if (empty($recentLogs)) {
        echo "⚠️  No webhook logs found in laravel.log\n";
        echo "   Webhook might not be reaching your server.\n";
    } else {
        foreach ($recentLogs as $log) {
            echo "   " . $log . "\n";
        }
    }
} else {
    echo "⚠️  Log file not found\n";
}
