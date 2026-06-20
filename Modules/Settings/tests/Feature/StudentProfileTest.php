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
        'email' => Str::uuid().'@example.edu',
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

it('renders student initials instead of uploaded avatars on the settings page', function () {
    $student = createProfileUser('student');
    $student->forceFill([
        'name' => 'Tyler James Cogan',
        'avatar_url' => '/storage/avatars/students/student-avatar.jpg',
    ])->save();

    $response = $this->actingAs($student)->get(route('student.settings.index'));

    $response->assertOk();
    $response->assertSee('<span>TC</span>', false);
    $response->assertSee('<div class="avatar">', false);
    $response->assertDontSee('/storage/avatars/students/student-avatar.jpg');
    $response->assertDontSee('Profile Image');
    $response->assertDontSee('name="avatar"', false);
    $response->assertDontSee('avatar has-image', false);
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

it('requires a .edu account email for undergrad and grad student profiles', function () {
    $student = createProfileUser('student');
    $student->forceFill(['email' => 'student-profile-'.Str::uuid().'@example.com'])->save();

    $this->actingAs($student)
        ->from(route('student.settings.index'))
        ->patch(route('student.settings.update'), [
            'name' => 'Student Example',
            'institution_text' => 'State U',
            'program_level' => 'grad',
            'program_type' => 'mba',
            'timezone' => 'Asia/Karachi',
        ])
        ->assertRedirect(route('student.settings.index'))
        ->assertSessionHasErrors('program_level');
});

it('allows professional student profiles to use non edu account emails', function () {
    $student = createProfileUser('student');
    $student->forceFill(['email' => 'professional-profile-'.Str::uuid().'@example.com'])->save();

    $this->actingAs($student)
        ->patch(route('student.settings.update'), [
            'name' => 'Professional Student',
            'institution_text' => 'Industry Org',
            'program_level' => 'professional',
            'program_type' => 'other',
            'timezone' => 'Asia/Karachi',
        ])
        ->assertRedirect(route('student.settings.index'))
        ->assertSessionHasNoErrors();

    $profile = StudentProfile::query()->where('user_id', $student->id)->first();

    expect($profile?->program_level)->toBe('professional');
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

it('ignores student avatar uploads and keeps initials-only display', function () {
    $student = createProfileUser('student');
    $student->forceFill(['avatar_url' => '/storage/avatars/students/legacy-avatar.jpg'])->save();

    $this->actingAs($student)->patch(route('student.settings.update'), [
        'name' => 'Tyler Cogan',
        'institution_text' => 'State U',
        'program_level' => 'grad',
        'program_type' => 'mba',
        'timezone' => 'Asia/Karachi',
        'avatar' => 'not-used-by-student-profile',
    ])->assertRedirect(route('student.settings.index'));

    $student->refresh();

    expect($student->name)->toBe('Tyler Cogan');
    expect($student->avatar_url)->toBe('/storage/avatars/students/legacy-avatar.jpg');

    $this->actingAs($student)
        ->get(route('student.settings.index'))
        ->assertOk()
        ->assertSee('TC')
        ->assertDontSee('/storage/avatars/students/legacy-avatar.jpg');
});
