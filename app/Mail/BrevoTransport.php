<?php

namespace App\Mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use GuzzleHttp\Client;

class BrevoTransport extends AbstractTransport
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();
        
        if (!$email instanceof Email) {
            throw new \InvalidArgumentException('Message must be an instance of Email');
        }
        
        $payload = [
            'sender' => $this->formatAddress($email->getFrom()[0]),
            'to' => $this->formatAddresses($email->getTo()),
            'subject' => $email->getSubject(),
        ];
        
        // Add HTML or text content
        if ($email->getHtmlBody()) {
            $payload['htmlContent'] = $email->getHtmlBody();
        } else {
            $payload['htmlContent'] = nl2br(htmlspecialchars($email->getTextBody() ?? ''));
        }
        
        // Add CC if present
        if ($email->getCc()) {
            $payload['cc'] = $this->formatAddresses($email->getCc());
        }
        
        // Add BCC if present
        if ($email->getBcc()) {
            $payload['bcc'] = $this->formatAddresses($email->getBcc());
        }
        
        $client = new Client();
        $response = $client->post('https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ],
            'json' => $payload,
        ]);
        
        if ($response->getStatusCode() !== 201) {
            throw new \Exception('Failed to send email via Brevo API: ' . $response->getBody());
        }
    }
    
    protected function formatAddress(Address $address): array
    {
        return [
            'email' => $address->getAddress(),
            'name' => $address->getName() ?: null,
        ];
    }
    
    protected function formatAddresses(array $addresses): array
    {
        return array_map(fn(Address $addr) => $this->formatAddress($addr), $addresses);
    }
    
    public function __toString(): string
    {
        return 'brevo';
    }
}
