# Milestone 3 - Admin Analytics, Payouts, and Production Hardening

## 1. Milestone Goal

Complete enterprise-grade operations and scale readiness:

- Full admin analytics and rankings
- Mentor payout lifecycle
- Final manual action stations
- Data quality and audit hardening
- Performance, reliability, and testing completion

Milestone 3 closes all remaining scope for production launch.

## 2. Architecture Rules (Final)

- Keep all classes inside their owning modules.
- No hidden shared logic in root app directory.
- Financial and admin mutations must be transaction-safe and auditable.
- Every endpoint must have request validation, policy checks, and tests.
- Feature completion gate: after implementing or changing any feature, write/update its automated test case, run the relevant tests, and only mark the feature complete when tests pass.
- Test file convention: place tests in the related file under tests/Feature (example: Auth module -> AuthTest). If a related test file does not exist, create a new feature test file with the best-fitting module/feature name.
- Reuse existing Blade files in modules. Do not create new Blade files.
- Follow Laravel 12 official docs for middleware, providers, queues, events, scheduling, and testing.
- Data access rule: use 80% Eloquent and 20% DB queries only for heavy/complex query paths.

### Laravel 12 alignment requirements

- Official reference source: https://laravel.com/docs/12.x
- Configure middleware aliases and CSRF exceptions in bootstrap/app.php.
- Register module service providers in bootstrap/providers.php.
- Keep module routes in each module's routes/web.php and keep HTTP behavior web-first.
- Keep scheduling in module providers or routes/console.php, without relying on legacy Kernel-only flow.
- Keep Eloquent as the default for domain operations; use DB builder/raw SQL only for performance-critical analytics, reconciliation, and payout/reporting workloads.
- After implementing each feature, cross-check the code against relevant Laravel 12 docs sections and resolve any mismatch before marking complete.

## 3. Modules and Features in Scope

### Payments

- Mentor payout records and payout execution
- Revenue breakdown APIs
- Webhook retry and reconciliation jobs

### Admin Operations (Auth, Payments, Feedback, Institutions)

- User and mentor management screens
- Rankings and KPI dashboards
- Full manual action stations with strict audit trails
- Service pricing governance

### Discovery and Feedback quality improvements

- Cached mentor ranking data
- Consistent rating source usage from mentor_ratings table

### Support

- Escalation and SLA-oriented ticket states

## 4. Database Schema for Milestone 3

### New or finalized tables

1. mentor_payouts (if deferred from M1)

### Existing table finalization

- mentor_ratings as single source of truth for aggregates
- admin_logs immutable and required for all admin writes
- credit_transactions immutable financial ledger
- support_tickets add escalation and resolution timestamps if missing

### Relationship map

- mentors 1-many mentor_payouts
- mentor_payouts belongs to mentor
- payouts linked to source transactions where applicable (optional nullable reference)
- admin_logs belongs to admin user and references changed target entity

## 5. Models to Implement/Finalize

### Payments module

- MentorPayout model (final)

### Support module

- SupportTicket model update for SLA metadata

### Feedback module

- MentorRating model final read model for dashboards and discovery sorting

## 6. Controllers to Implement/Finalize

### Admin controllers

- Admin/UserManagementController
- Admin/MentorManagementController
- Admin/RevenueController
- Admin/RankingsController
- Admin/OverviewController (final KPI cards and charts)

### Payments controllers

- Mentor/EarningsController
- Admin/PayoutsController

### Support admin extension

- Admin/SupportTicketsController (escalation and SLA state transitions)

## 7. Services to Implement/Finalize

- AnalyticsService (Payments/Admin)
- MentorPayoutService (Payments)
- ReportingExportService (Admin)
- ReconciliationService (Payments)
- ServicePricingService (Payments)
- AuditEnforcementService (Auth)

Keep implementation practical and avoid unnecessary abstractions.

## 8. Traits and Required Classes

### Traits

- Modules/Auth/app/Traits/LogsAdminActions.php (mandatory on all admin mutation paths)
- Modules/Payments/app/Traits/HandlesMoneyFormatting.php (optional utility, keep minimal)

### Form Requests

- UpdateUserStatusRequest
- ApproveMentorRequest
- PauseMentorRequest
- AdjustCreditsRequest
- UpdatePricingRequest
- CreatePayoutRequest
- UpdateTicketStatusRequest

### Policies

- MentorPayoutPolicy
- AdminAnalyticsPolicy
- AdminUserManagementPolicy

### Middleware

- EnsureAdminAuditEnabled
- EnsureFinancialWriteIdempotency

## 9. Web Routes (Milestone 3 Additions)

All routes are module web routes in each module's `routes/web.php`.

### Admin user and mentor management web routes

