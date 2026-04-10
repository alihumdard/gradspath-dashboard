Grads Paths – Backend Documentation

1. Project Overview
Grads Paths is a comprehensive Student-Mentor Portal that connects graduate students with alumni and professionals from top global institutions. The platform allows students to discover mentors, book office hours, manage sessions, give feedback, and maintain private progress notes while ensuring secure and smooth operations.
The backend is built to support three distinct user roles with proper access control and real-time features.
2. User Roles & Access
Student: Can browse mentors, book sessions, chat, submit feedback, create support tickets, and manage profile.
Mentor: Can manage profile & office hours, write private notes on students, view own bookings & chat, and receive feedback.
Admin: Has full oversight – manages users, institutions, moderates content, and handles support tickets.
3. Total Modules (10)
Authentication & User Management
Dashboard / Find Mentors & Explore Mentors
Institutions / Universities Management
Office Hours Module
Feedback & Reviews Module
Mentor Notes on Users (Highly Sensitive)
Bookings & Session Management
Support Tickets Module
Settings & Profile Management
Payments & Credits System
4. Development Milestones (3 Phases)
Milestone 1: Admin Side + Core Foundation
Focus: Backend foundation and admin tools
Authentication & Role Management
Institutions CRUD (Program Tiers & Types)
Global configurations
Support ticket management for admin
Basic analytics and moderation
Deliverables: Working auth system, admin dashboard, institutions module.
Milestone 2: Mentor Side
Focus: Tools for mentors
Mentor Profile Management
Office Hours setup with rotation logic
Mentor Notes on Users (private & secure)
View own bookings, chat, and feedback
Deliverables: Mentor dashboard, office hours, and secure notes system.
Milestone 3: Student Side + Full Integration
Focus: Student experience and complete platform
Find Mentors & Dashboard (search, filters, credits)
Explore by University
Office Hours Booking
Bookings Calendar, Real-time Chat & Video Links
Feedback System
Support Tickets
Settings & Payments/Credits
Deliverables: Full student flow, real-time features, payment integration, and polished UI-backend connection.
Module 1: Authentication & User Management
Purpose This is the foundational module of the platform. Without it, no user (Student, Mentor, or Admin) can access Grads Paths. It handles user registration, login, role assignment, and secure authentication.
How It Works (Flow)
User visits the website and clicks on Signup.
They provide email and password (or use Google sign-in).
Backend uses Laravel Auth to create the account.
A default role of “student” is assigned and a record is created in the users table.
If a user wants to become a Mentor, they submit a separate form. The request goes for Admin approval.
Once the Admin approves, the role changes from “student” to “mentor”.
On successful login, Laravel returns an auth token (e.g., Sanctum/Passport), which is sent with every subsequent API request.
Backend verifies the auth token and checks the user’s role on every protected request.
Main Features
User Signup (Email/Password + Social Login)
Login / Logout
Forgot Password & Reset Password
Role Management (Student, Mentor, Admin)
Mentor Approval Workflow
Basic Profile Creation on Signup
CRUD Operations
Create: New user registration with default “student” role
Read: Fetch user details during login and profile access
Update: Basic profile information (name, avatar, etc.)
Delete: Account deletion (soft delete recommended)
User Roles & Access
Student: Can create account and login
Mentor: Can signup but must wait for Admin approval
Admin: Highly restricted — only existing admins can create new admin accounts
Module 2: Find Mentors & User Dashboard
Purpose This module serves as the main entry point and marketplace interface for students. It allows students to discover mentors through a curated "Mentors of the Week" section, advanced search, and multiple filters.
How It Works (Flow)
After successful login, the student lands on the Dashboard.
The backend fetches "Mentors of the Week" — a curated list of high-rated mentors marked as is_featured = true.
For each mentor card, the backend provides complete data including name, title, university, office hours, services offered, rating, and a snippet of recent feedback.
The student can search by mentor name/keyword or apply filters such as Mentor Type (Graduates/Professionals), Program Type (MBA, Law, Therapy), and School.
       	When the student clicks the "Book Now" button on any mentor card:
The backend first performs a credit check to ensure the student has at least 1 credit.
If credits are available, the user is redirected to the "Book with Mentor" page where they can select service, meeting size, date, and time.
After selecting details and clicking "Continue", the user is taken to the "Your Session Is Booked" confirmation page.
Clicking "Read More" fetches the mentor’s full profile and complete recent feedback from the backend.
The user’s current credit balance (e.g., "Credits: 16") is displayed in real-time in the top-right corner and updates automatically after any credit-related action.
Main Features
Mentors of the Week curated section
Advanced search and multi-criteria filtering
Rich mentor cards displaying profile info, office hours, services, rating, and recent feedback
Real-time credit balance display
"Book Now" button with credit validation
"Read More" functionality for detailed mentor profile
Light/Dark mode preference toggle
CRUD Operations
Create: Not applicable (only admin can mark mentors as featured)
Read: Heavy usage — fetching featured mentors, search results, filtered lists, and full mentor profiles
Update: Only user theme preference (Light/Dark mode)
Delete: Not applicable
User Roles & Access
Student: Full access — can view, search, filter, and initiate booking
Mentor: Can view their own card but cannot book
Admin: Can manage featured mentors and view all data
Important Backend Logic
Featured mentors are fetched using the is_featured flag in the mentors table.
Credit validation is performed before allowing any booking action.
Efficient database queries with proper indexing on name, university, and rating for fast search and filtering.
Real-time credit balance is maintained and updated using Laravel Reverb or on-demand fetching.
Partial content loading (snippets) with "Read More" for better performance.
Database Tables Involved
mentors – Main table containing mentor profile, office hours, rating, and featured status
feedback – For displaying recent feedback snippets
user_credits – Real-time credit balance
user_settings – For saving Light/Dark mode preference
Module 3: Institutions / Explore by University Module
Purpose This module allows students to discover mentors through universities.
How It Works
Students can filter universities using Program Tier (All, Elite Programs, Top 25 Programs, Regional Programs) and Program Type (All, MBA, Law, Therapy).
A search bar allows searching universities by name.
Each university card displays relevant tags such as Top Rated, Multiple Tiers, MBA, Law, or Therapy.
When a student clicks on a university, the system shows available graduate programs for that university.
From there, students can navigate to view the filtered list of mentors associated with that university and its programs.
Backend Technologies Used
Laravel – Handles filtering logic and API endpoints
MySQL – Stores universities, program tiers, and relationships
Dynamic Query Building – Backend constructs SQL queries based on selected filters (tier, program_type, search term)
JOIN Queries – Used to fetch universities along with their associated programs and mentor count
Indexing – Applied on university name, tier, and program_type columns for fast search and filtering
Main Features
Advanced filtering by Program Tier and Program Type
University search functionality
University grid with informative tags
Clickable universities leading to program-specific mentor lists
Smooth navigation from university → programs → mentors
CRUD Operations
Read: Heavy usage for fetching filtered universities and programs
Create / Update / Delete: Managed by Admin (adding new universities, updating tiers and programs)
Database Tables Involved
universities
university_programs
mentors (linked with universities)
Module 4: Office Hours Module (Dynamic Office Hours Engine)
Purpose The Office Hours Module manages recurring group mentoring sessions where a maximum of 3 students can participate in a single session. It provides mentors with a flexible way to offer weekly or bi-weekly sessions while incorporating smart automation and conditional business logic to create a dynamic and fair booking experience.
How It Works
1.  	Mentor Schedule Setup Mentors define their recurring schedule (e.g., Every Tuesday at 5 PM EST) and list of services they can offer (such as Tutoring, Program Insights, Interview Preparation, etc.). They also select the rotation frequency — Weekly or Biweekly.
2.  	Automated Rotation Engine The system automatically rotates the service for each session.
o   A background task (CRON job or Laravel Scheduled Task) runs at the beginning of every week.
o   It moves to the next service in the mentor’s predefined list and updates the current_service.
o   This eliminates the need for mentors to manually change the session topic every week.
3.  	Real-time Spot Tracking System
o   Each session has a fixed capacity of 3 spots.
o   The backend maintains current_occupancy (number of students who have booked).
o   When students view the session, the backend calculates remaining spots and returns the status.
o   If all 3 spots are filled (current_occupancy == 3), the system sends is_full: true, so the frontend displays a disabled "Session Full" button.
4.  	First Student Choice Logic (Unique Conditional Feature)
o   When the first student books a session (current_occupancy == 1), they receive the option to choose any service from the mentor’s list.
o   24-Hour Cutoff Rule: The first student can change or select the service only if the booking is made at least 24 hours before the session time.
o   Once the second student books the same session, the chosen service becomes locked. After locking, neither the first nor any subsequent student can change the service.
5.  	Booking & Credit Flow
o   Student clicks "Book Now".
o   Backend performs a credit check.
o   If sufficient credits are available, the spot is reserved, current_occupancy is incremented by 1, and credits are deducted.
o   The system returns appropriate service options based on whether the student is the first booker and whether the 24-hour window is still open.
Main Features
Support for Weekly and Biweekly service rotations
Fully automated service rotation using background tasks
Real-time spot tracking (0/3, 1/3, 2/3, 3/3)
Intelligent "First Student Choice" with 24-hour cutoff
Automatic service locking after the second booking
"Session Full" status handling
Seamless integration with the Credits system
CRUD Operations
Create: Mentor sets up recurring schedule, services, and rotation type
Read: Students fetch available sessions with current rotation, spots, and service status
Update: Increment/decrement occupancy, change service (restricted to first student within cutoff), reschedule sessions
Delete: Cancel booking (decrements occupancy and triggers credit refund)
User Roles & Access
Student: Can view sessions, book spots, and select service if they are the first booker (within 24 hours)
Mentor: Can create and manage their recurring schedule and rotation settings
Admin: Has full visibility and can manage office hours if require
Module 5: Feedback & Reviews Module
Purpose The Feedback & Reviews Module acts as the social proof and trust engine of the platform. It collects verified student feedback after sessions and displays it in a structured,
Key Concepts & How It Works (Detailed Flow)
1.  	Top Stats Aggregation (The "Brain" of the Page) The top statistics boxes are not static text — they are powered by real-time backend calculations:
o   Average Rating (e.g., 4.9): Backend runs AVG(stars) query across all reviews for a mentor or globally.
o   Recommendation Rate (e.g., 96%): Backend counts reviews where recommend = true and divides it by total reviews.
o   Most Mentioned Tag (e.g., “Clear advice”): Backend performs frequency analysis on comment text to identify the most commonly used keywords/phrases.
2.  	Nested Review System (Card Structure) Each review card has a two-level structure:
o   Level 1 (Mentor Overview): Shows mentor name and overall rating.
o   Level 2 (Individual Reviews): Displays short previews of student reviews (e.g., “Aisha S. - Feb 15, 2026”).
o   When the page loads, the backend uses JOIN queries to fetch the mentor information along with their latest 2–3 reviews.
o   Clicking the "View" button on a review triggers a backend call to fetch the full comment using the review_id, which is then shown in a modal.
3.  	Dynamic Filters & Sorting (GET Requests) Users can filter reviews using dropdowns and search:
o   Filters include: Program Type (MBA, Law, Therapy), Mentor Type, “My Mentors Only”, etc.
o   Sorting options: Most Recent, Highest Rated, Most Helpful.
o   Backend builds dynamic SQL queries based on selected filters. For example:
§  WHERE program_type = 'MBA'
§  ORDER BY stars DESC for Highest Rating
4.  	Contextual "Book Now" Action Every review card includes a "Book Now" button.
o   This button passes the mentor_id and relevant office_hours_id (if available) to the Bookings module.
o   The goal is to convert trust (from good reviews) into immediate action (booking).
5.  	Real-time Updates
o   As soon as a new feedback is submitted, the Top Stats (Average Rating, Recommendation Rate, etc.) are recalculated and updated in real-time across the platform.
o   This can be achieved using Laravel Reverb subscriptions or database triggers.
Main Features
Real-time statistical aggregation (Average Rating, Recommendation Rate, Top Tags)
Nested review cards with preview and full view
Advanced dynamic filtering and sorting
Verified Booking badge on every review
Contextual “Book Now” button on review cards
Pagination or Infinite Scroll support
Option for mentors to reply to reviews (if enabled)
CRUD Operations
Create (POST): Student submits feedback after a completed session (stars, comment, recommend toggle)
Read (GET): Fetch aggregated stats + filtered and sorted reviews with JOINs
Update (PATCH): Mentor can reply to a review (optional feature)
Delete: Admin can remove spam or inappropriate reviews
User Roles & Access
Student: Can submit feedback (only for their completed sessions) and browse all public reviews
Mentor: Can view feedback received on their sessions and reply to reviews
Admin: Can moderate all reviews (delete or hide inappropriate content)
Important Backend Logic
Verification Check: Feedback can only be submitted if the associated booking status is “Completed”
Aggregation Queries: Efficient calculation of averages and percentages (can use materialized views for better performance)
Keyword Analysis: Simple frequency count or basic NLP for “Most Mentioned Tag”
Dynamic Query Building: Backend safely constructs queries based on user-selected filters to prevent SQL injection
Real-time Propagation: New reviews instantly update dashboard statistics
  	Database Tables Involved
