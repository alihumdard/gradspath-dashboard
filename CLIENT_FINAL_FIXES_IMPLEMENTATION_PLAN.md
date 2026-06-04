# Grads Paths Final Fixes Before Launch - Implementation Plan

Prepared from the client document: `Grads Paths - Final Fixes Before Launch.md` / PDF  
Screenshot references extracted to: `/tmp/gradspath-fixes-images/image1.png` through `image25.png`  
Purpose: planning only. No product fixes are implemented in this document.

## Executive Summary

The client list contains 29 numbered launch fixes covering authentication, email verification, landing-page links, portal UI consistency, mentor cards, institutions, bookings, office hours, feedback, mentor notes, support tickets, refunds, Stripe payouts, notifications, settings, and admin controls.

The current codebase is a modular Laravel app with these relevant modules:

| Area | Current Code Location |
|---|---|
| Auth, login, signup, verification, reset password | `Modules/Auth`, `resources/views/emails` |
| Student and mentor dashboards, mentor search | `Modules/Discovery` |
| Institutions and university programs | `Modules/Institutions` |
| Bookings, chats, meeting access, Zoom/Google sync | `Modules/Bookings` |
| Feedback and mentor ratings | `Modules/Feedback` |
| Mentor notes | `Modules/MentorNotes` |
| Office hours | `Modules/OfficeHours` |
| Credits, Stripe checkout, refunds, payouts | `Modules/Payments` |
| Profile/settings/timezone/avatar | `Modules/Settings` |
| Support tickets/admin support | `Modules/Support` |

Important current findings:

- Most portal routes already require `auth`, `active`, and role middleware.
- Admin dashboard exists under `/admin` and `/admin/dashboard`, but the client wants a secret admin link and guaranteed admin user/reset workflow.
- Password reset views and routes exist, but the client says the workflow is not fully usable from the website/admin dashboard.
- Email verification currently uses Laravel signed verification links, not the requested 6-digit code flow.
- Student sidebar currently includes Mentor Notes and Settings, but the client says students must not have either.
- Mentor topbar does not currently mirror student topbar credits/store controls.
- Payments/refunds/payouts are partially implemented, including `BookingRefundService`, `MentorPayoutService`, Stripe webhooks, credit ledger, and tests.
- Current cancellation copy/window says 24 hours in places, while the client references a 12-hour automatic refund/cancellation rule.
- Office Hours payout of `$15` per attendee exists in service config and payout calculator.
- Dynamic website/portal content management is not generally implemented as a CMS/admin-editable content system.

## Proposed Delivery Phases

### Phase 1 - Access, Auth, Links, and Blocking Bugs

Fix all issues that can block users from entering or navigating the platform.

Scope:

- [x] Light mode default.
- [x] Auth protection and guest redirect behavior.
- Login/signup modal closing bug.
- [x] Signup UI restore.
- `.edu` validation by audience.
- 6-digit verification code flow.
- Admin secret link and admin user access.
- Reset password entry points for website and admin.
- [x] Landing page/footer/header broken links.
- Support landing redirects.

### Phase 2 - Portal UI Consistency

Make the student and mentor portals visually and behaviorally consistent.

Scope:

- Shared portal topbar.
- Shared mentor card component.
- Services offered open by default.
- Correct sidebar order and active outlines.
- Remove student Settings and Mentor Notes.
- Mentor Notes on Users view only for mentors.
- Initials/avatar rules.
- [x] Booking page back arrows and unnecessary arrows.

### Phase 3 - Dynamic Admin Controls

Add admin-managed content and ranking controls.

Scope:

- Website/portal copy and numbers.
- Logo belt.
- Results from Our Community.
- Program charts.
- Institution logos.
- Institution display overrides.
- Program tier/type configuration.
- Featured institutions automatic/manual modes.
- Mentors of the Week automatic/manual modes.
- Mentor rating admin override.

### Phase 4 - Bookings, Feedback, Office Hours, Refunds, Notifications

Complete the business logic that affects money, access, and mandatory post-session workflows.

Scope:

- Feedback lock/unlock rules.
- Mentor session notes lock/unlock rules.
- Current vs upcoming bookings 24/25 hour split.
- Multiple booking-specific chats.
- Chat/email notifications and sidebar counts.
- Cancel meeting UI and refund logic.
- Office Hours service choice logic.
- Office Hours mentor service eligibility.
- Stripe payout/reversal/refund verification.
- Admin notifications for support/refunds.

### Phase 5 - Testing and Launch QA

Run route, policy, UI, payment, and workflow tests before launch.

Scope:

