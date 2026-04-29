# DOCX Plan Implementation Checklist

Source plan: [docs/docx.plan.md](/home/rauf/projects/gradspath-dashboard/docs/docx.plan.md)

This file tracks how much of the backend plan appears to be implemented in the current codebase.

Status legend:

- `[x]` Done or mostly implemented
- `[-]` Partially implemented
- `[ ]` Missing or largely not implemented

## Overall Snapshot

- `Milestone 1: Admin Side + Core Foundation` -> `Mostly implemented`
- `Milestone 2: Mentor Side` -> `Partially implemented`
- `Milestone 3: Student Side + Full Integration` -> `Partially implemented`

Estimated overall completion: `~65-75%`

## Quick Status Table

| Area | Status | What This Means |
|---|---|---|
| Authentication & User Management | `Mostly Done` | Core Laravel 12 auth works, but mentor approval still differs from the plan |
| Find Mentors & Dashboard | `Mostly Done` | Search and featured data exist, but some UI/content is still fallback |
| Institutions / Explore by University | `Mostly Done` | CRUD and browse flow work, but some planned filters are missing |
| Office Hours | `Mostly Done` | Core session logic, weekly automation/rotation, capacity, and first-student choice are implemented; biweekly remains intentionally out of scope |
| Feedback & Reviews | `Partial` | Submission and moderation work, advanced analytics are incomplete |
| Mentor Notes | `Mostly Remaining` | UI and migration exist, but backend CRUD is missing |
| Bookings & Session Management | `Mostly Done` | Booking and chat work well, but planned Zoom-based meeting creation is not implemented yet |
| Support Tickets | `Partial` | Ticket creation/admin reply work, user history and rate limiting are missing |
| Settings & Profile Management | `Partial` | Basic profile updates work, but planned Zoom connection and payout/upload integrations are not finished |
| Payments & Credits | `Partial to Mostly Done` | Student credits/payments work, mentor payouts are not implemented |

## Milestone Checklist

### Milestone 1: Admin Side + Core Foundation

- `[x]` Authentication and role management
- `[x]` Admin dashboard
- `[x]` Institutions CRUD
- `[x]` Global configurations and core module setup
- `[-]` Support ticket management for admin
- `[x]` Basic analytics and moderation

### Milestone 2: Mentor Side

- `[-]` Mentor profile management
- `[-]` Office hours setup
- `[ ]` Office hours rotation automation
- `[ ]` Secure mentor notes system
- `[x]` View own bookings
- `[x]` Booking chat
- `[x]` Mentor feedback view

### Milestone 3: Student Side + Full Integration

- `[x]` Find mentors and student dashboard
- `[x]` Explore by university
- `[x]` Office hours booking entry points
- `[x]` Bookings calendar and meeting links
- `[x]` Real-time chat
- `[-]` Feedback system
- `[-]` Support tickets
- `[-]` Settings
- `[-]` Payments and credits

## Module Checklist

### 1. Authentication & User Management

Status: `Mostly Done`

- `[x]` User signup
- `[x]` Login
- `[x]` Password reset flow
- `[x]` Email verification
- `[x]` Role-based access control
- `[x]` Admin-only user management pages
- `[x]` Basic student profile creation on signup
- `[x]` Basic mentor profile creation on signup
- `[x]` Laravel 12 auth/session-based backend foundation
- `[ ]` Mentor password-based approval workflow as described in the plan
- `[ ]` Professional users exempt from `.edu` requirement

Notes:

- The current implementation uses Laravel 12 auth/session flows, which now matches the intended architecture.
- Mentor registration is auto-approved immediately.
- Registration currently enforces a `.edu`-style domain for all users.

Evidence:

- [Modules/Auth/app/Services/AuthService.php](/home/rauf/projects/gradspath-dashboard/Modules/Auth/app/Services/AuthService.php:14)
- [Modules/Auth/app/Http/Requests/RegisterRequest.php](/home/rauf/projects/gradspath-dashboard/Modules/Auth/app/Http/Requests/RegisterRequest.php:18)
- [tests/Feature/AuthTest.php](/home/rauf/projects/gradspath-dashboard/tests/Feature/AuthTest.php:26)

Remaining work:

- Implement the mentor password approval flow described in the plan.
- Relax `.edu` validation for professional users if that requirement still stands.

### 2. Find Mentors & User Dashboard

Status: `Mostly Done`

- `[x]` Student dashboard route and controller
- `[x]` Featured mentors loading
- `[x]` Mentor search
- `[x]` Filtering by mentor type, program type, and university
- `[x]` Mentor profile page
- `[x]` Credit balance display on dashboard
- `[x]` Booking entry points from mentor views
- `[-]` Fully polished dashboard content across all sections
- `[ ]` Light/dark preference visibly integrated into this flow
- `[ ]` Exact “Mentors of the Week” ranking logic from the plan

Notes:

- Backend discovery/search is in place.
- Some dashboard UI still contains fallback “coming soon” states.

Evidence:

- [Modules/Discovery/app/Services/MentorDiscoveryService.php](/home/rauf/projects/gradspath-dashboard/Modules/Discovery/app/Services/MentorDiscoveryService.php:11)
- [Modules/Discovery/app/Http/Controllers/Student/DashboardController.php](/home/rauf/projects/gradspath-dashboard/Modules/Discovery/app/Http/Controllers/Student/DashboardController.php:19)
- [Modules/Discovery/resources/views/student/dashboard.blade.php](/home/rauf/projects/gradspath-dashboard/Modules/Discovery/resources/views/student/dashboard.blade.php:100)

Remaining work:

- Replace fallback “coming soon” dashboard sections with fully live data.
- Add any missing theme-preference behavior directly into this student flow.
- Implement the exact featured ranking logic from the plan if needed.

### 3. Institutions / Explore by University

Status: `Mostly Done`

- `[x]` Student institutions index
- `[x]` Institution detail page
- `[x]` Admin institutions CRUD
- `[x]` Admin university programs CRUD
- `[x]` University browse data with programs and mentor mapping
- `[x]` Search by university name
- `[x]` Tier filtering
- `[ ]` Program type filtering in the request layer
- `[-]` Full university -> program -> mentor navigation exactly as described

Notes:

- This is one of the stronger modules.
- Program-type filtering mentioned in the plan is not exposed in the current request validation.

Evidence:

- [Modules/Institutions/app/Services/InstitutionService.php](/home/rauf/projects/gradspath-dashboard/Modules/Institutions/app/Services/InstitutionService.php:11)
- [Modules/Institutions/app/Http/Controllers/Student/InstitutionsController.php](/home/rauf/projects/gradspath-dashboard/Modules/Institutions/app/Http/Controllers/Student/InstitutionsController.php:13)
- [Modules/Institutions/app/Http/Requests/FilterUniversitiesRequest.php](/home/rauf/projects/gradspath-dashboard/Modules/Institutions/app/Http/Requests/FilterUniversitiesRequest.php:14)

Remaining work:

- Add `program_type` filtering to match the original plan.
- Tighten the university -> programs -> mentors flow if you want it to mirror the doc exactly.

### 4. Office Hours Module

Status: `Mostly Done`

- `[x]` Office hour schedules and sessions data model
- `[x]` Mentor office hours configuration in availability flow
- `[x]` Student office hours directory
- `[x]` Capacity tracking
- `[x]` Full/remaining spots logic
- `[x]` First booker metadata
- `[x]` Service lock after additional bookings
- `[x]` Weekly/biweekly labels in surfaced data
- `[x]` First-student choice messaging
- `[x]` First-student service-change workflow
- `[x]` Automated weekly rotation engine
- `[x]` Automated recurring session generation engine proven in code
- `[ ]` Full dedicated mentor office-hours management backend beyond availability integration
- `[ ]` Biweekly automation, if this becomes required again

Notes:

- The booking/availability side understands office-hours sessions well.
- Weekly-only recurring generation and service rotation now run through `office-hours:sync-sessions`.
- The first booked student can choose another active mentor service only while they are the sole attendee and the session is between 24 and 12 hours away.
- Biweekly remains intentionally out of scope for the completed version, even though the older schema/UI still has the option.

Evidence:

