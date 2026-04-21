@extends('discovery::admin.layouts.app')

@php
  $adminProgramRankings = $adminRankingsData['programs'] ?? [];
  $adminServiceRankings = $adminRankingsData['services'] ?? [];
  $adminStudentSchoolRankings = $adminRankingsData['student_schools'] ?? [];
  $adminMentorSchoolRankings = $adminRankingsData['mentor_schools'] ?? [];
  $adminMeetingSizeRankings = $adminRankingsData['meeting_sizes'] ?? [];
@endphp

@section('admin_title', 'Rankings')
@section('admin_heading', 'Admin Rankings')
@section('admin_subtitle', 'All programs, all services, and school rankings for both students and mentors.')

@section('admin_content')
  <div class="section-head">
    <div>
      <h2>Rankings</h2>
      <p>All programs, all services, and school rankings for both students and mentors.</p>
    </div>
  </div>

  <div class="rank-grid">
    <div class="panel">
      <div class="panel-head">
        <h3>Programs by Rank</h3>
      </div>
      <div class="mini-list">
        @forelse ($adminProgramRankings as $adminProgramRanking)
          <div class="mini-item compact">
            <strong>{{ $adminProgramRanking['rank'] }}. {{ $adminProgramRanking['label'] }}</strong>
            <span>{{ $adminProgramRanking['count'] }} bookings</span>
          </div>
        @empty
          <div class="mini-item compact">
            <strong>No booking data yet</strong><span>-</span>
          </div>
        @endforelse
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Services by Rank</h3>
      </div>
      <div class="mini-list">
        @forelse ($adminServiceRankings as $adminServiceRanking)
          <div class="mini-item compact">
            <strong>{{ $adminServiceRanking['rank'] }}. {{ $adminServiceRanking['label'] }}</strong>
            <span>{{ $adminServiceRanking['count'] }} bookings</span>
          </div>
        @empty
          <div class="mini-item compact">
            <strong>No booking data yet</strong><span>-</span>
          </div>
        @endforelse
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Top Student Schools by Bookings</h3>
      </div>
      <div class="mini-list">
        @forelse ($adminStudentSchoolRankings as $adminStudentSchoolRanking)
          <div class="mini-item compact">
            <strong>{{ $adminStudentSchoolRanking['rank'] }}. {{ $adminStudentSchoolRanking['label'] }}</strong>
            <span>{{ $adminStudentSchoolRanking['count'] }} bookings</span>
          </div>
        @empty
          <div class="mini-item compact">
            <strong>No booking data yet</strong><span>-</span>
          </div>
        @endforelse
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Top Mentor Schools by Bookings</h3>
      </div>
      <div class="mini-list">
        @forelse ($adminMentorSchoolRankings as $adminMentorSchoolRanking)
          <div class="mini-item compact">
            <strong>{{ $adminMentorSchoolRanking['rank'] }}. {{ $adminMentorSchoolRanking['label'] }}</strong>
            <span>{{ $adminMentorSchoolRanking['count'] }} bookings</span>
          </div>
        @empty
          <div class="mini-item compact">
            <strong>No booking data yet</strong><span>-</span>
          </div>
        @endforelse
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Meeting Size Mix</h3>
      </div>
      <div class="mini-list">
        @forelse ($adminMeetingSizeRankings as $adminMeetingSizeRanking)
          <div class="mini-item compact">
            <strong>{{ $adminMeetingSizeRanking['rank'] }}. {{ $adminMeetingSizeRanking['label'] }}</strong>
            <span>{{ $adminMeetingSizeRanking['count'] }} bookings</span>
          </div>
        @empty
          <div class="mini-item compact">
            <strong>No booking data yet</strong><span>-</span>
          </div>
        @endforelse
      </div>
    </div>
  </div>
@endsection
