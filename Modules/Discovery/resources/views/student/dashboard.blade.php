@extends('layouts.portal-student')

@section('title', 'Dashboard - Grads Paths')
@section('portal_css_asset', 'assets/css/demo1.css')
@section('portal_js_asset', 'assets/js/demo1.js')
@section('portal_active_nav', 'dashboard')

@php
  $institutionsUrl = route('student.institutions.index');
@endphp

@section('portal_content')
  <section class="mentor-hero">
          <div class="mentor-hero-left">
            <h1>Find Mentors</h1>
            <p>Connect with alumni from top global institutions.</p>
          </div>

          <div class="mentor-hero-links">
            <a href="{{ route('student.mentors.index') }}">See all graduate mentors</a>
            <a href="{{ route('student.mentors.index') }}">See all professional mentors</a>
          </div>
        </section>

        <section class="content-section">
          <div class="section-head">
            <h2>Mentors of the Week</h2>
          </div>

          <div class="mentor-grid">
            @forelse ($featuredMentors as $mentor)
              @php
                $mentorActionUrl = route('student.mentor.book', $mentor['id']);
                $mentorActionLabel = 'Book Now';
              @endphp
              <article class="mentor-card">
                <div class="mentor-card-header">
                  <div class="mentor-card-identity">
                    <div class="mentor-avatar">{{ $mentor['initials'] }}</div>
                    <div>
                      <div class="mentor-name">{{ $mentor['name'] }}</div>
                      <div class="mentor-role">{{ $mentor['role'] }}</div>
                    </div>
                  </div>
                  <div class="mentor-rating">&#9733; {{ $mentor['rating'] }}</div>
                </div>

                <div class="mentor-office-hours">
                  <span class="mentor-office-hours-title">Office Hours:</span>
                  <span class="mentor-office-hours-time">{{ $mentor['officeHours'] }}</span>
                </div>

                <div class="read-more-block">
                  <div class="mentor-note-box read-more-text">
                    {{ $mentor['bio'] }}
                  </div>
                  <button class="read-more-btn" type="button">
                    <span class="read-more-label">Read More</span>
                    <span class="read-more-chevron">&#8964;</span>
                  </button>
                </div>

                <div class="services-accordion">
                  <button class="services-toggle" type="button">
                    <span class="services-toggle-text">SERVICES OFFERED</span>
                    <span class="services-toggle-icon">&#8964;</span>
                  </button>

                  <div class="services-dropdown">
                    <div class="service-grid">
                      @foreach ($mentor['services'] as $service)
                        <div class="service-pill">{{ $service }}</div>
                      @endforeach
                    </div>
                  </div>
                </div>

                <div class="student-note-box">
                  <div class="feedback-header">
                    <div class="student-note-title">Recent Feedback</div>
                    <button class="see-more-feedback" type="button">
                      See more Feedback
                    </button>
                  </div>

                  <div class="read-more-block feedback-read-more">
                    <p class="read-more-text">
                      &ldquo;{{ $mentor['review'] }}&rdquo;
                    </p>
                    <button class="read-more-btn" type="button">
                      <span class="read-more-label">Read More</span>
                      <span class="read-more-chevron">&#8964;</span>
                    </button>
                  </div>
                </div>

                <a href="{{ $mentorActionUrl }}" class="book-now-btn">{{ $mentorActionLabel }}</a>
              </article>
            @empty
              <article class="mentor-card">
                <div class="mentor-card-header">
                  <div class="mentor-card-identity">
                    <div class="mentor-avatar">GP</div>
                    <div>
                      <div class="mentor-name">Featured mentors coming soon</div>
                      <div class="mentor-role">We&apos;re preparing the next set of mentor highlights.</div>
                    </div>
                  </div>
                  <div class="mentor-rating">New</div>
                </div>

                <div class="mentor-office-hours">
                  <span class="mentor-office-hours-title">Office Hours:</span>
                  <span class="mentor-office-hours-time">Schedule updates soon</span>
                </div>

                <div class="read-more-block">
                  <div class="mentor-note-box read-more-text">
                    Check back shortly to see active featured mentors on the dashboard.
                  </div>
                </div>
              </article>
            @endforelse
          </div>
        </section>

        <section class="content-section institutions-section">
          <div class="section-head institution-head">
            <div>
              <h2>Explore by University</h2>
            </div>

            <a href="{{ $institutionsUrl }}" class="view-all-institutions-btn"
              >View all institutions</a
            >
          </div>

          <div class="school-grid">
            @forelse ($institutions as $institution)
              <a href="{{ $institutionsUrl }}" class="school-card">
                {{ $institution->display_name ?: $institution->name }}
              </a>
            @empty
              <div class="school-card">Universities coming soon</div>
            @endforelse
          </div>
  </section>
