<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing email configuration...\n";
    echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
    echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
    echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
    echo "MAIL_FROM: " . config('mail.from.address') . "\n";
    echo "\n";
    
    echo "Sending test email...\n";
    
    Illuminate\Support\Facades\Mail::raw('This is a test email from Quest Dormitory system.', function ($message) {
        $message->to('villacampasean@gmail.com')
                ->subject('Test Email from Quest Dorm');
    });
    
    echo "✅ Email sent successfully!\n";
    echo "Check your inbox at: villacampasean@gmail.com\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nFull error:\n";
    echo $e->getTraceAsString();
}
