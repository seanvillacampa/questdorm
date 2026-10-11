<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\MeterReading;
use App\Models\TenantDeposit;
use App\Models\TenantPayment;
use App\Models\Setting;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class QuestBuildingSeeder extends Seeder
{
    /**
     * Run the database seeds - Quest Building Data
     */
    public function run()
    {
        $this->command->info('Starting Quest Building seeder...');
        
        // Skip clearing for now - just try to seed
        $this->command->info('Creating roles...');
        $this->createRoles();
        
        $this->command->info('Creating users and tenants...');
        $this->createUsersAndTenants();
        
        $this->command->info('Creating settings...');
        $this->createSettings();
        
        $this->command->info('Creating rooms...');
        $this->createRooms();
        
        $this->command->info('Creating contracts...');
        $this->createContracts();
        
        $this->command->info('Creating tenant deposits...');
        $this->createTenantDeposits();
        
        $this->command->info('Creating invoices and payments...');
        $this->createInvoicesAndPayments();
        
        $this->command->newLine();
        $this->command->info('✓ Quest Building seed completed successfully!');
        $this->command->info('  - 28 rooms created');
        $this->command->info('  - 45 tenants created');
        $this->command->info('  - 28 contracts created');
        $this->command->info('  - 280 invoices created (Jan-Oct 2026)');
        $this->command->info('  - All deposits paid');
        $this->command->newLine();
        $this->command->info('Login credentials:');
        $this->command->info('  Owner: owner@dorm.test / password');
        $this->command->info('  Tenant: elena.rivera@email.com / password');
    }
    
    private function createRoles()
    {
        // Skip if roles already exist to avoid locks
        if (Role::where('name', 'tenant')->where('guard_name', 'web')->exists()) {
            $this->command->info('Roles already exist, skipping...');
            return;
        }
        
        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        Role::create(['name' => 'employee', 'guard_name' => 'web']);
        Role::create(['name' => 'tenant', 'guard_name' => 'web']);
    }
    
    private function createSettings()
    {
        // Settings table only supports numeric values (decimal)
        // Text settings like company_name are stored in .env or config
        $effectiveDate = Carbon::parse('2025-12-01')->format('Y-m-d');
        
        $settings = [
            'deposit_months' => 2,
            'electricity_rate' => 12.50,
            'late_payment_penalty_rate' => 3,
            'grace_period_days' => 5,
            'invoice_advance_days' => 3,
            'overdue_grace_days' => 3,
            'default_due_day' => 5,
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'effective_from' => $effectiveDate,
                    'set_by' => 1, // Owner user ID
                    'created_at' => Carbon::parse('2025-12-01'),
                    'updated_at' => Carbon::parse('2025-12-01')
                ]
            );
        }
    }
    
    private function createRooms()
    {
        $rooms = [
            // Floor 1 - 11 rooms
            ['id' => 1, 'number' => '101', 'floor' => 1, 'capacity' => 3, 'rate' => 4500],
            ['id' => 2, 'number' => '102', 'floor' => 1, 'capacity' => 2, 'rate' => 4000],
            ['id' => 3, 'number' => '103', 'floor' => 1, 'capacity' => 3, 'rate' => 4500],
            ['id' => 4, 'number' => '104', 'floor' => 1, 'capacity' => 2, 'rate' => 4000],
            ['id' => 5, 'number' => '105', 'floor' => 1, 'capacity' => 3, 'rate' => 4500],
            ['id' => 6, 'number' => '106', 'floor' => 1, 'capacity' => 2, 'rate' => 4000],
            ['id' => 7, 'number' => '107', 'floor' => 1, 'capacity' => 3, 'rate' => 4500],
            ['id' => 8, 'number' => '108', 'floor' => 1, 'capacity' => 2, 'rate' => 4000],
            ['id' => 9, 'number' => '109', 'floor' => 1, 'capacity' => 3, 'rate' => 4500],
            ['id' => 10, 'number' => '110', 'floor' => 1, 'capacity' => 2, 'rate' => 4000],
            ['id' => 11, 'number' => '111', 'floor' => 1, 'capacity' => 2, 'rate' => 4000],
            // Floor 2 - 6 rooms
            ['id' => 12, 'number' => '201', 'floor' => 2, 'capacity' => 3, 'rate' => 4800],
            ['id' => 13, 'number' => '202', 'floor' => 2, 'capacity' => 2, 'rate' => 4200],
            ['id' => 14, 'number' => '203', 'floor' => 2, 'capacity' => 3, 'rate' => 4800],
            ['id' => 15, 'number' => '204', 'floor' => 2, 'capacity' => 2, 'rate' => 4200],
            ['id' => 16, 'number' => '205', 'floor' => 2, 'capacity' => 3, 'rate' => 4800],
            ['id' => 17, 'number' => '206', 'floor' => 2, 'capacity' => 2, 'rate' => 4200],
            // Floor 3 - 6 rooms
            ['id' => 18, 'number' => '301', 'floor' => 3, 'capacity' => 3, 'rate' => 5000],
            ['id' => 19, 'number' => '302', 'floor' => 3, 'capacity' => 2, 'rate' => 4500],
            ['id' => 20, 'number' => '303', 'floor' => 3, 'capacity' => 3, 'rate' => 5000],
            ['id' => 21, 'number' => '304', 'floor' => 3, 'capacity' => 2, 'rate' => 4500],
            ['id' => 22, 'number' => '305', 'floor' => 3, 'capacity' => 3, 'rate' => 5000],
            ['id' => 23, 'number' => '306', 'floor' => 3, 'capacity' => 2, 'rate' => 4500],
            // Floor 4 - 5 rooms
            ['id' => 24, 'number' => '401', 'floor' => 4, 'capacity' => 3, 'rate' => 5200],
            ['id' => 25, 'number' => '402', 'floor' => 4, 'capacity' => 2, 'rate' => 4800],
            ['id' => 26, 'number' => '403', 'floor' => 4, 'capacity' => 3, 'rate' => 5200],
            ['id' => 27, 'number' => '404', 'floor' => 4, 'capacity' => 2, 'rate' => 4800],
            ['id' => 28, 'number' => '405', 'floor' => 4, 'capacity' => 3, 'rate' => 5200],
        ];
        
        foreach ($rooms as $room) {
            // Deposit is 2x monthly rate
            $depositRequired = $room['rate'] * 2;
            
            Room::create([
                'id' => $room['id'],
                'room_number' => $room['number'],
                'floor' => $room['floor'],
                'capacity' => $room['capacity'],
                'monthly_rate' => $room['rate'],
                'deposit_required' => $depositRequired,
                'meter_number' => 'MTR-' . $room['number'],
                'status' => 'occupied',
                'created_at' => Carbon::parse('2025-12-01'),
                'updated_at' => Carbon::parse('2025-12-15'),
            ]);
        }
    }
    
    private function createUsersAndTenants()
    {
        // Create owner user first
        $owner = User::create([
            'id' => 1,
            'first_name' => 'Quest',
            'middle_name' => 'Building',
            'last_name' => 'Owner',
            'email' => 'owner@dorm.test',
            'email_verified_at' => Carbon::parse('2025-12-01 10:00:00'),
            'password' => Hash::make('password'),
            'phone' => '09171234567',
            'phone_country_code' => '+63',
            'is_active' => 1,
            'created_at' => Carbon::parse('2025-12-01 10:00:00'),
            'updated_at' => Carbon::parse('2025-12-01 10:00:00')
        ]);
        $owner->assignRole('owner');
        
        // Proper Filipino names with first, middle, last names
        $tenants = [
            ['first' => 'Elena', 'middle' => 'Maria', 'last' => 'Rivera'],
            ['first' => 'Daniela', 'middle' => 'Sofia', 'last' => 'Alvarez'],
            ['first' => 'Jose', 'middle' => 'Miguel', 'last' => 'Santos'],
            ['first' => 'Rafael', 'middle' => 'Antonio', 'last' => 'Rios'],
            ['first' => 'Isabel', 'middle' => 'Carmen', 'last' => 'Diaz'],
            ['first' => 'Maria', 'middle' => 'Luisa', 'last' => 'Santos'],
            ['first' => 'Mariana', 'middle' => 'Teresa', 'last' => 'Ortiz'],
            ['first' => 'Jorge', 'middle' => 'Luis', 'last' => 'Lopez'],
            ['first' => 'Mariana', 'middle' => 'Rosa', 'last' => 'Dela Cruz'],
            ['first' => 'Ana', 'middle' => 'Victoria', 'last' => 'Lopez'],
            ['first' => 'Rodrigo', 'middle' => 'Manuel', 'last' => 'Lopez'],
            ['first' => 'Ana', 'middle' => 'Patricia', 'last' => 'Ramos'],
            ['first' => 'David', 'middle' => 'Fernando', 'last' => 'Soto'],
            ['first' => 'Natalia', 'middle' => 'Gabriela', 'last' => 'Diaz'],
            ['first' => 'Monica', 'middle' => 'Beatriz', 'last' => 'Castillo'],
            ['first' => 'Eduardo', 'middle' => 'Carlos', 'last' => 'Ramos'],
            ['first' => 'Hector', 'middle' => 'Alejandro', 'last' => 'Gonzalez'],
            ['first' => 'Javier', 'middle' => 'Roberto', 'last' => 'Valencia'],
            ['first' => 'Eduardo', 'middle' => 'Enrique', 'last' => 'Soto'],
            ['first' => 'Camila', 'middle' => 'Andrea', 'last' => 'Benitez'],
            ['first' => 'Liliana', 'middle' => 'Isabel', 'last' => 'Vega'],
            ['first' => 'Teresa', 'middle' => 'Angelica', 'last' => 'Cruz'],
            ['first' => 'Pablo', 'middle' => 'Vicente', 'last' => 'Herrera'],
            ['first' => 'Mariana', 'middle' => 'Elena', 'last' => 'Silva'],
            ['first' => 'Juan', 'middle' => 'Pablo', 'last' => 'Guzman'],
            ['first' => 'Jose', 'middle' => 'Ricardo', 'last' => 'Aguilar'],
            ['first' => 'Gloria', 'middle' => 'Mercedes', 'last' => 'Perez'],
            ['first' => 'Miguel', 'middle' => 'Angel', 'last' => 'Salazar'],
            ['first' => 'Adriana', 'middle' => 'Cristina', 'last' => 'Gutierrez'],
            ['first' => 'Rodrigo', 'middle' => 'Javier', 'last' => 'Rodriguez'],
            ['first' => 'Antonio', 'middle' => 'Francisco', 'last' => 'Ortiz'],
            ['first' => 'Mariana', 'middle' => 'Guadalupe', 'last' => 'Cruz'],
            ['first' => 'Natalia', 'middle' => 'Fernanda', 'last' => 'Castillo'],
            ['first' => 'Jose', 'middle' => 'Luis', 'last' => 'Alvarez'],
            ['first' => 'Ramon', 'middle' => 'Eduardo', 'last' => 'Pena'],
            ['first' => 'Monica', 'middle' => 'Alejandra', 'last' => 'Moreno'],
            ['first' => 'David', 'middle' => 'Ignacio', 'last' => 'Mendez'],
            ['first' => 'Javier', 'middle' => 'Arturo', 'last' => 'Salazar'],
            ['first' => 'Juan', 'middle' => 'Carlos', 'last' => 'Martinez'],
            ['first' => 'Jose', 'middle' => 'Alfredo', 'last' => 'Herrera'],
            ['first' => 'Rafael', 'middle' => 'Lorenzo', 'last' => 'Benitez'],
            ['first' => 'Lucia', 'middle' => 'Valentina', 'last' => 'Ramirez'],
            ['first' => 'Jorge', 'middle' => 'Alberto', 'last' => 'Ortiz'],
            ['first' => 'Daniela', 'middle' => 'Lucia', 'last' => 'Romero'],
            ['first' => 'Laura', 'middle' => 'Isabel', 'last' => 'Dela Cruz']
        ];
        
        $phones = [
            '09176450130', '09175017874', '09179476302', '09177913221', '09176714904',
            '09175097570', '09177221871', '09174259762', '09173202798', '09179398216',
            '09178187133', '09177793533', '09179169345', '09179931279', '09175476438',
            '09172714510', '09175237701', '09172963496', '09171917922', '09174855820',
            '09177354555', '09177322505', '09178981747', '09172602595', '09176659894',
            '09174577330', '09178071156', '09173844417', '09174378673', '09178061486',
            '09179036543', '09179012028', '09179140372', '09177174949', '09171097860',
            '09177099275', '09178401745', '09175998516', '09178229485', '09177493609',
            '09172877778', '09178108278', '09174837924', '09174607840', '09178533791'
        ];
        
        $baseDate = Carbon::parse('2025-12-10 10:00:00');
        
        for ($i = 0; $i < 45; $i++) {
            $userId = 101 + $i;
            $tenant = $tenants[$i];
            $email = strtolower($tenant['first'] . '.' . $tenant['last']) . '@email.com';
            $createdAt = $baseDate->copy()->addDays($i);
            
            $user = User::create([
                'id' => $userId,
                'first_name' => $tenant['first'],
                'middle_name' => $tenant['middle'],
                'last_name' => $tenant['last'],
                'email' => $email,
                'email_verified_at' => $createdAt,
                'password' => Hash::make('password'),
                'phone' => $phones[$i],
                'phone_country_code' => '+63',
                'is_active' => 1,
                'created_at' => $createdAt,
                'updated_at' => $createdAt
            ]);
            
            // Generate birthdate (ages 20-30 years old)
            $age = 20 + ($i % 11); // Ages from 20 to 30
            $birthdate = Carbon::now()->subYears($age)->subDays(rand(0, 364))->format('Y-m-d');
            
            // ID types
            $idTypes = ['National ID', 'Passport', 'Driver\'s License', 'SSS ID', 'UMID'];
            $idType = $idTypes[$i % 5];
            
            // Generate ID number based on type
            $idNumber = match($idType) {
                'National ID' => 'NIDPH-' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT) . '-' . rand(10, 99),
                'Passport' => 'P' . rand(1000000, 9999999) . 'A',
                'Driver\'s License' => 'DL' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'SSS ID' => str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
                'UMID' => str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
                default => 'ID-' . rand(100000, 999999)
            };
            
            // Emergency contacts (different from tenant's own phone)
            $emergencyNames = [
                'Maria Santos', 'Juan Dela Cruz', 'Rosa Garcia', 'Pedro Reyes', 'Carmen Lopez',
                'Antonio Ramos', 'Sofia Martinez', 'Miguel Torres', 'Isabel Gonzales', 'Ramon Cruz',
                'Luisa Diaz', 'Carlos Santos', 'Ana Rodriguez', 'Jose Fernandez', 'Gloria Perez',
                'Eduardo Morales', 'Patricia Castillo', 'Roberto Silva', 'Teresa Mendez', 'Fernando Ortiz',
                'Gabriela Herrera', 'Alfredo Rios', 'Beatriz Alvarez', 'Manuel Salazar', 'Cristina Vega',
                'Lorenzo Benitez', 'Valentina Ramirez', 'Alberto Guzman', 'Lucia Aguilar', 'Ricardo Valencia',
                'Andrea Gutierrez', 'Francisco Romero', 'Elena Pena', 'Javier Moreno', 'Natalia Castro',
                'Rodrigo Jimenez', 'Monica Vargas', 'David Navarro', 'Mariana Ruiz', 'Hector Flores',
                'Camila Medina', 'Jorge Acosta', 'Daniela Ortega', 'Pablo Iglesias', 'Laura Nunez'
            ];
            
            $emergencyPhones = [
                '09281234567', '09282345678', '09283456789', '09284567890', '09285678901',
                '09286789012', '09287890123', '09288901234', '09289012345', '09290123456',
                '09291234567', '09292345678', '09293456789', '09294567890', '09295678901',
                '09296789012', '09297890123', '09298901234', '09299012345', '09200123456',
                '09201234567', '09202345678', '09203456789', '09204567890', '09205678901',
                '09206789012', '09207890123', '09208901234', '09209012345', '09210123456',
                '09211234567', '09212345678', '09213456789', '09214567890', '09215678901',
                '09216789012', '09217890123', '09218901234', '09219012345', '09220123456',
                '09221234567', '09222345678', '09223456789', '09224567890', '09225678901'
            ];
            
            Tenant::create([
                'id' => $i + 1,
                'user_id' => $userId,
                'birthdate' => $birthdate,
                'id_type' => $idType,
                'id_number' => $idNumber,
                'emergency_name' => $emergencyNames[$i],
                'emergency_phone' => $emergencyPhones[$i],
                'created_at' => $createdAt,
                'updated_at' => $createdAt
            ]);
            
            // Assign tenant role
            $user->assignRole('tenant');
        }
    }
    
    private function createContracts()
    {
        $dueDays = [25, 20, 1, 25, 5, 25, 20, 22, 25, 28, 5, 12, 1, 25, 5, 10, 5, 12, 10, 15, 10, 8, 15, 1, 20, 22, 22, 18];
        $tenantAssignments = [
            1 => [1, 2], 2 => [3, 4], 3 => [5], 4 => [6, 7], 5 => [8], 6 => [9], 7 => [10],
            8 => [11, 12], 9 => [13, 14, 15], 10 => [16, 17], 11 => [18], 12 => [19], 13 => [20],
            14 => [21], 15 => [22], 16 => [23, 24], 17 => [25], 18 => [26, 27], 19 => [28],
            20 => [29], 21 => [30, 31], 22 => [32, 33], 23 => [34, 35], 24 => [36, 37], 25 => [38],
            26 => [39, 40], 27 => [41], 28 => [42, 43, 44]
        ];
        
        $contractDate = Carbon::parse('2025-12-15');
        
        for ($roomId = 1; $roomId <= 28; $roomId++) {
            $room = Room::find($roomId);
            $contract = Contract::create([
                'id' => $roomId,
                'room_id' => $roomId,
                'start_date' => $contractDate,
                'end_date' => Carbon::parse('2027-12-31'),
                'monthly_rent' => $room->monthly_rate,
                'due_day' => $dueDays[$roomId - 1],
                'deposit_collected' => $room->deposit_required, // Use room's deposit_required
                'status' => 'active',
                'is_active' => true,
                'created_at' => $contractDate,
                'updated_at' => $contractDate
            ]);
            
            // Assign tenants to contract
            foreach ($tenantAssignments[$roomId] as $tenantId) {
                DB::table('contract_tenants')->insert([
                    'contract_id' => $roomId,
                    'tenant_id' => $tenantId,
                    'joined_on' => $contractDate,
                    'created_at' => $contractDate,
                    'updated_at' => $contractDate
                ]);
            }
        }
    }
    
    private function createTenantDeposits()
    {
        $depositDate = Carbon::parse('2025-12-15 12:00:00');
        $tenantAssignments = [
            1 => [1, 2], 2 => [3, 4], 3 => [5], 4 => [6, 7], 5 => [8], 6 => [9], 7 => [10],
            8 => [11, 12], 9 => [13, 14, 15], 10 => [16, 17], 11 => [18], 12 => [19], 13 => [20],
            14 => [21], 15 => [22], 16 => [23, 24], 17 => [25], 18 => [26, 27], 19 => [28],
            20 => [29], 21 => [30, 31], 22 => [32, 33], 23 => [34, 35], 24 => [36, 37], 25 => [38],
            26 => [39, 40], 27 => [41], 28 => [42, 43, 44]
        ];
        
        foreach ($tenantAssignments as $contractId => $tenantIds) {
            $contract = Contract::find($contractId);
            $totalDeposit = $contract->deposit_collected;
            $tenantCount = count($tenantIds);
            $depositPerTenant = $totalDeposit / $tenantCount;
            
            foreach ($tenantIds as $index => $tenantId) {
                // Handle rounding for last tenant
                if ($index == $tenantCount - 1) {
                    $alreadyAllocated = $depositPerTenant * $index;
                    $thisShare = $totalDeposit - $alreadyAllocated;
                } else {
                    $thisShare = $depositPerTenant;
                }
                
                TenantDeposit::create([
                    'contract_id' => $contractId,
                    'tenant_id' => $tenantId,
                    'amount_required' => round($thisShare, 2),
                    'amount_paid' => round($thisShare, 2),
                    'amount_deducted' => 0,
                    'amount_refunded' => 0,
                    'created_at' => $depositDate,
                    'updated_at' => $depositDate
                ]);
            }
        }
    }
    
    private function createInvoicesAndPayments()
    {
        $electricityRate = 12.50;
        $invoiceId = 1;
        $paymentId = 1;
        $tenantPaymentId = 1;
        $meterReadingId = 1;
        
        $months = ['2026-01', '2026-02', '2026-03', '2026-04', '2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'];
        $dueDays = [25, 20, 1, 25, 5, 25, 20, 22, 25, 28, 5, 12, 1, 25, 5, 10, 5, 12, 10, 15, 10, 8, 15, 1, 20, 22, 22, 18];
        $tenantAssignments = [
            1 => [1, 2], 2 => [3, 4], 3 => [5], 4 => [6, 7], 5 => [8], 6 => [9], 7 => [10],
            8 => [11, 12], 9 => [13, 14, 15], 10 => [16, 17], 11 => [18], 12 => [19], 13 => [20],
            14 => [21], 15 => [22], 16 => [23, 24], 17 => [25], 18 => [26, 27], 19 => [28],
            20 => [29], 21 => [30, 31], 22 => [32, 33], 23 => [34, 35], 24 => [36, 37], 25 => [38],
            26 => [39, 40], 27 => [41], 28 => [42, 43, 44]
        ];
        
        // First, determine payment patterns per contract (room)
        // Each contract gets a payment pattern that's chronologically consistent
        $contractPaymentPatterns = [];
        
        for ($roomId = 1; $roomId <= 28; $roomId++) {
            $pattern = [];
            $hasUnpaid = false; // Track if we've hit an unpaid month
            
            foreach ($months as $monthIndex => $month) {
                if ($hasUnpaid) {
                    // Once we have an unpaid month, all future months must be unpaid
                    $pattern[$monthIndex] = ['status' => 'pending', 'paid_percentage' => 0];
                } else {
                    // Before hitting unpaid, assign status with weighted randomness
                    $rand = rand(1, 100);
                    
                    if ($rand <= 75) {
                        // 75% fully paid
                        $pattern[$monthIndex] = ['status' => 'paid', 'paid_percentage' => 100];
                    } elseif ($rand <= 88) {
                        // 13% partial - this creates unpaid balance
                        $paidPercent = rand(30, 80);
                        $pattern[$monthIndex] = ['status' => 'partial', 'paid_percentage' => $paidPercent];
                        $hasUnpaid = true; // Future months will be unpaid
                    } elseif ($rand <= 94) {
                        // 6% pending - this creates unpaid balance
                        $pattern[$monthIndex] = ['status' => 'pending', 'paid_percentage' => 0];
                        $hasUnpaid = true;
                    } else {
                        // 6% overdue - this creates unpaid balance
                        $pattern[$monthIndex] = ['status' => 'overdue', 'paid_percentage' => 0];
                        $hasUnpaid = true;
                    }
                }
            }
            
            $contractPaymentPatterns[$roomId] = $pattern;
        }
        
        // Now generate invoices based on the patterns
        for ($roomId = 1; $roomId <= 28; $roomId++) {
            $previousReading = 1000 + ($roomId * 50);
            $contract = Contract::find($roomId);
            $tenantIds = $tenantAssignments[$roomId];
            $tenantCount = count($tenantIds);
            $monthlyRent = $contract->monthly_rent;
            $dueDay = $dueDays[$roomId - 1];
            $paymentPattern = $contractPaymentPatterns[$roomId];
            
            foreach ($months as $monthIndex => $month) {
                $billingMonth = Carbon::parse($month . '-01');
                $prevMonth = $billingMonth->copy()->subMonth();
                $prevMonthEnd = $prevMonth->copy()->endOfMonth();
                $currentMonthEnd = $billingMonth->copy()->endOfMonth();
                
                // Generate meter readings
                $kwh = rand(150, 330);
                $currentReading = $previousReading + $kwh;
                
                MeterReading::create([
                    'id' => $meterReadingId++,
                    'room_id' => $roomId,
                    'reading_month' => $billingMonth->format('Y-m'),
                    'previous_kwh' => $previousReading,
                    'current_kwh' => $currentReading,
                    'kwh_used' => $kwh,
                    'created_at' => $currentMonthEnd,
                    'updated_at' => $currentMonthEnd
                ]);
                
                $electricityCost = $kwh * $electricityRate;
                $totalAmount = $monthlyRent + $electricityCost;
                
                // Set due date - cap October invoices at Oct 15 (today is Oct 12)
                if ($month === '2026-10') {
                    // October invoices: due dates between Oct 5-15
                    $dueDay = rand(5, 15);
                }
                $dueDate = Carbon::parse($month . '-' . str_pad($dueDay, 2, '0', STR_PAD_LEFT));
                
                // Get status from pattern
                $patternData = $paymentPattern[$monthIndex];
                $status = $patternData['status'];
                $paidPercentage = $patternData['paid_percentage'];
                
                $amountPaid = round($totalAmount * ($paidPercentage / 100), 2);
                
                if ($paidPercentage > 0) {
                    $paymentDate = $dueDate->copy()->addDays(rand(-2, 5));
                } else {
                    $paymentDate = null;
                }
                
                $invoiceNumber = 'BILL-' . $billingMonth->format('Ym') . '-' . str_pad($invoiceId, 4, '0', STR_PAD_LEFT);
                
                // Created_at is exactly 3 days before due date at 12:00 AM
                $invoiceCreatedAt = $dueDate->copy()
                    ->subDays(3)
                    ->setTime(0, 0, 0);  // Exactly 12:00 AM (midnight)
                
                // Create invoice
                $invoice = Invoice::create([
                    'id' => $invoiceId,
                    'invoice_number' => $invoiceNumber,
                    'contract_id' => $contract->id,
                    'tenant_count' => $tenantCount,
                    'billing_month' => $billingMonth->format('Y-m'), // e.g., "2026-01"
                    'due_date' => $dueDate->format('Y-m-d'),
                    'rent_amount' => $monthlyRent,
                    'electricity_amount' => $electricityCost,
                    'total_amount' => $totalAmount,
                    'amount_paid' => $amountPaid,
                    'status' => $status,
                    'paid_at' => $paymentDate?->format('Y-m-d H:i:s'),
                    'created_at' => $invoiceCreatedAt->format('Y-m-d H:i:s'),
                    'updated_at' => $invoiceCreatedAt->format('Y-m-d H:i:s')
                ]);
                
                // Create tenant payments
                $shareAmount = $totalAmount / $tenantCount;
                $shareAmountPaid = $amountPaid / $tenantCount;
                
                // Carry over is the unpaid portion of this invoice
                $carryOver = $shareAmount - $shareAmountPaid;
                
                foreach ($tenantIds as $tenantId) {
                    TenantPayment::create([
                        'id' => $tenantPaymentId++,
                        'invoice_id' => $invoiceId,
                        'tenant_id' => $tenantId,
                        'share_amount' => round($shareAmount, 2),
                        'amount_paid' => round($shareAmountPaid, 2),
                        'status' => $status,
                        'carry_over_balance' => round($carryOver, 2),
                        'created_at' => $billingMonth->format('Y-m-d H:i:s'),
                        'updated_at' => now()
                    ]);
                }
                
                // Create payment record if paid
                if ($status != 'pending' && $amountPaid > 0) {
                    $paymentMethods = ['cash', 'gcash', 'bank_transfer'];
                    $method = $paymentMethods[array_rand($paymentMethods)];
                    $refPrefix = ($method == 'cash') ? 'CASH-' : 'REF-';
                    $refNumber = $refPrefix . strtoupper(substr(md5($invoiceId . $method), 0, 12));
                    
                    Payment::create([
                        'id' => $paymentId++,
                        'invoice_id' => $invoiceId,
                        'amount' => $amountPaid,
                        'method' => $method,
                        'received_at' => $paymentDate->format('Y-m-d'),
                        'reference' => $refNumber,
                        'notes' => 'Payment for ' . $billingMonth->format('F Y'),
                        'recorded_by_type' => 'staff',
                        'recorded_by_user' => 1, // Owner recorded the payment
                        'created_at' => $paymentDate->format('Y-m-d H:i:s'),
                        'updated_at' => $paymentDate->format('Y-m-d H:i:s')
                    ]);
                }
                
                $invoiceId++;
                $previousReading = $currentReading;
            }
        }
    }
}
