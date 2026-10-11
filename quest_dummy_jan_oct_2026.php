<?php
/**
 * Quest Building Dummy Data Generator
 * Period: January 2026 - October 11, 2026
 * Run: php quest_dummy_jan_oct_2026.php > quest_complete_2026.sql
 */

// Filipino names pool
$firstNames = ['Maria', 'Juan', 'Ana', 'Pedro', 'Rosa', 'Carlos', 'Elena', 'Miguel', 'Sofia', 'David',
    'Isabel', 'Ricardo', 'Carmen', 'Luis', 'Patricia', 'Fernando', 'Laura', 'Jorge', 'Monica', 'Alberto',
    'Diana', 'Rafael', 'Gloria', 'Antonio', 'Veronica', 'Eduardo', 'Beatriz', 'Sergio', 'Mariana', 'Raul',
    'Adriana', 'Pablo', 'Liliana', 'Hector', 'Valentina', 'Oscar', 'Natalia', 'Gabriel', 'Camila', 'Andres',
    'Daniela', 'Javier', 'Lucia', 'Rodrigo', 'Sandra', 'Manuel', 'Teresa', 'Ramon', 'Cristina', 'Jose'];

$lastNames = ['Santos', 'Dela Cruz', 'Reyes', 'Garcia', 'Mendoza', 'Ramos', 'Torres', 'Castro', 'Flores', 'Aquino',
    'Cruz', 'Lopez', 'Diaz', 'Hernandez', 'Gonzalez', 'Martinez', 'Rodriguez', 'Sanchez', 'Perez', 'Gomez',
    'Rivera', 'Morales', 'Jimenez', 'Ruiz', 'Ortiz', 'Vargas', 'Herrera', 'Silva', 'Medina', 'Moreno',
    'Guzman', 'Romero', 'Aguilar', 'Mendez', 'Castillo', 'Navarro', 'Vega', 'Campos', 'Rios', 'Soto',
    'Luna', 'Pena', 'Ramirez', 'Chavez', 'Benitez', 'Alvarez', 'Fernandez', 'Gutierrez', 'Valencia', 'Salazar'];

// Generate 45 unique names
$usedNames = [];
$tenants = [];
for ($i = 0; $i < 45; $i++) {
    do {
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $fullName = "$firstName $lastName";
    } while (in_array($fullName, $usedNames));
    
    $usedNames[] = $fullName;
    $tenants[] = [
        'id' => 101 + $i,
        'name' => $fullName,
        'email' => strtolower(str_replace(' ', '.', $fullName)) . '@email.com',
        'phone' => '0917' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
    ];
}

// Room configuration: 28 rooms (11 on floor 1)
$rooms = [
    // Floor 1: 11 rooms
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
    // Floor 2: 6 rooms
    ['id' => 12, 'number' => '201', 'floor' => 2, 'capacity' => 3, 'rate' => 4800],
    ['id' => 13, 'number' => '202', 'floor' => 2, 'capacity' => 2, 'rate' => 4200],
    ['id' => 14, 'number' => '203', 'floor' => 2, 'capacity' => 3, 'rate' => 4800],
    ['id' => 15, 'number' => '204', 'floor' => 2, 'capacity' => 2, 'rate' => 4200],
    ['id' => 16, 'number' => '205', 'floor' => 2, 'capacity' => 3, 'rate' => 4800],
    ['id' => 17, 'number' => '206', 'floor' => 2, 'capacity' => 2, 'rate' => 4200],
    // Floor 3: 6 rooms
    ['id' => 18, 'number' => '301', 'floor' => 3, 'capacity' => 3, 'rate' => 5000],
    ['id' => 19, 'number' => '302', 'floor' => 3, 'capacity' => 2, 'rate' => 4500],
    ['id' => 20, 'number' => '303', 'floor' => 3, 'capacity' => 3, 'rate' => 5000],
    ['id' => 21, 'number' => '304', 'floor' => 3, 'capacity' => 2, 'rate' => 4500],
    ['id' => 22, 'number' => '305', 'floor' => 3, 'capacity' => 3, 'rate' => 5000],
    ['id' => 23, 'number' => '306', 'floor' => 3, 'capacity' => 2, 'rate' => 4500],
    // Floor 4: 5 rooms
    ['id' => 24, 'number' => '401', 'floor' => 4, 'capacity' => 3, 'rate' => 5200],
    ['id' => 25, 'number' => '402', 'floor' => 4, 'capacity' => 2, 'rate' => 4800],
    ['id' => 26, 'number' => '403', 'floor' => 4, 'capacity' => 3, 'rate' => 5200],
    ['id' => 27, 'number' => '404', 'floor' => 4, 'capacity' => 2, 'rate' => 4800],
    ['id' => 28, 'number' => '405', 'floor' => 4, 'capacity' => 3, 'rate' => 5200],
];

