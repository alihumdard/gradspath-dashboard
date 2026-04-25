<?php

return [
    'mentor_payout_percent_default' => (float) env('MENTOR_PAYOUT_PERCENT_DEFAULT', 70),
    'mentor_payout_retry_limit' => (int) env('MENTOR_PAYOUT_RETRY_LIMIT', 5),
];
