<div class="page-wrap">
  <div class="top-bar">
    <h1>Support</h1>
  </div>

  <p class="intro-text">
    Need help? Open a support ticket and our team will respond quickly to resolve your issue.
  </p>

  <div class="support-wrapper">
    <div class="feedback-card">
      <h1>Submit Feedback</h1>

      <form id="supportForm" novalidate>
        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">01</span>
            <div>
              <h2>Name <span class="required">*</span></h2>
              <p>Please provide your full name</p>
            </div>
          </div>
          <div class="form-field">
            <label for="name">NAME</label>
            <input type="text" id="name" name="name" placeholder="e.g. Mike Ross" required />
          </div>
        </div>

        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">02</span>
            <div>
              <h2>Email <span class="required">*</span></h2>
              <p>Your university email address</p>
            </div>
          </div>
          <div class="form-field">
            <label for="email">EMAIL</label>
            <input type="email" id="email" name="email" placeholder="you@university.edu" required />
          </div>
        </div>

        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">03</span>
            <div>
              <h2>Subject <span class="required">*</span></h2>
              <p>Brief description of your issue</p>
            </div>
          </div>
          <div class="form-field">
            <label for="subject">SUBJECT</label>
            <input type="text" id="subject" name="subject" placeholder="e.g. Issue with....." required />
          </div>
        </div>

        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">04</span>
            <div>
              <h2>Message <span class="required">*</span></h2>
              <p>Detailed description of your support request</p>
            </div>
          </div>
          <div class="feedback-text-wrap">
            <textarea id="message" name="message" rows="5" placeholder="Type your message here..." required></textarea>
            <div class="char-count">
              <span id="charCount">0</span> characters
            </div>
          </div>
        </div>

        <div class="form-footer">
          <button type="submit" class="send-message-btn">Send message</button>
        </div>

        <div id="successMessage" class="success-message" aria-live="polite">
          Thank you. Your support request has been submitted successfully.
        </div>
      </form>
    </div>
  </div>
</div>
