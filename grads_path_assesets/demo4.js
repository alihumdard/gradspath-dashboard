const graduatesProgramOptions = [
  { value: "all", label: "All" },
  { value: "mba", label: "MBA" },
  { value: "law", label: "Law" },
  { value: "therapy", label: "Therapy" },
];

const professionalsProgramOptions = [
  { value: "all", label: "All" },
  { value: "accountant", label: "Accountant" },
  { value: "lawyer", label: "Lawyer" },
  { value: "therapist", label: "Therapist" },
  { value: "nurse", label: "Nurse" },
];

const defaultServices = [
  "Free Consultation",
  "Office Hours",
  "Tutoring",
  "Program Insights",
  "Interview Prep",
  "Application Review",
];

const mentors = [
  {
    id: 1,
    type: "graduates",
    name: "Dr. Sarah Jenkin",
    initials: "SJ",
    category: "mba",
    categoryLabel: "PhD Person",
    school: "Harvard",
    rating: 5.0,
    officeHours: "Every Tuesday at 5 PM EST",
    bio: "Expert in grad school applications for STEM fields. I help with statement of purpose review.",
    bioExtra:
      "I also help with school selection, application positioning, and interview prep.",
    services: defaultServices,
    reviewShort:
      "Very clear, practical advice that helped me improve my essays in one session.",
    reviewExtra:
      "She was direct, organized, and gave me very clear next steps.",
  },
  {
    id: 2,
    type: "graduates",
    name: "Daniel Ross",
    initials: "DR",
    category: "law",
    categoryLabel: "Law",
    school: "Yale Law School",
    rating: 5.0,
    officeHours: "Every Thursday at 7 PM EST",
    bio: "I help with law school applications, personal statements, and 1L transition advice.",
    bioExtra:
      "I can also support LSAT planning, school fit, and stronger application strategy.",
    services: defaultServices,
    reviewShort:
      "Daniel gave clear feedback on my law school materials and was very honest in the best way.",
    reviewExtra: "He helped me rethink my personal statement and timing.",
  },
  {
    id: 3,
    type: "graduates",
    name: "Michael Kim",
    initials: "MK",
    category: "mba",
    categoryLabel: "MBA",
    school: "Wharton",
    rating: 4.9,
    officeHours: "Every Wednesday at 7 PM EST",
    bio: "Former McKinsey consultant. I can help with case prep and business school interviews.",
    bioExtra:
      "Happy to help with resume strategy, mock interviews, and school selection.",
    services: defaultServices,
    reviewShort:
      "Michael was incredibly helpful and direct. He made the MBA process feel clearer and more manageable.",
    reviewExtra: "His advice was practical and easy to use immediately.",
  },
  {
    id: 4,
    type: "graduates",
    name: "Emma Torres",
    initials: "ET",
    category: "therapy",
    categoryLabel: "Therapy",
    school: "Boston College",
    rating: 4.9,
    officeHours: "Every Monday at 6 PM EST",
    bio: "I can help students understand counseling programs, interview expectations, and career paths.",
    bioExtra:
      "Especially helpful for practicum questions and personal statements.",
    services: defaultServices,
    reviewShort:
      "Emma was thoughtful, knowledgeable, and helped me better understand counseling programs.",
    reviewExtra: "She made the process feel less overwhelming.",
  },
  {
    id: 5,
    type: "graduates",
    name: "Ava Bennett",
    initials: "AB",
    category: "law",
    categoryLabel: "Law",
    school: "Harvard Law School",
    rating: 4.9,
    officeHours: "Every Friday at 5 PM EST",
    bio: "Support for law school essays, interviews, application review, and overall planning.",
    bioExtra:
      "I can help applicants sharpen their narrative and school choice strategy.",
    services: defaultServices,
    reviewShort:
      "Ava gave very strong feedback on my materials and helped me improve both clarity and confidence.",
    reviewExtra: "Her advice felt realistic and strategic.",
  },
  {
    id: 6,
    type: "graduates",
    name: "James Liu",
    initials: "JL",
    category: "mba",
    categoryLabel: "MBA",
    school: "Stanford GSB",
    rating: 4.8,
    officeHours: "Every Sunday at 4 PM EST",
    bio: "Happy to help with school selection, applications, and preparing for admissions interviews.",
    bioExtra:
      "Strong fit for consulting, tech, and entrepreneurship applicants.",
    services: defaultServices,
    reviewShort:
      "James was easy to talk to and really helpful for organizing my school list and interview strategy.",
    reviewExtra: "I left with clearer priorities and better positioning.",
  },
  {
    id: 7,
    type: "professionals",
    name: "Rachel Cohen",
    initials: "RC",
    category: "accountant",
    categoryLabel: "Accountant",
    school: "CPA • Deloitte",
    rating: 5.0,
    officeHours: "Every Tuesday at 6 PM EST",
    bio: "Licensed accountant helping students understand accounting careers, recruiting, and professional readiness.",
    bioExtra:
      "Great for internships, firm recruiting, and long-term business career planning.",
    services: defaultServices,
    reviewShort:
      "Rachel gave me a much clearer understanding of accounting recruiting and what employers are actually looking for.",
    reviewExtra: "She broke down next steps really well.",
  },
  {
    id: 8,
    type: "professionals",
    name: "Marcus Hall",
    initials: "MH",
    category: "lawyer",
    categoryLabel: "Lawyer",
    school: "Corporate Law",
    rating: 4.9,
    officeHours: "Every Thursday at 8 PM EST",
    bio: "Practicing lawyer offering support on legal careers, interviewing, and professional development.",
    bioExtra:
      "Helpful for understanding practice areas and workplace expectations.",
    services: defaultServices,
    reviewShort:
      "Marcus was very informative and helped me better understand both the field itself and how to think about my next steps.",
    reviewExtra: "He explained things clearly and realistically.",
  },
  {
    id: 9,
    type: "professionals",
    name: "Leah Morris",
    initials: "LM",
    category: "therapist",
    categoryLabel: "Therapist",
    school: "Licensed Therapist",
    rating: 4.9,
    officeHours: "Every Wednesday at 5 PM EST",
    bio: "Licensed therapist offering field insight, interview prep, and career guidance for helping professions.",
    bioExtra:
      "Helpful for counseling work, graduate training, and licensure questions.",
    services: defaultServices,
    reviewShort:
      "Leah was thoughtful and deeply knowledgeable. She helped me understand what the field really looks like in practice.",
    reviewExtra: "Her guidance felt grounded and reassuring.",
  },
  {
    id: 10,
    type: "professionals",
    name: "Natalie Brooks",
    initials: "NB",
    category: "nurse",
    categoryLabel: "Nurse",
    school: "Registered Nurse",
    rating: 4.8,
    officeHours: "Every Monday at 7 PM EST",
    bio: "Registered nurse helping students explore healthcare careers, patient-facing work, and professional growth.",
    bioExtra:
      "Great for nursing responsibilities, healthcare environments, and career development.",
    services: defaultServices,
    reviewShort:
      "Natalie gave me a much better picture of what nursing work looks like day to day and what skills matter most.",
    reviewExtra: "She was encouraging and specific.",
  },
];

