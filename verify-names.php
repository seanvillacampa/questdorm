<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Verifying User Names Structure\n";
echo "================================\n\n";

// Check if 'name' column still exists
try {
    $columns = \DB::select('SHOW COLUMNS FROM users LIKE "name"');
    if (count($columns) > 0) {
        echo "⚠️  WARNING: 'name' column still exists in database!\n\n";
    } else {
        echo "✅ 'name' column successfully removed\n\n";
    }
} catch (\Exception $e) {
    echo "Error checking columns: " . $e->getMessage() . "\n\n";
}

// Check sample users
echo "Sample Users:\n";
$users = \App\Models\User::take(5)->get(['id', 'first_name', 'middle_name', 'last_name', 'email']);

foreach ($users as $user) {
    echo "ID {$user->id}:\n";
    echo "  First: '{$user->first_name}'\n";
    echo "  Middle: '{$user->middle_name}'\n";
    echo "  Last: '{$user->last_name}'\n";
    echo "  Full (accessor): '{$user->name}'\n";
    echo "  Email: {$user->email}\n";
    
    // Check for NULL values
    if (is_null($user->first_name) || empty($user->first_name)) {
        echo "  ❌ first_name is NULL or empty!\n";
    }
    if (is_null($user->middle_name) || empty($user->middle_name)) {
        echo "  ❌ middle_name is NULL or empty!\n";
    }
    if (is_null($user->last_name) || empty($user->last_name)) {
        echo "  ❌ last_name is NULL or empty!\n";
    }
    echo "\n";
}

// Check for any NULL values in users table
echo "Checking for NULL values in users table:\n";
$nullFirstNames = \App\Models\User::whereNull('first_name')->orWhere('first_name', '')->count();
$nullMiddleNames = \App\Models\User::whereNull('middle_name')->orWhere('middle_name', '')->count();
$nullLastNames = \App\Models\User::whereNull('last_name')->orWhere('last_name', '')->count();
$nullPhones = \App\Models\User::whereNull('phone')->orWhere('phone', '')->count();

echo "  First names (NULL/empty): {$nullFirstNames}\n";
echo "  Middle names (NULL/empty): {$nullMiddleNames}\n";
echo "  Last names (NULL/empty): {$nullLastNames}\n";
echo "  Phones (NULL/empty): {$nullPhones}\n\n";

if ($nullFirstNames == 0 && $nullMiddleNames == 0 && $nullLastNames == 0 && $nullPhones == 0) {
    echo "✅ No NULL or empty values found in name fields!\n";
} else {
    echo "❌ Found NULL or empty values!\n";
}
