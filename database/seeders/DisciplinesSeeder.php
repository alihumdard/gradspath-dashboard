<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institutions\app\Models\Discipline;

class DisciplinesSeeder extends Seeder
{
    public function run(): void
    {
        $disciplines = [
            // Graduate Programs
            [
                'name' => 'MBA',
                'type' => 'graduate',
                'description' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'Law',
                'type' => 'graduate',
                'description' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'Therapy Pathways',
                'type' => 'graduate',
                'description' => 'CMHC, MSW, MFT, Clinical Psych',
                'sort_order' => 3,
            ],
            // Professionals
            [
                'name' => 'Therapy',
                'type' => 'professional',
                'description' => 'real world guidance, licensure pathway planning, application support',
                'sort_order' => 1,
            ],
            // General
            [
                'name' => 'All categories in one view',
                'type' => 'general',
                'description' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'Clear labels and structured layout',
                'type' => 'general',
                'description' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'Built to expand as Grads Paths grows',
                'type' => 'general',
                'description' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'COMING SOON placeholders included',
                'type' => 'general',
                'description' => null,
                'sort_order' => 4,
            ],
        ];

        foreach ($disciplines as $seed) {
            Discipline::query()->updateOrCreate(
                [
                    'name' => $seed['name'],
                    'type' => $seed['type'],
                ],
                $seed
            );
        }
    }
}
