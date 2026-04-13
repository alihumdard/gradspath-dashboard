# Milestone 1 - Foundation and Core Student Flow

## 1. Milestone Goal
Deliver a fully runnable modular Laravel backend where a student can:
- Register or log in
- Discover mentors and institutions
- Purchase credits
- Book a 1on1 session
- Submit feedback after completion
- Open support tickets

This milestone is production-safe for core usage and admin operational control.

## 2. Architecture Rules (Non-Negotiable)
- Use nWidart modules only.
- No business logic classes in root app directory.
- Every feature is implemented in its owning module.
- Reuse existing Blade files already present in each module. Do not create new Blade files.
- Follow Laravel 12 official patterns for app bootstrapping and middleware setup.
- Data access rule: use 80% Eloquent and 20% DB queries only for heavy/complex query paths.
- Feature completion gate: after implementing or changing any feature, write/update its automated test case, run the relevant tests, and only mark the feature complete when tests pass.
- Test file convention: place tests in the related file under tests/Feature (example: Auth module -> AuthTest). If a related test file does not exist, create a new feature test file with the best-fitting module/feature name.
- Each module is self-contained with its own:
  - Controllers
  - Models
  - Services
  - Requests
  - Policies
  - Routes
  - Views
  - Jobs/events/listeners (when needed)

### Laravel 12 alignment requirements
- Official reference source: https://laravel.com/docs/12.x
- Register middleware aliases and middleware groups in bootstrap/app.php.
- Register module service providers in bootstrap/providers.php.
- Configure webhook CSRF exceptions in bootstrap/app.php (not legacy Kernel-based config).
- Keep route definitions in each module's routes/web.php and authorization in Form Requests/policies/middleware.
- Default to Eloquent-first repository/service patterns; use DB::table/DB::raw selectively for reporting/aggregation/performance-critical queries.
- After implementing each feature, cross-check the code against relevant Laravel 12 docs sections and resolve any mismatch before marking complete.

## 3. Modules and Features in Scope

### Auth
- Email/password registration and login
- Password reset
- Role assignment (student, mentor, admin)
- Admin authentication flow via shared session login plus role middleware
- Social login (Socialite) is out of current Milestone 1 runtime scope

### Discovery
- Student dashboard
- Mentor listing and mentor detail
- Basic search and filters

### Institutions
- University list, detail, programs, mentors
- Admin manage institutions and programs

### Bookings
- Create booking (1on1)
- View booking details
- Cancel booking (pending refund state)

### Payments
- Credit balance endpoint
- Credit purchase via Stripe checkout
- Stripe webhook idempotent processing
- Admin pricing management and manual credit adjustment

### Feedback
- Student post-session feedback
- Admin moderation endpoints
- Mentor rating aggregation update

### Support
- Student/mentor create ticket
- User ticket history
- Admin ticket response and status updates

### Settings (Minimal)
- Student settings read/update (theme, notifications)

## 4. Module Directory Standard (Apply to Every Module)
Use this exact structure per module:

- Modules/<ModuleName>/app/Http/Controllers/
- Modules/<ModuleName>/app/Http/Requests/
- Modules/<ModuleName>/app/Models/
- Modules/<ModuleName>/app/Services/
- Modules/<ModuleName>/app/Policies/
- Modules/<ModuleName>/app/Traits/
- Modules/<ModuleName>/app/Jobs/
- Modules/<ModuleName>/app/Events/
- Modules/<ModuleName>/app/Listeners/
- Modules/<ModuleName>/database/migrations/
- Modules/<ModuleName>/routes/web.php
- Modules/<ModuleName>/resources/views/

## 5. Database Schema for Milestone 1

### Tables to create now
1. users
2. password_reset_tokens
3. user_settings
4. universities
5. university_programs
6. mentors
7. services_config
8. mentor_services
9. user_credits
10. bookings
11. credit_transactions
12. stripe_webhooks
13. feedback
14. mentor_ratings
15. support_tickets
16. admin_logs

### Relationship map
- users 1-1 mentors
- users 1-1 user_settings
- users 1-1 user_credits
- universities 1-many university_programs
- universities 1-many mentors
- mentors many-many services_config via mentor_services
- users (student) 1-many bookings
- mentors 1-many bookings
- services_config 1-many bookings
- users 1-many credit_transactions
- bookings 1-many credit_transactions (optional link for audit)
- bookings 1-many feedback entries by unique(booking_id, student_id)
- mentors 1-1 mentor_ratings
- users 1-many support_tickets
- users (admin) 1-many admin_logs

### Critical constraints
- feedback unique key must be (booking_id, student_id)
- stripe_webhooks unique(event_id), processed boolean
- bookings store session_at in UTC and session_timezone string

## 6. Models to Implement

