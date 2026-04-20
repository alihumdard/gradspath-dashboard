const body = document.body;
const themeToggle = document.getElementById("themeToggle");
const bookingPageDataEl = document.getElementById("bookingPageData");
const bookingPageData = bookingPageDataEl
  ? JSON.parse(bookingPageDataEl.textContent)
  : null;

(function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  body.setAttribute("data-theme", savedTheme);
})();

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = body.getAttribute("data-theme") || "light";
    const nextTheme = currentTheme === "light" ? "dark" : "light";
    body.setAttribute("data-theme", nextTheme);
    localStorage.setItem("theme", nextTheme);
  });
}

const mentorData = bookingPageData?.mentor || {};
const studentData = bookingPageData?.student || {};
const services = Array.isArray(bookingPageData?.services)
  ? bookingPageData.services
  : [];
const officeHoursData = bookingPageData?.officeHours || null;
const availabilityRoutes = bookingPageData?.availabilityRoutes || {};
const bookingCheckoutUrl = bookingPageData?.bookingCheckoutUrl || "";
const creditBalanceUrl = bookingPageData?.creditBalanceUrl || "";

const state = {
  selectedServiceId: bookingPageData?.selectedServiceId || services[0]?.id || null,
  meetingSize: 1,
  selectedMonthIndex: 0,
  availableMonths: [],
  availableDays: [],
  availableTimes: [],
  selectedDate: null,
  selectedTime: null,
  selectedSlotId: null,
};

const serviceGrid = document.getElementById("serviceGrid");
const meetingSizeGrid = document.getElementById("meetingSizeGrid");
const meetingSection = document.getElementById("meetingSection");
const meetingHelperText = document.getElementById("meetingHelperText");
const specialRequestNote = document.getElementById("specialRequestNote");
const groupFields = document.getElementById("groupFields");
const groupFormGrid = document.getElementById("groupFormGrid");
const creditNote = document.getElementById("creditNote");
const selectionCard = document.getElementById("selectionCard");

const officeHoursPanel = document.getElementById("officeHoursPanel");
const officeHoursMentorName = document.getElementById("officeHoursMentorName");
const officeHoursMentorMeta = document.getElementById("officeHoursMentorMeta");
const officeHoursCapacityPill = document.getElementById("officeHoursCapacityPill");
const officeHoursWeeklyService = document.getElementById("officeHoursWeeklyService");
const officeHoursRecurringTime = document.getElementById("officeHoursRecurringTime");
const officeHoursMeetingType = document.getElementById("officeHoursMeetingType");
const officeHoursAvailability = document.getElementById("officeHoursAvailability");
const officeHoursNote = document.getElementById("officeHoursNote");

const calendarGrid = document.getElementById("calendarGrid");
const timeGrid = document.getElementById("timeGrid");
const selectedDateLabel = document.getElementById("selectedDateLabel");
const monthDisplay = document.getElementById("monthDisplay");
const prevMonthBtn = document.getElementById("prevMonthBtn");
const nextMonthBtn = document.getElementById("nextMonthBtn");

const mentorInitials = document.getElementById("mentorInitials");
const mentorDisplayName = document.getElementById("mentorDisplayName");
const mentorDisplayMeta = document.getElementById("mentorDisplayMeta");
const mentorDisplayRating = document.getElementById("mentorDisplayRating");
const mentorDescription = document.getElementById("mentorDescription");
const summaryMentor = document.getElementById("summaryMentor");
const summaryService = document.getElementById("summaryService");
const summaryDuration = document.getElementById("summaryDuration");
const summaryMeetingSize = document.getElementById("summaryMeetingSize");
const summaryPrice = document.getElementById("summaryPrice");
const summaryDate = document.getElementById("summaryDate");
const summaryTime = document.getElementById("summaryTime");
const continueBtn = document.getElementById("continueBtn");

