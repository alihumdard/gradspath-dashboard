const mentorData = [
  {
    id: 1,
    name: "Dr. Sarah Jenkin",
    initials: "SJ",
    category: "therapy",
    categoryLabel: "Therapy",
    profession: "graduate",
    school: "Harvard",
    degree: "PhD Person",
    rating: 5.0,
    reviews: 35,
    sessions: 49,
    officeHours: "Every Tuesday at 5 PM EST",
    description:
      "Expert in grad school applications for STEM fields. I help with statement of purpose review.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Tutoring",
      "Program Insights",
      "Interview Prep",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Boston College Student",
        date: "March 4, 2026",
        serviceUsed: "Application Review",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Very clear, practical advice that helped me improve my essays in one session.",
      },
      {
        student: "Villanova Student",
        date: "February 14, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 5,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Helped me understand differences across counseling and clinical routes.",
      },
      {
        student: "Fordham Student",
        date: "January 29, 2026",
        serviceUsed: "Office Hours",
        meetingRating: 4,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Helpful and easy to talk to. I left with better clarity about next steps.",
      },
    ],
  },
  {
    id: 2,
    name: "Michael Kim",
    initials: "MK",
    category: "mba",
    categoryLabel: "MBA",
    profession: "professional",
    school: "Wharton",
    degree: "MBA",
    rating: 4.9,
    reviews: 38,
    sessions: 52,
    officeHours: "Every Wednesday at 7 PM EST",
    description:
      "Former McKinsey consultant. I can help with case prep and business school interviews.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Interview Prep",
      "Program Insights",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Boston College Student",
        date: "March 2, 2026",
        serviceUsed: "Interview Prep",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Excellent mock interview and specific tips I could apply immediately.",
      },
      {
        student: "Villanova Student",
        date: "February 18, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 4,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Very strategic about my MBA story and helped me tighten my school list.",
      },
      {
        student: "NYU Student",
        date: "February 6, 2026",
        serviceUsed: "Office Hours",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Strong consulting advice and very honest about what programs made the most sense.",
      },
    ],
  },
  {
    id: 3,
    name: "Anita Lopez",
    initials: "AL",
    category: "law",
    categoryLabel: "Law",
    profession: "graduate",
    school: "Yale Law",
    degree: "JD",
    rating: 4.8,
    reviews: 33,
    sessions: 46,
    officeHours: "Every Monday at 6 PM EST",
    description:
      "Specializing in public interest law applications and LSAT strategy.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Tutoring",
      "Program Insights",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Boston College Student",
        date: "March 3, 2026",
        serviceUsed: "Application Review",
        meetingRating: 4,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Great breakdown of my school list and stronger essay structure.",
      },
      {
        student: "Villanova Student",
        date: "February 17, 2026",
        serviceUsed: "Tutoring",
        meetingRating: 5,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Very honest feedback on my personal statement and school balance.",
      },
    ],
  },
  {
    id: 4,
    name: "Rachel Stein",
    initials: "RS",
    category: "mba",
    categoryLabel: "MBA",
    profession: "graduate",
    school: "Harvard Business School",
    degree: "MBA",
    rating: 5.0,
    reviews: 29,
    sessions: 41,
    officeHours: "Every Thursday at 8 PM EST",
    description:
      "Focused on leadership essays, deferred MBA planning, and how to frame internships effectively.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Program Insights",
      "Interview Prep",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Boston College Student",
        date: "March 1, 2026",
        serviceUsed: "Application Review",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Super clear guidance on essays and how to make my experience sound more cohesive.",
      },
      {
        student: "Fordham Student",
        date: "February 11, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Helped me understand what mattered most in my application and what to leave out.",
      },
      {
        student: "Holy Cross Student",
        date: "January 26, 2026",
        serviceUsed: "Office Hours",
        meetingRating: 4,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Very helpful on deferred MBA planning and what experiences I should prioritize.",
      },
    ],
  },
  {
    id: 5,
    name: "Daniel Cho",
    initials: "DC",
    category: "mba",
    categoryLabel: "MBA",
    profession: "professional",
    school: "Stanford GSB",
    degree: "MBA",
    rating: 4.8,
    reviews: 25,
    sessions: 34,
    officeHours: "Every Tuesday at 8 PM EST",
    description:
      "Helpful for entrepreneurship-focused MBA applicants and school fit strategy.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Program Insights",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Northeastern Student",
        date: "February 24, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 4,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Really helped me sharpen why MBA and how to explain my startup experience.",
      },
    ],
  },
  {
    id: 6,
    name: "Priya Shah",
    initials: "PS",
    category: "mba",
    categoryLabel: "MBA",
    profession: "graduate",
    school: "Columbia Business School",
    degree: "MBA",
    rating: 4.9,
    reviews: 22,
    sessions: 31,
    officeHours: "Every Sunday at 6 PM EST",
    description:
      "Best for applicants targeting finance, consulting, and interview prep.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Interview Prep",
      "Program Insights",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Holy Cross Student",
        date: "February 12, 2026",
        serviceUsed: "Interview Prep",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Very practical and gave me exact steps to improve before applying.",
      },
      {
        student: "Boston College Student",
        date: "January 28, 2026",
        serviceUsed: "Office Hours",
        meetingRating: 4,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Clear advice on what to improve before interviews and which stories to use.",
      },
    ],
  },
  {
    id: 7,
    name: "Ethan Brooks",
    initials: "EB",
    category: "mba",
    categoryLabel: "MBA",
    profession: "professional",
    school: "Kellogg",
    degree: "MBA",
    rating: 4.7,
    reviews: 19,
    sessions: 26,
    officeHours: "Every Wednesday at 6 PM EST",
    description:
      "Strong for teamwork stories, leadership framing, and behavioral interview prep.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Interview Prep",
      "Program Insights",
    ],
    reviewList: [
      {
        student: "Boston College Student",
        date: "January 29, 2026",
        serviceUsed: "Interview Prep",
        meetingRating: 4,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Great at helping me turn vague experiences into stronger examples.",
      },
    ],
  },
  {
    id: 8,
    name: "Sophia Patel",
    initials: "SP",
    category: "mba",
    categoryLabel: "MBA",
    profession: "graduate",
    school: "MIT Sloan",
    degree: "MBA",
    rating: 4.9,
    reviews: 21,
    sessions: 28,
    officeHours: "Every Friday at 5 PM EST",
    description:
      "Best for analytics applicants, tech backgrounds, and story positioning.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Program Insights",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Villanova Student",
        date: "January 23, 2026",
        serviceUsed: "Application Review",
        meetingRating: 5,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "She made my tech background much easier to explain in MBA terms.",
      },
    ],
  },
  {
    id: 9,
    name: "James Carter",
    initials: "JC",
    category: "law",
    categoryLabel: "Law",
    profession: "professional",
    school: "Harvard Law",
    degree: "JD",
    rating: 5.0,
    reviews: 27,
    sessions: 39,
    officeHours: "Every Thursday at 6 PM EST",
    description:
      "Helpful for constitutional law interest, admissions positioning, and interview-style conversations.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Program Insights",
      "Interview Prep",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Fordham Student",
        date: "February 20, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "He helped me make my law school narrative much more focused.",
      },
    ],
  },
  {
    id: 10,
    name: "Maya Richardson",
    initials: "MR",
    category: "law",
    categoryLabel: "Law",
    profession: "graduate",
    school: "Columbia Law",
    degree: "JD",
    rating: 4.9,
    reviews: 24,
    sessions: 30,
    officeHours: "Every Tuesday at 7 PM EST",
    description:
      "Strong for LSAT mindset, statement editing, and application timing.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Tutoring",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Boston College Student",
        date: "February 8, 2026",
        serviceUsed: "Tutoring",
        meetingRating: 5,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Very direct and helpful. I left with a much better plan.",
      },
    ],
  },
  {
    id: 11,
    name: "Olivia Green",
    initials: "OG",
    category: "law",
    categoryLabel: "Law",
    profession: "professional",
    school: "NYU Law",
    degree: "JD",
    rating: 4.7,
    reviews: 20,
    sessions: 27,
    officeHours: "Every Monday at 7 PM EST",
    description:
      "Best for public service law, admissions strategy, and school selection.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Program Insights",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Holy Cross Student",
        date: "January 28, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 4,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "She gave me a much more realistic list and helped me think strategically.",
      },
    ],
  },
  {
    id: 12,
    name: "Noah Bennett",
    initials: "NB",
    category: "law",
    categoryLabel: "Law",
    profession: "graduate",
    school: "UChicago Law",
    degree: "JD",
    rating: 4.8,
    reviews: 18,
    sessions: 22,
    officeHours: "Every Wednesday at 5 PM EST",
    description:
      "Analytical feedback on essays, school fit, and admissions competitiveness.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Application Review",
      "Program Insights",
    ],
    reviewList: [
      {
        student: "Northeastern Student",
        date: "January 18, 2026",
        serviceUsed: "Application Review",
        meetingRating: 4,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Really strong at identifying weak points in my application.",
      },
    ],
  },
  {
    id: 13,
    name: "Emma Foster",
    initials: "EF",
    category: "law",
    categoryLabel: "Law",
    profession: "professional",
    school: "Penn Carey Law",
    degree: "JD",
    rating: 4.9,
    reviews: 19,
    sessions: 25,
    officeHours: "Every Thursday at 5 PM EST",
    description:
      "Strong on law school interviews, applications, and writing structure.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Interview Prep",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Boston College Student",
        date: "January 10, 2026",
        serviceUsed: "Interview Prep",
        meetingRating: 5,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "She made the whole process feel less overwhelming and more manageable.",
      },
    ],
  },
  {
    id: 14,
    name: "Elena Petrova",
    initials: "EP",
    category: "therapy",
    categoryLabel: "Therapy",
    profession: "professional",
    school: "Boston College",
    degree: "PhD",
    rating: 4.9,
    reviews: 26,
    sessions: 36,
    officeHours: "Every Friday at 6 PM EST",
    description:
      "Guidance on research fit, doctoral applications, and therapist training paths.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Program Insights",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Fordham Student",
        date: "February 27, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Very thoughtful advice and strong direction on research positioning.",
      },
    ],
  },
  {
    id: 15,
    name: "Laura Simmons",
    initials: "LS",
    category: "therapy",
    categoryLabel: "Therapy",
    profession: "graduate",
    school: "Northwestern",
    degree: "MA Counseling",
    rating: 4.8,
    reviews: 21,
    sessions: 29,
    officeHours: "Every Sunday at 4 PM EST",
    description:
      "Helpful for counseling psychology, master's programs, and licensure path questions.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Program Insights",
      "Tutoring",
    ],
    reviewList: [
      {
        student: "Boston College Student",
        date: "February 5, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 4,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "She explained the different therapy routes much more clearly than anyone else.",
      },
    ],
  },
  {
    id: 16,
    name: "Megan Walsh",
    initials: "MW",
    category: "therapy",
    categoryLabel: "Therapy",
    profession: "professional",
    school: "NYU",
    degree: "MSW",
    rating: 4.7,
    reviews: 17,
    sessions: 23,
    officeHours: "Every Tuesday at 4 PM EST",
    description:
      "Best for social work pathways, graduate school fit, and career direction.",
    services: ["Free Consultation", "Office Hours", "Program Insights"],
    reviewList: [
      {
        student: "Holy Cross Student",
        date: "January 31, 2026",
        serviceUsed: "Office Hours",
        meetingRating: 4,
        mentorKnowledge: "4",
        recommendation: "Yes",
        quickFeedback:
          "Very supportive and practical. I left with much more clarity.",
      },
    ],
  },
  {
    id: 17,
    name: "Julia Park",
    initials: "JP",
    category: "therapy",
    categoryLabel: "Therapy",
    profession: "graduate",
    school: "Vanderbilt",
    degree: "MEd",
    rating: 4.9,
    reviews: 20,
    sessions: 27,
    officeHours: "Every Wednesday at 4 PM EST",
    description:
      "Strong for school counseling, program comparisons, and application planning.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Program Insights",
      "Application Review",
    ],
    reviewList: [
      {
        student: "Villanova Student",
        date: "January 26, 2026",
        serviceUsed: "Program Insights",
        meetingRating: 5,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "Very organized and helped me narrow down where I should actually apply.",
      },
    ],
  },
  {
    id: 18,
    name: "Claire Thompson",
    initials: "CT",
    category: "therapy",
    categoryLabel: "Therapy",
    profession: "professional",
    school: "Boston University",
    degree: "PsyD",
    rating: 4.8,
    reviews: 18,
    sessions: 24,
    officeHours: "Every Thursday at 4 PM EST",
    description:
      "Helpful for PsyD fit, interview prep, and balancing clinical interests.",
    services: [
      "Free Consultation",
      "Office Hours",
      "Interview Prep",
      "Program Insights",
    ],
    reviewList: [
      {
        student: "Northeastern Student",
        date: "January 20, 2026",
        serviceUsed: "Interview Prep",
        meetingRating: 4,
        mentorKnowledge: "5",
        recommendation: "Yes",
        quickFeedback:
          "She was very honest and helped me better understand program fit.",
      },
    ],
  },
  {
    id: 19,
    name: "Olivia Mason",
    initials: "OM",
    category: "mba",
    categoryLabel: "MBA",
    profession: "graduate",
    school: "Duke Fuqua",
    degree: "MBA",
    rating: 4.9,
    reviews: 12,
    sessions: 17,
    officeHours: "Every Monday at 8 PM EST",
    description:
      "Helpful for deferred MBA planning and leadership story building.",
    services: ["Free Consultation", "Office Hours", "Program Insights"],
    reviewList: [],
  },
];

