# Grads Path — Laravel Backend: Module Structure Plan
**Stack: `nwidart/laravel-modules` v13 · Laravel 12 · Blade Templates · Web Routes · Vite per-module assets**
**Date:** 2026-04-11 | **Status:** Production-ready blueprint — ready to execute

---

## 0. Codebase Audit — What We Already Have

| Item | Status |
|------|--------|
| Laravel 12 skeleton | ✅ Installed |
| `nwidart/laravel-modules ^13` | ✅ In `composer.json` |
| `spatie/laravel-permission *` | ✅ In `composer.json` |
| `stubs/nwidart-stubs/` | ✅ Custom stubs ready |
| `vite-module-loader.js` | ✅ Already handles per-module Vite configs |
| 24-table DB schema fully designed | ✅ Documented |
| Web routes with Blade views already sketched | ✅ In `routes/web.php` |
| Any modules generated | ❌ None yet |
| Spatie RBAC migrations run | ❌ Not yet |
| Models beyond `User` | ❌ Only `User.php` |

> **Architecture decision:** We use **web routes + Blade templates + Laravel Sessions** (not API + token auth).
> Authentication = Laravel's built-in session auth (`Auth::guard('web')`).
> No JSON API, no Sanctum tokens — everything is server-rendered Blade.

---

## 1. Module Map — 10 Modules

| # | Platform Feature | Module Name | Namespace |
|---|-----------------|-------------|-----------|
| 1 | Auth & User Management | `Auth` | `Modules\Auth` |
| 2 | Find Mentors / Dashboard | `Discovery` | `Modules\Discovery` |
| 3 | Institutions / Universities | `Institutions` | `Modules\Institutions` |
| 4 | Office Hours Engine | `OfficeHours` | `Modules\OfficeHours` |
| 5 | Feedback & Reviews | `Feedback` | `Modules\Feedback` |
| 6 | Mentor Notes (Private) | `MentorNotes` | `Modules\MentorNotes` |
| 7 | Bookings & Sessions | `Bookings` | `Modules\Bookings` |
| 8 | Support Tickets | `Support` | `Modules\Support` |
| 9 | Settings & Profile | `Settings` | `Modules\Settings` |
| 10 | Payments & Credits | `Payments` | `Modules\Payments` |

> **Admin pages** live as `Admin/` sub-controllers inside each module — no separate Admin module needed.

---

## 2. Full Module Directory Structure

Every module follows this exact layout. Example: `Modules/Bookings/`

```
Modules/
└── Bookings/
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── BookingController.php          ← student/mentor web pages
    │   │   │   └── Admin/
    │   │   │       └── AdminBookingController.php  ← admin web pages
    │   │   ├── Requests/
    │   │   │   ├── CreateBookingRequest.php
    │   │   │   └── CancelBookingRequest.php
    │   │   └── Middleware/
    │   │       └── EnsureBookingOwner.php
    │   ├── Models/
    │   │   └── Booking.php
    │   ├── Policies/
    │   │   └── BookingPolicy.php
    │   └── Services/
    │       └── BookingService.php
    ├── database/
    │   ├── migrations/
    │   │   └── 2024_01_01_000014_create_bookings_table.php
    │   ├── seeders/
    │   │   └── BookingSeeder.php
    │   └── factories/
    │       └── BookingFactory.php
    ├── resources/
    │   ├── views/                                 ← Blade files (see Section 4)
    │   │   ├── index.blade.php
    │   │   ├── show.blade.php
    │   │   ├── create.blade.php
    │   │   └── admin/
    │   │       └── index.blade.php
    │   ├── css/
    │   │   └── bookings.css                       ← module-specific CSS
    │   └── js/
    │       └── bookings.js                        ← module-specific JS
    ├── routes/
    │   └── web.php                                ← module web routes
    ├── public/                                    ← published assets (see Section 5)
    │   └── (compiled by Vite → published here)
    ├── vite.config.js                             ← per-module Vite config (see Section 5)
    ├── tests/
    │   └── Feature/
    │       └── BookingTest.php
    └── module.json
```

---

## 3. Setup Commands — Run in This Exact Order

### Step 1 — Publish nwidart config
```bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

### Step 2 — Configure `config/modules.php`
```php
'namespace' => 'Modules',
'paths' => [
    'modules'   => base_path('Modules'),
    'assets'    => public_path('modules'),       // published assets land here
    'migration' => 'Database/Migrations',
    'generator' => [
        'controller' => ['path' => 'app/Http/Controllers', 'generate' => true],
        'request'    => ['path' => 'app/Http/Requests',    'generate' => true],
        'middleware' => ['path' => 'app/Http/Middleware',   'generate' => true],
        'model'      => ['path' => 'app/Models',            'generate' => true],
        'policies'   => ['path' => 'app/Policies',          'generate' => true],
        'service'    => ['path' => 'app/Services',          'generate' => true],
        'job'        => ['path' => 'app/Jobs',              'generate' => true],
        'event'      => ['path' => 'app/Events',            'generate' => true],
        'listener'   => ['path' => 'app/Listeners',         'generate' => true],
        'views'      => ['path' => 'resources/views',       'generate' => true],
        'factory'    => ['path' => 'database/factories',    'generate' => true],
        'migration'  => ['path' => 'database/migrations',   'generate' => true],
        'seeder'     => ['path' => 'database/seeders',      'generate' => true],
        'test'       => ['path' => 'tests/Feature',         'generate' => true],
        'routes'     => ['path' => 'routes',                'generate' => true],
        'lang'       => ['path' => 'lang',                  'generate' => false],
        'assets'     => ['path' => 'resources/assets',      'generate' => false],
    ],
],
```

### Step 3 — Publish Spatie permission migrations
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate --path=vendor/spatie/laravel-permission/database/migrations
```

### Step 4 — Generate all 10 modules
```bash
php artisan module:make Auth
php artisan module:make Discovery
php artisan module:make Institutions
php artisan module:make OfficeHours
php artisan module:make Feedback
php artisan module:make MentorNotes
php artisan module:make Bookings
php artisan module:make Support
php artisan module:make Settings
php artisan module:make Payments
```

