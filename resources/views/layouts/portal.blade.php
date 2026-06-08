@extends('layouts.app')

@section('css')
  @php($portalCssAsset = trim($__env->yieldContent('portal_css_asset')))
  @if ($portalCssAsset !== '')
    <link rel="stylesheet" href="{{ asset($portalCssAsset) }}?v={{ filemtime(public_path($portalCssAsset)) }}" />
  @endif

  @php($portalHeaderCssAsset = 'assets/css/portal-header.css')
  @php($portalHeaderCssPath = public_path($portalHeaderCssAsset))
  @if (is_file($portalHeaderCssPath))
    <link rel="stylesheet" href="{{ asset($portalHeaderCssAsset) }}?v={{ filemtime($portalHeaderCssPath) }}" />
  @endif

  @yield('portal_css')
@endsection

@section('content')
  <div class="app-shell">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    @yield('portal_sidebar')

    <main class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <button
            class="mobile-menu-toggle"
            id="mobileMenuToggle"
            type="button"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>

          @yield('portal_topbar_left')
        </div>

        <div class="topbar-right">
          <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme" title="Toggle theme">
            <span class="theme-toggle-icon theme-toggle-icon--sun" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </span>
            <span class="theme-toggle-icon theme-toggle-icon--moon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </span>
          </button>

          @yield('portal_topbar_right')
        </div>
      </header>

      @yield('portal_content')
    </main>
  </div>

  @yield('portal_after_shell')
@endsection

@section('js')
  @php($portalJsAsset = trim($__env->yieldContent('portal_js_asset')))
  @if ($portalJsAsset !== '')
    @php($portalJsPath = public_path($portalJsAsset))
    @php($portalJsVersion = is_file($portalJsPath) ? ('?v='.filemtime($portalJsPath)) : '')
    <script src="{{ asset($portalJsAsset) }}{{ $portalJsVersion }}"></script>
  @endif

  @yield('portal_js')

  <script>
    (function () {
      const sidebar = document.getElementById('sidebar');

      if (!sidebar) {
        return;
      }

      const normalizePath = (path) => {
        const normalized = `/${String(path || '').replace(/^\/+/, '')}`;
        return normalized.length > 1 ? normalized.replace(/\/+$/, '') : normalized;
      };

      const currentPath = normalizePath(window.location.pathname);
      const links = Array.from(sidebar.querySelectorAll('a.nav-item[href]'));

      const explicitTargets = [
        '/student/dashboard',
        '/mentor/dashboard',
        '/student/office-hours',
        '/mentor/office-hours',
        '/student/institutions',
        '/mentor/institutions',
        '/student/mentors',
        '/mentor/mentors',
        '/mentor/notes',
        '/student/feedback',
        '/feedback',
        '/student/bookings',
        '/mentor/bookings',
        '/mentor/availability',
        '/student/support',
        '/mentor/support',
        '/student/settings',
        '/mentor/settings',
      ];

      const matchingTarget = explicitTargets
        .filter((target) => currentPath === target || currentPath.startsWith(`${target}/`))
        .sort((first, second) => second.length - first.length)[0];

      const activeLink = links.find((link) => {
        const linkPath = normalizePath(new URL(link.href, window.location.origin).pathname);
        return linkPath === matchingTarget || linkPath === currentPath;
      });

      if (!activeLink) {
        return;
      }

      links.forEach((link) => {
        link.classList.remove('active');
        link.removeAttribute('aria-current');
      });

      activeLink.classList.add('active');
      activeLink.setAttribute('aria-current', 'page');
    })();

    (function () {
      const themeToggle = document.getElementById('themeToggle');

      if (!themeToggle) {
        return;
      }

      const iconMarkup = `
        <span class="theme-toggle-icon theme-toggle-icon--sun" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </span>
        <span class="theme-toggle-icon theme-toggle-icon--moon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
        </span>
      `;

      const normalizeThemeToggleIcon = () => {
        if (!themeToggle.querySelector('.theme-toggle-icon')) {
          themeToggle.innerHTML = iconMarkup;
        }

        themeToggle.setAttribute('aria-label', 'Toggle theme');
        themeToggle.setAttribute('title', 'Toggle theme');
      };

      normalizeThemeToggleIcon();
      themeToggle.addEventListener('click', () => {
        window.setTimeout(normalizeThemeToggleIcon, 0);
      });
    })();
  </script>
@endsection