const categoryConfig = [
  {
    key: "mba",
    title: "MBA Mentors",
    subtitle: "Top feedback in business school advising.",
  },
  {
    key: "law",
    title: "Law Mentors",
    subtitle: "Strongest-rated law school mentors.",
  },
  {
    key: "therapy",
    title: "Therapy Mentors",
    subtitle: "Top mentors across therapy and psychology pathways.",
  },
];

const serviceIcons = {
  Tutoring: `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm8-1h2a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-7a2 2 0 0 0-2 2v1.2A4.9 4.9 0 0 1 11 9.5V10h2.5l2.2 2.2A1 1 0 0 0 17.4 12V11.4A2 2 0 0 0 16 10Zm-8 3c-3.2 0-6 1.6-6 3.6 0 .8.7 1.4 1.5 1.4h9c.8 0 1.5-.6 1.5-1.4C14 14.6 11.2 13 8 13Z"/></svg>`,
  "Program Insights": `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2 8l10 5 8.2-4.1V15H22V8L12 3Zm-6.8 8.8V15c0 1.9 3.1 3.5 6.8 3.5s6.8-1.6 6.8-3.5v-3.2L12 15.2l-6.8-3.4Z"/></svg>`,
  "Interview Prep": `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7V5a3 3 0 0 1 6 0v2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2Zm2 0h2V5a1 1 0 1 0-2 0v2Z"/></svg>`,
  "Application Review": `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5M9 16.2l5.9-5.9 1.8 1.8-5.9 5.9H9v-1.8Z"/></svg>`,
  "Gap Year Planning": `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm6.9 9h-3.1a15.7 15.7 0 0 0-1.2-4A8.1 8.1 0 0 1 18.9 11ZM12 4.1c1 1.1 2 3.4 2.4 6H9.6c.4-2.6 1.4-4.9 2.4-6ZM5.1 13h3.1c.2 1.4.6 2.8 1.2 4a8.1 8.1 0 0 1-4.3-4Zm3.1-2H5.1a8.1 8.1 0 0 1 4.3-4c-.6 1.2-1 2.6-1.2 4Zm3.8 8c-1-1.1-2-3.4-2.4-6h4.8c-.4 2.6-1.4 4.9-2.4 6Zm2.6-2c.6-1.2 1-2.6 1.2-4h3.1a8.1 8.1 0 0 1-4.3 4Z"/></svg>`,
  "Office Hours": `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 5v4.4l3 1.8-.8 1.4L11 12V7Z"/></svg>`,
};

