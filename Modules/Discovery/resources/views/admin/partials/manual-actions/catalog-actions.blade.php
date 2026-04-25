@php
  $institutions = $adminManualActionsData['institutions'] ?? [];
  $programs = $adminManualActionsData['programs'] ?? [];
  $services = $adminManualActionsData['services'] ?? [];
  $programTypes = $adminManualActionsData['options']['program_types'] ?? [];
  $programTiers = $adminManualActionsData['options']['program_tiers'] ?? [];
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
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.institutions.store') }}">
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
          <input name="display_name" type="text" value="{{ old('display_name') }}" />
          @error('display_name')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Country</span>
          <input name="country" type="text" value="{{ old('country', 'US') }}" />
          @error('country')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Alpha-2 code</span>
          <input name="alpha_two_code" type="text" maxlength="2" value="{{ old('alpha_two_code', 'US') }}" />
          @error('alpha_two_code')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>City</span>
          <input name="city" type="text" value="{{ old('city') }}" />
          @error('city')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>State / province</span>
          <input name="state_province" type="text" value="{{ old('state_province') }}" />
          @error('state_province')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field manual-field--full">
          <span>Admin note</span>
          <textarea name="notes" rows="4" placeholder="Why are we adding this institution?" required>{{ old('notes') }}</textarea>
          @error('notes')
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
        <h5>Current institutions</h5>
        <ul>
          @foreach (collect($institutions)->take(6) as $institution)
            <li>
              <strong>{{ $institution['label'] }}</strong>
              <span>{{ $institution['country'] }} · {{ $institution['programs_count'] }} programs</span>
            </li>
          @endforeach
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

        <label class="manual-field manual-university-picker" data-university-picker data-search-url="{{ route('admin.manual-actions.universities.search') }}">
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

        <label class="manual-field manual-field--full">
          <span>Admin note</span>
          <textarea name="notes" rows="4" placeholder="Why are we adding this program?" required>{{ old('notes') }}</textarea>
          @error('notes')
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

  <div class="manual-panel" id="manual-section-services" data-section-panel="services">
    <div class="manual-panel__copy">
      <h4>Create service</h4>
      <p>Add a new service with supported session types and credit costs.</p>
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
          <span>Duration (minutes)</span>
          <input name="duration_minutes" type="number" min="15" max="300" value="{{ old('duration_minutes', 60) }}" />
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

        <label class="manual-field" data-session-field="1on1">
          <span>1 on 1 price</span>
          <input name="price_1on1" type="number" min="0" step="0.01" value="{{ old('price_1on1') }}" data-session-input="1on1" />
          @error('price_1on1')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>
        <label class="manual-field" data-session-field="1on1">
          <span>1 on 1 credits</span>
          <input name="credit_cost_1on1" type="number" min="0" step="1" value="{{ old('credit_cost_1on1') }}" data-session-input="1on1" />
          @error('credit_cost_1on1')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>
        <label class="manual-field" data-session-field="1on3">
          <span>1 on 3 price</span>
          <input name="price_1on3_per_person" type="number" min="0" step="0.01" value="{{ old('price_1on3_per_person') }}" data-session-input="1on3" />
          @error('price_1on3_per_person')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>
        <label class="manual-field" data-session-field="1on3">
          <span>1 on 3 credits</span>
          <input name="credit_cost_1on3" type="number" min="0" step="1" value="{{ old('credit_cost_1on3') }}" data-session-input="1on3" />
          @error('credit_cost_1on3')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>
        <label class="manual-field" data-session-field="1on5">
          <span>1 on 5 price</span>
          <input name="price_1on5_per_person" type="number" min="0" step="0.01" value="{{ old('price_1on5_per_person') }}" data-session-input="1on5" />
          @error('price_1on5_per_person')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>
        <label class="manual-field" data-session-field="1on5">
          <span>1 on 5 credits</span>
          <input name="credit_cost_1on5" type="number" min="0" step="1" value="{{ old('credit_cost_1on5') }}" data-session-input="1on5" />
          @error('credit_cost_1on5')
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

        <label class="manual-field">
          <span>Sort order</span>
          <input name="sort_order" type="number" min="0" step="1" value="{{ old('sort_order', 0) }}" />
          @error('sort_order')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field manual-field--full">
          <span>Admin note</span>
          <textarea name="notes" rows="4" placeholder="Why are we creating this service?" required>{{ old('notes') }}</textarea>
          @error('notes')
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
              <span>{{ $service['duration_minutes'] }} min · {{ $service['is_active'] ? 'Active' : 'Inactive' }}</span>
            </li>
          @endforeach
        </ul>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-pricing" data-section-panel="pricing">
    <div class="manual-panel__copy">
      <h4>Update service pricing</h4>
      <p>Edit pricing for an existing service without leaving the manual actions hub.</p>
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
          <span>1 on 1 price</span>
          <input name="price_1on1" type="number" min="0" step="0.01" value="{{ old('price_1on1') }}" />
          @error('price_1on1')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>1 on 3 price</span>
          <input name="price_1on3_per_person" type="number" min="0" step="0.01" value="{{ old('price_1on3_per_person') }}" />
          @error('price_1on3_per_person')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>1 on 5 price</span>
          <input name="price_1on5_per_person" type="number" min="0" step="0.01" value="{{ old('price_1on5_per_person') }}" />
          @error('price_1on5_per_person')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Office hours price</span>
          <input name="office_hours_subscription_price" type="number" min="0" step="0.01" value="{{ old('office_hours_subscription_price') }}" />
          @error('office_hours_subscription_price')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field manual-field--full">
          <span>Admin note</span>
          <textarea name="notes" rows="4" placeholder="Why is this pricing changing?" required>{{ old('notes') }}</textarea>
          @error('notes')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Update pricing</button>
      </form>

      <aside class="manual-summary" id="manualPricingSummary">
        <h5>Current pricing</h5>
        <p>Select a service to review the current pricing before updating it.</p>
      </aside>
    </div>
  </div>
</section>
