@extends('layouts.portal-student')

@section('title', 'Support - Grads Paths')
@section('portal_css_asset', 'assets/css/demo15.css')
@section('portal_active_nav', 'support')

@section('page_topbar_left')
  <div class="search-wrap">
    <input
      type="text"
      class="search-input"
      placeholder="Search mentors, universities..."
    />
  </div>
@endsection

@section('portal_content')
  @include('support::partials.form-content')
@endsection

@section('page_js')
  <script src="{{ asset('assets/js/demo15.js') }}"></script>
@endsection
