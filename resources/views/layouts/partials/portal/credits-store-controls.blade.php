@php
  $portalContext = $portal ?? (auth()->user()?->hasRole('mentor') ? 'mentor' : 'student');
  $balanceRoute = "{$portalContext}.credits.balance";
  $storeRoute = "{$portalContext}.store";
  $creditBalanceValue = (int) ($creditBalance ?? auth()->user()?->credit?->balance ?? 0);
@endphp

@if (Route::has($balanceRoute) && Route::has($storeRoute))
  <div class="credits-box" id="portalCreditsBox" data-balance-url="{{ route($balanceRoute) }}">
    Credits: <strong id="portalCreditsValue">{{ $creditBalanceValue }}</strong>
  </div>
  <a href="{{ route($storeRoute) }}" class="store-btn">Store</a>
@endif
