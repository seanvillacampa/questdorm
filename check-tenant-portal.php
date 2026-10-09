<?php
/**
 * Check what each tenant would see in their portal
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== What Tenants See in Their Portal ===\n\n";

$tenants = \App\Models\Tenant::whereHas('contracts', function($q) {
    $q->where('status', 'active');
})->with('user')->get();

foreach ($tenants as $tenant) {
    $contract = $tenant->contracts()->where('status', 'active')->with(['room', 'tenants.user'])->first();
    
    if (!$contract) continue;
    
    // This mimics the logic from TenantPortalController::bill()
    $myTenantId = $tenant->id;
    
    // First, try to get an unpaid invoice for the current tenant
    $unpaidInvoice = \App\Models\Invoice::where('contract_id', $contract->id)
        ->whereHas('tenantPayments', function($q) use ($myTenantId) {
            $q->where('tenant_id', $myTenantId)
              ->whereIn('status', ['pending', 'partial', 'late', 'partial_late', 'overdue', 'partial_overdue']);
        })
        ->orderByDesc('billing_month')
        ->with(['tenantPayments.tenant.user', 'payments'])
        ->first();
    
    $invoice = $unpaidInvoice;
    
    // If no unpaid invoice, show current month's invoice (if exists)
    if (!$invoice) {
        $invoice = \App\Models\Invoice::where('contract_id', $contract->id)
            ->where('billing_month', now()->format('Y-m'))
            ->with(['tenantPayments.tenant.user', 'payments'])
            ->first();
    }
    
    echo "Tenant: {$tenant->user->name} (ID: {$tenant->id})\n";
    echo "  Email: {$tenant->user->email}\n";
    echo "  Room: {$contract->room->room_number}\n";
    
    if (!$invoice) {
        echo "  Portal shows: NO INVOICE GENERATED YET\n";
    } else {
        $myPayment = $invoice->tenantPayments->firstWhere('tenant_id', $myTenantId);
        $myPaid = $myPayment?->amount_paid ?? 0;
        $myShare = $myPayment?->share_amount ?? $invoice->amountPerTenant();
        $myBalance = max(0, $myShare - $myPaid);
        $myStatus = $myPayment?->status ?? 'pending';
        
        echo "  Portal shows Invoice: {$invoice->invoice_number} ({$invoice->billing_month})\n";
        echo "  Your Balance: ₱" . number_format($myBalance, 2) . "\n";
        echo "  Status: {$myStatus}\n";
        
        if ($myBalance > 0) {
            echo "  ⚠️  UNPAID - Can pay via PayMongo\n";
        } else {
            echo "  ✓ PAID - Fully settled\n";
        }
    }
    
    echo "\n";
}

echo "=== End ===\n";
