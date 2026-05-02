const bookingDetailsDataEl = document.getElementById("bookingDetailsData");
const bookingDetailsData = bookingDetailsDataEl
  ? JSON.parse(bookingDetailsDataEl.textContent)
  : {};

const fallbackBooking = {
  id: 1,
  mentorDisplay: "Emily Carter • Graduate Mentor • MBA • Harvard",
  mentorName: "Emily Carter",
  serviceName: "Office Hours",
  serviceSlug: "office_hours",
  sessionDateKey: "2026-04-24",
  sessionDateLabel: "Friday, April 24, 2026",
  sessionTimeLabel: "2:30 PM",
  zoomLink: "https://zoom.us/j/9876543210",
  meetingSize: "Office Hours",
  duration: 45,
};

const bookingGroups = Array.isArray(bookingDetailsData.bookingGroups)
  ? bookingDetailsData.bookingGroups
  : [];
const currentBookings =
  Array.isArray(bookingDetailsData.currentBookings) &&
  bookingDetailsData.currentBookings.length > 0
    ? bookingDetailsData.currentBookings
    : [];
const upcomingBookings =
  Array.isArray(bookingDetailsData.upcomingBookings) &&
  bookingDetailsData.upcomingBookings.length > 0
    ? bookingDetailsData.upcomingBookings
    : [fallbackBooking];
const activeBookings = [...currentBookings, ...upcomingBookings];

const selectedBooking =
  activeBookings.find((booking) => booking.id === bookingDetailsData.selectedBookingId) ||
  bookingDetailsData.selectedBooking ||
  currentBookings[0] ||
  upcomingBookings[0] ||
  fallbackBooking;

const meetingData = {
  mentorName: selectedBooking.mentorDisplay || selectedBooking.mentorName || fallbackBooking.mentorDisplay,
  meetingLink: selectedBooking.meetingLink || selectedBooking.zoomLink || null,
  selectedService: selectedBooking.serviceName || fallbackBooking.serviceName,
  selectedServiceSlug: selectedBooking.serviceSlug || fallbackBooking.serviceSlug,
  supportUrl: bookingDetailsData.supportUrl || "/student/support",
  counterpartLabel: bookingDetailsData.counterpartLabel || "Mentor",
  viewerRoleLabel: bookingDetailsData.viewerRoleLabel || "You",
  viewerId: bookingDetailsData.viewerId || null,
};

const bookingsById = activeBookings.reduce((carry, booking) => {
  if (booking?.id != null) {
    carry[String(booking.id)] = booking;
  }

  return carry;
}, {});

const bookingsByDate = {};

const bookedDates = activeBookings.reduce((carry, booking) => {
  if (!booking.sessionDateKey) return carry;

  const normalizedBooking = {
    id: booking.id,
    time: booking.sessionTimeLabel || "Not set",
    service: booking.serviceName || "Service",
    serviceSlug: booking.serviceSlug || null,
    officeHoursFocusName: booking.officeHoursFocusName || null,
    serviceChoice: booking.serviceChoice || null,
    mentorName: booking.mentorDisplay || booking.mentorName || "Mentor",
    meetingLink: booking.meetingLink || booking.zoomLink || null,
    meetingProvider: booking.meetingProvider || "Meeting Link",
    meetingLinkLabel: booking.meetingLinkLabel || "Open Meeting Link",
    meetingLinkStatus: booking.meetingLinkStatus || "not_synced",
    meetingLinkStatusMessage: booking.meetingLinkStatusMessage || "Meeting link will be shared soon.",
    meetingAccessAllowed: Boolean(booking.meetingAccessAllowed),
    meetingAccessMessage: booking.meetingAccessMessage || "Meeting access is not available yet.",
    meetingAccessOpensAt: booking.meetingAccessOpensAt || null,
    meetingSize: booking.meetingSize || "1 on 1",
    meetingType: booking.meetingType || "meeting",
    meetingTypeLabel: booking.meetingTypeLabel || booking.meetingProvider || "Meeting",
    status: booking.status || "confirmed",
    statusLabel: booking.statusLabel || formatBookingStatus(booking.status),
    meetingStateLabel: booking.meetingStateLabel || null,
    duration: booking.duration || null,
    sessionDateLabel: booking.sessionDateLabel || null,
    mentorEmail: booking.mentorEmail || null,
    mentorProgram: booking.mentorProgram || null,
    mentorType: booking.mentorType || null,
    mentorSchool: booking.mentorSchool || null,
    mentorTitle: booking.mentorTitle || null,
    feedbackAllowed: Boolean(booking.feedbackAllowed),
    feedbackUnlockReason:
      booking.feedbackUnlockReason ||
      "Feedback will unlock after attendance is verified or the fallback window passes.",
    feedbackSubmitted: Boolean(booking.feedbackSubmitted),
    feedbackSubmitUrl: booking.feedbackSubmitUrl || "/student/feedback",
    attendanceLabel: booking.attendanceLabel || "Awaiting attendance data",
    canCancel: Boolean(booking.canCancel),
    cancelUrl: booking.cancelUrl || null,
    cancelPolicyCopy: booking.cancelPolicyCopy || "",
    mentorNotesAvailable: Boolean(booking.mentorNotesAvailable),
    mentorNotesUrl: booking.mentorNotesUrl || null,
    mentorNotesSubmitted: Boolean(booking.mentorNotesSubmitted),
    mentorNotesLabel: booking.mentorNotesLabel || "Add Session Notes",
    mentorNotesHelper:
      booking.mentorNotesHelper || "Internal notes stay visible to mentors only.",
    chatThreadUrl: booking.chatThreadUrl || null,
    chatSendUrl: booking.chatSendUrl || null,
    chatChannel: booking.chatChannel || null,
  };

  if (!bookingsByDate[booking.sessionDateKey]) {
    bookingsByDate[booking.sessionDateKey] = [];
  }

  bookingsByDate[booking.sessionDateKey].push(normalizedBooking);

  if (!carry[booking.sessionDateKey]) {
    carry[booking.sessionDateKey] = normalizedBooking;
  }

  return carry;
}, {});

Object.values(bookingsByDate).forEach((bookings) => {
  bookings.sort((a, b) => String(a.time || "").localeCompare(String(b.time || "")));
});

const mentorNameEl = document.getElementById("mentorName");
const meetingDateEl = document.getElementById("meetingDate");
const meetingTimeEl = document.getElementById("meetingTime");
const zoomLinkEl = document.getElementById("zoomLink");
const meetingProviderLabelEl = document.getElementById("meetingProviderLabel");
const meetingLinkStatusTextEl = document.getElementById("meetingLinkStatusText");
const monthLabelEl = document.getElementById("monthLabel");
const monthTitleButton = document.getElementById("monthTitleButton");
const monthDropdown = document.getElementById("monthDropdown");
const prevMonthBtn = document.getElementById("prevMonth");
const nextMonthBtn = document.getElementById("nextMonth");
const todayBtn = document.getElementById("todayBtn");
const calendarContentEl = document.getElementById("calendarContent");
const upcomingListEl = document.getElementById("upcomingList");
const currentListEl = document.getElementById("currentList");
const serviceCards = document.querySelectorAll(".service-card.locked-card");
const serviceLockNoteEl = document.getElementById("serviceLockNote");
const officeHoursServiceChoiceRow = document.getElementById("officeHoursServiceChoiceRow");
const officeHoursServiceChoiceNote = document.getElementById("officeHoursServiceChoiceNote");
const openOfficeHoursServiceChoiceBtn = document.getElementById("openOfficeHoursServiceChoiceBtn");
const counterpartLabelEl = document.getElementById("counterpartLabel");
const bookingSubtitleEl = document.getElementById("bookingSubtitle");

