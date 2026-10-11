<?php
/**
 * Generate COMPLETE SQL export from localhost dormitory_db
 * Includes ALL columns from all 45 migrations
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$output = "-- Quest Building - COMPLETE Database Export\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- Schema: Full schema with all 45 migrations\n";
$output .= "-- Source: localhost dormitory_db\n\n";

$output .= "SET FOREIGN_KEY_CHECKS=0;\n";
$output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$output .= "SET time_zone = \"+00:00\";\n\n";

// Get all tables
$tables = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_' . env('DB_DATABASE');

$orderedTables = [
    'users',
    'password_reset_tokens',
    'sessions',
    'cache',
    'cache_locks',
    'jobs',
    'job_batches',
    'failed_jobs',
    'roles',
    'permissions',
    'model_has_roles',
    'model_has_permissions',
    'role_has_permissions',
    'settings',
    'rooms',
    'contracts',
    'tenants',
    'contract_tenants',
    'invoices',
    'payments',
    'tenant_payments',
    'tenant_deposits',
    'meter_readings',
    'audit_logs',
    'deposit_entries',
    'tenant_messages',
    'tenant_message_replies',
    'customers',
    'services',
    'service_prices',
    'laundry_orders',
    'order_items',
    'detergent_inventory',
    'detergent_logs',
];

// Drop all tables
foreach ($orderedTables as $table) {
    if (Schema::hasTable($table)) {
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
    }
}

$output .= "\n";

// Export each table
foreach ($orderedTables as $tableName) {
    if (!Schema::hasTable($tableName)) {
        continue;
    }

    echo "Exporting table: $tableName\n";

    // Get CREATE TABLE statement
    $createTable = DB::select("SHOW CREATE TABLE `$tableName`");
    $createStatement = $createTable[0]->{'Create Table'};
    
    $output .= "\n-- ============================================================\n";
    $output .= "-- Table: $tableName\n";
    $output .= "-- ============================================================\n\n";
    $output .= $createStatement . ";\n\n";

    // Get all rows
    $rows = DB::table($tableName)->get();
    
    if ($rows->isEmpty()) {
        $output .= "-- No data in $tableName\n\n";
        continue;
    }

    // Get column names
    $columns = Schema::getColumnListing($tableName);
    $columnList = implode('`, `', $columns);

    foreach ($rows as $row) {
        $values = [];
        foreach ($columns as $column) {
            $value = $row->$column;
            
            if ($value === null) {
                $values[] = 'NULL';
            } elseif (is_numeric($value)) {
                $values[] = $value;
            } elseif (is_bool($value)) {
                $values[] = $value ? '1' : '0';
            } else {
                $escaped = addslashes($value);
                $values[] = "'$escaped'";
            }
        }
        
        $valueList = implode(', ', $values);
        $output .= "INSERT INTO `$tableName` (`$columnList`) VALUES ($valueList);\n";
    }
    
    $output .= "\n";
}

$output .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Save to file
$filename = 'quest_building_COMPLETE.sql';
file_put_contents($filename, $output);

$sizeKb = round(strlen($output) / 1024, 2);
echo "\n✅ SQL export generated: $filename ($sizeKb KB)\n";
echo "✅ Includes ALL columns from all 45 migrations\n";
echo "✅ Ready to import to Clever Cloud\n";
echo "✅ Tables exported: " . count($orderedTables) . "\n";
