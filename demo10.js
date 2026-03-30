const mentorForm = document.getElementById("mentorForm");

const mentorType = document.getElementById("mentorType");
const fullName = document.getElementById("fullName");
const email = document.getElementById("email");
const officeHours = document.getElementById("officeHours");
const calendlyLink = document.getElementById("calendlyLink");
const program = document.getElementById("program");
const school = document.getElementById("school");
const description = document.getElementById("description");

const nameError = document.getElementById("nameError");
const emailError = document.getElementById("emailError");
const officeHoursError = document.getElementById("officeHoursError");
const calendlyError = document.getElementById("calendlyError");
const programError = document.getElementById("programError");
const schoolError = document.getElementById("schoolError");
const descriptionError = document.getElementById("descriptionError");
const imageError = document.getElementById("imageError");
const payoutError = document.getElementById("payoutError");

const profileImageInput = document.getElementById("profileImageInput");
const uploadDropzone = document.getElementById("uploadDropzone");
const removeImageBtn = document.getElementById("removeImageBtn");

const cropSection = document.getElementById("cropSection");
const cropBox = document.getElementById("cropBox");
const cropImage = document.getElementById("cropImage");
const zoomSlider = document.getElementById("zoomSlider");
const resetCropBtn = document.getElementById("resetCropBtn");
const applyCropBtn = document.getElementById("applyCropBtn");

const enablePayoutsBtn = document.getElementById("enablePayoutsBtn");
const payoutStatus = document.getElementById("payoutStatus");

const avatar = document.getElementById("avatar");
const avatarImage = document.getElementById("avatarImage");
const avatarInitials = document.getElementById("avatarInitials");

const cardName = document.getElementById("cardName");
const cardSubtitle = document.getElementById("cardSubtitle");
const officeHoursDisplay = document.getElementById("officeHoursDisplay");
const officeHoursText = document.getElementById("officeHoursText");
const cardDescription = document.getElementById("cardDescription");
const readMoreBtn = document.getElementById("readMoreBtn");

let rawImageSrc = "";
let descriptionExpanded = false;
let hasAppliedImage = false;
let payoutsEnabled = false;

let cropState = {
  scale: 1,
  x: 0,
  y: 0,
  minScale: 1,
  naturalWidth: 0,
  naturalHeight: 0,
  dragging: false,
  startX: 0,
  startY: 0,
  startOffsetX: 0,
  startOffsetY: 0
};

function getInitials(name) {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
}

function updatePreview() {
  const nameValue = fullName.value.trim();
  const programValue = program.value.trim();
  const schoolValue = school.value.trim();
  const descriptionValue = description.value.trim();
  const officeHoursValue = officeHours.value.trim();

  cardName.textContent = nameValue;
  avatarInitials.textContent = getInitials(nameValue);

  if (programValue && schoolValue) {
    cardSubtitle.textContent = `${programValue} • ${schoolValue}`;
  } else if (programValue) {
    cardSubtitle.textContent = programValue;
  } else if (schoolValue) {
    cardSubtitle.textContent = schoolValue;
  } else {
    cardSubtitle.textContent = "";
  }

  cardDescription.textContent = descriptionValue;

  if (officeHoursValue) {
    officeHoursText.textContent = officeHoursValue;
    officeHoursDisplay.style.display = "block";
  } else {
    officeHoursText.textContent = "";
    officeHoursDisplay.style.display = "none";
  }

  updateReadMoreVisibility();
}

function updateReadMoreVisibility() {
  const textLength = cardDescription.textContent.trim().length;

  if (descriptionExpanded) {
    cardDescription.classList.remove("collapsed");
    readMoreBtn.innerHTML = `Show Less <span class="read-more-arrow">⌃</span>`;
  } else {
    cardDescription.classList.add("collapsed");
    readMoreBtn.innerHTML = `Read More <span class="read-more-arrow">⌄</span>`;
  }

  readMoreBtn.style.display = textLength > 110 ? "inline-flex" : "none";
}

function updatePayoutUI() {
  if (payoutsEnabled) {
    payoutStatus.textContent = "Enabled";
    payoutStatus.classList.add("enabled");
    enablePayoutsBtn.textContent = "Payouts Enabled";
  } else {
    payoutStatus.textContent = "Not enabled";
    payoutStatus.classList.remove("enabled");
    enablePayoutsBtn.textContent = "Enable Payouts";
  }
}