### Step 5 — Confirm `routes/web.php` is the root loader
The root `routes/web.php` only handles the welcome/landing page. Each module registers its own routes via its `ServiceProvider`. Confirm `bootstrap/app.php` has:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
)
```
Module routes auto-load because each module's `ServiceProvider` calls:
```php
// Modules/Bookings/app/Providers/BookingsServiceProvider.php (auto-generated)
$this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
```

---

## 4. Blade Views — Full Structure & How They Work

### 4.1 View Naming Convention in Modules

nwidart views are referenced with the module name as a namespace prefix:

```php
// In controller:
return view('bookings::student.index');        // Modules/Bookings/resources/views/student/index.blade.php
return view('bookings::admin.index');          // Modules/Bookings/resources/views/admin/index.blade.php
return view('auth::login');                    // Modules/Auth/resources/views/login.blade.php
return view('discovery::student.dashboard');   // Modules/Discovery/resources/views/student/dashboard.blade.php
```

Pattern: `{module-lowercase}::{subfolder}.{blade-name}`

### 4.2 Global Layouts (in `resources/views/layouts/` — main app, not a module)

These shared layouts live in the **main app** `resources/views/` folder:

```
resources/views/
├── layouts/
│   ├── app.blade.php          ← main authenticated layout (sidebar + nav)
│   ├── guest.blade.php        ← unauthenticated layout (login/register pages)
│   └── admin.blade.php        ← admin layout (admin sidebar + nav)
├── components/
│   ├── nav/
│   │   ├── student-sidebar.blade.php
│   │   ├── mentor-sidebar.blade.php
│   │   └── admin-sidebar.blade.php
│   ├── ui/
│   │   ├── card.blade.php
│   │   ├── badge.blade.php
│   │   ├── modal.blade.php
│   │   └── alert.blade.php
│   └── forms/
│       ├── input.blade.php
│       ├── select.blade.php
│       └── textarea.blade.php
└── welcome.blade.php
```

### 4.3 Global Layout Files — Content

#### `resources/views/layouts/app.blade.php`
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Grads Path')</title>

    {{-- Global CSS (compiled from resources/css/app.css) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Module-specific CSS/JS injected by each page --}}
    @stack('styles')
</head>
<body>
    {{-- Role-based sidebar --}}
    @if(auth()->user()->hasRole('admin'))
        @include('components.nav.admin-sidebar')
    @elseif(auth()->user()->hasRole('mentor'))
        @include('components.nav.mentor-sidebar')
    @else
        @include('components.nav.student-sidebar')
    @endif

    {{-- Page content --}}
    <main>
        @if(session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif
        @if(session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
```

#### `resources/views/layouts/guest.blade.php`
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Grads Path')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="guest-layout">
    @yield('content')
    @stack('scripts')
</body>
</html>
```

### 4.4 Per-Module Blade View Structure

Every module has its views split by role. Here are all 10 modules:

#### Module: `Auth` → `Modules/Auth/resources/views/`
```
views/
├── login.blade.php
├── register.blade.php
├── forgot-password.blade.php
└── reset-password.blade.php
```

#### Module: `Discovery` → `Modules/Discovery/resources/views/`
```
views/
├── student/
│   ├── dashboard.blade.php        ← Find Mentors / dashboard (demo1.html)
│   ├── explore-mentors.blade.php  ← Explore mentors listing (demo4.html)
│   └── mentor-profile.blade.php   ← Single mentor profile + book button
└── partials/
    ├── mentor-card.blade.php      ← reusable mentor card component
    └── featured-mentors.blade.php ← "Mentors of the Week" section
```

#### Module: `Institutions` → `Modules/Institutions/resources/views/`
```
views/
├── student/
│   ├── index.blade.php            ← University grid (demo3a.html)
│   ├── show.blade.php             ← University detail + programs
│   └── mentors.blade.php          ← Mentors filtered by university
└── admin/
    ├── index.blade.php            ← Admin: list institutions
    ├── create.blade.php           ← Admin: add new institution
    └── edit.blade.php             ← Admin: edit institution
```

#### Module: `OfficeHours` → `Modules/OfficeHours/resources/views/`
```
views/
├── student/
│   └── index.blade.php            ← Office Hours this week (demo13.html)
└── mentor/
    ├── schedules.blade.php        ← Mentor: manage recurring schedule
    └── schedule-form.blade.php    ← Create/edit schedule form
```

#### Module: `Feedback` → `Modules/Feedback/resources/views/`
```
views/
├── student/
│   ├── index.blade.php            ← Browse all reviews (demo5.html)
│   └── post-session.blade.php     ← Post-meeting feedback form (demo6.html)
├── mentor/
│   └── post-session.blade.php     ← Mentor post-session form (demo7.html)
└── admin/
    └── index.blade.php            ← Admin: moderate reviews
```

#### Module: `MentorNotes` → `Modules/MentorNotes/resources/views/`
```
views/
├── mentor/
│   ├── index.blade.php            ← Notes list with student cards (demo8.html)
│   ├── create.blade.php           ← Write new note (demo7.html)
│   └── edit.blade.php             ← Edit existing note
└── admin/
    └── index.blade.php            ← Admin: view all notes
```

#### Module: `Bookings` → `Modules/Bookings/resources/views/`
```
views/
├── student/
│   ├── create.blade.php           ← Book with Mentor page (demo11.html)
│   ├── confirmation.blade.php     ← Session booked confirmation (demo9.html)
│   └── index.blade.php            ← My bookings / calendar
├── mentor/
│   └── index.blade.php            ← Mentor: my bookings
└── admin/
    └── index.blade.php            ← Admin: all bookings
```

#### Module: `Support` → `Modules/Support/resources/views/`
```
views/
├── shared/
│   ├── create.blade.php           ← Create ticket form (demo15.html)
│   └── index.blade.php            ← My tickets
└── admin/
    └── index.blade.php            ← Admin: all tickets
```

#### Module: `Settings` → `Modules/Settings/resources/views/`
```
views/
├── student/
│   └── index.blade.php            ← Student settings
└── mentor/
    ├── profile.blade.php          ← Mentor profile editor (demo10.html)
    └── payouts.blade.php          ← Stripe Connect setup
