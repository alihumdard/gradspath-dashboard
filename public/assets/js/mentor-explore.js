const mentors = Array.isArray(window.mentorsData) ? window.mentorsData : [];

const body = document.body;
const themeToggle = document.getElementById("themeToggle");
const mobileMenuToggle = document.getElementById("mobileMenuToggle");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const appShell = document.querySelector(".app-shell");

const mentorGrid = document.getElementById("mentorGrid");
const searchMentor = document.getElementById("searchMentor");
const searchSchool = document.getElementById("searchSchool");
const resultsCount = document.getElementById("resultsCount");
const activeFilters = document.getElementById("activeFilters");
const emptyState = document.getElementById("emptyState");
const tabPills = document.querySelectorAll("#tabPills .filter-pill");
const programPillsContainer = document.getElementById("programPills");
const initialParams = new URLSearchParams(window.location.search);

let activeTab =
  initialParams.get("mentor_type") === "professional"
    ? "professionals"
    : "graduates";
let activeProgram = "all";
let mentorSearchTerm = "";
let schoolSearchTerm = "";

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function categoryLabel(category) {
  return (
    {
      all: "All",
      mba: "MBA",
      law: "Law",
      therapy: "Therapy",
    }[category] || "Other"
  );
}

function getProgramOptionsForTab() {
  const options = mentors
    .filter((mentor) => mentor.type === activeTab)
    .map((mentor) => mentor.category)
    .filter(Boolean);

  return ["all", ...new Set(options)].map((value) => ({
    value,
    label: categoryLabel(value),
  }));
}

function renderProgramPills() {
  const options = getProgramOptionsForTab();

  if (!options.some((option) => option.value === activeProgram)) {
    activeProgram = "all";
  }

  programPillsContainer.innerHTML = options
    .map(
      (option) => `
        <button
          class="filter-pill ${option.value === activeProgram ? "active" : ""}"
          data-program="${escapeHtml(option.value)}"
          type="button"
        >
          ${escapeHtml(option.label)}
        </button>
      `,
    )
    .join("");

  programPillsContainer.querySelectorAll(".filter-pill").forEach((pill) => {
    pill.addEventListener("click", () => {
      programPillsContainer
        .querySelectorAll(".filter-pill")
        .forEach((p) => p.classList.remove("active"));
      pill.classList.add("active");
      activeProgram = pill.dataset.program;
      renderMentors();
    });
  });
}

function getFilteredMentors() {
  let filtered = mentors.filter((mentor) => mentor.type === activeTab);

  if (activeProgram !== "all") {
    filtered = filtered.filter((mentor) => mentor.category === activeProgram);
  }

  if (mentorSearchTerm.trim()) {
    const q = mentorSearchTerm.trim().toLowerCase();
    filtered = filtered.filter((mentor) =>
      mentor.name.toLowerCase().includes(q),
    );
  }

  if (schoolSearchTerm.trim()) {
    const q = schoolSearchTerm.trim().toLowerCase();
    filtered = filtered.filter((mentor) =>
      mentor.school.toLowerCase().includes(q),
    );
  }

  filtered.sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0));
  return filtered;
}

function renderRating(rating) {
  return rating == null ? "New" : rating.toFixed(1);
}

