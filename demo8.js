const usersData = [
  {
    id: 1,
    name: "Tyler Cogan",
    email: "tyler.cogan@bc.edu",
    notes: [
      {
        mentor: "Daniel Cho",
        mentorEmail: "daniel.cho@gradspaths.com",
        service: "Application Review",
        date: "March 1, 2026",
        sessionWork:
          "We reviewed Tyler’s core MBA narrative, worked through the structure of his primary essay, and focused on how to frame his startup experience in a more cohesive and credible way. We also discussed which examples felt strongest for leadership and impact.",
        nextSteps:
          "The user should now revise the main essay with a tighter story arc, reduce repetition across examples, and make the long-term goals section more specific. He needs the most help with making the overall application feel consistent across essays and resume.",
        sessionOutcome:
          "The session gave the user a much clearer direction for how to rewrite the essay and what experiences should lead the application story. He left with a more focused plan and a better sense of what admissions readers need to understand quickly.",
        sessionReflection:
          "Strength: The user was highly engaged, open to direct feedback, and had strong raw material to work with. Challenge: Several examples were trying to do too much at once, so the story lost some clarity and needed sharper prioritization.",
        otherNotes:
          "The user responds well to straightforward and practical guidance. Another session would be useful after the revised draft is complete so the final structure and tone can be polished.",
      },
      {
        mentor: "Marcus Lee",
        mentorEmail: "marcus.lee@gradspaths.com",
        service: "Office Hours",
        date: "February 11, 2026",
        sessionWork:
          "We talked through overall MBA application priorities, which parts of Tyler’s background felt most differentiating, and how to lead with the strongest themes instead of trying to include everything equally.",
        nextSteps:
          "The user should decide on the top few themes that define his candidacy and use those as the foundation for the rest of the application. He needs the most help with narrowing and prioritizing.",
        sessionOutcome:
          "The session helped simplify the overall application strategy. The user became more confident about what should be emphasized first and what details can be saved for supporting sections.",
        sessionReflection:
          "Strength: Asked thoughtful questions and was very open to feedback. Challenge: Still deciding which differentiators matter most, which can create a less focused overall story.",
        otherNotes:
          "Good momentum overall. He should continue with a more intentional school-specific plan once the core story is fully locked in.",
      },
      {
        mentor: "Rachel Greene",
        mentorEmail: "rachel.greene@gradspaths.com",
        service: "Program Insights",
        date: "January 24, 2026",
        sessionWork:
          "We discussed program fit, how Tyler should think about culture and outcomes when building his school list, and how school selection connects back to the story he is telling in the application.",
        nextSteps:
          "He should now narrow the list more intentionally and make sure each school clearly connects to his goals. He needs help making school research more targeted and useful for essay writing.",
        sessionOutcome:
          "The user left with a better sense of how to separate reach, target, and fit schools, and how to use that research in a more practical way.",
        sessionReflection:
          "Strength: Strong ambition and clear energy. Challenge: At times the school list felt broader than necessary and not yet tied tightly enough to the overall narrative.",
        otherNotes:
          "This note would be helpful for any mentor reviewing school-specific essay strategy later.",
      },
    ],
  },
  {
    id: 2,
    name: "Emma Rodriguez",
    email: "emma.rodriguez@bc.edu",
    notes: [
      {
        mentor: "Rachel Greene",
        mentorEmail: "rachel.greene@gradspaths.com",
        service: "Program Insights",
        date: "March 2, 2026",
        sessionWork:
          "We compared counseling and therapy-related graduate programs, discussed differences in training models, and talked through what factors should matter most when evaluating fit.",
        nextSteps:
          "The user should continue researching practicum structure, licensure pathways, and the kinds of populations each program prepares students to serve. She needs the most help narrowing the list.",
        sessionOutcome:
          "The session helped move the user from a broad list toward a more thoughtful and realistic shortlist of programs based on values and career direction.",
        sessionReflection:
          "Strength: Very reflective and engaged. Challenge: She still needs more clarity around ideal program structure and future setting.",
        otherNotes:
          "A follow-up session would be useful after she completes another round of school research.",
      },
      {
        mentor: "Sarah Jenkin",
        mentorEmail: "sarah.jenkin@gradspaths.com",
        service: "Tutoring",
        date: "February 18, 2026",
        sessionWork:
          "We focused on writing clarity, especially how to explain prior experience in a cleaner and more concise way in application materials.",
        nextSteps:
          "She should revise with shorter and more specific examples, and cut introductory material that delays the strongest point. She needs the most help with concise written communication.",
        sessionOutcome:
          "The user came away with a better understanding of how to write more directly and how to reduce unnecessary detail without losing meaning.",
        sessionReflection:
          "Strength: Good motivation and clear values behind her goals. Challenge: The writing can drift broad before landing on the strongest point.",
        otherNotes:
          "Very coachable and likely to improve fast with another revision pass.",
      },
      {
        mentor: "Alyssa Hart",
        mentorEmail: "alyssa.hart@gradspaths.com",
        service: "Application Review",
        date: "January 30, 2026",
        sessionWork:
          "We reviewed her personal statement draft and spent time on the opening section, transitions, and how her experiences connect back to her long-term counseling goals.",
        nextSteps:
          "The user should rewrite the opening paragraph, strengthen transitions between experiences, and connect her background more directly to why this path makes sense now.",
        sessionOutcome:
          "The draft became much clearer conceptually, even though the writing still needs another pass. She now has a better structure to build from.",
        sessionReflection:
          "Strength: Sincere and mission-driven voice. Challenge: Some important ideas were buried too deep in the draft and need to come forward earlier.",
        otherNotes:
          "Would be a strong candidate for another application-focused session.",
      },
    ],
  },
  {
    id: 3,
    name: "Michael Tran",
    email: "michael.tran@bc.edu",
    notes: [
      {
        mentor: "Alyssa Hart",
        mentorEmail: "alyssa.hart@gradspaths.com",
        service: "Interview Prep",
        date: "March 1, 2026",
        sessionWork:
          "We practiced interview responses, especially around why law, leadership, and challenge-based questions. We focused on keeping answers tighter and more structured.",
        nextSteps:
          "He should continue mock interview work and practice answering common questions in shorter formats. He needs the most help with pacing and concision.",
        sessionOutcome:
          "The user became more aware of when his answers were running too long and started to improve transitions and structure during the session.",
        sessionReflection:
          "Strength: Thoughtful answers and strong reasons for pursuing law. Challenge: Tends to over-answer and move away from the main question.",
        otherNotes:
          "One more mock session would likely help a lot before formal interviews.",
      },
      {
        mentor: "Daniel Cho",
        mentorEmail: "daniel.cho@gradspaths.com",
        service: "Application Review",
        date: "February 11, 2026",
        sessionWork:
          "We reviewed the law school personal statement, especially the pacing of the introduction and how quickly the strongest material appears in the draft.",
        nextSteps:
          "He should cut the slower setup and lead with a stronger point much earlier. He needs the most help with getting to the strongest material faster.",
        sessionOutcome:
          "The user left with a clear idea of what to cut, where to tighten, and how to create a stronger opening section.",
        sessionReflection:
          "Strength: Solid baseline content and clear motivation. Challenge: The strongest material appears too late in the draft.",
        otherNotes:
          "The writing can become much stronger with a tighter structure and stronger opening paragraph.",
      },
    ],
  },
  {
    id: 4,
    name: "Sophia Patel",
    email: "sophia.patel@bc.edu",
    notes: [
      {
        mentor: "Rachel Greene",
        mentorEmail: "rachel.greene@gradspaths.com",
        service: "Program Insights",
        date: "March 3, 2026",
        sessionWork:
          "We discussed the types of therapy programs that best align with her goals, values, and preferred populations, and reviewed differences in program structure.",
        nextSteps:
          "She should narrow options based on training model, licensure path, and clinical interests. She needs the most help clarifying long-term direction.",
        sessionOutcome:
          "The session helped the user start separating programs that sound appealing from programs that are actually the best fit for her goals.",
        sessionReflection:
          "Strength: Very reflective and asks strong questions. Challenge: Still needs more clarity on long-term direction and ideal populations.",
        otherNotes:
          "Would benefit from another session after doing additional research on programs.",
      },
    ],
  },
  {
    id: 5,
    name: "James Wilson",
    email: "james.wilson@bc.edu",
    notes: [
      {
        mentor: "Marcus Lee",
        mentorEmail: "marcus.lee@gradspaths.com",
        service: "Office Hours",
        date: "March 4, 2026",
        sessionWork:
          "We reviewed his overall application strategy and talked about how essays, resume, and short answers should all reinforce the same story.",
        nextSteps:
          "He should make each application piece feel more aligned and reduce any generic phrasing. He needs the most help with consistency across materials.",
        sessionOutcome:
          "The user now has a clearer framework for how each application piece should support the others instead of feeling separate.",
        sessionReflection:
          "Strength: Leadership examples are strong and there is good self-awareness. Challenge: Needs more school-specific framing and fewer generic phrases.",
        otherNotes:
          "The user is in a strong position once the materials become more consistent.",
      },
    ],
  },
  {
    id: 6,
    name: "Olivia Chen",
    email: "olivia.chen@bc.edu",
    notes: [
      {
        mentor: "Daniel Cho",
        mentorEmail: "daniel.cho@gradspaths.com",
        service: "Application Review",
        date: "March 5, 2026",
        sessionWork:
          "We reviewed application essays and discussed how to keep the writing polished while still sounding more personal and authentic.",
        nextSteps:
          "She should add more reflection and a more personal tone to the essays. She needs the most help with voice and authenticity.",
        sessionOutcome:
          "The user left with a clearer understanding of how formal writing can sometimes create distance and how reflection can improve warmth and connection.",
        sessionReflection:
          "Strength: Very polished and prepared. Challenge: The writing can feel slightly too formal and less human.",
        otherNotes: "A final pass focused just on voice would help a lot.",
      },
    ],
  },
];

