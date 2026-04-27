@extends('layouts.portal-mentor')
@section('title', 'Office Hours This Week - Grads Paths')
@section('portal_css_asset', 'assets/css/demo13.css')
@section('portal_active_nav', 'office-hours')
@section('portal_content')
        <section class="office-hours-page">
          <div class="page-shell">
            <!-- PAGE HEADER -->
            <div class="page-top">
              <div class="title-wrap">
                <h1>Office Hours This Week</h1>
                <!-- INFORMATION BANNER (ICON REMOVED) -->
                <div class="info-banner">
                  <div class="info-copy">
                    <p>
                      Mentors rotate the service offered during office hours
                      every other week. The scheduled service for that week will
                      be one of the following:
                      <strong>Tutoring</strong>,
                      <strong>Program Insights</strong>, and
                      <strong>Interview Prep</strong>.
                    </p>
                    <p>If multiple students are booked, the session will focus on
                      the designated service for that week.</p>
                    <p>
                      If only one student is booked 24 hours before the session,
                      that student may choose from any available service:
                      <strong>Tutoring</strong>,
                      <strong>Program Insights</strong>,
                      <strong>Interview Prep</strong>,
                      <strong>Application Review</strong>, and
                      <strong>Gap Year Planning</strong>.
                    </p>
                  </div>
                </div>
              </div>
</div>
            <!-- FILTER CONTROLS -->
            <div class="controls-grid">
              <div class="control-card">
                <div class="control-icon-box">
                  <img
                  src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png"
                  alt="Graduation cap icon"
                  class="control-icon-img"
                  />
                </div>
                <div class="control-content">
                  <h3>Mentor Type</h3>
                  <p>Choose who you want to view</p>
                  <div class="pill-row">
                    <button
                    class="filter-pill active"
                    data-filter-group="mentorType"
                    data-value="Graduates"
                    >Graduates</button>
                  <button
                  class="filter-pill"
                  data-filter-group="mentorType"
                  data-value="Professionals"
                  >Professionals</button>
              </div>
            </div>
          </div>
          <div class="control-card">
            <div class="control-icon-box text-icon" aria-hidden="true">&bull;</div>
            <div class="control-content">
              <h3>Program Type</h3>
              <p>Filter by mentor category</p>
              <div class="pill-row">
                <button
                class="filter-pill active"
                data-filter-group="programType"
                data-value="All"
                >All</button>
              <button
              class="filter-pill"
              data-filter-group="programType"
              data-value="MBA"
              >MBA</button>
            <button
            class="filter-pill"
            data-filter-group="programType"
            data-value="Law"
            >Law</button>
          <button
          class="filter-pill"
          data-filter-group="programType"
          data-value="Therapy"
          >Therapy</button>
      </div>
      </div>
      </div>
      </div>
        <!-- SEARCH -->
        <div class="search-row">
          <div class="search-card">
            <label for="mentorSearch">Search Mentor</label>
            <div class="search-input-wrap">
              <span class="search-icon" aria-hidden="true">&#128269;</span>
              <input
              type="text"
              id="mentorSearch"
              placeholder="Search by mentor name"
              />
            </div>
          </div>
          <div class="search-card">
            <label for="schoolSearch">Search School</label>
            <div class="search-input-wrap">
              <span class="search-icon" aria-hidden="true">&#128269;</span>
              <input
              type="text"
              id="schoolSearch"
              placeholder="Search by school"
              />
            </div>
          </div>
        </div>
        <!-- RESULTS BAR -->
        <div class="results-bar">
          <div class="results-chip" id="resultsSummary">Graduates &bull; All &bull; All Mentors &bull; All Schools</div>
          <div class="results-count">
            <span id="mentorCount">0</span> mentors shown</div>
        </div>
        <!-- MENTOR GRID -->
        <div id="mentorGrid" class="mentor-grid">
        </div>
      </div>
      </section>
@endsection

@section('page_js')
        <script src="{{ asset('assets/js/demo13.js') }}">
        </script>
@endsection
