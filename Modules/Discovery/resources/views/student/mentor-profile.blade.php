@extends('layouts.portal-student')

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

        <div class="mt-4">
          <a href="{{ route('student.book-mentor', $mentor['id'] ?? 1) }}" class="store-btn">Book Now</a>
        </div>
      </article>
    </section>
  </div>
@endsection