- Feature tests for auth, verification, protected routes, admin access.
- Booking cancellation/refund tests.
- Office Hours service-choice tests.
- Feedback/mentor note enforcement tests.
- Support ticket notification tests.
- Browser QA for key student, mentor, admin, and guest flows.

## Client Questions and Implementation Answers

### 1. Default platform theme should be light mode - (Done)

**Client question:** When first going on the platform, default should be light mode, not dark.

**Answer:** Yes. Default theme will be light everywhere unless the user has explicitly chosen dark mode.

**Current finding:** The landing page starts with `<html class="scroll-smooth dark">` in `resources/views/landing_page/index.blade.php`, which forces dark mode on first visit. Portal JS generally defaults to light via `localStorage.getItem("theme") || "light"`.

**Implementation completed:**

- [x] Remove the default `dark` class from the landing page HTML.
- [x] Update the landing inline theme script so only `localStorage.theme === "dark"` applies dark mode.
- [x] Keep portal defaults as light.
- [x] Verify landing, student portal, mentor portal, admin login, and public pages.

### 2. No user or mentor can reach dashboard unless logged in - (Done)

**Client question:** No user of any kind or mentor can get to the dashboard unless logged in, including submit feedback.

**Answer:** Yes. All portal dashboards, feedback submit routes, bookings, support, payments, institutions, and settings must require login.

**Current finding:** Most routes already use `auth` and role middleware. Feedback index allows authenticated students or mentors; feedback submit is student-only. The website may still expose buttons/links that should redirect guests to login.

**Implementation completed:**

- [x] Audit all portal routes and confirm `auth`, `active`, role, and `mentor.approved` where needed.
- [x] For guest clicks like Submit Feedback, redirect to login with an intended URL.
- [x] Add feature tests for guest access to dashboards, feedback submit, bookings, support, and settings.

### 2a. Website and portal text/numbers/logos/sections/graphs must be dynamic

**Client question:** Admin must be able to change wording, numbers, logo belt, community results, programs/professional tracks, graphs, and any platform text.

**Answer:** This requires a content-management layer in the admin dashboard.

**Current finding:** Landing page content is mostly hard-coded in `resources/views/landing_page/index.blade.php` and `public/assets_landingPage/js/app.js`. There is no general CMS table for editable page copy, logo belt items, statistics, graphs, testimonials, or section labels.

**Implementation plan:**

- Add `site_content_sections`, `site_content_items`, or equivalent tables.
- Build admin screens for:
  - Landing page text blocks.
  - Portal text blocks.
  - Stats/numbers.
  - Logo belt upload/order.
  - Community feedback/testimonial cards.
  - Program/professional tracks.
  - Chart labels and values.
- Replace hard-coded landing data with database-backed view models.
- Cache public content and add admin cache invalidation after saves.

### 3. Signup popup must match the screenshot - (Done)

**Client question:** Signup popup was changed and needs to look like `image1`.

**Answer:** Restore the signup modal layout to match the reference.

**Current finding:** Signup is rendered through `resources/views/landing_page/index.blade.php`, styled by `public/assets_landingPage/css/style.css`, and controlled by `public/assets_landingPage/js/script.js` / `app.js`.

**Implementation completed:**

- [x] Use `image1.png` as the visual target.
- [x] Adjust modal width, spacing, selected role/student level controls, form order, button styles, and typography.
- [x] Preserve Laravel validation and old input behavior.
- [x] Add browser QA on desktop and mobile.

### 4. Only undergrads and grads require `.edu`; professionals can use any email

**Client question:** Undergrads and grads need `.edu`; professionals do not.

**Answer:** Yes. Email validation must depend on signup type.

**Current finding:** `RegisterRequest` validates email format and uniqueness only. It does not enforce `.edu`.

**Implementation plan:**

- Add conditional validation:
  - Student undergrad: email must end in `.edu`.
  - Graduate mentor: email must end in `.edu`.
  - Professional mentor: normal email is allowed.
- Add clear error messages.
- Mirror validation client-side for fast feedback, but keep server validation authoritative.
- Add feature tests for all three signup categories.

### 5 and 5a. Verification must use a 6-digit email code - (Done)

**Client question:** Users should receive a 6-digit code, email should match `image2`, verification screen should match `image3`.

**Answer:** Done. Signed-link verification was replaced with a 6-digit code flow.

**Current finding:** Implemented. The signed verification route `/email/verify/{id}/{hash}` was removed. Verification now uses `GET /email/verify` for the code screen and `POST /email/verify` for code submission.

**Implementation completed:**