### Auth module models
- User
- AdminLog
- Password reset tokens are broker-managed in password_reset_tokens (no dedicated Eloquent model).

### Settings module models
- UserSetting

### Institutions module models
- University
- UniversityProgram

### Discovery/Settings shared mentor model ownership
- Mentor (owned by Settings module)

### Payments module models
- ServiceConfig
- UserCredit
- CreditTransaction
- StripeWebhook

### Bookings module models
- Booking

### Feedback module models
- Feedback
- MentorRating

### Support module models
- SupportTicket

## 7. Controllers to Implement

### Auth module
- Auth/RegisterController
- Auth/LoginController
- Auth/PasswordController
- Admin/AuthController

### Discovery module
- Student/DashboardController
- Student/MentorSearchController

### Institutions module
- Student/InstitutionsController
- Admin/InstitutionsController

### Bookings module
- Student/BookingController
- Mentor/BookingsController

### Payments module
- Student/CreditsController
- Payments/StripeWebhookController
- Admin/OverviewController
- Admin/ServicesController
- Admin/ManualActionsController

### Feedback module
- Student/FeedbackController
- Admin/FeedbackController

### Support module
- Support/TicketsController
- Admin/SupportTicketsController

### Settings module
- Student/SettingsController

## 8. Services to Implement
- AuthService (Auth)
- MentorDiscoveryService (Discovery)
- InstitutionService (Institutions)
- BookingService (Bookings)
- CreditService (Payments)
- StripeWebhookService (Payments)
- FeedbackService (Feedback)
- RatingAggregationService (Feedback)
- SupportTicketService (Support)
- AdminAuditService (Auth)

Keep controllers thin: validate -> call service -> return response/view.

## 9. Traits and Shared Classes

### Traits
- Modules/Auth/app/Traits/LogsAdminActions.php
  - Used by admin services/controllers for immutable action logs.

### Additional classes required

#### Form Requests
- RegisterRequest
- LoginRequest
- ForgotPasswordRequest
- ResetPasswordRequest
- SearchMentorsRequest
- FilterUniversitiesRequest
- CreateBookingRequest
- CancelBookingRequest
- PurchaseCreditsRequest
- StoreFeedbackRequest
- AmendFeedbackRequest
- CreateSupportTicketRequest
- ReplySupportTicketRequest
- UpdateSettingsRequest

#### Policies
- BookingPolicy
- FeedbackPolicy
- SupportTicketPolicy

#### Middleware (module-scoped)
- EnsureActiveAccount (Auth)
- EnsureBookingParticipant (Bookings)

## 10. Web Routes (Milestone 1)
All routes are module web routes in each module's `routes/web.php`.

### Auth web routes
- GET /login
- POST /login
- GET /register
- POST /register
- GET /forgot-password
- POST /forgot-password
- GET /reset-password/{token}
- POST /reset-password
- POST /logout

### Discovery web routes
- GET /mentor/dashboard
- GET /student/dashboard
- GET /student/explore
- GET /student/mentors
- GET /student/mentors/{id}

### Institutions web routes
- GET /student/institutions
- GET /student/institutions/{id}
- GET /admin/institutions
- POST /admin/institutions
- PATCH /admin/institutions/{id}
- DELETE /admin/institutions/{id}

### Bookings web routes
- GET /student/bookings
- GET /student/bookings/create
- GET /student/book-mentor/{id}
- POST /student/bookings
- GET /student/bookings/{id}
- PATCH /student/bookings/{id}/cancel
- GET /mentor/bookings
- GET /mentor/bookings/{id}

### Payments web routes
- GET /student/store
- GET /student/credits/balance
- POST /student/store/purchase
- POST /webhooks/stripe
- GET /admin/dashboard
- GET /admin/services
- POST /admin/services
- PATCH /admin/services/{id}
- POST /admin/manual/credits/adjust
- POST /admin/manual/mentors/amend

### Feedback web routes
- GET /student/feedback
- POST /student/feedback
- PATCH /admin/feedback/{id}
- DELETE /admin/feedback/{id}

### Support web routes
- GET /student/support
- POST /student/support
- GET /student/support/my-tickets
- GET /student/support/{id}
- GET /admin/tickets
- GET /admin/tickets/{id}
- PATCH /admin/tickets/{id}

### Settings web routes
- GET /student/settings
- PATCH /student/settings

### Admin web routes
- Admin access is protected by role:admin middleware after shared session login.
- Dedicated /admin/auth/login and /admin/auth/logout endpoints are out of current milestone runtime scope.

## 11. Blade Structure (UI Per Module)

