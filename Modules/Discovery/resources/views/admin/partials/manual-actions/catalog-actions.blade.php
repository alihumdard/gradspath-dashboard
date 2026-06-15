@php
  $institutions = $adminManualActionsData['institutions'] ?? [];
  $programs = $adminManualActionsData['programs'] ?? [];
  $services = $adminManualActionsData['services'] ?? [];
  $programTypes = $adminManualActionsData['options']['program_types'] ?? [];
  $programTiers = $adminManualActionsData['options']['program_tiers'] ?? [];
  $featuredInstitutions = $adminManualActionsData['featured_institutions'] ?? [];
  $featuredOptions = $featuredInstitutions['options'] ?? [];
  $featuredManualRows = old('institutions', $featuredInstitutions['manual'] ?? []);
  $featuredAutomaticRows = $featuredInstitutions['automatic'] ?? [];
  $lastFeaturedRefresh = $featuredInstitutions['last_recalculated_at'] ?? null;
  $featuredInstitutionLimit = 5;
  $institutionUpdateRouteTemplate = route('admin.manual-actions.institutions.update', ['id' => '__ID__'], false);
  $programUpdateRouteTemplate = route('admin.manual-actions.programs.update', ['id' => '__ID__'], false);
  $initialInstitution = collect($institutions)->first();
  $initialProgram = collect($programs)->first();
  $featuredInstitutionIds = collect($featuredManualRows)
      ->map(fn ($row) => (string) ($row['university_id'] ?? $row['id'] ?? ''))
      ->filter()
      ->take($featuredInstitutionLimit)
      ->values()
      ->all();
@endphp

