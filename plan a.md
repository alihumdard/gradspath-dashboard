
GRADS PATHS
Backend Architecture & Development Plan


Stack	Laravel 11 · MySQL 8 · Spatie RBAC · Laravel Reverb · Stripe
Total Tables	24 tables across 10 modules
Total Models	24 Eloquent models + Spatie role/permission models
Total Controllers	28 controllers across 3 milestones
Total Services	12 business logic services
Total CRON Jobs	7 scheduled Artisan commands
Last Updated	April 10, 2026

Confidential — Internal Development Reference
 
1. Database Schema — All 24 Tables
Complete column reference for every table. All migrations are in dependency order (000001–000024). Run php artisan migrate --seed after publishing Spatie permission tables.

Module 1 — Auth & Users (Tables 1–4)
Table 1: users
Root table. All platform actors — students, mentors, admins — are rows here. Roles assigned via Spatie model_has_roles.
Column	Type	Constraints	Notes
id	bigint	PK, auto-increment	Primary key
name	string	NOT NULL	Full name
email	string	UNIQUE, NOT NULL	Login identifier
password_hash	string	NULLABLE	Null for social-only accounts
avatar_url	string	NULLABLE	Profile picture URL
is_active	boolean	DEFAULT true	Account activation flag
email_verified_at	timestamp	NULLABLE	OAuth / email verification
remember_token	string	NULLABLE	Laravel remember-me token
created_at	timestamp	DEFAULT NOW()	
updated_at	timestamp	DEFAULT NOW()	
Indexes: email · is_active · created_at

Table 2: oauth_tokens
Column	Type	Constraints	Notes
id	bigint	PK	
user_id	bigint	FK → users CASCADE	
provider	string	DEFAULT 'google'	Extensible for future providers
provider_user_id	string	NOT NULL	Provider's UID
access_token	text	NOT NULL	
refresh_token	text	NULLABLE	
token_expires_at	timestamp	NULLABLE	
created_at / updated_at	timestamp	DEFAULT NOW()	
Unique: (provider, provider_user_id) and (user_id, provider)

Table 3: password_resets
Column	Type	Constraints	Notes
email	string	INDEXED	Not FK — allows reset for deleted users
token	string	UNIQUE	Cryptographically secure
expires_at	timestamp	NOT NULL	24-hour expiry
created_at	timestamp	DEFAULT NOW()	

Table 4: user_settings
Column	Type	Constraints	Notes
id	bigint	PK	
user_id	bigint	FK → users UNIQUE CASCADE	One row per user
theme	enum	DEFAULT 'light'	'light' or 'dark'
email_notifications	boolean	DEFAULT true	
sms_notifications	boolean	DEFAULT false	
created_at / updated_at	timestamp	DEFAULT NOW()	

Module 2 — Institutions (Tables 5–6)
Table 5: universities
Column	Type	Constraints	Notes
id	bigint	PK	
name	string	NOT NULL	Full name
display_name	string	NULLABLE	"Harvard", "Yale Law"
country	string	DEFAULT 'US'	
logo_url	string	NULLABLE	
tier	enum	NOT NULL	'elite' / 'top25' / 'regional'
is_active	boolean	DEFAULT true	
created_at / updated_at	timestamp	DEFAULT NOW()	
Indexes: tier · is_active · name

Table 6: university_programs
Column	Type	Constraints	Notes
id	bigint	PK	
university_id	bigint	FK → universities CASCADE	
program_name	string	NOT NULL	
program_type	enum	NOT NULL	mba / law / therapy / cmhc / mft / msw / clinical_psy / other
description	string	NULLABLE	
duration_months	integer	NULLABLE	
is_active	boolean	DEFAULT true	
created_at / updated_at	timestamp	DEFAULT NOW()	

Module 3 — Mentors (Table 7)
Table 7: mentors
Updated table — includes avatar crop fields, grad_school_display, and stripe_onboarding_complete added from template review.
Column	Type	Constraints	Notes
id	bigint	PK	
user_id	bigint	FK → users UNIQUE CASCADE	1:1 with users
university_id	bigint	FK → universities NULL	Alma mater
title	string	NULLABLE	"PhD Psychology", "MBA"
grad_school_display	string	NULLABLE	Short: "Harvard", "Yale Law" ★
mentor_type	enum	NOT NULL	'graduate' or 'professional'
program_type	enum	NULLABLE	mba / law / therapy / cmhc / mft / msw ...
bio	text	NULLABLE	
description	text	NULLABLE	Longer "about" text on expanded card
office_hours_schedule	string	NULLABLE	"Every Tuesday at 5 PM EST"
avatar_url	string	NULLABLE	
avatar_crop_zoom	decimal(4,2)	NULLABLE	Crop param ★
avatar_crop_x	decimal(6,2)	NULLABLE	Crop param ★
avatar_crop_y	decimal(6,2)	NULLABLE	Crop param ★
edu_email	string	NULLABLE	Required if mentor_type = graduate
calendly_link	string	NULLABLE	
slack_link	string	NULLABLE	
is_featured	boolean	DEFAULT false	"Mentors of the Week" flag
stripe_account_id	string	NULLABLE	
payouts_enabled	boolean	DEFAULT false	
stripe_onboarding_complete	boolean	DEFAULT false	Flips on Stripe webhook ★
status	enum	DEFAULT 'pending'	pending / active / paused / rejected
approved_at	timestamp	NULLABLE	
approved_by	bigint	FK → users NULL	Admin who approved
created_at / updated_at	timestamp	DEFAULT NOW()	
★ = column added after template review.
★★ IMPORTANT: avg_rating and total_sessions were removed — they are now stored ONLY in mentor_ratings table (single source of truth)
Indexes: mentor_type · program_type · is_featured · status · (university_id, program_type)

