const loginForm = document.getElementById("loginForm");
const loginScreen = document.getElementById("loginScreen");
const dashboard = document.getElementById("dashboard");
const signOutBtn = document.getElementById("signOutBtn");
const reloadBtn = document.getElementById("reloadBtn");
const showPassword = document.getElementById("showPassword");
const passwordInput = document.getElementById("password");
const navLinks = document.querySelectorAll(".nav-link");
const panels = document.querySelectorAll(".tab-panel");

let chartsInitialized = false;

loginForm.addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const password = passwordInput.value.trim();

  if (!email || !password) return;

  loginScreen.classList.add("hidden");
  dashboard.classList.remove("hidden");

  if (!chartsInitialized) {
    initializeCharts();
    chartsInitialized = true;
  }
});

showPassword.addEventListener("change", function () {
  passwordInput.type = this.checked ? "text" : "password";
});

signOutBtn.addEventListener("click", function () {
  dashboard.classList.add("hidden");
  loginScreen.classList.remove("hidden");
  loginForm.reset();
  passwordInput.type = "password";
});

reloadBtn.addEventListener("click", function () {
  window.location.reload();
});

navLinks.forEach((link) => {
  link.addEventListener("click", function () {
    const tab = this.dataset.tab;

    navLinks.forEach((item) => item.classList.remove("active"));
    panels.forEach((panel) => panel.classList.remove("active"));

    this.classList.add("active");
    document.getElementById(tab).classList.add("active");
  });
});

/* USERS FILTERING */
const usersSearch = document.getElementById("usersSearch");
const usersProgramFilter = document.getElementById("usersProgramFilter");
const usersInstitutionFilter = document.getElementById(
  "usersInstitutionFilter",
);
const usersRows = document.querySelectorAll("#usersTable tbody tr");

function filterUsers() {
  const search = usersSearch.value.toLowerCase().trim();
  const program = usersProgramFilter.value;
  const institution = usersInstitutionFilter.value;

  usersRows.forEach((row) => {
    const text = row.textContent.toLowerCase();
    const rowProgram = row.dataset.program;
    const rowInstitution = row.dataset.institution;

    const matchesSearch = text.includes(search);
    const matchesProgram = program === "all" || rowProgram === program;
    const matchesInstitution =
      institution === "all" || rowInstitution === institution;

    row.style.display =
      matchesSearch && matchesProgram && matchesInstitution ? "" : "none";
  });
}

usersSearch.addEventListener("input", filterUsers);
usersProgramFilter.addEventListener("change", filterUsers);
usersInstitutionFilter.addEventListener("change", filterUsers);

/* MENTORS FILTERING */
const mentorsSearch = document.getElementById("mentorsSearch");
const mentorsProgramFilter = document.getElementById("mentorsProgramFilter");
const mentorsStatusFilter = document.getElementById("mentorsStatusFilter");
const mentorsRows = document.querySelectorAll("#mentorsTable tbody tr");

function filterMentors() {
  const search = mentorsSearch.value.toLowerCase().trim();
  const program = mentorsProgramFilter.value;
  const status = mentorsStatusFilter.value;

  mentorsRows.forEach((row) => {
    const text = row.textContent.toLowerCase();
    const rowProgram = row.dataset.program;
    const rowStatus = row.dataset.status;

    const matchesSearch = text.includes(search);
    const matchesProgram = program === "all" || rowProgram === program;
    const matchesStatus = status === "all" || rowStatus === status;

    row.style.display =
      matchesSearch && matchesProgram && matchesStatus ? "" : "none";
  });
}

mentorsSearch.addEventListener("input", filterMentors);
mentorsProgramFilter.addEventListener("change", filterMentors);
mentorsStatusFilter.addEventListener("change", filterMentors);

function getChartTextColor() {
  return "#9aa3b7";
}

function getGridColor() {
  return "rgba(255,255,255,0.08)";
}

function initializeCharts() {
  const sharedOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        labels: {
          color: getChartTextColor(),
          font: { family: "Inter" },
        },
      },
    },
    scales: {
      x: {
        ticks: { color: getChartTextColor() },
        grid: { color: getGridColor() },
      },
      y: {
        ticks: { color: getChartTextColor() },
        grid: { color: getGridColor() },
      },
    },
  };

  new Chart(document.getElementById("bookingsChart"), {
    type: "line",
    data: {
      labels: ["Oct", "Nov", "Dec", "Jan", "Feb", "Mar"],
      datasets: [
        {
          label: "Bookings",
          data: [38, 46, 51, 64, 78, 96],
          borderWidth: 2,
          tension: 0.35,
        },
      ],
    },
    options: sharedOptions,
  });

  new Chart(document.getElementById("revenueChart"), {
    type: "bar",
    data: {
      labels: ["Oct", "Nov", "Dec", "Jan", "Feb", "Mar"],
      datasets: [
        {
          label: "Revenue",
          data: [4200, 5100, 5900, 7600, 9800, 12840],
          borderWidth: 1,
        },
      ],
    },
    options: sharedOptions,
  });

  new Chart(document.getElementById("servicesChart"), {
    type: "bar",
    data: {
      labels: [
        "Free Consultation",
        "Tutoring",
        "Program Insights",
        "Interview Prep",
        "Application Review",
        "Gap Year Planning",
        "Office Hours",
      ],
      datasets: [
        {
          label: "Bookings",
          data: [22, 8, 31, 24, 18, 6, 14],
          borderWidth: 1,
        },
      ],
    },
    options: sharedOptions,
  });

  new Chart(document.getElementById("programRevenueChart"), {
    type: "doughnut",
    data: {
      labels: ["MBA", "Law", "CMHC", "MFT", "MSW", "Clinical Psy"],
      datasets: [
        {
          label: "Revenue",
          data: [6920, 3740, 2180, 900, 1450, 1100],
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: {
            color: getChartTextColor(),
            font: { family: "Inter" },
          },
        },
      },
    },
  });

  new Chart(document.getElementById("topMentorsChart"), {
    type: "bar",
    data: {
      labels: [
        "Sarah Kim",
        "Daniel Brooks",
        "Rachel Adams",
        "Leah Morris",
        "Anthony Cruz",
      ],
      datasets: [
        {
          label: "Revenue",
          data: [1870, 1190, 790, 720, 610],
          borderWidth: 1,
        },
      ],
    },
    options: {
      ...sharedOptions,
      indexAxis: "y",
    },
  });
}

/* ======================================================== */
/* DEMO14 FUNCTIONALITY - MERGED FROM Demo14.js            */
/* ======================================================== */

