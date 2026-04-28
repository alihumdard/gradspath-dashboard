@extends('layouts.portal-mentor')

@section('title', 'Mentor Settings - Grads Paths')
@section('portal_css_asset', 'assets/css/demo10.css')
@section('portal_js_asset', 'assets/js/demo10.js')
@section('portal_active_nav', 'settings')

@php
  $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
  $selectedIds = collect(old('service_config_ids', $selectedServiceIds ?? []))
      ->map(fn ($id) => (int) $id)
      ->all();
  $payoutButtonLabel = $mentor->payouts_enabled || $mentor->stripe_onboarding_complete
      ? 'Update payout details'
      : ($mentor->stripe_account_id ? 'Continue payout setup' : 'Enable Payouts');
@endphp

@section('page_topbar_left')
  <div class="search-wrap">
    <input type="text" class="search-input" placeholder="Search mentors, universities..." />
  </div>
@endsection

@section('portal_content')
  <div class="page">
    <div class="layout">
      <section class="form-panel">
        <h1>Mentor Settings</h1>
        <p class="subtitle">
          Update the profile details and services students see across discovery and booking.
        </p>
        <div class="availability-alert availability-alert--error" id="mentorFormAlert" hidden></div>
        <form id="mentorForm" method="POST" action="{{ route('mentor.settings.update') }}" novalidate>
          @csrf
          @method('PATCH')
          <div class="settings-columns">
            <div class="settings-column">
              <section class="settings-card">
                <div class="settings-card-head">
                  <h2>Profile Basics</h2>
                  <p>Your account identity and verification details.</p>
                </div>

                <div class="field-row">
                  <div class="field">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="name" value="{{ old('name', $user->name ?? '') }}" placeholder="Enter your full name" />
                    <p class="error-text" id="nameError">{{ $viewErrors->first('name') }}</p>
                  </div>

                  <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" placeholder="Enter your email address" />
                    <p class="helper-text">This is your main account email.</p>
                    <p class="error-text" id="emailError">{{ $viewErrors->first('email') }}</p>
                  </div>
                </div>

                <div class="field-row">
                  <div class="field">
                    <label for="mentorType">Mentor Type</label>
                    <div class="select-wrap">
                      <select id="mentorType" name="mentor_type">
                        <option value="graduate" @selected(old('mentor_type', $mentor->mentor_type ?? 'graduate') === 'graduate')>Grad Mentor</option>
                        <option value="professional" @selected(old('mentor_type', $mentor->mentor_type ?? 'graduate') === 'professional')>Professional</option>
                      </select>
                    </div>
                    <p class="helper-text">Graduate mentors must provide a .edu email for verification.</p>
                    <p class="error-text" id="mentorTypeError">{{ $viewErrors->first('mentor_type') }}</p>
                  </div>

                  <div class="field">
                    <label for="eduEmail">School / Verification Email</label>
                    <input type="email" id="eduEmail" name="edu_email" value="{{ old('edu_email', $mentor->edu_email ?? '') }}" placeholder="name@school.edu" />
                    <p class="error-text" id="eduEmailError">{{ $viewErrors->first('edu_email') }}</p>
                  </div>
                </div>

              </section>

              <section class="settings-card">
                <div class="settings-card-head">
                  <h2>Academic Profile</h2>
                  <p>Your academic background and how students see your school context.</p>
                </div>

                <div class="field-row">
                  <div class="field">
                    <label for="program">Display Title</label>
                    <input type="text" id="program" name="title" value="{{ old('title', $mentor->title ?? '') }}" placeholder="MBA Mentor, Law Mentor..." />
                    <p class="helper-text">This appears to students as your headline.</p>
                    <p class="error-text" id="programError">{{ $viewErrors->first('title') }}</p>
                  </div>

                  <div
                    class="field settings-university-picker"
                    data-mentor-university-picker
                    data-search-url="{{ route('universities.search') }}"
                    data-programs-url="{{ route('mentor.settings.university-programs') }}"
                  >
                    <label for="mentorUniversitySearch">University</label>
                    <div class="settings-picker-anchor">
                      <input
                        type="text"
                        id="mentorUniversitySearch"
                        value="{{ old('university_label', $selectedUniversity?->display_name ?: $selectedUniversity?->name ?: '') }}"
                        placeholder="Search universities..."
                        autocomplete="off"
                        data-university-search
                      />
                      <input
                        type="hidden"
                        name="university_id"
                        value="{{ old('university_id', $selectedUniversityId ?? '') }}"
                        data-university-id
                      />
                      <div class="settings-picker-results" data-university-results hidden></div>
                    </div>
                    <p class="helper-text">Choose the school your public profile should be linked to.</p>
                    <p class="error-text">{{ $viewErrors->first('university_id') }}</p>
                  </div>
                </div>

                <div class="field-row">
                  <div class="field">
                    <label for="universityProgram">Program</label>
                    <div class="select-wrap">
                      <select
                        id="universityProgram"
                        name="university_program_id"
                        data-program-select
                        data-selected-program-id="{{ $selectedUniversityProgramId ?? '' }}"
                        @disabled(($universityPrograms ?? collect())->isEmpty())
                      >
                        <option value="">Select a program</option>
                        @foreach (($universityPrograms ?? collect()) as $universityProgram)
                          <option
                            value="{{ $universityProgram->id }}"
                            @selected((string) ($selectedUniversityProgramId ?? '') === (string) $universityProgram->id)
                          >
                            {{ $universityProgram->program_name }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                    <p class="helper-text">
                      @if ($selectedUniversity)
                        Showing active programs for {{ $selectedUniversity->display_name ?: $selectedUniversity->name }}.
                      @else
                        Select a university first to load related programs.
                      @endif
                    </p>
                    <p class="error-text">{{ $viewErrors->first('university_program_id') }}</p>
                  </div>

                  <div class="field">
                    <label for="school">Grad School</label>
                    <input type="text" id="school" name="grad_school_display" value="{{ old('grad_school_display', $mentor->grad_school_display ?? '') }}" placeholder="Harvard, Wharton, Yale Law..." />
                    <p class="helper-text">This is the school name shown on your profile.</p>
                    <p class="error-text" id="schoolError">{{ $viewErrors->first('grad_school_display') }}</p>
                  </div>
                </div>
              </section>

              <section class="settings-card">
                <div class="settings-card-head">
                  <h2>Profile Content</h2>
                  <p>Write the summary students will read first.</p>
                </div>

                <div class="field">
                  <label for="bio">Short Bio</label>
                  <textarea id="bio" name="bio" rows="4" placeholder="Write a concise summary students will see first.">{{ old('bio', $mentor->bio ?? '') }}</textarea>
                  <p class="helper-text">Used in mentor cards and booking previews.</p>
                  <p class="error-text" id="bioError">{{ $viewErrors->first('bio') }}</p>
                </div>

                <div class="field">
                  <label for="description">Extended Description</label>
                  <textarea id="description" name="description" rows="6" placeholder="Add longer background, approach, and the kind of support you provide.">{{ old('description', $mentor->description ?? '') }}</textarea>
                  <p class="helper-text">Used when students expand your full profile.</p>
                  <p class="error-text" id="descriptionError">{{ $viewErrors->first('description') }}</p>
                </div>
              </section>
            </div>

            <div class="settings-column">
              <section class="settings-card">
                <div class="settings-card-head">
                  <h2>Booking Setup</h2>
                  <p>Availability details and links students use to book.</p>
                </div>

                <div class="field-row">
                  <div class="field">
                    <label for="officeHours">Office Hours</label>
                    <input type="text" id="officeHours" name="office_hours_schedule" value="{{ old('office_hours_schedule', $mentor->office_hours_schedule ?? '') }}" placeholder="Every Tuesday at 5 PM EST" />
                    <p class="error-text" id="officeHoursError">{{ $viewErrors->first('office_hours_schedule') }}</p>
                  </div>

                  <div class="field">
                    <label for="calendlyLink">Calendly Link</label>
                    <input type="url" id="calendlyLink" name="calendly_link" value="{{ old('calendly_link', $mentor->calendly_link ?? '') }}" placeholder="https://calendly.com/your-link" />
                    <p class="error-text" id="calendlyError">{{ $viewErrors->first('calendly_link') }}</p>
                  </div>
                </div>

                <div class="field-row">
                  <div class="field">
                    <label for="settingsTimezone">Timezone</label>
                    <div class="select-wrap">
                      <select
                        id="settingsTimezone"
                        name="timezone"
                        data-timezone-autosave-url="{{ $timezoneAutoSaveUrl }}"
                        data-has-saved-timezone="{{ $hasSavedTimezone ? 'true' : 'false' }}"
                      >
                        @foreach ($timezoneOptions as $value => $label)
                          <option value="{{ $value }}" @selected($selectedTimezone === $value)>{{ $label }} ({{ $value }})</option>
                        @endforeach
                      </select>
                    </div>
                    <p class="helper-text">We use your timezone as the default for scheduling and booking display.</p>
                    <p class="error-text">{{ $viewErrors->first('timezone') }}</p>
                  </div>

                  <div class="field">
                    <label for="isFeatured">Featured Mentor</label>
                    <div class="slack-box settings-inline-toggle">
                      <div>
                        <p class="slack-text" style="margin-bottom: 4px;">Show this mentor in featured sections across discovery.</p>
                        <p class="helper-text" style="margin: 0;">Turn this on to mark the profile as featured.</p>
                      </div>
                      <label for="isFeatured" class="settings-switch-label">
                        <input type="hidden" name="is_featured" value="0" />
                        <input
                          type="checkbox"
                          id="isFeatured"
                          name="is_featured"
                          value="1"
                          @checked((bool) old('is_featured', $mentor->is_featured))
                        />
                        <span>{{ old('is_featured', $mentor->is_featured) ? 'Enabled' : 'Disabled' }}</span>
                      </label>
                    </div>
                    <p class="error-text">{{ $viewErrors->first('is_featured') }}</p>
                  </div>
                </div>
              </section>

              <section class="settings-card">
                <div class="field">
                  <label>Zoom Host Connection</label>
                  <div class="payout-box">
                    <p class="payout-text">
                      Connect your own Zoom account so every booked meeting is created with you as the host and your Zoom name appears to students.
                    </p>
                    <p class="payout-text">
                      Current status:
                      <strong>
                        @if (! $zoomConfigured)
                          Zoom OAuth not configured
                        @elseif ($zoomConnectionStatus === 'connected')
                          Connected
                        @elseif ($zoomConnectionStatus === 'error')
                          Reconnect required
                        @else
                          Not connected
                        @endif
                      </strong>
                    </p>
                    @if ($zoomConnectedAccount)
                      <p class="helper-text" style="margin-top: 0;">Connected Zoom account: {{ $zoomConnectedAccount }}</p>
                    @endif
                    <div class="payout-actions">
                      @if ($zoomConnectionStatus === 'connected')
                        <button
                          type="submit"
                          form="zoomDisconnectForm"
                          class="primary-btn"
                        >
                          Disconnect Zoom
                        </button>
                      @else
                        <a href="{{ $zoomConnectUrl }}" class="primary-btn{{ $zoomConfigured ? '' : ' is-disabled' }}" @if (! $zoomConfigured) aria-disabled="true" @endif>
                          {{ $zoomConnectionStatus === 'error' ? 'Reconnect Zoom' : 'Connect Zoom' }}
                        </a>
                      @endif
                    </div>
                  </div>
                  <p class="helper-text">Students cannot complete new Zoom bookings with you until this connection is active.</p>
                  <p class="error-text">{{ $viewErrors->first('zoom') }}</p>
                </div>
              </section>

              <section class="settings-card">
                <div class="field">
                  <label>Services Offered</label>
                  <div class="services-panel">
                    <div class="services-panel-head">
                      <p class="services-eyebrow">Booking visibility</p>
                      <p class="slack-text">Select the active services students can book with you.</p>
                    </div>
                    <div class="service-options">
                      @foreach ($services as $service)
                        <label class="service-option">
                          <input
                            class="service-option-input"
                            type="checkbox"
                            name="service_config_ids[]"
                            value="{{ $service->id }}"
                            @checked(in_array($service->id, $selectedIds, true))
                          />
                          <span class="service-option-copy">
                            <strong>{{ $service->service_name }}</strong>
                            <span class="service-option-meta">{{ $service->duration_minutes }} min session</span>
                          </span>
                          <span class="service-option-toggle" aria-hidden="true">
                            <span class="service-option-toggle-thumb"></span>
                          </span>
                        </label>
                      @endforeach
                    </div>
                  </div>
                  <p class="error-text">{{ $viewErrors->first('service_config_ids') ?: $viewErrors->first('service_config_ids.*') }}</p>
                </div>
              </section>

              <section class="settings-card">
                <div class="field">
                  <label>Payouts</label>
                  <div class="payout-box">
                    <p class="payout-text">
                      Complete Stripe onboarding to receive payouts, then return here any time to update your bank or identity details.
                    </p>
                    <p class="payout-text">
                      Current status:
                      <strong id="payoutSummary">{{ $mentor->payouts_enabled ? 'Enabled' : 'Not enabled yet' }}</strong>
                    </p>
                    <div class="payout-actions">
                      <button
                        type="button"
                        class="primary-btn"
                        id="enablePayoutsBtn"
                        data-connect-url="{{ route('mentor.payouts.connect') }}"
                        data-status-url="{{ route('mentor.payouts.status') }}"
                        data-stripe-return="{{ $stripeReturn ? 'true' : 'false' }}"
                      >{{ $payoutButtonLabel }}</button>
                      <span class="payout-status{{ $mentor->payouts_enabled ? ' enabled' : '' }}" id="payoutStatus">
                        {{ $mentor->payouts_enabled ? 'Enabled' : 'Not enabled' }}
                      </span>
                    </div>
                  </div>
                  <p class="error-text" id="payoutError"></p>
                </div>
              </section>
            </div>
          </div>
          <button type="submit" class="save-btn">Save Changes</button>
        </form>
        <form id="zoomDisconnectForm" method="POST" action="{{ $zoomDisconnectUrl }}">
          @csrf
          @method('DELETE')
        </form>
      </section>
    </div>
  </div>
@endsection
