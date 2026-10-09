<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\TenantPayment;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class InvoiceController extends Controller
{
    // ── Latest created billing statements ──────────────────────────────────────────

    public function latest(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $month  = $request->get('month', '');

        $query = Invoice::with(['contract.room', 'contract.tenants.user'])
            ->whereNotIn('status', ['void'])
            ->orderByDesc('created_at'); // always newest first

        if ($search) {
            $query->where(fn ($q) =>
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('contract.room', fn ($r) =>
                      $r->where('room_number', 'like', "%{$search}%"))
            );
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($month) {
            $query->where('billing_month', $month);
        }

        $invoices = $query->paginate(20)->withQueryString();

        // Counts for filter badges
        $statusCounts = Invoice::whereNotIn('status', ['void'])
            ->selectRaw("status, COUNT(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // Available billing months for dropdown
        $billingMonths = Invoice::whereNotIn('status', ['void'])
            ->distinct()
            ->orderByDesc('billing_month')
            ->pluck('billing_month');

        return view('invoices.latest', compact(
            'invoices', 'search', 'status', 'month',
            'statusCounts', 'billingMonths'
        ));
    }

    // ── Room → Billing Statement redirect ───────────────────────────────────────────────

    /**
     * Called when a room card is clicked on the dashboard.
     * If a billing statement exists for the current month, show it.
     * If not, redirect back with a warning prompt to generate one.
     */
    public function roomInvoice(\App\Models\Room $room)
    {
        $month    = now()->format('Y-m');
        $contract = $room->activeContract;

        if (!$contract) {
            return redirect()->route('owner.dashboard')
                ->with('warning', "Room {$room->room_number} has no active contract.");
        }

        $invoice = Invoice::where('contract_id', $contract->id)
            ->where('billing_month', $month)
            ->first();

        if ($invoice) {
            return redirect()->route('invoices.show', $invoice);
        }

        // No billing statement yet — redirect to invoices page with a prompt
        return redirect()->route('invoices.index', ['month' => $month])
            ->with('warning', "No billing statement yet for Room {$room->room_number} ({$month}). Generate billing statements to create one.");
    }

    // ── Month coverage helper ────────────────────────────────────────────

    private function monthCoverage(): Collection
    {
        // Define room count milestones (when new rooms were added)
        // Format: 'YYYY-MM' => total_rooms_from_that_month_onwards
        $roomMilestones = [
            '2026-10' => 29, // October 2026: 29 rooms
            '2026-09' => 28, // Before October: 28 rooms
        ];
        
        $counts = Invoice::select('billing_month', DB::raw('COUNT(*) as cnt'))
            ->where('status', '!=', 'void')
            ->groupBy('billing_month')
            ->pluck('cnt', 'billing_month');

        // NEW: Check if all tenant payments are paid for each month
        $fullyPaidMonths = \App\Models\TenantPayment::join('invoices', 'tenant_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', '!=', 'void')
            ->groupBy('invoices.billing_month')
            ->havingRaw('COUNT(*) = SUM(CASE WHEN tenant_payments.status = "paid" THEN 1 ELSE 0 END)')
            ->pluck('invoices.billing_month')
            ->flip();

        $months = collect();
        for ($i = 0; $i <= 12; $i++) {
            $m = now()->subMonths($i)->format('Y-m');
            $cnt = (int) ($counts[$m] ?? 0);
            
            // Determine the expected room count for this month
            $totalRooms = 28; // Default for oldest months
            foreach ($roomMilestones as $milestoneMonth => $roomCount) {
                if ($m >= $milestoneMonth) {
                    $totalRooms = $roomCount;
                    break;
                }
            }
            
            // Only show green checkmark if ALL invoices exist AND all tenant payments are paid
            $allPaid = isset($fullyPaidMonths[$m]);
            $status = match(true) {
                $totalRooms > 0 && $cnt >= $totalRooms && $allPaid => 'all',
                $cnt > 0                                           => 'partial',
                default                                            => 'none',
            };
            $months->put($m, [
                'label'  => now()->subMonths($i)->format('F Y'),
                'status' => $status,
                'count'  => $cnt,
                'total'  => $totalRooms,
            ]);
        }
        return $months;
    }

    // ── Index ────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $month     = $request->get('month', now()->format('Y-m'));
        $activeTab = $request->get('tab', 'all'); // kept for manage tab only
        $status    = $request->get('status', '');  // filter value
        $search    = $request->get('search', '');

        $query = Invoice::with(['contract.room', 'contract.tenants.user', 'tenantPayments.tenant.user'])
            ->where('billing_month', $month)
            ->where('status', '!=', 'void')
            ->orderBy('invoice_number');

        // Status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Search by room number or invoice number
        if ($search) {
            $query->where(fn ($q) =>
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('contract.room', fn ($r) =>
                      $r->where('room_number', 'like', "%{$search}%"))
            );
        }

        $invoices = $query->paginate(10)->withQueryString();

        // Summary counts for the status dropdown labels
        $rawCounts = Invoice::where('billing_month', $month)
            ->where('status', '!=', 'void')
            ->selectRaw("
                COUNT(*) as total,
                SUM(status='paid') as paid,
                SUM(status='partial') as partial,
                SUM(status='late') as late,
                SUM(status='overdue') as overdue,
                SUM(status='pending') as pending
            ")->first();

        // All invoices (incl. void) for manage tab
        $allInvoices = Invoice::with(['contract.room', 'contract.tenants.user', 'tenantPayments.tenant.user'])
            ->where('billing_month', $month)
            ->orderByRaw("FIELD(status,'pending','late','overdue','partial','paid','void')")
            ->orderBy('invoice_number')
            ->paginate(28)
            ->withQueryString();

        $monthCoverage = $this->monthCoverage();

        return view('invoices.index', compact(
            'invoices', 'allInvoices', 'month', 'rawCounts',
            'activeTab', 'monthCoverage', 'status', 'search'
        ));
    }

    // ── Show ─────────────────────────────────────────────────────────────

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'contract.room',
            'contract.tenants.user',
            'tenantPayments.tenant.user',
            'payments.recordedByUser',
            'payments.tenantPayment.tenant.user',
            'createdBy',
        ]);
        $canVoid = Auth::user()->hasRole('owner') && $invoice->status !== 'void';
        return view('invoices.show', compact('invoice', 'canVoid'));
    }

    // ── Generate ─────────────────────────────────────────────────────────

    public function generate(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = $request->month;
        $rate  = Setting::currentElectricityRate();
        $created = 0;

        DB::transaction(function () use ($month, $rate, &$created) {
            $contracts = Contract::where('status', 'active')
                ->with(['room', 'tenants', 'lastMeterReading'])
                ->get();

            foreach ($contracts as $contract) {
                if (Invoice::where('contract_id', $contract->id)
                        ->where('billing_month', $month)->exists()) {
                    continue;
                }

                $tenantCount = $contract->tenants->count();
                if ($tenantCount === 0) continue;

                $roomRate    = $contract->room->monthly_rate;
                $electricity = 0;

                $reading = $contract->lastMeterReading;
                if ($reading && $reading->kwh_used !== null) {
                    $electricity = round($reading->kwh_used * $rate, 2);
                }

                // ── Carry-over from previous billing statement ────────────────────
                $carryOver   = 0.0; // unpaid balance from prev billing statement
                $creditBal   = 0.0; // overpayment from prev billing statement

                $prevInvoice = Invoice::where('contract_id', $contract->id)
                    ->where('billing_month', '<', $month)
                    ->whereNotIn('status', ['void'])
                    ->orderByDesc('billing_month')
                    ->first();

                if ($prevInvoice) {
                    $prevBalance = $prevInvoice->balanceDue();
                    $prevCredit  = max(0, $prevInvoice->amount_paid - $prevInvoice->effectiveTotal());

                    if ($prevBalance > 0) {
                        // Previous billing statement has unpaid balance — add it to this month
                        $carryOver = round($prevBalance, 2);
                    } elseif ($prevCredit > 0) {
                        // Previous billing statement was overpaid — credit this month
                        $creditBal = round($prevCredit, 2);
                    }
                }

                // Effective total: base + carry-over - credit
                $baseTotal   = $roomRate + $electricity;
                $effectTotal = max(0, round($baseTotal + $carryOver - $creditBal, 2));

                $dueDate = \Carbon\Carbon::createFromFormat('Y-m', $month)
                    ->day($contract->due_day);

                $invoice = Invoice::create([
                    'invoice_number'     => 'BILL-' . str_replace('-', '', substr($month, 2)) . '-' . $contract->room->room_number,
                    'contract_id'        => $contract->id,
                    'tenant_count'       => $tenantCount,
                    'billing_month'      => $month,
                    'due_date'           => $dueDate,
                    'rent_amount'        => $roomRate,
                    'electricity_amount' => $electricity,
                    'carry_over_balance' => $carryOver,
                    'credit_balance'     => $creditBal,
                    'total_amount'       => $effectTotal,
                    'amount_paid'        => 0,
                    'status'             => 'pending',
                    'created_by'         => Auth::id(),
                ]);

                // Per-tenant share based on effective total
                $shareTotal = round($effectTotal / $tenantCount, 2);

                foreach ($contract->tenants as $tenant) {
                    TenantPayment::create([
                        'invoice_id'   => $invoice->id,
                        'tenant_id'    => $tenant->id,
                        'share_amount' => $shareTotal,
                        'amount_paid'  => 0,
                        'status'       => 'pending',
                    ]);
                }

                $created++;
            }
        });

        AuditLog::record('Generated', 'Invoice', null,
            "{$created} room billing statements generated for {$month}");

        return redirect()->route('invoices.index', ['month' => $month])
            ->with('success', "{$created} billing statement(s) generated for {$month}.");
    }

    // ── Cancel (bulk void unpaid) ─────────────────────────────────────────

    public function cancel(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = $request->month;

        $ids = Invoice::where('billing_month', $month)
            ->whereNotIn('status', ['paid', 'void'])
            ->pluck('id');

        Invoice::whereIn('id', $ids)->update(['status' => 'void']);
        TenantPayment::whereIn('invoice_id', $ids)->update(['status' => 'void']);

        AuditLog::record('Cancelled', 'Invoice', null,
            "{$ids->count()} billing statements cancelled for {$month}");

        return redirect()->route('invoices.index', ['month' => $month])
            ->with('success', "{$ids->count()} billing statement(s) cancelled for {$month}.");
    }

    // ── Void (single billing statement, owner only) ────────────────────────────────

    public function void(Invoice $invoice)
    {
        abort_if($invoice->status === 'void', 422, 'Already voided.');
        $old = $invoice->status;
        $invoice->update(['status' => 'void']);
        TenantPayment::where('invoice_id', $invoice->id)
            ->whereNotIn('status', ['paid'])
            ->update(['status' => 'void']);

        AuditLog::record('Voided', 'Invoice', $invoice->id, "status: {$old} → void");
        return back()->with('success', "Billing Statement {$invoice->invoice_number} voided.");
    }
}
