# Template Page Flow -> Backend/DB Mapping

Date: 2026-04-08
Goal: Validate each template page/flow against current migrations and identify backend schema gaps.

## Overall status

- Flow coverage is strong.
- Not fully ready yet: a few schema mismatches will block real behavior in production.

## Page-by-page mapping

1. / and demo1.html (Find Mentors landing)

- Core actions: view featured mentors, read snippets, click Book Now.
- DB fit:
    - mentors (is_featured, rating/profile fields)
    - mentor_services + services_config
    - feedback snippets
    - user_credits badge
- Status: Covered.

2. demo4.html (Explore Mentors)

- Core actions: filter by mentor type, program type, mentor search, school search.
- DB fit:
    - mentors.mentor_type, mentors.program_type, mentors.university_id
    - universities.name/display_name
- Status: Covered.
- Note: ensure API supports combined filters + pagination.

3. demo3a.html (Explore by University)

- Core actions: filter by tier/program type, search school, open institution.
- DB fit:
    - universities.tier, universities.name
    - university_programs.program_type
    - mentors.university_id
- Status: Partial.
- Gap:
    - Template mentions tiers like "Top Rated" and "Multiple tiers" while schema has one tier enum per university (elite/top25/regional).
    - Decide if those are derived tags or need new columns/relationship.

4. demo11.html (Book with Mentor)

- Core actions: select service, meeting size (1on1/1on3/1on5/office hours), date/time, continue.
- DB fit:
    - bookings (service_config_id, meeting_size, session_at, duration_minutes)
    - services_config (prices by meeting size)
    - office_hour_sessions linkage for office-hours path
- Status: Covered with one important gap.
- Gap:
    - session timezone normalization is not explicit in bookings (session_at only).

5. demo13.html (Office Hours This Week)

- Core actions: view rotating service, spots filled, service lock behavior, book if available.
- DB fit:
    - office_hour_schedules (frequency/day/time)
    - office_hour_sessions (current_service_id, occupancy, service_locked, first_booker_id, cutoff)
    - bookings.office_hour_session_id
- Status: Covered.
- Note: template text mentions up to 5 people in one place, while schedule/session max_spots defaults to 3 in schema. Align product rule.

6. demo9.html (Your Session Is Booked)

- Core actions: view meeting details/calendar/upcoming appointments/chat/cancel.
- DB fit:
    - bookings (meeting_link, status, cancellation fields)
    - chats (booking thread)
- Status: Covered.
- Note: cancellation/refund policy should be enforced in service layer + transaction logs.

7. demo6.html (Student post-meeting feedback)

- Core actions: stars, preparedness, recommend, comment submit.
- DB fit:
    - feedback table fields match form.
- Status: Partial.
- Blocking gap:
    - feedback.booking_id is unique, which breaks group booking feedback (1on3/1on5).

8. demo7.html (Mentor notes after session)

- Core actions: 5 required note questions + session context.
- DB fit:
    - mentor_notes has all 5 structured fields + session metadata.
- Status: Covered.
- Note: form says required, but schema columns are nullable. Enforce required in request validation.

9. demo8.html (Users Notes listing)

- Core actions: search by user/mentor, view notes snippets, read full note.
- DB fit:
    - mentor_notes + users join pattern.
- Status: Covered.
- Note: strict RBAC/policies are required here.

10. demo5.html (Feedback directory)

- Core actions: browse mentor reviews by program/type, view snippets, book from feedback card.
- DB fit:
    - feedback + mentors + mentor_ratings + bookings entry path.
- Status: Covered with aggregate duplication risk.
- Gap:
    - ratings live in both mentors and mentor_ratings; can drift.

11. demo2.html (Office Hours subscription/store)

- Core actions: choose program, subscribe monthly, receive credits.
- DB fit:
    - office_hours_subscriptions
    - user_credits
    - credit_transactions
- Status: Partial.
- Gap:
    - subscription program enum is only mba/law/therapy while program taxonomy elsewhere is broader.

12. demo10.html (Mentor settings)

- Core actions: profile fields, image, links, payouts onboarding.
- DB fit:
    - mentors has most profile and payout fields.
    - users has basic identity fields.
- Status: Covered.
- Note: .edu rule for graduate mentor must be enforced in validation/policies.

13. demo15.html (Support)

- Core actions: submit ticket with name/email/subject/message.
- DB fit:
    - support_tickets (subject, message, status, admin reply).
- Status: Covered.
- Note: name/email should come from authenticated user record; do not trust client payload only.

14. admin.html (Admin dashboard + manual stations)

- Core actions:
    - KPI reads and exports
    - station 1 amend mentor
    - station 2 refund/add credits
    - station 3 amend/delete feedback
    - station 4 create institutions
    - station 5 create program/service
    - station 6 pricing editor
- DB fit:
    - mentors, services_config, mentor_services, universities, university_programs, feedback, user_credits, credit_transactions, support_tickets, admin_logs.
- Status: Partial.
- Gaps:
    - No executable roles/permissions migration present yet (only note file).
    - admin_logs does not strongly enforce full audit metadata.

15. demo12.html and demo14.html

- Result: 404 in deployed template.
- Status: Not auditable from current deployment.

## Cross-flow blockers to fix first

1. Role system migration must exist in schema

- Current state has a reference note only, not migration tables.

2. Feedback uniqueness must support group sessions

- Replace unique booking_id rule with unique(booking_id, student_id).

3. Remove duplicate aggregate truth sources

- Standardize ratings/session aggregates in mentor_ratings or keep strict sync mechanism.

4. Timezone handling for session time must be explicit

- Either always UTC + documented conversion or add booking timezone field.

5. Align office-hour capacity rule

- Template copy and schema should both use same max participants (3 vs 5).

## Recommended API groups by page flow

- Auth/RBAC: /auth/_, /roles/_, /permissions/\*
- Discovery: /mentors, /mentors/featured, /universities, /universities/{id}/programs
- Booking: /bookings, /bookings/{id}/cancel, /availability, /office-hours/sessions
- Feedback: /feedback, /mentor-feedback, /mentor-ratings
- Notes: /mentor-notes
- Finance: /credits/balance, /credits/transactions, /subscriptions/office-hours
- Support: /support/tickets
- Admin: /admin/analytics, /admin/manual/\*, /admin/logs

## Final judgment

- Your template flows are good for backend planning.
- Schema is close, but these blockers must be fixed before saying backend is fully aligned to all flows.