- [x] Added `email_verification_codes` table with hashed code, expiry, attempts, and last sent timestamp.
- [x] On registration/resend, generate a 6-digit code and send it by email.
- [x] Built verification form with six code boxes matching `image3`.
- [x] Verifies code and enforces expiry, failed attempts, one-time use, and resend cooldown.
- [x] Removed signed-link verification support.
- [x] Updated email design to match `image2`.
- [x] Added tests for correct code, wrong code, expired code, resend throttling, and verified redirect.
- [x] Verified with `php artisan test tests/Feature/AuthTest.php` - 38 passed, 155 assertions.

### 6. Admin dashboard secret link, admin user, and reset password workflow - (Done)

**Client question:** Admin dashboard must be accessible through a secret link; admin user must be set up; reset password must work for website and admin dashboard.

**Answer:** Yes. Admin access must be reliable but protected.

**Current finding:** Implemented. Admin routes now use the configurable `ADMIN_PORTAL_PATH` secret path. Old `/admin` routes are hidden, admin reset password is separate from public user reset, and Horizon guests redirect through the secret admin login.

**Implementation completed:**

- [x] Added configurable secret admin path through `ADMIN_PORTAL_PATH`.
- [x] Moved admin routes from `/admin` to the configured secret path.
- [x] Kept admin routes protected by `auth`, `active`, and `role:admin`.
- [x] Hid old `/admin` and `/admin/dashboard` with 404 behavior.
- [x] Added admin-only forgot/reset password routes under the secret path.
- [x] Updated admin reset emails to use the secret admin reset URL.
- [x] Kept public user password reset unchanged.
- [x] Redirected Horizon guests to the secret admin login and back to `/horizon` after login.
- [x] Verified with `php artisan test tests/Feature/AuthTest.php` - 43 passed, 168 assertions.
- [x] Verified with `php artisan test tests/Feature/HorizonAccessTest.php` - 6 passed, 14 assertions.

### 7. Highlighting text in login/signup popups closes the popup - (Done)

**Client question:** Selecting/highlighting modal text should not close the popup.

**Answer:** Fix modal event handling so only intentional backdrop/close actions close modals.

**Current finding:** Implemented. The click handlers on the backdrop overlay that closed the modals were removed, so the modals can only be closed by clicking the close button (the cross). This ensures that text highlight or selection actions inside the modal will never cause the modal to close.

**Implementation completed:**

- [x] Disabled backdrop-click event listeners for the login and signup modals in `public/assets_landingPage/js/script.js`.
- [x] Ensured modals only close when the user clicks the explicit close buttons (`id="login-close"` or `id="signup-close"`).
- [x] Verified that selecting or highlighting text inside the modals does not close the popups.


### 8. Portal light/dark toggle should match website style - Done

**Client question:** Portal theme toggle image `image4` should look like website image `image5`.

**Answer:** Done. The portal theme toggle now uses the same icon-only visual style as the website toggle.

**Current finding:** Implemented through the shared portal header in `resources/views/layouts/portal.blade.php` and `public/assets/css/portal-header.css`. The landing page default light-mode behavior from item 1 is also implemented.

**Implementation completed:**

- [x] Created a shared icon-only portal theme toggle visual style.
- [x] Applied the shared style to the portal topbar.
- [x] Ensured default light mode from item 1.
- [x] Added portal toggle normalization so older page-specific JS text labels are replaced by the icon-only toggle.

### 9. "Why Us" Learn More button missing/broken

**Client question:** Learn more about our approach button is missing or errors.

**Answer:** Link it to a valid `why-us` page/section.

**Current finding:** Static `resources/views/landing_page/why-us.html` exists, but route coverage needs to be added or corrected.

**Implementation plan:**

- Add named route for `/why-us`.
- Update button href to route or valid section anchor.
- Confirm no 404.

### 10. "How it works" broken link - (Done)

**Client question:** The How It Works link errors instead of scrolling/navigating to the section.

**Answer:** Point it to a real section ID or route.

**Current finding:** Static `how-it-works.html` exists. Landing section IDs and footer/header links need audit.

**Implementation completed:**

- [x] Add `id="how-it-works"` on the landing section or route `/how-it-works`.
- [x] Update header, hero, and footer links consistently.
- [x] Test from landing page, logged-out state, and logged-in redirect state.

### 11. Footer/home/menu/logo links show 404 - (Done)

**Client question:** Footer links Home, Our Services, Meeting Types, Sign up, Log in, Contact Us, Privacy Policy, Terms of Service, navbar Home, and logo must work.

**Answer:** Fix all public navigation links and modal trigger links.

