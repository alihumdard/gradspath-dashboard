# Grads Paths Schema Consistency Review

Date: 2026-04-08
Scope checked:

- Grad paths migrations (all files)
- Grads path documentaion.md
- grads_paths_updated_erd.html

## Verdict

Not ready for production yet. Core structure is strong, but there are blocking consistency and integrity issues.

## Blocking changes (fix before go-live)

1. RBAC schema gap

- users table has no role column and no active permission tables in migrations.
- Spatie migration is only a .note file, not executable migration.
- Why it matters: role-based access in docs cannot be enforced at DB level.
- Files:
    - Grad paths migrations/2024_01_01_000001_create_users_table.php
    - Grad paths migrations/2024_01_01_000025_spatie_permission_tables.php.note

2. Group booking feedback conflict

- feedback has unique booking_id, but bookings supports 1on3 and 1on5.
- This allows only one student feedback for a group booking.
- Why it matters: breaks mandatory post-session feedback flow for group sessions.
- Files:
    - Grad paths migrations/2024_01_01_000014_create_bookings_table.php
    - Grad paths migrations/2024_01_01_000019_create_feedback_table.php
- Recommended fix:
    - Replace unique booking_id with composite unique (booking_id, student_id).

3. Duplicate rating sources can drift

- mentors table stores avg_rating, total_sessions, total_reviews.
- mentor_ratings also stores avg_stars, total_sessions, total_reviews.
- Why it matters: inconsistent values can appear across screens.
- Files:
    - Grad paths migrations/2024_01_01_000007_create_mentors_table.php
    - Grad paths migrations/2024_01_01_000022_create_mentor_ratings_table.php
- Recommended fix:
    - Keep one source of truth for aggregates (prefer mentor_ratings).

4. ERD tab activation bug

- Tab activation uses substring matching with label Content.
- Button text is Feedback & notes, so active state can fail for that tab.
- Why it matters: incorrect active tab UI.
- File:
    - grads_paths_updated_erd.html
- Recommended fix:
    - Use deterministic key-to-button mapping instead of text includes.

## Important integrity improvements (high priority)

5. password_resets has no explicit PK and token is not unique

- File: Grad paths migrations/2024_01_01_000003_create_password_resets_table.php
- Recommended: add id and unique token.

6. Ratings are not range-constrained in DB

- stars, preparedness_rating, engagement_score are unsigned tinyint but not checked to 1-5.
- Files:
    - Grad paths migrations/2024_01_01_000019_create_feedback_table.php
    - Grad paths migrations/2024_01_01_000020_create_mentor_feedback_table.php

7. OAuth uniqueness is incomplete

- Has unique(provider, provider_user_id) but not unique(user_id, provider).
- File: Grad paths migrations/2024_01_01_000002_create_oauth_tokens_table.php

8. Timezone clarity for booking moment

- bookings stores session_at but no explicit session timezone field.
- File: Grad paths migrations/2024_01_01_000014_create_bookings_table.php

## Documentation mismatches

9. Non-existing table names referenced in docs

- office_hour_sessions / slots table
- bookings or office_hour_bookings table
- File: Grads path documentaion.md
- Recommended: align wording with actual schema (bookings + office_hour_sessions).

## Minor polish

10. File naming typo

- Grads path documentaion.md should be renamed to Grads path documentation.md

## Go-live checklist

- Implement all 4 blocking changes above.
- Run migrations fresh and confirm no schema conflicts.
- Run at least one end-to-end flow each for:
    - 1on1 booking feedback
    - 1on3 or 1on5 booking feedback
    - role-based route protection (student/mentor/admin)
    - ERD tab switching behavior

When these are done and validated, this can be considered ready.