Module 4 — Services & Office Hours (Tables 8–11)
Table 8: services_config
Global admin-managed catalog. Single source of truth for all pricing. Admin updates here sync across the entire platform (Station 6).
Column	Type	Notes
id	bigint PK	
service_name	string	"Interview Prep", "Tutoring"...
service_slug	string UNIQUE	"interview_prep"
duration_minutes	integer DEFAULT 60	
is_active	boolean DEFAULT true	
price_1on1	decimal(8,2) NULLABLE	$70.00
price_1on3_per_person	decimal(8,2) NULLABLE	$62.99
price_1on3_total	decimal(8,2) NULLABLE	$188.98
price_1on5_per_person	decimal(8,2) NULLABLE	$55.99
price_1on5_total	decimal(8,2) NULLABLE	$279.97
is_office_hours	boolean DEFAULT false	Special OH flag
office_hours_subscription_price	decimal(8,2) NULLABLE	$200/month
credit_cost_1on1	integer DEFAULT 1	
credit_cost_1on3	integer DEFAULT 1	
credit_cost_1on5	integer DEFAULT 1	
sort_order	integer DEFAULT 0	

Table 9: mentor_services
Many-to-many bridge — which mentor offers which service.
Column	Type	Constraints
id	bigint	PK
mentor_id	bigint	FK → mentors CASCADE
service_config_id	bigint	FK → services_config CASCADE
is_active	boolean	DEFAULT true
sort_order	integer	DEFAULT 0
Unique: (mentor_id, service_config_id)

Table 10: office_hour_schedules
Recurring pattern definition. CRON reads this to generate sessions each week.
Column	Type	Notes
id	bigint PK	
mentor_id	bigint FK → mentors CASCADE	
day_of_week	enum	mon / tue / wed / thu / fri / sat / sun
start_time	time NOT NULL	HH:MM:SS
timezone	string DEFAULT 'America/New_York'	
frequency	enum DEFAULT weekly	'weekly' or 'biweekly'
max_spots	integer DEFAULT 3	Max 3 per session per doc
is_active	boolean DEFAULT true	

Table 11: office_hour_sessions
Actual generated session instances. Contains all real-time booking state — spot tracking, service lock, first-booker logic.
Column	Type	Notes
id	bigint PK	
schedule_id	bigint FK → office_hour_schedules CASCADE	
current_service_id	bigint FK → services_config	Rotated service for this session
session_date	date NOT NULL	
start_time	time NOT NULL	
timezone	string	
current_occupancy	integer DEFAULT 0	Real-time spot count
max_spots	integer DEFAULT 3	
is_full	boolean DEFAULT false	Set when occupancy = max_spots
service_locked	boolean DEFAULT false	True after 2nd student books
first_booker_id	bigint FK → users NULL	First student — may choose service
first_booked_at	timestamp NULLABLE	
service_choice_cutoff_at	timestamp NULLABLE	24hrs before session start
status	enum DEFAULT upcoming	upcoming / in_progress / completed / cancelled
Indexes: (schedule_id, session_date) · (status, session_date) · is_full

Module 5 — Payments & Credits (Tables 12–17)
Table 12: user_credits
Live balance snapshot per user. Updated atomically on every credit transaction.
Column	Type	Constraints
id	bigint	PK
user_id	bigint	FK → users UNIQUE CASCADE
balance	integer UNSIGNED	DEFAULT 0
created_at / updated_at	timestamp	

Table 13: office_hours_subscriptions
NEW TABLE — $200/month recurring subscription. Grants 5 credits per billing cycle via Stripe webhook.
Column	Type	Notes
id	bigint PK	
user_id	bigint FK → users CASCADE	
program	enum	mba / law / therapy (demand tracking only)
stripe_subscription_id	string UNIQUE	Stripe sub ID
stripe_customer_id	string NULLABLE	
credits_per_cycle	integer DEFAULT 5	5 credits per month
current_period_start	timestamp NULLABLE	Updated by Stripe webhook
current_period_end	timestamp NULLABLE	
status	enum DEFAULT active	active / cancelled / past_due / incomplete
cancelled_at	timestamp NULLABLE	

Table 14: bookings
Central operational hub. Every session booking — 1:1, group, office hours — creates a row here. Updated status is single source of truth for the session lifecycle.
Column	Type	Notes
id	bigint PK	
student_id	bigint FK → users CASCADE	
mentor_id	bigint FK → mentors CASCADE	
service_config_id	bigint FK → services_config	
office_hour_session_id	bigint FK → office_hour_sessions NULL	Null for 1:1 / group
meeting_size	enum DEFAULT 1on1	1on1 / 1on3 / 1on5 / office_hours
session_at	timestamp NOT NULL	UTC datetime
session_timezone	string NULLABLE	Display timezone
duration_minutes	integer DEFAULT 60	
meeting_link	string NULLABLE	Zoom/Meet URL — generated on confirm
meeting_type	enum DEFAULT zoom	'zoom' or 'google_meet'
credits_charged	integer NOT NULL	Calculated from services_config.credit_cost_XXX based on meeting_size. For 1on3/1on5, uses cost_1on3/cost_1on5 (NOT always 1!)
status	enum DEFAULT pending	pending / confirmed / completed / cancelled / cancelled_pending_refund / no_show ★
cancelled_at	timestamp NULLABLE	
cancel_reason	string NULLABLE	
cancelled_by	bigint FK → users NULL	
feedback_due_at	timestamp NULLABLE	24hrs after session end
student_feedback_done	boolean DEFAULT false	Gates new bookings if false past due
mentor_feedback_done	boolean DEFAULT false	Gates mentor from accepting sessions
is_group_payer	boolean DEFAULT false	This student paid for the group
group_payer_id	bigint FK → users NULL	Who paid for the group ★
★ = added from template review. Indexes: (student_id, status, session_at) · (mentor_id, status, session_at) · feedback_due_at · session_at

Table 15: credit_transactions
Immutable ledger — never update, only insert. Every credit change writes a row. balance_after is a snapshot for easy audit.
Column	Type	Notes
id	bigint PK	
user_id	bigint FK → users CASCADE	
booking_id	bigint FK → bookings NULL	Null for admin/subscription
subscription_id	bigint FK → office_hours_subscriptions NULL	
type	enum NOT NULL	purchase / subscription / deduction / refund / manual ★
amount	integer NOT NULL	Positive = add, negative = deduct
balance_after	integer NOT NULL	Snapshot for audit trail
stripe_payment_id	string NULLABLE	
stripe_event_id	string NULLABLE	Idempotency reference
stripe_subscription_id	string NULLABLE	★
office_hours_program	enum NULLABLE	mba / law / therapy — for subscription rows ★
description	string NULLABLE	Human-readable label
performed_by	bigint FK → users NULL	Admin for manual transactions

