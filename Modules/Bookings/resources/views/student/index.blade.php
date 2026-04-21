@extends('layouts.portal-student')
@section('title', 'Bookings - Grads Paths')
@section('portal_css_asset', 'assets/css/demo9.css')
@section('portal_active_nav', 'bookings')
@section('page_topbar_left')
        <div class="search-wrap">
          <input
          type="text"
          class="search-input"
          placeholder="Search mentors, universities..."
          />
        </div>
@endsection

@section('portal_content')
        <div class="demo9-page">
          <div class="demo9-shell">
            <div class="demo9-card">
              <section class="top-section">
                <div class="booking-header-row">
<div class="booking-header-left">
                  <h1>Your Session Is Booked</h1>
                  <p class="subtitle" id="bookingSubtitle">Here is your meeting information with your mentor.</p>
                </div>
                <div class="booking-header-right">
                  <p class="cancel-copy">Want to cancel your meeting?</p>
                  <button
                  class="cancel-btn"
                  id="cancelMeetingBtn"
                  type="button"
                  >Cancel Meeting</button>
                  <form id="cancelBookingForm" method="POST" class="hidden">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="reason" id="cancelBookingReason" value="Cancelled from booking page">
                  </form>
              </div>
            </div>
            <section class="service-section">
              <h3 class="section-title">Selected Service</h3>
              <div
              class="service-grid locked"
              aria-label="Locked selected service"
              >
              <button
              class="service-card locked-card"
              type="button"
              disabled
              >
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path
                  d="M7 7.5A2.5 2.5 0 0 1 9.5 5H14a2.5 2.5 0 0 1 2.5 2.5V10A2.5 2.5 0 0 1 14 12.5h-2.7l-2.4 2.3A.8.8 0 0 1 7.5 14v-1.5A2.5 2.5 0 0 1 5 10V7.5A2.5 2.5 0 0 1 7.5 5H7Z"
                  >
                </path>
                  <path
                  d="M16 9.5h.5A2.5 2.5 0 0 1 19 12v2.5A2.5 2.5 0 0 1 16.5 17H16v1a.8.8 0 0 1-1.4.5L12.5 16H10a2.5 2.5 0 0 1-2.5-2.5V13h6.5A4 4 0 0 0 18 9V9.5Z"
                  >
                </path>
                </svg>
              </div>
              <span class="service-name">Tutoring</span>
            </button>
            <button
            class="service-card locked-card"
            type="button"
            disabled
            >
            <div class="service-icon-box">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path
                d="M12 3 2 9l10 6 8-4.8V17h2V9L12 3Zm0 9.7L5.7 9 12 5.3 18.3 9 12 12.7ZM6 14.4V17c0 1.9 3.1 3.4 6 3.4s6-1.5 6-3.4v-2.6l-6 3.6-6-3.6Z"
                >
              </path>
              </svg>
            </div>
            <span class="service-name">Program Insights</span>
          </button>
          <button
          class="service-card locked-card"
          type="button"
          disabled
          >
          <div class="service-icon-box">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path
              d="M19 7h-1V6a4 4 0 1 0-8 0v1H9a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Zm-7-1a2 2 0 1 1 4 0v1h-4V6Zm7 12H9V9h10v9Z"
              >
            </path>
            </svg>
          </div>
          <span class="service-name">Interview Prep</span>
        </button>
        <button
        class="service-card locked-card"
        type="button"
        disabled
        >
        <div class="service-icon-box">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
            d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7Zm0 2.5L16.5 7H14ZM7 20V4h5v5h5v11Z"
            >
          </path>
          </svg>
        </div>
        <span class="service-name">Application Review</span>
      </button>
        <button
        class="service-card locked-card"
        type="button"
        disabled
        >
        <div class="service-icon-box">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
            d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm6.9 9h-2.1a15.5 15.5 0 0 0-1.3-5A8 8 0 0 1 18.9 11ZM12 4.1c1 .9 2.2 3.3 2.7 6.9H9.3C9.8 7.4 11 5 12 4.1ZM8.5 6a15.5 15.5 0 0 0-1.3 5H5.1A8 8 0 0 1 8.5 6ZM5.1 13h2.1a15.5 15.5 0 0 0 1.3 5A8 8 0 0 1 5.1 13Zm6.9 6.9c-1-.9-2.2-3.3-2.7-6.9h5.4c-.5 3.6-1.7 6-2.7 6.9ZM15.5 18a15.5 15.5 0 0 0 1.3-5h2.1a8 8 0 0 1-3.4 5Z"
            >
          </path>
          </svg>
        </div>
        <span class="service-name">Gap Year Planning</span>
      </button>
        <button
        class="service-card locked-card selected"
        type="button"
        disabled
        >
        <div class="service-icon-box">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path
            d="M12 1.75A10.25 10.25 0 1 0 22.25 12 10.26 10.26 0 0 0 12 1.75Zm4.15 14.4-.95 1.2L11 14.1V6h1.5v7.35Z"
            >
          </path>
          </svg>
        </div>
        <span class="service-name">Office Hours</span>
      </button>
      </div>
        <p class="service-lock-note">Service is locked after booking and cannot be changed here.</p>
      </section>
        <div class="info-grid">
          <div class="info-box mentor-profile-box">
            <div class="info-icon">
              <i data-lucide="user-check">
              </i>
            </div>
            <div class="info-content">
              <span class="label" id="counterpartLabel">Mentor</span>
              <strong id="mentorName">Emily Carter &bull; Graduate Mentor &bull; MBA &bull; Harvard</strong>
            </div>
          </div>
          <div class="info-box">
            <div class="info-icon">
              <i data-lucide="calendar">
              </i>
            </div>
            <div class="info-content">
              <span class="label">Date</span>
              <strong id="meetingDate">March 21, 2026</strong>
            </div>
          </div>
          <div class="info-box">
            <div class="info-icon">
              <i data-lucide="clock">
              </i>
            </div>
            <div class="info-content">
              <span class="label">Time</span>
              <strong id="meetingTime">1:00 PM</strong>
            </div>
          </div>
          <div class="info-box zoom-box">
            <div class="info-icon">
              <i data-lucide="video">
              </i>
            </div>
            <div class="info-content">
              <span class="label" id="meetingProviderLabel">Meeting Link</span>
              <a id="zoomLink" href="https://zoom.us/j/9876543210" target="_blank" rel="noopener noreferrer">Join Meeting</a>
              <small id="meetingLinkStatusText">Meeting link will be shared soon.</small>
            </div>
          </div>
        </div>
      </section>
        <section class="calendar-section">
          <div class="calendar-card">
            <div class="calendar-toolbar">
              <div class="calendar-view-switch">
                <button type="button" class="view-btn" data-view="day">Day</button>
                <button type="button" class="view-btn" data-view="week">Week</button>
                <button
                type="button"
                class="view-btn active"
                data-view="month"
                >Month</button>
              <button type="button" class="view-btn" data-view="year">Year</button>
            </div>
            <div class="calendar-toolbar-right">
              <button class="today-btn" id="todayBtn" type="button">Today</button>
              <div class="calendar-nav-group">
                <button
                class="month-nav"
                id="prevMonth"
                type="button"
                aria-label="Previous period"
                >&lsaquo;</button>
              <button
              class="month-nav"
              id="nextMonth"
              type="button"
              aria-label="Next period"
              >&rsaquo;</button>
          </div>
        </div>
      </div>
        <div class="calendar-month-row">
          <button
          class="month-title-button"
          id="monthTitleButton"
          type="button"
          >
          <span id="monthLabel">March 2026</span>
          <span class="month-caret">&darr;</span>
        </button>
        <div class="month-dropdown" id="monthDropdown">
        </div>
      </div>
        <div id="calendarContent">
        </div>
      </div>
      </section>
        <section class="upcoming-section">
          <div class="upcoming-header">
            <div>
              <h3>Upcoming Appointments</h3>
              <p>All future booked sessions appear here.</p>
            </div>
          </div>
          <div class="upcoming-list" id="upcomingList">
          </div>
        </section>
        <section class="chat-section">
          <div class="chat-header">
            <div>
              <h3>Chat Before Session</h3>
              <p>Simple message thread for this booking.</p>
            </div>
            <span class="chat-status" id="chatStatus">Connecting...</span>
          </div>
          <div class="chat-typing" id="chatTyping" aria-live="polite"></div>
          <div class="chat-window" id="chatWindow"></div>
          <form class="chat-input-row" id="chatForm">
            <input
            type="text"
            id="chatInput"
            placeholder="Type a message..."
            autocomplete="off"
            />
            <button type="submit">Send</button>
          </form>
        </section>
      </div>
      </div>
      </div>
