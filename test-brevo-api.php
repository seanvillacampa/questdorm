<?php

/**
 * Quick test script to verify Brevo API integration works
 * 
 * Usage: php test-brevo-api.php
 */

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['BREVO_API_KEY'] ?? null;
$fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com';
$toEmail = $fromEmail; // Send test email to yourself

if (!$apiKey || $apiKey === 'your-brevo-api-key-here') {
    echo "❌ Error: BREVO_API_KEY not set in .env file\n";
    echo "Please get your API key from: https://app.brevo.com → Settings → SMTP & API → API Keys\n";
    exit(1);
}

echo "Testing Brevo API integration...\n";
echo "From: {$fromEmail}\n";
echo "To: {$toEmail}\n\n";

try {
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
    $apiInstance = new TransactionalEmailsApi(new Client(), $config);
    
    $sendSmtpEmail = new SendSmtpEmail([
        'sender' => [
            'email' => $fromEmail,
            'name' => 'Quest Dormitory Test'
        ],
        'to' => [[
            'email' => $toEmail,
            'name' => 'Test Recipient'
        ]],
        'subject' => 'Brevo API Test - ' . date('Y-m-d H:i:s'),
        'htmlContent' => '<html><body>
            <h1>✅ Brevo API Integration Works!</h1>
            <p>This test email confirms that your Quest Dormitory system can successfully send emails via Brevo API.</p>
            <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Environment:</strong> ' . ($_ENV['APP_ENV'] ?? 'unknown') . '</p>
            <hr>
            <p style="color: #666; font-size: 12px;">
                This is an automated test email from your dormitory management system.
            </p>
        </body></html>',
    ]);

    $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
    
    echo "✅ Success! Email sent successfully.\n";
    echo "Message ID: " . $result->getMessageId() . "\n\n";
    echo "Check your inbox at: {$toEmail}\n";
    echo "If you don't see it, check your spam folder.\n";
    
} catch (Exception $e) {
    echo "❌ Error sending email:\n";
    echo $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), '401') !== false) {
        echo "Hint: Your API key is invalid or not authorized.\n";
        echo "Generate a new one at: https://app.brevo.com → Settings → SMTP & API → API Keys\n";
    } elseif (strpos($e->getMessage(), 'sender') !== false) {
        echo "Hint: Your sender email may not be verified.\n";
        echo "Verify it at: https://app.brevo.com → Settings → Senders & IP → Senders\n";
    }
    
    exit(1);
}
