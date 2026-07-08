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
  <title>How It Works - Grads Paths</title>
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
            href="{{ route('public.how-it-works') }}"
            class="nav-underline text-[var(--primary)] transition-colors whitespace-nowrap"
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
            href="{{ route('public.how-it-works') }}"
            class="px-6 py-3 text-[var(--primary)] nav-underline"
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

  <main class="pt-24 pb-20 px-4 sm:px-6 max-w-4xl mx-auto overflow-x-hidden">
    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-black dark:text-white mb-2">How It Works</h1>
    <p class="text-slate-600 dark:text-slate-400 mb-14">A simple six-step process from curious about grad school to
      getting guidance from someone who's already there.</p>

    <div class="space-y-10">
      <section class="flex gap-6 items-start">
        <div
          class="flex-shrink-0 w-12 h-12 rounded-xl bg-[var(--primary)]/10 dark:bg-[var(--primary)]/20 border border-[var(--primary)]/20 flex items-center justify-center">
          <span class="text-lg font-bold text-[var(--primary)]">1</span>
        </div>
        <div>
          <h2 class="text-xl font-bold text-black dark:text-white mb-2 tracking-wide">Create your account</h2>
          <p class="text-slate-600 dark:text-slate-400">Sign up and tell us your program level and role (student or
            mentor). We verify so the community stays real.</p>
        </div>
      </section>

      <section class="flex gap-6 items-start">
        <div
          class="flex-shrink-0 w-12 h-12 rounded-xl bg-[var(--primary)]/10 dark:bg-[var(--primary)]/20 border border-[var(--primary)]/20 flex items-center justify-center">
          <span class="text-lg font-bold text-[var(--primary)]">2</span>
        </div>
        <div>
          <h2 class="text-xl font-bold text-black dark:text-white mb-2 tracking-wide">Browse mentors</h2>
          <p class="text-slate-600 dark:text-slate-400">Use the dashboard to filter by top-tier US universities and
            connect with mentors who match your goals.</p>
        </div>
      </section>

      <section class="flex gap-6 items-start">
        <div
          class="flex-shrink-0 w-12 h-12 rounded-xl bg-[var(--primary)]/10 dark:bg-[var(--primary)]/20 border border-[var(--primary)]/20 flex items-center justify-center">
          <span class="text-lg font-bold text-[var(--primary)]">3</span>
        </div>
        <div>
          <h2 class="text-xl font-bold text-black dark:text-white mb-2 tracking-wide">Book your FREE 15-minute consultation</h2>
          <p class="text-slate-600 dark:text-slate-400">Schedule a free intro call. Ask questions and confirm the mentor
            fit before committing to paid sessions.</p>
        </div>
      </section>

      <section class="flex gap-6 items-start">
        <div
          class="flex-shrink-0 w-12 h-12 rounded-xl bg-[var(--primary)]/10 dark:bg-[var(--primary)]/20 border border-[var(--primary)]/20 flex items-center justify-center">
          <span class="text-lg font-bold text-[var(--primary)]">4</span>
        </div>
        <div>
          <h2 class="text-xl font-bold text-black dark:text-white mb-2 tracking-wide">Meet your mentor</h2>
          <p class="text-slate-600 dark:text-slate-400">Connect via video. Get personalized guidance, next steps, and
            clarity on your path.</p>
        </div>
      </section>

      <section class="flex gap-6 items-start">
        <div
          class="flex-shrink-0 w-12 h-12 rounded-xl bg-[var(--primary)]/10 dark:bg-[var(--primary)]/20 border border-[var(--primary)]/20 flex items-center justify-center">
          <span class="text-lg font-bold text-[var(--primary)]">5</span>
        </div>
        <div>
          <h2 class="text-xl font-bold text-black dark:text-white mb-2 tracking-wide">Choose paid sessions if you want</h2>
          <p class="text-slate-600 dark:text-slate-400">Continue with tutoring, program insights, interview prep, or
            application review at clear, upfront prices.</p>
        </div>
      </section>

      <section class="flex gap-6 items-start">
        <div
          class="flex-shrink-0 w-12 h-12 rounded-xl bg-[var(--primary)]/10 dark:bg-[var(--primary)]/20 border border-[var(--primary)]/20 flex items-center justify-center">
          <span class="text-lg font-bold text-[var(--primary)]">6</span>
        </div>
        <div>
          <h2 class="text-xl font-bold text-black dark:text-white mb-2 tracking-wide">Get ongoing support</h2>
          <p class="text-slate-600 dark:text-slate-400">Return anytime for more sessions. Build a relationship with a
            mentor who knows your goals.</p>
        </div>
      </section>
    </div>

    <section class="mt-16 pt-16 border-t border-slate-200 dark:border-slate-700" id="services-we-offer">
      <h2 class="text-2xl font-bold text-black dark:text-white mb-4 tracking-wide">Services we offer</h2>
      <p class="text-slate-600 dark:text-slate-400 mb-6">We currently offer the following. All start with a free
        15-minute consultation.</p>
      <ul class="space-y-4">
        <li class="flex items-start gap-3">
          <i class="fa-solid fa-handshake text-[var(--primary)] mt-0.5"></i>
          <div>
            <span class="font-bold text-black dark:text-white">Free Consultation</span> — 10 min, free. Meet a mentor
            and align goals.
          </div>
        </li>
        <li class="flex items-start gap-3">
          <i class="fa-solid fa-chalkboard-user text-[var(--primary)] mt-0.5"></i>
          <div>
            <span class="font-bold text-black dark:text-white">Tutoring</span> — 60 min, $70. Conceptual guidance and
            academic support.
          </div>
        </li>
        <li class="flex items-start gap-3">
          <i class="fa-solid fa-graduation-cap text-[var(--primary)] mt-0.5"></i>
          <div>
            <span class="font-bold text-black dark:text-white">Program Insights</span> — 60 min, $55. Culture,
            expectations, and admission nuances from insiders.
          </div>
        </li>
        <li class="flex items-start gap-3">
          <i class="fa-solid fa-briefcase text-[var(--primary)] mt-0.5"></i>
          <div>
            <span class="font-bold text-black dark:text-white">Interview Prep</span> — 60 min, $50. Mock interviews and
            resume refinement.
          </div>
        </li>
        <li class="flex items-start gap-3">
          <i class="fa-solid fa-file-lines text-[var(--primary)] mt-0.5"></i>
          <div>
            <span class="font-bold text-black dark:text-white">Application Review</span> — 60 min, $60. Personal
            statements and application polish.
          </div>
        </li>
        <li class="flex items-start gap-3">
          <i class="fa-solid fa-calendar-check text-[var(--primary)] mt-0.5"></i>
          <div>
            <span class="font-bold text-black dark:text-white">Gap Year Planning</span> — 60 min, $40. Research,
            service, or certifications.
          </div>
        </li>
      </ul>
      <p class="mt-6">
        <a href="{{ route('public.home') . '#services' }}" class="font-semibold text-[var(--primary)] hover:underline">View services on
          homepage</a>
      </p>
    </section>

    <p class="mt-12"><a href="{{ route('public.home') }}" class="font-semibold text-[var(--primary)] hover:underline">Back to Home</a>
    </p>
  </main>

  <footer class="site-footer pt-16 pb-10 font-sans" aria-label="Footer">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      <div class="flex flex-col gap-4 mb-12">
        <a href="{{ route('public.home') }}" class="inline-flex items-center gap-2 no-underline">
          <i class="fa-solid fa-graduation-cap text-[#71a4f4] text-2xl" aria-hidden="true"></i>
          <span class="footer-brand">Grads Paths</span>
        </a>
        <p class="footer-tagline">
          Grads Paths is an education technology company dedicated to making high-quality mentorship and career planning accessible and affordable for college students. Our platform connects students with trusted graduate mentors and professionals from top programs.
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
          <a href="{{ route('public.how-it-works') }}">How It Works</a>
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
          <p>© <span id="copyright-year-hiw">2026</span> Grads Paths. All rights reserved.</p>
          <p class="footer-credit mt-0.5">Created by students who've been on this path</p>
        </div>
      </div>
    </div>
    <script>document.getElementById('copyright-year-hiw').textContent = new Date().getFullYear();</script>
  </footer>

  @include('auth::user.login')
  @include('auth::user.register')
  @include('layouts.partials.toasts')

  <script src="{{ asset('assets_landingPage/js/app.js') }}?v={{ filemtime(public_path('assets_landingPage/js/app.js')) }}"></script>
  <script src="{{ asset('assets_landingPage/js/script.js') }}"></script>
  <script>
    (function () {
      const activeModal = @json($activeAuthModal);

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