const knowledgeLabels = {
  1: "Very Low — Not prepared",
  2: "Low — Some gaps",
  3: "Moderate — Good overall",
  4: "High — Strong guidance",
  5: "Excellent — Very strong insight",
};

const categorySections = document.getElementById("categorySections");
const mentorSearch = document.getElementById("mentorSearch");
const sortMentors = document.getElementById("sortMentors");
const programFilter = document.getElementById("programFilter");
const professionFilter = document.getElementById("professionFilter");

const mentorModal = document.getElementById("mentorModal");
const closeModal = document.getElementById("closeModal");
const directFormView = document.getElementById("directFormView");

let expandedCategories = new Set();

function getActualReviewCount(mentor) {
  return Array.isArray(mentor.reviewList) ? mentor.reviewList.length : 0;
}

function sortMentorList(list) {
  const sortValue = sortMentors.value;

  return [...list].sort((a, b) => {
    if (sortValue === "rating") return b.rating - a.rating;
    if (sortValue === "reviews")
      return getActualReviewCount(b) - getActualReviewCount(a);
    if (sortValue === "sessions") return b.sessions - a.sessions;
    if (sortValue === "name") return a.name.localeCompare(b.name);
    return 0;
  });
}

function getFilteredMentors() {
  const query = mentorSearch.value.toLowerCase().trim();
  const selectedProgram = programFilter.value;
  const selectedProfession = professionFilter.value;

  return mentorData.filter((mentor) => {
    const searchableReviews = (mentor.reviewList || [])
      .map(
        (review) =>
          `${review.student} ${review.serviceUsed} ${review.quickFeedback}`,
      )
      .join(" ");

    const searchableText = [
      mentor.name,
      mentor.school,
      mentor.degree,
      mentor.categoryLabel,
      mentor.profession,
      mentor.description,
      mentor.officeHours || "",
      ...(mentor.services || []),
      searchableReviews,
    ]
      .join(" ")
      .toLowerCase();

    const matchesSearch = !query || searchableText.includes(query);
    const matchesProgram =
      selectedProgram === "all" || mentor.category === selectedProgram;
    const matchesProfession =
      selectedProfession === "all" || mentor.profession === selectedProfession;

    return matchesSearch && matchesProgram && matchesProfession;
  });
}

