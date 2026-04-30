<?php

namespace Modules\Auth\app\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;
use Modules\Institutions\app\Models\University;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $role = $this->resolveRegistrationRole($data['role'] ?? null);
            Role::findOrCreate($role, config('auth-module.guard', 'web'));
            $user->assignRole($role);

            $institutionText = $this->normalizeInstitution($data['institution'] ?? null);
            $university = $this->resolveUniversity($data['institution_id'] ?? null, $institutionText);

            $user->credit()->firstOrCreate([], ['balance' => 0]);

            $user->setting()->firstOrCreate(
                [],
                ['theme' => 'light', 'email_notifications' => true, 'sms_notifications' => false, 'timezone' => null]
            );

            if ($role === 'student') {
                $user->studentProfile()->firstOrCreate(
                    [],
                    [
                        'university_id' => $university?->id,
                        'institution_text' => $institutionText,
                        'program_level' => $data['program_level'] ?? null,
                    ]
                );
            }

            if ($role === 'mentor') {
                $mentorType = $this->resolveMentorType($data);

                $user->mentor()->firstOrCreate(
                    [],
                    [
                        'mentor_type' => $mentorType,
                        'status' => 'active',
                        'approved_at' => now(),
                        'university_id' => $university?->id,
                        'grad_school_display' => $institutionText,
                    ]
                );
            }

            return $user;
        });
    }

    private function resolveMentorType(array $data): string
    {
        if (in_array(($data['mentor_type'] ?? null), ['graduate', 'professional'], true)) {
            return $data['mentor_type'];
        }

        return ($data['program_level'] ?? null) === 'professional' ? 'professional' : 'graduate';
    }

    private function resolveRegistrationRole(?string $role): string
    {
        $defaultRole = config('auth-module.registration.default_role', 'student');
        $allowedRoles = config('auth-module.registration.allowed_roles', [$defaultRole]);

        return in_array($role, $allowedRoles, true) ? $role : $defaultRole;
    }

    private function resolveUniversity(mixed $institutionId, ?string $institution): ?University
    {
        if ($institutionId !== null && $institutionId !== '') {
            return University::query()
                ->where('is_active', true)
                ->find((int) $institutionId);
        }

        if ($institution === null) {
            return null;
        }

        $normalized = mb_strtolower($institution);

        return University::query()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->orWhereRaw('LOWER(display_name) = ?', [$normalized])
            ->first();
    }

    private function normalizeInstitution(?string $institution): ?string
    {
        $normalized = trim((string) $institution);

        return $normalized === '' ? null : $normalized;
    }

    public function loginUserPortal(array $credentials, bool $remember = false): ?User
    {
        if (!Auth::attempt($credentials, $remember)) {
            return null;
        }

        $user = Auth::user();
        if (!$user || !$user->is_active) {
            Auth::logout();
            return null;
        }

        if ($user->hasRole('admin')) {
            Auth::logout();
            return null;
        }

        return $user;
    }

    public function loginAdminPortal(array $credentials, bool $remember = false): ?User
    {
        if (!Auth::attempt($credentials, $remember)) {
            return null;
        }

        $user = Auth::user();
        if (!$user || !$user->is_active || !$user->hasRole('admin')) {
            Auth::logout();
            return null;
        }

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
