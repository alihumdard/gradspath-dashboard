@extends('layouts.portal-student')

@section('title', 'Student Store - Grads Paths')
@section('portal_css_asset', 'assets/css/demo2.css')
@section('portal_js_asset', 'assets/js/demo2.js')
@section('portal_active_nav', 'dashboard')

@section('portal_css')
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
  />
@endsection

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
  <section class="pricing-shell">
    <section class="pricing-card">
      <div class="card-top">
        <div class="hero-icon icon-top" aria-hidden="true">
          <i class="fa-solid fa-calendar-check"></i>
        </div>

        <h1>Office Hours</h1>

        <div class="hero-price-wrap">
          <div class="price-line">
            <span class="currency">$</span>
            <span class="price">200</span>
          </div>
          <div class="billing">USD / month</div>
        </div>

        <p class="card-subtitle">
          Flexible office hour access for students who want consistent
          support, better value, and simpler monthly booking.
        </p>
      </div>

      <div class="program-picker">
        <p class="section-label">Choose your program</p>

        <div class="program-options">
          <button
            type="button"
            class="program-pill selected"
            data-program="MBA"
            aria-label="Select MBA"
          >
            <span class="pill-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 8.5h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9Z" />
                <path d="M9 8.5V7a3 3 0 0 1 6 0v1.5" />
                <path d="M4 11.5h16" />
              </svg>
            </span>
            <span class="pill-label">MBA</span>
          </button>

          <button
            type="button"
            class="program-pill"
            data-program="Law"
            aria-label="Select Law"
          >
            <span class="pill-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 4v14" />
                <path d="M7 7h10" />
                <path
                  d="M5 7l-2.5 4.5A2.5 2.5 0 0 0 4.7 15h2.6a2.5 2.5 0 0 0 2.2-3.5L7 7Z"
                />
                <path
                  d="M19 7l-2.5 4.5a2.5 2.5 0 0 0 2.2 3.5h2.6a2.5 2.5 0 0 0 2.2-3.5L19 7Z"
                />
                <path d="M9 20h6" />
              </svg>
            </span>
            <span class="pill-label">Law</span>
          </button>

          <button
            type="button"
            class="program-pill"
            data-program="Therapy"
            aria-label="Select Therapy"
          >
            <span class="pill-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path
                  d="M12 20c4.2-2.5 6.5-5.4 6.5-8.8A4.5 4.5 0 0 0 14 6.7c-.8 0-1.6.2-2.3.7-.7-.5-1.5-.7-2.3-.7A4.5 4.5 0 0 0 5 11.2c0 3.4 2.3 6.3 7 8.8Z"
                />
                <path
                  d="M10 10.5c.4-.7 1.1-1 2-1 1.3 0 2.2.9 2.2 2 0 .9-.5 1.5-1.5 2.1-.9.5-1.2.9-1.2 1.7"
                />
                <path d="M11.5 17h.01" />
              </svg>
            </span>
            <span class="pill-label">Therapy</span>
          </button>
        </div>

        <p class="program-note" id="programNote">
          Your selected program helps us track demand and improve
          matching. Credits can be used across all office hours, not just
          one program.
        </p>
      </div>

      <button type="button" class="primary-cta" id="subscribeButton">
        Subscribe to Office Hours
      </button>

      <ul class="feature-list">
        <li>
          5 credits per month to use across MBA, Law, or Therapy office
          hours
        </li>
        <li>45 minutes per meeting with small-group access</li>
        <li>First come, first serve booking</li>
        <li>Maximum of 5 people per meeting</li>
        <li>Sessions happen every other week</li>
        <li>
          Better value for students who want more meetings at a lower
          per-session cost
        </li>
        <li>Simple monthly subscription with recurring access</li>
      </ul>

      <p class="footnote">
        Your selected program is used for internal analytics so we can
        understand demand, improve scheduling, and see which categories
        perform best.
      </p>
    </section>

    <section class="checkout-panel hidden" id="checkoutSection">
      <div class="checkout-header">
        <div class="stripe-wordmark">stripe</div>
        <span class="secure-pill">Secure checkout</span>
      </div>

      <div class="checkout-summary">
        <div class="summary-row">
          <span>Subscription</span>
          <span>Office Hours</span>
        </div>
        <div class="summary-row">
          <span>Selected program</span>
          <span id="summaryProgram">MBA</span>
        </div>
        <div class="summary-row">
          <span>Credit use</span>
          <span>All programs</span>
        </div>
        <div class="summary-row">
          <span>Billing</span>
          <span>Monthly</span>
        </div>
        <div class="summary-row total-row">
          <span>Total</span>
          <span>$200/month</span>
        </div>
      </div>

      <div class="payment-fields">
        <div class="field">
          <label>Card information</label>
          <div class="fake-input">4242 4242 4242 4242</div>
        </div>

        <div class="field-grid">
          <div class="field">
            <label>Expiration</label>
            <div class="fake-input">MM / YY</div>
          </div>

          <div class="field">
            <label>CVC</label>
            <div class="fake-input">CVC</div>
          </div>
        </div>

        <div class="field">
          <label>Name on card</label>
          <div class="fake-input">Full name</div>
        </div>
      </div>

      <button type="button" class="pay-button" id="payButton">
        Pay $200/month
      </button>

      <p class="checkout-note">
        Credits are flexible across all office hours. Your selected
        program is only used for internal tracking and analytics.
      </p>
    </section>
  </section>
@endsection

