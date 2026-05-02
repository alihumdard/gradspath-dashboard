<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\File;
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

it('renders uploaded student avatars on the settings page', function () {
    $student = createProfileUser('student');
    $student->forceFill([
        'avatar_url' => '/storage/avatars/students/student-avatar.jpg',
    ])->save();

    $response = $this->actingAs($student)->get(route('student.settings.index'));

    $response->assertOk();
    $response->assertSee('/storage/avatars/students/student-avatar.jpg');
    $response->assertSee('avatar has-image', false);
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

it('uploads a student avatar and cleans up the replaced file', function () {
    Storage::fake('public');

    $student = createProfileUser('student');
    $student->forceFill(['avatar_url' => '/storage/avatars/students/legacy-avatar.jpg'])->save();
    Storage::disk('public')->put('avatars/students/legacy-avatar.jpg', 'legacy');

    $this->actingAs($student)->patch(route('student.settings.update'), [
        'name' => 'Student Example',
        'program_level' => 'grad',
        'program_type' => 'mba',
        'timezone' => 'Asia/Karachi',
        'avatar' => UploadedFile::fake()->image('student-first.jpg'),
    ])->assertRedirect(route('student.settings.index'));

    $student->refresh();
    $firstPath = Str::after(parse_url($student->avatar_url, PHP_URL_PATH) ?: '', '/storage/');

    Storage::disk('public')->assertExists($firstPath);
    Storage::disk('public')->assertMissing('avatars/students/legacy-avatar.jpg');
    expect(File::query()
        ->where('fileable_type', \App\Models\User::class)
        ->where('fileable_id', $student->id)
        ->where('type', 'avatar')
        ->where('is_deleted', false)
        ->count())->toBe(1);

    $this->actingAs($student)->patch(route('student.settings.update'), [
        'name' => 'Student Example',
        'program_level' => 'grad',
        'program_type' => 'mba',
        'timezone' => 'Asia/Karachi',
        'avatar' => UploadedFile::fake()->image('student-second.jpg'),
    ])->assertRedirect(route('student.settings.index'));

    $student->refresh();
    $secondPath = Str::after(parse_url($student->avatar_url, PHP_URL_PATH) ?: '', '/storage/');

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
    expect(File::query()
        ->where('fileable_type', \App\Models\User::class)
        ->where('fileable_id', $student->id)
        ->where('type', 'avatar')
        ->where('is_deleted', false)
        ->count())->toBe(1);
    expect(File::query()
        ->where('fileable_type', \App\Models\User::class)
        ->where('fileable_id', $student->id)
        ->where('type', 'avatar')
        ->where('is_deleted', true)
        ->count())->toBe(1);
});
