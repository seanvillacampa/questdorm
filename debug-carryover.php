<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;
use App\Models\TenantPayment;

// Find a September invoice
$invoice = Invoice::where('billing_month', '2026-09')
    ->whereHas('contract', function($q) {
        $q->whereHas('room');
    })
    ->first();

if (!$invoice) {
    echo "No September 2026 invoice found\n";
    exit;
}

$contract = $invoice->contract;
$tenant = $contract->tenants->first();

if (!$tenant) {
    echo "No tenant found\n";
    exit;
}

echo "=== Debug Carry-Over Calculation ===\n";
echo "Contract ID: {$contract->id}\n";
echo "Room: {$contract->room->room_number}\n";
echo "Tenant ID: {$tenant->id}\n";
echo "Tenant Name: {$tenant->user->name}\n";
echo "Current month: 2026-09\n\n";

// Get all previous payments
$allPrev = TenantPayment::whereHas('invoice', function($q) use ($contract) {
    $q->where('contract_id', $contract->id)
      ->where('billing_month', '<', '2026-09')
      ->whereNotIn('status', ['void']);
})
->where('tenant_id', $tenant->id)
->with('invoice')
->orderBy('invoice_id')
->get();

echo "Previous payments found: {$allPrev->count()}\n\n";

$accumulated = 0;
foreach ($allPrev as $prev) {
    $balance = $prev->balanceDue();
    $status = $prev->status;
    echo sprintf(
        "  %-15s | Share: ₱%8.2f | Carry: ₱%8.2f | Paid: ₱%8.2f | Balance: ₱%8.2f | Status: %s\n",
        $prev->invoice->invoice_number,
        $prev->share_amount,
        $prev->carry_over_balance ?? 0,
        $prev->amount_paid,
        $balance,
        $status
    );
    
    if ($balance > 0) {
        $accumulated += $balance;
    }
}

echo "\nTotal accumulated unpaid: ₱" . number_format($accumulated, 2) . "\n\n";

// Check what's in the current September invoice
$currentTP = TenantPayment::where('invoice_id', $invoice->id)
    ->where('tenant_id', $tenant->id)
    ->first();

if ($currentTP) {
    echo "September 2026 invoice ({$invoice->invoice_number}):\n";
    echo "  Share amount: ₱" . number_format($currentTP->share_amount, 2) . "\n";
    echo "  Carry-over balance (stored): ₱" . number_format($currentTP->carry_over_balance ?? 0, 2) . "\n";
    echo "  Expected carry-over: ₱" . number_format($accumulated, 2) . "\n";
    
    if (abs(($currentTP->carry_over_balance ?? 0) - $accumulated) > 0.01) {
        echo "\n⚠️  MISMATCH! Stored carry-over does not match calculated accumulated balance!\n";
    } else {
        echo "\n✓ Carry-over is correct\n";
    }
}
