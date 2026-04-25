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
      @if (($adminRevenueSummary['mentor_payouts_failed'] ?? 0) > 0)
        <small>{{ $formatAdminMoney($adminRevenueSummary['mentor_payouts_failed']) }} failed</small>
      @endif
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

  <div class="panel" style="margin-top: 24px;">
    <div class="panel-head">
      <h3>Recent Mentor Payouts</h3>
      <span>latest transfer and retry state</span>
    </div>

    @if (! empty($adminRevenueData['recent_payouts']))
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px 12px;">Mentor</th>
              <th style="text-align:left; padding:10px 12px;">Gross</th>
              <th style="text-align:left; padding:10px 12px;">Mentor Share</th>
              <th style="text-align:left; padding:10px 12px;">Platform Fee</th>
              <th style="text-align:left; padding:10px 12px;">Status</th>
              <th style="text-align:left; padding:10px 12px;">Reference</th>
              <th style="text-align:left; padding:10px 12px;">Failure</th>
            </tr>
          </thead>
          <tbody>
            @foreach (($adminRevenueData['recent_payouts'] ?? []) as $recentPayout)
              <tr>
                <td style="padding:10px 12px;">{{ $recentPayout['mentor_name'] }}</td>
                <td style="padding:10px 12px;">{{ $formatAdminMoney($recentPayout['gross_amount']) }}</td>
                <td style="padding:10px 12px;">{{ $formatAdminMoney($recentPayout['mentor_share_amount']) }}</td>
                <td style="padding:10px 12px;">{{ $formatAdminMoney($recentPayout['platform_fee_amount']) }}</td>
                <td style="padding:10px 12px;">{{ str_replace('_', ' ', ucfirst($recentPayout['status'])) }}</td>
                <td style="padding:10px 12px;">{{ $recentPayout['reference_at'] ? \Illuminate\Support\Carbon::parse($recentPayout['reference_at'])->format('M j, Y g:i A') : '-' }}</td>
                <td style="padding:10px 12px;">{{ $recentPayout['failure_reason'] ?: '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <p style="margin:0; color:#6b7280;">No mentor payout records match the selected timeframe yet.</p>
    @endif
  </div>
@endsection

@section('admin_page_data')
  <script id="adminRevenueData" type="application/json">@json($adminRevenueData ?? [])</script>
@endsection