usersData.forEach((user) => {
  user.sessions = user.notes.length;
});

const usersGrid = document.getElementById("usersGrid");
const resultsCount = document.getElementById("resultsCount");
const emptyState = document.getElementById("emptyState");
const userSearch = document.getElementById("userSearch");
const mentorSearch = document.getElementById("mentorSearch");

const noteModal = document.getElementById("noteModal");
const closeModal = document.getElementById("closeModal");

const modalInitials = document.getElementById("modalInitials");
const modalUserName = document.getElementById("modalUserName");
const modalMeta = document.getElementById("modalMeta");
const modalSessions = document.getElementById("modalSessions");
const modalDate = document.getElementById("modalDate");
const modalService = document.getElementById("modalService");

const modalDetailUserName = document.getElementById("modalDetailUserName");
const modalUserEmail = document.getElementById("modalUserEmail");
const modalMentorName = document.getElementById("modalMentorName");
const modalMentorEmail = document.getElementById("modalMentorEmail");
const modalDetailDate = document.getElementById("modalDetailDate");
const modalDetailService = document.getElementById("modalDetailService");

const answer1 = document.getElementById("answer1");
const answer2 = document.getElementById("answer2");
const answer3 = document.getElementById("answer3");
const answer4 = document.getElementById("answer4");
const answer5 = document.getElementById("answer5");