- [Modules/Bookings/app/Services/BookingAvailabilityService.php](/home/rauf/projects/gradspath-dashboard/Modules/Bookings/app/Services/BookingAvailabilityService.php:16)
- [Modules/Bookings/app/Services/BookingService.php](/home/rauf/projects/gradspath-dashboard/Modules/Bookings/app/Services/BookingService.php:236)
- [Modules/OfficeHours/app/Http/Controllers/Student/OfficeHoursController.php](/home/rauf/projects/gradspath-dashboard/Modules/OfficeHours/app/Http/Controllers/Student/OfficeHoursController.php:9)
- [Modules/OfficeHours/app/Http/Controllers/Mentor/OfficeHoursController.php](/home/rauf/projects/gradspath-dashboard/Modules/OfficeHours/app/Http/Controllers/Mentor/OfficeHoursController.php:8)
- [Modules/OfficeHours/app/Console/SyncOfficeHourSessionsCommand.php](/home/rauf/projects/gradspath-dashboard/Modules/OfficeHours/app/Console/SyncOfficeHourSessionsCommand.php:8)
- [Modules/OfficeHours/app/Services/OfficeHourServiceChoiceService.php](/home/rauf/projects/gradspath-dashboard/Modules/OfficeHours/app/Services/OfficeHourServiceChoiceService.php:10)
- [tests/Feature/MentorBookingAndAvailabilityTest.php](/home/rauf/projects/gradspath-dashboard/tests/Feature/MentorBookingAndAvailabilityTest.php:232)

Remaining work:

- Expand the dedicated mentor office-hours backend beyond the availability page integration.
- Add biweekly automation only if product decides to support it again.

### 5. Feedback & Reviews Module

Status: `Partial`

- `[x]` Student feedback submission
- `[x]` Feedback limited to completed bookings
- `[x]` Verified feedback association to booking
- `[x]` Admin moderation
- `[x]` Aggregate average rating
- `[x]` Aggregate recommendation rate
- `[x]` Mentor-facing feedback page
- `[-]` Public review browsing and sorting
- `[ ]` Most-mentioned tag / keyword analysis in the aggregation service
- `[ ]` Mentor reply-to-review feature
- `[ ]` Realtime propagation clearly implemented

Notes:

- Core feedback persistence and moderation are implemented.
- The richer analytics described in the plan are only partially there.

Evidence:

- [Modules/Feedback/app/Services/FeedbackService.php](/home/rauf/projects/gradspath-dashboard/Modules/Feedback/app/Services/FeedbackService.php:15)
- [Modules/Feedback/app/Services/RatingAggregationService.php](/home/rauf/projects/gradspath-dashboard/Modules/Feedback/app/Services/RatingAggregationService.php:10)
- [Modules/Feedback/app/Http/Controllers/Student/FeedbackController.php](/home/rauf/projects/gradspath-dashboard/Modules/Feedback/app/Http/Controllers/Student/FeedbackController.php:14)

Remaining work:

- Add keyword/top-tag aggregation.
- Add mentor replies if that feature is still required.
- Improve public filtering/sorting/realtime behavior to match the plan more closely.

### 6. Mentor Notes on Users

Status: `Mostly Remaining`

- `[x]` Database migration exists
- `[x]` Mentor-only route to notes page exists
- `[x]` Notes UI/form exists
- `[ ]` Notes create endpoint
- `[ ]` Notes read/list endpoint
- `[ ]` Notes search/filter backend
- `[ ]` Notes edit/update backend
- `[ ]` Admin soft-delete flow
- `[ ]` Secure per-mentor data access enforcement for note records
- `[ ]` “View” / “Read More” backend fetch flow

Notes:

- This module is mostly scaffolding right now.
- I found the table and views, but not the actual CRUD/service layer described in the plan.

Evidence:

- [Modules/MentorNotes/routes/web.php](/home/rauf/projects/gradspath-dashboard/Modules/MentorNotes/routes/web.php:11)
- [Modules/MentorNotes/resources/views/mentor/notes.blade.php](/home/rauf/projects/gradspath-dashboard/Modules/MentorNotes/resources/views/mentor/notes.blade.php:32)
- [Modules/MentorNotes/database/migrations/2026_04_04_000021_create_mentor_notes_table.php](/home/rauf/projects/gradspath-dashboard/Modules/MentorNotes/database/migrations/2026_04_04_000021_create_mentor_notes_table.php:13)

Remaining work:

- Create note CRUD endpoints/controllers/services.
- Implement per-mentor access control on note records.
- Add note listing, grouping, search, snippet view, and full-view retrieval.
- Add edit support for mentors and soft delete for admins.

### 7. Bookings & Session Management

Status: `Mostly Done`

- `[x]` Booking creation
- `[x]` Booking pages for students and mentors
- `[x]` Availability lookup by month/day/time
- `[-]` Meeting link generation attempt on booking creation
- `[x]` Chat thread loading
- `[x]` Chat sending
- `[x]` Broadcast event for realtime chat
- `[x]` 24-hour self-cancellation restriction
- `[x]` Slot/session reservation and occupancy updates
- `[x]` Office-hours booking support
- `[x]` Payment-before-booking flow for paid sessions
- `[-]` Calendar visualization support via booking payloads
- `[ ]` Credit refund on cancellation
- `[ ]` Reschedule flow
- `[ ]` Admin booking visibility flow confirmed in dedicated screens
- `[ ]` Chat time-window restriction as described in the plan

Notes:

- This is one of the strongest modules in the codebase.
- The biggest missing piece is automatic refunding on cancellation.
- The current implementation is Google Calendar / Google Meet oriented, while the agreed plan direction is now Zoom API.

Evidence:

- [Modules/Bookings/app/Services/BookingService.php](/home/rauf/projects/gradspath-dashboard/Modules/Bookings/app/Services/BookingService.php:22)
- [Modules/Bookings/app/Http/Controllers/Student/BookingController.php](/home/rauf/projects/gradspath-dashboard/Modules/Bookings/app/Http/Controllers/Student/BookingController.php:145)
- [Modules/Bookings/app/Http/Controllers/BookingChatController.php](/home/rauf/projects/gradspath-dashboard/Modules/Bookings/app/Http/Controllers/BookingChatController.php:20)
- [Modules/Bookings/app/Listeners/GenerateMeetingLinkListener.php](/home/rauf/projects/gradspath-dashboard/Modules/Bookings/app/Listeners/GenerateMeetingLinkListener.php:14)

Remaining work:

- Wire cancellation to automatic credit refund.
- Add rescheduling if it is still in scope.
- Add stricter chat-availability timing if you want the 24-48 hour rule from the plan.
- Confirm or expand admin-side booking visibility tools.
- Replace the current Google-based meeting creation flow with Zoom API meeting creation for each confirmed booking.

### 8. Support Tickets Module

Status: `Partial`

- `[x]` Student support form
- `[x]` Mentor support form
- `[x]` Ticket creation service
- `[x]` Unique support reference generation
- `[x]` Message sanitization
- `[x]` Admin ticket list
- `[x]` Admin ticket reply/update
- `[x]` Queued notifications to user and admin
- `[ ]` User ticket history/dashboard
- `[ ]` Route-level rate limiting
- `[ ]` Threaded follow-up email / reply handling as described
- `[ ]` Ordered ticket numbering in the exact format requested

Notes:

- Good backend foundation.
- The user side currently exposes create screens, not a full “my tickets” history view.

Evidence:

- [Modules/Support/app/Services/SupportTicketService.php](/home/rauf/projects/gradspath-dashboard/Modules/Support/app/Services/SupportTicketService.php:13)
- [Modules/Support/app/Http/Controllers/Student/TicketsController.php](/home/rauf/projects/gradspath-dashboard/Modules/Support/app/Http/Controllers/Student/TicketsController.php:16)
- [Modules/Support/app/Http/Controllers/Mentor/TicketsController.php](/home/rauf/projects/gradspath-dashboard/Modules/Support/app/Http/Controllers/Mentor/TicketsController.php:16)
- [Modules/Support/app/Http/Controllers/Admin/SupportTicketsController.php](/home/rauf/projects/gradspath-dashboard/Modules/Support/app/Http/Controllers/Admin/SupportTicketsController.php:14)
- [Modules/Support/app/Listeners/DispatchSupportNotificationsListener.php](/home/rauf/projects/gradspath-dashboard/Modules/Support/app/Listeners/DispatchSupportNotificationsListener.php:11)

Remaining work:

- Add “my tickets” history for students and mentors.
- Add route-level rate limiting / anti-spam protections.
- Add email-thread style follow-up handling if that workflow is still required.

