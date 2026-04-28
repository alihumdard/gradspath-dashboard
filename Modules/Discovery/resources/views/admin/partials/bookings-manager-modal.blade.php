<dialog
  class="admin-bookings-dialog"
  id="adminBookingsModal"
  data-related-url-template="{{ url('/admin/bookings/related/__ENTITY_TYPE__/__ENTITY_ID__') }}"
  data-update-url-template="{{ url('/admin/bookings/__BOOKING_ID__') }}"
  data-destroy-url-template="{{ url('/admin/bookings/__BOOKING_ID__') }}"
>
  <div class="modal-card admin-bookings-modal" role="dialog" aria-modal="true" aria-labelledby="adminBookingsModalTitle">
    <button class="admin-bookings-modal__close" id="adminBookingsModalClose" type="button" aria-label="Close">×</button>

    <div class="admin-bookings-modal__header">
      <div>
        <p class="admin-bookings-modal__eyebrow">Admin Actions</p>
        <h3 id="adminBookingsModalTitle">Edit Bookings</h3>
        <p class="admin-bookings-modal__subtitle" id="adminBookingsModalSubtitle">Loading related bookings…</p>
      </div>
      <div class="admin-bookings-modal__status hidden" id="adminBookingsModalStatus"></div>
    </div>

    <div class="admin-bookings-modal__body">
      <section class="admin-bookings-modal__panel" id="adminBookingsListView">
        <div class="admin-bookings-modal__loading hidden" id="adminBookingsLoading">Loading bookings…</div>
        <div class="admin-bookings-modal__error hidden" id="adminBookingsError"></div>
        <div class="admin-bookings-modal__empty hidden" id="adminBookingsEmpty">
          <h4>No related bookings</h4>
          <p>There are no active bookings available for this row.</p>
        </div>
        <div class="admin-bookings-modal__list" id="adminBookingsList"></div>
      </section>

      <section class="admin-bookings-modal__panel hidden" id="adminBookingEditView">
        <div class="admin-bookings-modal__section-head">
          <div>
            <h4>Edit booking</h4>
            <p id="adminBookingEditLabel">Update schedule and status details.</p>
          </div>
          <button class="ghost-btn" id="adminBookingEditBack" type="button">Back</button>
        </div>

        <form class="admin-bookings-form" id="adminBookingEditForm">
          <div class="admin-bookings-form__grid">
            <label>
              <span>Session date & time</span>
              <input id="adminBookingSessionAt" name="session_at" type="datetime-local" required />
              <small class="admin-bookings-form__error" data-error-for="session_at"></small>
            </label>

            <label>
              <span>Timezone</span>
              <input id="adminBookingSessionTimezone" name="session_timezone" type="text" required />
              <small class="admin-bookings-form__error" data-error-for="session_timezone"></small>
            </label>

            <label>
              <span>Duration (minutes)</span>
              <input id="adminBookingDuration" name="duration_minutes" type="number" min="15" max="300" required />
              <small class="admin-bookings-form__error" data-error-for="duration_minutes"></small>
            </label>

            <label>
              <span>Meeting type</span>
              <select id="adminBookingMeetingType" name="meeting_type" required></select>
              <small class="admin-bookings-form__error" data-error-for="meeting_type"></small>
            </label>

            <label class="admin-bookings-form__full">
              <span>Meeting link</span>
              <input id="adminBookingMeetingLink" name="meeting_link" type="url" />
              <small class="admin-bookings-form__error" data-error-for="meeting_link"></small>
            </label>

            <label>
              <span>Status</span>
              <select id="adminBookingStatusField" name="status" required></select>
              <small class="admin-bookings-form__error" data-error-for="status"></small>
            </label>

            <label>
              <span>Approval status</span>
              <select id="adminBookingApprovalStatus" name="approval_status" required></select>
              <small class="admin-bookings-form__error" data-error-for="approval_status"></small>
            </label>

            <label>
              <span>Outcome</span>
              <select id="adminBookingOutcome" name="session_outcome" required></select>
              <small class="admin-bookings-form__error" data-error-for="session_outcome"></small>
            </label>

            <label>
              <span>Completion source</span>
              <select id="adminBookingCompletionSource" name="completion_source"></select>
              <small class="admin-bookings-form__error" data-error-for="completion_source"></small>
            </label>

            <label class="admin-bookings-form__full">
              <span>Outcome note</span>
              <textarea id="adminBookingOutcomeNote" name="session_outcome_note" rows="4"></textarea>
              <small class="admin-bookings-form__error" data-error-for="session_outcome_note"></small>
            </label>

            <label class="admin-bookings-form__full">
              <span>Admin note</span>
              <textarea id="adminBookingAdminNote" name="admin_note" rows="4" required></textarea>
              <small class="admin-bookings-form__error" data-error-for="admin_note"></small>
            </label>
          </div>

          <div class="admin-bookings-form__actions">
            <button class="ghost-btn" id="adminBookingEditCancel" type="button">Cancel</button>
            <button class="primary-btn" id="adminBookingEditSubmit" type="submit">Save</button>
          </div>
        </form>
      </section>

      <section class="admin-bookings-modal__panel hidden" id="adminBookingDeleteView">
        <div class="admin-bookings-modal__section-head">
          <div>
            <h4>Delete booking</h4>
            <p id="adminBookingDeleteLabel">This safely cancels the booking and keeps its history.</p>
          </div>
          <button class="ghost-btn" id="adminBookingDeleteBack" type="button">Back</button>
        </div>

        <form class="admin-bookings-form" id="adminBookingDeleteForm">
          <label class="admin-bookings-form__full" id="adminBookingDeleteReasonField">
            <span>Reason</span>
            <textarea id="adminBookingDeleteReason" name="reason" rows="4" required></textarea>
            <small class="admin-bookings-form__error" data-error-for="reason"></small>
          </label>

          <div class="admin-bookings-form__actions">
            <button class="ghost-btn" id="adminBookingDeleteCancel" type="button">Keep</button>
            <button class="primary-btn admin-bookings-form__danger" id="adminBookingDeleteSubmit" type="submit">Delete</button>
          </div>
        </form>
      </section>
    </div>
  </div>
</dialog>
