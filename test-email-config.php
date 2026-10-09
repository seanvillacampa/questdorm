<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing email configuration...\n\n";

echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name') . "\n";

if (config('mail.default') === 'smtp') {
    echo "SMTP_HOST: " . config('mail.mailers.smtp.host') . "\n";
    echo "SMTP_PORT: " . config('mail.mailers.smtp.port') . "\n";
    echo "SMTP_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
    echo "SMTP_PASSWORD: " . (config('mail.mailers.smtp.password') ? '***SET***' : 'NOT SET') . "\n";
} elseif (config('mail.default') === 'resend') {
    echo "RESEND_API_KEY: " . (config('services.resend.key') ? '***SET***' : 'NOT SET') . "\n";
}

echo "\nTrying to send test email...\n";

try {
    \Illuminate\Support\Facades\Mail::raw('Test email from QuestDorm', function($message) {
        $message->to('test@example.com')
                ->subject('Test Email Configuration');
    });
    echo "✅ Email sent successfully!\n";
    echo "Check logs at storage/logs/laravel.log for details.\n";
} catch (\Exception $e) {
    echo "❌ Email failed: " . $e->getMessage() . "\n";
    echo "\nFull error:\n";
    echo $e->getTraceAsString() . "\n";
}
