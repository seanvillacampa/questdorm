<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "October 2026 Invoices:\n";
echo "=====================\n\n";

$octoberInvoices = DB::table('invoices')
    ->where('billing_month', '2026-10')
    ->orderBy('id')
    ->get(['invoice_number', 'due_date', 'created_at', 'status']);

foreach ($octoberInvoices as $inv) {
    echo "Invoice: {$inv->invoice_number}\n";
    echo "  Due Date: {$inv->due_date}\n";
    echo "  Created: {$inv->created_at}\n";
    echo "  Status: {$inv->status}\n";
    echo "\n";
}

echo "\nAll Invoices Created At (sample):\n";
echo "==================================\n\n";

$allInvoices = DB::table('invoices')
    ->orderBy('id')
    ->limit(15)
    ->get(['invoice_number', 'billing_month', 'created_at']);

foreach ($allInvoices as $inv) {
    echo "{$inv->invoice_number} ({$inv->billing_month}): {$inv->created_at}\n";
}
