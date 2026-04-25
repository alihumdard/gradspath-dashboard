@extends('layouts.portal-mentor')

@section('title', 'Mentor Session Notes - Grads Paths')
@section('portal_css_asset', 'assets/css/demo7.css')
@section('portal_active_nav', 'mentor-notes')

@section('page_topbar_left')
        <div class="search-wrap">
          <input
          type="text"
          class="search-input"
          placeholder="Search mentors, universities..."
          />
        </div>
@endsection

@section('portal_content')
        <div class="page-shell">
          <main class="notes-wrapper">
            <section class="notes-card">
              <div class="card-top">
                <span class="eyebrow">Internal Mentor Notes</span>
                <h1>Mentor Notes After Session</h1>
                <p class="intro-text">
                  These notes are for internal mentor use only. Students cannot view this information.
                </p>
                <p class="intro-text secondary">
                  Use this form to record what was covered, what the student still needs help with, and any useful context for future mentors.
                </p>
              </div>

              @if ($errors->any())
                <div id="notesErrorMessage" class="success-message show" aria-live="polite" data-variant="error">
                  {{ $errors->first() }}
                </div>
              @endif

              <form id="mentorNotesForm" method="POST" action="{{ $mentorNotesFormData['formAction'] ?? route('mentor.notes') }}" novalidate>
                @csrf
                <div class="form-section compact-section">
                  <div class="session-info-card">
                    <div class="session-info-header">
                      <span class="session-badge">Session Details</span>
                      <p>
                        This information is automatically filled in from the scheduled session, and attached to the mentor notes.
                      </p>
                    </div>

                    <div class="session-info-grid">
                      <div class="info-field">
                        <label for="fullName">Full Name of User</label>
                        <input type="text" id="fullName" name="fullName" value="{{ $mentorNotesFormData['session']['fullName'] ?? '' }}" readonly />
                      </div>

                      <div class="info-field">
                        <label for="userEmail">Email of User</label>
                        <input type="email" id="userEmail" name="userEmail" value="{{ $mentorNotesFormData['session']['email'] ?? '' }}" readonly />
                      </div>

                      <div class="info-field">
                        <label for="sessionDate">Date of Session</label>
                        <input type="text" id="sessionDate" name="sessionDate" value="{{ $mentorNotesFormData['session']['sessionDate'] ?? '' }}" readonly />
                      </div>

                      <div class="info-field">
                        <label for="mentorName">Full Name of Mentor</label>
                        <input type="text" id="mentorName" name="mentorName" value="{{ $mentorNotesFormData['session']['mentorName'] ?? '' }}" readonly />
                      </div>

                      <div class="info-field">
                        <label for="mentorEmail">Email of Mentor</label>
                        <input type="email" id="mentorEmail" name="mentorEmail" value="{{ $mentorNotesFormData['session']['mentorEmail'] ?? '' }}" readonly />
                      </div>

                      <div class="info-field full-width">
                        <label>Type of Session</label>

                        <div class="service-display-grid" id="sessionTypeDisplay">
                          <div class="service-view-card" data-service="Tutoring">
                            <div class="service-view-icon">
                              <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm8-1h2a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-7a2 2 0 0 0-2 2v1.2A4.9 4.9 0 0 1 11 9.5V10h2.5l2.2 2.2A1 1 0 0 0 17.4 12V11.4A2 2 0 0 0 16 10Zm-8 3c-3.2 0-6 1.6-6 3.6 0 .8.7 1.4 1.5 1.4h9c.8 0 1.5-.6 1.5-1.4C14 14.6 11.2 13 8 13Z" />
                              </svg>
                            </div>
                            <span>Tutoring</span>
                          </div>

                          <div class="service-view-card" data-service="Program Insights">
                            <div class="service-view-icon">
                              <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3 2 8l10 5 8.2-4.1V15H22V8L12 3Zm-6.8 8.8V15c0 1.9 3.1 3.5 6.8 3.5s6.8-1.6 6.8-3.5v-3.2L12 15.2l-6.8-3.4Z" />
                              </svg>
                            </div>
                            <span>Program Insights</span>
                          </div>

                          <div class="service-view-card" data-service="Interview Prep">
                            <div class="service-view-icon">
                              <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9 7V5a3 3 0 0 1 6 0v2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2Zm2 0h2V5a1 1 0 1 0-2 0v2Z" />
                              </svg>
                            </div>
                            <span>Interview Prep</span>
                          </div>

                          <div class="service-view-card" data-service="Application Review">
                            <div class="service-view-icon">
                              <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M7 3h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5M9 16.2l5.9-5.9 1.8 1.8-5.9 5.9H9v-1.8Z" />
                              </svg>
                            </div>
                            <span>Application Review</span>
                          </div>

                          <div class="service-view-card" data-service="Gap Year Planning">
                            <div class="service-view-icon">
                              <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm6.9 9h-3.1a15.7 15.7 0 0 0-1.2-4A8.1 8.1 0 0 1 18.9 11ZM12 4.1c1 1.1 2 3.4 2.4 6H9.6c.4-2.6 1.4-4.9 2.4-6ZM5.1 13h3.1c.2 1.4.6 2.8 1.2 4a8.1 8.1 0 0 1-4.3-4Zm3.1-2H5.1a8.1 8.1 0 0 1 4.3-4c-.6 1.2-1 2.6-1.2 4Zm3.8 8c-1-1.1-2-3.4-2.4-6h4.8c-.4 2.6-1.4 4.9-2.4 6Zm2.6-2c.6-1.2 1-2.6 1.2-4h3.1a8.1 8.1 0 0 1-4.3 4Z" />
                              </svg>
                            </div>
                            <span>Gap Year Planning</span>
                          </div>

                          <div class="service-view-card" data-service="Office Hours">
                            <div class="service-view-icon">
                              <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 5v4.4l3 1.8-.8 1.4L11 12V7Z" />
                              </svg>
                            </div>
                            <span>Office Hours</span>
                          </div>
                        </div>

                        <input type="hidden" id="sessionType" name="sessionType" value="{{ $mentorNotesFormData['session']['sessionType'] ?? '' }}" />
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="question-title-row">
                    <span class="question-number">01</span>
                    <div>
                      <h2>
                        What did you work on during this session?
                        <span class="required">*</span>
                      </h2>
                      <p>
                        Briefly describe the main topics, tasks, or materials covered.
                      </p>
                    </div>
                  </div>

                  <div class="notes-text-wrap">
                    <textarea
                    id="sessionWork"
                    name="worked_on"
                    rows="5"
                    placeholder="Example: We reviewed the personal statement, discussed structure, and revised the introduction..."
                    >{{ old('worked_on', $note?->worked_on) }}</textarea>
                </div>
              </div>

              <div class="form-section">
                <div class="question-title-row">
                  <span class="question-number">02</span>
                  <div>
                    <h2>
                      What should happen next, and what does the user need most?
                      <span class="required">*</span>
                    </h2>
                    <p>
                      Describe the next steps, priorities, or areas where support is still needed.
                    </p>
                  </div>
                </div>

                <div class="notes-text-wrap">
                  <textarea
                  id="nextSteps"
                  name="next_steps"
                  rows="5"
                  placeholder="Example: The user needs help narrowing school choices and strengthening interview responses..."
                  >{{ old('next_steps', $note?->next_steps) }}</textarea>
              </div>
            </div>

            <div class="form-section">
              <div class="question-title-row">
                <span class="question-number">03</span>
                <div>
                  <h2>
                    What was the result of the session?
                    <span class="required">*</span>
                  </h2>
                  <p>
                    Summarize what was accomplished or what progress was made.
                  </p>
                </div>
              </div>

              <div class="notes-text-wrap">
                <textarea
                id="sessionOutcome"
                name="session_result"
                rows="5"
                placeholder="Example: The user left with a clearer application strategy and a revised draft to build from..."
                >{{ old('session_result', $note?->session_result) }}</textarea>
            </div>
          </div>

          <div class="form-section">
            <div class="question-title-row">
              <span class="question-number">04</span>
              <div>
                <h2>
                  What was one strength and one challenge from the session?
                  <span class="required">*</span>
                </h2>
                <p>
                  Include one positive takeaway and one issue, limitation, or area for improvement.
                </p>
              </div>
            </div>

            <div class="notes-text-wrap">
              <textarea
              id="sessionReflection"
              name="strengths_challenges"
              rows="5"
              placeholder="Example: Pro: The user was engaged and prepared. Con: Time was limited, so we could not finish mock interview practice..."
              >{{ old('strengths_challenges', $note?->strengths_challenges) }}</textarea>
          </div>
        </div>

        <div class="form-section">
          <div class="question-title-row">
            <span class="question-number">05</span>
            <div>
              <h2>
                Any other notes to share?
                <span class="required">*</span>
              </h2>
              <p>
                Add any extra context that could help future mentors before the next session.
              </p>
            </div>
          </div>

          <div class="notes-text-wrap">
            <textarea
            id="otherNotes"
            name="other_notes"
            rows="5"
            placeholder="Example: The user responds well to direct feedback and would benefit from another session next week..."
            >{{ old('other_notes', $note?->other_notes) }}</textarea>

          <div class="char-count">
            <span id="charCount">0</span> characters
          </div>
        </div>
      </div>

        <div class="form-footer">
          <button type="submit" class="submit-btn">{{ $mentorNotesFormData['submitLabel'] ?? 'Submit Mentors Notes' }}</button>
        </div>

      </form>
      </section>
      </main>
      </div>
@endsection

@section('page_js')
        <script id="mentorNotesFormData" type="application/json">@json($mentorNotesFormData ?? [])</script>
        <script src="{{ asset('assets/js/demo7.js') }}"></script>
@endsection
