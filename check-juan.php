<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = \App\Models\Tenant::find(1); // Juan
$contract = $tenant->contracts()->where('status', 'active')->first();

$invoices = \App\Models\Invoice::where('contract_id', $contract->id)
    ->with('tenantPayments')
    ->where('status', '!=', 'void')
    ->orderBy('billing_month')
    ->get();

echo "Juan Bautista - ALL invoices (including paid):\n";
echo str_repeat('-', 70) . "\n";

foreach ($invoices as $inv) {
    $tp = $inv->tenantPayments->where('tenant_id', $tenant->id)->first();
    echo sprintf(
        "%s (%s): Invoice=%10s, Tenant=%10s\n",
        $inv->invoice_number,
        $inv->billing_month,
        $inv->status,
        $tp?->status ?? 'N/A'
    );
}

echo "\nLATEST unpaid invoice for Juan:\n";
$latest = \App\Models\Invoice::where('contract_id', $contract->id)
    ->whereHas('tenantPayments', function($q) use ($tenant) {
        $q->where('tenant_id', $tenant->id)->where('status', '!=', 'paid');
    })
    ->orderByDesc('billing_month')
    ->first();

if ($latest) {
    $ltpJuan = $latest->tenantPayments->where('tenant_id', $tenant->id)->first();
    echo "{$latest->invoice_number} ({$latest->billing_month}): {$ltpJuan->status}\n";
    echo "\nIF this is overdue, ALL older unpaid should also be overdue.\n";
} else {
    echo "No unpaid invoices!\n";
}
