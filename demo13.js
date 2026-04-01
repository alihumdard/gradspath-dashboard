const mentors = [
  {
    id: 1,
    mentorType: "Graduates",
    name: "Dr. Sarah Jenkin",
    school: "Harvard",
    program: "Therapy",
    programLabel: "PhD Person",
    rating: 5.0,
    officeHours: "Every Tuesday at 5 PM EST",
    description:
      "Expert in grad school applications for STEM fields. I help with statement of purpose review, school selection strategy, interview confidence, research fit conversations, and application planning for students who want a more organized process from start to finish.",
    weeklyService: "Tutoring",
    sessionTime: "Tuesday, 5:00 PM EST",
    rotation: "Weekly",
    spotsFilled: 1,
    maxSpots: 3,
    servicesOffered: [
      "Tutoring",
      "Program Insights",
      "Interview Prep",
      "Application Review",
      "Gap Year Planning"
    ],
    icon: "therapy"
  },
  {
    id: 2,
    mentorType: "Graduates",
    name: "Daniel Ross",
    school: "Yale Law School",
    program: "Law",
    programLabel: "Law",
    rating: 5.0,
    officeHours: "Every Wednesday at 7 PM EST",
    description:
      "I help with law school applications, personal statements, addenda, interview preparation, and the transition into 1L. I especially enjoy helping students clarify their narrative and present a sharper application story.",
    weeklyService: "Program Insights",
    sessionTime: "Wednesday, 7:00 PM EST",
    rotation: "Biweekly",
    spotsFilled: 2,
    maxSpots: 3,
    servicesOffered: [
      "Tutoring",
      "Program Insights",
      "Interview Prep",
      "Application Review",
      "Gap Year Planning"
    ],
    icon: "law"
  },
  {
    id: 3,
    mentorType: "Graduates",
    name: "Michael Kim",
    school: "Wharton",
    program: "MBA",
    programLabel: "MBA",
    rating: 4.9,
    officeHours: "Every Thursday at 6 PM EST",
    description:
      "Former McKinsey consultant. I can help with case prep, MBA positioning, behavioral interview preparation, career narrative development, and school comparisons for students trying to decide where they fit best.",
    weeklyService: "Interview Prep",
    sessionTime: "Thursday, 6:00 PM EST",
    rotation: "Weekly",
    spotsFilled: 3,
    maxSpots: 3,
    servicesOffered: [
      "Tutoring",
      "Program Insights",
      "Interview Prep",
      "Application Review",
      "Gap Year Planning"
    ],
    icon: "mba"
  },
  {
    id: 4,
    mentorType: "Graduates",
    name: "Emma Torres",
    school: "Boston College",
    program: "Therapy",
    programLabel: "M.A. in Mental Health Counseling",
    rating: 4.9,
    officeHours: "Every Monday at 8 PM EST",
    description:
      "I can help students understand counseling programs, interview expectations, licensure questions, and different career paths across mental health training. I also support students comparing therapy-related graduate options.",
    weeklyService: "Program Insights",
    sessionTime: "Monday, 8:00 PM EST",
    rotation: "Weekly",
    spotsFilled: 1,
    maxSpots: 3,
    servicesOffered: [
      "Tutoring",
      "Program Insights",
      "Interview Prep",
      "Application Review",
      "Gap Year Planning"
    ],
    icon: "therapy"
  },
  {
    id: 5,
    mentorType: "Professionals",
    name: "Ava Mitchell",
    school: "Northwestern",
    program: "Law",
    programLabel: "Attorney",
    rating: 4.8,
    officeHours: "Every Friday at 1 PM EST",
    description:
      "Practicing attorney offering office hours for students exploring legal careers, internship pathways, application timing, and interview preparation. Great for students who want a practical perspective beyond the classroom.",
    weeklyService: "Interview Prep",
    sessionTime: "Friday, 1:00 PM EST",
    rotation: "Biweekly",
    spotsFilled: 0,
    maxSpots: 3,
    servicesOffered: [
      "Tutoring",
      "Program Insights",
      "Interview Prep",
      "Application Review",
      "Gap Year Planning"
    ],
    icon: "law"
  },
  {
    id: 6,
    mentorType: "Professionals",
    name: "Jordan Lee",
    school: "Columbia Business School",
    program: "MBA",
    programLabel: "MBA Professional",
    rating: 4.9,
    officeHours: "Every Sunday at 4 PM EST",
    description:
      "I support students thinking about MBA timing, admissions strategy, resume positioning, and professional storytelling. Sessions are especially useful for early-career students trying to decide whether business school makes sense.",
    weeklyService: "Tutoring",
    sessionTime: "Sunday, 4:00 PM EST",
    rotation: "Weekly",
    spotsFilled: 2,
    maxSpots: 3,
    servicesOffered: [
      "Tutoring",
      "Program Insights",
      "Interview Prep",
      "Application Review",
      "Gap Year Planning"
    ],
    icon: "mba"
  }
];

const mentorGrid = document.getElementById("mentorGrid");
const mentorSearchInput = document.getElementById("mentorSearch");
const schoolSearchInput = document.getElementById("schoolSearch");
const mentorCount = document.getElementById("mentorCount");
const resultsSummary = document.getElementById("resultsSummary");
const filterPills = document.querySelectorAll(".filter-pill");

const state = {
  mentorType: "Graduates",
  programType: "All",
  mentorSearch: "",
  schoolSearch: ""
};