const bookingForm = document.getElementById("bookingForm");
const formServiceConfigId = document.getElementById("formServiceConfigId");
const formSessionType = document.getElementById("formSessionType");
const formSlotId = document.getElementById("formSlotId");
const formOfficeHourSessionId = document.getElementById("formOfficeHourSessionId");
const csrfToken =
  bookingForm?.querySelector('input[name="_token"]')?.value || "";

const creditModal = document.getElementById("creditModal");
const closeCreditModal = document.getElementById("closeCreditModal");
const officeHoursModalPanel = creditModal?.querySelector(
  ".office-hours-modal-panel",
);
const storeModal = document.getElementById("storeModal");
const closeStoreModal = document.getElementById("closeStoreModal");
const storeModalPanel = storeModal?.querySelector(".store-modal-panel-inner");

function getServiceById(id) {
  return services.find((service) => service.id === id);
}

function currentPriceAmount() {
  const service = getServiceById(state.selectedServiceId);
  const priceInfo = service?.prices?.[state.meetingSize] || service?.prices?.[1];

  return Number(priceInfo?.amount || 0);
}

function isPaidService() {
  const service = getServiceById(state.selectedServiceId);

  return Boolean(service && !service.isOfficeHours && currentPriceAmount() > 0);
}

function isFreeService() {
  const service = getServiceById(state.selectedServiceId);

  return Boolean(service && !service.isOfficeHours && currentPriceAmount() <= 0);
}

function currentSessionType() {
  const service = getServiceById(state.selectedServiceId);
  if (!service) return "1on1";
  if (service.isOfficeHours) return "office_hours";
  if (state.meetingSize === 3) return "1on3";
  if (state.meetingSize === 5) return "1on5";
  return "1on1";
}

function formatLongDate(dateString) {
  const date = new Date(`${dateString}T12:00:00`);
  return date.toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
  });
}