const cancelMeetingBtn = document.getElementById("cancelMeetingBtn");
const cancelModal = document.getElementById("cancelModal");
const cancelConfirmModal = document.getElementById("cancelConfirmModal");
const supportModal = document.getElementById("supportModal");
const officeHoursServiceChoiceModal = document.getElementById("officeHoursServiceChoiceModal");
const officeHoursServiceChoiceModalText = document.getElementById("officeHoursServiceChoiceModalText");
const officeHoursServiceChoiceOptions = document.getElementById("officeHoursServiceChoiceOptions");
const officeHoursServiceChoiceAlert = document.getElementById("officeHoursServiceChoiceAlert");
const closeOfficeHoursServiceChoiceBtn = document.getElementById("closeOfficeHoursServiceChoiceBtn");
const saveOfficeHoursServiceChoiceBtn = document.getElementById("saveOfficeHoursServiceChoiceBtn");
const cancelNo1 = document.getElementById("cancelNo1");
const cancelYes1 = document.getElementById("cancelYes1");
const cancelNo2 = document.getElementById("cancelNo2");
const cancelYes2 = document.getElementById("cancelYes2");
const supportCloseBtn = document.getElementById("supportCloseBtn");
const supportLink = document.getElementById("supportLink");
const cancelBookingForm = document.getElementById("cancelBookingForm");
const cancelBookingReasonInput = document.getElementById("cancelBookingReason");
const mentorNotesBtn = document.getElementById("mentorNotesBtn");
const mentorNotesHelperEl = document.getElementById("mentorNotesHelper");
const feedbackModal = document.getElementById("feedbackModal");
const openFeedbackModalBtn = document.getElementById("openFeedbackModalBtn");
const closeFeedbackModalBtn = document.getElementById("closeFeedbackModalBtn");

const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const chatWindow = document.getElementById("chatWindow");
const chatStatusEl = document.getElementById("chatStatus");
const chatTypingEl = document.getElementById("chatTyping");
const feedbackForm = document.getElementById("feedbackForm");
const feedbackBookingIdInput = document.getElementById("feedbackBookingId");
const feedbackServiceTypeInput = document.getElementById("feedbackServiceType");
const feedbackReasonEl = document.getElementById("feedbackReason");
const feedbackHelperEl = document.getElementById("feedbackHelper");
const feedbackStatusPillEl = document.getElementById("feedbackStatusPill");
const feedbackSubmitBtn = document.getElementById("feedbackSubmitBtn");
const feedbackModalHelperEl = document.getElementById("feedbackModalHelper");
const feedbackStarsEl = document.getElementById("feedbackStars");
const feedbackCommentEl = document.getElementById("feedbackComment");
const feedbackMentorNameEl = document.getElementById("feedbackMentorName");
const feedbackMentorProgramEl = document.getElementById("feedbackMentorProgram");
const feedbackMentorTypeEl = document.getElementById("feedbackMentorType");
const feedbackMentorEmailEl = document.getElementById("feedbackMentorEmail");
const feedbackMentorSchoolEl = document.getElementById("feedbackMentorSchool");
const feedbackSessionDateEl = document.getElementById("feedbackSessionDate");
const feedbackServiceCards = document.querySelectorAll(".feedback-service-card");
const feedbackStarButtons = document.querySelectorAll(".feedback-star");
const feedbackRatingLabelEl = document.getElementById("feedbackRatingLabel");
const feedbackCharCountEl = document.getElementById("feedbackCharCount");
const feedbackScaleCards = document.querySelectorAll(".feedback-scale-card");
const feedbackBinaryCards = document.querySelectorAll(".feedback-binary-card");
const viewButtons = document.querySelectorAll(".view-btn");
const themeToggle = document.getElementById("themeToggle");
const body = document.body;
const root = document.documentElement;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

function updateTheme(theme) {
  root.setAttribute("data-theme", theme);
  localStorage.setItem("theme", theme);
  
  if (themeToggle) {
    themeToggle.textContent = theme === "dark" ? "Light / Dark" : "Dark / Light";
  }
}

// Load saved theme from localStorage, default to 'light'
const savedTheme = localStorage.getItem("theme") || "light";
updateTheme(savedTheme);

if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = root.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    updateTheme(newTheme);
  });
}

const monthNames = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];

const weekDayShort = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const weekDayLong = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
];

const today = new Date();
const todayKey = formatDateKey(
  today.getFullYear(),
  today.getMonth(),
  today.getDate(),
);

let currentDate = new Date(today.getFullYear(), today.getMonth(), 1);
let currentView = "month";
let selectedDateKey = getDefaultSelectedDateKey();
let selectedBookingId = selectedBooking?.id ?? null;
let chatClient = null;
let activeChatBookingId = null;
let activeChatChannel = null;
let activeChatChannelName = null;
let localTypingActive = false;
let localTypingTimeoutId = null;
let remoteTypingTimeoutId = null;
let selectedOfficeHoursServiceChoiceId = null;
let meetingAccessTimerId = null;
const chatMessagesByBooking = new Map();
if (selectedDateKey) {
  const selectedDate = parseDateKey(selectedDateKey);
  currentDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
}

mentorNameEl.textContent = meetingData.mentorName;
if (counterpartLabelEl) {
  counterpartLabelEl.textContent = meetingData.counterpartLabel;
}
if (bookingSubtitleEl) {
  bookingSubtitleEl.textContent =
    meetingData.counterpartLabel.toLowerCase() === "counterpart"
      ? "Here is your meeting information for this booking."
      : `Here is your meeting information with your ${meetingData.counterpartLabel.toLowerCase()}.`;
}

function formatDateKey(year, monthIndex, day) {
  const month = String(monthIndex + 1).padStart(2, "0");
  const date = String(day).padStart(2, "0");
  return `${year}-${month}-${date}`;
}

function parseDateKey(key) {
  const [year, month, day] = key.split("-").map(Number);
  return new Date(year, month - 1, day);
}

function normalizeServiceValue(value) {
  return String(value || "")
    .trim()
    .toLowerCase()
    .replace(/&/g, "and")
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_+|_+$/g, "");
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatBookingStatus(status) {
  switch (String(status || "confirmed")) {
    case "completed":
      return "Completed";
    case "pending":
      return "Pending";
    case "cancelled":
    case "cancelled_pending_refund":
      return "Cancelled";
    case "no_show":
      return "No Show";
    case "confirmed":
      return "Booked";
    default:
      return String(status || "Booked").replace(/_/g, " ");
  }
}

function getBookingsByDateKey(key) {
  return Array.isArray(bookingsByDate[key]) ? bookingsByDate[key] : [];
}

function getBookingByDateKey(key) {
  const dayBookings = getBookingsByDateKey(key);
  const selectedBookingForDate = selectedBookingId != null
    ? bookingsById[String(selectedBookingId)]
    : null;

  if (selectedBookingForDate?.sessionDateKey === key) {
    return {
      ...bookedDates[key],
      ...selectedBookingForDate,
      time: selectedBookingForDate.sessionTimeLabel || bookedDates[key]?.time || "Not set",
      service: selectedBookingForDate.serviceName || bookedDates[key]?.service || "Service",
    };
  }

  return dayBookings[0] || bookedDates[key] || null;
}

function selectBookingForDate(key, bookingId = null) {
  selectedDateKey = key;
  const dayBookings = getBookingsByDateKey(key);
  const selected = bookingId != null
    ? dayBookings.find((booking) => String(booking.id) === String(bookingId))
    : dayBookings[0];

  selectedBookingId = selected?.id ?? null;
}

function getSelectedBooking() {
  return selectedDateKey ? getBookingByDateKey(selectedDateKey) : null;
}

function updateZoomLink(booking) {
  if (!zoomLinkEl) return;

  if (meetingProviderLabelEl) {
    meetingProviderLabelEl.textContent = booking?.meetingProvider || "Meeting Link";
  }

  const hasMeetingLink = Boolean(booking?.meetingLink);
  const accessAllowed = meetingAccessAllowedNow(booking);
  scheduleMeetingAccessRefresh(booking);

  if (hasMeetingLink && accessAllowed) {
    zoomLinkEl.href = booking.meetingLink;
    zoomLinkEl.textContent = booking?.meetingLinkLabel || "Open Meeting Link";
    zoomLinkEl.removeAttribute("aria-disabled");
    if (meetingLinkStatusTextEl) {
      meetingLinkStatusTextEl.textContent =
        booking?.meetingLinkStatusMessage || "Meeting link is ready.";
    }
  } else {
    zoomLinkEl.href = "#";
    zoomLinkEl.textContent = hasMeetingLink
      ? booking?.meetingLinkLabel || "Open Meeting Link"
      : "Meeting link pending";
    zoomLinkEl.setAttribute("aria-disabled", "true");
    if (meetingLinkStatusTextEl) {
      meetingLinkStatusTextEl.textContent = hasMeetingLink
        ? booking?.meetingAccessMessage || "Meeting access is not available yet."
        : booking?.meetingLinkStatusMessage || "Meeting link will be shared soon.";
    }
  }
}