- GET /admin/users
- PATCH /admin/users/{id}
- DELETE /admin/users/{id}
- GET /admin/users/export
- GET /admin/mentors
- PATCH /admin/mentors/{id}/approve
- PATCH /admin/mentors/{id}/reject
- PATCH /admin/mentors/{id}/pause

### Admin analytics and rankings web routes

- GET /admin/overview
- GET /admin/revenue
- GET /admin/rankings

### Pricing and manual actions web routes

- GET /admin/services
- POST /admin/services
- PATCH /admin/services/{id}/pricing
- POST /admin/manual/amend-mentor
- POST /admin/manual/refund-credits
- POST /admin/manual/moderate-feedback
- POST /admin/manual/manage-institution
- POST /admin/manual/manage-program
- POST /admin/manual/update-pricing

### Payout web routes

- GET /mentor/earnings
- GET /admin/payouts
- POST /admin/payouts

### Support escalation web routes

- PATCH /admin/tickets/{id}/status
- POST /admin/tickets/{id}/escalate

## 10. Blade Structure (UI Per Module)

Blade policy for Milestone 3:

- Use only existing files under Modules/\*/resources/views.
- Do not create new Blade files.
- Build analytics and admin expansions as sections/tabs/components inside existing files.

### Admin views

- Modules/Payments/resources/views/admin/dashboard.blade.php
- Modules/Payments/resources/views/admin/overview/index.blade.php
- Modules/Payments/resources/views/admin/services/index.blade.php
- Modules/Support/resources/views/admin/support/index.blade.php
- Modules/Support/resources/views/admin/support/show.blade.php

### Mentor payout views

- Modules/Payments/resources/views/mentor/earnings.blade.php

### Support admin views

- Modules/Support/resources/views/admin/support/index.blade.php
- Modules/Support/resources/views/admin/support/show.blade.php

## 11. Jobs, Events, and Background Tasks

### Jobs

- ProcessMentorPayoutBatchJob
- RecalculateMentorRatingsJob
- BuildDailyAnalyticsSnapshotJob
- ExportUsersCsvJob
- RetryFailedWebhookJob

### Events

- CreditsAdjustedByAdmin
- MentorApproved
- MentorPaused
- PayoutInitiated
- PayoutCompleted
- FeedbackModerated

### Listeners

- LogAdminFinancialActionListener
- NotifyMentorPayoutStatusListener
- RefreshLeaderboardCacheListener

### Scheduled tasks

- mentors:recalculate-ratings (daily 02:00)
- payouts:process-batch (daily)
- analytics:build-snapshot (nightly)
- stripe:reconcile (hourly)
- support:escalation-check (every 30 min)

## 12. Hardening and Quality Gates

### Performance

- Add indexes for high-traffic filters and admin reporting queries.
- Enforce eager loading in listing endpoints.
- Cache dashboard cards and rankings with short TTL.

### Security

- Validate Stripe signatures on all webhook endpoints.
- Enforce strict role and policy checks for every admin route.
- Ensure immutable ledger behavior for credit_transactions and admin_logs.

### Reliability

- Financial writes wrapped in DB transactions.
- Webhook processing idempotent by event_id.
- Queue retry policies configured per job criticality.

### Test matrix

- Feature tests for all admin manual stations.
- Concurrency tests for credit and booking race conditions.
- Webhook replay tests for idempotency.
- Policy tests for admin-only and ownership-based paths.

## 13. Step-by-Step Implementation Order

1. Finish remaining migrations and model finalization.
2. Implement admin and payouts services.
3. Implement remaining admin/mentor controllers and routes.
4. Update existing admin and mentor Blade pages (no new Blade files).
5. Add jobs, events, listeners, and scheduler tasks.
6. Apply indexing, caching, and query optimizations.
7. For each implemented feature, write/update tests in the related tests/Feature file and run them to green; if no related file exists, create a new one with the best-fitting name, then complete full regression and security test suite. If auth behavior is touched, update tests/Feature/AuthTest.php in the same change.
8. Run production readiness checklist and deployment dry run.
9. Run Laravel 12 docs compliance review (routing, middleware, auth, password reset, validation, queues/scheduling) and fix deviations.

## 14. Done Criteria

- All admin stations are functional with immutable audit logs.
- Mentor payouts can be initiated, tracked, and reconciled.
- Revenue and ranking dashboards are accurate and performant.
- Background jobs are stable and observable.
- No new Blade files were created.
- Platform passes end-to-end regression for student, mentor, and admin flows.
- Code behavior and conventions are cross-checked against https://laravel.com/docs/12.x and confirmed aligned.
- Every implemented feature has an automated test case and related tests are passing.
- When milestone scope changes auth behavior, tests/Feature/AuthTest.php is updated and passing.
- Feature test coverage is organized in related tests/Feature files, and new test files are created when a related file does not yet exist.
