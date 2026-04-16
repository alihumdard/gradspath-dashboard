<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/university.json', function () {
    return response()->file(base_path('university.json'));
});

Route::get('/', function () {
    $user = Auth::user();

    if ($user?->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user?->hasRole('mentor')) {
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('mentor.dashboard');
    }

    if ($user?->hasRole('student')) {
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('student.dashboard');
    }

    return view('landing_page.index');
});