function createMentorCard(mentor) {
  const article = document.createElement("article");
  article.className = "mentor-card";

  const feedbackExtra =
    mentor.reviewExtra && mentor.reviewExtra.trim()
      ? mentor.reviewExtra
      : "More mentor feedback will appear here as reviews are added.";

  const avatarMarkup =
    mentor.avatarUrl && mentor.avatarUrl.trim()
      ? `<img src="${escapeHtml(mentor.avatarUrl)}" alt="${escapeHtml(mentor.name)}" class="mentor-avatar-image" />`
      : escapeHtml(mentor.initials || "M");

  article.innerHTML = `
    <div class="mentor-card-top">
      <div class="mentor-card-identity">
        <div class="mentor-avatar">${avatarMarkup}</div>
        <div class="mentor-headings">
          <div class="mentor-name">${escapeHtml(mentor.name)}</div>
          <div class="mentor-role">${escapeHtml(mentor.categoryLabel)} • ${escapeHtml(mentor.school)}</div>
        </div>
      </div>
      <div class="mentor-rating">★ ${escapeHtml(renderRating(mentor.rating))}</div>
    </div>

    <div class="mentor-office-hours">
      <span class="mentor-office-hours-title">Office Hours:</span>
      <span class="mentor-office-hours-time">${escapeHtml(mentor.officeHours)}</span>
    </div>

    <div class="bio-read-block">
      <div class="bio-short">${escapeHtml(mentor.bio)}</div>
      <div class="bio-full">${escapeHtml(mentor.bioExtra)}</div>
      <button class="read-more-btn mentor-bio-btn" type="button">Read More ▼</button>
    </div>

    <div class="services-accordion">
      <button class="services-toggle" type="button">
        <span class="services-toggle-text">Services Offered</span>
        <span class="services-toggle-icon">▼</span>
      </button>

      <div class="services-dropdown">
        <div class="service-grid">
          ${(mentor.services || [])
            .map((service) => `<div class="service-pill">${escapeHtml(service)}</div>`)
            .join("")}
        </div>
      </div>
    </div>

    <div class="feedback-box">
      <div class="feedback-top-row">
        <div class="feedback-title">Recent Feedback</div>
        ${
          mentor.visibleFeedbackCount >= 2 && mentor.feedbackUrl
            ? `<a class="feedback-link-btn" href="${escapeHtml(mentor.feedbackUrl)}">See more Feedback</a>`
            : ""
        }
      </div>

      <p class="feedback-short">“${escapeHtml(mentor.reviewShort)}”</p>
      <div class="feedback-full">“${escapeHtml(feedbackExtra)}”</div>
      <button class="feedback-read-more-btn" type="button">Read More ▼</button>
    </div>

    <button class="book-now-btn" type="button" ${mentor.canBook === false ? "disabled" : ""}>
      ${mentor.canBook === false ? "Unavailable" : "Book Now"}
    </button>
  `;

  const bioBtn = article.querySelector(".mentor-bio-btn");
  bioBtn.addEventListener("click", () => {
    const expanded = article.classList.toggle("bio-expanded");
    bioBtn.textContent = expanded ? "Read Less ▲" : "Read More ▼";
  });

  const feedbackBtn = article.querySelector(".feedback-read-more-btn");
  feedbackBtn.addEventListener("click", () => {
    const expanded = article.classList.toggle("feedback-expanded");
    feedbackBtn.textContent = expanded ? "Read Less ▲" : "Read More ▼";
  });

  const bookNowBtn = article.querySelector(".book-now-btn");
  bookNowBtn.addEventListener("click", () => {
    if (!mentor.canBook || !mentor.bookingUrl) {
      return;
    }

    window.location.href = mentor.bookingUrl;
  });

  const servicesAccordion = article.querySelector(".services-accordion");
  const servicesToggle = article.querySelector(".services-toggle");
  const servicesIcon = article.querySelector(".services-toggle-icon");

  servicesToggle.addEventListener("click", () => {
    const open = servicesAccordion.classList.toggle("open");
    servicesIcon.textContent = open ? "▲" : "▼";
  });

  return article;
}

function updateMeta(count) {
  const tabLabel = activeTab === "graduates" ? "Graduates" : "Professionals";
  const programLabel = categoryLabel(activeProgram);
  const mentorText = mentorSearchTerm.trim()
    ? `Mentor: ${mentorSearchTerm.trim()}`
    : "All Mentors";
  const schoolText = schoolSearchTerm.trim()
    ? `School: ${schoolSearchTerm.trim()}`
    : "All Schools";

  activeFilters.textContent = `${tabLabel} • ${programLabel} • ${mentorText} • ${schoolText}`;
  resultsCount.textContent = `${count} mentor${count === 1 ? "" : "s"} shown`;
}

function renderMentors() {
  const filtered = getFilteredMentors();
  mentorGrid.innerHTML = "";
  updateMeta(filtered.length);

  if (!filtered.length) {
    emptyState.classList.remove("hidden");
    return;
  }

  emptyState.classList.add("hidden");
  filtered.forEach((mentor) =>
    mentorGrid.appendChild(createMentorCard(mentor)),
  );
}

(function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  body.setAttribute("data-theme", savedTheme);
})();

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = body.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    body.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
  });
}

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

tabPills.forEach((pill) => {
  pill.classList.toggle("active", pill.dataset.tab === activeTab);

  pill.addEventListener("click", () => {
    tabPills.forEach((p) => p.classList.remove("active"));
    pill.classList.add("active");
    activeTab = pill.dataset.tab;
    activeProgram = "all";
    renderProgramPills();
    renderMentors();
  });
});

searchMentor.addEventListener("input", (e) => {
  mentorSearchTerm = e.target.value;
  renderMentors();
});

searchSchool.addEventListener("input", (e) => {
  schoolSearchTerm = e.target.value;
  renderMentors();
});

renderProgramPills();
renderMentors();