Table 16: stripe_webhooks
Raw Stripe event log. event_id unique constraint is the idempotency guard — prevents double credit grants if a webhook fires twice.
Column	Type	Notes
id	bigint PK	
event_id	string UNIQUE	Stripe evt_xxx — idempotency key
event_type	string NOT NULL	"checkout.session.completed"
payload	json NOT NULL	Full Stripe payload stored for replay
processed	boolean DEFAULT false	
error_message	string NULLABLE	Populated on processing failure
received_at	timestamp DEFAULT NOW()	
processed_at	timestamp NULLABLE	

Table 17: mentor_payouts
Column	Type	Notes
id	bigint PK	
mentor_id	bigint FK → mentors CASCADE	
amount	decimal(10,2) NOT NULL	
stripe_transfer_id	string NULLABLE UNIQUE	
status	enum DEFAULT pending	pending / paid / failed
failure_reason	string NULLABLE	
payout_date	timestamp NULLABLE	

Module 6 — Bookings & Chat (Table 18)
Table 18: chats
Real-time messages. Chat window opens 24–48hrs before session. Messages broadcast via Laravel Reverb.
Column	Type	Notes
id	bigint PK	
booking_id	bigint FK → bookings CASCADE	
sender_id	bigint FK → users CASCADE	
receiver_id	bigint FK → users CASCADE	
message_text	text NOT NULL	
is_read	boolean DEFAULT false	
sent_at	timestamp DEFAULT NOW()	
Indexes: (booking_id, sent_at) · (receiver_id, is_read)

Module 7 — Feedback (Tables 19–22)
Table 19: feedback
Student post-session feedback. Updated table includes admin moderation columns for Station 3 manual control. original_comment is immutable — stored when admin first amends a review.
Column	Type	Notes
id	bigint PK	
booking_id	bigint FK → bookings CASCADE	
student_id	bigint FK → users CASCADE	
mentor_id	bigint FK → mentors CASCADE	
stars	tinyint UNSIGNED NOT NULL	1–5
preparedness_rating	tinyint UNSIGNED NULLABLE	Mentor preparedness score
comment	text NOT NULL	Current display text
recommend	boolean DEFAULT true	
service_type	string NULLABLE	Denormalized for fast filtering
is_verified	boolean DEFAULT true	Only submittable after completed booking
original_comment	text NULLABLE	Immutable copy before first amendment ★
is_visible	boolean DEFAULT true	false = soft hidden by admin ★
admin_note	text NULLABLE	Moderation notes ★
amended_by	bigint FK → users NULL	Admin who amended ★
amended_at	timestamp NULLABLE	★
mentor_reply	text NULLABLE	Optional mentor response
replied_at	timestamp NULLABLE	
★ = added from template review. Unique: (booking_id, student_id). Indexes: (mentor_id, is_visible) · (mentor_id, stars) · service_type

Table 20: mentor_feedback
Mentor mandatory post-session form. Enforced within 24hrs — gates mentor from accepting new sessions if overdue.
Column	Type	Notes
id	bigint PK	
booking_id	bigint FK → bookings CASCADE	
mentor_id	bigint FK → mentors CASCADE	
student_id	bigint FK → users CASCADE	
engagement_score	tinyint UNSIGNED NULLABLE	1–5
notes	text NULLABLE	
Unique: (booking_id, mentor_id) — one form per mentor per session

Table 21: mentor_notes
Highly sensitive — mentor-only, zero student access enforced via Laravel Policy. Updated from single note_body to 5 structured question columns matching the demo8.html template.
Column	Type	Notes
id	bigint PK	
mentor_id	bigint FK → mentors CASCADE	
student_id	bigint FK → users CASCADE	
booking_id	bigint FK → bookings NULL	Links note to specific session
session_date	date NOT NULL	
service_type	string NULLABLE	Denormalized
worked_on	text NULLABLE	Q1: What did you work on? ★
next_steps	text NULLABLE	Q2: What should happen next? ★
session_result	text NULLABLE	Q3: What was the result? ★
strengths_challenges	text NULLABLE	Q4: Strength and challenge? ★
other_notes	text NULLABLE	Q5: Any other notes? ★
is_deleted	boolean DEFAULT false	Soft delete — admin only
deleted_by	bigint FK → users NULL	
deleted_at	timestamp NULLABLE	
★ = replaces old single note_body column. Indexes: (mentor_id, student_id) · (mentor_id, is_deleted) · session_date

Table 22: mentor_ratings
Pre-aggregated stats. Recalculated after every feedback submit or admin moderation. Avoids expensive AVG/COUNT on every page load.
Column	Type	Notes
id	bigint PK	
mentor_id	bigint UNIQUE FK → mentors CASCADE	One row per mentor
avg_stars	decimal(3,2) DEFAULT 0.00	0.00–5.00
recommend_rate	decimal(5,2) DEFAULT 0.00	Percentage 0.00–100.00
total_reviews	integer DEFAULT 0	
total_sessions	integer DEFAULT 0	
top_tag	string NULLABLE	"Clear advice"
top_tags_json	json NULLABLE	Full top-tags list
recalculated_at	timestamp NULLABLE	

Module 8 — Admin & Support (Tables 23–24)
Table 23: support_tickets
Column	Type	Notes
id	bigint PK	
user_id	bigint FK → users CASCADE	
ticket_ref	string UNIQUE	Auto-generated e.g. SUP-00102
subject	string NOT NULL	
message	text NOT NULL	Sanitized version
message_raw	text NULLABLE	Original unsanitized — audit only
status	enum DEFAULT open	open / in_progress / resolved / closed
admin_reply	text NULLABLE	
handled_by	bigint FK → users NULL	Admin responder
replied_at	timestamp NULLABLE	