// UI References
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

let activeTab = "graduates";
let activeProgram = "all";
let mentorSearchTerm = "";
let schoolSearchTerm = "";

// Load saved theme from localStorage, default to 'light'
(function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  body.setAttribute("data-theme", savedTheme);
})();

// Theme Switching
if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = body.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    body.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
  });
}

// Sidebar Controls
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

// Logic Functions
function getProgramOptionsForTab() {
  return activeTab === "graduates"
    ? graduatesProgramOptions
    : professionalsProgramOptions;
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
          data-program="${option.value}"
          type="button"
        >
          ${option.label}
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

  filtered.sort((a, b) => b.rating - a.rating);
  return filtered;
}

function createMentorCard(mentor) {
  const article = document.createElement("article");
  article.className = "mentor-card";

  article.innerHTML = `
    <div class="mentor-card-top">
      <div class="mentor-card-identity">
        <div class="mentor-avatar">${mentor.initials}</div>
        <div class="mentor-headings">
          <div class="mentor-name">${mentor.name}</div>
          <div class="mentor-role">${mentor.categoryLabel} • ${mentor.school}</div>
        </div>
      </div>
      <div class="mentor-rating">★ ${mentor.rating.toFixed(1)}</div>
    </div>

    <div class="mentor-office-hours">
      <span class="mentor-office-hours-title">Office Hours:</span>
      <span class="mentor-office-hours-time">${mentor.officeHours}</span>
    </div>

    <div class="bio-read-block">
      <div class="bio-short">${mentor.bio}</div>
      <div class="bio-full">${mentor.bioExtra}</div>
      <button class="read-more-btn mentor-bio-btn" type="button">Read More ▼</button>
    </div>

    <div class="services-accordion">
      <button class="services-toggle" type="button">
        <span class="services-toggle-text">Services Offered</span>
        <span class="services-toggle-icon">▼</span>
      </button>

      <div class="services-dropdown">
        <div class="service-grid">
          ${mentor.services.map((service) => `<div class="service-pill">${service}</div>`).join("")}
        </div>
      </div>
    </div>

    <div class="feedback-box">
      <div class="feedback-top-row">
        <div class="feedback-title">Recent Feedback</div>
        <button class="feedback-link-btn" type="button">See more Feedback</button>
      </div>

      <p class="feedback-short">“${mentor.reviewShort}”</p>
      <div class="feedback-full">“${mentor.reviewExtra}”</div>
      <button class="feedback-read-more-btn" type="button">Read More ▼</button>
    </div>

    <button class="book-now-btn" type="button">Book Now</button>
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
    window.location.href = "demo11.html";
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
  const options = getProgramOptionsForTab();
  const activeProgramOption = options.find(
    (option) => option.value === activeProgram,
  );
  const programLabel = activeProgramOption ? activeProgramOption.label : "All";

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

// Action Listeners
tabPills.forEach((pill) => {
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

// Initialization
renderProgramPills();
renderMentors();
