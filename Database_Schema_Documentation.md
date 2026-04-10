# Grads Path Database Schema Documentation

Complete database schema with all 24 tables, columns, data types, constraints, and relationships.

---

## 1. **users**
Core user account table for all platform users (students, mentors, admins).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| name | string | NOT NULL | Full name |
| email | string | UNIQUE, NOT NULL | Email address (unique) |
| password_hash | string | NULLABLE | null for social-only accounts |
| avatar_url | string | NULLABLE | Profile picture URL |
| is_active | boolean | DEFAULT true | Account activation status |
| email_verified_at | timestamp | NULLABLE | OAuth/email verification timestamp |
| remember_token | string | NULLABLE | Laravel remember-me token |
| created_at | timestamp | DEFAULT NOW() | Record creation time |
| updated_at | timestamp | DEFAULT NOW() | Last modification time |

**Indexes:**
- `INDEX (email)`
- `INDEX (is_active)`
- `INDEX (created_at)`

**Foreign Keys:** None (root table)

---

## 2. **oauth_tokens**
OAuth provider authentication tokens (Google, GitHub, Facebook, etc.).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| user_id | bigint | FK → users, CASCADE DELETE | User owning the token |
| provider | string | DEFAULT 'google' | OAuth provider name |
| provider_user_id | string | NOT NULL | Provider's unique user ID |
| access_token | text | NOT NULL | OAuth access token |
| refresh_token | text | NULLABLE | OAuth refresh token |
| token_expires_at | timestamp | NULLABLE | Token expiration time |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Unique Constraints:**
- `UNIQUE (provider, provider_user_id)` — prevent duplicate provider IDs
- `UNIQUE (user_id, provider)` — prevent same provider linked twice per user

**Indexes:**
- `INDEX (user_id)`

**Foreign Keys:**
- `user_id` → users(id) CASCADE DELETE

---

## 3. **password_resets**
Password reset token management for account recovery.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| email | string | INDEXED | User email (not FK, allows deleted users) |
| token | string | UNIQUE | Reset token (cryptographically secure) |
| expires_at | timestamp | NOT NULL | Token expiration time (24hrs) |
| created_at | timestamp | DEFAULT NOW() | Creation time |

**Indexes:**
- `INDEX (email)`

**Notes:** Not tied to users via FK to allow reset even if user deleted

---

## 4. **user_settings**
User preferences (theme, notifications, etc.). One row per user.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| user_id | bigint | FK → users, UNIQUE, CASCADE DELETE | One settings row per user |
| theme | enum | DEFAULT 'light' | 'light' or 'dark' |
| email_notifications | boolean | DEFAULT true | Email notification preference |
| sms_notifications | boolean | DEFAULT false | SMS notification preference |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Unique Constraint:** `UNIQUE (user_id)`

**Foreign Keys:**
- `user_id` → users(id) CASCADE DELETE

---

## 5. **universities**
Educational institutions offering mentored programs.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| name | string | NOT NULL | Full university name |
| display_name | string | NULLABLE | Short name (e.g., "Harvard", "Yale Law") |
| country | string | DEFAULT 'US' | Country code/name |
| logo_url | string | NULLABLE | University logo URL |
| tier | enum | NOT NULL | 'elite' / 'top25' / 'regional' |
| is_active | boolean | DEFAULT true | Active status |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (tier)`
- `INDEX (is_active)`
- `INDEX (name)`

**Foreign Keys:** None

---

## 6. **university_programs**
Educational programs offered by universities (MBA, Law, Therapy, etc.).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| university_id | bigint | FK → universities, CASCADE DELETE | Parent university |
| program_name | string | NOT NULL | Program name (e.g., "MBA 2025") |
| program_type | enum | NOT NULL | 'mba'/'law'/'therapy'/'cmhc'/'mft'/'msw'/'clinical_psy'/'other' |
| description | string | NULLABLE | Program description |
| duration_months | integer | NULLABLE | Program duration in months |
| is_active | boolean | DEFAULT true | Active status |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (university_id, program_type)`
- `INDEX (is_active)`