**Current finding:** Routes exist for `/`, `/home`, `/terms`, `/privacy`, `/support`, but not necessarily every footer label. Signup/login routes exist at `/register` and `/login`.

**Implementation completed:**

- [x] Map links:
  - [x] Home and logo: `route('public.home')` or `/`.
  - [x] Our Services: landing section or `/services`.
  - [x] Meeting Types: landing section.
  - [x] Sign up: `/register` or modal opener.
  - [x] Log in: `/login` or modal opener.
  - [x] Contact Us: `/support` for guests, portal support for logged-in users.
  - [x] Privacy Policy: `/privacy`.
  - [x] Terms of Service: `/terms`.
- [x] Add missing public routes/sections if required.
- [x] Add a route smoke test for all footer links.

### 12. Universal mentor card UI and Services Offered open by default

**Client question:** All mentor cards must use the same UI as `image6`; Services Offered should be open automatically but closable.

**Answer:** Create/reuse one mentor-card partial/component across dashboard, find mentors, institution detail, and mentor profile cards.

**Current finding:** Mentor card markup is duplicated in dashboard, mentor profile, institutions JS, office hours JS, settings, and discovery pages. Services accordion exists and is closed/toggled via `public/assets/js/discovery.js`.

**Implementation plan:**

- Create a Blade partial/component for mentor cards.
- Use the same structure in:
  - Student dashboard.
  - Mentor dashboard.
  - Find Mentors.
  - Institutions mentor tab.
  - Mentor profile previews.
  - Office Hours cards where applicable.
- Set services accordion open by default with user-toggle close behavior.
- Refactor JS-generated cards to use the same visual contract.

### 13. Explore by University top six, full names, logos - Done

**Client question:** Only top 6 most used colleges show before View All; names should be full names; logos should appear as in `image7`.

**Answer:** Done. The dashboard now uses real booking ranking data and displays full institution names with logos or initials fallback.

**Current finding:** Implemented for student and mentor dashboards through `Modules/Discovery/app/Services/TopInstitutionService.php`.

**Implementation completed:**

- [x] Ranked institutions by confirmed/completed bookings with mentors at that institution.
- [x] Selected the top 6 by default.
- [x] Displayed `name` as the full institution name instead of `display_name`.
- [x] Included `logo_url` with initials fallback.
- [x] Kept View All links to `/student/institutions` and `/mentor/institutions`.
- [x] Linked institution cards to the relevant institution detail pages.
- [x] Verified with `php artisan test Modules/Discovery/tests/Feature/DashboardSplitTest.php` - 15 passed, 49 assertions.

### 13b. Admin can choose institutions or default daily auto-update by meetings

**Client question:** Admin needs option to display chosen institutions; default should auto-update daily by meeting count.

**Answer:** Add featured institution configuration with automatic and manual modes.

**Current finding:** No dedicated featured institution/admin ranking config exists.

**Implementation plan:**

- Add table: `featured_institution_settings` or fields on universities:
  - `featured_mode`: automatic/manual.
  - manual order.
  - manual visibility.
  - last recalculated date.
- Add scheduled command to recalculate daily using booking counts.
- Add admin UI to override visible institutions.
- Make dashboard consume this service instead of direct query.

### 14. Mentors of the Week top rated 6 or admin-chosen - Done

**Client question:** Mentors of the Week must be top-rated 6 automatically weekly, with admin manual swap option.

**Answer:** Done. Mentors of the Week now uses admin-selected `is_featured` mentors first, fills remaining slots from top-rated active mentors, and has a weekly command to refresh the automatic set.

**Implemented:**

- [x] Added `FeaturedMentorService` to rank active mentors by `mentor_ratings.avg_stars`, then review/session counts.
- [x] Updated `MentorDiscoveryService::featured()` to show admin-featured mentors first and auto-fill to 6 by rating.
- [x] Added `discovery:refresh-featured-mentors` and scheduled it weekly.
- [x] Added admin manual-action UI and route for choosing up to 6 featured mentors.
- [x] Kept `mentors.is_featured` as the featured/manual selection flag.

### 15. Admin Institutions / Explore by University program tier/type and logos

**Client question:** Admin must manage Institutions, Program Tier, Program Type, and logos as in `image8`.

**Answer:** Extend admin institutions/program UI.

**Current finding:** `UniversityProgram` already has `program_type` and `tier`. `University` already has `logo_url`. Admin routes/controllers exist for institutions and programs.

**Implementation plan:**

- Add logo upload/display to admin institution forms.
- Add/edit full institution name and display name separately.
- Ensure program form supports:
  - Program Type.
  - Program Tier.
  - Active/inactive.
  - Description.
