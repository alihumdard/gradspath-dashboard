@extends('layouts.portal-student')

@section('title', 'Mentor Notes - Grads Paths')
@section('portal_css_asset', 'assets/css/demo8.css')
@section('portal_active_nav', 'mentor-notes')

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
    <main class="page">
      <header class="page-header">
        <div class="page-title-wrap">
          <h1>Mentor Notes</h1>
          <p>Notes written by your mentors after completed sessions. Only your own session notes are shown here.</p>
        </div>
      </header>

      <section class="filters-row filters-row-simple">
        <div class="filter-card">
          <label for="mentorSearch">Search Mentor</label>
          <div class="search-input">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2"></circle>
              <path d="M16 16L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
            </svg>
            <input id="mentorSearch" type="text" placeholder="Search by mentor name" />
          </div>
        </div>
      </section>

      <section class="section-header">
        <div>
          <h4>Your Mentor Notes</h4>
          <p>Open any note to review the full mentor session form response.</p>
        </div>
        <span id="resultsCount">0 users</span>
      </section>

      <section id="usersGrid" class="users-grid"></section>

      <section id="emptyState" class="empty-state hidden">
        <h5>No mentor notes found</h5>
        <p>Your mentors’ session notes will appear here after they are submitted.</p>
      </section>
    </main>
  </div>
@endsection

@section('portal_after_shell')
  <div id="noteModal" class="modal-overlay hidden">
    <div class="modal-card">
      <button id="closeModal" class="modal-close" aria-label="Close modal" type="button">
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
            <p id="modalMeta">Mentor Name &bull; Service</p>
          </div>
        </div>

        <div class="modal-stats">
          <div class="stat-box">
            <span>Sessions</span>
            <strong id="modalSessions">0</strong>
          </div>
          <div class="stat-box">
            <span>Date</span>
            <strong id="modalDate">&mdash;</strong>
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
            <p id="modalDetailUserName">&mdash;</p>
          </div>
          <div class="detail-item">
            <label>User Email</label>
            <p id="modalUserEmail">&mdash;</p>
          </div>
          <div class="detail-item">
            <label>Mentor Name</label>
            <p id="modalMentorName">&mdash;</p>
          </div>
          <div class="detail-item">
            <label>Mentor Email</label>
            <p id="modalMentorEmail">&mdash;</p>
          </div>
          <div class="detail-item">
            <label>Date of Session</label>
            <p id="modalDetailDate">&mdash;</p>
          </div>
          <div class="detail-item">
            <label>Type of Session</label>
            <p id="modalDetailService">&mdash;</p>
          </div>
        </div>
      </div>

      <div class="answers-grid">
        <div class="answer-card">
          <h3>1. What did you work on during this session?</h3>
          <p id="answer1"></p>
        </div>
        <div class="answer-card">
          <h3>2. What should happen next, and what do you need most?</h3>
          <p id="answer2"></p>
        </div>
        <div class="answer-card">
          <h3>3. What was the result of the session?</h3>
          <p id="answer3"></p>
        </div>
        <div class="answer-card">
          <h3>4. What was one strength and one challenge from the session?</h3>
          <p id="answer4"></p>
        </div>
        <div class="answer-card">
          <h3>5. Any other notes to share?</h3>
          <p id="answer5"></p>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page_js')
  <script id="mentorNotesData" type="application/json">@json($mentorNotesPageData ?? [])</script>
  <script src="{{ asset('assets/js/demo8.js') }}"></script>
@endsection
