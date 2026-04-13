# Grads Paths Master Plan (Single Source of Truth)

## 1. Purpose

This document is the single planning source for the Grads Paths application.
It replaces the older planning documents in the project root, while keeping:

- milestone_1.md
- milestone_2.md
- milestone_3.md
- agent.md

This plan is aligned to the current codebase state (routes and migrations), not older draft assumptions.

## 2. System Architecture

### 2.1 Stack

- Laravel (modular structure)
- nWidart modules
- Spatie roles/permissions
- Blade for UI pages

### 2.2 Active modules

- Auth
- Discovery
- Institutions
- Settings
- Bookings
- Payments
- Feedback
- Support
- OfficeHours (module exists; route file currently placeholder)
- MentorNotes (module exists; route file currently placeholder)

### 2.3 Route style in current code

- Web route model under module route files
- Role middleware gates by student, mentor, admin
- Admin areas are under /admin/\* prefixes

### 2.4 Migration source of truth

- Module service providers load migrations from each module database/migrations directory
- Root database/migrations is also present for framework/system migrations and includes duplicate copies of some domain migrations
- For this master plan, schema is anchored to the currently loaded migration definitions in code

## 3. Role Sections

### 3.1 Admin section

Admin owns platform governance, analytics, moderation, and manual controls.

Core responsibilities:

- View admin dashboard
- Manage users (view, show, activate/deactivate, delete)
- Manage mentor lifecycle (pending list, approve, reject, pause)
- Manage services and global pricing inputs
- Manual credit adjustment and mentor amendment paths
- Moderate feedback (update/delete)
- Manage institutions (create/update/delete)
- Handle support tickets
- Review admin logs/audit trail

Current admin route surface in code:

- /admin (admin login page)
- /admin/login
- /admin/users
- /admin/users/{id}
- /admin/users/{id}/toggle-active
- /admin/mentors/pending
- /admin/mentors/{id}/approve
- /admin/mentors/{id}/reject
- /admin/mentors/{id}/pause
- /admin/logs
- /admin/dashboard
- /admin/feedback/{id}
- /admin/institutions
- /admin/institutions/{id}
- /admin/services
- /admin/services/{id}
- /admin/manual/credits/adjust
- /admin/manual/mentors/amend
- /admin/tickets
- /admin/tickets/{id}

Data domains mainly touched by admin:

- users, mentors, mentor_services
- services_config
- universities, university_programs
- feedback, mentor_ratings
- user_credits, credit_transactions
- support_tickets
- admin_logs

### 3.2 Student section

Student is the primary booking-side actor.

Core responsibilities:

- Register/login/logout/reset password
- Use dashboard and mentor discovery
- Browse institutions
- Book, view, and cancel sessions
- Purchase credits and view balance
- Submit post-session feedback
- Open and track support tickets
- Update settings

Current student route surface in code:

- /login, /register, /forgot-password, /reset-password/{token}
- /student/dashboard
- /student/explore
- /student/mentors
- /student/mentors/{id}
- /student/institutions
- /student/institutions/{id}
- /student/bookings
- /student/bookings/create
- /student/book-mentor/{id}
- /student/bookings/{id}
- /student/bookings/{id}/cancel
- /student/store
- /student/credits/balance
- /student/store/purchase
- /student/feedback
- /student/support
- /student/support/my-tickets
- /student/support/{id}
- /student/settings

Data domains mainly touched by students:

- bookings, chats
- user_credits, credit_transactions
- student_profiles
- feedback
- support_tickets
- user_settings

### 3.3 Mentor section

Mentor is the delivery-side actor.

Current implemented mentor routes in code:

- /mentor/dashboard
- /mentor/bookings
- /mentor/bookings/{id}

Mentor-related admin operations are also active:

- pending approvals
- approve/reject/pause

Mentor module status note:

- OfficeHours module route file exists but currently has placeholder comment only
- MentorNotes module route file exists but currently has placeholder comment only

### 3.4 Booking section

Booking is the central operational flow.

Core behavior planned and/or scaffolded in code/docs:

- Create booking
- View booking details
- Cancel booking
- Participant-gated access to booking details and cancel paths
- booking_participants pivot for shared/group participant access
- Chat table exists and booking chat linkage exists in schema

