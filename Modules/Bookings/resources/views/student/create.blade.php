<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>dashboard</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
  />

  <link rel="stylesheet" href="{{ asset('assets/css/demo11.css') }}" />
   <script>
      (function () {
        const savedTheme = localStorage.getItem("theme") || "light";
        document.documentElement.setAttribute("data-theme", savedTheme);
      })();
    </script>
</head>
 <body>
    <div class="app-shell">
      <div class="sidebar-overlay" id="sidebarOverlay"></div>
      <aside class="sidebar">
        <div class="sidebar-top">
          <div class="brand">
            <div class="brand-icon">GP</div>
            <div class="brand-copy">
              <div class="brand-title">Grads Paths</div>
              <div class="brand-subtitle">STUDENT PORTAL</div>
            </div>
          </div>

          <a href="https://grads-path.vercel.app/" class="back-link">
            <span class="back-link-arrow">←</span>
            <span>Back to the Website</span>
          </a>
        </div>

        <nav class="sidebar-nav">
          <div class="nav-group">
            <a href="/student/dashboard" class="nav-item single-link active">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9.5" cy="7" r="3"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a3 3 0 0 1 0 5.74"></path>
                  </svg>
                </span>
                <span class="nav-text">Dashboard</span>
              </span>
            </a>
          </div>

          <div class="nav-group">
            <a href="/student/institutions" class="nav-item single-link">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l7-4 7 4v14"></path>
                    <path d="M9 9h.01"></path>
                    <path d="M9 13h.01"></path>
                    <path d="M9 17h.01"></path>
                    <path d="M15 9h.01"></path>
                    <path d="M15 13h.01"></path>
                    <path d="M15 17h.01"></path>
                  </svg>
                </span>
                <span class="nav-text">Institutions</span>
              </span>
            </a>
          </div>

          <div class="nav-group">
            <a href="/student/mentors" class="nav-item single-link">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M4 6.5h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 17.5h16"></path>
                  </svg>
                </span>
                <span class="nav-text">Find Mentors</span>
              </span>
            </a>
          </div>

          <div class="nav-group">
            <a href="/student/office-hours" class="nav-item single-link">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                    <path d="M16 3v4"></path>
                    <path d="M8 3v4"></path>
                    <path d="M3 11h18"></path>
                  </svg>
                </span>
                <span class="nav-text">Office Hours</span>
              </span>
            </a>
          </div>

          <div class="nav-group">
            <a href="/student/feedback" class="nav-item single-link">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path
                      d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                    ></path>
                  </svg>
                </span>
                <span class="nav-text">Feedback</span>
              </span>
            </a>
          </div>

          <div class="nav-group mentor-only">
            <a href="/student/mentor-notes" class="nav-item single-link">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M12 20h9"></path>
                    <path
                      d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                    ></path>
                  </svg>
                </span>
                <span class="nav-text">Mentor Notes on Users</span>
              </span>
            </a>
            <div class="helper-note">Only visible to verified mentors</div>
          </div>

          <div class="nav-group">
            <a href="/student/bookings" class="nav-item single-link">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <path d="M16 2v4"></path>
                    <path d="M8 2v4"></path>
                    <path d="M3 10h18"></path>
                  </svg>
                </span>
                <span class="nav-text">Bookings</span>
              </span>
            </a>
          </div>

          <div class="nav-group">
            <a href="/student/support" class="nav-item single-link">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 3-3 3"></path>
                    <path d="M12 17h.01"></path>
                  </svg>
                </span>
                <span class="nav-text">Support</span>
              </span>
            </a>
            <div class="helper-note">Create a support ticket</div>
          </div>

          <div class="nav-section-label">Settings</div>

          <div class="nav-group">
            <a href="/student/settings" class="nav-item single-link">
              <span class="nav-left">
                <span class="nav-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <circle cx="12" cy="12" r="3"></circle>
                    <path
                      d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"
                    ></path>
                  </svg>
                </span>
                <span class="nav-text">Settings</span>
              </span>
            </a>
            <div class="helper-note">
              Users and mentors can update profile details and displayed
              information
            </div>
          </div>
        </nav>
      </aside>

      <main class="main-content">
        <header class="shell-topbar">
          <div class="shell-topbar-left">
            <button
              class="mobile-menu-toggle"
              id="mobileMenuToggle"
              type="button"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
              </svg>
            </button>
            <div class="search-wrap">
              <input
                type="text"
                class="search-input"
                placeholder="Search mentors, universities..."
              />
            </div>
          </div>
          <div class="shell-topbar-right">
            <button class="theme-toggle" id="themeToggle" type="button">
              Light / Dark
            </button>
            <div class="credits-box">Credits: <strong>16</strong></div>
            <a href="/student/store" class="store-btn">Store</a>
          </div>
        </header>
       <div class="page-shell">
    <header class="booking-topbar">
      <div class="booking-topbar-left">
        <button class="back-arrow-btn" type="button" aria-label="Go back">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15.5 19 8.5 12l7-7" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <div class="title-block">
          <p class="step-kicker">Step 2</p>
          <h1>Book with Mentor</h1>
        </div>
      </div>

      <div class="booking-topbar-right">
        <a href="/student/dashboard" class="back-dashboard-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Back to Dashboard</a>

        <div class="credit-pill" aria-label="Available credits">
          <span>Credits:</span>
          <strong>16</strong>
        </div>

        <button class="store-btn" id="openStoreBtn" type="button">Store</button>
      </div>
    </header>

    <main class="booking-layout">
      <section class="left-column">
        <div class="mentor-card">
          <div class="mentor-top">
            <div class="mentor-icon-wrap">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 4v14M6 8l6-3 6 3M7 8v4m10-4v4M5 18h14M4 12h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>

            <div class="mentor-main">
              <h2>Daniel Ross</h2>
              <p>Law • Yale Law School</p>
            </div>

            <div class="mentor-rating">
              <span>★</span>
              <strong>5.0</strong>
            </div>
          </div>

          <p class="mentor-description">
            I help with law school applications, personal statements, and 1L transition advice.
          </p>

          <button class="read-more-btn" type="button">Read More ▼</button>
        </div>

        <div class="selection-card" id="selectionCard">
          <div class="section-title-row">
            <button class="small-back-arrow" type="button" aria-label="Go back to previous section">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M15.5 19 8.5 12l7-7" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <h3>Type of Session</h3>
          </div>

          <div class="service-grid" id="serviceGrid"></div>

          <div class="meeting-section" id="meetingSection">
            <h3>Meeting Size</h3>
            <p class="helper-text" id="meetingHelperText">
              Choose whether this is an individual booking or a small group request.
            </p>

            <div class="meeting-size-grid" id="meetingSizeGrid"></div>

            <div class="special-request-note" id="specialRequestNote" hidden>
              Group meetings are by <strong>special request</strong>. Only one person can pay for the meeting.
              If you want to split the cost, you must handle that separately yourselves.
            </div>

            <div class="group-fields" id="groupFields" hidden>
              <div class="group-pay-note">
                One applicant must submit payment for the full meeting.
              </div>
              <div class="form-grid" id="groupFormGrid"></div>
            </div>
          </div>

          <div class="credit-note" id="creditNote" hidden>
            Office Hours are booked using <strong>1 credit</strong>. These meetings happen at a
            <strong>set recurring time each week</strong> for this mentor.
          </div>

          <div class="office-hours-panel" id="officeHoursPanel" hidden>
            <div class="office-hours-header-row">
              <div>
                <h3>Office Hours for This Mentor</h3>
                <p class="office-hours-subtext">
                  You are viewing the recurring office hours session for the mentor you selected in the previous step.
                </p>
              </div>

                <a href="/student/office-hours" class="office-hours-directory-link"></a>
                See all mentors’ office hours →
              </a>
            </div>

            <div class="office-hours-card">
              <div class="office-hours-card-top">
                <div class="office-hours-mentor-block">
                  <div class="office-hours-mini-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 4v14M6 8l6-3 6 3M7 8v4m10-4v4M5 18h14M4 12h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>

                  <div>
                    <h4 id="officeHoursMentorName">Daniel Ross</h4>
                    <p id="officeHoursMentorMeta">Law • Yale Law School</p>
                  </div>
                </div>

                <div class="office-hours-capacity-pill" id="officeHoursCapacityPill">
                  2/3 spots filled
                </div>
              </div>

              <div class="office-hours-info-grid">
                <div class="office-hours-info-box">
                  <span class="office-hours-label">This Week’s Focus</span>
                  <strong id="officeHoursWeeklyService">Program Insights</strong>
                </div>

                <div class="office-hours-info-box">
                  <span class="office-hours-label">Recurring Weekly Time</span>
                  <strong id="officeHoursRecurringTime">Wednesdays at 7:00 PM</strong>
                </div>

                <div class="office-hours-info-box">
                  <span class="office-hours-label">Meeting Type</span>
                  <strong id="officeHoursMeetingType">Small Group Office Hours</strong>
                </div>

                <div class="office-hours-info-box">
                  <span class="office-hours-label">Current Availability</span>
                  <strong id="officeHoursAvailability">1 spot remaining</strong>
                </div>
              </div>

              <div class="office-hours-note" id="officeHoursNote">
                This week’s office hours for this mentor are currently set as <strong>Program Insights</strong>.
                If other students join, this is the meeting focus you are agreeing to.
                If you are the only student booked by the cutoff, you may request another eligible service this mentor offers.
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="right-column">
        <div class="calendar-panel">
          <div class="calendar-header">
            <div>
              <p class="panel-kicker">Availability</p>
              <h2>Select Date & Time</h2>
              <p class="calendar-subtext">Choose an available day and then select a time.</p>
            </div>
          </div>

          <div class="month-row">
            <button class="month-nav" type="button" aria-label="Previous month">
              <svg viewBox="0 0 24 24">
                <path d="M15.5 19 8.5 12l7-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>

            <div class="month-display">March 2026</div>

            <button class="month-nav" type="button" aria-label="Next month">
              <svg viewBox="0 0 24 24">
                <path d="m8.5 19 7-7-7-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <div class="calendar-grid" id="calendarGrid"></div>

          <div class="times-section">
            <div class="times-heading-row">
              <h3>Available Times</h3>
              <p id="selectedDateLabel">Select a date first</p>
            </div>

            <div class="time-grid" id="timeGrid">
              <button class="time-slot disabled" disabled>Select a date first</button>
            </div>
          </div>

          <div class="booking-summary">
            <div class="summary-item">
              <span>Mentor</span>
              <strong>Daniel Ross</strong>
            </div>
            <div class="summary-item">
              <span>Service</span>
              <strong id="summaryService">Tutoring</strong>
            </div>
            <div class="summary-item">
              <span>Duration</span>
              <strong id="summaryDuration">60 min</strong>
            </div>
            <div class="summary-item">
              <span>Meeting Size</span>
              <strong id="summaryMeetingSize">1 on 1</strong>
            </div>
            <div class="summary-item">
              <span>Price</span>
              <strong id="summaryPrice">$70.00</strong>
            </div>
            <div class="summary-item">
              <span>Date</span>
              <strong id="summaryDate">Not selected</strong>
            </div>
            <div class="summary-item">
              <span>Time</span>
              <strong id="summaryTime">Not selected</strong>
            </div>
          </div>

          <div class="bottom-actions">
            <button class="secondary-btn" type="button">Back</button>
            <button class="primary-btn" type="button" id="continueBtn" disabled>Continue</button>
          </div>
        </div>
      </section>
    </main>
  </div>
      </main>
    </div>