**Foreign Keys:**
- `university_id` → universities(id) CASCADE DELETE

---

## 7. **mentors**
Mentor profiles and credentials. Links to user accounts.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| user_id | bigint | FK → users, UNIQUE, CASCADE DELETE | One mentor record per user |
| university_id | bigint | FK → universities, NULLABLE, NULL ON DELETE | Alma mater |
| title | string | NULLABLE | e.g., "PhD Psychology", "MBA" |
| grad_school_display | string | NULLABLE | Short name for display |
| mentor_type | enum | NOT NULL | 'graduate' or 'professional' |
| program_type | enum | NULLABLE | 'mba'/'law'/'therapy'/'cmhc'/'mft'/'msw'/'clinical_psy'/'other' |
| bio | text | NULLABLE | Mentor biography |
| description | text | NULLABLE | Longer "about" text |
| office_hours_schedule | string | NULLABLE | e.g., "Every Tuesday at 5 PM EST" |
| avatar_url | string | NULLABLE | Profile picture URL |
| avatar_crop_zoom | decimal(4,2) | NULLABLE | Avatar crop zoom level |
| avatar_crop_x | decimal(6,2) | NULLABLE | Avatar crop X coordinate |
| avatar_crop_y | decimal(6,2) | NULLABLE | Avatar crop Y coordinate |
| edu_email | string | NULLABLE | Educational email |
| calendly_link | string | NULLABLE | Calendly booking link |
| slack_link | string | NULLABLE | Slack workspace link |
| is_featured | boolean | DEFAULT false | "Mentors of the Week" flag |
| stripe_account_id | string | NULLABLE | Stripe Connect account ID |
| payouts_enabled | boolean | DEFAULT false | Payout capability enabled |
| stripe_onboarding_complete | boolean | DEFAULT false | Stripe onboarding status |
| status | enum | DEFAULT 'pending' | 'pending'/'active'/'paused'/'rejected' |
| approved_at | timestamp | NULLABLE | Approval timestamp |
| approved_by | bigint | FK → users, NULLABLE, NULL ON DELETE | Admin who approved |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (mentor_type)`
- `INDEX (program_type)`
- `INDEX (is_featured)`
- `INDEX (status)`
- `INDEX (university_id, program_type)`

**Foreign Keys:**
- `user_id` → users(id) CASCADE DELETE
- `university_id` → universities(id) NULL ON DELETE
- `approved_by` → users(id) NULL ON DELETE

---

## 8. **services_config**
Global service catalog managed by admin (Pricing Manager station).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| service_name | string | NOT NULL | e.g., "Interview Prep" |
| service_slug | string | UNIQUE | URL-safe slug |
| duration_minutes | integer | DEFAULT 60 | Session duration |
| is_active | boolean | DEFAULT true | Active status |
| price_1on1 | decimal(8,2) | NULLABLE | 1-on-1 pricing |
| price_1on3_per_person | decimal(8,2) | NULLABLE | 1-on-3 per-person pricing |
| price_1on3_total | decimal(8,2) | NULLABLE | 1-on-3 total pricing |
| price_1on5_per_person | decimal(8,2) | NULLABLE | 1-on-5 per-person pricing |
| price_1on5_total | decimal(8,2) | NULLABLE | 1-on-5 total pricing |
| is_office_hours | boolean | DEFAULT false | Office hours flag |
| office_hours_subscription_price | decimal(8,2) | NULLABLE | Monthly subscription price |
| credit_cost_1on1 | integer | DEFAULT 1 | Credits deducted for 1-on-1 |
| credit_cost_1on3 | integer | DEFAULT 1 | Credits deducted for 1-on-3 |
| credit_cost_1on5 | integer | DEFAULT 1 | Credits deducted for 1-on-5 |
| sort_order | integer | DEFAULT 0 | Display order |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (is_active)`
- `INDEX (sort_order)`