readMoreBtn.addEventListener("click", () => {
  descriptionExpanded = !descriptionExpanded;
  updateReadMoreVisibility();
});

function showError(element, message) {
  element.textContent = message;
}

function clearError(element) {
  element.textContent = "";
}

function validateName() {
  const value = fullName.value.trim();
  const parts = value.split(/\s+/).filter(Boolean);

  if (!value) {
    showError(nameError, "Enter your full name.");
    return false;
  }

  if (parts.length < 2) {
    showError(nameError, "Enter at least a first and last name.");
    return false;
  }

  if (parts.some(part => part.length < 2)) {
    showError(nameError, "Each part of the name should be at least 2 letters.");
    return false;
  }

  clearError(nameError);
  return true;
}

function validateEmail() {
  const value = email.value.trim();
  const basicPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const isGrad = mentorType.value === "grad";

  if (!value) {
    showError(emailError, "Enter an email.");
    return false;
  }

  if (!value.includes("@")) {
    showError(emailError, "Email must include @.");
    return false;
  }

  if (!basicPattern.test(value)) {
    showError(emailError, "Enter a valid email address.");
    return false;
  }

  if (isGrad && !/@.+\.edu$/i.test(value)) {
    showError(emailError, "Grad mentors must use an .edu email address.");
    return false;
  }

  clearError(emailError);
  return true;
}

function validateOfficeHours() {
  const value = officeHours.value.trim();

  if (!value) {
    showError(officeHoursError, "Enter your office hours.");
    return false;
  }

  if (value.length < 6) {
    showError(officeHoursError, "Office hours are too short.");
    return false;
  }

  clearError(officeHoursError);
  return true;
}

function validateCalendly() {
  const value = calendlyLink.value.trim();

  if (!value) {
    showError(calendlyError, "Enter your Calendly link.");
    return false;
  }

  try {
    const parsed = new URL(value);

    if (!parsed.hostname.includes("calendly.com")) {
      showError(calendlyError, "Enter a valid Calendly URL.");
      return false;
    }
  } catch (error) {
    showError(calendlyError, "Enter a valid URL.");
    return false;
  }

  clearError(calendlyError);
  return true;
}

function validateProgram() {
  const value = program.value.trim();

  if (!value) {
    showError(programError, "Enter your program.");
    return false;
  }

  if (value.length < 2) {
    showError(programError, "Program is too short.");
    return false;
  }

  clearError(programError);
  return true;
}

function validateSchool() {
  const value = school.value.trim();

  if (!value) {
    showError(schoolError, "Enter your grad school.");
    return false;
  }

  if (value.length < 2) {
    showError(schoolError, "Grad school is too short.");
    return false;
  }

  clearError(schoolError);
  return true;
}

function validateDescription() {
  const value = description.value.trim();

  if (!value) {
    showError(descriptionError, "Enter a description.");
    return false;
  }

  if (value.length < 40) {
    showError(descriptionError, "Description should be at least 40 characters.");
    return false;
  }

  clearError(descriptionError);
  return true;
}

function validateImage() {
  if (!hasAppliedImage) {
    showError(imageError, "Upload and apply a profile image.");
    return false;
  }

  clearError(imageError);
  return true;
}

function validatePayouts() {
  if (!payoutsEnabled) {
    showError(payoutError, "Enable payouts before saving.");
    return false;
  }

  clearError(payoutError);
  return true;
}

function validateForm() {
  return [
    validateName(),
    validateEmail(),
    validateOfficeHours(),
    validateCalendly(),
    validateProgram(),
    validateSchool(),
    validateDescription(),
    validateImage(),
    validatePayouts()
  ].every(Boolean);
}

function openFilePicker() {
  profileImageInput.click();
}

uploadDropzone.addEventListener("click", openFilePicker);
uploadDropzone.addEventListener("keydown", (e) => {
  if (e.key === "Enter" || e.key === " ") {
    e.preventDefault();
    openFilePicker();
  }
});

uploadDropzone.addEventListener("dragover", (e) => {
  e.preventDefault();
  uploadDropzone.classList.add("dragover");
});

uploadDropzone.addEventListener("dragleave", () => {
  uploadDropzone.classList.remove("dragover");
});