Table 24: admin_logs
Immutable audit log. Every Manual Action station write must log here with before/after JSON state. Used for compliance and undo.
Column	Type	Notes
id	bigint PK	
admin_id	bigint FK → users CASCADE	
action	string NOT NULL	"amend_mentor", "manual_refund", "delete_feedback"
target_table	string NOT NULL	"mentors", "feedback", "user_credits"
target_id	bigint NULLABLE	ID of affected record
before_state	json NULLABLE	State snapshot before change
after_state	json NULLABLE	State snapshot after change
ip_address	string NULLABLE	
user_agent	string NULLABLE	
notes	text NULLABLE	Admin comment on why action was taken
Indexes: (admin_id, created_at) · (target_table, target_id) · action
 
2. Eloquent Models (24 Models)
All models live in app/Models/. Spatie HasRoles trait on User. All models use $guarded = [] for simplicity — tighten with $fillable per preference.

Model	Table	Key Traits	Key Relationships
User	users	HasRoles, Notifiable, HasFactory	hasOne: Mentor, UserSetting, UserCredit | hasMany: Bookings, Tickets, OauthTokens
OauthToken	oauth_tokens	BelongsToUser	belongsTo: User
PasswordReset	password_resets	no timestamps	email-based, no FK
UserSetting	user_settings	—	belongsTo: User
University	universities	SoftDeletes	hasMany: UniversityProgram, Mentor
UniversityProgram	university_programs	—	belongsTo: University
Mentor	mentors	HasFactory	belongsTo: User, University | hasMany: MentorService, OfficeHourSchedule, Booking, MentorNote, MentorPayout | hasOne: MentorRating
ServiceConfig	services_config	—	hasMany: MentorService, Booking | hasManyThrough: OfficeHourSession
MentorService	mentor_services	pivot	belongsTo: Mentor, ServiceConfig
OfficeHourSchedule	office_hour_schedules	—	belongsTo: Mentor | hasMany: OfficeHourSession
OfficeHourSession	office_hour_sessions	—	belongsTo: OfficeHourSchedule, ServiceConfig | hasMany: Booking | belongsTo first_booker: User
UserCredit	user_credits	—	belongsTo: User | hasMany: CreditTransaction
OfficeHoursSubscription	office_hours_subscriptions	—	belongsTo: User | hasMany: CreditTransaction
Booking	bookings	HasFactory	belongsTo: student(User), Mentor, ServiceConfig, OfficeHourSession | hasMany: Chat | hasOne: Feedback, MentorFeedback, MentorNote
CreditTransaction	credit_transactions	immutable	belongsTo: User, Booking, OfficeHoursSubscription
StripeWebhook	stripe_webhooks	no update	standalone log
MentorPayout	mentor_payouts	—	belongsTo: Mentor
Chat	chats	Broadcastable	belongsTo: Booking, sender(User), receiver(User)
Feedback	feedback	—	belongsTo: Booking, student(User), Mentor | belongsTo amended_by: User
MentorFeedback	mentor_feedback	—	belongsTo: Booking, Mentor, student(User)
MentorNote	mentor_notes	SoftDeletes custom	belongsTo: Mentor, student(User), Booking
MentorRating	mentor_ratings	—	belongsTo: Mentor
SupportTicket	support_tickets	—	belongsTo: User, handled_by(User)
AdminLog	admin_logs	immutable	belongsTo: admin(User)

Model Casts Reference
Add these casts to each model as appropriate:

Model	Cast
Booking	status → BookingStatusEnum | session_at → datetime | feedback_due_at → datetime | student_feedback_done → boolean
Mentor	status → MentorStatusEnum | is_featured → boolean | payouts_enabled → boolean | avatar_crop_zoom → float
CreditTransaction	amount → integer | balance_after → integer | type → CreditTypeEnum
OfficeHourSession	session_date → date | is_full → boolean | service_locked → boolean | service_choice_cutoff_at → datetime
Feedback	stars → integer | recommend → boolean | is_visible → boolean | amended_at → datetime
MentorNote	is_deleted → boolean | session_date → date | deleted_at → datetime
StripeWebhook	payload → array | processed → boolean
OfficeHoursSubscription	current_period_start → datetime | current_period_end → datetime | credits_per_cycle → integer
 
3. Laravel Policies
Policies enforce record-level ownership on top of Spatie role-based permissions. Register all policies in AuthServiceProvider.

Policy	Method	Rule
BookingPolicy	view(User, Booking)	booking.student_id === user.id OR booking.mentor.user_id === user.id
BookingPolicy	cancel(User, Booking)	if booking.is_group_payer: only group_payer_id can cancel | else: student can cancel own
MentorNotePolicy	viewAny(User)	user has role mentor OR admin
MentorNotePolicy	view(User, MentorNote)	note.mentor.user_id === user.id
MentorNotePolicy	create(User)	user has role mentor
MentorNotePolicy	update(User, MentorNote)	note.mentor.user_id === user.id
FeedbackPolicy	create(User, Booking)	booking.student_id === user.id + booking.status === 'completed' + no existing feedback
FeedbackPolicy	reply(User, Feedback)	feedback.mentor_id === user.mentor.id (mentor replying to their own feedback)
ChatPolicy	send(User, Booking)	user is student_id OR mentor.user_id on that booking
MentorSettingsPolicy	update(User, Mentor)	mentor.user_id === user.id
SupportTicketPolicy	viewAny(User)	admin: all | student/mentor: own tickets only
FeedbackPolicy	moderate(User)	user has role admin
MentorNotePolicy	delete(User, MentorNote)	user has role admin (soft delete only)
 
4. Controllers — 3 Milestones

Milestone 1 — Admin Side (10 Controllers)
All routes prefixed /api/v1/admin — protected by auth:sanctum + role:admin middleware

