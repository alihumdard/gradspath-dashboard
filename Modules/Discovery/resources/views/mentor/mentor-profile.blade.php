@extends('layouts.portal-mentor')

@section('title', 'Mentor Profile - Grads Paths')
@section('portal_css_asset', 'assets/css/demo1.css')
@section('portal_js_asset', 'assets/js/demo1.js')
@section('portal_active_nav', 'mentors')

@section('portal_content')
  <div class="page-wrap">
    <div class="top-bar-content">
      <h1>{{ $mentor['name'] ?? 'Mentor' }}</h1>
      <p>{{ $mentor['role'] ?? 'Mentor Role' }}</p>
    </div>

    <section class="content-section">
      <article class="mentor-card">
        <div class="mentor-note-box">
          {{ $mentor['bio'] ?? 'Mentor biography' }}
        </div>

        @if ($mentor['canBook'] ?? false)
          <div class="mt-4">
            <a href="{{ $mentor['bookingUrl'] ?? route('mentor.mentor.book', $mentor['id'] ?? 1) }}" class="store-btn">Book Now</a>
          </div>
        @else
          <div class="mt-4 text-sm text-slate-600">
            You cannot book a meeting with your own mentor profile.
          </div>
        @endif
      </article>
    </section>
  </div>
@endsection
