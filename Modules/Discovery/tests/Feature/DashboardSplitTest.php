<?php

use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Tests\TestCase;

uses(TestCase::class);

function createDashboardUser(): User
{
    $created = \App\Models\User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    return User::query()->findOrFail($created->id);
}

it('renders the mentor dashboard with mentor-specific navigation', function () {
    $mentor = createDashboardUser();

    $response = $this->withoutMiddleware()->actingAs($mentor)->get(route('mentor.dashboard'));

    $response->assertOk();
    $response->assertSee('MENTOR PORTAL');
    $response->assertSee('/mentor/settings', false);
    $response->assertDontSee('/student/settings', false);
});

it('renders the student dashboard with student-specific navigation', function () {
    $student = createDashboardUser();

    $response = $this->withoutMiddleware()->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('STUDENT PORTAL');
    $response->assertSee('/student/settings', false);
    $response->assertDontSee('/mentor/settings', false);
});
