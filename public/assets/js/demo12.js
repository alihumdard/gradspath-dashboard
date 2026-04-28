const loginForm = document.getElementById("loginForm");
const loginScreen = document.getElementById("loginScreen");
const dashboard = document.getElementById("dashboard");
const signOutBtn = document.getElementById("signOutBtn");
const reloadBtn = document.getElementById("reloadBtn");
const showPassword = document.getElementById("showPassword");
const passwordInput = document.getElementById("password");

function readJsonScript(id) {
    const element = document.getElementById(id);
    if (!element) return null;

    try {
        return JSON.parse(element.textContent || "null");
    } catch (error) {
        return null;
    }
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

const adminRevenueData = readJsonScript("adminRevenueData");
const adminOverviewData = readJsonScript("adminOverviewData");
const overviewBookingsChartData = Array.isArray(
    adminOverviewData?.charts?.bookings_over_time,
)
    ? adminOverviewData.charts.bookings_over_time
    : [];
const overviewRevenueChartData = Array.isArray(
    adminOverviewData?.charts?.revenue_over_time,
)
    ? adminOverviewData.charts.revenue_over_time
    : [];
const revenueProgramChartData = Array.isArray(
    adminRevenueData?.charts?.program_revenue,
)
    ? adminRevenueData.charts.program_revenue
    : [];
const revenueTopMentorsChartData = Array.isArray(
    adminRevenueData?.charts?.top_mentors,
)
    ? adminRevenueData.charts.top_mentors
    : [];

let chartsInitialized = false;
const dashboardCharts = [];

if (loginForm && loginScreen && dashboard && passwordInput) {
    loginForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const emailInput = document.getElementById("email");
        const email = emailInput ? emailInput.value.trim() : "";
        const password = passwordInput.value.trim();

        if (!email || !password) return;

        loginScreen.classList.add("hidden");
        dashboard.classList.remove("hidden");

        if (!chartsInitialized) {
            initializeCharts();
            chartsInitialized = true;
        }
    });
}

if (showPassword && passwordInput) {
    showPassword.addEventListener("change", function () {
        passwordInput.type = this.checked ? "text" : "password";
    });
}

if (signOutBtn && dashboard && loginScreen && loginForm && passwordInput) {
    signOutBtn.addEventListener("click", function () {
        dashboard.classList.add("hidden");
        loginScreen.classList.remove("hidden");
        loginForm.reset();
        passwordInput.type = "password";
    });
}

if (reloadBtn) {
    reloadBtn.addEventListener("click", function () {
        window.location.reload();
    });
}

/* USERS FILTERING */
const usersSearch = document.getElementById("usersSearch");
const usersProgramFilter = document.getElementById("usersProgramFilter");
const usersInstitutionFilter = document.getElementById(
    "usersInstitutionFilter",
);
const usersRows = document.querySelectorAll("#usersTable tbody tr");

