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
const usersInstitutionFilter = document.getElementById("usersInstitutionFilter");
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
    const matchesInstitution = institution === "all" || rowInstitution === institution;

    row.style.display = matchesSearch && matchesProgram && matchesInstitution ? "" : "none";
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

    row.style.display = matchesSearch && matchesProgram && matchesStatus ? "" : "none";
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
          font: { family: "Inter" }
        }
      }
    },
    scales: {
      x: {
        ticks: { color: getChartTextColor() },
        grid: { color: getGridColor() }
      },
      y: {
        ticks: { color: getChartTextColor() },
        grid: { color: getGridColor() }
      }
    }
  };

  new Chart(document.getElementById("bookingsChart"), {
    type: "line",
    data: {
      labels: ["Oct", "Nov", "Dec", "Jan", "Feb", "Mar"],
      datasets: [{
        label: "Bookings",
        data: [38, 46, 51, 64, 78, 96],
        borderWidth: 2,
        tension: 0.35
      }]
    },
    options: sharedOptions
  });

  new Chart(document.getElementById("revenueChart"), {
    type: "bar",
    data: {
      labels: ["Oct", "Nov", "Dec", "Jan", "Feb", "Mar"],
      datasets: [{
        label: "Revenue",
        data: [4200, 5100, 5900, 7600, 9800, 12840],
        borderWidth: 1
      }]
    },
    options: sharedOptions
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
        "Office Hours"
      ],
      datasets: [{
        label: "Bookings",
        data: [22, 8, 31, 24, 18, 6, 14],
        borderWidth: 1
      }]
    },
    options: sharedOptions
  });

  new Chart(document.getElementById("programRevenueChart"), {
    type: "doughnut",
    data: {
      labels: ["MBA", "Law", "CMHC", "MFT", "MSW", "Clinical Psy"],
      datasets: [{
        label: "Revenue",
        data: [6920, 3740, 2180, 900, 1450, 1100],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: {
            color: getChartTextColor(),
            font: { family: "Inter" }
          }
        }
      }
    }
  });

  new Chart(document.getElementById("topMentorsChart"), {
    type: "bar",
    data: {
      labels: ["Sarah Kim", "Daniel Brooks", "Rachel Adams", "Leah Morris", "Anthony Cruz"],
      datasets: [{
        label: "Revenue",
        data: [1870, 1190, 790, 720, 610],
        borderWidth: 1
      }]
    },
    options: {
      ...sharedOptions,
      indexAxis: "y"
    }
  });
}