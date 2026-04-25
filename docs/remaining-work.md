# Remaining Work

Updated: 2026-04-25

This file summarizes what still appears to be remaining in the app after comparing the current codebase with:

- `docs/docx.plan.md`
- `docs/docx-implementation-checklist.md`
- `docs/stripe-implementation-checklist.md`
- `APP_MASTER_PLAN.md`
- `milestone_1.md`
- `milestone_2.md`
- `milestone_3.md`

Some older checklist entries are now stale. In particular, mentor notes saving, Stripe Connect onboarding, mentor payouts, cancellation refunds, university DB import/search, and the 24-hour feedback/session-notes booking lock have been implemented or improved after those docs were written.

## Highest Priority Remaining

### 1. Office Hours Automation

- Build the automatic weekly/biweekly rotation engine.
- Confirm recurring session generation is fully automated by schedule, not only manually configured.
- Finish first-student service choice workflow for office hours.
- Confirm final office-hours payout rule.
  - Current payout logic is percent-based.
  - Original plan mentions a fixed mentor amount per attendee for office hours.

### 2. Mentor Notes Completion

Implemented:

- Mentor notes can be created and updated for hosted bookings.
- Hosted-booking access is protected.
- Saving mentor notes marks `mentor_feedback_done = true`.
- Overdue mentor notes now block new booking creation.

Remaining:

- Admin soft-delete/moderation flow for mentor notes.
- Search/filter backend for mentor notes.
- Clear decision on visibility:
  - Should mentors see only their own notes?
  - Or should mentors see notes from all mentors for the same student?
- Optional delete/archive UI for mentors.

### 3. Support Tickets

Implemented:

- Student and mentor support forms.
- Ticket creation.
- Admin list/reply/update.
- Notification dispatch.

Remaining:

- Student/mentor “my ticket history” pages.
- Route-level rate limiting / anti-spam protection.
- Threaded email follow-up handling, if still required.
- Final ticket numbering format, if the exact plan format is still required.

### 4. Feedback And Reviews

Implemented:

- Student feedback submission.
- Booking-linked feedback.
- Admin feedback moderation.
- Average rating and recommendation aggregation.
- Student feedback is required within 24 hours after the due time before new bookings.

Remaining:

- Most-mentioned tag / keyword analytics.
- Mentor reply-to-review feature, if still wanted.
- Public review browsing, filtering, and sorting polish.
- Realtime stats propagation, if required.
- Remove any stale mentor feedback permissions/routes references if they are no longer part of the product.

### 5. Booking Management

Implemented:

- Booking creation.
- Availability slot reservation.
- Office-hours booking.
- Student and mentor cancellation with 24-hour self-cancellation policy.
- Credit and paid booking refund handling on eligible cancellations.
- Zoom webhook attendance/completion logic.
- Feedback/session-note 24-hour lock before new bookings.

Remaining:

- Reschedule flow.
- Admin booking management screen polish/confirmation.
- Strict chat access window, if the 24-48 hour chat rule from the plan is still required.
- Production verification of meeting creation/provider setup.

### 6. Settings And Profiles

Implemented:

- Student settings.
- Mentor settings.
- Mentor service sync.
- Timezone support.
- Stripe Connect onboarding and payout status storage.

Remaining:

- Avatar/profile image upload with storage cleanup.
- Clearer mentor payout status copy in settings:
  - onboarding opened
  - pending Stripe review
  - payouts enabled
- Slack/community integration, if still required.
- Optional Zoom/provider account settings if mentors need personal Zoom connections.

### 7. Authentication And Registration

Implemented:

- Login/register/logout.
- Email verification.
- Role-based access control.
- Student/mentor profile creation.
- University registration search now uses the DB instead of the JSON file.

Remaining:

- Mentor password-based approval workflow from the original plan, if still required.
- Professional-user exemption from `.edu` validation, if still required.
- Final review of mentor approval status behavior.

### 8. Admin Manual Actions

Implemented:

- Admin manual actions surface.
- Searchable DB-backed university picker for create-program.
- Feedback, booking outcome, catalog, pricing, mentor, and credit action areas.

Remaining:

- Confirm all manual actions create admin audit logs consistently.
- Add search/pagination to any remaining large dropdowns.
- Polish validation messages for failed manual actions.
- Confirm recent/sidebar panels stay small and do not load large datasets.

### 9. Payments, Stripe, Refunds, And Payouts

Implemented:

- Stripe Checkout for credits.
- Stripe Checkout for paid bookings.
- Stripe webhook processing.
- Stripe Connect onboarding.
- `account.updated` payout status sync.
- Mentor payout ledger.
- Transfers after completed paid bookings.
- Retry command for eligible/failed payouts.
- Credit refunds on cancellation.
- Stripe cash refunds on cancellation.
- Transfer reversal before refund when needed.

Remaining:

- Production webhook runbook:
  - required events
  - connected-account events
  - queue worker requirement
  - scheduled retry command requirement
- Confirm final payout policy for office hours.
- Decide whether to keep current Express Connect flow or later migrate new Stripe work toward the latest Accounts/controller-properties approach.
- Admin workflow for refunds that land in `cancelled_pending_refund` / admin review.

## Lower Priority Polish

- Replace any remaining fallback/demo content on dashboards with live data.
- Confirm dark/light preference is consistently applied across student, mentor, and admin portals.
- Tighten public institution/program/mentor navigation to match the original plan exactly.
- Add missing request-level program-type filters where needed.
- Review old demo assets and unused static files before production cleanup.
- Add broader feature tests around admin manual actions and production payment edge cases.

## Suggested Next Build Order

1. Office-hours automation and final payout rule.
2. Support ticket history and rate limiting.
3. Mentor notes admin/search/visibility decisions.
4. Booking reschedule flow.
5. Feedback analytics and review browsing polish.
6. Settings polish: avatars, payout copy, optional provider settings.
7. Production runbooks for Stripe, queues, scheduler, and webhooks.