function getSpotsNote(mentor) {
  if (mentor.spotsFilled === 1) {
    return "Only one student is currently booked. If no one else joins by the cutoff, this student may choose another eligible service shown below.";
  }

  if (mentor.spotsFilled >= mentor.maxSpots) {
    return "This session is full for this week.";
  }

  if (mentor.spotsFilled === 0) {
    return "No students are currently booked. The scheduled service for this week is currently open.";
  }

  return "Multiple students are already booked, so the session will stay focused on this week's designated rotating service.";
}

function truncateText(text, limit = 120) {
  if (text.length <= limit) return text;
  return text.slice(0, limit).trim() + "...";
}

function getDisplayProgramLine(mentor) {
  return `${mentor.programLabel} • ${mentor.school}`;
}

function renderMentorIcon(type) {
  if (type === "law") {
    return `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 3 4 7v2h16V7l-8-4Zm-6 8h12v2H6v-2Zm1 4h10l1 5H6l1-5Z"/>
      </svg>
    `;
  }

  if (type === "mba") {
    return `
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 6.5 12 3l9 3.5-9 3.5L3 6.5Zm3 5.2 6 2.3 6-2.3V16l-6 2.5L6 16v-4.3Zm-3 2.1 2 .8V18l7 3 7-3v-3.4l2-.8V18l-9 3.5L3 18v-4.2Z"/>
      </svg>
    `;
  }

  return `
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 2a5 5 0 0 0-5 5c0 3.9 5 9 5 9s5-5.1 5-9a5 5 0 0 0-5-5Zm0 7.1A2.1 2.1 0 1 1 12 5a2.1 2.1 0 0 1 0 4.1ZM5 20c0-2.8 3.1-4.5 7-4.5s7 1.7 7 4.5v1H5v-1Z"/>
    `;
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

  const mentorText = state.mentorSearch ? state.mentorSearch : "All Mentors";
  const schoolText = state.schoolSearch ? state.schoolSearch : "All Schools";

  resultsSummary.textContent = `${state.mentorType} • ${state.programType} • ${mentorText} • ${schoolText}`;

  if (!filtered.length) {
    mentorGrid.innerHTML = `
      <div class="empty-state">
        No mentors match your current filters.
      </div>
    `;
    return;
  }

  mentorGrid.innerHTML = filtered
    .map((mentor) => {
      const isFull = mentor.spotsFilled >= mentor.maxSpots;
      const progress = (mentor.spotsFilled / mentor.maxSpots) * 100;
      const shortDescription = truncateText(mentor.description, 120);

      return `
        <article class="mentor-card">
          <div class="card-header">
            <div class="mentor-meta">
              <div class="avatar-box">
                ${renderMentorIcon(mentor.icon)}
              </div>
              <div class="meta-text">
                <h3>${mentor.name}</h3>
                <p>${getDisplayProgramLine(mentor)}</p>
              </div>
            </div>

            <div class="rating-pill">★ ${mentor.rating.toFixed(1)}</div>
          </div>

          <p class="office-hours-line">
            <span class="label">Office Hours:</span> ${mentor.officeHours}
          </p>

          <div class="description-block">
            <p
              class="description-text"
              data-full="${mentor.description.replace(/"/g, "&quot;")}"
              data-short="${shortDescription.replace(/"/g, "&quot;")}"
              data-expanded="false"
            >
              ${shortDescription}
            </p>
            ${
              mentor.description.length > 120
                ? `<button class="read-more-btn" type="button">Read More</button>`
                : ""
            }
          </div>

          <div class="card-main-box">
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">This Week's Service</span>
                <span class="info-value">${mentor.weeklyService}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Session Time</span>
                <span class="info-value">${mentor.sessionTime}</span>
              </div>

              <div class="info-item">
                <span class="info-label">Rotation</span>
                <span class="info-value">${mentor.rotation}</span>
              </div>

              <div class="info-item spots-item">
                <div class="spots-top">
                  <span class="info-label">Spots Filled</span>
                  <span class="spots-count">${mentor.spotsFilled}/${mentor.maxSpots}</span>
                </div>
                <div class="progress-track">
                  <div class="progress-fill" style="width: ${progress}%"></div>
                </div>
              </div>
            </div>

            <div class="spots-box">
              <p class="spots-line">${mentor.spotsFilled}/${mentor.maxSpots} spots filled</p>
              <p class="spots-note">${getSpotsNote(mentor)}</p>
            </div>

            <div class="services-display">
              <p class="services-title">Service Options</p>
              <div class="services-grid">
                ${mentor.servicesOffered
                  .map(
                    (service) => `
                      <div class="service-card ${service === mentor.weeklyService ? "active" : ""}">
                        ${service}
                      </div>
                    `
                  )
                  .join("")}
              </div>
            </div>

            <div class="card-action">
              <button class="book-btn ${isFull ? "full" : ""}" ${isFull ? "disabled" : ""}>
                ${isFull ? "Session Full" : "Book Now"}
              </button>
            </div>
          </div>
        </article>
      `;
    })
    .join("");

  attachReadMoreEvents();
}

function attachReadMoreEvents() {
  const buttons = document.querySelectorAll(".read-more-btn");

  buttons.forEach((button) => {
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
}

filterPills.forEach((pill) => {
  pill.addEventListener("click", () => {
    const group = pill.dataset.filterGroup;
    const value = pill.dataset.value;

    document
      .querySelectorAll(`.filter-pill[data-filter-group="${group}"]`)
      .forEach((item) => item.classList.remove("active"));

    pill.classList.add("active");
    state[group] = value;
    renderMentors();
  });
});

mentorSearchInput.addEventListener("input", (e) => {
  state.mentorSearch = e.target.value.trim();
  renderMentors();
});

schoolSearchInput.addEventListener("input", (e) => {
  state.schoolSearch = e.target.value.trim();
  renderMentors();
});

renderMentors();