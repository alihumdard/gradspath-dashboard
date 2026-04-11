# Grads Path — Migration & Blade Module Mapping
**Based on actual codebase scan · April 11, 2026**

---

## Part 1: Migration Audit & Required Changes

### Current State — All 27 Migrations Found

```
database/migrations/
├── 0001_01_01_000000_create_users_table.php          ← NEEDS CHANGES (see below)
├── 0001_01_01_000001_create_cache_table.php          ← KEEP IN ROOT (Laravel cache)
├── 0001_01_01_000002_create_jobs_table.php           ← KEEP IN ROOT (Queue jobs)
├── 2024_01_01_000025_spatie_permission_tables.php.note  ← DELETE (old note, replaced)
├── 2026_04_04_000002_create_oauth_tokens_table.php   ← MOVE to Modules/Auth
├── 2026_04_04_000004_create_user_settings_table.php  ← MOVE to Modules/Settings
├── 2026_04_04_000005_create_universities_table.php   ← MOVE to Modules/Institutions
├── 2026_04_04_000006_create_university_programs_table.php  ← MOVE to Modules/Institutions
├── 2026_04_04_000007_create_mentors_table.php        ← MOVE to Modules/Settings
├── 2026_04_04_000008_create_services_config_table.php  ← MOVE to Modules/Payments
├── 2026_04_04_000009_create_mentor_services_table.php  ← MOVE to Modules/Settings
├── 2026_04_04_000010_create_office_hour_schedules_table.php  ← MOVE to Modules/OfficeHours
├── 2026_04_04_000011_create_office_hour_sessions_table.php   ← MOVE to Modules/OfficeHours
├── 2026_04_04_000012_create_user_credits_table.php   ← MOVE to Modules/Payments
├── 2026_04_04_000013_create_office_hours_subscriptions_table.php ← MOVE to Modules/Payments
├── 2026_04_04_000014_create_bookings_table.php       ← MOVE to Modules/Bookings
├── 2026_04_04_000015_create_credit_transactions_table.php  ← MOVE to Modules/Payments
├── 2026_04_04_000016_create_stripe_webhooks_table.php  ← MOVE to Modules/Payments
├── 2026_04_04_000017_create_mentor_payouts_table.php ← MOVE to Modules/Payments
├── 2026_04_04_000018_create_chats_table.php          ← MOVE to Modules/Bookings
├── 2026_04_04_000019_create_feedback_table.php       ← MOVE to Modules/Feedback
├── 2026_04_04_000020_create_mentor_feedback_table.php  ← MOVE to Modules/Feedback
├── 2026_04_04_000021_create_mentor_notes_table.php   ← MOVE to Modules/MentorNotes
├── 2026_04_04_000022_create_mentor_ratings_table.php ← MOVE to Modules/Feedback
├── 2026_04_04_000023_create_support_tickets_table.php  ← MOVE to Modules/Support
├── 2026_04_04_000024_create_admin_logs_table.php     ← MOVE to Modules/Auth
└── 2026_04_10_144806_create_permission_tables.php    ← KEEP IN ROOT (Spatie vendor)
```

---

### Users Migration — Required Changes

The existing `users` table migration is **missing two fields** that the register Blade form (`register.blade.php`) collects:

1. **`role`** — The form has hidden `<input name="role">` with values `student` or `mentor`
2. **`program_level`** — The form has hidden `<input name="program_level">` with values `undergrad`, `grad`, `professional`
3. **`avatar_url`** — Needed for profile pictures (referenced in the plan)
4. **`is_active`** — Needed to block/suspend users from admin panel
5. **`password` is currently NOT NULL** — but schema docs say it should be nullable for social-only accounts

Also the `password_reset_tokens` table uses `email` as primary key (no separate `id`, token not unique) — this must match our custom `password_resets` design.