const expandedUsers = new Set();

function getInitials(name) {
  return name
    .split(" ")
    .map((part) => part[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

function sortNotesByDate(notes) {
  return [...notes].sort((a, b) => new Date(b.date) - new Date(a.date));
}

function escapeHtml(text) {
  return String(text)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function renderUsers() {
  const userTerm = userSearch.value.trim().toLowerCase();
  const mentorTerm = mentorSearch.value.trim().toLowerCase();

  const filteredUsers = usersData.filter((user) => {
    const matchesUser = !userTerm || user.name.toLowerCase().includes(userTerm);

    const matchesMentor =
      !mentorTerm ||
      user.notes.some((note) => note.mentor.toLowerCase().includes(mentorTerm));

    return matchesUser && matchesMentor;
  });

  usersGrid.innerHTML = "";
  resultsCount.textContent = `${filteredUsers.length} ${filteredUsers.length === 1 ? "user" : "users"}`;

  if (!filteredUsers.length) {
    emptyState.classList.remove("hidden");
    return;
  }

  emptyState.classList.add("hidden");

  filteredUsers.forEach((user) => {
    const notes = sortNotesByDate(user.notes);
    const isExpanded = expandedUsers.has(user.id);
    const visibleNotes = isExpanded ? notes : notes.slice(0, 2);
    const hasMoreThanTwo = notes.length > 2;

    const card = document.createElement("article");
    card.className = "user-card";

    card.innerHTML = `
      <div class="user-head">
        <div class="user-avatar">${getInitials(user.name)}</div>
        <div class="user-info">
          <h5>${escapeHtml(user.name)}</h5>
          <p>${user.sessions} ${user.sessions === 1 ? "session" : "sessions"}</p>
        </div>
        <div class="sessions-pill">${notes.length} ${notes.length === 1 ? "Note" : "Notes"}</div>
      </div>

      <div class="notes-label">Mentor Notes</div>

      <div class="note-list">
        ${visibleNotes
          .map(
            (note, index) => `
          <button
            class="note-box"
            data-user-id="${user.id}"
            data-note-index="${index}"
            type="button"
          >
            <div>
              <div class="note-box-top">
                <div class="note-box-main">
                  <p class="note-box-name">${escapeHtml(user.name)}</p>
                  <p class="note-box-date">${escapeHtml(note.date)}</p>
                </div>
                <span class="note-view-btn">View</span>
              </div>
              <p class="note-box-preview">“${escapeHtml(note.sessionWork)}”</p>
            </div>
          </button>
        `,
          )
          .join("")}
      </div>

      ${
        hasMoreThanTwo
          ? `
        <div class="note-list-footer">
          <button
            class="read-more-btn"
            data-user-toggle="${user.id}"
            type="button"
          >
            ${isExpanded ? "Read Less" : "Read More"}
          </button>
        </div>
      `
          : ""
      }
    `;

    usersGrid.appendChild(card);
  });

  attachNoteEvents(filteredUsers);
  attachReadMoreEvents();
}

function attachNoteEvents(filteredUsers) {
  const noteButtons = document.querySelectorAll(".note-box");

  noteButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const userId = Number(button.dataset.userId);
      const noteIndex = Number(button.dataset.noteIndex);
      const selectedUser = filteredUsers.find((item) => item.id === userId);
      const sortedNotes = sortNotesByDate(selectedUser.notes);
      const visibleNotes = expandedUsers.has(userId)
        ? sortedNotes
        : sortedNotes.slice(0, 2);
      const selectedNote = visibleNotes[noteIndex];

      openModal(selectedUser, selectedNote);
    });
  });
}

