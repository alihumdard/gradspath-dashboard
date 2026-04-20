const officeHoursDataEl = document.getElementById("officeHoursData");
const mentors = officeHoursDataEl ? JSON.parse(officeHoursDataEl.textContent) : [];

const state = {
  mentorType: "Graduates",
  programType: "All",
  mentorSearch: "",
  schoolSearch: "",
};

const body = document.body;
const appShell = document.querySelector(".app-shell");
const themeToggle = document.getElementById("themeToggle");
const mobileMenuToggle = document.getElementById("mobileMenuToggle");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const mentorGrid = document.getElementById("mentorGrid");
const mentorSearchInput = document.getElementById("mentorSearch");
const schoolSearchInput = document.getElementById("schoolSearch");
const mentorCount = document.getElementById("mentorCount");
const resultsSummary = document.getElementById("resultsSummary");
const filterPills = document.querySelectorAll(".filter-pill");

function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  body.setAttribute("data-theme", savedTheme);
}

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = body.getAttribute("data-theme");
    const newTheme = currentTheme === "light" ? "dark" : "light";
    body.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
  });
}

if (mobileMenuToggle) {
  mobileMenuToggle.addEventListener("click", () => {
    appShell.classList.add("sidebar-active");
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener("click", () => {
    appShell.classList.remove("sidebar-active");
  });
}

function getSpotsNote(mentor) {
  if (mentor.spotsFilled === 1) {
    return "Only one student is currently booked. If no one else joins by the cutoff, that student may choose another eligible service.";
  }
  if (mentor.spotsFilled >= mentor.maxSpots) {
    return "This session is full for the upcoming run.";
  }
  if (mentor.spotsFilled === 0) {
    return "No students are currently booked. The upcoming office-hours session is open.";
  }
  return "Multiple students are already booked, so the session will stay focused on this week's designated service.";
}

function truncateText(text, limit = 120) {
  if (!text || text.length <= limit) return text || "";
  return text.slice(0, limit).trim() + "...";
}

function getDisplayProgramLine(mentor) {
  return `${mentor.programLabel} • ${mentor.school}`;
}

function renderMentorIcon(type) {
  const icons = {
    law: `<svg viewBox="0 0 24 24"><path d="M12 3 4 7v2h16V7l-8-4Zm-6 8h12v2H6v-2Zm1 4h10l1 5H6l1-5Z"/></svg>`,
    mba: `<svg viewBox="0 0 24 24"><path d="M3 6.5 12 3l9 3.5-9 3.5L3 6.5Zm3 5.2 6 2.3 6-2.3V16l-6 2.5L6 16v-4.3Zm-3 2.1 2 .8V18l7 3 7-3v-3.4l2-.8V18l-9 3.5L3 18v-4.2Z"/></svg>`,
    therapy: `<svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 0 0-5 5c0 3.9 5 9 5 9s5-5.1 5-9a5 5 0 0 0-5-5Zm0 7.1A2.1 2.1 0 1 1 12 5a2.1 2.1 0 0 1 0 4.1ZM5 20c0-2.8 3.1-4.5 7-4.5s7 1.7 7 4.5v1H5v-1Z"/></svg>`,
  };
  return icons[type] || icons.therapy;
}

function renderMentors() {
  const filtered = mentors.filter((mentor) => {
    const matchesMentorType = mentor.mentorType === state.mentorType;
    const matchesProgram =
      state.programType === "All" || mentor.program === state.programType;
    const matchesMentorSearch = mentor.name
      .toLowerCase()
      .includes(state.mentorSearch.toLowerCase());
    const matchesSchoolSearch = mentor.school
      .toLowerCase()
      .includes(state.schoolSearch.toLowerCase());
    return (
      matchesMentorType &&
      matchesProgram &&
      matchesMentorSearch &&
      matchesSchoolSearch
    );
  });

  mentorCount.textContent = filtered.length;
  resultsSummary.textContent = `${state.mentorType} • ${state.programType} • ${state.mentorSearch || "All Mentors"} • ${state.schoolSearch || "All Schools"}`;

  if (!filtered.length) {
    mentorGrid.innerHTML = `<div class="empty-state">No mentors match your current filters.</div>`;
    return;
  }

  mentorGrid.innerHTML = filtered
    .map((mentor) => {
      const isFull = mentor.spotsFilled >= mentor.maxSpots || !mentor.isBookable;
      const progress = mentor.maxSpots > 0 ? (mentor.spotsFilled / mentor.maxSpots) * 100 : 0;
      const shortDescription = truncateText(mentor.description, 120);

      return `
        <article class="mentor-card">
          <div class="card-header">
            <div class="mentor-meta">
              <div class="avatar-box">${renderMentorIcon(mentor.icon)}</div>
              <div class="meta-text">
                <h3>${mentor.name}</h3>
                <p>${getDisplayProgramLine(mentor)}</p>
              </div>
            </div>
            <div class="rating-pill">★ ${Number(mentor.rating || 5).toFixed(1)}</div>
          </div>

          <p class="office-hours-line">
            <span class="label">Office Hours:</span> ${mentor.officeHours}
          </p>

          <div class="description-block">
            <p class="description-text" 
               data-full="${(mentor.description || "").replace(/"/g, "&quot;")}" 
               data-short="${shortDescription.replace(/"/g, "&quot;")}" 
               data-expanded="false">
              ${shortDescription}
            </p>
            ${(mentor.description || "").length > 120 ? `<button class="read-more-btn" type="button">Read More</button>` : ""}
          </div>

          <div class="card-main-box">
            <div class="info-grid">
              <div class="info-item"><span class="info-label">This Week's Service</span><span class="info-value">${mentor.weeklyService}</span></div>
              <div class="info-item"><span class="info-label">Session Time</span><span class="info-value">${mentor.sessionTime}</span></div>
              <div class="info-item"><span class="info-label">Rotation</span><span class="info-value">${mentor.rotation}</span></div>
              <div class="info-item spots-item">
                <div class="spots-top"><span class="info-label">Spots Filled</span><span class="spots-count">${mentor.spotsFilled}/${mentor.maxSpots}</span></div>
                <div class="progress-track"><div class="progress-fill" style="width: ${progress}%"></div></div>
              </div>
            </div>

            <div class="spots-box">
              <p class="spots-line">${mentor.spotsFilled}/${mentor.maxSpots} spots filled</p>
              <p class="spots-note">${getSpotsNote(mentor)}</p>
            </div>

            <div class="services-display">
              <p class="services-title">Service Options</p>
              <div class="services-grid">
                ${(mentor.servicesOffered || []).map((service) => `<div class="service-card ${service === mentor.weeklyService ? "active" : ""}">${service}</div>`).join("")}
              </div>
            </div>

            <div class="card-action">
              <button class="book-btn ${isFull ? "full" : ""}" data-booking-url="${mentor.bookingUrl || "#"}" ${isFull ? "disabled" : ""}>
                ${isFull ? "Session Full" : "Book Now"}
              </button>
            </div>
          </div>
        </article>`;
    })
    .join("");

  attachEvents();
}

function attachEvents() {
  document.querySelectorAll(".read-more-btn").forEach((button) => {
    button.addEventListener("click", () => {
      const textEl = button.previousElementSibling;
      const expanded = textEl.dataset.expanded === "true";
      if (expanded) {
        textEl.textContent = textEl.dataset.short;
        textEl.dataset.expanded = "false";
        button.textContent = "Read More";
      } else {
        textEl.textContent = textEl.dataset.full;
        textEl.dataset.expanded = "true";
        button.textContent = "Show Less";
      }
    });
  });

  document.querySelectorAll(".book-btn").forEach((button) => {
    button.addEventListener("click", () => {
      if (button.classList.contains("full")) return;
      const bookingUrl = button.dataset.bookingUrl;
      if (bookingUrl) {
        window.location.href = bookingUrl;
      }
    });
  });
}

filterPills.forEach((pill) => {
  pill.addEventListener("click", () => {
    const group = pill.dataset.filterGroup;
    document
      .querySelectorAll(`.filter-pill[data-filter-group="${group}"]`)
      .forEach((item) => item.classList.remove("active"));
    pill.classList.add("active");
    state[group] = pill.dataset.value;
    renderMentors();
  });
});

mentorSearchInput?.addEventListener("input", (e) => {
  state.mentorSearch = e.target.value.trim();
  renderMentors();
});

schoolSearchInput?.addEventListener("input", (e) => {
  state.schoolSearch = e.target.value.trim();
  renderMentors();
});

function setActiveNav() {
  const currentPath = window.location.pathname;
  document.querySelectorAll(".nav-item").forEach((item) => {
    const href = item.getAttribute("href");
    if (href && currentPath.startsWith(href)) {
      item.classList.add("active");
    } else {
      item.classList.remove("active");
    }
  });
}

initTheme();
setActiveNav();
renderMentors();
