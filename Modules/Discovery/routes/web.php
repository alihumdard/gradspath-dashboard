<?php

use Illuminate\Support\Facades\Route;

// Mentor Dashboard
Route::get('/mentor/dashboard', function () {
    return view('discovery::mentor.dashboard');
})->name('mentor.dashboard')->middleware('auth');

// Student pages
Route::get('/student/dashboard', function () {
    return view('discovery::student.dashboard');
})->name('student.dashboard');

Route::get('/student/explore', function () {
    return view('discovery::student.explore');
})->name('student.explore')->middleware('auth');
