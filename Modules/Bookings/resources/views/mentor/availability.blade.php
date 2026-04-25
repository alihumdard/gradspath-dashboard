@extends('layouts.portal-mentor')
@section('title', 'Mentor Availability - Grads Paths')
@section('portal_css_asset', 'assets/css/demo1.css')
@section('portal_js_asset', 'assets/js/mentor-availability.js')
@section('portal_active_nav', 'availability')

@section('portal_css')
  <link rel="stylesheet" href="{{ asset('assets/css/mentor-availability.css') }}" />
@endsection

@php
  $insights = $availabilityInsights ?? [];
  $initialSchedulerPayload = $schedulerPayload ?? [];
  $officeHoursConfigData = $officeHoursConfig ?? [];
  $officeHoursPreviewData = $officeHoursPreview ?? [];
  $hasOldInput = session()->getOldInput() !== [];

  $initialSchedulerPayload['save_url'] = route('mentor.availability.update');
  $initialSchedulerPayload['timezone'] = old('timezone', $initialSchedulerPayload['timezone'] ?? ($availabilityData['timezone'] ?? 'UTC'));
  $initialSchedulerPayload['uses_old_input'] = $hasOldInput;
  $initialSchedulerPayload['date_slots'] = collect(
      $hasOldInput ? (json_decode((string) old('date_slots_payload', '[]'), true) ?: []) : ($initialSchedulerPayload['date_slots'] ?? [])
  )
      ->filter(fn ($row) => is_array($row) && (!empty($row['date']) || !empty($row['key'])))
      ->map(function (array $row) {
          $date = (string) ($row['date'] ?? $row['key'] ?? '');
          $slots = collect($row['slots'] ?? [])
              ->filter(fn ($slot) => is_array($slot))
              ->map(fn ($slot) => [
                  'slot_id' => isset($slot['slot_id']) ? (int) $slot['slot_id'] : null,
                  'start_time' => (string) ($slot['start_time'] ?? ''),
                  'end_time' => (string) ($slot['end_time'] ?? ''),
                  'service_config_id' => isset($slot['service_config_id']) ? (int) $slot['service_config_id'] : null,
                  'is_booked' => !empty($slot['is_booked']),
                  'booking_count' => isset($slot['booking_count']) ? (int) $slot['booking_count'] : 0,
              ])
              ->values()
              ->all();

          return [
              'key' => $date,
              'label' => \Carbon\Carbon::parse($date)->format('l'),
              'enabled' => !empty($row['enabled']) || count($slots) > 0,
              'slot_count' => isset($row['slot_count']) ? (int) $row['slot_count'] : count($slots),
              'booked_count' => isset($row['booked_count']) ? (int) $row['booked_count'] : 0,
              'bookings' => collect($row['bookings'] ?? [])
                  ->filter(fn ($booking) => is_array($booking))
                  ->values()
                  ->all(),
              'slots' => $slots,
          ];
      })
      ->values()
      ->all();
  $initialSchedulerPayload['office_hours'] = [
      'config' => $officeHoursConfigData,
      'preview' => $officeHoursPreviewData,
  ];
  $officeHoursEnabled = filter_var(old('office_hours.enabled', $officeHoursConfigData['enabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
  $selectedOfficeHoursServiceId = old('office_hours.service_config_id', $officeHoursConfigData['service_config_id'] ?? '');
  $selectedOfficeHoursDay = old('office_hours.day_of_week', $officeHoursConfigData['day_of_week'] ?? 'sun');
  $selectedOfficeHoursTime = old('office_hours.start_time', $officeHoursConfigData['start_time'] ?? '20:00');
  $selectedOfficeHoursTimezone = old('office_hours.timezone', $officeHoursConfigData['timezone'] ?? 'UTC');
  $selectedOfficeHoursFrequency = old('office_hours.frequency', $officeHoursConfigData['frequency'] ?? 'weekly');
  if ($hasOldInput) {
      $officeHoursConfigData['enabled'] = $officeHoursEnabled;
      $officeHoursConfigData['service_config_id'] = $selectedOfficeHoursServiceId !== '' ? (int) $selectedOfficeHoursServiceId : null;
      $officeHoursConfigData['day_of_week'] = $selectedOfficeHoursDay;
      $officeHoursConfigData['start_time'] = $selectedOfficeHoursTime;
      $officeHoursConfigData['timezone'] = $selectedOfficeHoursTimezone;
      $officeHoursConfigData['frequency'] = $selectedOfficeHoursFrequency;
      $initialSchedulerPayload['office_hours']['config'] = $officeHoursConfigData;
  }
@endphp

@section('page_topbar_left')
  <div class="search-wrap">
    <input type="text" class="search-input" placeholder="Search mentors, universities..." />
  </div>
@endsection

@section('portal_content')
  <main class="availability-main">
    <header class="availability-page-header">
      <div class="availability-page-title">Set Your Date-Specific Availability</div>
      <p class="availability-page-sub">
        Build your booking schedule date by date. Each slot is tied to one mentor service and only applies to the specific date you add.
      </p>
    </header>

    @if (session('success'))
      <div class="availability-alert availability-alert--success">
        <strong>Availability saved.</strong>
        <p>{{ session('success') }}</p>
      </div>
    @endif

    @if ($errors->any())
      <div class="availability-alert availability-alert--error">
        <strong>Please review the availability form.</strong>
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="availability-alert" id="availabilityAlert" hidden></div>

    <section class="availability-stats">
      <article class="availability-stat availability-stat--accent">
        <div class="availability-stat-label">Saved Dates</div>
        <div class="availability-stat-value" id="availabilityActiveDays">{{ $insights['active_days'] ?? 0 }}</div>
        <div class="availability-stat-hint">dates currently open</div>
      </article>

      <article class="availability-stat">
        <div class="availability-stat-label">Total Hours</div>
        <div class="availability-stat-value" id="availabilityWeeklyHours">{{ $insights['weekly_hours'] ?? '0 hrs' }}</div>
        <div class="availability-stat-hint">hours across saved dates</div>
      </article>

      <article class="availability-stat">
        <div class="availability-stat-label">Upcoming Slots</div>
        <div class="availability-stat-value" id="availabilityProjectedSlots">{{ $insights['open_slots_count'] ?? 0 }}</div>
        <div class="availability-stat-hint">saved upcoming slots on your calendar</div>
      </article>

      <article class="availability-stat">
        <div class="availability-stat-label">Next Slot</div>
        <div class="availability-stat-value availability-stat-value--compact" id="availabilityNextSlot">
          {{ $insights['next_open_slot'] ?? 'No open slots generated yet' }}
        </div>
        <div class="availability-stat-hint">first upcoming block from this schedule</div>
      </article>
    </section>

    <form method="POST" action="{{ route('mentor.availability.update') }}" id="mentorAvailabilityForm" novalidate>
      @csrf
      @method('PATCH')

      <section class="availability-card availability-card--office-hours">
        <div class="availability-card-header availability-card-header--office-hours">
          <div>
            <div class="availability-card-title">Office Hours for This Mentor</div>
            <div class="availability-card-sub">
              Configure one recurring office-hours series for this mentor and preview how the next session will appear to students.
            </div>
          </div>
          <span class="availability-badge availability-badge--office-hours" id="officeHoursSpotsBadge">
            {{ $officeHoursPreviewData['spots_badge'] ?? '0/3 spots filled' }}
          </span>
        </div>

        <div class="availability-office-hours-shell">
          <div class="availability-office-hours-config">
            <div class="availability-office-hours-toggle">
              <input type="hidden" name="office_hours[enabled]" value="0" />
              <label class="availability-switch" for="officeHoursEnabled">
                <input
                  type="checkbox"
                  id="officeHoursEnabled"
                  name="office_hours[enabled]"
                  value="1"
                  @checked($officeHoursEnabled)
                />
                <span class="availability-switch-ui" aria-hidden="true"></span>
                <span class="availability-switch-copy">
                  <strong>Enable recurring office hours</strong>
                  <span>Students book these sessions with 1 credit at a recurring weekly or biweekly time.</span>
                </span>
              </label>
            </div>

            <div class="availability-office-hours-fields" id="officeHoursFields">
              <div class="availability-field">
                <label for="officeHoursService">This Week&rsquo;s Focus</label>
                <select id="officeHoursService" name="office_hours[service_config_id]">
                  <option value="">Select service</option>
                  @foreach ($officeHoursConfigData['service_options'] ?? $initialSchedulerPayload['service_options'] ?? [] as $option)
                    <option value="{{ $option['value'] }}" @selected((string) $selectedOfficeHoursServiceId === (string) $option['value'])>
                      {{ $option['label'] }}
                    </option>
                  @endforeach
                </select>
                <p class="availability-field-error" id="officeHoursServiceError">{{ $errors->first('office_hours.service_config_id') }}</p>
              </div>

              <div class="availability-field">
                <label for="officeHoursDay">Recurring Day</label>
                <select id="officeHoursDay" name="office_hours[day_of_week]">
                  @foreach ($officeHoursConfigData['weekday_options'] ?? [] as $option)
                    <option value="{{ $option['value'] }}" @selected($selectedOfficeHoursDay === $option['value'])>
                      {{ $option['label'] }}
                    </option>
                  @endforeach
                </select>
                <p class="availability-field-error" id="officeHoursDayError">{{ $errors->first('office_hours.day_of_week') }}</p>
              </div>

              <div class="availability-field">
                <label for="officeHoursTime">Start Time</label>
                <select id="officeHoursTime" name="office_hours[start_time]">
                  @foreach ($initialSchedulerPayload['time_options'] ?? [] as $option)
                    <option value="{{ $option['value'] }}" @selected($selectedOfficeHoursTime === $option['value'])>
                      {{ $option['label'] }}
                    </option>
                  @endforeach
                </select>
                <p class="availability-field-error" id="officeHoursTimeError">{{ $errors->first('office_hours.start_time') }}</p>
              </div>

              <div class="availability-field">
                <label for="officeHoursTimezone">Timezone</label>
                <select id="officeHoursTimezone" name="office_hours[timezone]">
                  @foreach ($timezoneOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedOfficeHoursTimezone === $value)>
                      {{ $label }} ({{ $value }})
                    </option>
                  @endforeach
                </select>
                <p class="availability-field-error" id="officeHoursTimezoneError">{{ $errors->first('office_hours.timezone') }}</p>
              </div>

              <div class="availability-field">
                <label for="officeHoursFrequency">Frequency</label>
                <select id="officeHoursFrequency" name="office_hours[frequency]">
                  @foreach ($officeHoursConfigData['frequency_options'] ?? [] as $option)
                    <option value="{{ $option['value'] }}" @selected($selectedOfficeHoursFrequency === $option['value'])>
                      {{ $option['label'] }}
                    </option>
                  @endforeach
                </select>
                <p class="availability-field-error" id="officeHoursFrequencyError">{{ $errors->first('office_hours.frequency') }}</p>
              </div>

              <div class="availability-office-hours-fixed">
                <div class="availability-office-hours-fixed-label">Capacity</div>
                <div class="availability-office-hours-fixed-value">3 spots</div>
                <p>Meeting type stays fixed as small-group office hours.</p>
              </div>
            </div>
          </div>

          <div class="availability-office-hours-preview">
            <div class="availability-office-hours-mentor">
              <div class="availability-office-hours-mentor-mark" aria-hidden="true">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path d="M12 3 2 8l10 5 10-5-10-5Z"></path>
                  <path d="M6 11v4.5c0 1.1 2.7 2.5 6 2.5s6-1.4 6-2.5V11"></path>
                </svg>
              </div>
              <div>
                <div class="availability-office-hours-mentor-name" id="officeHoursMentorName">{{ $officeHoursPreviewData['mentor_name'] ?? auth()->user()?->name ?? 'Mentor' }}</div>
                <div class="availability-office-hours-mentor-meta" id="officeHoursMentorMeta">{{ $officeHoursPreviewData['mentor_meta'] ?? 'Mentor' }}</div>
              </div>
            </div>

            <div class="availability-office-hours-preview-grid">
              <article class="availability-office-hours-tile">
                <div class="availability-office-hours-tile-label">This Week&rsquo;s Focus</div>
                <div class="availability-office-hours-tile-value" id="officeHoursWeeklyService">{{ $officeHoursPreviewData['weekly_service'] ?? 'Office Hours' }}</div>
              </article>

              <article class="availability-office-hours-tile">
                <div class="availability-office-hours-tile-label">Recurring Weekly Time</div>
                <div class="availability-office-hours-tile-value" id="officeHoursRecurringTime">{{ $officeHoursPreviewData['recurring_time'] ?? 'Schedule coming soon' }}</div>
              </article>

              <article class="availability-office-hours-tile">
                <div class="availability-office-hours-tile-label">Meeting Type</div>
                <div class="availability-office-hours-tile-value" id="officeHoursMeetingType">{{ $officeHoursPreviewData['meeting_type'] ?? 'Small Group Office Hours' }}</div>
              </article>

              <article class="availability-office-hours-tile">
                <div class="availability-office-hours-tile-label">Current Availability</div>
                <div class="availability-office-hours-tile-value" id="officeHoursAvailabilityText">{{ $officeHoursPreviewData['availability_text'] ?? 'No upcoming session generated yet' }}</div>
              </article>
            </div>

            <div class="availability-office-hours-note" id="officeHoursNote">
              {{ $officeHoursPreviewData['note'] ?? 'Turn on office hours to publish one recurring weekly or biweekly session for this mentor.' }}
            </div>
          </div>
        </div>
      </section>

      <section class="availability-card">
        <div class="availability-card-header">
          <div>
            <div class="availability-card-title">Schedule Settings</div>
            <div class="availability-card-sub">Timezone for the date-specific schedule below</div>
          </div>
        </div>

        <div class="availability-settings-grid">
          <div class="availability-field">
            <label for="availabilityTimezone">Timezone</label>
            <select id="availabilityTimezone" name="timezone">
              @foreach ($timezoneOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('timezone', $availabilityData['timezone'] ?? 'UTC') === $value)>
                  {{ $label }} ({{ $value }})
                </option>
              @endforeach
            </select>
          </div>

        </div>

        <div class="availability-settings-note" id="availabilityRangeNote">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span id="availabilityRangeSummary">{{ $insights['effective_range'] ?? 'Dates stay active until you remove them' }}</span>
        </div>
      </section>

      <section class="availability-card availability-card--scheduler">
        <div class="availability-card-header">
          <div>
            <div class="availability-card-title">Availability Scheduler</div>
            <div class="availability-card-sub">Use the calendar to click a future day and edit only that date's service-specific slots from the day panel.</div>
          </div>
          <span class="availability-badge" id="availabilityActiveBadge">
            {{ ($insights['active_days'] ?? 0) . ' active day' . (($insights['active_days'] ?? 0) === 1 ? '' : 's') }}
          </span>
        </div>

        <div class="availability-scheduler-toolbar">
          <p class="availability-toolbar-copy">
            Past days appear completed and locked. Clicking a future date edits only that date's service-specific slots.
          </p>
          <div class="availability-draft-state" id="availabilityDraftState">All changes saved</div>
        </div>

        <div class="availability-scheduler-shell">
          <div class="availability-month-shell">
            <section class="availability-month-card">
              <div class="availability-month-toolbar">
                <div class="availability-month-view-pill">
                  <button type="button" class="availability-month-view-btn" data-view="day">Day</button>
                  <button type="button" class="availability-month-view-btn is-active" data-view="week">Week</button>
                  {{-- <button type="button" class="availability-month-view-btn" data-view="month">Month</button> --}}
                  {{-- <button type="button" class="availability-month-view-btn" data-view="year">Year</button> --}}
                </div>

                <div class="availability-month-nav">
                  <button type="button" class="availability-month-today-btn" id="availabilityTodayBtn">Today</button>
                  <button type="button" class="availability-month-arrow" id="availabilityPrevMonthBtn" aria-label="Previous month">&lsaquo;</button>
                  <button type="button" class="availability-month-arrow" id="availabilityNextMonthBtn" aria-label="Next month">&rsaquo;</button>
                </div>
              </div>

              <div class="availability-month-heading">
                <h3 id="availabilityMonthLabel">Month</h3>
                <p id="availabilityMonthMeta">Click a future day to edit that date's service-specific slots.</p>
              </div>

              <div class="availability-month-grid" id="availabilityMonthGrid" aria-label="Date-specific availability calendar"></div>

              <section class="availability-bookings-panel" id="availabilityBookingsPanel">
                <div class="availability-bookings-head">
                  <div>
                    <div class="availability-bookings-kicker">Bookings</div>
                    <h4 id="availabilityBookingsTitle">Bookings for selected date</h4>
                    <p id="availabilityBookingsSubtitle">Click a date to review who booked, what service they chose, and which slot is reserved.</p>
                  </div>
                  <span class="availability-bookings-count" id="availabilityBookingsCount">0 booked</span>
                </div>

                <div class="availability-bookings-empty" id="availabilityBookingsEmpty">
                  No bookings for this date yet.
                </div>

                <div class="availability-bookings-list" id="availabilityBookingsList"></div>
              </section>
            </section>

            <aside class="availability-day-panel" id="availabilityDayPanel">
              <div class="availability-day-panel-head">
                <div>
                  <div class="availability-day-panel-kicker">Date Availability</div>
                  <h3 id="availabilityDayPanelTitle">Select a future day</h3>
                  <p id="availabilityDayPanelSubtitle">Choose a date from the calendar to edit that date's slots and services.</p>
                </div>
                <span class="availability-day-status" id="availabilityDayStatus">No slots</span>
              </div>

              <div class="availability-day-panel-note" id="availabilityDayPanelNote">
                Each time block below belongs to one mentor service and only updates the selected date.
              </div>

              <div class="availability-day-panel-actions">
                <button type="button" class="availability-secondary-btn" id="availabilityAddSlotBtn">Add Slot</button>
                <button type="button" class="availability-ghost-btn" id="availabilityClearDayBtn">Clear Day</button>
              </div>

              <div class="availability-day-panel-empty" id="availabilityDayEmptyState">
                This date is currently unavailable. Add a slot to make it active.
              </div>

              <div class="availability-day-slot-list" id="availabilityDaySlotList"></div>

              <p class="availability-day-error" id="availabilityDayError"></p>
            </aside>
          </div>
        </div>

        <div id="availabilityHiddenState" hidden></div>

        <div class="availability-card-footer">
          <span class="availability-footer-note">
            Save applies these date-specific service slots to future unbooked 1 on 1 availability only.
          </span>
          <button class="availability-save-btn" id="availabilitySaveBtn" type="submit">Save Availability</button>
        </div>
      </section>
    </form>
  </main>

  <script id="mentorAvailabilityPayload" type="application/json">@json($initialSchedulerPayload)</script>
  <div class="availability-toast" id="availabilityToast"></div>
@endsection
