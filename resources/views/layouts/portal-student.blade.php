@extends('layouts.portal')

@section('portal_sidebar')
  @include('layouts.partials.portal.student-sidebar', [
      'activeNav' => trim($__env->yieldContent('portal_active_nav')),
  ])
@endsection

@section('portal_topbar_left')
  @yield('page_topbar_left')
@endsection

@section('portal_topbar_right')
  @php($creditBalanceValue = (int) ($creditBalance ?? auth()->user()?->credit?->balance ?? 0))

  <div class="credits-box" id="studentCreditsBox" data-balance-url="{{ route('student.credits.balance') }}">
    Credits: <strong id="studentCreditsValue">{{ $creditBalanceValue }}</strong>
  </div>
  <a href="{{ route('student.store') }}" class="store-btn">Store</a>

  @yield('page_topbar_right')
@endsection

@section('portal_js')
  <script>
    (function () {
      const creditsBox = document.getElementById('studentCreditsBox');
      const creditsValue = document.getElementById('studentCreditsValue');

      if (!creditsBox || !creditsValue) {
        return;
      }

      const balanceUrl = creditsBox.dataset.balanceUrl;
      let refreshHandle = null;

      async function refreshCredits() {
        try {
          const response = await fetch(balanceUrl, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
            credentials: 'same-origin',
          });

          if (!response.ok) {
            return;
          }

          const payload = await response.json();

          if (typeof payload.balance === 'number') {
            creditsValue.textContent = payload.balance;
          }
        } catch (error) {
          console.debug('Unable to refresh student credits right now.', error);
        }
      }

      refreshCredits();
      refreshHandle = window.setInterval(refreshCredits, 30000);

      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
          refreshCredits();
        }
      });

      window.addEventListener('beforeunload', () => {
        if (refreshHandle) {
          window.clearInterval(refreshHandle);
        }
      });
    })();
  </script>

  @yield('page_js')
@endsection
