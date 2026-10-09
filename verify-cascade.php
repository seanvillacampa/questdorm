<?php
/**
 * Verify Cascading Overdue is Working
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== Verifying Cascade Overdue Logic ===\n\n";

// Find tenants who have at least one overdue invoice
$tenantsWithOverdue = \App\Models\TenantPayment::where('status', 'overdue')
    ->with(['tenant.user', 'invoice'])
    ->distinct('tenant_id')
    ->take(5)
    ->get()
    ->pluck('tenant')
    ->unique('id');

foreach ($tenantsWithOverdue as $tenant) {
    echo "Tenant: {$tenant->user->name} (ID: {$tenant->id})\n";
    echo str_repeat('-', 60) . "\n";
    
    $contract = $tenant->contracts()->where('status', 'active')->first();
    if (!$contract) continue;
    
    $invoices = \App\Models\Invoice::where('contract_id', $contract->id)
        ->with('tenantPayments')
        ->whereHas('tenantPayments', function($q) use ($tenant) {
            $q->where('tenant_id', $tenant->id)
              ->where('status', '!=', 'paid');
        })
        ->orderBy('billing_month')
        ->get();
    
    $hasOverdue = false;
    $hadLateBeforeOverdue = false;
    
    foreach ($invoices as $inv) {
        $tp = $inv->tenantPayments->where('tenant_id', $tenant->id)->first();
        if (!$tp) continue;
        
        echo sprintf(
            "  %s (%s): %s\n",
            $inv->invoice_number,
            $inv->billing_month,
            $tp->status
        );
        
        // Check logic: once we see overdue, all previous should be overdue
        if ($tp->status === 'overdue') {
            $hasOverdue = true;
        } elseif ($hasOverdue && in_array($tp->status, ['late', 'pending'])) {
            $hadLateBeforeOverdue = true;
        }
    }
    
    if ($hadLateBeforeOverdue) {
        echo "  ⚠️  WARNING: Found LATE status before OVERDUE - cascade not working!\n";
    } else {
        echo "  ✓ Cascade working correctly\n";
    }
    
    echo "\n";
}

echo "=== Verification Complete ===\n\n";
