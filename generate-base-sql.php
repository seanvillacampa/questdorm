<?php
/**
 * Generate SQL export using ONLY base migration columns
 * This matches the initial schema before any ALTER TABLE migrations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$output = "-- Quest Building Database Export (Base Schema Only)\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- Schema: Initial migrations only (no custom columns)\n\n";

$output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Clear existing tables
$tables = [
    'password_reset_tokens',
    'sessions',
    'cache',
    'cache_locks',
    'jobs',
    'job_batches',
    'failed_jobs',
    'model_has_permissions',
    'model_has_roles',
    'role_has_permissions',
    'permissions',
    'roles',
    'meter_readings',
    'tenant_deposits',
    'tenant_message_replies',
    'tenant_messages',
    'tenant_payments',
    'payments',
    'invoices',
    'contract_tenants',
    'contracts',
    'rooms',
    'tenants',
    'detergent_logs',
    'detergent_inventory',
    'order_items',
    'laundry_orders',
    'service_prices',
    'services',
    'customers',
    'deposit_entries',
    'audit_logs',
    'settings',
    'users'
];

foreach ($tables as $table) {
    $output .= "DROP TABLE IF EXISTS `$table`;\n";
}

$output .= "\n-- ============================================================\n";
$output .= "-- USERS TABLE (BASE SCHEMA WITH name column)\n";
$output .= "-- ============================================================\n\n";

$output .= "CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

// Export users with concatenated name
$users = DB::table('users')->get();
foreach ($users as $user) {
    // Get name parts from your split-name users
    $firstName = DB::raw("COALESCE(first_name, '')");
    $middleName = DB::raw("COALESCE(middle_name, '')");
    $lastName = DB::raw("COALESCE(last_name, '')");
    
    // Re-query to get the actual name parts
    $userFull = DB::table('users')->where('id', $user->id)->first();
    $fullName = trim(
        ($userFull->first_name ?? '') . ' ' . 
        ($userFull->middle_name ?? '') . ' ' . 
        ($userFull->last_name ?? '')
    );
    $fullName = preg_replace('/\s+/', ' ', $fullName); // Remove extra spaces
    
    $name = addslashes($fullName);
    $email = addslashes($user->email);
    $phone = $user->phone ? "'" . addslashes($user->phone) . "'" : 'NULL';
    $password = addslashes($user->password);
    $isActive = $user->is_active ? 1 : 0;
    $rememberToken = $user->remember_token ? "'" . addslashes($user->remember_token) . "'" : 'NULL';
    $emailVerifiedAt = $user->email_verified_at ? "'" . $user->email_verified_at . "'" : 'NULL';
    $createdAt = "'" . $user->created_at . "'";
    $updatedAt = "'" . $user->updated_at . "'";
    
    $output .= "INSERT INTO `users` VALUES ($user->id, '$name', '$email', $emailVerifiedAt, $phone, '$password', $isActive, $rememberToken, $createdAt, $updatedAt);\n";
}

$output .= "\n-- ============================================================\n";
$output .= "-- ROOMS TABLE (BASE SCHEMA)\n";
$output .= "-- ============================================================\n\n";

$output .= "CREATE TABLE `rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_number` varchar(10) NOT NULL,
  `floor` tinyint(3) unsigned NOT NULL,
  `capacity` varchar(20) NOT NULL DEFAULT '1 bed',
  `monthly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `meter_number` varchar(30) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'vacant',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rooms_room_number_unique` (`room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$rooms = DB::table('rooms')->get();
foreach ($rooms as $room) {
    $roomNumber = addslashes($room->room_number);
    $capacity = addslashes($room->capacity);
    $meterNumber = $room->meter_number ? "'" . addslashes($room->meter_number) . "'" : 'NULL';
    $status = addslashes($room->status);
    $createdAt = "'" . $room->created_at . "'";
    $updatedAt = "'" . $room->updated_at . "'";
    
    $output .= "INSERT INTO `rooms` VALUES ($room->id, '$roomNumber', $room->floor, '$capacity', $room->monthly_rate, $meterNumber, '$status', $createdAt, $updatedAt);\n";
}

$output .= "\n-- ============================================================\n";
$output .= "-- CONTRACTS TABLE (BASE SCHEMA)\n";
$output .= "-- ============================================================\n\n";

$output .= "CREATE TABLE `contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `due_day` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `deposit_collected` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `contract_file_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_room_id_foreign` (`room_id`),
  KEY `contracts_created_by_foreign` (`created_by`),
  CONSTRAINT `contracts_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contracts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$contracts = DB::table('contracts')->get();
foreach ($contracts as $contract) {
    $contractFilePath = $contract->contract_file_path ? "'" . addslashes($contract->contract_file_path) . "'" : 'NULL';
    $createdBy = $contract->created_by ?? 'NULL';
    $status = addslashes($contract->status);
    $createdAt = "'" . $contract->created_at . "'";
    $updatedAt = "'" . $contract->updated_at . "'";
    
    $output .= "INSERT INTO `contracts` VALUES ($contract->id, $contract->room_id, '$contract->start_date', '$contract->end_date', $contract->monthly_rent, $contract->due_day, $contract->deposit_collected, '$status', $contractFilePath, $createdBy, $createdAt, $updatedAt);\n";
}

$output .= "\n-- ============================================================\n";
$output .= "-- TENANTS TABLE (BASE SCHEMA)\n";
$output .= "-- ============================================================\n\n";

$output .= "CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `birthdate` date DEFAULT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `emergency_name` varchar(255) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_user_id_unique` (`user_id`),
  CONSTRAINT `tenants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$tenants = DB::table('tenants')->get();
foreach ($tenants as $tenant) {
    $birthdate = $tenant->birthdate ? "'" . $tenant->birthdate . "'" : 'NULL';
    $idType = $tenant->id_type ? "'" . addslashes($tenant->id_type) . "'" : 'NULL';
    $idNumber = $tenant->id_number ? "'" . addslashes($tenant->id_number) . "'" : 'NULL';
    $emergencyName = $tenant->emergency_name ? "'" . addslashes($tenant->emergency_name) . "'" : 'NULL';
    $emergencyPhone = $tenant->emergency_phone ? "'" . addslashes($tenant->emergency_phone) . "'" : 'NULL';
    $createdAt = "'" . $tenant->created_at . "'";
    $updatedAt = "'" . $tenant->updated_at . "'";
    
    $output .= "INSERT INTO `tenants` VALUES ($tenant->id, $tenant->user_id, $birthdate, $idType, $idNumber, $emergencyName, $emergencyPhone, $createdAt, $updatedAt);\n";
}

$output .= "\n-- ============================================================\n";
$output .= "-- INVOICES TABLE (BASE SCHEMA - NO carry_over_balance)\n";
$output .= "-- ============================================================\n\n";

$output .= "CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(30) NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `billing_month` varchar(7) NOT NULL,
  `due_date` date NOT NULL,
  `rent_amount` decimal(10,2) NOT NULL,
  `electricity_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_contract_id_foreign` (`contract_id`),
  KEY `invoices_created_by_foreign` (`created_by`),
  CONSTRAINT `invoices_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$invoices = DB::table('invoices')->get();
foreach ($invoices as $invoice) {
    $invoiceNumber = addslashes($invoice->invoice_number);
    $billingMonth = addslashes($invoice->billing_month);
    $status = addslashes($invoice->status);
    $paidAt = $invoice->paid_at ? "'" . $invoice->paid_at . "'" : 'NULL';
    $createdBy = $invoice->created_by ?? 'NULL';
    $createdAt = "'" . $invoice->created_at . "'";
    $updatedAt = "'" . $invoice->updated_at . "'";
    
    $output .= "INSERT INTO `invoices` VALUES ($invoice->id, '$invoiceNumber', $invoice->contract_id, '$billingMonth', '$invoice->due_date', $invoice->rent_amount, $invoice->electricity_amount, $invoice->total_amount, $invoice->amount_paid, '$status', $paidAt, $createdBy, $createdAt, $updatedAt);\n";
}

$output .= "\n-- ============================================================\n";
$output .= "-- METER READINGS TABLE (BASE SCHEMA)\n";
$output .= "-- ============================================================\n\n";

$output .= "CREATE TABLE `meter_readings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `reading_month` varchar(7) NOT NULL,
  `previous_kwh` decimal(10,1) NOT NULL DEFAULT 0.0,
  `current_kwh` decimal(10,1) NOT NULL,
  `kwh_used` decimal(10,1) NOT NULL DEFAULT 0.0,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meter_readings_room_id_reading_month_unique` (`room_id`,`reading_month`),
  KEY `meter_readings_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `meter_readings_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meter_readings_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$meterReadings = DB::table('meter_readings')->get();
foreach ($meterReadings as $reading) {
    $readingMonth = addslashes($reading->reading_month);
    $recordedBy = $reading->recorded_by ?? 'NULL';
    $createdAt = "'" . $reading->created_at . "'";
    $updatedAt = "'" . $reading->updated_at . "'";
    
    $output .= "INSERT INTO `meter_readings` VALUES ($reading->id, $reading->room_id, '$readingMonth', $reading->previous_kwh, $reading->current_kwh, $reading->kwh_used, $recordedBy, $createdAt, $updatedAt);\n";
}

$output .= "\n-- ============================================================\n";
$output .= "-- CONTRACT_TENANTS TABLE\n";
$output .= "-- ============================================================\n\n";

$output .= "CREATE TABLE `contract_tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_tenants_contract_id_foreign` (`contract_id`),
  KEY `contract_tenants_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `contract_tenants_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_tenants_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$contractTenants = DB::table('contract_tenants')->get();
foreach ($contractTenants as $ct) {
    $createdAt = "'" . $ct->created_at . "'";
    $updatedAt = "'" . $ct->updated_at . "'";
    $output .= "INSERT INTO `contract_tenants` VALUES ($ct->id, $ct->contract_id, $ct->tenant_id, $createdAt, $updatedAt);\n";
}

$output .= "\n-- ============================================================\n";
$output .= "-- SETTINGS TABLE\n";
$output .= "-- ============================================================\n\n";

$output .= "CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$settings = DB::table('settings')->get();
foreach ($settings as $setting) {
    $key = addslashes($setting->key);
    $value = $setting->value ? "'" . addslashes($setting->value) . "'" : 'NULL';
    $createdAt = $setting->created_at ? "'" . $setting->created_at . "'" : 'NULL';
    $updatedAt = $setting->updated_at ? "'" . $setting->updated_at . "'" : 'NULL';
    $output .= "INSERT INTO `settings` VALUES ($setting->id, '$key', $value, $createdAt, $updatedAt);\n";
}

$output .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

// Save to file
$filename = 'quest_building_BASE_SCHEMA.sql';
file_put_contents($filename, $output);

$sizeKb = round(strlen($output) / 1024, 2);
echo "✅ SQL export generated: $filename ($sizeKb KB)\n";
echo "✅ Using BASE migration schema (no custom columns)\n";
echo "✅ Users exported with concatenated name field\n";
echo "✅ Ready for import to Clever Cloud\n";
