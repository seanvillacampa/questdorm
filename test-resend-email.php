<?php

/**
 * Quick Resend Email Test Script
 * 
 * Run this to test if Resend is configured correctly:
 * php test-resend-email.php your-email@example.com
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

if (!isset($argv[1])) {
    echo "❌ Usage: php test-resend-email.php your-email@example.com\n";
    exit(1);
}

$toEmail = $argv[1];

echo "🚀 Testing Resend Email Configuration\n";
echo "=====================================\n\n";

echo "📧 Sending test email to: {$toEmail}\n";
echo "📤 From: " . config('mail.from.address') . "\n";
echo "🔑 Resend Key: " . substr(config('services.resend.key'), 0, 10) . "...\n";
echo "🎯 Mail Driver: " . config('mail.default') . "\n\n";

try {
    \Illuminate\Support\Facades\Mail::raw(
        "Hello!\n\nThis is a test email from Quest Building Management System.\n\nIf you received this, your Resend integration is working perfectly!\n\nBest regards,\nQuest Building Team",
        function ($message) use ($toEmail) {
            $message->to($toEmail)
                    ->subject('Quest Building - Test Email from Resend');
        }
    );
    
    echo "✅ Email sent successfully!\n\n";
    echo "📮 Next steps:\n";
    echo "   1. Check your inbox at: {$toEmail}\n";
    echo "   2. Check spam/junk folder if not in inbox\n";
    echo "   3. View email in Resend dashboard: https://resend.com/emails\n\n";
    
} catch (\Exception $e) {
    echo "❌ Failed to send email!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "💡 Troubleshooting:\n";
    echo "   1. Check your RESEND_KEY in .env file\n";
    echo "   2. Verify domain in Resend dashboard: https://resend.com/domains\n";
    echo "   3. If using test domain, use: onboarding@resend.dev as FROM address\n";
    echo "   4. Check Laravel logs: storage/logs/laravel.log\n\n";
    exit(1);
}