**Updated `users` table migration** (replace `0001_01_01_000000_create_users_table.php`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();           // nullable: social-only users have no password
            $table->string('avatar_url')->nullable();         // profile picture
            $table->boolean('is_active')->default(true);     // admin can block users
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Indexes for fast lookups
            $table->index('email');
            $table->index('is_active');
            $table->index('created_at');
        });

        // Keep Laravel's built-in sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Custom password_resets table (replaces default password_reset_tokens)
        // We use our own migration file for this (see Auth module migration)
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
```

> **Why remove `password_reset_tokens` from here?** Our separate `Auth` module migration
> `2026_04_04_000003_create_password_resets_table.php` (to be created) handles this with
> a proper `id` PK, unique `token`, and `expires_at` — better design than the default Laravel table.

---

### Register Form Fields — What the Backend Needs

The register Blade (`register.blade.php`) sends these POST fields:

| Field | HTML | What We Store |
|-------|------|---------------|
| `role` | hidden input | Used to assign Spatie role (`student` or `mentor`) — NOT stored in `users` table directly |
| `program_level` | hidden input | `undergrad` / `grad` / `professional` — stored in `mentors.mentor_type` if mentor, or ignored for students |
| `name` | text input | → `users.name` |
| `email` | email input | → `users.email` |
| `institution` | text input | → `mentors.grad_school_display` if mentor, or ignored for students |
| `password` | password input | → `users.password` (hashed) |
| `password_confirmation` | password input | validated only, not stored |

**Key point:** `role` and `program_level` are NOT columns on `users`. They drive:
- `role` → `$user->assignRole($request->role)` via Spatie
- `program_level` → stored in `mentors.mentor_type` only if user chose mentor role
- `institution` → stored in `mentors.grad_school_display` if mentor

---

## Part 2: Where to Move Each Migration

### Rule: After generating modules, copy (don't just move) files then delete originals.
### Keep original timestamps — do NOT rename files (timestamps = migration order).

---

### `Modules/Auth/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `0001_01_01_000000_create_users_table.php` | Users are the Auth foundation — AFTER updating |
| `2026_04_04_000002_create_oauth_tokens_table.php` | Google OAuth — belongs to Auth |
| `2026_04_04_000024_create_admin_logs_table.php` | Admin audit — belongs to Auth module |

**Also CREATE** this new file in `Modules/Auth/database/migrations/`:
```
2026_04_04_000003_create_password_resets_table.php
```
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();                                       // proper PK
            $table->string('email')->index();                   // not FK (allows reset for deleted users)
            $table->string('token')->unique();                  // cryptographically secure, must be unique
            $table->timestamp('expires_at');                    // 24-hour expiry enforced server-side
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
```

---

### `Modules/Settings/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `2026_04_04_000004_create_user_settings_table.php` | User preferences (theme etc.) |
| `2026_04_04_000007_create_mentors_table.php` | Mentor profile managed in Settings |
| `2026_04_04_000009_create_mentor_services_table.php` | Which services mentors offer |

---

### `Modules/Institutions/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `2026_04_04_000005_create_universities_table.php` | Universities = Institutions module |
| `2026_04_04_000006_create_university_programs_table.php` | Programs under universities |

---

### `Modules/Payments/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `2026_04_04_000008_create_services_config_table.php` | Service pricing managed by Payments |
| `2026_04_04_000012_create_user_credits_table.php` | Credits = Payments module |
| `2026_04_04_000013_create_office_hours_subscriptions_table.php` | Subscription billing |
| `2026_04_04_000015_create_credit_transactions_table.php` | Financial ledger |
| `2026_04_04_000016_create_stripe_webhooks_table.php` | Stripe events |
| `2026_04_04_000017_create_mentor_payouts_table.php` | Mentor payout records |

---

### `Modules/OfficeHours/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `2026_04_04_000010_create_office_hour_schedules_table.php` | Recurring schedules |
| `2026_04_04_000011_create_office_hour_sessions_table.php` | Generated sessions |

---

### `Modules/Bookings/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `2026_04_04_000014_create_bookings_table.php` | Core bookings |
| `2026_04_04_000018_create_chats_table.php` | Chat is part of booking flow |

