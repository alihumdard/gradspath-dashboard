const meetingData = {
  mentorName: "Emily Carter • Graduate Mentor • MBA • Harvard",
  zoomLink: "https://zoom.us/j/9876543210",
  selectedService: "Office Hours",
};

const bookedDates = {
  "2026-03-21": { time: "1:00 PM", service: "Office Hours" },
  "2026-04-09": { time: "11:00 AM", service: "Office Hours" },
  "2026-04-24": { time: "2:30 PM", service: "Office Hours" },
  "2026-05-06": { time: "3:00 PM", service: "Office Hours" },
};

const mentorNameEl = document.getElementById("mentorName");
const meetingDateEl = document.getElementById("meetingDate");
const meetingTimeEl = document.getElementById("meetingTime");
const zoomLinkEl = document.getElementById("zoomLink");
const monthLabelEl = document.getElementById("monthLabel");
const monthTitleButton = document.getElementById("monthTitleButton");
const monthDropdown = document.getElementById("monthDropdown");
const prevMonthBtn = document.getElementById("prevMonth");
const nextMonthBtn = document.getElementById("nextMonth");
const todayBtn = document.getElementById("todayBtn");
const calendarContentEl = document.getElementById("calendarContent");
const upcomingListEl = document.getElementById("upcomingList");

const cancelMeetingBtn = document.getElementById("cancelMeetingBtn");
const cancelModal = document.getElementById("cancelModal");
const cancelConfirmModal = document.getElementById("cancelConfirmModal");
const supportModal = document.getElementById("supportModal");
const cancelNo1 = document.getElementById("cancelNo1");
const cancelYes1 = document.getElementById("cancelYes1");
const cancelNo2 = document.getElementById("cancelNo2");
const cancelYes2 = document.getElementById("cancelYes2");
const supportCloseBtn = document.getElementById("supportCloseBtn");
const supportLink = document.getElementById("supportLink");

const chatForm = document.getElementById("chatForm");
const chatInput = document.getElementById("chatInput");
const chatWindow = document.getElementById("chatWindow");
const viewButtons = document.querySelectorAll(".view-btn");

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

mentorNameEl.textContent = meetingData.mentorName;
zoomLinkEl.href = meetingData.zoomLink;
zoomLinkEl.textContent = "Join Zoom Meeting";

function formatDateKey(year, monthIndex, day) {
  const month = String(monthIndex + 1).padStart(2, "0");
  const date = String(day).padStart(2, "0");
  return `${year}-${month}-${date}`;
}

function parseDateKey(key) {
  const [year, month, day] = key.split("-").map(Number);
  return new Date(year, month - 1, day);
}