Primary data domains:

- bookings
- booking_participants
- chats
- user_credits
- credit_transactions

### 3.5 Payments and credits section

Credits and payment tracking underpin booking operations.

Current scope in code:

- Student store page and purchase endpoint
- Student credits balance endpoint
- Stripe webhook endpoint
- Admin service CRUD-like update surface
- Admin manual credit adjustment endpoint

Primary data domains:

- services_config
- user_credits
- office_hours_subscriptions
- credit_transactions
- stripe_webhooks
- mentor_payouts

### 3.6 Feedback section

Feedback supports quality, moderation, and ratings.

Current scope in code:

- Student feedback list and store
- Admin feedback update/delete

Primary data domains:

- feedback
- mentor_feedback
- mentor_ratings

### 3.7 Institutions section

Institutional browsing and admin management are both present.

Current scope in code:

- Student institutions list/detail
- Admin institutions create/update/delete

Primary data domains:

- universities
- university_programs
- universities now also store `alpha_two_code`, `domains`, `web_pages`, and `state_province`

### 3.8 Support section

Support flow spans student/mentor ticket creation and admin handling.

Current scope in code:

- Student/mentor support index/store/show
- Student/mentor my tickets
- Admin ticket list/show/update

Primary data domain:

- support_tickets

## 4. Security and access control

- Spatie role and permission model is present in schema
- Route middleware enforces role-based access
- booking.participant middleware protects booking-specific routes
- admin_logs table exists for admin action traceability

## 5. Current Web Route Inventory (Code Anchored)

### 5.1 Auth

- GET /login
- POST /login
- GET /register
- POST /register
- GET /forgot-password
- POST /forgot-password
- GET /reset-password/{token}
- POST /reset-password
- POST /logout
- GET /admin
- POST /admin/login

### 5.2 Discovery

- GET /student/dashboard
- GET /student/explore
- GET /student/mentors
- GET /student/mentors/{id}
- GET /mentor/dashboard
- GET /admin/dashboard

### 5.3 Institutions

- GET /student/institutions
- GET /student/institutions/{id}
- GET /admin/institutions
- POST /admin/institutions
- PATCH /admin/institutions/{id}
- DELETE /admin/institutions/{id}

### 5.4 Bookings

- GET /student/bookings
- GET /student/bookings/create
- GET /student/book-mentor/{id}
- POST /student/bookings
- GET /student/bookings/{id}
- PATCH /student/bookings/{id}/cancel
- GET /mentor/bookings
- GET /mentor/bookings/{id}

### 5.5 Payments

- GET /student/store
- GET /student/credits/balance
- POST /student/store/purchase
- POST /webhooks/stripe
- GET /admin/services
- POST /admin/services
- PATCH /admin/services/{id}
- POST /admin/manual/credits/adjust
- POST /admin/manual/mentors/amend

### 5.6 Feedback

- GET /student/feedback
- POST /student/feedback
- PATCH /admin/feedback/{id}
- DELETE /admin/feedback/{id}

### 5.7 Settings

- GET /student/settings
- PATCH /student/settings

### 5.8 Support

- GET /student/support
- GET /student/support/my-tickets
- POST /student/support
- GET /student/support/{id}
- GET /admin/tickets
- GET /admin/tickets/{id}
- PATCH /admin/tickets/{id}

## 6. Database Schema (Exact Implementation Baseline)

Schema in this plan is derived directly from current migration files in code.

### 6.1 Domain tables

- users
- sessions
- password_reset_tokens
- oauth_tokens
- user_settings
- student_profiles
- universities
- university_programs
- mentors
- services_config
- mentor_services
- office_hour_schedules
- office_hour_sessions
- user_credits
- office_hours_subscriptions
- bookings
- booking_participants
- credit_transactions
- stripe_webhooks
- mentor_payouts
- chats
- feedback
- mentor_feedback
- mentor_notes
- mentor_ratings
- support_tickets
- admin_logs
- files

### 6.2 RBAC tables

- permissions
- roles
- model_has_permissions
- model_has_roles
- role_has_permissions

### 6.3 Framework infra tables

- cache
- cache_locks
- jobs
- job_batches
- failed_jobs