---

### `Modules/Feedback/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `2026_04_04_000019_create_feedback_table.php` | Student reviews |
| `2026_04_04_000020_create_mentor_feedback_table.php` | Mentor post-session forms |
| `2026_04_04_000022_create_mentor_ratings_table.php` | Aggregate ratings |

---

### `Modules/MentorNotes/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `2026_04_04_000021_create_mentor_notes_table.php` | Private mentor notes |

---

### `Modules/Support/database/migrations/`

| File to Move | Why Here |
|-------------|---------|
| `2026_04_04_000023_create_support_tickets_table.php` | Support tickets |

---

### Stay in ROOT `database/migrations/` (do NOT move)

| File | Reason |
|------|--------|
| `0001_01_01_000001_create_cache_table.php` | Laravel framework cache — root only |
| `0001_01_01_000002_create_jobs_table.php` | Laravel queue jobs — root only |
| `2026_04_10_144806_create_permission_tables.php` | Spatie vendor publish — must stay root |

---

### DELETE

| File | Reason |
|------|--------|
| `2024_01_01_000025_spatie_permission_tables.php.note` | Old note file (.note extension, not a real migration) — replaced by the proper Spatie migration |

---

## Part 3: Complete Module Migration Summary Table

| Module | Migrations Inside It |
|--------|---------------------|
| **Auth** | `users`, `password_resets` (new), `oauth_tokens`, `admin_logs` |
| **Settings** | `user_settings`, `mentors`, `mentor_services` |
| **Institutions** | `universities`, `university_programs` |
| **Payments** | `services_config`, `user_credits`, `office_hours_subscriptions`, `credit_transactions`, `stripe_webhooks`, `mentor_payouts` |
| **OfficeHours** | `office_hour_schedules`, `office_hour_sessions` |
| **Bookings** | `bookings`, `chats` |
| **Feedback** | `feedback`, `mentor_feedback`, `mentor_ratings` |
| **MentorNotes** | `mentor_notes` |
| **Support** | `support_tickets` |
| **Discovery** | *(no own tables — reads from Auth/Settings/Feedback/Payments)* |
| **ROOT** | `cache`, `jobs`, `permission_tables` (Spatie) |

---

## Part 4: Blade File Mapping — Where to Move Each View

### Current Blade Files Found

```
resources/views/
├── layouts/
│   └── app.blade.php                 ← KEEP in root (global layout)
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
├── student/
│   ├── dashboard.blade.php
│   ├── store.blade.php
│   ├── institutions.blade.php
│   ├── institution-detail.blade.php
│   ├── mentors.blade.php
│   ├── mentor-detail.blade.php
│   ├── book-mentor.blade.php
│   ├── office-hours.blade.php
│   ├── feedback.blade.php
│   ├── bookings.blade.php
│   ├── mentor-notes.blade.php
│   ├── support.blade.php
│   └── settings.blade.php
├── mentor/
│   ├── dashboard.blade.php
│   ├── bookings.blade.php
│   ├── earnings.blade.php
│   ├── availability.blade.php
│   ├── students.blade.php
│   ├── feedback.blade.php
│   ├── profile.blade.php
│   └── settings.blade.php
├── admin/
│   └── dashboard.blade.php
└── welcome.blade.php
```

---

### Move Map — Every Blade File

#### `Modules/Auth/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/auth/login.blade.php` | `Modules/Auth/resources/views/login.blade.php` | `auth::login` |
| `resources/views/auth/register.blade.php` | `Modules/Auth/resources/views/register.blade.php` | `auth::register` |
| `resources/views/auth/forgot-password.blade.php` | `Modules/Auth/resources/views/forgot-password.blade.php` | `auth::forgot-password` |
| `resources/views/auth/reset-password.blade.php` | `Modules/Auth/resources/views/reset-password.blade.php` | `auth::reset-password` |

---

