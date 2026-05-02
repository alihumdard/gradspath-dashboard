@extends('layouts.portal-mentor')

@section('title', 'Mentor Settings - Grads Paths')
@section('portal_css_asset', 'assets/css/demo10.css')
@section('portal_js_asset', 'assets/js/demo10.js')
@section('portal_active_nav', 'settings')

@php
  $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
  $mentorName = trim(old('name', $user->name ?? ''));
  $mentorInitials = collect(preg_split('/\s+/', $mentorName) ?: [])->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
  $mentorAvatarUrl = $user->avatar_url ?: $mentor->avatar_url;
  $payoutButtonLabel = $mentor->payouts_enabled || $mentor->stripe_onboarding_complete
      ? 'Update payout details'
      : ($mentor->stripe_account_id ? 'Continue payout setup' : 'Enable Payouts');
  $mentorStatus = $mentor->status ?: 'pending';
  $mentorStatusLabel = [
      'active' => 'Active',
      'pending' => 'Under review',
      'paused' => 'Paused',
      'rejected' => 'Blocked',
  ][$mentorStatus] ?? 'Not active';
  $mentorProfileBlocked = $mentorStatus !== 'active';
  $mentorStatusMessage = [
      'pending' => 'Your mentor application is under review. Profile editing and mentor tools will unlock after approval.',
      'paused' => 'Your mentor profile has been paused by admin. Your profile and mentor tools are read-only until admin reactivates it.',
      'rejected' => 'Your mentor profile is blocked. Please contact support or admin if you believe this needs review.',
  ][$mentorStatus] ?? 'Your mentor profile is not active. Please contact support or admin.';
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
        <div class="mentor-settings-title-row">
          <h1>Mentor Settings</h1>
          <span class="mentor-status-badge mentor-status-badge--{{ $mentorStatus }}">
            {{ $mentorStatusLabel }}
          </span>
        </div>
        <p class="subtitle">
          Update the profile details students see across discovery and booking.
        </p>
        @if ($mentorProfileBlocked)
          <div class="mentor-status-lock" role="alert">
            <strong>Your mentor profile is currently restricted.</strong>
            <span>{{ $mentorStatusMessage }}</span>
          </div>
        @endif
        <div class="availability-alert availability-alert--error" id="mentorFormAlert" hidden></div>
        <form id="mentorForm" method="POST" action="{{ route('mentor.settings.update') }}" enctype="multipart/form-data" novalidate data-avatar-upload-form>
          @csrf
          @method('PATCH')
          <fieldset class="mentor-settings-fieldset" @disabled($mentorProfileBlocked)>
            <div class="settings-columns">
            <div class="settings-column">
              <section class="settings-card">
                <div class="settings-card-head">
                  <h2>Profile Basics</h2>
                  <p>Your account identity and verification details.</p>
                </div>

                <div class="field">
                  <label for="avatar">Profile Image</label>
                  <input
                    type="file"
                    id="avatar"
                    name="avatar"
                    class="avatar-upload-input"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    data-avatar-input
                  />
                  <label
                    for="avatar"
                    class="avatar-upload-card{{ $mentorAvatarUrl ? ' has-image' : '' }}"
                    data-avatar-dropzone
                  >
                    <div
                      class="avatar-upload-preview"
                      data-avatar-preview
                      @if (! $mentorAvatarUrl) hidden @endif
                    >
                      @if ($mentorAvatarUrl)
                        <img src="{{ $mentorAvatarUrl }}" alt="{{ $mentorName ?: 'Mentor' }}" data-avatar-preview-image />
                      @else
                        <span data-avatar-preview-fallback>{{ $mentorInitials }}</span>
                      @endif
                    </div>
                    <svg class="avatar-upload-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path fill="currentColor" d="M11 20v-9.17l-3.59 3.58L6 13l6-6 6 6-1.41 1.41L13 10.83V20zM5 4h14v2H5z" />
                    </svg>
                    <div class="avatar-upload-copy">
                      <span class="avatar-upload-title">Drop image here to upload, or click here to browse</span>
                      <span class="avatar-upload-formats">PNG, JPG, or JPEG</span>
                      <span class="avatar-upload-filename" data-avatar-file-name @if (! $mentorAvatarUrl) hidden @endif>
                        {{ $mentorAvatarUrl ? 'Current image selected' : '' }}
                      </span>
                    </div>
                  </label>
                  <p class="helper-text">Upload a JPG, PNG, or WebP image up to 5 MB.</p>
                  <p class="error-text">{{ $viewErrors->first('avatar') }}</p>
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
                    @if ($zoomConnectionStatus === 'error')
                      <div class="mentor-status-lock" role="alert">
                        <strong>Your Zoom connection needs to be reconnected before students can book Zoom sessions.</strong>
                      </div>
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
                        @if ($mentorProfileBlocked)
                          <span class="primary-btn is-disabled" aria-disabled="true">
                            {{ $zoomConnectionStatus === 'error' ? 'Reconnect Zoom' : 'Connect Zoom' }}
                          </span>
                        @else
                          <a href="{{ $zoomConnectUrl }}" class="primary-btn{{ $zoomConfigured ? '' : ' is-disabled' }}" @if (! $zoomConfigured) aria-disabled="true" @endif>
                            {{ $zoomConnectionStatus === 'error' ? 'Reconnect Zoom' : 'Connect Zoom' }}
                          </a>
                        @endif
                      @endif
                    </div>
                  </div>
                  <p class="helper-text">Students cannot complete new Zoom bookings with you until this connection is active.</p>
                  <p class="error-text">{{ $viewErrors->first('zoom') }}</p>
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
                        @disabled($mentorProfileBlocked)
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
          </fieldset>
        </form>
        <form id="zoomDisconnectForm" method="POST" action="{{ $zoomDisconnectUrl }}">
          @csrf
          @method('DELETE')
        </form>
      </section>
    </div>
  </div>
