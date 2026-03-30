const mentorNotesForm = document.getElementById("mentorNotesForm");
const successMessage = document.getElementById("successMessage");

const fullNameField = document.getElementById("fullName");
const userEmailField = document.getElementById("userEmail");
const sessionDateField = document.getElementById("sessionDate");
const mentorNameField = document.getElementById("mentorName");
const mentorEmailField = document.getElementById("mentorEmail");
const sessionTypeField = document.getElementById("sessionType");
const serviceCards = document.querySelectorAll(".service-view-card");

const sessionWork = document.getElementById("sessionWork");
const nextSteps = document.getElementById("nextSteps");
const sessionOutcome = document.getElementById("sessionOutcome");
const sessionReflection = document.getElementById("sessionReflection");
const otherNotes = document.getElementById("otherNotes");
const charCount = document.getElementById("charCount");

const allowedServices = [
  "Tutoring",
  "Program Insights",
  "Interview Prep",
  "Application Review",
  "Gap Year Planning",
  "Office Hours",
];

/*
  Demo auto-filled values.
  Replace these with real values from your backend.
*/
const currentSession = {
  fullName: "User Name",
  email: "user@example.edu",
  mentorName: "Mentor Name",
  mentorEmail: "mentor@example.edu",
  sessionDate: new Date().toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }),
  sessionType: "Application Review",
};

function populateSessionDetails() {
  fullNameField.value = currentSession.fullName;
  userEmailField.value = currentSession.email;
  mentorNameField.value = currentSession.mentorName;
  mentorEmailField.value = currentSession.mentorEmail;
  sessionDateField.value = currentSession.sessionDate;
  sessionTypeField.value = currentSession.sessionType;

  serviceCards.forEach((card) => {
    const isActive = card.dataset.service === currentSession.sessionType;
    card.classList.toggle("active", isActive);
  });
}

otherNotes.addEventListener("input", () => {
  charCount.textContent = otherNotes.value.length;
});

mentorNotesForm.addEventListener("submit", function (e) {
  e.preventDefault();

  const fullName = fullNameField.value.trim();
  const userEmail = userEmailField.value.trim();
  const mentorName = mentorNameField.value.trim();
  const mentorEmail = mentorEmailField.value.trim();
  const sessionDate = sessionDateField.value.trim();
  const sessionType = sessionTypeField.value.trim();

  const sessionWorkValue = sessionWork.value.trim();
  const nextStepsValue = nextSteps.value.trim();
  const sessionOutcomeValue = sessionOutcome.value.trim();
  const sessionReflectionValue = sessionReflection.value.trim();
  const otherNotesValue = otherNotes.value.trim();

  if (!fullName) {
    alert("User full name is missing.");
    return;
  }

  if (!userEmail) {
    alert("User email is missing.");
    return;
  }

  if (!mentorName) {
    alert("Mentor name is missing.");
    return;
  }

  if (!mentorEmail) {
    alert("Mentor email is missing.");
    return;
  }

  if (!sessionDate) {
    alert("Session date is missing.");
    return;
  }

  if (!sessionType || !allowedServices.includes(sessionType)) {
    alert("Session type is missing or invalid.");
    return;
  }

  if (!sessionWorkValue) {
    alert("Please enter what was worked on during the session.");
    return;
  }

  if (!nextStepsValue) {
    alert("Please enter the plan going forward for the user.");
    return;
  }

  if (!sessionOutcomeValue) {
    alert("Please enter the outcome of the session.");
    return;
  }

  if (!sessionReflectionValue) {
    alert("Please enter one strength and one challenge from the session.");
    return;
  }

  if (!otherNotesValue) {
    alert("Please enter any other notes about the session.");
    return;
  }

  const formData = {
    fullName,
    userEmail,
    mentorName,
    mentorEmail,
    sessionDate,
    sessionType,
    sessionWork: sessionWorkValue,
    nextSteps: nextStepsValue,
    sessionOutcome: sessionOutcomeValue,
    sessionReflection: sessionReflectionValue,
    otherNotes: otherNotesValue,
  };

  console.log("Submitted mentor notes:", formData);

  successMessage.classList.add("show");
});

populateSessionDetails();
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
