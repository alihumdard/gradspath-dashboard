<!-- Login Modal -->
<div
  id="login-modal"
  class="{{ $activeAuthModal === 'login' ? '' : 'hidden ' }}fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
>
  <div
    class="relative w-full max-w-md rounded-2xl shadow-xl bg-white border-2 border-[#6D28D9] login-modal-panel"
  >
    <button
      type="button"
      id="login-close"
      class="absolute top-4 right-4 z-10 w-8 h-8 flex items-center justify-center rounded-full transition-colors text-[#3730A3] hover:bg-black/5"
      aria-label="Close"
    >
      <svg
        class="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    </button>
    <div class="p-8 relative">
      <h2 id="dialog-title" class="text-2xl font-bold text-[#3730A3] mb-1">
        Welcome back
      </h2>
      <p class="text-sm text-[#6D28D9] mb-6">
        Sign in to continue with Grads Paths.
      </p>
      <form id="login-form" method="POST" action="{{ route('auth.login.post') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="auth_context" value="login" />
        <div>
          <label
            for="login-email"
            class="block text-sm font-semibold text-[#3730A3] mb-1.5"
            >Email</label
          >
          <div class="relative flex items-center">
            <input
              type="email"
              name="email"
              id="login-email"
              placeholder="you@university.edu"
              value="{{ old('email') }}"
              class="w-full rounded-xl border border-[#6D28D9] bg-white px-4 py-3 pl-4 pr-10 text-[#6D28D9] placeholder:text-[#6D28D9]/70 focus:outline-none focus:ring-1 focus:ring-[#6D28D9] text-sm"
            />
            <i
              class="fa-solid fa-envelope absolute right-3 text-[#6D28D9]"
              aria-hidden="true"
            ></i>
          </div>
          @if ($activeAuthModal === 'login')
            @error('email')
              <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
            @enderror
          @endif
        </div>
        <div>
          <label
            for="login-password"
            class="block text-sm font-semibold text-[#3730A3] mb-1.5"
            >Password</label
          >
          <div class="relative flex items-center">
            <input
              type="password"
              name="password"
              id="login-password"
              placeholder="Your password"
              class="w-full rounded-xl border border-[#6D28D9] bg-white px-4 py-3 pl-4 pr-16 text-[#6D28D9] placeholder:text-[#6D28D9]/70 focus:outline-none focus:ring-1 focus:ring-[#6D28D9] text-sm"
            />
            <i
              class="fa-solid fa-lock absolute right-9 text-[#6D28D9]"
              aria-hidden="true"
            ></i>
            <button
              type="button"
              class="password-toggle absolute right-3 text-[#6D28D9] hover:opacity-75"
              aria-label="Toggle password"
              data-target="login-password"
            >
              <i class="fa-solid fa-eye-slash text-sm toggle-icon"></i>
            </button>
          </div>
          @if ($activeAuthModal === 'login')
            @error('password')
              <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
            @enderror
          @endif
        </div>
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              id="login-remember"
              name="remember"
              value="1"
              @checked(old('remember'))
              class="h-4 w-4 rounded border-[#6D28D9] text-[#E92E88] focus:ring-[#E92E88]"
            />
            <span class="text-sm font-medium text-[#3730A3]"
              >Remember me</span
            >
          </label>
          <a
            href="{{ route('password.request') }}"
            class="text-sm font-medium text-[#E92E88] hover:underline"
          >
            Forgot password?
          </a>
        </div>
        <button
          type="submit"
          class="w-full rounded-xl py-3 text-sm font-bold text-white bg-gradient-to-r from-[#8C5FE2] to-[#E57CE1] hover:opacity-90 transition-all mt-6"
        >
          Continue
        </button>
      </form>
      <p class="mt-5 text-center text-sm text-[#6D28D9]">
        Don't have an account?
        <button
          type="button"
          id="login-to-signup"
          class="font-semibold text-[#3730A3] hover:underline"
        >
          Create account
        </button>
      </p>
    </div>
  </div>
</div>
