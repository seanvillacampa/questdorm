<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\DepositEntry;
use App\Models\Invoice;
use App\Models\LaundryOrder;
use App\Models\MeterReading;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComprehensiveDummyDataSeeder extends Seeder
{
    private array $firstNames = [
        'Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Carmen', 'Miguel', 'Sofia', 'Carlos', 'Isabel',
        'Luis', 'Rosa', 'Manuel', 'Teresa', 'Antonio', 'Elena', 'Francisco', 'Patricia', 'Rafael', 'Laura',
        'Ricardo', 'Monica', 'Fernando', 'Diana', 'Rodrigo', 'Gabriela', 'Santiago', 'Andrea', 'Diego', 'Cristina',
        'Javier', 'Beatriz', 'Pablo', 'Lucia', 'Andres', 'Natalia', 'Alejandro', 'Veronica', 'Eduardo', 'Silvia',
        'Daniel', 'Clara', 'Sergio', 'Marina', 'Oscar', 'Adriana', 'Victor', 'Julia', 'Ramon', 'Pilar'
    ];

    private array $lastNames = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Gonzales', 'Mendoza', 'Lopez', 'Rodriguez', 'Martinez',
        'Dela Cruz', 'Ramos', 'Flores', 'Rivera', 'Torres', 'Aquino', 'Villanueva', 'Castro', 'Santiago', 'Domingo',
        'Fernandez', 'Morales', 'Romero', 'Gutierrez', 'Sanchez', 'Perez', 'Alvarez', 'Ramirez', 'Navarro', 'Diaz',
        'Hernandez', 'Jimenez', 'Ruiz', 'Moreno', 'Munoz', 'Alonso', 'Ortiz', 'Molina', 'Delgado', 'Castro'
    ];

    private array $streetNames = [
        'Mabini', 'Rizal', 'Luna', 'Bonifacio', 'Aguinaldo', 'Quezon', 'Roxas', 'Osmena', 'Magsaysay', 'Laurel',
        'Del Pilar', 'Escolta', 'Taft', 'Legarda', 'Recto', 'Mendiola', 'Espana', 'Pedro Gil', 'UN Avenue', 'JP Rizal'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting comprehensive data seeding...');
        
        // Create 28 rooms with varied configurations
        $this->command->info('Creating 28 rooms...');
        $rooms = $this->createRooms();
        
        // Create 50+ tenants
        $this->command->info('Creating tenants...');
        $tenants = $this->createTenants(60);
        
        // Create contracts (Jan 2025 - Sept 2026)
        $this->command->info('Creating contracts...');
        $contracts = $this->createContracts($rooms, $tenants);
        
        // Create monthly invoices
        $this->command->info('Creating invoices...');
        $invoices = $this->createInvoices($contracts);
        
        // Create payments
        $this->command->info('Creating payments...');
        $this->createPayments($invoices);
        
        // Create meter readings
        $this->command->info('Creating meter readings...');
        $this->createMeterReadings($rooms);
        
        // Create laundry orders
        $this->command->info('Creating laundry orders...');
        $this->createLaundryOrders($tenants);
        
        $this->command->info('Comprehensive dummy data seeded successfully!');
    }

    private function createRooms(): array
    {
        $rooms = [];
        $floors = [1, 2, 3, 4];
        $roomsPerFloor = 7; // 28 total rooms / 4 floors
        
        $roomNumber = 101;
        
        foreach ($floors as $floor) {
            for ($i = 1; $i <= $roomsPerFloor; $i++) {
                // Randomize room configurations
                $isAC = rand(0, 100) < 40; // 40% AC rooms
                $capacity = rand(1, 4); // 1-4 beds per room
                
                // Base rent varies by floor and AC
                $baseRent = match($floor) {
                    1 => rand(3500, 4500),
                    2 => rand(4000, 5000),
                    3 => rand(4500, 5500),
                    4 => rand(5000, 6000),
                };
                
                // AC rooms cost more
                if ($isAC) {
                    $baseRent += rand(1000, 2000);
                }
                
                $room = Room::create([
                    'room_number' => (string) $roomNumber,
                    'floor' => $floor,
                    'capacity' => $capacity,
                    'monthly_rent' => $baseRent,
                    'is_airconditioned' => $isAC,
                    'description' => $this->generateRoomDescription($capacity, $isAC, $floor),
                ]);
                
                $rooms[] = $room;
                $roomNumber++;
                
                // Skip to next hundred for next floor
                if ($i === $roomsPerFloor) {
                    $roomNumber = ($floor + 2) * 100 + 1;
                }
            }
        }
        
        return $rooms;
    }

    private function generateRoomDescription(int $capacity, bool $isAC, int $floor): string
    {
        $descriptions = [];
        
        if ($capacity === 1) {
            $descriptions[] = 'Single occupancy room';
        } elseif ($capacity === 2) {
            $descriptions[] = 'Double occupancy room';
        } else {
            $descriptions[] = "{$capacity}-bed dormitory style";
        }
        
        if ($isAC) {
            $descriptions[] = 'with air conditioning';
        }
        
        $features = [
            'shared bathroom',
            'with balcony',
            'window view',
            'corner unit',
            'near elevator',
            'quiet area',
            'spacious layout',
        ];
        
        $descriptions[] = $features[array_rand($features)];
        
        return implode(', ', $descriptions);
    }

    private function createTenants(int $count): array
    {
        $tenants = [];
        $usedEmails = [];
        
        for ($i = 0; $i < $count; $i++) {
            do {
                $firstName = $this->firstNames[array_rand($this->firstNames)];
                $lastName = $this->lastNames[array_rand($this->lastNames)];
                $email = strtolower($firstName . '.' . str_replace(' ', '', $lastName) . rand(1, 999) . '@example.com');
            } while (in_array($email, $usedEmails));
            
            $usedEmails[] = $email;
            
            $phone = '09' . rand(100000000, 999999999);
            $street = $this->streetNames[array_rand($this->streetNames)];
            $address = rand(1, 500) . ' ' . $street . ' St., ' . ['Manila', 'Quezon City', 'Makati', 'Pasig', 'Mandaluyong'][array_rand(['Manila', 'Quezon City', 'Makati', 'Pasig', 'Mandaluyong'])];
            
            // Create user account for tenant
            $user = User::create([
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'tenant',
            ]);
            
            $tenant = Tenant::create([
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'emergency_contact' => '+63' . rand(9000000000, 9999999999),
                'id_number' => strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) . rand(100000, 999999),
                'user_id' => $user->id,
            ]);
            
            $tenants[] = $tenant;
        }
        
        return $tenants;
    }

    private function createContracts(array $rooms, array $tenants): array
    {
        $contracts = [];
        $usedTenantIds = [];
        $startDate = new \DateTime('2025-01-01');
        $endDate = new \DateTime('2026-09-30');
        
        // Shuffle rooms to randomize assignment
        shuffle($rooms);
        
        $tenantIndex = 0;
        
        foreach ($rooms as $room) {
            // Random: some rooms might be vacant or have fewer tenants than capacity
            $occupants = rand(0, $room->capacity);
            
            if ($occupants === 0) {
                continue; // Skip vacant rooms
            }
            
            $tenantsForRoom = [];
            
            for ($i = 0; $i < $occupants; $i++) {
                if ($tenantIndex >= count($tenants)) {
                    break;
                }
                
                $tenantsForRoom[] = $tenants[$tenantIndex]->id;
                $usedTenantIds[] = $tenants[$tenantIndex]->id;
                $tenantIndex++;
            }
            
            if (empty($tenantsForRoom)) {
                continue;
            }
            
            // Random start date between Jan 2025 and March 2025
            $contractStart = (clone $startDate)->modify('+' . rand(0, 60) . ' days');
            
            // Random duration: 6-12 months
            $duration = rand(6, 12);
            $contractEnd = (clone $contractStart)->modify('+' . $duration . ' months');
            
            // Ensure contract doesn't go beyond Sept 2026
            if ($contractEnd > $endDate) {
                $contractEnd = clone $endDate;
            }
            
            // Random deposit: 1-2 months rent
            $depositAmount = $room->monthly_rent * rand(1, 2);
            
            $contract = Contract::create([
                'room_id' => $room->id,
                'start_date' => $contractStart->format('Y-m-d'),
                'end_date' => $contractEnd->format('Y-m-d'),
                'monthly_rent' => $room->monthly_rent,
                'deposit_amount' => $depositAmount,
                'status' => $contractEnd < new \DateTime() ? 'ended' : 'active',
                'payment_due_day' => rand(1, 28), // Random due date
            ]);
            
            // Attach tenants to contract
            $contract->tenants()->attach($tenantsForRoom);
            
            // Create deposit entry
            DepositEntry::create([
                'contract_id' => $contract->id,
                'date' => $contractStart->format('Y-m-d'),
                'type' => 'deposit',
                'amount' => $depositAmount,
                'reason' => 'Initial security deposit',
            ]);
            
            $contracts[] = $contract;
        }
        
        return $contracts;
    }

    private function createInvoices(array $contracts): array
    {
        $invoices = [];
        $startDate = new \DateTime('2025-01-01');
        $endDate = new \DateTime('2026-09-30');
        
        foreach ($contracts as $contract) {
            $contractStart = new \DateTime($contract->start_date);
            $contractEnd = new \DateTime($contract->end_date);
            
            // Generate invoices for each month from contract start to contract end (or current date)
            $invoiceDate = (clone $contractStart)->modify('first day of this month');
            
            while ($invoiceDate <= $endDate && $invoiceDate <= $contractEnd) {
                // Due date is based on payment_due_day
                $dueDate = (clone $invoiceDate)->modify('+' . $contract->payment_due_day . ' days');
                
                // Random utilities
                $waterBill = rand(200, 800);
                $electricityBill = $contract->room->is_airconditioned ? rand(500, 1500) : rand(300, 800);
                
                $totalAmount = $contract->monthly_rent + $waterBill + $electricityBill;
                
                // Determine status based on due date and current date
                $now = new \DateTime();
                $isPastDue = $dueDate < $now;
                
                if ($isPastDue) {
                    // Random statuses for past invoices
                    $statusRand = rand(1, 100);
                    if ($statusRand <= 70) {
                        $status = 'paid';
                    } elseif ($statusRand <= 85) {
                        $status = 'partial';
                    } elseif ($statusRand <= 92) {
                        $status = 'overdue';
                    } else {
                        $status = 'late';
                    }
                } else {
                    // Future invoices
                    $status = rand(0, 100) < 20 ? 'paid' : 'pending';
                }
                
                $invoice = Invoice::create([
                    'contract_id' => $contract->id,
                    'month' => $invoiceDate->format('Y-m'),
                    'due_date' => $dueDate->format('Y-m-d'),
                    'rent_amount' => $contract->monthly_rent,
                    'water_bill' => $waterBill,
                    'electricity_bill' => $electricityBill,
                    'other_charges' => rand(0, 100) < 10 ? rand(100, 500) : 0, // 10% chance of other charges
                    'total_amount' => $totalAmount,
                    'status' => $status,
                ]);
                
                // Create tenant_payments entries for each tenant in the contract
                $tenantCount = $contract->tenants->count();
                $shareAmount = $totalAmount / $tenantCount;
                
                foreach ($contract->tenants as $tenant) {
                    $tpStatus = $status;
                    $amountPaid = 0;
                    
                    if ($status === 'paid') {
                        $amountPaid = $shareAmount;
                        $tpStatus = 'paid';
                    } elseif ($status === 'partial') {
                        // Random: some tenants paid, some didn't
                        if (rand(0, 1)) {
                            $amountPaid = $shareAmount;
                            $tpStatus = 'paid';
                        } else {
                            $tpStatus = 'pending';
                        }
                    }
                    
                    TenantPayment::create([
                        'invoice_id' => $invoice->id,
                        'tenant_id' => $tenant->id,
                        'share_amount' => $shareAmount,
                        'amount_paid' => $amountPaid,
                        'status' => $tpStatus,
                    ]);
                }
                
                $invoices[] = $invoice;
                
                // Move to next month
                $invoiceDate->modify('+1 month');
            }
        }
        
        return $invoices;
    }

    private function createPayments(array $invoices): void
    {
        foreach ($invoices as $invoice) {
            if ($invoice->status === 'paid') {
                // Create 1-3 payments for this invoice
                $numPayments = rand(1, 3);
                $remainingAmount = $invoice->total_amount;
                
                for ($i = 0; $i < $numPayments; $i++) {
                    if ($remainingAmount <= 0) {
                        break;
                    }
                    
                    $isLastPayment = ($i === $numPayments - 1);
                    $amount = $isLastPayment ? $remainingAmount : rand(1000, min($remainingAmount, $invoice->total_amount / 2));
                    
                    // Payment date is random between due date - 5 days and due date + 10 days
                    $dueDate = new \DateTime($invoice->due_date);
                    $paymentDate = (clone $dueDate)->modify(rand(-5, 10) . ' days');
                    
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'payment_date' => $paymentDate->format('Y-m-d'),
                        'payment_method' => ['cash', 'gcash', 'bank_transfer', 'paymongo'][array_rand(['cash', 'gcash', 'bank_transfer', 'paymongo'])],
                        'reference_number' => 'REF-' . strtoupper(substr(md5(uniqid()), 0, 10)),
                        'notes' => ['Full payment', 'Partial payment', 'Payment via GCash', 'Bank transfer received', null][array_rand(['Full payment', 'Partial payment', 'Payment via GCash', 'Bank transfer received', null])],
                    ]);
                    
                    $remainingAmount -= $amount;
                }
            } elseif ($invoice->status === 'partial') {
                // Create 1 partial payment
                $amount = rand($invoice->total_amount * 0.3, $invoice->total_amount * 0.7);
                $dueDate = new \DateTime($invoice->due_date);
                $paymentDate = (clone $dueDate)->modify(rand(-3, 5) . ' days');
                
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'payment_date' => $paymentDate->format('Y-m-d'),
                    'payment_method' => ['cash', 'gcash', 'bank_transfer'][array_rand(['cash', 'gcash', 'bank_transfer'])],
                    'reference_number' => 'REF-' . strtoupper(substr(md5(uniqid()), 0, 10)),
                    'notes' => 'Partial payment',
                ]);
            }
        }
    }

    private function createMeterReadings(array $rooms): void
    {
        $startDate = new \DateTime('2025-01-01');
        $endDate = new \DateTime('2026-09-30');
        
        foreach ($rooms as $room) {
            // Generate meter readings for each month
            $readingDate = clone $startDate;
            
            // Initial readings
            $waterReading = rand(1000, 5000);
            $electricityReading = rand(5000, 20000);
            
            while ($readingDate <= $endDate) {
                $month = $readingDate->format('Y-m');
                
                // Increment readings by realistic amounts
                $waterReading += rand(5, 30); // 5-30 cubic meters per month
                $electricityReading += $room->is_airconditioned ? rand(100, 400) : rand(50, 200); // kWh per month
                
                // Random: some readings might not be recorded yet
                $isRecorded = $readingDate < (new \DateTime())->modify('-1 month') ? (rand(0, 100) < 95) : (rand(0, 100) < 30);
                
                if ($isRecorded) {
                    MeterReading::create([
                        'room_id' => $room->id,
                        'month' => $month,
                        'water_reading' => $waterReading,
                        'electricity_reading' => $electricityReading,
                        'recorded_at' => $readingDate->format('Y-m-d'),
                    ]);
                }
                
                $readingDate->modify('+1 month');
            }
        }
    }

    private function createLaundryOrders(array $tenants): void
    {
        // Get laundry services
        $services = Service::all();
        
        if ($services->isEmpty()) {
            $this->command->warn('No laundry services found. Skipping laundry orders.');
            return;
        }
        
        $startDate = new \DateTime('2025-01-01');
        $endDate = new \DateTime('2026-09-30');
        
        // Create 200-400 random laundry orders
        $orderCount = rand(200, 400);
        
        for ($i = 0; $i < $orderCount; $i++) {
            $tenant = $tenants[array_rand($tenants)];
            
            // Random date between start and end
            $daysDiff = $startDate->diff($endDate)->days;
            $randomDays = rand(0, $daysDiff);
            $orderDate = (clone $startDate)->modify("+{$randomDays} days");
            
            // Random pickup date (1-3 days after order)
            $pickupDate = (clone $orderDate)->modify('+' . rand(1, 3) . ' days');
            
            // Random status
            $isPast = $pickupDate < new \DateTime();
            if ($isPast) {
                $statusRand = rand(1, 100);
                if ($statusRand <= 85) {
                    $status = 'completed';
                } elseif ($statusRand <= 95) {
                    $status = 'picked_up';
                } else {
                    $status = 'ready';
                }
            } else {
                $status = ['pending', 'processing', 'ready'][array_rand(['pending', 'processing', 'ready'])];
            }
            
            $order = LaundryOrder::create([
                'tenant_id' => $tenant->id,
                'order_date' => $orderDate->format('Y-m-d H:i:s'),
                'pickup_date' => $pickupDate->format('Y-m-d'),
                'status' => $status,
                'notes' => rand(0, 100) < 20 ? ['Express service', 'Delicate items', 'No fabric softener', 'Separate whites', null][array_rand(['Express service', 'Delicate items', 'No fabric softener', 'Separate whites', null])] : null,
            ]);
            
            // Add 1-4 services to the order
            $numServices = rand(1, 4);
            $usedServices = [];
            
            for ($j = 0; $j < $numServices; $j++) {
                $service = $services->random();
                
                // Avoid duplicate services in same order
                if (in_array($service->id, $usedServices)) {
                    continue;
                }
                
                $usedServices[] = $service->id;
                $quantity = rand(1, 5);
                $price = $service->currentPrice->price ?? rand(50, 300);
                
                OrderItem::create([
                    'laundry_order_id' => $order->id,
                    'service_id' => $service->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $quantity * $price,
                ]);
            }
        }
    }
}
