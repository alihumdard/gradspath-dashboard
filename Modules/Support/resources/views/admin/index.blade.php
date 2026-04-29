@extends('discovery::admin.layouts.app')

@php
  $statusLabels = [
    'open' => 'Open',
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'more_information_required' => 'More Information Required',
    'resolved' => 'Resolved',
    'closed' => 'Closed',
  ];

  $statusFilters = ['' => 'All'] + $statusLabels;
  $selectedStatus = $selectedStatus ?? '';
  $searchTerm = $searchTerm ?? '';
  $selectedTicket = $selectedSupportTicket ?? null;
@endphp

@section('admin_title', 'Support Tickets')
@section('admin_heading', 'Support Tickets')
@section('admin_subtitle', 'Review user tickets, send admin replies, and update resolution status.')

@section('admin_head')
  <style>
    .support-admin-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 14px;
    }

    .support-admin-toolbar input,
    .support-admin-toolbar select {
      height: 46px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.04);
      color: var(--text);
      padding: 0 12px;
      outline: none;
    }

    .support-admin-toolbar input {
      flex: 1 1 240px;
    }

    .support-admin-toolbar select {
      color-scheme: dark;
      background-color: #202534;
    }

    .support-admin-status {
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

    .support-admin-status--open {
      border-color: rgba(98, 169, 255, 0.28);
      background: rgba(98, 169, 255, 0.12);
      color: #b9d8ff;
    }

    .support-admin-status--pending,
    .support-admin-status--more_information_required {
      border-color: rgba(242, 166, 63, 0.32);
      background: rgba(242, 166, 63, 0.12);
      color: #ffd89c;
    }

    .support-admin-status--in_progress {
      border-color: rgba(167, 139, 250, 0.3);
      background: rgba(167, 139, 250, 0.12);
      color: #ddd1ff;
    }

    .support-admin-status--resolved {
      border-color: rgba(34, 199, 122, 0.3);
      background: rgba(34, 199, 122, 0.12);
      color: #95efbc;
    }

    .support-admin-status--closed {
      color: var(--muted);
    }

    .support-ticket-link {
      color: var(--text);
      text-decoration: none;
    }

    .support-ticket-link:hover {
      color: #b9d8ff;
    }

    .support-admin-detail {
      display: grid;
      gap: 16px;
    }

    .support-admin-modal {
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

    .support-admin-modal__dialog {
      width: min(980px, 100%);
      max-height: min(860px, calc(100vh - 48px));
      overflow-y: auto;
      padding: 20px;
    }

    .support-admin-modal__head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
    }

    .support-admin-modal__title {
      display: grid;
      gap: 6px;
    }

    .support-admin-modal__title h3 {
      margin: 0;
      font-size: 28px;
    }

    .support-admin-modal__title span {
      color: var(--muted);
      line-height: 1.5;
    }

    .support-admin-modal__actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    .support-admin-modal__close {
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

    .support-admin-modal__close:hover {
      background: rgba(255, 255, 255, 0.08);
    }

    .support-admin-meta {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .support-admin-meta div,
    .support-admin-message {
      border: 1px solid var(--border);
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.03);
      padding: 14px;
    }

    .support-admin-meta span,
    .support-admin-message span,
    .support-admin-form label span {
      display: block;
      margin-bottom: 7px;
      color: var(--muted);
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .support-admin-meta strong,
    .support-admin-message p {
      margin: 0;
      line-height: 1.55;
    }

    .support-admin-form {
      display: grid;
      gap: 14px;
    }

    .support-admin-form textarea,
    .support-admin-form select {
      width: 100%;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.04);
      color: var(--text);
      padding: 14px 15px;
      outline: none;
    }

    .support-admin-form select {
      color-scheme: dark;
      background-color: #202534;
    }

    .support-admin-form textarea {
      min-height: 150px;
      resize: vertical;
    }

    .support-admin-form textarea:focus,
    .support-admin-form select:focus,
    .support-admin-toolbar input:focus,
    .support-admin-toolbar select:focus {
      border-color: rgba(98, 169, 255, 0.35);
      box-shadow: 0 0 0 3px rgba(98, 169, 255, 0.14);
    }

    .support-admin-empty {
      margin: 0;
      color: var(--muted);
      line-height: 1.6;
    }

    .support-admin-pagination {
      margin-top: 14px;
    }

    @media (max-width: 640px) {
      .support-admin-modal {
        padding: 14px;
        place-items: stretch;
      }

      .support-admin-modal__dialog {
        max-height: calc(100vh - 28px);
      }

      .support-admin-modal__head {
        flex-direction: column;
      }

      .support-admin-modal__actions {
        width: 100%;
        justify-content: space-between;
      }

      .support-admin-meta {
        grid-template-columns: 1fr;
      }
    }
  </style>
@endsection

@section('admin_content')
  <div class="kpi-grid">
    <div class="kpi-card">
      <span>All Tickets</span>
      <strong>{{ number_format((int) ($supportTicketCounts['all'] ?? 0)) }}</strong>
      <small>Total submitted</small>
    </div>
    <div class="kpi-card">
      <span>Open</span>
      <strong>{{ number_format((int) ($supportTicketCounts['open'] ?? 0)) }}</strong>
      <small>Awaiting triage</small>
    </div>
    <div class="kpi-card">
      <span>Pending</span>
      <strong>{{ number_format((int) ($supportTicketCounts['pending'] ?? 0)) }}</strong>
      <small>Queued for action</small>
    </div>
    <div class="kpi-card">
      <span>In Progress</span>
      <strong>{{ number_format((int) ($supportTicketCounts['in_progress'] ?? 0)) }}</strong>
      <small>Being handled</small>
    </div>
    <div class="kpi-card">
      <span>Needs Info</span>
      <strong>{{ number_format((int) ($supportTicketCounts['more_information_required'] ?? 0)) }}</strong>
      <small>User follow-up needed</small>
    </div>
    <div class="kpi-card">
      <span>Resolved</span>
      <strong>{{ number_format((int) ($supportTicketCounts['resolved'] ?? 0)) }}</strong>
      <small>Marked resolved</small>
    </div>
  </div>

  <section class="panel">
    <div class="section-head">
      <div>
        <h2>Tickets</h2>
        <p>Search by ticket ref, user, email, or subject.</p>
      </div>
    </div>

    <form class="support-admin-toolbar" method="GET" action="{{ route('admin.support.tickets.index') }}">
      <input type="search" name="q" value="{{ $searchTerm }}" placeholder="Search tickets" />
      <select name="status" aria-label="Filter by status">
        @foreach ($statusFilters as $value => $label)
          <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <button class="primary-btn" type="submit">Filter</button>
      @if ($selectedStatus !== '' || $searchTerm !== '')
        <a class="ghost-btn" href="{{ route('admin.support.tickets.index') }}">Clear</a>
      @endif
    </form>

    <div class="table-wrap">
      <table style="min-width: 820px;">
        <thead>
          <tr>
            <th>Ticket</th>
            <th>User</th>
            <th>Status</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($supportTickets as $ticket)
            <tr>
              <td>
                <a class="support-ticket-link" href="{{ route('admin.support.tickets.show', $ticket->id) }}">
                  <strong>{{ $ticket->ticket_ref }}</strong>
                  <span>{{ $ticket->subject }}</span>
                </a>
              </td>
              <td>
                <strong>{{ $ticket->user?->name ?? 'Deleted user' }}</strong>
                <span>{{ $ticket->user?->email ?? '-' }}</span>
              </td>
              <td>
                <span class="support-admin-status support-admin-status--{{ $ticket->status }}">
                  {{ $statusLabels[$ticket->status] ?? ucfirst((string) $ticket->status) }}
                </span>
              </td>
              <td>
                <strong>{{ $ticket->created_at?->format('M j, Y') }}</strong>
                <span>{{ $ticket->created_at?->format('g:i A') }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4">No support tickets match this view.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="support-admin-pagination">
      {{ $supportTickets->links() }}
    </div>
  </section>

  @if ($selectedTicket)
    <div class="support-admin-modal" role="dialog" aria-modal="true" aria-labelledby="supportTicketDialogTitle">
      <div class="panel support-admin-detail support-admin-modal__dialog">
        <div class="support-admin-modal__head">
          <div class="support-admin-modal__title">
            <h3 id="supportTicketDialogTitle">{{ $selectedTicket->ticket_ref }}</h3>
            <span>{{ $selectedTicket->subject }}</span>
          </div>
          <div class="support-admin-modal__actions">
          <span class="support-admin-status support-admin-status--{{ $selectedTicket->status }}">
            {{ $statusLabels[$selectedTicket->status] ?? ucfirst((string) $selectedTicket->status) }}
          </span>
            <a class="support-admin-modal__close" href="{{ route('admin.support.tickets.index') }}" aria-label="Close support ticket dialog">&times;</a>
          </div>
        </div>

        <div class="support-admin-meta">
          <div>
            <span>User</span>
            <strong>{{ $selectedTicket->user?->name ?? 'Deleted user' }}</strong>
            <p class="support-admin-empty">{{ $selectedTicket->user?->email ?? '-' }}</p>
          </div>
          <div>
            <span>Created</span>
            <strong>{{ $selectedTicket->created_at?->format('M j, Y g:i A') }}</strong>
            <p class="support-admin-empty">Last updated {{ $selectedTicket->updated_at?->diffForHumans() }}</p>
          </div>
          <div>
            <span>Handled By</span>
            <strong>{{ $selectedTicket->handler?->name ?? 'Unassigned' }}</strong>
            <p class="support-admin-empty">{{ $selectedTicket->replied_at ? $selectedTicket->replied_at->format('M j, Y g:i A') : 'No reply yet' }}</p>
          </div>
          <div>
            <span>Current Status</span>
            <strong>{{ $statusLabels[$selectedTicket->status] ?? ucfirst((string) $selectedTicket->status) }}</strong>
            <p class="support-admin-empty">Visible to the user</p>
          </div>
        </div>

        <div class="support-admin-message">
          <span>User Message</span>
          <p>{{ $selectedTicket->message }}</p>
        </div>

        <form class="support-admin-form" method="POST" action="{{ route('admin.support.tickets.update', $selectedTicket->id) }}">
          @csrf
          @method('PATCH')

          <label>
            <span>Admin Reply</span>
            <textarea name="admin_reply">{{ old('admin_reply', $selectedTicket->admin_reply) }}</textarea>
            @error('admin_reply')
              <small>{{ $message }}</small>
            @enderror
          </label>

          <label>
            <span>Status</span>
            <select name="status" required>
              @foreach ($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $selectedTicket->status) === $value)>{{ $label }}</option>
              @endforeach
            </select>
            @error('status')
              <small>{{ $message }}</small>
            @enderror
          </label>

          <button class="primary-btn" type="submit">Save Reply</button>
        </form>
      </div>
    </div>
  @endif
@endsection
