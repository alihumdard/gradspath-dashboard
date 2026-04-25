<?php

namespace Modules\Payments\app\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;

class StripeClient
{
    public function __construct(private readonly HttpFactory $http) {}

    public function createConnectedAccount(array $payload): array
    {
        return $this->post('/accounts', $payload);
    }

    public function retrieveConnectedAccount(string $accountId): array
    {
        return $this->get('/accounts/'.$accountId);
    }

    public function createAccountLink(array $payload): array
    {
        return $this->post('/account_links', $payload);
    }

    public function createCheckoutSession(array $payload): array
    {
        return $this->post('/checkout/sessions', $payload);
    }

    public function createTransfer(array $payload): array
    {
        return $this->post('/transfers', $payload);
    }

    public function createTransferReversal(string $transferId, array $payload): array
    {
        return $this->post('/transfers/'.$transferId.'/reversals', $payload);
    }

    public function createRefund(array $payload): array
    {
        return $this->post('/refunds', $payload);
    }

    public function retrieveTransfer(string $transferId): array
    {
        return $this->get('/transfers/'.$transferId);
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->get('/checkout/sessions/'.$sessionId, [
            'expand' => ['payment_intent'],
        ]);
    }

    public function verifyWebhookSignature(string $payload, ?string $signatureHeader): void
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            return;
        }

        if (!$signatureHeader) {
            throw new \RuntimeException('Missing Stripe signature.');
        }

        $parts = collect(explode(',', $signatureHeader))
            ->mapWithKeys(function (string $part) {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (!$timestamp || !$signature) {
            throw new \RuntimeException('Invalid Stripe signature header.');
        }

        if (abs(Carbon::now()->timestamp - (int) $timestamp) > 300) {
            throw new \RuntimeException('Expired Stripe signature timestamp.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        if (!hash_equals($expected, (string) $signature)) {
            throw new \RuntimeException('Stripe signature verification failed.');
        }
    }

    private function request(): PendingRequest
    {
        $secret = (string) config('services.stripe.secret_key');

        if ($secret === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        return $this->http
            ->asForm()
            ->withBasicAuth($secret, '')
            ->acceptJson();
    }

    private function get(string $path, array $query = []): array
    {
        return $this->json(
            $this->request()->get($this->endpoint($path), $this->normalizeForStripe($query))
        );
    }

    private function post(string $path, array $payload): array
    {
        return $this->json(
            $this->request()->post($this->endpoint($path), $this->normalizeForStripe($payload))
        );
    }

    private function json(Response $response): array
    {
        return $response->throw()->json();
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.stripe.api_base'), '/').$path;
    }

    private function normalizeForStripe(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeForStripe($item);
        }

        return $value;
    }
}
