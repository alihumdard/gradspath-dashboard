@php
  $feedbackItems = $adminManualActionsData['feedback'] ?? [];
@endphp

<section class="manual-group">
  <header class="manual-group__header">
    <div>
      <p class="manual-group__eyebrow">Moderation Actions</p>
      <h3>Feedback review</h3>
      <p>Moderate comments and visibility without leaving the admin dashboard.</p>
    </div>
  </header>

  <div class="manual-panel" id="manual-section-feedback" data-section-panel="feedback">
    <div class="manual-panel__copy">
      <h4>Update feedback</h4>
      <p>Review the current feedback text, set visibility, and save an admin note.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.feedback.update') }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="manual_section" value="feedback" />

        <label class="manual-field manual-field--full">
          <span>Feedback item</span>
          <select name="feedback_id" id="manualFeedbackSelect" required>
            <option value="">Select feedback</option>
            @foreach ($feedbackItems as $item)
              <option value="{{ $item['id'] }}" @selected((string) old('feedback_id') === (string) $item['id'])>{{ $item['label'] }}</option>
            @endforeach
          </select>
          @error('feedback_id')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field manual-field--full">
          <span>Comment</span>
          <textarea name="comment" rows="5">{{ old('comment') }}</textarea>
          @error('comment')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Visibility</span>
          <select name="is_visible" required>
            <option value="1" @selected(old('is_visible', '1') === '1')>Visible</option>
            <option value="0" @selected(old('is_visible') === '0')>Hidden</option>
          </select>
          @error('is_visible')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field manual-field--full">
          <span>Admin note</span>
          <textarea name="admin_note" rows="4" placeholder="Why was this feedback changed?" required>{{ old('admin_note') }}</textarea>
          @error('admin_note')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Save feedback action</button>
      </form>

      <aside class="manual-summary" id="manualFeedbackSummary">
        <h5>Current feedback state</h5>
        <p>Select a feedback item to review its current text, mentor, student, and visibility.</p>
      </aside>
    </div>
  </div>
</section>
