@extends('discovery::admin.layouts.app')

@php
  $adminMentorRows = $adminMentorsData['rows'] ?? [];
  $adminMentorProgramOptions = $adminMentorsData['program_options'] ?? [];
  $adminMentorStatusOptions = $adminMentorsData['status_options'] ?? [];
  $formatAdminMoney = static function ($amount): string {
    if ($amount === null) {
      return '-';
    }

    $precision = fmod((float) $amount, 1.0) === 0.0 ? 0 : 2;

    return '$' . number_format((float) $amount, $precision);
  };
@endphp

@section('admin_title', 'Mentors')
@section('admin_heading', 'Admin Mentors')
@section('admin_subtitle', 'Track mentor performance, service mix, and profile status.')

@section('admin_content')
  <div class="section-head">
    <div>
      <h2>Mentors</h2>
      <p>Includes full service-count breakdown and totals.</p>
    </div>
    <button class="primary-btn" type="button">Export Mentors</button>
  </div>

  <div class="toolbar">
    <input
      type="text"
      id="mentorsSearch"
      placeholder="Search mentor, school, email..."
    />
    <select id="mentorsProgramFilter">
      <option value="all">All Programs</option>
      @foreach ($adminMentorProgramOptions as $adminMentorProgramOption)
        <option value="{{ $adminMentorProgramOption }}">{{ $adminMentorProgramOption }}</option>
      @endforeach
    </select>
    <select id="mentorsStatusFilter">
      <option value="all">All Statuses</option>
      @foreach ($adminMentorStatusOptions as $adminMentorStatusOption)
        <option value="{{ $adminMentorStatusOption }}">{{ $adminMentorStatusOption }}</option>
      @endforeach
    </select>
  </div>

  <div class="table-wrap panel no-pad">
    <table id="mentorsTable">
      <thead>
        <tr>
          <th>Mentor</th>
          <th>Program</th>
          <th>School</th>
          <th>Total Meetings</th>
          <th>Total Revenue</th>
          <th>Free Consult</th>
          <th>Tutoring</th>
          <th>Program Insights</th>
          <th>Interview Prep</th>
          <th>Application Review</th>
          <th>Gap Year Planning</th>
          <th>Office Hours</th>
          <th>Missed</th>
          <th>Refunds</th>
          <th>Rating</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($adminMentorRows as $adminMentor)
          <tr data-program="{{ $adminMentor['program_filter'] }}" data-status="{{ $adminMentor['status_filter'] }}">
            <td>
              <strong>{{ $adminMentor['name'] ?: '-' }}</strong><span>{{ $adminMentor['email'] ?: '-' }}</span>
            </td>
            <td>{{ $adminMentor['program'] ?: '-' }}</td>
            <td>{{ $adminMentor['school'] ?: '-' }}</td>
            <td>{{ $adminMentor['total_meetings'] }}</td>
            <td>{{ $formatAdminMoney($adminMentor['total_revenue']) }}</td>
            <td>{{ $adminMentor['free_consult'] }}</td>
            <td>{{ $adminMentor['tutoring'] }}</td>
            <td>{{ $adminMentor['program_insights'] }}</td>
            <td>{{ $adminMentor['interview_prep'] }}</td>
            <td>{{ $adminMentor['application_review'] }}</td>
            <td>{{ $adminMentor['gap_year_planning'] }}</td>
            <td>{{ $adminMentor['office_hours'] }}</td>
            <td>{{ $adminMentor['missed'] }}</td>
            <td>{{ $adminMentor['refunds'] }}</td>
            <td>{{ $adminMentor['rating'] ?: '-' }}</td>
            <td>{{ $adminMentor['status'] ?: '-' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
