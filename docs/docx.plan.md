Grads Paths – Backend Documentation



1.	Project Overview
Grads Paths is a comprehensive platform utilizing a Student to Mentor Portal that connects graduate students and professionals with undergraduate students and users from top US ONLY (currently) institutions. The platform allows students to discover mentors, book office hours, manage sessions, give feedback, and maintain private progress notes while ensuring secure and smooth operations.

The backend is built on Laravel 12 and is designed to support three distinct user roles with proper access control and real-time features.

2.	User Roles & Access
●	Student: Can browse mentors, book sessions, chat, submit feedback, and create support tickets.
●	Mentor: Can manage profile & office hours, write private notes on students, view own bookings & chat, and receive feedback, set their calendar for when they can have meetings.
●	Admin: Has full oversight – manages users, institutions, moderates content, and handles support tickets. Can do everything from creating things on the platform to getting rid of anything.

3.	Total Modules (10)
1.	Authentication & User Management
2.	Dashboard / Find Mentors & Explore Mentors
3.	Institutions / Universities Management
4.	Office Hours Module
5.	Feedback & Reviews Module
6.	Mentor Notes on Users (Highly Sensitive - ONLY MENTORS and ADMIN CAN VIEW)
7.	Bookings & Session Management
8.	Support Tickets Module
9.	Settings & Profile Management
10.	Payments & Credits System

4.	Development Milestones (3 Phases) Milestone 1: Admin Side + Core Foundation Focus: Backend foundation and admin tools
●	Authentication & Role Management
●	Institutions CRUD (Program Tiers & Types)
●	Global configurations
●	Support ticket management for admin
 
●	Basic analytics and moderation

Deliverables: Working auth system, admin dashboard, institutions module.

Milestone 2: Mentor Side
Focus: Tools for mentors

●	Mentor Profile Management
●	Office Hours setup with rotation logic
●	Mentor Notes on Users (private & secure)
●	View own bookings, chat, and feedback

Deliverables: Mentor dashboard, office hours, and secure notes system.

Milestone 3: Student Side + Full Integration
Focus: Student experience and complete platform

●	Find Mentors & Dashboard (search, filters, credits)
●	Explore by University
●	Office Hours Booking
●	Bookings Calendar, Real-time Chat & Video Links
●	Feedback System
●	Support Tickets
●	Settings & Payments/Credits

Deliverables: Full student flow, real-time features, payment integration, and polished UI-backend connection.

