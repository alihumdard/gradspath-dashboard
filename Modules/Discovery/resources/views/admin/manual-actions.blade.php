@extends('discovery::admin.layouts.app')

@section('admin_title', 'Manual Actions')
@section('hide_admin_topbar', '1')

@section('admin_content')
  @php($manualSectionClass = 'manual-page-section')
  @include('discovery::admin.manualaction')
@endsection
