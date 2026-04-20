          @php
            $programUniversities = $programUniversities
              ?? \Modules\Institutions\app\Models\University::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            $programTypes = [
              'mba' => 'MBA',
              'law' => 'Law',
              'therapy' => 'Therapy',
              'cmhc' => 'CMHC',
              'mft' => 'MFT',
              'msw' => 'MSW',
              'clinical_psy' => 'Clinical Psychology',
              'other' => 'Other',
            ];

            $programTiers = [
              'elite' => 'Elite',
              'top' => 'Top',
              'regional' => 'Regional',
            ];

            $manualInstitutionRecords = \Modules\Institutions\app\Models\University::query()
              ->with([
                'programs' => fn ($query) => $query
                  ->where('is_active', true)
                  ->orderBy('program_name')
                  ->select(['id', 'university_id', 'program_name', 'program_type', 'description']),
              ])
              ->where('is_active', true)
              ->orderByRaw('COALESCE(display_name, name)')
              ->get(['id', 'name', 'display_name']);

            $manualPrograms = $manualInstitutionRecords
              ->flatMap(function ($institution) use ($programTypes) {
                return $institution->programs->map(function ($program) use ($institution, $programTypes) {
                  return [
                    'id' => (int) $program->id,
                    'name' => $program->program_name,
                    'category' => $programTypes[$program->program_type] ?? 'General',
                    'description' => $program->description ?: ('Program at ' . ($institution->display_name ?: $institution->name)),
                    'institutionId' => (int) $institution->id,
                    'programType' => $program->program_type,
                  ];
                });
              })
              ->values();

            $manualServiceRecords = \Modules\Payments\app\Models\ServiceConfig::query()
              ->where('is_active', true)
              ->orderBy('sort_order')
              ->orderBy('service_name')
              ->get([
                'id',
                'service_name',
                'duration_minutes',
                'price_1on1',
                'price_1on3_per_person',
                'price_1on5_per_person',
                'office_hours_subscription_price',
                'is_office_hours',
              ]);

            $manualServiceMeta = $manualServiceRecords
              ->mapWithKeys(function ($service) {
                $basePrice = $service->is_office_hours
                  ? $service->office_hours_subscription_price
                  : $service->price_1on1;

                $priceValue = $basePrice !== null ? (float) $basePrice : 0.0;

                return [
                  $service->service_name => [
                    'label' => $service->service_name,
                    'previewLabel' => $service->service_name,
                    'originalPrice' => $priceValue,
                    'currentPrice' => $priceValue,
                    'durationMinutes' => $service->duration_minutes,
                    'isOfficeHours' => (bool) $service->is_office_hours,
                  ],
                ];
              })
              ->all();

            $manualMentorRecords = \Modules\Settings\app\Models\Mentor::query()
              ->with([
                'user:id,name,email',
                'university:id,name,display_name',
                'services:id,service_name',
                'rating:id,mentor_id,avg_stars',
              ])
              ->orderBy('id')
              ->get();

            $manualMentors = $manualMentorRecords
              ->map(function ($mentor) use ($manualPrograms) {
                $programIds = $manualPrograms
                  ->filter(function ($program) use ($mentor) {
                    if ($mentor->university_id && $program['institutionId'] !== (int) $mentor->university_id) {
                      return false;
                    }

                    if ($mentor->program_type) {
                      return ($program['programType'] ?? null) === $mentor->program_type;
                    }

                    return true;
                  })
                  ->pluck('id')
                  ->map(fn ($id) => (int) $id)
                  ->values();

                if ($programIds->isEmpty() && $mentor->program_type) {
                  $programIds = $manualPrograms
                    ->where('programType', $mentor->program_type)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();
                }

                return [
                  'id' => (int) $mentor->id,
                  'type' => $mentor->title ?: ($mentor->mentor_type === 'professional' ? 'Professional Mentor' : 'Graduate Mentor'),
                  'name' => $mentor->user?->name ?: 'Unknown Mentor',
                  'email' => $mentor->user?->email ?: '',
                  'officeHours' => $mentor->office_hours_schedule ?: 'Not set',
                  'calendly' => $mentor->calendly_link ?: '',
                  'institutionId' => $mentor->university_id ? (int) $mentor->university_id : null,
                  'programIds' => $programIds->all(),
                  'description' => $mentor->description ?: ($mentor->bio ?: 'No description added yet.'),
                  'services' => $mentor->services->pluck('service_name')->values()->all(),
                ];
              })
              ->values();

            $manualInstitutions = $manualInstitutionRecords
              ->map(function ($institution) use ($manualPrograms, $manualMentors) {
                return [
                  'id' => (int) $institution->id,
                  'name' => $institution->display_name ?: $institution->name,
                  'category' => 'University',
                  'programIds' => $manualPrograms
                    ->where('institutionId', (int) $institution->id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
                  'mentorIds' => $manualMentors
                    ->filter(fn ($mentor) => ($mentor['institutionId'] ?? null) === (int) $institution->id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
                ];
              })
              ->values();

            $manualUserRecords = \Modules\Auth\app\Models\User::query()
              ->with([
                'credit:id,user_id,balance',
                'bookings.service:id,price_1on1,price_1on3_per_person,price_1on5_per_person,office_hours_subscription_price',
              ])
              ->whereDoesntHave('mentor')
              ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'))
              ->orderBy('name')
              ->get(['id', 'name', 'email']);

            $manualUsers = $manualUserRecords
              ->map(function ($user) {
                $totalSpent = $user->bookings->sum(function ($booking) {
                  $service = $booking->service;

                  if (! $service) {
                    return 0;
                  }

                  return match ($booking->session_type) {
                    '1on1' => (float) ($service->price_1on1 ?? 0),
                    '1on3' => (float) ($service->price_1on3_per_person ?? 0),
                    '1on5' => (float) ($service->price_1on5_per_person ?? 0),
                    'office_hours' => (float) ($service->office_hours_subscription_price ?? 0),
                    default => 0,
                  };
                });

                return [
                  'id' => (int) $user->id,
                  'name' => $user->name,
                  'email' => $user->email,
                  'officeHoursCredits' => (int) ($user->credit?->balance ?? 0),
                  'totalSpent' => (float) $totalSpent,
                  'lastRefund' => 0,
                ];
              })
              ->values();

            $manualFeedbackRecords = \Modules\Feedback\app\Models\Feedback::query()
              ->with([
                'student:id,name,email',
                'mentor.user:id,name,email',
                'mentor.university:id,name,display_name',
                'booking.service:id,service_name',
              ])
              ->latest('id')
              ->get();

            $manualFeedback = $manualFeedbackRecords
              ->map(function ($feedback) use ($programTypes) {
                $mentor = $feedback->mentor;
                $programLabel = $mentor?->program_type
                  ? ($programTypes[$mentor->program_type] ?? ucfirst(str_replace('_', ' ', $mentor->program_type)))
                  : 'General';

                return [
                  'id' => (int) $feedback->id,
                  'mentorId' => (int) ($feedback->mentor_id ?? 0),
                  'mentorName' => $mentor?->user?->name ?: 'Unknown Mentor',
                  'mentorType' => $mentor?->title ?: ($mentor?->mentor_type === 'professional' ? 'Professional Mentor' : 'Graduate Mentor'),
                  'mentorSchool' => $mentor?->university?->display_name ?: $mentor?->university?->name ?: ($mentor?->grad_school_display ?: 'Not set'),
                  'mentorProgram' => $programLabel,
                  'degree' => $mentor?->title ?: $programLabel,
                  'userName' => $feedback->student?->name ?: 'Unknown Student',
                  'rating' => (int) ($feedback->stars ?? 0),
                  'preparedness' => match ((int) ($feedback->preparedness_rating ?? 0)) {
                    5 => 'Excellent',
                    4 => 'Strong',
                    3 => 'Good',
                    2 => 'Needs improvement',
                    1 => 'Poor',
                    default => 'Not rated',
                  },
                  'recommend' => $feedback->recommend ? 'Yes' : 'No',
                  'serviceUsed' => $feedback->booking?->service?->service_name ?: ($feedback->service_type ?: 'Unknown Service'),
                  'dateOfSession' => optional($feedback->created_at)->format('F j, Y') ?: '',
                  'text' => $feedback->comment ?: '',
                  'statusNote' => $feedback->admin_note ?: '',
                ];
              })
              ->values();

            $manualData = [
              'programs' => $manualPrograms,
              'institutions' => $manualInstitutions,
              'mentors' => $manualMentors,
              'users' => $manualUsers,
              'feedback' => $manualFeedback,
              'serviceMeta' => $manualServiceMeta,
            ];
          @endphp

          <script>
            window.adminManualData = @json($manualData);
          </script>

          <section
            class="tab-panel"
            id="manual"
            data-initial-station="{{ old('manual_station', session('manual_station', 'mentor-station')) }}"
          >
            <div class="manual-content-wrapper">
                <main class="demo14-main">
                  <div class="demo14-page-wrap">
                    <section class="demo14-topbar">
                      <div>
                        <p class="demo14-eyebrow">MANUAL ADMIN</p>
                        <h1>Manual Controls</h1>
                        <p class="demo14-subtitle">
                          Manage mentors, users, institutions, programs,
                          services, and pricing in one place.
                        </p>
                      </div>
                      <div class="demo14-live-pill">Live</div>
                    </section>

                    <section class="demo14-search-wrap">
                      <div class="demo14-search-box">
                        <span class="demo14-search-icon">⌕</span>
                        <input
                          id="globalSearch"
                          type="text"
                          placeholder="Search mentors, users, emails, schools, programs, institutions, services, or feedback..."
                        />
                      </div>
                    </section>

                    <section class="demo14-stations-grid">
                      <button
                        class="demo14-station-tab active"
                        data-station="mentor-station"
                        type="button"
                      >
                        <span class="demo14-station-tab-number">1</span>
                        <span>Amend Mentor Account</span>
                      </button>
                      <button
                        class="demo14-station-tab"
                        data-station="refund-station"
                        type="button"
                      >
                        <span class="demo14-station-tab-number">2</span>
                        <span>Refund + Add Back Office Hours</span>
                      </button>
                      <button
                        class="demo14-station-tab"
                        data-station="feedback-station"
                        type="button"
                      >
                        <span class="demo14-station-tab-number">3</span>
                        <span>Amend / Delete Feedback</span>
                      </button>
                      <button
                        class="demo14-station-tab"
                        data-station="institution-station"
                        type="button"
                      >
                        <span class="demo14-station-tab-number">4</span>
                        <span>Create Institutions</span>
                      </button>
                      <button
                        class="demo14-station-tab"
                        data-station="program-station"
                        type="button"
                      >
                        <span class="demo14-station-tab-number">5</span>
                        <span>Create Services</span>
                      </button>
                      <button
                        class="demo14-station-tab"
                        data-station="pricing-station"
                        type="button"
                      >
                        <span class="demo14-station-tab-number">6</span>
                        <span>Service Pricing</span>
                      </button>
                      <button
                        class="demo14-station-tab"
                        data-station="program-create-station"
                        type="button"
                      >
                        <span class="demo14-station-tab-number">7</span>
                        <span>Create Programs</span>
                      </button>
                    </section>

                    <style>
                      #manual {
                        --station-theme-bg: #0b0e14;
                        --station-theme-surface: #131720;
                        --station-theme-surface-2: #1a1f2e;
                        --station-theme-surface-3: #1f2535;
                        --station-theme-border: rgba(255, 255, 255, 0.07);
                        --station-theme-border-focus: rgba(99, 179, 237, 0.5);
                        --station-theme-accent: #4f9cf9;
                        --station-theme-accent-2: #63b3ed;
                        --station-theme-accent-glow: rgba(79, 156, 249, 0.15);
                        --station-theme-text-primary: #f0f4ff;
                        --station-theme-text-secondary: #8892a4;
                        --station-theme-text-muted: #555f70;
                        --station-theme-success-bg: rgba(72, 187, 120, 0.1);
                        --station-theme-danger-bg: rgba(245, 101, 101, 0.1);
                        --station-theme-font: "DM Sans", sans-serif;
                        --station-theme-mono: "DM Mono", monospace;
                      }

                      #manual .demo14-station-panel .demo14-white-panel {
                        background:
                          radial-gradient(circle at top, rgba(79, 156, 249, 0.08), transparent 32%),
                          var(--station-theme-bg);
                        border: 1px solid var(--station-theme-border);
                        border-radius: 24px;
                        box-shadow: 0 28px 60px rgba(0, 0, 0, 0.32);
                        color: var(--station-theme-text-primary);
                        font-family: var(--station-theme-font);
                        padding: 32px;
                      }

                      #manual .demo14-station-panel .demo14-station-head {
                        align-items: flex-start;
                        margin-bottom: 24px;
                      }

                      #manual .demo14-station-panel .demo14-section-kicker,
                      #manual .demo14-station-panel .station4-intro,
                      #manual .demo14-station-panel .demo14-station-head p,
                      #manual .demo14-station-panel .demo14-card-title,
                      #manual .demo14-station-panel .demo14-preview-title,
                      #manual .demo14-station-panel .demo14-selected-services-title,
                      #manual .demo14-station-panel .demo14-info-label,
                      #manual .demo14-station-panel .service-used-title,
                      #manual .demo14-station-panel .toggle-label {
                        color: var(--station-theme-text-secondary);
                      }

                      #manual .demo14-station-panel .demo14-section-kicker {
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        margin-bottom: 14px;
                        color: var(--station-theme-accent-2);
                        font: 500 11px/1 var(--station-theme-mono);
                        letter-spacing: 0.14em;
                        text-transform: uppercase;
                      }

                      #manual .demo14-station-panel .demo14-section-kicker::before {
                        content: "";
                        width: 6px;
                        height: 6px;
                        border-radius: 999px;
                        background: var(--station-theme-accent);
                        box-shadow: 0 0 8px var(--station-theme-accent);
                      }

                      #manual .demo14-station-panel h2,
                      #manual .demo14-station-panel h3,
                      #manual .demo14-station-panel .demo14-card-title,
                      #manual .demo14-station-panel .demo14-preview-title,
                      #manual .demo14-station-panel .demo14-log-head h3 {
                        color: var(--station-theme-text-primary);
                      }

                      #manual .demo14-station-panel .demo14-form-grid,
                      #manual .demo14-station-panel .demo14-feedback-layout {
                        gap: 18px;
                      }

                      #manual .demo14-station-panel .demo14-field label {
                        color: var(--station-theme-text-secondary);
                        font-size: 12px;
                        font-weight: 500;
                        letter-spacing: 0.02em;
                      }

                      #manual .demo14-station-panel .demo14-field input,
                      #manual .demo14-station-panel .demo14-field select,
                      #manual .demo14-station-panel .demo14-field textarea,
                      #manual .demo14-station-panel .demo14-search-box-inline {
                        background: var(--station-theme-surface-2);
                        border: 1px solid var(--station-theme-border);
                        border-radius: 12px;
                        color: var(--station-theme-text-primary);
                        font: 14px/1.5 var(--station-theme-font);
                        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
                      }

                      #manual .demo14-station-panel .demo14-field input::placeholder,
                      #manual .demo14-station-panel .demo14-field textarea::placeholder {
                        color: var(--station-theme-text-muted);
                      }

                      #manual .demo14-station-panel .demo14-field input:hover,
                      #manual .demo14-station-panel .demo14-field select:hover,
                      #manual .demo14-station-panel .demo14-field textarea:hover {
                        background: var(--station-theme-surface-3);
                      }

                      #manual .demo14-station-panel .demo14-field input:focus,
                      #manual .demo14-station-panel .demo14-field select:focus,
                      #manual .demo14-station-panel .demo14-field textarea:focus {
                        background: var(--station-theme-surface-3);
                        border-color: var(--station-theme-border-focus);
                        box-shadow: 0 0 0 3px var(--station-theme-accent-glow);
                      }

                      #manual .demo14-station-panel .demo14-summary-box,
                      #manual .demo14-station-panel .demo14-feedback-card,
                      #manual .demo14-station-panel .demo14-preview-block,
                      #manual .demo14-station-panel .demo14-wide-box,
                      #manual .demo14-station-panel .demo14-info-box,
                      #manual .demo14-station-panel .demo14-selected-services-wrap,
                      #manual .demo14-station-panel .demo14-log-panel,
                      #manual .demo14-station-panel .demo14-mode-panel {
                        background: var(--station-theme-surface);
                        border: 1px solid var(--station-theme-border);
                        border-radius: 16px;
                        color: var(--station-theme-text-primary);
                      }

                      #manual .demo14-station-panel .demo14-summary-box {
                        background: linear-gradient(180deg, rgba(79, 156, 249, 0.12), rgba(79, 156, 249, 0.05));
                      }

                      #manual .demo14-station-panel .demo14-services-scroller,
                      #manual .demo14-station-panel .demo14-price-editor-grid,
                      #manual .demo14-station-panel .demo14-pricing-grid,
                      #manual .demo14-station-panel .demo14-checklist-grid,
                      #manual .demo14-station-panel .demo14-chip-grid {
                        gap: 14px;
                      }

                      #manual .demo14-station-panel .demo14-primary-btn,
                      #manual .demo14-station-panel .demo14-secondary-btn {
                        border-radius: 12px;
                        font-family: var(--station-theme-font);
                        font-weight: 500;
                      }

                      #manual .demo14-station-panel .demo14-primary-btn {
                        background: var(--station-theme-accent);
                        border: none;
                        box-shadow: 0 8px 20px rgba(79, 156, 249, 0.28);
                      }

                      #manual .demo14-station-panel .demo14-primary-btn:hover {
                        background: #6aaeff;
                      }

                      #manual .demo14-station-panel .demo14-secondary-btn {
                        background: var(--station-theme-surface-2);
                        border: 1px solid var(--station-theme-border);
                        color: var(--station-theme-text-secondary);
                      }

                      #manual .demo14-station-panel .demo14-secondary-btn:hover {
                        background: var(--station-theme-surface-3);
                        color: var(--station-theme-text-primary);
                      }

                      #manual .demo14-station-panel .demo14-info-value,
                      #manual .demo14-station-panel .mentor-preview-name,
                      #manual .demo14-station-panel .mentor-preview-meta,
                      #manual .demo14-station-panel .mentor-preview-description,
                      #manual .demo14-station-panel .demo14-quote-box,
                      #manual .demo14-station-panel .demo14-log-list {
                        color: var(--station-theme-text-primary);
                      }

                      #manual .demo14-station-panel .demo14-pill-value,
                      #manual .demo14-station-panel .demo14-recommend-pill {
                        background: rgba(79, 156, 249, 0.12);
                        color: var(--station-theme-accent-2);
                        border: 1px solid rgba(79, 156, 249, 0.18);
                      }

                      #manual .demo14-station-panel .demo14-stars {
                        color: #ffd166;
                      }

                      @media (max-width: 768px) {
                        #manual .demo14-station-panel .demo14-white-panel {
                          padding: 22px;
                          border-radius: 20px;
                        }
                      }
                    </style>

                    <!-- STATION 1 -->
                    <section
                      class="demo14-station-panel active"
                      id="mentor-station"
                    >
                      <div class="demo14-white-panel">
                        <div class="demo14-station-head">
                          <div>
                            <p class="demo14-section-kicker">STATION 1</p>
                            <h2>Mentor Settings</h2>
                            <p>
                              Update the mentor profile students see on the
                              platform.
                            </p>
                          </div>
                        </div>

                        <div class="demo14-form-grid">
                          <div class="demo14-field full">
                            <label for="mentorSelect"
                              >Select Mentor Account</label
                            >
                            <select id="mentorSelect"></select>
                          </div>

                          <div class="demo14-field">
                            <label for="mentorType">Mentor Type</label>
                            <input id="mentorType" type="text" />
                          </div>

                          <div class="demo14-field">
                            <label for="mentorName">Full Name</label>
                            <input id="mentorName" type="text" />
                          </div>

                          <div class="demo14-field">
                            <label for="mentorEmail">Email</label>
                            <input id="mentorEmail" type="email" />
                          </div>

                          <div class="demo14-field">
                            <label for="mentorOfficeHours">Office Hours</label>
                            <input id="mentorOfficeHours" type="text" />
                          </div>

                          <div class="demo14-field">
                            <label for="mentorCalendly">Calendly Link</label>
                            <input
                              id="mentorCalendly"
                              type="text"
                              placeholder="Enter Calendly link"
                            />
                          </div>

                          <div class="demo14-field">
                            <label for="mentorInstitution">Institution</label>
                            <select id="mentorInstitution"></select>
                          </div>

                          <div class="demo14-field">
                            <label for="mentorSchool"
                              >Institution Display Name</label
                            >
                            <input id="mentorSchool" type="text" readonly />
                          </div>

                          <div class="demo14-field full">
                            <label>Programs Connected to This Mentor</label>
                            <div
                              id="mentorProgramsPicker"
                              class="demo14-checklist-grid"
                            ></div>
                          </div>

                          <div class="demo14-field full">
                            <label for="mentorDescription">Description</label>
                            <textarea
                              id="mentorDescription"
                              rows="4"
                            ></textarea>
                          </div>

                          <div class="demo14-field full">
                            <div class="demo14-block-header">
                              <div>
                                <h3>Services Offered</h3>
                                <p>
                                  Select every service this mentor offers.
                                  Updated pricing shows automatically.
                                </p>
                              </div>
                            </div>

                            <div
                              class="demo14-services-scroller"
                              id="serviceCards"
                            ></div>

                            <div class="demo14-selected-services-wrap">
                              <div class="demo14-selected-services-title">
                                Selected Services
                              </div>
                              <div
                                class="demo14-services-selected-list"
                                id="servicesSelectedList"
                              ></div>
                            </div>
                          </div>

                          <div class="demo14-field full">
                            <div class="demo14-preview-grid">
                              <div class="demo14-preview-block">
                                <div class="demo14-preview-title">
                                  Expanded Mentor Card Preview
                                </div>
                                <div
                                  class="mentor-preview mentor-preview-expanded"
                                >
                                  <div class="mentor-preview-top">
                                    <div
                                      class="mentor-preview-avatar"
                                      id="previewAvatar"
                                    >
                                      SJ
                                    </div>
                                    <div class="mentor-preview-head-copy">
                                      <div
                                        class="mentor-preview-name"
                                        id="previewName"
                                      >
                                        Dr. Sarah Jenkin
                                      </div>
                                      <div class="mentor-preview-meta">
                                        <span id="previewType">PhD Person</span>
                                        <span>•</span>
                                        <span id="previewSchool">Harvard</span>
                                      </div>
                                    </div>
                                    <div class="mentor-preview-rating">
                                      ★ 5.0
                                    </div>
                                  </div>

                                  <div class="mentor-preview-office-hours">
                                    <span class="mentor-preview-label-black"
                                      >Office Hours:</span
                                    >
                                    <span id="previewOfficeHours"
                                      >Every Tuesday at 5 PM EST</span
                                    >
                                  </div>

                                  <div
                                    class="mentor-preview-description"
                                    id="previewDescription"
                                  >
                                    Expert in grad school applications for STEM
                                    fields. I help with statement of purpose
                                    review.
                                  </div>

                                  <div class="mentor-preview-readmore">
                                    Read More
                                  </div>

                                  <div class="mentor-preview-section-row">
                                    <div class="mentor-preview-section-title">
                                      Programs
                                    </div>
                                    <div class="mentor-preview-arrow">⌃</div>
                                  </div>
                                  <div
                                    class="mentor-preview-services-grid"
                                    id="mentorProgramPreviewGrid"
                                  ></div>

                                  <div class="mentor-preview-section-row">
                                    <div class="mentor-preview-section-title">
                                      Services Offered
                                    </div>
                                    <div class="mentor-preview-arrow">⌃</div>
                                  </div>
                                  <div
                                    class="mentor-preview-services-grid"
                                    id="expandedPreviewServices"
                                  ></div>

                                  <div class="mentor-preview-feedback-row">
                                    <div class="mentor-preview-feedback-title">
                                      Recent Feedback
                                    </div>
                                    <div class="mentor-preview-feedback-link">
                                      See more Feedback
                                    </div>
                                  </div>

                                  <div class="mentor-preview-feedback-text">
                                    “Very clear, practical advice that helped me
                                    improve my essays in one session.”
                                  </div>

                                  <div class="mentor-preview-readmore bottom">
                                    Read More
                                  </div>
                                </div>
                              </div>

                              <div class="demo14-preview-block">
                                <div class="demo14-preview-title">
                                  Collapsed Mentor Card Preview
                                </div>
                                <div
                                  class="mentor-preview mentor-preview-collapsed"
                                >
                                  <div class="mentor-preview-top">
                                    <div
                                      class="mentor-preview-avatar"
                                      id="previewAvatarSmall"
                                    >
                                      SJ
                                    </div>
                                    <div class="mentor-preview-head-copy">
                                      <div
                                        class="mentor-preview-name"
                                        id="previewNameSmall"
                                      >
                                        Dr. Sarah Jenkin
                                      </div>
                                      <div class="mentor-preview-meta">
                                        <span id="previewTypeSmall"
                                          >PhD Person</span
                                        >
                                        <span>•</span>
                                        <span id="previewSchoolSmall"
                                          >Harvard</span
                                        >
                                      </div>
                                    </div>
                                    <div class="mentor-preview-rating">
                                      ★ 5.0
                                    </div>
                                  </div>

                                  <div class="mentor-preview-office-hours">
                                    <span class="mentor-preview-label-black"
                                      >Office Hours:</span
                                    >
                                    <span id="previewOfficeHoursSmall"
                                      >Every Tuesday at 5 PM EST</span
                                    >
                                  </div>

                                  <div
                                    class="mentor-preview-description"
                                    id="previewDescriptionSmall"
                                  >
                                    Expert in grad school applications for STEM
                                    fields. I help with statement of purpose
                                    review.
                                  </div>

                                  <div
                                    class="mentor-preview-section-row collapsed-row"
                                  >
                                    <div class="mentor-preview-section-title">
                                      Services Offered
                                    </div>
                                    <div class="mentor-preview-arrow">⌄</div>
                                  </div>

                                  <div class="mentor-preview-feedback-row">
                                    <div class="mentor-preview-feedback-title">
                                      Recent Feedback
                                    </div>
                                    <div class="mentor-preview-feedback-link">
                                      See more Feedback
                                    </div>
                                  </div>

                                  <div class="mentor-preview-feedback-text">
                                    “Very clear, practical advice that helped me
                                    improve my essays in one session.”
                                  </div>

                                  <div class="mentor-preview-readmore bottom">
                                    Read More
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="demo14-action-row full">
                            <button
                              class="demo14-primary-btn"
                              id="saveMentorBtn"
                              type="button"
                            >
                              Save Mentor Changes
                            </button>
                          </div>
                        </div>
                      </div>
                    </section>

                    <!-- STATION 2 -->
                    <section class="demo14-station-panel" id="refund-station">
                      <div class="demo14-white-panel">
                        <div class="demo14-station-head">
                          <div>
                            <p class="demo14-section-kicker">STATION 2</p>
                            <h2>Refund + Office Hours</h2>
                            <p>
                              Use this only for refunds and adding back office
                              hour credits.
                            </p>
                          </div>
                        </div>

                        <div class="demo14-form-grid">
                          <div class="demo14-field full">
                            <label for="refundUserSelect">Select User</label>
                            <select id="refundUserSelect"></select>
                          </div>

                          <div class="demo14-field">
                            <label for="refundAmount">Refund Amount</label>
                            <input
                              id="refundAmount"
                              type="number"
                              min="0"
                              step="1"
                              placeholder="Enter amount"
                            />
                          </div>

                          <div class="demo14-field">
                            <label for="officeHoursAddBack"
                              >Office Hours Credits to Add Back</label
                            >
                            <input
                              id="officeHoursAddBack"
                              type="number"
                              min="0"
                              step="1"
                              placeholder="Enter credits"
                            />
                          </div>

                          <div class="demo14-field full">
                            <label for="refundReason">Reason</label>
                            <input
                              id="refundReason"
                              type="text"
                              placeholder="Reason for refund or restored credits"
                            />
                          </div>

                          <div class="demo14-field full">
                            <label>Selected User Snapshot</label>
                            <div
                              class="demo14-summary-box"
                              id="refundUserSummary"
                            ></div>
                          </div>

                          <div class="demo14-action-row full">
                            <button
                              class="demo14-primary-btn"
                              id="applyRefundBtn"
                              type="button"
                            >
                              Apply Refund / Credits
                            </button>
                          </div>
                        </div>
                      </div>
                    </section>

                    <!-- STATION 3 -->
                    <section class="demo14-station-panel" id="feedback-station">
                      <div class="demo14-white-panel">
                        <div class="demo14-station-head">
                          <div>
                            <p class="demo14-section-kicker">
                              POST-MEETING REVIEW
                            </p>
                            <h2>Feedback After Your Meeting</h2>
                            <p>
                              Review the full feedback submission and choose an
                              action before saving.
                            </p>
                          </div>
                        </div>

                        <div class="demo14-feedback-layout">
                          <div class="demo14-field full">
                            <label for="feedbackSelect"
                              >Select Feedback Item</label
                            >
                            <select id="feedbackSelect"></select>
                          </div>

                          <div class="demo14-feedback-card">
                            <div class="demo14-card-title">Session Details</div>

                            <div class="demo14-info-grid">
                              <div class="demo14-info-box">
                                <div class="demo14-info-label">
                                  Full Name of Mentor
                                </div>
                                <div
                                  class="demo14-info-value"
                                  id="feedbackMentorNameBox"
                                ></div>
                              </div>

                              <div class="demo14-info-box">
                                <div class="demo14-info-label">Program</div>
                                <div
                                  class="demo14-info-value"
                                  id="feedbackProgramBox"
                                ></div>
                              </div>

                              <div class="demo14-info-box">
                                <div class="demo14-info-label">Mentor Type</div>
                                <div
                                  class="demo14-info-value"
                                  id="feedbackMentorTypeBox"
                                ></div>
                              </div>

                              <div class="demo14-info-box">
                                <div class="demo14-info-label">School</div>
                                <div
                                  class="demo14-info-value"
                                  id="feedbackSchoolBox"
                                ></div>
                              </div>

                              <div class="demo14-info-box">
                                <div class="demo14-info-label">Degree</div>
                                <div
                                  class="demo14-info-value"
                                  id="feedbackDegreeBox"
                                ></div>
                              </div>

                              <div class="demo14-info-box">
                                <div class="demo14-info-label">
                                  Date of Session
                                </div>
                                <div
                                  class="demo14-info-value"
                                  id="feedbackDateBox"
                                ></div>
                              </div>
                            </div>

                            <div class="demo14-service-used-wrap">
                              <div class="demo14-info-label service-used-title">
                                Service Used
                              </div>
                              <div
                                class="demo14-services-scroller feedback-scroller"
                                id="feedbackServiceCards"
                              ></div>
                            </div>
                          </div>

                          <div class="demo14-feedback-card">
                            <div class="demo14-card-title">User Submission</div>

                            <div class="demo14-info-grid">
                              <div class="demo14-info-box">
                                <div class="demo14-info-label">Student</div>
                                <div
                                  class="demo14-info-value"
                                  id="feedbackStudentBox"
                                ></div>
                              </div>

                              <div class="demo14-info-box">
                                <div class="demo14-info-label">
                                  Overall Session Rating
                                </div>
                                <div
                                  class="demo14-stars"
                                  id="feedbackStarsBox"
                                ></div>
                              </div>

                              <div class="demo14-info-box">
                                <div class="demo14-info-label">
                                  Mentor Preparedness and Knowledge
                                </div>
                                <div
                                  class="demo14-pill-value"
                                  id="feedbackPreparednessBox"
                                ></div>
                              </div>
                            </div>

                            <div class="demo14-wide-box">
                              <div class="demo14-info-label">
                                Would You Recommend This Mentor?
                              </div>
                              <div
                                class="demo14-recommend-pill"
                                id="feedbackRecommendBox"
                              ></div>
                            </div>

                            <div class="demo14-wide-box">
                              <div class="demo14-info-label">
                                Quick Feedback
                              </div>
                              <div
                                class="demo14-quote-box"
                                id="feedbackQuickText"
                              ></div>
                            </div>
                          </div>

                          <div class="demo14-feedback-card">
                            <div class="demo14-card-title">Admin Action</div>

                            <div class="demo14-form-grid">
                              <div class="demo14-field">
                                <label for="feedbackAction"
                                  >Choose Action</label
                                >
                                <select id="feedbackAction" required>
                                  <option value="">Select an action</option>
                                  <option value="amend">Amend Feedback</option>
                                  <option value="delete">
                                    Delete Feedback
                                  </option>
                                </select>
                              </div>

                              <div class="demo14-field">
                                <label for="feedbackRating">Rating</label>
                                <input
                                  id="feedbackRating"
                                  type="number"
                                  min="1"
                                  max="5"
                                  step="1"
                                />
                              </div>

                              <div class="demo14-field full">
                                <label for="feedbackText">Feedback Text</label>
                                <textarea id="feedbackText" rows="4"></textarea>
                              </div>

                              <div class="demo14-field full">
                                <label for="feedbackNote">Admin Note</label>
                                <input
                                  id="feedbackNote"
                                  type="text"
                                  placeholder="Explain what was changed or why it was deleted"
                                />
                              </div>

                              <div class="demo14-field full">
                                <label>Feedback Snapshot</label>
                                <div
                                  class="demo14-summary-box"
                                  id="feedbackSummary"
                                ></div>
                              </div>

                              <div class="demo14-action-row full">
                                <button
                                  class="demo14-primary-btn"
                                  id="applyFeedbackBtn"
                                  type="button"
                                >
                                  Apply Feedback Change
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </section>

                    {{--
                    <!-- STATION 4 -->
                    <section
                      class="demo14-station-panel"
                      id="institution-station-legacy"
                    >
                      <div class="demo14-white-panel">
                        <div class="demo14-station-head">
                          <div>
                            <p class="demo14-section-kicker">STATION 4</p>
                            <h2>Create Institutions</h2>
                            <p>
                              Create a university record now. Programs and
                              mentor mappings will be managed in their own
                              forms next.
                            </p>
                          </div>
                        </div>

                        @if (session('manual_station') === 'institution-station' && session('success'))
                          <div
                            class="demo14-summary-box"
                            style="margin-bottom: 20px; border: 1px solid #d4f2df; background: #f3fff7; color: #1d6b3d;"
                          >
                            <strong>{{ session('success') }}</strong>
                          </div>
                        @endif

                        @if (old('manual_station') === 'institution-station' && $errors->any())
                          <div
                            class="demo14-summary-box"
                            style="margin-bottom: 20px; border: 1px solid #f3d0d8; background: #fff5f7; color: #84283e;"
                          >
                            <strong>Please fix the institution form errors below.</strong>
                            <ul style="margin: 12px 0 0 18px;">
                              @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                              @endforeach
                            </ul>
                          </div>
                        @endif

                        <style>
                          #institution-station #institutionAlphaTwoCode + .demo14-search-icon {
                            display: none;
                          }
                        </style>

                        <form
                          class="demo14-form-grid"
                          method="POST"
                          action="{{ route('admin.institutions.store') }}"
                        >
                          @csrf
                          <input type="hidden" name="manual_station" value="institution-station" />

                          <div class="demo14-field">
                            <label for="institutionName">Institution Name</label>
                            <input
                              id="institutionName"
                              name="name"
                              type="text"
                              value="{{ old('name') }}"
                              placeholder="Example: Boston College"
                              required
                            />
                          </div>

                          <div class="demo14-field">
                            <label for="institutionDisplayName">Display Name</label>
                            <input
                              id="institutionDisplayName"
                              name="display_name"
                              type="text"
                              value="{{ old('display_name') }}"
                              placeholder="Example: BC"
                            />
                          </div>

                          <div class="demo14-field">
                            <label for="institutionCountry">Country</label>
                            <input
                              id="institutionCountry"
                              name="country"
                              type="text"
                              value="{{ old('country', 'US') }}"
                              placeholder="US"
                            />
                          </div>

                          <div class="demo14-field">
                            <label for="institutionAlphaTwoCode">Country Code</label>
                            <input
                              id="institutionAlphaTwoCode"
                              name="alpha_two_code"
                              type="text"
                              value="{{ old('alpha_two_code') }}"
                              maxlength="2"
                              placeholder="US"
                            />
                              <span class="demo14-search-icon">⌕</span>
                            </div>
                          <div class="demo14-field">
                            <label for="institutionStateProvince">State / Province</label>
                            <input
                              id="institutionStateProvince"
                              name="state_province"
                              type="text"
                              value="{{ old('state_province') }}"
                              placeholder="Massachusetts"
                            />
                          </div>

                          <div
                            class="demo14-action-row full demo14-button-group"
                          >
                            <div class="demo14-field full">
                              <label for="institutionLogoUrl">Logo URL</label>
                              <input
                                id="institutionLogoUrl"
                                name="logo_url"
                                type="url"
                                value="{{ old('logo_url') }}"
                                placeholder="https://example.com/logo.png"
                              />
                            </div>

                            <div class="demo14-field full">
                              <label for="institutionDomains">Domains</label>
                              <textarea
                                id="institutionDomains"
                                name="domains"
                                rows="4"
                                placeholder="harvard.edu&#10;college.harvard.edu"
                              >{{ old('domains') }}</textarea>
                            </div>

                            <div class="demo14-field full">
                              <label for="institutionWebPages">Web Pages</label>
                              <textarea
                                id="institutionWebPages"
                                name="web_pages"
                                rows="4"
                                placeholder="https://www.harvard.edu/&#10;https://college.harvard.edu/"
                              >{{ old('web_pages') }}</textarea>
                            </div>

                            <div class="demo14-field full">
                              <label>Institution Snapshot</label>
                              <div class="demo14-summary-box">
                                This creates only the university record in the
                                <code>universities</code> table. Programs will
                                be added from the Program form next.
                              </div>
                            </div>

                            <div class="demo14-field full" style="display: flex; align-items: center; gap: 10px;">
                              <input type="hidden" name="is_active" value="0" />
                              <input
                                id="institutionIsActive"
                                name="is_active"
                                type="checkbox"
                                value="1"
                                {{ old('is_active', '1') ? 'checked' : '' }}
                              />
                              <label for="institutionIsActive" style="margin: 0;">Institution is active</label>
                            </div>

                            <button class="demo14-primary-btn" type="submit">
                              Create Institution
                            </button>
                          </div>
                        </form>
                      </div>
                    </section>
                    --}}

                    <section
                      class="demo14-station-panel"
                      id="institution-station"
                    >
                      <style>
                        @import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap");

                        #institution-station {
                          --station4-bg: #0b0e14;
                          --station4-surface: #131720;
                          --station4-surface-2: #1a1f2e;
                          --station4-surface-3: #1f2535;
                          --station4-border: rgba(255, 255, 255, 0.07);
                          --station4-border-focus: rgba(99, 179, 237, 0.5);
                          --station4-accent: #4f9cf9;
                          --station4-accent-glow: rgba(79, 156, 249, 0.15);
                          --station4-accent-2: #63b3ed;
                          --station4-text-primary: #f0f4ff;
                          --station4-text-secondary: #8892a4;
                          --station4-text-muted: #555f70;
                          --station4-success-bg: rgba(72, 187, 120, 0.1);
                          --station4-danger-bg: rgba(245, 101, 101, 0.1);
                          --station4-font: "DM Sans", sans-serif;
                          --station4-mono: "DM Mono", monospace;
                        }

                        #institution-station .demo14-white-panel {
                          background:
                            radial-gradient(ellipse 80% 40% at 50% -10%, rgba(79, 156, 249, 0.08) 0%, transparent 70%),
                            radial-gradient(ellipse 40% 30% at 80% 60%, rgba(99, 102, 241, 0.05) 0%, transparent 60%),
                            var(--station4-bg);
                          border: 1px solid var(--station4-border);
                          border-radius: 16px;
                          padding: 32px;
                          color: var(--station4-text-primary);
                          font-family: var(--station4-font);
                          box-shadow: 0 24px 80px rgba(3, 8, 20, 0.35);
                        }

                        #institution-station .demo14-section-kicker {
                          display: inline-flex;
                          align-items: center;
                          gap: 8px;
                          margin-bottom: 16px;
                          padding: 4px 12px;
                          border-radius: 999px;
                          background: rgba(79, 156, 249, 0.12);
                          border: 1px solid rgba(79, 156, 249, 0.2);
                          color: var(--station4-accent-2);
                          font: 500 11px/1 var(--station4-mono);
                          letter-spacing: 0.1em;
                        }

                        #institution-station .demo14-section-kicker::before {
                          content: "";
                          width: 6px;
                          height: 6px;
                          border-radius: 999px;
                          background: var(--station4-accent);
                          box-shadow: 0 0 8px var(--station4-accent);
                        }

                        #institution-station h2 {
                          margin: 0 0 10px;
                          color: var(--station4-text-primary);
                          font-size: 32px;
                          line-height: 1.15;
                          letter-spacing: -0.02em;
                        }

                        #institution-station .station4-intro {
                          margin: 0 0 24px;
                          max-width: 640px;
                          color: var(--station4-text-secondary);
                          font-size: 14px;
                          line-height: 1.7;
                        }

                        #institution-station .station4-message {
                          margin-bottom: 20px;
                          padding: 14px 16px;
                          border-radius: 10px;
                          border: 1px solid var(--station4-border);
                          font-size: 13px;
                          line-height: 1.6;
                        }

                        #institution-station .station4-message.success {
                          background: var(--station4-success-bg);
                          border-color: rgba(72, 187, 120, 0.3);
                          color: #d7ffe6;
                        }

                        #institution-station .station4-message.error {
                          background: var(--station4-danger-bg);
                          border-color: rgba(245, 101, 101, 0.3);
                          color: #ffd7de;
                        }

                        #institution-station .station4-message ul {
                          margin: 10px 0 0 18px;
                        }

                        #institution-station .station4-form {
                          display: grid;
                          gap: 28px;
                        }

                        #institution-station .station4-section {
                          display: grid;
                          gap: 16px;
                        }

                        #institution-station .station4-section-label {
                          display: flex;
                          align-items: center;
                          gap: 8px;
                          color: var(--station4-text-muted);
                          font: 500 11px/1 var(--station4-mono);
                          letter-spacing: 0.1em;
                          text-transform: uppercase;
                        }

                        #institution-station .station4-section-label::after {
                          content: "";
                          flex: 1;
                          height: 1px;
                          background: var(--station4-border);
                        }

                        #institution-station .station4-grid-2,
                        #institution-station .station4-grid-3 {
                          display: grid;
                          gap: 16px;
                        }

                        #institution-station .station4-grid-2 {
                          grid-template-columns: repeat(2, minmax(0, 1fr));
                        }

                        #institution-station .station4-grid-3 {
                          grid-template-columns: repeat(3, minmax(0, 1fr));
                        }

                        #institution-station .station4-field {
                          display: flex;
                          flex-direction: column;
                          gap: 8px;
                        }

                        #institution-station .station4-field label {
                          color: var(--station4-text-secondary);
                          font-size: 12px;
                          font-weight: 500;
                          letter-spacing: 0.02em;
                        }

                        #institution-station .station4-field input,
                        #institution-station .station4-field select,
                        #institution-station .station4-field textarea {
                          width: 100%;
                          border: 1px solid var(--station4-border);
                          border-radius: 10px;
                          background: var(--station4-surface-2);
                          color: var(--station4-text-primary);
                          padding: 11px 14px;
                          font: 14px/1.5 var(--station4-font);
                          transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
                        }

                        #institution-station .station4-field input::placeholder,
                        #institution-station .station4-field textarea::placeholder {
                          color: var(--station4-text-muted);
                        }

                        #institution-station .station4-field input:hover,
                        #institution-station .station4-field select:hover,
                        #institution-station .station4-field textarea:hover {
                          background: var(--station4-surface-3);
                          border-color: rgba(255, 255, 255, 0.12);
                        }

                        #institution-station .station4-field input:focus,
                        #institution-station .station4-field select:focus,
                        #institution-station .station4-field textarea:focus {
                          outline: none;
                          background: var(--station4-surface-3);
                          border-color: var(--station4-border-focus);
                          box-shadow: 0 0 0 3px var(--station4-accent-glow);
                        }

                        #institution-station .station4-field select {
                          appearance: none;
                          cursor: pointer;
                          color: var(--station4-text-secondary);
                          background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%238892a4' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
                          background-repeat: no-repeat;
                          background-position: right 12px center;
                          padding-right: 36px;
                        }

                        #institution-station .station4-field textarea {
                          min-height: 110px;
                          resize: vertical;
                          font-family: var(--station4-mono);
                          font-size: 13px;
                          line-height: 1.6;
                        }

                        #institution-station .station4-hint {
                          color: var(--station4-text-muted);
                          font: 11px/1.4 var(--station4-mono);
                        }

                        #institution-station .station4-info {
                          display: flex;
                          gap: 12px;
                          align-items: flex-start;
                          padding: 16px;
                          border-radius: 10px;
                          background: rgba(79, 156, 249, 0.06);
                          border: 1px solid rgba(79, 156, 249, 0.15);
                        }

                        #institution-station .station4-info-icon {
                          flex: 0 0 auto;
                          width: 18px;
                          height: 18px;
                          margin-top: 1px;
                          color: var(--station4-accent);
                        }

                        #institution-station .station4-info p {
                          margin: 0;
                          color: var(--station4-text-secondary);
                          font-size: 13px;
                          line-height: 1.7;
                        }

                        #institution-station .station4-info code {
                          color: var(--station4-accent-2);
                          background: rgba(79, 156, 249, 0.1);
                          padding: 1px 6px;
                          border-radius: 4px;
                          font: 12px/1.3 var(--station4-mono);
                        }

                        #institution-station .station4-footer {
                          display: flex;
                          justify-content: space-between;
                          align-items: center;
                          gap: 16px;
                          padding-top: 24px;
                          border-top: 1px solid var(--station4-border);
                          flex-wrap: wrap;
                        }

                        #institution-station .station4-toggle {
                          display: flex;
                          align-items: center;
                          gap: 12px;
                          color: var(--station4-text-secondary);
                          font-size: 13px;
                        }

                        #institution-station .station4-toggle-control {
                          position: relative;
                          width: 42px;
                          height: 24px;
                          flex: 0 0 auto;
                        }

                        #institution-station .station4-toggle-control input {
                          position: absolute;
                          inset: 0;
                          opacity: 0;
                          cursor: pointer;
                        }

                        #institution-station .station4-toggle-track {
                          position: absolute;
                          inset: 0;
                          border-radius: 999px;
                          background: var(--station4-surface-3);
                          border: 1px solid var(--station4-border);
                          transition: all 0.2s ease;
                        }

                        #institution-station .station4-toggle-thumb {
                          position: absolute;
                          top: 3px;
                          left: 3px;
                          width: 16px;
                          height: 16px;
                          border-radius: 999px;
                          background: var(--station4-text-muted);
                          transition: all 0.2s ease;
                        }

                        #institution-station .station4-toggle-control input:checked + .station4-toggle-track {
                          background: var(--station4-success-bg);
                          border-color: rgba(72, 187, 120, 0.35);
                        }

                        #institution-station .station4-toggle-control input:checked + .station4-toggle-track .station4-toggle-thumb {
                          background: #48bb78;
                          transform: translateX(18px);
                          box-shadow: 0 0 10px rgba(72, 187, 120, 0.35);
                        }

                        #institution-station .station4-submit {
                          display: inline-flex;
                          align-items: center;
                          gap: 10px;
                          border: 0;
                          border-radius: 10px;
                          background: var(--station4-accent);
                          color: #fff;
                          padding: 12px 24px;
                          font: 500 14px/1 var(--station4-font);
                          letter-spacing: 0.01em;
                          cursor: pointer;
                          box-shadow: 0 8px 22px rgba(79, 156, 249, 0.28);
                          transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
                        }

                        #institution-station .station4-submit:hover {
                          background: #6aaeff;
                          box-shadow: 0 12px 28px rgba(79, 156, 249, 0.34);
                        }

                        #institution-station .station4-submit:active {
                          transform: scale(0.98);
                        }

                        #institution-station .station4-submit svg {
                          width: 16px;
                          height: 16px;
                        }

                        @media (max-width: 720px) {
                          #institution-station .demo14-white-panel {
                            padding: 22px 18px;
                          }

                          #institution-station .station4-grid-2,
                          #institution-station .station4-grid-3 {
                            grid-template-columns: 1fr;
                          }

                          #institution-station h2 {
                            font-size: 26px;
                          }

                          #institution-station .station4-footer {
                            align-items: flex-start;
                            flex-direction: column;
                          }

                          #institution-station .station4-submit {
                            width: 100%;
                            justify-content: center;
                          }
                        }
                      </style>

                      <div class="demo14-white-panel">
                        <p class="demo14-section-kicker">STATION 4</p>
                        <h2>Create Institution</h2>
                        <p class="station4-intro">
                          Create a university record. Programs and mentor
                          mappings will be managed in their own forms next.
                        </p>

                        @if (session('manual_station') === 'institution-station' && session('success'))
                          <div class="station4-message success">
                            <strong>{{ session('success') }}</strong>
                          </div>
                        @endif

                        @if (old('manual_station') === 'institution-station' && $errors->any())
                          <div class="station4-message error">
                            <strong>Please fix the institution form errors below.</strong>
                            <ul>
                              @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                              @endforeach
                            </ul>
                          </div>
                        @endif

                        <form
                          class="station4-form"
                          method="POST"
                          action="{{ route('admin.institutions.store') }}"
                        >
                          @csrf
                          <input type="hidden" name="manual_station" value="institution-station" />
                          <input type="hidden" name="is_active" value="0" />

                          <div class="station4-section">
                            <div class="station4-section-label">Identity</div>
                            <div class="station4-grid-2">
                              <div class="station4-field">
                                <label for="institutionNameModern">Institution Name</label>
                                <input
                                  id="institutionNameModern"
                                  name="name"
                                  type="text"
                                  value="{{ old('name') }}"
                                  placeholder="Full legal name"
                                  required
                                />
                              </div>

                              <div class="station4-field">
                                <label for="institutionDisplayNameModern">Display Name</label>
                                <input
                                  id="institutionDisplayNameModern"
                                  name="display_name"
                                  type="text"
                                  value="{{ old('display_name') }}"
                                  placeholder="Short display name"
                                />
                              </div>
                            </div>

                            <div class="station4-grid-2">
                              <div class="station4-field">
                                <label for="institutionCountryModern">Country</label>
                                <input
                                  id="institutionCountryModern"
                                  name="country"
                                  type="text"
                                  value="{{ old('country', 'US') }}"
                                  placeholder="Country name"
                                />
                              </div>

                              <div class="station4-field">
                                <label for="institutionAlphaTwoCodeModern">Country Code</label>
                                <input
                                  id="institutionAlphaTwoCodeModern"
                                  name="alpha_two_code"
                                  type="text"
                                  value="{{ old('alpha_two_code') }}"
                                  maxlength="2"
                                  placeholder="e.g. US, PK"
                                />
                              </div>
                            </div>
                          </div>

                          <div class="station4-section">
                            <div class="station4-section-label">Location & Branding</div>
                            <div class="station4-grid-2">
                              <div class="station4-field">
                                <label for="institutionCityModern">City</label>
                                <input
                                  id="institutionCityModern"
                                  name="city"
                                  type="text"
                                  value="{{ old('city') }}"
                                  placeholder="e.g. Chengdu"
                                />
                              </div>

                              <div class="station4-field">
                                <label for="institutionStateProvinceModern">State / Province</label>
                                <input
                                  id="institutionStateProvinceModern"
                                  name="state_province"
                                  type="text"
                                  value="{{ old('state_province') }}"
                                  placeholder="e.g. Massachusetts"
                                />
                              </div>

                              <div class="station4-field">
                                <label for="institutionLogoUrlModern">Logo URL</label>
                                <input
                                  id="institutionLogoUrlModern"
                                  name="logo_url"
                                  type="url"
                                  value="{{ old('logo_url') }}"
                                  placeholder="https://example.com/logo.png"
                                />
                              </div>
                            </div>
                          </div>

                          <div class="station4-section">
                            <div class="station4-section-label">Web Presence</div>
                            <div class="station4-grid-2">
                              <div class="station4-field">
                                <label for="institutionDomainsModern">Domains</label>
                                <textarea
                                  id="institutionDomainsModern"
                                  name="domains"
                                  rows="4"
                                  placeholder="One domain per line&#10;example.edu"
                                >{{ old('domains') }}</textarea>
                                <span class="station4-hint">One domain per line</span>
                              </div>

                              <div class="station4-field">
                                <label for="institutionWebPagesModern">Web Pages</label>
                                <textarea
                                  id="institutionWebPagesModern"
                                  name="web_pages"
                                  rows="4"
                                  placeholder="One URL per line&#10;https://www.example.edu/"
                                >{{ old('web_pages') }}</textarea>
                                <span class="station4-hint">One URL per line</span>
                              </div>
                            </div>
                          </div>

                          <div class="station4-info">
                            <svg class="station4-info-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p>
                              This creates only the university record in the
                              <code>universities</code> table. Programs will be
                              added from the Program form next.
                            </p>
                          </div>

                          <div class="station4-footer">
                            <label class="station4-toggle" for="institutionIsActiveModern">
                              <span class="station4-toggle-control">
                                <input
                                  id="institutionIsActiveModern"
                                  name="is_active"
                                  type="checkbox"
                                  value="1"
                                  {{ old('is_active', '1') ? 'checked' : '' }}
                                />
                                <span class="station4-toggle-track">
                                  <span class="station4-toggle-thumb"></span>
                                </span>
                              </span>
                              <span>Institution is active</span>
                            </label>

                            <button class="station4-submit" type="submit">
                              <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                              </svg>
                              <span>Create Institution</span>
                            </button>
                          </div>
                        </form>
                      </div>

                    </section>

                    <!-- STATION 5 -->
                    <section class="demo14-station-panel" id="program-station">
                      <style>
                        #program-station {
                          --station5-bg: #0b0e14;
                          --station5-surface: #131720;
                          --station5-surface-2: #1a1f2e;
                          --station5-surface-3: #1f2535;
                          --station5-border: rgba(255, 255, 255, 0.07);
                          --station5-border-focus: rgba(99, 179, 237, 0.5);
                          --station5-accent: #4f9cf9;
                          --station5-accent-2: #63b3ed;
                          --station5-accent-glow: rgba(79, 156, 249, 0.15);
                          --station5-text-primary: #f0f4ff;
                          --station5-text-secondary: #8892a4;
                          --station5-text-muted: #555f70;
                          --station5-success: #48bb78;
                          --station5-success-bg: rgba(72, 187, 120, 0.1);
                          --station5-font: "DM Sans", sans-serif;
                          --station5-mono: "DM Mono", monospace;
                        }

                        #program-station .demo14-white-panel {
                          background: radial-gradient(circle at top, rgba(79, 156, 249, 0.08), transparent 32%), var(--station5-bg);
                          border: 1px solid var(--station5-border);
                          border-radius: 24px;
                          box-shadow: 0 28px 60px rgba(0, 0, 0, 0.32);
                          color: var(--station5-text-primary);
                          font-family: var(--station5-font);
                          padding: 32px;
                        }

                        #program-station .demo14-station-head {
                          align-items: flex-start;
                          margin-bottom: 24px;
                        }

                        #program-station .demo14-section-kicker {
                          display: inline-flex;
                          align-items: center;
                          gap: 8px;
                          margin-bottom: 14px;
                          color: var(--station5-accent-2);
                          font: 500 11px/1 var(--station5-mono);
                          letter-spacing: 0.14em;
                          text-transform: uppercase;
                        }

                        #program-station .demo14-section-kicker::before {
                          content: "";
                          width: 6px;
                          height: 6px;
                          border-radius: 999px;
                          background: var(--station5-accent);
                          box-shadow: 0 0 8px var(--station5-accent);
                        }

                        #program-station h2 {
                          color: var(--station5-text-primary);
                          font-size: 26px;
                          margin-bottom: 8px;
                        }

                        #program-station .station5-intro,
                        #program-station .station5-info-text,
                        #program-station .station5-inline-note {
                          color: var(--station5-text-secondary);
                          font-size: 14px;
                          line-height: 1.6;
                        }

                        #program-station .station5-shell,
                        #program-station .station5-panel-form,
                        #program-station .station5-section {
                          display: flex;
                          flex-direction: column;
                          gap: 16px;
                        }

                        #program-station .station5-shell,
                        #program-station .station5-panel-form {
                          gap: 24px;
                        }

                        #program-station .station5-panel-form {
                          margin-top: 24px;
                        }

                        #program-station .station5-section-label {
                          display: flex;
                          align-items: center;
                          gap: 8px;
                          color: var(--station5-text-muted);
                          font: 500 11px/1 var(--station5-mono);
                          letter-spacing: 0.1em;
                          text-transform: uppercase;
                        }

                        #program-station .station5-section-label::after {
                          content: "";
                          flex: 1;
                          height: 1px;
                          background: var(--station5-border);
                        }

                        #program-station .station5-grid-2,
                        #program-station .station5-grid-3,
                        #program-station .station5-pricing-grid,
                        #program-station .station5-price-row {
                          display: grid;
                          gap: 16px;
                        }

                        #program-station .station5-grid-2,
                        #program-station .station5-pricing-grid,
                        #program-station .station5-price-row {
                          grid-template-columns: repeat(2, minmax(0, 1fr));
                        }

                        #program-station .station5-grid-3 {
                          grid-template-columns: repeat(3, minmax(0, 1fr));
                        }

                        #program-station .station5-field {
                          display: flex;
                          flex-direction: column;
                          gap: 6px;
                        }

                        #program-station .station5-field label {
                          color: var(--station5-text-secondary);
                          font-size: 12px;
                          font-weight: 500;
                          letter-spacing: 0.02em;
                        }

                        #program-station .station5-field input,
                        #program-station .station5-field select,
                        #program-station .station5-field textarea {
                          width: 100%;
                          border: 1px solid var(--station5-border);
                          border-radius: 12px;
                          background: var(--station5-surface-2);
                          color: var(--station5-text-primary);
                          font: 14px/1.5 var(--station5-font);
                          padding: 10px 14px;
                        }

                        #program-station .station5-field select {
                          appearance: none;
                          background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%238892a4' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
                          background-repeat: no-repeat;
                          background-position: right 12px center;
                          padding-right: 36px;
                        }

                        #program-station .station5-field input::placeholder,
                        #program-station .station5-field textarea::placeholder {
                          color: var(--station5-text-muted);
                        }

                        #program-station .station5-field textarea {
                          min-height: 96px;
                          resize: vertical;
                        }

                        #program-station .station5-field input:hover,
                        #program-station .station5-field select:hover,
                        #program-station .station5-field textarea:hover,
                        #program-station .station5-field input:focus,
                        #program-station .station5-field select:focus,
                        #program-station .station5-field textarea:focus {
                          background: var(--station5-surface-3);
                        }

                        #program-station .station5-field input:focus,
                        #program-station .station5-field select:focus,
                        #program-station .station5-field textarea:focus {
                          border-color: var(--station5-border-focus);
                          box-shadow: 0 0 0 3px var(--station5-accent-glow);
                        }

                        #program-station .station5-info-box,
                        #program-station .station5-surface-box,
                        #program-station .station5-price-card {
                          border: 1px solid var(--station5-border);
                          border-radius: 16px;
                        }

                        #program-station .station5-info-box {
                          display: flex;
                          gap: 12px;
                          align-items: flex-start;
                          background: rgba(79, 156, 249, 0.06);
                          border-color: rgba(79, 156, 249, 0.15);
                          padding: 14px 16px;
                        }

                        #program-station .station5-info-icon {
                          width: 18px;
                          height: 18px;
                          flex-shrink: 0;
                          color: var(--station5-accent);
                          margin-top: 1px;
                        }

                        #program-station .station5-surface-box,
                        #program-station .station5-summary {
                          background: var(--station5-surface);
                          padding: 16px;
                        }

                        #program-station .station5-chip-grid,
                        #program-station .station5-session-chips,
                        #program-station .station5-button-group {
                          display: flex;
                          gap: 10px;
                          flex-wrap: wrap;
                        }

                        #program-station .demo14-chip,
                        #program-station .station5-chip {
                          display: inline-flex;
                          align-items: center;
                          gap: 6px;
                          background: var(--station5-surface-2);
                          border: 1px solid var(--station5-border);
                          border-radius: 999px;
                          color: var(--station5-text-secondary);
                          font-size: 13px;
                          font-weight: 500;
                          padding: 8px 14px;
                        }

                        #program-station .station5-chip-label input,
                        #program-station .station5-check-row input[type="checkbox"] {
                          display: none;
                        }

                        #program-station .station5-chip-label {
                          cursor: pointer;
                        }

                        #program-station .station5-chip-dot {
                          width: 7px;
                          height: 7px;
                          border-radius: 999px;
                          background: var(--station5-text-muted);
                        }

                        #program-station .station5-chip-label input:checked + .station5-chip {
                          background: rgba(79, 156, 249, 0.1);
                          border-color: rgba(79, 156, 249, 0.35);
                          color: var(--station5-accent-2);
                          box-shadow: 0 0 10px rgba(79, 156, 249, 0.12);
                        }

                        #program-station .station5-chip-label input:checked + .station5-chip .station5-chip-dot {
                          background: var(--station5-accent);
                          box-shadow: 0 0 6px var(--station5-accent);
                        }

                        #program-station .station5-price-card {
                          display: flex;
                          flex-direction: column;
                          gap: 10px;
                          padding: 14px;
                          background: var(--station5-surface-2);
                        }

                        #program-station .station5-price-card-title {
                          color: var(--station5-text-muted);
                          font: 500 11px/1 var(--station5-mono);
                          letter-spacing: 0.06em;
                          text-transform: uppercase;
                        }

                        #program-station .station5-office-box {
                          display: flex;
                          flex-direction: column;
                          gap: 12px;
                          padding: 16px;
                          background: var(--station5-surface-2);
                          border: 1px solid var(--station5-border);
                          border-radius: 12px;
                        }

                        #program-station .station5-toggle,
                        #program-station .station5-check-row,
                        #program-station .station5-footer {
                          display: flex;
                          align-items: center;
                          gap: 10px;
                          flex-wrap: wrap;
                        }

                        #program-station .station5-toggle {
                          color: var(--station5-text-secondary);
                          cursor: pointer;
                        }

                        #program-station .station5-toggle-control {
                          position: relative;
                          width: 40px;
                          height: 22px;
                          flex-shrink: 0;
                        }

                        #program-station .station5-toggle-control input {
                          position: absolute;
                          opacity: 0;
                          width: 0;
                          height: 0;
                        }

                        #program-station .station5-toggle-track {
                          position: absolute;
                          inset: 0;
                          background: var(--station5-surface-3);
                          border: 1px solid var(--station5-border);
                          border-radius: 999px;
                        }

                        #program-station .station5-toggle-thumb {
                          position: absolute;
                          top: 2px;
                          left: 2px;
                          width: 16px;
                          height: 16px;
                          border-radius: 999px;
                          background: var(--station5-text-muted);
                          transition: transform 0.2s ease;
                        }

                        #program-station .station5-toggle-control input:checked + .station5-toggle-track {
                          background: var(--station5-success-bg);
                          border-color: rgba(72, 187, 120, 0.3);
                        }

                        #program-station .station5-toggle-control input:checked + .station5-toggle-track .station5-toggle-thumb {
                          background: var(--station5-success);
                          transform: translateX(18px);
                        }

                        #program-station .station5-check-box {
                          width: 18px;
                          height: 18px;
                          border-radius: 5px;
                          border: 1px solid rgba(255, 255, 255, 0.15);
                          background: var(--station5-surface-2);
                          display: flex;
                          align-items: center;
                          justify-content: center;
                        }

                        #program-station .station5-check-box svg {
                          width: 11px;
                          height: 11px;
                          stroke: white;
                          stroke-width: 2.5;
                          fill: none;
                          opacity: 0;
                        }

                        #program-station .station5-check-row input[type="checkbox"]:checked + .station5-check-box {
                          background: var(--station5-accent);
                          border-color: var(--station5-accent);
                        }

                        #program-station .station5-check-row input[type="checkbox"]:checked + .station5-check-box svg {
                          opacity: 1;
                        }

                        #program-station .station5-conditional {
                          max-height: 0;
                          opacity: 0;
                          overflow: hidden;
                          transition: max-height 0.3s ease, opacity 0.3s ease;
                        }

                        #program-station .station5-conditional.open {
                          max-height: 180px;
                          opacity: 1;
                        }

                        #program-station .station5-footer {
                          justify-content: space-between;
                          padding-top: 24px;
                          border-top: 1px solid var(--station5-border);
                        }

                        #program-station .station5-primary-btn,
                        #program-station .station5-secondary-btn {
                          display: inline-flex;
                          align-items: center;
                          justify-content: center;
                          gap: 8px;
                          border-radius: 12px;
                          font: 500 14px/1 var(--station5-font);
                          padding: 11px 22px;
                        }

                        #program-station .station5-primary-btn {
                          background: var(--station5-accent);
                          border: none;
                          color: #fff;
                          box-shadow: 0 6px 20px rgba(79, 156, 249, 0.3);
                        }

                        #program-station .station5-secondary-btn {
                          background: var(--station5-surface-2);
                          border: 1px solid var(--station5-border);
                          color: var(--station5-text-secondary);
                        }

                        @media (max-width: 768px) {
                          #program-station .demo14-white-panel,
                          #program-station .station5-grid-2,
                          #program-station .station5-grid-3,
                          #program-station .station5-pricing-grid,
                          #program-station .station5-price-row,
                          #program-station .station5-footer {
                            grid-template-columns: 1fr;
                          }

                          #program-station .demo14-white-panel {
                            padding: 22px;
                          }

                          #program-station .station5-footer,
                          #program-station .station5-button-group {
                            width: 100%;
                            flex-direction: column;
                            align-items: stretch;
                          }
                        }
                      </style>
                      <div class="demo14-white-panel">
                        <div class="demo14-station-head">
                          <div>
                            <p class="demo14-section-kicker">STATION 5</p>
                            <h2>Create Services</h2>
                            <p class="station5-intro">
                              Create a new service in the global catalog and
                              prepare it for mentor assignment and pricing.
                            </p>
                          </div>
                        </div>

                        <div class="demo14-form-grid">
                          <div class="station5-info-box full" style="grid-column: 1 / -1;">
                            <svg class="station5-info-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="station5-info-text">
                              Add a new service directly from Manual Actions. This creates the service in the global catalog, and you can assign it to mentors after creation.
                            </p>
                          </div>

                          <div
                            id="serviceModePanel"
                            class="demo14-mode-panel station5-surface-box full"
                          >
                            <form method="POST" action="{{ route('admin.services.store') }}" data-real-service-form>
                              @csrf
                              <div class="demo14-form-grid">
                                <div class="demo14-field full station5-field">
                                  <label for="serviceSelect">Use Existing Service as a Starting Point</label>
                                  <select id="serviceSelect"></select>
                                </div>

                                <div class="demo14-field full station5-field">
                                  <label>Services Currently Visible in Manual Actions</label>
                                  <div id="serviceFilterPreview" class="station5-chip-grid"></div>
                                </div>

                                <div class="demo14-field full">
                                  <label>Create Service</label>
                                  <div class="demo14-summary-box">
                                    Add a new service directly from Manual Actions. This creates the service in the global catalog, and you can assign it to mentors after creation.
                                  </div>
                                </div>

                                <div class="demo14-field">
                                  <label for="serviceNameInput">Service Name</label>
                                  <input
                                    id="serviceNameInput"
                                    name="service_name"
                                    type="text"
                                    value="{{ old('service_name') }}"
                                    placeholder="Example: Resume Review"
                                    required
                                  />
                                </div>

                                <div class="demo14-field">
                                  <label for="serviceDurationInput">Duration</label>
                                  <input
                                    id="serviceDurationInput"
                                    name="duration_minutes"
                                    type="number"
                                    min="15"
                                    max="300"
                                    step="15"
                                    value="{{ old('duration_minutes', 60) }}"
                                    placeholder="60"
                                  />
                                </div>

                                <div class="demo14-field">
                                  <label for="serviceSortOrderInput">Sort Order</label>
                                  <input
                                    id="serviceSortOrderInput"
                                    name="sort_order"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="{{ old('sort_order', 0) }}"
                                  />
                                </div>

                                <div class="demo14-field" style="display: flex; align-items: end;">
                                  <input type="hidden" name="is_active" value="0" />
                                  <label class="station5-toggle" for="serviceActiveToggle">
                                    <span class="station5-toggle-control">
                                      <input id="serviceActiveToggle" type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} />
                                      <span class="station5-toggle-track">
                                        <span class="station5-toggle-thumb"></span>
                                      </span>
                                    </span>
                                      <span id="station5ServiceActiveLabel">Service is active</span>
                                  </label>
                                </div>

                                <div class="demo14-field full">
                                  <label>Available Session Types</label>
                                  <div class="station5-session-chips">
                                    <label class="station5-chip-label">
                                      <input type="checkbox" id="station5Session1on1" name="available_session_types[]" value="1on1" {{ in_array('1on1', old('available_session_types', ['1on1', '1on3', '1on5']), true) ? 'checked' : '' }} />
                                      <span class="station5-chip"><span class="station5-chip-dot"></span>1 on 1</span>
                                    </label>
                                    <label class="station5-chip-label">
                                      <input type="checkbox" id="station5Session1on3" name="available_session_types[]" value="1on3" {{ in_array('1on3', old('available_session_types', ['1on1', '1on3', '1on5']), true) ? 'checked' : '' }} />
                                      <span class="station5-chip"><span class="station5-chip-dot"></span>1 on 3</span>
                                    </label>
                                    <label class="station5-chip-label">
                                      <input type="checkbox" id="station5Session1on5" name="available_session_types[]" value="1on5" {{ in_array('1on5', old('available_session_types', ['1on1', '1on3', '1on5']), true) ? 'checked' : '' }} />
                                      <span class="station5-chip"><span class="station5-chip-dot"></span>1 on 5</span>
                                    </label>
                                  </div>
                                </div>

                                <div class="demo14-field station5-price-card" id="station5Card1on1">
                                  <div class="station5-price-card-title">1 on 1</div>
                                  <label for="servicePriceInput">1 on 1 Price</label>
                                  <input
                                    id="servicePriceInput"
                                    name="price_1on1"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('price_1on1') }}"
                                    placeholder="70.00"
                                  />
                                </div>

                                <div class="demo14-field station5-price-card">
                                  <label for="serviceCredit1on1Input">1 on 1 Credit Cost</label>
                                  <input
                                    id="serviceCredit1on1Input"
                                    name="credit_cost_1on1"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="{{ old('credit_cost_1on1', 1) }}"
                                    placeholder="1"
                                  />
                                </div>

                                <div class="demo14-field station5-price-card" id="station5Card1on3">
                                  <div class="station5-price-card-title">1 on 3 - per person</div>
                                  <label for="servicePrice1on3Input">1 on 3 Price Per Person</label>
                                  <input
                                    id="servicePrice1on3Input"
                                    name="price_1on3_per_person"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('price_1on3_per_person') }}"
                                    placeholder="58.49"
                                  />
                                </div>

                                <div class="demo14-field station5-price-card">
                                  <label for="serviceCredit1on3Input">1 on 3 Credit Cost</label>
                                  <input
                                    id="serviceCredit1on3Input"
                                    name="credit_cost_1on3"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="{{ old('credit_cost_1on3', 1) }}"
                                    placeholder="1"
                                  />
                                </div>

                                <div class="demo14-field station5-price-card" id="station5Card1on5">
                                  <div class="station5-price-card-title">1 on 5 - per person</div>
                                  <label for="servicePrice1on5Input">1 on 5 Price Per Person</label>
                                  <input
                                    id="servicePrice1on5Input"
                                    name="price_1on5_per_person"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('price_1on5_per_person') }}"
                                    placeholder="51.99"
                                  />
                                </div>

                                <div class="demo14-field station5-price-card">
                                  <label for="serviceCredit1on5Input">1 on 5 Credit Cost</label>
                                  <input
                                    id="serviceCredit1on5Input"
                                    name="credit_cost_1on5"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="{{ old('credit_cost_1on5', 1) }}"
                                    placeholder="1"
                                  />
                                </div>

                                <div class="demo14-field full">
                                  <label>Office Hours</label>
                                  <div class="station5-office-box">
                                    <label class="station5-check-row" for="station5OfficeHoursToggle">
                                      <input type="hidden" name="is_office_hours" value="0" />
                                      <input id="station5OfficeHoursToggle" type="checkbox" name="is_office_hours" value="1" {{ old('is_office_hours') ? 'checked' : '' }} />
                                      <span class="station5-check-box">
                                        <svg viewBox="0 0 12 12" aria-hidden="true"><polyline points="2 6 5 9 10 3"></polyline></svg>
                                      </span>
                                      <span>Enable office hours pricing for this service</span>
                                    </label>

                                    <div class="station5-conditional{{ old('is_office_hours') ? ' open' : '' }}" id="station5OfficeHoursPanel">
                                      <div class="station5-field">
                                        <label for="serviceOfficeHoursPriceInput">Office Hours Subscription Price</label>
                                        <input
                                          id="serviceOfficeHoursPriceInput"
                                          name="office_hours_subscription_price"
                                          type="number"
                                          min="0"
                                          step="0.01"
                                          value="{{ old('office_hours_subscription_price') }}"
                                          placeholder="200.00"
                                        />
                                      </div>
                                    </div>
                                  </div>
                                </div>

                                <div class="demo14-field full">
                                  <label>Service Snapshot</label>
                                  <div
                                    id="serviceSummary"
                                    class="demo14-summary-box"
                                  >
                                    This service will be created in <code>services_config</code>. After saving, assign it to mentors from the mentor-service mapping flow.
                                  </div>
                                </div>

                                <div
                                  class="demo14-action-row full demo14-button-group"
                                >
                                  <button
                                    class="demo14-primary-btn"
                                    id="saveServiceBtn"
                                    type="submit"
                                  >
                                    <span>Create Service</span>
                                  </button>
                                  <button
                                    class="demo14-secondary-btn"
                                    id="newServiceBtn"
                                    type="reset"
                                  >
                                    Clear Form
                                  </button>
                                </div>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                      <script>
                        (function () {
                          const root = document.getElementById('program-station');
                          if (!root) return;

                          const activeToggle = root.querySelector('#serviceActiveToggle');
                          const activeLabel = root.querySelector('#station5ServiceActiveLabel');
                          const officeHoursToggle = root.querySelector('#station5OfficeHoursToggle');
                          const officeHoursPanel = root.querySelector('#station5OfficeHoursPanel');

                          const syncOfficeHours = () => {
                            if (officeHoursToggle && officeHoursPanel) {
                              officeHoursPanel.classList.toggle('open', officeHoursToggle.checked);
                            }
                          };

                          const syncPricingCards = () => {
                            [
                              ['station5Session1on1', 'station5Card1on1'],
                              ['station5Session1on3', 'station5Card1on3'],
                              ['station5Session1on5', 'station5Card1on5'],
                            ].forEach(([checkboxId, cardId]) => {
                              const checkbox = root.querySelector(`#${checkboxId}`);
                              const card = root.querySelector(`#${cardId}`);
                              if (!checkbox || !card) return;
                              card.style.opacity = checkbox.checked ? '1' : '0.35';
                            });
                          };

                          if (activeToggle && activeLabel) {
                            activeToggle.addEventListener('change', () => {
                              activeLabel.textContent = `Service is ${activeToggle.checked ? 'active' : 'inactive'}`;
                            });
                          }
                          if (officeHoursToggle) officeHoursToggle.addEventListener('change', syncOfficeHours);
                          ['station5Session1on1', 'station5Session1on3', 'station5Session1on5'].forEach((id) => {
                            const checkbox = root.querySelector(`#${id}`);
                            if (checkbox) checkbox.addEventListener('change', syncPricingCards);
                          });

                          syncOfficeHours();
                          syncPricingCards();
                        })();
                      </script>
                    </section>

                    <section class="demo14-station-panel" id="program-create-station">
                      <style>
                        #program-create-station .demo14-white-panel {
                          background: radial-gradient(circle at top, rgba(79, 156, 249, 0.08), transparent 32%), var(--station5-bg);
                          border: 1px solid var(--station5-border);
                          border-radius: 24px;
                          box-shadow: 0 28px 60px rgba(0, 0, 0, 0.32);
                          color: var(--station5-text-primary);
                          font-family: var(--station5-font);
                          padding: 32px;
                        }

                        #program-create-station .demo14-station-head {
                          align-items: flex-start;
                          margin-bottom: 24px;
                        }

                        #program-create-station .demo14-section-kicker {
                          display: inline-flex;
                          align-items: center;
                          gap: 8px;
                          margin-bottom: 14px;
                          color: var(--station5-accent-2);
                          font: 500 11px/1 var(--station5-mono);
                          letter-spacing: 0.14em;
                          text-transform: uppercase;
                        }

                        #program-create-station .demo14-section-kicker::before {
                          content: "";
                          width: 6px;
                          height: 6px;
                          border-radius: 999px;
                          background: var(--station5-accent);
                          box-shadow: 0 0 8px var(--station5-accent);
                        }

                        #program-create-station h2,
                        #program-create-station p,
                        #program-create-station label,
                        #program-create-station .station5-info-text {
                          color: var(--station5-text-secondary);
                        }

                        #program-create-station h2 {
                          color: var(--station5-text-primary);
                          font-size: 26px;
                          margin-bottom: 8px;
                        }

                        #program-create-station .station5-info-box,
                        #program-create-station .station5-surface-box {
                          border: 1px solid var(--station5-border);
                          border-radius: 16px;
                        }

                        #program-create-station .station5-info-box {
                          display: flex;
                          gap: 12px;
                          align-items: flex-start;
                          background: rgba(79, 156, 249, 0.06);
                          border-color: rgba(79, 156, 249, 0.15);
                          padding: 14px 16px;
                        }

                        #program-create-station .station5-info-icon {
                          width: 18px;
                          height: 18px;
                          flex-shrink: 0;
                          color: var(--station5-accent);
                          margin-top: 1px;
                        }

                        #program-create-station .station5-surface-box {
                          background: var(--station5-surface);
                          padding: 16px;
                        }

                        #program-create-station .demo14-form-grid {
                          gap: 18px;
                        }

                        #program-create-station .demo14-field input,
                        #program-create-station .demo14-field select,
                        #program-create-station .demo14-field textarea {
                          width: 100%;
                          border: 1px solid var(--station5-border);
                          border-radius: 12px;
                          background: var(--station5-surface-2);
                          color: var(--station5-text-primary);
                          font: 14px/1.5 var(--station5-font);
                          padding: 10px 14px;
                        }

                        #program-create-station .demo14-field input::placeholder,
                        #program-create-station .demo14-field textarea::placeholder {
                          color: var(--station5-text-muted);
                        }

                        #program-create-station .demo14-field input:hover,
                        #program-create-station .demo14-field select:hover,
                        #program-create-station .demo14-field textarea:hover,
                        #program-create-station .demo14-field input:focus,
                        #program-create-station .demo14-field select:focus,
                        #program-create-station .demo14-field textarea:focus {
                          background: var(--station5-surface-3);
                        }

                        #program-create-station .demo14-field input:focus,
                        #program-create-station .demo14-field select:focus,
                        #program-create-station .demo14-field textarea:focus {
                          border-color: var(--station5-border-focus);
                          box-shadow: 0 0 0 3px var(--station5-accent-glow);
                        }

                        #program-create-station .demo14-chip-grid {
                          display: flex;
                          gap: 10px;
                          flex-wrap: wrap;
                        }

                        #program-create-station .demo14-chip {
                          display: inline-flex;
                          align-items: center;
                          gap: 6px;
                          background: var(--station5-surface-2);
                          border: 1px solid var(--station5-border);
                          border-radius: 999px;
                          color: var(--station5-text-secondary);
                          font-size: 13px;
                          font-weight: 500;
                          padding: 8px 14px;
                        }

                        #program-create-station .demo14-summary-box {
                          background: var(--station5-surface);
                          border: 1px solid var(--station5-border);
                          border-radius: 16px;
                          color: var(--station5-text-primary);
                        }

                        #program-create-station .station7-message {
                          border-radius: 16px;
                          margin-bottom: 18px;
                          padding: 14px 16px;
                        }

                        #program-create-station .station7-message.success {
                          background: rgba(72, 187, 120, 0.1);
                          border: 1px solid rgba(72, 187, 120, 0.26);
                          color: #9ae6b4;
                        }

                        #program-create-station .station7-message.error {
                          background: rgba(245, 101, 101, 0.1);
                          border: 1px solid rgba(245, 101, 101, 0.26);
                          color: #feb2b2;
                        }

                        #program-create-station .station7-message ul {
                          margin: 10px 0 0 18px;
                        }

                        #program-create-station .station7-toggle {
                          align-items: center;
                          cursor: pointer;
                          display: inline-flex;
                          gap: 10px;
                        }

                        #program-create-station .station7-toggle-control {
                          display: inline-flex;
                          position: relative;
                        }

                        #program-create-station .station7-toggle-control input {
                          height: 0;
                          opacity: 0;
                          position: absolute;
                          width: 0;
                        }

                        #program-create-station .station7-toggle-track {
                          align-items: center;
                          background: var(--station5-surface-3);
                          border: 1px solid var(--station5-border);
                          border-radius: 999px;
                          display: inline-flex;
                          height: 22px;
                          padding: 2px;
                          width: 40px;
                        }

                        #program-create-station .station7-toggle-thumb {
                          background: var(--station5-text-muted);
                          border-radius: 50%;
                          display: block;
                          height: 16px;
                          transition: transform 0.2s ease, background 0.2s ease;
                          width: 16px;
                        }

                        #program-create-station .station7-toggle-control input:checked + .station7-toggle-track {
                          background: rgba(72, 187, 120, 0.12);
                          border-color: rgba(72, 187, 120, 0.32);
                        }

                        #program-create-station .station7-toggle-control input:checked + .station7-toggle-track .station7-toggle-thumb {
                          background: #48bb78;
                          transform: translateX(18px);
                        }

                        #program-create-station .station7-footer {
                          align-items: center;
                          border-top: 1px solid var(--station5-border);
                          display: flex;
                          flex-wrap: wrap;
                          gap: 12px;
                          justify-content: space-between;
                          margin-top: 22px;
                          padding-top: 18px;
                        }

                        @media (max-width: 768px) {
                          #program-create-station .demo14-white-panel {
                            padding: 22px;
                          }
                        }
                      </style>
                      <div class="demo14-white-panel">
                        <div class="demo14-station-head">
                          <div>
                            <p class="demo14-section-kicker">STATION 7</p>
                            <h2>Create Programs</h2>
                            <p>
                              Create university program records separately from services.
                            </p>
                          </div>
                        </div>

                        @if (session('manual_station') === 'program-create-station' && session('success'))
                          <div class="station7-message success">
                            <strong>{{ session('success') }}</strong>
                          </div>
                        @endif

                        @if (old('manual_station') === 'program-create-station' && $errors->any())
                          <div class="station7-message error">
                            <strong>Please fix the program form errors below.</strong>
                            <ul>
                              @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                              @endforeach
                            </ul>
                          </div>
                        @endif

                        <form method="POST" action="{{ route('admin.programs.store') }}">
                          @csrf
                          <input type="hidden" name="manual_station" value="program-create-station" />

                          <div class="demo14-form-grid">
                          <div class="station5-info-box full" style="grid-column: 1 / -1;">
                            <svg class="station5-info-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="station5-info-text">
                              This creates a single university program record in <code>university_programs</code> and keeps program tier separate from university data.
                            </p>
                          </div>

                          <div class="demo14-mode-panel station5-surface-box full">
                            <div class="demo14-form-grid">
                              <div class="demo14-field full">
                                <label for="programUniversity">University</label>
                                <select id="programUniversity" name="university_id" required>
                                  <option value="">Select a university</option>
                                  @foreach ($programUniversities as $programUniversity)
                                    <option value="{{ $programUniversity->id }}" {{ (string) old('university_id') === (string) $programUniversity->id ? 'selected' : '' }}>
                                      {{ $programUniversity->name }}
                                    </option>
                                  @endforeach
                                </select>
                              </div>

                              <div class="demo14-field">
                                <label for="programName">Program Name</label>
                                <input
                                  id="programName"
                                  name="program_name"
                                  type="text"
                                  value="{{ old('program_name') }}"
                                  placeholder="Example: Public Policy"
                                  required
                                />
                              </div>

                              <div class="demo14-field">
                                <label for="programType">Program Type</label>
                                <select id="programType" name="program_type" required>
                                  <option value="">Select program type</option>
                                  @foreach ($programTypes as $programTypeValue => $programTypeLabel)
                                    <option value="{{ $programTypeValue }}" {{ old('program_type') === $programTypeValue ? 'selected' : '' }}>
                                      {{ $programTypeLabel }}
                                    </option>
                                  @endforeach
                                </select>
                              </div>

                              <div class="demo14-field">
                                <label for="programTier">Tier</label>
                                <select id="programTier" name="tier" required>
                                  <option value="">Select tier</option>
                                  @foreach ($programTiers as $programTierValue => $programTierLabel)
                                    <option value="{{ $programTierValue }}" {{ old('tier') === $programTierValue ? 'selected' : '' }}>
                                      {{ $programTierLabel }}
                                    </option>
                                  @endforeach
                                </select>
                              </div>

                              <div class="demo14-field">
                                <label for="programDuration">Duration (months)</label>
                                <input
                                  id="programDuration"
                                  name="duration_months"
                                  type="number"
                                  min="1"
                                  step="1"
                                  value="{{ old('duration_months') }}"
                                  placeholder="24"
                                />
                              </div>

                              <div class="demo14-field full">
                                <label for="programDescription">Program Description</label>
                                <textarea
                                  id="programDescription"
                                  name="description"
                                  rows="4"
                                  placeholder="Short description of the program"
                                >{{ old('description') }}</textarea>
                              </div>

                              <div class="demo14-field full">
                                <label>Program Snapshot</label>
                                <div class="demo14-summary-box" style="padding: 16px;">
                                  Programs created here can be used by university discovery filters, mentor matching, and future program-specific admin flows.
                                </div>
                              </div>

                              <div class="station7-footer full">
                                <label class="station7-toggle">
                                  <span class="station7-toggle-control">
                                    <input type="hidden" name="is_active" value="0" />
                                    <input id="programActiveToggle" name="is_active" type="checkbox" value="1" {{ old('is_active', '1') ? 'checked' : '' }} />
                                    <span class="station7-toggle-track">
                                      <span class="station7-toggle-thumb"></span>
                                    </span>
                                  </span>
                                  <span id="programActiveLabel" class="toggle-label">
                                    Program is {{ old('is_active', '1') ? 'active' : 'inactive' }}
                                  </span>
                                </label>

                                <div class="demo14-button-group">
                                  <button class="demo14-primary-btn" type="submit">
                                    Create Program
                                  </button>
                                  <button class="demo14-secondary-btn" type="reset">
                                    Clear Form
                                  </button>
                                </div>
                              </div>
                            </div>
                          </div>
                          </div>
                        </form>
                      </div>
                      <script>
                        (function () {
                          const root = document.getElementById('program-create-station');
                          if (!root) return;

                          const activeToggle = root.querySelector('#programActiveToggle');
                          const activeLabel = root.querySelector('#programActiveLabel');

                          const syncActiveLabel = () => {
                            if (!activeToggle || !activeLabel) return;
                            activeLabel.textContent = `Program is ${activeToggle.checked ? 'active' : 'inactive'}`;
                          };

                          if (activeToggle) {
                            activeToggle.addEventListener('change', syncActiveLabel);
                            syncActiveLabel();
                          }
                        })();
                      </script>
                    </section>

                    <!-- STATION 6 -->
                    <section class="demo14-station-panel" id="pricing-station">
                      <div class="demo14-white-panel">
                        <div class="demo14-station-head">
                          <div>
                            <p class="demo14-section-kicker">STATION 6</p>
                            <h2>Service Pricing</h2>
                            <p>
                              Edit service prices directly. The website display,
                              mentor cards, and payment preview update
                              automatically.
                            </p>
                          </div>
                        </div>

                        <div class="demo14-form-grid">
                          <div class="demo14-field full">
                            <label for="servicePricingSearch"
                              >Search Services</label
                            >
                            <div
                              class="demo14-search-box demo14-search-box-inline"
                            >
                              <span class="demo14-search-icon">⌕</span>
                              <input
                                id="servicePricingSearch"
                                type="text"
                                placeholder="Search service pricing..."
                              />
                            </div>
                          </div>

                          <div class="demo14-field full">
                            <label>Service Price Editor</label>
                            <div
                              id="priceEditorGrid"
                              class="demo14-price-editor-grid"
                            ></div>
                          </div>

                          <div class="demo14-field full">
                            <label>Website Pricing Preview</label>
                            <div
                              id="discountPricingPreview"
                              class="demo14-pricing-grid"
                            ></div>
                          </div>

                          <div class="demo14-field">
                            <label for="paymentServiceSelect"
                              >Payment Preview Service</label
                            >
                            <select id="paymentServiceSelect"></select>
                          </div>

                          <div class="demo14-field">
                            <label for="paymentQuantity">Quantity</label>
                            <input
                              id="paymentQuantity"
                              type="number"
                              min="1"
                              value="1"
                            />
                          </div>

                          <div class="demo14-field full">
                            <label>Payment Preview</label>
                            <div
                              id="paymentSummary"
                              class="demo14-summary-box"
                            ></div>
                          </div>

                          <div
                            class="demo14-action-row full demo14-button-group"
                          >
                            <button
                              class="demo14-primary-btn"
                              id="savePricingBtn"
                              type="button"
                            >
                              Save Pricing Changes
                            </button>
                            <button
                              class="demo14-secondary-btn"
                              id="resetPricingBtn"
                              type="button"
                            >
                              Reset to Original Prices
                            </button>
                          </div>
                        </div>
                      </div>
                    </section>

                    <section class="demo14-log-panel">
                      <div class="demo14-log-head">
                        <h3>Recent Manual Activity</h3>
                      </div>
                      <div id="activityLog" class="demo14-log-list"></div>
                    </section>
                  </div>
                </main>
            </div>
          </section>
