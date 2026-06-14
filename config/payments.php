<?php

return [
    'mentor_payout_retry_limit' => (int) env('MENTOR_PAYOUT_RETRY_LIMIT', 5),
    'stripe_webhook_retention_days' => (int) env('STRIPE_WEBHOOK_RETENTION_DAYS', 90),
    'office_hours' => [
        'credit_pack_price' => 200.00,
        'credit_pack_credits' => 5,
        'credit_cost_per_attendee' => 1,
        'max_attendees' => 3,
    ],
];