Module 1: Authentication & User Management
Purpose This is the foundational module of the platform. Without it, no user (Student, Mentor, or Admin) can access Grads Paths. It handles user registration, login, role assignment, and secure authentication.
How It Works (Flow)
1.	User visits the website and clicks on Signup.
2.	They provide Program Level: Undergrad OR Grad OR Professional - they also Provide position they want Student OR Mentor - They provide their full name, their .edu email (unless they are a professional (program level), their Institution they go to or went to, then they create a password (or use Google sign-in - but info stills need to be selected).
3.	Backend uses Laravel 12 authentication and application services to create the account.
4.	If a user wants to become a Mentor they select in the “I am a” section the press “Mentor.” Then they have to do email authentication and then they enter the Password given from the admin.
 
(workflow continues see “mentor_sign_up.html” and “user_sign_up.html” for specific workflow) this is an automatic system that does not need approval.
5.	On successful login, Laravel authenticates the user and establishes the appropriate session or API authentication context for subsequent requests.
6.	Backend checks the authenticated user and user role on every protected request.

Main Features
●	User Signup (Program Level, Position a.Student b.Mentor, Full Name, .edu email, Institution + Social Login)
●	Login / NO Logout - no need to have one
●	Forgot Password & Reset Password
●	Role Management (Student, Mentor, Admin)
●	Mentor Approval Workflow (Completely Automatic - Mentor Given Password: EvertoExcel123!
●	Basic Profile Creation on Signup

CRUD Operations
●	Create: New user registration with the selected “student or mentor” role
●	Read: Fetch user details during login and profile access
●	Update: Basic profile information (name, avatar, etc.) - ONLY MENTORS CAN DO - OR MANUAL DONE BY ADMIN in the Admin Dashboard
●	Delete: Account deletion (soft delete recommended)

User Roles & Access
●	Student: Can create account and login
●	Mentor: Can signup and enter the Admin Given Password for Admin approval in creating there account
●	Admin: Highly restricted — only existing admins can create new admin accounts

Module 2: Find Mentors & User Dashboard
Purpose This module serves as the main entry point and marketplace interface for students. It allows students to discover mentors through a curated "Mentors of the Week" section, advanced search, and multiple filters.
How It Works (Flow)
1.	After successful login, the student lands on the Website like normal but now they can access the “Find Mentors Dashboard” must be logged in to access.
2.	The backend fetches "Mentors of the Week" — a curated list of high-rated mentors marked as is_featured = true. (Highest ratings “5” ex: 4.8/5 highest, then (if there are duplicates go by most meetings with that highest rating)
3.	For each mentor card, the backend provides complete data including name, title, university, office hours, services offered, rating, and a snippet of recent feedback.
 
4.	The student can search by mentor name/keyword or apply filters such as Mentor Type (Graduates/Professionals), Program Type (MBA, Law, Therapy), and School.
5.	When the student clicks the "Book Now" button on any mentor card:
o	The backend (not sure if backend is needed here) takes users to the “Book with Mentor” view to book a meeting
o	If Office Hours Service is selected and spots are available for booking use 1 credit from the user (backend check if there are credits in that users account if not prompt users to buy more credits found in Store in the top right of there screen)
o	Once a meeting is selected and time and date are selected and the service is paid for (create a notification for user and mentor about the meeting (user and mentor can both see upcoming meetings they have in there bookings module. In that booking module there is a chat feature between Mentor and user to contact before the meeting. Meetings must be made 24 hours in advance
6.	Clicking "Read More" fetches the mentor’s full profile and complete recent feedback from the backend.
7.	The user’s current credit balance (e.g., "Credits: 16") is displayed in real-time in the top-right corner and updates automatically after any credit-related action.
Main Features
●	Mentors of the Week curated section
●	Advanced search and multi-criteria filtering
●	Rich mentor cards displaying profile info, office hours, services, rating, and recent feedback
●	Real-time credit balance display
●	"Book Now" button with credit validation
●	"Read More" functionality for detailed mentor profile
●	Light/Dark mode preference toggle
CRUD Operations
●	Create: Not applicable (only admin can mark mentors as featured)
●	Read: Heavy usage — fetching featured mentors, search results, filtered lists, and full mentor profiles
●	Update: Only user theme preference (Light/Dark mode)
●	Delete: Not applicable
User Roles & Access
●	Student: Full access — can view, search, filter, and initiate booking
●	Mentor: Can view their own card but cannot book
●	Admin: Can manage featured mentors and view all data
Important Backend Logic
 
●	Featured mentors are fetched using the is_featured flag in the mentors table.
●	Credit validation is performed before allowing any booking action.
●	Efficient database queries with proper indexing on name, university, and rating for fast search and filtering.
●	Real-time credit balance is maintained using Laravel-backed updates, broadcasting where needed, or on-demand fetching.
●	Partial content loading (snippets) with "Read More" for better performance.
Database Tables Involved
●	mentors – Main table containing mentor profile, office hours, rating, and featured status
●	feedback – For displaying recent feedback snippets
●	user_credits – Real-time credit balance
●	user_settings – For saving Light/Dark mode preference
Module 3: Institutions / Explore by University Module
Purpose This module allows students to discover mentors through universities.
How It Works
●	Students can filter universities using Program Tier (All, Elite Programs, Top 25 Programs, Regional Programs) and Program Type (All, MBA, Law, Therapy).
●	A search bar allows searching universities by name.
●	Each university card displays relevant tags such as Top Rated, Multiple Tiers, MBA, Law, or Therapy.
●	When a student clicks on a university, the system shows available graduate programs for that university.
●	From there, students can navigate to view the filtered list of mentors associated with that university and its programs.

Backend Technologies Used
●	Laravel 12 – Handles filtering logic, business rules, and API/web endpoints
●	PostgreSQL or MySQL – Stores universities, program tiers, and relationships
●	Dynamic Query Building – Backend constructs SQL queries based on selected filters (tier, program_type, search term)
●	JOIN Queries – Used to fetch universities along with their associated programs and mentor count
●	Indexing – Applied on university name, tier, and program_type columns for fast search and filtering

Main Features
●	Advanced filtering by Program Tier and Program Type
●	University search functionality
●	University grid with informative tags
 
●	Clickable universities leading to program-specific mentor lists
●	Smooth navigation from university → programs → mentors

CRUD Operations
●	Read: Heavy usage for fetching filtered universities and programs
●	Create / Update / Delete: Managed by Admin (adding new universities, updating tiers and programs)

Database Tables Involved
●	universities
●	university_programs
●	mentors (linked with universities)

Module 4: Office Hours Module (Dynamic Office Hours Engine)
Purpose The Office Hours Module manages recurring group mentoring sessions where a maximum of 3 students can participate in a single session. It provides mentors with a flexible way to offer weekly or bi-weekly sessions while incorporating smart automation and conditional business logic to create a dynamic and fair booking experience.
How It Works
Mentors get paid for this (per person signed up for the meeting) - Every person signed up means the mentor gets 15 dollars each (take from platform stripe account) - User pays for the office hours subscription $200 → from there the mentor gets paid 15 dollars per person on the meeting (e.g. ⅔ slots filled = $200 - $30 = $170 platform, $30 Specific Mentor
1.	Mentor Schedule Setup Mentors define their recurring schedule (e.g., Every Tuesday at 5 PM EST) and list of services they can offer (such as Tutoring, Program Insights, Interview Preparation, etc.). They also select the rotation frequency — Weekly ONLY.
2.	Automated Rotation Engine The system automatically rotates the service for each session.
o	A background task (CRON job or Laravel scheduler / queued job) runs at the beginning of every week.
o	It moves to the next service in the mentor’s predefined list and updates the current_service.
o	This eliminates the need for mentors to manually change the session topic every week.
3.	Real-time Spot Tracking System
o	Each session has a fixed capacity of 3 spots.
o	The backend maintains current_occupancy (number of students who have booked).
o	When students view the session, the backend calculates remaining spots and returns the status.
o	If all 3 spots are filled (current_occupancy == 3), the system sends is_full: true, so the frontend displays a disabled "Session Full" button.
4.	First Student Choice Logic (Unique Conditional Feature)
 
o	If there is only 1 slot filled within the 24 hours till the meeting then the 1 user can change the (Booking service) to a different option that the mentor still offers. books a session (current_occupancy == 1), they receive the option to choose any service from the mentor’s list.
o	24-Hour Cutoff Rule: The only student in the meeting can change or select the service only if the booking has only 1 slot selected total and it has to be within less than <24 hours till the meeting time.
o	The only user that has selected the meeting within less than <24 hours till the meeting time gets a pop up to choose the desired meeting they want because they are the only one in the meeting. The user must complete this within 12 hours of the meeting time to guarantee the Mentor is prepared to help with that service.
o	If second student books the same session, the predetermined service for that week becomes locked. After locking, neither the first nor any subsequent student can change the service.
5.	Booking & Credit Flow
o	Student clicks "Book Now".
o	Backend performs a credit check. If insufficient credits then there will be a prompt letting the user know and prompting them to buy credits from the top right button “Store”
o	If sufficient credits are available, the spot is reserved, current_occupancy is incremented by 1, and credits are deducted.
o	The system returns appropriate service options based on whether the student is the first booker and whether the 24-hour window is still open.
Main Features
●	Support for Weekly and Biweekly service rotations
●	Fully automated service rotation using background tasks
●	Real-time spot tracking (0/3, 1/3, 2/3, 3/3)
●	Intelligent "First Student Choice" with 24-hour cutoff
●	Automatic service locking after the second booking
●	"Session Full" status handling
●	Seamless integration with the Credits system

CRUD Operations
●	Create: Mentor sets up recurring schedule, services, and rotation type
●	Read: Students fetch available sessions with current rotation, spots, and service status
●	Update: Increment/decrement occupancy, change service (restricted to first student within cutoff), reschedule sessions
●	Delete: Cancel booking (decrements occupancy and triggers credit refund)

User Roles & Access
●	Student: Can view sessions, book spots, and select service if they are the first booker (within 24 hours)
●	Mentor: Can create and manage their recurring schedule and rotation settings
 
●	Admin: Has full visibility and can manage office hours if require

Module 5: Feedback & Reviews Module
Purpose The Feedback & Reviews Module acts as the social proof and trust engine of the platform. It collects verified student feedback after sessions and displays it in a structured,
Key Concepts & How It Works (Detailed Flow)
1.	Top Stats Aggregation (The "Brain" of the Page) The top statistics boxes are not static text — they are powered by real-time backend calculations:
o	Average Rating (e.g., 4.9): Backend runs AVG(stars) query across all reviews for a mentor or globally.
o	Recommendation Rate (e.g., 96%): Backend counts reviews where recommend = true and divides it by total reviews.
o	Most Mentioned Tag (e.g., “Clear advice”): Backend performs frequency analysis on comment text to identify the most commonly used keywords/phrases.
2.	Nested Review System (Card Structure) Each review card has a two-level structure:
o	Level 1 (Mentor Overview): Shows mentor name and overall rating.
o	Level 2 (Individual Reviews): Displays short previews of student reviews (e.g., “Institution student” like: “Harvard University Student” (Admin will be able to see the name of the users account and there info in the admin dashboard. - Feb 15, 2026”).
o	When the page loads, the backend uses JOIN queries to fetch the mentor information along with all of the mentor's reviews.
o	Clicking the "View" button on a review triggers a backend call to fetch the full comment
using the review_id, which is then shown in a modal.
3.	Dynamic Filters & Sorting (GET Requests) Users can filter reviews using dropdowns and search:
o	Filters include: Program Type (MBA, Law, Therapy), Mentor Type, “My Mentors Only”, “Highest Rating” etc.
o	Sorting options: Most Recent, Highest Rated, Most Helpful.
o	Backend builds dynamic SQL queries based on selected filters. For example:
▪	WHERE program_type = 'MBA'
▪	ORDER BY stars DESC for Highest Rating
4.	Contextual "Book Now" Action Every review card includes a "Book Now" button.
o	This button passes the mentor_id and relevant office_hours_id (if available) to the Bookings module. - This takes users to the book session module where they can select specific services, times, and then pay for the meeting.
o	The goal is to convert trust (from good reviews) into immediate action (booking).
5.	Real-time Updates
o	As soon as a new feedback is submitted, the Top Stats (Average Rating, Recommendation Rate, etc.) are recalculated and updated in real-time across the platform.
o	This can be achieved using Laravel broadcasting, queued events/listeners, or database triggers where needed.

Main Features
 
●	Real-time statistical aggregation (Average Rating, Recommendation Rate, Top Tags)
●	Nested review cards with preview and full view
●	Advanced dynamic filtering and sorting
●	Verified Booking badge on every review
●	Contextual “Book Now” button on review cards
●	Pagination or Infinite Scroll support
●	Option for mentors to reply to reviews (if enabled)

CRUD Operations
●	Create (POST): Student submits feedback after a completed session (stars, comment, recommend toggle)
●	Read (GET): Fetch aggregated stats + filtered and sorted reviews with JOINs
●	Update (PATCH): Mentor can reply to a review (optional feature)
●	Delete: Admin can remove spam or inappropriate reviews

User Roles & Access
●	Student: Can submit feedback (only for their completed sessions) and browse all public reviews
●	Mentor: Can view feedback received on their sessions and reply to reviews
●	Admin: Can moderate all reviews (delete or hide inappropriate content) - as well as see users info and there comments

Important Backend Logic
●	Verification Check: Feedback can only be submitted if the associated booking status is “Completed”
●	Aggregation Queries: Efficient calculation of averages and percentages (can use materialized views for better performance)
●	Keyword Analysis: Simple frequency count or basic NLP for “Most Mentioned Tag”
●	Dynamic Query Building: Backend safely constructs queries based on user-selected filters to prevent SQL injection
●	Real-time Propagation: New reviews instantly update dashboard statistics

Database Tables Involved
●	feedback — Core table (mentor_id, student_id, rating, comment, recommend, service_type, booking_id, created_at)
●	mentor_ratings — Optional aggregated table or view for fast stats retrieval


6.	Mentor Notes on Users (Highly Sensitive)
Purpose This module serves as a secure internal reporting and privacy-focused system. It allows mentors to create, store, and track private notes about their students’ progress, strengths, weaknesses, and session history.
 
How It Works (Detailed Flow)
1.	Secure Role-Based Access (RBAC)
o	This is the most critical part of the module.
o	Backend first verifies the authenticated user context and checks the user’s role.
o	Only users with Mentor or Admin role are allowed to access this module. Students/Users get zero access.
o	Using Laravel authorization policies, guards, and scoped queries, a mentor can only read notes where mentor_id matches their own ID.
o	This ensures a mentor cannot see notes written by other mentors.
2.	Nested Data Structure (One-to-Many Relationship)
o	One student can have multiple notes from the same or different mentors.
o	Backend uses JOIN queries between two tables:
▪	users table (for student basic info like name and avatar)
▪	mentor_notes table (for actual notes, session dates, and content)
o	When the page loads, the backend fetches students along with their associated notes, sorted by created_at DESC (newest first).
o	Each student card shows a Note Counter (e.g., “3 Notes”) using the COUNT() function.
3.	Search & Filter Mechanism
o	There are two search bars at the top:
▪	Search by User: Filters students by name (e.g., typing “Tyl” shows “Tyler Cogan”).
▪	Search by Mentor: Shows notes written by a specific mentor.
o	Backend uses the ILIKE operator for partial, case-insensitive matching.
o	Dual search logic is implemented so the query focuses on either student_id or mentor_id depending on the search type.
4.	"View" & "Read More" Interaction
o	For performance reasons, the initial card view only shows a short snippet (first 100–150 characters) of each note.
o	When the mentor clicks the "View" button, a separate backend request is made using the note_id to fetch the full note content, which is then displayed in a modal.
o	All the reviews for that user are always there and visible (unless deleted by admin)

Main Features
●	Strict privacy and role-based access control
●	Student cards with initials/avatar and note counter
●	Multiple notes per student (One-to-Many relationship)
●	Dual search functionality (by User and by Mentor)
●	Short snippet preview with full note view in modal
●	Notes sorted by date (newest first)
●	Soft delete functionality (Admin only)

CRUD Operations
 
●	Create (POST): Mentor creates a new note after a session (student_id, mentor_id, session_date, note_body)
●	Read (GET): Fetch student list with their notes (with search, filter, and pagination)
●	Update (PATCH): Mentor can edit their own notes
●	Delete: Soft delete by Admin only (record remains in database for backup)

User Roles & Access
●	Student: No access whatsoever to this module
●	Mentor: Can create, view, and edit only their own notes for students they have worked with
●	Admin: Full access to all notes and can perform soft delete

Important Backend Logic
●	RBAC: Heavy security layer — authenticated access, authorization policies, and scoped database queries
●	Performance Optimization: Short snippets on list view, full content only on "View" request
●	Search Efficiency: ILIKE operator with proper indexing on name fields
●	Data Grouping: Notes are grouped by student_id for clean card display

7.	Bookings & Session Management Module (Most Critical)
Purpose This is the central operational hub of the entire platform. It manages all confirmed mentoring sessions, coordinates communication between students and mentors, handles calendar visualization, generates virtual meeting links, enables real-time chat, and processes cancellations with credit refunds.
Key Concepts & How It Works (Detailed Flow)
1.	Single Source of Truth (Database Schema) When a user books a session, the backend creates a record in the bookings table. This table acts as the Single Source of Truth and is linked to multiple other tables:
o	users table → Student who is booking
o	mentors table → Mentor (e.g., Emily Carter)
o	services table → Booked service (e.g., "Office Hours" – locked after booking) - all services that each mentor offers (different for each mentor)
o	slots / office_hour_sessions table → Specific date and time (e.g., April 9, 2026 at 11:00 AM)
o	MEETING MUST BE PAID FOR BY USER BEFORE THE OFFICIAL MEETING IS CREATED HERE - BUT YES (USE STRIPE)
2.	Calendar Sync Logic (Visualization)
o	When the Bookings page loads, the backend sends an array of booked dates (e.g., ["2026-04-09", "2026-04-24", "2026-05-06"]).
o	Frontend highlights these dates on the calendar (usually in purple).
 
o	When a user clicks on a highlighted date, the backend filters and returns only the appointments for that specific date to populate the "Upcoming Appointments" list below.
3.	Virtual Meeting & Cancellation Logic
o	Meeting Link Generation: As soon as a booking is confirmed, the backend triggers the Zoom API to generate a unique meeting URL. This URL is stored in the bookings table and shown to both student and mentor as a "Join Meeting" button.
o	Cancellation & Refund:
▪	When the user clicks "Cancel Meeting", the backend checks the time remaining until the session (e.g., 24-hour cancellation policy).
▪	If cancellation is allowed, the backend:
▪	Changes the booking status to cancelled
▪	Adds the credit back to the student’s user_credits table using an
atomic transaction
▪	This ensures no double-spending or lost credits.
4.	Real-time Chat System
o	Chat becomes available when the meeting is booked and paid for (could be a month in advance, could be 3 days before - i am not picky, but i want them to be able to communicate). And i want to be able to monitor these communications
- also it would be great to emphasize here that this is the official place to communicate with mentors so as not to go outside of the platform to do this
o	The system uses Laravel broadcasting / WebSockets so messages appear instantly.
o	When a user sends a message:
▪	Backend saves it with sender_id, receiver_id, booking_id, and timestamp.
o	Frontend subscribes to the messages table for that specific booking to receive live updates.
o	The "Chat Available" badge is shown only when the backend confirms the booking is confirmed and the session time is approaching.
Main Features
●	Interactive calendar with highlighted booked dates
●	Upcoming Appointments list (auto-sorted by time)
●	Dynamic Zoom meeting link generation and storage
●	Real-time chat (24–48 hours before session)
●	Cancellation with intelligent refund logic
●	Service lock after successful booking
●	Automatic movement of past sessions to History
 
CRUD Operations
●	Create: New booking record creation (from Office Hours or direct booking)
●	Read: Calendar dates array, upcoming appointments, chat history
●	Update: Reschedule (if allowed), update booking status
●	Delete / Cancel: Cancel booking + trigger credit refund
User Roles & Access
●	Student: Can view own bookings, access chat and meeting link, cancel sessions
●	Mentor: Can view all their booked sessions, access chat and meeting link
●	Admin: Full visibility over all bookings
Important Backend Logic
●	Atomic Transactions: Credit deduction and refund are handled atomically to prevent errors
●	Time-based Checks: 24–48 hour window for chat availability and cancellation policy
●	Meeting Link Security: Links are only visible to authenticated participants of that booking
●	Real-time Subscription: Laravel broadcasting / WebSockets are used for instant chat delivery
Recommended Tools / Technologies
●	Meeting Link: Zoom API
●	Calendar Sync: Database date queries + Laravel services
●	Credit Refund: Atomic transactions via Laravel services and database transactions
●	Real-time Chat: Laravel broadcasting / WebSockets
Database Tables Involved
●	bookings — Main table (student_id, mentor_id, slot_id, service_type, meeting_link, status, created_at)
●	chats — Real-time messages (booking_id, sender_id, receiver_id, message_text, timestamp)
●	user_credits — For credit balance and refunds
●	slots / office_hour_sessions — Date and time information
8.	Support Tickets Module - same as the place near the bottom on the website
Purpose The Support Tickets Module acts as a bridge between users and the admin team. It allows students and mentors to report issues, ask questions, or seek help regarding bookings, payments, technical problems, or any other platform-related concerns. This module helps improve user retention by providing an organized and responsive support system.
 
Key Concepts & How It Works (Detailed Workflow)
1.	Data Submission (POST Request)
o	When a user clicks "Send Message" on the Support page, a POST request is sent to the backend.
o	Endpoint: /api/v1/support/tickets
o	Payload includes: name, email, subject, message, and automatically attached user_id from the authenticated Laravel user.
o	Backend validates the data and saves the ticket into the support_tickets table with a unique Ticket ID (e.g., #SUP-102 → could this be like this? “Support ticket #01” so i can have the order).
2.	Automated Triggers & Email Notifications
o	As soon as the ticket is successfully saved, the backend automatically triggers two emails:
▪	To User: Confirmation email — “Hi Mike, we've received your ticket #102. Our team will get back to you shortly.”
▪	To Admin: Alert email / push notification — “New support ticket received from [User Name] – Subject: [Subject]”
o	Emails are sent using Resend, SendGrid, or SMTP service via background tasks.
3.	Admin Management Logic
o	Admins have a dedicated dashboard where all tickets are listed.
o	Each ticket shows status: Open → In Progress → Resolved.
o	Admin can reply to the ticket, update its status, and the response is saved in the database so the user can see the update in their dashboard.
4.	Security & Validations (Critical)
o	Rate Limiting: Maximum 3–5 tickets per user per hour to prevent spam or abuse.
o	Input Sanitization: All user messages are sanitized to prevent XSS (Cross-Site Scripting) attacks.
o	Character Count Validation: Backend checks that the message is not empty and respects the allowed character limit.
o	User Identification: user_id is automatically taken from the authenticated Laravel user context (frontend does not need to send it).
o	Make it possible for me to respond on the same email as the confirmation email that the user or mentor gets sent - and make it so that the admin team can also see the emails in that confirmation email - sometimes people add more info after they send the first message - this keeps everything nice and together
Main Features
●	Simple and clean ticket creation form
 
●	Automatic unique Ticket ID generation
●	Dual automated email notifications (User + Admin)
●	Ticket status tracking (Open, In Progress, Resolved)
●	Admin reply functionality
●	Rate limiting and spam protection
●	Message sanitization for security
CRUD Operations
●	Create (POST): User creates a new support ticket
●	Read (GET):
o	Users can view their own tickets
o	Admins can view all tickets
●	Update (PATCH): Admin can update status and add replies
●	Delete: Usually archived (soft delete optional)
User Roles & Access
●	Student & Mentor: Can create tickets and view their own ticket history
●	Admin: Full access — can view all tickets, update status, and reply
Important Backend Logic
●	Automatic user_id attachment from the authenticated user context
●	Background jobs for sending emails
●	Rate limiting to prevent abuse
●	Secure input validation and sanitization
Database Tables Involved
●	support_tickets — Main table (Columns: id, ticket_id, user_id, subject, message, status, admin_reply, created_at, updated_at)
9.	Settings & Profile Management Module
Purpose This module enables users and mentors to manage their public profile, personal information, media assets, and financial payout settings. Changes made here directly reflect across the platform, especially on the Find Mentors page and mentor cards. It is particularly important for mentors as it controls their professional identity and payment configuration. NOT FOR USERS → ONLY MENTORS
Key Concepts & How It Works
1.	Profile & Media Management (The CRUD Foundation)
 
o	When a mentor clicks "Save Changes", a PATCH request is sent to update the mentors table.
o	Image Upload: The uploaded image is stored using Laravel storage. The new image URL is updated in the database, and the old image is automatically deleted for storage optimization.
o	Real-time Preview: The right-side "What Users Will See" card shows a live preview using frontend state. On page refresh, the backend fetches the latest saved data to ensure accuracy.
2.	Validation & Security (The Gatekeeper)
o	Mentor Type Validation: If mentor_type is set to "Grad Mentor", the backend strictly checks that the email ends with .edu. If not, an error is returned.
o	Meeting Provider Validation: This will be fully in-house and will use Zoom API for meeting creation. If mentors need to connect a Zoom account or provide Zoom-related configuration, the backend validates and stores the required connection data so broken meeting links are not shown to students.
o	All inputs are sanitized to protect against security vulnerabilities.
3.	Stripe Connect Integration
o	Clicking the "Enable Payouts" button triggers the Stripe Connect Express API.
o	The backend generates a unique Stripe Account ID and returns an onboarding link.
o	The mentor is redirected to Stripe’s hosted page to enter their personal and banking details (SSN, bank account, date of birth, etc.).
o	Once the mentor completes onboarding, Stripe sends a webhook to the backend.
o	The backend then updates the mentor’s status to payouts_enabled: true, and the frontend label changes from "Not enabled" to "Enabled". ALSO ALLOW FOR MAKING CHANGES TO THIS IF ANY OF THERE INFO CHANGES
4.	Community Integration
o	Slack Integration: The backend stores a Slack invite link (fixed or dynamically generated via Slack API).
o	An optional slack_joined boolean flag can track whether the mentor has joined the community Slack.
Main Features
●	Comprehensive profile editing (name, bio, office hours, description, etc.)
●	Secure profile image upload with automatic old image cleanup
●	Real-time preview of public profile
●	Strict validation rules (especially .edu email for Grad Mentors)
●	Calendly and Slack link management
●	Full Stripe Connect onboarding for receiving payouts
●	Light/Dark mode and user preference settings
 
CRUD Operations
●	Create: Initial profile setup when a mentor is approved
●	Read: Fetch current profile and settings data
●	Update (PATCH): Update profile information, image, links, and preferences
●	Delete: Rarely used (account-level deletion)
User Roles & Access
●	Student: Can edit basic profile and theme preferences
●	Mentor: Has full access to profile editing, image upload, external links, and Stripe Connect setup
●	Admin: Can view and moderate mentor profiles when necessary
Important Backend Logic
●	Atomic operations for image upload, URL update, and old image deletion
●	Strong validation for mentor type and email domain
●	Stripe webhook listener to detect completed onboarding
●	Real-time synchronization so profile changes appear instantly on public pages
Database Tables Involved
●	mentors — Core table for mentor profile (full_name, bio, image_url, office_hours, payouts_enabled, calendly_link, slack_link, etc.)
●	user_settings — Stores user preferences such as Light/Dark mode
10.	Payments & Credits System
Purpose This module handles the complete financial flow of the platform. It manages virtual credits for students, processes payments, deductions, refunds, and enables payouts for mentors.
Key Concepts & How It Works
1.	Credit Balance Management Every user has a virtual credit balance (displayed as "Credits: 16" in the top right). The backend maintains this balance in real-time and updates it across all pages.
2.	Credit Purchase Students can buy credits through the "Store" button. The backend creates a Stripe Checkout session. After successful payment, Stripe sends a webhook, and the user’s credit balance is increased.
3.	Credit Deduction When a student books a session, the backend checks the available credits. If sufficient, it deducts the required credits using an atomic transaction before confirming the booking.
4.	Credit Refund On booking cancellation (subject to policy, e.g., 24-hour rule), the backend automatically adds the credit back to the student’s balance and marks the booking as cancelled.
 
5.	Mentor Payouts Mentors receive earnings through Stripe Connect. Once payouts are enabled in Settings, mentors can withdraw their earnings to their bank account via Stripe.

Main Features
●	Real-time credit balance display
●	Secure credit purchase via Stripe
●	Automatic credit deduction on booking
●	Automatic refund on eligible cancellations
●	Mentor payout system using Stripe Connect
●	Atomic transactions for financial safety

CRUD Operations
●	Read: Current credit balance
●	Update: Increment (purchase/refund) and decrement (booking) of credits

User Roles & Access
●	Student: Buy credits, view balance, receive refunds
●	Mentor: Receive payouts through Stripe Connect
●	Admin: View overall financial transactions and analytics

Important Backend Logic
●	Atomic transactions to prevent credit errors
●	Stripe webhooks for payment and payout confirmation
●	Negative balance prevention

Database Tables Involved
●	user_credits — Stores current credit balance
●	credit_transactions — Records all transactions (purchase, deduction, refund)

7.	Backend Working Summary (How Everything Connects)
• Core Data Flow (End-to-End User Journey)

1.	Student logs in → Lands on Dashboard or Find Mentors page.
2.	Searches and applies filters → Backend returns matching mentors with ratings, availability, and recent feedback.
3.	Student explores by University (Institutions Module) or directly selects a mentor.
4.	Clicks “Book Now” → Backend performs credit check → Bookings Module creates a booking record and deducts credits (Payments Module).
5.	Session is scheduled → Student and mentor get access to calendar, real-time chat, and video meeting link.
 
6.	Session completes → Student submits feedback (Feedback Module) → Mentor can write private progress notes (Mentor Notes Module).
7.	If any issue occurs → User creates a support ticket (Support Tickets Module).

Key Connections Between Modules

●	Payments & Credits System is tightly integrated with almost every action — it deducts credits on booking, refunds credits on cancellation, and adds credits when purchased from the Store.
●	Bookings Module serves as the central operational hub. It receives requests from Find Mentors and Office Hours, manages calendar, generates meeting links, enables real-time chat, and handles cancellations with refunds.
●	Institutions Module feeds university-based filtered data into the Find Mentors flow.
●	Settings Module updates mentor profiles that are displayed on Find Mentors and Dashboard pages.
●	Feedback Module pulls data from completed bookings and shows verified reviews publicly.
●	Mentor Notes Module is completely private and linked only to specific student-mentor relationships.
●	Support Module operates independently but remains accessible to all users for issue resolution.

Core Backend Principles

●	All modules communicate through well-defined Laravel routes, controllers, services, and REST-style APIs where needed.
●	Security is enforced using Role-Based Access Control (RBAC)
●	Financial operations use atomic transactions to ensure data consistency.
●	Real-time features (chat, spot updates, notifications) are powered by Laravel broadcasting, WebSockets, and queued events where needed.
●	The system maintains a clear separation of concerns while keeping strong connections between related modules.
