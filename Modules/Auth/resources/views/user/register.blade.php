<!-- Signup Modal -->
<div
  id="signup-modal"
  class="{{ $activeAuthModal === 'signup' ? '' : 'hidden ' }}fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 modal-backdrop overflow-y-auto"
>
  <div
    class="relative w-full max-w-[28rem] my-auto max-h-[calc(100vh-1.5rem)] rounded-2xl bg-white border-2 border-[#6D28D9] shadow-xl flex flex-col"
  >
    <button
      type="button"
      id="signup-close"
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
          stroke-width="2.5"
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    </button>
    <div class="px-6 py-8 pb-6 overflow-y-auto flex-1 min-h-0">
      <h2 class="text-2xl font-bold text-[#3730A3] mb-2">
        Create Your Account
      </h2>
      <p
        id="signup-subtitle"
        class="text-[13px] leading-snug text-[#6D28D9] mb-5"
      >
        Tell us who you are so we can personalize your experience and keep this
        community secure.
      </p>

      @if($errors->any() && $activeAuthModal === 'signup')
        <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          <p class="font-semibold">We could not create your account:</p>
          <ul class="mt-1 list-disc pl-5">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form id="signup-form" method="POST" action="{{ route('auth.register.post') }}" class="space-y-4" novalidate>
        @csrf
        <input type="hidden" name="auth_context" value="signup" />
        <input type="hidden" name="role" id="signup-role-input" value="{{ old('role', 'student') }}" />
        <input type="hidden" name="program_level" id="signup-program-level-input" value="{{ old('program_level', 'undergrad') }}" />
        <input type="hidden" name="mentor_type" id="signup-mentor-type-input" value="{{ old('mentor_type', 'graduate') }}" />
        <input type="hidden" name="institution_id" id="signup-institution-id" value="{{ old('institution_id') }}" />
        <div id="signup-type-section">
          <label id="signup-type-label" class="block text-sm font-bold text-[#1D1440] mb-2"
            >Student level</label
          >
          <div id="signup-type-options" class="grid grid-cols-1 gap-3">
            <button
              type="button"
              data-value="undergrad"
              data-role-scope="student"
              class="signup-level flex items-center justify-center gap-1.5 px-2 py-2.5 rounded-xl text-xs font-bold border border-[#6D28D9] bg-[#EBE0F8] text-[#6D28D9]"
            >
              <i class="fa-solid fa-graduation-cap"></i> Undergrad
            </button>
            <button
              type="button"
              data-value="graduate"
              data-role-scope="mentor"
              class="signup-level hidden flex items-center justify-center gap-1.5 px-2 py-2.5 rounded-xl text-xs font-bold border border-[#D8B4FE] bg-white text-[#6D28D9] hover:border-[#6D28D9]"
            >
              <i class="fa-solid fa-graduation-cap"></i> Grad Mentor
            </button>
            <button
              type="button"
              data-value="professional"
              data-role-scope="mentor"
              class="signup-level hidden flex items-center justify-center gap-1.5 px-2 py-2.5 rounded-xl text-xs font-bold border border-[#D8B4FE] bg-white text-[#6D28D9] hover:border-[#6D28D9]"
            >
              <i class="fa-solid fa-briefcase"></i> Professional Mentor
            </button>
          </div>
          @error('program_level')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
          @error('mentor_type')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>
        <div>
          <label class="block text-sm font-bold text-[#1D1440] mb-2"
            >I am a:</label
          >
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              data-value="student"
              class="signup-role flex items-center justify-center gap-2 px-3 py-3 rounded-xl text-sm font-bold border border-[#6D28D9] bg-[#EBE0F8] text-[#6D28D9]"
            >
              <i class="fa-solid fa-book-open"></i> Student
            </button>
            <button
              type="button"
              data-value="mentor"
              class="signup-role flex items-center justify-center gap-2 px-3 py-3 rounded-xl text-sm font-bold border border-[#D8B4FE] bg-white text-[#6D28D9] hover:border-[#6D28D9]"
            >
              <i class="fa-solid fa-building-columns"></i> Mentor
            </button>
          </div>
          @error('role')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label class="block text-xs font-bold text-[#1D1440] mb-1.5"
            >Full name <span class="text-red-500">*</span></label
          >
          <div class="relative flex items-center">
            <input
              type="text"
              name="name"
              id="signup-fullname"
              placeholder="Your name"
              required
              value="{{ old('name') }}"
              class="w-full rounded-xl border border-[#6D28D9] bg-white px-3 py-2.5 pl-3 pr-10 text-[#6D28D9] placeholder:text-[#6D28D9]/60 focus:outline-none focus:ring-1 focus:ring-[#6D28D9] text-sm"
            />
            <i
              class="fa-solid fa-user absolute right-3 text-[#6D28D9]"
              aria-hidden="true"
            ></i>
          </div>
          @error('name')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-[#1D1440] mb-1.5"
            >Email <span class="text-red-500">*</span></label
          >
          <div class="relative flex items-center">
            <input
              type="email"
              name="email"
              id="signup-email"
              placeholder="you@example.com"
              required
              value="{{ old('email') }}"
              class="w-full rounded-xl border border-[#6D28D9] bg-white px-3 py-2.5 pl-3 pr-10 text-[#6D28D9] placeholder:text-[#6D28D9]/60 focus:outline-none focus:ring-1 focus:ring-[#6D28D9] text-sm"
            />
            <i
              class="fa-solid fa-envelope absolute right-3 text-[#6D28D9]"
              aria-hidden="true"
            ></i>
          </div>
          @error('email')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-[#1D1440] mb-1.5"
            >Institution <span class="text-red-500">*</span></label
          >
          <div class="relative flex items-center">
            <input
              type="text"
              name="institution"
              id="signup-institution"
              placeholder="Start typing to search..."
              required
              value="{{ old('institution') }}"
              class="w-full rounded-xl border border-[#6D28D9] bg-white px-3 py-2.5 pl-3 pr-10 text-[#6D28D9] placeholder:text-[#6D28D9]/60 focus:outline-none focus:ring-1 focus:ring-[#6D28D9] text-sm"
              autocomplete="off"
            />
            <i
              class="fa-solid fa-school absolute right-3 text-[#6D28D9]"
              aria-hidden="true"
            ></i>
            <div
              id="institution-suggestions"
              class="absolute top-full left-0 right-0 mt-1 bg-white border border-[#D8B4FE] rounded-xl shadow-lg max-h-64 overflow-y-auto hidden z-50"
            ></div>
          </div>
          @error('institution')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
          @error('institution_id')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-[#1D1440] mb-1.5"
            >Password <span class="text-red-500">*</span></label
          >
          <div class="relative flex items-center">
            <input
              type="password"
              name="password"
              id="signup-password"
              placeholder="Create a password"
              required
              class="w-full rounded-xl border border-[#6D28D9] bg-white px-3 py-2.5 pl-3 pr-16 text-[#6D28D9] placeholder:text-[#6D28D9]/60 focus:outline-none focus:ring-1 focus:ring-[#6D28D9] text-sm"
            />
            <i
              class="fa-solid fa-lock absolute right-9 text-[#6D28D9]"
              aria-hidden="true"
            ></i>
            <button
              type="button"
              class="password-toggle absolute right-3 text-[#6D28D9] hover:opacity-75"
              aria-label="Toggle password"
              data-target="signup-password"
            >
              <i class="fa-solid fa-eye-slash text-xs toggle-icon"></i>
            </button>
          </div>
          @error('password')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-[#1D1440] mb-1.5"
            >Confirm password <span class="text-red-500">*</span></label
          >
          <div class="relative flex items-center">
            <input
              type="password"
              name="password_confirmation"
              id="signup-password-confirmation"
              placeholder="Confirm your password"
              required
              class="w-full rounded-xl border border-[#6D28D9] bg-white px-3 py-2.5 pl-3 pr-16 text-[#6D28D9] placeholder:text-[#6D28D9]/60 focus:outline-none focus:ring-1 focus:ring-[#6D28D9] text-sm"
            />
            <i
              class="fa-solid fa-lock absolute right-9 text-[#6D28D9]"
              aria-hidden="true"
            ></i>
            <button
              type="button"
              class="password-toggle absolute right-3 text-[#6D28D9] hover:opacity-75"
              aria-label="Toggle password"
              data-target="signup-password-confirmation"
            >
              <i class="fa-solid fa-eye-slash text-xs toggle-icon"></i>
            </button>
          </div>
          @error('password_confirmation')
            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
          @enderror
        </div>

        <p class="text-[11px] leading-tight text-[#6D28D9]">
          We may re-verify your email periodically to keep mentors and
          students real and current.
        </p>
        <button
          type="submit"
          class="w-full rounded-xl py-3 text-[15px] font-bold text-white bg-gradient-to-r from-[#8C5FE2] to-[#E57CE1] hover:opacity-90 transition-all mt-4"
        >
          Continue
        </button>
      </form>
      <p class="mt-4 text-center text-sm text-[#6D28D9]">
        Already have an account?
        <button
          type="button"
          id="signup-to-login"
          class="font-semibold text-[#1D1440] hover:underline"
        >
          Log in
        </button>
      </p>
    </div>
  </div>
</div>
