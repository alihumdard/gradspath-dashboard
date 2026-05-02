@extends('layouts.portal-student')

@section('title', 'Student Profile - Grads Paths')
@section('portal_css_asset', 'assets/css/demo10.css')
@section('portal_active_nav', 'settings')

@php
  $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
  $studentName = trim(old('name', $user->name ?? ''));
  $studentInitials = collect(preg_split('/\s+/', $studentName) ?: [])->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

@section('portal_content')
  <div class="page">
    <div class="layout">
      <section class="form-panel">
        <h1>Student Profile</h1>
        <p class="subtitle">
          Keep your study profile up to date so the platform can show relevant programs and mentors.
        </p>
        <form method="POST" action="{{ route('student.settings.update') }}" enctype="multipart/form-data" novalidate data-avatar-upload-form>
          @csrf
          @method('PATCH')

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
              class="avatar-upload-card{{ $user->avatar_url ? ' has-image' : '' }}"
              data-avatar-dropzone
            >
              <div
                class="avatar-upload-preview"
                data-avatar-preview
                @if (! $user->avatar_url) hidden @endif
              >
                @if ($user->avatar_url)
                  <img src="{{ $user->avatar_url }}" alt="{{ $studentName ?: 'Student' }}" data-avatar-preview-image />
                @else
                  <span data-avatar-preview-fallback>{{ $studentInitials }}</span>
                @endif
              </div>
              <svg class="avatar-upload-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="currentColor" d="M11 20v-9.17l-3.59 3.58L6 13l6-6 6 6-1.41 1.41L13 10.83V20zM5 4h14v2H5z" />
              </svg>
              <div class="avatar-upload-copy">
                <span class="avatar-upload-title">Drop image here to upload, or click here to browse</span>
                <span class="avatar-upload-formats">PNG, JPG, or JPEG</span>
                <span class="avatar-upload-filename" data-avatar-file-name @if (! $user->avatar_url) hidden @endif>
                  {{ $user->avatar_url ? 'Current image selected' : '' }}
                </span>
              </div>
            </label>
            <p class="helper-text">Upload a JPG, PNG, or WebP image up to 5 MB.</p>
            <p class="error-text">{{ $viewErrors->first('avatar') }}</p>
          </div>

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
            <p class="error-text">{{ $viewErrors->first('timezone') }}</p>
          </div>

          <button type="submit" class="save-btn">Save Profile</button>
        </form>
      </section>

      <aside class="preview-panel">
        <h2>Profile Summary</h2>

        <div class="mentor-card">
          <div class="card-top">
            <div class="avatar{{ $user->avatar_url ? ' has-image' : '' }}">
              @if ($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $studentName ?: 'Student' }}" style="width:100%;height:100%;object-fit:cover;" />
              @else
                <span>{{ $studentInitials }}</span>
              @endif
            </div>

            <div class="card-main">
              <h3>{{ old('name', $user->name ?? '') }}</h3>
              <p class="card-subtitle">
                {{ collect([
                  old('program_level', $profile->program_level),
                  old('program_type', $profile->program_type),
                ])->filter()->map(fn ($value) => ucfirst(str_replace('_', ' ', (string) $value)))->implode(' â€¢ ') }}
              </p>
            </div>
          </div>

          <div class="card-description-wrap">
            <p class="card-description">
              Institution:
              <strong>
                {{
                  old('institution_text', $profile->institution_text)
                  ?: $profile->university?->display_name
                  ?: $profile->university?->name
                  ?: 'Not set yet'
                }}
              </strong>
            </p>
          </div>
        </div>
      </aside>
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

    async function autoSaveDetectedTimezone() {
      if (!timezoneSelect || timezoneSelect.dataset.hasSavedTimezone === "true") {
        return;
      }

      const detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      if (!detectedTimezone) {
        return;
      }

      const supported = Array.from(timezoneSelect.options).map((option) => option.value);
      if (!supported.includes(detectedTimezone)) {
        return;
      }

      timezoneSelect.value = detectedTimezone;

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
        body: JSON.stringify({ timezone: detectedTimezone }),
      }).catch(() => {});
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

    const avatarForm = document.querySelector("[data-avatar-upload-form]");
    const avatarInput = avatarForm?.querySelector("[data-avatar-input]");
    const avatarDropzone = avatarForm?.querySelector("[data-avatar-dropzone]");
    const avatarPreview = avatarForm?.querySelector("[data-avatar-preview]");
    const avatarPreviewImage = avatarForm?.querySelector("[data-avatar-preview-image]");
    const avatarPreviewFallback = avatarForm?.querySelector("[data-avatar-preview-fallback]");
    const avatarFileName = avatarForm?.querySelector("[data-avatar-file-name]");

    function renderAvatarFile(file) {
      if (!avatarDropzone || !avatarPreview || !avatarFileName || !file) {
        return;
      }

      avatarDropzone.classList.add("has-image");
      avatarPreview.hidden = false;
      avatarFileName.hidden = false;
      avatarFileName.textContent = file.name;

      const objectUrl = URL.createObjectURL(file);

      if (avatarPreviewImage) {
        avatarPreviewImage.src = objectUrl;
      } else {
        const image = document.createElement("img");
        image.src = objectUrl;
        image.alt = "Student profile image preview";
        image.setAttribute("data-avatar-preview-image", "");
        avatarPreview.innerHTML = "";
        avatarPreview.appendChild(image);
      }

      if (avatarPreviewFallback) {
        avatarPreviewFallback.remove();
      }
    }

    if (avatarInput && avatarDropzone) {
      avatarInput.addEventListener("change", () => {
        const [file] = avatarInput.files || [];
        renderAvatarFile(file);
      });

      ["dragenter", "dragover"].forEach((eventName) => {
        avatarDropzone.addEventListener(eventName, (event) => {
          event.preventDefault();
          avatarDropzone.classList.add("dragover");
        });
      });

      ["dragleave", "dragend", "drop"].forEach((eventName) => {
        avatarDropzone.addEventListener(eventName, (event) => {
          event.preventDefault();
          avatarDropzone.classList.remove("dragover");
        });
      });

      avatarDropzone.addEventListener("drop", (event) => {
        const [file] = event.dataTransfer?.files || [];
        if (!file) {
          return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        avatarInput.files = transfer.files;
        renderAvatarFile(file);
      });
    }

    autoSaveDetectedTimezone();
  </script>
@endsection
