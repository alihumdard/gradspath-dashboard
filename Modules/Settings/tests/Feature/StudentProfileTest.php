<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Institutions\app\Models\University;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Models\StudentProfile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('admin', 'web');
});

function createProfileUser(string $role): User
{
    $created = \App\Models\User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user = User::query()->findOrFail($created->id);
    $user->assignRole($role);

    return $user;
}

it('renders the student profile page with student-specific fields', function () {
    $student = createProfileUser('student');

    $university = University::query()->create([
        'name' => 'State University',
        'display_name' => 'State U',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    StudentProfile::query()->create([
        'user_id' => $student->id,
        'university_id' => $university->id,
        'institution_text' => 'State U',
        'program_level' => 'undergrad',
        'program_type' => 'law',
    ]);

    $response = $this->actingAs($student)->get(route('student.settings.index'));

    $response->assertOk();
    $response->assertSee('Student Profile');
    $response->assertSee('STUDENT PORTAL');
    $response->assertSee('State U');
    $response->assertSee('Undergrad');
    $response->assertSee('Timezone');
    $response->assertDontSee('Mentor Settings');
});

it('updates the student profile using student_profiles instead of mentor data', function () {
    $student = createProfileUser('student');

    $university = University::query()->create([
        'name' => 'Boston College',
        'display_name' => 'Boston College',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    $response = $this->actingAs($student)->patch(route('student.settings.update'), [
        'name' => 'Student Example',
        'university_id' => $university->id,
        'institution_text' => '',
        'program_level' => 'grad',
        'program_type' => 'mba',
        'timezone' => 'Asia/Karachi',
    ]);

    $response->assertRedirect(route('student.settings.index'));

    expect($student->fresh()->name)->toBe('Student Example');

    $profile = StudentProfile::query()->where('user_id', $student->id)->first();

    expect($profile)->not->toBeNull();
    expect($profile->university_id)->toBe($university->id);
    expect($profile->institution_text)->toBe('Boston College');
    expect($profile->program_level)->toBe('grad');
    expect($profile->program_type)->toBe('mba');
    expect($student->fresh()->setting?->timezone)->toBe('Asia/Karachi');
    expect(Mentor::query()->where('user_id', $student->id)->exists())->toBeFalse();
});

it('keeps student and mentor profile routes separated by role', function () {
    $student = createProfileUser('student');
    $mentor = createProfileUser('mentor');
    $admin = createProfileUser('admin');

    $this->actingAs($student)->get(route('student.settings.index'))->assertOk();
    $this->actingAs($student)->get(route('mentor.settings.index'))->assertForbidden();

    $this->actingAs($mentor)->get(route('mentor.settings.index'))->assertOk();
    $this->actingAs($mentor)->get(route('student.settings.index'))->assertForbidden();

    $this->actingAs($admin)->get(route('student.settings.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('mentor.settings.index'))->assertForbidden();
});