if (zoomLinkEl) {
  zoomLinkEl.addEventListener("click", (event) => {
    const booking = getSelectedBooking();

    if (zoomLinkEl.getAttribute("aria-disabled") === "true" && meetingAccessAllowedNow(booking)) {
      updateZoomLink(booking);
    }

    if (zoomLinkEl.getAttribute("aria-disabled") === "true") {
      event.preventDefault();
    }
  });
}

function meetingAccessAllowedNow(booking) {
  if (!booking) {
    return false;
  }

  return Boolean(booking.meetingLink || booking.meetingAccessAllowed);
}

function scheduleMeetingAccessRefresh(booking) {
  if (meetingAccessTimerId) {
    window.clearTimeout(meetingAccessTimerId);
    meetingAccessTimerId = null;
  }

  if (!booking || booking.meetingAccessAllowed || !booking.meetingAccessOpensAt) {
    return;
  }

  const opensAt = Date.parse(booking.meetingAccessOpensAt);
  const delay = opensAt - Date.now();

  if (!Number.isFinite(opensAt) || delay <= 0) {
    return;
  }

  meetingAccessTimerId = window.setTimeout(() => {
    booking.meetingAccessAllowed = true;
    updateMeetingInfoFromSelected();
  }, Math.min(delay + 250, 2147483647));
}

function getInitials(name) {
  return String(name || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || "")
    .join("") || "GP";
}

function setChatStatus(text) {
  if (chatStatusEl) {
    chatStatusEl.textContent = text;
  }
}

function getDefaultSelectedDateKey() {
  if (selectedBooking?.sessionDateKey && bookedDates[selectedBooking.sessionDateKey]) {
    return selectedBooking.sessionDateKey;
  }

  const keys = Object.keys(bookedDates).sort();
  const futureKey = keys.find((key) => {
    const compare = parseDateKey(key);
    compare.setHours(0, 0, 0, 0);
    const t = new Date(today);
    t.setHours(0, 0, 0, 0);
    return compare >= t;
  });

  return futureKey || keys[0];
}

function getDaysInMonth(year, monthIndex) {
  return new Date(year, monthIndex + 1, 0).getDate();
}

function getStartDay(year, monthIndex) {
  return new Date(year, monthIndex, 1).getDay();
}

function isTodayKey(key) {
  return key === todayKey;
}

function formatFullDate(dateObj) {
  return `${monthNames[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}`;
}

function updateMeetingInfoFromSelected() {
  const booking = getSelectedBooking();
  const dateObj = selectedDateKey ? parseDateKey(selectedDateKey) : null;

  meetingDateEl.textContent = dateObj ? formatFullDate(dateObj) : "Not set";
  meetingTimeEl.textContent = booking ? booking.time : "Not set";
  mentorNameEl.textContent = booking?.mentorName || meetingData.mentorName;
  updateZoomLink(booking);
  syncCancelState(booking);
  syncMentorNotesState(booking);
  syncSelectedService(booking);
  syncFeedbackState(booking);
  void updateChatFromSelected(booking);
}

function syncCancelState(booking) {
  if (!cancelMeetingBtn) return;

  const hasBooking = Boolean(booking);
  const canCancel = Boolean(booking?.canCancel && booking?.cancelUrl && cancelBookingForm);

  cancelMeetingBtn.disabled = !hasBooking;
  cancelMeetingBtn.setAttribute("aria-disabled", hasBooking ? "false" : "true");
  cancelMeetingBtn.textContent = canCancel ? "Cancel Meeting" : "Contact Support";
  cancelMeetingBtn.title = canCancel
    ? "Cancel this meeting"
    : booking?.cancelPolicyCopy || "Self-service cancellation closes 24 hours before the meeting";

  if (!cancelBookingForm) {
    return;
  }

  cancelBookingForm.action = canCancel ? booking.cancelUrl : "";

  if (cancelBookingReasonInput) {
    cancelBookingReasonInput.value = booking?.service
      ? `Cancelled from booking page for ${booking.service}`
      : "Cancelled from booking page";
  }
}

function syncSelectedService(booking) {
  const selectedValue = normalizeServiceValue(
    booking?.serviceSlug || booking?.service || meetingData.selectedServiceSlug || meetingData.selectedService,
  );

  serviceCards.forEach((card) => {
    const serviceName = card.querySelector(".service-name")?.textContent || "";
    const matches = normalizeServiceValue(serviceName) === selectedValue;
    card.classList.toggle("selected", matches);
  });

  syncOfficeHoursServiceChoiceState(booking);
}

function isOfficeHoursBooking(booking) {
  return normalizeServiceValue(booking?.serviceSlug || booking?.service || booking?.meetingSize) === "office_hours";
}

function syncOfficeHoursServiceChoiceState(booking) {
  const serviceChoice = booking?.serviceChoice || null;
  const isOfficeHours = isOfficeHoursBooking(booking);
  const focusName = serviceChoice?.currentServiceName || booking?.officeHoursFocusName || "the scheduled weekly focus";

  if (serviceLockNoteEl) {
    serviceLockNoteEl.textContent = isOfficeHours
      ? `Booked as Office Hours. Current focus: ${focusName}.`
      : "Service is locked after booking and cannot be changed here.";
  }

  if (!officeHoursServiceChoiceRow || !officeHoursServiceChoiceNote || !openOfficeHoursServiceChoiceBtn) {
    return;
  }

  officeHoursServiceChoiceRow.hidden = !isOfficeHours;

  if (!isOfficeHours) {
    return;
  }

  officeHoursServiceChoiceNote.textContent = serviceChoice?.eligible
    ? "You are the only student booked in the service-choice window."
    : serviceChoice?.reason || "Office Hours focus cannot be changed for this session.";

  openOfficeHoursServiceChoiceBtn.hidden = !serviceChoice?.eligible;
  openOfficeHoursServiceChoiceBtn.disabled = !serviceChoice?.eligible;
}

function setOfficeHoursChoiceAlert(message = "", type = "error") {
  if (!officeHoursServiceChoiceAlert) return;

  officeHoursServiceChoiceAlert.hidden = !message;
  officeHoursServiceChoiceAlert.textContent = message;
  officeHoursServiceChoiceAlert.classList.toggle("success", type === "success");
}

function renderOfficeHoursServiceChoiceOptions(serviceChoice) {
  if (!officeHoursServiceChoiceOptions) return;

  officeHoursServiceChoiceOptions.innerHTML = "";
  selectedOfficeHoursServiceChoiceId = Number(serviceChoice?.currentServiceId || 0) || null;

  (serviceChoice?.availableServices || []).forEach((service) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "service-choice-option";
    button.textContent = service.name || "Service";
    button.dataset.serviceId = String(service.id);
    button.classList.toggle("active", Number(service.id) === Number(selectedOfficeHoursServiceChoiceId));

    button.addEventListener("click", () => {
      selectedOfficeHoursServiceChoiceId = Number(service.id);
      officeHoursServiceChoiceOptions
        .querySelectorAll(".service-choice-option")
        .forEach((item) => item.classList.toggle("active", item === button));
    });

    officeHoursServiceChoiceOptions.appendChild(button);
  });
}

function openOfficeHoursServiceChoiceModal(booking = getSelectedBooking()) {
  const serviceChoice = booking?.serviceChoice || null;

  if (!officeHoursServiceChoiceModal || !serviceChoice?.eligible) {
    return;
  }

  if (officeHoursServiceChoiceModalText) {
    officeHoursServiceChoiceModalText.textContent =
      "You are the only student booked for this session. Choose the focus before the 12-hour cutoff so your mentor can prepare.";
  }

  renderOfficeHoursServiceChoiceOptions(serviceChoice);
  setOfficeHoursChoiceAlert("");
  officeHoursServiceChoiceModal.classList.remove("hidden");
}

function closeOfficeHoursServiceChoiceModal() {
  officeHoursServiceChoiceModal?.classList.add("hidden");
}

