<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Setting;
use App\Models\TenantPayment;
use App\Notifications\InvoiceCreated;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Runs daily. For each active contract, checks if today is
 * (due_day - invoice_advance_days) days into the month. If so,
 * and no invoice exists yet for this billing month, auto-generates
 * one and notifies every tenant.
 *
 * Example: due_day = 18, advance_days = 3
 *   → Invoice generated on the 15th, due date = 18th.
 *   → Tenants get an email on the 15th saying "Your bill is ready, due in 3 days."
 */
class GenerateScheduledInvoices extends Command
{
    protected $signature   = 'invoices:generate-scheduled';
    protected $description = 'Auto-generate invoices X days before each contract\'s due date';

    public function handle(): void
    {
        $rules        = Setting::billingRules();
        $advanceDays  = (int) ($rules['invoice_advance_days'] ?? 3);
        $rate         = Setting::currentElectricityRate();
        $today        = now();
        $generated    = 0;

        $contracts = Contract::where('status', 'active')
            ->with(['room', 'tenants.user'])
            ->get();

        foreach ($contracts as $contract) {
            // Determine the billing month and due date
            $currentMonthDueDate = Carbon::createFromDate($today->year, $today->month, $contract->due_day);
            $nextMonthDueDate = Carbon::createFromDate($today->year, $today->month, $contract->due_day)->addMonth();
            
            // Check if we need to generate for current month or next month
            $currentMonthGenerateOn = $currentMonthDueDate->copy()->subDays($advanceDays);
            $nextMonthGenerateOn = $nextMonthDueDate->copy()->subDays($advanceDays);
            
            $billingMonth = null;
            $dueDate = null;
            
            // Priority 1: Check if we should generate for current month
            // Generate if we're within the window OR if the due date has passed but invoice doesn't exist
            if ($today->greaterThanOrEqualTo($currentMonthGenerateOn) && $today->lessThanOrEqualTo($currentMonthDueDate->copy()->addDays(7))) {
                $billingMonth = $today->format('Y-m');
                $dueDate = $currentMonthDueDate;
            }
            // Priority 2: Check if we should generate for next month
            elseif ($today->greaterThanOrEqualTo($nextMonthGenerateOn)) {
                $billingMonth = $nextMonthDueDate->format('Y-m');
                $dueDate = $nextMonthDueDate;
            }
            else {
                // Not time to generate yet
                continue;
            }

            // Skip if invoice already exists for this contract + month
            if (Invoice::where('contract_id', $contract->id)
                    ->where('billing_month', $billingMonth)
                    ->exists()) {
                $this->line("Skipped Room {$contract->room->room_number} — invoice already exists for {$billingMonth}");
                continue;
            }

            $tenantCount = $contract->tenants->count();
            if ($tenantCount === 0) continue;

            // Electricity from most recent meter reading for this room
            $electricity = 0;
            $reading = MeterReading::where('room_id', $contract->room_id)
                ->where('reading_month', $billingMonth)
                ->first();

            // If current month's reading doesn't exist yet
            if (!$reading) {
                // Check if previous invoice had electricity charges
                $prevInvoiceWithElec = Invoice::where('contract_id', $contract->id)
                    ->where('billing_month', '<', $billingMonth)
                    ->whereNotIn('status', ['void'])
                    ->where('electricity_amount', '>', 0)
                    ->orderByDesc('billing_month')
                    ->first();

                // Only use fallback reading if previous invoice had electricity
                if ($prevInvoiceWithElec) {
                    $reading = MeterReading::where('room_id', $contract->room_id)
                        ->orderByDesc('reading_month')
                        ->first();
                }
                // Otherwise electricity stays 0 (no past electric balance)
            }

            if ($reading && $rate > 0) {
                $electricity = round($reading->kwh_used * $rate, 2);
            }

            $roomRate = $contract->room->monthly_rate;

            // ── Carry-over from previous invoice ────────────────────────
            $carryOver = 0.0;
            $creditBal = 0.0;

            $prevInvoice = Invoice::where('contract_id', $contract->id)
                ->where('billing_month', '<', $billingMonth)
                ->whereNotIn('status', ['void'])
                ->orderByDesc('billing_month')
                ->first();

            if ($prevInvoice) {
                $prevBalance = $prevInvoice->balanceDue();
                $prevCredit  = max(0, $prevInvoice->amount_paid - $prevInvoice->effectiveTotal());
                if ($prevBalance > 0) {
                    $carryOver = round($prevBalance, 2);
                } elseif ($prevCredit > 0) {
                    $creditBal = round($prevCredit, 2);
                }
            }

            $total    = max(0, round($roomRate + $electricity + $carryOver - $creditBal, 2));
            $shareAmt = round($total / $tenantCount, 2);

            DB::transaction(function () use (
                $contract, $billingMonth, $dueDate, $roomRate,
                $electricity, $carryOver, $creditBal, $total, $tenantCount, $shareAmt, &$generated
            ) {
                $invoice = Invoice::create([
                    'invoice_number'     => 'BILL-' . str_replace('-', '', substr($billingMonth, 2))
                                           . '-' . $contract->room->room_number,
                    'contract_id'        => $contract->id,
                    'tenant_count'       => $tenantCount,
                    'billing_month'      => $billingMonth,
                    'due_date'           => $dueDate,
                    'rent_amount'        => $roomRate,
                    'electricity_amount' => $electricity,
                    'carry_over_balance' => $carryOver,
                    'credit_balance'     => $creditBal,
                    'total_amount'       => $total,
                    'amount_paid'        => 0,
                    'status'             => 'pending',
                    'created_by'         => null,
                ]);

                foreach ($contract->tenants as $tenant) {
                    TenantPayment::create([
                        'invoice_id'   => $invoice->id,
                        'tenant_id'    => $tenant->id,
                        'share_amount' => $shareAmt,
                        'amount_paid'  => 0,
                        'status'       => 'pending',
                    ]);
                }

                // Notify each tenant
                foreach ($contract->tenants as $tenant) {
                    $tp = TenantPayment::where('invoice_id', $invoice->id)
                        ->where('tenant_id', $tenant->id)
                        ->first();

                    try {
                        $tenant->user->notify(new InvoiceCreated($invoice, $tp));
                        Log::info('InvoiceCreated notification sent', [
                            'room'   => $contract->room->room_number,
                            'tenant' => $tenant->user->email,
                            'month'  => $billingMonth,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('InvoiceCreated notification failed', [
                            'tenant' => $tenant->user->email,
                            'error'  => $e->getMessage(),
                        ]);
                    }
                }

                $generated++;
                Log::info("Auto-generated invoice for Room {$contract->room->room_number}", [
                    'billing_month' => $billingMonth,
                    'due_date'      => $dueDate->toDateString(),
                    'total'         => $total,
                ]);
            });

            $this->line("✓ Generated invoice for Room {$contract->room->room_number} — due {$dueDate->format('M d, Y')}");
        }

        $this->info("{$generated} invoice(s) auto-generated for " . $today->toDateString());
    }
}
