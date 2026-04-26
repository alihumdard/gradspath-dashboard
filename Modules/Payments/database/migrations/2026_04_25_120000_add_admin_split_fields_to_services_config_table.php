<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services_config', function (Blueprint $table) {
            $table->decimal('platform_fee_1on1', 8, 2)->nullable()->after('price_1on1');
            $table->decimal('mentor_payout_1on1', 8, 2)->nullable()->after('platform_fee_1on1');
            $table->decimal('platform_fee_1on3', 8, 2)->nullable()->after('price_1on3_total');
            $table->decimal('mentor_payout_1on3', 8, 2)->nullable()->after('platform_fee_1on3');
            $table->decimal('platform_fee_1on5', 8, 2)->nullable()->after('price_1on5_total');
            $table->decimal('mentor_payout_1on5', 8, 2)->nullable()->after('platform_fee_1on5');
            $table->decimal('office_hours_mentor_payout_per_attendee', 8, 2)->nullable()->after('office_hours_subscription_price');
        });

        collect($this->splitRules())
            ->each(function (array $sessionRules, string $serviceSlug): void {
                $updates = [];

                foreach (['1on1', '1on3', '1on5'] as $sessionType) {
                    $rule = $sessionRules[$sessionType] ?? null;

                    if (! is_array($rule)) {
                        continue;
                    }

                    $suffix = $sessionType;
                    $updates["platform_fee_{$suffix}"] = $rule['platform_fee'] ?? null;
                    $updates["mentor_payout_{$suffix}"] = $rule['mentor_share'] ?? null;
                }

                if ($updates !== []) {
                    DB::table('services_config')
                        ->where('service_slug', $serviceSlug)
                        ->update($updates);
                }
            });

        DB::table('services_config')
            ->where('is_office_hours', true)
            ->update([
                'office_hours_mentor_payout_per_attendee' => 15,
            ]);
    }

    public function down(): void
    {
        Schema::table('services_config', function (Blueprint $table) {
            $table->dropColumn([
                'platform_fee_1on1',
                'mentor_payout_1on1',
                'platform_fee_1on3',
                'mentor_payout_1on3',
                'platform_fee_1on5',
                'mentor_payout_1on5',
                'office_hours_mentor_payout_per_attendee',
            ]);
        });
    }

    private function splitRules(): array
    {
        return [
            'tutoring' => [
                '1on1' => ['platform_fee' => 27.00, 'mentor_share' => 43.00],
                '1on3' => ['platform_fee' => 90.00, 'mentor_share' => 98.97],
                '1on5' => ['platform_fee' => 165.00, 'mentor_share' => 114.95],
            ],
            'program_insights' => [
                '1on1' => ['platform_fee' => 23.00, 'mentor_share' => 42.00],
                '1on3' => ['platform_fee' => 83.00, 'mentor_share' => 92.47],
                '1on5' => ['platform_fee' => 146.00, 'mentor_share' => 113.95],
            ],
            'interview_prep' => [
                '1on1' => ['platform_fee' => 23.00, 'mentor_share' => 42.00],
                '1on3' => ['platform_fee' => 83.00, 'mentor_share' => 92.47],
                '1on5' => ['platform_fee' => 146.00, 'mentor_share' => 113.95],
            ],
            'application_review' => [
                '1on1' => ['platform_fee' => 23.00, 'mentor_share' => 37.00],
            ],
            'gap_year_planning' => [
                '1on1' => ['platform_fee' => 19.00, 'mentor_share' => 31.00],
            ],
        ];
    }
};
