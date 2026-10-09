<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\DepositEntry;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // ── Filipino name pools ──────────────────────────────────────────────
    private array $firstNames = [
        'Juan','Maria','Carlo','Liza','Miguel','Rica','Paolo','Jenny',
        'Mark','Sofia','Leo','Bea','Nico','Ana','Rex','Kim',
        'Jose','Carla','Renz','Tina','Dante','Mel','Felix','Grace',
        'Alex','Donna','Ryan','Cris','Edwin','Luz','Ariel','Pia',
        'Mario','Nora','Ruben','Dina','Eric','Mia','Vince','Claire',
        'Jason','Rose','Kevin','Lyn','Patrick','Elle','Ricky','Ivy',
        'Dennis','Joanna','Francis','Stella','Neil','Aileen','Jake','Lena',
        'Roy','Tess','Adrian','Gem','Oscar','Hazel','Noel','Vera',
        'Bernard','Wena','Alvin','Charina','Bobby','Flor','Jerome','Nina',
        'Percy','Diana','Romeo','Susie','Anton','Rhea','Arnold','Faye',
        'Carlos','Edna','Erwin','Mylene','Oliver','Sharon','Raffy','Imelda',
        'Tito','Cita','Reynald','Mercy','Ernesto','Lourdes','Alfredo','Cristina',
    ];

    private array $lastNames = [
        'Santos','Reyes','Cruz','Bautista','Ocampo','Garcia','Mendoza',
        'Torres','Flores','Villanueva','Ramos','Castillo','Soriano','Aquino',
        'Navarro','Pineda','Dela Cruz','De Leon','Manalo','Mercado',
        'Robles','Domingo','Salazar','Cabrera','Lim','Tan','Go','Chua',
        'Bernardo','Gonzales','Rivera','Diaz','Romero','Hernandez','Reyes',
        'Santiago','Valenzuela','Pascual','Perez','Fernandez',
    ];

    private int $emailIdx = 1;

    private function makeName(int $seed): array
    {
        $fn = $this->firstNames[$seed % count($this->firstNames)];
        $ln = $this->lastNames[($seed * 7 + 3) % count($this->lastNames)];
        return [$fn, $ln];
    }

    private function makeUser(string $fn, string $ln, string $role): User
    {
        $slug  = strtolower(preg_replace('/\s+/', '.', $fn));
        $email = $slug . $this->emailIdx++ . '@dorm.test';
        $user  = User::create([
            'name'      => "$fn $ln",
            'email'     => $email,
            'phone'     => '09' . rand(10,99) . ' ' . rand(100,999) . ' ' . rand(1000,9999),
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        return $user;
    }

    public function run(): void
    {
        // ── 1. Full wipe ─────────────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'tenant_payments','payments','invoices','meter_readings',
            'deposit_entries','contract_tenants','contracts','tenants',
            'rooms','settings','audit_logs','users',
            'model_has_roles','model_has_permissions',
        ] as $t) {
            DB::table($t)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── 2. Roles ──────────────────────────────────────────────────────
        Role::firstOrCreate(['name' => 'owner',    'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tenant',   'guard_name' => 'web']);

        // ── 3. Staff ──────────────────────────────────────────────────────
        $owner = User::create([
            'name' => 'Rosa Dela Vega', 'email' => 'owner@dorm.test',
            'phone' => '0917 000 0001', 'password' => Hash::make('password'),
            'is_active' => true, 'last_login_at' => now()->subDays(1),
        ]);
        $owner->assignRole('owner');

        $staff = User::create([
            'name' => 'Ben Lopez', 'email' => 'staff@dorm.test',
            'phone' => '0917 000 0002', 'password' => Hash::make('password'),
            'is_active' => true, 'last_login_at' => now()->subHours(3),
        ]);
        $staff->assignRole('employee');

        $staff2 = User::create([
            'name' => 'Ana Cruz', 'email' => 'staff2@dorm.test',
            'phone' => '0917 000 0003', 'password' => Hash::make('password'),
            'is_active' => true, 'last_login_at' => now()->subDays(2),
        ]);
        $staff2->assignRole('employee');

        // ── 4. Settings ───────────────────────────────────────────────────
        Setting::create(['key'=>'electricity_rate','value'=>11.20,'effective_from'=>'2025-01-01','set_by'=>$owner->id]);
        Setting::create(['key'=>'electricity_rate','value'=>11.80,'effective_from'=>'2025-07-01','set_by'=>$owner->id]);
        Setting::create(['key'=>'electricity_rate','value'=>12.50,'effective_from'=>'2026-01-01','set_by'=>$owner->id]);
        Setting::updateOrCreateSimple('invoice_advance_days', 3);
        Setting::updateOrCreateSimple('default_due_day', 5);
        Setting::updateOrCreateSimple('overdue_grace_days', 3);

        // ── 5. Room definitions ───────────────────────────────────────────
        // 28 rooms: 4 floors × 7 rooms each
        // is_airconditioned → higher rent; capacity 1–4; due_day varies
        $roomDefs = [
            // [number, floor, capacity, is_ac, rate,  meter,       due_day]
            ['101', 1, 2, false, 7000,  'MTR-1001', 5],
            ['102', 1, 4, true,  12000, 'MTR-1002', 8],
            ['103', 1, 3, false, 8000,  'MTR-1003', 10],
            ['104', 1, 1, true,  9500,  'MTR-1004', 5],
            ['105', 1, 4, false, 8500,  'MTR-1005', 15],
            ['106', 1, 2, true,  11000, 'MTR-1006', 5],
            ['107', 1, 3, false, 7500,  'MTR-1007', 20],
            ['201', 2, 4, true,  13000, 'MTR-1008', 5],
            ['202', 2, 2, false, 7000,  'MTR-1009', 10],
            ['203', 2, 3, true,  11500, 'MTR-1010', 5],
            ['204', 2, 4, false, 9000,  'MTR-1011', 18],
            ['205', 2, 1, true,  10000, 'MTR-1012', 5],
            ['206', 2, 2, false, 7500,  'MTR-1013', 12],
            ['207', 2, 3, true,  12000, 'MTR-1014', 5],
            ['301', 3, 4, false, 9500,  'MTR-1015', 5],
            ['302', 3, 2, true,  11000, 'MTR-1016', 10],
            ['303', 3, 3, false, 8000,  'MTR-1017', 5],
            ['304', 3, 4, true,  13500, 'MTR-1018', 15],
            ['305', 3, 1, false, 6500,  'MTR-1019', 5],
            ['306', 3, 2, true,  10500, 'MTR-1020', 20],
            ['307', 3, 4, false, 9000,  'MTR-1021', 5],
            ['401', 4, 3, true,  12500, 'MTR-1022', 5],
            ['402', 4, 4, false, 10000, 'MTR-1023', 8],
            ['403', 4, 2, true,  11500, 'MTR-1024', 5],
            ['404', 4, 4, true,  14000, 'MTR-1025', 12],
            ['405', 4, 1, false, 7000,  'MTR-1026', 5],
            ['406', 4, 2, true,  10000, 'MTR-1027', 5],
            ['407', 4, 3, false, 8500,  'MTR-1028', 18],
        ];

        // Months: Jan 2025 → Sep 2026 = 21 months
        $startYear  = 2025;
        $startMonth = 1;
        $endYear    = 2026;
        $endMonth   = 9; // current month
        $contractStart = Carbon::create(2025, 1, 1);

        // kWh baseline per room (realistic starting meter readings)
        $kwhBase = [800,1200,950,600,1400,880,1050,1600,720,1300,
                    1100,650,900,1450,1200,880,1000,1550,580,1020,
                    1350,1180,1450,1050,1700,620,920,1280];

        $nameIdx = 0;

        foreach ($roomDefs as $ri => [$roomNo, $floor, $cap, $isAC, $rate, $meterNo, $dueDay]) {

            // ── Create room ───────────────────────────────────────────────
            $room = Room::create([
                'room_number'       => $roomNo,
                'floor'             => $floor,
                'capacity'          => $cap,
                'monthly_rate'      => (float)$rate,
                'meter_number'      => $meterNo,
                'status'            => 'occupied',
                'is_airconditioned' => $isAC,
            ]);

            // ── Tenants: random 1..cap occupancy ─────────────────────────
            $occupancy  = rand(1, $cap);
            $tenantIds  = [];
            for ($t = 0; $t < $occupancy; $t++) {
                [$fn, $ln] = $this->makeName($nameIdx++);
                $user      = $this->makeUser($fn, $ln, 'tenant');
                $tenantIds[] = Tenant::create(['user_id' => $user->id])->id;
            }

            // ── Contract ──────────────────────────────────────────────────
            $contract = Contract::create([
                'room_id'           => $room->id,
                'start_date'        => $contractStart,
                'end_date'          => null,
                'due_day'           => $dueDay,
                'deposit_required'  => $rate,
                'deposit_collected' => $rate,
                'status'            => 'active',
                'is_active'         => true,
                'created_by'        => $owner->id,
            ]);

            $pivotData = [];
            foreach ($tenantIds as $tid) {
                $pivotData[$tid] = ['joined_on' => $contractStart->toDateString(), 'left_on' => null];
            }
            $contract->tenants()->attach($pivotData);

            // Deposit entry
            DepositEntry::create([
                'contract_id' => $contract->id,
                'type'        => 'collected',
                'amount'      => $rate,
                'date'        => $contractStart->toDateString(),
                'reason'      => 'Security deposit collected at move-in',
                'recorded_by' => $staff->id,
            ]);

            // ── Month-by-month data ───────────────────────────────────────
            $prevKwh  = (float)$kwhBase[$ri];
            $curYear  = $startYear;
            $curMonth = $startMonth;

            while (
                $curYear < $endYear ||
                ($curYear === $endYear && $curMonth <= $endMonth)
            ) {
                $billingMonth = sprintf('%04d-%02d', $curYear, $curMonth);
                $dueDate      = Carbon::create($curYear, $curMonth, $dueDay);

                // Electricity rate for this month
                $elecRate = $this->electricityRate($curYear, $curMonth);

                // kWh used: random per room, slight seasonal variation
                $kwhUsed = round(rand(30, 110) + ($isAC ? rand(20, 60) : 0), 1);
                $currKwh = round($prevKwh + $kwhUsed, 1);

                // ── Meter reading ─────────────────────────────────────────
                MeterReading::create([
                    'room_id'       => $room->id,
                    'reading_month' => $billingMonth,
                    'previous_kwh'  => $prevKwh,
                    'current_kwh'   => $currKwh,
                    'kwh_used'      => $kwhUsed,
                    'recorded_by'   => ($ri % 2 === 0) ? $staff->id : $staff2->id,
                ]);

                $prevKwh = $currKwh;

                // ── Invoice ───────────────────────────────────────────────
                $electricity = round($kwhUsed * $elecRate, 2);
                $total       = (float)$rate + $electricity;
                $shareTotal  = round($total / $occupancy, 2);

                // Check for carry-over from previous invoice
                $carryOver = 0.0;
                $creditBal = 0.0;
                $prevInvoice = Invoice::where('contract_id', $contract->id)
                    ->where('billing_month', '<', $billingMonth)
                    ->whereNotIn('status', ['void'])
                    ->orderByDesc('billing_month')
                    ->first();

                if ($prevInvoice) {
                    $prevBal    = $prevInvoice->balanceDue();
                    $prevCredit = max(0, $prevInvoice->amount_paid - $prevInvoice->effectiveTotal());
                    if ($prevBal > 0)    $carryOver = round($prevBal, 2);
                    elseif ($prevCredit > 0) $creditBal = round($prevCredit, 2);
                }

                $effectiveTotal = max(0, round($total + $carryOver - $creditBal, 2));
                $effectiveShare = round($effectiveTotal / $occupancy, 2);

                $invoice = Invoice::create([
                    'invoice_number'     => 'BILL-' . str_replace('-','',substr($billingMonth,2)) . '-' . $roomNo,
                    'contract_id'        => $contract->id,
                    'tenant_count'       => $occupancy,
                    'billing_month'      => $billingMonth,
                    'due_date'           => $dueDate,
                    'rent_amount'        => (float)$rate,
                    'electricity_amount' => $electricity,
                    'carry_over_balance' => $carryOver,
                    'credit_balance'     => $creditBal,
                    'total_amount'       => $effectiveTotal,
                    'amount_paid'        => 0,
                    'status'             => 'pending',
                    'created_by'         => $owner->id,
                ]);

                // ── TenantPayments + actual payments ──────────────────────
                $totalAmountPaid  = 0;
                $tpStatuses       = [];

                foreach ($tenantIds as $tidx => $tid) {
                    // Determine payment behaviour for this tenant-month
                    $scenario = $this->paymentScenario($ri, $tidx, $curYear, $curMonth);

                    $tp = TenantPayment::create([
                        'invoice_id'   => $invoice->id,
                        'tenant_id'    => $tid,
                        'share_amount' => $effectiveShare,
                        'amount_paid'  => 0,
                        'status'       => 'pending',
                    ]);

                    if ($scenario === 'paid') {
                        // Paid in full a few days before / after due date
                        $paidAt  = $dueDate->copy()->subDays(rand(0, 5));
                        $method  = $this->randomMethod();
                        $paidAmt = $effectiveShare;

                        // Occasionally overpay (5% chance)
                        if (rand(1,100) <= 5) {
                            $paidAmt = $effectiveShare + rand(100, 500);
                        }

                        $tp->update([
                            'amount_paid' => $paidAmt,
                            'status'      => 'paid',
                            'method'      => $method,
                            'paid_at'     => $paidAt,
                        ]);

                        Payment::create([
                            'invoice_id'        => $invoice->id,
                            'tenant_payment_id' => $tp->id,
                            'amount'            => $paidAmt,
                            'method'            => $method,
                            'received_at'       => $paidAt->toDateString(),
                            'reference'         => strtoupper(substr($method,0,3)) . rand(10000,99999),
                            'notes'             => null,
                            'recorded_by_type'  => rand(0,1) ? 'paymongo' : 'staff',
                            'recorded_by_user'  => rand(0,1) ? $staff->id : $staff2->id,
                        ]);

                        $totalAmountPaid += $paidAmt;
                        $tpStatuses[]     = 'paid';

                    } elseif ($scenario === 'partial') {
                        // Paid half
                        $paidAmt = round($effectiveShare / 2, 2);
                        $paidAt  = $dueDate->copy()->addDays(rand(1,5));
                        $method  = $this->randomMethod();

                        $tp->update([
                            'amount_paid' => $paidAmt,
                            'status'      => 'paid', // partial treated as a paid portion
                            'method'      => $method,
                            'paid_at'     => $paidAt,
                        ]);

                        Payment::create([
                            'invoice_id'        => $invoice->id,
                            'tenant_payment_id' => $tp->id,
                            'amount'            => $paidAmt,
                            'method'            => $method,
                            'received_at'       => $paidAt->toDateString(),
                            'reference'         => strtoupper(substr($method,0,3)) . rand(10000,99999),
                            'recorded_by_type'  => 'staff',
                            'recorded_by_user'  => $staff->id,
                        ]);

                        $totalAmountPaid += $paidAmt;
                        $tpStatuses[]     = 'paid'; // partial counted as paid for invoice status

                    } elseif ($scenario === 'late') {
                        $tp->update(['status' => 'late']);
                        $tpStatuses[] = 'late';

                    } elseif ($scenario === 'overdue') {
                        $tp->update(['status' => 'overdue']);
                        $tpStatuses[] = 'overdue';

                    } else {
                        // pending
                        $tpStatuses[] = 'pending';
                    }
                }

                // ── Invoice status ────────────────────────────────────────
                $paidCount    = collect($tpStatuses)->filter(fn($s)=>$s==='paid')->count();
                $lateCount    = collect($tpStatuses)->filter(fn($s)=>$s==='late')->count();
                $overdueCount = collect($tpStatuses)->filter(fn($s)=>$s==='overdue')->count();
                $unpaid       = $occupancy - $paidCount;

                $invStatus = match(true) {
                    $paidCount === $occupancy      => 'paid',
                    $paidCount > 0                 => 'partial',
                    $overdueCount === $unpaid      => 'overdue',
                    $lateCount === $unpaid         => 'late',
                    ($lateCount+$overdueCount) > 0 => 'late',
                    default                        => 'pending',
                };

                $invoice->update([
                    'status'      => $invStatus,
                    'amount_paid' => $totalAmountPaid,
                    'paid_at'     => $invStatus === 'paid' ? $dueDate->copy()->subDays(rand(0,5)) : null,
                ]);

                // ── Advance month ─────────────────────────────────────────
                $curMonth++;
                if ($curMonth > 12) { $curMonth = 1; $curYear++; }
            }
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Determine payment scenario for a tenant in a given month.
     * Past months: mostly paid. Current month: mix. Last 2 months: more unpaid.
     */
    private function paymentScenario(int $roomIdx, int $tenantIdx, int $year, int $month): string
    {
        $now        = Carbon::now();
        $monthDate  = Carbon::create($year, $month, 1);
        $monthsAgo  = $now->diffInMonths($monthDate);

        // Current month (Sep 2026) — mix of statuses
        if ($monthsAgo === 0) {
            $seed = ($roomIdx * 7 + $tenantIdx * 3) % 10;
            return match(true) {
                $seed < 4 => 'paid',
                $seed < 6 => 'pending',
                $seed < 8 => 'late',
                default   => 'overdue',
            };
        }

        // Last 1-2 months — some unpaid
        if ($monthsAgo <= 2) {
            $seed = ($roomIdx * 5 + $tenantIdx * 11 + $month) % 10;
            return match(true) {
                $seed < 6 => 'paid',
                $seed < 8 => 'partial',
                $seed < 9 => 'late',
                default   => 'overdue',
            };
        }

        // Older months — mostly paid, some partial
        $seed = ($roomIdx * 3 + $tenantIdx * 7 + $month + $year) % 20;
        return match(true) {
            $seed < 14 => 'paid',
            $seed < 17 => 'partial',
            $seed < 19 => 'paid',  // catch-up payment
            default    => 'paid',
        };
    }

    private function randomMethod(): string
    {
        $methods = ['cash','gcash','maya','bank_transfer','card','qr_ph'];
        return $methods[array_rand($methods)];
    }

    private function electricityRate(int $year, int $month): float
    {
        if ($year >= 2026) return 12.50;
        if ($year === 2025 && $month >= 7) return 11.80;
        return 11.20;
    }
}
