<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;
use Modules\Institutions\app\Models\University;

class StudentsSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'name' => 'Emma Chen',
                'email' => 'emma.student@gradspath.edu',
                'institution' => 'Boston College',
                'program_level' => 'undergrad',
                'program_type' => 'mba',
                'credit_balance' => 12,
            ],
            [
                'name' => 'Alex Rivera',
                'email' => 'alex.student@gradspath.edu',
                'institution' => 'New York University',
                'program_level' => 'grad',
                'program_type' => 'law',
                'credit_balance' => 8,
            ],
            [
                'name' => 'Mia Patel',
                'email' => 'mia.student@gradspath.edu',
                'institution' => 'University of Connecticut',
                'program_level' => 'professional',
                'program_type' => 'cmhc',
                'credit_balance' => 15,
            ],
        ];

        foreach ($students as $seed) {
            $user = User::query()->updateOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['name'],
                    'password' => Hash::make('Password123!'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole('student');

            $user->setting()->updateOrCreate(
                [],
                [
                    'theme' => 'light',
                    'email_notifications' => true,
                    'sms_notifications' => false,
                ]
            );

            $user->credit()->updateOrCreate(
                [],
                ['balance' => $seed['credit_balance']]
            );

            $university = University::query()
                ->where('name', $seed['institution'])
                ->orWhere('display_name', $seed['institution'])
                ->first();

            $user->studentProfile()->updateOrCreate(
                [],
                [
                    'university_id' => $university?->id,
                    'institution_text' => $seed['institution'],
                    'program_level' => $seed['program_level'],
                    'program_type' => $seed['program_type'],
                ]
            );
        }
    }
}