- Add validation and preview.

### 16. Institution detail mentor cards and dark button color

**Client question:** Institution mentor cards must match item 12; dark button should use lighter scheme like `image10`.

**Answer:** Reuse universal mentor cards and adjust button tokens.

**Current finding:** Institutions pages use `public/assets/js/institutions.js` to render mentor cards. Button styling is in `public/assets/css/institutions.css`.

**Implementation plan:**

- Update institution detail mentor rendering to match shared card design.
- Replace the dark button class with the lighter purple/white scheme from reference.
- Verify both Programs and Mentors tabs.

### 17. Multiple tiers behavior and four program filter options - (Done)

**Client question:** If All is selected and a college has programs in multiple tiers, show "Multiple Tiers"; if Top 25 or Regional is selected, show that selected tier. Only options: All, Elite Programs, Top 25 Programs, Regional Programs.

**Answer:** Done. Institution tier filters and display labels now match the requested four-option tier model.

**Current finding:** Implemented. `InstitutionService` maps all tiers to the exact client labels, and the institutions UI displays "Multiple Tiers" when an institution has more than one tier under the All filter.

**Implementation completed:**

- [x] Kept tier filter options as All, Elite Programs, Top 25 Programs, and Regional Programs.
- [x] Normalized `top` labels to "Top 25 Programs" everywhere in institution browse data.
- [x] Updated multi-tier institution cards to show "Multiple Tiers" with exact capitalization.
- [x] Confirmed selected tier filtering continues to show the selected tier label.
- [x] Added coverage for exact client tier labels.
- [x] Verified with `php artisan test tests/Feature/Admin/UniversityProgramsCrudTest.php` - 15 passed, 48 assertions.

### 18. Find Mentors UI matches item 12

**Client question:** Find Mentors looks great, but mentor cards need to match item 12 and Services Offered open by default.

**Answer:** Reuse universal mentor card component.

**Current finding:** Find Mentors uses discovery views and `public/assets/js/discovery.js`.

**Implementation plan:**

- Apply shared mentor card markup/style.
- Keep existing filters/search.
- Default services accordion to open.
- Ensure student and mentor portals both behave the same.

### 19. Book with Mentor back arrows and type-of-session arrow - (Done)

**Client question:** Back arrows do not work in Book with Mentor; remove unnecessary type-of-session arrow on the left.

**Answer:** Fix navigation and remove unused UI affordance.

**Current finding:** Booking create view has a `Back to Dashboard` link and JS-driven steps. Back behavior is in `public/assets/js/booking-create.js`.

**Implementation completed:**

- [x] Audit all booking step back buttons.
- [x] Ensure each step returns to previous step or correct source page.
- [x] Remove the left arrow from type-of-session if decorative/unneeded.
- [x] Add browser QA for booking from dashboard, Find Mentors, and institution detail.

### 19a. Recent Feedback in mentor notes comes from latest Quick Feedback

**Client question:** Recent Feedback must automatically come from the latest user form Quick Feedback and update often.

**Answer:** Feed mentor note/recent feedback display from latest visible feedback comment.

**Current finding:** Feedback stores `comment`, and discovery featured cards already use `latestVisibleFeedback`. Mentor Notes currently show only mentor note fields, not latest student quick feedback.

**Implementation plan:**

- Add relationship/query for latest feedback per mentor/booking/student.
- Surface latest `Feedback.comment` as "Recent Feedback".
- Ensure it is tied to the correct booking and mentor.
- Update after feedback submission via existing rating listener/event.

### 20. Office Hours meeting choice, mentor payout, services, rating order

**Client question:** Test Office Hours service choice; mentors get `$15` per person; all mentors on page; mentors only show services they can offer; no rotation for mentors with one service; only Tutoring, Program Insights, Interview Prep; order by highest rating; admin can override rating.

**Answer:** Office Hours needs final business-rule verification and admin rating override.

**Current finding:** Office Hours supports service choice and 12-hour cutoff. `$15` per attendee exists in `ServiceConfigSeeder` and `MentorPayoutCalculator`. Current office-hours/service code may still include other services in places. Mentor rating override is not clearly implemented.

**Implementation plan:**

- Restrict Office Hours eligible services to:
  - Tutoring.
  - Program Insights.
  - Interview Prep.
- For mentors with only one eligible service, lock that service; no rotation.
- For mentors with multiple eligible services, rotate only among eligible services.
- Sort Office Hours mentors by rating descending.
- Add admin rating override fields and ensure rankings use effective rating.
- Verify payout creation: one attendee equals `$15` mentor earning from platform account.
- Add tests for one-service mentor, multi-service mentor, service choice window, and payout ledger.

