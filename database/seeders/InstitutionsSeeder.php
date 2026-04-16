<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;

class InstitutionsSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            [
                'name' => 'Harvard University',
                'display_name' => 'Harvard',
                'country' => 'US',
                'alpha_two_code' => 'US',
                'domains' => ['harvard.edu'],
                'web_pages' => ['https://www.harvard.edu'],
                'state_province' => 'Massachusetts',
                'is_active' => true,
            ],
            [
                'name' => 'Boston College',
                'display_name' => 'BC',
                'country' => 'US',
                'alpha_two_code' => 'US',
                'domains' => ['bc.edu'],
                'web_pages' => ['https://www.bc.edu'],
                'state_province' => 'Massachusetts',
                'is_active' => true,
            ],
            [
                'name' => 'New York University',
                'display_name' => 'NYU',
                'country' => 'US',
                'alpha_two_code' => 'US',
                'domains' => ['nyu.edu'],
                'web_pages' => ['https://www.nyu.edu'],
                'state_province' => 'New York',
                'is_active' => true,
            ],
            [
                'name' => 'University of Connecticut',
                'display_name' => 'UConn',
                'country' => 'US',
                'alpha_two_code' => 'US',
                'domains' => ['uconn.edu'],
                'web_pages' => ['https://www.uconn.edu'],
                'state_province' => 'Connecticut',
                'is_active' => true,
            ],
            [
                'name' => 'University of Lahore',
                'display_name' => 'UOL',
                'country' => 'PK',
                'alpha_two_code' => 'PK',
                'domains' => ['uol.edu.pk'],
                'web_pages' => ['https://www.uol.edu.pk'],
                'state_province' => 'Punjab',
                'is_active' => true,
            ],
        ];

        foreach ($institutions as $item) {
            University::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }

        $programs = [
            'Harvard University' => [
                ['program_name' => 'MBA', 'program_type' => 'mba', 'tier' => 'elite', 'duration_months' => 24],
                ['program_name' => 'Clinical Psychology', 'program_type' => 'clinical_psy', 'tier' => 'elite', 'duration_months' => 60],
            ],
            'Boston College' => [
                ['program_name' => 'MSW', 'program_type' => 'msw', 'tier' => 'regional', 'duration_months' => 24],
                ['program_name' => 'MFT', 'program_type' => 'mft', 'tier' => 'regional', 'duration_months' => 30],
            ],
            'New York University' => [
                ['program_name' => 'Law', 'program_type' => 'law', 'tier' => 'elite', 'duration_months' => 36],
                ['program_name' => 'CMHC', 'program_type' => 'cmhc', 'tier' => 'top', 'duration_months' => 30],
            ],
            'University of Connecticut' => [
                ['program_name' => 'Therapy Pathways', 'program_type' => 'therapy', 'tier' => 'regional', 'duration_months' => 24],
                ['program_name' => 'MBA', 'program_type' => 'mba', 'tier' => 'regional', 'duration_months' => 24],
            ],
            'University of Lahore' => [
                ['program_name' => 'MBA', 'program_type' => 'mba', 'tier' => 'regional', 'duration_months' => 24],
                ['program_name' => 'Law', 'program_type' => 'law', 'tier' => 'regional', 'duration_months' => 36],
            ],
        ];

        foreach ($programs as $universityName => $items) {
            $university = University::query()->where('name', $universityName)->first();

            if (!$university) {
                continue;
            }

            foreach ($items as $program) {
                UniversityProgram::query()->updateOrCreate(
                    [
                        'university_id' => $university->id,
                        'program_name' => $program['program_name'],
                    ],
                    [
                        'program_type' => $program['program_type'],
                        'tier' => $program['tier'],
                        'description' => $program['program_name'] . ' program at ' . $university->display_name,
                        'duration_months' => $program['duration_months'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