// Contract configurations with random due dates
$dueDays = [1, 5, 8, 10, 12, 15, 18, 20, 22, 25, 28];
$contracts = [];
$tenantIdx = 0;

foreach ($rooms as $room) {
    $tenantCount = rand(1, min($room['capacity'], 45 - $tenantIdx));
    $assignedTenants = [];
    
    for ($t = 0; $t < $tenantCount; $t++) {
        if ($tenantIdx < 45) {
            $assignedTenants[] = $tenantIdx + 1; // tenant_id (1-45)
            $tenantIdx++;
        }
    }
    
    if (count($assignedTenants) > 0) {
        $contracts[] = [
            'id' => $room['id'],
            'room_id' => $room['id'],
            'room_number' => $room['number'],
            'start_date' => '2025-12-15',
            'end_date' => '2027-12-31',
            'monthly_rent' => $room['rate'],
            'due_day' => $dueDays[array_rand($dueDays)],
            'tenant_count' => count($assignedTenants),
            'tenant_ids' => $assignedTenants,
            'deposit_required' => $room['rate'] * 2,
        ];
    }
}

$electricityRate = 12.50;
$currentDate = new DateTime('2026-10-11');

// Start output
echo "-- ============================================================================\n";
echo "-- Quest Building Dummy Data - January 2026 to October 11, 2026\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- 28 Rooms (11 on Floor 1), Random Due Dates, All Paid Until July 2026\n";
echo "-- ============================================================================\n\n";

echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

echo "TRUNCATE TABLE `tenant_payments`;\n";
echo "TRUNCATE TABLE `payments`;\n";
echo "TRUNCATE TABLE `deposit_entries`;\n";
echo "TRUNCATE TABLE `tenant_deposits`;\n";
echo "TRUNCATE TABLE `invoices`;\n";
echo "TRUNCATE TABLE `meter_readings`;\n";
echo "TRUNCATE TABLE `contract_tenants`;\n";
echo "TRUNCATE TABLE `contracts`;\n";
echo "TRUNCATE TABLE `tenants`;\n";
echo "TRUNCATE TABLE `model_has_roles`;\n";
echo "TRUNCATE TABLE `users`;\n";
echo "TRUNCATE TABLE `settings`;\n";
echo "TRUNCATE TABLE `rooms`;\n\n";

echo "SET FOREIGN_KEY_CHECKS = 1;\n\n";

// Settings
echo "-- SETTINGS\n";
echo "INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`) VALUES\n";
echo "('company_name', 'Quest Building', NOW(), NOW()),\n";
echo "('company_address', '123 Main Street, Cebu City, Philippines', NOW(), NOW()),\n";
echo "('company_phone', '+63 123 456 7890', NOW(), NOW()),\n";
echo "('company_email', 'admin@questbuilding.com', NOW(), NOW()),\n";
echo "('deposit_months', '2', NOW(), NOW()),\n";
echo "('electricity_rate', '12.50', NOW(), NOW()),\n";
echo "('late_payment_penalty_rate', '3', NOW(), NOW()),\n";
echo "('grace_period_days', '5', NOW(), NOW());\n\n";

// Rooms (WITH deposit_required column)
echo "-- ROOMS (28 total: 11 on Floor 1)\n";
echo "INSERT INTO `rooms` (`id`, `room_number`, `floor`, `capacity`, `monthly_rate`, `deposit_required`, `meter_number`, `status`, `created_at`, `updated_at`) VALUES\n";
foreach ($rooms as $idx => $room) {
    $comma = ($idx < count($rooms) - 1) ? ',' : ';';
    $depositRequired = $room['rate'] * 2;
    echo "({$room['id']}, '{$room['number']}', {$room['floor']}, {$room['capacity']}, {$room['rate']}.00, $depositRequired.00, 'MTR-{$room['number']}', 'occupied', '2025-12-01 00:00:00', '2025-12-15 00:00:00')$comma\n";
}
echo "\n";

