# Grads Paths Backend Implementation Master Plan

Date: 2026-04-08
Purpose: Single source of truth to move from template flows -> production-ready backend.

This document combines:

- DB migration patch list (exact changes)
- API contracts by flow/page
- End-to-end implementation checklist
- Testing/validation checklist

## 1. Current Assessment

Status: Close, but not production-ready.

Main blockers:

1. RBAC schema not executable yet (Spatie migration is a note file only).
2. Group booking feedback conflict (`feedback.booking_id` is unique).
3. Duplicate aggregate stats in `mentors` and `mentor_ratings`.
4. Session timezone strategy not explicit in booking records.
5. Minor taxonomy/rule mismatches (capacity copy and program enums).

## 2. Migration Patch Plan (Exact)

## Patch A: RBAC tables (BLOCKER)

Goal: enforce Student/Mentor/Admin authorization.

Actions:

1. Install/publish Spatie permission migrations.
2. Migrate tables:
    - `roles`
    - `permissions`
    - `model_has_roles`
    - `model_has_permissions`
    - `role_has_permissions`
3. Seed default roles: `student`, `mentor`, `admin`.
4. Seed permissions by module.

Notes:

- Keep user role assignment in pivot via Spatie (preferred), not a plain users.role enum.

## Patch B: Fix group feedback model (BLOCKER)

Implementation status: Applied directly in [Grad paths migrations/2024_01_01_000019_create_feedback_table.php](Grad%20paths%20migrations/2024_01_01_000019_create_feedback_table.php).

Current issue:

- `feedback.booking_id` unique allows one feedback only per booking.
- This breaks `1on3` and `1on5` scenarios.

Change:

- Drop unique index on `booking_id`.
- Add composite unique on (`booking_id`, `student_id`).

Result:

- Each student can submit one feedback per shared booking.

## Patch C: Single source of truth for aggregates (BLOCKER)

Current issue:

- Aggregates exist in both `mentors` and `mentor_ratings`.

Decision (recommended):

- Keep aggregates only in `mentor_ratings`.
- Deprecate these from `mentors`: `avg_rating`, `total_sessions`, `total_reviews`.

Safe rollout option:

1. Keep columns for now.
2. Treat `mentor_ratings` as source of truth.
3. Update read APIs to join/use `mentor_ratings`.
4. Remove duplicate columns in a later cleanup migration.

## Patch D: Booking timezone clarity (BLOCKER)

Current issue:

- `bookings.session_at` exists, but timezone convention is not explicit.

Pick one strategy:

1. Strategy 1 (preferred):
    - Store `session_at` in UTC only.
    - Add `session_timezone` (mentor timezone at booking time) for display.
2. Strategy 2:
    - Store local datetime with offset and keep conversion logic strict.

Recommended migration:

- Add nullable `session_timezone` string to `bookings`.

## Patch E: Ratings range integrity (HIGH)

Add DB constraints or strict server validation for:

- `feedback.stars` in 1..5
- `feedback.preparedness_rating` in 1..5 (nullable)
- `mentor_feedback.engagement_score` in 1..5 (nullable)

If DB CHECK is not used, enforce in FormRequest + tests.

## Patch F: Password reset hardening (HIGH)

Current issue:

- No explicit PK, token not unique.

Recommended changes:

- Add `id` PK.
- Add unique index on `token`.

## Patch G: OAuth uniqueness hardening (HIGH)

Current:

- unique(`provider`, `provider_user_id`) only.

Add:

- unique(`user_id`, `provider`) to prevent duplicate provider linkage per user.

## Patch H: Program taxonomy alignment (MEDIUM)

Current mismatch:

- Office-hours subscription supports only `mba/law/therapy`.
- Other areas support broader types.

Decision:

1. Keep limited enum intentionally (if subscription only for these 3).
2. Or expand enum to align with global taxonomy.

Document this explicitly in product rules.

## Patch I: Audit log enforcement (MEDIUM)

`admin_logs` already exists; improve standards:

- Require `action`, `target_table`, `admin_id` always.
- Ensure every manual admin mutation writes an audit row.
- Add service-level guard that blocks writes without audit transaction.

## 3. API Contract by Template Flow

Base prefix: `/api/v1`
Auth: Bearer token (Sanctum/Passport)

