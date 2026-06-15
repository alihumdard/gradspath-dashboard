@extends('layouts.portal-student')

@section('title', 'Student Profile - Grads Paths')
@section('portal_css_asset', 'assets/css/profile-settings.css')
@section('portal_active_nav', 'settings')

@php
  $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

@section('portal_content')
  <div class="page">
    <div class="layout">
      <section class="form-panel">
        <h1>Student Profile</h1>
        <p class="subtitle">
          Keep your study profile up to date so the platform can show relevant programs and mentors.
        </p>
        <form method="POST" action="{{ route('student.settings.update') }}" novalidate>
          @csrf
          @method('PATCH')

          <div class="field">
            <label for="fullName">Full Name</label>
            <input
              type="text"
              id="fullName"
              name="name"
              value="{{ old('name', $user->name ?? '') }}"
              placeholder="Enter your full name"
            />
            <p class="error-text">{{ $viewErrors->first('name') }}</p>
          </div>

          <div class="field">
            <label for="programLevel">Program Level</label>
            <div class="select-wrap">
              <select id="programLevel" name="program_level">
                <option value="">Select your level</option>
                <option value="undergrad" @selected(old('program_level', $profile->program_level) === 'undergrad')>Undergrad</option>
                <option value="grad" @selected(old('program_level', $profile->program_level) === 'grad')>Grad</option>
                <option value="professional" @selected(old('program_level', $profile->program_level) === 'professional')>Professional</option>
              </select>
            </div>
            <p class="error-text">{{ $viewErrors->first('program_level') }}</p>
          </div>

          <div class="field">
            <label for="programType">Program Type</label>
            <div class="select-wrap">
              <select id="programType" name="program_type">
                <option value="">Select your focus</option>
                <option value="mba" @selected(old('program_type', $profile->program_type) === 'mba')>MBA</option>
                <option value="law" @selected(old('program_type', $profile->program_type) === 'law')>Law</option>
                <option value="therapy" @selected(old('program_type', $profile->program_type) === 'therapy')>Therapy</option>
                <option value="cmhc" @selected(old('program_type', $profile->program_type) === 'cmhc')>Counseling</option>
                <option value="mft" @selected(old('program_type', $profile->program_type) === 'mft')>Marriage & Family Therapy</option>
                <option value="msw" @selected(old('program_type', $profile->program_type) === 'msw')>Social Work</option>
                <option value="clinical_psy" @selected(old('program_type', $profile->program_type) === 'clinical_psy')>Clinical Psychology</option>
                <option value="other" @selected(old('program_type', $profile->program_type) === 'other')>Other</option>
              </select>
            </div>
            <p class="error-text">{{ $viewErrors->first('program_type') }}</p>
          </div>

          <div class="field">
            <label for="universityId">Institution</label>
            <div class="select-wrap">
              <select id="universityId" name="university_id">
                <option value="">Select a known institution</option>
                @foreach ($universities as $university)
                  <option
                    value="{{ $university->id }}"
                    @selected((string) old('university_id', $profile->university_id) === (string) $university->id)
                  >
                    {{ $university->display_name ?: $university->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <p class="helper-text">Choose your institution from the list, or type it below if it is not listed.</p>
            <p class="error-text">{{ $viewErrors->first('university_id') }}</p>
          </div>

          <div class="field">
            <label for="institutionText">Institution Name</label>
            <input
              type="text"
              id="institutionText"
              name="institution_text"
              value="{{ old('institution_text', $profile->institution_text ?? '') }}"
              placeholder="Type your school or institution"
            />
            <p class="error-text">{{ $viewErrors->first('institution_text') }}</p>
          </div>

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
            <p class="helper-text">We use your timezone as the default when showing booking dates and times.</p>
            <button type="button" class="settings-timezone-detected-btn" id="settingsUseDetectedTimezone" hidden>
              Use detected timezone
            </button>
            <p class="settings-timezone-detected-hint" id="settingsDetectedTimezoneHint" hidden></p>
            <p class="error-text">{{ $viewErrors->first('timezone') }}</p>
          </div>

          <button type="submit" class="save-btn">Save Profile</button>
        </form>
      </section>

    </div>
  </div>
@endsection

@section('page_js')
  <script>
    const menuBtn = document.getElementById("mobileMenuToggle");
    const overlay = document.getElementById("sidebarOverlay");
    const shell = document.querySelector(".app-shell");
    const themeToggle = document.getElementById("themeToggle");
    const timezoneSelect = document.getElementById("settingsTimezone");
    const useDetectedTimezoneButton = document.getElementById("settingsUseDetectedTimezone");
    const detectedTimezoneHint = document.getElementById("settingsDetectedTimezoneHint");
    let detectedTimezone = "";

    function isSupportedTimezone(timezone) {
      const supported = Array.from(timezoneSelect.options).map((option) => option.value);
      return supported.includes(timezone);
    }

    function renderDetectedTimezoneButton() {
      if (!useDetectedTimezoneButton || !detectedTimezone || !timezoneSelect) {
        return;
      }

      const shouldShow = timezoneSelect.value !== detectedTimezone;
      useDetectedTimezoneButton.hidden = !shouldShow;
      useDetectedTimezoneButton.textContent = shouldShow
        ? `Use ${detectedTimezone}`
        : "Use detected timezone";

      if (detectedTimezoneHint) {
        detectedTimezoneHint.hidden = !shouldShow;
        detectedTimezoneHint.textContent = shouldShow
          ? `Your current detected timezone is ${detectedTimezone}.`
          : "";
      }
    }

    async function saveTimezone(timezone) {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      if (!csrfToken || !timezoneSelect.dataset.timezoneAutosaveUrl) {
        return;
      }

      await fetch(timezoneSelect.dataset.timezoneAutosaveUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
        body: JSON.stringify({ timezone }),
      }).catch(() => {});

      timezoneSelect.dataset.hasSavedTimezone = "true";
    }

    async function autoSaveDetectedTimezone() {
      if (!timezoneSelect) {
        return;
      }

      detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || "";
      if (!detectedTimezone || !isSupportedTimezone(detectedTimezone)) {
        return;
      }

      if (timezoneSelect.dataset.hasSavedTimezone !== "true") {
        timezoneSelect.value = detectedTimezone;
        await saveTimezone(detectedTimezone);
      }

      renderDetectedTimezoneButton();
    }

    function updateTheme(theme) {
      document.documentElement.setAttribute("data-theme", theme);
      localStorage.setItem("theme", theme);
      if (themeToggle) {
        themeToggle.textContent = theme === "dark" ? "Light Mode" : "Dark Mode";
      }
    }

    const currentSavedTheme = localStorage.getItem("theme") || "light";
    if (themeToggle) {
      themeToggle.textContent = currentSavedTheme === "dark" ? "Light Mode" : "Dark Mode";
      themeToggle.addEventListener("click", () => {
        const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
        updateTheme(currentTheme === "dark" ? "light" : "dark");
      });
    }

    if (menuBtn && shell) {
      menuBtn.addEventListener("click", () => shell.classList.add("sidebar-active"));
    }

    if (overlay && shell) {
      overlay.addEventListener("click", () => shell.classList.remove("sidebar-active"));
    }

    timezoneSelect?.addEventListener("change", renderDetectedTimezoneButton);

    useDetectedTimezoneButton?.addEventListener("click", async () => {
      if (!detectedTimezone || !timezoneSelect) {
        return;
      }

      timezoneSelect.value = detectedTimezone;
      renderDetectedTimezoneButton();
      await saveTimezone(detectedTimezone);
    });

    autoSaveDetectedTimezone();
  </script>
@endsection
