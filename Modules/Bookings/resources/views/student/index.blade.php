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
        <div class="service-choice-row" id="officeHoursServiceChoiceRow" hidden>
          <p class="service-lock-note" id="officeHoursServiceChoiceNote">Office Hours focus updates here when eligible.</p>
          <button class="feedback-trigger-btn service-choice-trigger" id="openOfficeHoursServiceChoiceBtn" type="button">Change Office Hours Focus</button>
        </div>
        <p class="service-lock-note" id="serviceLockNote">Service is locked after booking and cannot be changed here.</p>
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
              <h3>Current Appointments</h3>
              <p>Meetings that are live and running right now appear here.</p>
            </div>
          </div>
          <div class="upcoming-list" id="currentList">
          </div>
        </section>
        <section class="upcoming-section">
          <div class="upcoming-header">
            <div>
              <h3>Upcoming Appointments</h3>
              <p>Future booked sessions that have not started yet appear here.</p>
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
        <section class="feedback-entry-section">
          <div class="feedback-entry-copy">
            <span class="feedback-entry-eyebrow">Post-Meeting Review</span>
            <h3>Feedback After Your Meeting</h3>
            <p id="feedbackReason">Feedback will unlock after attendance is verified or the fallback window passes.</p>
            <p class="feedback-entry-helper" id="feedbackHelper">Select a completed booking to leave feedback.</p>
          </div>
          <div class="feedback-entry-actions">
            <span class="feedback-status-pill" id="feedbackStatusPill">Locked</span>
            <button type="button" class="feedback-trigger-btn" id="openFeedbackModalBtn">Open Feedback Form</button>
          </div>
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
            <p>Eligible cancellations automatically refund the credits or payment used for this booking.</p>
            <div class="modal-actions">
              <button class="modal-btn secondary" id="cancelNo2" type="button">Go Back</button>
              <button class="modal-btn danger" id="cancelYes2" type="button">Yes, Continue</button>
            </div>
          </div>
        </div>
        <div class="modal-overlay hidden" id="supportModal">
          <div class="modal-card support-card">
            <h3>Cancellation Submitted</h3>
            <p>Your meeting has been marked for cancellation. If the automatic refund needs admin review, support can help from the link below.</p>
            <a href="#" class="support-link" id="supportLink"
            >Contact Us / Support</a
            >
            <div class="support-note">Include your mentor name, booked date, and session time if you contact support.</div>
            <div class="modal-actions single">
              <button class="modal-btn primary" id="supportCloseBtn" type="button">Done</button>
            </div>
          </div>
        </div>
        <div class="modal-overlay hidden" id="officeHoursServiceChoiceModal">
          <div class="modal-card service-choice-modal-card">
            <h3>Choose Office Hours Focus</h3>
            <p id="officeHoursServiceChoiceModalText">You are the only student booked for this session, so you can choose another eligible service before the cutoff.</p>
            <div class="service-choice-options" id="officeHoursServiceChoiceOptions"></div>
            <div class="service-choice-alert" id="officeHoursServiceChoiceAlert" hidden></div>
            <div class="modal-actions">
              <button class="modal-btn secondary" id="closeOfficeHoursServiceChoiceBtn" type="button">Keep Current Focus</button>
              <button class="modal-btn primary" id="saveOfficeHoursServiceChoiceBtn" type="button">Save Focus</button>
            </div>
          </div>
        </div>
        <div class="modal-overlay hidden" id="feedbackModal">
          <div class="feedback-modal-card">
            <button class="feedback-modal-close" id="closeFeedbackModalBtn" type="button" aria-label="Close feedback form">&times;</button>

            <div class="feedback-modal-top">
              <span class="feedback-entry-eyebrow">Post-Meeting Review</span>
              <h2>Feedback After Your Meeting</h2>
              <p>Full direct feedback form submission for this mentor session.</p>
            </div>

            @if ($errors->any())
              <div class="feedback-inline-alert error modal-alert">
                {{ $errors->first('feedback') ?? $errors->first() }}
              </div>
            @endif

            <form id="feedbackForm" method="POST" action="{{ route('student.feedback.store') }}">
              @csrf
              <input type="hidden" name="booking_id" id="feedbackBookingId" value="{{ old('booking_id') }}">
              <input type="hidden" name="service_type" id="feedbackServiceType" value="{{ old('service_type') }}">

              <div class="feedback-modal-section">
                <div class="feedback-modal-section-header">
                  <h3>Session Details</h3>
                  <p>This information is automatically filled from the selected booking.</p>
                </div>

                <div class="feedback-session-grid">
                  <div class="feedback-detail-card">
                    <span>Full Name of Mentor</span>
                    <strong id="feedbackMentorName">Mentor</strong>
                  </div>
                  <div class="feedback-detail-card">
                    <span>Program</span>
                    <strong id="feedbackMentorProgram">-</strong>
                  </div>
                  <div class="feedback-detail-card">
                    <span>Mentor Type</span>
                    <strong id="feedbackMentorType">-</strong>
                  </div>
                  <div class="feedback-detail-card">
                    <span>Email of Mentor</span>
                    <strong id="feedbackMentorEmail">-</strong>
                  </div>
                  <div class="feedback-detail-card">
                    <span>School</span>
                    <strong id="feedbackMentorSchool">-</strong>
                  </div>
                  <div class="feedback-detail-card">
                    <span>Date of Session</span>
                    <strong id="feedbackSessionDate">-</strong>
                  </div>
                </div>

                <div class="feedback-service-block">
                  <span class="feedback-service-label">Service Used</span>
                  <div class="feedback-service-grid" id="feedbackServiceGrid">
                    <div class="feedback-service-card" data-service="Tutoring">
                      <div class="feedback-service-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm8-1h2a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-7a2 2 0 0 0-2 2v1.2A4.9 4.9 0 0 1 11 9.5V10h2.5l2.2 2.2A1 1 0 0 0 17.4 12V11.4A2 2 0 0 0 16 10Zm-8 3c-3.2 0-6 1.6-6 3.6 0 .8.7 1.4 1.5 1.4h9c.8 0 1.5-.6 1.5-1.4C14 14.6 11.2 13 8 13Z"/></svg>
                      </div>
                      <span>Tutoring</span>
                    </div>
                    <div class="feedback-service-card" data-service="Program Insights">
                      <div class="feedback-service-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2 8l10 5 8.2-4.1V15H22V8L12 3Zm-6.8 8.8V15c0 1.9 3.1 3.5 6.8 3.5s6.8-1.6 6.8-3.5v-3.2L12 15.2l-6.8-3.4Z"/></svg>
                      </div>
                      <span>Program Insights</span>
                    </div>
                    <div class="feedback-service-card" data-service="Interview Prep">
                      <div class="feedback-service-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7V5a3 3 0 0 1 6 0v2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2Zm2 0h2V5a1 1 0 1 0-2 0v2Z"/></svg>
                      </div>
                      <span>Interview Prep</span>
                    </div>
                    <div class="feedback-service-card" data-service="Application Review">
                      <div class="feedback-service-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5M9 16.2l5.9-5.9 1.8 1.8-5.9 5.9H9v-1.8Z"/></svg>
                      </div>
                      <span>Application Review</span>
                    </div>
                    <div class="feedback-service-card" data-service="Gap Year Planning">
                      <div class="feedback-service-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm6.9 9h-3.1a15.7 15.7 0 0 0-1.2-4A8.1 8.1 0 0 1 18.9 11ZM12 4.1c1 1.1 2 3.4 2.4 6H9.6c.4-2.6 1.4-4.9 2.4-6ZM5.1 13h3.1c.2 1.4.6 2.8 1.2 4a8.1 8.1 0 0 1-4.3-4Zm3.1-2H5.1a8.1 8.1 0 0 1 4.3-4c-.6 1.2-1 2.6-1.2 4Zm3.8 8c-1-1.1-2-3.4-2.4-6h4.8c-.4 2.6-1.4 4.9-2.4 6Zm2.6-2c.6-1.2 1-2.6 1.2-4h3.1a8.1 8.1 0 0 1-4.3 4Z"/></svg>
                      </div>
                      <span>Gap Year Planning</span>
                    </div>
                    <div class="feedback-service-card" data-service="Office Hours">
                      <div class="feedback-service-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 5v4.4l3 1.8-.8 1.4L11 12V7Z"/></svg>
                      </div>
                      <span>Office Hours</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="feedback-modal-section">
                <div class="feedback-question-row">
                  <span class="feedback-question-number">01</span>
                  <div>
                    <h3>Overall Session Rating <span class="required">*</span></h3>
                    <p>How helpful was your session?</p>
                  </div>
                </div>
                <div class="feedback-rating-box">
                  <div class="feedback-star-rating" id="feedbackStarRating">
                    @for ($i = 1; $i <= 5; $i++)
                      <button type="button" class="feedback-star" data-value="{{ $i }}" aria-label="{{ $i }} star">&#9733;</button>
                    @endfor
                  </div>
                  <div class="feedback-rating-label" id="feedbackRatingLabel">Select a rating</div>
                  <input type="hidden" name="stars" id="feedbackStars" value="{{ old('stars') }}">
                </div>
              </div>

              <div class="feedback-modal-section">
                <div class="feedback-question-row">
                  <span class="feedback-question-number">02</span>
                  <div>
                    <h3>Mentor Preparedness and Knowledge</h3>
                    <p>How knowledgeable and prepared did the mentor seem during the session?</p>
                  </div>
                </div>
                <div class="feedback-scale-options">
                  @php
                    $preparednessOptions = [
                      1 => ['Very Low', 'Not prepared'],
                      2 => ['Low', 'Some gaps'],
                      3 => ['Moderate', 'Good overall'],
                      4 => ['High', 'Strong guidance'],
                      5 => ['Excellent', 'Very strong insight'],
                    ];
                  @endphp
                  @foreach ($preparednessOptions as $value => [$title, $subtitle])
                    <label class="feedback-scale-card">
                      <input type="radio" name="preparedness_rating" value="{{ $value }}" @checked((string) old('preparedness_rating') === (string) $value)>
                      <span class="feedback-scale-title">{{ $title }}</span>
                      <span class="feedback-scale-sub">{{ $subtitle }}</span>
                    </label>
                  @endforeach
                </div>
              </div>

              <div class="feedback-modal-section">
                <div class="feedback-question-row">
                  <span class="feedback-question-number">03</span>
                  <div>
                    <h3>Would You Recommend This Mentor? <span class="required">*</span></h3>
                    <p>Would you recommend this mentor to another student?</p>
                  </div>
                </div>
                <div class="feedback-binary-options">
                  <label class="feedback-binary-card">
                    <input type="radio" name="recommend" value="1" @checked((string) old('recommend', '1') === '1')>
                    <span>Yes</span>
                  </label>
                  <label class="feedback-binary-card">
                    <input type="radio" name="recommend" value="0" @checked((string) old('recommend') === '0')>
                    <span>No</span>
                  </label>
                </div>
              </div>

              <div class="feedback-modal-section">
                <div class="feedback-question-row">
                  <span class="feedback-question-number">04</span>
                  <div>
                    <h3>Quick Feedback <span class="required">*</span></h3>
                    <p>What went well, what could have been better, and is there anything else you want us to know?</p>
                  </div>
                </div>
                <div class="feedback-text-wrap">
                  <textarea id="feedbackComment" name="comment" rows="5" maxlength="2000" placeholder="Write your feedback here...">{{ old('comment') }}</textarea>
                  <div class="char-count"><span id="feedbackCharCount">{{ strlen((string) old('comment')) }}</span> characters</div>
                </div>
              </div>

              <div class="feedback-modal-footer">
                <p class="feedback-entry-helper" id="feedbackModalHelper">Select a completed booking to leave feedback.</p>
                <button type="submit" class="feedback-modal-submit" id="feedbackSubmitBtn">Submit Feedback</button>
              </div>
            </form>
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
