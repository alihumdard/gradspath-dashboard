@extends('layouts.portal-mentor')
@section('title', 'Explore Mentors | Grads Paths')
@section('portal_css_asset', 'assets/css/demo4.css')
@section('portal_active_nav', 'mentors')
@section('portal_content')
        <div class="page-wrap">
          <div class="top-bar-content">
            <h1>Explore Mentors</h1>
            <p class="intro-text">Browse graduate students and professionals by category and school.</p>
          </div>
          <section class="controls-panel">
            <div class="control-card">
              <div class="control-heading">
                <div class="control-icon-box">
                  <svg viewBox="0 0 24 24" class="outline-icon">
                    <path d="M12 4 3 8.5 12 13l9-4.5L12 4Z">
                  </path>
                    <path d="M6 11.5V15c0 1.5 2.7 3 6 3s6-1.5 6-3v-3.5">
                  </path>
                  </svg>
                </div>
                <div>
                  <h3>Mentor Type</h3>
                  <p>Choose who you want to view</p>
                </div>
              </div>
              <div class="pill-row" id="tabPills">
                <button class="filter-pill active" data-tab="graduates">Graduates</button>
                <button class="filter-pill" data-tab="professionals">Professionals</button>
              </div>
            </div>
            <div class="control-card">
              <div class="control-heading">
                <div class="control-icon-box">
                  <svg viewBox="0 0 24 24" class="outline-icon">
                    <path d="M7 7h10M7 12h10M7 17h6M4 7h.01M4 12h.01M4 17h.01">
                  </path>
                  </svg>
                </div>
                <div>
                  <h3>Program Type</h3>
                  <p>Filter by category</p>
                </div>
              </div>
              <div class="pill-row" id="programPills">
              </div>
            </div>
            <div class="lower-controls">
              <div class="search-field">
                <label for="searchMentor">Search Mentor</label>
                <div class="search-input-wrap">
                  <span class="search-icon-inner">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="6">
                    </circle>
                      <path d="m20 20-4.2-4.2">
                    </path>
                    </svg>
                  </span>
                  <input type="text" id="searchMentor" placeholder="Search by mentor name" />
                </div>
              </div>
              <div class="search-field">
                <label for="searchSchool">Search School</label>
                <div class="search-input-wrap">
                  <span class="search-icon-inner">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="6">
                    </circle>
                      <path d="m20 20-4.2-4.2">
                    </path>
                    </svg>
                  </span>
                  <input type="text" id="searchSchool" placeholder="Search by school..." />
                </div>
              </div>
            </div>
          </section>
          <section class="results-meta">
            <div class="active-filters" id="activeFilters">All Mentors</div>
            <div class="results-count" id="resultsCount">0 mentors shown</div>
          </section>
          <section class="mentor-grid" id="mentorGrid">
          </section>
          <section class="empty-state hidden" id="emptyState">No mentors match these filters right now.</section>
        </div>
@endsection

@section('page_js')
        <script>window.mentorsData = @json($mentorsData);</script>
        <script src="{{ asset('assets/js/demo4.js') }}">
        </script>
@endsection
