<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;

class ProgramsSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [
                'university_name' => 'Harvard University',
                'program_type' => 'mba',
                'program_name' => 'MBA (Harvard Business School)',
                'tier' => 'elite',
                'description' => 'Master of Business Administration with focus on leadership and strategy.',
                'duration_months' => 24,
            ],
            [
                'university_name' => 'Harvard University',
                'program_type' => 'law',
                'program_name' => 'JD (Harvard Law School)',
                'tier' => 'elite',
                'description' => 'Juris Doctor with emphasis on constitutional law and policy.',
                'duration_months' => 36,
            ],
            [
                'university_name' => 'New York University',
                'program_type' => 'law',
                'program_name' => 'JD (NYU School of Law)',
                'tier' => 'elite',
                'description' => 'Juris Doctor with focus on international law and corporate practice.',
                'duration_months' => 36,
            ],
            [
                'university_name' => 'New York University',
                'program_type' => 'mba',
                'program_name' => 'MBA (Stern School of Business)',
                'tier' => 'top',
                'description' => 'Master of Business Administration with specialization in finance.',
                'duration_months' => 24,
            ],
            [
                'university_name' => 'Boston College',
                'program_type' => 'cmhc',
                'program_name' => 'M.Ed in Counseling (Community Mental Health Counseling)',
                'tier' => 'regional',
                'description' => 'Master of Education with focus on mental health and community support.',
                'duration_months' => 30,
            ],
            [
                'university_name' => 'Boston College',
                'program_type' => 'mft',
                'program_name' => 'M.Ed in Marriage and Family Therapy',
                'tier' => 'regional',
                'description' => 'Master of Education specializing in family systems and therapy.',
                'duration_months' => 24,
            ],
            [
                'university_name' => 'Boston College',
                'program_type' => 'msw',
                'program_name' => 'MSW (Master of Social Work)',
                'tier' => 'regional',
                'description' => 'Master of Social Work with clinical and organizational practice pathways.',
                'duration_months' => 24,
            ],
            [
                'university_name' => 'Stanford University',
                'program_type' => 'mba',
                'program_name' => 'MBA (Stanford Graduate School of Business)',
                'tier' => 'elite',
                'description' => 'Master of Business Administration with emphasis on innovation and entrepreneurship.',
                'duration_months' => 24,
            ],
            [
                'university_name' => 'Stanford University',
                'program_type' => 'other',
                'program_name' => 'PhD in Computer Science',
                'tier' => 'elite',
                'description' => 'Doctoral research program in artificial intelligence and systems.',
                'duration_months' => 60,
            ],
            [
                'university_name' => 'Yale University',
                'program_type' => 'law',
                'program_name' => 'JD (Yale School of Law)',
                'tier' => 'elite',
                'description' => 'Juris Doctor with focus on civil rights and public interest law.',
                'duration_months' => 36,
            ],
            [
                'university_name' => 'Yale University',
                'program_type' => 'clinical_psy',
                'program_name' => 'PhD in Clinical Psychology',
                'tier' => 'elite',
                'description' => 'Doctoral program with training in evidence-based clinical practice.',
                'duration_months' => 60,
            ],
            [
                'university_name' => 'University of Pennsylvania',
                'program_type' => 'mba',
                'program_name' => 'MBA (Wharton School)',
                'tier' => 'elite',
                'description' => 'Master of Business Administration with global economics focus.',
                'duration_months' => 24,
            ],
            [
                'university_name' => 'University of Pennsylvania',
                'program_type' => 'msw',
                'program_name' => 'MSW (School of Social Policy & Practice)',
                'tier' => 'top',
                'description' => 'Master of Social Work with policy and advocacy specializations.',
                'duration_months' => 24,
            ],
            [
                'university_name' => 'Columbia University',
                'program_type' => 'mba',
                'program_name' => 'MBA (Columbia Business School)',
                'tier' => 'elite',
                'description' => 'Master of Business Administration with international business focus.',
                'duration_months' => 24,
            ],
            [
                'university_name' => 'Columbia University',
                'program_type' => 'law',
                'program_name' => 'JD (Columbia Law School)',
                'tier' => 'elite',
                'description' => 'Juris Doctor with emphasis on corporate and commercial law.',
                'duration_months' => 36,
            ],
        ];

        foreach ($programs as $seed) {
            $university = University::query()
                ->where('name', $seed['university_name'])
                ->orWhere('display_name', $seed['university_name'])
                ->first();

            if (!$university) {
                continue;
            }

            UniversityProgram::query()->updateOrCreate(
                [
                    'university_id' => $university->id,
                    'program_type' => $seed['program_type'],
                    'program_name' => $seed['program_name'],
                ],
                [
                    'tier' => $seed['tier'],
                    'description' => $seed['description'] ?? null,
                    'duration_months' => $seed['duration_months'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}
