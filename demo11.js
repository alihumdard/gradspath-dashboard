const userHasOfficeHoursCredits = false;

const services = [
  {
    id: "free-consultation",
    name: "Free Consultation",
    duration: "15 min",
    desc: "Meet a mentor of your choosing, assess the fit, and align your goals in a focused introductory session.",
    prices: {
      1: { label: "Free", total: "Free" },
    },
    allowedSizes: [1],
    defaultSize: 1,
    isOfficeHours: false,
  },
  {
    id: "tutoring",
    name: "Tutoring",
    duration: "60 min",
    desc: "High-performance preparation for the GMAT, GRE, LSAT, and therapy licensing exams with current graduate mentors.",
    prices: {
      1: { label: "$70.00", total: "$70.00" },
      3: { label: "$188.98 total · $62.99 each", total: "$188.98 total" },
      5: { label: "$279.97 total · $55.99 each", total: "$279.97 total" },
    },
    allowedSizes: [1, 3, 5],
    defaultSize: 1,
    isOfficeHours: false,
  },
  {
    id: "program-insights",
    name: "Program Insights",
    duration: "60 min",
    desc: "Learn what programs are really like from current graduate students and get firsthand insight into culture, academics, and opportunities.",
    prices: {
      1: { label: "$65.00", total: "$65.00" },
      3: { label: "$175.48 total · $58.49 each", total: "$175.48 total" },
      5: { label: "$259.97 total · $51.99 each", total: "$259.97 total" },
    },
    allowedSizes: [1, 3, 5],
    defaultSize: 1,
    isOfficeHours: false,
  },
  {
    id: "interview-prep",
    name: "Interview Prep",
    duration: "60 min",
    desc: "Prepare for graduate school and career interviews through mock practice, structured feedback, and strategy.",
    prices: {
      1: { label: "$65.00", total: "$65.00" },
      3: { label: "$175.48 total · $58.49 each", total: "$175.48 total" },
      5: { label: "$259.97 total · $51.99 each", total: "$259.97 total" },
    },
    allowedSizes: [1, 3, 5],
    defaultSize: 1,
    isOfficeHours: false,
  },
  {
    id: "application-review",
    name: "Application Review",
    duration: "60 min",
    desc: "Receive detailed feedback on essays, resumes, and application materials from current graduate students.",
    prices: {
      1: { label: "$60.00", total: "$60.00" },
    },
    allowedSizes: [1],
    defaultSize: 1,
    isOfficeHours: false,
  },
  {
    id: "gap-year-planning",
    name: "Gap Year Planning",
    duration: "60 min",
    desc: "Design a purposeful year focused on research, service, or professional experience to strengthen your next application cycle.",
    prices: {
      1: { label: "$50.00", total: "$50.00" },
    },
    allowedSizes: [1],
    defaultSize: 1,
    isOfficeHours: false,
  },
  {
    id: "office-hours",
    name: "Office Hours",
    duration: "45 min",
    desc: "Office Hours are subscription-based and booked using 1 credit. These sessions may have 1 to 3 students depending on availability.",
    prices: {
      1: { label: "1 credit", total: "1 credit" },
    },
    allowedSizes: [],
    defaultSize: 1,
    isOfficeHours: true,
  },
];

const selectedMentorOfficeHours = {
  mentorName: "Daniel Ross",
  mentorMeta: "Law • Yale Law School",
  weeklyService: "Program Insights",
  recurringDay: "Wednesday",
  recurringTime: "7:00 PM",
  spotsFilled: 2,
  maxSpots: 3,
  meetingType: "Small Group Office Hours",
  soloFallbackAllowed: true,
};

const schedule = {
  "2026-03-11": ["10:00 AM", "11:30 AM", "1:00 PM", "4:30 PM"],
  "2026-03-12": ["9:00 AM", "10:30 AM", "2:00 PM", "5:00 PM"],
  "2026-03-13": ["8:30 AM", "11:00 AM", "1:30 PM", "3:00 PM"],
  "2026-03-14": ["10:00 AM", "12:00 PM", "2:30 PM"],
  "2026-03-15": ["9:30 AM", "11:30 AM", "3:30 PM"],
  "2026-03-16": ["10:00 AM", "1:00 PM", "4:00 PM"],
  "2026-03-17": ["9:00 AM", "12:30 PM", "2:00 PM", "5:30 PM"],
};

