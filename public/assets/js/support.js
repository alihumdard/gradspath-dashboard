const menuBtn = document.getElementById("mobileMenuToggle");
const overlay = document.getElementById("sidebarOverlay");
const shell = document.querySelector(".app-shell");

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
    updateTheme(currentTheme === "dark" ? "light" : "dark");
  });
}

if (menuBtn && shell) {
  menuBtn.onclick = () => shell.classList.add("sidebar-active");
}

if (overlay && shell) {
  overlay.onclick = () => shell.classList.remove("sidebar-active");
}

const navItems = document.querySelectorAll(".nav-item");

function setActiveNav() {
  const currentPath = window.location.pathname;

  navItems.forEach((item) => {
    const href = item.getAttribute("href");

    if (href && currentPath.startsWith(href)) {
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

const supportForm = document.getElementById("supportForm");
const messageInput = document.getElementById("message");
const charCount = document.getElementById("charCount");
const successMessage = document.getElementById("successMessage");

if (messageInput && charCount) {
  charCount.textContent = messageInput.value.length;
  messageInput.addEventListener("input", () => {
    charCount.textContent = messageInput.value.length;
  });
}

if (supportForm) {
  supportForm.addEventListener("submit", () => {
    const submitButton = supportForm.querySelector('button[type="submit"]');

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Sending...";
    }
  });
}

if (successMessage) {
  setTimeout(() => {
    successMessage.classList.remove("visible");
  }, 4000);
}