### 6.4 Exact migration blocks

Appendix A below contains verbatim Schema::create / Schema::table blocks extracted from migration code so schema details stay exact to implementation.

## 7. Execution and ownership model

- Controllers remain module-scoped
- Services own business rules and transactions
- Policies and middleware enforce boundaries
- Admin mutations should always include admin_logs writes

## 8. Implementation status notes

- Current database bootstrap uses `InstitutionsSeeder`, `ProgramsSeeder`, `ServiceConfigSeeder`, `StudentsSeeder`, `MentorsSeeder`, `OfficeHoursSeeder`, `BookingsSeeder`, `MentorNotesSeeder`, and `FeedbackSeeder` in that order after role seeding.
- `booking_participants` is the only shared booking pivot in the current implementation; `bookings.office_hour_session_id` remains the direct office-hours link.
- OfficeHours and MentorNotes route files are placeholders and need route implementation
- Duplicate migration copies exist between root and modules for several tables; team should keep one canonical location long-term to avoid maintenance drift

## Appendix A - Verbatim migration schema blocks (implementation source)

The blocks below are copied verbatim from current migration files.
Only presentation formatting (headings and code fences) is applied here.

### Modules/Auth/database/migrations/0001_01_01_000000_create_users_table.php

```php
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();        // nullable: social-only users have no password
            $table->string('avatar_url')->nullable();      // profile picture (also used by File upload system)
            $table->boolean('is_active')->default(true);  // admin can suspend users
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Performance indexes
            $table->index('is_active');
            $table->index('created_at');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });


```

### Modules/Auth/database/migrations/2026_04_12_000004_create_password_reset_tokens_table.php

```php
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('password_reset_tokens', 'email')) {
                $table->string('email')->index();
            }

            if (!Schema::hasColumn('password_reset_tokens', 'token')) {
                $table->string('token');
            }

            if (!Schema::hasColumn('password_reset_tokens', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
        });


```

### Modules/Auth/database/migrations/2026_04_04_000002_create_oauth_tokens_table.php

```php
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('google'); // extensible for future providers
            $table->string('provider_user_id');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
            $table->index('user_id');
        });


```

### Modules/Settings/database/migrations/2026_04_04_000004_create_user_settings_table.php

```php
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('theme', ['light', 'dark'])->default('light');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->timestamps();
        });


```

### Modules/Institutions/database/migrations/2026_04_04_000005_create_universities_table.php

```php
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name')->nullable(); // short version e.g. "Harvard", "Yale Law"
            $table->string('country')->default('US');
            $table->string('alpha_two_code', 2)->nullable();
            $table->json('domains')->nullable();
            $table->json('web_pages')->nullable();
            $table->string('state_province')->nullable();
            $table->string('logo_url')->nullable();
            $table->enum('tier', ['elite', 'top25', 'regional']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for filtering and search (used heavily in Institutions module)
            $table->index('tier');
            $table->index('is_active');
            $table->index('name');
        });


```

### Modules/Settings/database/migrations/2026_04_12_000010_create_student_profiles_table.php

```php
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('university_id')->nullable()->constrained('universities')->nullOnDelete();
            $table->string('institution_text')->nullable();
            $table->string('program_level')->nullable();
            $table->timestamps();
        });


```

### Modules/Institutions/database/migrations/2026_04_04_000006_create_university_programs_table.php

```php
        Schema::create('university_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->cascadeOnDelete();
            $table->string('program_name');
            $table->enum('program_type', ['mba', 'law', 'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy', 'other']);
            $table->string('description')->nullable();
            $table->integer('duration_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['university_id', 'program_type']);
            $table->index('is_active');
        });


```

### Modules/Settings/database/migrations/2026_04_04_000007_create_mentors_table.php