function getServiceIcon(serviceId) {
  const icons = {
    "free-consultation": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 13.5 11 16l5-6M7 5h10M7 9h10M6 3h12a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    tutoring: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M6 7h8l4 4v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Zm8 0v4h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    "program-insights": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="m4 10 8-4 8 4-8 4-8-4Zm3 2.5v3L12 18l5-2.5v-3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    program_insights: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="m4 10 8-4 8 4-8 4-8-4Zm3 2.5v3L12 18l5-2.5v-3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    "interview-prep": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 11V8a4 4 0 1 1 8 0v3m-9 0h10a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    interview_prep: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 11V8a4 4 0 1 1 8 0v3m-9 0h10a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    "application-review": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 7h8M8 11h8M8 15h5M7 4h7l4 4v12H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    application_review: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 7h8M8 11h8M8 15h5M7 4h7l4 4v12H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    "gap-year-planning": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-6-9h12M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    gap_year_planning: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-6-9h12M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    "office-hours": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3h10M8 6h8m-9 3h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Zm3 5 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    office_hours: `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3h10M8 6h8m-9 3h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Zm3 5 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
  };

  return icons[serviceId] || icons.tutoring;
}

function hydrateMentorDetails() {
  if (mentorInitials) mentorInitials.textContent = mentorData.initials || "M";
  if (mentorDisplayName) mentorDisplayName.textContent = mentorData.name || "Mentor";
  if (mentorDisplayMeta) mentorDisplayMeta.textContent = mentorData.meta || "Mentor";
  if (mentorDisplayRating) mentorDisplayRating.textContent = mentorData.rating || "New";
  if (mentorDescription) {
    mentorDescription.textContent =
      mentorData.description || "Mentor description coming soon.";
  }
  if (summaryMentor) summaryMentor.textContent = mentorData.name || "Mentor";
}

function getAccentClass(index) {
  return index % 2 === 0 ? "accent-purple" : "accent-pink";
}

function ensureSelectedMeetingSize(service) {
  if (!service) {
    state.meetingSize = 1;
    return;
  }

  if (service.isOfficeHours) {
    state.meetingSize = 1;
    return;
  }

  if (!service.allowedSizes.includes(state.meetingSize)) {
    state.meetingSize = service.defaultSize || service.allowedSizes[0] || 1;
  }
}

async function fetchAvailability(url, params) {
  const query = new URLSearchParams(params);
  const response = await fetch(`${url}?${query.toString()}`, {
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    credentials: "same-origin",
  });

  if (!response.ok) {
    throw new Error("Unable to load availability.");
  }

  return response.json();
}

function currentAvailabilityParams() {
  const service = getServiceById(state.selectedServiceId);

  return {
    mentor_id: mentorData.id,
    service_config_id: service?.serviceConfigId || "",
    session_type: currentSessionType(),
  };
}

async function loadMonths() {
  const service = getServiceById(state.selectedServiceId);
  if (!service || service.isOfficeHours) {
    state.availableMonths = [];
    renderMonthNavigation();
    return;
  }

  const result = await fetchAvailability(
    availabilityRoutes.months,
    currentAvailabilityParams(),
  );

  state.availableMonths = result.months || [];
  state.selectedMonthIndex = 0;
  state.availableDays = [];
  state.availableTimes = [];
  state.selectedDate = null;
  state.selectedTime = null;
  state.selectedSlotId = null;

  renderMonthNavigation();

  if (state.availableMonths.length > 0) {
    await loadDays();
  } else {
    renderCalendar();
    renderTimes();
    updateSummary();
    updateContinue();
  }
}

async function loadDays() {
  const currentMonth = state.availableMonths[state.selectedMonthIndex];
  if (!currentMonth) {
    state.availableDays = [];
    renderCalendar();
    renderTimes();
    return;
  }

  const result = await fetchAvailability(availabilityRoutes.days, {
    ...currentAvailabilityParams(),
    month: currentMonth.month,
  });

  state.availableDays = result.days || [];
  state.availableTimes = [];
  state.selectedDate = null;
  state.selectedTime = null;
  state.selectedSlotId = null;

  renderMonthNavigation();
  renderCalendar();
  renderTimes();
  updateSummary();
  updateContinue();
}

async function loadTimes(date) {
  const result = await fetchAvailability(availabilityRoutes.times, {
    ...currentAvailabilityParams(),
    date,
  });

  state.availableTimes = result.times || [];
  state.selectedDate = date;
  state.selectedTime = null;
  state.selectedSlotId = null;

  renderCalendar();
  renderTimes();
  updateSummary();
  updateContinue();
}

function renderServices() {
  serviceGrid.innerHTML = "";

  services.forEach((service, index) => {
    const isActive = service.id === state.selectedServiceId;
    const defaultPrice = service.prices?.[service.defaultSize]?.label || "";
    const accentClass = getAccentClass(index);

    const card = document.createElement("button");
    card.type = "button";
    card.className = `service-card ${accentClass} ${isActive ? "active" : ""}`;
    card.innerHTML = `
      <div class="service-top">
        <div class="service-icon">
          ${getServiceIcon(service.id)}
        </div>
        <div class="service-heading">
          <h4>${service.name}</h4>
          <div class="service-subprice">${defaultPrice}</div>
        </div>
      </div>
      <p class="service-desc">${service.desc}</p>
      <div class="service-bottom">
        <span class="service-duration">${service.duration}</span>
        <span class="service-price">${service.isOfficeHours ? "1 credit" : defaultPrice}</span>
      </div>
    `;

    card.addEventListener("click", async () => {
      state.selectedServiceId = service.id;
      state.meetingSize = service.defaultSize || 1;
      ensureSelectedMeetingSize(service);
      renderServices();
      renderMeetingSizes();
      renderGroupFields();
      renderOfficeHoursPanel();
      await loadMonths();
      updateSummary();
      updateContinue();
    });

    serviceGrid.appendChild(card);
  });
}

function renderMeetingSizes() {
  const service = getServiceById(state.selectedServiceId);
  meetingSizeGrid.innerHTML = "";

  if (!service) return;

  ensureSelectedMeetingSize(service);

  if (service.isOfficeHours) {
    meetingSection.hidden = true;
    creditNote.hidden = false;
    specialRequestNote.hidden = true;
    groupFields.hidden = true;
    renderOfficeHoursPanel();
    return;
  }

  meetingSection.hidden = false;
  creditNote.hidden = true;
  meetingHelperText.textContent =
    "Choose whether this is an individual booking or a small group request.";

  service.allowedSizes.forEach((size) => {
    const priceInfo = service.prices[size];
    const card = document.createElement("button");
    card.type = "button";
    card.className = `meeting-size-card ${state.meetingSize === size ? "active" : ""}`;
    const subtext =
      size === 1 ? priceInfo.label : `Special request - ${priceInfo.label}`;

    card.innerHTML = `
      <strong>1 on ${size}</strong>
      <span>${subtext}</span>
    `;

    card.addEventListener("click", async () => {
      state.meetingSize = size;
      renderMeetingSizes();
      renderGroupFields();
      await loadMonths();
      updateSummary();
    });

    meetingSizeGrid.appendChild(card);
  });

  specialRequestNote.hidden = !(state.meetingSize === 3 || state.meetingSize === 5);
  renderOfficeHoursPanel();
}

function renderGroupFields() {
  const service = getServiceById(state.selectedServiceId);
  groupFormGrid.innerHTML = "";

  if (!service || service.isOfficeHours || state.meetingSize === 1) {
    groupFields.hidden = true;
    return;
  }

  groupFields.hidden = false;

  const payerField = document.createElement("div");
  payerField.className = "form-field full-width";
  payerField.innerHTML = `
    <label for="payerApplicant">Who is paying?</label>
    <select id="payerApplicant" disabled>
      <option value="1" selected>${studentData.name || "Applicant 1"} (booking user)</option>
    </select>
  `;
  groupFormGrid.appendChild(payerField);

  const primaryApplicant = document.createElement("div");
  primaryApplicant.className = "applicant-row";
  primaryApplicant.innerHTML = `
    <div class="form-field">
      <label>Applicant 1 Name</label>
      <input type="text" value="${studentData.name || ""}" disabled />
    </div>
    <div class="form-field">
      <label>Applicant 1 Email</label>
      <input type="email" value="${studentData.email || ""}" disabled />
    </div>
  `;
  groupFormGrid.appendChild(primaryApplicant);

  for (let i = 2; i <= state.meetingSize; i += 1) {
    const row = document.createElement("div");
    row.className = "applicant-row";
    row.innerHTML = `
      <div class="form-field">
        <label for="applicantName${i}">Applicant ${i} Name</label>
        <input id="applicantName${i}" type="text" placeholder="Enter applicant ${i} name" />
      </div>
      <div class="form-field">
        <label for="applicantEmail${i}">Applicant ${i} Email</label>
        <input id="applicantEmail${i}" type="email" placeholder="Enter applicant ${i} email" />
      </div>
    `;
    groupFormGrid.appendChild(row);
  }
}

function renderOfficeHoursPanel() {
  const service = getServiceById(state.selectedServiceId);

  if (!service || !service.isOfficeHours || !officeHoursData) {
    officeHoursPanel.hidden = true;
    selectionCard.classList.remove("office-hours-active");
    return;
  }

  officeHoursPanel.hidden = false;
  selectionCard.classList.add("office-hours-active");
  officeHoursMentorName.textContent = officeHoursData.mentorName || mentorData.name;
  officeHoursMentorMeta.textContent = officeHoursData.mentorMeta || mentorData.meta;
  officeHoursWeeklyService.textContent = officeHoursData.weeklyService || "Office Hours";
  officeHoursRecurringTime.textContent = officeHoursData.recurringTime || "Schedule coming soon";
  officeHoursMeetingType.textContent = officeHoursData.meetingType || "Small Group Office Hours";
  officeHoursCapacityPill.textContent = `${officeHoursData.spotsFilled || 0}/${officeHoursData.maxSpots || 3} spots filled`;
  officeHoursAvailability.textContent =
    officeHoursData.availabilityText || "Availability updates soon";
  officeHoursNote.textContent =
    officeHoursData.note ||
    "This session stays focused on the designated weekly service once multiple students are booked.";
}

function renderMonthNavigation() {
  const currentMonth = state.availableMonths[state.selectedMonthIndex];
  monthDisplay.textContent = currentMonth
    ? currentMonth.label
    : "No available months";
  prevMonthBtn.disabled = state.selectedMonthIndex <= 0;
  nextMonthBtn.disabled =
    state.availableMonths.length === 0 ||
    state.selectedMonthIndex >= state.availableMonths.length - 1;
}

function renderCalendar() {
  calendarGrid.innerHTML = "";
  const service = getServiceById(state.selectedServiceId);

  if (service?.isOfficeHours) {
    calendarGrid.innerHTML = `
      <button class="time-slot disabled" disabled>
        Office Hours uses the upcoming live session shown on the left.
      </button>
    `;
    return;
  }

  if (!state.availableDays.length) {
    calendarGrid.innerHTML = `
      <button class="time-slot disabled" disabled>
        No dates available for this month
      </button>
    `;
    return;
  }

  state.availableDays.forEach((day) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `date-card ${state.selectedDate === day.date ? "active" : ""}`;
    btn.innerHTML = `
      <span class="date-day">${day.weekday}</span>
      <span class="date-num">${day.day}</span>
    `;
    btn.addEventListener("click", () => {
      loadTimes(day.date);
    });
    calendarGrid.appendChild(btn);
  });
}

function renderTimes() {
  timeGrid.innerHTML = "";
  const service = getServiceById(state.selectedServiceId);

  if (service?.isOfficeHours) {
    selectedDateLabel.textContent = officeHoursData?.sessionDate
      ? formatLongDate(officeHoursData.sessionDate)
      : "Upcoming office-hours session";
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `time-slot ${officeHoursData?.isBookable ? "active" : "disabled"}`;
    btn.disabled = !officeHoursData?.isBookable;
    btn.textContent = officeHoursData?.sessionTime || "Session not yet published";
    timeGrid.appendChild(btn);
    return;
  }

  if (!state.selectedDate) {
    selectedDateLabel.textContent = "Select a date first";
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "time-slot disabled";
    btn.disabled = true;
    btn.textContent = "Select a date first";
    timeGrid.appendChild(btn);
    return;
  }

  selectedDateLabel.textContent = formatLongDate(state.selectedDate);

  if (!state.availableTimes.length) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "time-slot disabled";
    btn.disabled = true;
    btn.textContent = "No times available";
    timeGrid.appendChild(btn);
    return;
  }

  state.availableTimes.forEach((time) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `time-slot ${state.selectedSlotId === time.slotId ? "active" : ""}`;
    btn.textContent = time.label;
    btn.addEventListener("click", () => {
      state.selectedTime = time.label;
      state.selectedSlotId = time.slotId;
      renderTimes();
      updateSummary();
      updateContinue();
    });
    timeGrid.appendChild(btn);
  });
}

function updateSummary() {
  const service = getServiceById(state.selectedServiceId);
  if (!service) return;

  const priceInfo = service.prices[state.meetingSize] || service.prices[1];
  summaryService.textContent = service.name;
  summaryDuration.textContent = service.duration;
  summaryMeetingSize.textContent = service.isOfficeHours
    ? `${officeHoursData?.spotsFilled || 0}/${officeHoursData?.maxSpots || 3} filled`
    : `1 on ${state.meetingSize}`;
  summaryPrice.textContent = priceInfo?.total || priceInfo?.label || "Not available";

  if (service.isOfficeHours) {
    summaryDate.textContent = officeHoursData?.sessionDate
      ? formatLongDate(officeHoursData.sessionDate)
      : "Not scheduled";
    summaryTime.textContent = officeHoursData?.sessionTime || "Not scheduled";
    return;
  }

  summaryDate.textContent = state.selectedDate
    ? formatLongDate(state.selectedDate)
    : "Not selected";
  summaryTime.textContent = state.selectedTime || "Not selected";
}

function updateContinue() {
  if (continueBtn?.dataset?.busy === "true") {
    return;
  }

  const service = getServiceById(state.selectedServiceId);

  if (!service) {
    continueBtn.disabled = true;
    return;
  }

  if (service.isOfficeHours) {
    continueBtn.disabled = !officeHoursData?.isBookable;
    return;
  }

  continueBtn.disabled = !state.selectedSlotId;
}

function clearGeneratedGuestInputs() {
  bookingForm
    .querySelectorAll('input[data-guest-participant="true"]')
    .forEach((input) => input.remove());
}

function appendGuestInput(name, value) {
  const input = document.createElement("input");
  input.type = "hidden";
  input.name = name;
  input.value = value;
  input.dataset.guestParticipant = "true";
  bookingForm.appendChild(input);
}

function addGuestParticipantInputs() {
  clearGeneratedGuestInputs();

  if (currentSessionType() === "1on1" || currentSessionType() === "office_hours") {
    return;
  }

  for (let i = 2; i <= state.meetingSize; i += 1) {
    const nameValue = document.getElementById(`applicantName${i}`)?.value?.trim() || "";
    const emailValue = document.getElementById(`applicantEmail${i}`)?.value?.trim() || "";
    appendGuestInput(`guest_participants[${i - 2}][full_name]`, nameValue);
    appendGuestInput(`guest_participants[${i - 2}][email]`, emailValue);
  }
}

function currentBookingPayload() {
  const service = getServiceById(state.selectedServiceId);

  return {
    mentor_id: mentorData.id,
    service_config_id: service?.serviceConfigId || "",
    session_type: currentSessionType(),
    mentor_availability_slot_id: service?.isOfficeHours ? "" : String(state.selectedSlotId || ""),
    office_hour_session_id: service?.isOfficeHours
      ? String(officeHoursData?.sessionId || "")
      : "",
    meeting_type: "zoom",
    guest_participants:
      currentSessionType() === "1on1" || currentSessionType() === "office_hours"
        ? []
        : Array.from({ length: state.meetingSize - 1 }, (_, index) => ({
            full_name:
              document.getElementById(`applicantName${index + 2}`)?.value?.trim() || "",
            email:
              document.getElementById(`applicantEmail${index + 2}`)?.value?.trim() || "",
          })),
  };
}

function setClientMessage(message) {
  if (!message) return;

  if (window.AppToast?.show) {
    window.AppToast.show({
      type: "error",
      title: "Booking issue",
      message,
    });
    return;
  }

  window.alert(message);
}

function clearClientMessage() {}

function setContinueBusy(isBusy, label = "Continue") {
  if (!continueBtn) return;
  continueBtn.disabled = isBusy;
  continueBtn.dataset.busy = isBusy ? "true" : "false";
  continueBtn.textContent = isBusy ? label : "Continue";
}

async function fetchCreditBalance() {
  const response = await fetch(creditBalanceUrl, {
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    credentials: "same-origin",
  });

  if (!response.ok) {
    throw new Error("Unable to check your credit balance right now.");
  }

  return response.json();
}

async function startStripeCheckout(payload) {
  const response = await fetch(bookingCheckoutUrl, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken,
      "X-Requested-With": "XMLHttpRequest",
    },
    credentials: "same-origin",
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.message || "Unable to start Stripe checkout.");
  }

  if (!data.checkout_url) {
    throw new Error("Stripe checkout URL was not returned.");
  }

  window.location.href = data.checkout_url;
}

function openModal(modal) {
  if (!modal) return;
  modal.hidden = false;
  modal.classList.add("open");
  document.body.classList.add("modal-open");
}

function closeModal(modal) {
  if (!modal) return;
  modal.classList.remove("open");
  modal.hidden = true;
  const anyOpenModal = document.querySelector(".global-modal-overlay.open");
  if (!anyOpenModal) {
    document.body.classList.remove("modal-open");
  }
}

async function initializeAvailability() {
  try {
    await loadMonths();
  } catch (error) {
    monthDisplay.textContent = "Availability unavailable";
  }
}

prevMonthBtn?.addEventListener("click", async () => {
  if (state.selectedMonthIndex <= 0) return;
  state.selectedMonthIndex -= 1;
  await loadDays();
});

nextMonthBtn?.addEventListener("click", async () => {
  if (state.selectedMonthIndex >= state.availableMonths.length - 1) return;
  state.selectedMonthIndex += 1;
  await loadDays();
});

continueBtn?.addEventListener("click", () => {
  const service = getServiceById(state.selectedServiceId);
  if (!service) return;

  clearClientMessage();
  setContinueBusy(true, isPaidService() ? "Redirecting..." : "Processing...");

  const payload = currentBookingPayload();

  formServiceConfigId.value = service.serviceConfigId;
  formSessionType.value = currentSessionType();
  formSlotId.value = service.isOfficeHours ? "" : String(state.selectedSlotId || "");
  formOfficeHourSessionId.value = service.isOfficeHours
    ? String(officeHoursData?.sessionId || "")
    : "";

  addGuestParticipantInputs();

  (async () => {
    try {
      if (service.isOfficeHours) {
        const balance = await fetchCreditBalance();
        if (Number(balance.balance || 0) < 1) {
          throw new Error("You need at least 1 credit to book Office Hours.");
        }
        bookingForm.submit();
        return;
      }

      if (isFreeService()) {
        bookingForm.submit();
        return;
      }

      await startStripeCheckout(payload);
    } catch (error) {
      setContinueBusy(false);
      setClientMessage(error.message || "Something went wrong. Please try again.");
      updateContinue();
    }
  })();
});

closeCreditModal?.addEventListener("click", (event) => {
  event.preventDefault();
  event.stopPropagation();
  closeModal(creditModal);
});

creditModal?.addEventListener("click", (event) => {
  if (officeHoursModalPanel && !officeHoursModalPanel.contains(event.target)) {
    closeModal(creditModal);
  }
});

closeStoreModal?.addEventListener("click", (event) => {
  event.preventDefault();
  event.stopPropagation();
  closeModal(storeModal);
});

storeModal?.addEventListener("click", (event) => {
  if (storeModalPanel && !storeModalPanel.contains(event.target)) {
    closeModal(storeModal);
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    if (creditModal?.classList.contains("open")) closeModal(creditModal);
    if (storeModal?.classList.contains("open")) closeModal(storeModal);
  }
});

closeModal(creditModal);
closeModal(storeModal);
hydrateMentorDetails();
renderServices();
renderMeetingSizes();
renderGroupFields();
renderOfficeHoursPanel();
renderCalendar();
renderTimes();
updateSummary();
updateContinue();
initializeAvailability();

const menuBtn = document.getElementById("mobileMenuToggle");
const overlay = document.getElementById("sidebarOverlay");
const shell = document.querySelector(".app-shell");

if (menuBtn && shell) {
  menuBtn.onclick = () => shell.classList.add("sidebar-active");
}
if (overlay && shell) {
  overlay.onclick = () => shell.classList.remove("sidebar-active");
}

const navItems = document.querySelectorAll(".nav-item");
function setActiveNav() {
  const currentPath = window.location.pathname;
  navItems.forEach((item) => {
    const href = item.getAttribute("href");
    if (href && currentPath.startsWith(href)) {
      item.classList.add("active");
    } else {
      item.classList.remove("active");
    }
  });
}

setActiveNav();
navItems.forEach((item) => {
  item.addEventListener("click", () => {
    navItems.forEach((nav) => nav.classList.remove("active"));
    item.classList.add("active");
    if (shell) shell.classList.remove("sidebar-active");
  });
});
