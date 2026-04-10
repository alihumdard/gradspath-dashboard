@extends('layouts.app')
@section('title', 'Institution - Grads Paths')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/demo3a.css') }}" />@endsection
@section('content')<div class="app-shell"><main class="main-content"><h1>{{ $institution['name'] ?? 'Institution' }}</h1><p>Mentors from this institution</p></main></div>@endsection
@section('js')<script src="{{ asset('assets/js/demo3a.js') }}"></script>@endsection
