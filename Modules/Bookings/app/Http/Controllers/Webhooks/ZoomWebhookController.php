<?php

namespace Modules\Bookings\app\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Services\ZoomWebhookService;

class ZoomWebhookController extends Controller
{
    public function __construct(private readonly ZoomWebhookService $webhooks) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $decoded = json_decode($payload, true);

        Log::info('Zoom webhook received.', [
            'event' => is_array($decoded) ? ($decoded['event'] ?? null) : null,
            'event_id' => is_array($decoded) ? ($decoded['event_id'] ?? null) : null,
            'payload_bytes' => strlen($payload),
            'signature_present' => filled($request->header('x-zm-signature')),
            'timestamp_present' => filled($request->header('x-zm-request-timestamp')),
            'meeting_id' => is_array($decoded)
                ? (data_get($decoded, 'payload.object.id')
                    ?? data_get($decoded, 'payload.object.uuid')
                    ?? data_get($decoded, 'payload.object.meeting_id'))
                : null,
            'host_email_present' => is_array($decoded) && filled(data_get($decoded, 'payload.object.host_email')),
            'participant_email_present' => is_array($decoded) && (
                filled(data_get($decoded, 'payload.object.participant.email'))
                || filled(data_get($decoded, 'payload.object.participant.user_email'))
            ),
        ]);

        try {
            $this->webhooks->verifyRequest($payload, (string) $request->header('x-zm-signature'), (string) $request->header('x-zm-request-timestamp'));
        } catch (\RuntimeException $exception) {
            Log::warning('Zoom webhook signature verification failed.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

        if (! is_array($decoded) || $decoded === []) {
            Log::warning('Zoom webhook payload could not be decoded.');

            return response()->json([
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        if ((string) ($decoded['event'] ?? '') === 'endpoint.url_validation') {
            Log::info('Zoom webhook endpoint validation requested.', [
                'plain_token_present' => filled(data_get($decoded, 'payload.plainToken')),
            ]);

            return response()->json($this->webhooks->validationResponse($decoded));
        }

        $this->webhooks->process($decoded);

        return response()->json([
            'message' => 'Webhook received.',
        ], 202);
    }
}