#### `Modules/Discovery/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/dashboard.blade.php` | `Modules/Discovery/resources/views/student/dashboard.blade.php` | `discovery::student.dashboard` |
| `resources/views/student/mentors.blade.php` | `Modules/Discovery/resources/views/student/explore.blade.php` | `discovery::student.explore` |
| `resources/views/student/mentor-detail.blade.php` | `Modules/Discovery/resources/views/student/mentor-profile.blade.php` | `discovery::student.mentor-profile` |
| `resources/views/mentor/dashboard.blade.php` | `Modules/Discovery/resources/views/mentor/dashboard.blade.php` | `discovery::mentor.dashboard` |

---

#### `Modules/Institutions/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/institutions.blade.php` | `Modules/Institutions/resources/views/student/index.blade.php` | `institutions::student.index` |
| `resources/views/student/institution-detail.blade.php` | `Modules/Institutions/resources/views/student/show.blade.php` | `institutions::student.show` |

---

#### `Modules/OfficeHours/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/office-hours.blade.php` | `Modules/OfficeHours/resources/views/student/index.blade.php` | `office-hours::student.index` |
| `resources/views/mentor/availability.blade.php` | `Modules/OfficeHours/resources/views/mentor/schedules.blade.php` | `office-hours::mentor.schedules` |

---

#### `Modules/Bookings/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/book-mentor.blade.php` | `Modules/Bookings/resources/views/student/create.blade.php` | `bookings::student.create` |
| `resources/views/student/bookings.blade.php` | `Modules/Bookings/resources/views/student/index.blade.php` | `bookings::student.index` |
| `resources/views/mentor/bookings.blade.php` | `Modules/Bookings/resources/views/mentor/index.blade.php` | `bookings::mentor.index` |

> **NOTE:** A `bookings::student.confirmation` view needs to be **created new** (the Session Booked page — demo9.html).

---

#### `Modules/Feedback/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/feedback.blade.php` | `Modules/Feedback/resources/views/student/index.blade.php` | `feedback::student.index` |
| `resources/views/mentor/feedback.blade.php` | `Modules/Feedback/resources/views/mentor/index.blade.php` | `feedback::mentor.index` |

> **NOTE:** Post-session forms (student + mentor after meeting) need to be **created new**.

---

#### `Modules/MentorNotes/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/mentor-notes.blade.php` | `Modules/MentorNotes/resources/views/mentor/index.blade.php` | `mentor-notes::mentor.index` |
| `resources/views/mentor/students.blade.php` | `Modules/MentorNotes/resources/views/mentor/students.blade.php` | `mentor-notes::mentor.students` |

> **Important rename:** `student/mentor-notes.blade.php` is actually a mentor-facing page (mentor views notes about students). Move to `mentor/` subfolder.

---

#### `Modules/Support/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/support.blade.php` | `Modules/Support/resources/views/shared/create.blade.php` | `support::shared.create` |

---

#### `Modules/Settings/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/settings.blade.php` | `Modules/Settings/resources/views/student/index.blade.php` | `settings::student.index` |
| `resources/views/mentor/settings.blade.php` | `Modules/Settings/resources/views/mentor/index.blade.php` | `settings::mentor.index` |
| `resources/views/mentor/profile.blade.php` | `Modules/Settings/resources/views/mentor/profile.blade.php` | `settings::mentor.profile` |

---

#### `Modules/Payments/resources/views/`

| Current Path | New Path Inside Module | New Reference |
|-------------|----------------------|---------------|
| `resources/views/student/store.blade.php` | `Modules/Payments/resources/views/student/store.blade.php` | `payments::student.store` |
| `resources/views/mentor/earnings.blade.php` | `Modules/Payments/resources/views/mentor/earnings.blade.php` | `payments::mentor.earnings` |
| `resources/views/admin/dashboard.blade.php` | `Modules/Payments/resources/views/admin/dashboard.blade.php` | `payments::admin.dashboard` |

---

#### KEEP in ROOT `resources/views/`