```

#### Module: `Payments` → `Modules/Payments/resources/views/`
```
views/
├── student/
│   ├── store.blade.php            ← Credits store (demo2.html)
│   └── transactions.blade.php     ← Credit history
└── admin/
    ├── overview.blade.php         ← Revenue dashboard
    ├── rankings.blade.php         ← Top mentors/students
    └── services.blade.php         ← Services & pricing manager
```

### 4.5 How a Blade Page Uses the Layout

Every module page extends the global layout using `@extends` + `@section`:

```blade
{{-- Modules/Bookings/resources/views/student/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Book a Session — Grads Path')

@push('styles')
    {{-- Module-specific CSS compiled by Vite --}}
    @vite(['Modules/Bookings/resources/css/bookings.css'])
@endpush

@section('content')
<div class="booking-page">
    <h1 class="page-title">Book with {{ $mentor->name }}</h1>

    {{-- Flash messages --}}
    @if($errors->any())
        <x-ui.alert type="error">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('bookings.store') }}">
        @csrf

        {{-- Service selection --}}
        <x-forms.select name="service_config_id" label="Select Service" :options="$services" />

        {{-- Meeting size --}}
        <x-forms.select name="meeting_size" label="Meeting Size" :options="['1on1' => '1-on-1', '1on3' => 'Group (3)']" />

        {{-- Date/Time --}}
        <x-forms.input type="date" name="session_date" label="Session Date" />
        <x-forms.select name="session_time" label="Session Time" :options="$availableSlots" />

        <button type="submit" class="btn-primary">Continue →</button>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['Modules/Bookings/resources/js/bookings.js'])
@endpush
```

### 4.6 Reusable Blade Components (in `resources/views/components/`)

Components are used across all modules with `<x-component-name />` syntax:

```blade
{{-- resources/views/components/ui/card.blade.php --}}
@props(['title' => null, 'class' => ''])
<div class="card {{ $class }}">
    @if($title)
        <div class="card-header">{{ $title }}</div>
    @endif
    <div class="card-body">{{ $slot }}</div>
</div>

{{-- Usage in any module Blade: --}}
<x-ui.card title="Mentor of the Week">
    <p>Content here</p>
</x-ui.card>
```

```blade
{{-- resources/views/components/forms/input.blade.php --}}
@props(['name', 'label', 'type' => 'text', 'value' => ''])
<div class="form-group">
    <label for="{{ $name }}">{{ $label }}</label>
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        class="form-input @error($name) is-invalid @enderror"
    />
    @error($name)
        <span class="error-msg">{{ $message }}</span>
    @enderror
</div>
```

```blade
{{-- resources/views/components/nav/student-sidebar.blade.php --}}
<nav class="sidebar" id="student-sidebar">
    <div class="logo">Grads Path</div>
    <ul>
        <li><a href="{{ route('discovery.dashboard') }}" @class(['active' => request()->routeIs('discovery.*')])>Dashboard</a></li>
        <li><a href="{{ route('institutions.index') }}" @class(['active' => request()->routeIs('institutions.*')])>Universities</a></li>
        <li><a href="{{ route('bookings.index') }}" @class(['active' => request()->routeIs('bookings.*')])>My Bookings</a></li>
        <li><a href="{{ route('support.create') }}" @class(['active' => request()->routeIs('support.*')])>Support</a></li>
        <li><a href="{{ route('settings.index') }}" @class(['active' => request()->routeIs('settings.*')])>Settings</a></li>
    </ul>
    <div class="credits-badge">Credits: {{ auth()->user()->credits->balance ?? 0 }}</div>
</nav>
```

### 4.7 Form Handling Pattern (POST → Redirect → GET)

All forms follow **PRG (Post-Redirect-Get)** pattern:

```php
// In BookingController.php (Modules/Bookings)
public function store(CreateBookingRequest $request): RedirectResponse
{
    try {
        $booking = $this->bookingService->createBooking(
            $request->validated(),
            auth()->user()
        );
        return redirect()
            ->route('bookings.show', $booking->id)
            ->with('success', 'Session booked successfully!');
    } catch (InsufficientCreditsException $e) {
        return back()->withErrors(['credits' => 'You do not have enough credits.'])->withInput();
    } catch (SessionFullException $e) {
        return back()->withErrors(['session' => 'This session is now full.'])->withInput();
    }
}
```

---

## 5. Web Routes — Per Module

### 5.1 How Module Routes Work

Each module has `routes/web.php` auto-loaded by its ServiceProvider. Routes are grouped by role middleware:

```
Auth middleware group used:
- 'auth'                → Laravel session auth (replaces 'auth:sanctum')
- 'role:student'        → Spatie role middleware
- 'role:mentor'         → Spatie role middleware
- 'role:admin'          → Spatie role middleware
- 'permission:...'      → Spatie permission middleware
```

Register Spatie middleware in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

### 5.2 Route Files Per Module

#### `Modules/Auth/routes/web.php`
```php
<?php
use Modules\Auth\app\Http\Controllers\AuthController;
use Modules\Auth\app\Http\Controllers\SocialAuthController;

