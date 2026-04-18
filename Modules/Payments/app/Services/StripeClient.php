<?php

namespace Modules\Payments\app\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class StripeClient
{
    public function __construct(private readonly HttpFactory $http) {}

    public function createCheckoutSession(array $payload): array
    {
        $response = $this->request()->withBody(
            http_build_query($this->normalize($payload)),
            'application/x-www-form-urlencoded'
        )->post($this->endpoint('/checkout/sessions'));

        $data = $response->throw()->json();

        return is_array($data) ? $data : [];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $response = $this->request()->get($this->endpoint('/checkout/sessions/'.$sessionId), [
            'expand' => ['payment_intent'],
        ]);

        $data = $response->throw()->json();

        return is_array($data) ? $data : [];
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

    private function request()
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

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.stripe.api_base'), '/').$path;
    }

    private function normalize(array $payload): array
    {
        return Arr::undot(Arr::dot($payload));
    }
}
