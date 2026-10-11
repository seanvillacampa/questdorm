<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Set UTF-8 encoding
DB::statement('SET NAMES utf8mb4');

// Ensure UTF-8 output
header('Content-Type: text/plain; charset=UTF-8');

$output = "-- Quest Building Complete Database Export\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- Database: dormitory_db\n\n";

$output .= "SET NAMES utf8mb4;\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Tables in order
$tables = [
    'users',
    'rooms',
    'contracts',
    'tenants',
    'tenant_deposits',
    'deposit_entries',
    'meter_readings',
    'invoices',
    'payments',
    'tenant_payments',
    'settings'
];

foreach ($tables as $table) {
    $output .= "-- Table: {$table}\n";
    $output .= "TRUNCATE TABLE `{$table}`;\n\n";
    
    $rows = DB::table($table)->get();
    
    if ($rows->isEmpty()) {
        $output .= "-- No data in {$table}\n\n";
        continue;
    }
    
    foreach ($rows as $row) {
        $row = (array) $row;
        $columns = array_keys($row);
        $values = array_values($row);
        
        // Escape values
        $escapedValues = array_map(function($value) {
            if (is_null($value)) {
                return 'NULL';
            }
            if (is_numeric($value)) {
                return $value;
            }
            return "'" . addslashes($value) . "'";
        }, $values);
        
        $columnList = '`' . implode('`, `', $columns) . '`';
        $valueList = implode(', ', $escapedValues);
        
        $output .= "INSERT INTO `{$table}` ({$columnList}) VALUES ({$valueList});\n";
    }
    
    $output .= "\n";
}

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$output .= "\n-- Export complete\n";

// Write to file with explicit UTF-8 encoding
file_put_contents('quest_building_clever_import.sql', $output);
