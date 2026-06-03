<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;

class AdminUsersSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Admin User',
                'email' => 'admin@admin.com',
                'password' => 'password',
            ],
            [
                'name' => 'Tyler Cogan',
                'email' => 'tcogan5@gmail.com',
                'password' => 'Password123!',
            ],
        ];

        foreach ($admins as $adminData) {
            $admin = User::query()->updateOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'password' => Hash::make($adminData['password']),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $admin->assignRole('admin');
        }
    }
}
