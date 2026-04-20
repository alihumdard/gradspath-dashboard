<?php

namespace Modules\Payments\app\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payments\app\Jobs\ProcessStripeWebhookJob;
use Modules\Payments\app\Services\StripeClient;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        try {
            $this->stripe->verifyWebhookSignature($payload, $request->header('Stripe-Signature'));
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            $decoded = $request->all();
        }

        if (!is_array($decoded) || $decoded === []) {
            return response()->json([
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        ProcessStripeWebhookJob::dispatch($decoded);

        return response()->json([
            'message' => 'Webhook received and queued for processing.',
        ], 202);
    }
}
