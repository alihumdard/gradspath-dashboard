@extends('layouts.app')

@section('css')
  @php($portalCssAsset = trim($__env->yieldContent('portal_css_asset')))
  @if ($portalCssAsset !== '')
    <link rel="stylesheet" href="{{ asset($portalCssAsset) }}?v={{ filemtime(public_path($portalCssAsset)) }}" />
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
          <button class="theme-toggle" id="themeToggle" type="button">
            Light / Dark
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
@endsection