**Notes:** Managed via admin Pricing Manager manual control station

---

## 9. **mentor_services**
Many-to-many mapping of which services each mentor offers.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| mentor_id | bigint | FK → mentors, CASCADE DELETE | Mentor offering service |
| service_config_id | bigint | FK → services_config, CASCADE DELETE | Service offered |
| is_active | boolean | DEFAULT true | Service availability |
| sort_order | integer | DEFAULT 0 | Display order |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Unique Constraint:** `UNIQUE (mentor_id, service_config_id)`

**Indexes:**
- `INDEX (mentor_id, is_active)`

**Foreign Keys:**
- `mentor_id` → mentors(id) CASCADE DELETE
- `service_config_id` → services_config(id) CASCADE DELETE

---

## 10. **office_hour_schedules**
Recurring office hours pattern (e.g., "Every Tuesday 5 PM EST, weekly").

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| mentor_id | bigint | FK → mentors, CASCADE DELETE | Mentor's schedule |
| day_of_week | enum | NOT NULL | 'mon'/'tue'/'wed'/'thu'/'fri'/'sat'/'sun' |
| start_time | time | NOT NULL | Start time (HH:MM:SS) |
| timezone | string | DEFAULT 'America/New_York' | Mentor's timezone |
| frequency | enum | DEFAULT 'weekly' | 'weekly' or 'biweekly' |
| max_spots | integer | DEFAULT 3 | Max participants per session |
| is_active | boolean | DEFAULT true | Schedule active status |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (mentor_id, is_active)`

**Foreign Keys:**
- `mentor_id` → mentors(id) CASCADE DELETE

---

## 11. **office_hour_sessions**
Actual office hour sessions generated from schedules (CRON-generated).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| schedule_id | bigint | FK → office_hour_schedules, CASCADE DELETE | Parent schedule |
| current_service_id | bigint | FK → services_config | Rotated service for this session |
| session_date | date | NOT NULL | Date of session |
| start_time | time | NOT NULL | Session start time |
| timezone | string | DEFAULT 'America/New_York' | Mentor's timezone |
| current_occupancy | integer | DEFAULT 0 | Current spot count |
| max_spots | integer | DEFAULT 3 | Max capacity |
| is_full | boolean | DEFAULT false | Full status flag |
| service_locked | boolean | DEFAULT false | Cannot change service after 2nd student books |
| first_booker_id | bigint | FK → users, NULLABLE, NULL ON DELETE | First student to book |
| first_booked_at | timestamp | NULLABLE | Time of first booking |
| service_choice_cutoff_at | timestamp | NULLABLE | Deadline for service choice (24hrs before) |
| status | enum | DEFAULT 'upcoming' | 'upcoming'/'in_progress'/'completed'/'cancelled' |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (schedule_id, session_date)`
- `INDEX (status, session_date)`
- `INDEX (is_full)`

**Foreign Keys:**
- `schedule_id` → office_hour_schedules(id) CASCADE DELETE
- `current_service_id` → services_config(id)
- `first_booker_id` → users(id) NULL ON DELETE

---

## 12. **user_credits**
Current credit balance per user (denormalized for performance).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| user_id | bigint | FK → users, UNIQUE, CASCADE DELETE | One credit balance per user |
| balance | integer | DEFAULT 0, UNSIGNED | Current available credits |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Unique Constraint:** `UNIQUE (user_id)`

**Foreign Keys:**
- `user_id` → users(id) CASCADE DELETE

**Notes:** Updated atomically with every credit transaction

---