function createReviewPreview(review, mentorId, reviewIndex) {
  return `
    <div class="student-note-card">
      <div class="student-note-top">
        <div>
          <div class="student-note-student">${review.student}</div>
          <div class="student-note-date">${review.date}</div>
        </div>
        <button
          class="student-note-view-btn"
          type="button"
          data-mentor-id="${mentorId}"
          data-review-index="${reviewIndex}"
        >
          View
        </button>
      </div>
      <p class="student-note-preview">“${review.quickFeedback}”</p>
    </div>
  `;
}

function createServicesMarkup(mentor) {
  const services = mentor.services || [];
  return services
    .map((service) => `<div class="service-pill">${service}</div>`)
    .join("");
}

function createUserNotesSection(mentor) {
  const reviews = mentor.reviewList || [];
  const initialReviews = reviews.slice(0, 2);
  const extraReviews = reviews.slice(2);
  const hasExtras = extraReviews.length > 0;

  if (reviews.length === 0) {
    return `
      <div class="student-note-label">USERS NOTES</div>
      <div class="no-notes-card">No user notes yet.</div>
    `;
  }

  return `
    <div class="student-note-label">USERS NOTES</div>

    <div class="student-notes-stack">
      ${initialReviews
        .map((review, index) => createReviewPreview(review, mentor.id, index))
        .join("")}
    </div>

    ${
      hasExtras
        ? `
          <div class="more-reviews-wrap" id="more-reviews-${mentor.id}">
            ${extraReviews
              .map((review, index) =>
                createReviewPreview(review, mentor.id, index + 2),
              )
              .join("")}
          </div>

          <div class="inline-readmore-wrap">
            <button
              class="inline-readmore-btn toggle-inline-btn"
              type="button"
              data-mentor-id="${mentor.id}"
            >
              Read More
            </button>
          </div>
        `
        : ""
    }
  `;
}

