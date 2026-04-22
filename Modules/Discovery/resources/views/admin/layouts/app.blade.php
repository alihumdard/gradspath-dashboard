<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('admin_title', 'Admin Dashboard') - Grads Paths</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="{{ asset('assets/css/demo12.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @yield('admin_head')
  </head>

  <body>
    <div class="app">
      <section class="dashboard" id="dashboard">
        <div class="sidebar-overlay" id="adminSidebarOverlay"></div>
        <aside class="sidebar">
          <div class="brand">
            <div class="brand-icon">GP</div>
            <div>
              <h2>Grads Paths</h2>
              <p>Admin Dashboard</p>
            </div>
          </div>

          <nav class="nav">
            <a class="nav-link{{ request()->routeIs('admin.overview') ? ' active' : '' }}" href="{{ route('admin.overview') }}">Overview</a>
            <a class="nav-link{{ request()->routeIs('admin.users') ? ' active' : '' }}" href="{{ route('admin.users') }}">Users</a>
            <a class="nav-link{{ request()->routeIs('admin.mentors') ? ' active' : '' }}" href="{{ route('admin.mentors') }}">Mentors</a>
            <a class="nav-link{{ request()->routeIs('admin.services') ? ' active' : '' }}" href="{{ route('admin.services') }}">Services</a>
            <a class="nav-link{{ request()->routeIs('admin.revenue') ? ' active' : '' }}" href="{{ route('admin.revenue') }}">Revenue</a>
            <a class="nav-link{{ request()->routeIs('admin.rankings') ? ' active' : '' }}" href="{{ route('admin.rankings') }}">Rankings</a>
            <a class="nav-link{{ request()->routeIs('admin.manual-actions') ? ' active' : '' }}" href="{{ route('admin.manual-actions') }}">Manual Actions</a>
          </nav>

          <div class="sidebar-bottom">
            <button class="ghost-btn" id="reloadBtn" type="button">Reload</button>
            <form method="POST" action="{{ route('auth.logout') }}">
              @csrf
              <button class="ghost-btn" id="signOutBtn" type="submit">Sign out</button>
            </form>
          </div>
        </aside>

        <main class="main">
          @unless(View::hasSection('hide_admin_topbar'))
            <header class="topbar">
              <div class="topbar-row">
                <div class="topbar-left">
                  <button class="mobile-menu-toggle" id="adminMenuToggle" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="3" y1="12" x2="21" y2="12"></line>
                      <line x1="3" y1="6" x2="21" y2="6"></line>
                      <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                  </button>
                  <h1>@yield('admin_heading', 'Admin Dashboard')</h1>
                </div>
                <div class="status-pill">Live</div>
              </div>
              <p class="topbar-desc">@yield('admin_subtitle', 'Track users, mentors, service mix, meeting sizes, schools, and revenue.')</p>
            </header>
          @endunless

          @yield('admin_content')
        </main>
      </section>
    </div>

    @yield('admin_page_data')
    <script src="{{ asset('assets/js/demo12.js') }}"></script>
  </body>
</html>
