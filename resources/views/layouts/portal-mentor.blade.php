@extends('layouts.portal')

@section('portal_sidebar')
  @include('layouts.partials.portal.mentor-sidebar', [
      'activeNav' => trim($__env->yieldContent('portal_active_nav')),
  ])
@endsection

@section('portal_topbar_left')
@endsection

@section('portal_topbar_right')
  @yield('page_topbar_right')
@endsection

@section('portal_js')
  @yield('page_js')
@endsection