function applyOfficeHoursServiceChoicePayload(bookingId, serviceChoice) {
  const applyToBooking = (booking) => {
    if (!booking || Number(booking.id) !== Number(bookingId)) return;

    booking.serviceChoice = serviceChoice;
    booking.officeHoursFocusName = serviceChoice?.currentServiceName || booking.officeHoursFocusName;
  };

  activeBookings.forEach(applyToBooking);
  Object.values(bookingsById).forEach(applyToBooking);
  Object.values(bookedDates).forEach(applyToBooking);
  bookingGroups.forEach((group) => {
    (group?.items || []).forEach(applyToBooking);
  });
}

async function saveOfficeHoursServiceChoice() {
  const booking = getSelectedBooking();
  const serviceChoice = booking?.serviceChoice || null;

  if (!booking?.id || !serviceChoice?.changeUrl || !selectedOfficeHoursServiceChoiceId) {
    setOfficeHoursChoiceAlert("Choose a service before saving.");
    return;
  }

  saveOfficeHoursServiceChoiceBtn.disabled = true;
  setOfficeHoursChoiceAlert("");

  try {
    const response = await fetch(serviceChoice.changeUrl, {
      method: "PATCH",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken,
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
      body: JSON.stringify({ service_config_id: selectedOfficeHoursServiceChoiceId }),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.message || "Unable to update the office-hours focus.");
    }

    applyOfficeHoursServiceChoicePayload(booking.id, payload.serviceChoice);
    updateMeetingInfoFromSelected();
    renderUpcomingAppointments();
    setOfficeHoursChoiceAlert(payload.message || "Office-hours focus updated.", "success");

    window.setTimeout(() => {
      closeOfficeHoursServiceChoiceModal();
    }, 700);
  } catch (error) {
    setOfficeHoursChoiceAlert(error.message || "Unable to update the office-hours focus.");
  } finally {
    saveOfficeHoursServiceChoiceBtn.disabled = false;
  }
}

function syncMentorNotesState(booking) {
  if (!mentorNotesBtn) return;

  const hasBooking = Boolean(booking?.id);
  const canOpenNotes = Boolean(booking?.mentorNotesAvailable && booking?.mentorNotesUrl);

  mentorNotesBtn.disabled = !hasBooking || !canOpenNotes;
  mentorNotesBtn.textContent = booking?.mentorNotesLabel || "Add Session Notes";
  mentorNotesBtn.title = canOpenNotes
    ? "Open internal mentor notes for this hosted session"
    : booking?.mentorNotesHelper || "Mentor notes can only be added for sessions hosted by you.";

  if (mentorNotesHelperEl) {
    mentorNotesHelperEl.textContent = !hasBooking
      ? "Select a booking to open internal mentor notes."
      : booking?.mentorNotesHelper || "Internal notes stay visible to mentors only.";
  }
}

function setFeedbackFieldsDisabled(disabled) {
  [
    feedbackStarsEl,
    feedbackCommentEl,
    feedbackSubmitBtn,
  ].forEach((field) => {
    if (field) {
      field.disabled = disabled;
    }
  });

  feedbackStarButtons.forEach((button) => {
    button.disabled = disabled;
  });

  document
    .querySelectorAll('input[name="preparedness_rating"], input[name="recommend"]')
    .forEach((field) => {
      field.disabled = disabled;
    });
}

function syncFeedbackState(booking) {
  if (!feedbackForm || !openFeedbackModalBtn) return;

  const hasBooking = Boolean(booking?.id);
  const isAllowed = Boolean(booking?.feedbackAllowed);
  const isSubmitted = Boolean(booking?.feedbackSubmitted);
  const shouldDisable = !hasBooking || !isAllowed || isSubmitted;

  feedbackForm.action = booking?.feedbackSubmitUrl || "/student/feedback";

  if (feedbackBookingIdInput) {
    feedbackBookingIdInput.value = booking?.id || "";
  }

  if (feedbackServiceTypeInput) {
    feedbackServiceTypeInput.value = booking?.serviceSlug || booking?.service || "";
  }

  if (feedbackReasonEl) {
    feedbackReasonEl.textContent = booking?.feedbackUnlockReason
      || "Feedback will unlock after attendance is verified or the fallback window passes.";
  }

  if (feedbackStatusPillEl) {
    feedbackStatusPillEl.textContent = !hasBooking
      ? "Unavailable"
      : isSubmitted
        ? "Submitted"
        : isAllowed
          ? "Open"
          : "Locked";
    feedbackStatusPillEl.classList.toggle("open", hasBooking && isAllowed && !isSubmitted);
    feedbackStatusPillEl.classList.toggle("done", isSubmitted);
  }

  if (feedbackHelperEl) {
    feedbackHelperEl.textContent = !hasBooking
      ? "Select a booking to leave feedback."
      : isSubmitted
        ? "Feedback has already been submitted for this booking."
        : isAllowed
          ? `Leave feedback for ${booking.service || "this session"} with ${booking.mentorName || meetingData.mentorName}.`
          : `${booking?.attendanceLabel || "Attendance is still pending"}. ${booking?.feedbackUnlockReason || ""}`.trim();
  }

  if (feedbackSubmitBtn) {
    feedbackSubmitBtn.textContent = isSubmitted ? "Feedback Submitted" : "Submit Feedback";
  }

  if (feedbackModalHelperEl) {
    feedbackModalHelperEl.textContent = feedbackHelperEl?.textContent || "";
  }

  openFeedbackModalBtn.disabled = shouldDisable;
  openFeedbackModalBtn.textContent = isSubmitted
    ? "Feedback Submitted"
    : isAllowed
      ? "Open Feedback Form"
      : "Feedback Locked";

  populateFeedbackModal(booking);
  setFeedbackFieldsDisabled(shouldDisable);
}

function setFeedbackRating(value) {
  const numericValue = Number(value || 0);

  if (feedbackStarsEl) {
    feedbackStarsEl.value = numericValue ? String(numericValue) : "";
  }

  feedbackStarButtons.forEach((button) => {
    const starValue = Number(button.dataset.value || 0);
    button.classList.toggle("active", starValue <= numericValue);
  });

  if (feedbackRatingLabelEl) {
    feedbackRatingLabelEl.textContent = numericValue
      ? `${numericValue} out of 5`
      : "Select a rating";
  }
}

function populateFeedbackModal(booking) {
  if (!feedbackModal) return;

  if (feedbackMentorNameEl) {
    feedbackMentorNameEl.textContent = booking?.mentorName || meetingData.mentorName || "-";
  }

  if (feedbackMentorProgramEl) {
    feedbackMentorProgramEl.textContent = booking?.mentorProgram || "-";
  }

  if (feedbackMentorTypeEl) {
    feedbackMentorTypeEl.textContent = booking?.mentorType || "-";
  }

  if (feedbackMentorEmailEl) {
    feedbackMentorEmailEl.textContent = booking?.mentorEmail || "-";
  }

  if (feedbackMentorSchoolEl) {
    feedbackMentorSchoolEl.textContent = booking?.mentorSchool || "-";
  }

  if (feedbackSessionDateEl) {
    feedbackSessionDateEl.textContent = booking?.sessionDateLabel || "Not set";
  }

  const selectedService = normalizeServiceValue(booking?.service || booking?.serviceSlug);
  feedbackServiceCards.forEach((card) => {
    const matches = normalizeServiceValue(card.dataset.service) === selectedService;
    card.classList.toggle("active", matches);
  });
}

feedbackStarButtons.forEach((button) => {
  button.addEventListener("click", () => {
    if (button.disabled) {
      return;
    }

    setFeedbackRating(button.dataset.value || "");
  });
});

feedbackScaleCards.forEach((card) => {
  const input = card.querySelector('input[name="preparedness_rating"]');

  input?.addEventListener("change", () => {
    feedbackScaleCards.forEach((item) => item.classList.remove("selected"));

    if (input.checked) {
      card.classList.add("selected");
    }
  });
});

feedbackBinaryCards.forEach((card) => {
  const input = card.querySelector('input[name="recommend"]');

  input?.addEventListener("change", () => {
    feedbackBinaryCards.forEach((item) => item.classList.remove("selected"));

    if (input.checked) {
      card.classList.add("selected");
    }
  });
});

if (feedbackCommentEl && feedbackCharCountEl) {
  feedbackCommentEl.addEventListener("input", () => {
    feedbackCharCountEl.textContent = String(feedbackCommentEl.value.length);
  });
}