// Guest routes (not logged in)
Route::middleware('guest')->group(function () {
    Route::get('/login',            [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login',           [AuthController::class, 'login'])->name('auth.login.post');
    Route::get('/register',         [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/register',        [AuthController::class, 'register'])->name('auth.register.post');
    Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('auth.password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('auth.password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('auth.password.reset');
    Route::post('/reset-password',  [AuthController::class, 'resetPassword'])->name('auth.password.update');
    Route::get('/auth/google',       [SocialAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');
});

// Authenticated
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

// Admin only
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users',                          [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/mentors/{id}/approve',         [AdminUserController::class, 'approveMentor'])->name('mentors.approve');
    Route::patch('/mentors/{id}/pause',           [AdminUserController::class, 'pauseMentor'])->name('mentors.pause');
    Route::delete('/users/{id}',                  [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/logs',                           [AdminUserController::class, 'logs'])->name('logs');
});
```

#### `Modules/Discovery/routes/web.php`
```php
<?php
use Modules\Discovery\app\Http\Controllers\DashboardController;
use Modules\Discovery\app\Http\Controllers\MentorController;

Route::middleware(['auth', 'role:student|mentor|admin'])->group(function () {
    Route::get('/',                       [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard',              [DashboardController::class, 'index'])->name('discovery.dashboard');
    Route::get('/mentors',                [MentorController::class, 'index'])->name('discovery.mentors');
    Route::get('/mentors/{id}',           [MentorController::class, 'show'])->name('discovery.mentor.show');
});
```

#### `Modules/Institutions/routes/web.php`
```php
<?php
use Modules\Institutions\app\Http\Controllers\UniversityController;
use Modules\Institutions\app\Http\Controllers\Admin\AdminInstitutionController;

Route::middleware(['auth'])->group(function () {
    Route::get('/institutions',           [UniversityController::class, 'index'])->name('institutions.index');
    Route::get('/institutions/{id}',      [UniversityController::class, 'show'])->name('institutions.show');
    Route::get('/institutions/{id}/mentors', [UniversityController::class, 'mentors'])->name('institutions.mentors');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/institutions',           [AdminInstitutionController::class, 'index'])->name('institutions.index');
    Route::get('/institutions/create',    [AdminInstitutionController::class, 'create'])->name('institutions.create');
    Route::post('/institutions',          [AdminInstitutionController::class, 'store'])->name('institutions.store');
    Route::get('/institutions/{id}/edit', [AdminInstitutionController::class, 'edit'])->name('institutions.edit');
    Route::patch('/institutions/{id}',    [AdminInstitutionController::class, 'update'])->name('institutions.update');
    Route::post('/programs',              [AdminInstitutionController::class, 'storeProgram'])->name('programs.store');
});
```

#### `Modules/Bookings/routes/web.php`
```php
<?php
use Modules\Bookings\app\Http\Controllers\BookingController;
use Modules\Bookings\app\Http\Controllers\ChatController;
use Modules\Bookings\app\Http\Controllers\Admin\AdminBookingController;

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/mentors/{mentorId}/book', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings',               [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings',                [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{id}',           [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{id}/cancel',   [BookingController::class, 'cancel'])->name('bookings.cancel');
});

// Chat accessible to both student and mentor (owner check in controller)
Route::middleware(['auth', 'role:student|mentor'])->group(function () {
    Route::get('/bookings/{id}/chat',      [ChatController::class, 'index'])->name('bookings.chat');
    Route::post('/bookings/{id}/chat',     [ChatController::class, 'store'])->name('bookings.chat.store');
});

// Mentor view own bookings
Route::middleware(['auth', 'role:mentor'])->prefix('mentor')->name('mentor.')->group(function () {
    Route::get('/bookings',                [BookingController::class, 'mentorIndex'])->name('bookings.index');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/bookings',                [AdminBookingController::class, 'index'])->name('bookings.index');
});
```

#### `Modules/OfficeHours/routes/web.php`
```php
<?php
use Modules\OfficeHours\app\Http\Controllers\OfficeHourController;
use Modules\OfficeHours\app\Http\Controllers\MentorScheduleController;

Route::middleware(['auth'])->group(function () {
    Route::get('/office-hours',            [OfficeHourController::class, 'index'])->name('office-hours.index');
});

Route::middleware(['auth', 'role:mentor'])->prefix('mentor')->name('mentor.')->group(function () {
    Route::get('/schedules',               [MentorScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/create',        [MentorScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/schedules',              [MentorScheduleController::class, 'store'])->name('schedules.store');
    Route::get('/schedules/{id}/edit',     [MentorScheduleController::class, 'edit'])->name('schedules.edit');
    Route::patch('/schedules/{id}',        [MentorScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{id}',       [MentorScheduleController::class, 'destroy'])->name('schedules.destroy');
});
```

#### `Modules/Feedback/routes/web.php`
```php
<?php
use Modules\Feedback\app\Http\Controllers\FeedbackController;
use Modules\Feedback\app\Http\Controllers\MentorFeedbackController;
use Modules\Feedback\app\Http\Controllers\Admin\AdminFeedbackController;

Route::middleware(['auth'])->group(function () {
    Route::get('/feedback',                [FeedbackController::class, 'index'])->name('feedback.index');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/bookings/{id}/feedback',  [FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/bookings/{id}/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
});

Route::middleware(['auth', 'role:mentor'])->group(function () {
    Route::get('/bookings/{id}/mentor-feedback',  [MentorFeedbackController::class, 'create'])->name('mentor-feedback.create');
    Route::post('/bookings/{id}/mentor-feedback', [MentorFeedbackController::class, 'store'])->name('mentor-feedback.store');
    Route::patch('/feedback/{id}/reply',           [FeedbackController::class, 'reply'])->name('feedback.reply');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/feedback',                [AdminFeedbackController::class, 'index'])->name('feedback.index');
    Route::patch('/feedback/{id}',         [AdminFeedbackController::class, 'update'])->name('feedback.update');
    Route::delete('/feedback/{id}',        [AdminFeedbackController::class, 'destroy'])->name('feedback.destroy');
});
```

#### `Modules/MentorNotes/routes/web.php`
```php
<?php
use Modules\MentorNotes\app\Http\Controllers\MentorNoteController;
use Modules\MentorNotes\app\Http\Controllers\Admin\AdminMentorNoteController;

Route::middleware(['auth', 'role:mentor'])->prefix('mentor')->name('mentor.')->group(function () {
    Route::get('/notes',                   [MentorNoteController::class, 'index'])->name('notes.index');
    Route::get('/notes/create',            [MentorNoteController::class, 'create'])->name('notes.create');
    Route::post('/notes',                  [MentorNoteController::class, 'store'])->name('notes.store');
    Route::get('/notes/{id}/edit',         [MentorNoteController::class, 'edit'])->name('notes.edit');
    Route::patch('/notes/{id}',            [MentorNoteController::class, 'update'])->name('notes.update');
});

// Admin soft-delete
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/mentor-notes',            [AdminMentorNoteController::class, 'index'])->name('notes.index');
    Route::delete('/mentor-notes/{id}',    [AdminMentorNoteController::class, 'destroy'])->name('notes.destroy');
});
```

#### `Modules/Support/routes/web.php`
```php
<?php
use Modules\Support\app\Http\Controllers\SupportController;
use Modules\Support\app\Http\Controllers\Admin\AdminSupportController;

Route::middleware(['auth'])->group(function () {
    Route::get('/support',                 [SupportController::class, 'create'])->name('support.create');
    Route::post('/support',                [SupportController::class, 'store'])->name('support.store')->middleware('throttle:5,60');
    Route::get('/support/my-tickets',      [SupportController::class, 'index'])->name('support.index');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/support/tickets',         [AdminSupportController::class, 'index'])->name('support.index');
    Route::get('/support/tickets/{id}',    [AdminSupportController::class, 'show'])->name('support.show');
    Route::patch('/support/tickets/{id}',  [AdminSupportController::class, 'update'])->name('support.update');
});
```

#### `Modules/Settings/routes/web.php`
```php
<?php
use Modules\Settings\app\Http\Controllers\UserSettingController;
use Modules\Settings\app\Http\Controllers\MentorProfileController;
use Modules\Settings\app\Http\Controllers\StripeConnectController;

Route::middleware(['auth'])->group(function () {
    Route::get('/settings',                [UserSettingController::class, 'index'])->name('settings.index');
    Route::patch('/settings',              [UserSettingController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', 'role:mentor'])->prefix('mentor')->name('mentor.')->group(function () {
    Route::get('/profile',                 [MentorProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile',              [MentorProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar',         [MentorProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::post('/payouts/onboarding',     [StripeConnectController::class, 'onboard'])->name('payouts.onboard');
});

// Stripe Connect webhook (no auth required, but Stripe-signed)
Route::post('/webhooks/stripe/connect',   [StripeConnectController::class, 'webhook'])->name('webhooks.stripe.connect');
```

#### `Modules/Payments/routes/web.php`
```php
<?php
use Modules\Payments\app\Http\Controllers\CreditController;
use Modules\Payments\app\Http\Controllers\SubscriptionController;
use Modules\Payments\app\Http\Controllers\StripeWebhookController;
use Modules\Payments\app\Http\Controllers\Admin\AdminAnalyticsController;
use Modules\Payments\app\Http\Controllers\Admin\AdminServiceController;
use Modules\Payments\app\Http\Controllers\Admin\AdminCreditController;

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/store',                   [CreditController::class, 'store'])->name('payments.store');
    Route::get('/credits/transactions',    [CreditController::class, 'transactions'])->name('credits.transactions');
    Route::post('/subscriptions/office-hours', [SubscriptionController::class, 'subscribe'])->name('subscriptions.office-hours');
});

// Stripe payment webhook
Route::post('/webhooks/stripe',            [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');
// Stripe success/cancel redirect pages
Route::get('/payments/success',            [CreditController::class, 'success'])->name('payments.success');
Route::get('/payments/cancelled',          [CreditController::class, 'cancelled'])->name('payments.cancelled');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard',               [AdminAnalyticsController::class, 'overview'])->name('dashboard');
    Route::get('/analytics/revenue',       [AdminAnalyticsController::class, 'revenue'])->name('analytics.revenue');
    Route::get('/rankings',                [AdminAnalyticsController::class, 'rankings'])->name('rankings');
    Route::get('/services',                [AdminServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create',         [AdminServiceController::class, 'create'])->name('services.create');
    Route::post('/services',               [AdminServiceController::class, 'store'])->name('services.store');
    Route::patch('/services/{id}/pricing', [AdminServiceController::class, 'updatePricing'])->name('services.pricing');
    Route::post('/credits/adjust',         [AdminCreditController::class, 'adjust'])->name('credits.adjust');
});
```

---

## 6. Public Assets — Per Module (Vite)

### 6.1 How It Works (Your `vite-module-loader.js` Already Handles This)

Your project already has `vite-module-loader.js` which:
1. Reads `modules_statuses.json` to find enabled modules
2. Looks for `vite.config.js` inside each module folder
3. Collects asset paths and merges them into the root Vite build

### 6.2 Root `vite.config.js` — Updated to Load Module Assets

```js
// vite.config.js (root)
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import collectModuleAssetsPaths from './vite-module-loader.js';

const modulePaths = await collectModuleAssetsPaths(
    [
        'resources/css/app.css',    // global CSS
        'resources/js/app.js',      // global JS
    ],
    'Modules'                        // modules directory
);

export default defineConfig({
    plugins: [
        laravel({
            input: modulePaths,
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

### 6.3 Per-Module `vite.config.js`

Each module that has CSS/JS needs its own `vite.config.js`:

```js
// Modules/Bookings/vite.config.js
export const paths = [
    'Modules/Bookings/resources/css/bookings.css',
    'Modules/Bookings/resources/js/bookings.js',
];

export default { paths };
```

```js
// Modules/Discovery/vite.config.js
export const paths = [
    'Modules/Discovery/resources/css/discovery.css',
    'Modules/Discovery/resources/js/discovery.js',
];

export default { paths };
```

```js
// Modules/Auth/vite.config.js
export const paths = [
    'Modules/Auth/resources/css/auth.css',
    'Modules/Auth/resources/js/auth.js',
];

export default { paths };
```

> Create one `vite.config.js` per module **only if it has its own CSS/JS**. Modules with no custom styles don't need one.

### 6.4 Module Asset Directory Structure

```
Modules/
└── Bookings/
    └── resources/
        ├── css/
        │   └── bookings.css     ← module-specific styles
        └── js/
            └── bookings.js      ← module-specific scripts
```

### 6.5 Publishing Module Assets (Static Assets — Images, Fonts, Icons)

For static files (images, fonts) stored inside a module, publish them to `public/modules/{module}/`:

In the module's `ServiceProvider`:
```php
// Modules/Bookings/app/Providers/BookingsServiceProvider.php
public function boot(): void
{
    $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'bookings');
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

    // Publish static images/icons to public/modules/bookings/
    $this->publishes([
        __DIR__ . '/../../public' => public_path('modules/bookings'),
    ], 'bookings-assets');
}
```

Run once (or in deployment):
```bash
php artisan module:publish           # publishes all modules
# OR individually:
php artisan vendor:publish --tag=bookings-assets
```

Published files land in:
```
public/
└── modules/
    ├── bookings/
    │   └── images/
    │       └── calendar-icon.svg
    ├── discovery/
    │   └── images/
    │       └── mentor-placeholder.png
    └── payments/
        └── images/
            └── stripe-badge.svg
```

Reference in Blade:
```html
<img src="{{ asset('modules/bookings/images/calendar-icon.svg') }}" alt="Calendar">
```

### 6.6 Using Vite in Blade (Module CSS/JS)

After running `npm run dev` or `npm run build`, reference module assets in Blade using `@vite()`:

```blade
{{-- In any module Blade page inside @push('styles') --}}
@push('styles')
    @vite(['Modules/Bookings/resources/css/bookings.css'])
@endpush

@push('scripts')
    @vite(['Modules/Bookings/resources/js/bookings.js'])
@endpush
```

Vite automatically handles:
- **Hot Module Replacement (HMR)** in dev mode
- **Cache-busting hashes** in production
- **CSS extraction** from JS modules

### 6.7 Global vs Module Assets — Decision Guide

| Asset Type | Where to Put | How to Load |
|-----------|-------------|-------------|
| Reset CSS, fonts, global variables | `resources/css/app.css` | `@vite(['resources/css/app.css'])` in layout |
| Sidebar, navbar, modal styles | `resources/css/app.css` or layout | `@vite()` in `layouts/app.blade.php` |
| Page-specific styles (booking form) | `Modules/Bookings/resources/css/` | `@push('styles')` + `@vite()` in page |
| Page-specific JS (calendar, chart.js) | `Modules/Payments/resources/js/` | `@push('scripts')` + `@vite()` in page |
| Module images/icons | `Modules/{Name}/public/images/` | `asset('modules/{name}/images/file.svg')` |
| Global images | `public/images/` | `asset('images/file.png')` |

---

## 7. Controller Pattern (Web — Blade)

Controllers return views with data, not JSON. Example:

```php
// Modules/Discovery/app/Http/Controllers/DashboardController.php
namespace Modules\Discovery\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Discovery\app\Services\DiscoveryService;

class DashboardController extends Controller
{
    public function __construct(
        private DiscoveryService $discoveryService
    ) {}

    public function index(): View
    {
        $featuredMentors = $this->discoveryService->getFeaturedMentors();
        $creditBalance   = auth()->user()->credits->balance ?? 0;

        return view('discovery::student.dashboard', [
            'featuredMentors' => $featuredMentors,
            'creditBalance'   => $creditBalance,
        ]);
    }
}
```

---

## 8. Authentication — Session-Based (Not API Tokens)

### 8.1 Login Controller (`Modules/Auth`)
```php
public function login(LoginRequest $request): RedirectResponse
{
    $credentials = $request->only('email', 'password');

    if (!Auth::attempt($credentials, $request->boolean('remember'))) {
        return back()
            ->withErrors(['email' => 'Invalid credentials.'])
            ->withInput($request->only('email'));
    }

    $request->session()->regenerate();

    // Redirect based on role
    $user = Auth::user();
    return match(true) {
        $user->hasRole('admin')  => redirect()->route('admin.dashboard'),
        $user->hasRole('mentor') => redirect()->route('mentor.bookings.index'),
        default                  => redirect()->route('discovery.dashboard'),
    };
}

public function logout(Request $request): RedirectResponse
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('auth.login');
}
```

### 8.2 Register — Auto-assign student role + create credit wallet
```php
public function register(RegisterRequest $request): RedirectResponse
{
    DB::transaction(function () use ($request) {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('student');                    // Spatie role
        UserCredit::create(['user_id' => $user->id, 'balance' => 0]);
        UserSetting::create(['user_id' => $user->id]);

        Auth::login($user);
    });

    return redirect()->route('discovery.dashboard')
        ->with('success', 'Welcome to Grads Path!');
}
```

### 8.3 Middleware in `bootstrap/app.php`
```php
->withMiddleware(function (Middleware $middleware) {
    // Spatie role/permission middleware
    $middleware->alias([
        'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);

    // Exclude Stripe webhook routes from CSRF verification
    $middleware->validateCsrfTokens(except: [
        'webhooks/stripe',
        'webhooks/stripe/connect',
    ]);
})
```

---

## 9. RBAC Setup (Spatie Roles + Permissions)

### 9.1 Roles: `student` | `mentor` | `admin`

### 9.2 Permissions Seeded

```
discovery.read
booking.create
booking.cancel
feedback.create
mentor_feedback.create
mentor_notes.manage_own
mentor_profile.manage_own
mentor_services.manage
office_hours.manage
support.create
support.read_own
admin.analytics.read
admin.manual.manage
admin.feedback.moderate
admin.institutions.manage
admin.pricing.manage
admin.logs.read
credits.read
credits.purchase
```

### 9.3 `RolePermissionSeeder.php` (in `Modules/Auth/database/seeders/`)
```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$student = Role::create(['name' => 'student']);
$mentor  = Role::create(['name' => 'mentor']);
$admin   = Role::create(['name' => 'admin']);

$student->syncPermissions(['discovery.read', 'booking.create', 'booking.cancel',
    'feedback.create', 'support.create', 'support.read_own', 'credits.read', 'credits.purchase']);

$mentor->syncPermissions(['discovery.read', 'mentor_profile.manage_own',
    'mentor_services.manage', 'office_hours.manage', 'mentor_feedback.create',
    'mentor_notes.manage_own', 'support.create', 'support.read_own']);

$admin->syncPermissions(Permission::all());
```

---

## 10. Migration Order (24 Tables Across Modules)

```bash
php artisan module:migrate           # runs all in correct order
```

| # | Table | Module | Migration Timestamp |
|---|-------|--------|---------------------|
| 1 | `users` | Auth | `2024_01_01_000001` |
| 2 | `oauth_tokens` | Auth | `2024_01_01_000002` |
| 3 | `password_resets` | Auth | `2024_01_01_000003` |
| 4 | `user_settings` | Settings | `2024_01_01_000004` |
| 5 | `universities` | Institutions | `2024_01_01_000005` |
| 6 | `university_programs` | Institutions | `2024_01_01_000006` |
| 7 | `mentors` | Settings | `2024_01_01_000007` |
| 8 | `services_config` | Payments | `2024_01_01_000008` |
| 9 | `mentor_services` | Settings | `2024_01_01_000009` |
| 10 | `office_hour_schedules` | OfficeHours | `2024_01_01_000010` |
| 11 | `office_hour_sessions` | OfficeHours | `2024_01_01_000011` |
| 12 | `user_credits` | Payments | `2024_01_01_000012` |
| 13 | `office_hours_subscriptions` | Payments | `2024_01_01_000013` |
| 14 | `bookings` | Bookings | `2024_01_01_000014` |
| 15 | `credit_transactions` | Payments | `2024_01_01_000015` |
| 16 | `stripe_webhooks` | Payments | `2024_01_01_000016` |
| 17 | `mentor_payouts` | Payments | `2024_01_01_000017` |
| 18 | `chats` | Bookings | `2024_01_01_000018` |
| 19 | `feedback` | Feedback | `2024_01_01_000019` |
| 20 | `mentor_feedback` | Feedback | `2024_01_01_000020` |
| 21 | `mentor_notes` | MentorNotes | `2024_01_01_000021` |
| 22 | `mentor_ratings` | Feedback | `2024_01_01_000022` |
| 23 | `support_tickets` | Support | `2024_01_01_000023` |
| 24 | `admin_logs` | Auth | `2024_01_01_000024` |
| 25 | Spatie RBAC tables | (vendor) | `2024_01_01_000025+` |

---

## 11. Shared Infrastructure (in root `app/` — not a module)

### Admin Audit Logging Trait
```php
// app/Traits/LogsAdminActions.php
trait LogsAdminActions
{
    protected function logAdminAction(string $action, string $targetTable, ?int $targetId, mixed $before, mixed $after): void
    {
        AdminLog::create([
            'admin_id'     => auth()->id(),
            'action'       => $action,
            'target_table' => $targetTable,
            'target_id'    => $targetId,
            'before_state' => $before ? json_encode($before) : null,
            'after_state'  => $after  ? json_encode($after)  : null,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }
}
```

All admin services `use LogsAdminActions;` and call `logAdminAction()` **inside the same DB transaction**.

---

## 12. Queue & Background Jobs

| Job | Module | Trigger | Queue |
|-----|--------|---------|-------|
| `SendBookingConfirmationJob` | Bookings | booking created | `notifications` |
| `GenerateMeetingLinkJob` | Bookings | booking created | `default` |
| `CheckPendingFeedbackJob` | Bookings | scheduled hourly | `default` |
| `RecalculateMentorRatingsJob` | Feedback | feedback submitted | `default` |
| `GenerateWeeklySessionsJob` | OfficeHours | scheduled Monday | `default` |
| `SendUserTicketConfirmationJob` | Support | ticket created | `notifications` |
| `NotifyAdminNewTicketJob` | Support | ticket created | `notifications` |
| `ProcessStripeWebhookJob` | Payments | webhook received | `payments` |

```php
// routes/console.php
Schedule::job(new GenerateWeeklySessionsJob())->weeklyOn(1, '00:01')->timezone('UTC');
Schedule::job(new CheckPendingFeedbackJob())->hourly();
Schedule::job(new CleanExpiredPasswordResetsJob())->daily();
```

---

## 13. Phased Execution Checklist

### Phase 0 — Foundation (Day 1)
- [ ] `php artisan vendor:publish` for nwidart + Spatie
- [ ] Configure `config/modules.php` (namespace, generators, asset path)
- [ ] Register Spatie middleware aliases in `bootstrap/app.php`
- [ ] Exclude webhook routes from CSRF in `bootstrap/app.php`
- [ ] Generate all 10 modules: `php artisan module:make {Name}`
- [ ] Add `HasRoles` to `app/Models/User.php`
- [ ] Create global layouts: `layouts/app.blade.php`, `layouts/guest.blade.php`, `layouts/admin.blade.php`
- [ ] Create global components: sidebar (3 roles), ui cards, form inputs, alert

### Phase 1 — Schema + Seed (Days 2–3)
- [ ] Distribute all 24 migrations into correct module folders with correct timestamps
- [ ] Run Spatie permission migrations
- [ ] `php artisan module:migrate` — verify all 24 tables
- [ ] Run `RolePermissionSeeder` — 3 roles + all permissions seeded
- [ ] Write all 24 Eloquent models (fillable, casts, relationships)

### Phase 2 — Auth Module (Day 4)
- [ ] Login, Register, Forgot Password, Reset Password controllers + Blade views
- [ ] Register flow: create user + assign student role + create credits wallet + user settings
- [ ] Google OAuth with `SocialAuthController`
- [ ] Role-based redirect on login (admin/mentor/student)
- [ ] Mentor apply form + admin approval workflow
- [ ] Test: register → login → dashboard redirect

### Phase 3 — Discovery + Institutions (Days 5–6)
- [ ] `DashboardController` returning featured mentors + credit balance to Blade
- [ ] `MentorController` (index with filters + show)
- [ ] `UniversityController` (index with tier/program filters + mentors)
- [ ] `discovery::student.dashboard` Blade (mentor cards, search, filters)
- [ ] `institutions::student.index` Blade (university grid, tags)
- [ ] Admin CRUD for institutions (create/edit Blade forms + audit log)
- [ ] Per-module Vite config for Discovery + Institutions

### Phase 4 — Bookings Core (Days 7–9)
- [ ] `BookingController` (create form, store with atomic transaction, show confirmation, index, cancel)
- [ ] `bookings::student.create` Blade (service select, meeting size, date/time)
- [ ] `bookings::student.confirmation` Blade (session details, meeting link, chat, cancel)
- [ ] `bookings::student.index` Blade (calendar, upcoming appointments)
- [ ] Cancellation flow with atomically: status + refund + credit_transactions row
- [ ] Test: create booking → credit deducted → cancel → credit refunded

### Phase 5 — Office Hours (Day 10)
- [ ] `OfficeHourService`: bookSlot with lockForUpdate + first-booker logic + service lock
- [ ] `office-hours::student.index` Blade (session cards with spot tracker)
- [ ] `MentorScheduleController` (create/edit recurring schedule Blade forms)
- [ ] `GenerateWeeklySessionsJob` scheduler
- [ ] Test: first slot → choose service → second slot → service locked

### Phase 6 — Feedback + Notes (Days 11–12)
- [ ] `FeedbackController` (create/store with completed-booking check)
- [ ] `feedback::student.post-session` Blade (stars, recommend, comment)
- [ ] `MentorFeedbackController` + `mentor::post-session` Blade
- [ ] `RecalculateMentorRatingsJob` dispatched after feedback stored
- [ ] `MentorNoteController` + `MentorNotePolicy` (student = 0 access)
- [ ] `mentor-notes::mentor.index` Blade (student cards + note snippets)
- [ ] Admin feedback moderation with audit log
- [ ] Test: group booking (1on3) → two students submit feedback independently

### Phase 7 — Support + Settings (Day 13)
- [ ] `SupportController` (throttled create + myTickets listing)
- [ ] `support::shared.create` Blade (ticket form with sanitization)
- [ ] Admin support ticket dashboard + reply form
- [ ] `MentorProfileController` (profile update + avatar upload atomic)
- [ ] `settings::mentor.profile` Blade (profile editor + live preview section)
- [ ] `EduEmailRule` custom validation
- [ ] `UserSettingController` (theme + notifications)

### Phase 8 — Payments + Stripe (Days 14–15)
- [ ] `CreditController` (store Blade page, transactions listing)
- [ ] `payments::student.store` Blade (credit packages, Office Hours subscription)
- [ ] `SubscriptionController` → creates Stripe Checkout session → redirect
- [ ] `StripeWebhookController` (idempotent via `stripe_webhooks.event_id`)
- [ ] `StripeConnectController` → onboarding redirect + connect webhook
- [ ] Admin analytics Blade (KPI cards, revenue charts, rankings tables)
- [ ] Admin services pricing editor Blade
- [ ] Test: webhook sent twice → only one `credit_transactions` row

### Phase 9 — Real-time Chat + Polish (Days 16–17)
- [ ] Install + configure Laravel Reverb
- [ ] `ChatController` (chat window with 48h gate)
- [ ] `bookings::student.confirmation` Blade: chat window (Reverb JS)
- [ ] `CheckPendingFeedbackJob`: flag users with overdue feedback
- [ ] Flash message display in `layouts/app.blade.php`
- [ ] Active sidebar link highlighting (`request()->routeIs()`)
- [ ] Credit balance in sidebar (from `auth()->user()->credits->balance`)
- [ ] Per-module Vite configs for all remaining modules

### Phase 10 — Testing + Hardening (Days 18–20)
- [ ] E2E: Student path (register → mentor search → book → chat → feedback)
- [ ] E2E: Mentor path (apply → approve → schedule → note → payouts)
- [ ] E2E: Admin path (all 6 stations + audit log per action)
- [ ] Role gate tests: student → mentor routes = 403, student → admin = 403
- [ ] Concurrent booking race: lockForUpdate prevents double-deduction
- [ ] Stripe webhook idempotency test
- [ ] `php artisan test` — all green
- [ ] `npm run build` — all module assets compiled without errors

---

## 14. Complete Route → View → Controller Reference

| URL | Controller | View (module::path) |
|-----|-----------|---------------------|
| `/login` | Auth\AuthController@showLogin | `auth::login` |
| `/register` | Auth\AuthController@showRegister | `auth::register` |
| `/dashboard` | Discovery\DashboardController@index | `discovery::student.dashboard` |
| `/mentors` | Discovery\MentorController@index | `discovery::student.explore-mentors` |
| `/mentors/{id}` | Discovery\MentorController@show | `discovery::student.mentor-profile` |
| `/institutions` | Institutions\UniversityController@index | `institutions::student.index` |
| `/institutions/{id}` | Institutions\UniversityController@show | `institutions::student.show` |
| `/office-hours` | OfficeHours\OfficeHourController@index | `office-hours::student.index` |
| `/mentors/{id}/book` | Bookings\BookingController@create | `bookings::student.create` |
| `/bookings` | Bookings\BookingController@store | (redirect to show) |
| `/bookings/{id}` | Bookings\BookingController@show | `bookings::student.confirmation` |
| `/bookings/{id}/chat` | Bookings\ChatController@index | (embedded in confirmation) |
| `/feedback` | Feedback\FeedbackController@index | `feedback::student.index` |
| `/bookings/{id}/feedback` | Feedback\FeedbackController@create | `feedback::student.post-session` |
| `/bookings/{id}/mentor-feedback` | Feedback\MentorFeedbackController@create | `feedback::mentor.post-session` |
| `/mentor/notes` | MentorNotes\MentorNoteController@index | `mentor-notes::mentor.index` |
| `/support` | Support\SupportController@create | `support::shared.create` |
| `/store` | Payments\CreditController@store | `payments::student.store` |
| `/mentor/profile` | Settings\MentorProfileController@index | `settings::mentor.profile` |
| `/settings` | Settings\UserSettingController@index | `settings::student.index` |
| `/admin/dashboard` | Payments\Admin\AdminAnalyticsController@overview | `payments::admin.overview` |
| `/admin/users` | Auth\Admin\AdminUserController@index | `auth::admin.users` |
| `/admin/mentors` | Settings\Admin\AdminMentorController@index | `settings::admin.mentors` |
| `/admin/feedback` | Feedback\Admin\AdminFeedbackController@index | `feedback::admin.index` |
| `/admin/support/tickets` | Support\Admin\AdminSupportController@index | `support::admin.index` |
| `/admin/services` | Payments\Admin\AdminServiceController@index | `payments::admin.services` |
| `/admin/institutions` | Institutions\Admin\AdminInstitutionController@index | `institutions::admin.index` |
| `/admin/logs` | Auth\Admin\AdminUserController@logs | `auth::admin.logs` |

---

*Built from: `Grads path documentaion.md` · `Database_Schema_Documentation.md` · `Backend_Implementation_Master_Plan.md` · `Role_Flow_Execution_Blueprint.md` · `routes/web.php` (existing) · `vite-module-loader.js` (existing) · Aligned with `nwidart/laravel-modules v13` + `Laravel 12` + Blade + Session Auth*
