const institutions = Array.isArray(window.institutionsData)
  ? window.institutionsData
  : [];

const tierFilters = [
  "All",
  "Elite Programs",
  "Top 25 Programs",
  "Regional Programs",
];
const programFamilyFilters = ["All", "MBA", "Law", "Therapy"];

const universityGrid = document.getElementById("universityGrid");
const programGrid = document.getElementById("programGrid");
const universitiesSection = document.getElementById("universitiesSection");
const programsSection = document.getElementById("programsSection");
const selectedSchoolName = document.getElementById("selectedSchoolName");
const selectedSchoolSubtext = document.getElementById("selectedSchoolSubtext");
const selectedSchoolTierTag = document.getElementById("selectedSchoolTierTag");
const selectedSchoolProgramTag = document.getElementById(
  "selectedSchoolProgramTag",
);
const backBtn = document.getElementById("backBtn");
const selectedSchoolIcon = document.querySelector(".selected-school-icon");
const tierFiltersContainer = document.getElementById("tierFilters");
const programFiltersContainer = document.getElementById("programFilters");
const schoolProgramFiltersContainer = document.getElementById(
  "schoolProgramFilters",
);
const resultsBadge = document.getElementById("resultsBadge");
const resultsCount = document.getElementById("resultsCount");
const mentorsPanel = document.getElementById("mentorsPanel");
const mentorGrid = document.getElementById("mentorGrid");
const mentorPanelTitle = document.getElementById("mentorPanelTitle");
const mentorPanelSubtext = document.getElementById("mentorPanelSubtext");
const seeAllMentorsBtn = document.getElementById("seeAllMentorsBtn");
const schoolSearchInput = document.getElementById("schoolSearchInput");
const topbarSearch = document.getElementById("topbarSearch");
const themeToggle = document.getElementById("themeToggle");

const state = {
  tier: "All",
  family: "All",
  search: "",
  selectedSchool: null,
  selectedSchoolFamily: "All",
  selectedProgram: null,
};

