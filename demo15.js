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

// Support form interactions: character counter + success message
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

if (supportForm && successMessage) {
  supportForm.addEventListener("submit", (event) => {
    event.preventDefault();
    successMessage.classList.add("visible");
    setTimeout(() => {
      successMessage.classList.remove("visible");
    }, 4000);
    supportForm.reset();
    if (charCount) charCount.textContent = "0";
  });
}