const state = {
  selectedServiceId: "tutoring",
  meetingSize: 1,
  selectedDate: null,
  selectedTime: null,
};

const serviceGrid = document.getElementById("serviceGrid");
const meetingSizeGrid = document.getElementById("meetingSizeGrid");
const meetingSection = document.getElementById("meetingSection");
const meetingHelperText = document.getElementById("meetingHelperText");
const specialRequestNote = document.getElementById("specialRequestNote");
const groupFields = document.getElementById("groupFields");
const groupFormGrid = document.getElementById("groupFormGrid");
const creditNote = document.getElementById("creditNote");

const officeHoursPanel = document.getElementById("officeHoursPanel");
const officeHoursMentorName = document.getElementById("officeHoursMentorName");
const officeHoursMentorMeta = document.getElementById("officeHoursMentorMeta");
const officeHoursCapacityPill = document.getElementById(
  "officeHoursCapacityPill",
);
const officeHoursWeeklyService = document.getElementById(
  "officeHoursWeeklyService",
);
const officeHoursRecurringTime = document.getElementById(
  "officeHoursRecurringTime",
);
const officeHoursMeetingType = document.getElementById(
  "officeHoursMeetingType",
);
const officeHoursAvailability = document.getElementById(
  "officeHoursAvailability",
);
const officeHoursNote = document.getElementById("officeHoursNote");
const selectionCard = document.getElementById("selectionCard");

const calendarGrid = document.getElementById("calendarGrid");
const timeGrid = document.getElementById("timeGrid");
const selectedDateLabel = document.getElementById("selectedDateLabel");

const summaryService = document.getElementById("summaryService");
const summaryDuration = document.getElementById("summaryDuration");
const summaryMeetingSize = document.getElementById("summaryMeetingSize");
const summaryPrice = document.getElementById("summaryPrice");
const summaryDate = document.getElementById("summaryDate");
const summaryTime = document.getElementById("summaryTime");
const continueBtn = document.getElementById("continueBtn");

const creditModal = document.getElementById("creditModal");
const closeCreditModal = document.getElementById("closeCreditModal");
const officeHoursModalPanel = creditModal.querySelector(
  ".office-hours-modal-panel",
);

const storeModal = document.getElementById("storeModal");
const openStoreBtn = document.getElementById("openStoreBtn");
const closeStoreModal = document.getElementById("closeStoreModal");
const storeModalPanel = storeModal.querySelector(".store-modal-panel-inner");

function getServiceById(id) {
  return services.find((service) => service.id === id);
}

