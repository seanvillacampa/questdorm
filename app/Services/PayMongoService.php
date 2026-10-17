<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper around the PayMongo v1 REST API.
 * Docs: https://developers.paymongo.com/reference
 */
class PayMongoService
{
    private string $baseUrl;
    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl       = config('paymongo.api_url', 'https://api.paymongo.com/v1');
        $this->secretKey     = config('paymongo.secret_key', '');
        $this->webhookSecret = config('paymongo.webhook_secret', '');
    }

    // ── Create Payment Link ──────────────────────────────────────────────

    public function createPaymentLink(Invoice $invoice, string $description = '', string $successUrl = '', string $cancelUrl = ''): array
    {
        $amountCentavos = (int) round($invoice->balanceDue() * 100);

        if ($amountCentavos < 2000) {
            throw new \RuntimeException('Amount too small for PayMongo (minimum ₱20).');
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'amount'      => $amountCentavos,
                    'currency'    => 'PHP',
                    'description' => $description ?: "Billing Statement {$invoice->invoice_number}",
                    'remarks'     => $invoice->invoice_number,
                ],
            ],
        ];

        // Add redirect URLs if provided (PayMongo links support this)
        if ($successUrl || $cancelUrl) {
            $payload['data']['attributes']['redirect'] = [
                'success' => $successUrl ?: $cancelUrl,
                'failed'  => $cancelUrl  ?: $successUrl,
            ];
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->post("{$this->baseUrl}/links", $payload);

        if ($response->failed()) {
            Log::error('PayMongo createPaymentLink failed', [
                'status'  => $response->status(),
                'body'    => $response->json(),
                'invoice' => $invoice->invoice_number,
            ]);
            throw new \RuntimeException(
                'PayMongo error: ' . ($response->json('errors.0.detail') ?? $response->body())
            );
        }

        $data = $response->json('data');

        return [
            'id'           => $data['id'],
            'checkout_url' => $data['attributes']['checkout_url'],
            'reference'    => $data['attributes']['reference_number'] ?? $data['id'],
        ];
    }

    // ── Create Checkout Session (supports success/cancel redirect) ────────

    public function createCheckoutSession(Invoice $invoice, string $description = '', string $successUrl = '', string $cancelUrl = ''): array
    {
        $amountCentavos = (int) round($invoice->balanceDue() * 100);

        if ($amountCentavos < 2000) {
            throw new \RuntimeException('Amount too small for PayMongo (minimum ₱20).');
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'billing'            => null,
                    'cancel_url'         => $cancelUrl  ?: $successUrl,
                    'success_url'        => $successUrl ?: $cancelUrl,
                    'description'        => $description ?: "Billing Statement {$invoice->invoice_number}",
                    'line_items'         => [
                        [
                            'amount'      => $amountCentavos,
                            'currency'    => 'PHP',
                            'description' => $description ?: "Billing Statement {$invoice->invoice_number}",
                            'name'        => $invoice->invoice_number,
                            'quantity'    => 1,
                        ],
                    ],
                    'payment_method_types' => ['gcash', 'paymaya', 'card', 'dob', 'dob_ubp', 'brankas_bdo', 'brankas_landbank', 'brankas_metrobank', 'qrph'],
                    'reference_number'   => $invoice->invoice_number,
                    'send_email_receipt' => false,
                    'show_description'   => true,
                    'show_line_items'    => true,
                    'metadata'           => [
                        'invoice_number' => $invoice->invoice_number,
                        'invoice_id'     => $invoice->id,
                    ],
                ],
            ],
        ];

        $response = Http::withBasicAuth($this->secretKey, '')
            ->post("{$this->baseUrl}/checkout_sessions", $payload);

        if ($response->failed()) {
            Log::error('PayMongo createCheckoutSession failed', [
                'status'  => $response->status(),
                'body'    => $response->json(),
                'invoice' => $invoice->invoice_number,
            ]);
            throw new \RuntimeException(
                'PayMongo error: ' . ($response->json('errors.0.detail') ?? $response->body())
            );
        }

        $data = $response->json('data');

        return [
            'id'           => $data['id'],
            'checkout_url' => $data['attributes']['checkout_url'],
            'reference'    => $data['attributes']['reference_number'] ?? $data['id'],
        ];
    }

    // ── Verify Webhook Signature ─────────────────────────────────────────

    /**
     * Verify the PayMongo webhook signature.
     *
     * Header format: t=<timestamp>,te=<test_hmac>,li=<live_hmac>
     * Signed string: "<timestamp>.<raw_body>"
     * HMAC key:      webhook secret key (whsk_...)
     *
     * For test keys (sk_test_*): use 'te' signature field
     * For live keys (sk_live_*): use 'li' signature field
     *
     * If the webhook secret is empty or missing, we skip verification in
     * development to avoid blocking local testing — log a warning and allow.
     */
    public function verifyWebhookSignature(string $rawBody, string $sigHeader): bool
    {
        // If no webhook secret configured, allow through with a warning
        if (empty($this->webhookSecret)) {
            Log::warning('PayMongo webhook: PAYMONGO_WEBHOOK_SECRET is empty — skipping signature check');
            return true;
        }

        if (empty($sigHeader)) {
            Log::warning('PayMongo webhook: Paymongo-Signature header is missing');
            // In production you'd return false here; allow through for local tunnels
            // that may strip headers, but log loudly
            return !app()->isProduction();
        }

        // Parse header — split by comma, then split each part on FIRST '=' only
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            $eq = strpos($part, '=');
            if ($eq !== false) {
                $k = trim(substr($part, 0, $eq));
                $v = trim(substr($part, $eq + 1));
                $parts[$k] = $v;
            }
        }

        Log::info('PayMongo signature header parsed', [
            'keys'      => array_keys($parts),
            'timestamp' => $parts['t'] ?? null,
        ]);

        $timestamp = $parts['t'] ?? null;

        // Choose signature field: test key → 'te', live key → 'li'
        // Also fall back to trying both in case key detection is off
        $isTestKey  = str_starts_with($this->secretKey, 'sk_test') ||
                      str_starts_with($this->webhookSecret, 'whsk_test');
        $sigField   = $isTestKey ? 'te' : 'li';
        $signature  = $parts[$sigField] ?? $parts['te'] ?? $parts['li'] ?? null;

        if (empty($timestamp) || empty($signature)) {
            Log::warning('PayMongo webhook: could not extract timestamp or signature', [
                'parsed_keys' => array_keys($parts),
                'sig_field'   => $sigField,
            ]);
            return false;
        }

        $signedPayload = $timestamp . '.' . $rawBody;
        $expectedHmac  = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        $valid = hash_equals($expectedHmac, $signature);

        Log::info('PayMongo signature check', [
            'valid'            => $valid,
            'key_type'         => $isTestKey ? 'test' : 'live',
            'sig_field_used'   => $sigField,
            'expected_prefix'  => substr($expectedHmac, 0, 12) . '...',
            'received_prefix'  => substr($signature, 0, 12) . '...',
        ]);

        return $valid;
    }

    // ── Parse Webhook Event ──────────────────────────────────────────────

    /**
     * Extract a structured event from a decoded webhook body array.
     *
     * PayMongo link.payment.paid structure:
     *   data.attributes.type                              = "link.payment.paid"
     *   data.attributes.data.type                         = "payment"
     *   data.attributes.data.id                           = "pay_xxx"
     *   data.attributes.data.attributes.source.id         = "link_xxx"  ← link id
     *   data.attributes.data.attributes.source.type       = "gcash|maya|card|..."
     *   data.attributes.data.attributes.amount            = 12000  (centavos)
     *   data.attributes.data.attributes.description       = "..."
     *   data.attributes.data.attributes.remarks           = "BILL-..."
     *
     * PayMongo checkout_session.payment.paid structure:
     *   data.attributes.type                              = "checkout_session.payment.paid"
     *   data.attributes.data.type                         = "checkout_session"
     *   data.attributes.data.id                           = "cs_xxx"  ← checkout session id
     *   data.attributes.data.attributes.payment_intent.id = "pi_xxx"
     *   data.attributes.data.attributes.payments[0].id    = "pay_xxx"
     *   data.attributes.data.attributes.payments[0].attributes.source.type = "gcash|..."
     *   data.attributes.data.attributes.payments[0].attributes.amount      = 12000
     *   data.attributes.data.attributes.reference_number  = "BILL-..."
     *   data.attributes.data.attributes.metadata.invoice_number = "BILL-..."
     */
    public function parseWebhookEvent(array $body): array
    {
        $attrs    = $body['data']['attributes'] ?? [];
        $resource = $attrs['data'] ?? [];
        $resAttrs = $resource['attributes'] ?? [];
        $eventType = $attrs['type'] ?? null;

        // ── Checkout session event ────────────────────────────────────────
        if ($eventType === 'checkout_session.payment.paid') {
            $sessionId   = $resource['id'] ?? null; // cs_xxx
            $payments    = $resAttrs['payments'] ?? [];
            $firstPay    = $payments[0] ?? [];
            $firstAttrs  = $firstPay['attributes'] ?? [];

            // Amount & method come from the first payment object
            $amount      = $firstAttrs['amount'] ?? ($resAttrs['payment_intent']['attributes']['amount'] ?? 0);
            $sourceType  = $firstAttrs['source']['type'] ?? ($firstAttrs['payment_method_used'] ?? 'paymongo');
            $payId       = $firstPay['id'] ?? null;

            // Reference for matching: reference_number or metadata.invoice_number
            $reference   = $resAttrs['reference_number']
                ?? $resAttrs['metadata']['invoice_number']
                ?? null;

            Log::info('PayMongo parseWebhookEvent [checkout_session]', [
                'event_type'   => $eventType,
                'session_id'   => $sessionId,
                'payment_id'   => $payId,
                'amount'       => $amount,
                'source_type'  => $sourceType,
                'reference'    => $reference,
            ]);

            return [
                'event_type'        => $eventType,
                'resource_type'     => 'checkout_session',
                'resource_id'       => $payId,       // pay_xxx for idempotency
                'link_id'           => $sessionId,   // cs_xxx — stored in paymongo_link_id
                'payment_intent_id' => $resAttrs['payment_intent']['id'] ?? null,
                'resource_attrs'    => array_merge($firstAttrs, [
                    'amount'      => $amount,
                    'source'      => ['type' => $sourceType, 'id' => $sessionId],
                    'remarks'     => $reference,
                    'description' => $resAttrs['description'] ?? '',
                    'id'          => $payId,
                ]),
            ];
        }

        // ── Link payment event (original flow) ────────────────────────────
        // For link payments: link id is in source.id
        $linkIdFromSource = $resAttrs['source']['id'] ?? null;

        // Some versions put it in a 'links' sub-object
        $linkIdFromLinks  = $resAttrs['links']['id']
            ?? (is_array($resAttrs['links'] ?? null) ? ($resAttrs['links'][0]['id'] ?? null) : null);

        $linkId = $linkIdFromSource ?? $linkIdFromLinks ?? null;

        // For checkout sessions, match via payment_intent_id
        $paymentIntentId = $resAttrs['payment_intent_id'] ?? null;

        Log::info('PayMongo parseWebhookEvent', [
            'event_type'        => $eventType,
            'resource_id'       => $resource['id'] ?? null,
            'link_id_source'    => $linkIdFromSource,
            'link_id_links'     => $linkIdFromLinks,
            'payment_intent_id' => $paymentIntentId,
            'amount_centavos'   => $resAttrs['amount'] ?? null,
            'source_type'       => $resAttrs['source']['type'] ?? null,
            'remarks'           => $resAttrs['remarks'] ?? null,
            'description'       => $resAttrs['description'] ?? null,
        ]);

        return [
            'event_type'        => $eventType,
            'resource_type'     => $resource['type'] ?? null,
            'resource_id'       => $resource['id'] ?? null,
            'link_id'           => $linkId,
            'payment_intent_id' => $paymentIntentId,
            'resource_attrs'    => $resAttrs,
        ];
    }
}
