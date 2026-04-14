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
  $previewName = old('name', $user->name ?? '');
  $previewProgram = old('title', $mentor->title ?: match ($mentor->program_type) {
      'mba' => 'MBA',
      'law' => 'Law',
      'cmhc' => 'Counseling',
      'mft' => 'Marriage & Family Therapy',
      'msw' => 'Social Work',
      'clinical_psy' => 'Clinical Psychology',
      'therapy' => 'Therapy',
      default => '',
  });
  $previewSchool = old('grad_school_display', $mentor->grad_school_display ?? '');
  $previewBio = old('bio', $mentor->bio ?? '');
  $previewOfficeHours = old('office_hours_schedule', $mentor->office_hours_schedule ?? '');
  $ratingValue = $mentor->relationLoaded('rating') && $mentor->rating?->avg_stars
      ? number_format((float) $mentor->rating->avg_stars, 1)
      : 'New';
  $initials = collect(preg_split('/\s+/', trim($previewName)) ?: [])
      ->filter()
      ->take(2)
      ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
      ->implode('');
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

        @if (session('success'))
          <div class="success-message" style="display: block; margin-bottom: 20px;">
            {{ session('success') }}
          </div>
        @endif

        @if ($viewErrors->any())
          <div class="error-text" style="display: block; margin-bottom: 20px;">
            Please review the highlighted fields and try again.
          </div>
        @endif

        <form id="mentorForm" method="POST" action="{{ route('mentor.settings.update') }}" novalidate>
          @csrf
          @method('PATCH')

          <div class="field">
            <label for="fullName">Full Name</label>
            <input type="text" id="fullName" name="name" value="{{ old('name', $user->name ?? '') }}" placeholder="Enter your full name" />
            <p class="error-text" id="nameError">{{ $viewErrors->first('name') }}</p>
          </div>

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

          <div class="field-row">
            <div class="field">
              <label for="program">Display Title</label>
              <input type="text" id="program" name="title" value="{{ old('title', $mentor->title ?? '') }}" placeholder="MBA Mentor, Law Mentor..." />
              <p class="helper-text">This appears to students as your headline.</p>
              <p class="error-text" id="programError">{{ $viewErrors->first('title') }}</p>
            </div>

            <div class="field">
              <label for="programType">Program Type</label>
              <div class="select-wrap">
                <select id="programType" name="program_type">
                  <option value="">Select a program</option>
                  <option value="mba" @selected(old('program_type', $mentor->program_type) === 'mba')>MBA</option>
                  <option value="law" @selected(old('program_type', $mentor->program_type) === 'law')>Law</option>
                  <option value="therapy" @selected(old('program_type', $mentor->program_type) === 'therapy')>Therapy</option>
                  <option value="cmhc" @selected(old('program_type', $mentor->program_type) === 'cmhc')>Counseling</option>
                  <option value="mft" @selected(old('program_type', $mentor->program_type) === 'mft')>Marriage & Family Therapy</option>
                  <option value="msw" @selected(old('program_type', $mentor->program_type) === 'msw')>Social Work</option>
                  <option value="clinical_psy" @selected(old('program_type', $mentor->program_type) === 'clinical_psy')>Clinical Psychology</option>
                  <option value="other" @selected(old('program_type', $mentor->program_type) === 'other')>Other</option>
                </select>
              </div>
              <p class="error-text">{{ $viewErrors->first('program_type') }}</p>
            </div>
          </div>

          <div class="field">
            <label for="school">Grad School</label>
            <input type="text" id="school" name="grad_school_display" value="{{ old('grad_school_display', $mentor->grad_school_display ?? '') }}" placeholder="Harvard, Wharton, Yale Law..." />
            <p class="error-text" id="schoolError">{{ $viewErrors->first('grad_school_display') }}</p>
          </div>

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

          <div class="field">
            <label>Services Offered</label>
            <div class="slack-box">
              <p class="slack-text">Select the active services students can book with you.</p>
              <div class="service-options" style="display: grid; gap: 12px; margin-top: 16px;">
                @foreach ($services as $service)
                  <label style="display: flex; align-items: flex-start; gap: 12px;">
                    <input
                      type="checkbox"
                      name="service_config_ids[]"
                      value="{{ $service->id }}"
                      @checked(in_array($service->id, $selectedIds, true))
                      style="margin-top: 4px;"
                    />
                    <span>
                      <strong>{{ $service->service_name }}</strong><br />
                      <span style="opacity: 0.75;">{{ $service->duration_minutes }} min</span>
                    </span>
                  </label>
                @endforeach
              </div>
            </div>
            <p class="error-text">{{ $viewErrors->first('service_config_ids') ?: $viewErrors->first('service_config_ids.*') }}</p>
          </div>

          <div class="field">
            <label>Payouts</label>
            <div class="payout-box">
              <p class="payout-text">
                Payout onboarding is separate from profile save in this first version.
              </p>
              <p class="payout-text">
                Current status:
                <strong>{{ $mentor->payouts_enabled ? 'Enabled' : 'Not enabled yet' }}</strong>
              </p>
              <div class="payout-actions">
                <button type="button" class="primary-btn" id="enablePayoutsBtn">Enable Payouts</button>
                <span class="payout-status{{ $mentor->payouts_enabled ? ' enabled' : '' }}" id="payoutStatus">
                  {{ $mentor->payouts_enabled ? 'Enabled' : 'Not enabled' }}
                </span>
              </div>
            </div>
            <p class="error-text" id="payoutError"></p>
          </div>

          <button type="submit" class="save-btn">Save Changes</button>
        </form>
      </section>

      <aside class="preview-panel">
        <h2>What Students Will See</h2>

        <div class="mentor-card">
          <div class="card-top">
            <div class="avatar" id="avatar">
              <span id="avatarInitials">{{ $initials }}</span>
            </div>

            <div class="card-main">
              <h3 id="cardName">{{ $previewName }}</h3>
              <p class="card-subtitle" id="cardSubtitle">
                {{ trim(collect([$previewProgram, $previewSchool])->filter()->implode(' â€¢ ')) }}
              </p>

              <p class="office-hours-inline" id="officeHoursDisplay" @style(['display: none;' => $previewOfficeHours === ''])>
                <span>Office Hours:</span>
                <span id="officeHoursText">{{ $previewOfficeHours }}</span>
              </p>
            </div>

            <div class="rating-badge">
              <span class="star">â˜…</span>
              <span id="ratingValue">{{ $ratingValue }}</span>
            </div>
          </div>

          <div class="card-description-wrap">
            <p class="card-description" id="cardDescription">{{ $previewBio }}</p>
          </div>
        </div>
      </aside>
    </div>
  </div>
@endsection