## 13. **office_hours_subscriptions**
$200/month Office Hours subscription (grants 5 credits per cycle).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| user_id | bigint | FK → users, CASCADE DELETE | Subscriber |
| program | enum | NOT NULL | 'mba'/'law'/'therapy' (for demand tracking) |
| stripe_subscription_id | string | UNIQUE | Stripe subscription ID |
| stripe_customer_id | string | NULLABLE | Stripe customer ID |
| credits_per_cycle | integer | DEFAULT 5 | Credits granted per billing cycle |
| current_period_start | timestamp | NULLABLE | Billing period start |
| current_period_end | timestamp | NULLABLE | Billing period end |
| status | enum | DEFAULT 'active' | 'active'/'cancelled'/'past_due'/'incomplete' |
| cancelled_at | timestamp | NULLABLE | Cancellation timestamp |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (user_id, status)`
- `INDEX (stripe_subscription_id)`

**Foreign Keys:**
- `user_id` → users(id) CASCADE DELETE

---

## 14. **bookings**
Session booking records (1-on-1, group, office hours).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| student_id | bigint | FK → users, CASCADE DELETE | Booking student |
| mentor_id | bigint | FK → mentors, CASCADE DELETE | Mentor being booked |
| service_config_id | bigint | FK → services_config | Service booked |
| office_hour_session_id | bigint | FK → office_hour_sessions, NULLABLE, NULL ON DELETE | Office hours session (null for 1-on-1/group) |
| meeting_size | enum | DEFAULT '1on1' | '1on1'/'1on3'/'1on5'/'office_hours' |
| session_at | timestamp | NOT NULL | Session datetime (UTC) |
| session_timezone | string | NULLABLE | Mentor's timezone for display |
| duration_minutes | integer | DEFAULT 60 | Session duration |
| meeting_link | string | NULLABLE | Zoom/Google Meet link |
| meeting_type | enum | DEFAULT 'zoom' | 'zoom' or 'google_meet' |
| credits_charged | integer | DEFAULT 1 | Credits deducted |
| status | enum | DEFAULT 'pending' | 'pending'/'confirmed'/'completed'/'cancelled'/'cancelled_pending_refund'/'no_show' |
| cancelled_at | timestamp | NULLABLE | Cancellation time |
| cancel_reason | string | NULLABLE | Reason for cancellation |
| cancelled_by | bigint | FK → users, NULLABLE, NULL ON DELETE | Who cancelled |
| feedback_due_at | timestamp | NULLABLE | Feedback deadline (24hrs after session) |
| student_feedback_done | boolean | DEFAULT false | Student feedback submitted |
| mentor_feedback_done | boolean | DEFAULT false | Mentor feedback submitted |
| is_group_payer | boolean | DEFAULT false | This student paid for group |
| group_payer_id | bigint | FK → users, NULLABLE, NULL ON DELETE | Who paid for the group |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (student_id, status, session_at)`
- `INDEX (mentor_id, status, session_at)`
- `INDEX (status, session_at)`
- `INDEX (feedback_due_at)`
- `INDEX (session_at)`

**Foreign Keys:**
- `student_id` → users(id) CASCADE DELETE
- `mentor_id` → mentors(id) CASCADE DELETE
- `service_config_id` → services_config(id)
- `office_hour_session_id` → office_hour_sessions(id) NULL ON DELETE
- `cancelled_by` → users(id) NULL ON DELETE
- `group_payer_id` → users(id) NULL ON DELETE

---

## 15. **credit_transactions**
Immutable ledger of all credit changes (never update, only insert).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| user_id | bigint | FK → users, CASCADE DELETE | User account |
| booking_id | bigint | FK → bookings, NULLABLE, NULL ON DELETE | Related booking (null for admin manual) |
| subscription_id | bigint | FK → office_hours_subscriptions, NULLABLE, NULL ON DELETE | Related subscription |
| type | enum | NOT NULL | 'purchase'/'subscription'/'deduction'/'refund'/'manual' |
| amount | integer | NOT NULL | Positive (add) or negative (deduct) |
| balance_after | integer | NOT NULL | Balance after this transaction |
| stripe_payment_id | string | NULLABLE | Stripe payment ID |
| stripe_event_id | string | NULLABLE | Stripe event ID (for idempotency) |
| stripe_subscription_id | string | NULLABLE | Stripe subscription ID |
| office_hours_program | enum | NULLABLE | 'mba'/'law'/'therapy' (for subscription credits) |
| description | string | NULLABLE | Human-readable description |
| performed_by | bigint | FK → users, NULLABLE, NULL ON DELETE | Admin who performed manual transaction |
| created_at | timestamp | DEFAULT NOW(), NOT NULL | Transaction timestamp |

