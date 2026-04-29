<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

Route::view('/terms', 'public_pages.terms')->name('public.terms');
Route::view('/privacy', 'public_pages.privacy')->name('public.privacy');
Route::view('/support', 'public_pages.support')->name('public.support');
Route::view('/zoom-app-guide', 'public_pages.zoom-app-guide')->name('public.zoom-app-guide');
