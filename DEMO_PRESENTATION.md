# Grads Paths Demo Presentation Guide

## Product Summary
Grads Paths is a role-based mentorship platform built around three distinct experiences: student, mentor, and admin. The implemented product supports mentor discovery, booking workflows, Zoom-based meeting delivery, office hours, Stripe-backed payments and mentor payouts, post-session feedback, mentor notes, support tickets, institutions management, and operational dashboards for administrators.

This guide is designed for a live stakeholder demo. It focuses on what to open, what to say, what business value to highlight, and how to move through the product in a professional, confident order without overclaiming functionality that is not implemented.

## Demo Objective
The goal of this demo is to present Grads Paths as a complete operational platform rather than a collection of pages. The strongest story is:

1. Students can discover mentors and book sessions in a structured way.
2. Mentors can manage profiles, availability, meetings, and post-session follow-up.
3. Admins can oversee the platform, manage catalog and users, and monitor payouts, support, and performance.

## Audience Framing
For clients and stakeholders, keep the language focused on outcomes:

- Better discovery and matching experience for students
- Structured mentor operations and delivery workflows
- Controlled booking, payment, and meeting lifecycle
- Platform-level visibility for admins
- Clear post-session quality loop through feedback and notes

Do not lead with code or architecture. Lead with product value, then briefly reinforce that the system is modular and role-based.

## How To Start The Demo
Start from the public landing page or root route of the application.

Suggested opening line:

> Today I’ll walk you through Grads Paths as a full mentorship platform. I’ll show it from the student side, the mentor side, and finally the admin side so you can see how the complete operating model works end to end.

Then explain the structure:

> The platform is organized around role-based experiences. Students discover and book support, mentors deliver sessions and manage their workflow, and administrators oversee users, services, revenue, support, and payouts.

## Recommended Demo Order
Use this order unless you need a shorter presentation:

1. Public entry and role-based routing
2. Student dashboard
3. Mentor discovery and mentor profile
4. Student booking flow
5. Student bookings and meeting experience
6. Office hours and credits flow
7. Student feedback and support
8. Mentor dashboard
9. Mentor settings and Zoom connection
10. Mentor availability and bookings
11. Mentor notes after completed sessions
12. Admin dashboard overview
13. Admin users, mentors, services, revenue, rankings, payouts, support, and institutions
14. Professional close

## Live Demo Flow

### 1. Public Entry And Role-Based Routing
What to open:

- Root route `/`
- Login page

What to show:

- The application starts with a public landing experience
- Once authenticated, users are redirected by role
- Students go to the student dashboard
- Mentors go to the mentor dashboard
- Admins go to the admin dashboard

What to say:

> One of the first strengths of the platform is that it is role-aware from the entry point. Instead of sending every user into the same interface, the application routes each role into the experience that matches how they use the platform.

Why it matters:

- Reduces confusion
- Supports cleaner workflows
- Reinforces that the platform is designed for multiple operational personas

### 2. Student Dashboard
What to open:

- Student dashboard

What to show:

- Student-specific navigation
- Student portal framing
- Mentor discovery shortcuts
- Access to explore, bookings, office hours, feedback, store, support, and settings

What to say:

> The student portal is built to guide a student from exploration to session completion. It gives a clear starting point for finding mentors, managing active bookings, accessing office hours, and returning later for support or feedback.

Why it matters:

- Organizes the student journey
- Reduces drop-off between discovery and booking
- Provides a repeatable service experience instead of a one-time interaction

### 3. Mentor Discovery And Mentor Profile
What to open:

- Student explore page
- A mentor profile page

What to show:

- Search and browse flow for mentors
- Mentor listing and profile detail
- Profile presentation of mentor identity, academic background, and booking CTA

What to say:

> Students can browse mentors through a dedicated discovery experience and then move into a focused mentor profile before booking. This creates a more informed decision process instead of sending students directly into scheduling.

Key points to mention:

- The platform separates discovery from checkout
- Mentor profiles are tied into the booking journey
- Mentor visibility is part of a broader quality and ranking system

### 4. Student Booking Flow
What to open:

- Student booking creation page from a mentor profile

What to show:

- Mentor selection
- Service selection
- Session type selection
- Availability lookup by month, day, and time
- Booking creation path

What to say:

> The booking flow is more than a calendar form. It validates the selected mentor, service, session type, and available slot, and it controls whether the booking should use direct payment or a credit-based model depending on the session type.

