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
const universityPicker = document.querySelector("[data-mentor-university-picker]");
const universitySearch = universityPicker?.querySelector("[data-university-search]");
const universityIdInput = universityPicker?.querySelector("[data-university-id]");
const universityResults = universityPicker?.querySelector("[data-university-results]");
const universityProgramSelect = document.querySelector("[data-program-select]");

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

function escapeHtml(value) {
  const div = document.createElement("div");
  div.textContent = value ?? "";
  return div.innerHTML;
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

function initializeMentorUniversityPicker() {
  if (
    !universityPicker ||
    !universitySearch ||
    !universityIdInput ||
    !universityResults ||
    !universityPicker.dataset.searchUrl ||
    !universityPicker.dataset.programsUrl
  ) {
    return;
  }

  let selectedLabel = universitySearch.value || "";
  let debounceTimer = null;
  let searchToken = 0;
  let programToken = 0;

  function hideResults() {
    universityResults.hidden = true;
  }

  function showResults() {
    universityResults.hidden = false;
  }

  function universityMeta(university) {
    return [university.country, university.state_province].filter(Boolean).join(" · ");
  }

  function normalizeUniversities(payload) {
    const universities = Array.isArray(payload?.data)
      ? payload.data
      : Array.isArray(payload)
        ? payload
        : [];

    return universities.map((university) => ({
      id: university.id,
      label: university.label || university.name || "",
      country: university.country || "",
      state_province: university.state_province || "",
    }));
  }

  function renderUniversities(universities) {
    universityResults.innerHTML = universities.length
      ? universities
          .map(
            (university) => `
              <button class="settings-picker-option" type="button" data-university-option="${escapeHtml(university.id)}" data-university-label="${escapeHtml(university.label)}">
                <strong>${escapeHtml(university.label)}</strong>
                <small>${escapeHtml(universityMeta(university))}</small>
              </button>
            `
          )
          .join("")
      : '<div class="settings-picker-empty">No universities found</div>';

    showResults();
  }

  function renderPrograms(programs, selectedProgramId = "") {
    if (!universityProgramSelect) {
      return;
    }

    universityProgramSelect.innerHTML = '<option value="">Select a program</option>';

    programs.forEach((program) => {
      const option = document.createElement("option");
      option.value = String(program.id);
      option.textContent = program.program_name;
      option.selected = String(program.id) === String(selectedProgramId);
      universityProgramSelect.appendChild(option);
    });

    universityProgramSelect.disabled = programs.length === 0;
    universityProgramSelect.dataset.selectedProgramId = selectedProgramId || "";
  }

  async function fetchUniversities(query) {
    const token = ++searchToken;
    const url = new URL(universityPicker.dataset.searchUrl, window.location.origin);
    url.searchParams.set("q", query);

    const response = await fetch(url.toString(), {
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    });

    if (!response.ok || token !== searchToken) {
      return;
    }

    renderUniversities(normalizeUniversities(await response.json()));
  }

  async function loadPrograms(universityId, selectedProgramId = "") {
    const token = ++programToken;

    if (!universityId) {
      renderPrograms([]);
      return;
    }

    const url = new URL(universityPicker.dataset.programsUrl, window.location.origin);
    url.searchParams.set("university_id", universityId);

    const response = await fetch(url.toString(), {
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    });

    if (!response.ok || token !== programToken) {
      return;
    }

    const payload = await response.json();
    renderPrograms(Array.isArray(payload.data) ? payload.data : [], selectedProgramId);
  }

  universitySearch.addEventListener("focus", () => {
    const query = universitySearch.value.trim();
    if (query.length >= 2) {
      fetchUniversities(query);
    }
  });

  universitySearch.addEventListener("input", () => {
    if (universitySearch.value !== selectedLabel) {
      selectedLabel = "";
      universityIdInput.value = "";
      if (school) {
        school.value = "";
      }
      renderPrograms([]);
    }

    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(() => {
      const query = universitySearch.value.trim();
      if (query.length < 2) {
        hideResults();
        return;
      }

      fetchUniversities(query);
    }, 220);
  });

  universityResults.addEventListener("click", (event) => {
    const option = event.target.closest("[data-university-option]");
    if (!option) {
      return;
    }

    universityIdInput.value = option.dataset.universityOption || "";
    selectedLabel = option.dataset.universityLabel || "";
    universitySearch.value = selectedLabel;
    if (school) {
      school.value = selectedLabel;
    }
    hideResults();
    loadPrograms(universityIdInput.value);
  });

  document.addEventListener("click", (event) => {
    if (!universityPicker.contains(event.target)) {
      hideResults();
    }
  });

  if (universityIdInput.value && universityProgramSelect?.dataset.selectedProgramId) {
    loadPrograms(universityIdInput.value, universityProgramSelect.dataset.selectedProgramId);
  }
}

initializeMentorUniversityPicker();

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
