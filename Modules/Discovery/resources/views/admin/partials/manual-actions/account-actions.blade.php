@php
  $mentors = $adminManualActionsData['mentors'] ?? [];
  $users = $adminManualActionsData['users'] ?? [];
  $mentorStatuses = $adminManualActionsData['options']['mentor_statuses'] ?? [];
  $featuredMentorIds = collect($mentors)
      ->filter(fn ($mentor) => (bool) ($mentor['is_featured'] ?? false))
      ->sortBy(fn ($mentor) => $mentor['featured_sort_order'] ?? 9999)
      ->pluck('id')
      ->map(fn ($id) => (string) $id)
      ->all();
@endphp

<section class="manual-group" data-section-group="mentor credits">
  <header class="manual-group__header">
    <div>
      <p class="manual-group__eyebrow">Account Actions</p>
      <h3>Mentors and credits</h3>
      <p>Use these actions for real account changes that already exist on the backend today.</p>
    </div>
  </header>

  <div class="manual-panel" id="manual-section-mentor" data-section-panel="mentor">
    <div class="manual-panel__copy">
      <h4>Amend mentor account</h4>
      <p>Update the mentor status from the current backend state.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.mentors.update') }}">
        @csrf
        <input type="hidden" name="manual_section" value="mentor" />
        <input type="hidden" name="featured_order" id="manualFeaturedMentorOrder" value="{{ implode(',', old('featured_order') ? explode(',', (string) old('featured_order')) : $featuredMentorIds) }}" />

        <label class="manual-field">
          <span>Mentor</span>
          <select name="mentor_id" id="manualMentorSelect" required>
            <option value="">Select a mentor</option>
            @foreach ($mentors as $mentor)
              <option value="{{ $mentor['id'] }}" @selected((string) old('mentor_id') === (string) $mentor['id'])>{{ $mentor['label'] }}</option>
            @endforeach
          </select>
          @error('mentor_id')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>New status</span>
          <select name="status" required>
            <option value="">Select status</option>
            @foreach ($mentorStatuses as $value => $label)
              <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error('status')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Save mentor action</button>
      </form>

      <aside class="manual-summary" id="manualMentorSummary">
        <h5>Current mentor state</h5>
        <p>Select a mentor to review their current profile state before saving.</p>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-featured-mentors" data-section-panel="mentor">
    <div class="manual-panel__copy">
      <h4>Mentors of the Week</h4>
      <p>Choose up to 6 mentors for the dashboard. If none are selected, the dashboard falls back to the top-rated active mentors.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.mentors.featured.update') }}">
        @csrf
        <input type="hidden" name="manual_section" value="mentor" />

        <label class="manual-field manual-field--full">
          <span>Featured mentors</span>
          <input
            type="search"
            id="manualFeaturedMentorSearch"
            class="manual-featured-mentor-search"
            placeholder="Search by mentor name, email, or institution..."
            autocomplete="off"
          />
          <div class="manual-featured-mentor-list" id="manualFeaturedMentorSelect">
            @foreach ($mentors as $mentor)
              @php
                $canFeatureMentor = ($mentor['status'] ?? null) === 'active';
                $isChecked = $canFeatureMentor && in_array((string) $mentor['id'], old('mentor_ids', $featuredMentorIds), true);
              @endphp
              <label
                class="manual-featured-mentor-option{{ $canFeatureMentor ? '' : ' is-disabled' }}"
                data-search-text="{{ \Illuminate\Support\Str::lower(($mentor['name'] ?? '').' '.($mentor['email'] ?? '').' '.($mentor['institution'] ?? '').' '.($mentor['program_type'] ?? '')) }}"
              >
                <input
                  type="checkbox"
                  name="mentor_ids[]"
                  value="{{ $mentor['id'] }}"
                  @checked($isChecked)
                  @disabled(! $canFeatureMentor)
                />
                <span class="manual-featured-mentor-rank" data-featured-rank></span>
                <span class="manual-featured-mentor-copy">
                  <strong>{{ $mentor['name'] }}</strong>
                  <small>{{ $mentor['institution'] ?? '-' }}</small>
                </span>
                <span class="manual-featured-mentor-meta">
                  {{ $mentor['rating'] ?? 'New' }} {{ ($mentor['rating'] ?? 'New') === 'New' ? '' : 'stars' }}
                </span>
              </label>
            @endforeach
          </div>
          @error('mentor_ids')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
          @error('mentor_ids.*')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Save Mentors of the Week</button>
      </form>

      <aside class="manual-summary" id="manualFeaturedMentorSummary">
        <h5>Current featured mentors</h5>
        <p>The selected mentors appear first on the dashboard, with top-rated mentors filling any open slots.</p>
      </aside>
    </div>
  </div>

  <div class="manual-panel" id="manual-section-credits" data-section-panel="credits">
    <div class="manual-panel__copy">
      <h4>Adjust user credits</h4>
      <p>Add or deduct credits with a single audited adjustment.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.credits.adjust') }}">
        @csrf
        <input type="hidden" name="manual_section" value="credits" />

        <label class="manual-field">
          <span>User</span>
          <select name="user_id" id="manualUserSelect" required>
            <option value="">Select a user</option>
            @foreach ($users as $user)
              <option value="{{ $user['id'] }}" @selected((string) old('user_id') === (string) $user['id'])>{{ $user['label'] }}</option>
            @endforeach
          </select>
          @error('user_id')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Credit adjustment</span>
          <input name="amount" type="number" step="1" placeholder="Use positive or negative values" value="{{ old('amount') }}" required />
          @error('amount')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        @error('manual')
          <small class="manual-field__error manual-field--full">{{ $message }}</small>
        @enderror

        <button class="primary-btn manual-submit-btn" type="submit">Apply credit change</button>
      </form>

      <aside class="manual-summary" id="manualUserSummary">
        <h5>Current credit balance</h5>
        <p>Select a user to confirm the current balance before submitting the adjustment.</p>
      </aside>
    </div>
  </div>
</section>
