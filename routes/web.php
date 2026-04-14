<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Serve the local university dataset for registration autocomplete
Route::get('/university.json', function () {
    return response()->file(base_path('university.json'));
});

// Root landing page
Route::get('/', function () {
    $user = Auth::user();

    if ($user?->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user?->hasRole('mentor')) {
        return redirect()->route('mentor.dashboard');
    }

    if ($user?->hasRole('student')) {
        return redirect()->route('student.dashboard');
    }

    return view('landing_page.index');
});
