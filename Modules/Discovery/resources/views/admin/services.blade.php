@extends('discovery::admin.layouts.app')

@php
  $formatAdminMoney = static function ($amount): string {
    if ($amount === null) {
      return '-';
    }

    $precision = fmod((float) $amount, 1.0) === 0.0 ? 0 : 2;

    return '$' . number_format((float) $amount, $precision);
  };
@endphp

@section('admin_title', 'Services')
@section('admin_heading', 'Admin Services')
@section('admin_subtitle', 'Review service pricing, booking totals, and popularity.')

@section('admin_content')
  <div class="section-head">
    <div>
      <h2>Services</h2>
      <p>Fixed service pricing, meeting-size breakdown, and service performance.</p>
    </div>
    <button class="primary-btn" type="button">Export Services</button>
  </div>

  @if (session('success'))
    <div class="panel" style="margin-bottom: 20px; border: 1px solid #d4f2df; background: #f3fff7; color: #1d6b3d;">
      <strong>{{ session('success') }}</strong>
    </div>
  @endif

  <div class="table-wrap panel no-pad">
    <table id="servicesTable">
      <thead>
        <tr>
          <th>Service</th>
          <th>Format</th>
          <th>Set Price</th>
          <th>Bookings</th>
          <th>Revenue</th>
          <th>Mentors Offering</th>
          <th>Popularity Rank</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($adminServiceRows as $adminService)
          <tr>
            <td><strong>{{ $adminService['service_name'] ?: '-' }}</strong></td>
            <td>{{ $adminService['format'] ?: '-' }}</td>
            <td>{{ $adminService['set_price'] ?: '-' }}</td>
            <td><span data-booking-count-cell>{{ $adminService['bookings'] }}</span></td>
            <td>{{ $formatAdminMoney($adminService['revenue']) }}</td>
            <td>{{ $adminService['mentors_offering'] }}</td>
            <td>{{ $adminService['popularity_rank'] }}</td>
            <td>
              <div class="admin-bookings-actions">
                <button
                  class="ghost-btn admin-bookings-trigger admin-bookings-trigger--danger"
                  type="button"
                  data-entity-type="service"
                  data-entity-id="{{ $adminService['id'] }}"
                  data-entity-label="{{ $adminService['service_name'] }}"
                  data-booking-count="{{ $adminService['booking_count'] }}"
                  data-booking-mode="delete"
                  data-direct-delete-url="{{ route('admin.services.destroy', $adminService['id']) }}"
                  data-direct-delete-kind="service"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="chart-grid single-chart">
    <div class="panel chart-panel">
      <div class="panel-head">
        <h3>Service Popularity</h3>
        <span>by bookings</span>
      </div>
      <canvas id="servicesChart"></canvas>
    </div>
  </div>

  @include('discovery::admin.partials.bookings-manager-modal')
@endsection

@section('admin_page_data')
  @include('discovery::admin.partials.bookings-manager-page-data')
@endsection
