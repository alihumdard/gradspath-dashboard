@extends('layouts.app')
@section('title', 'Mentor Profile - Grads Paths')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/demo1.css') }}" />@endsection
@section('content')<div class="app-shell"><main class="main-content"><h1>{{ $mentor['name'] ?? 'Mentor' }}</h1><p>{{ $mentor['role'] ?? 'Mentor Role' }}</p><p>{{ $mentor['bio'] ?? 'Mentor biography' }}</p><a href="{{ route('student.book-mentor', $mentor['id'] ?? 1) }}" class="book-btn">Book Now</a></main></div>@endsection
@section('js')<script src="{{ asset('assets/js/demo1.js') }}"></script>@endsection