### 21. Mentor Notes on Users only visible to mentors; tab order/search/read more

**Client question:** Students must not see Mentor Notes at all. Rename to Mentor Notes on Users. Feedback tab should come before it. Add search bars and centered purple Read More.

**Answer:** Remove student access/UI and keep mentor-only notes view.

**Current finding:** Student routes and sidebar currently include `/student/notes`. Mentor sidebar already labels "Mentor Notes on Users", but Feedback currently comes after Mentor Notes in mentor sidebar. Mentor notes page title still says "Users Notes".

**Implementation plan:**

- Remove student Mentor Notes route/sidebar item or return 404/403.
- Keep mentor notes route under `role:mentor` only.
- Reorder mentor sidebar: Feedback before Mentor Notes on Users.
- Rename page title/header to "Mentor Notes on Users".
- Keep most recent forms first.
- Match search bars from `image15`.
- Ensure Read More button is purple and centered.
- Ensure card rows show user name and correct mentor names from notes.

### 22. Feedback stats live update

**Client question:** Feedback stats, including "across 10 complete sessions", must update as meetings are created/completed.

**Answer:** Stats should be computed from live feedback/completed booking data.

**Current finding:** Feedback summary currently counts visible feedback records, not necessarily completed sessions. It displays "Across X completed sessions" from `$feedbackSummary['completedSessions']`.

**Implementation plan:**

- Decide source of truth:
  - Average/recommend rate from visible feedback.
  - Completed sessions count from completed bookings or mentor rating `total_sessions`.
- Update `FeedbackController::summary()` to use completed session count, not just feedback count.
- Ensure rating aggregation updates after feedback and completion.
- Add tests for completed sessions with/without feedback.

### 23. Booked session cancel UI and automatic refunds

**Client question:** Replace Contact Support button with cancel meeting UI from images `image18`, `image19`, `image20`; add extra Cancel this meeting option and close X; refunds automatic if 12 hours before meeting; admin notifications; credit-pack refund rule.

**Answer:** Update cancellation UI and align refund policy/business rules.

**Current finding:** Student booking page has cancel modal and support modal. `BookingService` currently says self-service cancellation closes 24 hours before meeting. `BookingRefundService` supports automatic credit and Stripe refunds. `$200/5 credits` purchase exists as credit pack config.

**Implementation plan:**

- Update UI copy/buttons to match screenshots.
- Add close X to second modal.
- Change cancellation/refund policy from 24 hours to 12 hours if confirmed by product owner.
- On eligible cancellation:
  - Cancel booking.
  - Refund credits or Stripe charge automatically.
  - Reverse mentor transfer first if already transferred.
  - Create admin notification/log.
- Credit-pack rule:
  - Individual credits can be refunded one by one.
  - Full `$200` credit-pack refund allowed only if user still has 5 removable credits.
- Expose refund status in admin dashboard.
- Add tests for credit refund, Stripe refund, transfer reversal, late cancellation, insufficient credits for full pack refund.

### 23a. Feedback after meeting, mentor notes, chats, notifications, current/upcoming split

**Client question:** Feedback After Your Meeting locked until meeting ends; user has 24 hours; if overdue, block access until complete. Same rule for mentor notes with different form/location. Multiple chats tied to correct booking/mentor/service. Email notifications and sidebar booking notification counts. Current meetings within next 24 hours; upcoming 25+ hours.

**Answer:** Enforce a post-session completion gate and booking-scoped chat/notifications.

**Current finding:** Booking model has feedback fields and presenter. `BookingService` blocks new bookings if overdue feedback/mentor notes. Middleware exists but may not be globally applied. Chat is booking-scoped via `/bookings/{id}/chat` and `booking.{id}` channel. Current/upcoming currently uses presenter states live/upcoming, not 24/25-hour split.

**Implementation plan:**

- Ensure completed bookings set `feedback_unlocked_at` and `feedback_due_at` exactly.
- Student:
  - Feedback form locked until session ends.
  - 24-hour grace period.
  - After due time, block portal actions except completing required feedback.
- Mentor:
  - Mentor notes form locked until session ends.
  - 24-hour grace period.
  - After due time, block portal actions except completing required mentor notes.
- Apply middleware globally to relevant portal routes, not just booking creation.
- Keep chat tied to `booking_id`; ensure UI clearly separates multiple upcoming chats.
- Add unread notification counts near Bookings in sidebar.
- Send email notification for chat activity.
- Split booking lists:
  - Current: starts within next 24 hours or live.
  - Upcoming: starts 25 hours or more in the future.

