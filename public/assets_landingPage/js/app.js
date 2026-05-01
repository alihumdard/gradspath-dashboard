function switchTab(tabName) {
    const tabs = document.querySelectorAll(".tab-btn");
    const inactiveClass =
        " bg-transparent text-[var(--text-muted)] hover:text-[var(--text-main)]";
    const activeClass = " bg-[var(--primary)] text-white shadow-md";

    tabs.forEach((tab) => {
        tab.className =
            "tab-btn px-4 py-2.5 rounded-full text-[11px] sm:text-[12px] leading-none whitespace-nowrap font-bold tracking-widest uppercase transition-all duration-200 focus:outline-none" +
            inactiveClass;
        tab.setAttribute("aria-selected", "false");
    });

    const activeTab = document.getElementById(`tab-${tabName}`);
    activeTab.className =
        "tab-btn px-4 py-2.5 rounded-full text-[11px] sm:text-[12px] leading-none whitespace-nowrap font-bold tracking-widest uppercase transition-all duration-200 focus:outline-none" +
        activeClass;
    activeTab.setAttribute("aria-selected", "true");

    document.querySelectorAll(".program-panel").forEach((panel) => {
        panel.classList.add("hidden");
        panel.classList.remove("flex");
    });

    const activePanel = document.getElementById(`panel-${tabName}`);
    activePanel.classList.remove("hidden");
    activePanel.classList.add("flex");

    // When switching to Therapy, show first therapy sub-tab (CMHC)
    if (tabName === "psych") {
        const therapyTabs = document.querySelectorAll(".therapy-tab-btn");
        const therapyPanels = document.querySelectorAll(".therapy-panel");
        therapyTabs.forEach((t) => {
            t.classList.remove("bg-[var(--primary)]", "text-white");
            t.classList.add("bg-transparent", "text-[var(--text-muted)]");
            t.setAttribute("aria-selected", "false");
        });
        therapyPanels.forEach((p) => p.classList.add("hidden"));
        const firstTab = document.getElementById("therapy-tab-cmhc");
        const firstPanel = document.getElementById("therapy-panel-cmhc");
        if (firstTab) {
            firstTab.classList.remove(
                "bg-transparent",
                "text-[var(--text-muted)]",
            );
            firstTab.classList.add("bg-[var(--primary)]", "text-white");
            firstTab.setAttribute("aria-selected", "true");
        }
        if (firstPanel) firstPanel.classList.remove("hidden");
    }
}

function switchTherapyTab(tabName) {
    const therapyTabs = document.querySelectorAll(".therapy-tab-btn");
    const therapyPanels = document.querySelectorAll(".therapy-panel");
    therapyTabs.forEach((tab) => {
        tab.classList.remove("bg-[var(--primary)]", "text-white");
        tab.classList.add("bg-transparent", "text-[var(--text-muted)]");
        tab.setAttribute("aria-selected", "false");
    });
    therapyPanels.forEach((panel) => panel.classList.add("hidden"));
    const activeTab = document.getElementById(`therapy-tab-${tabName}`);
    const activePanel = document.getElementById(`therapy-panel-${tabName}`);
    if (activeTab) {
        activeTab.classList.remove(
            "bg-transparent",
            "text-[var(--text-muted)]",
        );
        activeTab.classList.add("bg-[var(--primary)]", "text-white");
        activeTab.setAttribute("aria-selected", "true");
    }
    if (activePanel) activePanel.classList.remove("hidden");
}

function switchProgramsChart(tabName) {
    const tabs = document.querySelectorAll(".programs-chart-tab");
    const views = document.querySelectorAll(".programs-chart-view");
    const subtitleEl = document.getElementById("programs-chart-subtitle");
    const subtitles = {
        general: "All Programs + Professional Tracks",
        graduate: "Graduate Programs",
        professionals: "Professionals",
        colleges: "Colleges",
    };
    tabs.forEach((tab) => {
        tab.classList.remove(
            "bg-white",
            "dark:bg-white/10",
            "text-[var(--primary)]",
            "dark:text-white",
            "border-2",
            "border-[var(--primary)]",
        );
        tab.classList.add(
            "bg-transparent",
            "text-[var(--text-main)]",
            "dark:text-[var(--text-muted)]",
        );
        tab.setAttribute("aria-selected", "false");
    });
    views.forEach((v) => v.classList.add("hidden"));
    const activeTab = document.getElementById(`chart-tab-${tabName}`);
    const activeView = document.getElementById(`chart-view-${tabName}`);
    if (activeTab) {
        activeTab.classList.remove(
            "bg-transparent",
            "text-[var(--text-main)]",
            "dark:text-[var(--text-muted)]",
        );
        activeTab.classList.add(
            "bg-white",
            "dark:bg-white/10",
            "text-[var(--primary)]",
            "dark:text-white",
            "border-2",
            "border-[var(--primary)]",
        );
        activeTab.setAttribute("aria-selected", "true");
    }
    if (activeView) activeView.classList.remove("hidden");
    if (subtitleEl && subtitles[tabName])
        subtitleEl.textContent = subtitles[tabName];
}