function getAccentClass(index) {
  return index % 2 === 0 ? "accent-purple" : "accent-pink";
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
    "interview-prep": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 11V8a4 4 0 1 1 8 0v3m-9 0h10a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    "application-review": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 7h8M8 11h8M8 15h5M7 4h7l4 4v12H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    "gap-year-planning": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-6-9h12M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
    "office-hours": `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 3h10M8 6h8m-9 3h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Zm3 5 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
  };

  return icons[serviceId] || icons.tutoring;
}

function renderServices() {
  serviceGrid.innerHTML = "";

  services.forEach((service, index) => {
    const isActive = service.id === state.selectedServiceId;
    const defaultPrice = service.prices[service.defaultSize]?.label || "";
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

    card.addEventListener("click", () => {
      state.selectedServiceId = service.id;
      state.meetingSize = service.defaultSize;
      renderServices();
      renderMeetingSizes();
      renderGroupFields();
      renderOfficeHoursPanel();
      updateSummary();
      updateContinue();
    });

    serviceGrid.appendChild(card);
  });
}

function renderMeetingSizes() {
  const service = getServiceById(state.selectedServiceId);
  meetingSizeGrid.innerHTML = "";

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

  if (service.allowedSizes.length === 1 && service.allowedSizes[0] === 1) {
    meetingHelperText.textContent =
      service.id === "free-consultation"
        ? "Free Consultation is only available as a 15 minute 1 on 1 session."
        : "This service is only available as a 1 on 1 session.";
  } else {
    meetingHelperText.textContent =
      "Choose whether this is an individual booking or a small group request.";
  }

  service.allowedSizes.forEach((size) => {
    const priceInfo = service.prices[size];
    const card = document.createElement("button");
    card.type = "button";
    card.className = `meeting-size-card ${state.meetingSize === size ? "active" : ""}`;

    let subtext = "Standard booking";
    if (size === 3 || size === 5) {
      subtext = `Special request · ${priceInfo.label}`;
    }
    if (size === 1) {
      subtext = priceInfo.label;
    }

    card.innerHTML = `
      <strong>1 on ${size}</strong>
      <span>${subtext}</span>
    `;

    card.addEventListener("click", () => {
      state.meetingSize = size;
      renderMeetingSizes();
      renderGroupFields();
      updateSummary();
    });

    meetingSizeGrid.appendChild(card);
  });

  specialRequestNote.hidden = !(
    state.meetingSize === 3 || state.meetingSize === 5
  );
  renderOfficeHoursPanel();
}

function renderGroupFields() {
  const service = getServiceById(state.selectedServiceId);
  groupFormGrid.innerHTML = "";

  if (service.isOfficeHours || state.meetingSize === 1) {
    groupFields.hidden = true;
    return;
  }

  groupFields.hidden = false;

  const payerField = document.createElement("div");
  payerField.className = "form-field full-width";
  payerField.innerHTML = `
    <label for="payerApplicant">Who is paying?</label>
    <select id="payerApplicant" disabled>
      <option value="1" selected>Applicant 1</option>
    </select>
  `;

  groupFormGrid.appendChild(payerField);

  for (let i = 1; i <= state.meetingSize; i++) {
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

  if (!service.isOfficeHours) {
    officeHoursPanel.hidden = true;
    selectionCard.classList.remove("office-hours-active");
    return;
  }

  officeHoursPanel.hidden = false;
  selectionCard.classList.add("office-hours-active");

  const remaining =
    selectedMentorOfficeHours.maxSpots - selectedMentorOfficeHours.spotsFilled;

  officeHoursMentorName.textContent = selectedMentorOfficeHours.mentorName;
  officeHoursMentorMeta.textContent = selectedMentorOfficeHours.mentorMeta;
  officeHoursWeeklyService.textContent =
    selectedMentorOfficeHours.weeklyService;
  officeHoursRecurringTime.textContent = `${selectedMentorOfficeHours.recurringDay}s at ${selectedMentorOfficeHours.recurringTime}`;
  officeHoursMeetingType.textContent = selectedMentorOfficeHours.meetingType;
  officeHoursCapacityPill.textContent = `${selectedMentorOfficeHours.spotsFilled}/${selectedMentorOfficeHours.maxSpots} spots filled`;

  if (remaining <= 0) {
    officeHoursAvailability.textContent = "Currently full";
  } else if (remaining === 1) {
    officeHoursAvailability.textContent = "1 spot remaining";
  } else {
    officeHoursAvailability.textContent = `${remaining} spots remaining`;
  }

  if (
    selectedMentorOfficeHours.spotsFilled === 1 &&
    selectedMentorOfficeHours.soloFallbackAllowed
  ) {
    officeHoursNote.innerHTML = `
      This week’s office hours for this mentor are currently set as <strong>${selectedMentorOfficeHours.weeklyService}</strong>.
      Right now only one student is booked. If no one else joins by the cutoff, the student may request another eligible service this mentor offers.
    `;
  } else {
    officeHoursNote.innerHTML = `
      This week’s office hours for this mentor are currently set as <strong>${selectedMentorOfficeHours.weeklyService}</strong>.
      If other students join, this is the meeting focus you are agreeing to.
      If you are the only student booked by the cutoff, you may request another eligible service this mentor offers.
    `;
  }
}

function renderCalendar() {
  calendarGrid.innerHTML = "";

  Object.keys(schedule).forEach((dateString) => {
    const date = new Date(`${dateString}T12:00:00`);

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `date-card ${state.selectedDate === dateString ? "active" : ""}`;
    btn.innerHTML = `
      <span class="date-day">${date.toLocaleDateString("en-US", { weekday: "short" })}</span>
      <span class="date-num">${date.getDate()}</span>
    `;

    btn.addEventListener("click", () => {
      state.selectedDate = dateString;
      state.selectedTime = null;
      renderCalendar();
      renderTimes();
      updateSummary();
      updateContinue();
    });

    calendarGrid.appendChild(btn);
  });
}

function renderTimes() {
  timeGrid.innerHTML = "";

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

  const times = schedule[state.selectedDate] || [];
  times.forEach((time) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `time-slot ${state.selectedTime === time ? "active" : ""}`;
    btn.textContent = time;

    btn.addEventListener("click", () => {
      state.selectedTime = time;
      renderTimes();
      updateSummary();
      updateContinue();
    });

    timeGrid.appendChild(btn);
  });
}

function updateSummary() {
  const service = getServiceById(state.selectedServiceId);
  const priceInfo = service.prices[state.meetingSize] || service.prices[1];

  summaryService.textContent = service.name;
  summaryDuration.textContent = service.duration;

  if (service.isOfficeHours) {
    summaryMeetingSize.textContent = `${selectedMentorOfficeHours.spotsFilled}/${selectedMentorOfficeHours.maxSpots} filled`;
    summaryPrice.textContent = "1 credit";
  } else {
    summaryMeetingSize.textContent = `1 on ${state.meetingSize}`;
    summaryPrice.textContent = priceInfo.total;
  }

  summaryDate.textContent = state.selectedDate
    ? formatLongDate(state.selectedDate)
    : "Not selected";
  summaryTime.textContent = state.selectedTime || "Not selected";
}

function updateContinue() {
  continueBtn.disabled = !(state.selectedDate && state.selectedTime);
}

function openModal(modal) {
  modal.hidden = false;
  modal.classList.add("open");
  document.body.classList.add("modal-open");
}

function closeModal(modal) {
  modal.classList.remove("open");
  modal.hidden = true;

  const anyOpenModal = document.querySelector(".global-modal-overlay.open");
  if (!anyOpenModal) {
    document.body.classList.remove("modal-open");
  }
}

document.querySelector(".back-arrow-btn").addEventListener("click", () => {
  alert("Go back to previous step.");
});

document.querySelector(".small-back-arrow").addEventListener("click", () => {
  alert("Go back to mentor selection.");
});

document.querySelector(".back-dashboard-btn").addEventListener("click", () => {
  alert("Go back to dashboard.");
});

document.querySelector(".secondary-btn").addEventListener("click", () => {
  alert("Go back.");
});

continueBtn.addEventListener("click", () => {
  const service = getServiceById(state.selectedServiceId);

  if (service.isOfficeHours && !userHasOfficeHoursCredits) {
    openModal(creditModal);
    return;
  }

  // alert("Booking confirmed!");
  window.location.href = "demo9.html"; // Redirect to user's bookings
});

closeCreditModal.addEventListener("click", (event) => {
  event.preventDefault();
  event.stopPropagation();
  closeModal(creditModal);
});

creditModal.addEventListener("click", (event) => {
  if (!officeHoursModalPanel.contains(event.target)) {
    closeModal(creditModal);
  }
});

openStoreBtn.addEventListener("click", () => {
  openModal(storeModal);
});

closeStoreModal.addEventListener("click", (event) => {
  event.preventDefault();
  event.stopPropagation();
  closeModal(storeModal);
});

storeModal.addEventListener("click", (event) => {
  if (!storeModalPanel.contains(event.target)) {
    closeModal(storeModal);
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    if (creditModal.classList.contains("open")) {
      closeModal(creditModal);
    }
    if (storeModal.classList.contains("open")) {
      closeModal(storeModal);
    }
  }
});

document.querySelectorAll(".program-card").forEach((card) => {
  card.addEventListener("click", () => {
    document
      .querySelectorAll(".program-card")
      .forEach((c) => c.classList.remove("active"));
    card.classList.add("active");
  });
});

document
  .querySelector(".office-hours-subscribe-btn")
  .addEventListener("click", () => {
    alert("Go to Office Hours subscription checkout.");
  });

document.querySelectorAll(".store-option-btn").forEach((button) => {
  button.addEventListener("click", () => {
    alert(`${button.textContent} clicked.`);
  });
});

closeModal(creditModal);
closeModal(storeModal);

renderServices();
renderMeetingSizes();
renderGroupFields();
renderOfficeHoursPanel();
renderCalendar();
renderTimes();
updateSummary();
updateContinue();
// Mobile sidebar toggle
const menuBtn = document.getElementById("mobileMenuToggle");
const overlay = document.getElementById("sidebarOverlay");
const shell = document.querySelector(".app-shell");

if (menuBtn && shell) {
  menuBtn.onclick = () => shell.classList.add("sidebar-active");
}
if (overlay && shell) {
  overlay.onclick = () => shell.classList.remove("sidebar-active");
}
