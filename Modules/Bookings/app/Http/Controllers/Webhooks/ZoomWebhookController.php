<?php

namespace Modules\Bookings\app\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Bookings\app\Services\ZoomWebhookService;

class ZoomWebhookController extends Controller
{
    public function __construct(private readonly ZoomWebhookService $webhooks) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        try {
            $this->webhooks->verifyRequest($payload, (string) $request->header('x-zm-signature'), (string) $request->header('x-zm-request-timestamp'));
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded) || $decoded === []) {
            return response()->json([
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        if ((string) ($decoded['event'] ?? '') === 'endpoint.url_validation') {
            return response()->json($this->webhooks->validationResponse($decoded));
        }

        $this->webhooks->process($decoded);

        return response()->json([
            'message' => 'Webhook received.',
        ], 202);
    }
}