### 24. Support tickets from website to portal and admin notifications

**Client question:** Website support should take logged-in users/mentors to portal Support tab; tickets must appear in admin dashboard; admin/user notifications on replies.

**Answer:** Public support CTA should route by auth/role; support ticket workflow needs notification completion.

**Current finding:** Support module exists for student, mentor, and admin tickets. Public `/support` page exists separately. Notification jobs exist for ticket creation/confirmation. Admin index exists.

**Implementation plan:**

- Update public support CTA:
  - Guest: login/register with intended support route.
  - Student: `/student/support`.
  - Mentor: `/mentor/support`.
  - Admin: admin support tickets.
- Ensure ticket creation dispatches admin notification.
- Ensure admin reply emails/notifies user.
- Add admin dashboard notification count/badge.
- Add tests for student ticket, mentor ticket, admin reply.

### 25. Active portal tab outline - (Done)

**Client question:** Current tab in portal sidebar should have an outline like `image23`.

**Answer:** Done. Active portal sidebar items now have a clear outline/ring state.

**Current finding:** Implemented through shared portal CSS so student and mentor portal sidebars get the same active outline treatment.

**Implementation completed:**

- [x] Updated shared active nav CSS in `public/assets/css/portal-header.css`.
- [x] Added an outline-style active ring plus inset border for current sidebar tabs.
- [x] Ensured active state remains visible in light and dark mode.
- [x] Existing student/mentor sidebars continue to apply active state through route/path matching.

### 26. Users no settings page, automatic timezone, initials for users, mentor faces

**Client question:** Students/users should not have settings page. Timezone auto-detect from computer; fallback EST US. Users show initials only; mentors show face/avatar. Fix image issues.

**Answer:** Remove student settings and enforce role-specific avatar behavior.

**Current finding:** Student settings route, controller, view, and sidebar link exist. Timezone auto-save endpoint exists and stores timezone only if empty. Student avatar upload exists. Sidebar currently shows student avatar if `avatar_url` exists.

**Implementation plan:**

- Remove student Settings sidebar item.
- Disable or remove student avatar upload flow.
- Student/user avatar display should always be initials from first/last name.
- Mentor avatar display should use mentor/user `avatar_url`; fallback initials only if missing.
- Add client-side timezone detection using `Intl.DateTimeFormat().resolvedOptions().timeZone`.
- Save timezone on first portal load if empty.
- Fallback should be Eastern Time, e.g. `America/New_York`, not app timezone.
- Verify uploaded mentor avatars are stored and served from `public/storage`.

### 27. Mentor portal topbar same as user portal - (Done)

**Client question:** Mentor topbar should match user portal, including credits/store/light-dark toggle, because mentors can book other mentors.

**Answer:** Done. Mentor portal now uses the same credits, Store, and light/dark toggle topbar controls as the student portal.

**Current finding:** Implemented. Both student and mentor portal layouts include the shared `credits-store-controls` partial and shared `credits-store-script`.

**Implementation completed:**

- [x] Added shared credits/store controls to mentor portal topbar.
- [x] Mentor portal uses `mentor.credits.balance` and `mentor.store` routes.
- [x] Mentor portal keeps the shared icon-only light/dark toggle from the base portal layout.
- [x] Mentor-specific page topbar content still renders after shared controls.

### 28. Mentor left tab ordering is good

**Client question:** Mentor left tabs look great; ordering is perfect.

**Answer:** Keep mentor tab set mostly as-is, except item 21 requires Feedback before Mentor Notes.

**Current finding:** Mentor sidebar currently has Mentor Notes before Feedback.

**Implementation plan:**

- Confirm with client whether item 21 overrides item 28.
- Recommended: follow item 21 because it gives the exact required ordering.
- Do not otherwise redesign mentor sidebar.

### 29. Mentor booked session and mandatory session notes

**Client question:** Mentor booked session needs same cancel/feedback logic from 23/23a; mentor session notes mandatory; notes should not sit in top-right corner; view should look like student portal with live/upcoming meetings.

**Answer:** Align mentor bookings page with student bookings page and enforce mentor notes.

**Current finding:** Mentor bookings page has a top-right notes trigger and cancellation/support modal. Mandatory mentor notes are partially enforced by `BookingService`, but portal-wide blocking needs confirmation.

**Implementation plan:**

- Redesign mentor bookings page to match student live/upcoming layout.
- Move session notes into the booking flow/card, not isolated top-right.
- Lock notes until meeting ends.
- Enforce 24-hour completion window.
- After overdue, block mentor portal actions except completing notes.
- Apply same cancel modal/refund policy as student side where applicable.

