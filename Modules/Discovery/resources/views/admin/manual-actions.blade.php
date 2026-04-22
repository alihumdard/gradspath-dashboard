@extends('discovery::admin.layouts.app')

@section('admin_title', 'Manual Controls')
@section('admin_heading', 'Manual Controls')
@section('admin_subtitle', 'Run audited admin actions for accounts, catalog records, pricing, and feedback from one consistent workspace.')

@section('admin_content')
  @include('discovery::admin.partials.manual-actions.hub', ['adminManualActionsData' => $adminManualActionsData ?? []])
@endsection
