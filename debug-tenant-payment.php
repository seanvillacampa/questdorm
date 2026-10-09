<?php
/**
 * Debug Tenant Payment Status
 * 
 * Shows the most recent tenant payment details to understand why
 * payments aren't showing up in the tenant portal
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Tenant Payment Debug ===\n\n";

// Get the most recent payment
$payment = \App\Models\Payment::with(['invoice.contract.tenants.user', 'invoice.tenantPayments'])
    ->latest()
    ->first();

if (!$payment) {
    echo "No payments found.\n";
    exit;
}

echo "Latest Payment:\n";
echo str_repeat('-', 60) . "\n";
echo "Payment ID: {$payment->id}\n";
echo "Amount: ₱" . number_format($payment->amount, 2) . "\n";
echo "Method: {$payment->method}\n";
echo "Reference: {$payment->reference}\n";
echo "Date: {$payment->received_at}\n";
echo "Notes: {$payment->notes}\n";
echo "\n";

$invoice = $payment->invoice;
echo "Associated Invoice:\n";
echo str_repeat('-', 60) . "\n";
echo "Invoice Number: {$invoice->invoice_number}\n";
echo "Status: {$invoice->status}\n";
echo "Total Amount: ₱" . number_format($invoice->total_amount, 2) . "\n";
echo "Amount Paid: ₱" . number_format($invoice->amount_paid, 2) . "\n";
echo "Balance Due: ₱" . number_format($invoice->balanceDue(), 2) . "\n";
echo "Room: {$invoice->contract->room->room_number}\n";
echo "\n";

echo "Tenant Payments for this Invoice:\n";
echo str_repeat('-', 60) . "\n";
foreach ($invoice->tenantPayments as $tp) {
    $tenant = $tp->tenant;
    $user = $tenant->user;
    
    echo "\nTenant: {$user->name} (ID: {$tenant->id})\n";
    echo "  Share Amount: ₱" . number_format($tp->share_amount, 2) . "\n";
    echo "  Amount Paid: ₱" . number_format($tp->amount_paid, 2) . "\n";
    echo "  Balance: ₱" . number_format($tp->balanceDue(), 2) . "\n";
    echo "  Status: {$tp->status}\n";
    echo "  Payment Method: " . ($tp->method ?? 'not set') . "\n";
    echo "  Paid At: " . ($tp->paid_at ? $tp->paid_at->format('Y-m-d H:i:s') : 'not set') . "\n";
    echo "  PayMongo Link ID: " . ($tp->paymongo_link_id ?? 'none') . "\n";
    
    // Show what the tenant portal would calculate
    $myPaid = $tp->amount_paid ?? 0;
    $myShare = $tp->share_amount ?? $invoice->amountPerTenant();
    $myBalance = max(0, $myShare - $myPaid);
    
    echo "  --- Portal Calculation ---\n";
    echo "  Would show: Balance = ₱" . number_format($myBalance, 2) . "\n";
    echo "  Status display: {$tp->status}\n";
}

echo "\n";
echo "All Payments for this Invoice:\n";
echo str_repeat('-', 60) . "\n";
$allPayments = \App\Models\Payment::where('invoice_id', $invoice->id)->get();
foreach ($allPayments as $p) {
    echo "- ₱" . number_format($p->amount, 2) . " via {$p->method} on {$p->received_at} (ref: {$p->reference})\n";
    echo "  Notes: {$p->notes}\n";
}

echo "\n=== End Debug ===\n";
