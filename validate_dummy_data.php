<?php
/**
 * Validation Script for Dummy Data
 * Run after importing: php artisan tinker < validate_dummy_data.php
 * Or: php validate_dummy_data.php (requires database connection)
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=============================================================================\n";
echo "Quest Building Dummy Data Validation Report\n";
echo "=============================================================================\n\n";

// 1. Basic Counts
echo "1. BASIC ENTITY COUNTS\n";
echo str_repeat("-", 60) . "\n";
$counts = [
    'Rooms' => \App\Models\Room::count(),
    'Tenants' => \App\Models\Tenant::count(),
    'Contracts' => \App\Models\Contract::count(),
    'Active Contracts' => \App\Models\Contract::where('is_active', 1)->count(),
    'Tenant Deposits' => \App\Models\TenantDeposit::count(),
    'Invoices' => \App\Models\Invoice::count(),
    'Payments' => \App\Models\Payment::count(),
    'Tenant Payments' => \App\Models\TenantPayment::count(),
    'Meter Readings' => \App\Models\MeterReading::count(),
];

foreach ($counts as $label => $count) {
    printf("%-25s: %5d\n", $label, $count);
}

// 2. Date Range Validation
echo "\n2. DATE RANGE VALIDATION\n";
echo str_repeat("-", 60) . "\n";
$firstInvoice = \App\Models\Invoice::orderBy('billing_month')->first();
$lastInvoice = \App\Models\Invoice::orderBy('billing_month', 'desc')->first();
echo "First Invoice Month    : " . ($firstInvoice ? $firstInvoice->billing_month : 'N/A') . "\n";
echo "Last Invoice Month     : " . ($lastInvoice ? $lastInvoice->billing_month : 'N/A') . "\n";
echo "Latest Invoice Date    : " . ($lastInvoice ? $lastInvoice->due_date : 'N/A') . "\n";
$futureDates = \App\Models\Invoice::where('billing_month', '>', '2026-10-01')->count();
echo "Invoices after Oct 2026: " . $futureDates . " " . ($futureDates > 0 ? "❌ ERROR" : "✓") . "\n";

// 3. Invoice Status Distribution
echo "\n3. INVOICE STATUS DISTRIBUTION\n";
echo str_repeat("-", 60) . "\n";
$statusCounts = \App\Models\Invoice::selectRaw('status, COUNT(*) as count, SUM(total_amount) as total')
    ->groupBy('status')
    ->get();

foreach ($statusCounts as $status) {
    printf("%-15s: %4d invoices | ₱%s\n", 
        ucfirst($status->status), 
        $status->count, 
        number_format($status->total, 2)
    );
}

// 4. Payment Period Analysis
echo "\n4. PAYMENT PERIOD ANALYSIS\n";
echo str_repeat("-", 60) . "\n";
$paidBeforeAug = \App\Models\Invoice::where('billing_month', '<', '2026-08-01')
    ->where('status', 'paid')
    ->count();
$totalBeforeAug = \App\Models\Invoice::where('billing_month', '<', '2026-08-01')->count();
echo "Jan 2025 - Jul 2026:\n";
echo "  Total Invoices       : $totalBeforeAug\n";
echo "  Paid Invoices        : $paidBeforeAug\n";
echo "  Payment Rate         : " . ($totalBeforeAug > 0 ? round(($paidBeforeAug / $totalBeforeAug) * 100, 1) : 0) . "%\n";

$totalAfterJul = \App\Models\Invoice::where('billing_month', '>=', '2026-08-01')->count();
$paidAfterJul = \App\Models\Invoice::where('billing_month', '>=', '2026-08-01')->where('status', 'paid')->count();
$partialAfterJul = \App\Models\Invoice::where('billing_month', '>=', '2026-08-01')->where('status', 'partial')->count();
$pendingAfterJul = \App\Models\Invoice::where('billing_month', '>=', '2026-08-01')->where('status', 'pending')->count();
$overdueAfterJul = \App\Models\Invoice::where('billing_month', '>=', '2026-08-01')->where('status', 'overdue')->count();

echo "\nAug 2026 - Oct 2026:\n";
echo "  Total Invoices       : $totalAfterJul\n";
echo "  Paid                 : $paidAfterJul (" . round(($paidAfterJul/$totalAfterJul)*100, 1) . "%)\n";
echo "  Partial              : $partialAfterJul (" . round(($partialAfterJul/$totalAfterJul)*100, 1) . "%)\n";
echo "  Pending              : $pendingAfterJul (" . round(($pendingAfterJul/$totalAfterJul)*100, 1) . "%)\n";
echo "  Overdue              : $overdueAfterJul (" . round(($overdueAfterJul/$totalAfterJul)*100, 1) . "%)\n";

// 5. Outstanding Balance Check
echo "\n5. OUTSTANDING BALANCE CHECK (Max ₱20,000 per tenant)\n";
echo str_repeat("-", 60) . "\n";
$outstandingBalances = \DB::table('tenant_payments')
    ->select('tenant_id', \DB::raw('SUM(share_amount - amount_paid) as outstanding'))
    ->where('status', '!=', 'paid')
    ->groupBy('tenant_id')
    ->having('outstanding', '>', 0)
    ->get();

if ($outstandingBalances->count() === 0) {
    echo "No outstanding balances found ✓\n";
} else {
    $violations = 0;
    foreach ($outstandingBalances as $balance) {
        $tenant = \App\Models\Tenant::find($balance->tenant_id);
        $userName = $tenant ? $tenant->user->name : "Unknown";
        $status = $balance->outstanding > 20000 ? "❌ EXCEEDS LIMIT" : "✓";
        printf("Tenant #%d (%s): ₱%s %s\n", 
            $balance->tenant_id, 
            $userName,
            number_format($balance->outstanding, 2),
            $status
        );
        if ($balance->outstanding > 20000) $violations++;
    }
    echo "\nTotal tenants with outstanding: " . $outstandingBalances->count() . "\n";
    echo "Violations (>₱20,000)         : $violations " . ($violations > 0 ? "❌" : "✓") . "\n";
}

// 6. Deposit Status
echo "\n6. DEPOSIT STATUS\n";
echo str_repeat("-", 60) . "\n";
$totalDeposits = \App\Models\TenantDeposit::count();
$fullyPaidDeposits = \App\Models\TenantDeposit::whereRaw('amount_paid >= amount_required')->count();
$unpaidDeposits = \App\Models\TenantDeposit::whereRaw('amount_paid < amount_required')->count();
echo "Total Deposits         : $totalDeposits\n";
echo "Fully Paid             : $fullyPaidDeposits (" . round(($fullyPaidDeposits/$totalDeposits)*100, 1) . "%) ✓\n";
echo "Unpaid/Partial         : $unpaidDeposits\n";

// 7. Contract Distribution
echo "\n7. CONTRACT & TENANT DISTRIBUTION\n";
echo str_repeat("-", 60) . "\n";
$contractsByTenantCount = \DB::table('contracts as c')
    ->select(\DB::raw('COUNT(DISTINCT ct.tenant_id) as tenant_count, COUNT(*) as contract_count'))
    ->leftJoin('contract_tenants as ct', function($join) {
        $join->on('c.id', '=', 'ct.contract_id')
             ->whereNull('ct.left_on');
    })
    ->groupBy('c.id')
    ->get()
    ->groupBy('tenant_count');

foreach ($contractsByTenantCount as $tenantCount => $contracts) {
    printf("%d tenant(s)  : %2d contracts\n", $tenantCount, $contracts->count());
}

// 8. Revenue Summary
echo "\n8. REVENUE SUMMARY\n";
echo str_repeat("-", 60) . "\n";
$totalRevenue = \App\Models\Payment::sum('amount');
$totalBilled = \App\Models\Invoice::sum('total_amount');
$totalOutstanding = $totalBilled - \App\Models\Invoice::sum('amount_paid');
echo "Total Billed           : ₱" . number_format($totalBilled, 2) . "\n";
echo "Total Collected        : ₱" . number_format($totalRevenue, 2) . "\n";
echo "Total Outstanding      : ₱" . number_format($totalOutstanding, 2) . "\n";
echo "Collection Rate        : " . round(($totalRevenue / $totalBilled) * 100, 2) . "%\n";

// 9. Room Occupancy
echo "\n9. ROOM OCCUPANCY\n";
echo str_repeat("-", 60) . "\n";
$totalRooms = \App\Models\Room::count();
$occupiedRooms = \App\Models\Room::where('status', 'occupied')->count();
$vacantRooms = \App\Models\Room::where('status', 'vacant')->count();
echo "Total Rooms            : $totalRooms\n";
echo "Occupied               : $occupiedRooms (" . round(($occupiedRooms/$totalRooms)*100, 1) . "%)\n";
echo "Vacant                 : $vacantRooms\n";

// 10. Data Integrity Checks
echo "\n10. DATA INTEGRITY CHECKS\n";
echo str_repeat("-", 60) . "\n";
$orphanedInvoices = \App\Models\Invoice::whereNotIn('contract_id', \App\Models\Contract::pluck('id'))->count();
$orphanedPayments = \App\Models\Payment::whereNotIn('invoice_id', \App\Models\Invoice::pluck('id'))->count();
$orphanedTenantPayments = \App\Models\TenantPayment::whereNotIn('invoice_id', \App\Models\Invoice::pluck('id'))->count();
$orphanedMeterReadings = \App\Models\MeterReading::whereNotIn('room_id', \App\Models\Room::pluck('id'))->count();

echo "Orphaned Invoices      : $orphanedInvoices " . ($orphanedInvoices > 0 ? "❌" : "✓") . "\n";
echo "Orphaned Payments      : $orphanedPayments " . ($orphanedPayments > 0 ? "❌" : "✓") . "\n";
echo "Orphaned Tenant Pays   : $orphanedTenantPayments " . ($orphanedTenantPayments > 0 ? "❌" : "✓") . "\n";
echo "Orphaned Meter Reads   : $orphanedMeterReadings " . ($orphanedMeterReadings > 0 ? "❌" : "✓") . "\n";

echo "\n=============================================================================\n";
echo "VALIDATION COMPLETE\n";
echo "=============================================================================\n";
