<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('student', 'web');
});

function createAdminUser(): User
{
    $user = User::factory()->create([
        'email' => 'admin-programs-' . Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole('admin');

    return $user;
}

function createStudentUser(): User
{
    $user = User::factory()->create([
        'email' => 'student-programs-' . Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole('student');

    return $user;
}

function createUniversity(array $attributes = []): University
{
    return University::query()->create(array_merge([
        'name' => 'University ' . Str::random(6),
        'display_name' => 'University Display',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ], $attributes));
}

function programPayload(array $overrides = []): array
{
    $university = $overrides['university'] ?? createUniversity();

    unset($overrides['university']);

    return array_merge([
        'university_id' => $university->id,
        'program_name' => 'Master of Public Policy',
        'program_type' => 'other',
        'tier' => 'top',
        'duration_months' => 24,
        'description' => 'A policy leadership track.',
        'is_active' => '1',
        'notes' => 'Creating program from manual actions.',
        'manual_section' => 'programs',
        'manual_station' => 'program-create-station',
    ], $overrides);
}

it('admin can create a program with valid data', function () {
    $admin = createAdminUser();
    $university = createUniversity(['name' => 'Harvard University']);

    $this->actingAs($admin)
        ->post(route('admin.programs.store'), programPayload([
            'university' => $university,
            'program_name' => 'Master of Public Policy',
            'program_type' => 'other',
            'tier' => 'elite',
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('university_programs', [
        'university_id' => $university->id,
        'program_name' => 'Master of Public Policy',
        'program_type' => 'other',
        'tier' => 'elite',
        'duration_months' => 24,
        'is_active' => 1,
    ]);
});

it('create requires university_id', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)
        ->from(route('admin.dashboard'))
        ->post(route('admin.programs.store'), programPayload([
            'university_id' => null,
        ]));

    $response->assertRedirect(route('admin.dashboard'));
    $response->assertSessionHasErrors('university_id');
});

it('create requires valid program_type', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)
        ->from(route('admin.dashboard'))
        ->post(route('admin.programs.store'), programPayload([
            'program_type' => 'engineering',
        ]));

    $response->assertRedirect(route('admin.dashboard'));
    $response->assertSessionHasErrors('program_type');
});

it('create requires valid tier', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)
        ->from(route('admin.dashboard'))
        ->post(route('admin.programs.store'), programPayload([
            'tier' => 'tier-1',
        ]));

    $response->assertRedirect(route('admin.dashboard'));
    $response->assertSessionHasErrors('tier');
});

it('create rejects non existent university ids', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)
        ->from(route('admin.dashboard'))
        ->post(route('admin.programs.store'), programPayload([
            'university_id' => 999999,
        ]));

    $response->assertRedirect(route('admin.dashboard'));
    $response->assertSessionHasErrors('university_id');
});

it('create accepts nullable description', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.programs.store'), programPayload([
            'description' => null,
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('university_programs', [
        'program_name' => 'Master of Public Policy',
        'description' => null,
    ]);
});

it('create accepts nullable duration months', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.programs.store'), programPayload([
            'duration_months' => null,
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('university_programs', [
        'program_name' => 'Master of Public Policy',
        'duration_months' => null,
    ]);
});

it('create persists is_active true', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.programs.store'), programPayload([
            'program_name' => 'Active Program',
            'is_active' => '1',
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('university_programs', [
        'program_name' => 'Active Program',
        'is_active' => 1,
    ]);
});

it('create persists is_active false when unchecked', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.programs.store'), programPayload([
            'program_name' => 'Inactive Program',
            'is_active' => '0',
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('university_programs', [
        'program_name' => 'Inactive Program',
        'is_active' => 0,
    ]);
});

it('update changes program fields correctly', function () {
    $admin = createAdminUser();
    $firstUniversity = createUniversity(['name' => 'Stanford University']);
    $secondUniversity = createUniversity(['name' => 'Yale University']);
    $program = UniversityProgram::query()->create([
        'university_id' => $firstUniversity->id,
        'program_name' => 'Initial Program',
        'program_type' => 'mba',
        'tier' => 'regional',
        'description' => 'Initial description',
        'duration_months' => 12,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.programs.update', $program->id), [
            'university_id' => $secondUniversity->id,
            'program_name' => 'Updated Program',
            'program_type' => 'law',
            'tier' => 'elite',
            'description' => 'Updated description',
            'duration_months' => 36,
            'is_active' => '0',
            'notes' => 'Updating program from manual actions.',
            'manual_section' => 'programs',
            'manual_station' => 'program-create-station',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('university_programs', [
        'id' => $program->id,
        'university_id' => $secondUniversity->id,
        'program_name' => 'Updated Program',
        'program_type' => 'law',
        'tier' => 'elite',
        'description' => 'Updated description',
        'duration_months' => 36,
        'is_active' => 0,
    ]);
});

it('delete removes the program', function () {
    $admin = createAdminUser();
    $program = UniversityProgram::query()->create([
        'university_id' => createUniversity()->id,
        'program_name' => 'Delete Me',
        'program_type' => 'mba',
        'tier' => 'top',
        'description' => 'Delete this record',
        'duration_months' => 18,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.programs.destroy', $program->id))
        ->assertRedirect();

    $this->assertDatabaseMissing('university_programs', [
        'id' => $program->id,
    ]);
});

it('non admin user cannot access admin program routes', function () {
    $student = createStudentUser();
    $program = UniversityProgram::query()->create([
        'university_id' => createUniversity()->id,
        'program_name' => 'Blocked Program',
        'program_type' => 'mba',
        'tier' => 'top',
        'description' => 'Blocked',
        'duration_months' => 18,
        'is_active' => true,
    ]);

    $this->actingAs($student)
        ->get(route('admin.programs.index'))
        ->assertForbidden();

    $this->actingAs($student)
        ->post(route('admin.programs.store'), programPayload())
        ->assertForbidden();

    $this->actingAs($student)
        ->patch(route('admin.programs.update', $program->id), [
            'program_name' => 'Should Not Update',
        ])
        ->assertForbidden();

    $this->actingAs($student)
        ->delete(route('admin.programs.destroy', $program->id))
        ->assertForbidden();
});

it('validation redirect keeps manual section state for program form', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)
        ->from(route('admin.manual-actions'))
        ->post(route('admin.programs.store'), programPayload([
            'program_name' => '',
        ]));

    $response->assertRedirect(route('admin.manual-actions'));
    $response->assertSessionHasErrors('program_name');

    expect(session()->getOldInput('manual_section'))->toBe('programs');
    expect(session()->getOldInput('manual_station'))->toBe('program-create-station');
});

it('index passes active universities needed for the dropdown', function () {
    $admin = createAdminUser();
    $activeUniversity = createUniversity(['name' => 'Active University', 'is_active' => true]);
    createUniversity(['name' => 'Inactive University', 'is_active' => false]);

    $this->actingAs($admin)
        ->get(route('admin.programs.index'))
        ->assertOk()
        ->assertViewIs('discovery::admin.admin')
        ->assertViewHas('programUniversities', function ($universities) use ($activeUniversity) {
            return $universities->pluck('id')->contains($activeUniversity->id)
                && ! $universities->pluck('name')->contains('Inactive University');
        });
});