```php
        Schema::create('mentors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('university_id')->nullable()->constrained()->nullOnDelete();

            // Identity & display
            $table->string('title')->nullable();          // e.g. "PhD Person", "MBA"
            $table->string('grad_school_display')->nullable(); // short name: "Harvard", "Wharton", "Yale Law"
            $table->enum('mentor_type', ['graduate', 'professional']);
            $table->enum('program_type', ['mba', 'law', 'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy', 'other'])->nullable();

            // Profile content
            $table->text('bio')->nullable();
            $table->text('description')->nullable();      // longer "about" shown on expanded card
            $table->string('office_hours_schedule')->nullable(); // e.g. "Every Tuesday at 5 PM EST"

            // Media — avatar with crop metadata (processed server-side)
            $table->string('avatar_url')->nullable();
            $table->decimal('avatar_crop_zoom', 4, 2)->nullable();
            $table->decimal('avatar_crop_x', 6, 2)->nullable();
            $table->decimal('avatar_crop_y', 6, 2)->nullable();

            // External links
            $table->string('edu_email')->nullable();      // required if mentor_type = graduate
            $table->string('calendly_link')->nullable();
            $table->string('slack_link')->nullable();

            // Aggregate stats are stored in mentor_ratings (single source of truth)
            $table->boolean('is_featured')->default(false); // "Mentors of the Week"

            // Stripe Connect
            $table->string('stripe_account_id')->nullable();
            $table->boolean('payouts_enabled')->default(false);
            $table->boolean('stripe_onboarding_complete')->default(false);

            // Status lifecycle: pending → active / rejected; active ↔ paused
            $table->enum('status', ['pending', 'active', 'paused', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes for Find Mentors filters and search
            $table->index('mentor_type');
            $table->index('program_type');
            $table->index('is_featured');
            $table->index('status');
            $table->index(['university_id', 'program_type']);
        });


```

### Modules/Payments/database/migrations/2026_04_04_000008_create_services_config_table.php

```php
        Schema::create('services_config', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');               // "Program Insights", "Interview Prep"
            $table->string('service_slug')->unique();     // "program_insights", "interview_prep"
            $table->integer('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);

            // Pricing per meeting size (null = size not available for this service)
            $table->decimal('price_1on1', 8, 2)->nullable();
            $table->decimal('price_1on3_per_person', 8, 2)->nullable();
            $table->decimal('price_1on3_total', 8, 2)->nullable();
            $table->decimal('price_1on5_per_person', 8, 2)->nullable();
            $table->decimal('price_1on5_total', 8, 2)->nullable();

            // Office Hours is a special subscription-based service
            $table->boolean('is_office_hours')->default(false);
            $table->decimal('office_hours_subscription_price', 8, 2)->nullable(); // $200/month

            // Credit costs (used for deduction logic)
            $table->integer('credit_cost_1on1')->default(1);
            $table->integer('credit_cost_1on3')->default(1);
            $table->integer('credit_cost_1on5')->default(1);

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });


```

### Modules/Settings/database/migrations/2026_04_04_000009_create_mentor_services_table.php

```php
        Schema::create('mentor_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_config_id')->constrained('services_config')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['mentor_id', 'service_config_id']);
            $table->index(['mentor_id', 'is_active']);
        });


```

### Modules/OfficeHours/database/migrations/2026_04_04_000010_create_office_hour_schedules_table.php

```php
        Schema::create('office_hour_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained()->cascadeOnDelete();
            $table->enum('day_of_week', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']);
            $table->time('start_time');
            $table->string('timezone')->default('America/New_York');
            $table->enum('frequency', ['weekly', 'biweekly'])->default('weekly');
            $table->integer('max_spots')->default(3); // max 3 per session per doc
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mentor_id', 'is_active']);
        });


```

### Modules/OfficeHours/database/migrations/2026_04_04_000011_create_office_hour_sessions_table.php

```php
        Schema::create('office_hour_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('office_hour_schedules')->cascadeOnDelete();

            // Current rotated service for this session
            $table->foreignId('current_service_id')->constrained('services_config');

            // Session timing
            $table->date('session_date');
            $table->time('start_time');
            $table->string('timezone')->default('America/New_York');

            // Spot tracking (core of the booking logic)
            $table->integer('current_occupancy')->default(0);
            $table->integer('max_spots')->default(3);
            $table->boolean('is_full')->default(false);

            // First-student-choice logic
            // Once a 2nd student books, service_locked = true and cannot change
            $table->boolean('service_locked')->default(false);
            $table->foreignId('first_booker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_booked_at')->nullable();

            // 24-hour cutoff: first student can only choose service if booked >= 24hrs before session
            $table->timestamp('service_choice_cutoff_at')->nullable();

            $table->enum('status', ['upcoming', 'in_progress', 'completed', 'cancelled'])->default('upcoming');
            $table->timestamps();

            // Critical indexes for real-time spot display
            $table->index(['schedule_id', 'session_date']);
            $table->index(['status', 'session_date']);
            $table->index('is_full');
        });


```