**Indexes:**
- `INDEX (user_id, created_at)`
- `INDEX (type)`
- `INDEX (stripe_payment_id)`

**Foreign Keys:**
- `user_id` → users(id) CASCADE DELETE
- `booking_id` → bookings(id) NULL ON DELETE
- `subscription_id` → office_hours_subscriptions(id) NULL ON DELETE
- `performed_by` → users(id) NULL ON DELETE

**Notes:** Immutable — never updated, only inserted for audit trail

---

## 16. **stripe_webhooks**
Raw Stripe event log (prevents duplicate processing).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| event_id | string | UNIQUE | Stripe event ID (idempotency guard) |
| event_type | string | NOT NULL | e.g., 'checkout.session.completed' |
| payload | json | NOT NULL | Full Stripe webhook payload |
| processed | boolean | DEFAULT false | Processing status |
| error_message | string | NULLABLE | Error details if failed |
| received_at | timestamp | DEFAULT NOW() | Receipt time |
| processed_at | timestamp | NULLABLE | Processing completion time |

**Indexes:**
- `INDEX (event_id)`
- `INDEX (processed, received_at)`

---

## 17. **mentor_payouts**
Mentor payout records (Stripe Connect transfers).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| mentor_id | bigint | FK → mentors, CASCADE DELETE | Mentor receiving payout |
| amount | decimal(10,2) | NOT NULL | Payout amount |
| stripe_transfer_id | string | NULLABLE, UNIQUE | Stripe transfer ID |
| status | enum | DEFAULT 'pending' | 'pending'/'paid'/'failed' |
| failure_reason | string | NULLABLE | Failure details |
| payout_date | timestamp | NULLABLE | When payout was sent |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (mentor_id, status)`

**Foreign Keys:**
- `mentor_id` → mentors(id) CASCADE DELETE

---

## 18. **chats**
Real-time chat messages (available 24–48hrs before session).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| booking_id | bigint | FK → bookings, CASCADE DELETE | Related booking |
| sender_id | bigint | FK → users, CASCADE DELETE | Message sender |
| receiver_id | bigint | FK → users, CASCADE DELETE | Message recipient |
| message_text | text | NOT NULL | Message content |
| is_read | boolean | DEFAULT false | Read status |
| sent_at | timestamp | DEFAULT NOW() | Send timestamp |

**Indexes:**
- `INDEX (booking_id, sent_at)`
- `INDEX (receiver_id, is_read)`

**Foreign Keys:**
- `booking_id` → bookings(id) CASCADE DELETE
- `sender_id` → users(id) CASCADE DELETE
- `receiver_id` → users(id) CASCADE DELETE

---

## 19. **feedback**
Student post-session feedback with admin moderation (Station 3).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| booking_id | bigint | FK → bookings, CASCADE DELETE | Related booking |
| student_id | bigint | FK → users, CASCADE DELETE | Feedback author |
| mentor_id | bigint | FK → mentors, CASCADE DELETE | Mentor being reviewed |
| stars | tinyint | UNSIGNED, NOT NULL | Rating 1–5 |
| preparedness_rating | tinyint | UNSIGNED, NULLABLE | Mentor preparedness rating |
| comment | text | NOT NULL | Feedback text |
| recommend | boolean | DEFAULT true | Recommendation flag |
| service_type | string | NULLABLE | Denormalized service type |
| is_verified | boolean | DEFAULT true | Post-session verification flag |
| original_comment | text | NULLABLE | Immutable original (when amended) |
| is_visible | boolean | DEFAULT true | Visibility flag (false = soft hidden by admin) |
| admin_note | string | NULLABLE | Admin's moderation notes |
| amended_by | bigint | FK → users, NULLABLE, NULL ON DELETE | Admin who amended |
| amended_at | timestamp | NULLABLE | Amendment timestamp |
| mentor_reply | text | NULLABLE | Mentor's optional reply |
| replied_at | timestamp | NULLABLE | Reply timestamp |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Unique Constraint:** `UNIQUE (booking_id, student_id)` — supports group bookings (1on3/1on5)

**Indexes:**
- `INDEX (mentor_id, is_visible)`
- `INDEX (mentor_id, stars)`
- `INDEX (created_at)`
- `INDEX (service_type)`

**Foreign Keys:**
- `booking_id` → bookings(id) CASCADE DELETE
- `student_id` → users(id) CASCADE DELETE
- `mentor_id` → mentors(id) CASCADE DELETE
- `amended_by` → users(id) NULL ON DELETE

---

## 20. **mentor_feedback**
Mentor's mandatory post-session form about student engagement.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| booking_id | bigint | FK → bookings, CASCADE DELETE | Related booking |
| mentor_id | bigint | FK → mentors, CASCADE DELETE | Mentor author |
| student_id | bigint | FK → users, CASCADE DELETE | Student being evaluated |
| engagement_score | tinyint | UNSIGNED, NULLABLE | Engagement rating 1–5 |
| notes | text | NULLABLE | General notes |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Unique Constraint:** `UNIQUE (booking_id, mentor_id)` — one form per mentor per session

**Indexes:**
- `INDEX (mentor_id, student_id)`

**Foreign Keys:**
- `booking_id` → bookings(id) CASCADE DELETE
- `mentor_id` → mentors(id) CASCADE DELETE
- `student_id` → users(id) CASCADE DELETE

---

## 21. **mentor_notes**
Mentor-only sensitive session notes (5 structured questions, soft delete).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| mentor_id | bigint | FK → mentors, CASCADE DELETE | Note author (mentor) |
| student_id | bigint | FK → users, CASCADE DELETE | Student being discussed |
| booking_id | bigint | FK → bookings, NULLABLE, NULL ON DELETE | Related session (optional) |
| session_date | date | NOT NULL | Session date |
| service_type | string | NULLABLE | Denormalized service type |
| worked_on | text | NULLABLE | Q1: What did you work on? |
| next_steps | text | NULLABLE | Q2: What should happen next? |
| session_result | text | NULLABLE | Q3: What was the result? |
| strengths_challenges | text | NULLABLE | Q4: Strength and challenge? |
| other_notes | text | NULLABLE | Q5: Other notes? |
| is_deleted | boolean | DEFAULT false | Soft delete flag (admin only) |
| deleted_by | bigint | FK → users, NULLABLE, NULL ON DELETE | Admin who deleted |
| deleted_at | timestamp | NULLABLE | Deletion timestamp |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (mentor_id, student_id)`
- `INDEX (mentor_id, is_deleted)`
- `INDEX (session_date)`