function filterUsers() {
    if (!usersSearch || !usersProgramFilter || !usersInstitutionFilter) return;

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

if (usersSearch && usersProgramFilter && usersInstitutionFilter) {
    usersSearch.addEventListener("input", filterUsers);
    usersProgramFilter.addEventListener("change", filterUsers);
    usersInstitutionFilter.addEventListener("change", filterUsers);
}

/* MENTORS FILTERING */
const mentorsSearch = document.getElementById("mentorsSearch");
const mentorsProgramFilter = document.getElementById("mentorsProgramFilter");
const mentorsStatusFilter = document.getElementById("mentorsStatusFilter");
const mentorsRows = document.querySelectorAll("#mentorsTable tbody tr");

function filterMentors() {
    if (!mentorsSearch || !mentorsProgramFilter || !mentorsStatusFilter) return;

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

if (mentorsSearch && mentorsProgramFilter && mentorsStatusFilter) {
    mentorsSearch.addEventListener("input", filterMentors);
    mentorsProgramFilter.addEventListener("change", filterMentors);
    mentorsStatusFilter.addEventListener("change", filterMentors);
}

if (
    dashboard &&
    !dashboard.classList.contains("hidden") &&
    !chartsInitialized
) {
    initializeCharts();
    chartsInitialized = true;
}

function getChartTextColor() {
    return "#9aa3b7";
}

function getGridColor() {
    return "rgba(255,255,255,0.08)";
}

function createChartIfCanvas(id, config) {
    const canvas = document.getElementById(id);

    if (!canvas) {
        return;
    }

    dashboardCharts.push(new Chart(canvas, config));
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

    createChartIfCanvas("bookingsChart", {
        type: "line",
        data: {
            labels: overviewBookingsChartData.map((item) => item.label ?? ""),
            datasets: [
                {
                    label: "Bookings",
                    data: overviewBookingsChartData.map(
                        (item) => Number(item?.value ?? 0) || 0,
                    ),
                    borderWidth: 2,
                    tension: 0.35,
                },
            ],
        },
        options: sharedOptions,
    });

    createChartIfCanvas("revenueChart", {
        type: "bar",
        data: {
            labels: overviewRevenueChartData.map((item) => item.label ?? ""),
            datasets: [
                {
                    label: "Revenue",
                    data: overviewRevenueChartData.map(
                        (item) => Number(item?.value ?? 0) || 0,
                    ),
                    borderWidth: 1,
                },
            ],
        },
        options: {
            ...sharedOptions,
            scales: {
                x: {
                    ticks: { color: getChartTextColor() },
                    grid: { color: getGridColor() },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: getChartTextColor(),
                        callback: (value) => `$${value}`,
                    },
                    grid: { color: getGridColor() },
                },
            },
        },
    });

    createChartIfCanvas("servicesChart", {
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

    createChartIfCanvas("programRevenueChart", {
        type: "doughnut",
        data: {
            labels: revenueProgramChartData.map((item) => item.label ?? "Unknown"),
            datasets: [
                {
                    label: "Revenue",
                    data: revenueProgramChartData.map(
                        (item) => Number(item?.value ?? 0) || 0,
                    ),
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

    createChartIfCanvas("topMentorsChart", {
        type: "bar",
        data: {
            labels: revenueTopMentorsChartData.map(
                (item) => item.label ?? "Unknown",
            ),
            datasets: [
                {
                    label: "Revenue",
                    data: revenueTopMentorsChartData.map(
                        (item) => Number(item?.value ?? 0) || 0,
                    ),
                    borderWidth: 1,
                    minBarLength: 4,
                },
            ],
        },
        options: {
            ...sharedOptions,
            indexAxis: "y",
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        color: getChartTextColor(),
                        callback: (value) => `$${value}`,
                    },
                    grid: { color: getGridColor() },
                },
                y: {
                    ticks: {
                        color: getChartTextColor(),
                        autoSkip: false,
                    },
                    grid: { color: getGridColor() },
                },
            },
        },
    });
}

/* ======================================================== */
/* DEMO14 FUNCTIONALITY - MERGED FROM Demo14.js            */
/* ======================================================== */

document.addEventListener("DOMContentLoaded", () => {
    if (usersSearch && usersProgramFilter && usersInstitutionFilter) {
        usersSearch.addEventListener("input", filterUsers);
        usersProgramFilter.addEventListener("change", filterUsers);
        usersInstitutionFilter.addEventListener("change", filterUsers);
    }

    if (mentorsSearch && mentorsProgramFilter && mentorsStatusFilter) {
        mentorsSearch.addEventListener("input", filterMentors);
        mentorsProgramFilter.addEventListener("change", filterMentors);
        mentorsStatusFilter.addEventListener("change", filterMentors);
    }

    // Mobile sidebar toggle
    const menuBtn =
        document.getElementById("mobileMenuToggle") ||
        document.getElementById("adminMenuToggle");
    const overlay =
        document.getElementById("sidebarOverlay") ||
        document.getElementById("adminSidebarOverlay");
    const shell =
        document.querySelector(".app-shell") ||
        document.querySelector(".dashboard");

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

const adminManualData = window.adminManualData || {};
const cloneAdminData = (value) => JSON.parse(JSON.stringify(value));

const fallbackDemo14ServiceMeta = {
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

const knownServiceIcons = Object.fromEntries(
    Object.entries(fallbackDemo14ServiceMeta).map(([name, meta]) => [
        name,
        meta.icon,
    ]),
);

const genericServiceIcon = `
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 5a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H6a1 1 0 1 1 0-2h5V6a1 1 0 0 1 1-1Z"/>
      </svg>
    `;

const demo14ServiceMeta =
    adminManualData.serviceMeta &&
    Object.keys(adminManualData.serviceMeta).length > 0
        ? Object.fromEntries(
              Object.entries(cloneAdminData(adminManualData.serviceMeta)).map(
                  ([name, meta]) => [
                      name,
                      {
                          label: meta.label || name,
                          previewLabel: meta.previewLabel || name,
                          originalPrice: Number(meta.originalPrice || 0),
                          currentPrice: Number(
                              meta.currentPrice ?? meta.originalPrice ?? 0,
                          ),
                          icon: knownServiceIcons[name] || genericServiceIcon,
                      },
                  ],
              ),
          )
        : fallbackDemo14ServiceMeta;

function getAllServices() {
    return Object.keys(demo14ServiceMeta);
}

const fallbackDemo14Programs = [
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

const demo14Programs =
    adminManualData.programs && adminManualData.programs.length > 0
        ? cloneAdminData(adminManualData.programs)
        : fallbackDemo14Programs;

const fallbackDemo14Institutions = [
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

const demo14Institutions =
    adminManualData.institutions && adminManualData.institutions.length > 0
        ? cloneAdminData(adminManualData.institutions)
        : fallbackDemo14Institutions;

const fallbackDemo14Mentors = [
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

const demo14Mentors =
    adminManualData.mentors && adminManualData.mentors.length > 0
        ? cloneAdminData(adminManualData.mentors)
        : fallbackDemo14Mentors;

const fallbackDemo14Users = [
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

const demo14Users =
    adminManualData.users && adminManualData.users.length > 0
        ? cloneAdminData(adminManualData.users)
        : fallbackDemo14Users;

const fallbackDemo14Feedback = [
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

const demo14Feedback =
    adminManualData.feedback && adminManualData.feedback.length > 0
        ? cloneAdminData(adminManualData.feedback)
        : fallbackDemo14Feedback;

/* ---------- DOM ---------- */
const manualActionsRoot = document.getElementById("manual");
const initialManualStation =
    manualActionsRoot?.dataset.initialStation || "mentor-station";
const demo14Tabs = manualActionsRoot
    ? manualActionsRoot.querySelectorAll(".demo14-station-tab")
    : [];
const demo14Panels = manualActionsRoot
    ? manualActionsRoot.querySelectorAll(".demo14-station-panel")
    : [];
const globalSearch = manualActionsRoot
    ? manualActionsRoot.querySelector("#globalSearch")
    : null;

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
    if (!activityLog) return;

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
function setActiveManualStation(stationId) {
    if (!manualActionsRoot) return;

    const tabs = manualActionsRoot.querySelectorAll(".demo14-station-tab");
    const panels = manualActionsRoot.querySelectorAll(".demo14-station-panel");
    const targetPanel = manualActionsRoot.querySelector(`#${stationId}`);

    tabs.forEach((tab) => {
        tab.classList.toggle("active", tab.dataset.station === stationId);
    });

    panels.forEach((panel) => {
        const isActive = panel.id === stationId;
        panel.classList.toggle("active", isActive);
        panel.style.display = isActive ? "block" : "none";
    });

    if (targetPanel) {
        targetPanel.classList.add("active");
        targetPanel.style.display = "block";
    }
}

demo14Tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
        setActiveManualStation(tab.dataset.station);
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
    previewSchool.textContent = institution
        ? institution.name
        : "No institution";
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
              .map(
                  (name) =>
                      `<div class="mentor-preview-service-pill">${name}</div>`,
              )
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

if (mentorSelect && mentorInstitution && saveMentorBtn) {
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
}

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
    const user = demo14Users.find(
        (u) => String(u.id) === refundUserSelect.value,
    );
    if (!user) return;

    refundUserSummary.innerHTML = `
    <strong>${user.name}</strong><br>
    Email: ${user.email}<br>
    Office Hours Credits: ${user.officeHoursCredits}<br>
    Total Spent: ${formatMoney(user.totalSpent)}<br>
    Last Refund: ${formatMoney(user.lastRefund)}
  `;
}

if (refundUserSelect && applyRefundBtn) {
    refundUserSelect.addEventListener("change", renderRefundUserSummary);

    applyRefundBtn.addEventListener("click", () => {
        const user = demo14Users.find(
            (u) => String(u.id) === refundUserSelect.value,
        );
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
}

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
        .map((service) =>
            createServiceCard(service, service === selectedService),
        )
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

if (feedbackSelect && applyFeedbackBtn) {
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
}

/* ---------- institution station ---------- */
function populateInstitutionSelect() {
    if (!institutionSelect) return;

    institutionSelect.innerHTML = demo14Institutions
        .map(
            (item) =>
                `<option value="${item.id}">${item.name} — ${item.category}</option>`,
        )
        .join("");
}

function getSelectedInstitution() {
    if (!institutionSelect) return null;

    return demo14Institutions.find(
        (item) => String(item.id) === institutionSelect.value,
    );
}

function renderInstitutionForm(mentorFilter = "") {
    if (
        !institutionSelect ||
        !institutionCategory ||
        !institutionProgramsPicker ||
        !institutionMentorsPicker ||
        !institutionSummary
    ) {
        return;
    }

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
        (mentor) =>
            getProgramNames(mentor.programIds).join(", ") || "No programs",
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

if (
    institutionSelect &&
    institutionMentorSearch &&
    institutionCategory &&
    institutionProgramsPicker &&
    institutionMentorsPicker &&
    institutionSummary &&
    saveInstitutionBtn &&
    newInstitutionBtn
) {
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
        setActiveManualStation("institution-station");

        demo14Log(
            `Saved institution <strong>${institution.name}</strong> with ${institution.programIds.length} programs and ${institution.mentorIds.length} mentors.`,
        );
    });

    newInstitutionBtn.addEventListener("click", () => {
        const newId =
            Math.max(...demo14Institutions.map((item) => item.id), 0) + 1;
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
        setActiveManualStation("institution-station");
        demo14Log(`Created <strong>${newInstitution.name}</strong>.`);
    });
}

/* ---------- program functions ---------- */
function populateProgramSelect() {
    if (!programSelect) return;

    programSelect.innerHTML = demo14Programs
        .map(
            (program) =>
                `<option value="${program.id}">${program.name} — ${program.category}</option>`,
        )
        .join("");
}

function getSelectedProgram() {
    if (!programSelect) return null;

    return demo14Programs.find(
        (item) => String(item.id) === programSelect.value,
    );
}

function renderProgramFilterPreview() {
    if (!programFilterPreview) return;

    programFilterPreview.innerHTML = demo14Programs
        .map((program) => `<span class="demo14-chip">${program.name}</span>`)
        .join("");
}

function renderProgramForm() {
    if (
        !programName ||
        !programCategory ||
        !programDescription ||
        !programSummary
    ) {
        return;
    }

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

if (programSelect) {
    programSelect.addEventListener("change", renderProgramForm);
}

if (saveProgramBtn) {
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
}

if (newProgramBtn) {
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
}

/* ---------- service creation functions ---------- */
function toggleCreationMode() {
    if (!creationMode || !programModePanel || !serviceModePanel) {
        return;
    }

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

if (creationMode) {
    creationMode.addEventListener("change", toggleCreationMode);
}

if (serviceSelect) {
    serviceSelect.addEventListener("change", renderServiceForm);
}

if (saveServiceBtn) {
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
}

if (newServiceBtn) {
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
}

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

if (
    servicePricingSearch &&
    paymentServiceSelect &&
    paymentQuantity &&
    savePricingBtn &&
    resetPricingBtn
) {
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
}

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

if (globalSearch) {
    globalSearch.addEventListener("input", applyGlobalSearch);
}

/* ---------- boot ---------- */
function initializeDemo14() {
    ensureMentorServiceIntegrity();
    syncInstitutionMentorLists();
    refreshFeedbackFromMentors();

    populateMentorSelect();
    populateMentorInstitutionSelect();
    if (demo14Mentors.length > 0) {
        mentorSelect.value = String(demo14Mentors[0].id);
        renderMentorForm();
    }

    populateRefundUsers();
    if (demo14Users.length > 0) {
        refundUserSelect.value = String(demo14Users[0].id);
        renderRefundUserSummary();
    }

    populateFeedbackSelect();
    if (demo14Feedback.length > 0) {
        feedbackSelect.value = String(demo14Feedback[0].id);
        renderFeedbackForm();
    }

    if (institutionSelect && institutionMentorSearch) {
        populateInstitutionSelect();
        if (demo14Institutions.length > 0) {
            institutionSelect.value = String(demo14Institutions[0].id);
            institutionMentorSearch.value = "";
            renderInstitutionForm();
        }
    }

    if (programSelect) {
        populateProgramSelect();
        renderProgramFilterPreview();
        if (demo14Programs.length > 0) {
            programSelect.value = String(demo14Programs[0].id);
            renderProgramForm();
        }
    }

    if (serviceSelect) {
        populateServiceSelect();
        if (getAllServices().length) {
            serviceSelect.value = getAllServices()[0];
        }
        renderServiceFilterPreview();
        renderServiceForm();
    }

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

    setActiveManualStation(initialManualStation);
    demo14Log("Demo14 manual page ready.");
}

if (document.getElementById("manual") && document.querySelector(".demo14-page-wrap")) {
    initializeDemo14();
}

const adminManualActionsData = readJsonScript("adminManualActionsData");

function money(value) {
    const numeric = Number(value ?? 0);
    return Number.isFinite(numeric)
        ? new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD",
          }).format(numeric)
        : "-";
}

function renderSummary(element, rows) {
    if (!element) return;

    element.innerHTML = `<div class="manual-summary__meta">${rows
        .map(
            (row) =>
                `<div><strong>${row.label}</strong><div>${row.value}</div></div>`,
        )
        .join("")}</div>`;
}

function initializeManualActionsHub() {
    const app = document.getElementById("manualActionsApp");

    if (!app || !adminManualActionsData) {
        return;
    }

    const initialSection = app.dataset.initialSection || "mentor";
    const buttons = Array.from(
        app.querySelectorAll("[data-section-target]"),
    );
    const panels = Array.from(
        app.querySelectorAll("[data-section-panel]"),
    );
    const groups = Array.from(
        app.querySelectorAll("[data-section-group]"),
    );

    function setSection(section) {
        buttons.forEach((button) => {
            button.classList.toggle(
                "is-active",
                button.dataset.sectionTarget === section,
            );
        });

        panels.forEach((panel) => {
            panel.classList.toggle(
                "is-active",
                panel.dataset.sectionPanel === section,
            );
        });

        groups.forEach((group) => {
            const sections = (group.dataset.sectionGroup || "")
                .split(/\s+/)
                .filter(Boolean);

            group.classList.toggle("is-active", sections.includes(section));
        });
    }

    buttons.forEach((button) => {
        button.addEventListener("click", () => {
            setSection(button.dataset.sectionTarget || "mentor");
        });
    });

    const mentors = Array.isArray(adminManualActionsData.mentors)
        ? adminManualActionsData.mentors
        : [];
    const users = Array.isArray(adminManualActionsData.users)
        ? adminManualActionsData.users
        : [];
    const services = Array.isArray(adminManualActionsData.services)
        ? adminManualActionsData.services
        : [];
    const feedbackItems = Array.isArray(adminManualActionsData.feedback)
        ? adminManualActionsData.feedback
        : [];

    const mentorSelect = document.getElementById("manualMentorSelect");
    const mentorSummary = document.getElementById("manualMentorSummary");
    const userSelect = document.getElementById("manualUserSelect");
    const userSummary = document.getElementById("manualUserSummary");
    const pricingSelect = document.getElementById("manualPricingServiceSelect");
    const pricingSummary = document.getElementById("manualPricingSummary");
    const feedbackSelect = document.getElementById("manualFeedbackSelect");
    const feedbackSummary = document.getElementById("manualFeedbackSummary");

    function syncMentorSummary() {
        const mentor = mentors.find(
            (item) => String(item.id) === String(mentorSelect?.value || ""),
        );

        if (!mentor) {
            return;
        }

        renderSummary(mentorSummary, [
            { label: "Mentor", value: `${mentor.name} (${mentor.email})` },
            { label: "Current status", value: mentor.status },
            { label: "Institution", value: mentor.institution },
            {
                label: "Services",
                value: mentor.services.length ? mentor.services.join(", ") : "-",
            },
            { label: "Description", value: mentor.description || "-" },
        ]);
    }

    function syncUserSummary() {
        const user = users.find(
            (item) => String(item.id) === String(userSelect?.value || ""),
        );

        if (!user) {
            return;
        }

        renderSummary(userSummary, [
            { label: "User", value: `${user.name} (${user.email})` },
            { label: "Current balance", value: `${user.credits} credits` },
        ]);
    }

    function syncPricingSummary() {
        const service = services.find(
            (item) => String(item.id) === String(pricingSelect?.value || ""),
        );

        if (!service) {
            return;
        }

        renderSummary(pricingSummary, [
            { label: "Service", value: service.name },
            {
                label: "1 on 1",
                value: `${money(service.price_1on1)} | Admin ${money(service.platform_fee_1on1)} | Mentor ${money(service.mentor_payout_1on1)}`,
            },
            {
                label: "1 on 3",
                value: `${money(service.price_1on3_total)} | Admin ${money(service.platform_fee_1on3)} | Mentor ${money(service.mentor_payout_1on3)}`,
            },
            {
                label: "1 on 5",
                value: `${money(service.price_1on5_total)} | Admin ${money(service.platform_fee_1on5)} | Mentor ${money(service.mentor_payout_1on5)}`,
            },
            {
                label: "Office hours",
                value: `${money(service.office_hours_subscription_price)} | Mentor ${money(service.office_hours_mentor_payout_per_attendee)} / attendee`,
            },
        ]);
    }

    function syncFeedbackSummary() {
        const item = feedbackItems.find(
            (feedback) =>
                String(feedback.id) === String(feedbackSelect?.value || ""),
        );

        if (!item) {
            return;
        }

        renderSummary(feedbackSummary, [
            { label: "Mentor", value: item.mentor_name },
            { label: "Student", value: item.student_name },
            { label: "School", value: item.mentor_school },
            { label: "Service", value: item.service_name },
            { label: "Stars", value: `${item.stars}/5` },
            { label: "Visible", value: item.is_visible ? "Yes" : "No" },
            { label: "Comment", value: item.comment || "-" },
            { label: "Admin note", value: item.admin_note || "-" },
        ]);
    }

    mentorSelect?.addEventListener("change", syncMentorSummary);
    userSelect?.addEventListener("change", syncUserSummary);
    pricingSelect?.addEventListener("change", syncPricingSummary);
    feedbackSelect?.addEventListener("change", syncFeedbackSummary);

    function initializeUniversityPicker() {
        const picker = app.querySelector("[data-university-picker]");
        const searchInput = picker?.querySelector("[data-university-search]");
        const idInput = picker?.querySelector("[data-university-id]");
        const results = picker?.querySelector("[data-university-results]");
        const searchUrl = picker?.dataset.searchUrl;

        if (!picker || !searchInput || !idInput || !results || !searchUrl) {
            return;
        }

        let currentQuery = "";
        let nextPage = null;
        let selectedLabel = "";
        let requestToken = 0;
        let debounceTimer = null;

        function hideResults() {
            results.hidden = true;
        }

        function showResults() {
            results.hidden = false;
        }

        function universityMeta(university) {
            return [university.country, university.state_province]
                .filter(Boolean)
                .join(" · ");
        }

        function renderUniversities(universities, append = false) {
            const html = universities.length
                ? universities
                      .map(
                          (university) => `
                            <button class="manual-picker-option" type="button" data-university-option="${escapeHtml(university.id)}" data-university-label="${escapeHtml(university.label)}">
                                <strong>${escapeHtml(university.label)}</strong>
                                <small>${escapeHtml(universityMeta(university))}</small>
                            </button>
                        `,
                      )
                      .join("")
                : '<div class="manual-picker-empty">No universities found</div>';

            results.innerHTML = append ? results.innerHTML + html : html;

            if (nextPage) {
                results.insertAdjacentHTML(
                    "beforeend",
                    '<button class="manual-picker-load" type="button" data-university-load-more>Load more universities</button>',
                );
            }

            showResults();
        }

        async function fetchUniversities({ query = "", page = 1, selectedId = "" } = {}) {
            const token = ++requestToken;
            const url = new URL(searchUrl, window.location.origin);

            if (query) url.searchParams.set("q", query);
            if (page > 1) url.searchParams.set("page", String(page));
            if (selectedId) url.searchParams.set("selected_id", selectedId);

            const response = await fetch(url.toString(), {
                headers: { Accept: "application/json" },
            });

            if (!response.ok || token !== requestToken) {
                return null;
            }

            return response.json();
        }

        async function searchUniversities(query = "", page = 1, append = false) {
            currentQuery = query;
            const payload = await fetchUniversities({ query, page });

            if (!payload) return;

            nextPage = payload.next_page;
            renderUniversities(Array.isArray(payload.data) ? payload.data : [], append);
        }

        async function hydrateSelectedUniversity() {
            if (!idInput.value) return;

            const payload = await fetchUniversities({ selectedId: idInput.value });
            const university = payload?.data?.[0];

            if (!university) return;

            selectedLabel = university.label;
            searchInput.value = university.label;
        }

        searchInput.addEventListener("focus", () => {
            searchUniversities(searchInput.value.trim());
        });

        searchInput.addEventListener("input", () => {
            if (searchInput.value !== selectedLabel) {
                selectedLabel = "";
                idInput.value = "";
            }

            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                searchUniversities(searchInput.value.trim());
            }, 220);
        });

        results.addEventListener("click", (event) => {
            const option = event.target.closest("[data-university-option]");
            const loadMore = event.target.closest("[data-university-load-more]");

            if (option) {
                idInput.value = option.dataset.universityOption || "";
                selectedLabel = option.dataset.universityLabel || "";
                searchInput.value = selectedLabel;
                hideResults();
                return;
            }

            if (loadMore && nextPage) {
                loadMore.remove();
                searchUniversities(currentQuery, nextPage, true);
            }
        });

        document.addEventListener("click", (event) => {
            if (!picker.contains(event.target)) {
                hideResults();
            }
        });

        hydrateSelectedUniversity();
    }

    initializeUniversityPicker();

    const serviceCreateForm = document.getElementById("manualServiceCreateForm");

    if (serviceCreateForm) {
        const sessionTypes = ["1on1", "1on3", "1on5"];

        function syncServiceSessionFields() {
            sessionTypes.forEach((sessionType) => {
                const toggle = serviceCreateForm.querySelector(
                    `[data-session-toggle="${sessionType}"]`,
                );
                const enabled = Boolean(toggle?.checked);
                const fields = serviceCreateForm.querySelectorAll(
                    `[data-session-field="${sessionType}"]`,
                );
                const inputs = serviceCreateForm.querySelectorAll(
                    `[data-session-input="${sessionType}"]`,
                );

                fields.forEach((field) => {
                    field.classList.toggle("is-disabled", !enabled);
                });

                inputs.forEach((input) => {
                    input.disabled = !enabled;

                    if (!enabled) {
                        input.value = "";
                    }
                });
            });
        }

        sessionTypes.forEach((sessionType) => {
            const toggle = serviceCreateForm.querySelector(
                `[data-session-toggle="${sessionType}"]`,
            );

            toggle?.addEventListener("change", syncServiceSessionFields);
        });

        syncServiceSessionFields();
    }

    syncMentorSummary();
    syncUserSummary();
    syncPricingSummary();
    syncFeedbackSummary();
    setSection(initialSection);
}

initializeManualActionsHub();

(function initializeAdminBookingButtons() {
    const modal = document.getElementById("adminBookingsModal");
    const triggers = document.querySelectorAll(".admin-bookings-trigger");

    if (!modal || triggers.length === 0) {
        return;
    }

    const titleEl = document.getElementById("adminBookingsModalTitle");
    const subtitleEl = document.getElementById("adminBookingsModalSubtitle");
    const statusEl = document.getElementById("adminBookingsModalStatus");
    const closeBtn = document.getElementById("adminBookingsModalClose");
    const loadingEl = document.getElementById("adminBookingsLoading");
    const errorEl = document.getElementById("adminBookingsError");
    const emptyEl = document.getElementById("adminBookingsEmpty");
    const listEl = document.getElementById("adminBookingsList");
    const listView = document.getElementById("adminBookingsListView");
    const editView = document.getElementById("adminBookingEditView");
    const deleteView = document.getElementById("adminBookingDeleteView");
    const editLabelEl = document.getElementById("adminBookingEditLabel");
    const deleteLabelEl = document.getElementById("adminBookingDeleteLabel");
    const editForm = document.getElementById("adminBookingEditForm");
    const deleteForm = document.getElementById("adminBookingDeleteForm");
    const deleteReasonField = document.getElementById(
        "adminBookingDeleteReasonField",
    );
    const editBackBtn = document.getElementById("adminBookingEditBack");
    const deleteBackBtn = document.getElementById("adminBookingDeleteBack");
    const editCancelBtn = document.getElementById("adminBookingEditCancel");
    const deleteCancelBtn = document.getElementById("adminBookingDeleteCancel");
    const sessionAtInput = document.getElementById("adminBookingSessionAt");
    const timezoneInput = document.getElementById("adminBookingSessionTimezone");
    const durationInput = document.getElementById("adminBookingDuration");
    const meetingTypeInput = document.getElementById("adminBookingMeetingType");
    const meetingLinkInput = document.getElementById("adminBookingMeetingLink");
    const statusInput = document.getElementById("adminBookingStatusField");
    const approvalStatusInput = document.getElementById(
        "adminBookingApprovalStatus",
    );
    const outcomeInput = document.getElementById("adminBookingOutcome");
    const completionSourceInput = document.getElementById(
        "adminBookingCompletionSource",
    );
    const outcomeNoteInput = document.getElementById("adminBookingOutcomeNote");
    const adminNoteInput = document.getElementById("adminBookingAdminNote");
    const deleteReasonInput = document.getElementById("adminBookingDeleteReason");
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const state = {
        trigger: null,
        entityType: "",
        entityId: "",
        entityLabel: "",
        preferredMode: "edit",
        directDeleteUrl: "",
        directDeleteKind: "",
        selectedBookingId: null,
        options: {},
        bookings: [],
    };

    function replaceTokens(template, values) {
        return Object.entries(values).reduce(
            (carry, [token, value]) => carry.replaceAll(token, String(value)),
            template,
        );
    }

    function routeFromTemplate(templateName, values) {
        const template = modal.dataset[templateName] || "";
        return replaceTokens(template, values);
    }

    function titleCaseKind(kind) {
        if (!kind) {
            return "Item";
        }

        return String(kind).charAt(0).toUpperCase() + String(kind).slice(1);
    }

    function setStatus(message = "", type = "success") {
        if (!statusEl) {
            return;
        }

        if (!message) {
            statusEl.textContent = "";
            statusEl.className = "admin-bookings-modal__status hidden";
            return;
        }

        statusEl.textContent = message;
        statusEl.className = `admin-bookings-modal__status admin-bookings-modal__status--${type}`;
    }

    function setError(message = "") {
        if (!errorEl) {
            return;
        }

        errorEl.textContent = message;
        errorEl.classList.toggle("hidden", !message);
    }

    function setLoading(isLoading) {
        loadingEl?.classList.toggle("hidden", !isLoading);
    }

    function setModalOpen(isOpen) {
        if (typeof modal.showModal === "function") {
            if (isOpen && !modal.open) {
                modal.showModal();
            } else if (!isOpen && modal.open) {
                modal.close();
            }
        }

        modal.hidden = !isOpen;
        modal.classList.toggle("hidden", !isOpen);
        modal.classList.toggle("open", isOpen);
        modal.style.display = isOpen ? "flex" : "none";
        document.body.classList.toggle("admin-modal-open", isOpen);
    }

    function showView(name) {
        listView?.classList.toggle("hidden", name !== "list");
        editView?.classList.toggle("hidden", name !== "edit");
        deleteView?.classList.toggle("hidden", name !== "delete");
    }

    function clearFormErrors(form) {
        form?.querySelectorAll("[data-error-for]").forEach((node) => {
            node.textContent = "";
        });
    }

    function applyFormErrors(form, errors = {}) {
        Object.entries(errors).forEach(([field, messages]) => {
            const target = form?.querySelector(`[data-error-for="${field}"]`);
            if (target) {
                target.textContent = Array.isArray(messages)
                    ? messages[0]
                    : String(messages || "");
            }
        });
    }

    function renderSelectOptions(select, options, selectedValue) {
        if (!select) {
            return;
        }

        select.innerHTML = Object.entries(options || {})
            .map(
                ([value, label]) =>
                    `<option value="${escapeHtml(value)}"${String(selectedValue) === String(value) ? " selected" : ""}>${escapeHtml(label)}</option>`,
            )
            .join("");
    }

    function bookingById(bookingId) {
        return state.bookings.find(
            (booking) => Number(booking.id) === Number(bookingId),
        );
    }

    function updateRowCounts() {
        if (!state.trigger) {
            return;
        }

        const count = state.bookings.length;
        const row = state.trigger.closest("tr");

        row?.querySelectorAll("[data-booking-count-cell]").forEach((node) => {
            node.textContent = String(count);
        });

        row?.querySelectorAll("[data-booking-count-label]").forEach((node) => {
            node.textContent = String(count);
        });

        row?.querySelectorAll(".admin-bookings-trigger").forEach((button) => {
            button.dataset.bookingCount = String(count);
            button.disabled = count < 1;
        });
    }

    function renderList() {
        if (!listEl || !emptyEl) {
            return;
        }

        if (state.bookings.length < 1) {
            listEl.innerHTML = "";
            emptyEl.classList.remove("hidden");
            return;
        }

        emptyEl.classList.add("hidden");
        listEl.innerHTML = state.bookings
            .map(
                (booking) => `
                    <article class="admin-bookings-item" data-booking-id="${escapeHtml(booking.id)}">
                        <div class="admin-bookings-item__content">
                            <div class="admin-bookings-item__title-row">
                                <h4>#${escapeHtml(booking.id)} · ${escapeHtml(booking.service_name)}</h4>
                                <div class="admin-bookings-item__chips">
                                    <span class="admin-bookings-chip">${escapeHtml(booking.status_label)}</span>
                                    <span class="admin-bookings-chip admin-bookings-chip--muted">${escapeHtml(booking.session_outcome_label)}</span>
                                </div>
                            </div>
                            <p>${escapeHtml(booking.student_name)} · ${escapeHtml(booking.mentor_name)}</p>
                            <p>${escapeHtml(booking.session_at_display)} · ${escapeHtml(booking.session_timezone)}</p>
                        </div>
                        <div class="admin-bookings-item__actions">
                            <button class="ghost-btn admin-bookings-item__button" type="button" data-booking-edit="${escapeHtml(booking.id)}"${booking.can_edit ? "" : " disabled"}>Edit</button>
                            <button class="ghost-btn admin-bookings-item__button admin-bookings-item__button--danger" type="button" data-booking-delete="${escapeHtml(booking.id)}"${booking.can_cancel ? "" : " disabled"}>Delete</button>
                        </div>
                    </article>
                `,
            )
            .join("");
    }

    function populateEditForm(booking) {
        if (!booking) {
            return;
        }

        if (editLabelEl) {
            editLabelEl.textContent = `Update booking #${booking.id} for ${booking.student_name} with ${booking.mentor_name}.`;
        }

        sessionAtInput.value = booking.session_at_input || "";
        timezoneInput.value = booking.session_timezone || "";
        durationInput.value = String(booking.duration_minutes ?? 60);
        meetingLinkInput.value = booking.meeting_link || "";
        outcomeNoteInput.value = booking.session_outcome_note || "";
        adminNoteInput.value = "";

        renderSelectOptions(
            meetingTypeInput,
            state.options.meeting_types,
            booking.meeting_type,
        );
        renderSelectOptions(statusInput, state.options.statuses, booking.status);
        renderSelectOptions(
            approvalStatusInput,
            state.options.approval_statuses,
            booking.approval_status,
        );
        renderSelectOptions(
            outcomeInput,
            state.options.session_outcomes,
            booking.session_outcome,
        );
        renderSelectOptions(
            completionSourceInput,
            state.options.completion_sources,
            booking.completion_source,
        );
    }

    function openEditView(bookingId) {
        const booking = bookingById(bookingId);
        if (!booking) {
            return;
        }

        state.selectedBookingId = booking.id;
        clearFormErrors(editForm);
        populateEditForm(booking);
        setStatus("");
        showView("edit");
    }

    function openDeleteView(bookingId) {
        if (!bookingId && state.directDeleteUrl) {
            state.selectedBookingId = null;
            clearFormErrors(deleteForm);
            deleteReasonInput.value = "";
            deleteReasonInput.required = false;
            deleteReasonField?.classList.add("hidden");

            if (deleteLabelEl) {
                deleteLabelEl.textContent = `Delete ${state.entityLabel} and all related data. This action cannot be undone.`;
            }

            const deleteHeading = deleteView?.querySelector("h4");
            if (deleteHeading) {
                deleteHeading.textContent = `Delete ${state.directDeleteKind || "item"}`;
            }

            setStatus("");
            showView("delete");
            return;
        }

        const booking = bookingById(bookingId);
        if (!booking) {
            return;
        }

        state.selectedBookingId = booking.id;
        clearFormErrors(deleteForm);
        deleteReasonInput.value = "";
        deleteReasonInput.required = true;
        deleteReasonField?.classList.remove("hidden");

        if (deleteLabelEl) {
            deleteLabelEl.textContent = `Cancel booking #${booking.id} for ${booking.student_name} with ${booking.mentor_name}.`;
        }

        const deleteHeading = deleteView?.querySelector("h4");
        if (deleteHeading) {
            deleteHeading.textContent = "Delete booking";
        }

        setStatus("");
        showView("delete");
    }

    async function loadBookings(trigger) {
        state.trigger = trigger;
        state.entityType = trigger.dataset.entityType || "";
        state.entityId = trigger.dataset.entityId || "";
        state.entityLabel = trigger.dataset.entityLabel || "this row";
        state.preferredMode = trigger.dataset.bookingMode || "edit";
        state.directDeleteUrl = trigger.dataset.directDeleteUrl || "";
        state.directDeleteKind = trigger.dataset.directDeleteKind || "";
        state.selectedBookingId = null;
        state.bookings = [];

        if (state.preferredMode === "delete" && state.directDeleteUrl) {
            if (titleEl) {
                titleEl.textContent = `Delete ${state.directDeleteKind || "item"}`;
            }

            if (subtitleEl) {
                subtitleEl.textContent = `Confirm deletion for ${state.entityLabel}.`;
            }

            setStatus("");
            setError("");
            renderList();
            showView("list");
            setModalOpen(true);
            openDeleteView(null);
            return;
        }

        if (titleEl) {
            titleEl.textContent =
                state.preferredMode === "delete"
                    ? "Delete Bookings"
                    : "Edit Bookings";
        }

        if (subtitleEl) {
            subtitleEl.textContent = `Loading bookings for ${state.entityLabel}…`;
        }

        setStatus("");
        setError("");
        setLoading(true);
        renderList();
        showView("list");
        setModalOpen(true);

        try {
            const response = await fetch(
                routeFromTemplate("relatedUrlTemplate", {
                    "__ENTITY_TYPE__": state.entityType,
                    "__ENTITY_ID__": state.entityId,
                }),
                {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                },
            );
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || "Unable to load bookings.");
            }

            state.options = payload.options || {};
            state.bookings = Array.isArray(payload.bookings)
                ? payload.bookings
                : [];

            if (titleEl) {
                titleEl.textContent = `${
                    state.preferredMode === "delete"
                        ? "Delete Bookings"
                        : "Edit Bookings"
                } · ${payload.entity?.label || state.entityLabel}`;
            }

            if (subtitleEl) {
                const count = state.bookings.length;
                subtitleEl.textContent = `${count} related booking${count === 1 ? "" : "s"} ready to manage.`;
            }

            renderList();
            updateRowCounts();

            if (state.bookings.length === 1) {
                const booking = state.bookings[0];
                if (state.preferredMode === "delete") {
                    openDeleteView(booking.id);
                } else {
                    openEditView(booking.id);
                }
            }
        } catch (error) {
            setError(error.message || "Unable to load bookings.");
        } finally {
            setLoading(false);
        }
    }

    async function submitEdit(event) {
        event.preventDefault();

        if (!state.selectedBookingId) {
            return;
        }

        clearFormErrors(editForm);
        setStatus("");

        const submitButton = document.getElementById("adminBookingEditSubmit");
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(
                routeFromTemplate("updateUrlTemplate", {
                    "__BOOKING_ID__": state.selectedBookingId,
                }),
                {
                    method: "PATCH",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({
                        session_at: sessionAtInput.value,
                        session_timezone: timezoneInput.value,
                        duration_minutes: durationInput.value,
                        meeting_link: meetingLinkInput.value,
                        meeting_type: meetingTypeInput.value,
                        status: statusInput.value,
                        approval_status: approvalStatusInput.value,
                        session_outcome: outcomeInput.value,
                        completion_source: completionSourceInput.value,
                        session_outcome_note: outcomeNoteInput.value,
                        admin_note: adminNoteInput.value,
                    }),
                },
            );
            const payload = await response.json();

            if (!response.ok) {
                if (response.status === 422) {
                    applyFormErrors(editForm, payload.errors || {});
                }

                throw new Error(payload.message || "Unable to update booking.");
            }

            state.bookings = state.bookings.map((booking) =>
                Number(booking.id) === Number(payload.booking?.id)
                    ? payload.booking
                    : booking,
            );
            renderList();
            updateRowCounts();
            showView("list");
            setStatus(payload.message || "Booking updated successfully.");
        } catch (error) {
            if (!editForm.querySelector("[data-error-for]:not(:empty)")) {
                setStatus(
                    error.message || "Unable to update booking.",
                    "error",
                );
            }
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    async function submitDelete(event) {
        event.preventDefault();

        if (!state.directDeleteUrl && !state.selectedBookingId) {
            return;
        }

        clearFormErrors(deleteForm);
        setStatus("");

        const submitButton = document.getElementById("adminBookingDeleteSubmit");
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const url = state.directDeleteUrl
                ? state.directDeleteUrl
                : routeFromTemplate("destroyUrlTemplate", {
                      "__BOOKING_ID__": state.selectedBookingId,
                  });
            const response = await fetch(
                url,
                {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                    body: JSON.stringify(
                        state.directDeleteUrl
                            ? {}
                            : {
                                  reason: deleteReasonInput.value,
                              },
                    ),
                },
            );
            const payload = await response.json();

            if (!response.ok) {
                if (response.status === 422) {
                    applyFormErrors(deleteForm, payload.errors || {});
                }

                throw new Error(payload.message || "Unable to delete booking.");
            }

            if (state.directDeleteUrl) {
                state.trigger?.closest("tr")?.remove();
                setModalOpen(false);
                window.setTimeout(() => {
                    window.AppToast?.show({
                        type: "success",
                        title: `${titleCaseKind(state.directDeleteKind)} deleted`,
                        message:
                            payload.message ||
                                `${state.entityLabel} and related data were deleted successfully.`,
                    });
                }, 40);
                return;
            }

            state.bookings = state.bookings.filter(
                (booking) => Number(booking.id) !== Number(payload.booking?.id),
            );
            renderList();
            updateRowCounts();

            if (subtitleEl) {
                const count = state.bookings.length;
                subtitleEl.textContent = `${count} related booking${count === 1 ? "" : "s"} ready to manage.`;
            }

            showView("list");
            setStatus(payload.message || "Booking cancelled successfully.");
            window.setTimeout(() => {
                window.AppToast?.show({
                    type: "success",
                    title: "Booking deleted",
                    message:
                        payload.message || "Booking deleted successfully.",
                });
            }, 40);
        } catch (error) {
            if (!deleteForm.querySelector("[data-error-for]:not(:empty)")) {
                setStatus(
                    error.message || "Unable to delete booking.",
                    "error",
                );
            }
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    triggers.forEach((trigger) => {
        trigger.addEventListener("click", () => loadBookings(trigger));
    });

    listEl?.addEventListener("click", (event) => {
        const editTrigger = event.target.closest("[data-booking-edit]");
        if (editTrigger) {
            openEditView(editTrigger.dataset.bookingEdit);
            return;
        }

        const deleteTrigger = event.target.closest("[data-booking-delete]");
        if (deleteTrigger) {
            openDeleteView(deleteTrigger.dataset.bookingDelete);
        }
    });

    [editBackBtn, deleteBackBtn, editCancelBtn, deleteCancelBtn].forEach(
        (button) => {
            button?.addEventListener("click", () => showView("list"));
        },
    );

    closeBtn?.addEventListener("click", () => setModalOpen(false));
    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            setModalOpen(false);
        }
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.classList.contains("open")) {
            setModalOpen(false);
        }
    });

    editForm?.addEventListener("submit", submitEdit);
    deleteForm?.addEventListener("submit", submitDelete);
})();
