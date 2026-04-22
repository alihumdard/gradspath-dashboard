@extends('discovery::admin.layouts.app')

@php
  $adminOverviewSummary = $adminOverviewData['summary'] ?? [];
  $adminOverviewTables = $adminOverviewData['tables'] ?? [];
  $formatAdminMoney = static function ($amount): string {
    if ($amount === null) {
      return '-';
    }

    $precision = fmod((float) $amount, 1.0) === 0.0 ? 0 : 2;

    return '$' . number_format((float) $amount, $precision);
  };
@endphp

@section('admin_title', 'Overview')
@section('admin_heading', 'Admin Overview')
@section('admin_subtitle', 'Track users, mentors, service mix, meeting sizes, schools, and revenue.')

@section('admin_content')
  <div class="kpi-grid">
    <div class="kpi-card">
      <span>Total Users</span>
      <strong>{{ number_format((int) ($adminOverviewSummary['total_users'] ?? 0)) }}</strong>
      <small>{{ number_format((int) ($adminOverviewSummary['new_users_30d'] ?? 0)) }} new in 30 days</small>
    </div>
    <div class="kpi-card">
      <span>Active Mentors</span>
      <strong>{{ number_format((int) ($adminOverviewSummary['active_mentors'] ?? 0)) }}</strong>
      <small>{{ number_format((int) ($adminOverviewSummary['inactive_mentors'] ?? 0)) }} inactive</small>
    </div>
    <div class="kpi-card">
      <span>Bookings (30d)</span>
      <strong>{{ number_format((int) ($adminOverviewSummary['bookings_30d'] ?? 0)) }}</strong>
      <small>{{ number_format((int) ($adminOverviewSummary['bookings_7d'] ?? 0)) }} this week</small>
    </div>
    <div class="kpi-card">
      <span>Gross Revenue</span>
      <strong>{{ $formatAdminMoney($adminOverviewSummary['gross_revenue_30d'] ?? 0) }}</strong>
      <small>30 day total</small>
    </div>
    <div class="kpi-card">
      <span>Platform Revenue</span>
      <strong>{{ $formatAdminMoney($adminOverviewSummary['platform_revenue_30d'] ?? 0) }}</strong>
      <small>after mentor split</small>
    </div>
    <div class="kpi-card">
      <span>Refunds</span>
      <strong>{{ $formatAdminMoney($adminOverviewSummary['refund_amount_30d'] ?? 0) }}</strong>
      <small>{{ number_format((int) ($adminOverviewSummary['refund_requests_30d'] ?? 0)) }} requests</small>
    </div>
  </div>

  <div class="chart-grid">
    <div class="panel chart-panel">
      <div class="panel-head">
        <h3>Bookings Over Time</h3>
        <span>Last 6 months</span>
      </div>
      <canvas id="bookingsChart"></canvas>
    </div>

    <div class="panel chart-panel">
      <div class="panel-head">
        <h3>Revenue Over Time</h3>
        <span>Last 6 months</span>
      </div>
      <canvas id="revenueChart"></canvas>
    </div>
  </div>

  <div class="split-grid">
    <div class="panel">
      <div class="panel-head">
        <h3>Top Mentors</h3>
        <span>last 30 days by revenue</span>
      </div>
      <div class="table-wrap compact-table">
        <table>
          <thead>
            <tr>
              <th>Mentor</th>
              <th>Program</th>
              <th>Meetings</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            @forelse (($adminOverviewTables['top_mentors'] ?? []) as $adminTopMentor)
              <tr>
                <td>{{ $adminTopMentor['mentor'] }}</td>
                <td>{{ $adminTopMentor['program'] }}</td>
                <td>{{ $adminTopMentor['meetings'] }}</td>
                <td>{{ $formatAdminMoney($adminTopMentor['revenue']) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4">No booking data yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head">
        <h3>Top Services</h3>
        <span>last 30 days by bookings</span>
      </div>
      <div class="table-wrap compact-table">
        <table>
          <thead>
            <tr>
              <th>Service</th>
              <th>Bookings</th>
              <th>Revenue</th>
              <th>Set Price</th>
            </tr>
          </thead>
          <tbody>
            @forelse (($adminOverviewTables['top_services'] ?? []) as $adminTopService)
              <tr>
                <td>{{ $adminTopService['service'] }}</td>
                <td>{{ $adminTopService['bookings'] }}</td>
                <td>{{ $formatAdminMoney($adminTopService['revenue']) }}</td>
                <td>{{ $adminTopService['set_price'] }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4">No booking data yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection

@section('admin_page_data')
  <script id="adminOverviewData" type="application/json">@json($adminOverviewData ?? [])</script>
@endsection