feedback — Core table (mentor_id, student_id, rating, comment, recommend, service_type, booking_id, created_at)
mentor_ratings — Optional aggregated table or view for fast stats retrieval
 
6.     Mentor Notes on Users (Highly Sensitive)
Purpose This module serves as a secure internal reporting and privacy-focused system. It allows mentors to create, store, and track private notes about their students’ progress, strengths, weaknesses, and session history.
How It Works (Detailed Flow)
Secure Role-Based Access (RBAC + Laravel Policies)
This is the most critical part of the module.
Backend first verifies the auth token and checks the user’s role.
Only users with Mentor or Admin role are allowed to access this module. Students get zero access.
Using Laravel Policies, a mentor can only read notes where mentor_id matches their own ID.
This ensures a mentor cannot see notes written by other mentors.
Nested Data Structure (One-to-Many Relationship)
One student can have multiple notes from the same or different mentors.
Backend uses JOIN queries between two tables:
users table (for student basic info like name and avatar)
mentor_notes table (for actual notes, session dates, and content)
When the page loads, the backend fetches students along with their associated notes, sorted by created_at DESC (newest first).
Each student card shows a Note Counter (e.g., “3 Notes”) using the COUNT() function.
Search & Filter Mechanism
There are two search bars at the top:
Search by User: Filters students by name (e.g., typing “Tyl” shows “Tyler Cogan”).
Search by Mentor: Shows notes written by a specific mentor.
Backend uses the ILIKE operator for partial, case-insensitive matching.
Dual search logic is implemented so the query focuses on either student_id or mentor_id depending on the search type.
"View" & "Read More" Interaction
For performance reasons, the initial card view only shows a short snippet (first 100–150 characters) of each note.
When the mentor clicks the "View" button, a separate backend request is made using the note_id to fetch the full note content, which is then displayed in a modal.
Main Features
Strict privacy and role-based access control
Student cards with initials/avatar and note counter
Multiple notes per student (One-to-Many relationship)
Dual search functionality (by User and by Mentor)
Short snippet preview with full note view in modal
Notes sorted by date (newest first)
Soft delete functionality (Admin only)
CRUD Operations
Create (POST): Mentor creates a new note after a session (student_id, mentor_id, session_date, note_body)
Read (GET): Fetch student list with their notes (with search, filter, and pagination)
Update (PATCH): Mentor can edit their own notes
Delete: Soft delete by Admin only (record remains in database for backup)
User Roles & Access
Student: No access whatsoever to this module
Mentor: Can create, view, and edit only their own notes for students they have worked with
Admin: Full access to all notes and can perform soft delete
Important Backend Logic
RBAC + Laravel Policies: Heavy security layer — auth token validation + Laravel Policies
Performance Optimization: Short snippets on list view, full content only on "View" request
Search Efficiency: ILIKE operator with proper indexing on name fields
Data Grouping: Notes are grouped by student_id for clean card display
7. Bookings & Session Management Module (Most Critical)
Purpose This is the central operational hub of the entire platform. It manages all confirmed mentoring sessions, coordinates communication between students and mentors, handles calendar visualization, generates virtual meeting links, enables real-time chat, and processes cancellations with credit refunds.
Key Concepts & How It Works (Detailed Flow)
Single Source of Truth (Database Schema) When a user books a session, the backend creates a record in the bookings table. This table acts as the Single Source of Truth and is linked to multiple other tables:
users table → Student who is booking
mentors table → Mentor (e.g., Emily Carter)
services table → Booked service
office_hour_sessions / slots table → Specific date and time
Booking Flow (From "Book with Mentor" to Confirmation)
Student clicks "Book Now" on a mentor card.
They are taken to the "Book with Mentor" page where they select service, meeting size (1 on 1, 1 on 3, 1 on 5), date, and time.
After clicking "Continue", they are redirected to the "Your Session Is Booked" confirmation page.
This page shows selected service, mentor details, date, time, Zoom link, real-time chat, upcoming appointments, and cancel option.
Calendar Sync Logic (Visualization)
When the Bookings page loads, the backend sends an array of booked dates.
Frontend highlights these dates on the calendar.
Clicking a highlighted date filters the "Upcoming Appointments" list.
Virtual Meeting & Cancellation Logic
Meeting Link Generation: As soon as a booking is confirmed, the backend triggers the Zoom or Google Meet API to generate a unique meeting URL. This URL is stored in the bookings table.
Cancellation & Refund: When the user clicks "Cancel Meeting", the backend checks the cancellation policy (e.g., 24-hour rule). If allowed, it changes the booking status to cancelled and adds the credit back to the student’s user_credits table using an atomic transaction.
Real-time Chat System
Chat becomes available 24–48 hours before the session.
The system uses Laravel Reverb so messages appear instantly.
Backend saves messages with sender_id, receiver_id, booking_id, and timestamp.
The "Chat Available" badge is shown only when the session time is approaching.
Main Features
Interactive calendar with highlighted booked dates
Upcoming Appointments list (auto-sorted by time)
Dynamic Zoom / Google Meet link generation and storage
Real-time chat (24–48 hours before session)
Cancellation with intelligent refund logic
Service lock after successful booking
Automatic movement of past sessions to History
CRUD Operations
Create: New booking record creation
Read: Calendar dates array, upcoming appointments, chat history
Update: Reschedule (if allowed), update booking status
Delete / Cancel: Cancel booking + trigger credit refund
User Roles & Access
Student: Can view own bookings, access chat and meeting link, cancel sessions
Mentor: Can view all their booked sessions, access chat and meeting link
Admin: Full visibility over all bookings
Important Backend Logic
Atomic Transactions: Credit deduction and refund are handled atomically to prevent errors
Time-based Checks: 24–48 hour window for chat availability and cancellation policy
Meeting Link Security: Links are only visible to authenticated participants of that booking
Real-time Subscription: Laravel Reverb is used for instant chat delivery
Recommended Tools / Technologies
Meeting Link: Zoom API or Google Meet API
Calendar Sync: MySQL date queries + Laravel
Credit Refund: Atomic transactions via Laravel Database Transactions
Real-time Chat: Laravel Reverb (WebSockets)
Database Tables Involved
bookings — Main table (student_id, mentor_id, service_type, meeting_link, status, created_at)
chats — Real-time messages (booking_id, sender_id, receiver_id, message_text, timestamp)
user_credits — For credit balance and refunds
office_hour_sessions — Date and time information
Post-Meeting Feedback System (Mandatory)
Purpose After every completed session (individual or Office Hours), both the student and the mentor are required to fill out a mandatory post-meeting feedback form. This form appears automatically after the session ends (demo6 for students and demo7 for mentors).
How It Works
Once a session ends, the system automatically redirects the student to the "Feedback After Your Meeting" page.
The student is asked to rate the session, rate the mentor’s preparedness, answer whether they would recommend the mentor, and provide quick feedback.
For mentors, a similar form appears where they provide feedback on each student (especially important in group Office Hours sessions where multiple students attend).
Both sides have a temporary "Skip" button, but they must complete the feedback within 24 hours.
If not completed within 24 hours:
Student: They will be restricted from making new bookings or accessing certain platform features until the feedback is submitted.
Mentor: They will be restricted from accepting new sessions or continuing other activities until all pending feedback is completed.
Backend Working
The system tracks session completion status in the bookings or office_hour_bookings table.
After session end time, the backend triggers the feedback form for both parties.
Feedback data is stored in the feedback table (for students) and a separate mentor feedback table (for mentors).
A 24-hour timer is maintained. If feedback is not submitted, the backend restricts further actions by checking the user’s pending feedback status on every protected route.
 