function getDefaultSelectedDateKey() {
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
  const dateObj = parseDateKey(selectedDateKey);
  const booking = bookedDates[selectedDateKey];

  meetingDateEl.textContent = formatFullDate(dateObj);
  meetingTimeEl.textContent = booking ? booking.time : "Not set";
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

      const timeEl = document.createElement("span");
      timeEl.className = "day-time";
      timeEl.textContent = bookedDates[key].time;
      cell.appendChild(timeEl);

      cell.addEventListener("click", () => {
        selectedDateKey = key;
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
        <strong>${booking.service}</strong>
        <span>${booking.time}</span>
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
        selectedDateKey = key;
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
  const booking = bookedDates[key];

  const wrapper = document.createElement("div");
  wrapper.className = "day-view-card";

  const header = document.createElement("div");
  header.className = "day-view-header";
  header.innerHTML = `
    <h4>${weekDayLong[currentDate.getDay()]}, ${monthNames[currentDate.getMonth()]} ${currentDate.getDate()}</h4>
    <p>${currentDate.getFullYear()}${isTodayKey(key) ? " • Today" : ""}</p>
  `;
  wrapper.appendChild(header);

  const eventsWrap = document.createElement("div");
  eventsWrap.className = "day-view-events";

  if (!booking) {
    const empty = document.createElement("div");
    empty.className = "day-view-empty";
    empty.textContent = "No booked session on this day.";
    eventsWrap.appendChild(empty);
  } else {
    const event = document.createElement("button");
    event.type = "button";
    event.className = "day-view-event";

    if (key === selectedDateKey) {
      event.classList.add("active");
    }

    event.innerHTML = `
      <div>
        <div class="day-view-event-title">${booking.service}</div>
        <div class="day-view-event-sub">With ${meetingData.mentorName}</div>
      </div>
      <div class="day-view-event-time">${booking.time}</div>
    `;

    event.addEventListener("click", () => {
      selectedDateKey = key;
      updateMeetingInfoFromSelected();
      renderCalendar();
      renderUpcomingAppointments();
    });

    eventsWrap.appendChild(event);
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

  const now = new Date(today);
  now.setHours(0, 0, 0, 0);

  const futureKeys = Object.keys(bookedDates)
    .sort()
    .filter((key) => {
      const dateObj = parseDateKey(key);
      dateObj.setHours(0, 0, 0, 0);
      return dateObj >= now;
    });

  futureKeys.forEach((key) => {
    const booking = bookedDates[key];
    const dateObj = parseDateKey(key);

    const item = document.createElement("button");
    item.type = "button";
    item.className = "upcoming-item";

    if (key === selectedDateKey) {
      item.classList.add("active");
    }

    item.innerHTML = `
      <div class="upcoming-item-left">
        <strong>${formatFullDate(dateObj)}</strong>
        <span>With ${meetingData.mentorName}</span>
      </div>
      <div class="upcoming-item-right">
        <span class="time">${booking.time}</span>
        <span class="service">${booking.service}</span>
      </div>
    `;

    item.addEventListener("click", () => {
      selectedDateKey = key;
      currentDate = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
      updateMeetingInfoFromSelected();
      renderCalendar();
      renderUpcomingAppointments();
    });

    upcomingListEl.appendChild(item);
  });
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

chatForm.addEventListener("submit", function (e) {
  e.preventDefault();

  const message = chatInput.value.trim();
  if (!message) return;

  const wrapper = document.createElement("div");
  wrapper.className = "chat-message user";

  const bubbleWrap = document.createElement("div");
  bubbleWrap.className = "chat-bubble-wrap";

  const meta = document.createElement("div");
  meta.className = "chat-meta";
  meta.textContent = "You";

  const bubble = document.createElement("div");
  bubble.className = "chat-bubble";
  bubble.textContent = message;

  bubbleWrap.appendChild(meta);
  bubbleWrap.appendChild(bubble);
  wrapper.appendChild(bubbleWrap);
  chatWindow.appendChild(wrapper);

  chatInput.value = "";
  chatWindow.scrollTop = chatWindow.scrollHeight;

  setTimeout(() => {
    const mentorReply = document.createElement("div");
    mentorReply.className = "chat-message mentor";

    const avatar = document.createElement("div");
    avatar.className = "chat-avatar";
    avatar.textContent = "EC";

    const mentorWrap = document.createElement("div");
    mentorWrap.className = "chat-bubble-wrap";

    const mentorMeta = document.createElement("div");
    mentorMeta.className = "chat-meta";
    mentorMeta.textContent = "Emily Carter • Graduate Mentor • MBA • Harvard";

    const mentorBubble = document.createElement("div");
    mentorBubble.className = "chat-bubble";
    mentorBubble.textContent =
      "Thanks for the message. I saw this and will be ready for our session.";

    mentorWrap.appendChild(mentorMeta);
    mentorWrap.appendChild(mentorBubble);
    mentorReply.appendChild(avatar);
    mentorReply.appendChild(mentorWrap);
    chatWindow.appendChild(mentorReply);

    chatWindow.scrollTop = chatWindow.scrollHeight;
  }, 900);
});

function openModal(modal) {
  modal.classList.remove("hidden");
}

function closeModal(modal) {
  modal.classList.add("hidden");
}

cancelMeetingBtn.addEventListener("click", () => {
  openModal(cancelModal);
});

cancelNo1.addEventListener("click", () => {
  closeModal(cancelModal);
});

cancelYes1.addEventListener("click", () => {
  closeModal(cancelModal);
  openModal(cancelConfirmModal);
});

cancelNo2.addEventListener("click", () => {
  closeModal(cancelConfirmModal);
});

cancelYes2.addEventListener("click", () => {
  closeModal(cancelConfirmModal);
  openModal(supportModal);
});

supportCloseBtn.addEventListener("click", () => {
  closeModal(supportModal);
});

supportLink.addEventListener("click", function (e) {
  e.preventDefault();
  alert("Support form would open here for the refund request.");
});

updateMeetingInfoFromSelected();
setActiveViewButton();
renderCalendar();
renderUpcomingAppointments();
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
