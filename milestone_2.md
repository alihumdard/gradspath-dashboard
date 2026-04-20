# Milestone 2 - Mentor Workflows and Office Hours Engine

## 1. Milestone Goal

Extend the platform from core booking into full mentor operations:

- Recurring office hours with seat management
- Mentor notes after sessions
- Mentor post-session feedback
- Chat windows around session time
- Stripe Connect onboarding for mentors
- Office-hours subscription support

Milestone 2 must remain modular, runnable, and production-safe.

## 2. Architecture Rules (Carry Forward)

- No business logic outside modules.
- Keep module ownership strict.
- Reuse existing milestone 1 modules, but add missing classes inside the correct module.
- Keep each endpoint complete (validation, authorization, service, response).
- Reuse existing Blade files already present in modules. Do not create new Blade files.
- Follow Laravel 12 official docs for middleware, providers, routing, queues, and scheduling conventions.
- Data access rule: use 80% Eloquent and 20% DB queries only for heavy/complex query paths.
- Feature completion gate: after implementing or changing any feature, write/update its automated test case, run the relevant tests, and only mark the feature complete when tests pass.
- Test file convention: place tests in the related file under tests/Feature (example: Auth module -> AuthTest). If a related test file does not exist, create a new feature test file with the best-fitting module/feature name.

### Laravel 12 alignment requirements

- Official reference source: https://laravel.com/docs/12.x
- Configure middleware aliases/exceptions in bootstrap/app.php.
- Register module providers in bootstrap/providers.php.
- Keep module routes in each module's routes/web.php and avoid legacy Kernel-only assumptions.
- Implement validation and authorization through Form Requests, policies, and middleware.
- Prefer Eloquent relationships/scopes for standard CRUD and workflow reads; use DB queries for high-volume aggregations, joins, or scheduler/reporting paths when needed.
- After implementing each feature, cross-check the code against relevant Laravel 12 docs sections and resolve any mismatch before marking complete.

## 3. Modules and Features in Scope

### OfficeHours

- Mentor schedule CRUD
- Automatic session generation
- Occupancy tracking
- Service lock after second participant
- First-booker service choice cutoff logic

### MentorNotes

- Structured 5-question notes form
- Mentor-owned note listing and edits
- Admin soft-delete view for moderation/audit

### Feedback

- Mentor feedback submission
- Public mentor reply to student feedback
- Overdue feedback enforcement integration

### Bookings

- Session chat enabled only in allowed time window
- Mentor session detail enhancements

### Settings

- Mentor profile completion (social links, display fields)
- Avatar upload and crop metadata persistence
- Stripe Connect onboarding flow

### Payments

- Office-hours subscription purchase and renewal state tracking
- Subscription credit grants via webhook cycles

## 4. Database Schema for Milestone 2

### New tables to add

1. office_hour_schedules
2. office_hour_sessions
3. office_hours_subscriptions
4. chats
5. mentor_feedback
6. mentor_notes

### Existing tables updated

- bookings
    - add office_hour_session_id nullable FK
    - ensure meeting_size supports 1on1, 1on3, 1on5
- feedback
    - keep unique(booking_id, student_id)
- mentors
    - ensure Stripe Connect fields exist: stripe_account_id, payouts_enabled, stripe_onboarding_complete

### Relationship map

- mentors 1-many office_hour_schedules
- office_hour_schedules 1-many office_hour_sessions
- office_hour_sessions 1-many bookings
- bookings 1-many chats
- bookings 1-1 mentor_feedback
- bookings 1-1 mentor_notes (per mentor+student session note record)
- users 1-many office_hours_subscriptions

## 5. Models to Implement in Milestone 2

### OfficeHours module models

- OfficeHourSchedule
- OfficeHourSession

### Payments module models

- OfficeHoursSubscription

### Bookings module models

- Chat

### Feedback module models

- MentorFeedback

### MentorNotes module models

- MentorNote

### Settings module updates

- Mentor model add casts and relations needed for onboarding state.

## 6. Controllers to Implement

### OfficeHours module

- Mentor/OfficeHoursController
- Student/OfficeHoursController

### MentorNotes module

- Mentor/NotesController
- Admin/NotesController

### Feedback module

- Mentor/FeedbackController

### Bookings module

- Mentor/ChatController
- Student/ChatController

### Settings module

- Mentor/ProfileController (expand)
- Mentor/StripeController

### Payments module

- Student/SubscriptionController

## 7. Services to Implement

- OfficeHourRotationService (OfficeHours)
- SpotTrackingService (OfficeHours)
- MentorNoteService (MentorNotes)
- FeedbackEnforcementService (Feedback)
- ChatService (Bookings)
- StripeConnectService (Settings)
- SubscriptionService (Payments)
- ImageUploadService (Settings)

## 8. Traits and Required Classes

### Traits

- Modules/Auth/app/Traits/LogsAdminActions.php (reuse from M1)
- Modules/OfficeHours/app/Traits/ValidatesOfficeHourWindow.php
    - Keep small and focused (time-window checks only)

### Form Requests

- CreateScheduleRequest
- UpdateScheduleRequest
- StoreMentorNoteRequest
- UpdateMentorNoteRequest
- StoreMentorFeedbackRequest
- SendChatMessageRequest
- UpdateProfileRequest
- UploadAvatarRequest
- SubscribeOfficeHoursRequest

