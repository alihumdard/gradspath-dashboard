const institutions = [
  {
    school: "Alliant International University",
    programs: [
      {
        name: "Master of Science in Marriage and Family Therapy",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Azusa Pacific University",
    programs: [
      {
        name: "Psy.D. in Clinical Psychology",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Baylor University",
    programs: [
      {
        name: "Law",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "Boston College",
    programs: [
      {
        name: "MBA",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
      { name: "Law", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      {
        name: "M.A. in Mental Health Counseling",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Boston University",
    programs: [
      {
        name: "MBA",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "Carnegie Mellon University",
    programs: [
      { name: "MBA", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
    ],
  },
  {
    school: "Columbia University",
    programs: [
      { name: "MBA", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      { name: "Law", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      { name: "MSW", tier: "Top 25 Programs", tierLabel: "Top Rated" },
    ],
  },
  {
    school: "Dartmouth College",
    programs: [
      { name: "MBA", tier: "Elite Programs", tierLabel: "Elite Programs" },
    ],
  },
  {
    school: "Duke University",
    programs: [
      { name: "MBA", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      { name: "Law", tier: "Elite Programs", tierLabel: "Elite Programs" },
    ],
  },
  {
    school: "Emory University",
    programs: [
      {
        name: "Law",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "Fuller Theological Seminary",
    programs: [
      {
        name: "Psy.D. in Clinical Psychology",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Georgetown University",
    programs: [
      { name: "Law", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
    ],
  },
  {
    school: "Harvard University",
    programs: [
      { name: "MBA", tier: "Elite Programs", tierLabel: "Elite Programs" },
      { name: "Law", tier: "Elite Programs", tierLabel: "Elite Programs" },
    ],
  },
  {
    school: "James Madison University",
    programs: [
      {
        name: "Master of Science in Counseling",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Johns Hopkins University",
    programs: [
      {
        name: "Master of Science in Counseling",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Loma Linda University",
    programs: [
      {
        name: "Master of Science in Marriage and Family Therapy",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Massachusetts Institute of Technology",
    programs: [
      { name: "MBA", tier: "Elite Programs", tierLabel: "Elite Programs" },
    ],
  },
  {
    school: "Northwestern University",
    programs: [
      { name: "MBA", tier: "Elite Programs", tierLabel: "Elite Programs" },
      { name: "Law", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      {
        name: "Master of Science in Marriage and Family Therapy",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Palo Alto University",
    programs: [
      {
        name: "PAU–Stanford PsyD Consortium",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "San Diego State University",
    programs: [
      {
        name: "Master of Science in Marriage and Family Therapy",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Southern Methodist University",
    programs: [
      {
        name: "MBA",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "Stanford University",
    programs: [
      { name: "MBA", tier: "Elite Programs", tierLabel: "Elite Programs" },
      { name: "Law", tier: "Elite Programs", tierLabel: "Elite Programs" },
    ],
  },
  {
    school: "Syracuse University",
    programs: [
      {
        name: "Master of Science in Counseling",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
      {
        name: "Master of Science in Marriage and Family Therapy",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Texas A&M University",
    programs: [
      {
        name: "MBA",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "The Wright Institute",
    programs: [
      {
        name: "Psy.D. in Clinical Psychology",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "University of Alabama",
    programs: [
      {
        name: "Law",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "University of California, Berkeley",
    programs: [
      { name: "MBA", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      { name: "MSW", tier: "Top 25 Programs", tierLabel: "Top Rated" },
    ],
  },
  {
    school: "University of California, Irvine",
    programs: [
      {
        name: "MBA",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
      {
        name: "Law",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "University of California, Los Angeles",
    programs: [
      { name: "MBA", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      { name: "Law", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
    ],
  },
  {
    school: "University of Chicago",
    programs: [
      { name: "MBA", tier: "Elite Programs", tierLabel: "Elite Programs" },
      { name: "Law", tier: "Elite Programs", tierLabel: "Elite Programs" },
      { name: "MSW", tier: "Top 25 Programs", tierLabel: "Top Rated" },
    ],
  },
  {
    school: "University of Colorado Denver",
    programs: [
      {
        name: "Master of Science in Counseling",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "University of Florida",
    programs: [
      {
        name: "Master of Science in Counseling",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "University of Michigan",
    programs: [
      { name: "MSW", tier: "Top 25 Programs", tierLabel: "Top Rated" },
    ],
  },
  {
    school: "University of Minnesota Twin Cities",
    programs: [
      {
        name: "MBA",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "University of North Carolina at Chapel Hill",
    programs: [
      { name: "Law", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      { name: "MSW", tier: "Top 25 Programs", tierLabel: "Top Rated" },
    ],
  },
  {
    school: "University of Notre Dame",
    programs: [
      {
        name: "MBA",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
      { name: "Law", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
    ],
  },
  {
    school: "University of Pennsylvania",
    programs: [
      { name: "MBA", tier: "Elite Programs", tierLabel: "Elite Programs" },
      { name: "Law", tier: "Elite Programs", tierLabel: "Elite Programs" },
    ],
  },
  {
    school: "University of San Diego",
    programs: [
      {
        name: "Master of Science in Marriage and Family Therapy",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "University of Southern California",
    programs: [
      {
        name: "Law",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "University of Virginia",
    programs: [
      { name: "MBA", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      { name: "Law", tier: "Elite Programs", tierLabel: "Elite Programs" },
    ],
  },
  {
    school: "Villanova University",
    programs: [
      {
        name: "Law",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "Wake Forest University",
    programs: [
      {
        name: "Law",
        tier: "Regional Programs",
        tierLabel: "Regional Programs",
      },
    ],
  },
  {
    school: "Washington University in St. Louis",
    programs: [
      { name: "MSW", tier: "Top 25 Programs", tierLabel: "Top Rated" },
    ],
  },
  {
    school: "UNC Greensboro",
    programs: [
      {
        name: "Master of Science in Counseling",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Wright State University",
    programs: [
      {
        name: "Psy.D. in Clinical Psychology",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
  {
    school: "Yale University",
    programs: [
      { name: "MBA", tier: "Top 25 Programs", tierLabel: "Top 25 Programs" },
      { name: "Law", tier: "Elite Programs", tierLabel: "Elite Programs" },
    ],
  },
  {
    school: "Yeshiva University",
    programs: [
      {
        name: "Psy.D. in Clinical Psychology",
        tier: "Top 25 Programs",
        tierLabel: "Top Rated",
      },
    ],
  },
];

const programDetails = {
  MBA: {
    icon: "briefcase-business",
    description:
      "Connect with MBA mentors for applications, essays, interviews, school selection, and career positioning.",
  },
  Law: {
    icon: "scale",
    description:
      "Work with law mentors on school lists, essays, admissions strategy, LSAT direction, and legal career planning.",
  },
  "Master of Science in Counseling": {
    icon: "heart-handshake",
    description:
      "Explore counseling-focused mentorship for applications, interviews, and the path into professional counseling training.",
  },
  "M.A. in Mental Health Counseling": {
    icon: "heart-handshake",
    description:
      "Connect with mentors for mental health counseling applications, program fit, and preparation for graduate clinical training.",
  },
  MSW: {
    icon: "users-round",
    description:
      "Connect with mentors for social work applications, field education insight, and career direction across practice settings.",
  },
  "Master of Science in Marriage and Family Therapy": {
    icon: "home",
    description:
      "Meet mentors for MFT program selection, applications, interviews, and family-systems clinical pathways.",
  },
  "Psy.D. in Clinical Psychology": {
    icon: "brain",
    description:
      "Get support for PsyD applications, clinical training questions, interviews, and doctoral program positioning.",
  },
  "PAU–Stanford PsyD Consortium": {
    icon: "brain",
    description:
      "Explore mentorship for the PAU–Stanford PsyD Consortium, including applications, fit, interviews, and clinical training questions.",
  },
};

const programOrder = [
  "MBA",
  "Law",
  "Master of Science in Counseling",
  "M.A. in Mental Health Counseling",
  "MSW",
  "Master of Science in Marriage and Family Therapy",
  "Psy.D. in Clinical Psychology",
  "PAU–Stanford PsyD Consortium",
];

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
const dashboardBtn = document.getElementById("dashboardBtn");
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

const state = {
  tier: "All",
  family: "All",
  search: "",
  selectedSchool: null,
  selectedSchoolFamily: "All",
  selectedProgram: null,
};

function escapeHtml(str) {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function getProgramFamily(programName) {
  if (programName === "MBA") return "MBA";
  if (programName === "Law") return "Law";
  return "Therapy";
}

function getProgramIcon(programName) {
  return programDetails[programName]?.icon || "graduation-cap";
}

function getProgramDescription(programName) {
  return (
    programDetails[programName]?.description ||
    "Explore mentor support for this graduate program."
  );
}

function sortPrograms(programs) {
  return [...programs].sort((a, b) => {
    const aIndex = programOrder.indexOf(a.name);
    const bIndex = programOrder.indexOf(b.name);
    return (aIndex === -1 ? 999 : aIndex) - (bIndex === -1 ? 999 : bIndex);
  });
}

function getProgramFamiliesFromPrograms(programs) {
  return [
    ...new Set(programs.map((program) => getProgramFamily(program.name))),
  ];
}

function getProgramsLabel(programs) {
  const families = getProgramFamiliesFromPrograms(programs);
  return families.join(" · ");
}

function getTierSummary(programs) {
  const tiers = [
    ...new Set(programs.map((program) => program.tierLabel || program.tier)),
  ];
  if (tiers.length === 0) return "Programs";
  if (tiers.length === 1) return tiers[0];
  return "Multiple tiers";
}

const normalizedInstitutions = institutions
  .map((item) => ({
    school: item.school,
    programs: sortPrograms(item.programs),
  }))
  .sort((a, b) => a.school.localeCompare(b.school));

function matchesGlobalFilters(program) {
  const matchesTier = state.tier === "All" || program.tier === state.tier;
  const matchesFamily =
    state.family === "All" || getProgramFamily(program.name) === state.family;
  return matchesTier && matchesFamily;
}

function schoolMatchesSearch(school) {
  if (!state.search.trim()) return true;
  return school.school
    .toLowerCase()
    .includes(state.search.trim().toLowerCase());
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
      getProgramFamily(program.name) === state.selectedSchoolFamily;

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

  resultsBadge.textContent = badgeParts.join(" · ");
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
      const tierSummary = getTierSummary(matchingPrograms);
      const familySummary = getProgramsLabel(matchingPrograms);

      return `
        <div class="university-card" data-school="${escapeHtml(school.school)}">
          <div class="university-icon-wrap">
            <i data-lucide="building-2"></i>
          </div>

          <div class="university-name">${escapeHtml(school.school)}</div>

          <div class="university-meta">
            <span class="university-tier-pill">${escapeHtml(tierSummary)}</span>
            <span class="university-family-pill">${escapeHtml(familySummary)}</span>
          </div>
        </div>
      `;
    })
    .join("");

  lucide.createIcons();

  universityGrid.querySelectorAll(".university-card").forEach((card) => {
    card.addEventListener("click", () => {
      const schoolName = card.dataset.school;
      const school = normalizedInstitutions.find(
        (item) => item.school === schoolName,
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
      programsRespectingTier.map((program) => getProgramFamily(program.name)),
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
  const toplinePrograms = visiblePrograms.length
    ? visiblePrograms
    : school.programs;

  selectedSchoolName.textContent = school.school;
  selectedSchoolTierTag.textContent = getTierSummary(toplinePrograms);
  selectedSchoolProgramTag.textContent = getProgramsLabel(toplinePrograms);
  selectedSchoolSubtext.textContent = `${visiblePrograms.length} available program${visiblePrograms.length === 1 ? "" : "s"} shown at this institution`;

  renderSchoolProgramFilters(school);

  if (!visiblePrograms.length) {
    programGrid.innerHTML = `
      <div class="empty-state">
        <h3>No programs in this category</h3>
        <p>This school does not currently have mentors listed for that program type and tier combination.</p>
      </div>
    `;
    lucide.createIcons();
    return;
  }

  programGrid.innerHTML = visiblePrograms
    .map((program) => {
      const family = getProgramFamily(program.name);
      const isSelected = state.selectedProgram === program.name;

      return `
        <div class="program-card ${isSelected ? "selected" : ""}" data-program="${escapeHtml(program.name)}">
          <div>
            <div class="program-top">
              <div class="program-icon">
                <i data-lucide="${escapeHtml(getProgramIcon(program.name))}"></i>
              </div>
              <h3 class="program-title">${escapeHtml(program.name)}</h3>
            </div>

            <p class="program-desc">${escapeHtml(getProgramDescription(program.name))}</p>
          </div>

          <div class="program-footer">
            <div class="program-footer-left">
              <span class="program-family-pill">${escapeHtml(family)}</span>
              <span class="program-tier-pill">${escapeHtml(program.tierLabel || program.tier)}</span>
            </div>
            <span class="program-cta">View mentors</span>
          </div>
        </div>
      `;
    })
    .join("");

  lucide.createIcons();

  programGrid.querySelectorAll(".program-card").forEach((card) => {
    card.addEventListener("click", () => {
      const programName = card.dataset.program;
      const selectedProgram = school.programs.find(
        (program) => program.name === programName,
      );
      if (!selectedProgram) return;

      state.selectedProgram = selectedProgram.name;
      renderProgramsForSelectedSchool();
      renderMentorsForProgram(school, selectedProgram);
    });
  });
}

const dummyMentorPools = {
  MBA: [
    {
      name: "Michael Kim",
      schoolTag: "Wharton",
      score: "4.9",
      tags: ["Case Prep", "Interviews", "Applications"],
    },
    {
      name: "Ava Mitchell",
      schoolTag: "Kellogg",
      score: "4.8",
      tags: ["Essays", "School Fit", "Career Strategy"],
    },
    {
      name: "Liam Foster",
      schoolTag: "Booth",
      score: "4.9",
      tags: ["Leadership", "Applications", "Consulting"],
    },
  ],
  Law: [
    {
      name: "Emma Sullivan",
      schoolTag: "Stanford Law",
      score: "4.9",
      tags: ["Personal Statement", "School List", "Admissions"],
    },
    {
      name: "Noah Reed",
      schoolTag: "Yale Law",
      score: "4.8",
      tags: ["Applications", "Strategy", "Pre-Law"],
    },
    {
      name: "Charlotte Hayes",
      schoolTag: "UChicago Law",
      score: "4.9",
      tags: ["Essays", "Interviews", "School Fit"],
    },
  ],
  Therapy: [
    {
      name: "Mia Turner",
      schoolTag: "Clinical Training",
      score: "4.9",
      tags: ["Program Fit", "Applications", "Interviews"],
    },
    {
      name: "Amelia Brooks",
      schoolTag: "Counseling Pathways",
      score: "4.8",
      tags: ["Personal Statement", "Clinical Fit", "Graduate School"],
    },
    {
      name: "Ella Griffin",
      schoolTag: "Therapy Programs",
      score: "4.9",
      tags: ["MSW", "Counseling", "Program Selection"],
    },
  ],
};

function hashString(input) {
  let hash = 0;
  for (let i = 0; i < input.length; i += 1) {
    hash = (hash * 31 + input.charCodeAt(i)) % 1000000007;
  }
  return hash;
}

function getMentorDescription(family, programName) {
  if (family === "MBA") {
    return `Former consulting and admissions-focused mentor who can help with ${programName.toLowerCase()} strategy, essays, interviews, and school positioning.`;
  }
  if (family === "Law") {
    return `Experienced law school mentor who can help with personal statements, admissions strategy, interviews, and school selection for ${programName}.`;
  }
  return `Graduate mentor who can help with program fit, applications, interview preparation, and understanding the path into ${programName.toLowerCase()}.`;
}

function getDummyMentors(school, program) {
  const family = getProgramFamily(program.name);
  const pool = dummyMentorPools[family];
  const baseIndex =
    hashString(`${school.school}-${program.name}-${program.tier}`) %
    pool.length;

  return [0, 1, 2].map((offset) => {
    const mentor = pool[(baseIndex + offset) % pool.length];
    return {
      ...mentor,
      roleLabel: `${family} • ${mentor.schoolTag}`,
      description: getMentorDescription(family, program.name),
      icon:
        family === "MBA"
          ? "briefcase-business"
          : family === "Law"
            ? "scale"
            : "brain",
    };
  });
}

function renderMentorsForProgram(school, program) {
  const mentors = getDummyMentors(school, program);

  mentorPanelTitle.textContent = `${school.school} · ${getProgramFamily(program.name)}`;
  mentorPanelSubtext.textContent = `Mentors available from the ${program.name} pathway at ${school.school}.`;
  mentorsPanel.classList.remove("hidden");

  mentorGrid.innerHTML = mentors
    .map(
      (mentor) => `
        <div class="mentor-card">
          <div class="mentor-card-top">
            <div class="mentor-top-left">
              <div class="mentor-avatar">
                <i data-lucide="${escapeHtml(mentor.icon)}"></i>
              </div>

              <div class="mentor-header-copy">
                <h4 class="mentor-name">${escapeHtml(mentor.name)}</h4>
                <p class="mentor-role">${escapeHtml(mentor.roleLabel)}</p>
              </div>
            </div>

            <div class="mentor-score">
              <i data-lucide="star"></i>
              ${escapeHtml(mentor.score)}
            </div>
          </div>

          <p class="mentor-desc">${escapeHtml(mentor.description)}</p>

          <div class="mentor-tags">
            ${mentor.tags.map((tag) => `<span class="mentor-tag">${escapeHtml(tag)}</span>`).join("")}
          </div>
        </div>
      `,
    )
    .join("");

  lucide.createIcons();

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

backBtn.addEventListener("click", showUniversities);

dashboardBtn.addEventListener("click", () => {
  window.location.href = "/dashboard";
});

seeAllMentorsBtn.addEventListener("click", () => {
  window.location.href = "/mentors";
});

schoolSearchInput.addEventListener("input", (event) => {
  state.search = event.target.value;
  renderAllUniversitiesView();
});

renderAllUniversitiesView();

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