### Modules/Payments/database/migrations/2026_04_04_000012_create_user_credits_table.php

```php
        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('balance')->default(0)->unsigned();
            $table->timestamps();
        });


```

### Modules/Payments/database/migrations/2026_04_04_000013_create_office_hours_subscriptions_table.php

```php
        Schema::create('office_hours_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Program chosen at subscribe time (for demand tracking only — credits are universal)
            $table->enum('program', ['mba', 'law', 'therapy']);

            // Stripe recurring subscription
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_customer_id')->nullable();

            // Credits granted per cycle
            $table->integer('credits_per_cycle')->default(5);

            // Billing period (populated/updated on every Stripe webhook)
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();

            $table->enum('status', ['active', 'cancelled', 'past_due', 'incomplete'])->default('active');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('stripe_subscription_id');
        });


```

### Modules/Bookings/database/migrations/2026_04_04_000014_create_bookings_table.php

```php
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('service_config_id')->constrained('services_config');

            // Office Hours sessions get linked here; null for 1:1 / group bookings
            $table->foreignId('office_hour_session_id')->nullable()->constrained('office_hour_sessions')->nullOnDelete();

            // Session details
            $table->enum('meeting_size', ['1on1', '1on3', '1on5', 'office_hours'])->default('1on1');
            $table->timestamp('session_at');
            $table->string('session_timezone')->nullable(); // mentor/session timezone for UI rendering
            $table->integer('duration_minutes')->default(60);

            // Meeting link (generated by Zoom/Google Meet API on booking confirmation)
            $table->string('meeting_link')->nullable();
            $table->enum('meeting_type', ['zoom', 'google_meet'])->default('zoom');

            // Credits
            $table->integer('credits_charged')->default(1);

            // Status lifecycle
            // cancelled_pending_refund: cancelled but refund goes through support (as per template flow)
            $table->enum('status', [
                'pending',
                'confirmed',
                'completed',
                'cancelled',
                'cancelled_pending_refund',
                'no_show',
            ])->default('pending');

            // Cancellation tracking
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            // Mandatory post-session feedback enforcement
            // Both flags must be true within 24hrs or platform restricts the user
            $table->timestamp('feedback_due_at')->nullable();
            $table->boolean('student_feedback_done')->default(false);
            $table->boolean('mentor_feedback_done')->default(false);

            // Group booking: one person pays for the full group
            $table->boolean('is_group_payer')->default(false);
            $table->foreignId('group_payer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes for calendar, dashboard, and admin queries
            $table->index(['student_id', 'status', 'session_at']);
            $table->index(['mentor_id', 'status', 'session_at']);
            $table->index(['status', 'session_at']);
            $table->index('feedback_due_at');
            $table->index('session_at');
        });


```

### Modules/Bookings/database/migrations/2026_04_12_000011_create_booking_participants_table.php

```php
        Schema::create('booking_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('participant_role')->default('student');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['booking_id', 'user_id']);
            $table->index(['user_id', 'participant_role']);
        });


```

### Modules/Payments/database/migrations/2026_04_04_000015_create_credit_transactions_table.php

```php
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // nullable: some transactions (admin manual) are not tied to a booking
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            // nullable: some transactions (admin manual) are not tied to a subscription
            $table->foreignId('subscription_id')->nullable()->constrained('office_hours_subscriptions')->nullOnDelete();

            // Transaction type
            $table->enum('type', ['purchase', 'subscription', 'deduction', 'refund', 'manual']);

            // Positive = credits added, negative = credits deducted
            $table->integer('amount');

            // Balance snapshot after this transaction (for audit/debugging)
            $table->integer('balance_after');

            // Stripe references
            $table->string('stripe_payment_id')->nullable();
            $table->string('stripe_event_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();

            // For subscription credits: which program
            $table->enum('office_hours_program', ['mba', 'law', 'therapy'])->nullable();

            // Human-readable description for admin audit trail
            $table->string('description')->nullable();

            // Manual transactions: who did it
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // Indexes for financial reporting and per-user history
            $table->index(['user_id', 'created_at']);
            $table->index('type');
            $table->index('stripe_payment_id');
        });


```