Key business points:

- Supports structured service offerings
- Supports availability-driven scheduling
- Prevents invalid or mismatched bookings
- Connects the booking process to payment and meeting creation workflows

### 5. Student Bookings And Meeting Experience
What to open:

- Student bookings list
- A specific booking detail state

What to show:

- Upcoming bookings
- Booking detail panel
- Meeting provider and meeting status
- Chat access
- Join meeting action

What to say:

> Once a booking is created, the student has a dedicated bookings area to manage the session lifecycle. This includes the upcoming meeting, its provider, its current state, and related actions such as chat and joining the meeting.

Important implemented control to highlight:

> Meeting access is controlled by timing rules, so even if a meeting link already exists, students are not allowed into the session before the valid access window begins.

Why this matters:

- Protects meeting integrity
- Prevents premature meeting access
- Shows operational control beyond simple link sharing

### 6. Office Hours And Credits Flow
What to open:

- Student office hours page
- Student store page

What to show:

- Office hours directory
- Shared office-hours concept
- Credit balance and credit purchase entry point
- Distinction between standard paid bookings and office hours

What to say:

> The platform supports two commercial models. Standard bookings can go through Stripe checkout, while office hours are supported through a credits model. This gives the business flexibility to package recurring or shared-access support differently from one-to-one sessions.

Key points:

- Office hours are treated as a dedicated experience
- Students can purchase credits
- Credits can then be applied to office-hours style usage

### 7. Student Feedback And Support
What to open:

- Student feedback page
- Student support page

What to show:

- Feedback listing and summary
- Support ticket submission form
- Recent support ticket history

What to say:

> After the session experience, the platform continues the relationship through feedback and support. Students can submit feedback on mentor sessions, and they can also raise support tickets without leaving the platform.

Why it matters:

- Builds quality signals for mentors
- Supports service recovery when something goes wrong
- Extends the platform beyond scheduling into trust and retention

### 8. Mentor Dashboard
What to open:

- Mentor dashboard

What to show:

- Mentor-specific navigation
- Mentor portal framing
- Access to settings, bookings, availability, office hours, institutions, and notes

What to say:

> The mentor side is designed around service delivery. Instead of behaving like a student portal with minor edits, it gives mentors tools to manage their profile, connect meeting infrastructure, control availability, and follow through after each session.

### 9. Mentor Settings And Zoom Connection
What to open:

- Mentor settings page

What to show:

- Mentor profile fields
- University and university program linkage
- Timezone support
- Zoom connection status
- Zoom connect and disconnect flow

What to say:

> Mentor setup is not just profile editing. It connects the mentor’s academic profile, timezone context, and Zoom account so that the platform can support real booking and meeting operations.

Key point:

> This is where the platform transitions from a static mentor listing to an operational mentor account that can actually host sessions.

### 10. Mentor Availability And Bookings
What to open:

- Mentor availability page
- Mentor bookings page

What to show:

- Date-based availability blocks
- Service-specific slot support
- Session size support such as one-to-one and group formats
- Mentor booking management
- Start meeting action

What to say:

> On the mentor side, availability is treated as structured inventory. Mentors define the blocks they are offering, and the booking system uses that inventory to control what students can reserve.

Important operational point:

> Booked slots are protected from invalid edits, which helps preserve booking integrity once students have already reserved time.

### 11. Mentor Notes After Completed Sessions
What to open:

- Mentor notes index
- Mentor notes form for a completed booking

What to show:

- Notes tied to hosted bookings
- Session summary fields
- Student-specific session history from the mentor perspective

What to say:

> A particularly strong workflow in this platform is the post-session mentor notes feature. After a meeting is actually complete, mentors can document what was covered, what the next steps are, and any qualitative observations that matter for continuity.

Important implemented rules to mention:

- Notes are tied to a specific booking
- Only the hosting mentor can create or update them
- Notes are blocked until the session is actually complete
- Duplicate notes for the same booking are prevented by update-or-create behavior

Why it matters:

- Supports service quality
- Helps with continuity across multiple sessions
- Gives the platform a stronger professional-services feel

### 12. Admin Dashboard Overview
What to open:

- Admin overview page

What to show:

- Admin dashboard landing
- High-level operational overview

What to say:

> The admin area turns Grads Paths from a user-facing product into an operable platform. This is where the organization can monitor activity, intervene when needed, and manage the business side of delivery.

