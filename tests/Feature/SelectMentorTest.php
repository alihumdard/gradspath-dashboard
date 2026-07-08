<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseTransactions::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
});

function makeSelectMentorUser(string $prefix): User
{
    return User::factory()->create([
        'email' => $prefix.'-'.Str::uuid().'@example.edu',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
}

test('mentor select your mentor button links to mentor dashboard', function () {
    $mentor = makeSelectMentorUser('mentor');
    $mentor->assignRole('mentor');

    $response = $this->actingAs($mentor)->get('/home');

    $response->assertOk();
    $response->assertSee(route('mentor.dashboard'), false);
});

test('student select your mentor button links to student mentors index', function () {
    $student = makeSelectMentorUser('student');
    $student->assignRole('student');

    $response = $this->actingAs($student)->get('/home');

    $response->assertOk();
    $response->assertSee(route('student.mentors.index'), false);
});

test('guest select your mentor button opens login instead of navigating', function () {
    $response = $this->get('/home');

    $response->assertOk();
    $response->assertSee('data-open-login', false);
});