### Modules/Payments/database/migrations/2026_04_04_000016_create_stripe_webhooks_table.php

```php
        Schema::create('stripe_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();    // Stripe's evt_xxx ID — unique constraint is the guard
            $table->string('event_type');            // e.g. checkout.session.completed
            $table->json('payload');                 // full raw payload for debugging/replay
            $table->boolean('processed')->default(false);
            $table->string('error_message')->nullable(); // populated if processing failed
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

            $table->index('event_id');
            $table->index(['processed', 'received_at']);
        });


```

### Modules/Payments/database/migrations/2026_04_04_000017_create_mentor_payouts_table.php

```php
        Schema::create('mentor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('stripe_transfer_id')->nullable()->unique();
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('failure_reason')->nullable();
            $table->timestamp('payout_date')->nullable();
            $table->timestamps();

            $table->index(['mentor_id', 'status']);
        });


```

### Modules/Bookings/database/migrations/2026_04_04_000018_create_chats_table.php

```php
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('message_text');
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->useCurrent();

            // Indexes for loading chat thread and unread counts
            $table->index(['booking_id', 'sent_at']);
            $table->index(['receiver_id', 'is_read']);
        });


```

### Modules/Feedback/database/migrations/2026_04_04_000019_create_feedback_table.php

```php
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();

            // Student submission
            $table->tinyInteger('stars')->unsigned();                // 1–5
            $table->tinyInteger('preparedness_rating')->unsigned()->nullable(); // mentor preparedness
            $table->text('comment');
            $table->boolean('recommend')->default(true);
            $table->string('service_type')->nullable();              // denormalized for filtering

            // Verification — feedback only submittable after booking status = completed
            $table->boolean('is_verified')->default(true);

            // Admin moderation (Station 3 in Manual Controls)
            // original_comment is immutable — never overwritten, only comment can be amended
            $table->text('original_comment')->nullable();            // populated on first admin amendment
            $table->boolean('is_visible')->default(true);            // false = soft hidden by admin
            $table->text('admin_note')->nullable();
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('amended_at')->nullable();

            // Mentor reply (optional feature)
            $table->text('mentor_reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            // Indexes for aggregation queries and filtering
            $table->unique(['booking_id', 'student_id']);
            $table->index(['mentor_id', 'is_visible']);
            $table->index(['mentor_id', 'stars']);
            $table->index('created_at');
            $table->index('service_type');
        });


```

### Modules/Feedback/database/migrations/2026_04_04_000020_create_mentor_feedback_table.php

```php
        Schema::create('mentor_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            $table->tinyInteger('engagement_score')->unsigned()->nullable(); // 1–5
            $table->text('notes')->nullable();                               // general notes
            $table->timestamps();

            $table->unique(['booking_id', 'mentor_id']); // one form per mentor per session
            $table->index(['mentor_id', 'student_id']);
        });


```

### Modules/MentorNotes/database/migrations/2026_04_04_000021_create_mentor_notes_table.php

```php
        Schema::create('mentor_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // Links to the specific session this note is about (optional but recommended)
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            $table->date('session_date');
            $table->string('service_type')->nullable();  // denormalized for display

            // 5 structured questions from the template (demo8.html)
            $table->text('worked_on')->nullable();              // Q1: What did you work on during this session?
            $table->text('next_steps')->nullable();             // Q2: What should happen next, and what does the user need most?
            $table->text('session_result')->nullable();         // Q3: What was the result of the session?
            $table->text('strengths_challenges')->nullable();   // Q4: One strength and one challenge from the session?
            $table->text('other_notes')->nullable();            // Q5: Any other notes to share?

            // Soft delete — admin only, record stays in DB for backup
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();

            // Critical indexes for search (ILIKE on student name requires join — index student_id and mentor_id)
            $table->index(['mentor_id', 'student_id']);
            $table->index(['mentor_id', 'is_deleted']);
            $table->index('session_date');
        });


```