uploadDropzone.addEventListener("drop", (e) => {
  e.preventDefault();
  uploadDropzone.classList.remove("dragover");

  const file = e.dataTransfer.files[0];
  if (!file || !file.type.startsWith("image/")) return;

  handleSelectedFile(file);
});

profileImageInput.addEventListener("change", (event) => {
  const file = event.target.files[0];
  if (!file) return;
  handleSelectedFile(file);
});

function handleSelectedFile(file) {
  const reader = new FileReader();

  reader.onload = (e) => {
    rawImageSrc = e.target.result;

    cropImage.onload = () => {
      cropSection.classList.add("active");
      setupCrop();

      avatarImage.src = rawImageSrc;
      avatar.classList.add("has-image");
      hasAppliedImage = false;
      clearError(imageError);
    };

    cropImage.src = rawImageSrc;
  };

  reader.readAsDataURL(file);
}

function clearCurrentImage() {
  rawImageSrc = "";
  hasAppliedImage = false;
  profileImageInput.value = "";
  cropImage.src = "";
  avatarImage.src = "";
  avatar.classList.remove("has-image");
  cropSection.classList.remove("active");
  clearError(imageError);
  updatePreview();

  const saved = JSON.parse(localStorage.getItem("demo10MentorSettings")) || {};
  saved.image = "";
  localStorage.setItem("demo10MentorSettings", JSON.stringify(saved));
}

removeImageBtn.addEventListener("click", clearCurrentImage);

function setupCrop() {
  const boxSize = cropBox.clientWidth;
  cropState.naturalWidth = cropImage.naturalWidth;
  cropState.naturalHeight = cropImage.naturalHeight;

  cropState.minScale = Math.max(
    boxSize / cropState.naturalWidth,
    boxSize / cropState.naturalHeight
  );

  cropState.scale = cropState.minScale;
  zoomSlider.value = "1";

  const displayWidth = cropState.naturalWidth * cropState.scale;
  const displayHeight = cropState.naturalHeight * cropState.scale;

  cropState.x = (boxSize - displayWidth) / 2;
  cropState.y = (boxSize - displayHeight) / 2;

  applyCropTransform();
}

function applyCropTransform() {
  const width = cropState.naturalWidth * cropState.scale;
  const height = cropState.naturalHeight * cropState.scale;

  cropImage.style.width = `${width}px`;
  cropImage.style.height = `${height}px`;
  cropImage.style.left = `${cropState.x}px`;
  cropImage.style.top = `${cropState.y}px`;
}

function clampCropPosition() {
  const boxSize = cropBox.clientWidth;
  const imageWidth = cropState.naturalWidth * cropState.scale;
  const imageHeight = cropState.naturalHeight * cropState.scale;

  const minX = boxSize - imageWidth;
  const minY = boxSize - imageHeight;
  const maxX = 0;
  const maxY = 0;

  cropState.x = Math.min(maxX, Math.max(minX, cropState.x));
  cropState.y = Math.min(maxY, Math.max(minY, cropState.y));
}

zoomSlider.addEventListener("input", () => {
  if (!cropState.naturalWidth || !cropState.naturalHeight) return;

  const previousScale = cropState.scale;
  const zoomMultiplier = parseFloat(zoomSlider.value);
  cropState.scale = cropState.minScale * zoomMultiplier;

  const boxSize = cropBox.clientWidth;
  const centerX = boxSize / 2;
  const centerY = boxSize / 2;

  const imagePointX = (centerX - cropState.x) / previousScale;
  const imagePointY = (centerY - cropState.y) / previousScale;

  cropState.x = centerX - imagePointX * cropState.scale;
  cropState.y = centerY - imagePointY * cropState.scale;

  clampCropPosition();
  applyCropTransform();
});

function startDrag(clientX, clientY) {
  cropState.dragging = true;
  cropBox.classList.add("dragging");
  cropState.startX = clientX;
  cropState.startY = clientY;
  cropState.startOffsetX = cropState.x;
  cropState.startOffsetY = cropState.y;
}

function duringDrag(clientX, clientY) {
  if (!cropState.dragging) return;

  const dx = clientX - cropState.startX;
  const dy = clientY - cropState.startY;

  cropState.x = cropState.startOffsetX + dx;
  cropState.y = cropState.startOffsetY + dy;

  clampCropPosition();
  applyCropTransform();
}

function endDrag() {
  cropState.dragging = false;
  cropBox.classList.remove("dragging");
}