function createMentorCard(mentor) {
  const professionLabel =
    mentor.profession === "graduate"
      ? "Graduate Mentor"
      : "Professional Mentor";

  const reviewCount = getActualReviewCount(mentor);
  const card = document.createElement("article");
  card.className = "mentor-card";

  card.innerHTML = `
    <div class="mentor-card-top">
      <div class="mentor-identity">
        <div class="mentor-avatar">${mentor.initials}</div>
        <div>
          <h3 class="mentor-name">${mentor.name}</h3>
          <p class="mentor-meta">${mentor.degree} • ${mentor.school}</p>
        </div>
      </div>
      <div class="rating-badge">★ ${mentor.rating.toFixed(1)}</div>
    </div>

    <div class="mentor-office-hours">
      <span class="office-hours-label">Office Hours:</span>
      <span class="office-hours-value">${mentor.officeHours || "Available weekly"}</span>
    </div>

    <p class="mentor-description">${mentor.description}</p>

    <div class="description-readmore-wrap">
      <button
        class="description-readmore-btn mentor-details-toggle"
        type="button"
        data-mentor-id="${mentor.id}"
      >
        Read More
      </button>
    </div>

    <div class="mentor-details-expand" id="mentor-details-${mentor.id}">
      <div class="services-label">SERVICES OFFERED</div>
      <div class="services-pill-grid">
        ${createServicesMarkup(mentor)}
      </div>
    </div>

    ${createUserNotesSection(mentor)}

    <div class="card-bottom">
      <div class="card-stats">${reviewCount} review${reviewCount === 1 ? "" : "s"}</div>
      <div class="card-type">${professionLabel}</div>
    </div>

    <button class="book-now-btn" type="button">Book Now</button>
  `;

  const detailsToggleBtn = card.querySelector(".mentor-details-toggle");
  if (detailsToggleBtn) {
    detailsToggleBtn.addEventListener("click", () => {
      const detailsWrap = card.querySelector(`#mentor-details-${mentor.id}`);
      const isOpen = detailsWrap.classList.contains("open");
      detailsWrap.classList.toggle("open", !isOpen);
      detailsToggleBtn.textContent = isOpen ? "Read More" : "Read Less";
    });
  }

  const inlineToggleBtn = card.querySelector(".toggle-inline-btn");
  if (inlineToggleBtn) {
    inlineToggleBtn.addEventListener("click", () => {
      const moreWrap = card.querySelector(`#more-reviews-${mentor.id}`);
      if (!moreWrap) return;

      const isOpen = moreWrap.classList.contains("open");
      moreWrap.classList.toggle("open", !isOpen);
      inlineToggleBtn.textContent = isOpen ? "Read More" : "Read Less";
    });
  }

  card.querySelectorAll(".student-note-view-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      openReviewModal(
        Number(btn.dataset.mentorId),
        Number(btn.dataset.reviewIndex),
      );
    });
  });

  return card;
}

