<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\TenantDeposit;
use App\Models\TenantPayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RealisticDummySeeder extends Seeder
{
    private array $firstNames = [
        'Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Rosa', 'Miguel', 'Carmen', 
        'Luis', 'Elena', 'Carlos', 'Sofia', 'Ricardo', 'Isabel', 'Fernando',
        'Lucia', 'Diego', 'Patricia', 'Manuel', 'Angela', 'Rafael', 'Laura',
        'Antonio', 'Cristina', 'Gabriel', 'Monica', 'Roberto', 'Teresa'
    ];

    private array $lastNames = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Dela Cruz', 'Garcia', 'Ramos',
        'Flores', 'Mendoza', 'Torres', 'Gonzales', 'Lopez', 'Rivera', 'Martinez',
        'Hernandez', 'Aquino', 'Villanueva', 'Castillo', 'Morales', 'Romero'
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        TenantPayment::truncate();
        Payment::truncate();
        Invoice::truncate();
        MeterReading::truncate();
        TenantDeposit::truncate();
        Contract::truncate();
        Tenant::truncate();
        Room::truncate();
        User::whereHas('roles', fn($q) => $q->where('name', 'tenant'))->delete();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Creating rooms...');
        $rooms = $this->createRooms();
        
        $this->command->info('Creating tenants and contracts...');
        $contracts = $this->createTenantsAndContracts($rooms);
        
        $this->command->info('Creating meter readings...');
        $this->createMeterReadings($rooms);
        
        $this->command->info('Creating invoices and payments...');
        $this->createInvoicesAndPayments($contracts);
        
        $this->command->info('✓ Realistic dummy data created successfully!');
    }

    private function createRooms(): array
    {
        $rooms = [];
        $roomNumber = 101;
        
        // Floor 1: 11 rooms (101-111)
        for ($i = 0; $i < 11; $i++) {
            $capacity = rand(1, 3);
            $rooms[] = Room::create([
                'room_number' => (string)$roomNumber,
                'floor' => 1,
                'capacity' => $capacity,
                'monthly_rate' => match($capacity) {
                    1 => 5000,
                    2 => 7000,
                    3 => 9000,
                },
                'deposit_required' => match($capacity) {
                    1 => 5000,
                    2 => 7000,
                    3 => 9000,
                },
                'meter_number' => 'MTR-' . str_pad($roomNumber, 4, '0', STR_PAD_LEFT),
                'status' => 'vacant',
                'is_airconditioned' => rand(0, 100) < 30,
            ]);
            $roomNumber++;
        }
        
        // Floor 2-3: 17 rooms (201-209, 301-308)
        for ($floor = 2; $floor <= 3; $floor++) {
            $roomsInFloor = $floor === 2 ? 9 : 8;
            $roomNumber = $floor * 100 + 1;
            
            for ($i = 0; $i < $roomsInFloor; $i++) {
                $capacity = rand(1, 3);
                $rooms[] = Room::create([
                    'room_number' => (string)$roomNumber,
                    'floor' => $floor,
                    'capacity' => $capacity,
                    'monthly_rate' => match($capacity) {
                        1 => 5500,
                        2 => 7500,
                        3 => 9500,
                    },
                    'deposit_required' => match($capacity) {
                        1 => 5500,
                        2 => 7500,
                        3 => 9500,
                    },
                    'meter_number' => 'MTR-' . str_pad($roomNumber, 4, '0', STR_PAD_LEFT),
                    'status' => 'vacant',
                    'is_airconditioned' => rand(0, 100) < 40,
                ]);
                $roomNumber++;
            }
        }
        
        return $rooms;
    }

    private function createTenantsAndContracts(array $rooms): array
    {
        $contracts = [];
        $usedNames = [];
        $owner = User::role('owner')->first();
        
        // Decide which rooms are occupied (about 75-85%)
        $occupiedCount = rand(21, 24); // 75-85% of 28 rooms
        shuffle($rooms);
        $occupiedRooms = array_slice($rooms, 0, $occupiedCount);
        
        foreach ($occupiedRooms as $room) {
            // Random contract start date between Aug 1 - Sep 15, 2026
            $startDate = now()->setDate(2026, 8, 1)->addDays(rand(0, 45));
            
            // Number of tenants based on room capacity (not always full)
            $tenantCount = rand(1, min($room->capacity, $room->capacity === 1 ? 1 : $room->capacity - (rand(0, 100) < 30 ? 1 : 0)));
            
            // Create tenants
            $tenantIds = [];
            for ($i = 0; $i < $tenantCount; $i++) {
                do {
                    $firstName = $this->firstNames[array_rand($this->firstNames)];
                    $lastName = $this->lastNames[array_rand($this->lastNames)];
                    $fullName = $firstName . ' ' . $lastName;
                } while (in_array($fullName, $usedNames));
                
                $usedNames[] = $fullName;
                
                $email = strtolower(str_replace(' ', '.', $firstName . '.' . $lastName . rand(1, 99))) . '@tenant.quest';
                
                $user = User::create([
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'phone' => '09' . rand(100000000, 999999999),
                    'phone_country_code' => '+63',
                    'is_active' => true,
                ]);
                
                $user->assignRole('tenant');
                
                $tenant = Tenant::create([
                    'user_id' => $user->id,
                    'birthdate' => now()->subYears(rand(20, 35))->format('Y-m-d'),
                    'id_type' => ['National ID', 'Passport', 'Driver\'s License'][rand(0, 2)],
                    'id_number' => 'ID-' . rand(100000, 999999),
                    'emergency_name' => $this->firstNames[array_rand($this->firstNames)] . ' ' . $this->lastNames[array_rand($this->lastNames)],
                    'emergency_phone' => '09' . rand(100000000, 999999999),
                ]);
                
                $tenantIds[] = $tenant->id;
            }
            
            // Create contract
            $dueDay = [5, 10, 15][rand(0, 2)]; // Random due day
            
            $contract = Contract::create([
                'room_id' => $room->id,
                'start_date' => $startDate,
                'end_date' => null,
                'due_day' => $dueDay,
                'deposit_collected' => 0,
                'status' => 'active',
                'is_active' => true,
                'created_by' => $owner->id,
            ]);
            
            // Attach tenants
            foreach ($tenantIds as $tid) {
                $contract->tenants()->attach($tid, [
                    'joined_on' => $startDate,
                    'left_on' => null,
                ]);
            }
            
            // Create deposit records - tenants MUST pay deposit before moving in
            $depositPerTenant = round($room->deposit_required / $tenantCount, 2);
            foreach ($tenantIds as $tid) {
                TenantDeposit::create([
                    'contract_id' => $contract->id,
                    'tenant_id' => $tid,
                    'amount_required' => $depositPerTenant,
                    'amount_paid' => $depositPerTenant, // All tenants paid deposit (required to move in)
                    'amount_deducted' => 0,
                    'amount_refunded' => 0,
                ]);
            }
            
            // Mark room as occupied
            $room->update(['status' => 'occupied']);
            
            $contracts[] = [
                'contract' => $contract,
                'room' => $room,
                'tenant_count' => $tenantCount,
                'start_date' => $startDate,
                'due_day' => $dueDay,
            ];
        }
        
        return $contracts;
    }

    private function createMeterReadings(array $rooms): void
    {
        $owner = User::role('owner')->first();
        
        // Create readings for Aug, Sep, Oct 2026
        foreach (['2026-08', '2026-09', '2026-10'] as $month) {
            foreach ($rooms as $room) {
                if ($room->status === 'occupied') {
                    // Random usage between 50-250 kWh
                    $previousReading = rand(1000, 5000);
                    $currentReading = $previousReading + rand(50, 250);
                    
                    MeterReading::create([
                        'room_id' => $room->id,
                        'month' => $month,
                        'previous_reading' => $previousReading,
                        'current_reading' => $currentReading,
                        'kwh_used' => $currentReading - $previousReading,
                        'recorded_by' => $owner->id,
                    ]);
                }
            }
        }
    }

    private function createInvoicesAndPayments(array $contracts): void
    {
        $owner = User::role('owner')->first();
        $electricityRate = 12.50;
        
        // Only create invoices up to October 2026 (current month)
        // Since today is Oct 9, we can create Oct invoices
        $months = ['2026-08', '2026-09', '2026-10'];
        
        foreach ($contracts as $contractData) {
            $contract = $contractData['contract'];
            $room = $contractData['room'];
            $tenantCount = $contractData['tenant_count'];
            $startDate = $contractData['start_date'];
            $dueDay = $contractData['due_day'];
            
            foreach ($months as $month) {
                $monthDate = now()->parse($month . '-01');
                
                // Only create invoice if contract started before or during this month
                if ($startDate->format('Y-m') > $month) {
                    continue;
                }
                
                // Don't create Oct invoice if contract started after Oct 9
                if ($month === '2026-10' && $startDate->day > 9) {
                    continue;
                }
                
                $dueDate = $monthDate->copy()->day($dueDay);
                
                // Get meter reading
                $reading = MeterReading::where('room_id', $room->id)
                    ->where('month', $month)
                    ->first();
                
                $electricityAmount = $reading ? ($reading->kwh_used * $electricityRate) : 0;
                
                // Calculate carry-over from previous unpaid invoices
                $carryOver = 0;
                foreach ($contract->tenants as $tenant) {
                    $previousUnpaid = TenantPayment::whereHas('invoice', function($q) use ($contract, $month) {
                        $q->where('contract_id', $contract->id)
                          ->where('billing_month', '<', $month);
                    })
                    ->where('tenant_id', $tenant->id)
                    ->where('status', '!=', 'paid')
                    ->sum('share_amount');
                    
                    $carryOver += $previousUnpaid;
                }
                
                $totalAmount = $room->monthly_rate + $electricityAmount + $carryOver;
                
                $invoiceNumber = 'BILL-' . str_replace('-', '', $month) . '-' . str_pad($room->room_number, 3, '0', STR_PAD_LEFT);
                
                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'contract_id' => $contract->id,
                    'tenant_count' => $tenantCount,
                    'billing_month' => $month,
                    'due_date' => $dueDate,
                    'rent_amount' => $room->monthly_rate,
                    'electricity_amount' => $electricityAmount,
                    'carry_over_balance' => $carryOver,
                    'credit_balance' => 0,
                    'total_amount' => $totalAmount,
                    'amount_paid' => 0,
                    'status' => 'pending',
                    'paid_at' => null,
                    'created_by' => $owner->id,
                ]);
                
                // Create tenant payments
                $shareAmount = round(($room->monthly_rate + $electricityAmount) / $tenantCount, 2);
                
                foreach ($contract->tenants as $tenant) {
                    // Get this tenant's carry-over
                    $tenantCarryOver = TenantPayment::whereHas('invoice', function($q) use ($contract, $month) {
                        $q->where('contract_id', $contract->id)
                          ->where('billing_month', '<', $month);
                    })
                    ->where('tenant_id', $tenant->id)
                    ->where('status', '!=', 'paid')
                    ->sum('share_amount');
                    
                    $tenantPayment = TenantPayment::create([
                        'invoice_id' => $invoice->id,
                        'tenant_id' => $tenant->id,
                        'share_amount' => $shareAmount,
                        'carry_over_balance' => $tenantCarryOver,
                        'amount_paid' => 0,
                        'status' => 'pending',
                        'method' => null,
                        'paid_at' => null,
                    ]);
                    
                    // Determine payment status based on month and randomization
                    $paymentChance = $this->getPaymentChance($month, $dueDate);
                    $shouldPay = rand(1, 100) <= $paymentChance;
                    
                    if ($shouldPay) {
                        $totalOwed = $shareAmount + $tenantCarryOver;
                        $amountPaid = $totalOwed; // Pay in full
                        
                        $paidDate = $this->getRandomPaymentDate($month, $dueDate);
                        
                        $tenantPayment->update([
                            'amount_paid' => $amountPaid,
                            'status' => 'paid',
                            'method' => ['cash', 'gcash', 'bank_transfer'][rand(0, 2)],
                            'paid_at' => $paidDate,
                        ]);
                        
                        // Create payment record
                        Payment::create([
                            'invoice_id' => $invoice->id,
                            'amount' => $amountPaid,
                            'payment_date' => $paidDate,
                            'payment_method' => $tenantPayment->method,
                            'reference_number' => 'REF-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                            'remarks' => 'Payment for ' . $tenant->user->name,
                            'recorded_by_user' => $owner->id,
                        ]);
                    }
                }
                
                // Recompute invoice status
                $invoice->recomputeStatus();
            }
        }
    }

    private function getPaymentChance(string $month, $dueDate): int
    {
        // August: 85% paid (old month)
        if ($month === '2026-08') return 85;
        
        // September: 70% paid
        if ($month === '2026-09') return 70;
        
        // October: 40% paid (current month, only 9 days in)
        if ($month === '2026-10') return 40;
        
        return 50;
    }

    private function getRandomPaymentDate(string $month, $dueDate)
    {
        $monthStart = now()->parse($month . '-01');
        
        // August: paid anywhere in August-September
        if ($month === '2026-08') {
            return now()->setDate(2026, rand(8, 9), rand(1, 28));
        }
        
        // September: paid anywhere in September
        if ($month === '2026-09') {
            return now()->setDate(2026, 9, rand(1, 30));
        }
        
        // October: paid between Oct 1-9 (since today is Oct 9)
        if ($month === '2026-10') {
            return now()->setDate(2026, 10, rand(1, 9));
        }
        
        return now();
    }
}