Controller	Methods	Route	Notes
Admin\AuthController	login, logout	/admin/auth	Separate admin login page
Admin\OverviewController	index	/admin/overview	KPI cards, 6-month charts, leaderboards
Admin\UserManagementController	index, update, destroy, export	/admin/users	CSV export via Pandas · per-service session counts
Admin\MentorManagementController	index, update, approve, reject, pause	/admin/mentors	Performance table · missed sessions · refunds
Admin\ServicesController	index, store, update, destroy	/admin/services	Station 5 — create/edit service types
Admin\RevenueController	index	/admin/revenue	Gross / payout / platform / refund breakdown
Admin\RankingsController	index	/admin/rankings	Programs · services · schools · meeting size mix
Admin\ManualActionsController	amendMentor, refundCredits, moderateFeedback, manageInstitution, manageProgram, updatePricing	/admin/manual	All 6 stations — each logs to admin_logs
Admin\InstitutionsController	index, store, update, destroy	/admin/institutions	Station 4 — universities + programs
Admin\SupportTicketsController	index, show, reply, updateStatus	/admin/tickets	View all tickets · reply · status changes

Milestone 2 — Mentor Side (7 Controllers)
All routes prefixed /api/v1/mentor — protected by auth:sanctum + role:mentor middleware

Controller	Methods	Route	Notes
Mentor\ProfileController	show, update, uploadAvatar	/mentor/profile	Crop params processed server-side · .edu validation · old image deletion
Mentor\StripeController	onboard, webhook	/mentor/stripe	Connect Express onboarding · webhook flips stripe_onboarding_complete
Mentor\OfficeHoursController	index, store, update, destroy	/mentor/office-hours	Manage schedules + service rotation order
Mentor\BookingsController	index, show	/mentor/bookings	View own booked sessions · calendar dates
Mentor\NotesController	index, store, show, update	/mentor/notes	RBAC + Policy ownership enforced · 5-question structured form
Mentor\FeedbackController	index, store, reply	/mentor/feedback	View received feedback · submit post-session mentor form · reply to review
Mentor\ChatController	index, send	/mentor/chat/{booking}	Only available 24–48hrs before session · Reverb broadcast

Milestone 3 — Student + Auth (11 Controllers)
Auth routes: public. Student routes: auth:sanctum + role:student|mentor

Controller	Methods	Route	Notes
Auth\RegisterController	register, verifyEmail	/auth/register	Creates user + UserCredit row + UserSetting row + assigns student role
Auth\LoginController	login, logout, refresh	/auth/login	Sanctum token · returns role + credit balance
Auth\SocialController	redirect, callback	/auth/google	Google OAuth · creates OauthToken · assigns student role if new user
Auth\PasswordController	forgot, reset	/auth/password	PasswordReset table · email via queued job
Student\DashboardController	index	/dashboard	Featured mentors (is_featured=true) · credit balance · university grid
Student\MentorSearchController	index, show	/mentors	Search + multi-filter (type, program, school) · paginated · credit check on book
Student\InstitutionsController	index, show, programs, mentors	/institutions	Tier + program type filters · university → programs → mentors flow
Student\OfficeHoursController	index, show	/office-hours	All mentor OH sessions · real-time spot data · service locked status
Student\BookingController	store, show, cancel	/bookings	Credit check → create → meeting link → deduct (atomic) · cancel → pending_refund
Student\FeedbackController	index, store	/feedback	Browse all public feedback · submit post-session (Policy: completed booking only)
Student\CreditsController	balance, purchase, subscribe	/credits	Live balance · Stripe checkout · OH subscription
 
5. Service Layer (12 Services)
All business logic lives in app/Services/. Controllers are thin — they validate request, call service, return response.

BookingService
Orchestrates the full booking creation flow atomically.
Method	Description
createBooking(User, array $data)	Credit check → DB transaction: create Booking row + deduct credits + write CreditTransaction + increment OH occupancy + fire BookingConfirmed event
cancelBooking(Booking, User)	Sets status = cancelled_pending_refund · logs to admin_logs for manual refund via Station 2 · fires BookingCancelled event
markCompleted(Booking)	Sets status = completed · sets feedback_due_at = now() + 24hrs · fires SessionCompleted event
openChatWindow(Booking)	Returns true if session_at - now() <= 48hrs (chat gate for Reverb)

CreditService
All credit mutations go through this service. Atomic DB transactions prevent partial updates.
Method	Description
deduct(User, int $amount, Booking)	DB::transaction: check balance >= amount · decrement UserCredit.balance · insert CreditTransaction(type=deduction)
refund(User, int $amount, Booking, User $admin)	DB::transaction: increment balance · insert CreditTransaction(type=refund) · log to admin_logs
grant(User, int $amount, string $reason, ?OfficeHoursSubscription)	For subscription cycle credits · insert CreditTransaction(type=subscription)
purchase(User, int $amount, string $stripePaymentId)	Called from Stripe webhook handler · insert CreditTransaction(type=purchase)

OfficeHourRotationService
Method	Description
rotateService(OfficeHourSchedule)	Advances current_service_id to next in mentor_services sort_order list. Called weekly by CRON.
generateSessionsForWeek()	Creates OfficeHourSession rows for all active schedules for the coming week. Sets service_choice_cutoff_at = session_at - 24hrs.

SpotTrackingService
Method	Description
bookSpot(OfficeHourSession, User)	DB::transaction: check !is_full · increment current_occupancy · if first student: set first_booker_id · if second student: set service_locked = true · if occupancy = max_spots: set is_full = true · fire OfficeHourSpotBooked event
releaseSpot(OfficeHourSession)	Decrement occupancy · set is_full = false if was full · broadcast spot update
canChooseService(OfficeHourSession, User)	Returns true if: user is first_booker + service_locked = false + now() < service_choice_cutoff_at

FeedbackEnforcementService
Method	Description
checkAndRestrictOverdue()	Queries bookings where feedback_due_at < now() AND (student_feedback_done = false OR mentor_feedback_done = false). Flags BOTH students AND mentors. Called every 30min by CRON.
isUserRestricted(User)	Returns true if user (student OR mentor) has any overdue feedback. Used as gate in BookingService (students blocked from booking), Mentor\BookingsController (mentors blocked from accepting new bookings), and chat opening.
clearRestriction(User)	Called after feedback submitted. Clears flag if no other pending feedback.

MentorRatingService
Method	Description
recalculate(Mentor)	Queries feedback table: AVG(stars), COUNT(*), COUNT(recommend=true)/total. Runs frequency analysis on comment text for top_tag. Updates mentor_ratings row. Also syncs avg_rating + total_sessions to mentors table.