**Foreign Keys:**
- `mentor_id` → mentors(id) CASCADE DELETE
- `student_id` → users(id) CASCADE DELETE
- `booking_id` → bookings(id) NULL ON DELETE
- `deleted_by` → users(id) NULL ON DELETE

---

## 22. **mentor_ratings**
Source of truth for mentor aggregate stats (recalculated after feedback/moderation).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| mentor_id | bigint | FK → mentors, UNIQUE, CASCADE DELETE | Mentor being rated |
| avg_stars | decimal(3,2) | DEFAULT 0.00 | Average star rating (0.00–5.00) |
| recommend_rate | decimal(5,2) | DEFAULT 0.00 | Recommendation percentage (0.00–100.00) |
| total_reviews | integer | DEFAULT 0 | Total feedback count |
| total_sessions | integer | DEFAULT 0 | Total completed sessions |
| top_tag | string | NULLABLE | Most frequently mentioned tag |
| top_tags_json | json | NULLABLE | Top tags list as JSON array |
| recalculated_at | timestamp | NULLABLE | Last recalculation time |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Unique Constraint:** `UNIQUE (mentor_id)` — one rating per mentor

**Foreign Keys:**
- `mentor_id` → mentors(id) CASCADE DELETE

**Notes:** Single source of truth; mentors table references this, not vice versa

