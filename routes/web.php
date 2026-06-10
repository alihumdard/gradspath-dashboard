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

Route::get('/home', function () {
    return view('landing_page.index');
})->name('public.home');

Route::view('/terms', 'public_pages.terms')->name('public.terms');
Route::view('/privacy', 'public_pages.privacy')->name('public.privacy');
Route::get('/support', function () {
    $user = Auth::user();

    if ($user?->hasRole('student')) {
        return redirect()->route('student.support.index');
    }

    if ($user?->hasRole('mentor')) {
        return redirect()->route('mentor.support.index');
    }

    if ($user?->hasRole('admin')) {
        return redirect()->route('admin.support.tickets.index');
    }

    return view('public_pages.support');
})->name('public.support');
Route::view('/zoom-app-guide', 'public_pages.zoom-app-guide')->name('public.zoom-app-guide');
