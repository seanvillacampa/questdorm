<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\DetergentInventory;
use App\Models\DetergentLog;
use App\Models\Invoice;
use App\Models\LaundryOrder;
use App\Models\MeterReading;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantDeposit;
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
            'order_items','laundry_orders','customers','detergent_logs','detergent_inventory',
            'service_prices','services',
            'tenant_payments','payments','invoices','meter_readings',
            'tenant_deposits','contract_tenants','contracts','tenants',
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
        // 28 rooms: Floor 1 (11 rooms), Floor 2-4 (17 rooms total)
        // is_airconditioned → higher rent; capacity 1–4; due_day varies
        $roomDefs = [
            // Floor 1: 11 rooms (101-111)
            ['101', 1, 2, false, 7000,  'MTR-1001', 5],
            ['102', 1, 4, true,  12000, 'MTR-1002', 8],
            ['103', 1, 3, false, 8000,  'MTR-1003', 10],
            ['104', 1, 1, true,  9500,  'MTR-1004', 5],
            ['105', 1, 4, false, 8500,  'MTR-1005', 15],
            ['106', 1, 2, true,  11000, 'MTR-1006', 5],
            ['107', 1, 3, false, 7500,  'MTR-1007', 20],
            ['108', 1, 2, false, 7200,  'MTR-1008', 5],
            ['109', 1, 4, true,  12500, 'MTR-1009', 10],
            ['110', 1, 3, false, 8200,  'MTR-1010', 5],
            ['111', 1, 2, true,  10500, 'MTR-1011', 12],
            
            // Floor 2: 6 rooms (201-206)
            ['201', 2, 4, true,  13000, 'MTR-1012', 5],
            ['202', 2, 2, false, 7000,  'MTR-1013', 10],
            ['203', 2, 3, true,  11500, 'MTR-1014', 5],
            ['204', 2, 4, false, 9000,  'MTR-1015', 18],
            ['205', 2, 1, true,  10000, 'MTR-1016', 5],
            ['206', 2, 3, false, 8000,  'MTR-1017', 15],
            
            // Floor 3: 6 rooms (301-306)
            ['301', 3, 4, false, 9500,  'MTR-1018', 5],
            ['302', 3, 2, true,  11000, 'MTR-1019', 10],
            ['303', 3, 3, false, 8000,  'MTR-1020', 5],
            ['304', 3, 4, true,  13500, 'MTR-1021', 15],
            ['305', 3, 1, false, 6500,  'MTR-1022', 5],
            ['306', 3, 2, true,  10500, 'MTR-1023', 20],
            
            // Floor 4: 5 rooms (401-405)
            ['401', 4, 3, true,  12500, 'MTR-1024', 5],
            ['402', 4, 4, false, 10000, 'MTR-1025', 8],
            ['403', 4, 2, true,  11500, 'MTR-1026', 5],
            ['404', 4, 4, true,  14000, 'MTR-1027', 12],
            ['405', 4, 3, false, 8500,  'MTR-1028', 5],
        ];

        // Months: Oct 2025 → Oct 2026 = 13 months
        $startYear  = 2025;
        $startMonth = 10;
        $endYear    = 2026;
        $endMonth   = 10; // current month
        $contractStart = Carbon::create(2025, 10, 1);

        // kWh baseline per room (realistic starting meter readings)
        $kwhBase = [800,1200,950,600,1400,880,1050,1600,720,1300,
                    1100,650,900,1450,1200,880,1000,1550,580,1020,
                    1350,1180,1450,1050,1700,620,920,1280];

        $nameIdx = 0;

        $allTenantIds = []; // Track all tenant IDs for laundry orders

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
            
            // Store tenant IDs for this room
            $allTenantIds[$ri] = $tenantIds;

            // ── Contract ──────────────────────────────────────────────────
            $contract = Contract::create([
                'room_id'           => $room->id,
                'start_date'        => $contractStart,
                'end_date'          => null,
                'due_day'           => $dueDay,
                'status'            => 'active',
                'is_active'         => true,
                'created_by'        => $owner->id,
            ]);

            $pivotData = [];
            foreach ($tenantIds as $tid) {
                $pivotData[$tid] = ['joined_on' => $contractStart->toDateString(), 'left_on' => null];
            }
            $contract->tenants()->attach($pivotData);

            // Create tenant deposits for each tenant
            foreach ($tenantIds as $tid) {
                TenantDeposit::create([
                    'contract_id'     => $contract->id,
                    'tenant_id'       => $tid,
                    'amount_required' => $rate,
                    'amount_paid'     => $rate,
                    'amount_deducted' => 0,
                    'amount_refunded' => 0,
                    'notes'           => 'Security deposit collected at move-in',
                ]);
            }

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
                $overdueCount = collect($tpStatuses)->filter(fn($s)=>$s==='overdue')->count();
                $unpaid       = $occupancy - $paidCount;

                $invStatus = match(true) {
                    $paidCount === $occupancy      => 'paid',
                    $paidCount > 0                 => 'partial',
                    $overdueCount === $unpaid      => 'overdue',
                    $overdueCount > 0              => 'partial',
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

        // ── 6. Laundry Services ───────────────────────────────────────────
        $this->seedLaundryServices($owner);

        // ── 7. Laundry Orders ─────────────────────────────────────────────
        $this->seedLaundryOrders($staff, $staff2, $allTenantIds);
    }

    // ── Laundry Seeding Methods ──────────────────────────────────────────

    private function seedLaundryServices(User $owner): void
    {
        // Define laundry services
        $services = [
            ['code' => 'WDF', 'name' => 'Wash-Dry-Fold',      'weight_limit_kg' => 8.0],
            ['code' => 'WD',  'name' => 'Wash-Dry',           'weight_limit_kg' => 8.0],
            ['code' => 'DRY', 'name' => 'Dry Only',           'weight_limit_kg' => 8.0],
            ['code' => 'WO',  'name' => 'Wash Only',          'weight_limit_kg' => 8.0],
            ['code' => 'COM', 'name' => 'Comforter/Blanket',  'weight_limit_kg' => 5.0],
        ];

        // Pricing per customer type (using the enum values from migration)
        $prices = [
            'WDF' => ['tenant' => 70, 'student' => 75, 'non_student' => 80],
            'WD'  => ['tenant' => 60, 'student' => 65, 'non_student' => 70],
            'DRY' => ['tenant' => 40, 'student' => 45, 'non_student' => 50],
            'WO'  => ['tenant' => 35, 'student' => 40, 'non_student' => 45],
            'COM' => ['tenant' => 100, 'student' => 110, 'non_student' => 120],
        ];

        foreach ($services as $serviceData) {
            $service = Service::create([
                'code'            => $serviceData['code'],
                'name'            => $serviceData['name'],
                'weight_limit_kg' => $serviceData['weight_limit_kg'],
                'is_active'       => true,
            ]);

            foreach ($prices[$serviceData['code']] as $type => $price) {
                ServicePrice::create([
                    'service_id'    => $service->id,
                    'customer_type' => $type,
                    'price'         => $price,
                ]);
            }
        }

        // Initialize detergent inventory with 50 liters (50,000 ml)
        $inventory = DetergentInventory::create(['stock_ml' => 50000]);
        
        // Log initial stock
        DetergentLog::create([
            'type'            => 'restock',
            'amount_ml'       => 50000,
            'stock_before_ml' => 0,
            'stock_after_ml'  => 50000,
            'user_id'         => $owner->id,
            'notes'           => 'Initial inventory stock',
        ]);
    }

    private function seedLaundryOrders(User $staff, User $staff2, array $allTenantIds): void
    {
        $services = Service::with('prices')->get()->keyBy('code');
        $inventory = DetergentInventory::current();
        
        // Get all tenants with their rooms
        $tenantsWithRooms = [];
        foreach ($allTenantIds as $roomIdx => $tenantIds) {
            $roomNo = $this->getRoomNumberByIndex($roomIdx);
            foreach ($tenantIds as $tid) {
                $tenant = Tenant::with('user')->find($tid);
                if ($tenant && $tenant->user) {
                    $tenantsWithRooms[] = [
                        'id'         => $tid,
                        'name'       => $tenant->user->name,
                        'room_no'    => $roomNo,
                        'contact_no' => $tenant->user->phone,
                    ];
                }
            }
        }

        // Generate laundry orders from Oct 2025 to Oct 2026
        $startDate = Carbon::create(2025, 10, 1);
        $endDate   = Carbon::create(2026, 10, 9); // Current date
        $currentDate = $startDate->copy();

        $orderCount = 0;
        $customers = []; // Track walk-in customers

        while ($currentDate <= $endDate) {
            // Random 2-8 orders per day
            $ordersToday = rand(2, 8);

            for ($i = 0; $i < $ordersToday; $i++) {
                $orderCount++;
                
                // 60% tenant, 30% student, 10% non-student
                $isTenant = rand(1, 100) <= 60;
                
                if ($isTenant && !empty($tenantsWithRooms)) {
                    // Tenant customer
                    $tenantData = $tenantsWithRooms[array_rand($tenantsWithRooms)];
                    
                    $customer = Customer::firstOrCreate(
                        ['name' => $tenantData['name'], 'type' => 'tenant'],
                        [
                            'room_no'    => $tenantData['room_no'],
                            'contact_no' => $tenantData['contact_no'],
                        ]
                    );
                } else {
                    // Student or non-student customer
                    $type = rand(1, 100) <= 75 ? 'student' : 'non_student';
                    
                    // Reuse or create customer
                    $customerKey = $type . '_' . rand(1, 30);
                    if (!isset($customers[$customerKey])) {
                        [$fn, $ln] = $this->makeName(rand(0, 1000));
                        $customers[$customerKey] = Customer::create([
                            'name'       => "$fn $ln",
                            'type'       => $type,
                            'room_no'    => null,
                            'contact_no' => rand(0, 1) ? '09' . rand(10,99) . ' ' . rand(100,999) . ' ' . rand(1000,9999) : null,
                        ]);
                    }
                    $customer = $customers[$customerKey];
                }

                // Create order
                $order = LaundryOrder::create([
                    'order_no'            => LaundryOrder::generateOrderNo($currentDate->toDateString()),
                    'customer_id'         => $customer->id,
                    'customer_type'       => $customer->type,
                    'room_no'             => $customer->room_no,
                    'date_received'       => $currentDate->toDateString(),
                    'payment_method'      => $this->randomLaundryPaymentMethod($customer->type),
                    'payment_status'      => $this->randomLaundryPaymentStatus($currentDate),
                    'paid_before_service' => rand(0, 1),
                    'remarks'             => rand(1, 100) <= 20 ? $this->randomLaundryRemark() : null,
                    'recorded_by'         => rand(0, 1) ? $staff->id : $staff2->id,
                ]);

                // Add 1-3 service items
                $itemCount = rand(1, 3);
                $totalAmount = 0;
                $totalWeight = 0;
                $totalLoads = 0;
                $totalLiquid = 0;

                for ($j = 0; $j < $itemCount; $j++) {
                    $serviceCode = $this->randomLaundryService();
                    $service = $services[$serviceCode];
                    $weight = $this->randomWeight($serviceCode);
                    $unitPrice = (float) $service->priceFor($customer->type);
                    $loads = max(1, (int) ceil($weight / $service->weight_limit_kg));
                    $liquidMl = (int) ceil($weight / Service::LIQUID_KG_PER_UNIT) * Service::LIQUID_ML_PER_UNIT;

                    OrderItem::create([
                        'laundry_order_id' => $order->id,
                        'service_id'       => $service->id,
                        'weight_kg'        => $weight,
                        'weight_limit_kg'  => $service->weight_limit_kg,
                        'unit_price'       => $unitPrice,
                        'loads'            => $loads,
                        'liquid_ml'        => $liquidMl,
                        'subtotal'         => $unitPrice * $loads,
                    ]);

                    $totalAmount += $unitPrice * $loads;
                    $totalWeight += $weight;
                    $totalLoads += $loads;
                    $totalLiquid += $liquidMl;
                }

                // Update order totals
                $order->update([
                    'total_amount'    => $totalAmount,
                    'total_weight_kg' => $totalWeight,
                    'total_loads'     => $totalLoads,
                    'total_liquid_ml' => $totalLiquid,
                ]);

                // Deduct detergent (if enough stock)
                if ($inventory->hasEnough($totalLiquid)) {
                    $inventory->deductStock($totalLiquid, $order, $order->recorder);
                } else {
                    // Restock if low (add 30 liters)
                    $inventory->addStock(30000, $order->recorder, 'Auto-restock during seeding');
                    $inventory->deductStock($totalLiquid, $order, $order->recorder);
                }

                // Occasionally add more restock entries
                if (rand(1, 100) <= 5) {
                    $inventory->addStock(rand(10000, 50000), rand(0, 1) ? $staff : $staff2, 'Regular restock');
                }
            }

            $currentDate->addDay();
        }
    }

    // ── Laundry Helper Methods ───────────────────────────────────────────

    private function getRoomNumberByIndex(int $idx): string
    {
        $rooms = [
            '101','102','103','104','105','106','107','108','109','110','111',
            '201','202','203','204','205','206',
            '301','302','303','304','305','306',
            '401','402','403','404','405',
        ];
        return $rooms[$idx] ?? '101';
    }

    private function randomLaundryService(): string
    {
        $services = ['WDF', 'WD', 'DRY', 'WO', 'COM'];
        $weights = [40, 30, 15, 10, 5]; // Weighted probability
        
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach ($weights as $i => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $services[$i];
            }
        }
        
        return 'WDF';
    }

    private function randomWeight(string $serviceCode): float
    {
        return match($serviceCode) {
            'COM' => round(rand(30, 60) / 10, 1), // 3.0 - 6.0 kg for comforters
            default => round(rand(15, 120) / 10, 1), // 1.5 - 12.0 kg for regular
        };
    }

    private function randomLaundryPaymentMethod(string $customerType): string
    {
        $methods = ['cash', 'cash', 'cash', 'online']; // 75% cash, 25% online
        return $methods[array_rand($methods)];
    }

    private function randomLaundryPaymentStatus(Carbon $date): string
    {
        // Past orders: 90% paid, 10% unpaid
        // Recent orders (last week): 70% paid, 30% unpaid
        $daysAgo = now()->diffInDays($date);
        
        if ($daysAgo > 7) {
            return rand(1, 100) <= 90 ? 'paid' : 'unpaid';
        }
        
        return rand(1, 100) <= 70 ? 'paid' : 'unpaid';
    }

    private function randomLaundryRemark(): string
    {
        $remarks = [
            'Extra fabric softener',
            'Separate whites',
            'No bleach',
            'Delicate cycle',
            'Rush order',
            'Fold neatly',
            'Separate dark colors',
            'Air dry only',
            'Iron shirts',
            'Stain on collar',
        ];
        
        return $remarks[array_rand($remarks)];
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Determine payment scenario for a tenant in a given month.
     * More realistic distribution: mix of paid, pending, late, overdue across all months.
     */
    private function paymentScenario(int $roomIdx, int $tenantIdx, int $year, int $month): string
    {
        $now        = Carbon::now();
        $monthDate  = Carbon::create($year, $month, 1);
        $monthsAgo  = $now->diffInMonths($monthDate);

        // Current month (Oct 2026) — mostly pending/unpaid
        if ($monthsAgo === 0) {
            $seed = ($roomIdx * 7 + $tenantIdx * 3) % 100;
            return match(true) {
                $seed < 25 => 'paid',       // 25% already paid
                $seed < 60 => 'pending',    // 35% pending
                default   => 'overdue',     // 40% overdue
            };
        }

        // Last month — mix of statuses
        if ($monthsAgo === 1) {
            $seed = ($roomIdx * 11 + $tenantIdx * 5 + $month) % 100;
            return match(true) {
                $seed < 45 => 'paid',       // 45% paid
                $seed < 60 => 'partial',    // 15% partial
                $seed < 75 => 'pending',    // 15% still pending
                default   => 'overdue',     // 25% overdue
            };
        }

        // 2-3 months ago — more paid but still some pending
        if ($monthsAgo <= 3) {
            $seed = ($roomIdx * 5 + $tenantIdx * 11 + $month) % 100;
            return match(true) {
                $seed < 60 => 'paid',       // 60% paid
                $seed < 75 => 'partial',    // 15% partial
                $seed < 90 => 'pending',    // 15% pending
                default   => 'overdue',     // 10% overdue
            };
        }

        // Older months (4+ months) — mostly paid, occasional issues
        $seed = ($roomIdx * 3 + $tenantIdx * 7 + $month + $year) % 100;
        return match(true) {
            $seed < 75 => 'paid',       // 75% paid
            $seed < 88 => 'partial',    // 13% partial
            $seed < 95 => 'pending',    // 7% still pending
            default   => 'overdue',     // 5% overdue
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