function switchProgTab(tabName) {
    const tabs = document.querySelectorAll(".prog-tab-btn");
    const inactiveClass =
        " bg-transparent text-[var(--text-muted)] hover:text-[var(--text-main)]";
    const activeClass = " bg-[var(--primary)] text-white shadow-sm";

    tabs.forEach((tab) => {
        tab.className =
            "prog-tab-btn px-4 py-2.5 rounded-full text-[11px] sm:text-[12px] leading-none whitespace-nowrap font-bold tracking-widest uppercase transition-all duration-200 focus:outline-none" +
            inactiveClass;
        tab.setAttribute("aria-selected", "false");
    });

    const activeTab = document.getElementById(`prog-tab-${tabName}`);
    if (activeTab) {
        activeTab.className =
            "prog-tab-btn px-4 py-2.5 rounded-full text-[11px] sm:text-[12px] leading-none whitespace-nowrap font-bold tracking-widest uppercase transition-all duration-200 focus:outline-none" +
            activeClass;
        activeTab.setAttribute("aria-selected", "true");
    }

    document.querySelectorAll(".prog-panel").forEach((panel) => {
        panel.classList.add("hidden");
    });
    const activePanel = document.getElementById(`prog-panel-${tabName}`);
    if (activePanel) {
        activePanel.classList.remove("hidden");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const themeToggleBtn = document.getElementById("theme-toggle");
    const htmlElement = document.documentElement;

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", () => {
            if (htmlElement.classList.contains("dark")) {
                htmlElement.classList.remove("dark");
                localStorage.setItem("theme", "light");
            } else {
                htmlElement.classList.add("dark");
                localStorage.setItem("theme", "dark");
            }
        });
    }

    // Remember Me: restore saved login email and checkbox
    const loginEmail = document.getElementById("login-email");
    const loginRemember = document.getElementById("login-remember");
    if (loginEmail && localStorage.getItem("gradspaths_remember_email")) {
        loginEmail.value = localStorage.getItem("gradspaths_remember_email");
    }
    if (
        loginRemember &&
        localStorage.getItem("gradspaths_remember") === "true"
    ) {
        loginRemember.checked = true;
    }

    // Login form: save email if Remember Me checked; set signed-in for contact/ticket
    const loginForm = document.getElementById("login-form");
    if (loginForm) {
        loginForm.addEventListener("submit", () => {
            if (loginRemember && loginRemember.checked && loginEmail) {
                localStorage.setItem("gradspaths_remember", "true");
                localStorage.setItem(
                    "gradspaths_remember_email",
                    loginEmail.value,
                );
            } else {
                localStorage.removeItem("gradspaths_remember");
                localStorage.removeItem("gradspaths_remember_email");
            }
            localStorage.setItem("gradpaths_signed_in", "1");
            if (loginEmail) {
                document.cookie = `gradspaths_signed_in=1; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Strict`; // 30 days
                document.cookie = `gradspaths_user_email=${encodeURIComponent(loginEmail.value)}; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Strict`;
            }
        });
    }

    // Signup form: keep role and level selection only; text fields should not persist across refreshes
    const signupForm = document.getElementById("signup-form");
    const signupFullname = document.getElementById("signup-fullname");
    const signupEmail = document.getElementById("signup-email");
    const signupInstitution = document.getElementById("signup-institution");
    localStorage.removeItem("gradspaths_signup_fullname");
    localStorage.removeItem("gradspaths_signup_email");
    localStorage.removeItem("gradspaths_signup_institution");

    const savedLevelRaw = localStorage.getItem("gradspaths_signup_level");
    const savedRoleRaw = localStorage.getItem("gradspaths_signup_role");
    const savedLevel =
        savedLevelRaw === "Grad" || savedLevelRaw === "grad"
            ? "graduate"
            : savedLevelRaw === "Professional"
              ? "professional"
              : savedLevelRaw;
    const savedRole =
        savedRoleRaw === "Mentor"
            ? "mentor"
            : savedRoleRaw === "Student"
              ? "student"
              : savedRoleRaw;

    if (savedLevel) {
        const levelBtn = document.querySelector(
            '.signup-level[data-value="' + savedLevel + '"]',
        );
        if (levelBtn) levelBtn.click();
    }
    if (savedRole) {
        const roleBtn = document.querySelector(
            '.signup-role[data-value="' + savedRole + '"]',
        );
        if (roleBtn) roleBtn.click();
    }
    if (signupForm) {
        signupForm.addEventListener("submit", () => {
            localStorage.setItem("gradpaths_signed_in", "1");
            if (signupEmail) {
                document.cookie = `gradspaths_signed_in=1; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Strict`;
                document.cookie = `gradspaths_user_email=${encodeURIComponent(signupEmail.value)}; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Strict`;
            }
        });
    }

    // Institution autocomplete functionality
    const institutionInput = document.getElementById("signup-institution");
    const institutionIdInput = document.getElementById("signup-institution-id");
    const suggestionsContainer = document.getElementById(
        "institution-suggestions",
    );
    let latestInstitutionQuery = "";

    function escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = value ?? "";
        return div.innerHTML;
    }

    async function searchUniversities(query) {
        const response = await fetch(
            "/universities/search?q=" + encodeURIComponent(query),
            {
                headers: {
                    Accept: "application/json",
                },
            },
        );

        if (!response.ok) {
            return [];
        }

        return response.json();
    }

    // Filter and display suggestions
    async function showSuggestions(query) {
        if (!query || query.length < 2) {
            suggestionsContainer.classList.add("hidden");
            return;
        }

        latestInstitutionQuery = query;

        let filtered = [];
        try {
            filtered = await searchUniversities(query);
        } catch (error) {
            console.log("Could not fetch universities list:", error);
        }

        if (latestInstitutionQuery !== query) {
            return;
        }

        if (filtered.length === 0) {
            suggestionsContainer.innerHTML =
                '<div class="p-3 text-sm text-[#6D28D9]">No universities found</div>';
            suggestionsContainer.classList.remove("hidden");
            return;
        }

        suggestionsContainer.innerHTML = filtered
            .map(
                (uni) => `
        <div class="p-3 cursor-pointer hover:bg-[#f5efff] border-b border-[#E9D5FF] last:border-b-0 text-sm text-[#6D28D9]" data-university-id="${escapeHtml(uni.id)}">
          <div class="font-semibold">${escapeHtml(uni.name)}</div>
          <div class="text-xs text-[#9A7FD9]">${escapeHtml(uni.country)}${uni.state_province ? ", " + escapeHtml(uni.state_province) : ""}</div>
        </div>
      `,
            )
            .join("");

        suggestionsContainer.classList.remove("hidden");

        // Add click handlers to suggestions
        document
            .querySelectorAll("#institution-suggestions > div")
            .forEach((el, index) => {
                el.addEventListener("click", () => {
                    institutionInput.value = filtered[index].name;
                    if (institutionIdInput) {
                        institutionIdInput.value = filtered[index].id ?? "";
                    }
                    suggestionsContainer.classList.add("hidden");
                });
            });
    }

    // Input event listener
    if (institutionInput) {
        institutionInput.addEventListener("input", (e) => {
            if (institutionIdInput) {
                institutionIdInput.value = "";
            }
            showSuggestions(e.target.value);
        });

        // Hide suggestions when clicking outside
        document.addEventListener("click", (e) => {
            if (
                e.target !== institutionInput &&
                !suggestionsContainer.contains(e.target)
            ) {
                suggestionsContainer.classList.add("hidden");
            }
        });

        // Show suggestions on focus if there's a value
        institutionInput.addEventListener("focus", () => {
            if (institutionInput.value.length >= 2) {
                showSuggestions(institutionInput.value);
            }
        });
    }
});