function renderCategories() {
  const filteredMentors = getFilteredMentors();
  categorySections.innerHTML = "";

  let categoriesToRender = categoryConfig;

  if (programFilter.value !== "all") {
    categoriesToRender = categoryConfig.filter(
      (category) => category.key === programFilter.value,
    );
  }

  categoriesToRender.forEach((category) => {
    const allInCategory = filteredMentors.filter(
      (mentor) => mentor.category === category.key,
    );
    const sorted = sortMentorList(allInCategory);
    const isExpanded = expandedCategories.has(category.key);
    const visibleMentors = isExpanded ? sorted : sorted.slice(0, 6);

    const section = document.createElement("section");
    section.className = "category-section";

    section.innerHTML = `
      <div class="category-header">
        <div class="category-title-wrap">
          <h2>${category.title}</h2>
          <p>${category.subtitle}</p>
        </div>
        <div class="category-actions">
          ${
            sorted.length > 6
              ? `<button class="text-link-btn" data-category="${category.key}">
                  ${isExpanded ? "Show fewer" : "See all"}
                </button>`
              : ""
          }
        </div>
      </div>
    `;

    if (!visibleMentors.length) {
      const empty = document.createElement("div");
      empty.className = "empty-category";
      empty.textContent = "No mentors match your filters in this category.";
      section.appendChild(empty);
    } else {
      const grid = document.createElement("div");
      grid.className = "mentor-grid";

      visibleMentors.forEach((mentor) => {
        grid.appendChild(createMentorCard(mentor));
      });

      section.appendChild(grid);
    }

    categorySections.appendChild(section);

    const toggleBtn = section.querySelector(".text-link-btn");
    if (toggleBtn) {
      toggleBtn.addEventListener("click", () => {
        if (expandedCategories.has(category.key)) {
          expandedCategories.delete(category.key);
        } else {
          expandedCategories.add(category.key);
        }
        renderCategories();
      });
    }
  });
}

function renderStars(rating) {
  const safeRating = Number(rating) || 0;
  let stars = "";
  for (let i = 1; i <= 5; i += 1) {
    stars += i <= safeRating ? "★" : "☆";
  }
  return stars;
}

function createServiceGrid(activeService) {
  const serviceNames = [
    "Tutoring",
    "Program Insights",
    "Interview Prep",
    "Application Review",
    "Gap Year Planning",
    "Office Hours",
  ];

  return serviceNames
    .map((service) => {
      const activeClass = service === activeService ? "active" : "";
      return `
        <div class="direct-service-card ${activeClass}">
          <div class="direct-service-icon">
            ${serviceIcons[service]}
          </div>
          <span>${service}</span>
        </div>
      `;
    })
    .join("");
}

