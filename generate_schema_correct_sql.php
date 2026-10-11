<?php
/**
 * Generate schema-correct SQL for Quest Building
 * Based on actual database migrations
 */

$output = "-- ============================================================================
-- Quest Building Rental Management System - Schema-Correct Dummy Data
-- Period: January 1, 2025 - October 11, 2026
-- Generated: " . date('Y-m-d H:i:s') . "
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing data
TRUNCATE TABLE `tenant_payments`;
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `deposit_entries`;
TRUNCATE TABLE `tenant_deposits`;
TRUNCATE TABLE `invoices`;
TRUNCATE TABLE `meter_readings`;
TRUNCATE TABLE `contract_tenants`;
TRUNCATE TABLE `contracts`;
TRUNCATE TABLE `tenants`;
TRUNCATE TABLE `model_has_roles`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `settings`;
TRUNCATE TABLE `rooms`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SETTINGS
-- ============================================================================
INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`) VALUES
('company_name', 'Quest Building', NOW(), NOW()),
('company_address', '123 Main Street, Cebu City, Philippines', NOW(), NOW()),
('company_phone', '+63 123 456 7890', NOW(), NOW()),
('company_email', 'admin@questbuilding.com', NOW(), NOW()),
('deposit_months', '2', NOW(), NOW()),
('electricity_rate', '12.50', NOW(), NOW()),
('late_payment_penalty_rate', '3', NOW(), NOW()),
('grace_period_days', '5', NOW(), NOW());

-- ============================================================================
-- ROOMS (28 rooms: 11 on Floor 1, 6 on Floor 2, 6 on Floor 3, 5 on Floor 4)
-- ============================================================================
INSERT INTO `rooms` (`id`, `room_number`, `floor`, `capacity`, `monthly_rate`, `meter_number`, `status`, `created_at`, `updated_at`) VALUES
-- Floor 1 (11 rooms)
(1, '101', 1, 3, 4500.00, 'MTR-101', 'occupied', '2024-12-01 00:00:00', '2025-01-15 00:00:00'),
(2, '102', 1, 2, 4000.00, 'MTR-102', 'occupied', '2024-12-01 00:00:00', '2025-01-10 00:00:00'),
(3, '103', 1, 3, 4500.00, 'MTR-103', 'occupied', '2024-12-01 00:00:00', '2025-01-12 00:00:00'),
(4, '104', 1, 2, 4000.00, 'MTR-104', 'occupied', '2024-12-01 00:00:00', '2025-02-01 00:00:00'),
(5, '105', 1, 3, 4500.00, 'MTR-105', 'occupied', '2024-12-01 00:00:00', '2025-01-20 00:00:00'),
(6, '106', 1, 2, 4000.00, 'MTR-106', 'occupied', '2024-12-01 00:00:00', '2025-02-15 00:00:00'),
(7, '107', 1, 3, 4500.00, 'MTR-107', 'occupied', '2024-12-01 00:00:00', '2025-03-01 00:00:00'),
(8, '108', 1, 2, 4000.00, 'MTR-108', 'occupied', '2024-12-01 00:00:00', '2025-01-25 00:00:00'),
(9, '109', 1, 3, 4500.00, 'MTR-109', 'occupied', '2024-12-01 00:00:00', '2025-02-10 00:00:00'),
(10, '110', 1, 2, 4000.00, 'MTR-110', 'occupied', '2024-12-01 00:00:00', '2025-03-15 00:00:00'),
(11, '111', 1, 2, 4000.00, 'MTR-111', 'occupied', '2024-12-01 00:00:00', '2025-01-18 00:00:00'),
-- Floor 2 (6 rooms)
(12, '201', 2, 3, 4800.00, 'MTR-201', 'occupied', '2024-12-01 00:00:00', '2025-01-08 00:00:00'),
(13, '202', 2, 2, 4200.00, 'MTR-202', 'occupied', '2024-12-01 00:00:00', '2025-02-20 00:00:00'),
(14, '203', 2, 3, 4800.00, 'MTR-203', 'occupied', '2024-12-01 00:00:00', '2025-01-05 00:00:00'),
(15, '204', 2, 2, 4200.00, 'MTR-204', 'occupied', '2024-12-01 00:00:00', '2025-03-10 00:00:00'),
(16, '205', 2, 3, 4800.00, 'MTR-205', 'occupied', '2024-12-01 00:00:00', '2025-02-05 00:00:00'),
(17, '206', 2, 2, 4200.00, 'MTR-206', 'occupied', '2024-12-01 00:00:00', '2025-01-22 00:00:00'),
-- Floor 3 (6 rooms)
(18, '301', 3, 3, 5000.00, 'MTR-301', 'occupied', '2024-12-01 00:00:00', '2025-01-14 00:00:00'),
(19, '302', 3, 2, 4500.00, 'MTR-302', 'occupied', '2024-12-01 00:00:00', '2025-02-25 00:00:00'),
(20, '303', 3, 3, 5000.00, 'MTR-303', 'occupied', '2024-12-01 00:00:00', '2025-01-30 00:00:00'),
(21, '304', 3, 2, 4500.00, 'MTR-304', 'occupied', '2024-12-01 00:00:00', '2025-03-05 00:00:00'),
(22, '305', 3, 3, 5000.00, 'MTR-305', 'occupied', '2024-12-01 00:00:00', '2025-02-12 00:00:00'),
(23, '306', 3, 2, 4500.00, 'MTR-306', 'occupied', '2024-12-01 00:00:00', '2025-01-28 00:00:00'),
-- Floor 4 (5 rooms)
(24, '401', 4, 3, 5200.00, 'MTR-401', 'occupied', '2024-12-01 00:00:00', '2025-01-16 00:00:00'),
(25, '402', 4, 2, 4800.00, 'MTR-402', 'occupied', '2024-12-01 00:00:00', '2025-03-20 00:00:00'),
(26, '403', 4, 3, 5200.00, 'MTR-403', 'occupied', '2024-12-01 00:00:00', '2025-02-08 00:00:00'),
(27, '404', 4, 2, 4800.00, 'MTR-404', 'occupied', '2024-12-01 00:00:00', '2025-01-11 00:00:00'),
(28, '405', 4, 3, 5200.00, 'MTR-405', 'occupied', '2024-12-01 00:00:00', '2025-02-18 00:00:00');

-- ============================================================================
-- USERS & TENANTS (45 tenants)
-- ============================================================================
";

// Generate 45 tenant users
$tenantNames = [
    'Maria Santos', 'Juan Dela Cruz', 'Ana Reyes', 'Pedro Garcia', 'Rosa Mendoza',
    'Carlos Ramos', 'Elena Torres', 'Miguel Castro', 'Sofia Flores', 'David Aquino',
    'Isabel Cruz', 'Ricardo Lopez', 'Carmen Diaz', 'Luis Hernandez', 'Patricia Gonzalez',
    'Fernando Martinez', 'Laura Rodriguez', 'Jorge Sanchez', 'Monica Perez', 'Alberto Gomez',
    'Diana Rivera', 'Rafael Morales', 'Gloria Jimenez', 'Antonio Ruiz', 'Veronica Ortiz',
    'Eduardo Vargas', 'Beatriz Herrera', 'Sergio Silva', 'Mariana Medina', 'Raul Moreno',
    'Adriana Guzman', 'Pablo Romero', 'Liliana Aguilar', 'Hector Mendez', 'Valentina Castillo',
    'Oscar Navarro', 'Natalia Vega', 'Gabriel Campos', 'Camila Rios', 'Andres Soto',
    'Daniela Luna', 'Javier Pena', 'Lucia Ramirez', 'Rodrigo Chavez', 'Sandra Benitez'
];

$output .= "INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `phone`, `phone_country_code`, `is_active`, `created_at`, `updated_at`) VALUES\n";
foreach ($tenantNames as $idx => $name) {
    $userId = 101 + $idx;
    $email = strtolower(str_replace(' ', '.', $name)) . '@email.com';
    $phone = '0917123' . str_pad(4501 + $idx, 4, '0', STR_PAD_LEFT);
    $date = date('2024-12-' . str_pad(15 + $idx, 2, '0', STR_PAD_LEFT) . ' 10:00:00');
    $comma = ($idx < count($tenantNames) - 1) ? ',' : ';';
    
    $output .= "($userId, '$name', '$email', '$date', '\$2y\$12\$LQv3c1yLk4Q5M6x8K9h0xez3.J4N5K6L7M8N9O0P1Q2R3S4T5U6V7W', '$phone', '+63', 1, '$date', '$date')$comma\n";
}

// Tenants table
$output .= "\nINSERT INTO `tenants` (`id`, `user_id`, `created_at`, `updated_at`) VALUES\n";
for ($i = 0; $i < 45; $i++) {
    $tenantId = $i + 1;
    $userId = $i + 101;
    $date = date('2024-12-' . str_pad(15 + $i, 2, '0', STR_PAD_LEFT) . ' 10:00:00');
    $comma = ($i < 44) ? ',' : ';';
    $output .= "($tenantId, $userId, '$date', '$date')$comma\n";
}

// Contracts configuration [room_id => [start_date, end_date, due_day, monthly_rent, tenant_ids]]
$contracts = [
    1 => ['2025-01-15', '2026-12-31', 15, 4500, [1,2,3]],
    2 => ['2025-01-10', '2026-12-31', 10, 4000, [4,5]],
    3 => ['2025-01-12', '2026-12-31', 12, 4500, [6,7,8]],
    4 => ['2025-02-01', '2026-12-31', 1, 4000, [9,10]],
    5 => ['2025-01-20', '2026-12-31', 20, 4500, [11,12]],
    6 => ['2025-02-15', '2026-12-31', 15, 4000, [13]],
    7 => ['2025-03-01', '2026-12-31', 1, 4500, [14,15]],
    8 => ['2025-01-25', '2026-12-31', 25, 4000, [16,17]],
    9 => ['2025-02-10', '2026-12-31', 10, 4500, [18]],
    10 => ['2025-03-15', '2026-12-31', 15, 4000, [19,20]],
    11 => ['2025-01-18', '2026-12-31', 18, 4000, [21]],
    12 => ['2025-01-08', '2026-12-31', 8, 4800, [22,23]],
    13 => ['2025-02-20', '2026-12-31', 20, 4200, [24,25]],
    14 => ['2025-01-05', '2026-12-31', 5, 4800, [26,27,28]],
    15 => ['2025-03-10', '2026-12-31', 10, 4200, [29]],
    16 => ['2025-02-05', '2026-12-31', 5, 4800, [30,31]],
    17 => ['2025-01-22', '2026-12-31', 22, 4200, [32,33]],
    18 => ['2025-01-14', '2026-12-31', 14, 5000, [34,35]],
    19 => ['2025-02-25', '2026-12-31', 25, 4500, [36]],
    20 => ['2025-01-30', '2026-12-31', 30, 5000, [37,38,39]],
    21 => ['2025-03-05', '2026-12-31', 5, 4500, [40,41]],
    22 => ['2025-02-12', '2026-12-31', 12, 5000, [42,43]],
    23 => ['2025-01-28', '2026-12-31', 28, 4500, [44,45]],
    24 => ['2025-01-16', '2026-12-31', 16, 5200, [1,2]],
    25 => ['2025-03-20', '2026-12-31', 20, 4800, [3]],
    26 => ['2025-02-08', '2026-12-31', 8, 5200, [4,5,6]],
    27 => ['2025-01-11', '2026-12-31', 11, 4800, [7,8]],
    28 => ['2025-02-18', '2026-12-31', 18, 5200, [9,10]],
];

// Contracts
$output .= "\n-- ============================================================================\n";
$output .= "-- CONTRACTS (28 contracts for 28 rooms)\n";
$output .= "-- Schema: id, room_id, start_date, end_date, monthly_rent, due_day,\n";
$output .= "--         deposit_required, deposit_collected, status, created_by\n";
$output .= "-- ============================================================================\n";
$output .= "INSERT INTO `contracts` (`id`, `room_id`, `start_date`, `end_date`, `monthly_rent`, `due_day`, `deposit_required`, `deposit_collected`, `status`, `contract_file_path`, `created_by`, `created_at`, `updated_at`) VALUES\n";

$contractIdx = 0;
foreach ($contracts as $roomId => $data) {
    list($startDate, $endDate, $dueDay, $monthlyRent, $tenantIds) = $data;
    $depositRequired = $monthlyRent * 2; // 2 months deposit
    $depositCollected = $depositRequired; // All fully paid
    $contractId = $roomId;
    $comma = (++$contractIdx < count($contracts)) ? ',' : ';';
    
    $output .= "($contractId, $roomId, '$startDate', '$endDate', $monthlyRent, $dueDay, $depositRequired, $depositCollected, 'active', NULL, NULL, '$startDate 00:00:00', '$startDate 00:00:00')$comma\n";
}

// Contract tenants
$output .= "\n-- ============================================================================\n";
$output .= "-- CONTRACT_TENANTS (Pivot table)\n";
$output .= "-- ============================================================================\n";
$output .= "INSERT INTO `contract_tenants` (`contract_id`, `tenant_id`, `joined_on`, `left_on`, `created_at`, `updated_at`) VALUES\n";

$pivotIdx = 0;
$totalPivots = array_sum(array_map(fn($c) => count($c[4]), $contracts));
foreach ($contracts as $roomId => $data) {
    list($startDate, $endDate, $dueDay, $monthlyRent, $tenantIds) = $data;
    $contractId = $roomId;
    foreach ($tenantIds as $tenantId) {
        $comma = (++$pivotIdx < $totalPivots) ? ',' : ';';
        $output .= "($contractId, $tenantId, '$startDate', NULL, '$startDate 00:00:00', '$startDate 00:00:00')$comma\n";
    }
}

// Tenant deposits
$output .= "\n-- ============================================================================\n";
$output .= "-- TENANT_DEPOSITS\n";
$output .= "-- ============================================================================\n";
$output .= "INSERT INTO `tenant_deposits` (`id`, `contract_id`, `tenant_id`, `amount_required`, `amount_paid`, `amount_deducted`, `amount_refunded`, `created_at`, `updated_at`) VALUES\n";

$depositIdx = 0;
$depositId = 1;
foreach ($contracts as $roomId => $data) {
    list($startDate, $endDate, $dueDay, $monthlyRent, $tenantIds) = $data;
    $contractId = $roomId;
    $depositRequired = $monthlyRent * 2;
    $shareAmount = round($depositRequired / count($tenantIds), 2);
    
    foreach ($tenantIds as $idx => $tenantId) {
        // Last tenant gets remainder to handle rounding
        if ($idx === count($tenantIds) - 1) {
            $shareAmount = $depositRequired - ($shareAmount * $idx);
        }
        $comma = (++$depositIdx < $totalPivots) ? ',' : ';';
        $output .= "($depositId, $contractId, $tenantId, $shareAmount, $shareAmount, 0.00, 0.00, '$startDate 12:00:00', '$startDate 12:00:00')$comma\n";
        $depositId++;
    }
}

file_put_contents('quest_building_base_data.sql', $output);
echo "✓ Base data generated: quest_building_base_data.sql\n";
echo "✓ Run: php generate_dummy_data.php > quest_building_invoices.sql\n";
echo "✓ Then combine both files\n";
