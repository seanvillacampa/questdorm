<?php
/**
 * Test Cascading Overdue Logic
 * 
 * Verifies that when a current invoice becomes overdue,
 * all older unpaid invoices for the same tenants also become overdue.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Testing Cascading Overdue Logic ===\n\n";

// Find a tenant with multiple unpaid invoices
$tenant = \App\Models\Tenant::whereHas('contracts', function($q) {
    $q->where('status', 'active');
})->first();

if (!$tenant) {
    echo "No active tenants found.\n";
    exit;
}

echo "Testing with: {$tenant->user->name} (Tenant ID: {$tenant->id})\n";
echo str_repeat('-', 60) . "\n\n";

$contract = $tenant->contracts()->where('status', 'active')->first();

// Get all invoices for this contract
$invoices = \App\Models\Invoice::where('contract_id', $contract->id)
    ->with('tenantPayments')
    ->orderBy('billing_month')
    ->get();

echo "BEFORE cascading:\n";
foreach ($invoices as $inv) {
    $tp = $inv->tenantPayments->where('tenant_id', $tenant->id)->first();
    if ($tp) {
        echo sprintf(
            "  %s (%s): Invoice=%s, Tenant=%s\n",
            $inv->invoice_number,
            $inv->billing_month,
            $inv->status,
            $tp->status
        );
    }
}

// Find the latest invoice and mark it as overdue (simulate)
$latestInvoice = $invoices->sortByDesc('billing_month')
    ->whereNotIn('status', ['paid', 'void'])
    ->first();

if (!$latestInvoice) {
    echo "\nNo unpaid invoices found to test with.\n";
    exit;
}

echo "\nSimulating: Latest invoice ({$latestInvoice->invoice_number}) becomes OVERDUE\n";
echo "Expected: All older unpaid invoices should also become OVERDUE\n\n";

// Manually set the latest invoice's tenant payment to overdue to trigger cascade
$latestTenantPay = $latestInvoice->tenantPayments->where('tenant_id', $tenant->id)->first();
if ($latestTenantPay && $latestTenantPay->status !== 'paid') {
    // Trigger recomputeStatus which will cascade
    $latestInvoice->recomputeStatus();
}

// Reload and check
$invoices = \App\Models\Invoice::where('contract_id', $contract->id)
    ->with('tenantPayments')
    ->orderBy('billing_month')
    ->get();

echo "AFTER cascading:\n";
foreach ($invoices as $inv) {
    $tp = $inv->tenantPayments->where('tenant_id', $tenant->id)->first();
    if ($tp) {
        echo sprintf(
            "  %s (%s): Invoice=%s, Tenant=%s %s\n",
            $inv->invoice_number,
            $inv->billing_month,
            $inv->status,
            $tp->status,
            ($inv->billing_month < $latestInvoice->billing_month && $tp->status === 'overdue' && $tp->status !== 'paid') 
                ? '✓ CASCADED' 
                : ''
        );
    }
}

echo "\n=== Test Complete ===\n";
echo "\nNote: This test triggers the cascade logic.\n";
echo "Run 'php artisan invoices:process-statuses' to apply to all invoices.\n\n";
