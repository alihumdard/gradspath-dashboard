@extends('layouts.portal-student')

@section('title', 'Feedback - Grads Paths')
@section('portal_css_asset', 'assets/css/demo5.css')
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
  <section class="simple-layout">
    <section class="page-header clean-header">
      <div class="page-header-top">
        <div class="page-header-copy">
          <h1>Feedback</h1>
          <p>
            Browse mentor feedback, filter by program and mentor type, and review student
            experiences across Grads Paths.
          </p>
        </div>
      </div>
    </section>

    <section class="overview-grid">
      <div class="overview-card">
        <div class="overview-label">Average Rating</div>
        <div class="overview-value">{{ number_format((float) ($feedbackSummary['averageRating'] ?? 0), 1) }}</div>
        <div class="overview-sub">Across {{ (int) ($feedbackSummary['completedSessions'] ?? 0) }} completed sessions</div>
      </div>
      <div class="overview-card">
        <div class="overview-label">Recommend Rate</div>
        <div class="overview-value">{{ (int) ($feedbackSummary['recommendRate'] ?? 0) }}%</div>
        <div class="overview-sub">Students would book again</div>
      </div>
      <div class="overview-card">
        <div class="overview-label">Most Mentioned</div>
        <div class="overview-value small-value">{{ $feedbackSummary['topMentioned'] ?? 'No feedback yet' }}</div>
        <div class="overview-sub">
          {{ !empty($feedbackSummary['topMentionedOthers']) ? 'Also: '.implode(', ', $feedbackSummary['topMentionedOthers']) : 'More feedback themes will appear here.' }}
        </div>
      </div>
    </section>

    <section class="feedback-controls">
      <div class="control-left">
        <input
          type="text"
          id="mentorSearch"
          class="mentor-search"
          placeholder="Search mentor name, school, program..."
        />
      </div>
      <div class="control-right">
        <select id="programFilter">
          <option value="all">All Programs</option>
          <option value="mba">MBA</option>
          <option value="law">Law</option>
          <option value="therapy">Therapy</option>
        </select>
        <select id="professionFilter">
          <option value="all">All Mentor Types</option>
          <option value="graduate">Graduate Mentors</option>
          <option value="professional">Professional Mentors</option>
        </select>
        <select id="sortMentors">
          <option value="rating">Highest Rating</option>
          <option value="reviews">Most Reviews</option>
          <option value="sessions">Most Sessions</option>
          <option value="name">Name A-Z</option>
        </select>
      </div>
    </section>

    <section id="categorySections"></section>
  </section>
@endsection

@section('portal_after_shell')
  <div class="modal-overlay" id="mentorModal">
    <div class="mentor-modal full-form-modal">
      <button class="modal-close" id="closeModal" type="button" aria-label="Close modal">
        &times;
      </button>
      <div class="direct-form-view" id="directFormView"></div>
    </div>
  </div>
@endsection

@section('page_js')
  <script id="feedbackPageData" type="application/json">@json($feedbackPageData ?? [])</script>
  <script src="{{ asset('assets/js/demo5.js') }}"></script>
@endsection
