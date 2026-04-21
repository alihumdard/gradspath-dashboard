@extends('discovery::admin.layouts.app')

@php
  $adminStudentRows = $adminUsersData['rows'] ?? [];
  $adminUserProgramOptions = $adminUsersData['program_options'] ?? [];
  $adminUserInstitutionOptions = $adminUsersData['institution_options'] ?? [];
  $formatAdminMoney = static function ($amount): string {
    if ($amount === null) {
      return '-';
    }

    $precision = fmod((float) $amount, 1.0) === 0.0 ? 0 : 2;

    return '$' . number_format((float) $amount, $precision);
  };
@endphp

@section('admin_title', 'Users')
@section('admin_heading', 'Admin Users')
@section('admin_subtitle', 'Review student activity, spending, and service usage.')

@section('admin_content')
  <div class="section-head">
    <div>
      <h2>Users</h2>
      <p>Total meetings, total spend, and numeric breakdown by service.</p>
    </div>
    <button class="primary-btn" type="button">Export Users</button>
  </div>

  <div class="toolbar">
    <input
      type="text"
      id="usersSearch"
      placeholder="Search user, email, institution..."
    />
    <select id="usersProgramFilter">
      <option value="all">All Programs</option>
      @foreach ($adminUserProgramOptions as $adminUserProgramOption)
        <option value="{{ $adminUserProgramOption }}">{{ $adminUserProgramOption }}</option>
      @endforeach
    </select>
    <select id="usersInstitutionFilter">
      <option value="all">All Institutions</option>
      @foreach ($adminUserInstitutionOptions as $adminUserInstitutionOption)
        <option value="{{ $adminUserInstitutionOption }}">{{ $adminUserInstitutionOption }}</option>
      @endforeach
    </select>
  </div>

  <div class="table-wrap panel no-pad">
    <table id="usersTable">
      <thead>
        <tr>
          <th>User</th>
          <th>Program</th>
          <th>Institution</th>
          <th>Total Meetings</th>
          <th>Total Spent</th>
          <th>Free Consult</th>
          <th>Tutoring</th>
          <th>Program Insights</th>
          <th>Interview Prep</th>
          <th>Application Review</th>
          <th>Gap Year Planning</th>
          <th>Office Hours</th>
          <th>Last Active</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($adminStudentRows as $adminStudent)
          <tr data-program="{{ $adminStudent['program_filter'] }}" data-institution="{{ $adminStudent['institution_filter'] }}">
            <td>
              <strong>{{ $adminStudent['name'] ?? '-' }}</strong><span>{{ $adminStudent['email'] ?? '-' }}</span>
            </td>
            <td>{{ $adminStudent['program'] ?: '-' }}</td>
            <td>{{ $adminStudent['institution'] ?: '-' }}</td>
            <td>{{ $adminStudent['total_meetings'] }}</td>
            <td>{{ $formatAdminMoney($adminStudent['total_spent']) }}</td>
            <td>{{ $adminStudent['free_consult'] }}</td>
            <td>{{ $adminStudent['tutoring'] }}</td>
            <td>{{ $adminStudent['program_insights'] }}</td>
            <td>{{ $adminStudent['interview_prep'] }}</td>
            <td>{{ $adminStudent['application_review'] }}</td>
            <td>{{ $adminStudent['gap_year_planning'] }}</td>
            <td>{{ $adminStudent['office_hours'] }}</td>
            <td>{{ $adminStudent['last_active'] ?: '-' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
