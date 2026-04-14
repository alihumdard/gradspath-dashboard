@extends('layouts.portal-student')
@section('title', 'Book with Mentor - Grads Paths')
@section('portal_css_asset', 'assets/css/demo11.css')
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
              <a href="{{ route('student.dashboard') }}" class="back-dashboard-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Back to Dashboard</a>
            </div>
          </header>
          <main class="booking-layout">
            <section class="left-column">
              <div class="mentor-card">
                <div class="mentor-top">
                  <div class="mentor-icon-wrap">
                    <span id="mentorInitials">{{ $bookingPageData['mentor']['initials'] ?? 'M' }}</span>
                  </div>
                  <div class="mentor-main">
                    <h2 id="mentorDisplayName">{{ $bookingPageData['mentor']['name'] ?? 'Mentor' }}</h2>
                    <p id="mentorDisplayMeta">{{ $bookingPageData['mentor']['meta'] ?? 'Mentor' }}</p>
                  </div>
                  <div class="mentor-rating">
                    <span>&#9733;</span>
                    <strong id="mentorDisplayRating">{{ $bookingPageData['mentor']['rating'] ?? 'New' }}</strong>
                  </div>
                </div>
                <p class="mentor-description" id="mentorDescription">{{ $bookingPageData['mentor']['description'] ?? 'Mentor description coming soon.' }}</p>
                <button class="read-more-btn" type="button">Read More &#9660;</button>
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
                <div class="service-grid" id="serviceGrid">
                </div>
                <div class="meeting-section" id="meetingSection">
                  <h3>Meeting Size</h3>
                  <p class="helper-text" id="meetingHelperText">Choose whether this is an individual booking or a small group request.</p>
                  <div class="meeting-size-grid" id="meetingSizeGrid">
                  </div>
                  <div class="special-request-note" id="specialRequestNote" hidden>
                    Group meetings are by <strong>special request</strong>. Only one person can pay for the meeting.
                    If you want to split the cost, you must handle that separately yourselves.
                  </div>
                  <div class="group-fields" id="groupFields" hidden>
                    <div class="group-pay-note">One applicant must submit payment for the full meeting.</div>
                    <div class="form-grid" id="groupFormGrid">
                    </div>
                  </div>
                </div>
                <div class="credit-note" id="creditNote" hidden>
                  Office Hours are booked using <strong>1 credit</strong>. These meetings happen at a
                  <strong>set recurring time each week</strong>for this mentor.</div>
                <div class="office-hours-panel" id="officeHoursPanel" hidden>
                  <div class="office-hours-header-row">
                    <div>
                      <h3>Office Hours for This Mentor</h3>
                      <p class="office-hours-subtext">You are viewing the recurring office hours session for the mentor you selected in the previous step.</p>
                    </div>
                    <a href="{{ route('student.office-hours') }}" class="office-hours-directory-link">See all mentors&rsquo; office hours &rarr;</a>
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
                          <h4 id="officeHoursMentorName">{{ $bookingPageData['officeHours']['mentorName'] ?? ($bookingPageData['mentor']['name'] ?? 'Mentor') }}</h4>
                          <p id="officeHoursMentorMeta">{{ $bookingPageData['officeHours']['mentorMeta'] ?? ($bookingPageData['mentor']['meta'] ?? 'Mentor') }}</p>
                        </div>
                      </div>
                      <div class="office-hours-capacity-pill" id="officeHoursCapacityPill">{{ ($bookingPageData['officeHours']['spotsFilled'] ?? 0) . '/' . ($bookingPageData['officeHours']['maxSpots'] ?? 3) }} spots filled</div>
                    </div>
                    <div class="office-hours-info-grid">
                      <div class="office-hours-info-box">
                        <span class="office-hours-label">This Week&rsquo;s Focus</span>
                        <strong id="officeHoursWeeklyService">{{ $bookingPageData['officeHours']['weeklyService'] ?? 'Office Hours' }}</strong>
                      </div>
                      <div class="office-hours-info-box">
                        <span class="office-hours-label">Recurring Weekly Time</span>
                        <strong id="officeHoursRecurringTime">{{ $bookingPageData['officeHours']['recurringTime'] ?? 'Schedule coming soon' }}</strong>
                      </div>
                      <div class="office-hours-info-box">
                        <span class="office-hours-label">Meeting Type</span>
                        <strong id="officeHoursMeetingType">{{ $bookingPageData['officeHours']['meetingType'] ?? 'Small Group Office Hours' }}</strong>
                      </div>
                      <div class="office-hours-info-box">
                        <span class="office-hours-label">Current Availability</span>
                        <strong id="officeHoursAvailability">Availability updates soon</strong>
                      </div>
                    </div>
                    <div class="office-hours-note" id="officeHoursNote">
                      This week&rsquo;s office hours for this mentor are currently set as <strong>{{ $bookingPageData['officeHours']['weeklyService'] ?? 'Office Hours' }}</strong>.
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
                <div class="calendar-grid" id="calendarGrid">
                </div>
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
                    <strong id="summaryMentor">{{ $bookingPageData['mentor']['name'] ?? 'Mentor' }}</strong>
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
@endsection

@section('portal_after_shell')
        <div class="global-modal-overlay" id="creditModal" hidden>
          <div class="office-hours-modal-panel" role="dialog" aria-modal="true" aria-labelledby="officeHoursTitle">
            <button class="modal-close-btn" id="closeCreditModal" type="button" aria-label="Close modal">ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â</button>
            <div class="modal-badge">Best Value</div>
            <div class="modal-hero-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 3h10M8 6h8m-9 3h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Zm3 5 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h2 id="officeHoursTitle">Office Hours</h2>
            <div class="modal-price">$200</div>
            <div class="modal-price-sub">USD / month</div>
            <p class="modal-summary-text">Flexible office hour access for students who want consistent support, better value, and simpler monthly booking.</p>
            <div class="program-box">
              <h3>Choose Your Program</h3>
              <div class="program-grid">
                <button class="program-card active" type="button">MBA</button>
                <button class="program-card" type="button">Law</button>
                <button class="program-card" type="button">Therapy</button>
              </div>
              <p class="program-note">Your selected program helps us track demand and improve matching. Credits can be used across all office hours, not just one program.</p>
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
            <button class="modal-close-btn" id="closeStoreModal" type="button" aria-label="Close store modal">ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â</button>
            <div class="modal-hero-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 8h12l-1 11H7L6 8Zm2-3h8l1 3H7l1-3Zm1 7h6M10 15h4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h2 id="storeTitle">Store</h2>
            <p class="modal-summary-text">Buy credits or start an Office Hours subscription.</p>
            <div class="store-options">
              <button class="store-option-btn" type="button">Buy 1 Credit</button>
              <button class="store-option-btn" type="button">Buy 5 Credits</button>
                <a href="{{ route('student.store') }}" class="store-subscribe-link">Subscribe to Office Hours</a>
            </div>
          </div>
        </div>
@endsection

@section('page_js')
        <script id="bookingPageData" type="application/json">@json($bookingPageData)</script>
        <script src="{{ asset('assets/js/demo11.js') }}">
        </script>
@endsection