---

## 23. **support_tickets**
Support ticket system for user issues (Station 5 in Manual Controls).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| user_id | bigint | FK → users, CASCADE DELETE | Ticket creator |
| ticket_ref | string | UNIQUE | Auto-generated reference (e.g., SUP-00102) |
| subject | string | NOT NULL | Ticket subject |
| message | text | NOT NULL | User message (sanitized) |
| message_raw | text | NULLABLE | Raw original message (audit) |
| status | enum | DEFAULT 'open' | 'open'/'in_progress'/'resolved'/'closed' |
| admin_reply | text | NULLABLE | Admin response |
| handled_by | bigint | FK → users, NULLABLE, NULL ON DELETE | Admin handler |
| replied_at | timestamp | NULLABLE | Reply timestamp |
| created_at | timestamp | DEFAULT NOW() | Creation time |
| updated_at | timestamp | DEFAULT NOW() | Last update time |

**Indexes:**
- `INDEX (user_id, status)`
- `INDEX (status)`
- `INDEX (created_at)`

**Foreign Keys:**
- `user_id` → users(id) CASCADE DELETE
- `handled_by` → users(id) NULL ON DELETE

---

## 24. **admin_logs**
Immutable audit log of all admin manual actions (all 6 stations).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | bigint | PK, auto-increment | Primary Key |
| admin_id | bigint | FK → users, CASCADE DELETE | Admin performing action |
| action | string | NOT NULL | Action type (e.g., 'amend_mentor', 'manual_refund') |
| target_table | string | NOT NULL | Table affected (e.g., 'mentors', 'feedback') |
| target_id | bigint | NULLABLE | ID of affected record |
| before_state | json | NULLABLE | State snapshot before change |
| after_state | json | NULLABLE | State snapshot after change |
| ip_address | string | NULLABLE | Admin's IP address |
| user_agent | string | NULLABLE | Admin's user agent |
| notes | text | NULLABLE | Optional admin comment |
| created_at | timestamp | DEFAULT NOW(), NOT NULL | Action timestamp |

**Indexes:**
- `INDEX (admin_id, created_at)`
- `INDEX (target_table, target_id)`
- `INDEX (action)`

**Foreign Keys:**
- `admin_id` → users(id) CASCADE DELETE

**Notes:** Every admin manual write must log here; used for compliance/audit

---

## Entity Relationship Summary

### Core Users & Auth
- **users** ← root
- **oauth_tokens** ← users
- **user_settings** (1:1) ← users
- **password_resets** ← users (email-based, not FK)

### Mentors & Profiles
- **mentors** (1:1) ← users
- **mentor_ratings** (1:1) ← mentors (aggregate source of truth)
- **mentor_services** (M:M) ← mentors, services_config
- **mentor_payouts** ← mentors
- **mentor_notes** ← mentors, users (student)
- **mentor_feedback** ← mentors, users (student), bookings

### Universities & Programs
- **universities** ← root
- **university_programs** ← universities

### Services & Scheduling
- **services_config** ← root (admin-managed)
- **office_hour_schedules** ← mentors
- **office_hour_sessions** ← schedules, services_config
- **office_hours_subscriptions** ← users

