// Theme handling
(function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", savedTheme);
})();

const mentorForm = document.getElementById("mentorForm");
const themeToggle = document.getElementById("themeToggle");
const menuBtn = document.getElementById("mobileMenuToggle");
const overlay = document.getElementById("sidebarOverlay");
const shell = document.querySelector(".app-shell");

const fullName = document.getElementById("fullName");
const mentorType = document.getElementById("mentorType");
const eduEmail = document.getElementById("eduEmail");
const program = document.getElementById("program");
const school = document.getElementById("school");
const officeHours = document.getElementById("officeHours");
const calendlyLink = document.getElementById("calendlyLink");
const bio = document.getElementById("bio");
const description = document.getElementById("description");

const avatarInitials = document.getElementById("avatarInitials");
const cardName = document.getElementById("cardName");
const cardSubtitle = document.getElementById("cardSubtitle");
const officeHoursDisplay = document.getElementById("officeHoursDisplay");
const officeHoursText = document.getElementById("officeHoursText");
const cardDescription = document.getElementById("cardDescription");

const nameError = document.getElementById("nameError");
const eduEmailError = document.getElementById("eduEmailError");
const calendlyError = document.getElementById("calendlyError");
const payoutError = document.getElementById("payoutError");
const enablePayoutsBtn = document.getElementById("enablePayoutsBtn");
const payoutStatus = document.getElementById("payoutStatus");

function updateTheme(theme) {
  document.documentElement.setAttribute("data-theme", theme);
  localStorage.setItem("theme", theme);

  if (themeToggle) {
    themeToggle.textContent = theme === "dark" ? "Light Mode" : "Dark Mode";
  }
}

if (themeToggle) {
  const currentSavedTheme = localStorage.getItem("theme") || "light";
  themeToggle.textContent = currentSavedTheme === "dark" ? "Light Mode" : "Dark Mode";

  themeToggle.addEventListener("click", () => {
    const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
    updateTheme(currentTheme === "dark" ? "light" : "dark");
  });
}

if (menuBtn && shell) {
  menuBtn.addEventListener("click", () => shell.classList.add("sidebar-active"));
}

if (overlay && shell) {
  overlay.addEventListener("click", () => shell.classList.remove("sidebar-active"));
}

function getInitials(name) {
  const parts = name.trim().split(/\s+/).filter(Boolean);

  if (parts.length === 0) return "";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();

  return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
}

function showError(element, message) {
  if (element) {
    element.textContent = message;
  }
}

function clearError(element) {
  if (element) {
    element.textContent = "";
  }
}

function updatePreview() {
  if (!cardName || !cardSubtitle || !cardDescription) {
    return;
  }

  const nameValue = fullName?.value.trim() || "";
  const titleValue = program?.value.trim() || "";
  const schoolValue = school?.value.trim() || "";
  const officeHoursValue = officeHours?.value.trim() || "";
  const bioValue = bio?.value.trim() || "";

  cardName.textContent = nameValue;

  if (avatarInitials) {
    avatarInitials.textContent = getInitials(nameValue);
  }

  const subtitleParts = [titleValue, schoolValue].filter(Boolean);
  cardSubtitle.textContent = subtitleParts.join(" • ");

  cardDescription.textContent = bioValue;

  if (officeHoursDisplay && officeHoursText) {
    officeHoursText.textContent = officeHoursValue;
    officeHoursDisplay.style.display = officeHoursValue ? "block" : "none";
  }
}

function validateName() {
  const value = fullName?.value.trim() || "";

  if (!value) {
    showError(nameError, "Enter your full name.");
    return false;
  }

  if (value.split(/\s+/).filter(Boolean).length < 2) {
    showError(nameError, "Enter at least a first and last name.");
    return false;
  }

  clearError(nameError);
  return true;
}

function validateEduEmail() {
  if (!mentorType || !eduEmail) {
    return true;
  }

  const typeValue = mentorType.value;
  const emailValue = eduEmail.value.trim();

  if (typeValue !== "graduate") {
    clearError(eduEmailError);
    return true;
  }

  if (!emailValue) {
    showError(eduEmailError, "Graduate mentors must provide a .edu email.");
    return false;
  }

  if (!emailValue.toLowerCase().endsWith(".edu")) {
    showError(eduEmailError, "Graduate mentors must use a .edu email address.");
    return false;
  }

  clearError(eduEmailError);
  return true;
}

function validateCalendly() {
  if (!calendlyLink || !calendlyLink.value.trim()) {
    clearError(calendlyError);
    return true;
  }

  try {
    const parsed = new URL(calendlyLink.value.trim());

    if (!parsed.hostname.includes("calendly.com")) {
      showError(calendlyError, "Enter a valid Calendly URL.");
      return false;
    }
  } catch (error) {
    showError(calendlyError, "Enter a valid URL.");
    return false;
  }

  clearError(calendlyError);
  return true;
}

if (mentorType) {
  mentorType.addEventListener("change", validateEduEmail);
}

if (fullName) {
  fullName.addEventListener("input", () => {
    validateName();
    updatePreview();
  });
}

if (eduEmail) {
  eduEmail.addEventListener("input", validateEduEmail);
}

if (program) {
  program.addEventListener("input", updatePreview);
}

if (school) {
  school.addEventListener("input", updatePreview);
}

if (officeHours) {
  officeHours.addEventListener("input", updatePreview);
}

if (bio) {
  bio.addEventListener("input", updatePreview);
}

if (description) {
  description.addEventListener("input", () => {
    // Keep the field reactive for future enhancements without localStorage persistence.
  });
}

if (enablePayoutsBtn) {
  enablePayoutsBtn.addEventListener("click", () => {
    showError(
      payoutError,
      "Payout onboarding is not connected on this page yet. Your profile can still be saved."
    );

    if (payoutStatus && !payoutStatus.classList.contains("enabled")) {
      payoutStatus.textContent = "Not enabled";
    }
  });
}

if (mentorForm) {
  mentorForm.addEventListener("submit", (event) => {
    const isValid = [validateName(), validateEduEmail(), validateCalendly()].every(Boolean);

    if (!isValid) {
      event.preventDefault();
    }
  });
}

updatePreview();
