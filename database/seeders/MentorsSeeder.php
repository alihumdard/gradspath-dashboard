<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;
use Modules\Institutions\app\Models\University;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class MentorsSeeder extends Seeder
{
    public function run(): void
    {
        $mentors = [
            [
                'name' => 'Sarah Kim',
                'email' => 'sarah.mentor@gradspath.edu',
                'title' => 'MBA Mentor',
                'mentor_type' => 'graduate',
                'program_type' => 'mba',
                'institution' => 'Harvard University',
                'office_hours_schedule' => 'Every Tuesday at 5 PM EST',
                'status' => 'active',
                'is_featured' => true,
                'service_slugs' => ['free_consultation', 'program_insights', 'interview_prep', 'office_hours'],
            ],
            [
                'name' => 'Daniel Brooks',
                'email' => 'daniel.mentor@gradspath.edu',
                'title' => 'Law Admissions Mentor',
                'mentor_type' => 'professional',
                'program_type' => 'law',
                'institution' => 'New York University',
                'office_hours_schedule' => 'Every Thursday at 7 PM EST',
                'status' => 'active',
                'is_featured' => true,
                'service_slugs' => ['free_consultation', 'application_review', 'interview_prep', 'office_hours'],
            ],
            [
                'name' => 'Leah Morris',
                'email' => 'leah.mentor@gradspath.edu',
                'title' => 'Therapy Pathway Mentor',
                'mentor_type' => 'graduate',
                'program_type' => 'cmhc',
                'institution' => 'Boston College',
                'office_hours_schedule' => 'Every Wednesday at 6 PM EST',
                'status' => 'active',
                'is_featured' => false,
                'service_slugs' => ['free_consultation', 'program_insights', 'application_review'],
            ],
            [
                'name' => 'Abdul Rauf',
                'email' => 'abdul.rauf@uol.edu.pk',
                'title' => 'MBA Admissions Mentor',
                'mentor_type' => 'graduate',
                'program_type' => 'mba',
                'institution' => 'University of Lahore',
                'office_hours_schedule' => 'Every Friday at 8 PM PKT',
                'status' => 'active',
                'is_featured' => false,
                'service_slugs' => ['program_insights', 'interview_prep', 'office_hours'],
            ],
        ];

        foreach ($mentors as $seed) {
            $user = User::query()->updateOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['name'],
                    'password' => Hash::make('Password123!'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole('mentor');

            $university = University::query()
                ->where('name', $seed['institution'])
                ->orWhere('display_name', $seed['institution'])
                ->first();

            $mentor = Mentor::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'university_id' => $university?->id,
                    'title' => $seed['title'],
                    'grad_school_display' => $university?->display_name ?? $seed['institution'],
                    'mentor_type' => $seed['mentor_type'],
                    'program_type' => $seed['program_type'],
                    'bio' => $seed['name'] . ' helps students navigate applications with practical guidance, program strategy, and interview preparation.',
                    'description' => $seed['name'] . ' provides structured admissions guidance, first-hand program insight, and practical next steps for students preparing stronger applications.',
                    'office_hours_schedule' => $seed['office_hours_schedule'],
                    'edu_email' => $seed['email'],
                    'calendly_link' => 'https://calendly.com/' . str_replace(['@', '.'], ['-', '-'], $seed['email']),
                    'is_featured' => $seed['is_featured'],
                    'status' => $seed['status'],
                    'approved_at' => now(),
                ]
            );

            $serviceIds = ServiceConfig::query()
                ->whereIn('service_slug', $seed['service_slugs'])
                ->pluck('id')
                ->all();

            $pivot = [];
            foreach (array_values($serviceIds) as $index => $serviceId) {
                $pivot[$serviceId] = [
                    'is_active' => true,
                    'sort_order' => $index,
                ];
            }

            if ($pivot !== []) {
                $mentor->services()->sync($pivot);
            }
        }
    }
}
