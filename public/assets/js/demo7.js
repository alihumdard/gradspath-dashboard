const mentorNotesFormDataEl = document.getElementById("mentorNotesFormData");
const mentorNotesFormData = mentorNotesFormDataEl
  ? JSON.parse(mentorNotesFormDataEl.textContent)
  : {};

const mentorNotesForm = document.getElementById("mentorNotesForm");

const fullNameField = document.getElementById("fullName");
const userEmailField = document.getElementById("userEmail");
const sessionDateField = document.getElementById("sessionDate");
const mentorNameField = document.getElementById("mentorName");
const mentorEmailField = document.getElementById("mentorEmail");
const sessionTypeField = document.getElementById("sessionType");
const serviceCards = document.querySelectorAll(".service-view-card");

const sessionWork = document.getElementById("sessionWork");
const nextSteps = document.getElementById("nextSteps");
const sessionOutcome = document.getElementById("sessionOutcome");
const sessionReflection = document.getElementById("sessionReflection");
const otherNotes = document.getElementById("otherNotes");
const charCount = document.getElementById("charCount");

const fallbackSession = {
  fullName: fullNameField?.value || "User Name",
  email: userEmailField?.value || "user@example.edu",
  mentorName: mentorNameField?.value || "Mentor Name",
  mentorEmail: mentorEmailField?.value || "mentor@example.edu",
  sessionDate:
    sessionDateField?.value ||
    new Date().toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    }),
  sessionType: sessionTypeField?.value || "Application Review",
};

const currentSession = mentorNotesFormData.session || fallbackSession;

function populateSessionDetails() {
  if (!fullNameField || !userEmailField || !mentorNameField || !mentorEmailField || !sessionDateField || !sessionTypeField) {
    return;
  }

  fullNameField.value = currentSession.fullName || fallbackSession.fullName;
  userEmailField.value = currentSession.email || fallbackSession.email;
  mentorNameField.value = currentSession.mentorName || fallbackSession.mentorName;
  mentorEmailField.value = currentSession.mentorEmail || fallbackSession.mentorEmail;
  sessionDateField.value = currentSession.sessionDate || fallbackSession.sessionDate;
  sessionTypeField.value = currentSession.sessionType || fallbackSession.sessionType;

  serviceCards.forEach((card) => {
    const isActive = card.dataset.service === sessionTypeField.value;
    card.classList.toggle("active", isActive);
  });
}

function updateCharCount() {
  if (charCount && otherNotes) {
    charCount.textContent = String(otherNotes.value.length);
  }
}

function validateField(value, message) {
  if (!String(value || "").trim()) {
    alert(message);
    return false;
  }

  return true;
}

if (otherNotes) {
  otherNotes.addEventListener("input", updateCharCount);
}

if (mentorNotesForm) {
  mentorNotesForm.addEventListener("submit", function (event) {
    const isValid =
      validateField(fullNameField?.value, "User full name is missing.") &&
      validateField(userEmailField?.value, "User email is missing.") &&
      validateField(mentorNameField?.value, "Mentor name is missing.") &&
      validateField(mentorEmailField?.value, "Mentor email is missing.") &&
      validateField(sessionDateField?.value, "Session date is missing.") &&
      validateField(sessionTypeField?.value, "Session type is missing.") &&
      validateField(sessionWork?.value, "Please enter what was worked on during the session.") &&
      validateField(nextSteps?.value, "Please enter the plan going forward for the user.") &&
      validateField(sessionOutcome?.value, "Please enter the outcome of the session.") &&
      validateField(sessionReflection?.value, "Please enter one strength and one challenge from the session.") &&
      validateField(otherNotes?.value, "Please enter any other notes about the session.");

    if (!isValid) {
      event.preventDefault();
      return;
    }
  });
}

populateSessionDetails();
updateCharCount();

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

// Sidebar navigation logic
const navItems = document.querySelectorAll(".nav-item");

function setActiveNav() {
  const currentPath = window.location.pathname.split("/").pop() || "demo1.html";

  navItems.forEach((item) => {
    const href = item.getAttribute("href");
    if (href === currentPath) {
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

// Theme Logic
const themeToggle = document.getElementById("themeToggle");

(function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  document.body.setAttribute("data-theme", savedTheme);
})();

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = document.body.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    document.body.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
  });
}