// Users
echo "-- USERS (45 tenants with random names)\n";
echo "INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `phone`, `phone_country_code`, `is_active`, `created_at`, `updated_at`) VALUES\n";
foreach ($tenants as $idx => $tenant) {
    $comma = ($idx < count($tenants) - 1) ? ',' : ';';
    $date = date('2025-12-' . str_pad(10 + $idx, 2, '0', STR_PAD_LEFT) . ' 10:00:00');
    echo "({$tenant['id']}, '{$tenant['name']}', '{$tenant['email']}', '$date', '\$2y\$12\$LQv3c1yLk4Q5M6x8K9h0xez3.J4N5K6L7M8N9O0P1Q2R3S4T5U6V7W', '{$tenant['phone']}', '+63', 1, '$date', '$date')$comma\n";
}
echo "\n";

// Tenants
echo "-- TENANTS\n";
echo "INSERT INTO `tenants` (`id`, `user_id`, `created_at`, `updated_at`) VALUES\n";
foreach ($tenants as $idx => $tenant) {
    $comma = ($idx < count($tenants) - 1) ? ',' : ';';
    $tenantId = $idx + 1;
    $date = date('2025-12-' . str_pad(10 + $idx, 2, '0', STR_PAD_LEFT) . ' 10:00:00');
    echo "($tenantId, {$tenant['id']}, '$date', '$date')$comma\n";
}
echo "\n";

// Role assignments (assume tenant role_id = 3)
echo "-- ROLE ASSIGNMENTS (Spatie)\n";
echo "INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES\n";
foreach ($tenants as $idx => $tenant) {
    $comma = ($idx < count($tenants) - 1) ? ',' : ';';
    echo "(3, 'App\\\\Models\\\\User', {$tenant['id']})$comma\n";
}
echo "\n";

// Contracts (NO deposit_required or deposit_collected columns!)
echo "-- CONTRACTS (28 contracts with random due dates)\n";
echo "INSERT INTO `contracts` (`id`, `room_id`, `start_date`, `end_date`, `monthly_rent`, `due_day`, `status`, `contract_file_path`, `created_by`, `created_at`, `updated_at`) VALUES\n";
foreach ($contracts as $idx => $contract) {
    $comma = ($idx < count($contracts) - 1) ? ',' : ';';
    echo "({$contract['id']}, {$contract['room_id']}, '{$contract['start_date']}', '{$contract['end_date']}', {$contract['monthly_rent']}.00, {$contract['due_day']}, 'active', NULL, NULL, '{$contract['start_date']} 00:00:00', '{$contract['start_date']} 00:00:00')$comma\n";
}
echo "\n";

// Contract tenants
echo "-- CONTRACT_TENANTS\n";
echo "INSERT INTO `contract_tenants` (`contract_id`, `tenant_id`, `joined_on`, `left_on`, `created_at`, `updated_at`) VALUES\n";
$pivotCount = 0;
$totalPivots = array_sum(array_map(fn($c) => count($c['tenant_ids']), $contracts));
foreach ($contracts as $contract) {
    foreach ($contract['tenant_ids'] as $tenantId) {
        $pivotCount++;
        $comma = ($pivotCount < $totalPivots) ? ',' : ';';
        echo "({$contract['id']}, $tenantId, '{$contract['start_date']}', NULL, '{$contract['start_date']} 00:00:00', '{$contract['start_date']} 00:00:00')$comma\n";
    }
}
echo "\n";

// Tenant deposits
echo "-- TENANT_DEPOSITS (all fully paid)\n";
echo "INSERT INTO `tenant_deposits` (`id`, `contract_id`, `tenant_id`, `amount_required`, `amount_paid`, `amount_deducted`, `amount_refunded`, `created_at`, `updated_at`) VALUES\n";
$depositId = 1;
$depositCount = 0;
foreach ($contracts as $contract) {
    $shareAmount = round($contract['deposit_required'] / $contract['tenant_count'], 2);
    foreach ($contract['tenant_ids'] as $idx => $tenantId) {
        if ($idx === count($contract['tenant_ids']) - 1) {
            $shareAmount = $contract['deposit_required'] - ($shareAmount * $idx);
        }
        $depositCount++;
        $comma = ($depositCount < $totalPivots) ? ',' : ';';
        echo "($depositId, {$contract['id']}, $tenantId, $shareAmount, $shareAmount, 0.00, 0.00, '{$contract['start_date']} 12:00:00', '{$contract['start_date']} 12:00:00')$comma\n";
        $depositId++;
    }
}
echo "\n";

// Generate invoices and payments
$invoiceId = 1;
$paymentId = 1;
$tenantPaymentId = 1;
$meterReadingId = 1;