### Bookings & Credits
- **bookings** ← students(users), mentors, services_config, office_hour_sessions
- **user_credits** (1:1) ← users
- **credit_transactions** (immutable ledger) ← users, bookings, subscriptions

### Feedback & Reviews
- **feedback** ← bookings, students(users), mentors
- **chats** ← bookings, sender(users), receiver(users)

### Payments
- **stripe_webhooks** ← root (idempotency log)

### Admin & Audit
- **admin_logs** (immutable) ← admin(users)
- **support_tickets** ← users

---

## Key Design Patterns

### 1. Immutable Ledgers
- `credit_transactions` — never update, only insert
- `admin_logs` — never update, only insert
- `stripe_webhooks` — immutable event log for idempotency

### 2. Composite Unique Keys
- `oauth_tokens(user_id, provider)` — prevent duplicate provider per user
- `feedback(booking_id, student_id)` — support group bookings (1on3/1on5)
- `mentor_feedback(booking_id, mentor_id)` — one form per mentor per session

### 3. Soft Delete vs Hard Delete
- `mentor_notes.is_deleted` — soft delete with audit trail
- All others — hard delete with CASCADE

### 4. Single Source of Truth
- `mentor_ratings` — aggregate source of truth (not duplicated in mentors)
- `user_credits.balance` — current balance snapshot
- `services_config` — global pricing maintained here only

### 5. Denormalization for Performance
- `bookings.session_timezone` — avoid repeated timezone lookups
- `feedback.service_type` — fast filtering without joins
- `mentor_notes.service_type` — same pattern
- `user_credits.balance` — atomic snapshot for performance

### 6. Foreign Key Cascades
- `CASCADE DELETE` — for dependent records (mentors → mentors table deletes if user deleted)
- `NULL ON DELETE` — for optional references (approved_by, cancelled_by, etc.)

---

## Migration Files Reference

| Table | Migration File |
|-------|---|
| users | 2024_01_01_000001_create_users_table.php |
| oauth_tokens | 2024_01_01_000002_create_oauth_tokens_table.php |
| password_resets | 2024_01_01_000003_create_password_resets_table.php |
| user_settings | 2024_01_01_000004_create_user_settings_table.php |
| universities | 2024_01_01_000005_create_universities_table.php |
| university_programs | 2024_01_01_000006_create_university_programs_table.php |
| mentors | 2024_01_01_000007_create_mentors_table.php |
| services_config | 2024_01_01_000008_create_services_config_table.php |
| mentor_services | 2024_01_01_000009_create_mentor_services_table.php |
| office_hour_schedules | 2024_01_01_000010_create_office_hour_schedules_table.php |
| office_hour_sessions | 2024_01_01_000011_create_office_hour_sessions_table.php |
| user_credits | 2024_01_01_000012_create_user_credits_table.php |
| office_hours_subscriptions | 2024_01_01_000013_create_office_hours_subscriptions_table.php |
| bookings | 2024_01_01_000014_create_bookings_table.php |
| credit_transactions | 2024_01_01_000015_create_credit_transactions_table.php |
| stripe_webhooks | 2024_01_01_000016_create_stripe_webhooks_table.php |
| mentor_payouts | 2024_01_01_000017_create_mentor_payouts_table.php |
| chats | 2024_01_01_000018_create_chats_table.php |
| feedback | 2024_01_01_000019_create_feedback_table.php |
| mentor_feedback | 2024_01_01_000020_create_mentor_feedback_table.php |
| mentor_notes | 2024_01_01_000021_create_mentor_notes_table.php |
| mentor_ratings | 2024_01_01_000022_create_mentor_ratings_table.php |
| support_tickets | 2024_01_01_000023_create_support_tickets_table.php |
| admin_logs | 2024_01_01_000024_create_admin_logs_table.php |

---

**Last Updated:** April 10, 2026  
**Status:** Production-ready (all 24 tables documented)
