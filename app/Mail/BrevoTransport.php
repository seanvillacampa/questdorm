<?php

namespace App\Mail;

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Illuminate\Mail\Transport\Transport;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends Transport
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(\Symfony\Component\Mime\RawMessage $message): void
    {
        $email = MessageConverter::toEmail($message);
        
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        $apiInstance = new TransactionalEmailsApi(new Client(), $config);
        
        $sendSmtpEmail = new SendSmtpEmail([
            'sender' => [
                'email' => $email->getFrom()[0]->getAddress(),
                'name' => $email->getFrom()[0]->getName() ?? config('app.name')
            ],
            'to' => collect($email->getTo())->map(fn($addr) => [
                'email' => $addr->getAddress(),
                'name' => $addr->getName()
            ])->toArray(),
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody() ?? $email->getTextBody(),
        ]);

        if ($email->getCc()) {
            $sendSmtpEmail->setCc(
                collect($email->getCc())->map(fn($addr) => [
                    'email' => $addr->getAddress(),
                    'name' => $addr->getName()
                ])->toArray()
            );
        }

        if ($email->getBcc()) {
            $sendSmtpEmail->setBcc(
                collect($email->getBcc())->map(fn($addr) => [
                    'email' => $addr->getAddress(),
                    'name' => $addr->getName()
                ])->toArray()
            );
        }

        $apiInstance->sendTransacEmail($sendSmtpEmail);
    }
}
