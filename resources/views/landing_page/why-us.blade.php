@php
  $authModal = $authModal ?? null;
  $submittedAuthContext = old('auth_context');
  $registerOldInput = old('role') || old('program_level') || old('institution') || old('name');
  $activeAuthModal = $authModal ?: ($errors->any() ? ($submittedAuthContext === 'signup' || $registerOldInput ? 'signup' : 'login') : null);
@endphp
<!doctype html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Why Grads Paths - Grads Paths</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('assets_landingPage/css/style.css') }}" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    rel="stylesheet" />
  <script>
    if (localStorage.getItem("theme") === "dark") {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            background: "var(--bg)",
            surface: "var(--surface)",
            primary: "var(--primary)",
            secondary: "var(--secondary)",
            accent: "var(--accent)",
            border: "var(--border)",
            text: { main: "var(--text-main)", muted: "var(--text-muted)" },
          },
          fontFamily: {
            sans: ["Plus Jakarta Sans", "system-ui", "sans-serif"],
            display: ["Plus Jakarta Sans", "system-ui", "sans-serif"],
          },
        },
      },
    };
  </script>
</head>

<body class="antialiased bg-[var(--bg)] text-[var(--text-main)] overflow-x-hidden transition-colors duration-300">
    <svg
      style="width: 0; height: 0; position: absolute"
      aria-hidden="true"
      focusable="false"
    >
      <defs>
        <linearGradient id="gpClockGrad" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stop-color="#5B30E5" class="gp-stop-1" />
          <stop offset="50%" stop-color="#8C5FE2" class="gp-stop-2" />
          <stop offset="100%" stop-color="#E57CE1" class="gp-stop-3" />
        </linearGradient>
      </defs>
    </svg>

    <header
      class="fixed inset-x-0 top-0 z-50 glass h-16 flex items-center transition-all duration-300"
      aria-label="Main navigation"
    >
      <div
        class="w-full max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between relative h-full"
      >
        <a
          href="{{ route('public.home') }}"
          class="flex items-center shrink-0 z-10"
          aria-label="Home - Grads Paths"
        >
          <img
            src="{{ asset('gradspaths_logo/Gradspaths_logo_transparent.png') }}"
            alt="Grads Paths"
            class="h-8 sm:h-14 w-auto max-w-[110px] sm:max-w-[180px] object-contain"
          />
        </a>

        <nav
          class="hidden md:flex items-center gap-6 sm:gap-8 font-bold text-sm absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2"
          aria-label="Primary"
        >
          <a
            href="{{ route('public.home') }}"
            class="nav-underline text-[var(--text-main)] hover:text-[var(--primary)] transition-colors whitespace-nowrap"
            >Home</a
          >
          <a
            href="{{ route('public.home') }}"
            class="nav-underline text-[var(--text-main)] hover:text-[var(--primary)] transition-colors whitespace-nowrap"
            >Find Mentors</a
          >
          <a
            href="{{ route('public.home') . '#how' }}"
            class="nav-underline text-[var(--text-main)] hover:text-[var(--primary)] transition-colors whitespace-nowrap"
            >How it Works</a
          >
          <a
            href="{{ route('public.home') . '#why-us' }}"
            class="nav-underline text-[var(--text-main)] hover:text-[var(--primary)] transition-colors whitespace-nowrap"
            >Why Us</a
          >
        </nav>

        <div class="flex items-center gap-1.5 sm:gap-3 shrink-0 z-10">
          <button
            id="theme-toggle"
            class="w-9 h-9 rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-surface flex items-center justify-center text-slate-600 dark:text-slate-400 hover:text-[var(--primary)] hover:border-[var(--primary)]/40 transition-all"
            aria-label="Toggle theme"
          >
            <svg
              class="w-5 h-5 dark:hidden"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
              />
            </svg>
            <svg
              class="w-5 h-5 hidden dark:block"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
              />
            </svg>
          </button>
          @auth
            <a
              href="{{ route('public.home') }}"
              class="inline-flex items-center justify-center gap-2 px-3 sm:px-[30px] py-1.5 sm:py-[10px] rounded-full min-w-0 sm:min-w-[112px] text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-[#8C5FE2] to-[#E57CE1] hover:opacity-90 transition-all dark:hover:opacity-80"
            >
              Dashboard
            </a>
            <form method="POST" action="{{ route('auth.logout') }}" class="inline-flex">
              @csrf
              <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 px-3 sm:px-7 py-1.5 sm:py-2 rounded-full min-w-0 sm:min-w-[112px] text-xs sm:text-sm font-bold text-[#3730A3] bg-white border-2 border-slate-200 hover:bg-slate-50 transition-all dark:bg-transparent dark:text-white dark:border-white/40 dark:hover:bg-white/10"
              >
                Logout
              </button>
            </form>
          @else
            <button
              id="btn-login"
              class="inline-flex items-center justify-center gap-2 px-3 sm:px-[30px] py-1.5 sm:py-[10px] rounded-full min-w-0 sm:min-w-[112px] text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-[#8C5FE2] to-[#E57CE1] hover:opacity-90 transition-all dark:hover:opacity-80"
            >
              Login
            </button>
            <button
              id="btn-signup"
              class="inline-flex items-center justify-center gap-2 px-3 sm:px-7 py-1.5 sm:py-2 rounded-full min-w-0 sm:min-w-[124px] text-xs sm:text-sm font-bold text-[#3730A3] bg-white border-2 border-slate-200 hover:bg-slate-50 transition-all dark:bg-transparent dark:text-white dark:border-white/40 dark:hover:bg-white/10"
            >
              Sign Up
            </button>
          @endauth
          <button
            id="menu-toggle"
            class="md:hidden w-10 h-10 flex items-center justify-center rounded-full border border-slate-300 dark:border-slate-600 text-black dark:text-white"
          >
            <i id="menu-icon" class="fa-solid fa-bars text-lg"></i>
          </button>
        </div>
      </div>
    </header>

    <div id="mobile-menu" class="md:hidden fixed top-16 inset-x-0 z-40 hidden">
      <div
        class="bg-white dark:bg-surface border-b border-slate-200 dark:border-slate-700 shadow-lg"
      >
        <nav class="flex flex-col py-2 font-semibold text-sm">
          <a
            href="{{ route('public.home') }}"
            class="px-6 py-3 text-black dark:text-white hover:bg-slate-100 dark:hover:bg-white/5 nav-underline"
            >Home</a
          >
          <a
            href="{{ route('public.home') }}"
            class="px-6 py-3 text-black dark:text-white hover:bg-slate-100 dark:hover:bg-white/5 nav-underline"
            >Find Mentors</a
          >
          <a
            href="{{ route('public.home') . '#how' }}"
            class="px-6 py-3 text-black dark:text-white hover:bg-slate-100 dark:hover:bg-white/5 nav-underline"
            >How it Works</a
          >
          <a
            href="{{ route('public.home') . '#why-us' }}"
            class="px-6 py-3 text-black dark:text-white hover:bg-slate-100 dark:hover:bg-white/5 nav-underline"
            >Why Us</a
          >
        </nav>
        <div
          class="flex gap-3 p-4 border-t border-slate-200 dark:border-slate-700"
        >
          @auth
            <a
              href="{{ route('public.home') }}"
              class="flex-1 py-[12px] rounded-full text-sm font-bold text-white bg-gradient-to-r from-[#8C5FE2] to-[#E57CE1] hover:opacity-90 transition-all text-center"
            >
              Dashboard
            </a>
            <form method="POST" action="{{ route('auth.logout') }}" class="flex-1">
              @csrf
              <button
                type="submit"
                class="w-full py-2.5 rounded-full text-sm font-bold text-[#3730A3] bg-white border-2 border-slate-200 hover:bg-slate-50 transition-all dark:bg-transparent dark:text-white dark:border-white/40 dark:hover:bg-white/10"
              >
                Logout
              </button>
            </form>
          @else
            <button
              id="btn-login-mob"
              class="flex-1 py-[12px] rounded-full text-sm font-bold text-white bg-gradient-to-r from-[#8C5FE2] to-[#E57CE1] hover:opacity-90 transition-all dark:hover:opacity-80"
            >
              Login
            </button>
            <button
              id="btn-signup-mob"
              class="flex-1 py-2.5 rounded-full text-sm font-bold text-[#3730A3] bg-white border-2 border-slate-200 hover:bg-slate-50 transition-all dark:bg-transparent dark:text-white dark:border-white/40 dark:hover:bg-white/10"
            >
              Sign Up
            </button>
          @endauth
        </div>
      </div>
    </div>

  <main class="pt-24 pb-20 px-4 sm:px-6 max-w-6xl mx-auto overflow-x-hidden">
    <!-- Why Grads Paths: two columns - left: heading + content + card, right: 3 boxes aligned with heading -->
    <section class="mb-16 sm:mb-20 flex flex-col items-center mx-auto" id="why-grads">
      <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-[#1D1440] dark:text-white mb-5 tracking-tight text-center">Why Grads Paths</h1>
      <p class="text-[#555273] dark:text-[var(--text-muted)] text-base md:text-[1.1rem] max-w-4xl mb-8 leading-relaxed text-center font-medium">
        Most students don't need a $300 to $500 per hour consulting package. They need the right mentor, the right plan, and honest feedback that reflects what programs and employers expect today. Grads Paths gives you verified graduate mentors and professionals with transparent pricing so you can get high-quality help without premium overhead.
      </p>

      <div class="flex flex-wrap justify-center gap-4 mb-12">
        <span class="px-5 py-2.5 rounded-full text-[15px] font-bold border-2 border-[#3E80EE] bg-white text-[#3E80EE] tracking-tight">Transparent pricing</span>
        <span class="px-5 py-2.5 rounded-full text-[15px] font-bold border-2 border-[#FE5673] bg-white text-[#FE5673] tracking-tight">Real mentorship</span>
        <span class="px-5 py-2.5 rounded-full text-[15px] font-bold border-2 border-[#9A6FFF] bg-white text-[#9A6FFF] tracking-tight">Next-step focused</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6 w-full max-w-4xl mx-auto">
        <div class="rounded-2xl border-2 border-blue-500 dark:border-blue-400 bg-[#FCFBFF] dark:bg-[var(--surface)] p-6 lg:p-8 flex flex-col items-center text-center">
          <h3 class="font-extrabold text-[#1D1440] dark:text-white text-lg mb-2">Pay-per-session</h3>
          <p class="text-[15px] leading-snug text-[#555273] dark:text-[var(--text-muted)] font-medium">No forced bundles or<br/>long programs.</p>
        </div>
        <div class="rounded-2xl border-2 border-red-500 dark:border-red-400 bg-[#FCFBFF] dark:bg-[var(--surface)] p-6 lg:p-8 flex flex-col items-center text-center">
          <h3 class="font-extrabold text-[#1D1440] dark:text-white text-lg mb-2">Current insight</h3>
          <p class="text-[15px] leading-snug text-[#555273] dark:text-[var(--text-muted)] font-medium">Mentors close to the<br/>process you're entering.</p>
        </div>
        <div class="rounded-2xl border-2 border-purple-500 dark:border-purple-400 bg-[#FCFBFF] dark:bg-[var(--surface)] p-6 lg:p-8 flex flex-col items-center text-center">
          <h3 class="font-extrabold text-[#1D1440] dark:text-white text-lg mb-2">Better value</h3>
          <p class="text-[15px] leading-snug text-[#555273] dark:text-[var(--text-muted)] font-medium">Premium-quality<br/>guidance without<br/>premium pricing.</p>
        </div>
      </div>

      <div class="rounded-2xl bg-[#FCFBFF] dark:bg-[var(--surface)] border-2 border-[#6D28D9] dark:border-[#9A6FFF] p-6 lg:p-8 mt-8 w-full max-w-4xl mx-auto text-left">
        <div class="flex items-center gap-3 mb-5">
          <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-bold text-[#E92E88] border border-[#F9A8D4] bg-white dark:bg-[var(--surface)] bg-opacity-80 dark:bg-opacity-100">Free</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1D1440] dark:text-white">15-minute consultation</h2>
        </div>
        <p class="text-[1.05rem] text-[#6D28D9] dark:text-white/90 mb-6 leading-relaxed font-medium max-w-3xl">
          Meet a mentor, confirm fit, and align goals before spending any money. We care about user success, so we start with clarity and fit—not pressure.
        </p>
        <ul class="space-y-3 mb-8 text-[1.05rem] text-[#6D28D9] dark:text-white/90 font-medium">
          <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-1"></i><span>Choose a mentor and test the fit</span></li>
          <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-1"></i><span>Identify your biggest lever for improvement</span></li>
          <li class="flex items-start gap-3"><i class="fa-solid fa-check mt-1"></i><span>Leave with a clear, realistic next step</span></li>
        </ul>
        <div class="flex flex-wrap gap-4">
          <a href="/" class="inline-flex items-center justify-center px-6 py-3 rounded-full text-[15px] font-bold text-white bg-gradient-to-r from-[#D9269A] to-[#6D28D9] hover:opacity-90 transition-opacity">Start free consult</a>
          <a href="/" class="inline-flex items-center justify-center px-6 py-3 rounded-full text-[15px] font-bold text-[#1D1440] dark:text-white bg-white dark:bg-[var(--surface)] border-2 border-[#E9D5FF] dark:border-[var(--border)] hover:bg-[#F3E8FF] dark:hover:bg-white/10 transition-colors">Browse mentors</a>
        </div>
      </div>
    </section>

    <!-- Pricing comparison -->
    <section class="mb-16 sm:mb-20" id="pricing">
      <div class="rounded-t-[2.5rem] bg-[#FAEFF8] dark:bg-[#FAEFF8]/10 bg-opacity-70 pt-12 pb-6 px-4">
        <h2 class="text-3xl sm:text-4xl md:text-[2.75rem] font-extrabold text-[#3730A3] dark:text-white tracking-tight text-center mb-4">Pricing comparison (per 60 minutes)</h2>
        <p class="text-[#6B7280] dark:text-[var(--text-muted)] font-medium text-sm md:text-[15px] max-w-4xl mx-auto mb-8 text-center leading-relaxed">
          Grads Paths uses your posted rates. "Market norm" and "high-end competitors" are rounded benchmark ranges based on public tutoring, admissions consulting, and interview prep pricing.
        </p>

        <!-- Legend: outside graph box -->
        <div class="flex flex-wrap gap-8 mb-2 sm:mb-4 text-[13px] justify-center font-bold text-[#3730A3] dark:text-white">
          <span class="flex items-center gap-2"><span class="w-5 h-3.5 rounded-sm bg-[#E46FA3] shrink-0"></span> Grads Paths</span>
          <span class="flex items-center gap-2"><span class="w-5 h-3.5 rounded-sm bg-[#8CC6DF] shrink-0"></span> Market norm</span>
          <span class="flex items-center gap-2"><span class="w-5 h-3.5 rounded-sm bg-[#CFCFD4] shrink-0"></span> High-end competitors</span>
        </div>
      </div>
      <div class="bg-white dark:bg-[var(--surface)] pt-10 px-6 pb-6 overflow-x-auto">
        <p class="text-sm font-bold text-[#3730A3] dark:text-white text-center mt-0 mb-12">$ per 60 minutes</p>
        <!-- Bar chart -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-10 sm:gap-8 min-w-[500px]" role="img" aria-label="Pricing comparison bar chart per 60 minutes">
          <!-- Tutoring: $70, $95, $180 -->
          <div class="flex flex-col items-center gap-1 flex-1 min-w-0">
            <div class="flex items-end justify-center gap-2 sm:gap-3 w-full h-[14rem]">
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Grads<br/>Paths $70</span>
                <div class="w-full rounded-t bg-[#E46FA3]" style="height: 12%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Market<br/>$95</span>
                <div class="w-full rounded-t bg-[#8CC6DF]" style="height: 18%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">High-end<br/>$180</span>
                <div class="w-full rounded-t bg-[#CFCFD4]" style="height: 30%;"></div>
              </div>
            </div>
            <div class="flex flex-col items-center mt-3 text-center">
              <span class="font-bold text-[#3730A3] dark:text-white text-[13px]">Tutoring</span>
              <span class="text-xs text-[#8B8AA1] dark:text-[var(--text-muted)] font-bold">$70</span>
            </div>
          </div>
          <!-- Program Insights: $65, $150, $400 -->
          <div class="flex flex-col items-center gap-1 flex-1 min-w-0">
            <div class="flex items-end justify-center gap-2 sm:gap-3 w-full h-[14rem]">
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Grads<br/>Paths $65</span>
                <div class="w-full rounded-t bg-[#E46FA3]" style="height: 11%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Market<br/>$150</span>
                <div class="w-full rounded-t bg-[#8CC6DF]" style="height: 25%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">High-end<br/>$400</span>
                <div class="w-full rounded-t bg-[#CFCFD4]" style="height: 55%;"></div>
              </div>
            </div>
            <div class="flex flex-col items-center mt-3 text-center">
              <span class="font-bold text-[#3730A3] dark:text-white text-[13px]">Program Insights</span>
              <span class="text-xs text-[#8B8AA1] dark:text-[var(--text-muted)] font-bold">$65</span>
            </div>
          </div>
          <!-- Interview Prep: $60, $295, $750 -->
          <div class="flex flex-col items-center gap-1 flex-1 min-w-0">
            <div class="flex items-end justify-center gap-2 sm:gap-3 w-full h-[14rem]">
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Grads<br/>Paths $60</span>
                <div class="w-full rounded-t bg-[#E46FA3]" style="height: 10%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Market<br/>$295</span>
                <div class="w-full rounded-t bg-[#8CC6DF]" style="height: 42%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">High-end<br/>$750</span>
                <div class="w-full rounded-t bg-[#CFCFD4]" style="height: 100%;"></div>
              </div>
            </div>
            <div class="flex flex-col items-center mt-3 text-center">
              <span class="font-bold text-[#3730A3] dark:text-white text-[13px]">Interview Prep</span>
              <span class="text-xs text-[#8B8AA1] dark:text-[var(--text-muted)] font-bold">$60</span>
            </div>
          </div>
          <!-- Application Review: $60, $260, $500 -->
          <div class="flex flex-col items-center gap-1 flex-1 min-w-0">
            <div class="flex items-end justify-center gap-2 sm:gap-3 w-full h-[14rem]">
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Grads<br/>Paths $60</span>
                <div class="w-full rounded-t bg-[#E46FA3]" style="height: 10%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Market<br/>$260</span>
                <div class="w-full rounded-t bg-[#8CC6DF]" style="height: 38%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">High-end<br/>$500</span>
                <div class="w-full rounded-t bg-[#CFCFD4]" style="height: 70%;"></div>
              </div>
            </div>
            <div class="flex flex-col items-center mt-3 text-center">
              <span class="font-bold text-[#3730A3] dark:text-white text-[13px]">Application Review</span>
              <span class="text-xs text-[#8B8AA1] dark:text-[var(--text-muted)] font-bold">$60</span>
            </div>
          </div>
          <!-- Gap Year Planning: $40, $125, $275 -->
          <div class="flex flex-col items-center gap-1 flex-1 min-w-0">
            <div class="flex items-end justify-center gap-2 sm:gap-3 w-full h-[14rem]">
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Grads<br/>Paths $40</span>
                <div class="w-full rounded-t bg-[#E46FA3]" style="height: 8%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">Market<br/>$125</span>
                <div class="w-full rounded-t bg-[#8CC6DF]" style="height: 20%;"></div>
              </div>
              <div class="flex flex-col items-center justify-end gap-1 flex-1 min-w-[2.5rem] h-full">
                <span class="inline-block rounded-md mb-2 text-[#3730A3] dark:text-white text-[10px] sm:text-[11px] font-bold text-center leading-tight">High-end<br/>$275</span>
                <div class="w-full rounded-t bg-[#CFCFD4]" style="height: 40%;"></div>
              </div>
            </div>
            <div class="flex flex-col items-center mt-3 text-center">
              <span class="font-bold text-[#3730A3] dark:text-white text-[13px]">Gap Year Planning</span>
              <span class="text-xs text-[#8B8AA1] dark:text-[var(--text-muted)] font-bold">$40</span>
            </div>
          </div>
        </div>
        <p class="text-[13px] font-medium text-[#8C5FE2] dark:text-[var(--text-muted)] mt-10 max-w-4xl mx-auto text-center leading-relaxed">
          In premium advising, the hourly price often reflects overhead and packaging. Grads Paths keeps it simple: pay per session, pick the exact service you need, and start with a free consultation to confirm fit.
        </p>
      </div>
    </section>

    <!-- Recommended number of meetings -->
    <section class="mb-16 sm:mb-20" id="recommended-meetings">
      <div class="text-center mb-10 mx-auto max-w-4xl px-4">
        <span class="text-sm font-bold text-[#8E5EDE] dark:text-white/80 mb-2 block tracking-tight">Optimize user experience</span>
        <h2 class="text-3xl sm:text-4xl md:text-[2.75rem] font-extrabold text-[#3730A3] dark:text-white tracking-tight mb-4 leading-tight">Recommended number of<br/>meetings</h2>
        <p class="text-[#8E5EDE] dark:text-[var(--text-muted)] font-medium text-sm md:text-[15px] mx-auto leading-relaxed">
          These recommendations are meant to help students choose the right level of support. Needs can vary based on goals, confidence, timing, and application complexity.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-4 max-w-7xl mx-auto">
        <!-- Card 1 -->
        <div class="bg-white dark:bg-[var(--surface)] rounded-[2rem] p-6 lg:p-8 flex flex-col items-center text-center shadow-sm border-2 border-blue-500 dark:border-blue-400">
          <div class="w-14 h-14 rounded-2xl bg-[#F5EFFF] dark:bg-white/10 flex items-center justify-center text-[#3730A3] dark:text-white mb-6">
            <i class="fa-solid fa-graduation-cap text-xl"></i>
          </div>
          <h3 class="font-bold text-[#3730A3] dark:text-white text-base mb-1">Program Insights</h3>
          <p class="text-[#8E5EDE] dark:text-[var(--text-muted)] text-xs font-semibold mb-3">Per school and program</p>
          <div class="text-[2rem] font-bold text-[#8E5EDE] dark:text-[var(--text-muted)] tracking-tighter mb-4 leading-none">1–3 meetings</div>
          <p class="text-[#6B7280] dark:text-[var(--text-muted)] text-[13px] leading-relaxed">Best for understanding fit, clarifying program differences, and getting direct perspective from someone currently in or closely connected to the program.</p>
        </div>

        <!-- Card 2 -->
        <div class="bg-white dark:bg-[var(--surface)] rounded-[2rem] p-6 lg:p-8 flex flex-col items-center text-center shadow-sm border-2 border-red-500 dark:border-red-400">
          <div class="w-14 h-14 rounded-2xl bg-[#F5EFFF] dark:bg-white/10 flex items-center justify-center text-[#3730A3] dark:text-white mb-6">
            <i class="fa-solid fa-briefcase text-xl"></i>
          </div>
          <h3 class="font-bold text-[#3730A3] dark:text-white text-base mb-1">Interview Prep</h3>
          <p class="text-[#8E5EDE] dark:text-[var(--text-muted)] text-xs font-semibold mb-3">Based on confidence and interview volume</p>
          <div class="text-[2rem] font-bold text-[#8E5EDE] dark:text-[var(--text-muted)] tracking-tighter mb-4 leading-none">1–5 meetings</div>
          <p class="text-[#6B7280] dark:text-[var(--text-muted)] text-[13px] leading-relaxed">A smaller number may be enough for confident applicants, while multiple meetings can help with repeated mock interviews, feedback, and refinement.</p>
        </div>

        <!-- Card 3 -->
        <div class="bg-white dark:bg-[var(--surface)] rounded-[2rem] p-6 lg:p-8 flex flex-col items-center text-center shadow-sm border-2 border-purple-500 dark:border-purple-400">
          <div class="w-14 h-14 rounded-2xl bg-[#F5EFFF] dark:bg-white/10 flex items-center justify-center text-[#3730A3] dark:text-white mb-6">
            <i class="fa-solid fa-file-signature text-xl"></i>
          </div>
          <h3 class="font-bold text-[#3730A3] dark:text-white text-base mb-1">Application Review</h3>
          <p class="text-[#8E5EDE] dark:text-[var(--text-muted)] text-xs font-semibold mb-3">Per school and program</p>
          <div class="text-[2rem] font-bold text-[#8E5EDE] dark:text-[var(--text-muted)] tracking-tighter mb-4 leading-none">4–7 meetings</div>
          <p class="text-[#6B7280] dark:text-[var(--text-muted)] text-[13px] leading-relaxed">This usually benefits from multiple touchpoints for brainstorming, draft review, revisions, and final polishing across the application process.</p>
        </div>

        <!-- Card 4 -->
        <div class="bg-white dark:bg-[var(--surface)] rounded-[2rem] p-6 lg:p-8 flex flex-col items-center text-center shadow-sm border-2 border-blue-500 dark:border-blue-400">
          <div class="w-14 h-14 rounded-2xl bg-[#F5EFFF] dark:bg-white/10 flex items-center justify-center text-[#3730A3] dark:text-white mb-6">
            <i class="fa-solid fa-earth-americas text-xl"></i>
          </div>
          <h3 class="font-bold text-[#3730A3] dark:text-white text-base mb-1">Gap Year Planning</h3>
          <p class="text-[#8E5EDE] dark:text-[var(--text-muted)] text-xs font-semibold mb-3">Depends on goals and planning depth</p>
          <div class="text-[2rem] font-bold text-[#8E5EDE] dark:text-[var(--text-muted)] tracking-tighter mb-4 leading-none">1–5 meetings</div>
          <p class="text-[#6B7280] dark:text-[var(--text-muted)] text-[13px] leading-relaxed">The right number depends on whether the student needs light direction or a more detailed roadmap for academics, work, service, travel, or applications.</p>
        </div>
      </div>

      <div class="px-4 mt-8 max-w-5xl mx-auto text-center">
        <p class="text-[#8E5EDE] dark:text-[var(--text-muted)] text-[13px] leading-relaxed font-medium">
          <span class="font-bold text-[#3730A3] dark:text-white">Tutoring note:</span> Tutoring can vary significantly depending on a student's starting point, target score, exam type, pacing, and subject-specific needs. Because of that, it is difficult to recommend a fixed number of meetings right now. As more students use the platform, we will be able to better gauge typical tutoring needs over time.
        </p>
      </div>
    </section>

    <!-- Why this is better value + What high quality means -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16 sm:mb-20">
      <div class="rounded-2xl border border-[var(--border)] bg-white dark:bg-[var(--surface)] p-6 sm:p-8">
        <h2 class="text-xl font-bold text-[var(--text-main)] mb-4">Why this is better value</h2>
        <p class="text-[var(--text-muted)] text-sm mb-4">
          High-end competitors can be strong, but the experience is often expensive to access and hard to "try" first. Grads Paths lowers the risk: you can meet a mentor for free, then only pay for the help you actually want. That means students can move faster with less wasted time and money.
        </p>
        <ul class="space-y-2 text-sm text-[var(--text-muted)]">
          <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Free 15-minute fit check before paying</li>
          <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Transparent hourly pricing, no bundles required</li>
          <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Mentors close to the current process, not outdated advice</li>
        </ul>
      </div>
      <div class="rounded-2xl border border-[var(--border)] bg-white dark:bg-[var(--surface)] p-6 sm:p-8">
        <h2 class="text-xl font-bold text-[var(--text-main)] mb-4">What "high quality" means here</h2>
        <p class="text-[var(--text-muted)] text-sm mb-4">
          We aim for sessions that feel structured and clear. You should walk away knowing exactly what to do next. Mentors don't just give opinions—they help you build a plan, tighten your materials, and practice your delivery.
        </p>
        <ul class="space-y-2 text-sm text-[var(--text-muted)]">
          <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Clear goals at the start of the session</li>
          <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Specific feedback, not vague reassurance</li>
          <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Actionable next steps after every meeting</li>
        </ul>
      </div>
    </section>

    <!-- The Grads Paths difference -->
    <section class="mb-16 sm:mb-20" id="difference">
      <h2 class="text-2xl sm:text-3xl font-bold text-[var(--text-main)] text-center mb-4">The Grads Paths difference</h2>
      <p class="text-[var(--text-muted)] text-center max-w-2xl mx-auto mb-10">
        The core issue is not "lack of information." It's lack of relevant, credible guidance—and students waste time and money trying to figure out what matters. We built Grads Paths to solve that.
      </p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="rounded-2xl border border-[var(--border)] bg-white dark:bg-[var(--surface)] p-6">
          <h3 class="flex items-center gap-2 text-lg font-bold text-[var(--text-main)] mb-3">
            <span class="w-1 h-8 rounded-full bg-[var(--primary)] shrink-0" aria-hidden="true"></span>
            The problem
          </h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">
            Students face opaque pricing, generic advice, and no way to confirm fit before paying. The result: wasted cycles and unclear next steps.
          </p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)]">
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> High cost to get meaningful support</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Generic guidance that ignores context</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Hard to confirm fit before paying</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Unclear next steps and wasted cycles</li>
          </ul>
        </div>
        <div class="rounded-2xl border border-[var(--border)] bg-white dark:bg-[var(--surface)] p-6">
          <h3 class="flex items-center gap-2 text-lg font-bold text-[var(--text-main)] mb-3">
            <span class="w-1 h-8 rounded-full bg-[var(--primary)] shrink-0" aria-hidden="true"></span>
            Our solution
          </h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">
            Verified mentors from real programs, transparent pay-per-session pricing, and a free consult so you can confirm fit and goals before spending.
          </p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)]">
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Mentorship that is current and practical</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Pay per session with clear pricing</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Free consult to confirm fit and goals</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Feedback that turns into a plan</li>
          </ul>
        </div>
        <div class="rounded-2xl border border-[var(--border)] bg-white dark:bg-[var(--surface)] p-6">
          <h3 class="flex items-center gap-2 text-lg font-bold text-[var(--text-main)] mb-3">
            <span class="w-1 h-8 rounded-full bg-[var(--primary)] shrink-0" aria-hidden="true"></span>
            Our approach
          </h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">
            We define the goal, diagnose what matters next, then practice and tighten. Every session ends with next steps you can execute.
          </p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)]">
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Define the goal and constraints</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Diagnose what actually matters next</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Practice, revise, and tighten delivery</li>
            <li class="flex items-start gap-2"><span class="w-1.5 h-1.5 rounded-full bg-[var(--secondary)] mt-1.5 shrink-0"></span> Leave with next steps you can execute</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Services built for real progress -->
    <section class="mb-16 sm:mb-20">
      <h2 class="text-2xl sm:text-3xl font-bold text-[var(--text-main)] text-center mb-4">Services built for real progress</h2>
      <p class="text-[var(--text-muted)] text-center max-w-3xl mx-auto mb-10 text-sm">
        Each service is designed to optimize your preparation, clarity, and strategy. We focus on what students can control: better decisions, stronger materials, and better performance through practice. Outcomes depend on many factors and are never guaranteed.
      </p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="rounded-2xl border-2 border-blue-500 dark:border-blue-400 bg-white dark:bg-[var(--surface)] p-6 shadow-sm">
          <h3 class="text-lg font-bold text-[var(--text-main)] mb-2">Tutoring</h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">High-performance study plans and priorities, with strategy and practice review that targets weak spots.</p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)] mb-4">
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> High-performance study plans and priorities</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Strategy + practice review that targets weak spots</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Support designed to optimize improvement over time</li>
          </ul>
          <p class="text-xs text-[var(--text-muted)] italic mb-4">Note: Exam score outcomes are not guaranteed and depend on many factors.</p>
          <span class="inline-block px-3 py-1.5 rounded-full text-sm font-semibold text-white bg-[#E92E88] border-2 border-[#E92E88]">$70 / 60 min</span>
        </div>
        <div class="rounded-2xl border-2 border-red-500 dark:border-red-400 bg-white dark:bg-[var(--surface)] p-6 shadow-sm">
          <h3 class="text-lg font-bold text-[var(--text-main)] mb-2">Program Insights</h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">Better program fit and stronger school-list decisions from people who've been there.</p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)] mb-4">
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Better program fit and stronger school-list decisions</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Clarity on day-to-day workload and culture</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Insight you can't get from generic guides</li>
          </ul>
          <span class="inline-block px-3 py-1.5 rounded-full text-sm font-semibold text-white bg-[#6D28D9] border-2 border-[#6D28D9]">$65 / 60 min</span>
        </div>
        <div class="rounded-2xl border-2 border-purple-500 dark:border-purple-400 bg-white dark:bg-[var(--surface)] p-6 shadow-sm">
          <h3 class="text-lg font-bold text-[var(--text-main)] mb-2">Interview Prep</h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">Mock interviews with targeted feedback and strategy to improve your overall approach.</p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)] mb-4">
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Mock interviews with targeted feedback</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Clearer, more confident answers</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Strategy to improve your overall approach</li>
          </ul>
          <p class="text-xs text-[var(--text-muted)] italic mb-4">Note: Hiring and admissions outcomes are not guaranteed.</p>
          <span class="inline-block px-3 py-1.5 rounded-full text-sm font-semibold text-white bg-[#6D28D9] border-2 border-[#6D28D9]">$60 / 60 min</span>
        </div>
        <div class="rounded-2xl border-2 border-blue-500 dark:border-blue-400 bg-white dark:bg-[var(--surface)] p-6 shadow-sm">
          <h3 class="text-lg font-bold text-[var(--text-main)] mb-2">Application Review</h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">Line-level edits and concrete improvements for stronger narrative structure and positioning.</p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)] mb-4">
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Line-level edits and concrete improvements</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Stronger narrative structure and positioning</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> A clear plan for the next revision</li>
          </ul>
          <span class="inline-block px-3 py-1.5 rounded-full text-sm font-semibold text-white bg-[#6D28D9] border-2 border-[#6D28D9]">$60 / 60 min</span>
        </div>
        <div class="rounded-2xl border-2 border-red-500 dark:border-red-400 bg-white dark:bg-[var(--surface)] p-6 shadow-sm">
          <h3 class="text-lg font-bold text-[var(--text-main)] mb-2">Gap Year Planning</h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">Roadmap and timeline tailored to your goals, with profile-building strategy and smarter next-cycle planning.</p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)] mb-4">
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Roadmap and timeline tailored to your goals</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Profile-building strategy with clear priorities</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Smarter next-cycle planning with less guesswork</li>
          </ul>
          <span class="inline-block px-3 py-1.5 rounded-full text-sm font-semibold text-white bg-[#E92E88] border-2 border-[#E92E88]">$40 / 60 min</span>
        </div>
        <div class="rounded-2xl border-2 border-purple-500 dark:border-purple-400 bg-white dark:bg-[var(--surface)] p-6 shadow-sm">
          <h3 class="text-lg font-bold text-[var(--text-main)] mb-2">Why we beat high-end competitors</h3>
          <p class="text-sm text-[var(--text-muted)] mb-4">Transparent pricing, quality mentorship, and no premium overhead. Start with a free consult and only pay for what you need.</p>
          <ul class="space-y-2 text-sm text-[var(--text-muted)] mb-5">
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Free 15-minute consultation to confirm fit first</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Pay-per-session pricing with no bundles required</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Mentors close to the current process for more relevant insight</li>
            <li class="flex items-start gap-2"><span class="w-2 h-2 rounded-full bg-[#6D28D9] mt-1.5 shrink-0"></span> Clear next steps after every session</li>
          </ul>
          <div class="flex flex-wrap gap-3">
            <a href="{{ route('public.home') . '#get-in-touch' }}"
              class="inline-flex items-center justify-center px-5 py-2.5 rounded-full text-sm font-bold text-white bg-[#E92E88] hover:opacity-90 transition-opacity">Start free consult</a>
            <a href="{{ route('public.home') }}"
              class="inline-flex items-center justify-center px-5 py-2.5 rounded-full text-sm font-bold text-[#6D28D9] dark:text-white/90 bg-white dark:bg-transparent border-2 border-[#6D28D9] dark:border-white/90 hover:bg-[#6D28D9]/5 dark:hover:bg-white/10 transition-colors">Browse mentors</a>
          </div>
        </div>
      </div>
    </section>

    <p class="text-center"><a href="{{ route('public.home') }}" class="font-semibold text-[var(--primary)] hover:underline">Back to Home</a></p>
  </main>

  <footer class="site-footer pt-16 pb-10 font-sans" aria-label="Footer">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      <div class="flex flex-col gap-4 mb-12">
        <a href="{{ route('public.home') }}" class="inline-flex items-center gap-2 no-underline">
          <i class="fa-solid fa-graduation-cap text-[#71a4f4] text-2xl" aria-hidden="true"></i>
          <span class="footer-brand">Grads Paths</span>
        </a>
        <p class="footer-tagline">
          Grads Paths is an education technology company dedicated to making high-quality mentorship and career planning
          accessible and affordable for college students. Our platform connects students with trusted graduate mentors
          and professionals from top programs.
        </p>
        <div class="footer-socials" aria-label="Social links">
          <a href="https://www.linkedin.com/company/gradspaths" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
          <a href="https://www.facebook.com/profile.php?id=1205297569326529" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="https://www.instagram.com/gradspaths/" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="https://www.youtube.com/@GradsPaths" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-10 sm:gap-8 mb-12">
        <div class="footer-col">
          <h3 class="footer-col-title">Explore</h3>
          <a href="{{ route('public.home') }}">Home</a>
          <a href="{{ route('public.home') . '#how' }}">How It Works</a>
          <a href="{{ route('public.home') . '#services' }}">Our Services</a>
          <a href="{{ route('public.why-us') }}">Why Grads Paths</a>
        </div>
        <div class="footer-col">
          <h3 class="footer-col-title">Services</h3>
          <a href="{{ route('public.home') }}">Find Mentors</a>
          <a href="{{ route('public.home') . '#meeting-types' }}">Meeting Types</a>
          <a href="{{ route('public.home') . '#programs-disciplines' }}">Programs Offered</a>
          <a href="{{ route('public.home') . '#programs-disciplines' }}">Professional Disciplines</a>
        </div>
        <div class="footer-col">
          <h3 class="footer-col-title">Connect</h3>
          <a href="{{ route('public.home') . '#get-in-touch' }}"><i class="fa-solid fa-envelope mr-1.5 text-sm opacity-90" aria-hidden="true"></i> Contact Us</a>
        </div>
      </div>
      <div class="footer-bottom pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-t border-[#3b4656]">
        <nav class="flex flex-wrap items-center gap-x-5 gap-y-1" aria-label="Footer legal and contact">
          <a href="{{ route('public.home') . '#get-in-touch' }}"><i class="fa-solid fa-envelope mr-1 text-sm opacity-90" aria-hidden="true"></i> Contact Us</a>
          <a href="{{ route('public.privacy') }}">Privacy Policy</a>
          <a href="{{ route('public.terms') }}">Terms of Service</a>
        </nav>
        <div class="text-left sm:text-right">
          <p>© <span id="copyright-year-wu">2026</span> Grads Paths. All rights reserved.</p>
          <p class="footer-credit mt-0.5">Created by students who've been on this path</p>
        </div>
      </div>
    </div>
    <script>document.getElementById('copyright-year-wu').textContent = new Date().getFullYear();</script>
  </footer>

  @include('auth::user.login')
  @include('auth::user.register')
  @include('layouts.partials.toasts')

  <script src="{{ asset('assets_landingPage/js/app.js') }}?v={{ filemtime(public_path('assets_landingPage/js/app.js')) }}"></script>
  <script src="{{ asset('assets_landingPage/js/script.js') }}"></script>
  <script>
    (function () {
      const activeModal = @json($activeAuthModal);
      const signupForm = document.getElementById("signup-form");
      const roleInput = document.getElementById("signup-role-input");
      const levelInput = document.getElementById("signup-program-level-input");
      const mentorTypeInput = document.getElementById("signup-mentor-type-input");
      const typeLabel = document.getElementById("signup-type-label");
      const typeOptions = document.getElementById("signup-type-options");

      function normalizeRole(value) {
        return value === "Mentor" ? "mentor" : value === "mentor" ? "mentor" : "student";
      }

      function normalizeLevel(value) {
        if (value === "Grad" || value === "grad" || value === "graduate") {
          return "graduate";
        }

        if (value === "Professional" || value === "professional") {
          return "professional";
        }

        return "undergrad";
      }

      function normalizeMentorType(value) {
        const level = normalizeLevel(value);

        return level === "professional" ? "professional" : "graduate";
      }

      function syncSelected(selector, activeValue) {
        document.querySelectorAll(selector).forEach(function (btn) {
          const isActive = btn.getAttribute("data-value") === activeValue;
          btn.classList.toggle("border-[#6D28D9]", isActive);
          btn.classList.toggle("bg-[#EBE0F8]", isActive);
          btn.classList.toggle("border-[#D8B4FE]", !isActive);
          btn.classList.toggle("bg-white", !isActive);
          btn.classList.toggle("hover:border-[#6D28D9]", !isActive);
          btn.dataset.selected = isActive ? "true" : "false";
        });
      }

      function findActiveValue(selector) {
        const activeButton = Array.from(document.querySelectorAll(selector)).find(function (btn) {
          return btn.dataset.selected === "true";
        });

        return activeButton?.getAttribute("data-value") || null;
      }

      function findVisibleActiveLevel() {
        const visibleActiveButton = visibleLevelButtons().find(function (btn) {
          return btn.classList.contains("border-[#6D28D9]");
        });

        return visibleActiveButton?.getAttribute("data-value") || null;
      }

      function visibleLevelButtons() {
        return Array.from(document.querySelectorAll(".signup-level"));
      }

      function activeRoleValue() {
        return normalizeRole(findActiveValue(".signup-role"));
      }

      function applyRoleTypeVisibility(roleValue) {
        const isMentor = roleValue === "mentor";

        if (typeLabel) {
          typeLabel.textContent = "Program level";
        }

        if (typeOptions) {
          typeOptions.classList.remove("grid-cols-1", "grid-cols-2");
          typeOptions.classList.add("grid-cols-3");
        }

        document.querySelectorAll(".signup-level").forEach(function (btn) {
          btn.classList.remove("hidden");
        });

        const visibleButtons = visibleLevelButtons();
        const activeButton = visibleButtons.find(function (btn) {
          return btn.classList.contains("border-[#6D28D9]");
        });

        if (!activeButton && visibleButtons[0]) {
          syncSelected(".signup-level", isMentor ? "graduate" : "undergrad");
        }
      }

      function syncHiddenInputs() {
        const roleValue = activeRoleValue();

        if (roleInput) {
          roleInput.value = roleValue;
        }

        if (levelInput) {
          levelInput.value = roleValue === "student" ? "undergrad" : "";
        }

        if (mentorTypeInput) {
          const activeLevel = normalizeMentorType(findVisibleActiveLevel());
          mentorTypeInput.value = roleValue === "mentor" ? activeLevel : "";
        }
      }

      document.querySelectorAll(".signup-role").forEach(function (btn) {
        btn.addEventListener("click", function () {
          const selectedRole = normalizeRole(btn.getAttribute("data-value"));
          syncSelected(".signup-role", selectedRole);
          syncSelected(".signup-level", selectedRole === "mentor" ? normalizeMentorType(findVisibleActiveLevel()) : "undergrad");

          if (roleInput) {
            roleInput.value = selectedRole;
          }

          applyRoleTypeVisibility(roleInput?.value || "student");
          syncHiddenInputs();
        });
      });

      document.querySelectorAll(".signup-level").forEach(function (btn) {
        btn.addEventListener("click", function () {
          const selectedLevel = normalizeLevel(btn.getAttribute("data-value"));
          syncSelected(".signup-level", selectedLevel);
          syncSelected(".signup-role", selectedLevel === "undergrad" ? "student" : "mentor");
          if (roleInput) {
            roleInput.value = selectedLevel === "undergrad" ? "student" : "mentor";
          }
          applyRoleTypeVisibility(roleInput?.value || "student");
          syncHiddenInputs();
        });
      });

      const oldRole = @json(old('role'));
      const oldMentorType = @json(old('mentor_type'));
      const savedRole = window.localStorage?.getItem("gradspaths_signup_role");
      const savedLevel = window.localStorage?.getItem("gradspaths_signup_level");
      const initialRole = normalizeRole(oldRole || savedRole || "mentor");
      const initialLevel = initialRole === "mentor"
        ? normalizeLevel(oldMentorType || savedLevel)
        : "undergrad";

      if (roleInput) {
        roleInput.value = initialRole;
      }

      if (levelInput) {
        levelInput.value = initialRole === "student" ? "undergrad" : "";
      }

      if (mentorTypeInput) {
        mentorTypeInput.value = initialRole === "mentor" ? normalizeMentorType(initialLevel) : "";
      }

      syncSelected(".signup-role", initialRole);
      syncSelected(".signup-level", initialLevel);
      applyRoleTypeVisibility(initialRole);
      syncHiddenInputs();

      signupForm?.addEventListener("submit", function () {
        syncHiddenInputs();
      });

      if (activeModal === "login") {
        document.getElementById("login-modal")?.classList.remove("hidden");
        document.getElementById("signup-modal")?.classList.add("hidden");
      } else if (activeModal === "signup") {
        document.getElementById("signup-modal")?.classList.remove("hidden");
        document.getElementById("login-modal")?.classList.add("hidden");
      }
    })();
  </script>
</body>

</html>