8. Support Tickets Module
Purpose The Support Tickets Module acts as a bridge between users and the admin team. It allows students and mentors to report issues, ask questions, or seek help regarding bookings, payments, technical problems, or any other platform-related concerns. This module helps improve user retention by providing an organized and responsive support system.
Key Concepts & How It Works (Detailed Workflow)
Data Submission (POST Request)
When a user clicks "Send Message" on the Support page, a POST request is sent to the backend.
Endpoint: /api/v1/support/tickets
Payload includes: name, email, subject, message, and automatically attached user_id (from auth token).
Backend validates the data and saves the ticket into the support_tickets table with a unique Ticket ID (e.g., #SUP-102).
Automated Triggers & Email Notifications
As soon as the ticket is successfully saved, the backend automatically triggers two emails:
To User: Confirmation email — “Hi Mike, we've received your ticket #102. Our team will get back to you shortly.”
To Admin: Alert email / push notification — “New support ticket received from [User Name] – Subject: [Subject]”
Emails are sent using Resend, SendGrid, or SMTP service via background tasks.
Admin Management Logic
Admins have a dedicated dashboard where all tickets are listed.
Each ticket shows status: Open → In Progress → Resolved.
Admin can reply to the ticket, update its status, and the response is saved in the database so the user can see the update in their dashboard.
Security & Validations (Critical)
Rate Limiting: Maximum 3–5 tickets per user per hour to prevent spam or abuse.
Input Sanitization: All user messages are sanitized to prevent XSS (Cross-Site Scripting) attacks.
Character Count Validation: Backend checks that the message is not empty and respects the allowed character limit.
User Identification: user_id is automatically taken from the authenticated auth token (frontend does not need to send it).
Main Features
Simple and clean ticket creation form
Automatic unique Ticket ID generation
Dual automated email notifications (User + Admin)
Ticket status tracking (Open, In Progress, Resolved)
Admin reply functionality
Rate limiting and spam protection
Message sanitization for security
CRUD Operations
Create (POST): User creates a new support ticket
Read (GET):
Users can view their own tickets
Admins can view all tickets
Update (PATCH): Admin can update status and add replies
Delete: Usually archived (soft delete optional)
User Roles & Access
Student & Mentor: Can create tickets and view their own ticket history
Admin: Full access — can view all tickets, update status, and reply
Important Backend Logic
Automatic user_id attachment from JWT
Background jobs for sending emails
Rate limiting to prevent abuse
Secure input validation and sanitization
Database Tables Involved
support_tickets — Main table (Columns: id, ticket_id, user_id, subject, message, status, admin_reply, created_at, updated_at)
9. Settings & Profile Management Module
Purpose This module enables users and mentors to manage their public profile, personal information, media assets, and financial payout settings. Changes made here directly reflect across the platform, especially on the Find Mentors page and mentor cards. It is particularly important for mentors as it controls their professional identity and payment configuration.
Key Concepts & How It Works
Profile & Media Management (The CRUD Foundation)
When a mentor clicks "Save Changes", a PATCH request is sent to update the mentors table.
Image Upload: The uploaded image is stored in Laravel Storage. The new image URL is updated in the database, and the old image is automatically deleted for storage optimization.
Real-time Preview: The right-side "What Users Will See" card shows a live preview using frontend state. On page refresh, the backend fetches the latest saved data to ensure accuracy.
Validation & Security (The Gatekeeper)
Mentor Type Validation: If mentor_type is set to "Grad Mentor", the backend strictly checks that the email ends with .edu. If not, an error is returned.
Calendly Link Validation: The backend verifies that the provided link is a valid URL to prevent broken links for students.
All inputs are sanitized to protect against security vulnerabilities.
Stripe Connect Integration
Clicking the "Enable Payouts" button triggers the Stripe Connect Express API.
The backend generates a unique Stripe Account ID and returns an onboarding link.
The mentor is redirected to Stripe’s hosted page to enter their personal and banking details (SSN, bank account, date of birth, etc.).
Once the mentor completes onboarding, Stripe sends a webhook to the backend.
The backend then updates the mentor’s status to payouts_enabled: true, and the frontend label changes from "Not enabled" to "Enabled".
Community Integration
Slack Integration: The backend stores a Slack invite link (fixed or dynamically generated via Slack API).
An optional slack_joined boolean flag can track whether the mentor has joined the community Slack.
Main Features
Comprehensive profile editing (name, bio, office hours, description, etc.)
Secure profile image upload with automatic old image cleanup
Real-time preview of public profile
Strict validation rules (especially .edu email for Grad Mentors)
Calendly and Slack link management
Full Stripe Connect onboarding for receiving payouts
Light/Dark mode and user preference settings
CRUD Operations
Create: Initial profile setup when a mentor is approved
Read: Fetch current profile and settings data
Update (PATCH): Update profile information, image, links, and preferences
Delete: Rarely used (account-level deletion)
User Roles & Access
Student: Can edit basic profile and theme preferences
Mentor: Has full access to profile editing, image upload, external links, and Stripe Connect setup
Admin: Can view and moderate mentor profiles when necessary
Important Backend Logic
Atomic operations for image upload, URL update, and old image deletion
Strong validation for mentor type and email domain
Stripe webhook listener to detect completed onboarding
Real-time synchronization so profile changes appear instantly on public pages
Database Tables Involved
mentors — Core table for mentor profile (full_name, bio, image_url, office_hours, payouts_enabled, calendly_link, slack_link, etc.)
user_settings — Stores user preferences such as Light/Dark mode
10. Payments & Credits System
Purpose This module handles the complete financial flow of the platform. It manages virtual credits for students, processes payments, deductions, refunds, and enables payouts for mentors.
Key Concepts & How It Works
Credit Balance Management Every user has a virtual credit balance (displayed as "Credits: 16" in the top right). The backend maintains this balance in real-time and updates it across all pages.
Credit Purchase Students can buy credits through the "Store" button. The backend creates a Stripe Checkout session. After successful payment, Stripe sends a webhook, and the user’s credit balance is increased.
Credit Deduction When a student books a session, the backend checks the available credits. If sufficient, it deducts the required credits using an atomic transaction before confirming the booking.
Credit Refund On booking cancellation (subject to policy, e.g., 24-hour rule), the backend automatically adds the credit back to the student’s balance and marks the booking as cancelled.
Mentor Payouts Mentors receive earnings through Stripe Connect. Once payouts are enabled in Settings, mentors can withdraw their earnings to their bank account via Stripe.
Main Features
Real-time credit balance display
Secure credit purchase via Stripe
Automatic credit deduction on booking
Automatic refund on eligible cancellations
Mentor payout system using Stripe Connect
Atomic transactions for financial safety
CRUD Operations
Read: Current credit balance
Update: Increment (purchase/refund) and decrement (booking) of credits
User Roles & Access
Student: Buy credits, view balance, receive refunds
Mentor: Receive payouts through Stripe Connect
Admin: View overall financial transactions and analytics
Important Backend Logic
Atomic transactions to prevent credit errors
Stripe webhooks for payment and payout confirmation
Negative balance prevention
Database Tables Involved
user_credits — Stores current credit balance
credit_transactions — Records all transactions (purchase, deduction, refund)
7. Backend Working Summary (How Everything Connects)
  Core Data Flow (End-to-End User Journey)
Student logs in → Lands on Dashboard or Find Mentors page.
Searches and applies filters → Backend returns matching mentors with ratings, availability, and recent feedback.
Student explores by University (Institutions Module) or directly selects a mentor.
Clicks “Book Now” → Backend performs credit check → Bookings Module creates a booking record and deducts credits (Payments Module).
Session is scheduled → Student and mentor get access to calendar, real-time chat, and video meeting link.
Session completes → Student submits feedback (Feedback Module) → Mentor can write private progress notes (Mentor Notes Module).
If any issue occurs → User creates a support ticket (Support Tickets Module).
Key Connections Between Modules
Payments & Credits System is tightly integrated with almost every action — it deducts credits on booking, refunds credits on cancellation, and adds credits when purchased from the Store.
Bookings Module serves as the central operational hub. It receives requests from Find Mentors and Office Hours, manages calendar, generates meeting links, enables real-time chat, and handles cancellations with refunds.
Institutions Module feeds university-based filtered data into the Find Mentors flow.
Settings Module updates mentor profiles that are displayed on Find Mentors and Dashboard pages.
Feedback Module pulls data from completed bookings and shows verified reviews publicly.
Mentor Notes Module is completely private and linked only to specific student-mentor relationships.
Support Module operates independently but remains accessible to all users for issue resolution.
Core Backend Principles
All modules communicate through well-defined REST APIs built with Laravel.
Security is enforced using Role-Based Access Control (RBAC)
Financial operations use atomic transactions to ensure data consistency.
Real-time features (chat, spot updates, notifications) are powered by Laravel Reverb.
The system maintains a clear separation of concerns while keeping strong connections between related modules.
 
Grads Paths: Milestone 1
Focus: Administrative Infrastructure, Data Analytics, and Global Controls.

1. Overview Dashboard (The Intelligence Hub)
Purpose: Serving as the landing page, it provides real-time monitoring of platform health and high-level growth trends.
·        Key Features & UI Elements:
o   KPI Scorecards: Real-time counters for Total Users (with 30-day growth), Active Mentors, Bookings (30d/7d), Gross Revenue, Net Platform Revenue, and Refunded Amounts.
o   Interactive Charts: 6-month line charts for Booking Velocity and bar charts for Revenue Trends.
o   Performance Leaderboards: Quick-view tables for Top Mentors (by revenue) and Top Services (by bookings).
·          ·        CRUD Operations:
o   Read: Full access to view platform-wide metrics and historical performance data.
·        User Roles & Access:
o   Admin: Full access.
o   Mentor/Student: No access.

2. Users Management Page
Purpose: A granular directory for managing the student/applicant base, tracking their engagement, and financial contribution.
·        Key Features & UI Elements:
o   Detailed Table: Columns for Name, Email, Program, Institution, Total Meetings, and Total Spent.
o   Numeric Service Breakdown: Specific columns for every service (Free Consult, Tutoring, Program Insights, Interview Prep, Application Review, Gap Year Planning, Office Hours) showing the exact count of sessions used by that user.
o   Search & Filter: Advanced global search by name/email/school and filters for specific Programs or Institutions.
o   Export Tool: A dedicated button to generate and download the entire user list in CSV/Excel format.
·        Backend Technologies & Implementation:
o   Laravel: Handles complex multi-column filtering and pagination logic.
o   MySQL: Executes relational joins between users and bookings tables to calculate per-user service counts.
o   Pandas (Python): Integrated into the backend to process and format large datasets for clean CSV/Excel exports.
·        CRUD Operations:
o   Read: Access to all user profiles and their historical service usage.
o   Update: Ability to activate, block, or modify user roles.
o   Delete: Soft delete functionality for inappropriate or duplicate accounts.
·        User Roles & Access:
o   Admin: Full oversight and export rights.

3. Mentors Management Page
Purpose: Tracking the efficiency, performance, and accountability of the platform's service providers.
·        Key Features & UI Elements:
o   Performance Table: Columns for Mentor Name, Program, School, Total Revenue Generated, and Total Successful Meetings.
o   Service-Count Breakdown: Detailed counts for all 7 services provided by the mentor.
o   Accountability Metrics: Specific columns for Missed Sessions and Refunds issued against the mentor’s bookings.
o   Smart Search: Filter by name, email, school, or program status (Active/Paused).
·        Backend Technologies & Implementation:
o   Laravel: Processes performance logic and manages mentor-specific business rules.
o   MySQL: Real-time querying of feedback and booking tables to determine mentor ratings and reliability.
o   Atomic Transactions: Ensures that updates to mentor status or revenue shares are processed without data corruption.
·        CRUD Operations:
o   Read: Comprehensive view of mentor performance and reliability logs.
o   Update: Approve/Reject applications, change status to 'Paused' or 'Active'.
·        User Roles & Access:
o   Admin: Complete management of mentor lifecycle.

4. Services Management Page
Purpose: Managing the product portfolio, pricing structures, and analyzing service-level demand.
·        Key Features & UI Elements:
o   Configuration Table: Columns for Service Name, Format (Time duration), Meeting Size Mix (1:1, 1:3, 1:5), and Set Prices.
o   Supply Tracking: "Mentors Offering" column to identify which services lack enough providers.
o   Service Popularity: A dedicated bar chart showing which services have the highest booking volume.
o   Export Services: Downloadable configuration and performance reports.
·        Backend Technologies & Implementation:
o   Dynamic Pricing Engine: Laravel logic that allows admins to update a price in one place and sync it across the entire platform.
o   Aggregation Logic: Queries bookings table to group data by service_type for the popularity charts.
o   MySQL: Stores and serves global service configurations.
·        CRUD Operations:
o   Create: Add new service tiers or session formats.
o   Update: Global price adjustments and changing service availability.
o   Delete: Soft delete obsolete services.

5. Revenue & Rankings Module
Purpose: Financial auditing and benchmarking of top-tier performance on the platform.
·        Key Features & UI Elements:
o   Financial Hub: Cards for Gross Revenue, Mentor Payouts (Paid/Due), Platform Fees, and Refund Totals.
o   Program Revenue: Doughnut chart showing revenue split by degree program (MBA, Law, etc.).
o   Leaderboards: Top 5 rankings for both Mentors (by revenue) and Students (by LTV - Lifetime Value).
·        Backend Technologies & Implementation:
o   Revenue Split Logic: Automated backend calculation of commission percentages vs. mentor earnings for every transaction.
o   Ranking Algorithm: Sorted queries (ORDER BY total_spent DESC) used to generate real-time leaderboards.
·        CRUD Operations:
o   Read: Financial auditing and top-tier performance tracking.

6. Manual Actions (Admin Control Stations)
Purpose: Providing the administrator with the power to make manual overrides and global system changes.
·        The 6 Control Stations:
1. 	Amend Mentor: Full profile editing with card previews.
2. 	Refunds & Credits: Manual processing of refunds and restoring "Office Hour" credits.
3. 	Moderation: Editing/Deleting user feedback and session notes.
4. 	Institutions: Adding new schools and universities globally.
5. 	Programs: Creating new academic programs or degree paths.
6. 	Pricing: Bulk editing global service prices.
·        Backend Technologies & Implementation:
o   Global Search Engine: A unified API endpoint that scans all system tables simultaneously for rapid data access.
o   Audit Logging: Backend logs every manual change (Who, When, What) into a secure admin_logs table for accountability.
o   Atomic Transactions: Ensures sensitive operations like refunds or pricing changes are processed securely.

User Roles & Access Summary
·        Admin: Full access to all data, management tools, export functionality, and financial controls.
·        Student/Mentor: No access to any module in the Admin Dashboard.
 
 
