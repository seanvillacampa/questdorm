<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TenantPayment;

// Find a tenant with unpaid August 2026
$unpaidAug = TenantPayment::whereHas('invoice', function($q) {
    $q->where('billing_month', '2026-08');
})
->where('status', '!=', 'paid')
->with(['tenant.user', 'invoice.contract.room'])
->first();

if ($unpaidAug) {
    $tenant = $unpaidAug->tenant;
    $contract = $unpaidAug->invoice->contract;
    
    echo "Found unpaid August invoice:\n";
    echo "Tenant: {$tenant->user->name}\n";
    echo "Tenant ID: {$tenant->id}\n";
    echo "Room: {$contract->room->room_number}\n";
    echo "Contract ID: {$contract->id}\n";
    echo "August Balance Due: ₱" . number_format($unpaidAug->balanceDue(), 2) . "\n";
}