StripePaymentService
Method	Description
createCheckoutSession(User, int $credits)	Creates Stripe Checkout session. Returns checkout URL.
createSubscription(User, string $program)	Creates Stripe recurring subscription. Stores stripe_subscription_id in office_hours_subscriptions.
handleWebhook(Request)	Verifies signature. Checks stripe_webhooks for event_id (idempotency). Dispatches to appropriate handler based on event_type.
processPaymentCompleted(array $event)	Calls CreditService::purchase(). Marks webhook processed.
processSubscriptionCycle(array $event)	Calls CreditService::grant() for 5 credits. Updates subscription period dates. Marks webhook processed.

StripeConnectService
Method	Description
createOnboardingLink(Mentor)	Creates Stripe Connect Express account. Returns hosted onboarding URL. Stores stripe_account_id on mentor.
handleAccountUpdated(array $event)	Webhook handler. If charges_enabled = true: set payouts_enabled = true + stripe_onboarding_complete = true.
createPayout(Mentor, decimal $amount)	Creates Stripe Transfer to mentor stripe_account_id. Inserts MentorPayout row.

MeetingLinkService
Method	Description
generate(Booking)	Calls Zoom API (or Google Meet API) to create a unique meeting URL. Stores in bookings.meeting_link. Returns URL.

AdminLogService
Method	Description
log(User $admin, string $action, string $table, int $id, array $before, array $after, ?string $notes)	Inserts immutable row to admin_logs. Called inside every Manual Action station handler. Never throws — wrapped in try/catch.

SupportTicketService
Method	Description
create(User, array $data)	Sanitizes message. Generates ticket_ref (SUP-xxxxx). Inserts ticket. Dispatches queued jobs: SendUserConfirmationEmail + SendAdminAlertEmail.
reply(SupportTicket, User $admin, string $reply)	Updates admin_reply + status + replied_at. Logs to admin_logs. Dispatches user notification email.

ImageUploadService
Method	Description
upload(UploadedFile $file, Mentor, array $cropParams)	Processes crop (zoom/x/y) server-side using Intervention Image. Stores result in Laravel Storage (S3 or local). Updates mentor.avatar_url + crop columns. Deletes old image file.
 
6. Events & Listeners

Event	Listeners	Notes
BookingConfirmed	GenerateMeetingLink, DeductCredits, SendConfirmationEmail	Fired after booking row created and credit check passed
BookingCancelled	SetCancellationStatus, NotifyParties	Status → cancelled_pending_refund
SessionCompleted	SetFeedbackDueAt, BroadcastFeedbackPrompt	Sets 24hr countdown on both student and mentor
FeedbackSubmitted	RecalculateMentorRating, MarkFeedbackDone	Recalculates mentor_ratings row + clears user restriction
FeedbackModerated	RecalculateMentorRating	Fired when admin amends feedback in Station 3 — recalculates mentor_ratings immediately
StripeWebhookReceived	ProcessPayment, GrantSubscriptionCredits, UpdatePayoutStatus	Idempotency checked first via stripe_webhooks.event_id
MentorApproved	SwapRoleStudentToMentor, SendApprovalEmail	removeRole(student) + assignRole(mentor) in DB transaction
ChatMessageSent	BroadcastViaReverb	Laravel Reverb WebSocket — instant delivery
OfficeHourSpotBooked	UpdateOccupancy, LockServiceIfSecondStudent, BroadcastSpotUpdate	Real-time spot count pushed to all connected clients
 
7. CRON Jobs — Scheduled Artisan Commands

Command	Schedule	Service Called	Purpose
office-hours:rotate-service	Weekly Mon 00:00	OfficeHourRotationService	Advances current_service_id to next in rotation
office-hours:generate-sessions	Weekly Mon 00:05	OfficeHourRotationService	Creates office_hour_sessions rows for the coming week
bookings:mark-completed	Every 15 min	BookingService	Marks past sessions completed · sets feedback_due_at
bookings:open-chat-windows	Hourly	BookingService	Enables chat 48hrs before session; closes 24hrs after session end
feedback:enforce-pending	Every 30 min	FeedbackEnforcementService	Flags users with overdue feedback · restricts new bookings
mentors:recalculate-ratings	Daily 02:00	MentorRatingService	Syncs mentor_ratings aggregates from feedback table

IMPORTANT: Also recalculate on these events:
- FeedbackSubmitted event → RecalculateMentorRating listener (immediate, not waiting for CRON)
- FeedbackModerated event (admin amend in Station 3) → RecalculateMentorRating listener (immediate)
stripe:retry-webhooks	Every 5 min	StripePaymentService	Retries unprocessed stripe_webhooks rows
 
8. API Route Structure
Base prefix: /api/v1/ — All authenticated routes use Laravel Sanctum (auth:sanctum). Role middleware from Spatie.

Public routes (no auth)
Method	Endpoint	Controller	Notes
POST	/auth/register	Auth\RegisterController@register	
POST	/auth/login	Auth\LoginController@login	
POST	/auth/logout	Auth\LoginController@logout	Sanctum token revoke
GET	/auth/google/redirect	Auth\SocialController@redirect	
GET	/auth/google/callback	Auth\SocialController@callback	
POST	/auth/password/forgot	Auth\PasswordController@forgot	
POST	/auth/password/reset	Auth\PasswordController@reset	
POST	/webhooks/stripe	Admin\ManualActionsController@stripeWebhook	Signature verified — no Sanctum

