@extends('layouts.portal-mentor')
@section('title', 'Feedback - Grads Paths')
@section('portal_css_asset', 'assets/css/demo6.css')
@section('portal_active_nav', 'feedback')
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
          <main class="feedback-wrapper">
            <section class="feedback-card">
              <div class="card-top">
                <span class="eyebrow">Post-Meeting Review</span>
                <h1>Feedback After Your Meeting</h1>
                <p class="intro-text">To maintain quality and create the best student experience, we
                  ask students to answer a few short questions after each
                  session.</p>
                <p class="intro-text secondary">Your feedback helps improve the platform and helps future
                  students choose the right mentors.</p>
              </div>
              <form id="feedbackForm" novalidate>
                <div class="form-section compact-section">
                  <div class="session-info-card">
                    <div class="session-info-header">
                      <span class="session-badge">Session Details</span>
                      <p>This information is automatically filled in from the
                        scheduled session.</p>
                    </div>
                    <div class="session-info-grid">
                      <div class="info-field">
                        <label for="fullName">Full Name of Mentor</label>
                        <input
                        type="text"
                        id="fullName"
                        name="fullName"
                        readonly
                        />
                      </div>
                      <div class="info-field">
                        <label for="userEmail">Email of Mentor</label>
                        <input
                        type="email"
                        id="userEmail"
                        name="userEmail"
                        readonly
                        />
                      </div>
                      <div class="info-field">
                        <label for="sessionDate">Date of Session</label>
                        <input
                        type="text"
                        id="sessionDate"
                        name="sessionDate"
                        readonly
                        />
                      </div>
                      <div class="info-field full-width">
                        <label>Service Used</label>
                        <div
                        class="service-display-grid"
                        id="serviceUsedDisplay"
                        >
                        <div
                        class="service-view-card"
                        data-service="Tutoring"
                        >
                        <div class="service-view-icon">
                          <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                            d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm8-1h2a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-7a2 2 0 0 0-2 2v1.2A4.9 4.9 0 0 1 11 9.5V10h2.5l2.2 2.2A1 1 0 0 0 17.4 12V11.4A2 2 0 0 0 16 10Zm-8 3c-3.2 0-6 1.6-6 3.6 0 .8.7 1.4 1.5 1.4h9c.8 0 1.5-.6 1.5-1.4C14 14.6 11.2 13 8 13Z"
                            />
                          </svg>
                        </div>
                        <span>Tutoring</span>
                      </div>
                      <div
                      class="service-view-card"
                      data-service="Program Insights"
                      >
                      <div class="service-view-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                          <path
                          d="M12 3 2 8l10 5 8.2-4.1V15H22V8L12 3Zm-6.8 8.8V15c0 1.9 3.1 3.5 6.8 3.5s6.8-1.6 6.8-3.5v-3.2L12 15.2l-6.8-3.4Z"
                          />
                        </svg>
                      </div>
                      <span>Program Insights</span>
                    </div>
                    <div
                    class="service-view-card"
                    data-service="Interview Prep"
                    >
                    <div class="service-view-icon">
                      <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                        d="M9 7V5a3 3 0 0 1 6 0v2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2Zm2 0h2V5a1 1 0 1 0-2 0v2Z"
                        />
                      </svg>
                    </div>
                    <span>Interview Prep</span>
                  </div>
                  <div
                  class="service-view-card"
                  data-service="Application Review"
                  >
                  <div class="service-view-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path
                      d="M7 3h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5M9 16.2l5.9-5.9 1.8 1.8-5.9 5.9H9v-1.8Z"
                      />
                    </svg>
                  </div>
                  <span>Application Review</span>
                </div>
                <div
                class="service-view-card"
                data-service="Gap Year Planning"
                >
                <div class="service-view-icon">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                    d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm6.9 9h-3.1a15.7 15.7 0 0 0-1.2-4A8.1 8.1 0 0 1 18.9 11ZM12 4.1c1 1.1 2 3.4 2.4 6H9.6c.4-2.6 1.4-4.9 2.4-6ZM5.1 13h3.1c.2 1.4.6 2.8 1.2 4a8.1 8.1 0 0 1-4.3-4Zm3.1-2H5.1a8.1 8.1 0 0 1 4.3-4c-.6 1.2-1 2.6-1.2 4Zm3.8 8c-1-1.1-2-3.4-2.4-6h4.8c-.4 2.6-1.4 4.9-2.4 6Zm2.6-2c.6-1.2 1-2.6 1.2-4h3.1a8.1 8.1 0 0 1-4.3 4Z"
                    />
                  </svg>
                </div>
                <span>Gap Year Planning</span>
              </div>
              <div
              class="service-view-card"
              data-service="Office Hours"
              >
              <div class="service-view-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path
                  d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 5v4.4l3 1.8-.8 1.4L11 12V7Z"
                  />
                </svg>
              </div>
              <span>Office Hours</span>
            </div>
          </div>
          <input
          type="hidden"
          id="serviceUsed"
          name="serviceUsed"
          />
        </div>
      </div>
      </div>
      </div>
        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">01</span>
            <div>
              <h2>
                Overall Session Rating <span class="required">*</span>
              </h2>
              <p>How helpful was your session?</p>
            </div>
          </div>
          <div class="rating-box">
            <div
            class="star-rating"
            id="meetingRating"
            data-name="meetingRating"
            >
            <button
            type="button"
            class="star"
            data-value="1"
            aria-label="1 star"
            >ÃƒÆ’Ã‚Â¢Ãƒâ€¹Ã…â€œÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦</button>
          <button
          type="button"
          class="star"
          data-value="2"
          aria-label="2 stars"
          >ÃƒÆ’Ã‚Â¢Ãƒâ€¹Ã…â€œÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦</button>
        <button
        type="button"
        class="star"
        data-value="3"
        aria-label="3 stars"
        >ÃƒÆ’Ã‚Â¢Ãƒâ€¹Ã…â€œÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦</button>
        <button
        type="button"
        class="star"
        data-value="4"
        aria-label="4 stars"
        >ÃƒÆ’Ã‚Â¢Ãƒâ€¹Ã…â€œÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦</button>
        <button
        type="button"
        class="star"
        data-value="5"
        aria-label="5 stars"
        >ÃƒÆ’Ã‚Â¢Ãƒâ€¹Ã…â€œÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦</button>
      </div>
        <div class="rating-label" id="meetingRatingLabel">Select a rating</div>
        <input
        type="hidden"
        name="meetingRating"
        id="meetingRatingInput"
        />
      </div>
      </div>
        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">02</span>
            <div>
              <h2>
                Mentor Preparedness and Knowledge
                <span class="required">*</span>
              </h2>
              <p>How knowledgeable and prepared did the mentor seem
                during the session?</p>
            </div>
          </div>
          <div class="scale-options compact-five">
            <label class="scale-card">
              <input type="radio" name="mentorKnowledge" value="1" />
              <span class="scale-title">Very Low</span>
              <span class="scale-sub">Not prepared</span>
            </label>
            <label class="scale-card">
              <input type="radio" name="mentorKnowledge" value="2" />
              <span class="scale-title">Low</span>
              <span class="scale-sub">Some gaps</span>
            </label>
            <label class="scale-card">
              <input type="radio" name="mentorKnowledge" value="3" />
              <span class="scale-title">Moderate</span>
              <span class="scale-sub">Good overall</span>
            </label>
            <label class="scale-card">
              <input type="radio" name="mentorKnowledge" value="4" />
              <span class="scale-title">High</span>
              <span class="scale-sub">Strong guidance</span>
            </label>
            <label class="scale-card">
              <input type="radio" name="mentorKnowledge" value="5" />
              <span class="scale-title">Excellent</span>
              <span class="scale-sub">Very strong insight</span>
            </label>
          </div>
        </div>
        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">03</span>
            <div>
              <h2>
                Would You Recommend This Mentor?
                <span class="required">*</span>
              </h2>
              <p>Would you recommend this mentor to another student?</p>
            </div>
          </div>
          <div class="binary-options">
            <label class="binary-card">
              <input type="radio" name="recommendation" value="Yes" />
              <span>Yes</span>
            </label>
            <label class="binary-card">
              <input type="radio" name="recommendation" value="No" />
              <span>No</span>
            </label>
          </div>
        </div>
        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">04</span>
            <div>
              <h2>Quick Feedback <span class="required">*</span>
              </h2>
              <p>What went well, what could have been better, and is
                there anything else you want us to know?</p>
            </div>
          </div>
          <div class="feedback-text-wrap">
            <textarea
            id="quickFeedback"
            name="quickFeedback"
            rows="5"
            placeholder="Write your feedback here..."
            >
          </textarea>
          <div class="char-count">
            <span id="charCount">0</span>characters</div>
        </div>
      </div>
        <div class="form-footer">
          <button type="submit" class="submit-btn">Submit Feedback</button>
        </div>
        <div
        id="successMessage"
        class="success-message"
        aria-live="polite"
        >Thank you. Your feedback has been submitted successfully.</div>
      </form>
      </section>
      </main>
      </div>
@endsection

@section('page_js')
        <script src="{{ asset('assets/js/demo6.js') }}">
        </script>
@endsection
