<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Helpers;

final class StripeGateway
{
    public static function enabled(): bool
    {
        return ((string) Helpers::setting('stripe_enabled', '0')) === '1'
            && self::secretKey() !== ''
            && self::publishableKey() !== '';
    }

    public static function mode(): string
    {
        return ((string) Helpers::setting('stripe_mode', 'sandbox')) === 'live' ? 'live' : 'sandbox';
    }

    public static function publishableKey(): string
    {
        return self::mode() === 'live'
            ? trim((string) Helpers::setting('stripe_live_publishable_key', ''))
            : trim((string) Helpers::setting('stripe_sandbox_publishable_key', ''));
    }

    public static function secretKey(): string
    {
        return self::mode() === 'live'
            ? trim((string) Helpers::setting('stripe_live_secret_key', ''))
            : trim((string) Helpers::setting('stripe_sandbox_secret_key', ''));
    }

    public static function webhookSecret(): string
    {
        return self::mode() === 'live'
            ? trim((string) Helpers::setting('stripe_live_webhook_secret', ''))
            : trim((string) Helpers::setting('stripe_sandbox_webhook_secret', ''));
    }

    public static function currencyCode(): string
    {
        $symbol = trim((string) Helpers::setting('currency_symbol', '£'));
        return match ($symbol) {
            '$' => 'usd',
            '€' => 'eur',
            default => 'gbp',
        };
    }

    /**
     * @param array<string, mixed> $order
     * @return array{id: string, url: string, payment_intent: ?string}
     */
    public static function createCheckoutSession(array $order): array
    {
        $number = (string) ($order['order_number'] ?? '');
        $pence = (int) round(((float) ($order['total_amount'] ?? 0)) * 100);
        if ($number === '' || $pence < 30) {
            throw new \RuntimeException('Order total is too small to charge by card.');
        }

        $success = Helpers::baseUrl('checkout/stripe/return') . '?session_id={CHECKOUT_SESSION_ID}';
        $cancel = Helpers::baseUrl('checkout/stripe/cancel') . '?order=' . rawurlencode($number);

        $session = self::request('POST', '/v1/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $success,
            'cancel_url' => $cancel,
            'client_reference_id' => $number,
            'customer_email' => (string) ($order['customer_email'] ?? ''),
            'expires_at' => time() + 30 * 60,
            'payment_method_types' => ['card'],
            'metadata' => [
                'order_number' => $number,
                'order_id' => (string) ($order['id'] ?? ''),
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => self::currencyCode(),
                    'unit_amount' => $pence,
                    'product_data' => [
                        'name' => (string) Helpers::setting('store_name', 'Kraved') . ' order ' . $number,
                    ],
                ],
            ]],
        ]);

        $url = (string) ($session['url'] ?? '');
        $id = (string) ($session['id'] ?? '');
        if ($url === '' || $id === '') {
            throw new \RuntimeException('Stripe did not return a checkout URL.');
        }

        $intent = $session['payment_intent'] ?? null;
        return [
            'id' => $id,
            'url' => $url,
            'payment_intent' => is_string($intent) ? $intent : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function retrieveSession(string $sessionId): array
    {
        if ($sessionId === '' || !str_starts_with($sessionId, 'cs_')) {
            throw new \RuntimeException('Invalid Stripe session.');
        }
        return self::request('GET', '/v1/checkout/sessions/' . rawurlencode($sessionId));
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseWebhook(string $payload, string $signatureHeader): array
    {
        $secret = self::webhookSecret();
        if ($secret === '') {
            throw new \RuntimeException('Stripe webhook secret is not set.');
        }
        self::verifySignature($payload, $signatureHeader, $secret);
        $event = json_decode($payload, true);
        if (!is_array($event) || empty($event['type'])) {
            throw new \RuntimeException('Invalid Stripe webhook payload.');
        }
        return $event;
    }

    public static function maskKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }
        if (strlen($key) < 12) {
            return str_repeat('•', 8);
        }
        return substr($key, 0, 7) . str_repeat('•', 8) . substr($key, -4);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function request(string $method, string $path, array $params = []): array
    {
        $secret = self::secretKey();
        if ($secret === '') {
            throw new \RuntimeException('Stripe secret key is missing. Add it in Admin → Settings.');
        }

        $url = 'https://api.stripe.com' . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Could not start Stripe request.');
        }

        $headers = [
            'Authorization: Bearer ' . $secret,
            'Stripe-Version: 2024-06-20',
        ];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || !is_string($body)) {
            throw new \RuntimeException('Could not reach Stripe. Please try again.');
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Unexpected response from Stripe.');
        }
        if ($status >= 400) {
            $message = (string) ($json['error']['message'] ?? 'Stripe payment failed.');
            throw new \RuntimeException($message);
        }
        return $json;
    }

    private static function verifySignature(string $payload, string $header, string $secret): void
    {
        $parts = [];
        foreach (explode(',', $header) as $item) {
            [$k, $v] = array_pad(explode('=', trim($item), 2), 2, '');
            $parts[$k][] = $v;
        }
        $timestamp = (string) ($parts['t'][0] ?? '');
        $signatures = $parts['v1'] ?? [];
        if ($timestamp === '' || $signatures === []) {
            throw new \RuntimeException('Missing Stripe signature.');
        }
        if (abs(time() - (int) $timestamp) > 300) {
            throw new \RuntimeException('Stripe webhook timestamp is too old.');
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, (string) $sig)) {
                return;
            }
        }
        throw new \RuntimeException('Invalid Stripe signature.');
    }
}