function attachReadMoreEvents() {
  const toggleButtons = document.querySelectorAll("[data-user-toggle]");

  toggleButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const userId = Number(button.dataset.userToggle);

      if (expandedUsers.has(userId)) {
        expandedUsers.delete(userId);
      } else {
        expandedUsers.add(userId);
      }

      renderUsers();
    });
  });
}

function openModal(user, note) {
  modalInitials.textContent = getInitials(user.name);
  modalUserName.textContent = user.name;
  modalMeta.textContent = `${note.mentor} • ${note.service}`;
  modalSessions.textContent = user.sessions;
  modalDate.textContent = note.date;
  modalService.textContent = note.service;

  modalDetailUserName.textContent = user.name;
  modalUserEmail.textContent = user.email;
  modalMentorName.textContent = note.mentor;
  modalMentorEmail.textContent = note.mentorEmail;
  modalDetailDate.textContent = note.date;
  modalDetailService.textContent = note.service;

  answer1.textContent = note.sessionWork;
  answer2.textContent = note.nextSteps;
  answer3.textContent = note.sessionOutcome;
  answer4.textContent = note.sessionReflection;
  answer5.textContent = note.otherNotes;

  noteModal.classList.remove("hidden");
  document.body.classList.add("modal-open");
}

function closeModalView() {
  noteModal.classList.add("hidden");
  document.body.classList.remove("modal-open");
}

userSearch.addEventListener("input", renderUsers);
mentorSearch.addEventListener("input", renderUsers);
closeModal.addEventListener("click", closeModalView);

noteModal.addEventListener("click", (e) => {
  if (e.target === noteModal) {
    closeModalView();
  }
});

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && !noteModal.classList.contains("hidden")) {
    closeModalView();
  }
});

renderUsers();
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
    if (shell) shell.classList.remove("sidebar-active");
  });
});