### Modules/Feedback/database/migrations/2026_04_04_000022_create_mentor_ratings_table.php

```php
        Schema::create('mentor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->unique()->constrained()->cascadeOnDelete();

            $table->decimal('avg_stars', 3, 2)->default(0.00);
            $table->decimal('recommend_rate', 5, 2)->default(0.00);  // percentage e.g. 96.00
            $table->integer('total_reviews')->default(0);
            $table->integer('total_sessions')->default(0);

            // Most mentioned keyword from comment frequency analysis
            $table->string('top_tag')->nullable();                   // e.g. "Clear advice"
            $table->json('top_tags_json')->nullable();               // full list: ["Clear advice","honest","strategic"]

            $table->timestamp('recalculated_at')->nullable();
            $table->timestamps();
        });


```

### Modules/Support/database/migrations/2026_04_04_000023_create_support_tickets_table.php

```php
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Auto-generated unique ref e.g. SUP-00102
            $table->string('ticket_ref')->unique();

            $table->string('subject');
            $table->text('message');

            // Sanitized on write — raw stored for audit
            $table->text('message_raw')->nullable();

            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');

            // Admin response
            $table->text('admin_reply')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });


```

### Modules/Auth/database/migrations/2026_04_04_000024_create_admin_logs_table.php

```php
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();

            // What station/action was performed
            $table->string('action');           // e.g. "amend_mentor", "manual_refund", "delete_feedback"
            $table->string('target_table');     // e.g. "mentors", "feedback", "user_credits"
            $table->unsignedBigInteger('target_id')->nullable();

            // State snapshots for full auditability
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();

            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('notes')->nullable();  // optional admin comment on why action was taken

            $table->timestamp('created_at')->useCurrent();

            $table->index(['admin_id', 'created_at']);
            $table->index(['target_table', 'target_id']);
            $table->index('action');
        });


```

### Modules/Auth/database/migrations/2026_04_04_000026_create_files_table.php

```php
        Schema::create('files', function (Blueprint $table) {
            $table->id();

            // Owner — who uploaded this file
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphic relationship — attach file to any model (nullable = standalone)
            $table->nullableMorphs('fileable'); // creates: fileable_type (string), fileable_id (bigint)

            // File identity
            $table->string('original_name');            // original filename user uploaded, e.g. "transcript.pdf"
            $table->string('stored_name');              // UUID-based stored name, e.g. "a1b2c3d4.pdf"
            $table->string('path');                     // full storage path, e.g. "uploads/2026/04/a1b2c3d4.pdf"
            $table->string('disk')->default('public');  // storage disk: 'public', 'local', 's3'
            $table->string('extension', 20);            // e.g. "pdf", "jpg", "docx"
            $table->string('mime_type', 100);           // e.g. "application/pdf", "image/jpeg"
            $table->unsignedBigInteger('size');         // file size in bytes

            // File type/purpose — helps filter and apply different rules per type
            $table->enum('type', [
                'avatar',       // profile picture (user or mentor)
                'document',     // transcripts, CVs, application docs
                'attachment',   // support ticket or booking attachments
                'receipt',      // payment receipts / invoices
                'other',        // anything else
            ])->default('other');

            // Visibility — controls whether the file URL is public or signed
            $table->boolean('is_public')->default(false);  // true = public URL, false = private signed URL

            // Soft approach: flag as deleted rather than physically removing immediately
            // Physical deletion is handled by a scheduled cleanup job
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();    // when flagged for deletion

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'type']);             // "all my documents", "all my avatars"
            // Note: nullableMorphs('fileable') already creates index on [fileable_type, fileable_id]
            $table->index('created_at');                    // admin audit / date filtering
            $table->index('is_deleted');                    // cleanup job filter
        });


```

### database/migrations/0001_01_01_000001_create_cache_table.php

```php
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });


```

### database/migrations/0001_01_01_000002_create_jobs_table.php

```php
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });


```

### database/migrations/2026_04_10_144806_create_permission_tables.php

```php
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->id(); // permission id
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->id(); // role id
            if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($tableNames['roles'])
                ->cascadeOnDelete();
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });


```
