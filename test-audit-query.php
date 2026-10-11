<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test the query that was failing
try {
    $staffUsers = App\Models\User::role(['owner','employee'])->orderBy('last_name')->orderBy('first_name')->get();

    echo "Staff Users (ordered by last_name, first_name):\n";
    echo "===============================================\n";
    foreach ($staffUsers as $user) {
        echo $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name . ' (' . $user->email . ')' . "\n";
    }
    echo "\nTotal: " . $staffUsers->count() . " users\n";
    echo "\n✅ Audit log query works!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