### 13. Admin Users And Mentor Management
What to open:

- Admin users page
- Admin mentors page
- Pending mentors if available in your demo data

What to show:

- User management
- Mentor approval, rejection, pause, and removal flows
- Separation between standard users and mentor operations

What to say:

> Admins can manage both account-level and mentor-specific workflows. That includes handling mentor approvals and controlling which mentors are actively available on the platform.

Why it matters:

- Supports platform governance
- Protects quality standards
- Gives operations staff direct control over supply-side readiness

### 14. Admin Services, Revenue, Rankings, And Manual Actions
What to open:

- Admin services page
- Admin revenue page
- Admin rankings page
- Admin manual actions page

What to show:

- Service configuration and catalog management
- Revenue view
- Rankings and performance view
- Manual operational actions

What to say:

> This section shows that the product is not limited to front-end scheduling. Administrators can manage the service catalog, understand platform performance, review rankings, and take manual corrective actions when needed.

Business value:

- Catalog control
- Operational visibility
- Revenue awareness
- Platform intervention tools

### 15. Admin Payouts And Payments
What to open:

- Admin payouts page
- Mentor payout status if available

What to show:

- Payout overview
- Payout detail if demo data exists
- Connection between booking revenue and mentor payouts

What to say:

> Payments are not handled as a dead-end checkout event. The platform also supports downstream payout operations for mentors, including onboarding through Stripe Connect and tracking payout states within the admin experience.

Important implementation points:

- Standard bookings can go through Stripe checkout
- Office hours can use credits
- Refund logic is implemented
- Mentor payout onboarding and status tracking are implemented

### 16. Admin Support And Institutions
What to open:

- Admin support tickets page
- Admin institutions page
- Admin programs page

What to show:

- Support queue with status handling
- Search and filtering
- Institution and university-program management

What to say:

> The platform also includes the operational support and catalog layers that are often missing from early products. Admins can manage tickets and maintain the institution and program data that supports both student and mentor profile quality.

## What To Say At Each Transition
Use short transition lines so the demo feels intentional:

- From public to student:
  > Let’s start with the student journey, because this is where demand enters the platform.

- From student dashboard to discovery:
  > The next step is helping the student find the right mentor rather than forcing them into a booking flow too early.

- From discovery to booking:
  > Once the student has enough confidence in the mentor profile, the platform moves them into a structured booking experience.

- From booking to office hours and payments:
  > Beyond standard sessions, the product also supports a second model for access through office hours and credits.

- From student to mentor:
  > Now I’ll switch to the mentor side, where we can see how session delivery is actually managed.

- From mentor setup to admin:
  > Finally, I’ll show the admin side, which is where the platform becomes operationally manageable at scale.

## Core Modules Implemented
This short section is useful if stakeholders ask how broad the implemented scope is.

### Auth
- Student, mentor, and admin authentication flows
- Registration, login, password reset, email verification
- Admin login and admin user management
- Mentor approval, rejection, pause, and admin logs

### Discovery
- Student and mentor dashboards
- Mentor exploration and mentor profiles
- Admin overview, users, mentors, services, revenue, rankings, and manual actions

### Bookings
- Booking creation and cancellation
- Availability queries by month, day, and time
- Mentor and student booking management
- Meeting join and start actions
- Booking chat
- Booking reminders and confirmations
- Zoom webhook handling and meeting sync
- Attendance and completion-related lifecycle support

### Payments
- Stripe checkout for standard bookings
- Credits purchase flow
- Booking payments records
- Refund logic
- Mentor payout flows and Stripe Connect onboarding
- Admin payout monitoring

### OfficeHours
- Student office-hours directory
- Mentor office-hours view
- Session service selection support

### MentorNotes
- Post-session mentor notes
- Booking-linked note creation and update
- Note access restricted to hosting mentor

### Feedback
- Student feedback submission
- Feedback listing and summary
- Rating aggregation support
- Admin moderation actions

### Settings
- Separate student and mentor settings
- Timezone management
- Mentor profile, academic, and Zoom setup

### Institutions
- University and program browsing
- Admin institution and program management

### Support
- Student and mentor ticket creation
- Admin support queue and reply workflow

## Key Features To Emphasize
These are the strongest differentiators to highlight verbally.

### 1. Role-Based Product Design
The platform is not one dashboard with cosmetic differences. Student, mentor, and admin users each have dedicated flows, routes, permissions, and operational responsibilities.

