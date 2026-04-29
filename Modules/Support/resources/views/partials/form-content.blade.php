<div class="page-wrap">
  <div class="top-bar">
    <h1>Support</h1>
  </div>

  <p class="intro-text">
    Need help? Open a support ticket and our team will respond quickly to resolve your issue.
  </p>

  <div class="support-wrapper">
    <div class="feedback-card">
      <h1>Submit Feedback</h1>

      @if ($errors->any())
        <div class="error-summary" aria-live="polite">
          <strong>Please fix the highlighted fields.</strong>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form id="supportForm" method="POST" action="{{ $submitRoute }}" novalidate>
        @csrf

        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">01</span>
            <div>
              <h2>Name</h2>
              <p>{{ auth()->user()?->name ?? 'Signed in user' }}</p>
            </div>
          </div>
          <div class="form-field">
            <label for="display_name">NAME</label>
            <input type="text" id="display_name" value="{{ auth()->user()?->name }}" readonly />
          </div>
        </div>

        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">02</span>
            <div>
              <h2>Email</h2>
              <p>{{ auth()->user()?->email ?? 'Signed in email' }}</p>
            </div>
          </div>
          <div class="form-field">
            <label for="display_email">EMAIL</label>
            <input type="email" id="display_email" value="{{ auth()->user()?->email }}" readonly />
          </div>
        </div>

        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">03</span>
            <div>
              <h2>Subject <span class="required">*</span></h2>
              <p>Brief description of your issue</p>
            </div>
          </div>
          <div class="form-field">
            <label for="subject">SUBJECT</label>
            <input
              type="text"
              id="subject"
              name="subject"
              value="{{ old('subject') }}"
              placeholder="e.g. Issue with booking, payment, or account access"
              maxlength="200"
              required
              aria-invalid="{{ $errors->has('subject') ? 'true' : 'false' }}"
            />
            @error('subject')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">04</span>
            <div>
              <h2>Message <span class="required">*</span></h2>
              <p>Detailed description of your support request</p>
            </div>
          </div>
          <div class="feedback-text-wrap">
            <textarea
              id="message"
              name="message"
              rows="5"
              placeholder="Type your message here..."
              maxlength="5000"
              required
              aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}"
            >{{ old('message') }}</textarea>
            <div class="char-count">
              <span id="charCount">0</span> characters
            </div>
            @error('message')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="form-footer">
          <button type="submit" class="send-message-btn">Send message</button>
        </div>
      </form>
    </div>

    <div class="feedback-card">
      <h1>My Tickets</h1>

      @php
        $ticketStatusLabels = [
          'open' => 'Open',
          'pending' => 'Pending',
          'in_progress' => 'In Progress',
          'more_information_required' => 'More Information Required',
          'resolved' => 'Resolved',
          'closed' => 'Closed',
        ];
      @endphp

      @forelse (($supportTickets ?? collect()) as $ticket)
        <article class="form-section">
          <div class="question-title-row">
            <span class="question-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            <div>
              <h2>{{ $ticket->ticket_ref }} · {{ $ticketStatusLabels[$ticket->status] ?? ucfirst((string) $ticket->status) }}</h2>
              <p>{{ $ticket->created_at?->format('M j, Y g:i A') }}</p>
            </div>
          </div>

          <div class="form-field">
            <label>SUBJECT</label>
            <p>{{ $ticket->subject }}</p>
          </div>

          <div class="form-field">
            <label>MESSAGE</label>
            <p>{{ $ticket->message }}</p>
          </div>

          @if ($ticket->admin_reply)
            <div class="form-field">
              <label>ADMIN REPLY</label>
              <p>{{ $ticket->admin_reply }}</p>
              <small>
                {{ $ticket->handler?->name ?? 'Admin team' }}
                @if ($ticket->replied_at)
                  · {{ $ticket->replied_at->format('M j, Y g:i A') }}
                @endif
              </small>
            </div>
          @endif
        </article>
      @empty
        <p class="intro-text">No support tickets yet.</p>
      @endforelse
    </div>
  </div>

</div>
