<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>dashboard - Users Notes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="{{ asset('assets/css/demo8.css') }}" />
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

          <a href="{{ url('/') }}" class="back-link">
            <span class="back-link-arrow">←</span>
            <span>Back to the Website</span>
          </a>
        </div>

        <nav class="sidebar-nav">
          <div class="nav-group">
            <a href="/student/dashboard" class="nav-item single-link">
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
            <a href="/student/mentor-notes" class="nav-item single-link active">
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
        <div class="page-shell">
          <main class="page">
            <header class="page-header">
              <div class="page-title-wrap">
                <h1>Users Notes</h1>
                <p>
                  Internal notes from all mentors. Only visible to mentors and
                  admins.
                </p>
              </div>
              <!-- <a href="/student/dashboard" class="dashboard-btn">Back to Dashboard</a> -->
            </header>

            <section class="filters-row filters-row-simple">
              <div class="filter-card">
                <label for="userSearch">Search User</label>
                <div class="search-input">
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle
                      cx="11"
                      cy="11"
                      r="6.5"
                      stroke="currentColor"
                      stroke-width="2"
                    ></circle>
                    <path
                      d="M16 16L21 21"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                    ></path>
                  </svg>
                  <input
                    id="userSearch"
                    type="text"
                    placeholder="Search by user name"
                  />
                </div>
              </div>

              <div class="filter-card">
                <label for="mentorSearch">Search Mentor</label>
                <div class="search-input">
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle
                      cx="11"
                      cy="11"
                      r="6.5"
                      stroke="currentColor"
                      stroke-width="2"
                    ></circle>
                    <path
                      d="M16 16L21 21"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                    ></path>
                  </svg>
                  <input
                    id="mentorSearch"
                    type="text"
                    placeholder="Search by mentor name"
                  />
                </div>
              </div>
            </section>

            <section class="section-header">
              <div>
                <h4>Mentor Feedback on Users</h4>
                <p>
                  Open any note to see the full mentor session form response.
                </p>
              </div>
              <span id="resultsCount">0 users</span>
            </section>

            <section id="usersGrid" class="users-grid"></section>

            <section id="emptyState" class="empty-state hidden">
              <h5>No user notes found</h5>
              <p>Try changing the search.</p>
            </section>
          </main>
        </div>
      </main>
    </div>
    <div id="noteModal" class="modal-overlay hidden">
      <div class="modal-card">
        <button
          id="closeModal"
          class="modal-close"
          aria-label="Close modal"
          type="button"
        >
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
              d="M6 6L18 18M18 6L6 18"
              stroke="currentColor"
              stroke-width="2.2"
              stroke-linecap="round"
            />
          </svg>
        </button>

        <div class="modal-header">
          <div class="modal-user">
            <div class="modal-initials" id="modalInitials">TC</div>
            <div class="modal-user-copy">
              <h2 id="modalUserName">User Name</h2>
              <p id="modalMeta">Mentor Name • Service</p>
            </div>
          </div>

          <div class="modal-stats">
            <div class="stat-box">
              <span>Sessions</span>
              <strong id="modalSessions">0</strong>
            </div>
            <div class="stat-box">
              <span>Date</span>
              <strong id="modalDate">—</strong>
            </div>
          </div>
        </div>

        <div class="modal-summary">
          <div class="summary-top">
            <span class="summary-label">Session Details</span>
            <span class="summary-service" id="modalService">Service</span>
          </div>

          <div class="modal-details-grid">
            <div class="detail-item">
              <label>User Name</label>
              <p id="modalDetailUserName">—</p>
            </div>
            <div class="detail-item">
              <label>User Email</label>
              <p id="modalUserEmail">—</p>
            </div>
            <div class="detail-item">
              <label>Mentor Name</label>
              <p id="modalMentorName">—</p>
            </div>
            <div class="detail-item">
              <label>Mentor Email</label>
              <p id="modalMentorEmail">—</p>
            </div>
            <div class="detail-item">
              <label>Date of Session</label>
              <p id="modalDetailDate">—</p>
            </div>
            <div class="detail-item">
              <label>Type of Session</label>
              <p id="modalDetailService">—</p>
            </div>
          </div>
        </div>

        <div class="answers-grid">
          <div class="answer-card">
            <h3>1. What did you work on during this session?</h3>
            <p id="answer1"></p>
          </div>
          <div class="answer-card">
            <h3>
              2. What should happen next, and what does the user need most?
            </h3>
            <p id="answer2"></p>
          </div>
          <div class="answer-card">
            <h3>3. What was the result of the session?</h3>
            <p id="answer3"></p>
          </div>
          <div class="answer-card">
            <h3>
              4. What was one strength and one challenge from the session?
            </h3>
            <p id="answer4"></p>
          </div>
          <div class="answer-card">
            <h3>5. Any other notes to share?</h3>
            <p id="answer5"></p>
          </div>
        </div>
      </div>
    </div>
    <script src="{{ asset('assets/js/demo8.js') }}"></script>
  </body>
</html>
