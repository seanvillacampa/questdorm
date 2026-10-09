<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "Testing Brevo API email...\n";
echo "From: " . config('mail.from.address') . "\n";
echo "Mailer: " . config('mail.default') . "\n\n";

try {
    Mail::raw('This is a test email from Quest Dormitory using Brevo API.', function ($message) {
        $message->to('villacampasean@gmail.com')
                ->subject('Test Email - ' . now()->format('Y-m-d H:i:s'));
    });
    
    echo "✅ Email sent successfully!\n";
    echo "Check your inbox at villacampasean@gmail.com\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nFull error:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
