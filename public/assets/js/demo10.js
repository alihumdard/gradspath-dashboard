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
const settingsTimezone = document.getElementById("settingsTimezone");

const nameError = document.getElementById("nameError");
const eduEmailError = document.getElementById("eduEmailError");
const calendlyError = document.getElementById("calendlyError");
const payoutError = document.getElementById("payoutError");
const enablePayoutsBtn = document.getElementById("enablePayoutsBtn");
const payoutStatus = document.getElementById("payoutStatus");
const payoutSummary = document.getElementById("payoutSummary");

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
  });
}

if (eduEmail) {
  eduEmail.addEventListener("input", validateEduEmail);
}

if (description) {
  description.addEventListener("input", () => {
    // Keep the field reactive for future enhancements without localStorage persistence.
  });
}

function applyPayoutStatus(payload) {
  if (payoutStatus) {
    payoutStatus.textContent = payload.status_label || "Not enabled";
    payoutStatus.classList.toggle("enabled", Boolean(payload.payouts_enabled));
  }

  if (payoutSummary) {
    payoutSummary.textContent = payload.summary_label || "Not enabled yet";
  }

  if (enablePayoutsBtn && payload.button_label) {
    enablePayoutsBtn.textContent = payload.button_label;
  }
}

async function syncPayoutStatus() {
  if (!enablePayoutsBtn?.dataset.statusUrl) {
    return false;
  }

  const response = await fetch(enablePayoutsBtn.dataset.statusUrl, {
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    credentials: "same-origin",
  });

  if (!response.ok) {
    throw new Error("Unable to refresh payout status.");
  }

  const payload = await response.json();
  applyPayoutStatus(payload);

  return Boolean(payload.payouts_enabled);
}

if (enablePayoutsBtn) {
  enablePayoutsBtn.addEventListener("click", () => {
    clearError(payoutError);
    enablePayoutsBtn.disabled = true;
    window.location.assign(enablePayoutsBtn.dataset.connectUrl);
  });

  if (enablePayoutsBtn.dataset.stripeReturn === "true") {
    let attempts = 0;
    const interval = window.setInterval(async () => {
      attempts += 1;

      try {
        const payoutsEnabled = await syncPayoutStatus();
        if (payoutsEnabled || attempts >= 10) {
          window.clearInterval(interval);
        }
      } catch (error) {
        if (attempts >= 10) {
          window.clearInterval(interval);
          showError(
            payoutError,
            "We could not confirm your Stripe status yet. Refresh this page in a moment."
          );
        }
      }
    }, 2000);
  }
}

if (mentorForm) {
  mentorForm.addEventListener("submit", (event) => {
    const isValid = [validateName(), validateEduEmail(), validateCalendly()].every(Boolean);

    if (!isValid) {
      event.preventDefault();
    }
  });
}

async function autoSaveDetectedTimezone() {
  if (!settingsTimezone || settingsTimezone.dataset.hasSavedTimezone === "true") {
    return;
  }

  const detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
  if (!detectedTimezone) {
    return;
  }

  const supported = Array.from(settingsTimezone.options).map((option) => option.value);
  if (!supported.includes(detectedTimezone)) {
    return;
  }

  settingsTimezone.value = detectedTimezone;

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  if (!csrfToken || !settingsTimezone.dataset.timezoneAutosaveUrl) {
    return;
  }

  await fetch(settingsTimezone.dataset.timezoneAutosaveUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken,
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    credentials: "same-origin",
    body: JSON.stringify({ timezone: detectedTimezone }),
  }).catch(() => {});
}

autoSaveDetectedTimezone();
