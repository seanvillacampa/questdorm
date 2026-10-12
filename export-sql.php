<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "-- Quest Building Database Dump (Aug-Oct 2026)\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

echo "SET FOREIGN_KEY_CHECKS=0;\n";
echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

$pdo = DB::connection()->getPdo();

// Get all tables
$tables = [
    'roles',
    'permissions',
    'model_has_roles',
    'model_has_permissions',
    'role_has_permissions',
    'users',
    'tenants',
    'rooms',
    'contracts',
    'contract_tenants',
    'tenant_deposits',
    'settings',
    'meter_readings',
    'invoices',
    'tenant_payments',
    'payments',
    'services',
    'service_prices',
    'customers',
    'laundry_orders',
    'order_items',
    'detergent_inventory',
    'detergent_logs',
];

foreach ($tables as $table) {
    echo "-- Table: $table\n";
    echo "TRUNCATE TABLE `$table`;\n\n";
    
    $rows = DB::table($table)->get();
    
    if ($rows->isEmpty()) {
        echo "-- No data\n\n";
        continue;
    }
    
    foreach ($rows as $row) {
        $row = (array) $row;
        $columns = array_keys($row);
        $values = array_values($row);
        
        // Use PDO's quote method for proper escaping
        $escapedValues = array_map(function($value) use ($pdo) {
            if ($value === null) {
                return 'NULL';
            }
            // Let PDO handle the escaping
            return $pdo->quote($value);
        }, $values);
        
        echo "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (";
        echo implode(', ', $escapedValues);
        echo ");\n";
    }
    
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