Student routes (auth:sanctum + role:student|mentor)
Method	Endpoint	Controller@Method	Notes
GET	/dashboard	Student\DashboardController@index	Featured mentors + credit balance
GET	/mentors	Student\MentorSearchController@index	Search + filter
GET	/mentors/{id}	Student\MentorSearchController@show	Full profile + feedback snippet
GET	/institutions	Student\InstitutionsController@index	Tier + program filter
GET	/institutions/{id}/programs	Student\InstitutionsController@programs	
GET	/institutions/{id}/mentors	Student\InstitutionsController@mentors	
GET	/office-hours	Student\OfficeHoursController@index	Real-time spots
GET	/office-hours/{id}	Student\OfficeHoursController@show	
POST	/bookings	Student\BookingController@store	Credit check → atomic
GET	/bookings/{id}	Student\BookingController@show	Calendar + chat + meeting link
PATCH	/bookings/{id}/cancel	Student\BookingController@cancel	→ cancelled_pending_refund
GET	/feedback	Student\FeedbackController@index	Public filtered reviews
POST	/feedback	Student\FeedbackController@store	Policy: completed booking only
GET	/credits/balance	Student\CreditsController@balance	Real-time balance
POST	/credits/purchase	Student\CreditsController@purchase	Stripe checkout session
POST	/credits/subscribe	Student\CreditsController@subscribe	OH subscription
GET	/chat/{booking}	Student\ChatController@index	Gate: 24–48hr window
POST	/chat/{booking}	Student\ChatController@send	Reverb broadcast
GET	/support	Student\SupportController@index	Own tickets only
POST	/support	Student\SupportController@store	Rate limited: 5/hour
GET	/settings	Student\SettingsController@show	
PATCH	/settings	Student\SettingsController@update	

Mentor routes (auth:sanctum + role:mentor)
Method	Endpoint	Controller@Method	Notes
GET	/mentor/profile	Mentor\ProfileController@show	
PATCH	/mentor/profile	Mentor\ProfileController@update	.edu validation if graduate
POST	/mentor/profile/avatar	Mentor\ProfileController@uploadAvatar	Crop + store + delete old
POST	/mentor/stripe/onboard	Mentor\StripeController@onboard	Connect Express link
GET/POST	/mentor/office-hours	Mentor\OfficeHoursController	CRUD schedules
GET	/mentor/bookings	Mentor\BookingsController@index	Own sessions + calendar
GET	/mentor/notes	Mentor\NotesController@index	Policy: own notes only
POST	/mentor/notes	Mentor\NotesController@store	5-question structured form
GET	/mentor/notes/{id}	Mentor\NotesController@show	Full note (Policy check)
PATCH	/mentor/notes/{id}	Mentor\NotesController@update	Policy check
GET	/mentor/feedback	Mentor\FeedbackController@index	Received reviews
POST	/mentor/feedback	Mentor\FeedbackController@store	Post-session form on student
PATCH	/mentor/feedback/{id}/reply	Mentor\FeedbackController@reply	Public mentor reply

Admin routes (auth:sanctum + role:admin)
Method	Endpoint	Controller@Method	Notes
GET	/admin/overview	Admin\OverviewController@index	KPIs + charts + leaderboards
GET	/admin/users	Admin\UserManagementController@index	Filter + paginate
PATCH	/admin/users/{id}	Admin\UserManagementController@update	Activate/block/role change
DELETE	/admin/users/{id}	Admin\UserManagementController@destroy	Soft delete
GET	/admin/users/export	Admin\UserManagementController@export	CSV via Pandas
GET	/admin/mentors	Admin\MentorManagementController@index	Performance table
PATCH	/admin/mentors/{id}/approve	Admin\MentorManagementController@approve	Role swap student→mentor
PATCH	/admin/mentors/{id}/reject	Admin\MentorManagementController@reject	
PATCH	/admin/mentors/{id}/pause	Admin\MentorManagementController@pause	
GET	/admin/services	Admin\ServicesController@index	
POST/PATCH/DELETE	/admin/services/{id}	Admin\ServicesController	Global price sync
GET	/admin/revenue	Admin\RevenueController@index	Financial overview
GET	/admin/rankings	Admin\RankingsController@index	
POST	/admin/manual/amend-mentor	Admin\ManualActionsController@amendMentor	Station 1 + logs
POST	/admin/manual/refund-credits	Admin\ManualActionsController@refundCredits	Station 2 + logs
POST	/admin/manual/moderate-feedback	Admin\ManualActionsController@moderateFeedback	Station 3 + logs
POST	/admin/manual/institutions	Admin\ManualActionsController@manageInstitution	Station 4 + logs
POST	/admin/manual/programs	Admin\ManualActionsController@manageProgram	Station 5 + logs
POST	/admin/manual/pricing	Admin\ManualActionsController@updatePricing	Station 6 + logs
GET	/admin/tickets	Admin\SupportTicketsController@index	All tickets
POST	/admin/tickets/{id}/reply	Admin\SupportTicketsController@reply	
PATCH	/admin/tickets/{id}/status	Admin\SupportTicketsController@updateStatus	
 
9. Spatie RBAC — Roles & Permissions

Roles
Role	Description	Assigned When
student	Default role. All registered users start here.	On registration (Auth\RegisterController)
mentor	Replaces student role after admin approval.	Admin calls MentorManagementController@approve → MentorApproved event → SwapRoleStudentToMentor listener
admin	Full platform access. Only existing admins can create new admins.	Manually seeded or granted by another admin

All Permissions
Permission	student	mentor	admin
user.view-any			YES
user.update			YES
user.delete			YES
user.export			YES
mentor.apply	YES		YES
mentor.approve			YES
mentor.manage-own		YES	YES
mentor.view-any			YES
mentor.reply-feedback		YES	YES
institution.manage			YES
office-hours.manage-own		YES	YES
office-hours.book	YES		YES
booking.create	YES		YES
booking.cancel-own	YES	YES	YES
booking.view-own	YES	YES	YES
booking.view-any			YES
feedback.create	YES		YES
feedback.view-any			YES
feedback.moderate			YES
notes.create		YES	YES
notes.view-own		YES	YES
notes.edit-own		YES	YES
notes.delete			YES
ticket.create	YES	YES	YES
ticket.view-own	YES	YES	YES
ticket.manage			YES
credits.purchase	YES		YES
credits.subscribe	YES		YES
credits.manual-adjust			YES
payout.manage-own		YES	YES
services.manage			YES
analytics.view			YES
logs.view			YES
 
10. Build Order — Milestone Checklist

Milestone 1 — Admin & Core Foundation
Deliverable: Working auth system, admin dashboard, institutions module.