<div class="global-modal-overlay" id="creditModal" hidden>
    <div class="office-hours-modal-panel" role="dialog" aria-modal="true" aria-labelledby="officeHoursTitle">
      <button class="modal-close-btn" id="closeCreditModal" type="button" aria-label="Close modal">×</button>

      <div class="modal-badge">Best Value</div>

      <div class="modal-hero-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M7 3h10M8 6h8m-9 3h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Zm3 5 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <h2 id="officeHoursTitle">Office Hours</h2>
      <div class="modal-price">$200</div>
      <div class="modal-price-sub">USD / month</div>

      <p class="modal-summary-text">
        Flexible office hour access for students who want consistent support, better value, and simpler monthly booking.
      </p>

      <div class="program-box">
        <h3>Choose Your Program</h3>
        <div class="program-grid">
          <button class="program-card active" type="button">MBA</button>
          <button class="program-card" type="button">Law</button>
          <button class="program-card" type="button">Therapy</button>
        </div>
        <p class="program-note">
          Your selected program helps us track demand and improve matching. Credits can be used across all office hours, not just one program.
        </p>
      </div>

      <button class="office-hours-subscribe-btn" type="button">Subscribe to Office Hours</button>

      <ul class="benefits-list">
        <li>5 credits per month to use across MBA, Law, or Therapy office hours</li>
        <li>45 minutes per meeting with small-group access</li>
        <li>First come, first serve booking</li>
        <li>Maximum of 5 people per meeting</li>
        <li>Sessions happen every other week</li>
        <li>Better value for students who want more meetings at a lower per-session cost</li>
      </ul>
    </div>
  </div>

  <div class="global-modal-overlay" id="storeModal" hidden>
    <div class="store-modal-panel-inner" role="dialog" aria-modal="true" aria-labelledby="storeTitle">
      <button class="modal-close-btn" id="closeStoreModal" type="button" aria-label="Close store modal">×</button>

      <div class="modal-hero-icon">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M6 8h12l-1 11H7L6 8Zm2-3h8l1 3H7l1-3Zm1 7h6M10 15h4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <h2 id="storeTitle">Store</h2>
      <p class="modal-summary-text">
        Buy credits or start an Office Hours subscription.
      </p>

      <div class="store-options">
        <button class="store-option-btn" type="button">Buy 1 Credit</button>
        <button class="store-option-btn" type="button">Buy 5 Credits</button>
        <a href="/student/store" class="store-subscribe-link">Subscribe to Office Hours</a>
      </div>
    </div>
  </div>
    <script src="{{ asset('assets/js/demo11.js') }}"></script>
  </body>
</html>
 

  