### Policies

- OfficeHourSchedulePolicy
- MentorNotePolicy
- ChatPolicy
- MentorFeedbackPolicy

### Middleware

- EnsureFeedbackCompliance (block actions when overdue feedback exists)
- EnsureChatWindowOpen (chat 48h before to 24h after session)

## 9. Web Routes (Milestone 2 Additions)

All routes are module web routes in each module's `routes/web.php`.

### OfficeHours web routes

- GET /office-hours
- GET /office-hours/{id}
- GET /mentor/office-hours
- POST /mentor/office-hours
- PATCH /mentor/office-hours/{id}
- DELETE /mentor/office-hours/{id}

### MentorNotes web routes

- GET /mentor/notes
- POST /mentor/notes
- GET /mentor/notes/{id}
- PATCH /mentor/notes/{id}
- GET /admin/mentor-notes
- DELETE /admin/mentor-notes/{id}

### Feedback web routes

- GET /mentor/feedback
- POST /mentor/feedback
- PATCH /mentor/feedback/{id}/reply

### Chat web routes

- GET /chat/{bookingId}
- POST /chat/{bookingId}

### Settings web routes

- GET /mentor/profile
- PATCH /mentor/profile
- POST /mentor/profile/avatar
- POST /mentor/stripe/onboard
- POST /webhooks/stripe/connect

### Payments web routes

- POST /credits/subscribe
- GET /credits/subscription

## 10. Blade Structure (UI Per Module)

Blade policy for Milestone 2:

- Use only existing files under Modules/\*/resources/views.
- Do not create new Blade files.
- Implement milestone 2 UI by extending existing files.

### OfficeHours views

- Modules/OfficeHours/resources/views/student/index.blade.php
- Modules/OfficeHours/resources/views/mentor/schedules.blade.php

### MentorNotes views

- Modules/MentorNotes/resources/views/mentor/index.blade.php
- Modules/MentorNotes/resources/views/mentor/students.blade.php

### Feedback views

- Modules/Feedback/resources/views/mentor/index.blade.php
- Modules/Feedback/resources/views/student/index.blade.php
- Modules/Feedback/resources/views/student/create.blade.php
- Modules/Feedback/resources/views/admin/feedback/index.blade.php

### Bookings/Chat views

- Modules/Bookings/resources/views/student/show.blade.php
- Modules/Bookings/resources/views/mentor/bookings/index.blade.php
- Modules/Bookings/resources/views/student/index.blade.php

### Settings mentor views

- Modules/Settings/resources/views/mentor/profile.blade.php
- Modules/Settings/resources/views/mentor/index.blade.php

### Payments subscription views

- Modules/Payments/resources/views/student/store.blade.php
- Modules/Payments/resources/views/student/store/index.blade.php

## 11. Jobs, Events, and Background Tasks

### Jobs

- GenerateWeeklySessionsJob (OfficeHours)
- BroadcastOfficeHourOccupancyJob (OfficeHours)
- CheckPendingFeedbackJob (Feedback)
- GrantSubscriptionCreditsJob (Payments)
- ProcessStripeConnectWebhookJob (Settings)

### Events

- OfficeHourSpotBooked
- OfficeHourSpotReleased
- ServiceLockedForSession
- MentorNoteSaved
- MentorFeedbackSubmitted
- ChatMessageSent
- MentorOnboardingCompleted

### Listeners

- LockServiceWhenSecondBookingListener
- UpdateOfficeHourFullStateListener
- RestrictUserOnOverdueFeedbackListener
- NotifySessionParticipantsChatOpenedListener

### Scheduled tasks

- office-hours:rotate-service (weekly)
- office-hours:generate-sessions (weekly)
- feedback:enforce-pending (every 30 min)
- bookings:open-chat-windows (hourly)

## 12. Step-by-Step Implementation Order

1. Add milestone 2 migrations and foreign keys.
2. Implement new models and relationships.
3. Implement services before controllers.
4. Add requests, policies, and middleware gates.
5. Add controllers and route groups.
6. Update existing Blade pages and wire module assets (no new Blade files).
7. Add jobs/events/listeners and scheduler entries.
8. Write/update tests for each implemented feature (office-hour seat race conditions, chat access windows, mentor note RBAC, and related flows) in the related tests/Feature file; if no related file exists, create a new one with the best-fitting name, run tests immediately, and fix failures before moving on. If auth behavior is touched, update tests/Feature/AuthTest.php as part of the same change.
9. Run Laravel 12 docs compliance review (routing, middleware, auth, password reset, validation, queues/scheduling) and fix deviations.

## 13. Done Criteria

- Mentor can create recurring office hours and students can book available seats.
- Mentor notes are private and policy-protected.
- Chat works only in allowed session window.
- Mentor feedback and reply flows are functional.
- Stripe Connect onboarding state is persisted and visible.
- Subscription credits are granted correctly and idempotently.
- No new Blade files were created.
- Code behavior and conventions are cross-checked against https://laravel.com/docs/12.x and confirmed aligned.
- Every implemented feature has an automated test case and related tests are passing.
- When milestone scope changes auth behavior, tests/Feature/AuthTest.php is updated and passing.
- Feature test coverage is organized in related tests/Feature files, and new test files are created when a related file does not yet exist.
