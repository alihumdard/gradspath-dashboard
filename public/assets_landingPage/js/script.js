(function () {
  const menuBtn = document.getElementById("menu-toggle");
  const menuIcon = document.getElementById("menu-icon");
  const mobileMenu = document.getElementById("mobile-menu");

  if (menuBtn && mobileMenu) {
    menuBtn.addEventListener("click", function () {
      mobileMenu.classList.toggle("hidden");
      if (menuIcon) {
        menuIcon.classList.toggle("fa-bars");
        menuIcon.classList.toggle("fa-xmark");
      }
    });
    document.addEventListener("click", function (e) {
      if (!menuBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
        mobileMenu.classList.add("hidden");
        if (menuIcon) {
          menuIcon.classList.add("fa-bars");
          menuIcon.classList.remove("fa-xmark");
        }
      }
    });
  }

  // Login / Signup modals
  const loginModal = document.getElementById("login-modal");
  const signupModal = document.getElementById("signup-modal");

  function openLogin() {
    if (signupModal) signupModal.classList.add("hidden");
    if (loginModal) loginModal.classList.remove("hidden");
  }
  function openSignup() {
    if (loginModal) loginModal.classList.add("hidden");
    if (signupModal) signupModal.classList.remove("hidden");
  }
  function closeLogin() {
    if (loginModal) loginModal.classList.add("hidden");
  }
  function closeSignup() {
    if (signupModal) signupModal.classList.add("hidden");
  }

  function getCookie(name) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  function isSignedIn() {
    // Show dashboard/logout when saved state indicates signed in.
    // Prefer cookie, with localStorage fallback (e.g., file:// and cookie-disabled browsers).
    var cookieSignedIn = getCookie("gradspaths_signed_in") === "1";
    var storageSignedIn = localStorage.getItem("gradpaths_signed_in") === "1";
    // return cookieSignedIn || storageSignedIn;
    return false;
  }

  function signOut() {
    localStorage.removeItem("gradpaths_signed_in");
    localStorage.removeItem("gradspaths_remember");
    localStorage.removeItem("gradspaths_remember_email");
    document.cookie = "gradspaths_signed_in=; path=/; max-age=0; SameSite=Strict";
    document.cookie = "gradspaths_user_email=; path=/; max-age=0; SameSite=Strict";
    updateAuthButtons();
    if (window.gradpathsUpdateContactSection) window.gradpathsUpdateContactSection();
  }

  function setBtnVisibility(el, show, mobile) {
    if (!el) return;
    if (show) {
      el.classList.remove("hidden");
      el.style.display = mobile ? "block" : "inline-flex";
    } else {
      el.classList.add("hidden");
      el.style.display = "none";
    }
  }

  function updateAuthButtons() {
    var signedIn = isSignedIn();
    var loginBtn = document.getElementById("btn-login");
    var signupBtn = document.getElementById("btn-signup");
    var loginBtnMob = document.getElementById("btn-login-mob");
    var signupBtnMob = document.getElementById("btn-signup-mob");
    var dashboardBtn = document.getElementById("btn-dashboard");
    var logoutBtn = document.getElementById("btn-logout");
    var dashboardBtnMob = document.getElementById("btn-dashboard-mob");
    var logoutBtnMob = document.getElementById("btn-logout-mob");
    var footerFindMentors = null;
    var footerSignup = document.getElementById("footer-signup");
    var footerLogin = document.getElementById("footer-login");

    if (signedIn) {
      setBtnVisibility(loginBtn, false, false);
      setBtnVisibility(signupBtn, false, false);
      setBtnVisibility(loginBtnMob, false, true);
      setBtnVisibility(signupBtnMob, false, true);
      setBtnVisibility(dashboardBtn, true, false);
      setBtnVisibility(logoutBtn, true, false);
      setBtnVisibility(dashboardBtnMob, true, true);
      setBtnVisibility(logoutBtnMob, true, true);
      // Footer: show Find Mentors, hide Sign Up and Log In
      if (footerFindMentors) {
        footerFindMentors.classList.remove("hidden");
        footerFindMentors.style.display = "block";
      }
      if (footerSignup) {
        footerSignup.classList.add("hidden");
        footerSignup.style.display = "none";
      }
      if (footerLogin) {
        footerLogin.classList.add("hidden");
        footerLogin.style.display = "none";
      }
    } else {
      setBtnVisibility(loginBtn, true, false);
      setBtnVisibility(signupBtn, true, false);
      setBtnVisibility(loginBtnMob, true, true);
      setBtnVisibility(signupBtnMob, true, true);
      setBtnVisibility(dashboardBtn, false, false);
      setBtnVisibility(logoutBtn, false, false);
      setBtnVisibility(dashboardBtnMob, false, true);
      setBtnVisibility(logoutBtnMob, false, true);
      // Footer: hide Find Mentors, show Sign Up and Log In
      if (footerFindMentors) {
        footerFindMentors.classList.add("hidden");
        footerFindMentors.style.display = "none";
      }
      if (footerSignup) {
        footerSignup.classList.remove("hidden");
        footerSignup.style.display = "block";
      }
      if (footerLogin) {
        footerLogin.classList.remove("hidden");
        footerLogin.style.display = "block";
      }
    }
    if (window.gradpathsUpdateContactSection) window.gradpathsUpdateContactSection();
  }

  window.gradpathsUpdateAuthButtons = updateAuthButtons;
  window.gradpathsSignOut = signOut;
  window.dispatchEvent(new Event("gradpathsUpdateAuthButtonsReady"));

  ["btn-login", "btn-login-mob"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener("click", openLogin);
  });
  ["btn-signup", "btn-signup-mob"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener("click", openSignup);
  });

  var dashboardBtn = document.getElementById("btn-dashboard");
  var dashboardBtnMob = document.getElementById("btn-dashboard-mob");
  var logoutBtn = document.getElementById("btn-logout");
  var logoutBtnMob = document.getElementById("btn-logout-mob");

  if (dashboardBtn) {
    dashboardBtn.addEventListener("click", function () {
      window.location.href = "https://gradspath-dashboard.vercel.app";
    });
  }
  if (dashboardBtnMob) {
    dashboardBtnMob.addEventListener("click", function () {
      window.location.href = "https://gradspath-dashboard.vercel.app";
    });
  }
  if (logoutBtn) logoutBtn.addEventListener("click", signOut);
  if (logoutBtnMob) logoutBtnMob.addEventListener("click", signOut);

  var footerSignup = document.getElementById("footer-signup");
  var footerLogin = document.getElementById("footer-login");
  if (footerSignup) footerSignup.addEventListener("click", function (e) { e.preventDefault(); openSignup(); });
  if (footerLogin) footerLogin.addEventListener("click", function (e) { e.preventDefault(); openLogin(); });

  // Open login/signup modal when landing with hash (e.g. from footer on other pages)
  function checkHashModal() {
    var hash = (window.location.hash || "").toLowerCase();
    if (hash === "#signup" && signupModal) { signupModal.classList.remove("hidden"); if (loginModal) loginModal.classList.add("hidden"); }
    else if (hash === "#login" && loginModal) { loginModal.classList.remove("hidden"); if (signupModal) signupModal.classList.add("hidden"); }
  }
  if (window.location.hash) checkHashModal();
  window.addEventListener("hashchange", checkHashModal);

  var loginClose = document.getElementById("login-close");
  var signupClose = document.getElementById("signup-close");
  if (loginClose) loginClose.addEventListener("click", closeLogin);
  if (signupClose) signupClose.addEventListener("click", closeSignup);

  var loginToSignup = document.getElementById("login-to-signup");
  var signupToLogin = document.getElementById("signup-to-login");
  if (loginToSignup) loginToSignup.addEventListener("click", openSignup);
  if (signupToLogin) signupToLogin.addEventListener("click", openLogin);

  if (loginModal) {
    loginModal.addEventListener("click", function (e) {
      if (e.target === loginModal) closeLogin();
    });
  }
  if (signupModal) {
    signupModal.addEventListener("click", function (e) {
      if (e.target === signupModal) closeSignup();
    });
  }

  // Password toggle (eyeball): show/hide password in Login and Signup
  document.querySelectorAll(".password-toggle").forEach(function (btn) {
    var targetId = btn.getAttribute("data-target");
    if (!targetId) return;
    var input = document.getElementById(targetId);
    var icon = btn.querySelector(".toggle-icon");
    if (!input || !icon) return;
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      var isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      icon.classList.toggle("fa-eye-slash", isPassword);
      icon.classList.toggle("fa-eye", !isPassword);
    });
  });

  // Signup Program level / Role button selection; remember choice for next time
  document.querySelectorAll(".signup-level").forEach(function (btn) {
    btn.addEventListener("click", function () {
      document.querySelectorAll(".signup-level").forEach(function (b) {
        b.classList.remove("border-[#6D28D9]", "bg-[#EBE0F8]");
        b.classList.add("border-[#D8B4FE]", "bg-white", "hover:border-[#6D28D9]");
      });
      btn.classList.add("border-[#6D28D9]", "bg-[#EBE0F8]");
      btn.classList.remove("border-[#D8B4FE]", "bg-white", "hover:border-[#6D28D9]");
      var val = btn.getAttribute("data-value");
      if (val)
        try {
          localStorage.setItem("gradspaths_signup_level", val);
        } catch (e) {}
    });
  });
  document.querySelectorAll(".signup-role").forEach(function (btn) {
    btn.addEventListener("click", function () {
      document.querySelectorAll(".signup-role").forEach(function (b) {
        b.classList.remove("border-[#6D28D9]", "bg-[#EBE0F8]");
        b.classList.add("border-[#D8B4FE]", "bg-white", "hover:border-[#6D28D9]");
      });
      btn.classList.add("border-[#6D28D9]", "bg-[#EBE0F8]");
      btn.classList.remove("border-[#D8B4FE]", "bg-white", "hover:border-[#6D28D9]");
      var val = btn.getAttribute("data-value");
      if (val)
        try {
          localStorage.setItem("gradspaths_signup_role", val);
        } catch (e) {}
      var isMentor = val === "mentor" || val === "Mentor";
      var stepsEl = document.getElementById("signup-steps");
      var subtitleEl = document.getElementById("signup-subtitle");
      if (subtitleEl) {
        if (isMentor) {
          if (stepsEl) {
            stepsEl.classList.remove("hidden");
            stepsEl.setAttribute("aria-hidden", "false");
          }
          subtitleEl.textContent = "Begin helping students achieve their dreams.";
        } else {
          if (stepsEl) {
            stepsEl.classList.add("hidden");
            stepsEl.setAttribute("aria-hidden", "true");
          }
          subtitleEl.textContent = "Tell us who you are so we can verify your school and keep this community secure.";
        }
      }
    });
  });

  // Contact Us: show form only when signed in, else show sign-in prompt
  function updateContactSection() {
    var signedIn = isSignedIn();
    var signinRequired = document.getElementById("contact-signin-required");
    var formWrapper = document.getElementById("contact-form-wrapper");
    if (signinRequired && formWrapper) {
      if (signedIn) {
        signinRequired.classList.add("hidden");
        formWrapper.classList.remove("hidden");
      } else {
        signinRequired.classList.remove("hidden");
        formWrapper.classList.add("hidden");
      }
    }
  }
  window.gradpathsUpdateContactSection = updateContactSection;
  updateContactSection();

  var contactOpenLogin = document.getElementById("contact-open-login");
  var contactOpenSignup = document.getElementById("contact-open-signup");
  if (contactOpenLogin) contactOpenLogin.addEventListener("click", openLogin);
  if (contactOpenSignup)
    contactOpenSignup.addEventListener("click", openSignup);

  // See Feedback and Reviews: require sign-in; if not signed in, open login modal instead of navigating
  var btnSeeFeedback = document.getElementById("btn-see-feedback");
  if (btnSeeFeedback) {
    btnSeeFeedback.addEventListener("click", function (e) {
      if (!isSignedIn()) {
        e.preventDefault();
        openLogin();
      }
    });
  }

  // Handle Select Your Mentor button clicks: if signed in, go to dashboard; else open login
  function handleSelectMentorClick(e) {
    e.preventDefault();
    if (isSignedIn()) {
      window.location.href = "https://gradspath-dashboard.vercel.app";
    } else {
      openLogin();
    }
  }
  window.gradpathsHandleSelectMentor = handleSelectMentorClick;

  if (window.gradpathsUpdateAuthButtons) window.gradpathsUpdateAuthButtons();
})();