### 9. Settings & Profile Management

Status: `Partial`

- `[x]` Student settings page
- `[x]` Student basic profile updates
- `[x]` Mentor settings page
- `[x]` Mentor profile field updates
- `[x]` Mentor service selection sync
- `[x]` `.edu` validation for graduate mentors
- `[x]` Featured mentor toggle
- `[-]` Public mentor profile data maintenance
- `[ ]` Avatar upload handling and cleanup
- `[ ]` Zoom account / provider connection flow
- `[ ]` Stripe Connect onboarding flow
- `[ ]` Payout enablement webhook handling
- `[ ]` Slack integration

Notes:

- Settings are usable for standard profile edits.
- The payout section is still explicitly a placeholder in the mentor settings UI.
- The plan now assumes Zoom API for meeting creation, but mentor-side Zoom connection/configuration is not implemented.

Evidence:

- [Modules/Settings/app/Http/Controllers/Mentor/MentorSettingsController.php](/home/rauf/projects/gradspath-dashboard/Modules/Settings/app/Http/Controllers/Mentor/MentorSettingsController.php:16)
- [Modules/Settings/tests/Feature/MentorSettingsTest.php](/home/rauf/projects/gradspath-dashboard/Modules/Settings/tests/Feature/MentorSettingsTest.php:34)
- [Modules/Settings/resources/views/mentor/index.blade.php](/home/rauf/projects/gradspath-dashboard/Modules/Settings/resources/views/mentor/index.blade.php:202)

Remaining work:

- Implement avatar upload/storage/cleanup.
- Implement Zoom account/configuration flow if mentors need Zoom-linked meeting creation.
- Implement Stripe Connect onboarding.
- Implement payout enablement status updates from webhooks.
- Add Slack/community integration if still required.

### 10. Payments & Credits System

Status: `Partial to Mostly Done`

- `[x]` Credit balance storage
- `[x]` Credit balance retrieval
- `[x]` Credit purchase logic
- `[x]` Stripe checkout for credits
- `[x]` Stripe checkout for paid bookings
- `[x]` Stripe webhook processing for completed checkout sessions
- `[x]` Atomic deduction logic
- `[x]` Atomic refund method exists in service layer
- `[x]` Idempotency handling for credit purchases
- `[ ]` Booking cancellation wired to credit refund
- `[ ]` Mentor payout processing
- `[ ]` Stripe Connect onboarding for mentors
- `[ ]` Withdrawal flow for mentors

Notes:

- Student-side payments and credits are in decent shape.
- Mentor-side payout functionality is not implemented end-to-end.

Evidence:

- [Modules/Payments/app/Services/CreditService.php](/home/rauf/projects/gradspath-dashboard/Modules/Payments/app/Services/CreditService.php:15)
- [Modules/Payments/app/Services/CreditCheckoutService.php](/home/rauf/projects/gradspath-dashboard/Modules/Payments/app/Services/CreditCheckoutService.php:14)
- [Modules/Payments/app/Services/BookingCheckoutService.php](/home/rauf/projects/gradspath-dashboard/Modules/Payments/app/Services/BookingCheckoutService.php:22)
- [Modules/Payments/app/Services/StripeWebhookService.php](/home/rauf/projects/gradspath-dashboard/Modules/Payments/app/Services/StripeWebhookService.php:16)

Remaining work:

- Connect cancellation flow to refunds.
- Implement mentor payouts.
- Implement Stripe Connect onboarding and payout status lifecycle.

## Biggest Gaps To Address Next

- `[ ]` Implement the full Mentor Notes backend: CRUD, per-mentor access control, search, edit, and admin soft delete.
- `[ ]` Wire booking cancellation to automatic credit refund.
- `[ ]` Build the actual office-hours rotation/session-generation engine.
- `[ ]` Finish Stripe Connect onboarding and mentor payouts.
- `[ ]` Add user ticket history and rate limiting to Support.
- `[ ]` Expand feedback analytics to include top-tag / keyword aggregation.

## Verification Notes

- Code inspection was completed across routes, controllers, services, migrations, and tests.
- A focused test run was attempted, but runtime verification is currently blocked by the local test database connection failing for MySQL `grads_path_test` on `127.0.0.1:3306`.