document.addEventListener("DOMContentLoaded", () => {
  // Initialize demo12 functionality
  navLinks.forEach((link) => {
    link.addEventListener("click", function () {
      const tab = this.dataset.tab;

      navLinks.forEach((item) => item.classList.remove("active"));
      panels.forEach((panel) => panel.classList.remove("active"));
      this.classList.add("active");
      document.getElementById(tab).classList.add("active");
    });
  });

  usersSearch.addEventListener("input", filterUsers);
  usersProgramFilter.addEventListener("change", filterUsers);
  usersInstitutionFilter.addEventListener("change", filterUsers);

  mentorsSearch.addEventListener("input", filterMentors);
  mentorsProgramFilter.addEventListener("change", filterMentors);
  mentorsStatusFilter.addEventListener("change", filterMentors);

  // Mobile sidebar toggle
  const menuBtn = document.getElementById("mobileMenuToggle");
  const overlay = document.getElementById("sidebarOverlay");
  const shell = document.querySelector(".app-shell");

  if (menuBtn && shell) {
    menuBtn.addEventListener("click", (e) => {
      e.preventDefault();
      shell.classList.add("sidebar-active");
    });
  }

  if (overlay && shell) {
    overlay.addEventListener("click", (e) => {
      e.preventDefault();
      shell.classList.remove("sidebar-active");
    });
  }

  // Sidebar navigation logic
  const navItems = document.querySelectorAll(".nav-item");

  function setActiveNav() {
    const currentPath =
      window.location.pathname.split("/").pop() || "demo1.html";

    navItems.forEach((item) => {
      const href = item.getAttribute("href");
      // Use lowercase comparison for better cross-platform reliability
      if (href && href.toLowerCase() === currentPath.toLowerCase()) {
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
});

const demo14ServiceMeta = {
  "Free Consultation": {
    label: "Free Consultation",
    previewLabel: "Free Consultation",
    originalPrice: 0,
    currentPrice: 0,
    icon: `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 3a7 7 0 0 0-7 7c0 1.7.6 3.2 1.6 4.4L5 19l4.8-1.3A7 7 0 1 0 12 3Zm-3 7.2h6v1.6H9v-1.6Zm0-3h6v1.6H9V7.2Zm0 6h4v1.6H9v-1.6Z"/>
      </svg>
    `,
  },
  Tutoring: {
    label: "Tutoring",
    previewLabel: "Tutoring",
    originalPrice: 125,
    currentPrice: 100,
    icon: `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 4a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 12c4.4 0 8 2.2 8 5v1H4v-1c0-2.8 3.6-5 8-5Zm6.5-7.5 3.5 2-3.5 2v-4Zm-13 0v4l-3.5-2 3.5-2Z"/>
      </svg>
    `,
  },
  "Program Insights": {
    label: "Program Insights",
    previewLabel: "Program Insights",
    originalPrice: 140,
    currentPrice: 112,
    icon: `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="m12 3 10 5-10 5L2 8l10-5Zm-6 8.5 6 3 6-3V16l-6 3-6-3v-4.5Z"/>
      </svg>
    `,
  },
  "Interview Prep": {
    label: "Interview Prep",
    previewLabel: "Interview Prep",
    originalPrice: 160,
    currentPrice: 128,
    icon: `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M8 7V6a4 4 0 1 1 8 0v1h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2Zm2 0h4V6a2 2 0 1 0-4 0v1Z"/>
      </svg>
    `,
  },
  "Application Review": {
    label: "Application Review",
    previewLabel: "Application Review",
    originalPrice: 175,
    currentPrice: 140,
    icon: `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5L13 4.5Z"/>
      </svg>
    `,
  },
  "Gap Year Planning": {
    label: "Gap Year Planning",
    previewLabel: "Gap Year Planning",
    originalPrice: 150,
    currentPrice: 120,
    icon: `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20Zm6.9 9h-3.1a15.2 15.2 0 0 0-1.3-5A8 8 0 1 1 18.9 11ZM12 4.2c1 1.2 1.8 3.4 2 6.8h-4c.2-3.4 1-5.6 2-6.8ZM9.5 6A15.2 15.2 0 0 0 8.2 11H5.1A8 8 0 0 1 9.5 6Zm-4.4 7h3.1a15.2 15.2 0 0 0 1.3 5A8 8 0 0 1 5.1 13Zm6.9 6.8c-1-1.2-1.8-3.4-2-6.8h4c-.2 3.4-1 5.6-2 6.8ZM14.5 18a15.2 15.2 0 0 0 1.3-5h3.1a8 8 0 0 1-4.4 5Z"/>
      </svg>
    `,
  },
  "Office Hours": {
    label: "Office Hours",
    previewLabel: "Office Hours",
    originalPrice: 90,
    currentPrice: 72,
    icon: `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20Zm1 5h-2v6l5 3 1-1.7-4-2.3V7Z"/>
      </svg>
    `,
  },
};

function getAllServices() {
  return Object.keys(demo14ServiceMeta);
}

const demo14Programs = [
  {
    id: 1,
    name: "MBA",
    category: "Business",
    description: "Master of Business Administration programs.",
  },
  {
    id: 2,
    name: "Law",
    category: "Legal",
    description: "JD and legal pathway programs.",
  },
  {
    id: 3,
    name: "Therapy Pathway",
    category: "Healthcare",
    description: "Therapy, counseling, and mental health pathway programs.",
  },
  {
    id: 4,
    name: "MSF",
    category: "Business",
    description: "Master of Science in Finance programs.",
  },
  {
    id: 5,
    name: "STEM PhD",
    category: "STEM",
    description: "Doctoral research programs in STEM fields.",
  },
];

const demo14Institutions = [
  {
    id: 1,
    name: "Boston College",
    category: "University",
    programIds: [1, 4],
    mentorIds: [1],
  },
  {
    id: 2,
    name: "Harvard",
    category: "University",
    programIds: [5],
    mentorIds: [2],
  },
];

const demo14Mentors = [
  {
    id: 1,
    type: "Graduate Mentor",
    name: "Tyler Cogan",
    email: "coganty@bc.edu",
    officeHours: "Every Tuesday at 5 PM EST",
    calendly: "",
    institutionId: 1,
    programIds: [1, 4],
    description:
      "Tyler is a Boston College student mentor who helps students think through applications, school strategy, and next-step planning.",
    services: ["Free Consultation", "Application Review", "Office Hours"],
  },
  {
    id: 2,
    type: "PhD Person",
    name: "Dr. Sarah Jenkin",
    email: "sarah@harvard.edu",
    officeHours: "Every Tuesday at 5 PM EST",
    calendly: "https://calendly.com/sarahjenkin",
    institutionId: 2,
    programIds: [5],
    description:
      "Expert in grad school applications for STEM fields. I help with statement of purpose review.",
    services: [
      "Free Consultation",
      "Tutoring",
      "Program Insights",
      "Interview Prep",
      "Application Review",
      "Office Hours",
    ],
  },
];

const demo14Users = [
  {
    id: 1,
    name: "Boston College Student",
    email: "student1@bc.edu",
    officeHoursCredits: 1,
    totalSpent: 345,
    lastRefund: 0,
  },
  {
    id: 2,
    name: "Emma Chen",
    email: "emma@email.com",
    officeHoursCredits: 2,
    totalSpent: 210,
    lastRefund: 0,
  },
];

const demo14Feedback = [
  {
    id: 1,
    mentorId: 2,
    mentorName: "Dr. Sarah Jenkin",
    mentorType: "PhD Person",
    mentorSchool: "Harvard",
    mentorProgram: "STEM PhD",
    degree: "PhD",
    userName: "Boston College Student",
    rating: 5,
    preparedness: "Excellent — Very strong insight",
    recommend: "Yes",
    serviceUsed: "Application Review",
    dateOfSession: "March 1, 2026",
    text: "Super clear guidance on essays and how to make my experience sound more cohesive.",
    statusNote: "",
  },
  {
    id: 2,
    mentorId: 1,
    mentorName: "Tyler Cogan",
    mentorType: "Graduate Mentor",
    mentorSchool: "Boston College",
    mentorProgram: "MBA, MSF",
    degree: "MBA",
    userName: "Emma Chen",
    rating: 4,
    preparedness: "Strong — Helpful and practical",
    recommend: "Yes",
    serviceUsed: "Office Hours",
    dateOfSession: "March 10, 2026",
    text: "Very clear, practical advice that helped me improve my essays in one session.",
    statusNote: "",
  },
];

/* ---------- DOM ---------- */
const demo14Tabs = document.querySelectorAll(".demo14-station-tab");
const demo14Panels = document.querySelectorAll(".demo14-station-panel");
const globalSearch = document.getElementById("globalSearch");

/* mentor */
const mentorSelect = document.getElementById("mentorSelect");
const mentorType = document.getElementById("mentorType");
const mentorName = document.getElementById("mentorName");
const mentorEmail = document.getElementById("mentorEmail");
const mentorOfficeHours = document.getElementById("mentorOfficeHours");
const mentorCalendly = document.getElementById("mentorCalendly");
const mentorInstitution = document.getElementById("mentorInstitution");
const mentorSchool = document.getElementById("mentorSchool");
const mentorDescription = document.getElementById("mentorDescription");
const mentorProgramsPicker = document.getElementById("mentorProgramsPicker");
const serviceCards = document.getElementById("serviceCards");
const servicesSelectedList = document.getElementById("servicesSelectedList");
const saveMentorBtn = document.getElementById("saveMentorBtn");
const expandedPreviewServices = document.getElementById(
  "expandedPreviewServices",
);
const mentorProgramPreviewGrid = document.getElementById(
  "mentorProgramPreviewGrid",
);
const previewName = document.getElementById("previewName");
const previewType = document.getElementById("previewType");
const previewSchool = document.getElementById("previewSchool");
const previewOfficeHours = document.getElementById("previewOfficeHours");
const previewDescription = document.getElementById("previewDescription");
const previewAvatar = document.getElementById("previewAvatar");
const previewNameSmall = document.getElementById("previewNameSmall");
const previewTypeSmall = document.getElementById("previewTypeSmall");
const previewSchoolSmall = document.getElementById("previewSchoolSmall");
const previewOfficeHoursSmall = document.getElementById(
  "previewOfficeHoursSmall",
);
const previewDescriptionSmall = document.getElementById(
  "previewDescriptionSmall",
);
const previewAvatarSmall = document.getElementById("previewAvatarSmall");

/* refund */
const refundUserSelect = document.getElementById("refundUserSelect");
const refundAmount = document.getElementById("refundAmount");
const officeHoursAddBack = document.getElementById("officeHoursAddBack");
const refundReason = document.getElementById("refundReason");
const refundUserSummary = document.getElementById("refundUserSummary");
const applyRefundBtn = document.getElementById("applyRefundBtn");

/* feedback */
const feedbackSelect = document.getElementById("feedbackSelect");
const feedbackAction = document.getElementById("feedbackAction");
const feedbackText = document.getElementById("feedbackText");
const feedbackRating = document.getElementById("feedbackRating");
const feedbackNote = document.getElementById("feedbackNote");
const feedbackSummary = document.getElementById("feedbackSummary");
const applyFeedbackBtn = document.getElementById("applyFeedbackBtn");
const feedbackMentorNameBox = document.getElementById("feedbackMentorNameBox");
const feedbackProgramBox = document.getElementById("feedbackProgramBox");
const feedbackMentorTypeBox = document.getElementById("feedbackMentorTypeBox");
const feedbackSchoolBox = document.getElementById("feedbackSchoolBox");
const feedbackDegreeBox = document.getElementById("feedbackDegreeBox");
const feedbackDateBox = document.getElementById("feedbackDateBox");
const feedbackStudentBox = document.getElementById("feedbackStudentBox");
const feedbackStarsBox = document.getElementById("feedbackStarsBox");
const feedbackPreparednessBox = document.getElementById(
  "feedbackPreparednessBox",
);
const feedbackRecommendBox = document.getElementById("feedbackRecommendBox");
const feedbackQuickText = document.getElementById("feedbackQuickText");
const feedbackServiceCards = document.getElementById("feedbackServiceCards");

/* institution */
const institutionSelect = document.getElementById("institutionSelect");
const institutionName = document.getElementById("institutionName");
const institutionCategory = document.getElementById("institutionCategory");
const institutionProgramsPicker = document.getElementById(
  "institutionProgramsPicker",
);
const institutionMentorsPicker = document.getElementById(
  "institutionMentorsPicker",
);
const institutionSummary = document.getElementById("institutionSummary");
const institutionMentorSearch = document.getElementById(
  "institutionMentorSearch",
);
const saveInstitutionBtn = document.getElementById("saveInstitutionBtn");
const newInstitutionBtn = document.getElementById("newInstitutionBtn");

/* program / service creation */
const creationMode = document.getElementById("creationMode");
const programModePanel = document.getElementById("programModePanel");
const serviceModePanel = document.getElementById("serviceModePanel");

const programSelect = document.getElementById("programSelect");
const programName = document.getElementById("programName");
const programCategory = document.getElementById("programCategory");
const programDescription = document.getElementById("programDescription");
const programFilterPreview = document.getElementById("programFilterPreview");
const programSummary = document.getElementById("programSummary");
const saveProgramBtn = document.getElementById("saveProgramBtn");
const newProgramBtn = document.getElementById("newProgramBtn");

const serviceSelect = document.getElementById("serviceSelect");
const serviceNameInput = document.getElementById("serviceNameInput");
const servicePriceInput = document.getElementById("servicePriceInput");
const serviceFilterPreview = document.getElementById("serviceFilterPreview");
const serviceSummary = document.getElementById("serviceSummary");
const saveServiceBtn = document.getElementById("saveServiceBtn");
const newServiceBtn = document.getElementById("newServiceBtn");

/* pricing */
const servicePricingSearch = document.getElementById("servicePricingSearch");
const priceEditorGrid = document.getElementById("priceEditorGrid");
const discountPricingPreview = document.getElementById(
  "discountPricingPreview",
);
const paymentServiceSelect = document.getElementById("paymentServiceSelect");
const paymentQuantity = document.getElementById("paymentQuantity");
const paymentSummary = document.getElementById("paymentSummary");
const savePricingBtn = document.getElementById("savePricingBtn");
const resetPricingBtn = document.getElementById("resetPricingBtn");

/* log */
const activityLog = document.getElementById("activityLog");

/* ---------- helpers ---------- */
function demo14Log(message) {
  const now = new Date().toLocaleTimeString([], {
    hour: "numeric",
    minute: "2-digit",
  });
  const item = document.createElement("div");
  item.className = "demo14-log-item";
  item.innerHTML = `${message}<small>${now}</small>`;
  activityLog.prepend(item);
}

function formatMoney(value) {
  return `$${Number(value).toFixed(0)}`;
}

function getInitials(name) {
  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join("")
    .toUpperCase();
}

function getInstitutionById(id) {
  return demo14Institutions.find((item) => item.id === Number(id));
}

function getProgramById(id) {
  return demo14Programs.find((item) => item.id === Number(id));
}

function getProgramsByIds(ids = []) {
  return ids.map((id) => getProgramById(id)).filter(Boolean);
}

function getProgramNames(ids = []) {
  return getProgramsByIds(ids).map((item) => item.name);
}

function getMentorById(id) {
  return demo14Mentors.find((item) => item.id === Number(id));
}

function getServicePricing(serviceName) {
  const service = demo14ServiceMeta[serviceName];
  if (!service) {
    return {
      originalPrice: 0,
      currentPrice: 0,
      dollarOff: 0,
      percentOff: 0,
      hasDiscount: false,
    };
  }

  const originalPrice = Number(service.originalPrice || 0);
  const currentPrice = Math.max(0, Number(service.currentPrice || 0));
  const dollarOff = Math.max(0, originalPrice - currentPrice);
  const percentOff =
    originalPrice > 0 ? Math.round((dollarOff / originalPrice) * 100) : 0;
  const hasDiscount = dollarOff > 0;

  return {
    originalPrice,
    currentPrice,
    dollarOff,
    percentOff,
    hasDiscount,
  };
}

function getServiceLabelHtml(service) {
  return service
    .replace("Free Consultation", "Free<br>Consultation")
    .replace("Program Insights", "Program<br>Insights")
    .replace("Application Review", "Application<br>Review")
    .replace("Gap Year Planning", "Gap Year<br>Planning")
    .replace("Office Hours", "Office<br>Hours")
    .replace("Interview Prep", "Interview<br>Prep");
}

function createServiceCard(service, active = false) {
  const meta = demo14ServiceMeta[service];
  const pricing = getServicePricing(service);

  return `
    <button class="demo14-service-card ${active ? "active" : ""}" data-service="${service}" type="button">
      <div class="demo14-service-icon">${meta.icon}</div>
      <div class="demo14-service-label">${getServiceLabelHtml(service)}</div>
      <div class="demo14-service-price">
        ${pricing.hasDiscount ? `<div class="demo14-price-old">${formatMoney(pricing.originalPrice)}</div>` : ``}
        <div class="demo14-price-new">${formatMoney(pricing.currentPrice)}</div>
        <div class="demo14-price-discount">${pricing.hasDiscount ? `${pricing.percentOff}% off` : `standard`}</div>
      </div>
    </button>
  `;
}

function renderChecklist(
  container,
  items,
  selectedIds,
  valueKey = "id",
  titleRenderer,
  subtitleRenderer,
) {
  container.innerHTML = items
    .map((item) => {
      const value = item[valueKey];
      return `
      <label class="demo14-check-item">
        <input type="checkbox" value="${value}" ${selectedIds.includes(value) ? "checked" : ""} />
        <div>
          <strong>${titleRenderer(item)}</strong>
          <span>${subtitleRenderer(item)}</span>
        </div>
      </label>
    `;
    })
    .join("");
}

function getCheckedValues(container) {
  return Array.from(
    container.querySelectorAll('input[type="checkbox"]:checked'),
  ).map((input) => Number(input.value));
}

function syncInstitutionMentorLists() {
  demo14Institutions.forEach((institution) => {
    institution.mentorIds = demo14Mentors
      .filter((mentor) => mentor.institutionId === institution.id)
      .map((mentor) => mentor.id);
  });
}

function refreshFeedbackFromMentors() {
  demo14Feedback.forEach((item) => {
    const mentor = getMentorById(item.mentorId);
    if (!mentor) return;
    const institution = getInstitutionById(mentor.institutionId);

    item.mentorName = mentor.name;
    item.mentorType = mentor.type;
    item.mentorSchool = institution ? institution.name : "No institution";
    item.mentorProgram =
      getProgramNames(mentor.programIds).join(", ") || "No programs";
  });
}

function refreshAllServiceUIs() {
  populatePaymentServiceSelect();

  if (paymentServiceSelect.options.length) {
    const existing = Array.from(paymentServiceSelect.options).map(
      (o) => o.value,
    );
    if (!existing.includes(paymentServiceSelect.value)) {
      paymentServiceSelect.value = paymentServiceSelect.options[0].value;
    }
  }

  renderPriceEditorCards(
    servicePricingSearch ? servicePricingSearch.value : "",
  );
  populateDiscountPricingPreview();
  renderPaymentSummary();

  if (getSelectedMentor()) {
    renderMentorServiceCards(getSelectedMentor().services || []);
    renderSelectedServices(getSelectedMentor().services || []);
    renderMentorPreviews(getSelectedMentor());
  }

  renderFeedbackForm();
  renderServiceFilterPreview();
}

function ensureMentorServiceIntegrity() {
  const validServices = new Set(getAllServices());
  demo14Mentors.forEach((mentor) => {
    mentor.services = mentor.services.filter((service) =>
      validServices.has(service),
    );
  });
}

/* ---------- tabs ---------- */
demo14Tabs.forEach((tab) => {
  tab.addEventListener("click", () => {
    demo14Tabs.forEach((t) => t.classList.remove("active"));
    demo14Panels.forEach((p) => p.classList.remove("active"));
    tab.classList.add("active");
    document.getElementById(tab.dataset.station).classList.add("active");
  });
});

/* ---------- mentor station ---------- */
function populateMentorSelect(filteredMentors = demo14Mentors) {
  mentorSelect.innerHTML = filteredMentors
    .map((mentor) => {
      const institution = getInstitutionById(mentor.institutionId);
      const programNames =
        getProgramNames(mentor.programIds).join(", ") || "No programs";
      return `<option value="${mentor.id}">${mentor.name} — ${programNames} — ${institution ? institution.name : "No institution"}</option>`;
    })
    .join("");
}

function populateMentorInstitutionSelect() {
  mentorInstitution.innerHTML = demo14Institutions
    .map(
      (institution) =>
        `<option value="${institution.id}">${institution.name}</option>`,
    )
    .join("");
}

function getSelectedMentor() {
  return demo14Mentors.find((m) => String(m.id) === mentorSelect.value);
}

function renderMentorProgramPicker(selectedIds) {
  renderChecklist(
    mentorProgramsPicker,
    demo14Programs,
    selectedIds,
    "id",
    (program) => program.name,
    (program) => program.category,
  );
}

function renderMentorServiceCards(activeServices) {
  serviceCards.innerHTML = getAllServices()
    .map((service) =>
      createServiceCard(service, activeServices.includes(service)),
    )
    .join("");

  serviceCards.querySelectorAll(".demo14-service-card").forEach((card) => {
    card.addEventListener("click", () => {
      const mentor = getSelectedMentor();
      const service = card.dataset.service;
      if (!mentor) return;

      if (mentor.services.includes(service)) {
        mentor.services = mentor.services.filter((s) => s !== service);
      } else {
        mentor.services.push(service);
      }

      renderMentorServiceCards(mentor.services);
      renderSelectedServices(mentor.services);
      renderMentorPreviews(mentor);
    });
  });
}

function renderSelectedServices(services) {
  servicesSelectedList.innerHTML = services.length
    ? services
        .map((service) => {
          const pricing = getServicePricing(service);
          return `<span class="demo14-service-pill">${service} — ${formatMoney(pricing.currentPrice)}</span>`;
        })
        .join("")
    : `<span class="demo14-service-pill">No services selected</span>`;
}

function renderMentorPreviews(mentor) {
  const institution = getInstitutionById(mentor.institutionId);
  const initials = getInitials(mentor.name);
  const programNames = getProgramNames(mentor.programIds);

  previewName.textContent = mentor.name;
  previewType.textContent = mentor.type;
  previewSchool.textContent = institution ? institution.name : "No institution";
  previewOfficeHours.textContent = mentor.officeHours;
  previewDescription.textContent = mentor.description;
  previewAvatar.textContent = initials;

  previewNameSmall.textContent = mentor.name;
  previewTypeSmall.textContent = mentor.type;
  previewSchoolSmall.textContent = institution
    ? institution.name
    : "No institution";
  previewOfficeHoursSmall.textContent = mentor.officeHours;
  previewDescriptionSmall.textContent = mentor.description;
  previewAvatarSmall.textContent = initials;

  mentorProgramPreviewGrid.innerHTML = programNames.length
    ? programNames
        .map((name) => `<div class="mentor-preview-service-pill">${name}</div>`)
        .join("")
    : `<div class="mentor-preview-service-pill">No programs selected</div>`;

  expandedPreviewServices.innerHTML = mentor.services.length
    ? mentor.services
        .map((service) => {
          const pricing = getServicePricing(service);
          const label = pricing.hasDiscount
            ? `${service}<br>${formatMoney(pricing.currentPrice)}`
            : `${service}<br>${formatMoney(pricing.originalPrice)}`;
          return `<div class="mentor-preview-service-pill">${label}</div>`;
        })
        .join("")
    : `<div class="mentor-preview-service-pill">No services selected</div>`;
}

function renderMentorForm() {
  const mentor = getSelectedMentor();
  if (!mentor) return;

  const institution = getInstitutionById(mentor.institutionId);

  mentorType.value = mentor.type;
  mentorName.value = mentor.name;
  mentorEmail.value = mentor.email;
  mentorOfficeHours.value = mentor.officeHours;
  mentorCalendly.value = mentor.calendly;
  mentorInstitution.value = mentor.institutionId
    ? String(mentor.institutionId)
    : "";
  mentorSchool.value = institution ? institution.name : "";
  mentorDescription.value = mentor.description;

  renderMentorProgramPicker(mentor.programIds);
  renderMentorServiceCards(mentor.services);
  renderSelectedServices(mentor.services);
  renderMentorPreviews(mentor);
}

mentorSelect.addEventListener("change", renderMentorForm);

mentorInstitution.addEventListener("change", () => {
  const institution = getInstitutionById(mentorInstitution.value);
  mentorSchool.value = institution ? institution.name : "";
});

saveMentorBtn.addEventListener("click", () => {
  const mentor = getSelectedMentor();
  if (!mentor) return;

  mentor.type = mentorType.value.trim();
  mentor.name = mentorName.value.trim();
  mentor.email = mentorEmail.value.trim();
  mentor.officeHours = mentorOfficeHours.value.trim();
  mentor.calendly = mentorCalendly.value.trim();
  mentor.institutionId = Number(mentorInstitution.value);
  mentor.programIds = getCheckedValues(mentorProgramsPicker);
  mentor.description = mentorDescription.value.trim();

  syncInstitutionMentorLists();
  refreshFeedbackFromMentors();

  populateMentorSelect();
  populateInstitutionSelect();
  populateRefundUsers();
  populateFeedbackSelect();
  renderInstitutionForm();
  renderProgramFilterPreview();

  mentorSelect.value = String(mentor.id);
  renderMentorForm();

  demo14Log(`Saved mentor settings for <strong>${mentor.name}</strong>.`);
});

/* ---------- refund station ---------- */
function populateRefundUsers(filteredUsers = demo14Users) {
  refundUserSelect.innerHTML = filteredUsers
    .map(
      (user) =>
        `<option value="${user.id}">${user.name} — ${user.email}</option>`,
    )
    .join("");
}

function renderRefundUserSummary() {
  const user = demo14Users.find((u) => String(u.id) === refundUserSelect.value);
  if (!user) return;

  refundUserSummary.innerHTML = `
    <strong>${user.name}</strong><br>
    Email: ${user.email}<br>
    Office Hours Credits: ${user.officeHoursCredits}<br>
    Total Spent: ${formatMoney(user.totalSpent)}<br>
    Last Refund: ${formatMoney(user.lastRefund)}
  `;
}

refundUserSelect.addEventListener("change", renderRefundUserSummary);

applyRefundBtn.addEventListener("click", () => {
  const user = demo14Users.find((u) => String(u.id) === refundUserSelect.value);
  if (!user) return;

  const refund = Number(refundAmount.value || 0);
  const addBack = Number(officeHoursAddBack.value || 0);
  const reason = refundReason.value.trim();

  user.lastRefund = refund;
  user.officeHoursCredits += addBack;

  renderRefundUserSummary();

  demo14Log(
    `Applied refund actions for <strong>${user.name}</strong>: refund ${formatMoney(refund)} and added back ${addBack} office hour credits${reason ? ` — ${reason}` : ""}.`,
  );

  refundAmount.value = "";
  officeHoursAddBack.value = "";
  refundReason.value = "";
});

/* ---------- feedback station ---------- */
function populateFeedbackSelect(filteredFeedback = demo14Feedback) {
  feedbackSelect.innerHTML = filteredFeedback.length
    ? filteredFeedback
        .map(
          (item) =>
            `<option value="${item.id}">${item.userName} → ${item.mentorName}</option>`,
        )
        .join("")
    : `<option value="">No feedback found</option>`;
}

function getSelectedFeedback() {
  return demo14Feedback.find(
    (item) => String(item.id) === feedbackSelect.value,
  );
}

function getStars(count) {
  const safeCount = Math.max(1, Math.min(5, Number(count || 0)));
  return "★".repeat(safeCount);
}

function renderFeedbackServiceCards(selectedService) {
  feedbackServiceCards.innerHTML = getAllServices()
    .map((service) => createServiceCard(service, service === selectedService))
    .join("");
}

function renderFeedbackForm() {
  const item = getSelectedFeedback();
  if (!item) {
    feedbackText.value = "";
    feedbackRating.value = "";
    feedbackNote.value = "";
    feedbackSummary.innerHTML = "No feedback items found.";
    feedbackServiceCards.innerHTML = "";
    return;
  }

  feedbackMentorNameBox.textContent = item.mentorName;
  feedbackProgramBox.textContent = item.mentorProgram;
  feedbackMentorTypeBox.textContent = item.mentorType;
  feedbackSchoolBox.textContent = item.mentorSchool;
  feedbackDegreeBox.textContent = item.degree;
  feedbackDateBox.textContent = item.dateOfSession;
  feedbackStudentBox.textContent = item.userName;
  feedbackStarsBox.textContent = getStars(item.rating);
  feedbackPreparednessBox.textContent = item.preparedness;
  feedbackRecommendBox.textContent = item.recommend;
  feedbackQuickText.textContent = `“${item.text}”`;

  feedbackText.value = item.text;
  feedbackRating.value = item.rating;
  feedbackNote.value = item.statusNote || "";
  feedbackAction.value = "";

  renderFeedbackServiceCards(item.serviceUsed);

  const servicePricing = getServicePricing(item.serviceUsed);

  feedbackSummary.innerHTML = `
    <strong>User:</strong> ${item.userName}<br>
    <strong>Mentor:</strong> ${item.mentorName}<br>
    <strong>Service Used:</strong> ${item.serviceUsed} — ${formatMoney(servicePricing.currentPrice)}<br>
    <strong>Rating:</strong> ${item.rating}<br>
    <strong>Recommendation:</strong> ${item.recommend}<br>
    <strong>Current Feedback:</strong><br>
    ${item.text}
  `;
}

feedbackSelect.addEventListener("change", renderFeedbackForm);

applyFeedbackBtn.addEventListener("click", () => {
  const item = getSelectedFeedback();
  if (!item) return;

  const action = feedbackAction.value;

  if (!action) {
    alert("Please choose an action first.");
    return;
  }

  if (action === "delete") {
    const userName = item.userName;
    const mentorName = item.mentorName;
    const index = demo14Feedback.findIndex((f) => f.id === item.id);

    if (index > -1) {
      demo14Feedback.splice(index, 1);
    }

    populateFeedbackSelect();

    if (demo14Feedback.length > 0) {
      feedbackSelect.value = feedbackSelect.options[0].value;
      renderFeedbackForm();
    } else {
      feedbackMentorNameBox.textContent = "";
      feedbackProgramBox.textContent = "";
      feedbackMentorTypeBox.textContent = "";
      feedbackDegreeBox.textContent = "";
      feedbackDateBox.textContent = "";
      feedbackSchoolBox.textContent = "";
      feedbackDateBox.textContent = "";
      feedbackStudentBox.textContent = "";
      feedbackStarsBox.textContent = "";
      feedbackPreparednessBox.textContent = "";
      feedbackRecommendBox.textContent = "";
      feedbackQuickText.textContent = "";
      feedbackText.value = "";
      feedbackRating.value = "";
      feedbackNote.value = "";
      feedbackSummary.innerHTML = "No feedback items left.";
      feedbackServiceCards.innerHTML = "";
    }

    demo14Log(
      `Deleted feedback from <strong>${userName}</strong> for mentor <strong>${mentorName}</strong>.`,
    );
    return;
  }

  item.text = feedbackText.value.trim();
  item.rating = Number(feedbackRating.value || item.rating);
  item.statusNote = feedbackNote.value.trim();

  renderFeedbackForm();

  demo14Log(
    `Amended feedback from <strong>${item.userName}</strong> for mentor <strong>${item.mentorName}</strong>.`,
  );
});

/* ---------- institution station ---------- */
function populateInstitutionSelect() {
  institutionSelect.innerHTML = demo14Institutions
    .map(
      (item) =>
        `<option value="${item.id}">${item.name} — ${item.category}</option>`,
    )
    .join("");
}

function getSelectedInstitution() {
  return demo14Institutions.find(
    (item) => String(item.id) === institutionSelect.value,
  );
}

function renderInstitutionForm(mentorFilter = "") {
  const institution = getSelectedInstitution();
  if (!institution) return;

  institutionName.value = institution.name;
  institutionCategory.value = institution.category;

  renderChecklist(
    institutionProgramsPicker,
    demo14Programs,
    institution.programIds,
    "id",
    (program) => program.name,
    (program) => program.category,
  );

  const mentorQuery = mentorFilter.trim().toLowerCase();
  const filteredMentors = demo14Mentors.filter((mentor) => {
    const institutionNameText =
      getInstitutionById(mentor.institutionId)?.name || "";
    const programText = getProgramNames(mentor.programIds).join(", ");
    const haystack = [
      mentor.name,
      mentor.email,
      mentor.type,
      institutionNameText,
      programText,
    ]
      .join(" ")
      .toLowerCase();

    return haystack.includes(mentorQuery);
  });

  renderChecklist(
    institutionMentorsPicker,
    filteredMentors,
    institution.mentorIds,
    "id",
    (mentor) => mentor.name,
    (mentor) => getProgramNames(mentor.programIds).join(", ") || "No programs",
  );

  const programs = getProgramNames(institution.programIds);
  const mentors = institution.mentorIds
    .map((id) => getMentorById(id))
    .filter(Boolean)
    .map((mentor) => mentor.name);

  institutionSummary.innerHTML = `
    <strong>${institution.name}</strong><br>
    Category: ${institution.category}<br>
    Programs Offered: ${programs.length ? programs.join(", ") : "None selected"}<br>
    Mentors Connected: ${mentors.length ? mentors.join(", ") : "None selected"}
  `;
}

institutionMentorSearch.addEventListener("input", () => {
  renderInstitutionForm(institutionMentorSearch.value);
});

institutionSelect.addEventListener("change", () => {
  institutionMentorSearch.value = "";
  renderInstitutionForm();
});

saveInstitutionBtn.addEventListener("click", () => {
  const institution = getSelectedInstitution();
  if (!institution) return;

  institution.name = institutionName.value.trim();
  institution.category = institutionCategory.value.trim();
  institution.programIds = getCheckedValues(institutionProgramsPicker);
  institution.mentorIds = getCheckedValues(institutionMentorsPicker);

  demo14Mentors.forEach((mentor) => {
    if (institution.mentorIds.includes(mentor.id)) {
      mentor.institutionId = institution.id;
    } else if (mentor.institutionId === institution.id) {
      mentor.institutionId = null;
    }
  });

  syncInstitutionMentorLists();
  refreshFeedbackFromMentors();

  populateInstitutionSelect();
  populateMentorInstitutionSelect();
  populateMentorSelect();
  populateFeedbackSelect();

  institutionSelect.value = String(institution.id);
  renderInstitutionForm(institutionMentorSearch.value);
  renderMentorForm();

  demo14Log(
    `Saved institution <strong>${institution.name}</strong> with ${institution.programIds.length} programs and ${institution.mentorIds.length} mentors.`,
  );
});

newInstitutionBtn.addEventListener("click", () => {
  const newId = Math.max(...demo14Institutions.map((item) => item.id), 0) + 1;
  const newInstitution = {
    id: newId,
    name: `New Institution ${newId}`,
    category: "University",
    programIds: [],
    mentorIds: [],
  };

  demo14Institutions.push(newInstitution);
  populateInstitutionSelect();
  populateMentorInstitutionSelect();
  institutionSelect.value = String(newId);
  institutionMentorSearch.value = "";
  renderInstitutionForm();
  demo14Log(`Created <strong>${newInstitution.name}</strong>.`);
});

/* ---------- program functions ---------- */
function populateProgramSelect() {
  programSelect.innerHTML = demo14Programs
    .map(
      (program) =>
        `<option value="${program.id}">${program.name} — ${program.category}</option>`,
    )
    .join("");
}

function getSelectedProgram() {
  return demo14Programs.find((item) => String(item.id) === programSelect.value);
}

function renderProgramFilterPreview() {
  programFilterPreview.innerHTML = demo14Programs
    .map((program) => `<span class="demo14-chip">${program.name}</span>`)
    .join("");
}

function renderProgramForm() {
  const program = getSelectedProgram();
  if (!program) return;

  programName.value = program.name;
  programCategory.value = program.category;
  programDescription.value = program.description;

  const connectedInstitutions = demo14Institutions
    .filter((institution) => institution.programIds.includes(program.id))
    .map((item) => item.name);

  const connectedMentors = demo14Mentors
    .filter((mentor) => mentor.programIds.includes(program.id))
    .map((item) => item.name);

  programSummary.innerHTML = `
    <strong>${program.name}</strong><br>
    Category: ${program.category}<br>
    Description: ${program.description}<br>
    Institutions: ${connectedInstitutions.length ? connectedInstitutions.join(", ") : "None yet"}<br>
    Mentors: ${connectedMentors.length ? connectedMentors.join(", ") : "None yet"}
  `;
}

programSelect.addEventListener("change", renderProgramForm);

saveProgramBtn.addEventListener("click", () => {
  const program = getSelectedProgram();
  if (!program) return;

  program.name = programName.value.trim();
  program.category = programCategory.value.trim();
  program.description = programDescription.value.trim();

  refreshFeedbackFromMentors();

  populateProgramSelect();
  renderProgramFilterPreview();
  renderProgramForm();
  renderMentorForm();
  renderInstitutionForm(
    institutionMentorSearch ? institutionMentorSearch.value : "",
  );
  populateMentorSelect();
  populateFeedbackSelect();

  demo14Log(
    `Saved program <strong>${program.name}</strong>. It now appears in filters across Grads Paths.`,
  );
});

newProgramBtn.addEventListener("click", () => {
  const newId = Math.max(...demo14Programs.map((item) => item.id), 0) + 1;
  const newProgram = {
    id: newId,
    name: `New Program ${newId}`,
    category: "General",
    description: "New program description",
  };

  demo14Programs.push(newProgram);
  populateProgramSelect();
  programSelect.value = String(newId);
  renderProgramFilterPreview();
  renderProgramForm();
  renderMentorProgramPicker([]);
  renderInstitutionForm(
    institutionMentorSearch ? institutionMentorSearch.value : "",
  );
  demo14Log(`Created <strong>${newProgram.name}</strong>.`);
});

/* ---------- service creation functions ---------- */
function toggleCreationMode() {
  const mode = creationMode.value;

  if (mode === "program") {
    programModePanel.style.display = "block";
    serviceModePanel.style.display = "none";
  } else {
    programModePanel.style.display = "none";
    serviceModePanel.style.display = "block";
  }
}

function populateServiceSelect() {
  serviceSelect.innerHTML = getAllServices()
    .map((service) => `<option value="${service}">${service}</option>`)
    .join("");
}

function renderServiceFilterPreview() {
  serviceFilterPreview.innerHTML = getAllServices()
    .map((service) => `<span class="demo14-chip">${service}</span>`)
    .join("");
}

function getSelectedServiceName() {
  return serviceSelect.value;
}

function renderServiceForm() {
  const serviceName = getSelectedServiceName();
  if (!serviceName || !demo14ServiceMeta[serviceName]) return;

  const service = demo14ServiceMeta[serviceName];
  serviceNameInput.value = serviceName;
  servicePriceInput.value = service.originalPrice;

  const pricing = getServicePricing(serviceName);

  serviceSummary.innerHTML = `
    <strong>${serviceName}</strong><br>
    Original Price: ${formatMoney(service.originalPrice)}<br>
    Current Price: ${formatMoney(service.currentPrice)}<br>
    Dollar Difference: ${formatMoney(pricing.dollarOff)}<br>
    Percent Difference Off: ${pricing.percentOff}%
  `;
}

creationMode.addEventListener("change", toggleCreationMode);
serviceSelect.addEventListener("change", renderServiceForm);

saveServiceBtn.addEventListener("click", () => {
  const selectedService = getSelectedServiceName();
  const newName = serviceNameInput.value.trim();
  const newPrice = Math.max(0, Number(servicePriceInput.value || 0));

  if (!selectedService || !demo14ServiceMeta[selectedService]) return;
  if (!newName) {
    alert("Please enter a service name.");
    return;
  }

  const oldMeta = demo14ServiceMeta[selectedService];
  const existingServices = Object.keys(demo14ServiceMeta);

  if (newName !== selectedService && existingServices.includes(newName)) {
    alert("A service with that name already exists.");
    return;
  }

  if (newName !== selectedService) {
    demo14ServiceMeta[newName] = {
      ...oldMeta,
      label: newName,
      previewLabel: newName,
      originalPrice: newPrice,
      currentPrice: newPrice,
    };

    delete demo14ServiceMeta[selectedService];

    demo14Mentors.forEach((mentor) => {
      mentor.services = mentor.services.map((service) =>
        service === selectedService ? newName : service,
      );
    });

    demo14Feedback.forEach((item) => {
      if (item.serviceUsed === selectedService) {
        item.serviceUsed = newName;
      }
    });
  } else {
    demo14ServiceMeta[selectedService].label = newName;
    demo14ServiceMeta[selectedService].previewLabel = newName;
    demo14ServiceMeta[selectedService].originalPrice = newPrice;

    if (demo14ServiceMeta[selectedService].currentPrice > newPrice) {
      demo14ServiceMeta[selectedService].currentPrice = newPrice;
    }
  }

  ensureMentorServiceIntegrity();
  refreshAllServiceUIs();
  populateServiceSelect();
  serviceSelect.value = newName;
  renderServiceForm();

  demo14Log(`Saved service <strong>${newName}</strong>.`);
});

newServiceBtn.addEventListener("click", () => {
  const baseName = "New Service";
  let counter = 1;
  let newName = `${baseName} ${counter}`;

  while (demo14ServiceMeta[newName]) {
    counter += 1;
    newName = `${baseName} ${counter}`;
  }

  demo14ServiceMeta[newName] = {
    label: newName,
    previewLabel: newName,
    originalPrice: 100,
    currentPrice: 100,
    icon: `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 5a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H6a1 1 0 1 1 0-2h5V6a1 1 0 0 1 1-1Z"/>
      </svg>
    `,
  };

  refreshAllServiceUIs();
  populateServiceSelect();
  serviceSelect.value = newName;
  renderServiceForm();

  demo14Log(`Created <strong>${newName}</strong>.`);
});

/* ---------- pricing functions ---------- */
function renderPriceEditorCards(filterText = "") {
  const query = filterText.trim().toLowerCase();
  const services = getAllServices().filter((service) =>
    service.toLowerCase().includes(query),
  );

  priceEditorGrid.innerHTML = services
    .map((service) => {
      const pricing = getServicePricing(service);

      return `
      <div class="demo14-price-editor-card">
        <div class="demo14-price-editor-head">
          <div>
            <div class="demo14-price-editor-title">${service}</div>
            <div class="demo14-price-editor-subtitle">Edit the website display price for this service.</div>
          </div>
          <div class="demo14-price-editor-badge ${pricing.hasDiscount ? "" : "standard"}">
            ${pricing.hasDiscount ? `${pricing.percentOff}% off` : "standard"}
          </div>
        </div>

        <div class="demo14-price-editor-fields">
          <div class="demo14-mini-field">
            <label>Original Price</label>
            <div class="demo14-mini-readonly">${formatMoney(pricing.originalPrice)}</div>
          </div>

          <div class="demo14-mini-field">
            <label>Current Price</label>
            <input
              class="demo14-price-input"
              data-service="${service}"
              type="number"
              min="0"
              step="1"
              value="${pricing.currentPrice}"
            />
          </div>
        </div>

        <div class="demo14-price-editor-fields">
          <div class="demo14-mini-field">
            <label>Dollar Difference</label>
            <div class="demo14-mini-readonly">${formatMoney(pricing.dollarOff)}</div>
          </div>

          <div class="demo14-mini-field">
            <label>Percent Difference Off</label>
            <div class="demo14-mini-readonly">${pricing.percentOff}%</div>
          </div>
        </div>
      </div>
    `;
    })
    .join("");

  priceEditorGrid.querySelectorAll(".demo14-price-input").forEach((input) => {
    input.addEventListener("input", (e) => {
      const service = e.target.dataset.service;
      const value = Math.max(0, Number(e.target.value || 0));
      demo14ServiceMeta[service].currentPrice = value;

      renderPriceEditorCards(servicePricingSearch.value);
      populateDiscountPricingPreview();
      renderPaymentSummary();

      if (getSelectedMentor()) {
        renderMentorServiceCards(getSelectedMentor().services || []);
        renderSelectedServices(getSelectedMentor().services || []);
        renderMentorPreviews(getSelectedMentor());
      }

      renderFeedbackForm();
      renderServiceForm();
    });
  });
}

function populateDiscountPricingPreview() {
  discountPricingPreview.innerHTML = getAllServices()
    .map((service) => {
      const pricing = getServicePricing(service);

      return `
      <div class="demo14-price-card">
        <div class="demo14-price-editor-title">${service}</div>
        <div class="demo14-price-stats">
          <span>Original</span>
          <strong>${formatMoney(pricing.originalPrice)}</strong>

          <span>Current</span>
          <strong>${formatMoney(pricing.currentPrice)}</strong>

          <span>Dollar Difference</span>
          <strong>${formatMoney(pricing.dollarOff)}</strong>

          <span>Percent Difference Off</span>
          <strong>${pricing.percentOff}%</strong>
        </div>
        <div class="demo14-price-bottom">
          <span class="demo14-price-badge ${pricing.hasDiscount ? "discount" : "standard"}">
            ${pricing.hasDiscount ? `${pricing.percentOff}% off` : "standard"}
          </span>
        </div>
      </div>
    `;
    })
    .join("");
}

function populatePaymentServiceSelect() {
  paymentServiceSelect.innerHTML = getAllServices()
    .map((service) => `<option value="${service}">${service}</option>`)
    .join("");
}

function renderPaymentSummary() {
  const services = getAllServices();
  if (!services.length) {
    paymentSummary.innerHTML = "No services available.";
    return;
  }

  const service = paymentServiceSelect.value || services[0];
  const quantity = Math.max(1, Number(paymentQuantity.value || 1));
  const pricing = getServicePricing(service);

  const originalTotal = pricing.originalPrice * quantity;
  const currentTotal = pricing.currentPrice * quantity;
  const savings = originalTotal - currentTotal;

  paymentSummary.innerHTML = `
    <strong>${service}</strong><br>
    Quantity: ${quantity}<br>
    Original Price: ${formatMoney(pricing.originalPrice)} each<br>
    Current Price: ${formatMoney(pricing.currentPrice)} each<br>
    Original Total: ${formatMoney(originalTotal)}<br>
    Current Total: ${formatMoney(currentTotal)}<br>
    Dollar Difference: ${formatMoney(savings)}<br>
    Percent Difference Off: ${pricing.percentOff}%
  `;
}

servicePricingSearch.addEventListener("input", () => {
  renderPriceEditorCards(servicePricingSearch.value);
});

paymentServiceSelect.addEventListener("change", renderPaymentSummary);
paymentQuantity.addEventListener("input", renderPaymentSummary);

savePricingBtn.addEventListener("click", () => {
  populateDiscountPricingPreview();
  renderPaymentSummary();

  if (getSelectedMentor()) {
    renderMentorServiceCards(getSelectedMentor().services || []);
    renderSelectedServices(getSelectedMentor().services || []);
    renderMentorPreviews(getSelectedMentor());
  }

  renderFeedbackForm();
  renderServiceForm();

  demo14Log(
    `Saved service pricing changes across website and payment previews.`,
  );
});

resetPricingBtn.addEventListener("click", () => {
  getAllServices().forEach((service) => {
    demo14ServiceMeta[service].currentPrice =
      demo14ServiceMeta[service].originalPrice;
  });

  renderPriceEditorCards(servicePricingSearch.value);
  populateDiscountPricingPreview();
  renderPaymentSummary();

  if (getSelectedMentor()) {
    renderMentorServiceCards(getSelectedMentor().services || []);
    renderSelectedServices(getSelectedMentor().services || []);
    renderMentorPreviews(getSelectedMentor());
  }

  renderFeedbackForm();
  renderServiceForm();

  demo14Log(`Reset all service prices back to original values.`);
});

/* ---------- search ---------- */
function applyGlobalSearch() {
  const query = globalSearch.value.trim().toLowerCase();

  const filteredMentors = demo14Mentors.filter((mentor) => {
    const institution = getInstitutionById(mentor.institutionId);
    const haystack = [
      mentor.name,
      mentor.email,
      mentor.type,
      mentor.description,
      mentor.officeHours,
      mentor.services.join(" "),
      institution ? institution.name : "",
      getProgramNames(mentor.programIds).join(" "),
    ]
      .join(" ")
      .toLowerCase();

    return haystack.includes(query);
  });

  const filteredUsers = demo14Users.filter((user) => {
    const haystack = [
      user.name,
      user.email,
      String(user.officeHoursCredits),
      String(user.totalSpent),
    ]
      .join(" ")
      .toLowerCase();

    return haystack.includes(query);
  });

  const filteredFeedback = demo14Feedback.filter((item) => {
    const haystack = [
      item.userName,
      item.mentorName,
      item.mentorSchool,
      item.mentorProgram,
      item.serviceUsed,
      item.text,
      item.preparedness,
      item.recommend,
      item.dateOfSession,
    ]
      .join(" ")
      .toLowerCase();

    return haystack.includes(query);
  });

  populateMentorSelect(query ? filteredMentors : demo14Mentors);
  if (mentorSelect.options.length > 0) {
    mentorSelect.value = mentorSelect.options[0].value;
    renderMentorForm();
  }

  populateRefundUsers(query ? filteredUsers : demo14Users);
  if (refundUserSelect.options.length > 0) {
    refundUserSelect.value = refundUserSelect.options[0].value;
    renderRefundUserSummary();
  }

  populateFeedbackSelect(query ? filteredFeedback : demo14Feedback);
  if (feedbackSelect.options.length > 0 && feedbackSelect.value) {
    feedbackSelect.value = feedbackSelect.options[0].value;
    renderFeedbackForm();
  }
}

globalSearch.addEventListener("input", applyGlobalSearch);

/* ---------- boot ---------- */
function initializeDemo14() {
  ensureMentorServiceIntegrity();
  syncInstitutionMentorLists();
  refreshFeedbackFromMentors();

  populateMentorSelect();
  populateMentorInstitutionSelect();
  mentorSelect.value = String(demo14Mentors[1].id);
  renderMentorForm();

  populateRefundUsers();
  refundUserSelect.value = String(demo14Users[0].id);
  renderRefundUserSummary();

  populateFeedbackSelect();
  if (demo14Feedback.length > 0) {
    feedbackSelect.value = String(demo14Feedback[0].id);
    renderFeedbackForm();
  }

  populateInstitutionSelect();
  institutionSelect.value = String(demo14Institutions[0].id);
  institutionMentorSearch.value = "";
  renderInstitutionForm();

  populateProgramSelect();
  programSelect.value = String(demo14Programs[0].id);
  renderProgramFilterPreview();
  renderProgramForm();

  populateServiceSelect();
  if (getAllServices().length) {
    serviceSelect.value = getAllServices()[0];
  }
  renderServiceFilterPreview();
  renderServiceForm();

  creationMode.value = "program";
  toggleCreationMode();

  populatePaymentServiceSelect();
  if (
    Array.from(paymentServiceSelect.options).some(
      (o) => o.value === "Application Review",
    )
  ) {
    paymentServiceSelect.value = "Application Review";
  } else if (paymentServiceSelect.options.length) {
    paymentServiceSelect.value = paymentServiceSelect.options[0].value;
  }

  renderPriceEditorCards();
  populateDiscountPricingPreview();
  renderPaymentSummary();

  demo14Log("Demo14 manual page ready.");
}

initializeDemo14();
