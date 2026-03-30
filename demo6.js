const ratingLabels = {
  1: "1 out of 5 — Poor",
  2: "2 out of 5 — Fair",
  3: "3 out of 5 — Good",
  4: "4 out of 5 — Very Good",
  5: "5 out of 5 — Excellent"
};

const allowedServices = [
  "Tutoring",
  "Program Insights",
  "Interview Prep",
  "Application Review",
  "Gap Year Planning",
  "Office Hours"
];

const starContainer = document.getElementById("meetingRating");
const stars = starContainer.querySelectorAll(".star");
const ratingInput = document.getElementById("meetingRatingInput");
const ratingLabel = document.getElementById("meetingRatingLabel");

const fullNameField = document.getElementById("fullName");
const userEmailField = document.getElementById("userEmail");
const sessionDateField = document.getElementById("sessionDate");
const serviceUsedField = document.getElementById("serviceUsed");
const serviceCards = document.querySelectorAll(".service-view-card");

const quickFeedback = document.getElementById("quickFeedback");
const charCount = document.getElementById("charCount");
const feedbackForm = document.getElementById("feedbackForm");
const successMessage = document.getElementById("successMessage");

let selectedRating = 0;

const currentUser = {
  fullName: "Mentor Name",
  email: "mentor@example.edu",
  sessionDate: new Date().toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric"
  }),
  serviceUsed: "Office Hours"
};

function populateSessionDetails() {
  fullNameField.value = currentUser.fullName;
  userEmailField.value = currentUser.email;
  sessionDateField.value = currentUser.sessionDate;
  serviceUsedField.value = currentUser.serviceUsed;

  serviceCards.forEach((card) => {
    const isActive = card.dataset.service === currentUser.serviceUsed;
    card.classList.toggle("active", isActive);
  });
}

function updateStars(value) {
  stars.forEach((star) => {
    const starValue = Number(star.dataset.value);
    star.classList.toggle("active", starValue <= value);
  });
}

stars.forEach((star) => {
  star.addEventListener("mouseenter", () => {
    const hoverValue = Number(star.dataset.value);
    stars.forEach((item) => {
      const itemValue = Number(item.dataset.value);
      item.classList.toggle("hovered", itemValue <= hoverValue);
    });
  });

  star.addEventListener("mouseleave", () => {
    stars.forEach((item) => item.classList.remove("hovered"));
  });

  star.addEventListener("click", () => {
    selectedRating = Number(star.dataset.value);
    ratingInput.value = selectedRating;
    updateStars(selectedRating);
    ratingLabel.textContent = ratingLabels[selectedRating];
  });
});

starContainer.addEventListener("mouseleave", () => {
  updateStars(selectedRating);
});

document.querySelectorAll(".scale-card").forEach((card) => {
  const input = card.querySelector("input");

  input.addEventListener("change", () => {
    document.querySelectorAll(".scale-card").forEach((item) => {
      item.classList.remove("selected");
    });

    if (input.checked) {
      card.classList.add("selected");
    }
  });
});

document.querySelectorAll(".binary-card").forEach((card) => {
  const input = card.querySelector("input");

  input.addEventListener("change", () => {
    document.querySelectorAll(".binary-card").forEach((item) => {
      item.classList.remove("selected");
    });

    if (input.checked) {
      card.classList.add("selected");
    }
  });
});

quickFeedback.addEventListener("input", () => {
  charCount.textContent = quickFeedback.value.length;
});

feedbackForm.addEventListener("submit", (event) => {
  event.preventDefault();

  const fullName = fullNameField.value.trim();
  const userEmail = userEmailField.value.trim();
  const sessionDate = sessionDateField.value.trim();
  const serviceUsed = serviceUsedField.value.trim();
  const meetingRating = ratingInput.value;
  const mentorKnowledge = document.querySelector('input[name="mentorKnowledge"]:checked');
  const recommendation = document.querySelector('input[name="recommendation"]:checked');
  const feedbackText = quickFeedback.value.trim();

  if (!fullName) {
    alert("Mentor full name is missing.");
    return;
  }

  if (!userEmail) {
    alert("Mentor email is missing.");
    return;
  }

  if (!sessionDate) {
    alert("Session date is missing.");
    return;
  }

  if (!serviceUsed || !allowedServices.includes(serviceUsed)) {
    alert("Service used is missing or invalid.");
    return;
  }

  if (!meetingRating) {
    alert("Please select an overall session rating.");
    return;
  }

  if (!mentorKnowledge) {
    alert("Please select a mentor preparedness and knowledge rating.");
    return;
  }

  if (!recommendation) {
    alert("Please select whether you would recommend this mentor.");
    return;
  }

  if (!feedbackText) {
    alert("Please provide feedback before submitting.");
    return;
  }

  const formData = {
    fullName,
    userEmail,
    sessionDate,
    serviceUsed,
    meetingRating,
    mentorKnowledge: mentorKnowledge.value,
    recommendation: recommendation.value,
    quickFeedback: feedbackText
  };

  console.log("Submitted feedback:", formData);

  successMessage.classList.add("show");
});

populateSessionDetails();