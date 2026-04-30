@php
  $feedbackItems = $adminManualActionsData['feedback'] ?? [];
  $bookingItems = $adminManualActionsData['bookings'] ?? [];
  $bookingOutcomes = $adminManualActionsData['options']['booking_outcomes'] ?? [];
@endphp

<section class="manual-group" data-section-group="feedback">
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
      <p>Review the current feedback text and set visibility.</p>
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

        <button class="primary-btn manual-submit-btn" type="submit">Save feedback action</button>
      </form>

      <aside class="manual-summary" id="manualFeedbackSummary">
        <h5>Current feedback state</h5>
        <p>Select a feedback item to review its current text, mentor, student, and visibility.</p>
      </aside>
    </div>
  </div>
</section>

<section class="manual-group" data-section-group="bookings">
  <header class="manual-group__header">
    <div>
      <p class="manual-group__eyebrow">Meeting Outcomes</p>
      <h3>Booking outcome review</h3>
      <p>Classify no-shows and interrupted sessions without changing student feedback eligibility.</p>
    </div>
  </header>

  <div class="manual-panel" id="manual-section-bookings" data-section-panel="bookings">
    <div class="manual-panel__copy">
      <h4>Update booking outcome</h4>
      <p>Mark a completed or disputed booking outcome.</p>
    </div>

    <div class="manual-panel__grid">
      <form class="manual-form" method="POST" action="{{ route('admin.manual-actions.bookings.outcome.update') }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="manual_section" value="bookings" />

        <label class="manual-field manual-field--full">
          <span>Booking</span>
          <select name="booking_id" required>
            <option value="">Select booking</option>
            @foreach ($bookingItems as $item)
              <option value="{{ $item['id'] }}" @selected((string) old('booking_id') === (string) $item['id'])>{{ $item['label'] }}</option>
            @endforeach
          </select>
          @error('booking_id')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Outcome</span>
          <select name="session_outcome" required>
            @foreach ($bookingOutcomes as $value => $label)
              <option value="{{ $value }}" @selected(old('session_outcome', 'completed') === $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error('session_outcome')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <label class="manual-field">
          <span>Completion source</span>
          <select name="completion_source">
            <option value="manual" @selected(old('completion_source', 'manual') === 'manual')>Manual</option>
            <option value="schedule" @selected(old('completion_source') === 'schedule')>Schedule</option>
            <option value="zoom_event" @selected(old('completion_source') === 'zoom_event')>Zoom event</option>
          </select>
          @error('completion_source')
            <small class="manual-field__error">{{ $message }}</small>
          @enderror
        </label>

        <button class="primary-btn manual-submit-btn" type="submit">Save booking outcome</button>
      </form>

      <aside class="manual-summary">
        <h5>Outcome guidance</h5>
        <p>Use this when Zoom signals or support reports indicate a no-show, interruption, or early ending. Student feedback still opens after scheduled completion.</p>
      </aside>
    </div>
  </div>
</section>