function renderChatEmpty(message) {
  if (!chatWindow) return;

  chatWindow.innerHTML = "";

  const wrapper = document.createElement("div");
  wrapper.className = "chat-message user";

  const bubbleWrap = document.createElement("div");
  bubbleWrap.className = "chat-bubble-wrap";

  const meta = document.createElement("div");
  meta.className = "chat-meta";
  meta.textContent = "Chat";

  const bubble = document.createElement("div");
  bubble.className = "chat-bubble";
  bubble.textContent = message;

  bubbleWrap.appendChild(meta);
  bubbleWrap.appendChild(bubble);
  wrapper.appendChild(bubbleWrap);
  chatWindow.appendChild(wrapper);
}

function setTypingIndicator(text = "") {
  if (!chatTypingEl) return;

  chatTypingEl.textContent = text;
}

function clearTypingIndicator() {
  if (remoteTypingTimeoutId) {
    clearTimeout(remoteTypingTimeoutId);
    remoteTypingTimeoutId = null;
  }

  setTypingIndicator("");
}

function createChatMessageElement(message) {
  const wrapper = document.createElement("div");
  wrapper.className = `chat-message ${message.isOwn ? "user" : "mentor"}`;
  wrapper.dataset.messageId = String(message.id);

  if (!message.isOwn) {
    const avatar = document.createElement("div");
    avatar.className = "chat-avatar";
    avatar.textContent = getInitials(message.senderName);
    wrapper.appendChild(avatar);
  }

  const bubbleWrap = document.createElement("div");
  bubbleWrap.className = "chat-bubble-wrap";

  const meta = document.createElement("div");
  meta.className = "chat-meta";
  meta.textContent = message.isOwn ? meetingData.viewerRoleLabel : message.senderName;

  const bubble = document.createElement("div");
  bubble.className = "chat-bubble";
  bubble.textContent = message.messageText;

  bubbleWrap.appendChild(meta);
  bubbleWrap.appendChild(bubble);
  wrapper.appendChild(bubbleWrap);

  return wrapper;
}

