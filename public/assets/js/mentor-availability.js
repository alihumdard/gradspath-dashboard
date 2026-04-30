(function mentorAvailabilityScheduler() {
  const root = document.documentElement;
  const body = document.body;
  const themeToggle = document.getElementById("themeToggle");
  const menuButton = document.getElementById("mobileMenuToggle");
  const overlay = document.getElementById("sidebarOverlay");
  const shell = document.querySelector(".app-shell");
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

  const payloadEl = document.getElementById("mentorAvailabilityPayload");
  const form = document.getElementById("mentorAvailabilityForm");

  initTheme();
  initShell();

  if (!payloadEl || !form) {
    return;
  }

  const timezoneSelect = document.getElementById("availabilityTimezone");
  const effectiveFromInput = document.getElementById("availabilityEffectiveFrom");
  const effectiveUntilInput = document.getElementById("availabilityEffectiveUntil");
  const rangeNote = document.getElementById("availabilityRangeNote");
  const rangeSummary = document.getElementById("availabilityRangeSummary");
  const activeDaysStat = document.getElementById("availabilityActiveDays");
  const weeklyHoursStat = document.getElementById("availabilityWeeklyHours");
  const projectedSlotsStat = document.getElementById("availabilityProjectedSlots");
  const nextSlotStat = document.getElementById("availabilityNextSlot");
  const activeBadge = document.getElementById("availabilityActiveBadge");
  const draftStateEl = document.getElementById("availabilityDraftState");
  const saveButton = document.getElementById("availabilitySaveBtn");
  const hiddenState = document.getElementById("availabilityHiddenState");
  const alertEl = document.getElementById("availabilityAlert");
  const toastEl = document.getElementById("availabilityToast");
  const officeHoursEnabledInput = document.getElementById("officeHoursEnabled");
  const officeHoursServiceSelect = document.getElementById("officeHoursService");
  const officeHoursDaySelect = document.getElementById("officeHoursDay");
  const officeHoursTimeSelect = document.getElementById("officeHoursTime");
  const officeHoursFrequencySelect = document.getElementById("officeHoursFrequency");
  const officeHoursFields = document.getElementById("officeHoursFields");
  const officeHoursSpotsBadge = document.getElementById("officeHoursSpotsBadge");
  const officeHoursMentorName = document.getElementById("officeHoursMentorName");
  const officeHoursMentorMeta = document.getElementById("officeHoursMentorMeta");
  const officeHoursWeeklyService = document.getElementById("officeHoursWeeklyService");
  const officeHoursRecurringTime = document.getElementById("officeHoursRecurringTime");
  const officeHoursMeetingType = document.getElementById("officeHoursMeetingType");
  const officeHoursAvailabilityText = document.getElementById("officeHoursAvailabilityText");
  const officeHoursNote = document.getElementById("officeHoursNote");
  const officeHoursServiceError = document.getElementById("officeHoursServiceError");
  const officeHoursDayError = document.getElementById("officeHoursDayError");
  const officeHoursTimeError = document.getElementById("officeHoursTimeError");
  const officeHoursFrequencyError = document.getElementById("officeHoursFrequencyError");
  const serviceInputs = Array.from(document.querySelectorAll(".availability-service-option-input"));

  const monthGrid = document.getElementById("availabilityMonthGrid");
  const monthLabel = document.getElementById("availabilityMonthLabel");
  const monthMeta = document.getElementById("availabilityMonthMeta");
  const viewButtons = Array.from(document.querySelectorAll(".availability-month-view-btn[data-view]"));
  const todayButton = document.getElementById("availabilityTodayBtn");
  const prevMonthButton = document.getElementById("availabilityPrevMonthBtn");
  const nextMonthButton = document.getElementById("availabilityNextMonthBtn");

  const dayPanelTitle = document.getElementById("availabilityDayPanelTitle");
  const dayPanelSubtitle = document.getElementById("availabilityDayPanelSubtitle");
  const dayPanelStatus = document.getElementById("availabilityDayStatus");
  const dayPanelNote = document.getElementById("availabilityDayPanelNote");
  const addSlotButton = document.getElementById("availabilityAddSlotBtn");
  const clearDayButton = document.getElementById("availabilityClearDayBtn");
  const emptyState = document.getElementById("availabilityDayEmptyState");
  const slotList = document.getElementById("availabilityDaySlotList");
  const dayErrorEl = document.getElementById("availabilityDayError");
  const bookingsTitle = document.getElementById("availabilityBookingsTitle");
  const bookingsSubtitle = document.getElementById("availabilityBookingsSubtitle");
  const bookingsCount = document.getElementById("availabilityBookingsCount");
  const bookingsEmpty = document.getElementById("availabilityBookingsEmpty");
  const bookingsList = document.getElementById("availabilityBookingsList");

  let toastTimer = null;
  let blockSequence = 0;

  const payload = parsePayload(payloadEl.textContent);
  const state = createState(payload);
  autoSaveDetectedTimezone();

  bindEvents();
  safeRender();

  function initTheme() {
    const savedTheme = localStorage.getItem("theme") || "light";
    root.setAttribute("data-theme", savedTheme);

    if (themeToggle) {
      themeToggle.textContent = savedTheme === "dark" ? "Light Mode" : "Dark Mode";
      themeToggle.addEventListener("click", () => {
        const nextTheme = (root.getAttribute("data-theme") || "light") === "dark" ? "light" : "dark";
        root.setAttribute("data-theme", nextTheme);
        localStorage.setItem("theme", nextTheme);
        themeToggle.textContent = nextTheme === "dark" ? "Light Mode" : "Dark Mode";
      });
    }
  }

  function initShell() {
    if (menuButton && shell) {
      menuButton.addEventListener("click", () => {
        shell.classList.add("sidebar-active");
        body.classList.add("sidebar-open");
      });
    }

    if (overlay && shell) {
      overlay.addEventListener("click", () => {
        shell.classList.remove("sidebar-active");
        body.classList.remove("sidebar-open");
      });
    }
  }

  function parsePayload(text) {
    try {
      return JSON.parse(text || "{}");
    } catch (error) {
      return {};
    }
  }

  function createState(input) {
    const days = {};
    const dayOrder = Array.isArray(input.date_slots) ? input.date_slots.map((day) => String(day.key || "")) : [];

    (Array.isArray(input.date_slots) ? input.date_slots : []).forEach((day) => {
      const key = String(day.key || "");
      if (!key) {
        return;
      }

      days[key] = {
        key,
        label: String(day.label || key),
        bookedCount: Number(day.booked_count || 0),
        bookings: Array.isArray(day.bookings) ? day.bookings : [],
        blocks: sortBlocks((Array.isArray(day.slots) ? day.slots : []).map((slot) => normalizeBlock(slot, input.service_options))),
      };
    });

    const calendarMonth = input.calendar_month || {};
    const monthDate = new Date(
      Number(calendarMonth.year || new Date().getFullYear()),
      Number(calendarMonth.month || new Date().getMonth() + 1) - 1,
      1,
    );
    const today = parseDateInput(String(input.today || formatDateKey(new Date())));
    const initialSelectedDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    return {
      saveUrl: String(input.save_url || form.action || ""),
      timezoneOptions: Array.isArray(input.timezone_options) ? input.timezone_options : [],
      serviceOptions: normalizeServiceOptions(input.service_options),
      timeOptions: Array.isArray(input.time_options) ? input.time_options : [],
      windowWeeks: Number(input.window_weeks || 12),
      timezone: String(input.timezone || "UTC"),
      effectiveFrom: String(input.effective_from || ""),
      effectiveUntil: String(input.effective_until || ""),
      dayOrder,
      days,
      serverInsights: input.insights && typeof input.insights === "object" ? input.insights : {},
      hasSavedTimezone: Boolean(input.has_saved_timezone),
      timezoneAutoSaveUrl: String(input.timezone_autosave_url || ""),
      dirty: Boolean(input.uses_old_input),
      saving: false,
      serverErrors: {},
      validation: {
        dayErrors: {},
        slotErrors: {},
        officeHoursErrors: {},
        rangeError: "",
        hasErrors: false,
      },
      alert: null,
      today,
      currentMonth: monthDate,
      currentView: "week",
      selectedDate: initialSelectedDate,
      selectedDayKey: formatDateKey(initialSelectedDate),
      officeHours: normalizeOfficeHours(input.office_hours, input.service_options),
    };
  }

  function normalizeOfficeHours(input, serviceOptions = []) {
    const config = input?.config && typeof input.config === "object" ? input.config : {};
    const preview = input?.preview && typeof input.preview === "object" ? input.preview : {};
    const optionSource = Array.isArray(input?.service_options) && input.service_options.length > 0
      ? input.service_options
      : Array.isArray(serviceOptions)
      ? serviceOptions
      : [];
    const firstServiceOption = optionSource.length > 0 ? optionSource[0] : null;
    const fallbackServiceId = firstServiceOption?.value ? Number(firstServiceOption.value) : null;
    const serviceConfigId = config.service_config_id ? Number(config.service_config_id) : fallbackServiceId;
    const fallbackServiceName = serviceLabelFromOptions(optionSource, serviceConfigId);

    return {
      enabled: Boolean(config.enabled),
      serviceConfigId,
      dayOfWeek: String(config.day_of_week || "sun"),
      startTime: String(config.start_time || "20:00"),
      timezone: String(config.timezone || "UTC"),
      frequency: "weekly",
      meetingType: String(config.meeting_type || preview.meeting_type || "Small Group Office Hours"),
      preview: {
        mentorName: String(preview.mentor_name || "Mentor"),
        mentorMeta: String(preview.mentor_meta || "Mentor"),
        spotsBadge: String(preview.spots_badge || "0/3 spots filled"),
        weeklyService: String(preview.weekly_service || fallbackServiceName || "Office Hours"),
        recurringTime: String(preview.recurring_time || "Schedule coming soon"),
        meetingType: String(preview.meeting_type || "Small Group Office Hours"),
        availabilityText: String(preview.availability_text || "No upcoming session generated yet"),
        note: String(preview.note || "Turn on office hours to publish one recurring weekly session for this mentor."),
        serviceLocked: Boolean(preview.service_locked),
        hasUpcomingSession: Boolean(preview.has_upcoming_session),
      },
    };
  }

  function bindEvents() {
    timezoneSelect?.addEventListener("change", () => {
      state.timezone = timezoneSelect.value;
      state.officeHours.timezone = timezoneSelect.value;
      markDirty();
      safeRender();
    });

    effectiveFromInput?.addEventListener("change", () => {
      state.effectiveFrom = effectiveFromInput.value;
      markDirty();
      safeRender();
    });

    effectiveUntilInput?.addEventListener("change", () => {
      state.effectiveUntil = effectiveUntilInput.value;
      markDirty();
      safeRender();
    });

    form.addEventListener("submit", handleSubmit);
    [
      officeHoursEnabledInput,
      officeHoursServiceSelect,
      officeHoursDaySelect,
      officeHoursTimeSelect,
      officeHoursFrequencySelect,
    ].forEach((field) => {
      field?.addEventListener("change", handleOfficeHoursChange);
    });
    serviceInputs.forEach((input) => {
      input.addEventListener("change", handleServiceSelectionChange);
    });

    monthGrid?.addEventListener("click", (event) => {
      const monthCard = event.target.closest("[data-view-month]");
      if (monthCard) {
        const monthIndex = Number(monthCard.dataset.viewMonth);
        if (Number.isFinite(monthIndex)) {
          state.currentMonth = new Date(state.currentMonth.getFullYear(), monthIndex, 1);
          state.currentView = "month";
          syncSelectedDateToCurrentMonth();
          safeRender();
        }
        return;
      }

      const cell = event.target.closest("[data-calendar-date]");

      if (!cell || cell.dataset.locked === "true" || cell.dataset.outsideMonth === "true") {
        return;
      }

      const date = parseDateInput(String(cell.dataset.calendarDate || ""));
      if (!date) {
        return;
      }

      state.selectedDate = date;
      state.selectedDayKey = String(cell.dataset.dayKey || formatDateKey(date));
      safeRender();
    });

    viewButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const nextView = String(button.dataset.view || "month");
        if (!["day", "week"].includes(nextView)) {
          return;
        }

        state.currentView = nextView;

        if (!state.selectedDate) {
          state.selectedDate = findFirstSelectableDate(state.currentMonth, state.today);
          state.selectedDayKey = formatDateKey(state.selectedDate);
        }

        if (nextView === "day" || nextView === "week") {
          state.currentMonth = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1);
        }

        safeRender();
      });
    });

    slotList?.addEventListener("input", handleSlotFieldChange);
    slotList?.addEventListener("change", handleSlotFieldChange);
    slotList?.addEventListener("click", handleSlotListClick);

    addSlotButton?.addEventListener("click", () => {
      addSuggestedBlock(state.selectedDayKey);
      safeRender();
    });

    clearDayButton?.addEventListener("click", () => {
      clearDay(state.selectedDayKey);
      safeRender();
    });

    todayButton?.addEventListener("click", () => {
      state.currentMonth = new Date(state.today.getFullYear(), state.today.getMonth(), 1);
      state.selectedDate = new Date(state.today.getFullYear(), state.today.getMonth(), state.today.getDate());
      state.selectedDayKey = formatDateKey(state.selectedDate);
      safeRender();
    });

    prevMonthButton?.addEventListener("click", () => {
      shiftView(-1);
      safeRender();
    });

    nextMonthButton?.addEventListener("click", () => {
      shiftView(1);
      safeRender();
    });
  }

  async function autoSaveDetectedTimezone() {
    if (state.hasSavedTimezone) {
      return;
    }

    const detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (!detectedTimezone) {
      return;
    }

    const supported = state.timezoneOptions.map((option) => option.value);
    if (!supported.includes(detectedTimezone)) {
      return;
    }

    state.timezone = detectedTimezone;
    state.officeHours.timezone = detectedTimezone;
    safeRender();

    if (!state.timezoneAutoSaveUrl || !csrfToken) {
      return;
    }

    try {
      await fetch(state.timezoneAutoSaveUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
        body: JSON.stringify({ timezone: detectedTimezone }),
      });

      state.hasSavedTimezone = true;
    } catch (error) {
      // Leave the detected timezone selected locally even if the auto-save call fails.
    }
  }

  function handleSlotFieldChange(event) {
    const select = event.target.closest("[data-slot-field]");
    if (!select) {
      return;
    }

    const blockId = String(select.dataset.blockId || "");
    const field = String(select.dataset.slotField || "");
    const block = getBlock(state.selectedDayKey, blockId);

    if (!block) {
      return;
    }

    if (event.type === "input" && field !== "service") {
      return;
    }

    if (block.isBooked) {
      safeRender();
      return;
    }

    if (field === "start") {
      block.startTime = normalizeTimeFieldValue(select.value, block.startTime);
      block.endTime = endTimeForService(block.startTime, block.serviceConfigId);
    }

    if (field === "end") {
      block.endTime = normalizeTimeFieldValue(select.value, block.endTime);
    }

    if (field === "service") {
      block.serviceConfigId = select.value ? Number(select.value) : null;
      block.sessionType = normalizeBlockSessionType(block.sessionType, block.serviceConfigId);
      block.endTime = endTimeForService(block.startTime, block.serviceConfigId);
    }

    if (field === "session") {
      block.sessionType = normalizeBlockSessionType(select.value, block.serviceConfigId);
    }

    state.days[state.selectedDayKey].blocks = sortBlocks(state.days[state.selectedDayKey].blocks);
    markDirty();
    safeRender();
  }

  function handleServiceSelectionChange() {
    state.serviceOptions = serviceOptionsFromInputs();

    if (
      state.officeHours.serviceConfigId
      && !state.serviceOptions.some((option) => Number(option.value) === Number(state.officeHours.serviceConfigId))
    ) {
      state.officeHours.serviceConfigId = defaultServiceConfigId();
    }

    markDirty();
    safeRender();
  }

  function handleSlotListClick(event) {
    const removeButton = event.target.closest("[data-remove-slot]");
    if (!removeButton) {
      return;
    }

    const blockId = String(removeButton.dataset.blockId || "");
    const block = getBlock(state.selectedDayKey, blockId);

    if (block?.isBooked) {
      return;
    }

    deleteBlock(state.selectedDayKey, blockId);
    safeRender();
  }

  function render() {
    state.validation = validateState();
    syncFormValues();
    syncHiddenInputs();
    renderAlert();
    renderStats();
    renderDraftState();
    renderRangeNote();
    renderViewButtons();
    renderCalendar();
    renderDayPanel();
    renderBookingsPanel();
    renderOfficeHours();
    renderSaveButton();
  }

  function safeRender() {
    try {
      render();
    } catch (error) {
      console.error("Mentor availability render failed", error);
      try {
        renderViewButtons();
        renderCalendar();
        renderDayPanel();
        renderBookingsPanel();
      } catch (fallbackError) {
        console.error("Mentor availability fallback render failed", fallbackError);
      }
    }
  }

  function renderOfficeHours() {
    const preview = state.dirty ? buildDraftOfficeHoursPreview() : state.officeHours.preview;

    syncOfficeHoursFields();
    renderOfficeHoursErrors();

    if (officeHoursFields) {
      officeHoursFields.classList.toggle("is-disabled", !state.officeHours.enabled);
    }

    if (officeHoursSpotsBadge) {
      officeHoursSpotsBadge.textContent = preview.spotsBadge;
    }

    if (officeHoursMentorName) {
      officeHoursMentorName.textContent = preview.mentorName;
    }

    if (officeHoursMentorMeta) {
      officeHoursMentorMeta.textContent = preview.mentorMeta;
    }

    if (officeHoursWeeklyService) {
      officeHoursWeeklyService.textContent = preview.weeklyService;
    }

    if (officeHoursRecurringTime) {
      officeHoursRecurringTime.textContent = preview.recurringTime;
    }

    if (officeHoursMeetingType) {
      officeHoursMeetingType.textContent = preview.meetingType;
    }

    if (officeHoursAvailabilityText) {
      officeHoursAvailabilityText.textContent = preview.availabilityText;
    }

    if (officeHoursNote) {
      officeHoursNote.textContent = preview.note;
    }
  }

  function renderViewButtons() {
    viewButtons.forEach((button) => {
      button.classList.toggle("is-active", button.dataset.view === state.currentView);
    });
  }

  function renderAlert() {
    if (!alertEl) {
      return;
    }

    if (!state.alert) {
      alertEl.hidden = true;
      alertEl.className = "availability-alert";
      alertEl.innerHTML = "";
      return;
    }

    const items = Array.isArray(state.alert.items) ? state.alert.items : [];
    alertEl.hidden = false;
    alertEl.className = `availability-alert availability-alert--${state.alert.type || "info"}`;
    alertEl.innerHTML = `
      <strong>${escapeHtml(state.alert.title || state.alert.message || "")}</strong>
      ${state.alert.message && state.alert.title ? `<p>${escapeHtml(state.alert.message)}</p>` : ""}
      ${items.length ? `<ul>${items.map((item) => `<li>${escapeHtml(item)}</li>`).join("")}</ul>` : ""}
    `;
  }

  function renderStats() {
    const insights = state.dirty ? computeDraftInsights() : {
      active_days: state.serverInsights.active_days ?? computeDraftInsights().active_days,
      weekly_hours: state.serverInsights.weekly_hours ?? computeDraftInsights().weekly_hours,
      open_slots_count: state.serverInsights.open_slots_count ?? computeDraftInsights().open_slots_count,
      next_open_slot: state.serverInsights.next_open_slot ?? computeDraftInsights().next_open_slot,
      effective_range: state.serverInsights.effective_range ?? computeDraftInsights().effective_range,
    };

    if (activeDaysStat) {
      activeDaysStat.textContent = String(insights.active_days ?? 0);
    }

    if (weeklyHoursStat) {
      weeklyHoursStat.textContent = String(insights.weekly_hours ?? "0 hrs");
    }

    if (projectedSlotsStat) {
      projectedSlotsStat.textContent = String(insights.open_slots_count ?? 0);
    }

    if (nextSlotStat) {
      nextSlotStat.textContent = String(insights.next_open_slot || "No open slots generated yet");
    }

    if (activeBadge) {
      const count = Number(insights.active_days || 0);
      activeBadge.textContent = `${count} active day${count === 1 ? "" : "s"}`;
    }
  }

  function renderDraftState() {
    if (!draftStateEl) {
      return;
    }

    draftStateEl.classList.remove("is-dirty", "is-saving");

    if (state.saving) {
      draftStateEl.textContent = "Saving changes...";
      draftStateEl.classList.add("is-saving");
      return;
    }

    if (state.dirty) {
      draftStateEl.textContent = "Unsaved changes";
      draftStateEl.classList.add("is-dirty");
      return;
    }

    draftStateEl.textContent = "All changes saved";
  }

  function renderRangeNote() {
    const draftInsights = computeDraftInsights();
    const rangeError = getRangeError();

    if (rangeSummary) {
      rangeSummary.textContent = rangeError || draftInsights.effective_range;
    }

    if (rangeNote) {
      rangeNote.classList.toggle("is-error", Boolean(rangeError));
    }
  }

  function renderCalendar() {
    if (!monthGrid) {
      return;
    }

    monthGrid.className = `availability-month-grid is-${state.currentView}-view`;

    if (state.currentView === "day") {
      renderDayCalendar();
      return;
    }

    if (state.currentView === "week") {
      renderWeekCalendar();
      return;
    }

    renderWeekCalendar();
  }

  function renderMonthCalendar() {
    if (!monthGrid) {
      return;
    }

    if (monthLabel) {
      monthLabel.textContent = formatMonthLabel(state.currentMonth);
    }

    if (monthMeta) {
      monthMeta.textContent = "Completed dates are locked. Select a current or future date to edit that date's service-specific slots.";
    }

    monthGrid.innerHTML = buildWeekdayHeader() + buildMonthCells();
  }

  function renderWeekCalendar() {
    const referenceDate = state.selectedDate || findFirstSelectableDate(state.currentMonth, state.today);
    const weekStart = startOfWeek(referenceDate);
    const weekEnd = addDays(weekStart, 6);

    if (monthLabel) {
      monthLabel.textContent = `Week of ${formatDateLong(weekStart)}`;
    }

    if (monthMeta) {
      monthMeta.textContent = `${formatDateLong(weekStart)} to ${formatDateLong(weekEnd)}. Select a current or future day to edit that date's service-specific slots.`;
    }

    monthGrid.innerHTML = buildWeekdayHeader() + buildDateCellsForRange(weekStart, weekEnd);
  }

  function renderDayCalendar() {
    const selectedDate = state.selectedDate || findFirstSelectableDate(state.currentMonth, state.today);
    const dayKey = formatDateKey(selectedDate);
    const day = state.days[dayKey] || { blocks: [], label: selectedDate.toLocaleDateString(undefined, { weekday: "long" }) };
    const locked = isLockedDate(selectedDate);

    if (monthLabel) {
      monthLabel.textContent = formatDateLong(selectedDate);
    }

    if (monthMeta) {
      monthMeta.textContent = "This view shows only the selected date's service-specific slots.";
    }

    monthGrid.innerHTML = renderDateCell(selectedDate, {
      day,
      locked,
      selected: true,
      meta: locked ? "Completed" : day.blocks.length ? summarizeBlocks(day.blocks) : "",
      extraClass: "availability-month-cell--single",
    });
  }

  function renderYearCalendar() {
    const year = state.currentMonth.getFullYear();

    if (monthLabel) {
      monthLabel.textContent = String(year);
    }

    if (monthMeta) {
      monthMeta.textContent = "Pick a month to open its availability calendar.";
    }

    monthGrid.innerHTML = Array.from({ length: 12 }, (_, monthIndex) => {
      const monthDate = new Date(year, monthIndex, 1);
      const activeDays = countActiveDaysForMonth(monthDate);
      const monthSelected = monthIndex === state.currentMonth.getMonth();

      return `
        <button
          type="button"
          class="availability-year-card ${monthSelected ? "is-selected" : ""}"
          data-view-month="${monthIndex}"
        >
          <span class="availability-year-card-title">${escapeHtml(formatMonthLabel(monthDate))}</span>
          <span class="availability-year-card-meta">${activeDays} active day${activeDays === 1 ? "" : "s"}</span>
        </button>
      `;
    }).join("");
  }

  function buildWeekdayHeader() {
    return `
      <div class="availability-month-weekday">Sun</div>
      <div class="availability-month-weekday">Mon</div>
      <div class="availability-month-weekday">Tue</div>
      <div class="availability-month-weekday">Wed</div>
      <div class="availability-month-weekday">Thu</div>
      <div class="availability-month-weekday">Fri</div>
      <div class="availability-month-weekday">Sat</div>
    `;
  }

  function buildMonthCells() {
    const first = new Date(state.currentMonth.getFullYear(), state.currentMonth.getMonth(), 1);
    const start = new Date(first);
    start.setDate(first.getDate() - first.getDay());

    const last = new Date(state.currentMonth.getFullYear(), state.currentMonth.getMonth() + 1, 0);
    const end = new Date(last);
    end.setDate(last.getDate() + (6 - last.getDay()));

    const cells = [];

    for (let cursor = new Date(start); cursor <= end; cursor.setDate(cursor.getDate() + 1)) {
      const cellDate = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate());
      const outsideMonth = cellDate.getMonth() !== state.currentMonth.getMonth();
      cells.push(buildDateCellMarkup(cellDate, { outsideMonth }));
    }

    return cells.join("");
  }

  function buildDateCellsForRange(start, end) {
    const cells = [];

    for (let cursor = new Date(start); cursor <= end; cursor.setDate(cursor.getDate() + 1)) {
      const cellDate = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate());
      cells.push(buildDateCellMarkup(cellDate));
    }

    return cells.join("");
  }

  function buildDateCellMarkup(cellDate, options = {}) {
    const dateKey = formatDateKey(cellDate);
    const day = options.day || state.days[dateKey] || { blocks: [], label: cellDate.toLocaleDateString(undefined, { weekday: "long" }), bookedCount: 0, bookings: [] };
    const locked = options.locked ?? isLockedDate(cellDate);
    const outsideMonth = Boolean(options.outsideMonth);
    const selected = options.selected ?? (state.selectedDate && formatDateKey(cellDate) === formatDateKey(state.selectedDate));
    const preview = options.meta ?? (day.blocks.length ? summarizeBlocks(day.blocks) : locked ? "Completed" : "");

    return renderDateCell(cellDate, {
      day,
      locked,
      outsideMonth,
      selected,
      meta: preview,
      extraClass: options.extraClass || "",
    });
  }

  function renderDateCell(cellDate, options) {
    const hasSlots = options.day.blocks.length > 0;
    const dayStatus = options.locked ? "locked" : hasSlots ? "active" : "inactive";
    const metaHtml = renderDateCellMeta(options.meta, options.day.blocks.length, Number(options.day.bookedCount || 0), options.locked);

    return `
      <button
        type="button"
        class="availability-month-cell is-${dayStatus} ${options.selected ? "is-selected" : ""} ${options.outsideMonth ? "is-outside-month" : ""} ${options.extraClass || ""}"
        data-calendar-date="${escapeAttribute(formatDateKey(cellDate))}"
        data-day-key="${formatDateKey(cellDate)}"
        data-locked="${options.locked ? "true" : "false"}"
        data-outside-month="${options.outsideMonth ? "true" : "false"}"
        ${options.locked || options.outsideMonth ? 'tabindex="-1"' : ""}
      >
        <span class="availability-month-cell-date">${cellDate.getDate()}</span>
        <span class="availability-month-cell-label">${escapeHtml(options.day.label.slice(0, 3))}</span>
        ${metaHtml}
      </button>
    `;
  }

  function renderDateCellMeta(meta, blockCount, bookedCount, locked) {
    if (bookedCount > 0 && blockCount === 0) {
      return `<span class="availability-month-cell-booked">${bookedCount} ${bookedCount === 1 ? "Booked" : "Bookings"}</span>`;
    }

    if (locked && meta) {
      return `<span class="availability-month-cell-meta">${escapeHtml(meta)}</span>`;
    }

    if (blockCount > 0) {
      return `
        <span class="availability-month-cell-badge is-positive">${blockCount} ${blockCount === 1 ? "Slot" : "Slots"}</span>
        <span class="availability-month-cell-booked">${bookedCount} Booked</span>
      `;
    }

    if (!meta) {
      return '<span class="availability-month-cell-badge is-zero">0 Slots</span>';
    }

    return `<span class="availability-month-cell-meta">${escapeHtml(meta)}</span>`;
  }

  function renderDayPanel() {
    if (!dayPanelTitle || !slotList || !emptyState || !dayErrorEl) {
      return;
    }

    const selectedDate = state.selectedDate;
    const dayKey = state.selectedDayKey || (selectedDate ? formatDateKey(selectedDate) : "");
    const day = state.days[dayKey] || (selectedDate ? {
      key: dayKey,
      label: selectedDate.toLocaleDateString(undefined, { weekday: "long" }),
      bookedCount: 0,
      bookings: [],
      blocks: [],
    } : null);

    if (!selectedDate || !day) {
      dayPanelTitle.textContent = "Select a future day";
      dayPanelSubtitle.textContent = "Choose a date from the calendar to edit that date's slots and services.";
      dayPanelStatus.textContent = "No slots";
      slotList.innerHTML = "";
      emptyState.hidden = false;
      emptyState.textContent = "Choose a future day to manage its service-specific time blocks.";
      dayErrorEl.textContent = "";
      return;
    }

    const locked = isLockedDate(selectedDate);
    const hasSlots = day.blocks.length > 0;
    const hasEditableSlots = day.blocks.some((block) => !block.isBooked);
    const canAddAnotherSlot = hasServiceOptions() && hasRemainingTimeWindow(dayKey);
    const formattedDate = selectedDate.toLocaleDateString(undefined, {
      weekday: "long",
      month: "long",
      day: "numeric",
      year: "numeric",
    });

    dayPanelTitle.textContent = `${day.label} availability`;
    dayPanelSubtitle.textContent = `${formattedDate} has its own service-specific slots.`;
    dayPanelStatus.textContent = !hasServiceOptions() ? "Setup needed" : locked ? "Completed" : hasSlots ? "Active" : "No slots";
    dayPanelStatus.className = `availability-day-status ${locked ? "is-locked" : hasSlots ? "is-active" : "is-empty"}`;
    dayPanelNote.textContent = hasServiceOptions()
      ? day.bookedCount > 0
        ? `Booked slots on ${formattedDate} stay locked. You can only edit or remove the unbooked slots for this date.`
        : `Each slot must be assigned to one mentor service and only applies to ${formattedDate}.`
      : "Add an active mentor service first before opening booking availability for this day.";

    addSlotButton.disabled = !canAddAnotherSlot;
    clearDayButton.disabled = !hasEditableSlots;

    const error = getDayError(dayKey);
    dayErrorEl.textContent = error;

    if (!hasSlots) {
      slotList.innerHTML = "";
      emptyState.hidden = false;
      emptyState.textContent = !hasServiceOptions()
        ? "You do not have any active non-office-hours mentor services yet. Add one first to create availability slots."
        : !hasRemainingTimeWindow(dayKey)
        ? `This date cannot accept more slots. Select another day to add availability.`
        : `This date is currently unavailable. Add a slot and choose its service to make ${formattedDate} bookable.`;
      return;
    }

    emptyState.hidden = true;
    slotList.innerHTML = sortBlocks(day.blocks)
      .map((block, index) => renderSlotRow(block, locked, dayKey, index))
      .join("");
  }

  function renderBookingsPanel() {
    if (!bookingsTitle || !bookingsSubtitle || !bookingsCount || !bookingsEmpty || !bookingsList) {
      return;
    }

    const selectedDate = state.selectedDate;
    const dayKey = state.selectedDayKey || (selectedDate ? formatDateKey(selectedDate) : "");
    const day = state.days[dayKey] || null;
    const bookings = Array.isArray(day?.bookings) ? day.bookings : [];
    const formattedDate = selectedDate ? formatDateLong(selectedDate) : "selected date";

    bookingsTitle.textContent = `Bookings for ${formattedDate}`;
    bookingsSubtitle.textContent = "Review who booked this day, the service they chose, and the reserved slot.";
    bookingsCount.textContent = `${bookings.length} ${bookings.length === 1 ? "booked" : "bookings"}`;

    if (bookings.length === 0) {
      bookingsList.innerHTML = "";
      bookingsEmpty.hidden = false;
      bookingsEmpty.textContent = `No bookings for ${formattedDate} yet.`;
      return;
    }

    bookingsEmpty.hidden = true;
    bookingsList.innerHTML = bookings
      .map((booking, index) => `
        <article class="availability-booking-card ${index === 0 ? "is-highlighted" : ""}">
          <div class="availability-booking-card-top">
            <div>
              <div class="availability-booking-card-name">${escapeHtml(booking.booker_name || "Booked user")}</div>
              <div class="availability-booking-card-email">${escapeHtml(booking.booker_email || "No email available")}</div>
            </div>
            <span class="availability-booking-card-status is-${escapeAttribute(String(booking.status || "confirmed"))}">
              ${escapeHtml(formatBookingStatus(booking.status))}
            </span>
          </div>
          <div class="availability-booking-card-grid">
            <div class="availability-booking-card-item">
              <span class="availability-booking-card-label">Service</span>
              <strong>${escapeHtml(booking.service_name || "Service")}</strong>
            </div>
            <div class="availability-booking-card-item">
              <span class="availability-booking-card-label">Meeting Size</span>
              <strong>${escapeHtml(booking.meeting_size_label || "1 on 1")}</strong>
            </div>
            <div class="availability-booking-card-item">
              <span class="availability-booking-card-label">Slot</span>
              <strong>${escapeHtml(booking.slot_label || "Not available")}</strong>
            </div>
            <div class="availability-booking-card-item">
              <span class="availability-booking-card-label">Session</span>
              <strong>${escapeHtml(booking.session_label || formattedDate)}</strong>
            </div>
            <div class="availability-booking-card-item">
              <span class="availability-booking-card-label">Booked On</span>
              <strong>${escapeHtml(booking.booked_at_label || "Not available")}</strong>
            </div>
          </div>
        </article>
      `)
      .join("");
  }

  function renderSlotRow(block, locked, dayKey, index) {
    const isBooked = Boolean(block.isBooked);
    const isPast = isPastTimeBlock(state.selectedDayKey, block);
    const fieldsDisabled = isBooked;
    const removeDisabled = isBooked;
    const badgeLabel = isBooked ? "Booked" : isPast ? "Past" : "";
    const slotError = getSlotError(dayKey, block, index);

    return `
      <div class="availability-day-slot-row ${isBooked ? "is-booked" : ""} ${slotError ? "is-error" : ""}">
        <div class="availability-day-slot-row-strike" aria-hidden="true"></div>
        <div class="availability-day-slot-row-top">
          <div class="availability-day-slot-summary">${escapeHtml(formatTimeRange(block.startTime, block.endTime))} • ${escapeHtml(sessionTypeLabel(normalizeBlockSessionType(block.sessionType, block.serviceConfigId)))}</div>
          ${badgeLabel ? `<span class="availability-day-slot-badge">${escapeHtml(badgeLabel)}</span>` : ""}
        </div>
        <div class="availability-day-slot-controls">
          <label class="availability-day-slot-field availability-day-slot-field--start">
            <span>Start</span>
            <input
              type="time"
              data-slot-field="start"
              data-block-id="${block.id}"
              value="${escapeAttribute(block.startTime)}"
              min="00:00"
              max="23:59"
              step="60"
              ${fieldsDisabled ? "disabled" : ""}
            />
          </label>
          <label class="availability-day-slot-field availability-day-slot-field--end">
            <span>End</span>
            <input
              type="time"
              data-slot-field="end"
              data-block-id="${block.id}"
              value="${escapeAttribute(block.endTime)}"
              min="00:00"
              max="23:59"
              step="60"
              ${fieldsDisabled ? "disabled" : ""}
            />
          </label>
          <label class="availability-day-slot-field availability-day-slot-service">
            <span>Service</span>
            <select data-slot-field="service" data-block-id="${block.id}" ${fieldsDisabled ? "disabled" : ""}>
              ${renderServiceOptions(block.serviceConfigId)}
            </select>
          </label>
          <label class="availability-day-slot-field availability-day-slot-session">
            <span>Meeting Size</span>
            <select data-slot-field="session" data-block-id="${block.id}" ${fieldsDisabled ? "disabled" : ""}>
              ${renderSessionTypeOptions(block.sessionType, block.serviceConfigId)}
            </select>
          </label>
          <button type="button" class="availability-danger-text-btn" data-remove-slot data-block-id="${block.id}" ${removeDisabled ? "disabled" : ""}>
            ${isBooked ? "Locked" : "Remove"}
          </button>
        </div>
        ${slotError ? `<p class="availability-day-slot-error">${escapeHtml(slotError)}</p>` : ""}
      </div>
    `;
  }

  function renderServiceOptions(selectedValue) {
    const placeholder = `<option value="" ${selectedValue ? "" : "selected"} disabled>Select service</option>`;

    return placeholder + state.serviceOptions
      .map((option) => `
        <option value="${escapeAttribute(String(option.value))}" ${Number(option.value) === Number(selectedValue) ? "selected" : ""}>
          ${escapeHtml(option.label)}
        </option>
      `)
      .join("");
  }

  function renderSessionTypeOptions(selectedValue, serviceId) {
    return allowedSessionTypesForService(serviceId)
      .map((type) => `
        <option value="${escapeAttribute(type)}" ${type === normalizeBlockSessionType(selectedValue, serviceId) ? "selected" : ""}>
          ${escapeHtml(sessionTypeLabel(type))}
        </option>
      `)
      .join("");
  }

  function renderSaveButton() {
    if (!saveButton) {
      return;
    }

    saveButton.disabled = state.saving || state.validation.hasErrors || !state.dirty;
    saveButton.textContent = state.saving ? "Saving..." : state.dirty ? "Save Availability" : "Saved";
    saveButton.classList.toggle("is-saving", state.saving);
  }

  function syncFormValues() {
    if (timezoneSelect && timezoneSelect.value !== state.timezone) {
      timezoneSelect.value = state.timezone;
    }

    if (effectiveFromInput && effectiveFromInput.value !== state.effectiveFrom) {
      effectiveFromInput.value = state.effectiveFrom;
    }

    if (effectiveUntilInput && effectiveUntilInput.value !== state.effectiveUntil) {
      effectiveUntilInput.value = state.effectiveUntil;
    }
  }

  function syncOfficeHoursFields() {
    if (officeHoursEnabledInput) {
      officeHoursEnabledInput.checked = state.officeHours.enabled;
    }

    syncOfficeHoursServiceOptions();

    if (officeHoursServiceSelect && officeHoursServiceSelect.value !== String(state.officeHours.serviceConfigId || "")) {
      officeHoursServiceSelect.value = String(state.officeHours.serviceConfigId || "");
    }

    if (officeHoursDaySelect && officeHoursDaySelect.value !== state.officeHours.dayOfWeek) {
      officeHoursDaySelect.value = state.officeHours.dayOfWeek;
    }

    if (officeHoursTimeSelect && officeHoursTimeSelect.value !== state.officeHours.startTime) {
      officeHoursTimeSelect.value = state.officeHours.startTime;
    }

    if (officeHoursFrequencySelect && officeHoursFrequencySelect.value !== state.officeHours.frequency) {
      officeHoursFrequencySelect.value = state.officeHours.frequency;
    }

    [
      officeHoursServiceSelect,
      officeHoursDaySelect,
      officeHoursTimeSelect,
      officeHoursFrequencySelect,
    ].forEach((field) => {
      if (!field) {
        return;
      }

      field.disabled = !state.officeHours.enabled;
    });
  }

  function syncHiddenInputs() {
    if (!hiddenState) {
      return;
    }

    const payload = state.dayOrder
      .map((dayKey) => {
        const slots = serializableBlocks(dayKey).map((block) => ({
          slot_id: block.slotId ?? null,
          start_time: block.startTime,
          end_time: block.endTime,
          service_config_id: block.serviceConfigId ?? null,
          session_type: normalizeBlockSessionType(block.sessionType, block.serviceConfigId),
          is_booked: block.isBooked,
          booking_count: block.bookingCount,
        }));

        return {
          date: dayKey,
          enabled: slots.length > 0,
          slots,
        };
      })
      .filter((day) => day.enabled || day.slots.length > 0);

    hiddenState.innerHTML = `<input type="hidden" name="date_slots_payload" value="${escapeAttribute(JSON.stringify(payload))}" />`;
  }

  async function handleSubmit(event) {
    event.preventDefault();

    state.validation = validateState();
    safeRender();

    if (state.validation.hasErrors) {
      showToast("Please fix the highlighted availability settings before saving.", {
        type: "error",
        title: "Availability issue",
      });
      return;
    }

    if (!state.dirty) {
      showToast("No new changes to save.", {
        type: "info",
        title: "Nothing to save",
      });
      return;
    }

    syncHiddenInputs();
    state.saving = true;
    safeRender();

    try {
      const response = await fetch(state.saveUrl || form.action, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "X-CSRF-TOKEN": csrfToken,
          "X-Requested-With": "XMLHttpRequest",
        },
        body: new FormData(form),
      });

      const responseData = await response.json().catch(() => ({}));

      if (response.status === 422) {
        state.serverErrors = responseData.errors || {};
        state.alert = {
          type: "error",
          title: "Please review the availability form.",
          message: responseData.message || "Please fix the highlighted availability settings before saving.",
          items: flattenErrors(state.serverErrors),
        };
        state.saving = false;
        safeRender();
        showToast("Please fix the highlighted availability settings before saving.", {
          type: "error",
          title: "Availability issue",
        });
        return;
      }

      if (!response.ok) {
        throw new Error(responseData.message || "Unable to save availability right now.");
      }

      if (responseData.scheduler) {
        hydrateFromResponse(responseData.scheduler);
      }

      if (responseData.insights) {
        state.serverInsights = responseData.insights;
      }

      state.alert = null;
      state.saving = false;
      state.serverErrors = {};
      safeRender();
      showToast("Availability saved.", {
        type: "success",
        title: "Availability saved",
      });
    } catch (error) {
      state.saving = false;
      state.alert = {
        type: "error",
        title: "Save failed.",
        message: error instanceof Error ? error.message : "Unable to save availability right now.",
        items: [],
      };
      safeRender();
      showToast("Unable to save availability right now.", {
        type: "error",
        title: "Save failed",
      });
    }
  }

  function hydrateFromResponse(input) {
    const next = createState(input);
    const currentView = state.currentView || next.currentView || "week";
    const selectedDate = state.selectedDate || next.selectedDate;
    const selectedDayKey = state.selectedDayKey || next.selectedDayKey;

    state.saveUrl = next.saveUrl;
    state.timezoneOptions = next.timezoneOptions;
    state.serviceOptions = next.serviceOptions;
    state.timeOptions = next.timeOptions;
    state.windowWeeks = next.windowWeeks;
    state.timezone = next.timezone;
    state.effectiveFrom = next.effectiveFrom;
    state.effectiveUntil = next.effectiveUntil;
    state.dayOrder = next.dayOrder;
    state.days = next.days;
    state.serverInsights = next.serverInsights;
    state.today = next.today;
    state.currentMonth = selectedDate
      ? new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1)
      : next.currentMonth;
    state.currentView = currentView;
    state.selectedDate = selectedDate;
    state.selectedDayKey = selectedDayKey;
    state.officeHours = next.officeHours;
    state.dirty = false;
  }

  function validateState() {
    const dayErrors = {};
    const slotErrors = {};
    const officeHoursErrors = {};
    let hasErrors = false;

    const addSlotError = (dayKey, block, message) => {
      if (!slotErrors[dayKey]) {
        slotErrors[dayKey] = {};
      }

      slotErrors[dayKey][block.id] = message;
      hasErrors = true;
    };

    state.dayOrder.forEach((dayKey) => {
      const day = state.days[dayKey];
      if (!day) {
        return;
      }

      const blocks = sortBlocks(day.blocks);
      const dayLabel = formatDateLong(parseDateInput(dayKey) || state.today);
      let previousEnd = null;

      for (let index = 0; index < blocks.length; index += 1) {
        const block = blocks[index];

        if (!block.startTime || !block.endTime || block.startTime >= block.endTime) {
          addSlotError(dayKey, block, `${dayLabel} blocks must end after they start.`);
          return;
        }

        if (!block.serviceConfigId) {
          addSlotError(dayKey, block, `${dayLabel} blocks must include a service.`);
          return;
        }

        if (!allowedSessionTypesForService(block.serviceConfigId).includes(normalizeBlockSessionType(block.sessionType, block.serviceConfigId))) {
          addSlotError(dayKey, block, `${dayLabel} uses a meeting size that is not available for the selected service.`);
          return;
        }

        if (!block.isBooked && !isUnchangedExistingBlock(block) && !isFutureStartTime(dayKey, block.startTime)) {
          addSlotError(dayKey, block, `${dayLabel} has a new slot that starts in the past. Choose a future start time.`);
          return;
        }

        if (previousEnd !== null && block.startTime < previousEnd) {
          addSlotError(dayKey, block, `${dayLabel}: this time overlaps another slot. Choose a start time after the previous slot ends, or remove one of the slots.`);
          return;
        }

        previousEnd = block.endTime;
      }
    });

    if (state.officeHours.enabled) {
      if (!state.officeHours.serviceConfigId) {
        officeHoursErrors.service_config_id = "Choose the weekly focus service for office hours.";
        hasErrors = true;
      }

      if (!state.officeHours.dayOfWeek) {
        officeHoursErrors.day_of_week = "Choose the recurring day for office hours.";
        hasErrors = true;
      }

      if (!state.officeHours.startTime) {
        officeHoursErrors.start_time = "Choose the recurring start time for office hours.";
        hasErrors = true;
      }

      if (!state.officeHours.frequency) {
        officeHoursErrors.frequency = "Choose weekly office hours.";
        hasErrors = true;
      } else if (state.officeHours.frequency !== "weekly") {
        officeHoursErrors.frequency = "Office hours must repeat weekly.";
        hasErrors = true;
      }
    }

    return {
      dayErrors,
      slotErrors,
      officeHoursErrors,
      rangeError: "",
      hasErrors,
    };
  }

  function handleOfficeHoursChange() {
    state.officeHours.enabled = Boolean(officeHoursEnabledInput?.checked);
    state.officeHours.serviceConfigId = officeHoursServiceSelect?.value ? Number(officeHoursServiceSelect.value) : null;
    state.officeHours.dayOfWeek = String(officeHoursDaySelect?.value || "sun");
    state.officeHours.startTime = String(officeHoursTimeSelect?.value || "");
    state.officeHours.timezone = String(state.timezone || "UTC");
    state.officeHours.frequency = "weekly";
    state.officeHours.preview = buildDraftOfficeHoursPreview();
    markDirty();
    safeRender();
  }

  function buildDraftOfficeHoursPreview() {
    const serviceName = serviceLabelById(state.officeHours.serviceConfigId) || "Office Hours";
    const weekday = weekdayLabel(state.officeHours.dayOfWeek);
    const recurringTime = state.officeHours.enabled
      ? state.officeHours.startTime
        ? `${weekday}, ${formatTimeLabel(state.officeHours.startTime)} ${timezoneShortLabel(state.officeHours.timezone)}`
        : `${weekday} schedule pending`
      : "Schedule coming soon";

    return {
      mentorName: officeHoursMentorName?.textContent || state.officeHours.preview.mentorName || "Mentor",
      mentorMeta: officeHoursMentorMeta?.textContent || state.officeHours.preview.mentorMeta || "Mentor",
      spotsBadge: "0/3 spots filled",
      weeklyService: serviceName,
      recurringTime,
      meetingType: "Small Group Office Hours",
      availabilityText: state.officeHours.enabled ? "No upcoming weekly session generated yet" : "Office hours are currently off",
      note: state.officeHours.enabled
        ? `This week's office hours are currently set as ${serviceName}. Once an upcoming session is generated, spots and service-lock details will appear here.`
        : "Turn on office hours to publish one recurring weekly session for this mentor.",
      serviceLocked: false,
      hasUpcomingSession: false,
    };
  }

  function renderOfficeHoursErrors() {
    renderFieldError(officeHoursServiceError, getOfficeHoursError("service_config_id"));
    renderFieldError(officeHoursDayError, getOfficeHoursError("day_of_week"));
    renderFieldError(officeHoursTimeError, getOfficeHoursError("start_time"));
    renderFieldError(officeHoursFrequencyError, getOfficeHoursError("frequency"));
  }

  function syncOfficeHoursServiceOptions() {
    if (!officeHoursServiceSelect) {
      return;
    }

    const currentValue = officeHoursServiceSelect.value;
    const options = [
      '<option value="">Select service</option>',
      ...state.serviceOptions.map((option) => {
        const value = String(option.value);
        return `<option value="${escapeHtml(value)}">${escapeHtml(option.label)}</option>`;
      }),
    ];

    officeHoursServiceSelect.innerHTML = options.join("");

    if (state.officeHours.serviceConfigId) {
      officeHoursServiceSelect.value = String(state.officeHours.serviceConfigId);
    } else if (currentValue) {
      officeHoursServiceSelect.value = currentValue;
    }
  }

  function renderFieldError(element, message) {
    if (!element) {
      return;
    }

    element.textContent = message || "";
  }

  function getOfficeHoursError(field) {
    if (state.validation.officeHoursErrors?.[field]) {
      return state.validation.officeHoursErrors[field];
    }

    const messages = state.serverErrors[`office_hours.${field}`];
    if (Array.isArray(messages) && messages[0]) {
      return messages[0];
    }

    return "";
  }

  function getDayError(dayKey) {
    if (state.validation.dayErrors[dayKey]) {
      return state.validation.dayErrors[dayKey];
    }

    const slotIndex = state.dayOrder.indexOf(dayKey);
    if (slotIndex === -1) {
      return "";
    }

    for (const [key, messages] of Object.entries(state.serverErrors)) {
      if (!key.startsWith(`date_slots.${slotIndex}`)) {
        continue;
      }

      if (key.includes(".slots.")) {
        continue;
      }

      if (Array.isArray(messages) && messages[0]) {
        return messages[0];
      }
    }

    return "";
  }

  function getSlotError(dayKey, block, blockIndex) {
    const clientError = state.validation.slotErrors?.[dayKey]?.[block.id];
    if (clientError) {
      return clientError;
    }

    const dayIndex = state.dayOrder.indexOf(dayKey);
    if (dayIndex === -1 || !Number.isFinite(blockIndex)) {
      return "";
    }

    const slotPrefix = `date_slots.${dayIndex}.slots.${blockIndex}`;

    for (const [key, messages] of Object.entries(state.serverErrors)) {
      if (!key.startsWith(slotPrefix)) {
        continue;
      }

      if (Array.isArray(messages) && messages[0]) {
        return messages[0];
      }
    }

    return "";
  }

  function getRangeError() {
    if (state.validation.rangeError) {
      return state.validation.rangeError;
    }

    for (const field of ["effective_from", "effective_until"]) {
      const messages = state.serverErrors[field];
      if (Array.isArray(messages) && messages[0]) {
        return messages[0];
      }
    }

    return "";
  }

  function computeDraftInsights() {
    const activeDays = state.dayOrder.filter((dayKey) => state.days[dayKey].blocks.length > 0).length;
    let weeklyMinutes = 0;
    let projectedSlots = 0;
    let nextOpenSlot = "No open slots generated yet";

    state.dayOrder.forEach((dayKey) => {
      if (getDayError(dayKey)) {
        return;
      }

      serializableBlocks(dayKey).forEach((block) => {
        weeklyMinutes += diffMinutes(block.startTime, block.endTime);
      });
    });

    state.dayOrder.forEach((dayKey) => {
      const date = parseDateInput(dayKey);
      const day = state.days[dayKey];

      if (!date || !day || getDayError(dayKey) || isLockedDate(date)) {
        return;
      }

      serializableBlocks(dayKey).forEach((block) => {
        if (!block.startTime || !block.endTime || block.startTime >= block.endTime) {
          return;
        }

        projectedSlots += diffMinutes(block.startTime, block.endTime) / 60;

        if (nextOpenSlot === "No open slots generated yet") {
          nextOpenSlot = `${formatDateLong(date)} at ${formatTimeLabel(block.startTime)}`;
        }
      });
    });

    return {
      active_days: activeDays,
      weekly_hours: formatHoursLabel(weeklyMinutes),
      open_slots_count: projectedSlots,
      next_open_slot: nextOpenSlot,
      effective_range: "Dates stay active until you remove them",
    };
  }

  function markDirty() {
    state.dirty = true;
    state.serverErrors = {};
    state.alert = null;
  }

  function weekdayLabel(value) {
    return {
      sun: "Sunday",
      mon: "Monday",
      tue: "Tuesday",
      wed: "Wednesday",
      thu: "Thursday",
      fri: "Friday",
      sat: "Saturday",
    }[value] || "Sunday";
  }

  function timezoneShortLabel(value) {
    return value === "America/New_York"
      ? "ET"
      : value === "America/Chicago"
      ? "CT"
      : value === "America/Denver"
      ? "MT"
      : value === "America/Los_Angeles"
      ? "PT"
      : value === "Europe/London"
      ? "BST"
      : value === "UTC"
      ? "UTC"
      : value === "Asia/Karachi"
      ? "PKT"
      : value;
  }

  function serviceLabelById(serviceId) {
    const match = state.serviceOptions.find((option) => Number(option.value) === Number(serviceId));

    return match ? String(match.label || "") : "";
  }

  function serviceLabelFromOptions(options, serviceId) {
    const match = (Array.isArray(options) ? options : [])
      .find((option) => Number(option?.value) === Number(serviceId));

    return match ? String(match.label || "") : "";
  }

  function normalizeServiceOptions(options) {
    return (Array.isArray(options) ? options : [])
      .map((option) => ({
        value: Number(option?.value),
        label: String(option?.label || "Service"),
        duration_minutes: Math.max(Number(option?.duration_minutes || option?.durationMinutes || 1), 1),
        allowed_sizes: normalizeAllowedSessionTypes(option?.allowed_sizes || option?.allowedSizes),
      }))
      .filter((option) => Number.isFinite(option.value) && option.value > 0);
  }

  function serviceOptionsFromInputs() {
    return serviceInputs
      .filter((input) => input.checked && input.dataset.serviceOfficeHours !== "true")
      .map((input) => ({
        value: Number(input.value),
        label: String(input.dataset.serviceLabel || "Service"),
        duration_minutes: Math.max(Number(input.dataset.serviceDuration || 1), 1),
        allowed_sizes: parseAllowedSessionTypes(input.dataset.serviceAllowedSizes),
      }));
  }

  function parseAllowedSessionTypes(value) {
    try {
      return normalizeAllowedSessionTypes(JSON.parse(String(value || "[]")));
    } catch (error) {
      return ["1on1"];
    }
  }

  function normalizeAllowedSessionTypes(value) {
    const allowed = Array.isArray(value)
      ? value.map((type) => String(type)).filter((type) => ["1on1", "1on3", "1on5"].includes(type))
      : [];

    return allowed.length > 0 ? allowed : ["1on1"];
  }

  function serviceDurationById(serviceId) {
    const match = state.serviceOptions.find((option) => Number(option.value) === Number(serviceId));
    const duration = Number(match?.duration_minutes || match?.durationMinutes || 0);

    return Number.isFinite(duration) && duration > 0 ? duration : 60;
  }

  function allowedSessionTypesForService(serviceId, options = null) {
    const optionSource = Array.isArray(options) ? normalizeServiceOptions(options) : state.serviceOptions;
    const match = optionSource.find((option) => Number(option.value) === Number(serviceId));
    const allowed = Array.isArray(match?.allowed_sizes)
      ? match.allowed_sizes
      : Array.isArray(match?.allowedSizes)
      ? match.allowedSizes
      : ["1on1"];

    const normalized = allowed
      .map((type) => String(type))
      .filter((type) => ["1on1", "1on3", "1on5"].includes(type));

    return normalized.length > 0 ? normalized : ["1on1"];
  }

  function defaultSessionTypeForService(serviceId, options = null) {
    const allowed = allowedSessionTypesForService(serviceId, options);

    return allowed.includes("1on1") ? "1on1" : allowed[0] || "1on1";
  }

  function normalizeBlockSessionType(sessionType, serviceId, options = null) {
    const value = ["1on1", "1on3", "1on5"].includes(String(sessionType)) ? String(sessionType) : "1on1";
    const allowed = allowedSessionTypesForService(serviceId, options);

    return allowed.includes(value) ? value : defaultSessionTypeForService(serviceId, options);
  }

  function sessionTypeLabel(sessionType) {
    return {
      "1on1": "1 on 1",
      "1on3": "1 on 3",
      "1on5": "1 on 5",
    }[sessionType] || "1 on 1";
  }

  function endTimeForService(startTime, serviceId) {
    if (!startTime || !serviceId) {
      return "";
    }

    return addMinutes(startTime, serviceDurationById(serviceId));
  }

  function addMinutes(value, minutesToAdd) {
    const [hourRaw, minuteRaw] = String(value || "").split(":").map(Number);
    const hour = Number.isFinite(hourRaw) ? hourRaw : 0;
    const minute = Number.isFinite(minuteRaw) ? minuteRaw : 0;
    const total = (hour * 60) + minute + Number(minutesToAdd || 0);

    if (total >= 24 * 60) {
      return "";
    }

    return `${String(Math.floor(total / 60)).padStart(2, "0")}:${String(total % 60).padStart(2, "0")}`;
  }

  function addSuggestedBlock(dayKey) {
    if (!hasServiceOptions()) {
      return;
    }

    if (!state.days[dayKey]) {
      const date = parseDateInput(dayKey);
      state.days[dayKey] = {
        key: dayKey,
        label: date ? date.toLocaleDateString(undefined, { weekday: "long" }) : dayKey,
        bookedCount: 0,
        bookings: [],
        blocks: [],
      };
      if (!state.dayOrder.includes(dayKey)) {
        state.dayOrder.push(dayKey);
        state.dayOrder.sort();
      }
    }

    const day = state.days[dayKey];

    const serviceConfigId = defaultServiceConfigId();
    const start = findAvailableStartTime(dayKey, serviceConfigId);
    const end = endTimeForService(start, serviceConfigId);

    if (!start || start >= "24:00" || start === "23:30" || !end || end <= start) {
      return;
    }

    day.blocks.push({
      id: createBlockId(),
      slotId: null,
      startTime: clampTime(start),
      endTime: normalizeExactTime(end),
      serviceConfigId,
      sessionType: defaultSessionTypeForService(serviceConfigId),
      originalStartTime: null,
      originalEndTime: null,
      originalServiceConfigId: null,
      originalSessionType: null,
      isBooked: false,
      bookingCount: 0,
    });

    day.blocks = sortBlocks(day.blocks);
    markDirty();
  }

  function findAvailableStartTime(dayKey, serviceConfigId) {
    const duration = serviceDurationById(serviceConfigId);
    const earliestStart = defaultStartTimeForDay(dayKey);
    const blocks = sortBlocks(state.days[dayKey]?.blocks || []);
    let cursor = timeToMinutes(earliestStart);

    if (!Number.isFinite(cursor)) {
      return "";
    }

    for (const block of blocks) {
      const blockStart = timeToMinutes(block.startTime);
      const blockEnd = timeToMinutes(block.endTime);

      if (!Number.isFinite(blockStart) || !Number.isFinite(blockEnd)) {
        continue;
      }

      if (cursor + duration <= blockStart) {
        return minutesToTime(cursor);
      }

      cursor = Math.max(cursor, blockEnd);
    }

    return cursor + duration <= 24 * 60 ? minutesToTime(cursor) : "";
  }

  function deleteBlock(dayKey, blockId) {
    const day = state.days[dayKey];
    if (!day) {
      return;
    }

    day.blocks = day.blocks.filter((block) => block.id !== blockId);
    markDirty();
  }

  function clearDay(dayKey) {
    const day = state.days[dayKey];
    if (!day) {
      return;
    }

    day.blocks = day.blocks.filter((block) => block.isBooked);
    markDirty();
  }

  function getBlock(dayKey, blockId) {
    return state.days[dayKey]?.blocks.find((block) => block.id === blockId) || null;
  }

  function syncSelectedDateToCurrentMonth() {
    const firstSelectable = findFirstSelectableDate(state.currentMonth, state.today);
    if (firstSelectable) {
      state.selectedDate = firstSelectable;
      state.selectedDayKey = formatDateKey(firstSelectable);
    }
  }

  function findFirstSelectableDate(monthDate, today) {
    const year = monthDate.getFullYear();
    const month = monthDate.getMonth();
    const startDay = new Date(year, month, 1);
    const endDay = new Date(year, month + 1, 0);

    for (let cursor = new Date(startDay); cursor <= endDay; cursor.setDate(cursor.getDate() + 1)) {
      if (!isLockedDate(cursor, today)) {
        return new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate());
      }
    }

    return new Date(today.getFullYear(), today.getMonth(), today.getDate());
  }

  function shiftView(step) {
    if (state.currentView === "day") {
      state.selectedDate = addDays(state.selectedDate || state.today, step);
      state.selectedDayKey = formatDateKey(state.selectedDate);
      state.currentMonth = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1);
      return;
    }

    if (state.currentView === "week") {
      state.selectedDate = addDays(state.selectedDate || state.today, step * 7);
      state.selectedDayKey = formatDateKey(state.selectedDate);
      state.currentMonth = new Date(state.selectedDate.getFullYear(), state.selectedDate.getMonth(), 1);
      return;
    }

    if (state.currentView === "year") {
      state.currentMonth = new Date(state.currentMonth.getFullYear() + step, state.currentMonth.getMonth(), 1);
      syncSelectedDateToCurrentMonth();
      return;
    }

    state.currentMonth = new Date(state.currentMonth.getFullYear(), state.currentMonth.getMonth() + step, 1);
    syncSelectedDateToCurrentMonth();
  }

  function isLockedDate(date, referenceToday = state?.today) {
    if (!referenceToday) {
      return false;
    }

    return startOfDay(date) < startOfDay(referenceToday);
  }

  function isPastTimeBlock(dayKey, block) {
    if (!dayKey || !block) {
      return false;
    }

    const nowParts = currentTimePartsInTimezone(state.timezone);
    if (!nowParts) {
      return false;
    }

    return dayKey < nowParts.dateKey;
  }

  function isUnchangedExistingBlock(block) {
    return Boolean(block?.slotId)
      && block.startTime === block.originalStartTime
      && block.endTime === block.originalEndTime
      && Number(block.serviceConfigId || 0) === Number(block.originalServiceConfigId || 0)
      && normalizeBlockSessionType(block.sessionType, block.serviceConfigId) === normalizeBlockSessionType(block.originalSessionType, block.originalServiceConfigId);
  }

  function isFutureStartTime(dayKey, startTime) {
    if (!dayKey || !startTime) {
      return false;
    }

    const nowParts = currentTimePartsInTimezone(state.timezone);
    if (!nowParts) {
      return true;
    }

    if (dayKey > nowParts.dateKey) {
      return true;
    }

    if (dayKey < nowParts.dateKey) {
      return false;
    }

    return startTime > nowParts.time;
  }

  function hasRemainingTimeWindow(dayKey) {
    const nextStart = defaultStartTimeForDay(dayKey);
    return Boolean(nextStart && nextStart < "23:30");
  }

  function serializableBlocks(dayKey) {
    return sortBlocks(state.days[dayKey]?.blocks || [])
      .filter((block) => block.isBooked || !isPastTimeBlock(dayKey, block));
  }

  function defaultStartTimeForDay(dayKey) {
    const nowParts = currentTimePartsInTimezone(state.timezone);
    if (!nowParts) {
      return "00:00";
    }

    if (dayKey && dayKey > nowParts.dateKey) {
      return "00:00";
    }

    if (dayKey && dayKey < nowParts.dateKey) {
      return "";
    }

    return roundUpToNextHalfHour(nowParts.time);
  }

  function currentTimePartsInTimezone(timezone) {
    try {
      const formatter = new Intl.DateTimeFormat("en-CA", {
        timeZone: timezone || "UTC",
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });

      const parts = formatter.formatToParts(new Date());
      const values = Object.fromEntries(parts.filter((part) => part.type !== "literal").map((part) => [part.type, part.value]));

      return {
        dateKey: `${values.year}-${values.month}-${values.day}`,
        time: `${values.hour}:${values.minute}`,
      };
    } catch (error) {
      return null;
    }
  }

  function roundUpToNextHalfHour(value) {
    const [hourRaw, minuteRaw] = String(value || "00:00").split(":").map(Number);
    let hour = Number.isFinite(hourRaw) ? hourRaw : 0;
    let minute = Number.isFinite(minuteRaw) ? minuteRaw : 0;

    if (minute === 0 || minute === 30) {
      minute += 30;
    } else if (minute < 30) {
      minute = 30;
    } else {
      minute = 0;
      hour += 1;
    }

    if (minute >= 60) {
      minute = 0;
      hour += 1;
    }

    return `${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`;
  }

  function normalizeBlock(slot, serviceOptions = null) {
    blockSequence += 1;
    const startTime = String(slot?.start_time || "");
    const endTime = String(slot?.end_time || "");
    const serviceConfigId = slot?.service_config_id ? Number(slot.service_config_id) : null;
    const sessionType = normalizeBlockSessionType(String(slot?.session_type || "1on1"), serviceConfigId, serviceOptions);

    return {
      id: `availability-block-${blockSequence}`,
      slotId: slot?.slot_id ? Number(slot.slot_id) : null,
      startTime,
      endTime,
      serviceConfigId,
      sessionType,
      originalStartTime: slot?.slot_id ? startTime : null,
      originalEndTime: slot?.slot_id ? endTime : null,
      originalServiceConfigId: slot?.slot_id ? serviceConfigId : null,
      originalSessionType: slot?.slot_id ? sessionType : null,
      isBooked: Boolean(slot?.is_booked),
      bookingCount: Number(slot?.booking_count || 0),
    };
  }

  function sortBlocks(blocks) {
    return [...blocks].sort((left, right) => left.startTime.localeCompare(right.startTime));
  }

  function createBlockId() {
    blockSequence += 1;
    return `availability-block-${blockSequence}`;
  }

  function renderTimeLabel(value) {
    const option = state.timeOptions.find((item) => item.value === value);
    return option ? option.label : value;
  }

  function formatTimeRange(start, end) {
    return `${formatTimeLabel(start)} - ${formatTimeLabel(end)}`;
  }

  function formatTimeLabel(value) {
    const [hourRaw, minuteRaw] = String(value || "00:00").split(":").map(Number);
    const hour = Number.isFinite(hourRaw) ? hourRaw : 0;
    const minute = Number.isFinite(minuteRaw) ? minuteRaw : 0;
    const suffix = hour >= 12 ? "PM" : "AM";
    const displayHour = hour % 12 || 12;

    return `${displayHour}:${String(minute).padStart(2, "0")} ${suffix}`;
  }

  function formatMonthLabel(date) {
    return date.toLocaleDateString(undefined, {
      month: "long",
      year: "numeric",
    });
  }

  function summarizeBlocks(blocks) {
    return sortBlocks(blocks)
      .slice(0, 2)
      .map((block) => formatTimeLabel(block.startTime))
      .join(" • ");
  }

  function formatHoursLabel(minutes) {
    if (!minutes) {
      return "0 hrs";
    }

    const hours = minutes / 60;
    return Number.isInteger(hours) ? `${hours} hrs` : `${hours.toFixed(1)} hrs`;
  }

  function formatEffectiveRange(from, until) {
    if (!from && !until) {
      return "Ongoing each week until you change it";
    }

    if (from && until) {
      return `Applies from ${from} to ${until}`;
    }

    if (from) {
      return `Applies from ${from} onward`;
    }

    return `Applies until ${until}`;
  }

  function formatDateLong(date) {
    return date.toLocaleDateString(undefined, {
      weekday: "long",
      month: "long",
      day: "numeric",
      year: "numeric",
    });
  }

  function formatBookingStatus(value) {
    const status = String(value || "confirmed");

    if (status === "pending") {
      return "Pending";
    }

    if (status === "cancelled") {
      return "Cancelled";
    }

    return "Confirmed";
  }

  function hasServiceOptions() {
    return state.serviceOptions.length > 0;
  }

  function defaultServiceConfigId() {
    return hasServiceOptions() ? Number(state.serviceOptions[0].value) : null;
  }

  function countActiveDaysForMonth(monthDate) {
    const first = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
    const last = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0);
    const activeDayKeys = new Set();

    for (let cursor = new Date(first); cursor <= last; cursor.setDate(cursor.getDate() + 1)) {
      const dayKey = formatDateKey(cursor);
      if ((state.days[dayKey]?.blocks || []).length > 0) {
        activeDayKeys.add(dayKey);
      }
    }

    return activeDayKeys.size;
  }

  function diffMinutes(start, end) {
    return Math.max(timeToMinutes(end) - timeToMinutes(start), 0);
  }

  function timeToMinutes(value) {
    const [hourRaw, minuteRaw] = String(value || "").split(":").map(Number);
    const hour = Number.isFinite(hourRaw) ? hourRaw : NaN;
    const minute = Number.isFinite(minuteRaw) ? minuteRaw : NaN;

    if (!Number.isFinite(hour) || !Number.isFinite(minute)) {
      return NaN;
    }

    return (hour * 60) + minute;
  }

  function minutesToTime(value) {
    const minutes = Math.max(0, Math.min(Number(value) || 0, 24 * 60));
    const hour = Math.floor(minutes / 60);
    const minute = minutes % 60;

    return `${String(hour).padStart(2, "0")}:${String(minute).padStart(2, "0")}`;
  }

  function addHour(value) {
    const [hourRaw, minuteRaw] = value.split(":").map(Number);
    const total = (hourRaw * 60) + minuteRaw + 60;
    const hours = Math.min(Math.floor(total / 60), 23);
    const minutes = total % 60;
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
  }

  function maxTimeValue(left, right) {
    const leftValue = String(left || "");
    const rightValue = String(right || "");

    if (!leftValue) {
      return rightValue;
    }

    if (!rightValue) {
      return leftValue;
    }

    return leftValue >= rightValue ? leftValue : rightValue;
  }

  function clampTime(value) {
    const [hourRaw, minuteRaw] = String(value).split(":").map(Number);
    const hours = Math.max(0, Math.min(Number.isFinite(hourRaw) ? hourRaw : 0, 23));
    const minutes = minuteRaw >= 30 ? 30 : 0;
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
  }

  function normalizeExactTime(value) {
    const [hourRaw, minuteRaw] = String(value).split(":").map(Number);
    const hours = Math.max(0, Math.min(Number.isFinite(hourRaw) ? hourRaw : 0, 23));
    const minutes = Math.max(0, Math.min(Number.isFinite(minuteRaw) ? minuteRaw : 0, 59));

    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
  }

  function normalizeTimeFieldValue(nextValue, fallbackValue = "") {
    if (!nextValue) {
      return fallbackValue;
    }

    return normalizeExactTime(nextValue);
  }

  function startOfDay(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  }

  function startOfWeek(date) {
    const next = startOfDay(date);
    next.setDate(next.getDate() - next.getDay());
    return next;
  }

  function addDays(date, amount) {
    const next = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    next.setDate(next.getDate() + amount);
    return next;
  }

  function maxDate(left, right) {
    return left > right ? left : right;
  }

  function minDate(left, right) {
    return left < right ? left : right;
  }

  function dateToDayKey(date) {
    return ["sun", "mon", "tue", "wed", "thu", "fri", "sat"][date.getDay()];
  }

  function parseDateInput(value) {
    if (!value) {
      return null;
    }

    const [year, month, day] = String(value).split("-").map(Number);
    if (!year || !month || !day) {
      return null;
    }

    return new Date(year, month - 1, day);
  }

  function formatDateKey(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
  }

  function flattenErrors(errors) {
    return Object.values(errors)
      .flatMap((messages) => Array.isArray(messages) ? messages : [])
      .filter(Boolean);
  }

  function showToast(message, options = {}) {
    if (window.AppToast?.show) {
      window.AppToast.show({
        type: options.type || "info",
        title: options.title || undefined,
        message,
      });
      return;
    }

    if (!toastEl) {
      return;
    }

    toastEl.textContent = message;
    toastEl.classList.add("is-visible");

    if (toastTimer) {
      clearTimeout(toastTimer);
    }

    toastTimer = window.setTimeout(() => {
      toastEl.classList.remove("is-visible");
    }, 2800);
  }

  function escapeHtml(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function escapeAttribute(value) {
    return escapeHtml(value);
  }
})();
