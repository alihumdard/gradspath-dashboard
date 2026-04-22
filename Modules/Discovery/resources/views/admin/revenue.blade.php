@extends('discovery::admin.layouts.app')

@php
  $adminRevenueSummary = $adminRevenueData['summary'] ?? [];
  $adminRevenueRanges = $adminRevenueData['available_ranges'] ?? [];
  $adminSelectedRevenueRange = $adminRevenueData['selected_range'] ?? '30d';
  $formatAdminMoney = static function ($amount): string {
    if ($amount === null) {
      return '-';
    }

    $precision = fmod((float) $amount, 1.0) === 0.0 ? 0 : 2;

    return '$' . number_format((float) $amount, $precision);
  };
@endphp

@section('admin_title', 'Revenue')
@section('admin_heading', 'Admin Revenue')
@section('admin_subtitle', 'Programs, top mentors, payouts, and refunds for the selected timeframe.')

@section('admin_content')
  <div class="section-head">
    <div>
      <h2>Revenue</h2>
      <p>Programs, top mentors, payouts, and refunds for the selected timeframe.</p>
    </div>
    <button class="primary-btn" type="button">Export Revenue</button>
  </div>

  <div class="toolbar">
    <form method="GET" action="{{ route('admin.revenue') }}">
      <select id="revenueRange" name="revenue_range" onchange="this.form.submit()">
        @foreach ($adminRevenueRanges as $adminRevenueRange)
          <option value="{{ $adminRevenueRange['value'] }}" @selected($adminSelectedRevenueRange === $adminRevenueRange['value'])>
            {{ $adminRevenueRange['label'] }}
          </option>
        @endforeach
      </select>
    </form>
  </div>

  <div class="summary-grid">
    <div class="small-card">
      <span>Gross Revenue</span>
      <strong>{{ $formatAdminMoney($adminRevenueSummary['gross_revenue'] ?? 0) }}</strong>
      <small>{{ $adminRevenueSummary['timeframe_label'] ?? 'Last 30 Days' }}</small>
    </div>
    <div class="small-card">
      <span>Mentor Payouts</span>
      <strong>{{ $formatAdminMoney($adminRevenueSummary['mentor_payouts_total'] ?? 0) }}</strong>
      <small>
        {{ $formatAdminMoney($adminRevenueSummary['mentor_payouts_paid'] ?? 0) }}
        paid / {{ $formatAdminMoney($adminRevenueSummary['mentor_payouts_pending'] ?? 0) }} due
      </small>
    </div>
    <div class="small-card">
      <span>Platform Revenue</span>
      <strong>{{ $formatAdminMoney($adminRevenueSummary['platform_revenue'] ?? 0) }}</strong>
      <small>retained amount</small>
    </div>
    <div class="small-card">
      <span>Refund Amount</span>
      <strong>{{ $formatAdminMoney($adminRevenueSummary['refund_amount'] ?? 0) }}</strong>
      <small>{{ $adminRevenueSummary['timeframe_label'] ?? 'Last 30 Days' }}</small>
    </div>
  </div>

  <div class="chart-grid">
    <div class="panel chart-panel">
      <div class="panel-head">
        <h3>Revenue by Program</h3>
        <span>all active programs</span>
      </div>
      <canvas id="programRevenueChart"></canvas>
    </div>

    <div class="panel chart-panel">
      <div class="panel-head">
        <h3>Top Mentors by Revenue</h3>
        <span>current period</span>
      </div>
      <canvas id="topMentorsChart"></canvas>
    </div>
  </div>
@endsection

@section('admin_page_data')
  <script id="adminRevenueData" type="application/json">@json($adminRevenueData ?? [])</script>
@endsection