#	Task	Files
1	Install Laravel 11, Sanctum, Spatie, Reverb	composer.json
2	Run all 24 migrations + Spatie publish	database/migrations/
3	Run seeders (RolesAndPermissions + ServicesConfig)	database/seeders/
4	User, Mentor, UserSetting, UserCredit models	app/Models/
5	Admin\AuthController — login/logout	app/Http/Controllers/Admin/
6	Admin\UserManagementController — CRUD + export	
7	Admin\MentorManagementController — approve/reject/pause	
8	Admin\OverviewController — KPIs + charts	
9	Admin\RevenueController + RankingsController	
10	Admin\ServicesController — CRUD pricing	
11	University, UniversityProgram models + Admin\InstitutionsController	
12	Admin\SupportTicketsController	
13	Admin\ManualActionsController — all 6 stations + AdminLogService	
14	AdminLogService (logs every manual action)	
15	All admin routes in routes/api.php	

Milestone 2 — Mentor Side
Deliverable: Mentor dashboard, office hours scheduling, secure notes system.

#	Task	Files
1	Mentor, MentorService, MentorRating models	app/Models/
2	MentorSettingsPolicy + registration in AuthServiceProvider	app/Policies/
3	Mentor\ProfileController + ImageUploadService	app/Services/ImageUploadService.php
4	StripeConnectService + Mentor\StripeController	app/Services/StripeConnectService.php
5	OfficeHourSchedule, OfficeHourSession models	
6	Mentor\OfficeHoursController	
7	OfficeHourRotationService + 2 Artisan commands	app/Console/Commands/
8	SpotTrackingService	
9	MentorNote model + MentorNotePolicy	
10	Mentor\NotesController (5-question form)	
11	Feedback, MentorFeedback models + FeedbackPolicy	
12	Mentor\FeedbackController + MentorRatingService	
13	Chat model + Mentor\ChatController + Reverb channel	
14	Mentor\BookingsController	
15	All mentor routes in routes/api.php	

Milestone 3 — Student Side + Full Integration
Deliverable: Full student flow, real-time features, payment integration.

#	Task	Files
1	Auth controllers (Register, Login, Social, Password)	app/Http/Controllers/Auth/
2	Student\DashboardController	
3	Student\MentorSearchController — search + filter + credit check	
4	Student\InstitutionsController — tier/program filter → mentors	
5	Student\OfficeHoursController — real-time spots	
6	BookingService (credit check + atomic create + meeting link)	app/Services/BookingService.php
7	Student\BookingController + CalendarController	
8	Student\ChatController + Reverb broadcast	
9	Student\FeedbackController	
10	CreditService + StripePaymentService	app/Services/
11	Student\CreditsController + OH subscription flow	
12	OfficeHoursSubscription model + webhook handler	
13	Student\SupportController + SupportTicketService + dual emails	
14	Student\SettingsController	
15	FeedbackEnforcementService + enforce-pending CRON	
16	All Events + Listeners registered in EventServiceProvider	app/Providers/EventServiceProvider.php
17	All CRON commands registered in Console\Kernel	app/Console/Kernel.php
18	All student routes in routes/api.php	
19	Rate limiting on support tickets (5/hour)	app/Http/Kernel.php
20	End-to-end testing: booking flow, OH rotation, feedback gate	tests/Feature/
 
11. Key Business Rules

Credit & Booking Rules
Rule	Enforced In
Credit balance must be >= cost before booking is created	BookingService::createBooking
Credit cost determined by meeting_size (use services_config.credit_cost_1on1 / credit_cost_1on3 / credit_cost_1on5)	BookingService::createBooking
Credit deduction and booking creation are a single DB::transaction	BookingService + CreditService
Cancellation sets status = cancelled_pending_refund — no auto-refund	BookingService::cancelBooking
Refund only processed by admin via Station 2 + manual CreditService::refund call	Admin\ManualActionsController
Group bookings: one student pays full price (is_group_payer=true), others have booking but don't pay	BookingService + Student\BookingController
Group booking cancellation: ONLY group_payer can cancel the entire group	BookingPolicy::cancel — throw exception if non-payer tries to cancel
No_show status: set by admin after session time passes without completion (manual via Station 1 or auto CRON)	Admin\ManualActionsController or cron:mark-completed
No_show blocks feedback submission (student cannot submit feedback on no_show booking)	FeedbackPolicy::create validation

Office Hours Rules
Rule	Enforced In
Office hours group booking: First student pays 1 credit for entire session (shared pool). Each student has own booking record. When 2nd student books, service locks.	BookingService::createBooking + SpotTrackingService
First student can choose any eligible service if booked >= 24hrs before session	SpotTrackingService::canChooseService
Service locks (service_locked = true) when second student books	SpotTrackingService::bookSpot
Session marked is_full = true when current_occupancy = max_spots	SpotTrackingService::bookSpot
Service rotates weekly via CRON — mentor cannot manually skip rotation	OfficeHourRotationService::rotateService

Feedback Gate Rules
Rule	Enforced In
Student feedback can only be submitted after booking.status = completed	FeedbackPolicy::create
feedback_due_at = session end time + 24 hours	SessionCompleted event listener
Student with overdue feedback cannot create new bookings	FeedbackEnforcementService::isUserRestricted (checked in BookingService)
Mentor with overdue mentor_feedback cannot accept new sessions	FeedbackEnforcementService::isUserRestricted (checked in Mentor\BookingsController@index gate)
Student with overdue student_feedback cannot create new bookings	FeedbackEnforcementService::isUserRestricted (checked in Student\BookingController@store)

Security Rules
Rule	Enforced In
Mentor notes: mentor can only view/edit notes where mentor_id = own mentor id	MentorNotePolicy (all methods)
.edu email required when mentor_type = graduate	MentorSettingsRequest validation
Stripe webhook signature verified before processing	StripePaymentService::handleWebhook
Stripe webhook event_id unique — prevents double credit grants	stripe_webhooks table UNIQUE constraint
Support tickets rate limited to 5 per hour per user	ThrottleRequests middleware on /support POST
All admin manual actions logged to admin_logs with before/after state	AdminLogService::log (called in every station)
Chat opens at session_at - 48 hours; closes at session_at + 24 hours (48 + 24 = 72 hour window total)	BookingService::openChatWindow gate