| File | Reason |
|------|--------|
| `resources/views/layouts/app.blade.php` | Global layout used by all modules |
| `resources/views/welcome.blade.php` | Landing page, not module-specific |

> **To Create** — new root files needed:
> - `resources/views/layouts/guest.blade.php` — for login/register pages
> - `resources/views/layouts/admin.blade.php` — optional separate admin layout
> - `resources/views/components/nav/student-sidebar.blade.php`
> - `resources/views/components/nav/mentor-sidebar.blade.php`
> - `resources/views/components/nav/admin-sidebar.blade.php`

---

## Part 5: Route Name Changes Required After Moving Blades

The existing `routes/web.php` references view names like `view('auth.login')`. After moving to modules these change:

| Old (in `routes/web.php`) | New (in module controller) |
|--------------------------|--------------------------|
| `view('auth.login')` | `view('auth::login')` |
| `view('auth.register')` | `view('auth::register')` |
| `view('auth.forgot-password')` | `view('auth::forgot-password')` |
| `view('auth.reset-password', ...)` | `view('auth::reset-password', ...)` |
| `view('student.dashboard')` | `view('discovery::student.dashboard')` |
| `view('student.store')` | `view('payments::student.store')` |
| `view('student.institutions')` | `view('institutions::student.index')` |
| `view('student.institution-detail', ...)` | `view('institutions::student.show', ...)` |
| `view('student.mentors')` | `view('discovery::student.explore')` |
| `view('student.mentor-detail', ...)` | `view('discovery::student.mentor-profile', ...)` |
| `view('student.book-mentor', ...)` | `view('bookings::student.create', ...)` |
| `view('student.office-hours')` | `view('office-hours::student.index')` |
| `view('student.feedback')` | `view('feedback::student.index')` |
| `view('student.bookings')` | `view('bookings::student.index')` |
| `view('student.mentor-notes')` | `view('mentor-notes::mentor.index')` |
| `view('student.support')` | `view('support::shared.create')` |
| `view('student.settings')` | `view('settings::student.index')` |
| `view('mentor.dashboard', ...)` | `view('discovery::mentor.dashboard', ...)` |
| `view('mentor.bookings')` | `view('bookings::mentor.index')` |
| `view('mentor.earnings')` | `view('payments::mentor.earnings')` |
| `view('mentor.availability')` | `view('office-hours::mentor.schedules')` |
| `view('mentor.students')` | `view('mentor-notes::mentor.students')` |
| `view('mentor.feedback')` | `view('feedback::mentor.index')` |
| `view('mentor.profile')` | `view('settings::mentor.profile')` |
| `view('mentor.settings')` | `view('settings::mentor.index')` |
| `view('admin.dashboard')` | `view('payments::admin.dashboard')` |

---

## Part 6: Middleware — What's Needed

### Already Available (no install needed)

| Middleware | Source | Purpose |
|-----------|--------|---------|
| `auth` | Laravel built-in | Require session login |
| `guest` | Laravel built-in | Redirect logged-in users away from login/register |
| `throttle:5,60` | Laravel built-in | Rate limit (5 requests per 60 min) |
| `verified` | Laravel built-in | Require email verification (optional) |

### Must Register (already installed, just need aliasing)

In `bootstrap/app.php` → `withMiddleware()`:

```php
->withMiddleware(function (Middleware $middleware) {

    // Spatie role/permission middleware aliases
    $middleware->alias([
        'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);

    // Exclude Stripe webhook routes from CSRF — they come from Stripe servers, not browsers
    $middleware->validateCsrfTokens(except: [
        'webhooks/stripe',
        'webhooks/stripe/connect',
    ]);
})
```

### Custom Middleware to Create

#### 1. `EnsureFeedbackCompleted` — Block booking if feedback overdue