echo "-- INVOICES, PAYMENTS, METER READINGS (Jan 2026 - Oct 2026)\n\n";

foreach ($contracts as $contract) {
    echo "-- Contract {$contract['id']} (Room {$contract['room_number']}, Due Day: {$contract['due_day']})\n";
    
    // Track outstanding balance per tenant
    $tenantBalances = array_fill_keys($contract['tenant_ids'], 0);
    
    // Generate invoices from January 2026 to October 2026
    $startMonth = new DateTime('2026-01-01');
    $endMonth = new DateTime('2026-10-01');
    
    $currentMonth = clone $startMonth;
    $prevReading = 1000 + (rand(0, 500));
    
    while ($currentMonth <= $endMonth) {
        $year = $currentMonth->format('Y');
        $month = $currentMonth->format('m');
        $monthName = $currentMonth->format('F Y');
        
        // Stop if we're in October and past the 11th
        if ($year == '2026' && $month == '10' && $currentMonth > $currentDate) {
            break;
        }
        
        $invoiceNumber = "BILL-$year$month-" . str_pad($invoiceId, 4, '0', STR_PAD_LEFT);
        
        // Due date
        $dueDay = min($contract['due_day'], cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year));
        $dueDate = "$year-$month-" . str_pad($dueDay, 2, '0', STR_PAD_LEFT);
        
        // Meter readings
        $currentReading = $prevReading + rand(150, 350);
        $kwh = $currentReading - $prevReading;
        $electricityAmount = $kwh * $electricityRate;
        
        $readingDate = (clone $currentMonth)->modify('last day of this month')->format('Y-m-d');
        $prevReadingDate = (clone $currentMonth)->modify('first day of this month')->modify('-1 day')->format('Y-m-d');
        
        echo "INSERT INTO `meter_readings` (`id`, `room_id`, `reading_date`, `reading_value`, `previous_reading`, `kwh_used`, `created_at`, `updated_at`) VALUES\n";
        echo "($meterReadingId, {$contract['room_id']}, '$prevReadingDate', $prevReading, " . ($prevReading - 250) . ", 250, '$prevReadingDate 00:00:00', '$prevReadingDate 00:00:00'),\n";
        $meterReadingId++;
        echo "($meterReadingId, {$contract['room_id']}, '$readingDate', $currentReading, $prevReading, $kwh, '$readingDate 00:00:00', '$readingDate 00:00:00');\n";
        $meterReadingId++;
        
        $totalAmount = $contract['monthly_rent'] + $electricityAmount;
        $shareAmount = round($totalAmount / $contract['tenant_count'], 2);
        
        // Determine status based on period
        $isPaidPeriod = ($currentMonth < new DateTime('2026-08-01'));
        
        if ($isPaidPeriod) {
            // Jan-Jul 2026: All PAID
            $status = 'paid';
            $amountPaid = $totalAmount;
            $paidDate = date('Y-m-d H:i:s', strtotime($dueDate) + (rand(1, 3) * 86400));
        } else {
            // Aug-Oct 2026: Mixed statuses with 20k limit
            $maxOutstanding = 20000 / $contract['tenant_count'];
            
            // Check if any tenant would exceed 20k
            $wouldExceed = false;
            foreach ($contract['tenant_ids'] as $tenantId) {
                if (($tenantBalances[$tenantId] + $shareAmount) > $maxOutstanding) {
                    $wouldExceed = true;
                    break;
                }
            }
            
            if ($wouldExceed) {
                // Force paid to stay under limit
                $status = 'paid';
                $amountPaid = $totalAmount;
                $paidDate = date('Y-m-d H:i:s', strtotime($dueDate) + (rand(1, 5) * 86400));
            } else {
                // Random status
                $rand = rand(1, 100);
                if ($rand <= 35) {
                    $status = 'paid';
                    $amountPaid = $totalAmount;
                    $paidDate = date('Y-m-d H:i:s', strtotime($dueDate) + (rand(1, 5) * 86400));
                } elseif ($rand <= 60) {
                    $status = 'partial';
                    $amountPaid = round($totalAmount * (rand(40, 75) / 100), 2);
                    $paidDate = date('Y-m-d H:i:s', strtotime($dueDate) + (rand(1, 3) * 86400));
                    
                    // Update balances
                    foreach ($contract['tenant_ids'] as $tenantId) {
                        $tenantBalances[$tenantId] += ($shareAmount - ($amountPaid / $contract['tenant_count']));
                    }
                } elseif ($rand <= 80) {
                    $status = 'pending';
                    $amountPaid = 0;
                    $paidDate = null;
                    
                    // Update balances
                    foreach ($contract['tenant_ids'] as $tenantId) {
                        $tenantBalances[$tenantId] += $shareAmount;
                    }
                } else {
                    $status = 'overdue';
                    $amountPaid = 0;
                    $paidDate = null;
                    
                    // Update balances
                    foreach ($contract['tenant_ids'] as $tenantId) {
                        $tenantBalances[$tenantId] += $shareAmount;
                    }
                }
            }
        }
        
        // Invoice
        echo "INSERT INTO `invoices` (`id`, `invoice_number`, `contract_id`, `tenant_count`, `billing_month`, `due_date`, `rent_amount`, `electricity_amount`, `total_amount`, `amount_paid`, `status`, `paid_at`, `created_by`, `created_at`, `updated_at`) VALUES\n";
        $paidAtValue = $paidDate ? "'$paidDate'" : 'NULL';
        echo "($invoiceId, '$invoiceNumber', {$contract['id']}, {$contract['tenant_count']}, '$year-$month-01', '$dueDate', {$contract['monthly_rent']}.00, " . round($electricityAmount, 2) . ", " . round($totalAmount, 2) . ", $amountPaid, '$status', $paidAtValue, NULL, '$year-$month-01 00:00:00', '" . date('Y-m-d H:i:s') . "');\n";
        
        // Tenant payments
        echo "INSERT INTO `tenant_payments` (`id`, `invoice_id`, `tenant_id`, `share_amount`, `amount_paid`, `status`, `carry_over_balance`, `created_at`, `updated_at`) VALUES\n";
        foreach ($contract['tenant_ids'] as $idx => $tenantId) {
            $tenantShare = round($shareAmount, 2);
            if ($idx === count($contract['tenant_ids']) - 1) {
                $tenantShare = round($totalAmount - ($shareAmount * $idx), 2);
            }
            
            if ($status === 'paid') {
                $tenantPaid = $tenantShare;
                $tenantStatus = 'paid';
                $carryOver = 0;
            } elseif ($status === 'partial') {
                $tenantPaid = round($amountPaid / $contract['tenant_count'], 2);
                if ($idx === count($contract['tenant_ids']) - 1) {
                    $tenantPaid = round($amountPaid - (($amountPaid / $contract['tenant_count']) * $idx), 2);
                }
                $tenantStatus = 'partial';
                $carryOver = round($tenantShare - $tenantPaid, 2);
            } else {
                $tenantPaid = 0;
                $tenantStatus = $status;
                $carryOver = 0;
            }
            
            $comma = ($idx < count($contract['tenant_ids']) - 1) ? ',' : ';';
            echo "($tenantPaymentId, $invoiceId, $tenantId, $tenantShare, $tenantPaid, '$tenantStatus', $carryOver, '$year-$month-01 00:00:00', '" . date('Y-m-d H:i:s') . "')$comma\n";
            $tenantPaymentId++;
        }
        
        // Payment record
        if ($amountPaid > 0 && $paidDate) {
            $paymentMethod = rand(1, 100) <= 70 ? 'cash' : (rand(1, 2) == 1 ? 'bank_transfer' : 'gcash');
            $refNumber = $paymentMethod === 'cash' ? 'CASH-' . strtoupper(substr(md5($invoiceId), 0, 8)) : 'REF-' . strtoupper(substr(md5($invoiceId . 'pay'), 0, 12));
            echo "INSERT INTO `payments` (`id`, `invoice_id`, `amount`, `payment_method`, `payment_date`, `reference_number`, `created_at`, `updated_at`) VALUES\n";
            echo "($paymentId, $invoiceId, $amountPaid, '$paymentMethod', '$paidDate', '$refNumber', '$paidDate', '$paidDate');\n";
            $paymentId++;
        }
        
        echo "\n";
        $prevReading = $currentReading;
        $invoiceId++;
        $currentMonth->modify('+1 month');
    }
    
    echo "\n";
}

echo "-- ============================================================================\n";
echo "-- DATA GENERATION COMPLETE\n";
echo "-- Total Invoices: " . ($invoiceId - 1) . "\n";
echo "-- Total Payments: " . ($paymentId - 1) . "\n";
echo "-- Total Tenant Payments: " . ($tenantPaymentId - 1) . "\n";
echo "-- Total Meter Readings: " . ($meterReadingId - 1) . "\n";
echo "-- Period: January 2026 - October 11, 2026\n";
echo "-- ============================================================================\n";
