@extends('discovery::admin.layouts.app')

@php
  $payouts = $adminPayoutsData['payouts'];
  $summary = $adminPayoutsData['summary'] ?? [];
  $filters = $adminPayoutsData['filters'] ?? [];
  $statusLabels = $adminPayoutsData['status_labels'] ?? [];
  $rangeOptions = $adminPayoutsData['range_options'] ?? [];
  $formatMoney = static function ($amount, string $currency = 'USD'): string {
      $precision = fmod((float) $amount, 1.0) === 0.0 ? 0 : 2;
      return strtoupper($currency) . ' ' . number_format((float) $amount, $precision);
  };
  $formatDate = static fn ($date): string => $date ? \Illuminate\Support\Carbon::parse($date)->format('M j, Y g:i A') : '-';
  $detailCloseUrl = route('admin.payouts', request()->only(['q', 'status', 'range']));
@endphp

@section('admin_title', 'Payouts')
@section('admin_heading', 'Admin Payouts')
@section('admin_subtitle', 'Review mentor payout ledger entries, Stripe references, booking context, and transfer state.')

@section('admin_head')
  <style>
    .payout-kpi-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 16px;
    }

    .payout-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 14px;
    }

    .payout-toolbar input,
    .payout-toolbar select {
      height: 46px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.04);
      color: var(--text);
      padding: 0 12px;
      outline: none;
    }

    .payout-toolbar input {
      flex: 1 1 280px;
    }

    .payout-toolbar select {
      min-width: 180px;
      color-scheme: dark;
      background-color: #202534;
    }

    .payout-status {
      display: inline-flex;
      align-items: center;
      width: fit-content;
      min-height: 28px;
      border-radius: 999px;
      padding: 4px 10px;
      font-size: 12px;
      font-weight: 800;
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--text);
      background: rgba(255, 255, 255, 0.06);
    }

    .payout-status--pending_release {
      border-color: rgba(98, 169, 255, 0.28);
      background: rgba(98, 169, 255, 0.12);
      color: #b9d8ff;
    }

    .payout-status--ready {
      border-color: rgba(242, 166, 63, 0.32);
      background: rgba(242, 166, 63, 0.12);
      color: #ffd89c;
    }

    .payout-status--transferred,
    .payout-status--paid_out {
      border-color: rgba(34, 199, 122, 0.3);
      background: rgba(34, 199, 122, 0.12);
      color: #95efbc;
    }

    .payout-status--failed {
      border-color: rgba(255, 96, 120, 0.32);
      background: rgba(255, 96, 120, 0.12);
      color: #ffb2bf;
    }

    .payout-status--reversed {
      color: var(--muted);
    }

    .payout-link {
      color: var(--text);
      text-decoration: none;
    }

    .payout-link:hover {
      color: #b9d8ff;
    }

    .payout-pagination {
      margin-top: 14px;
    }

    .payout-modal {
      position: fixed;
      inset: 0;
      z-index: 1100;
      display: grid;
      place-items: center;
      padding: 24px;
      background: rgba(0, 0, 0, 0.62);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }

    .payout-dialog {
      width: min(1080px, 100%);
      max-height: min(880px, calc(100vh - 48px));
      overflow-y: auto;
      padding: 20px;
      display: grid;
      gap: 16px;
    }

    .payout-dialog-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
    }

    .payout-dialog-title h3 {
      margin: 0 0 6px;
      font-size: 28px;
    }

    .payout-dialog-title span {
      color: var(--muted);
      line-height: 1.5;
    }

    .payout-dialog-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    .payout-dialog-close {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--text);
      text-decoration: none;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.04);
      font-size: 24px;
      line-height: 1;
    }

    .payout-detail-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
    }

    .payout-detail-card,
    .payout-detail-block {
      border: 1px solid var(--border);
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.03);
      padding: 14px;
      min-width: 0;
    }

    .payout-detail-card span,
    .payout-detail-block span {
      display: block;
      margin-bottom: 7px;
      color: var(--muted);
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .payout-detail-card strong,
    .payout-detail-card p,
    .payout-detail-block p {
      margin: 0;
      line-height: 1.55;
      overflow-wrap: anywhere;
    }

    .payout-detail-section {
      display: grid;
      gap: 10px;
    }

    .payout-detail-section h4 {
      margin: 0;
      font-size: 18px;
    }

    .payout-rule-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    @media (max-width: 1180px) {
      .payout-kpi-grid,
      .payout-detail-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 640px) {
      .payout-kpi-grid,
      .payout-detail-grid,
      .payout-rule-grid {
        grid-template-columns: 1fr;
      }

      .payout-modal {
        padding: 14px;
        place-items: stretch;
      }

      .payout-dialog {
        max-height: calc(100vh - 28px);
      }

      .payout-dialog-head {
        flex-direction: column;
      }

      .payout-dialog-actions {
        width: 100%;
        justify-content: space-between;
      }
    }
  </style>
@endsection

@section('admin_content')
  <div class="section-head">
    <div>
      <h2>Payouts</h2>
      <p>Read-only mentor payout ledger with Stripe transfer and booking context.</p>
    </div>
  </div>

  <div class="payout-kpi-grid">
    <div class="small-card">
      <span>Total Mentor Share</span>
      <strong>{{ $formatMoney($summary['total_mentor_share'] ?? 0) }}</strong>
      <small>{{ number_format((int) ($summary['count'] ?? 0)) }} payouts</small>
    </div>
    <div class="small-card">
      <span>Pending Release</span>
      <strong>{{ $formatMoney($summary['pending_release'] ?? 0) }}</strong>
      <small>waiting for completion</small>
    </div>
    <div class="small-card">
      <span>Ready / Failed</span>
      <strong>{{ $formatMoney($summary['ready_failed'] ?? 0) }}</strong>
      <small>needs payout attention</small>
    </div>
    <div class="small-card">
      <span>Transferred / Paid</span>
      <strong>{{ $formatMoney($summary['transferred_paid_out'] ?? 0) }}</strong>
      <small>sent through Stripe</small>
    </div>
    <div class="small-card">
      <span>Reversed</span>
      <strong>{{ $formatMoney($summary['reversed'] ?? 0) }}</strong>
      <small>cancelled before release</small>
    </div>
  </div>

  <section class="panel">
    <form class="payout-toolbar" method="GET" action="{{ route('admin.payouts') }}">
      <input type="search" name="q" value="{{ $filters['search'] ?? '' }}" placeholder="Search mentor, student, Stripe account, transfer id" />
      <select name="status" aria-label="Filter by payout status">
        <option value="" @selected(($filters['status'] ?? '') === '')>All Statuses</option>
        @foreach ($statusLabels as $value => $label)
          <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <select name="range" aria-label="Filter by date range">
        @foreach ($rangeOptions as $range)
          <option value="{{ $range['value'] }}" @selected(($filters['range'] ?? '30d') === $range['value'])>{{ $range['label'] }}</option>
        @endforeach
      </select>
      <button class="primary-btn" type="submit">Filter</button>
      @if (($filters['search'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['range'] ?? '30d') !== '30d')
        <a class="ghost-btn" href="{{ route('admin.payouts') }}">Clear</a>
      @endif
    </form>

    <div class="table-wrap">
      <table style="min-width: 1120px;">
        <thead>
          <tr>
            <th>Payout</th>
            <th>Mentor</th>
            <th>Student</th>
            <th>Amounts</th>
            <th>Status</th>
            <th>Reference</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($payouts as $payout)
            <tr>
              <td>
                <a class="payout-link" href="{{ route('admin.payouts.show', array_merge(['id' => $payout['id']], request()->only(['q', 'status', 'range']))) }}">
                  <strong>#{{ $payout['id'] }}</strong>
                  <span>Booking {{ $payout['booking_id'] ? '#'.$payout['booking_id'] : '-' }} · {{ $payout['session_type'] }}</span>
                </a>
              </td>
              <td>
                <strong>{{ $payout['mentor_name'] }}</strong>
                <span>{{ $payout['mentor_email'] }}</span>
              </td>
              <td>{{ $payout['student_name'] }}</td>
              <td>
                <strong>{{ $formatMoney($payout['mentor_share_amount'], $payout['currency']) }}</strong>
                <span>gross {{ $formatMoney($payout['gross_amount'], $payout['currency']) }} / fee {{ $formatMoney($payout['platform_fee_amount'], $payout['currency']) }}</span>
              </td>
              <td>
                <span class="payout-status payout-status--{{ $payout['status'] }}">{{ $payout['status_label'] }}</span>
                @if ($payout['failure_reason'])
                  <span>{{ $payout['failure_reason'] }}</span>
                @endif
              </td>
              <td>
                <strong>{{ $formatDate($payout['reference_at']) }}</strong>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6">No mentor payouts match this view.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="payout-pagination">
      {{ $payouts->links() }}
    </div>
  </section>

  @if ($selectedPayout)
    <div class="payout-modal" role="dialog" aria-modal="true" aria-labelledby="payoutDialogTitle">
      <div class="panel payout-dialog">
        <div class="payout-dialog-head">
          <div class="payout-dialog-title">
            <h3 id="payoutDialogTitle">Payout #{{ $selectedPayout['id'] }}</h3>
            <span>{{ $selectedPayout['mentor_name'] }} · {{ $formatMoney($selectedPayout['mentor_share_amount'], $selectedPayout['currency']) }}</span>
          </div>
          <div class="payout-dialog-actions">
            <span class="payout-status payout-status--{{ $selectedPayout['status'] }}">{{ $selectedPayout['status_label'] }}</span>
            <a class="payout-dialog-close" href="{{ $detailCloseUrl }}" aria-label="Close payout dialog">&times;</a>
          </div>
        </div>

        <div class="payout-detail-section">
          <h4>Mentor & Student</h4>
          <div class="payout-detail-grid">
            <div class="payout-detail-card">
              <span>Mentor</span>
              <strong>{{ $selectedPayout['mentor_name'] }}</strong>
              <p>{{ $selectedPayout['mentor_email'] }}</p>
            </div>
            <div class="payout-detail-card">
              <span>Stripe Account</span>
              <strong>{{ $selectedPayout['stripe_account_id'] ?: '-' }}</strong>
              <p>{{ $selectedPayout['payouts_enabled'] ? 'Payouts enabled' : 'Payouts not enabled' }} · {{ $selectedPayout['stripe_onboarding_complete'] ? 'Onboarding complete' : 'Onboarding incomplete' }}</p>
            </div>
            <div class="payout-detail-card">
              <span>Student</span>
              <strong>{{ $selectedPayout['student_name'] }}</strong>
              <p>{{ $selectedPayout['student_email'] }}</p>
            </div>
          </div>
        </div>

        <div class="payout-detail-section">
          <h4>Booking & Payment</h4>
          <div class="payout-detail-grid">
            <div class="payout-detail-card">
              <span>Booking</span>
              <strong>{{ $selectedPayout['booking_id'] ? '#'.$selectedPayout['booking_id'] : '-' }}</strong>
              <p>{{ $selectedPayout['service_name'] }}</p>
            </div>
            <div class="payout-detail-card">
              <span>Session</span>
              <strong>{{ $selectedPayout['booking_session_type'] }}</strong>
              <p>{{ $selectedPayout['booking_meeting_type'] }} · {{ $selectedPayout['booking_status'] }}</p>
            </div>
            <div class="payout-detail-card">
              <span>Payment</span>
              <strong>{{ $selectedPayout['payment_status'] }}</strong>
              <p>{{ $formatMoney($selectedPayout['payment_amount'], $selectedPayout['currency']) }} · completed {{ $formatDate($selectedPayout['payment_completed_at']) }}</p>
            </div>
          </div>
        </div>

        <div class="payout-detail-section">
          <h4>Amounts</h4>
          <div class="payout-detail-grid">
            <div class="payout-detail-card">
              <span>Gross</span>
              <strong>{{ $formatMoney($selectedPayout['gross_amount'], $selectedPayout['currency']) }}</strong>
            </div>
            <div class="payout-detail-card">
              <span>Mentor Share</span>
              <strong>{{ $formatMoney($selectedPayout['mentor_share_amount'], $selectedPayout['currency']) }}</strong>
            </div>
            <div class="payout-detail-card">
              <span>Platform Fee</span>
              <strong>{{ $formatMoney($selectedPayout['platform_fee_amount'], $selectedPayout['currency']) }}</strong>
            </div>
          </div>
        </div>

        <div class="payout-detail-section">
          <h4>Status Timeline</h4>
          <div class="payout-detail-grid">
            <div class="payout-detail-card"><span>Eligible</span><strong>{{ $formatDate($selectedPayout['eligible_at']) }}</strong></div>
            <div class="payout-detail-card"><span>Transferred</span><strong>{{ $formatDate($selectedPayout['transferred_at']) }}</strong></div>
            <div class="payout-detail-card"><span>Paid Out</span><strong>{{ $formatDate($selectedPayout['paid_out_at']) }}</strong></div>
            <div class="payout-detail-card"><span>Failed</span><strong>{{ $formatDate($selectedPayout['failed_at']) }}</strong></div>
            <div class="payout-detail-card"><span>Attempts</span><strong>{{ number_format((int) $selectedPayout['attempt_count']) }}</strong><p>Last {{ $formatDate($selectedPayout['last_attempt_at']) }}</p></div>
            <div class="payout-detail-card"><span>Failure Reason</span><strong>{{ $selectedPayout['failure_reason'] ?: '-' }}</strong></div>
          </div>
        </div>

        <div class="payout-detail-section">
          <h4>Stripe References</h4>
          <div class="payout-detail-grid">
            <div class="payout-detail-card"><span>Transfer ID</span><strong>{{ $selectedPayout['stripe_transfer_id'] ?: '-' }}</strong></div>
            <div class="payout-detail-card"><span>Balance Transaction</span><strong>{{ $selectedPayout['stripe_balance_transaction_id'] ?: '-' }}</strong></div>
            <div class="payout-detail-card"><span>Payment Intent</span><strong>{{ $selectedPayout['stripe_payment_intent_id'] ?: '-' }}</strong></div>
          </div>
        </div>

        <div class="payout-detail-section">
          <h4>Calculation Rule</h4>
          @if (! empty($selectedPayout['calculation_rule']))
            <div class="payout-rule-grid">
              @foreach ($selectedPayout['calculation_rule'] as $ruleItem)
                <div class="payout-detail-block">
                  <span>{{ $ruleItem['label'] }}</span>
                  <p>{{ $ruleItem['value'] }}</p>
                </div>
              @endforeach
            </div>
          @else
            <div class="payout-detail-block">
              <span>Rule</span>
              <p>No calculation rule was stored for this payout.</p>
            </div>
          @endif
        </div>
      </div>
    </div>
  @endif
@endsection
