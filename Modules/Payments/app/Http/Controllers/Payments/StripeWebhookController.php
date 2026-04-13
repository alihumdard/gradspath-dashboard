<?php

namespace Modules\Payments\app\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payments\app\Jobs\ProcessStripeWebhookJob;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        ProcessStripeWebhookJob::dispatch($request->all());

        return response()->json([
            'message' => 'Webhook received and queued for processing.',
        ], 202);
    }
}