```
Location: app/Http/Middleware/EnsureFeedbackCompleted.php
```

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFeedbackCompleted
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Check if there's any completed booking older than 24h with missing feedback
        $pendingFeedback = \Modules\Bookings\app\Models\Booking::where('student_id', $user->id)
            ->where('status', 'completed')
            ->where('student_feedback_done', false)
            ->where('feedback_due_at', '<', now())
            ->exists();

        if ($pendingFeedback) {
            return redirect()
                ->route('feedback.pending')
                ->with('warning', 'Please complete your pending feedback before making new bookings.');
        }

        return $next($request);
    }
}
```

Register alias in `bootstrap/app.php`:
```php
$middleware->alias([
    // ... existing aliases ...
    'feedback.required' => \App\Http\Middleware\EnsureFeedbackCompleted::class,
]);
```

Use on booking create route:
```php
Route::get('/mentors/{mentorId}/book', [BookingController::class, 'create'])
     ->middleware(['auth', 'role:student', 'feedback.required'])
     ->name('bookings.create');
```

#### 2. `EnsureUserIsActive` — Block suspended/inactive users

```
Location: app/Http/Middleware/EnsureUserIsActive.php
```

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->is_active) {
            Auth::logout();
            return redirect()
                ->route('auth.login')
                ->withErrors(['email' => 'Your account has been suspended. Contact support.']);
        }

        return $next($request);
    }
}
```

Register and apply **globally** to authenticated routes:
```php
$middleware->alias([
    'active' => \App\Http\Middleware\EnsureUserIsActive::class,
]);
```

#### 3. `EnsureMentorApproved` — Only approved mentors access mentor routes

```
Location: app/Http/Middleware/EnsureMentorApproved.php
```

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMentorApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user->hasRole('mentor')) {
            $mentor = $user->mentor;  // relationship to mentors table
            if (!$mentor || $mentor->status !== 'active') {
                return redirect()
                    ->route('auth.login')
                    ->withErrors(['role' => 'Your mentor application is still pending approval.']);
            }
        }

        return $next($request);
    }
}
```

Register alias:
```php
$middleware->alias([
    'mentor.approved' => \App\Http\Middleware\EnsureMentorApproved::class,
]);
```

Apply to all mentor routes:
```php
Route::middleware(['auth', 'role:mentor', 'mentor.approved'])->prefix('mentor')...
```

---

### Middleware Summary Table

| Middleware | Alias | Where Used | Create? |
|-----------|-------|-----------|---------|
| `auth` | built-in | All protected routes | No |
| `guest` | built-in | Login/Register routes | No |
| `role:student` | Spatie (register alias) | Student-only routes | Register alias only |
| `role:mentor` | Spatie (register alias) | Mentor-only routes | Register alias only |
| `role:admin` | Spatie (register alias) | Admin-only routes | Register alias only |
| `permission:...` | Spatie (register alias) | Fine-grained endpoints | Register alias only |
| `throttle:5,60` | built-in | Support ticket POST | No |
| `throttle:10,1` | built-in | Login POST (brute force) | No |
| `feedback.required` | custom | Booking create route | ✅ Create |
| `active` | custom | All auth routes globally | ✅ Create |
| `mentor.approved` | custom | All mentor routes | ✅ Create |

---

## Part 7: Register Blade — `@extends` Fix Needed

The current `register.blade.php` uses:
```blade
@extends('layouts.app')
```

After moving to `Modules/Auth/resources/views/register.blade.php`, the layout reference stays the same because `layouts.app` is in the **root** `resources/views/` — which is still on the view search path. ✅ No change needed for `@extends`.

**BUT** the CSS route needs updating:
```blade
{{-- Current (will break after moving): --}}
<link rel="stylesheet" href="{{ asset('assets/css/register-signup.css') }}" />