<section class="manual-group" data-section-group="institutions programs services pricing">
  <header class="manual-group__header">
    <div>
      <p class="manual-group__eyebrow">Catalog Actions</p>
      <h3>Institutions, programs, services, and pricing</h3>
      <p>Manage the discovery and service catalog with one consistent admin surface.</p>
    </div>
  </header>

  <div class="manual-panel" id="manual-section-institutions" data-section-panel="institutions">
    <div class="manual-panel__copy">
      <h4>Create institution</h4>
      <p>Add a university record that can later be used by programs and mentor discovery.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.institutions.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="manual_section" value="institutions" />

        <label class="manual-field">
          <span>Institution name</span>
          <input name="name" type="text" value="{{ old('name') }}" required />
          @error('name')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Display name</span>
          <input name="display_name" type="text" value="{{ old('display_name') }}" required />
          @error('display_name')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Country</span>
          <input name="country" type="text" value="{{ old('country') }}" required />
          @error('country')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>City</span>
          <input name="city" type="text" value="{{ old('city') }}" required />
          @error('city')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>State / province</span>
          <input name="state_province" type="text" value="{{ old('state_province') }}" required />
          @error('state_province')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Upload logo</span>
          <input name="logo_file" type="file" accept="image/png,image/jpeg,image/webp,image/gif" required />
          @error('logo_file')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Logo URL or public path</span>
          <input name="logo_url" type="text" value="{{ old('logo_url') }}" placeholder="Optional: university_logo/lahore.jfif" />
          @error('logo_url')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-check">
          <input name="is_active" type="hidden" value="0" />
          <input name="is_active" type="checkbox" value="1" @checked(old('is_active', '1') === '1') />
          <span>Institution is active</span>
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Create institution</button>
      </form>

      <aside class="manual-summary manual-summary--list">
        <h5>Latest added institutions</h5>
        <ul>
          @foreach (collect($institutions)->take(6) as $institution)
            <li>
              @if (! empty($institution['logo_preview_url']))
                <span class="manual-logo-row">
                  <img src="{{ $institution['logo_preview_url'] }}" alt="" loading="lazy">
                  <strong>{{ $institution['label'] }}</strong>
                </span>
              @else
                <strong>{{ $institution['label'] }}</strong>
              @endif
              <span>{{ $institution['country'] }} · {{ $institution['programs_count'] }} programs</span>
            </li>
          @endforeach
        </ul>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-institution-edit" data-section-panel="institutions">
    <div class="manual-panel__copy">
      <h4>Edit institution</h4>
      <p>Update institution names, location, active status, and logo assets used across dashboards and Explore by University.</p>
    </div>

    <div class="manual-panel__grid">
      <form
        class="manual-form"
        method="POST"
        action=""
        enctype="multipart/form-data"
        id="manualInstitutionEditForm"
        data-update-route-template="{{ $institutionUpdateRouteTemplate }}"
      >
        @csrf
        @method('PATCH')
        <input type="hidden" name="manual_section" value="institutions" />

        <label
          class="manual-field manual-field--full manual-university-picker"
          data-institution-edit-picker
          data-search-url="{{ route('admin.manual-actions.universities.search', [], false) }}"
        >
          <span>Institution to edit</span>
          <input
            id="manualInstitutionEditSearch"
            type="text"
            value="{{ $initialInstitution['label'] ?? '' }}"
            placeholder="Search institutions..."
            autocomplete="off"
            data-institution-edit-search
          />
          <input id="manualInstitutionEditId" type="hidden" value="{{ $initialInstitution['id'] ?? '' }}" data-institution-edit-id required />
          <div class="manual-picker-results" data-institution-edit-results hidden></div>
        </label>

        <label class="manual-field">
          <span>Institution name</span>
          <input name="name" type="text" required data-institution-field="name" />
        </label>

        <label class="manual-field">
          <span>Display name</span>
          <input name="display_name" type="text" required data-institution-field="display_name" />
        </label>

        <label class="manual-field">
          <span>Country</span>
          <input name="country" type="text" required data-institution-field="country" />
        </label>

        <label class="manual-field">
          <span>City</span>
          <input name="city" type="text" required data-institution-field="city" />
        </label>

        <label class="manual-field">
          <span>State / province</span>
          <input name="state_province" type="text" required data-institution-field="state_province" />
        </label>

        <label class="manual-field manual-field--full">
          <span>Domains</span>
          <textarea name="domains" rows="2" data-institution-field="domains"></textarea>
        </label>

        <label class="manual-field manual-field--full">
          <span>Web pages</span>
          <textarea name="web_pages" rows="2" data-institution-field="web_pages"></textarea>
        </label>

        <label class="manual-field">
          <span>Replace logo upload</span>
          <input name="logo_file" type="file" accept="image/png,image/jpeg,image/webp,image/gif" />
        </label>

        <label class="manual-field">
          <span>Logo URL or public path</span>
          <input name="logo_url" type="text" data-institution-field="logo_url" />
        </label>

        <label class="manual-check">
          <input name="is_active" type="hidden" value="0" />
          <input name="is_active" type="checkbox" value="1" data-institution-field="is_active" />
          <span>Institution is active</span>
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Save institution</button>
      </form>

      <aside class="manual-summary" id="manualInstitutionEditSummary">
        <h5>Institution preview</h5>
        <p>Select an institution to preview its logo and saved details.</p>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-featured-institutions" data-section-panel="institutions">
    <div class="manual-panel__copy">
      <h4>Featured institutions</h4>
      <p>Use automatic meeting-count ranking by default, or choose the dashboard institutions manually.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.institutions.featured.update') }}">
        @csrf
        <input type="hidden" name="manual_section" value="institutions" />

        <label class="manual-field manual-field--full">
          <span>Manual institutions</span>
          <input
            type="search"
            id="manualFeaturedInstitutionSearch"
            class="manual-featured-mentor-search"
            placeholder="Search by institution name..."
            autocomplete="off"
          />
          <div id="manualFeaturedInstitutionFields">
            @foreach ($featuredInstitutionIds as $index => $institutionId)
              <input type="hidden" name="institutions[{{ $index }}][university_id]" value="{{ $institutionId }}" />
              <input type="hidden" name="institutions[{{ $index }}][sort_order]" value="{{ $index + 1 }}" />
            @endforeach
          </div>
          <div
            class="manual-featured-mentor-list"
            id="manualFeaturedInstitutionSelect"
            data-search-url="{{ route('admin.manual-actions.universities.search', [], false) }}"
          >
            @foreach ($featuredOptions as $institution)
              @php
                $institutionId = (string) ($institution['id'] ?? '');
                $isChecked = in_array($institutionId, $featuredInstitutionIds, true);
              @endphp
              <label
                class="manual-featured-mentor-option"
                data-institution-option
                data-institution-id="{{ $institutionId }}"
                data-institution-label="{{ $institution['label'] ?? $institution['name'] ?? 'Institution' }}"
                data-institution-name="{{ $institution['name'] ?? '' }}"
                data-search-text="{{ \Illuminate\Support\Str::lower(($institution['label'] ?? '').' '.($institution['name'] ?? '')) }}"
              >
                <input
                  type="checkbox"
                  value="{{ $institutionId }}"
                  @checked($isChecked)
                />
                <span class="manual-featured-mentor-rank" data-featured-institution-rank></span>
                <span class="manual-featured-mentor-copy">
                  <strong>{{ $institution['label'] ?? $institution['name'] ?? 'Institution' }}</strong>
                  <small>{{ $institution['name'] ?? '' }}</small>
                </span>
              </label>
            @endforeach
          </div>
          @error('institutions')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
          @error('institutions.*.university_id')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Save featured institutions</button>
      </form>

      <aside class="manual-summary manual-summary--list">
        <h5>Automatic ranking preview</h5>
        @if ($lastFeaturedRefresh)
          <p>Last refreshed {{ \Illuminate\Support\Carbon::parse($lastFeaturedRefresh)->diffForHumans() }}.</p>
        @else
          <p>Automatic ranking will be stored after the first daily refresh.</p>
        @endif
        <ul>
          @forelse ($featuredAutomaticRows as $institution)
            <li>
              <strong>{{ $institution['rank'] }}. {{ $institution['label'] }}</strong>
              <span>{{ $institution['meetings_count'] }} meetings</span>
            </li>
          @empty
            <li>
              <strong>No ranking available yet</strong>
              <span>The dashboard can still fall back to live institution counts.</span>
            </li>
          @endforelse
        </ul>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-programs" data-section-panel="programs">
    <div class="manual-panel__copy">
      <h4>Create program</h4>
      <p>Create a program record for discovery, mentor matching, and future reporting.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.programs.store') }}">
        @csrf
        <input type="hidden" name="manual_section" value="programs" />

        <label class="manual-field manual-university-picker" data-university-picker data-search-url="{{ route('admin.manual-actions.universities.search', [], false) }}">
          <span>University</span>
          <input
            id="manualUniversitySearch"
            type="text"
            value=""
            placeholder="Search universities..."
            autocomplete="off"
            data-university-search
          />
          <input name="university_id" type="hidden" value="{{ old('university_id') }}" data-university-id required />
          <div class="manual-picker-results" data-university-results hidden></div>
          @error('university_id')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Program name</span>
          <input name="program_name" type="text" value="{{ old('program_name') }}" required />
          @error('program_name')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Program type</span>
          <select name="program_type" required>
            <option value="">Select program type</option>
            @foreach ($programTypes as $value => $label)
              <option value="{{ $value }}" @selected(old('program_type') === $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error('program_type')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Tier</span>
          <select name="tier" required>
            <option value="">Select tier</option>
            @foreach ($programTiers as $value => $label)
              <option value="{{ $value }}" @selected(old('tier') === $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error('tier')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Duration (months)</span>
          <input name="duration_months" type="number" min="1" value="{{ old('duration_months') }}" />
          @error('duration_months')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field manual-field--full">
          <span>Description</span>
          <textarea name="description" rows="3">{{ old('description') }}</textarea>
          @error('description')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-check">
          <input name="is_active" type="hidden" value="0" />
          <input name="is_active" type="checkbox" value="1" @checked(old('is_active', '1') === '1') />
          <span>Program is active</span>
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Create program</button>
      </form>

      <aside class="manual-summary manual-summary--list">
        <h5>Recent programs</h5>
        <ul>
          @foreach (collect($programs)->take(6) as $program)
            <li>
              <strong>{{ $program['name'] }}</strong>
              <span>{{ $program['university'] }} · {{ strtoupper($program['program_type']) }}</span>
            </li>
          @endforeach
        </ul>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-program-edit" data-section-panel="programs">
    <div class="manual-panel__copy">
      <h4>Edit program</h4>
      <p>Update program type, tier, description, duration, and active state for existing university programs.</p>
    </div>

    <div class="manual-panel__grid">
      <form
        class="manual-form"
        method="POST"
        action=""
        id="manualProgramEditForm"
        data-update-route-template="{{ $programUpdateRouteTemplate }}"
      >
        @csrf
        @method('PATCH')
        <input type="hidden" name="manual_section" value="programs" />

        <label
          class="manual-field manual-field--full manual-university-picker"
          data-program-edit-picker
          data-search-url="{{ route('admin.manual-actions.programs.search', [], false) }}"
        >
          <span>Program to edit</span>
          <input
            id="manualProgramEditSearch"
            type="text"
            value="{{ $initialProgram['label'] ?? '' }}"
            placeholder="Search programs..."
            autocomplete="off"
            data-program-edit-search
          />
          <input id="manualProgramEditId" type="hidden" value="{{ $initialProgram['id'] ?? '' }}" data-program-edit-id required />
          <div class="manual-picker-results" data-program-edit-results hidden></div>
        </label>

        <label class="manual-field manual-university-picker" data-university-picker data-search-url="{{ route('admin.manual-actions.universities.search', [], false) }}">
          <span>University</span>
          <input
            type="text"
            value=""
            placeholder="Search universities..."
            autocomplete="off"
            data-university-search
          />
          <input name="university_id" type="hidden" value="" data-university-id data-program-field="university_id" required />
          <div class="manual-picker-results" data-university-results hidden></div>
        </label>

        <label class="manual-field">
          <span>Program name</span>
          <input name="program_name" type="text" required data-program-field="name" />
        </label>

        <label class="manual-field">
          <span>Program type</span>
          <select name="program_type" required data-program-field="program_type">
            @foreach ($programTypes as $value => $label)
              <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
        </label>

        <label class="manual-field">
          <span>Tier</span>
          <select name="tier" required data-program-field="tier">
            @foreach ($programTiers as $value => $label)
              <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
        </label>

        <label class="manual-field">
          <span>Duration (months)</span>
          <input name="duration_months" type="number" min="1" data-program-field="duration_months" />
        </label>

        <label class="manual-field manual-field--full">
          <span>Description</span>
          <textarea name="description" rows="3" data-program-field="description"></textarea>
        </label>

        <label class="manual-check">
          <input name="is_active" type="hidden" value="0" />
          <input name="is_active" type="checkbox" value="1" data-program-field="is_active" />
          <span>Program is active</span>
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Save program</button>
      </form>

      <aside class="manual-summary" id="manualProgramEditSummary">
        <h5>Program preview</h5>
        <p>Select a program to preview its saved details.</p>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-services" data-section-panel="services">
    <div class="manual-panel__copy">
      <h4>Create service</h4>
      <p>Add a new service with supported session types and payout splits.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.services.store') }}" id="manualServiceCreateForm">
        @csrf
        <input type="hidden" name="manual_section" value="services" />

        <label class="manual-field">
          <span>Service name</span>
          <input name="service_name" type="text" value="{{ old('service_name') }}" required />
          @error('service_name')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Session duration (minutes)</span>
          <input name="duration_minutes" type="number" min="15" max="300" step="1" value="{{ old('duration_minutes', 60) }}" required />
          @error('duration_minutes')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <fieldset class="manual-fieldset manual-field--full">
          <legend>Session types</legend>
          <div class="manual-check-grid">
            @foreach (['1on1' => '1 on 1', '1on3' => '1 on 3', '1on5' => '1 on 5'] as $value => $label)
              <label class="manual-check">
                <input
                  name="available_session_types[]"
                  type="checkbox"
                  value="{{ $value }}"
                  data-session-toggle="{{ $value }}"
                  @checked(in_array($value, old('available_session_types', ['1on1']), true))
                />
                <span>{{ $label }}</span>
              </label>
            @endforeach
          </div>
          @error('available_session_types')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </fieldset>

        <div class="manual-split-row manual-field--full" data-session-field="1on1">
          <label class="manual-field">
            <span>1 on 1 student price</span>
            <input name="price_1on1" type="number" min="0" step="0.01" value="{{ old('price_1on1') }}" data-session-input="1on1" />
            @error('price_1on1')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
          <label class="manual-field">
            <span>1 on 1 admin gets</span>
            <input name="platform_fee_1on1" type="number" min="0" step="0.01" value="{{ old('platform_fee_1on1') }}" data-session-input="1on1" />
            @error('platform_fee_1on1')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
          <label class="manual-field">
            <span>1 on 1 mentor gets</span>
            <input name="mentor_payout_1on1" type="number" min="0" step="0.01" value="{{ old('mentor_payout_1on1') }}" data-session-input="1on1" />
            @error('mentor_payout_1on1')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
        </div>

        <div class="manual-split-row manual-field--full" data-session-field="1on3">
          <label class="manual-field">
            <span>1 on 3 student price total</span>
            <input name="price_1on3_total" type="number" min="0" step="0.01" value="{{ old('price_1on3_total') }}" data-session-input="1on3" />
            @error('price_1on3_total')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
          <label class="manual-field">
            <span>1 on 3 admin gets total</span>
            <input name="platform_fee_1on3" type="number" min="0" step="0.01" value="{{ old('platform_fee_1on3') }}" data-session-input="1on3" />
            @error('platform_fee_1on3')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
          <label class="manual-field">
            <span>1 on 3 mentor gets total</span>
            <input name="mentor_payout_1on3" type="number" min="0" step="0.01" value="{{ old('mentor_payout_1on3') }}" data-session-input="1on3" />
            @error('mentor_payout_1on3')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
        </div>

        <div class="manual-split-row manual-field--full" data-session-field="1on5">
          <label class="manual-field">
            <span>1 on 5 student price total</span>
            <input name="price_1on5_total" type="number" min="0" step="0.01" value="{{ old('price_1on5_total') }}" data-session-input="1on5" />
            @error('price_1on5_total')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
          <label class="manual-field">
            <span>1 on 5 admin gets total</span>
            <input name="platform_fee_1on5" type="number" min="0" step="0.01" value="{{ old('platform_fee_1on5') }}" data-session-input="1on5" />
            @error('platform_fee_1on5')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
          <label class="manual-field">
            <span>1 on 5 mentor gets total</span>
            <input name="mentor_payout_1on5" type="number" min="0" step="0.01" value="{{ old('mentor_payout_1on5') }}" data-session-input="1on5" />
            @error('mentor_payout_1on5')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
        </div>

        <label class="manual-field">
          <span>Office hours mentor payout per attendee</span>
          <input name="office_hours_mentor_payout_per_attendee" type="number" min="0" step="0.01" value="{{ old('office_hours_mentor_payout_per_attendee') }}" />
          @error('office_hours_mentor_payout_per_attendee')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-check manual-field--full">
          <input name="is_office_hours" type="checkbox" value="1" @checked(old('is_office_hours')) />
          <span>Enable office hours pricing</span>
        </label>

        <label class="manual-field">
          <span>Office hours price</span>
          <input name="office_hours_subscription_price" type="number" min="0" step="0.01" value="{{ old('office_hours_subscription_price') }}" />
          @error('office_hours_subscription_price')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-check">
          <input name="is_active" type="hidden" value="0" />
          <input name="is_active" type="checkbox" value="1" @checked(old('is_active', '1') === '1') />
          <span>Service is active</span>
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Create service</button>
      </form>

      <aside class="manual-summary manual-summary--list">
        <h5>Current services</h5>
        <ul>
          @foreach (collect($services)->take(6) as $service)
            <li>
              <strong>{{ $service['name'] }}</strong>
              <span>{{ $service['duration_minutes'] }} min - {{ $service['is_active'] ? 'Active' : 'Inactive' }}</span>
            </li>
          @endforeach
        </ul>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-pricing" data-section-panel="pricing">
    <div class="manual-panel__copy">
      <h4>Update service details</h4>
      <p>Edit session duration and pricing for an existing service without leaving the manual actions hub.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.services.pricing.update') }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="manual_section" value="pricing" />

        <label class="manual-field manual-field--full">
          <span>Service</span>
          <select name="service_id" id="manualPricingServiceSelect" required>
            <option value="">Select a service</option>
            @foreach ($services as $service)
              <option value="{{ $service['id'] }}" @selected((string) old('service_id') === (string) $service['id'])>{{ $service['label'] }}</option>
            @endforeach
          </select>
          @error('service_id')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Session duration (minutes)</span>
          <input name="duration_minutes" type="number" min="15" max="300" step="1" value="{{ old('duration_minutes') }}" required />
          @error('duration_minutes')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <div class="manual-split-row manual-field--full" data-pricing-session-field>
          <label class="manual-field">
            <span>1 on 1 student price</span>
            <input name="price_1on1" type="number" min="0" step="0.01" value="{{ old('price_1on1') }}" />
            @error('price_1on1')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>

          <label class="manual-field">
            <span>1 on 1 admin gets</span>
            <input name="platform_fee_1on1" type="number" min="0" step="0.01" value="{{ old('platform_fee_1on1') }}" />
            @error('platform_fee_1on1')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>

          <label class="manual-field">
            <span>1 on 1 mentor gets</span>
            <input name="mentor_payout_1on1" type="number" min="0" step="0.01" value="{{ old('mentor_payout_1on1') }}" />
            @error('mentor_payout_1on1')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
        </div>

        <div class="manual-split-row manual-field--full" data-pricing-session-field>
          <label class="manual-field">
            <span>1 on 3 student price total</span>
            <input name="price_1on3_total" type="number" min="0" step="0.01" value="{{ old('price_1on3_total') }}" />
            @error('price_1on3_total')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>

          <label class="manual-field">
            <span>1 on 3 admin gets total</span>
            <input name="platform_fee_1on3" type="number" min="0" step="0.01" value="{{ old('platform_fee_1on3') }}" />
            @error('platform_fee_1on3')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>

          <label class="manual-field">
            <span>1 on 3 mentor gets total</span>
            <input name="mentor_payout_1on3" type="number" min="0" step="0.01" value="{{ old('mentor_payout_1on3') }}" />
            @error('mentor_payout_1on3')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
        </div>

        <div class="manual-split-row manual-field--full" data-pricing-session-field>
          <label class="manual-field">
            <span>1 on 5 student price total</span>
            <input name="price_1on5_total" type="number" min="0" step="0.01" value="{{ old('price_1on5_total') }}" />
            @error('price_1on5_total')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>

          <label class="manual-field">
            <span>1 on 5 admin gets total</span>
            <input name="platform_fee_1on5" type="number" min="0" step="0.01" value="{{ old('platform_fee_1on5') }}" />
            @error('platform_fee_1on5')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>

          <label class="manual-field">
            <span>1 on 5 mentor gets total</span>
            <input name="mentor_payout_1on5" type="number" min="0" step="0.01" value="{{ old('mentor_payout_1on5') }}" />
            @error('mentor_payout_1on5')
              <small class="manual-field__error">{{ $message }}</small>
            @enderror
          </label>
        </div>

        <label class="manual-field" data-pricing-office-hours-field>
          <span>Office hours price</span>
          <input name="office_hours_subscription_price" type="number" min="0" step="0.01" value="{{ old('office_hours_subscription_price') }}" />
          @error('office_hours_subscription_price')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field" data-pricing-office-hours-field>
          <span>Office hours mentor payout per attendee</span>
          <input name="office_hours_mentor_payout_per_attendee" type="number" min="0" step="0.01" value="{{ old('office_hours_mentor_payout_per_attendee') }}" />
          @error('office_hours_mentor_payout_per_attendee')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Update service</button>
      </form>

      <aside class="manual-summary" id="manualPricingSummary">
        <h5>Current service details</h5>
        <p>Select a service to review the current duration and pricing before updating it.</p>
      </aside>
    </div>
  </div>
</section>
