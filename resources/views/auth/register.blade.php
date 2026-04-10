@extends('layouts.app')

@section('title', 'Register - Grads Paths')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/demo12.css') }}" />
@endsection

@section('content')
    <div class="app">
      <section class="login-screen">
        <div class="login-card">
          <h1>Grads Paths</h1>
          <p>Create your account</p>

          <form method="POST" action="{{ route('register') }}" class="login-form">
            @csrf
            
            <div class="form-group">
              <input type="text" name="name" placeholder="Full Name" required value="{{ old('name') }}" />
              @error('name')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" required value="{{ old('email') }}" />
              @error('email')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <select name="role" required>
                <option value="">Select Role</option>
                <option value="student">Student</option>
                <option value="mentor">Mentor</option>
              </select>
              @error('role')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="password-group">
              <input type="password" name="password" id="password" placeholder="Password" required />
              <label class="show-pass-inline">
                <input type="checkbox" id="showPassword" />
                <span>Show password</span>
              </label>
              @error('password')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="password-group">
              <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm Password" required />
              @error('password_confirmation')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <button type="submit" class="primary-btn">Create Account</button>
          </form>

          <div class="auth-links">
            <p>Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
          </div>
        </div>
      </section>
    </div>
@endsection

@section('js')
<script src="{{ asset('assets/js/demo12.js') }}"></script>
<script>
  document.getElementById('showPassword')?.addEventListener('change', function() {
    const input = document.getElementById('password');
    const confirm = document.getElementById('password_confirmation');
    input.type = this.checked ? 'text' : 'password';
    confirm.type = this.checked ? 'text' : 'password';
  });
</script>
@endsection