{{-- After module setup, if CSS is inside the module: --}}
@vite(['Modules/Auth/resources/css/auth.css'])
{{-- OR keep as asset() if the CSS stays in public/assets/: --}}
<link rel="stylesheet" href="{{ asset('assets/css/register-signup.css') }}" />
```

👉 **Recommendation:** Keep the `asset()` approach for now since the CSS files are in `public/assets/`. Only switch to `@vite()` per-module when you start module-specific Vite builds.

**Route name fix** — the register form action currently uses:
```blade
<form action="{{ route('register') }}">
```

After moving routes to the Auth module, update `register.blade.php` to:
```blade
<form action="{{ route('auth.register.post') }}">
```
And close link:
```blade
<a href="{{ route('auth.login') }}">Sign in</a>
```

Same for `login.blade.php`:
```blade
<form action="{{ route('auth.login.post') }}">
<a href="{{ route('auth.register') }}">Sign up</a>
<a href="{{ route('auth.password.request') }}">Forgot password?</a>
```

---

## Part 8: Complete Move Checklist (Ordered Steps)

### Step 1 — Generate modules
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

### Step 2 — Update `users` migration as shown in Part 1
Edit: `database/migrations/0001_01_01_000000_create_users_table.php`

### Step 3 — Move migrations (copy then delete originals)

**Move to `Modules/Auth/database/migrations/`:**
```
0001_01_01_000000_create_users_table.php   (updated version)
2026_04_04_000002_create_oauth_tokens_table.php
2026_04_04_000024_create_admin_logs_table.php
```
**Create new in `Modules/Auth/database/migrations/`:**
```
2026_04_04_000003_create_password_resets_table.php   (new file from Part 1)
```

**Move to `Modules/Settings/database/migrations/`:**
```
2026_04_04_000004_create_user_settings_table.php
2026_04_04_000007_create_mentors_table.php
2026_04_04_000009_create_mentor_services_table.php
```

**Move to `Modules/Institutions/database/migrations/`:**
```
2026_04_04_000005_create_universities_table.php
2026_04_04_000006_create_university_programs_table.php
```

**Move to `Modules/Payments/database/migrations/`:**
```
2026_04_04_000008_create_services_config_table.php
2026_04_04_000012_create_user_credits_table.php
2026_04_04_000013_create_office_hours_subscriptions_table.php
2026_04_04_000015_create_credit_transactions_table.php
2026_04_04_000016_create_stripe_webhooks_table.php
2026_04_04_000017_create_mentor_payouts_table.php
```

**Move to `Modules/OfficeHours/database/migrations/`:**
```
2026_04_04_000010_create_office_hour_schedules_table.php
2026_04_04_000011_create_office_hour_sessions_table.php
```

**Move to `Modules/Bookings/database/migrations/`:**
```
2026_04_04_000014_create_bookings_table.php
2026_04_04_000018_create_chats_table.php
```

**Move to `Modules/Feedback/database/migrations/`:**
```
2026_04_04_000019_create_feedback_table.php
2026_04_04_000020_create_mentor_feedback_table.php
2026_04_04_000022_create_mentor_ratings_table.php
```

**Move to `Modules/MentorNotes/database/migrations/`:**
```
2026_04_04_000021_create_mentor_notes_table.php
```

**Move to `Modules/Support/database/migrations/`:**
```
2026_04_04_000023_create_support_tickets_table.php
```

**Delete:**
```
2024_01_01_000025_spatie_permission_tables.php.note  (not a real migration)
```

### Step 4 — Move Blade files as mapped in Part 4

### Step 5 — Update route names in Blade files (Part 5)

### Step 6 — Register Spatie middleware + custom middleware (Part 6)

### Step 7 — Register custom middleware in `bootstrap/app.php`

### Step 8 — Update `User` model (already done — `HasRoles` is there ✅)

### Step 9 — Add `is_active` + `avatar_url` to `$fillable` in `User` model
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'avatar_url',   // add
    'is_active',    // add
];
```

### Step 10 — Run migrations
```bash
php artisan module:migrate          # all module migrations
php artisan migrate                 # root migrations (cache, jobs, spatie)
```

### Step 11 — Seed roles + permissions
```bash
php artisan db:seed --class=Modules\\Auth\\Database\\Seeders\\RolePermissionSeeder
```

---

*All file paths verified against actual directory scan of `c:\Users\Rauf\gradspath-dashboard` — April 11, 2026*