## Cross-Cutting Architecture Needed

### Shared Portal Components

Create shared partials/components for:

- Theme toggle.
- Credits/store topbar.
- Sidebar active item style.
- Mentor card.
- Avatar/initials display.
- Booking cancel modal.
- Booking/chat notification badge.

### Dynamic Content Admin

Add a content service that supports:

- Keyed content blocks.
- Rich text or safe Markdown where needed.
- Numeric stat blocks.
- Image/logo uploads.
- Ordered collections.
- Publish/unpublish status.
- Admin preview.

### Notifications

Needed notification types:

- Verification code email.
- Password reset email.
- Booking confirmation/reminders/cancellation.
- Chat message email.
- Support ticket created.
- Support ticket replied.
- Refund requires admin review.
- Refund completed.
- Overdue feedback/mentor notes reminder.

### Payment and Stripe Notes

Recommended Stripe approach:

- Continue using Checkout Sessions for user payments/credit purchases.
- Continue verifying Stripe webhook signatures.
- Continue using backend-only Stripe secret access.
- For Connect mentor payouts, prefer a consistent Connect account model and avoid mixing charge flows.
- Refund flow must reverse mentor transfer before refunding if funds were already transferred.

## Testing Checklist

### Auth and Access

- Guest cannot open student dashboard.
- Guest cannot open mentor dashboard.
- Guest cannot submit feedback.
- Admin cannot use user portal login.
- Non-admin cannot use admin login.
- Password reset works for user and admin.
- Verification code works and expires.

### UI and Links

- Landing defaults light.
- Landing header Home/logo links work.
- Footer links all return 200 or open intended modal.
- Login/signup text selection does not close modal.
- Signup modal matches screenshot.
- Portal theme toggle matches website.

### Discovery and Institutions

- Featured mentors sorted by rating unless manual override.
- Featured institutions auto-rank by booking count.
- Manual institution override takes priority.
- Institution logo displays.
- Tier filter labels and Multiple Tiers logic are correct.
- Mentor cards match across all pages.

### Bookings and Office Hours

- Booking back buttons work.
- Student can choose Office Hours service only when eligible.
- One-service mentor does not rotate Office Hours service.
- Office Hours eligible services restricted to three.
- Mentor payout records `$15` per Office Hours attendee.
- Current bookings show within 24 hours.
- Upcoming bookings show 25+ hours out.

### Feedback, Notes, Chat

- Student feedback locked before meeting end.
- Student feedback required within 24 hours.
- Overdue feedback blocks portal except completion.
- Mentor notes locked before meeting end.
- Mentor notes required within 24 hours.
- Overdue mentor notes blocks mentor portal except completion.
- Multiple booking chats stay separate.
- Chat email notifications send.
- Booking sidebar unread count updates.

### Support and Admin

- Public support sends logged-in student to student support.
- Public support sends logged-in mentor to mentor support.
- Guest support sends user to login/register with intended destination.
- Admin can see new tickets.
- User receives confirmation and admin reply notifications.

### Refunds and Payments

- Eligible booking cancellation refunds credits.
- Eligible Stripe booking cancellation refunds payment.
- Transferred mentor payout reverses before refund.
- Failed refund creates admin review notification.
- Full `$200` credit-pack refund only allowed if 5 credits can be removed.
- Individual credit refunds work one by one.

## Open Decisions to Confirm Before Implementation

1. Should the cancellation/refund cutoff be exactly 12 hours? Current app copy and logic mention 24 hours.
2. Should the admin secret link completely hide `/admin`, or should `/admin` redirect/404 while the configured secret path remains active?
3. Should student settings be fully removed, or only hidden from sidebar while keeping profile data editable elsewhere by admin?
4. Should `.edu` be required for all students, or only undergrad students as currently the signup model has only student `program_level = undergrad`?
5. Should Mentors of the Week require a minimum number of reviews before automatic ranking?
6. Should dynamic content edits support rich text, plain text only, or structured fields per section?

## Implementation Order Recommendation

1. Fix auth, verification, reset password, admin access, and broken public links.
2. Fix student/mentor sidebar access mistakes and portal topbar consistency.
3. Build shared mentor-card UI and apply to dashboards, Find Mentors, and Institutions.
4. Add admin-managed institutions, featured mentors, featured institutions, and dynamic content.
5. Finish bookings/refunds/feedback/mentor-notes/chats/notifications.
6. Run full launch QA and payment sandbox tests.