## 3.1 Auth + RBAC

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /me`
- `GET /me/permissions`

Policies:

- Student: booking, feedback, support, discovery.
- Mentor: own profile/schedule/notes/mentor-feedback.
- Admin: full + manual stations.

## 3.2 Discovery (demo1, demo4, demo3a)

- `GET /mentors/featured`
    - returns mentor cards + services + snippet feedback + rating summary
- `GET /mentors`
    - query: `mentor_type`, `program_type`, `school`, `q`, `page`
- `GET /mentors/{id}`
    - full profile + services + latest feedback
- `GET /universities`
    - query: `tier`, `program_type`, `q`
- `GET /universities/{id}/programs`
- `GET /universities/{id}/mentors`

## 3.3 Office Hours + Booking (demo11, demo13, demo9)

- `GET /services`
- `GET /mentors/{id}/availability`
    - returns date/time slots by service + meeting size
- `GET /office-hours/sessions`
    - filters: `mentor_type`, `program_type`, `school`
    - includes occupancy, lock state, current_service
- `POST /bookings`
    - payload: `mentor_id`, `service_config_id`, `meeting_size`, `session_at`, optional `office_hour_session_id`
    - behavior: credit check + atomic reserve + transaction ledger write
- `GET /bookings/upcoming`
- `GET /bookings/{id}`
- `POST /bookings/{id}/cancel`
    - applies policy + refund logic atomically

## 3.4 Chat (demo9)

- `GET /bookings/{id}/chat`
- `POST /bookings/{id}/chat/messages`
- `PATCH /chat/messages/{id}/read`

Rules:

- Chat window enabled by booking time policy (for example 48h before).
- Only booking participants can read/write.

## 3.5 Feedback (demo6, demo5, admin station 3)

Student feedback:

- `POST /bookings/{id}/feedback`
- `GET /feedback`
    - filters: `program_type`, `mentor_type`, `mentor_id`, `sort`

Mentor feedback:

- `POST /bookings/{id}/mentor-feedback`

Aggregation:

- `POST /internal/ratings/recalculate/{mentor_id}` (job/internal)

Admin moderation:

- `PATCH /admin/feedback/{id}`
- `DELETE /admin/feedback/{id}` or hide toggle

Rules:

- Only completed bookings can submit feedback.
- One feedback per (`booking_id`, `student_id`).

## 3.6 Mentor Notes (demo7, demo8)

- `POST /mentor-notes`
- `GET /mentor-notes`
    - query: `student_q`, `mentor_q`, `page`
- `GET /mentor-notes/{id}`
- `PATCH /mentor-notes/{id}`
- `DELETE /admin/mentor-notes/{id}` (soft delete path)

Rules:

- Mentor can only access own notes.
- Admin can view all.
- Student cannot access.

## 3.7 Credits + Subscription + Payments (demo2 + booking)

- `GET /credits/balance`
- `GET /credits/transactions`
- `POST /subscriptions/office-hours`
    - create Stripe subscription
- `POST /webhooks/stripe`
    - idempotent processing via event id

Rules:

- Every credit mutation writes `credit_transactions` row.
- Balance update and transaction insert in one DB transaction.

## 3.8 Support (demo15)

- `POST /support/tickets`
- `GET /support/tickets/me`
- `GET /admin/support/tickets`
- `PATCH /admin/support/tickets/{id}`

Rules:

- `user_id` from auth token only.
- Rate limit create endpoint.
- Input sanitization.

## 3.9 Mentor Settings (demo10)

- `GET /mentor/profile`
- `PATCH /mentor/profile`
- `POST /mentor/profile/avatar`
- `POST /mentor/payouts/onboarding-link`
- `POST /webhooks/stripe/connect`

Validation:

- If `mentor_type=graduate`, enforce `.edu` email.
- Validate calendly/slack URLs.

## 3.10 Admin Dashboard + Manual Stations (admin.html)

Read APIs:

- `GET /admin/analytics/overview`
- `GET /admin/analytics/revenue`
- `GET /admin/users`
- `GET /admin/mentors`
- `GET /admin/services`
- `GET /admin/rankings`
- `GET /admin/logs`

Write APIs (manual stations):

- `PATCH /admin/mentors/{id}`
- `POST /admin/credits/manual-adjustment`
- `PATCH /admin/feedback/{id}`
- `POST /admin/institutions`
- `PATCH /admin/institutions/{id}`
- `POST /admin/programs`
- `POST /admin/services`
- `PATCH /admin/services/{id}/pricing`

All admin writes:

- Must append `admin_logs` within same transaction boundary.

## 4. Execution Checklist (Do in order)

## Phase 1 - Schema blockers

1. Publish and run Spatie permission migrations.
2. Feedback unique key fix (`booking_id`, `student_id`).
3. Booking timezone strategy update.
4. Aggregate source-of-truth decision and API alignment.

## Phase 2 - Integrity hardening

1. Rating range constraints/validation.
2. Password reset table hardening.
3. OAuth additional unique constraint.
4. Stripe idempotency safeguards.

## Phase 3 - API delivery

1. Discovery APIs.
2. Booking + office-hours APIs.
3. Feedback + mentor feedback + notes APIs.
4. Credits/subscription/payment APIs.
5. Support APIs.
6. Admin analytics + manual station APIs.

## Phase 4 - Policy and jobs

1. Laravel policies for all sensitive modules.
2. Scheduled jobs:
    - office-hour session generation/rotation
    - stale booking state cleanup
    - pending feedback restriction checks
3. Async jobs:
    - email notifications
    - rating recalculation

## Phase 5 - Verification

1. Feature tests per module.
2. Cross-module E2E tests:
    - book -> chat -> complete -> dual feedback -> notes
    - cancel -> refund -> ledger consistency
    - admin manual action -> audit log existence

## 5. Required Test Cases

Minimum pass criteria:

1. Student can book 1on1 and 1on3.
2. For shared booking, multiple students can submit feedback independently.
3. Mentor can submit mentor-feedback and notes for completed session.
4. Credits never go negative under concurrent booking attempts.
5. Stripe webhook re-delivery does not duplicate credit transaction.
6. Non-admin cannot access admin endpoints.
7. Student cannot access mentor notes endpoint.
8. Every admin write creates an `admin_logs` record.

## 6. Definition of Done

Backend is considered ready when:

1. All Phase 1 blockers are merged and migrated.
2. All required APIs above exist with auth/policy enforcement.
3. E2E scenarios pass on staging.
4. No schema contradictions remain with template flows.
5. Documentation updated to reflect final schema and endpoint behavior.

## 7. Quick SQL/Schema Delta Summary

- feedback: unique(booking_id, student_id)
- bookings: add session_timezone (if using UTC + display timezone)
- oauth_tokens: add unique(user_id, provider)
- password_resets: add PK + unique token
- roles/permissions tables: add via Spatie migrations
- aggregates: standardize reads on mentor_ratings

## 8. Final Recommendation

Implement Phase 1 immediately, then lock API contracts before controller coding.
This avoids rework and keeps your template-driven flows consistent with production backend behavior.
