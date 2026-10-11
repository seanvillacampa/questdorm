<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking invoices table structure...\n";
$columns = DB::select('SHOW COLUMNS FROM invoices');
foreach ($columns as $col) {
    echo '  ' . $col->Field . ' (' . $col->Type . ')' . "\n";
}

echo "\nChecking rooms table structure...\n";
$columns = DB::select('SHOW COLUMNS FROM rooms');
foreach ($columns as $col) {
    echo '  ' . $col->Field . ' (' . $col->Type . ')' . "\n";
}

echo "\nChecking contracts table structure...\n";
$columns = DB::select('SHOW COLUMNS FROM contracts');
foreach ($columns as $col) {
    echo '  ' . $col->Field . ' (' . $col->Type . ')' . "\n";
}

echo "\nChecking tenant_deposits table structure...\n";
$columns = DB::select('SHOW COLUMNS FROM tenant_deposits');
foreach ($columns as $col) {
    echo '  ' . $col->Field . ' (' . $col->Type . ')' . "\n";
}

echo "\nChecking meter_readings table structure...\n";
$columns = DB::select('SHOW COLUMNS FROM meter_readings');
foreach ($columns as $col) {
    echo '  ' . $col->Field . ' (' . $col->Type . ')' . "\n";
}