@endsection

@section('portal_after_shell')
  <div class="store-modal-overlay" id="storeModal">
      <div class="store-modal">
        <button class="modal-close" id="closeStoreBtn" type="button">&times;</button>

        <div class="store-modal-inner">
          <h2>Store</h2>
          <p>Select a service to continue.</p>

          <div class="store-option-row">
            <button
              class="store-option active-store-option"
              data-service="office-hours"
              type="button"
            >
              Office Hours
            </button>
            <button
              class="store-option"
              data-service="one-on-three"
              type="button"
            >
              1 on 3 Meeting
            </button>
            <button
              class="store-option"
              data-service="one-on-five"
              type="button"
            >
              1 on 5 Meeting
            </button>
          </div>

          <section class="service-panel" id="officeHoursPanel">
            <div class="service-card">
              <h3>Office Hours Subscription</h3>
              <p>Choose your program to continue.</p>

              <div class="pathway-row">
                <button
                  class="pathway-btn active-pathway"
                  data-pathway="MBA"
                  type="button"
                >
                  MBA
                </button>
                <button class="pathway-btn" data-pathway="Law" type="button">
                  Law
                </button>
                <button
                  class="pathway-btn"
                  data-pathway="Therapy"
                  type="button"
                >
                  Therapy
                </button>
              </div>

              <div class="credit-assignment-note" id="creditAssignmentNote">
                Credits will be applied to MBA office hours.
              </div>

              <div class="rules-box">
                <div class="rules-title">Membership rules</div>
                <ul>
                  <li>5 credits per month = 5 meetings</li>
                  <li>45 minutes per meeting</li>
                  <li>Small-group sessions with up to 5 students</li>
                  <li>Credits reset each month</li>
                </ul>
              </div>

              <div class="pricing-row">
                <div id="officeHoursProgramLabel">MBA</div>
                <div id="officeHoursPriceLabel">$200/month</div>
              </div>

              <div class="payment-row">
                <input type="text" placeholder="8448 8444 8888 8888" />
                <input type="text" placeholder="08 / 27" />
                <input type="text" placeholder="277" />
              </div>

              <button class="primary-btn full-btn" type="button">
                Subscribe
              </button>
            </div>
          </section>

          <section class="service-panel hidden-panel" id="oneOnThreePanel">
            <div class="service-card">
              <h3>1 on 3 Meeting</h3>
              <p>
                Choose how payment will be handled, then complete the meeting
                details.
              </p>

              <div class="payment-type-row">
                <button class="pay-type-btn active-pay-type" type="button">
                  Paying for the group
                </button>
                <button class="pay-type-btn" type="button">
                  Paying individually
                </button>
              </div>

              <div class="meeting-form-grid">
                <input type="text" placeholder="Applicant name 1" />
                <input type="text" placeholder="Applicant name 2" />
                <input type="text" placeholder="Applicant name 3" />
                <select>
                  <option>Select mentor</option>
                </select>
                <input type="date" />
                <input type="time" />
                <select class="full">
                  <option>Service</option>
                </select>
              </div>

              <div class="payment-row">
                <input type="text" placeholder="8448 8444 8888 8888" />
                <input type="text" placeholder="08 / 27" />
                <input type="text" placeholder="277" />
              </div>

              <button class="primary-btn full-btn" type="button">
                Pay and Book Meeting
              </button>
            </div>
          </section>

          <section class="service-panel hidden-panel" id="oneOnFivePanel">
            <div class="service-card">
              <h3>1 on 5 Meeting</h3>
              <p>
                Choose how payment will be handled, then complete the meeting
                details.
              </p>

              <div class="payment-type-row">
                <button class="pay-type-btn active-pay-type" type="button">
                  Paying for the group
                </button>
                <button class="pay-type-btn" type="button">
                  Paying individually
                </button>
              </div>

              <div class="meeting-form-grid">
                <input type="text" placeholder="Applicant name 1" />
                <input type="text" placeholder="Applicant name 2" />
                <input type="text" placeholder="Applicant name 3" />
                <input type="text" placeholder="Applicant name 4" />
                <input type="text" placeholder="Applicant name 5" />
                <select>
                  <option>Select mentor</option>
                </select>
                <input type="date" />
                <input type="time" />
                <select class="full">
                  <option>Service</option>
                </select>
              </div>

              <div class="payment-row">
                <input type="text" placeholder="8448 8444 8888 8888" />
                <input type="text" placeholder="08 / 27" />
                <input type="text" placeholder="277" />
              </div>

              <button class="primary-btn full-btn" type="button">
                Pay and Book Meeting
              </button>
            </div>
          </section>
        </div>
      </div>
  </div>
@endsection