Blade policy for Milestone 1:
- Use only existing files under Modules/*/resources/views.
- Do not create new Blade files.
- If UI scope expands, extend content inside existing files.
- Login and signup UI must stay in landing page file resources/views/landing_page/index.html (and its Blade counterpart when rendered). Do not create dedicated login/signup Blade pages.

### Auth views
- Modules/Auth/resources/views/forgot-password.blade.php
- Modules/Auth/resources/views/reset-password.blade.php
- Login and signup are rendered from resources/views/landing_page/index.html via modal flow.

### Discovery views
- Modules/Discovery/resources/views/student/dashboard.blade.php
- Modules/Discovery/resources/views/student/explore.blade.php
- Modules/Discovery/resources/views/student/mentor-profile.blade.php
- Modules/Discovery/resources/views/student/mentors/index.blade.php
- Modules/Discovery/resources/views/student/mentors/show.blade.php
- Modules/Discovery/resources/views/mentor/dashboard.blade.php

### Institutions views
- Modules/Institutions/resources/views/student/index.blade.php
- Modules/Institutions/resources/views/student/show.blade.php
- Modules/Institutions/resources/views/admin/institutions/index.blade.php

### Bookings views
- Modules/Bookings/resources/views/student/create.blade.php
- Modules/Bookings/resources/views/student/index.blade.php
- Modules/Bookings/resources/views/student/show.blade.php
- Modules/Bookings/resources/views/mentor/bookings/index.blade.php
- Modules/Bookings/resources/views/mentor/index.blade.php

### Payments views
- Modules/Payments/resources/views/student/store.blade.php
- Modules/Payments/resources/views/student/store/index.blade.php
- Modules/Payments/resources/views/admin/dashboard.blade.php
- Modules/Payments/resources/views/admin/overview/index.blade.php
- Modules/Payments/resources/views/admin/services/index.blade.php

### Feedback views
- Modules/Feedback/resources/views/student/index.blade.php
- Modules/Feedback/resources/views/student/create.blade.php
- Modules/Feedback/resources/views/mentor/index.blade.php
- Modules/Feedback/resources/views/admin/feedback/index.blade.php

### Support views
- Modules/Support/resources/views/shared/create.blade.php
- Modules/Support/resources/views/student/support/index.blade.php
- Modules/Support/resources/views/student/support/create.blade.php
- Modules/Support/resources/views/admin/support/index.blade.php
- Modules/Support/resources/views/admin/support/show.blade.php

### Settings views
- Modules/Settings/resources/views/student/index.blade.php
- Modules/Settings/resources/views/student/settings/index.blade.php

## 12. Jobs, Events, and Background Tasks

### Jobs
- ProcessStripeWebhookJob (Payments)
- SendBookingConfirmationJob (Bookings)
- NotifyAdminNewTicketJob (Support)
- SendUserTicketConfirmationJob (Support)

### Events
- BookingCreated
- BookingCancelled
- FeedbackSubmitted
- SupportTicketCreated
- CreditsPurchased

### Listeners
- GenerateMeetingLinkListener (can create placeholder link in M1)
- UpdateMentorRatingListener
- DispatchSupportNotificationsListener

### Scheduled tasks
- bookings:mark-completed (every 15 min)
- stripe:retry-webhooks (every 5 min)

## 13. Implementation Steps (Execution Order)
1. Scaffold modules and providers.
2. Add migrations and run module migration order.
3. Ensure DatabaseSeeder calls Modules\\Auth\\database\\seeders\\RolePermissionSeeder so student/mentor/admin roles exist before auth flows.
4. Implement models with relationships and casts.
5. Implement services and trait first.
6. Add requests, policies, middleware.
7. Add controllers and routes.
8. Reuse and update existing Blade pages per module (no new Blade files).
9. Add jobs/events/listeners.
10. Write/update feature tests for each implemented feature (auth, booking, purchase, feedback, support) in the related tests/Feature file; if no related file exists, create a new one with the best-fitting name (example: AuthTest.php for Auth), run tests immediately, and fix failures before moving on.
11. Keep auth regression coverage in tests/Feature/AuthTest.php for login, register, logout, forgot password, reset password, and active-account enforcement.
12. Run seeders and validate end-to-end flow.
13. Run Laravel 12 docs compliance review (routing, middleware, auth, password reset, validation, queues/scheduling) and fix deviations.

## 14. Done Criteria
- Student can complete register -> purchase -> booking -> feedback flow.
- Admin can log in, adjust credits, moderate feedback, and manage institutions.
- Stripe webhook is idempotent.
- All code lives in modules.
- No new Blade files were created.
- No stub endpoints left in milestone scope.
- Code behavior and conventions are cross-checked against https://laravel.com/docs/12.x and confirmed aligned.
- Every implemented feature has an automated test case and related tests are passing.
- tests/Feature/AuthTest.php passes for all auth flows in milestone scope.
- Feature test coverage is organized in related tests/Feature files, and new test files are created when a related file does not yet exist.
