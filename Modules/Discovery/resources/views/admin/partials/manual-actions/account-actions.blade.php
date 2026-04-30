@php
  $mentors = $adminManualActionsData['mentors'] ?? [];
  $users = $adminManualActionsData['users'] ?? [];
  $mentorStatuses = $adminManualActionsData['options']['mentor_statuses'] ?? [];
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
