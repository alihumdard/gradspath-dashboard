const mentorNotesDataEl = document.getElementById("mentorNotesData");
const mentorNotesData = mentorNotesDataEl
  ? JSON.parse(mentorNotesDataEl.textContent)
  : {};

const fallbackUsersData = [];
const viewerRole = mentorNotesData.viewerRole === "student" ? "student" : "mentor";

const usersData =
  Array.isArray(mentorNotesData.users) && mentorNotesData.users.length > 0
    ? mentorNotesData.users
    : fallbackUsersData;

usersData.forEach((user) => {
  user.sessions = Number(user.sessions || user.notes?.length || 0);
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
  return String(name || "")
    .split(" ")
    .filter(Boolean)
    .map((part) => part[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

function sortNotesByDate(notes) {
  return [...notes].sort((a, b) => {
    const first = a.rawDate || a.date;
    const second = b.rawDate || b.date;
    return new Date(second) - new Date(first);
  });
}

function escapeHtml(text) {
  return String(text ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function renderAvatarMarkup(user) {
  return user.avatarUrl && String(user.avatarUrl).trim()
    ? `<img src="${escapeHtml(user.avatarUrl)}" alt="${escapeHtml(user.name)}" class="user-avatar-image" />`
    : escapeHtml(getInitials(user.name));
}

function cardSubtitle(user) {
  return `${user.sessions} ${user.sessions === 1 ? "session" : "sessions"}`;
}

function cardResultsLabel(count) {
  const noun = viewerRole === "student" ? "mentor" : "user";
  return `${count} ${count === 1 ? noun : `${noun}s`}`;
}

function notePreviewTitle(user, note) {
  if (viewerRole === "student") {
    return escapeHtml(note.service || "Session");
  }

  return escapeHtml(user.name);
}

function modalUserNameValue(user) {
  return viewerRole === "student"
    ? user.studentName || "User"
    : user.name || "User";
}

function modalUserEmailValue(user) {
  return viewerRole === "student"
    ? user.studentEmail || "Not available"
    : user.email || "Not available";
}

function renderUsers() {
  const userTerm = userSearch?.value.trim().toLowerCase() || "";
  const mentorTerm = mentorSearch?.value.trim().toLowerCase() || "";

  const filteredUsers = usersData.filter((user) => {
    const matchesUser = !userTerm || String(user.name || "").toLowerCase().includes(userTerm);
    const matchesMentor =
      !mentorTerm ||
      (Array.isArray(user.notes) &&
        user.notes.some((note) => String(note.mentor || "").toLowerCase().includes(mentorTerm)));

    return matchesUser && matchesMentor;
  });

  if (!usersGrid || !resultsCount || !emptyState) {
    return;
  }

  usersGrid.innerHTML = "";
  resultsCount.textContent = cardResultsLabel(filteredUsers.length);

  if (!filteredUsers.length) {
    emptyState.classList.remove("hidden");
    return;
  }

  emptyState.classList.add("hidden");

  filteredUsers.forEach((user) => {
    const notes = sortNotesByDate(Array.isArray(user.notes) ? user.notes : []);
    const isExpanded = expandedUsers.has(user.id);
    const visibleNotes = isExpanded ? notes : notes.slice(0, 2);
    const hasMoreThanTwo = notes.length > 2;

    const card = document.createElement("article");
    card.className = "user-card";

    card.innerHTML = `
      <div class="user-head">
        <div class="user-avatar">${renderAvatarMarkup(user)}</div>
        <div class="user-info">
          <h5>${escapeHtml(user.name)}</h5>
          <p>${cardSubtitle(user)}</p>
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
                  <p class="note-box-name">${notePreviewTitle(user, note)}</p>
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
      const selectedUser = filteredUsers.find((item) => Number(item.id) === userId);

      if (!selectedUser) {
        return;
      }

      const sortedNotes = sortNotesByDate(selectedUser.notes || []);
      const visibleNotes = expandedUsers.has(userId)
        ? sortedNotes
        : sortedNotes.slice(0, 2);
      const selectedNote = visibleNotes[noteIndex];

      if (!selectedNote) {
        return;
      }

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
  if (!noteModal) {
    return;
  }

  modalInitials.innerHTML = renderAvatarMarkup(user);
  modalUserName.textContent = user.name;
  modalMeta.textContent = `${note.mentor} • ${note.service}`;
  modalSessions.textContent = user.sessions;
  modalDate.textContent = note.date;
  modalService.textContent = note.service;

  modalDetailUserName.textContent = modalUserNameValue(user);
  modalUserEmail.textContent = modalUserEmailValue(user);
  modalMentorName.textContent = note.mentor;
  modalMentorEmail.textContent = note.mentorEmail || "Not available";
  modalDetailDate.textContent = note.date;
  modalDetailService.textContent = note.service;

  answer1.textContent = note.sessionWork || "";
  answer2.textContent = note.nextSteps || "";
  answer3.textContent = note.sessionOutcome || "";
  answer4.textContent = note.sessionReflection || "";
  answer5.textContent = note.otherNotes || "";

  noteModal.classList.remove("hidden");
  document.body.classList.add("modal-open");
}

function closeModalView() {
  if (!noteModal) {
    return;
  }

  noteModal.classList.add("hidden");
  document.body.classList.remove("modal-open");
}

if (userSearch) {
  userSearch.addEventListener("input", renderUsers);
}

if (mentorSearch) {
  mentorSearch.addEventListener("input", renderUsers);
}

if (closeModal) {
  closeModal.addEventListener("click", closeModalView);
}

if (noteModal) {
  noteModal.addEventListener("click", (e) => {
    if (e.target === noteModal) {
      closeModalView();
    }
  });
}

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && noteModal && !noteModal.classList.contains("hidden")) {
    closeModalView();
  }
});

renderUsers();

// Mobile sidebar toggle
const menuBtn = document.getElementById("mobileMenuToggle");
const overlay = document.getElementById("sidebarOverlay");
const shell = document.querySelector(".app-shell");

if (menuBtn && shell) {
  menuBtn.addEventListener("click", () => shell.classList.add("sidebar-active"));
}
if (overlay && shell) {
  overlay.addEventListener("click", () => shell.classList.remove("sidebar-active"));
}

// Theme Toggle Logic
const themeToggle = document.getElementById("themeToggle");
const body = document.body;

function updateTheme(theme) {
  body.setAttribute("data-theme", theme);
  localStorage.setItem("theme", theme);

  if (themeToggle) {
    themeToggle.textContent = theme === "dark" ? "Light / Dark" : "Dark / Light";
  }
}

const savedTheme = localStorage.getItem("theme") || "light";
updateTheme(savedTheme);

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = body.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    updateTheme(newTheme);
  });
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