function openReviewModal(mentorId, reviewIndex) {
  const mentor = mentorData.find((item) => item.id === mentorId);
  if (!mentor) return;

  const review = mentor.reviewList[reviewIndex];
  if (!review) return;

  const professionLabel =
    mentor.profession === "graduate"
      ? "Graduate Mentor"
      : "Professional Mentor";

  directFormView.innerHTML = `
    <div class="direct-header">
      <span class="direct-eyebrow">Post-Meeting Review</span>
      <h2>Feedback After Your Meeting</h2>
      <p>
        Full direct feedback form submission for this mentor session.
      </p>
    </div>

    <section class="direct-section">
      <h3 class="direct-section-title">Session Details</h3>
      <div class="direct-grid">
        <div class="direct-field">
          <div class="direct-field-label">Full Name of Mentor</div>
          <div class="direct-field-value">${mentor.name}</div>
        </div>
        <div class="direct-field">
          <div class="direct-field-label">Program</div>
          <div class="direct-field-value">${mentor.categoryLabel}</div>
        </div>
        <div class="direct-field">
          <div class="direct-field-label">Mentor Type</div>
          <div class="direct-field-value">${professionLabel}</div>
        </div>
        <div class="direct-field">
          <div class="direct-field-label">School</div>
          <div class="direct-field-value">${mentor.school}</div>
        </div>
        <div class="direct-field">
          <div class="direct-field-label">Degree</div>
          <div class="direct-field-value">${mentor.degree}</div>
        </div>
        <div class="direct-field">
          <div class="direct-field-label">Date of Session</div>
          <div class="direct-field-value">${review.date}</div>
        </div>
        <div class="direct-field full">
          <div class="direct-field-label">Service Used</div>
          <div class="direct-service-grid">
            ${createServiceGrid(review.serviceUsed)}
          </div>
        </div>
      </div>
    </section>

    <section class="direct-section">
      <h3 class="direct-section-title">User Submission</h3>
      <div class="direct-grid">
        <div class="direct-field">
          <div class="direct-field-label">Student</div>
          <div class="direct-field-value">${review.student}</div>
        </div>
        <div class="direct-field">
          <div class="direct-field-label">Overall Session Rating</div>
          <div class="direct-field-value">
            <span class="direct-stars">${renderStars(review.meetingRating)}</span>
          </div>
        </div>
        <div class="direct-field">
          <div class="direct-field-label">Mentor Preparedness and Knowledge</div>
          <div class="direct-field-value">
            <span class="direct-scale-badge">${knowledgeLabels[review.mentorKnowledge] || "Not provided"}</span>
          </div>
        </div>
        <div class="direct-field full">
          <div class="direct-field-label">Would You Recommend This Mentor?</div>
          <div class="direct-field-value">
            <span class="direct-binary-badge ${review.recommendation === "Yes" ? "yes" : "no"}">
              ${review.recommendation}
            </span>
          </div>
        </div>
        <div class="direct-field full">
          <div class="direct-field-label">Quick Feedback</div>
          <p class="direct-feedback-text">“${review.quickFeedback}”</p>
        </div>
      </div>
    </section>
  `;

  mentorModal.classList.add("open");
  document.body.style.overflow = "hidden";
}

function closeMentorModal() {
  mentorModal.classList.remove("open");
  document.body.style.overflow = "";
  directFormView.innerHTML = "";
}

closeModal.addEventListener("click", closeMentorModal);

mentorModal.addEventListener("click", (event) => {
  if (event.target === mentorModal) {
    closeMentorModal();
  }
});

mentorSearch.addEventListener("input", renderCategories);
sortMentors.addEventListener("change", renderCategories);
programFilter.addEventListener("change", renderCategories);
professionFilter.addEventListener("change", renderCategories);

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && mentorModal.classList.contains("open")) {
    closeMentorModal();
  }
});

renderCategories();

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

// Sidebar navigation logic
const navItems = document.querySelectorAll(".nav-item");

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

setActiveNav();

navItems.forEach((item) => {
  item.addEventListener("click", () => {
    navItems.forEach((nav) => nav.classList.remove("active"));
    item.classList.add("active");
  });
});