### 2. Full Booking Lifecycle
The product manages availability, booking, payment, meeting readiness, reminders, cancellation, post-session notes, and feedback rather than stopping at calendar scheduling.

### 3. Operational Meeting Controls
Meeting access is controlled by business rules, and the mentor side includes Zoom setup and hosted meeting workflows.

### 4. Multiple Commercial Models
The platform supports both direct paid bookings and credit-based office hours, which gives flexibility in how services can be packaged.

### 5. Admin Operability
Admins can manage users, mentors, services, revenue views, payouts, support, and institutions from inside the platform.

### 6. Post-Session Quality Loop
The combination of feedback and mentor notes gives the platform a stronger professional-services model with continuity and quality signals.

## Suggested Opening Script
Use or adapt this:

> Thank you for the time today. I’m going to walk you through Grads Paths as a complete mentorship platform. I’ll show the student experience, the mentor workflow, and the admin operating layer so you can see how the system supports discovery, booking, delivery, and platform management end to end.

## Suggested Closing Script
Use or adapt this:

> In summary, Grads Paths is designed not just to help a student book a mentor, but to support the full service lifecycle. Students can discover and access mentors, mentors can manage delivery professionally, and admins can operate the platform with visibility and control. That gives us a foundation for both user experience and operational scale.

## Demo Checklist

### Before The Demo
- Confirm you have working student, mentor, and admin accounts.
- Confirm the mentor demo account is approved and active.
- Confirm the student account has at least one visible mentor to explore.
- Confirm there is at least one mentor with services attached.
- Confirm there is at least one bookable availability slot or office-hours session.
- Confirm the mentor account has meaningful profile data such as title, school, and bio.
- Confirm at least one booking already exists so you can show bookings history and detail.
- Confirm at least one completed booking exists if you want to show mentor notes and feedback naturally.
- Confirm the support section has sample tickets if you want to show admin support operations.
- Confirm payouts or payment records exist if you want to show realistic admin payout states.

### For A Stronger Live Demo
- Prepare one student account with a ready-to-show journey.
- Prepare one mentor account with Zoom connected if that integration is configured in your environment.
- Prepare one admin account with enough seeded data to make overview, rankings, revenue, and support pages meaningful.
- Keep one completed session available to demonstrate feedback and mentor notes.
- Keep one upcoming session available to demonstrate booking detail and meeting access logic.

### If Something Fails Live
- If Stripe checkout is not configured in the environment, explain the standard paid booking flow conceptually and continue with the rest of the booking lifecycle.
- If Zoom connection is not available, show the mentor settings page and explain where meeting setup is controlled.
- If seeded booking data is limited, focus on the role-based portals, discovery flow, availability flow, and admin operations pages.
- If revenue or payout data is sparse, emphasize the implemented admin pages and payout workflow structure rather than specific totals.

### What Not To Overclaim
- Do not claim AI matching or recommendation if you are not demonstrating it.
- Do not claim mobile apps unless they exist separately.
- Do not claim advanced analytics beyond the implemented admin views.
- Do not claim unrestricted real-time communication behavior beyond what you can show confidently.

## Short Version For A 5 To 7 Minute Demo
If time is short, use this compressed order:

1. Student dashboard
2. Explore mentors
3. Mentor profile
4. Booking page
5. Student bookings page
6. Mentor dashboard
7. Mentor availability or notes
8. Admin overview
9. Admin payouts or support

Suggested short close:

> What you’ve seen is a platform that supports the full mentorship workflow, from discovery and booking to delivery, quality tracking, and admin operations.

## Short Version For An 8 To 12 Minute Demo
If you have more time, use this fuller order:

1. Public landing and role-based routing
2. Student dashboard
3. Mentor discovery
4. Mentor profile
5. Booking flow
6. Student bookings and meeting controls
7. Office hours and credits
8. Feedback and support
9. Mentor dashboard and settings
10. Mentor availability and bookings
11. Mentor notes
12. Admin overview
13. Admin users, mentors, services, revenue, rankings
14. Admin payouts, support, institutions

## Final Presenter Advice
- Speak in terms of user journeys and platform operations.
- Keep transitions short and intentional.
- Show confidence by narrating why each page exists, not only what it contains.
- Avoid trying to demo every field on every screen.
- Emphasize control, workflow integrity, and role separation.
- End on business value, not on technical details.