function renderChatMessages(messages) {
  if (!chatWindow) return;

  chatWindow.innerHTML = "";

  if (!Array.isArray(messages) || messages.length === 0) {
    renderChatEmpty("No messages yet. Start the conversation before the session.");
    return;
  }

  messages.forEach((message) => {
    chatWindow.appendChild(createChatMessageElement(message));
  });

  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function normalizeChatMessage(message) {
  if (!message || typeof message !== "object") {
    return message;
  }

  const senderId = Number(message.senderId || 0);
  const viewerId = Number(meetingData.viewerId || 0);

  return {
    ...message,
    isOwn: senderId > 0 && viewerId > 0 ? senderId === viewerId : Boolean(message.isOwn),
  };
}

function mergeChatMessage(bookingId, message) {
  const cacheKey = String(bookingId);
  const existing = chatMessagesByBooking.get(cacheKey) || [];
  const normalizedMessage = normalizeChatMessage(message);

  if (existing.some((row) => Number(row.id) === Number(normalizedMessage.id))) {
    return existing;
  }

  const next = [...existing, normalizedMessage].sort((a, b) => Number(a.id) - Number(b.id));
  chatMessagesByBooking.set(cacheKey, next);
  return next;
}

function ensureChatClient() {
  const realtime = bookingDetailsData.chat?.realtime || {};
  const authEndpoint =
    bookingDetailsData.chat?.authEndpoint || "/broadcasting/auth";

  if (!realtime.enabled || typeof window.Pusher === "undefined") {
    return null;
  }

  if (chatClient) {
    return chatClient;
  }

  chatClient = new window.Pusher(realtime.key, {
    cluster: realtime.cluster || "mt1",
    wsHost: realtime.host,
    wsPort: realtime.port,
    wssPort: realtime.port,
    forceTLS: realtime.scheme === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint,
    disableStats: true,
    auth: {
      headers: {
        "X-CSRF-TOKEN": csrfToken,
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
    },
  });

  chatClient.connection.bind("connected", () => setChatStatus("Live"));
  chatClient.connection.bind("error", () => setChatStatus("Saved in DB"));
  chatClient.connection.bind("unavailable", () => setChatStatus("Offline"));

  return chatClient;
}

function sendTypingEvent(isTyping) {
  if (!activeChatChannel || !activeChatBookingId) {
    return;
  }

  try {
    activeChatChannel.trigger("client-typing", {
      bookingId: Number(activeChatBookingId),
      senderId: Number(meetingData.viewerId || 0),
      senderName: bookingDetailsData.viewerName || meetingData.viewerRoleLabel || "You",
      isTyping,
    });
  } catch (error) {
    console.debug("Unable to send typing event.", error);
  }
}

function stopTypingIndicatorBroadcast() {
  if (localTypingTimeoutId) {
    clearTimeout(localTypingTimeoutId);
    localTypingTimeoutId = null;
  }

  if (!localTypingActive) {
    return;
  }

  localTypingActive = false;
  sendTypingEvent(false);
}

function queueTypingIndicatorBroadcast() {
  if (!chatInput || !activeChatBookingId) {
    return;
  }

  const hasText = chatInput.value.trim().length > 0;

  if (!hasText) {
    stopTypingIndicatorBroadcast();
    return;
  }

  if (!localTypingActive) {
    localTypingActive = true;
    sendTypingEvent(true);
  }

  if (localTypingTimeoutId) {
    clearTimeout(localTypingTimeoutId);
  }

  localTypingTimeoutId = window.setTimeout(() => {
    stopTypingIndicatorBroadcast();
  }, 1600);
}

function handleRemoteTypingEvent(payload) {
  if (
    !payload ||
    Number(payload.bookingId) !== Number(activeChatBookingId) ||
    Number(payload.senderId) === Number(meetingData.viewerId || 0)
  ) {
    return;
  }

  if (!payload.isTyping) {
    clearTypingIndicator();
    return;
  }

  const senderName = String(payload.senderName || meetingData.counterpartLabel || "Someone");
  setTypingIndicator(`${senderName} is typing...`);

  if (remoteTypingTimeoutId) {
    clearTimeout(remoteTypingTimeoutId);
  }

  remoteTypingTimeoutId = window.setTimeout(() => {
    clearTypingIndicator();
  }, 2200);
}

function subscribeToChatChannel(booking) {
  const client = ensureChatClient();

  if (!client || !booking?.chatChannel) {
    setChatStatus("Saved in DB");
    return;
  }

  const channelName = `private-${booking.chatChannel}`;

  if (activeChatChannelName === channelName) {
    return;
  }

  if (activeChatChannelName) {
    stopTypingIndicatorBroadcast();
    clearTypingIndicator();
    client.unsubscribe(activeChatChannelName);
  }

  activeChatChannelName = channelName;
  activeChatChannel = client.subscribe(channelName);
  activeChatChannel.bind("chat.message.sent", (payload) => {
    if (!payload?.message || Number(payload.bookingId) !== Number(activeChatBookingId)) {
      return;
    }

    const messages = mergeChatMessage(payload.bookingId, payload.message);
    renderChatMessages(messages);
    clearTypingIndicator();
  });
  activeChatChannel.bind("client-typing", handleRemoteTypingEvent);
}

async function loadChatThread(booking) {
  if (!booking?.chatThreadUrl) {
    renderChatEmpty("Chat is not available for this booking.");
    setChatStatus("Unavailable");
    clearTypingIndicator();
    return;
  }

  activeChatBookingId = booking.id;
  subscribeToChatChannel(booking);

  const cacheKey = String(booking.id);
  if (chatMessagesByBooking.has(cacheKey)) {
    renderChatMessages(chatMessagesByBooking.get(cacheKey));
  } else {
    renderChatEmpty("Loading conversation...");
  }

  try {
    const response = await fetch(booking.chatThreadUrl, {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
      credentials: "same-origin",
    });

    if (!response.ok) {
      throw new Error("Unable to load chat thread.");
    }

    const payload = await response.json();
    const messages = Array.isArray(payload.messages)
      ? payload.messages.map(normalizeChatMessage)
      : [];
    chatMessagesByBooking.set(cacheKey, messages);

    if (Number(activeChatBookingId) === Number(booking.id)) {
      renderChatMessages(messages);
      setChatStatus(chatClient ? "Live" : "Saved in DB");
    }
  } catch (error) {
    renderChatEmpty("Unable to load the conversation right now.");
    setChatStatus("Unavailable");
    console.debug("Unable to load chat thread.", error);
  }
}

async function updateChatFromSelected(booking) {
  await loadChatThread(booking);
}

function getMonthLabel() {
  if (currentView === "year") {
    return String(currentDate.getFullYear());
  }

  if (currentView === "week") {
    const start = getStartOfWeek(currentDate);
    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    if (start.getMonth() === end.getMonth()) {
      return `${monthNames[start.getMonth()]} ${start.getFullYear()}`;
    }

    if (start.getFullYear() === end.getFullYear()) {
      return `${monthNames[start.getMonth()]} / ${monthNames[end.getMonth()]} ${start.getFullYear()}`;
    }

    return `${monthNames[start.getMonth()]} ${start.getFullYear()} / ${monthNames[end.getMonth()]} ${end.getFullYear()}`;
  }

  if (currentView === "day") {
    return formatFullDate(currentDate);
  }

  return `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
}

function buildMonthDropdown() {
  monthDropdown.innerHTML = "";

  if (currentView !== "month") {
    monthTitleButton.style.pointerEvents = "none";
    monthTitleButton.style.opacity = "0.7";
    monthDropdown.classList.remove("open");
    return;
  }

  monthTitleButton.style.pointerEvents = "auto";
  monthTitleButton.style.opacity = "1";

  for (let offset = 0; offset < 24; offset++) {
    const optionDate = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth() + offset,
      1,
    );
    const year = optionDate.getFullYear();
    const monthIndex = optionDate.getMonth();
    const label = `${monthNames[monthIndex]} ${year}`;

    const option = document.createElement("button");
    option.type = "button";
    option.className = "month-option";
    option.textContent = label;

    if (
      year === currentDate.getFullYear() &&
      monthIndex === currentDate.getMonth()
    ) {
      option.classList.add("active");
    }

    option.addEventListener("click", () => {
      currentDate = new Date(year, monthIndex, 1);
      renderCalendar();
      monthDropdown.classList.remove("open");
    });

    monthDropdown.appendChild(option);
  }
}

function getStartOfWeek(dateObj) {
  const date = new Date(dateObj);
  const day = date.getDay();
  date.setDate(date.getDate() - day);
  date.setHours(0, 0, 0, 0);
  return date;
}

function renderMonthView() {
  const year = currentDate.getFullYear();
  const monthIndex = currentDate.getMonth();
  const daysInMonth = getDaysInMonth(year, monthIndex);
  const startDay = getStartDay(year, monthIndex);

  const daysHeader = document.createElement("div");
  daysHeader.className = "calendar-days";
  weekDayShort.forEach((dayName) => {
    const div = document.createElement("div");
    div.textContent = dayName;
    daysHeader.appendChild(div);
  });

  const grid = document.createElement("div");
  grid.className = "calendar-grid";

  const totalSlots = startDay + daysInMonth;
  const endPadding = (7 - (totalSlots % 7)) % 7;

  for (let i = 0; i < startDay; i++) {
    const emptyCell = document.createElement("div");
    emptyCell.className = "day empty";
    grid.appendChild(emptyCell);
  }

  for (let day = 1; day <= daysInMonth; day++) {
    const key = formatDateKey(year, monthIndex, day);
    const cell = document.createElement("button");
    cell.type = "button";
    cell.className = "day";
    cell.innerHTML = `<span>${day}</span>`;

    if (isTodayKey(key)) {
      cell.classList.add("today");
    }

    if (bookedDates[key]) {
      cell.classList.add("booked");

      const dayBookings = getBookingsByDateKey(key);
      const timeEl = document.createElement("span");
      timeEl.className = "day-time";
      timeEl.textContent = dayBookings.length > 1
        ? `${dayBookings.length} bookings`
        : bookedDates[key].time;
      cell.appendChild(timeEl);

      cell.addEventListener("click", () => {
        selectBookingForDate(key);
        currentView = "day";
        currentDate = parseDateKey(key);
        setActiveViewButton();
        updateMeetingInfoFromSelected();
        renderCalendar();
        renderUpcomingAppointments();
      });
    } else {
      cell.disabled = true;
      cell.classList.add("muted");
    }

    if (key === selectedDateKey) {
      cell.classList.add("active");
    }

    grid.appendChild(cell);
  }

  for (let i = 0; i < endPadding; i++) {
    const emptyCell = document.createElement("div");
    emptyCell.className = "day empty";
    grid.appendChild(emptyCell);
  }

  calendarContentEl.appendChild(daysHeader);
  calendarContentEl.appendChild(grid);
}

function renderWeekView() {
  const start = getStartOfWeek(currentDate);

  const daysHeader = document.createElement("div");
  daysHeader.className = "week-days-row";

  weekDayShort.forEach((dayName) => {
    const div = document.createElement("div");
    div.textContent = dayName;
    daysHeader.appendChild(div);
  });

  const grid = document.createElement("div");
  grid.className = "week-grid";

  for (let i = 0; i < 7; i++) {
    const dayDate = new Date(start);
    dayDate.setDate(start.getDate() + i);

    const key = formatDateKey(
      dayDate.getFullYear(),
      dayDate.getMonth(),
      dayDate.getDate(),
    );
    const booking = bookedDates[key];
    const dayBookings = getBookingsByDateKey(key);

    const card = document.createElement("button");
    card.type = "button";
    card.className = "week-day-card";

    if (booking) card.classList.add("has-booking");
    if (key === selectedDateKey) card.classList.add("selected");

    const top = document.createElement("div");
    top.className = "week-day-top";
    top.innerHTML = `<span>${dayDate.getDate()}</span>`;

    if (isTodayKey(key)) {
      const dot = document.createElement("span");
      dot.className = "today-dot";
      top.appendChild(dot);
    }

    card.appendChild(top);

    if (booking) {
      const bookingEl = document.createElement("div");
      bookingEl.className = "week-booking";
      bookingEl.innerHTML = `
        <strong>${escapeHtml(dayBookings.length > 1 ? `${dayBookings.length} meetings` : booking.service)}</strong>
        <span>${escapeHtml(dayBookings.length > 1 ? dayBookings.map((item) => item.time).join(", ") : booking.time)}</span>
      `;
      card.appendChild(bookingEl);
    } else {
      const empty = document.createElement("div");
      empty.className = "week-booking";
      empty.textContent = "No booking";
      card.appendChild(empty);
    }

    card.addEventListener("click", () => {
      currentDate = new Date(dayDate);
      if (booking) {
        selectBookingForDate(key);
        currentView = "day";
        setActiveViewButton();
        updateMeetingInfoFromSelected();
        renderUpcomingAppointments();
      }
      renderCalendar();
    });

    grid.appendChild(card);
  }

  calendarContentEl.appendChild(daysHeader);
  calendarContentEl.appendChild(grid);
}

function renderDayView() {
  const key = formatDateKey(
    currentDate.getFullYear(),
    currentDate.getMonth(),
    currentDate.getDate(),
  );
  const dayBookings = getBookingsByDateKey(key);

  const wrapper = document.createElement("div");
  wrapper.className = "day-view-card";

  const header = document.createElement("div");
  header.className = "day-view-header";
  header.innerHTML = `
    <h4>${weekDayLong[currentDate.getDay()]}, ${monthNames[currentDate.getMonth()]} ${currentDate.getDate()}</h4>
    <p>${currentDate.getFullYear()}${isTodayKey(key) ? " • Today" : ""} • ${dayBookings.length} ${dayBookings.length === 1 ? "meeting" : "meetings"}</p>
  `;
  wrapper.appendChild(header);

  const eventsWrap = document.createElement("div");
  eventsWrap.className = "day-view-events";

  if (dayBookings.length === 0) {
    const empty = document.createElement("div");
    empty.className = "day-view-empty";
    empty.textContent = "No booked session on this day.";
    eventsWrap.appendChild(empty);
  } else {
    dayBookings.forEach((booking) => {
      const event = document.createElement("button");
      event.type = "button";
      event.className = "day-view-event";

      if (key === selectedDateKey && String(booking.id) === String(selectedBookingId)) {
        event.classList.add("active");
      }

      event.innerHTML = `
        <div class="day-view-event-main">
          <div class="day-view-event-title">${escapeHtml(booking.service)}</div>
          <div class="day-view-event-sub">With ${escapeHtml(booking.mentorName || meetingData.mentorName)}</div>
          <div class="day-view-event-meta">
            <span>${escapeHtml(booking.meetingTypeLabel || "Meeting")}</span>
            <span>${escapeHtml(booking.meetingSize || "1 on 1")}</span>
            <span>${escapeHtml(booking.attendanceLabel || booking.meetingStateLabel || "Booked")}</span>
          </div>
        </div>
        <div class="day-view-event-side">
          <span class="day-view-event-status is-${escapeHtml(String(booking.status || "confirmed"))}">${escapeHtml(booking.statusLabel || formatBookingStatus(booking.status))}</span>
          <span class="day-view-event-time">${escapeHtml(booking.time)}</span>
        </div>
      `;

      event.addEventListener("click", () => {
        selectBookingForDate(key, booking.id);
        updateMeetingInfoFromSelected();
        renderCalendar();
        renderUpcomingAppointments();
      });

      eventsWrap.appendChild(event);
    });
  }

  wrapper.appendChild(eventsWrap);
  calendarContentEl.appendChild(wrapper);
}

function renderYearView() {
  const year = currentDate.getFullYear();
  const grid = document.createElement("div");
  grid.className = "year-grid";

  for (let monthIndex = 0; monthIndex < 12; monthIndex++) {
    const card = document.createElement("button");
    card.type = "button";
    card.className = "year-month-card";

    if (
      currentDate.getFullYear() === year &&
      currentDate.getMonth() === monthIndex
    ) {
      card.classList.add("active");
    }

    const title = document.createElement("div");
    title.className = "year-month-card-title";
    title.textContent = monthNames[monthIndex];
    card.appendChild(title);

    const miniGrid = document.createElement("div");
    miniGrid.className = "year-mini-grid";

    const totalDays = getDaysInMonth(year, monthIndex);
    const startDay = getStartDay(year, monthIndex);
    const maxCells = 35;

    for (let i = 0; i < startDay; i++) {
      const empty = document.createElement("div");
      empty.className = "year-mini-day empty";
      miniGrid.appendChild(empty);
    }

    for (let day = 1; day <= totalDays; day++) {
      if (miniGrid.children.length >= maxCells) break;

      const key = formatDateKey(year, monthIndex, day);
      const dot = document.createElement("div");
      dot.className = "year-mini-day";
      dot.textContent = day;

      if (bookedDates[key]) {
        dot.classList.add("has-booking");
      }

      if (isTodayKey(key)) {
        dot.classList.add("today");
      }

      miniGrid.appendChild(dot);
    }

    while (miniGrid.children.length < maxCells) {
      const empty = document.createElement("div");
      empty.className = "year-mini-day empty";
      miniGrid.appendChild(empty);
    }

    card.appendChild(miniGrid);

    card.addEventListener("click", () => {
      currentView = "month";
      setActiveViewButton();
      currentDate = new Date(year, monthIndex, 1);
      renderCalendar();
    });

    grid.appendChild(card);
  }

  calendarContentEl.appendChild(grid);
}

function renderCalendar() {
  monthLabelEl.textContent = getMonthLabel();
  calendarContentEl.innerHTML = "";
  buildMonthDropdown();

  if (currentView === "month") {
    renderMonthView();
  } else if (currentView === "week") {
    renderWeekView();
  } else if (currentView === "day") {
    renderDayView();
  } else {
    renderYearView();
  }
}

function renderUpcomingAppointments() {
  upcomingListEl.innerHTML = "";
  if (currentListEl) {
    currentListEl.innerHTML = "";
  }
  const todayStart = new Date(today);
  todayStart.setHours(0, 0, 0, 0);

  renderAppointmentSection(currentListEl, currentBookings, "No live meetings right now.", todayStart);
  renderAppointmentSection(upcomingListEl, upcomingBookings, "No future bookings yet.", todayStart);
}

function renderAppointmentSection(container, sourceBookings, emptyMessage, todayStart) {
  if (!container) {
    return;
  }

  if (bookingGroups.length > 0) {
    let renderedGroup = false;

    bookingGroups.forEach((group) => {
      const items = Array.isArray(group?.items)
        ? group.items
            .filter((booking) =>
              sourceBookings.some((sourceBooking) => Number(sourceBooking?.id) === Number(booking?.id)),
            )
            .filter((booking) => booking?.sessionDateKey)
            .filter((booking) => {
              const dateObj = parseDateKey(booking.sessionDateKey);
              dateObj.setHours(0, 0, 0, 0);
              return dateObj >= todayStart;
            })
            .sort((a, b) => String(a.sessionDateKey).localeCompare(String(b.sessionDateKey)))
        : [];

      if (!items.length) {
        return;
      }

      renderedGroup = true;

      const section = document.createElement("section");
      section.className = "upcoming-group";

      const header = document.createElement("div");
      header.className = "upcoming-group-header";
      header.innerHTML = `
        <h4>${group.label || "Upcoming Bookings"}</h4>
        <span>${items.length} booking${items.length === 1 ? "" : "s"}</span>
      `;
      section.appendChild(header);

      const list = document.createElement("div");
      list.className = "upcoming-group-list";

      items.forEach((booking) => {
        const key = booking.sessionDateKey;
        const dateObj = parseDateKey(key);
        list.appendChild(createUpcomingItem(booking, key, dateObj));
      });

      section.appendChild(list);
      container.appendChild(section);
    });

    if (!renderedGroup) {
      container.innerHTML = `
        <div class="upcoming-empty-state">${escapeHtml(emptyMessage)}</div>
      `;
    }

    return;
  }

  const futureKeys = Object.keys(bookedDates)
    .sort()
    .filter((key) => {
      const dateObj = parseDateKey(key);
      dateObj.setHours(0, 0, 0, 0);
      return dateObj >= todayStart;
    });

  futureKeys.forEach((key) => {
    const dateObj = parseDateKey(key);
    getBookingsByDateKey(key).forEach((booking) => {
      if (sourceBookings.some((sourceBooking) => Number(sourceBooking?.id) === Number(booking?.id))) {
        container.appendChild(createUpcomingItem(booking, key, dateObj));
      }
    });
  });

  if (!container.children.length) {
    container.innerHTML = `
      <div class="upcoming-empty-state">${escapeHtml(emptyMessage)}</div>
    `;
  }
}

function createUpcomingItem(booking, key, dateObj) {
  const item = document.createElement("button");
  item.type = "button";
  item.className = "upcoming-item";
  const counterpartName =
    booking.mentorName || booking.counterpartName || meetingData.mentorName;
  const timeText = booking.time || booking.sessionTimeLabel || "Not set";
  const serviceText = booking.service || booking.serviceName || "Service";

  if (key === selectedDateKey) {
    if (booking?.id != null && selectedBookingId != null && booking.id !== selectedBookingId) {
      item.classList.remove("active");
    } else {
      item.classList.add("active");
    }
  }

  const relationshipText = booking.relationshipLabel
    ? `${booking.relationshipLabel} • ${counterpartName}`
    : `With ${counterpartName}`;

  item.innerHTML = `
    <div class="upcoming-item-left">
      <strong>${escapeHtml(formatFullDate(dateObj))}</strong>
      <span>${escapeHtml(relationshipText)}</span>
    </div>
    <div class="upcoming-item-right">
      <span class="time">${escapeHtml(timeText)}</span>
      <span class="service">${escapeHtml(serviceText)}</span>
    </div>
  `;

  item.addEventListener("click", () => {
    selectBookingForDate(key, booking?.id ?? null);
    currentDate = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
    updateMeetingInfoFromSelected();
    renderCalendar();
    renderUpcomingAppointments();
  });

  return item;
}

function setActiveViewButton() {
  viewButtons.forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.view === currentView);
  });
}

viewButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    currentView = btn.dataset.view;
    setActiveViewButton();

    if (currentView === "day" && selectedDateKey) {
      currentDate = parseDateKey(selectedDateKey);
    } else if (currentView === "month") {
      const selected = parseDateKey(selectedDateKey);
      currentDate = new Date(selected.getFullYear(), selected.getMonth(), 1);
    }

    renderCalendar();
  });
});

monthTitleButton.addEventListener("click", () => {
  if (currentView === "month") {
    monthDropdown.classList.toggle("open");
  }
});

document.addEventListener("click", (event) => {
  if (
    !monthTitleButton.contains(event.target) &&
    !monthDropdown.contains(event.target)
  ) {
    monthDropdown.classList.remove("open");
  }
});

prevMonthBtn.addEventListener("click", () => {
  if (currentView === "month") {
    currentDate = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth() - 1,
      1,
    );
  } else if (currentView === "week") {
    currentDate = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth(),
      currentDate.getDate() - 7,
    );
  } else if (currentView === "day") {
    currentDate = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth(),
      currentDate.getDate() - 1,
    );
  } else {
    currentDate = new Date(currentDate.getFullYear() - 1, 0, 1);
  }

  renderCalendar();
});

nextMonthBtn.addEventListener("click", () => {
  if (currentView === "month") {
    currentDate = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth() + 1,
      1,
    );
  } else if (currentView === "week") {
    currentDate = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth(),
      currentDate.getDate() + 7,
    );
  } else if (currentView === "day") {
    currentDate = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth(),
      currentDate.getDate() + 1,
    );
  } else {
    currentDate = new Date(currentDate.getFullYear() + 1, 0, 1);
  }

  renderCalendar();
});

todayBtn.addEventListener("click", () => {
  if (currentView === "month") {
    currentDate = new Date(today.getFullYear(), today.getMonth(), 1);
  } else if (currentView === "year") {
    currentDate = new Date(today.getFullYear(), 0, 1);
  } else {
    currentDate = new Date(
      today.getFullYear(),
      today.getMonth(),
      today.getDate(),
    );
  }

  renderCalendar();
});

chatForm.addEventListener("submit", async function (e) {
  e.preventDefault();

  const booking = getBookingByDateKey(selectedDateKey);
  const message = chatInput.value.trim();

  if (!booking?.chatSendUrl || !message) {
    return;
  }

  const socketId = chatClient?.connection?.socket_id;
  stopTypingIndicatorBroadcast();

  try {
    const response = await fetch(booking.chatSendUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken,
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
        ...(socketId ? { "X-Socket-Id": socketId } : {}),
      },
      credentials: "same-origin",
      body: JSON.stringify({ message }),
    });

    if (!response.ok) {
      throw new Error("Unable to send message.");
    }

    const payload = await response.json();
    const savedMessage = payload?.message;

    if (!savedMessage) {
      throw new Error("Missing message payload.");
    }

    const messages = mergeChatMessage(booking.id, savedMessage);
    renderChatMessages(messages);
    chatInput.value = "";
    clearTypingIndicator();
    setChatStatus(chatClient ? "Live" : "Saved in DB");
  } catch (error) {
    setChatStatus("Send failed");
    console.debug("Unable to send chat message.", error);
  }
});

if (chatInput) {
  chatInput.addEventListener("input", () => {
    queueTypingIndicatorBroadcast();
  });

  chatInput.addEventListener("blur", () => {
    stopTypingIndicatorBroadcast();
  });
}

function openModal(modal) {
  modal.classList.remove("hidden");
}

function closeModal(modal) {
  modal.classList.add("hidden");
}

if (cancelMeetingBtn) {
  cancelMeetingBtn.addEventListener("click", () => {
    const booking = getBookingByDateKey(selectedDateKey);

    if (!booking) {
      return;
    }

    if (!booking?.canCancel || !booking?.cancelUrl || !cancelModal) {
      if (supportModal) {
        openModal(supportModal);
      }
      return;
    }

    openModal(cancelModal);
  });
}

if (mentorNotesBtn) {
  mentorNotesBtn.addEventListener("click", () => {
    const booking = getBookingByDateKey(selectedDateKey);

    if (!booking?.mentorNotesAvailable || !booking?.mentorNotesUrl) {
      return;
    }

    window.location.assign(booking.mentorNotesUrl);
  });
}

if (openFeedbackModalBtn) {
  openFeedbackModalBtn.addEventListener("click", () => {
    const booking = getBookingByDateKey(selectedDateKey);

    if (!booking || !feedbackModal) {
      return;
    }

    populateFeedbackModal(booking);
    openModal(feedbackModal);
  });
}

if (openOfficeHoursServiceChoiceBtn) {
  openOfficeHoursServiceChoiceBtn.addEventListener("click", () => {
    openOfficeHoursServiceChoiceModal(getSelectedBooking());
  });
}

if (closeOfficeHoursServiceChoiceBtn) {
  closeOfficeHoursServiceChoiceBtn.addEventListener("click", closeOfficeHoursServiceChoiceModal);
}

if (saveOfficeHoursServiceChoiceBtn) {
  saveOfficeHoursServiceChoiceBtn.addEventListener("click", () => {
    void saveOfficeHoursServiceChoice();
  });
}

if (closeFeedbackModalBtn) {
  closeFeedbackModalBtn.addEventListener("click", () => {
    closeModal(feedbackModal);
  });
}

if (cancelNo1) {
  cancelNo1.addEventListener("click", () => {
    closeModal(cancelModal);
  });
}

if (cancelYes1) {
  cancelYes1.addEventListener("click", () => {
    closeModal(cancelModal);
    openModal(cancelConfirmModal);
  });
}

if (cancelNo2) {
  cancelNo2.addEventListener("click", () => {
    closeModal(cancelConfirmModal);
  });
}

if (cancelYes2) {
  cancelYes2.addEventListener("click", () => {
    const booking = getBookingByDateKey(selectedDateKey);

    if (!booking?.canCancel || !booking?.cancelUrl || !cancelBookingForm) {
      closeModal(cancelConfirmModal);
      return;
    }

    closeModal(cancelConfirmModal);
    cancelYes2.disabled = true;
    cancelBookingForm.submit();
  });
}

if (supportCloseBtn) {
  supportCloseBtn.addEventListener("click", () => {
    closeModal(supportModal);
  });
}

if (feedbackModal) {
  feedbackModal.addEventListener("click", (event) => {
    if (event.target === feedbackModal) {
      closeModal(feedbackModal);
    }
  });
}

if (officeHoursServiceChoiceModal) {
  officeHoursServiceChoiceModal.addEventListener("click", (event) => {
    if (event.target === officeHoursServiceChoiceModal) {
      closeOfficeHoursServiceChoiceModal();
    }
  });
}

if (supportLink) {
  supportLink.addEventListener("click", function (e) {
    if (meetingData.supportUrl) {
      return;
    }

    e.preventDefault();
  });
}

updateMeetingInfoFromSelected();
setActiveViewButton();
renderCalendar();
renderUpcomingAppointments();
syncSelectedService(getSelectedBooking());
setFeedbackRating(feedbackStarsEl?.value || "");

document
  .querySelectorAll('input[name="preparedness_rating"]:checked')
  .forEach((input) => {
    input.closest(".feedback-scale-card")?.classList.add("selected");
  });

document
  .querySelectorAll('input[name="recommend"]:checked')
  .forEach((input) => {
    input.closest(".feedback-binary-card")?.classList.add("selected");
  });

if (feedbackCharCountEl && feedbackCommentEl) {
  feedbackCharCountEl.textContent = String(feedbackCommentEl.value.length);
}

if (feedbackModal && document.querySelector(".feedback-inline-alert.error.modal-alert")) {
  openModal(feedbackModal);
}

const autoOpenServiceChoiceBookingId = bookingDetailsData.autoOpenServiceChoiceBookingId;
if (autoOpenServiceChoiceBookingId && bookingsById[String(autoOpenServiceChoiceBookingId)]?.serviceChoice?.eligible) {
  selectedBookingId = Number(autoOpenServiceChoiceBookingId);
  selectedDateKey = bookingsById[String(autoOpenServiceChoiceBookingId)].sessionDateKey || selectedDateKey;
  updateMeetingInfoFromSelected();
  renderCalendar();
  renderUpcomingAppointments();
  openOfficeHoursServiceChoiceModal(bookingsById[String(autoOpenServiceChoiceBookingId)]);
}
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

// Initialize Lucide icons
if (typeof lucide !== "undefined") {
  lucide.createIcons();
}
