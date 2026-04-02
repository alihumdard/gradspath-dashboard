const programPills = document.querySelectorAll(".program-pill");
const programNote = document.getElementById("programNote");
const summaryProgram = document.getElementById("summaryProgram");
const subscribeButton = document.getElementById("subscribeButton");
const checkoutSection = document.getElementById("checkoutSection");
const payButton = document.getElementById("payButton");
const themeToggle = document.getElementById("themeToggle");
const heroIcon = document.querySelector(".icon-top");

// Load saved theme from localStorage, default to 'light'
(function initTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  document.body.setAttribute("data-theme", savedTheme);
})();

// Theme Toggle Logic
if (themeToggle) {
  themeToggle.addEventListener("click", () => {
    const currentTheme = document.body.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    document.body.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
  });
}

function updateProgram(program) {
  summaryProgram.textContent = program;
  programNote.textContent =
    "Your selected program helps us track demand and improve matching. Credits can be used across all office hours, not just one program.";
}

programPills.forEach((pill) => {
  pill.addEventListener("click", () => {
    programPills.forEach((item) => item.classList.remove("selected"));
    pill.classList.add("selected");
    updateProgram(pill.dataset.program);
  });
});

subscribeButton.addEventListener("click", () => {
  checkoutSection.classList.remove("hidden");
  checkoutSection.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
});

payButton.addEventListener("click", () => {
  alert("Replace this with your real Stripe checkout flow.");
});

if (heroIcon) {
  heroIcon.addEventListener("mouseenter", () => {
    heroIcon.style.transform = "scale(1.06)";
  });

  heroIcon.addEventListener("mouseleave", () => {
    heroIcon.style.transform = "scale(1)";
  });
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
  });
});
