<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payments\app\Models\ServiceConfig;

class ServiceConfigSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'service_name' => 'Free Consultation',
                'service_slug' => 'free_consultation',
                'duration_minutes' => 30,
                'is_active' => true,
                'price_1on1' => 0,
                'price_1on3_per_person' => null,
                'price_1on3_total' => null,
                'price_1on5_per_person' => null,
                'price_1on5_total' => null,
                'is_office_hours' => false,
                'office_hours_subscription_price' => null,
                'credit_cost_1on1' => 0,
                'credit_cost_1on3' => 0,
                'credit_cost_1on5' => 0,
                'sort_order' => 1,
            ],
            [
                'service_name' => 'Program Insights',
                'service_slug' => 'program_insights',
                'duration_minutes' => 60,
                'is_active' => true,
                'price_1on1' => 65,
                'price_1on3_per_person' => 58.49,
                'price_1on3_total' => 175.48,
                'price_1on5_per_person' => 51.99,
                'price_1on5_total' => 259.97,
                'is_office_hours' => false,
                'office_hours_subscription_price' => null,
                'credit_cost_1on1' => 1,
                'credit_cost_1on3' => 1,
                'credit_cost_1on5' => 1,
                'sort_order' => 2,
            ],
            [
                'service_name' => 'Interview Prep',
                'service_slug' => 'interview_prep',
                'duration_minutes' => 60,
                'is_active' => true,
                'price_1on1' => 70,
                'price_1on3_per_person' => 62.99,
                'price_1on3_total' => 188.97,
                'price_1on5_per_person' => 55.99,
                'price_1on5_total' => 279.95,
                'is_office_hours' => false,
                'office_hours_subscription_price' => null,
                'credit_cost_1on1' => 1,
                'credit_cost_1on3' => 1,
                'credit_cost_1on5' => 1,
                'sort_order' => 3,
            ],
            [
                'service_name' => 'Application Review',
                'service_slug' => 'application_review',
                'duration_minutes' => 60,
                'is_active' => true,
                'price_1on1' => 60,
                'price_1on3_per_person' => null,
                'price_1on3_total' => null,
                'price_1on5_per_person' => null,
                'price_1on5_total' => null,
                'is_office_hours' => false,
                'office_hours_subscription_price' => null,
                'credit_cost_1on1' => 1,
                'credit_cost_1on3' => 0,
                'credit_cost_1on5' => 0,
                'sort_order' => 4,
            ],
            [
                'service_name' => 'Office Hours',
                'service_slug' => 'office_hours',
                'duration_minutes' => 45,
                'is_active' => true,
                'price_1on1' => null,
                'price_1on3_per_person' => null,
                'price_1on3_total' => null,
                'price_1on5_per_person' => null,
                'price_1on5_total' => null,
                'is_office_hours' => true,
                'office_hours_subscription_price' => 200,
                'credit_cost_1on1' => 1,
                'credit_cost_1on3' => 1,
                'credit_cost_1on5' => 1,
                'sort_order' => 5,
            ],
        ];

        foreach ($services as $service) {
            ServiceConfig::query()->updateOrCreate(
                ['service_slug' => $service['service_slug']],
                $service
            );
        }
    }
}
