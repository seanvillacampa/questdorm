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
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    // ── Latest created billing statements ──────────────────────────────────────────

    public function latest(Request $request)
    {
        $search = strtolower($request->get('search', ''));
        $status = $request->get('status', '');
        $month  = $request->get('month', '');

        $query = Invoice::with(['contract.room', 'contract.tenants.user'])
            ->whereNotIn('status', ['void'])
            ->orderByDesc('created_at'); // always newest first

        if ($search) {
            $searchPattern = '%'.$search.'%';
            $query->where(function ($q) use ($searchPattern) {
                $q->whereRaw('LOWER(invoice_number) LIKE ?', [$searchPattern])
                  ->orWhereHas('contract.room', function($r) use ($searchPattern) {
                      $r->whereRaw('LOWER(room_number) LIKE ?', [$searchPattern]);
                  });
            });
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
        $search    = strtolower($request->get('search', ''));

        // ── Auto-recompute stale statuses for the viewed month ─────────
        // Runs only for the current/viewed month, keeps page loads fast.
        // Handles environments where the scheduler isn't running (e.g. Render free tier).
        $this->recomputeMonthStatuses($month);

        $query = Invoice::with(['contract.room', 'contract.tenants.user', 'tenantPayments.tenant.user'])
            ->where('status', '!=', 'void')
            ->orderBy('invoice_number');

        // If search is active, search across ALL months, not just the selected month
        if ($search) {
            $searchPattern = '%'.$search.'%';
            $query->where(function ($q) use ($searchPattern) {
                $q->whereRaw('LOWER(invoice_number) LIKE ?', [$searchPattern])
                  ->orWhereHas('contract.room', function($r) use ($searchPattern) {
                      $r->whereRaw('LOWER(room_number) LIKE ?', [$searchPattern]);
                  });
            });
            // Don't filter by month when searching
        } else {
            // Only filter by month when NOT searching
            $query->where('billing_month', $month);
        }

        // Status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
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

        // Sort payments newest first
        $invoice->setRelation(
            'payments',
            $invoice->payments->sortByDesc('created_at')->values()
        );

        // Auto-cascade: for each tenant on this invoice, if a newer invoice
        // with carry-over is paid, mark this one paid too. Fixes pre-deploy payments.
        foreach ($invoice->tenantPayments->whereIn('status', ['pending','overdue','partial','late','partial_late','partial_overdue']) as $tp) {
            $newerPaid = Invoice::where('contract_id', $invoice->contract_id)
                ->where('billing_month', '>', $invoice->billing_month)
                ->where('carry_over_balance', '>', 0)
                ->whereHas('tenantPayments', fn ($q) => $q
                    ->where('tenant_id', $tp->tenant_id)
                    ->where('status', 'paid')
                )
                ->first();

            if ($newerPaid) {
                $newerPaid->cascadePaymentToOlderInvoices($tp->tenant_id);
            }
        }

        // Reload after potential cascade
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

    // ── Download PDF ─────────────────────────────────────────────────────

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load([
            'contract.room',
            'contract.tenants.user',
            'tenantPayments.tenant.user',
            'payments.tenantPayment.tenant.user',
        ]);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Billing-Statement-{$invoice->invoice_number}.pdf");
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

                // ── Calculate per-tenant carry-over (accumulate ALL unpaid balances) ────
                $perTenantCarryOver = []; // keyed by tenant_id
                $totalRoomCarryOver = 0.0;
                $totalRoomCredit = 0.0;

                // Get ALL previous unpaid tenant payments (not just the most recent)
                foreach ($contract->tenants as $tenant) {
                    $allPreviousPayments = TenantPayment::whereHas('invoice', function($q) use ($contract, $month) {
                        $q->where('contract_id', $contract->id)
                          ->where('billing_month', '<', $month)
                          ->whereNotIn('status', ['void']);
                    })
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('invoice_id')
                    ->get();

                    $accumulatedBalance = 0.0;
                    $totalCredit = 0.0;
                    
                    foreach ($allPreviousPayments as $prevPayment) {
                        // Use share_amount - amount_paid (base debt only).
                        // DO NOT use balanceDue() — it includes carry_over_balance
                        // which would double-count carry-overs rolled into later months.
                        $baseDebt = $prevPayment->share_amount - $prevPayment->amount_paid;
                        if ($baseDebt > 0) {
                            $accumulatedBalance += $baseDebt;
                        } elseif ($baseDebt < 0) {
                            $totalCredit += abs($baseDebt);
                        }
                    }
                    
                    // Apply credit first, then set carry-over
                    $netBalance = $accumulatedBalance - $totalCredit;
                    
                    if ($netBalance > 0) {
                        $perTenantCarryOver[$tenant->id] = round($netBalance, 2);
                        $totalRoomCarryOver += $perTenantCarryOver[$tenant->id];
                    } else if ($netBalance < 0) {
                        // Net credit (overpayment)
                        $perTenantCarryOver[$tenant->id] = 0;
                        $totalRoomCredit += round(abs($netBalance), 2);
                    } else {
                        $perTenantCarryOver[$tenant->id] = 0;
                    }
                }

                // Create invoice at room level (for legacy compatibility)
                $baseTotal   = $roomRate + $electricity;
                $effectTotal = max(0, round($baseTotal + $totalRoomCarryOver - $totalRoomCredit, 2));

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
                    'carry_over_balance' => $totalRoomCarryOver,
                    'credit_balance'     => $totalRoomCredit,
                    'total_amount'       => $effectTotal,
                    'amount_paid'        => 0,
                    'status'             => 'pending',
                    'created_by'         => Auth::id(),
                ]);

                // Create per-tenant payment records with individual carry-overs
                $rentPerTenant = round($roomRate / $tenantCount, 2);
                $elecPerTenant = round($electricity / $tenantCount, 2);
                $baseSharePerTenant = $rentPerTenant + $elecPerTenant;

                foreach ($contract->tenants as $tenant) {
                    $tenantCarryOver = $perTenantCarryOver[$tenant->id] ?? 0;
                    
                    TenantPayment::create([
                        'invoice_id'         => $invoice->id,
                        'tenant_id'          => $tenant->id,
                        'share_amount'       => $baseSharePerTenant,
                        'carry_over_balance' => $tenantCarryOver,
                        'amount_paid'        => 0,
                        'status'             => 'pending',
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

    // ── Auto-recompute stale statuses ─────────────────────────────────
    // Called on every invoice index load for the viewed month.
    // Recomputes only unpaid invoices whose due date has already passed,
    // so the status column and filter counts stay accurate even when
    // the scheduler (invoices:process-statuses) isn't running.

    private function recomputeMonthStatuses(string $month): void
    {
        $stale = Invoice::with('tenantPayments')
            ->where('billing_month', $month)
            ->whereNotIn('status', ['paid', 'void'])
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        foreach ($stale as $invoice) {
            $invoice->recomputeStatus();
        }
    }
}
