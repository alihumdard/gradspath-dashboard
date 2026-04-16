<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;
use Modules\Auth\database\seeders\RolePermissionSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $this->call(InstitutionsSeeder::class);
        $this->call(ProgramsSeeder::class);
        $this->call(ServiceConfigSeeder::class);
        $this->call(StudentsSeeder::class);
        $this->call(MentorsSeeder::class);
        $this->call(MentorAvailabilitySeeder::class);
        $this->call(OfficeHoursSeeder::class);
        $this->call(BookingsSeeder::class);
        $this->call(MentorNotesSeeder::class);
        $this->call(FeedbackSeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');
    }
}
