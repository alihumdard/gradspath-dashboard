<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LandingPageController;

Route::get('/', [LandingPageController::class, 'index']);
Route::get('/home', [LandingPageController::class, 'home'])->name('public.home');
Route::get('/why-us', [LandingPageController::class, 'whyUs'])->name('public.why-us');

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