function escapeHtml(str) {
  return String(str ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function normalizedAssetUrl(url) {
  const value = String(url || "").trim();
  if (!value) return "";
  return value.startsWith("http") || value.startsWith("/") ? value : `/${value}`;
}

function mentorAvatarMarkup(mentor) {
  if (mentor.avatarUrl) {
    return `<img src="${escapeHtml(normalizedAssetUrl(mentor.avatarUrl))}" alt="${escapeHtml(mentor.name)}" class="mentor-avatar-image" loading="lazy">`;
  }

  return escapeHtml(mentor.initials || "GP");
}

function mentorServicesMarkup(mentor) {
  const services = Array.isArray(mentor.services) && mentor.services.length
    ? mentor.services
    : mentor.tags || [];

  if (!services.length) {
    return `<div class="service-pill">Program Insights</div>`;
  }

  return services
    .slice(0, 6)
    .map((service) => `<div class="service-pill">${escapeHtml(service)}</div>`)
    .join("");
}

function bindMentorCardInteractions() {
  mentorGrid.querySelectorAll(".read-more-btn").forEach((button) => {
    button.addEventListener("click", () => {
      const block = button.closest(".read-more-block");
      const label = button.querySelector(".read-more-label");

      if (!block || !label) return;

      block.classList.toggle("expanded");
      label.textContent = block.classList.contains("expanded")
        ? "Read Less"
        : "Read More";
    });
  });

  mentorGrid.querySelectorAll(".services-toggle").forEach((toggle) => {
    toggle.addEventListener("click", () => {
      const accordion = toggle.closest(".services-accordion");
      if (!accordion) return;

      const open = accordion.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
  });
}

function normalizePrograms(programs) {
  const seen = new Set();

  return (programs || [])
    .filter((program) => program && program.name)
    .filter((program) => {
      const key = `${program.name}|${program.type}|${program.tier}`;
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    })
    .sort((a, b) => a.name.localeCompare(b.name));
}

const normalizedInstitutions = institutions
  .map((item) => ({
    id: item.id,
    school: item.school,
    fullName: item.fullName || item.school,
    logo_url: item.logo_url,
    programs: normalizePrograms(item.programs),
  }))
  .sort((a, b) => a.school.localeCompare(b.school));

function getProgramFamily(program) {
  return program.family || "Therapy";
}

function getProgramIcon(program) {
  if (program.mentors?.[0]?.icon) {
    return program.mentors[0].icon;
  }

  return getProgramFamily(program) === "MBA"
    ? "briefcase-business"
    : getProgramFamily(program) === "Law"
      ? "scale"
      : "heart-handshake";
}

function getProgramDescription(program) {
  return (
    program.description ||
    `Explore mentor support for ${program.name} at this institution.`
  );
}

function getProgramFamiliesFromPrograms(programs) {
  return [...new Set(programs.map((program) => getProgramFamily(program)))];
}

function getProgramsLabel(programs) {
  const families = getProgramFamiliesFromPrograms(programs);
  return families.join(" • ");
}

function getTierSummary(programs) {
  const tiers = [...new Set(programs.map((program) => program.tierLabel || program.tier))];
  if (tiers.length === 0) return "Programs";
  if (tiers.length === 1) return tiers[0];
  return "Multiple Tiers";
}

function matchesGlobalFilters(program) {
  const matchesTier = state.tier === "All" || program.tier === state.tier;
  const matchesFamily =
    state.family === "All" || getProgramFamily(program) === state.family;
  return matchesTier && matchesFamily;
}

function schoolMatchesSearch(school) {
  if (!state.search.trim()) return true;

  const needle = state.search.trim().toLowerCase();

  return (
    school.school.toLowerCase().includes(needle) ||
    school.fullName.toLowerCase().includes(needle)
  );
}

function getSchoolsForGrid() {
  return normalizedInstitutions.filter(
    (school) =>
      schoolMatchesSearch(school) &&
      school.programs.some((program) => matchesGlobalFilters(program)),
  );
}

function getVisibleProgramsForSchool(school) {
  return school.programs.filter((program) => {
    const matchesTier = state.tier === "All" || program.tier === state.tier;
    const matchesFamily =
      state.selectedSchoolFamily === "All" ||
      getProgramFamily(program) === state.selectedSchoolFamily;

    return matchesTier && matchesFamily;
  });
}

function renderTierFilters() {
  tierFiltersContainer.innerHTML = tierFilters
    .map(
      (filter) => `
        <button
          class="filter-chip ${state.tier === filter ? "active-tier" : ""}"
          data-tier="${escapeHtml(filter)}"
          type="button"
        >
          ${escapeHtml(filter)}
        </button>
      `,
    )
    .join("");

  tierFiltersContainer.querySelectorAll("[data-tier]").forEach((btn) => {
    btn.addEventListener("click", () => {
      state.tier = btn.dataset.tier;
      state.selectedProgram = null;
      renderAllUniversitiesView();
    });
  });
}

function renderProgramFilters() {
  programFiltersContainer.innerHTML = programFamilyFilters
    .map(
      (filter) => `
        <button
          class="filter-chip ${state.family === filter ? "active-program" : ""}"
          data-family="${escapeHtml(filter)}"
          type="button"
        >
          ${escapeHtml(filter)}
        </button>
      `,
    )
    .join("");

  programFiltersContainer.querySelectorAll("[data-family]").forEach((btn) => {
    btn.addEventListener("click", () => {
      state.family = btn.dataset.family;
      state.selectedProgram = null;
      renderAllUniversitiesView();
    });
  });
}

function renderResultsMeta(filteredInstitutions) {
  const badgeParts = [];
  badgeParts.push(state.tier === "All" ? "All tiers" : state.tier);
  badgeParts.push(state.family === "All" ? "All programs" : state.family);
  if (state.search.trim()) {
    badgeParts.push(`Search: "${state.search.trim()}"`);
  }

  resultsBadge.textContent = badgeParts.join(" • ");
  resultsCount.textContent = `${filteredInstitutions.length} universit${filteredInstitutions.length === 1 ? "y" : "ies"} shown`;
}

function renderInstitutions() {
  const filteredInstitutions = getSchoolsForGrid();
  renderResultsMeta(filteredInstitutions);

  if (!filteredInstitutions.length) {
    universityGrid.innerHTML = `
      <div class="empty-state">
        <h3>No universities match this filter</h3>
        <p>Try changing the filters or search term.</p>
      </div>
    `;
    lucide.createIcons();
    return;
  }

  universityGrid.innerHTML = filteredInstitutions
    .map((school) => {
      const matchingPrograms = school.programs.filter((program) =>
        matchesGlobalFilters(program),
      );

      return `
        <div class="university-card" data-school-id="${escapeHtml(school.id)}">
          <div class="university-icon-wrap">
            ${school.logo_url && school.logo_url.trim() !== "" ?
              `<img class="university-logo-image" src="${escapeHtml(school.logo_url.startsWith('http') || school.logo_url.startsWith('/') ? school.logo_url : '/' + school.logo_url)}" alt="${escapeHtml(school.school)}" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" /><i data-lucide="building-2" class="university-logo-fallback-icon" style="display:none;"></i>` :
              `<i data-lucide="building-2"></i>`
            }
          </div>

          <div class="university-name">${escapeHtml(school.school)}</div>

          <div class="university-meta">
            <span class="university-tier-pill">${escapeHtml(getTierSummary(matchingPrograms))}</span>
            <span class="university-family-pill">${escapeHtml(getProgramsLabel(matchingPrograms))}</span>
          </div>
        </div>
      `;
    })
    .join("");

  lucide.createIcons();

  universityGrid.querySelectorAll(".university-card").forEach((card) => {
    card.addEventListener("click", () => {
      const school = normalizedInstitutions.find(
        (item) => String(item.id) === card.dataset.schoolId,
      );

      if (!school) return;

      state.selectedSchool = school;
      state.selectedSchoolFamily =
        state.family === "All" ? "All" : state.family;
      state.selectedProgram = null;
      mentorsPanel.classList.add("hidden");
      showProgramsForSchool(school);
    });
  });
}

function getSchoolFamilyOptions(school) {
  const programsRespectingTier = school.programs.filter((program) => {
    return state.tier === "All" || program.tier === state.tier;
  });

  const families = [
    ...new Set(
      programsRespectingTier.map((program) => getProgramFamily(program)),
    ),
  ];

  return ["All", ...families];
}

function renderSchoolProgramFilters(school) {
  const options = getSchoolFamilyOptions(school);

  if (!options.includes(state.selectedSchoolFamily)) {
    state.selectedSchoolFamily = "All";
  }

  schoolProgramFiltersContainer.innerHTML = options
    .map(
      (filter) => `
        <button
          class="filter-chip ${state.selectedSchoolFamily === filter ? "active-program" : ""}"
          data-school-family="${escapeHtml(filter)}"
          type="button"
        >
          ${escapeHtml(filter)}
        </button>
      `,
    )
    .join("");

  schoolProgramFiltersContainer
    .querySelectorAll("[data-school-family]")
    .forEach((btn) => {
      btn.addEventListener("click", () => {
        state.selectedSchoolFamily = btn.dataset.schoolFamily;
        state.selectedProgram = null;
        mentorsPanel.classList.add("hidden");
        renderProgramsForSelectedSchool();
      });
    });
}

function renderProgramsForSelectedSchool() {
  const school = state.selectedSchool;
  if (!school) return;

  const visiblePrograms = getVisibleProgramsForSchool(school);
  const toplinePrograms = visiblePrograms.length ? visiblePrograms : school.programs;

  selectedSchoolName.textContent = school.school;
  selectedSchoolTierTag.textContent = getTierSummary(toplinePrograms);
  selectedSchoolProgramTag.textContent = getProgramsLabel(toplinePrograms);
  selectedSchoolSubtext.textContent = `${visiblePrograms.length} available program${visiblePrograms.length === 1 ? "" : "s"} shown at this institution`;

  if (selectedSchoolIcon) {
    selectedSchoolIcon.innerHTML = school.logo_url && school.logo_url.trim() !== "" ?
      `<img class="university-logo-image" src="${escapeHtml(school.logo_url.startsWith('http') || school.logo_url.startsWith('/') ? school.logo_url : '/' + school.logo_url)}" alt="${escapeHtml(school.school)}" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" /><i data-lucide="building-2" class="university-logo-fallback-icon" style="display:none;"></i>` :
      `<i data-lucide="building-2"></i>`;
  }

  renderSchoolProgramFilters(school);

  if (!visiblePrograms.length) {
    programGrid.innerHTML = `
      <div class="empty-state">
        <h3>No programs in this category</h3>
        <p>This school does not currently have programs listed for that filter combination.</p>
      </div>
    `;
    lucide.createIcons();
    return;
  }

  programGrid.innerHTML = visiblePrograms
    .map((program) => {
      const isSelected = state.selectedProgram === program.name;

      return `
        <div class="program-card ${isSelected ? "selected" : ""}" data-program="${escapeHtml(program.name)}">
          <div>
            <div class="program-top">
              <div class="program-icon">
                <i data-lucide="${escapeHtml(getProgramIcon(program))}"></i>
              </div>
              <h3 class="program-title">${escapeHtml(program.name)}</h3>
            </div>

            <p class="program-desc">${escapeHtml(getProgramDescription(program))}</p>
          </div>

          <div class="program-footer">
            <div class="program-tags">
              <span class="program-family-pill">${escapeHtml(getProgramFamily(program))}</span>
              <span class="program-tier-pill">${escapeHtml(program.tierLabel || program.tier)}</span>
            </div>
            <div class="program-cta-group">
              <span class="program-cta">View available mentors</span>
              <i data-lucide="arrow-right-circle" class="cta-icon"></i>
            </div>
          </div>
        </div>
      `;
    })
    .join("");

  lucide.createIcons();

  programGrid.querySelectorAll(".program-card").forEach((card) => {
    card.addEventListener("click", () => {
      const selectedProgram = school.programs.find(
        (program) => program.name === card.dataset.program,
      );
      if (!selectedProgram) return;

      state.selectedProgram = selectedProgram.name;
      renderProgramsForSelectedSchool();
      renderMentorsForProgram(school, selectedProgram);
    });
  });
}

function renderMentorsForProgram(school, program) {
  const mentors = Array.isArray(program.mentors) ? program.mentors : [];

  mentorPanelTitle.textContent = `${school.school} • ${getProgramFamily(program)}`;
  mentorPanelSubtext.textContent = `Mentors available from the ${program.name} pathway at ${school.school}.`;
  mentorsPanel.classList.remove("hidden");

  if (!mentors.length) {
    mentorGrid.innerHTML = `
      <div class="empty-state">
        <h3>No mentors available yet</h3>
        <p>There are no active mentors currently linked to ${program.name} at ${school.school}.</p>
      </div>
    `;
    lucide.createIcons();
    return;
  }

  mentorGrid.innerHTML = mentors
    .map(
      (mentor) => `
        <article class="mentor-card">
          <div class="mentor-card-header">
            <div class="mentor-card-identity">
              <div class="mentor-avatar">
                ${mentorAvatarMarkup(mentor)}
              </div>

              <div>
                <div class="mentor-name">${escapeHtml(mentor.name)}</div>
                <div class="mentor-role">${escapeHtml(mentor.roleLabel || school.school)}</div>
              </div>
            </div>

            <div class="mentor-rating">&#9733; ${escapeHtml(mentor.score || "New")}</div>
          </div>

          <div class="mentor-office-hours">
            <span class="mentor-office-hours-title">Office Hours:</span>
            <span class="mentor-office-hours-time">${escapeHtml(mentor.officeHours || "Schedule coming soon")}</span>
          </div>

          <div class="read-more-block">
            <div class="mentor-note-box read-more-text">
              ${escapeHtml(mentor.description || "Available to support applications and next steps for this program.")}
            </div>
            <button class="read-more-btn" type="button">
              <span class="read-more-label">Read More</span>
              <span class="read-more-chevron">&#8964;</span>
            </button>
          </div>

          <div class="services-accordion open">
            <button class="services-toggle" type="button" aria-expanded="true">
              <span class="services-toggle-text">SERVICES OFFERED</span>
              <span class="services-toggle-icon">&or;</span>
            </button>

            <div class="services-dropdown">
              <div class="service-grid">
                ${mentorServicesMarkup(mentor)}
              </div>
            </div>
          </div>

          <div class="student-note-box">
            <div class="feedback-header">
              <div class="student-note-title">Recent Feedback</div>
              ${mentor.feedbackUrl ? `<a href="${escapeHtml(mentor.feedbackUrl)}" class="see-more-feedback">See more Feedback</a>` : ""}
            </div>

            <div class="read-more-block feedback-read-more">
              <p class="read-more-text">&ldquo;${escapeHtml(mentor.review || "Students value this mentor for practical, focused guidance.")}&rdquo;</p>
              <button class="read-more-btn" type="button">
                <span class="read-more-label">Read More</span>
                <span class="read-more-chevron">&#8964;</span>
              </button>
            </div>
          </div>

          ${mentor.canBook === false || !mentor.bookingUrl
            ? `<button class="book-now-btn" type="button" disabled>Current Mentor</button>`
            : `<a href="${escapeHtml(mentor.bookingUrl)}" class="book-now-btn">Book Now</a>`}
        </article>
      `,
    )
    .join("");

  lucide.createIcons();
  bindMentorCardInteractions();

  setTimeout(() => {
    window.scrollTo({
      top: mentorsPanel.offsetTop - 32,
      behavior: "smooth",
    });
  }, 40);
}

function showProgramsForSchool(school) {
  state.selectedSchool = school;
  renderProgramsForSelectedSchool();

  universitiesSection.classList.remove("active");
  programsSection.classList.add("active");

  window.scrollTo({ top: 0, behavior: "smooth" });
  lucide.createIcons();
}

function showUniversities() {
  state.selectedProgram = null;
  mentorsPanel.classList.add("hidden");
  programsSection.classList.remove("active");
  universitiesSection.classList.add("active");
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function renderAllUniversitiesView() {
  renderTierFilters();
  renderProgramFilters();
  renderInstitutions();
  if (schoolSearchInput) {
    schoolSearchInput.value = state.search;
  }
  lucide.createIcons();
}

if (backBtn) {
  backBtn.addEventListener("click", showUniversities);
}

if (seeAllMentorsBtn) {
  seeAllMentorsBtn.addEventListener("click", () => {
    window.location.href = "/student/mentors";
  });
}

if (topbarSearch) {
  topbarSearch.addEventListener("input", (event) => {
    state.search = event.target.value;
    if (schoolSearchInput) {
      schoolSearchInput.value = state.search;
    }
    renderAllUniversitiesView();
  });
}

if (schoolSearchInput) {
  schoolSearchInput.addEventListener("input", (event) => {
    state.search = event.target.value;
    if (topbarSearch) {
      topbarSearch.value = state.search;
    }
    renderAllUniversitiesView();
  });
}

renderAllUniversitiesView();

const menuBtn = document.getElementById("mobileMenuToggle");
const overlay = document.getElementById("sidebarOverlay");
const shell = document.querySelector(".app-shell");

if (menuBtn && shell) {
  menuBtn.onclick = () => shell.classList.add("sidebar-active");
}
if (overlay && shell) {
  overlay.onclick = () => shell.classList.remove("sidebar-active");
}

(function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  document.body.setAttribute("data-theme", savedTheme);
})();

if (themeToggle) {
  themeToggle.onclick = () => {
    const body = document.body;
    const currentTheme = body.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    body.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
  };
}

const navItems = document.querySelectorAll(".nav-item");

function setActiveNav() {
  const currentPath = window.location.pathname;

  navItems.forEach((item) => {
    const href = item.getAttribute("href");
    if (href && currentPath.startsWith(href)) {
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
