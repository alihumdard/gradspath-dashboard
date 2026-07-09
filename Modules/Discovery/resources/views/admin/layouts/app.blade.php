<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('admin_title', 'Admin Dashboard') - Grads Paths</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="{{ asset('assets/css/admin-auth.css') }}?v={{ filemtime(public_path('assets/css/admin-auth.css')) }}" />
    <style>
      .admin-dropdown-item:hover {
        background: rgba(255, 255, 255, 0.05);
      }
      .admin-dropdown-btn:hover {
        background: rgba(255, 255, 255, 0.08) !important;
      }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @yield('admin_head')
  </head>

  <body>
    @php
      $adminSupportOpenCount = class_exists(\Modules\Support\app\Models\SupportTicket::class)
        ? \Modules\Support\app\Models\SupportTicket::query()
            ->whereIn('status', ['open', 'pending', 'in_progress', 'more_information_required'])
            ->count()
        : 0;
    @endphp
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
            <a class="nav-link{{ request()->routeIs('admin.payouts*') ? ' active' : '' }}" href="{{ route('admin.payouts') }}">Payouts</a>
            <a class="nav-link{{ request()->routeIs('admin.rankings') ? ' active' : '' }}" href="{{ route('admin.rankings') }}">Rankings</a>
            <a class="nav-link nav-link--with-badge{{ request()->routeIs('admin.support.*') ? ' active' : '' }}" href="{{ route('admin.support.tickets.index') }}">
              <span>Support</span>
              @if ($adminSupportOpenCount > 0)
                <span class="nav-badge">{{ $adminSupportOpenCount > 99 ? '99+' : $adminSupportOpenCount }}</span>
              @endif
            </a>
            <a class="nav-link{{ request()->routeIs('admin.manual-actions') ? ' active' : '' }}" href="{{ route('admin.manual-actions') }}">Manual Actions</a>
          </nav>
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
                <div style="display: flex; align-items: center; gap: 16px;">
                  @php
                    $adminUser = Auth::user();
                    $adminNameParts = collect(preg_split('/\s+/', trim($adminUser->name ?? '')) ?: [])->filter()->values();
                    $adminInitials = $adminNameParts->isEmpty() ? 'A' : mb_strtoupper(mb_substr($adminNameParts->first(), 0, 1).($adminNameParts->count() > 1 ? mb_substr($adminNameParts->last(), 0, 1) : ''));
                  @endphp
                  <div class="admin-dropdown" style="position: relative; display: inline-block;">
                    <button class="admin-dropdown-btn" id="adminDropdownBtn" type="button" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border); border-radius: 12px; padding: 6px 12px; color: var(--text); font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 13px; outline: none; transition: background 0.2s;">
                      @if ($adminUser?->avatar_url)
                        <img src="{{ $adminUser->avatar_url }}" alt="" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;" />
                      @else
                        <div style="width: 24px; height: 24px; border-radius: 50%; background: #4d44dc; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800;">
                          {{ $adminInitials }}
                        </div>
                      @endif
                      <span>{{ $adminUser->name ?? 'Admin' }}</span>
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.2s; pointer-events: none;">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </button>
                    
                    <div class="admin-dropdown-menu" id="adminDropdownMenu" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); background: #121622; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow); width: 180px; z-index: 1000; padding: 6px;">
                      <a href="{{ route('admin.settings') }}" class="admin-dropdown-item" style="display: block; padding: 10px 12px; color: var(--text); text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 500; transition: background 0.2s;">Settings</a>
                      
                      <div style="border-top: 1px solid var(--border); margin: 6px 0;"></div>
                      
                      <form method="POST" action="{{ route('auth.logout') }}" style="margin: 0; padding: 0;">
                        @csrf
                        <button type="submit" class="admin-dropdown-item" style="display: block; width: 100%; text-align: left; background: none; border: none; padding: 10px 12px; color: var(--danger, #ff6078); font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 8px; transition: background 0.2s;">Sign out</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
              <p class="topbar-desc">@yield('admin_subtitle', 'Track users, mentors, service mix, meeting sizes, schools, and revenue.')</p>
            </header>
          @endunless

          @yield('admin_content')
        </main>
      </section>
    </div>

    @include('layouts.partials.toasts')
    @yield('admin_page_data')
    <script src="{{ asset('assets/js/admin-auth.js') }}?v={{ filemtime(public_path('assets/js/admin-auth.js')) }}"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('adminDropdownBtn');
        const menu = document.getElementById('adminDropdownMenu');
        
        if (btn && menu) {
          btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isVisible = menu.style.display === 'block';
            menu.style.display = isVisible ? 'none' : 'block';
            const svg = btn.querySelector('svg');
            if (svg) {
              svg.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
            }
          });
          
          document.addEventListener('click', function () {
            menu.style.display = 'none';
            const svg = btn.querySelector('svg');
            if (svg) {
              svg.style.transform = 'rotate(0deg)';
            }
          });
        }
      });
    </script>
  </body>
</html>
