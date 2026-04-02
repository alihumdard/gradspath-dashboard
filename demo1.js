const body = document.body;

const themeToggle = document.getElementById("themeToggle");
const openStoreBtn = document.getElementById("openStoreBtn");
const closeStoreBtn = document.getElementById("closeStoreBtn");
const storeModal = document.getElementById("storeModal");
const mobileMenuToggle = document.getElementById("mobileMenuToggle");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const appShell = document.querySelector(".app-shell");

if (mobileMenuToggle) {
  mobileMenuToggle.addEventListener("click", () => {
    appShell.classList.add("sidebar-active");
    body.classList.add("sidebar-open");
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener("click", () => {
    appShell.classList.remove("sidebar-active");
    body.classList.remove("sidebar-open");
  });
}

const storeOptions = document.querySelectorAll(".store-option");
const officeHoursPanel = document.getElementById("officeHoursPanel");
const oneOnThreePanel = document.getElementById("oneOnThreePanel");
const oneOnFivePanel = document.getElementById("oneOnFivePanel");

const pathwayButtons = document.querySelectorAll(".pathway-btn");
const officeHoursProgramLabel = document.getElementById(
  "officeHoursProgramLabel",
);
const officeHoursPriceLabel = document.getElementById("officeHoursPriceLabel");
const creditAssignmentNote = document.getElementById("creditAssignmentNote");

const readMoreButtons = document.querySelectorAll(".read-more-btn");
const servicesToggles = document.querySelectorAll(".services-toggle");

const officeHoursPricing = {
  MBA: "$200/month",
  Law: "$200/month",
  Therapy: "$200/month",
};

// Load saved theme from localStorage, default to 'light'
(function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", savedTheme);
})();

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme =
      document.documentElement.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "light" ? "dark" : "light";
    document.documentElement.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
  });
}

function openModal() {
  if (!storeModal) return;
  storeModal.classList.add("show");
  body.classList.add("modal-open");
}

function closeModal() {
  if (!storeModal) return;
  storeModal.classList.remove("show");
  body.classList.remove("modal-open");
}

if (openStoreBtn) {
  openStoreBtn.addEventListener("click", openModal);
}

if (closeStoreBtn) {
  closeStoreBtn.addEventListener("click", closeModal);
}

if (storeModal) {
  storeModal.addEventListener("click", (e) => {
    if (e.target === storeModal) closeModal();
  });
}

document.addEventListener("keydown", (e) => {
  if (
    e.key === "Escape" &&
    storeModal &&
    storeModal.classList.contains("show")
  ) {
    closeModal();
  }
});

function hideAllStorePanels() {
  if (officeHoursPanel) officeHoursPanel.classList.add("hidden-panel");
  if (oneOnThreePanel) oneOnThreePanel.classList.add("hidden-panel");
  if (oneOnFivePanel) oneOnFivePanel.classList.add("hidden-panel");
}

storeOptions.forEach((option) => {
  option.addEventListener("click", () => {
    storeOptions.forEach((btn) => btn.classList.remove("active-store-option"));
    option.classList.add("active-store-option");

    hideAllStorePanels();

    const service = option.dataset.service;
    if (service === "office-hours" && officeHoursPanel) {
      officeHoursPanel.classList.remove("hidden-panel");
    }
    if (service === "one-on-three" && oneOnThreePanel) {
      oneOnThreePanel.classList.remove("hidden-panel");
    }
    if (service === "one-on-five" && oneOnFivePanel) {
      oneOnFivePanel.classList.remove("hidden-panel");
    }
  });
});

pathwayButtons.forEach((button) => {
  button.addEventListener("click", () => {
    pathwayButtons.forEach((btn) => btn.classList.remove("active-pathway"));
    button.classList.add("active-pathway");

    const pathway = button.dataset.pathway;
    if (officeHoursProgramLabel) {
      officeHoursProgramLabel.textContent = pathway;
    }
    if (officeHoursPriceLabel) {
      officeHoursPriceLabel.textContent =
        officeHoursPricing[pathway] || "$200/month";
    }
    if (creditAssignmentNote) {
      creditAssignmentNote.textContent = `Credits will be applied to ${pathway} office hours.`;
    }
  });
});

document.querySelectorAll(".payment-type-row").forEach((row) => {
  const buttons = row.querySelectorAll(".pay-type-btn");

  buttons.forEach((button) => {
    button.addEventListener("click", () => {
      buttons.forEach((btn) => btn.classList.remove("active-pay-type"));
      button.classList.add("active-pay-type");
    });
  });
});

readMoreButtons.forEach((button) => {
  const block = button.closest(".read-more-block");
  const label = button.querySelector(".read-more-label");

  button.addEventListener("click", (e) => {
    e.stopPropagation();
    if (!block || !label) return;

    block.classList.toggle("expanded");
    label.textContent = block.classList.contains("expanded")
      ? "Read Less"
      : "Read More";
  });
});

servicesToggles.forEach((toggle) => {
  toggle.addEventListener("click", () => {
    const accordion = toggle.closest(".services-accordion");
    if (!accordion) return;
    accordion.classList.toggle("open");
  });
});

document.querySelectorAll(".book-now-btn").forEach((button) => {
  button.addEventListener("click", (e) => {
    e.stopPropagation();
    openModal();
  });
});

// Sidebar navigation logic
const navItems = document.querySelectorAll(".nav-item");

// Function to set active state
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

// Initial activation on load
setActiveNav();

// Handle clicks for immediate feedback
navItems.forEach((item) => {
  item.addEventListener("click", () => {
    navItems.forEach((nav) => nav.classList.remove("active"));
    item.classList.add("active");
  });
});