@endsection

@section('portal_after_shell')
        <div class="modal-overlay hidden" id="cancelModal">
          <div class="modal-card">
            <h3>Cancel this meeting?</h3>
            <p>This will remove your upcoming session from your schedule.</p>
            <div class="modal-actions">
              <button class="modal-btn secondary" id="cancelNo1" type="button">Keep Meeting</button>
              <button class="modal-btn danger" id="cancelYes1" type="button">Yes, Cancel</button>
            </div>
          </div>
        </div>
        <div class="modal-overlay hidden" id="cancelConfirmModal">
          <div class="modal-card">
            <h3>Are you absolutely sure?</h3>
            <p>If you still want to cancel, you will be directed to submit a refund
              request through support.</p>
            <div class="modal-actions">
              <button class="modal-btn secondary" id="cancelNo2" type="button">Go Back</button>
              <button class="modal-btn danger" id="cancelYes2" type="button">Yes, Continue</button>
            </div>
          </div>
        </div>
        <div class="modal-overlay hidden" id="supportModal">
          <div class="modal-card support-card">
            <h3>Submit Refund Request</h3>
            <p>Your meeting has been marked for cancellation. To request a refund,
              please contact support through the link below.</p>
            <a href="#" class="support-link" id="supportLink"
            >Contact Us / Support</a
            >
            <div class="support-note">Include your mentor name, booked date, and session time in your refund
              request.</div>
            <div class="modal-actions single">
              <button class="modal-btn primary" id="supportCloseBtn" type="button">Done</button>
            </div>
          </div>
        </div>
@endsection

@section('page_js')
        <script id="bookingDetailsData" type="application/json">@json($bookingPageData ?? [])</script>
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
        <script src="https://unpkg.com/lucide@latest">
        </script>
        <script src="{{ asset('assets/js/demo9.js') }}">
        </script>
@endsection
