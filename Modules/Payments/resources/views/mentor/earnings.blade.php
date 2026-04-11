<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>dashboard</title>

    <link rel="stylesheet" href="{{ asset('assets/css/demo2.css') }}" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
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
        <header class="topbar">
          <div class="topbar-left">
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
          <div class="topbar-right">
            <button class="theme-toggle" id="themeToggle" type="button">
              Light / Dark
            </button>
            <div class="credits-box">Credits: <strong>16</strong></div>
            <a href="/student/store" class="store-btn">Store</a>
            <!-- <a
              href="/student/dashboard"
              class="recommended-badge"
              style="position: static"
              >← Back to Dashboard</a
            > -->
          </div>
        </header>

        <section class="pricing-shell">
          <section class="pricing-card">
            <div class="card-top">
              <!-- <a class="recommended-badge" href="/student/dashboard"
                >← Back to Dashboard</a
              > -->

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
      </main>
    </div>

    <script src="{{ asset('assets/js/demo2.js') }}"></script>
  </body>
</html>
