@extends('layouts.portal-mentor')

@section('title', 'Institution - Grads Paths')
@section('portal_css_asset', 'assets/css/institutions.css')
@section('portal_js_asset', 'assets/js/institutions.js')
@section('portal_active_nav', 'institutions')

@section('portal_content')
  <div class="page-wrap">
    <div class="top-bar">
      <h1>{{ $institution['name'] ?? 'Institution' }}</h1>
    </div>

    <p class="intro-text">Mentors from this institution</p>
  </div>
@endsection
