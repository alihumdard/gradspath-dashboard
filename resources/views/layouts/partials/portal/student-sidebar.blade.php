@php($activeNav = $activeNav ?? '')
@php($roleLabel = ucfirst(auth()->user()?->getRoleNames()->first() ?? 'student'))
@php($currentUser = auth()->user())
@php($sidebarNameParts = collect(preg_split('/\s+/', trim($currentUser?->name ?? '')) ?: [])->filter()->values())
@php($sidebarInitials = $sidebarNameParts->isEmpty() ? '' : mb_strtoupper(mb_substr($sidebarNameParts->first(), 0, 1).($sidebarNameParts->count() > 1 ? mb_substr($sidebarNameParts->last(), 0, 1) : '')))
@php($isActiveNav = fn (string $nav, array $routePatterns, array $pathPatterns) => $activeNav === $nav || request()->routeIs(...$routePatterns) || request()->is(...$pathPatterns))
@php($bookingUnreadCount = $currentUser ? \Modules\Bookings\app\Models\Chat::query()->where('receiver_id', $currentUser->id)->where('is_read', false)->count() : 0)

<aside class="sidebar" id="sidebar">
  <div class="sidebar-top">
    <div class="brand">
      <div class="brand-icon">GP</div>
      <div class="brand-copy">
        <div class="brand-title">Grads Paths</div>
        <div class="brand-subtitle">STUDENT PORTAL</div>
      </div>
    </div>

    <a href="{{ route('public.home') }}" class="back-link">
      <span class="back-link-arrow">&larr;</span>
      <span>Back to the Website</span>
    </a>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group">
      <a href="{{ route('student.dashboard') }}" @class(['nav-item single-link', 'active' => $isActiveNav('dashboard', ['student.dashboard'], ['student/dashboard'])])>
        <span class="nav-left">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
              <circle cx="9.5" cy="7" r="3"></circle>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a3 3 0 0 1 0 5.74"></path>
            </svg>
          </span>
          <span class="nav-text">Dashboard</span>
        </span>
      </a>
    </div>

    <div class="nav-group">
      <a href="/student/institutions" @class(['nav-item single-link', 'active' => $isActiveNav('institutions', ['student.institutions.*'], ['student/institutions*'])])>
        <span class="nav-left">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 21h18"></path>
              <path d="M5 21V7l7-4 7 4v14"></path>
              <path d="M9 9h.01"></path>
              <path d="M9 13h.01"></path>
              <path d="M9 17h.01"></path>
              <path d="M15 9h.01"></path>
              <path d="M15 13h.01"></path>
              <path d="M15 17h.01"></path>
            </svg>
          </span>
          <span class="nav-text">Institutions</span>
        </span>
      </a>
    </div>

    <div class="nav-group">
      <a href="/student/mentors" @class(['nav-item single-link', 'active' => $isActiveNav('mentors', ['student.mentors.*'], ['student/mentors*'])])>
        <span class="nav-left">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 6.5h16"></path>
              <path d="M4 12h16"></path>
              <path d="M4 17.5h16"></path>
            </svg>
          </span>
          <span class="nav-text">Find Mentors</span>
        </span>
      </a>
    </div>

    <div class="nav-group">
      <a href="{{ route('student.office-hours') }}" @class(['nav-item single-link', 'active' => $isActiveNav('office-hours', ['student.office-hours*'], ['student/office-hours*'])])>
        <span class="nav-left">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="5" width="18" height="16" rx="2"></rect>
              <path d="M16 3v4"></path>
              <path d="M8 3v4"></path>
              <path d="M3 11h18"></path>
            </svg>
          </span>
          <span class="nav-text">Office Hours</span>
        </span>
      </a>
    </div>

    <div class="nav-group">
      <a href="{{ route('feedback.index') }}" @class(['nav-item single-link', 'active' => $isActiveNav('feedback', ['feedback.*', 'student.feedback.*'], ['feedback*', 'student/feedback*'])])>
        <span class="nav-left">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>
            </svg>
          </span>
          <span class="nav-text">Feedback</span>
        </span>
      </a>
    </div>

    <div class="nav-group">
      <a href="{{ route('student.bookings.index') }}" @class(['nav-item single-link', 'active' => $isActiveNav('bookings', ['student.bookings.*'], ['student/bookings*'])])>
        <span class="nav-left">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2"></rect>
              <path d="M16 2v4"></path>
              <path d="M8 2v4"></path>
              <path d="M3 10h18"></path>
            </svg>
          </span>
          <span class="nav-text">Bookings</span>
        </span>
        @if ($bookingUnreadCount > 0)
          <span class="portal-nav-badge" aria-label="{{ $bookingUnreadCount }} unread booking messages">{{ $bookingUnreadCount > 99 ? '99+' : $bookingUnreadCount }}</span>
        @endif
      </a>
    </div>

    <div class="nav-group">
      <a href="/student/support" @class(['nav-item single-link', 'active' => $isActiveNav('support', ['student.support.*'], ['student/support*'])])>
        <span class="nav-left">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"></circle>
              <path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 3-3 3"></path>
              <path d="M12 17h.01"></path>
            </svg>
          </span>
          <span class="nav-text">Support</span>
        </span>
      </a>
      <div class="helper-note">Create a support ticket</div>
    </div>

    <div class="nav-section-label">Settings</div>

    <div class="nav-group">
      <a href="{{ route('student.settings.index') }}" @class(['nav-item single-link', 'active' => $isActiveNav('settings', ['student.settings.*'], ['student/settings*'])])>
        <span class="nav-left">
          <span class="nav-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
          </span>
          <span class="nav-text">Settings</span>
        </span>
      </a>
      <div class="helper-note">Profile and display preferences</div>

      <form method="POST" action="{{ route('auth.logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="nav-item single-link w-full text-left">
          <span class="nav-left">
            <span class="nav-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
            </span>
            <span class="nav-text">Logout</span>
          </span>
        </button>
      </form>

      <div class="portal-account-card">
        <div class="portal-account-avatar">
          <span>{{ $sidebarInitials }}</span>
        </div>
        <div class="portal-account-copy">
          <div class="portal-account-name">{{ $currentUser?->name ?: 'Student' }}</div>
          <div class="portal-account-email" title="{{ $currentUser?->email }}">{{ $currentUser?->email }}</div>
          <div class="portal-account-role">{{ $roleLabel }}</div>
        </div>
      </div>
    </div>
  </nav>
</aside>
