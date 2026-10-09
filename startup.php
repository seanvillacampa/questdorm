<?php

echo "Starting QuestDorm application...\n";

// Fix permissions
echo "Setting storage permissions...\n";
@chmod(__DIR__ . '/storage', 0775);
@chmod(__DIR__ . '/bootstrap/cache', 0775);

// Recursively set permissions
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/storage', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    @chmod($item, $item->isDir() ? 0775 : 0664);
}

// Wait for database with retry logic
echo "Waiting for database connection...\n";
$maxAttempts = 30;
$attempt = 0;
$connected = false;

while ($attempt < $maxAttempts && !$connected) {
    $attempt++;
    try {
        require __DIR__ . '/vendor/autoload.php';
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        
        // Try to connect
        $kernel->call('migrate', ['--force' => true]);
        $connected = true;
        echo "Database migrations completed successfully!\n";
    } catch (Exception $e) {
        echo "Database not ready, waiting... (attempt $attempt/$maxAttempts)\n";
        sleep(2);
    }
}

if (!$connected) {
    echo "WARNING: Could not connect to database after $maxAttempts attempts\n";
    echo "Application will start anyway. Run migrations manually when database is ready.\n";
} else {
    // Clear and cache configuration
    echo "Caching configuration...\n";
    try {
        $kernel->call('config:cache');
        $kernel->call('route:cache');
        $kernel->call('view:cache');
        echo "Configuration cached successfully!\n";
    } catch (Exception $e) {
        echo "Warning: Could not cache configuration: " . $e->getMessage() . "\n";
    }
}

echo "QuestDorm application startup complete!\n";