cropBox.addEventListener("mousedown", (e) => {
  e.preventDefault();
  startDrag(e.clientX, e.clientY);
});

window.addEventListener("mousemove", (e) => {
  duringDrag(e.clientX, e.clientY);
});

window.addEventListener("mouseup", endDrag);

cropBox.addEventListener("touchstart", (e) => {
  const touch = e.touches[0];
  startDrag(touch.clientX, touch.clientY);
}, { passive: true });

window.addEventListener("touchmove", (e) => {
  if (!cropState.dragging) return;
  const touch = e.touches[0];
  duringDrag(touch.clientX, touch.clientY);
}, { passive: true });

window.addEventListener("touchend", endDrag);

resetCropBtn.addEventListener("click", () => {
  if (!rawImageSrc) return;
  cropImage.onload = () => setupCrop();
  cropImage.src = rawImageSrc;
});

applyCropBtn.addEventListener("click", () => {
  const canvas = document.createElement("canvas");
  const outputSize = 500;
  const boxSize = cropBox.clientWidth;

  canvas.width = outputSize;
  canvas.height = outputSize;

  const ctx = canvas.getContext("2d");

  const sourceX = (-cropState.x) / cropState.scale;
  const sourceY = (-cropState.y) / cropState.scale;
  const sourceSize = boxSize / cropState.scale;

  ctx.drawImage(
    cropImage,
    sourceX,
    sourceY,
    sourceSize,
    sourceSize,
    0,
    0,
    outputSize,
    outputSize
  );

  const croppedDataUrl = canvas.toDataURL("image/png");
  avatarImage.src = croppedDataUrl;
  avatar.classList.add("has-image");
  hasAppliedImage = true;
  clearError(imageError);
  saveForm(croppedDataUrl);
});

enablePayoutsBtn.addEventListener("click", () => {
  clearError(payoutError);

  // Replace this with your real backend Stripe Connect onboarding route
  // Example:
  // window.location.href = "/api/stripe/connect/onboarding";

  payoutsEnabled = true;
  updatePayoutUI();
  saveForm();
});

function saveForm(imageData = null) {
  const existing = JSON.parse(localStorage.getItem("demo10MentorSettings")) || {};
  const finalImage = imageData !== null ? imageData : existing.image || "";

  const data = {
    mentorType: mentorType.value,
    fullName: fullName.value.trim(),
    email: email.value.trim(),
    officeHours: officeHours.value.trim(),
    calendlyLink: calendlyLink.value.trim(),
    program: program.value.trim(),
    school: school.value.trim(),
    description: description.value.trim(),
    payoutsEnabled,
    image: finalImage
  };

  localStorage.setItem("demo10MentorSettings", JSON.stringify(data));
}

function loadForm() {
  const saved = localStorage.getItem("demo10MentorSettings");
  if (!saved) {
    updatePreview();
    updatePayoutUI();
    return;
  }

  const data = JSON.parse(saved);

  mentorType.value = data.mentorType || "grad";
  fullName.value = data.fullName || "";
  email.value = data.email || "";
  officeHours.value = data.officeHours || "";
  calendlyLink.value = data.calendlyLink || "";
  program.value = data.program || "";
  school.value = data.school || "";
  description.value = data.description || "";
  payoutsEnabled = Boolean(data.payoutsEnabled);

  if (data.image) {
    avatarImage.src = data.image;
    avatar.classList.add("has-image");
    hasAppliedImage = true;
  } else {
    avatar.classList.remove("has-image");
    hasAppliedImage = false;
  }

  updatePreview();
  updatePayoutUI();
}

mentorType.addEventListener("change", validateEmail);

fullName.addEventListener("input", () => {
  validateName();
  updatePreview();
});

email.addEventListener("input", validateEmail);

officeHours.addEventListener("input", () => {
  validateOfficeHours();
  updatePreview();
});

calendlyLink.addEventListener("input", validateCalendly);

program.addEventListener("input", () => {
  validateProgram();
  updatePreview();
});

school.addEventListener("input", () => {
  validateSchool();
  updatePreview();
});

description.addEventListener("input", () => {
  validateDescription();
  updatePreview();
});

mentorForm.addEventListener("submit", (e) => {
  e.preventDefault();

  if (!validateForm()) return;

  saveForm();
  alert("Settings saved successfully.");
});

loadForm();