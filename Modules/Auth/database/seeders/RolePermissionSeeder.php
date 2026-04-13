<?php

namespace Modules\Auth\database\seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all permissions ────────────────────────────────────────────
        $permissions = [
            // Discovery
            'discovery.read',

            // Bookings
            'booking.create',
            'booking.cancel',
            'booking.view_own',

            // Feedback
            'feedback.create',
            'mentor_feedback.create',

            // Mentor Notes
            'mentor_notes.manage_own',

            // Mentor Profile / Services
            'mentor_profile.manage_own',
            'mentor_services.manage',

            // Office Hours
            'office_hours.manage',

            // Support
            'support.create',
            'support.read_own',

            // Credits / Payments
            'credits.read',
            'credits.purchase',

            // Files
            'files.upload',
            'files.delete_own',

            // Admin — Analytics
            'admin.analytics.read',

            // Admin — User Management
            'admin.users.manage',
            'admin.mentors.approve',

            // Admin — Content
            'admin.feedback.moderate',
            'admin.institutions.manage',
            'admin.pricing.manage',
            'admin.notes.view',

            // Admin — Support
            'admin.support.manage',

            // Admin — Logs
            'admin.logs.read',

            // Admin — Credits
            'admin.credits.adjust',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Create roles & assign permissions ─────────────────────────────────

        // Student: can browse, book, give feedback, upload, buy credits
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->syncPermissions([
            'discovery.read',
            'booking.create',
            'booking.cancel',
            'booking.view_own',
            'feedback.create',
            'support.create',
            'support.read_own',
            'credits.read',
            'credits.purchase',
            'files.upload',
            'files.delete_own',
        ]);

        // Mentor: can manage profile, services, schedule, notes, give feedback
        $mentor = Role::firstOrCreate(['name' => 'mentor', 'guard_name' => 'web']);
        $mentor->syncPermissions([
            'discovery.read',
            'booking.view_own',
            'mentor_feedback.create',
            'mentor_profile.manage_own',
            'mentor_services.manage',
            'office_hours.manage',
            'mentor_notes.manage_own',
            'support.create',
            'support.read_own',
            'files.upload',
            'files.delete_own',
        ]);

        // Admin: all permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        if ($this->command) {
            $this->command->info('Roles and permissions seeded successfully.');
            $this->command->table(
                ['Role', 'Permissions'],
                [
                    ['student', $student->permissions->count()],
                    ['mentor',  $mentor->permissions->count()],
                    ['admin',   $admin->permissions->count()],
                ]
            );
        }
    }
}
