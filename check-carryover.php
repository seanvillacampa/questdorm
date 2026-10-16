<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TenantPayment;
use App\Models\Invoice;

$tenantId = 3; // Jose Miguel Santos
$contractId = 2;

echo "=== Checking Carry-Over for Tenant #$tenantId ===\n\n";

$months = ['2026-08', '2026-09', '2026-10'];

foreach ($months as $month) {
    $invoice = Invoice::where('contract_id', $contractId)
        ->where('billing_month', $month)
        ->first();
    
    if (!$invoice) {
        echo "$month: No invoice found\n";
        continue;
    }
    
    $tp = TenantPayment::where('invoice_id', $invoice->id)
        ->where('tenant_id', $tenantId)
        ->first();
    
    if (!$tp) {
        echo "$month: No tenant payment found\n";
        continue;
    }
    
    echo sprintf(
        "%s (%s):\n  Share: ₱%8.2f | Carry-over: ₱%8.2f | Paid: ₱%8.2f | Balance Due: ₱%8.2f | Status: %s\n\n",
        $month,
        $invoice->invoice_number,
        $tp->share_amount,
        $tp->carry_over_balance ?? 0,
        $tp->amount_paid,
        $tp->balanceDue(),
        $tp->status
    );
}

// Now manually calculate what September's carry-over SHOULD be
echo "=== Manual Calculation ===\n";
$augustTP = TenantPayment::whereHas('invoice', function($q) use ($contractId) {
    $q->where('contract_id', $contractId)->where('billing_month', '2026-08');
})->where('tenant_id', $tenantId)->first();

$septemberTP = TenantPayment::whereHas('invoice', function($q) use ($contractId) {
    $q->where('contract_id', $contractId)->where('billing_month', '2026-09');
})->where('tenant_id', $tenantId)->first();

if ($augustTP && $septemberTP) {
    $augustBalance = $augustTP->balanceDue();
    echo "August balance due: ₱" . number_format($augustBalance, 2) . "\n";
    echo "September carry-over (stored): ₱" . number_format($septemberTP->carry_over_balance ?? 0, 2) . "\n";
    echo "Expected: ₱" . number_format($augustBalance, 2) . "\n";
    
    if (abs(($septemberTP->carry_over_balance ?? 0) - $augustBalance) > 0.01) {
        echo "❌ MISMATCH!\n";
    } else {
        echo "✓ Correct\n";
    }
}
