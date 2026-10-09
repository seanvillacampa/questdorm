<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Mail Configuration ===\n\n";
echo "Default mailer: " . config('mail.default') . "\n";
echo "From address: " . config('mail.from.address') . "\n";
echo "From name: " . config('mail.from.name') . "\n";
echo "Brevo API Key: " . (config('services.brevo.key') ? 'SET (length: ' . strlen(config('services.brevo.key')) . ')' : 'NOT SET') . "\n\n";

echo "Available mailers:\n";
foreach (array_keys(config('mail.mailers')) as $mailer) {
    echo "  - $mailer\n";
}

echo "\n";

// Check if brevo mailer exists
$mailers = config('mail.mailers');
if (isset($mailers['brevo'])) {
    echo "✅ 'brevo' mailer is configured\n";
    echo "   Transport: " . $mailers['brevo']['transport'] . "\n";
} else {
    echo "❌ 'brevo' mailer is NOT configured\n";
}