@endsection

@section('page_js')
  <script>
    const mentorAvatarForm = document.querySelector("[data-avatar-upload-form]");
    const mentorAvatarInput = mentorAvatarForm?.querySelector("[data-avatar-input]");
    const mentorAvatarDropzone = mentorAvatarForm?.querySelector("[data-avatar-dropzone]");
    const mentorAvatarPreview = mentorAvatarForm?.querySelector("[data-avatar-preview]");
    const mentorAvatarPreviewImage = mentorAvatarForm?.querySelector("[data-avatar-preview-image]");
    const mentorAvatarPreviewFallback = mentorAvatarForm?.querySelector("[data-avatar-preview-fallback]");
    const mentorAvatarFileName = mentorAvatarForm?.querySelector("[data-avatar-file-name]");

    function renderMentorAvatarFile(file) {
      if (!mentorAvatarDropzone || !mentorAvatarPreview || !mentorAvatarFileName || !file) {
        return;
      }

      mentorAvatarDropzone.classList.add("has-image");
      mentorAvatarPreview.hidden = false;
      mentorAvatarFileName.hidden = false;
      mentorAvatarFileName.textContent = file.name;

      const objectUrl = URL.createObjectURL(file);

      if (mentorAvatarPreviewImage) {
        mentorAvatarPreviewImage.src = objectUrl;
      } else {
        const image = document.createElement("img");
        image.src = objectUrl;
        image.alt = "Mentor profile image preview";
        image.setAttribute("data-avatar-preview-image", "");
        mentorAvatarPreview.innerHTML = "";
        mentorAvatarPreview.appendChild(image);
      }

      if (mentorAvatarPreviewFallback) {
        mentorAvatarPreviewFallback.remove();
      }
    }

    if (mentorAvatarInput && mentorAvatarDropzone) {
      mentorAvatarInput.addEventListener("change", () => {
        const [file] = mentorAvatarInput.files || [];
        renderMentorAvatarFile(file);
      });

      ["dragenter", "dragover"].forEach((eventName) => {
        mentorAvatarDropzone.addEventListener(eventName, (event) => {
          event.preventDefault();
          mentorAvatarDropzone.classList.add("dragover");
        });
      });

      ["dragleave", "dragend", "drop"].forEach((eventName) => {
        mentorAvatarDropzone.addEventListener(eventName, (event) => {
          event.preventDefault();
          mentorAvatarDropzone.classList.remove("dragover");
        });
      });

      mentorAvatarDropzone.addEventListener("drop", (event) => {
        const [file] = event.dataTransfer?.files || [];
        if (!file) {
          return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        mentorAvatarInput.files = transfer.files;
        renderMentorAvatarFile(file);
      });
    }
  </script>
@endsection
