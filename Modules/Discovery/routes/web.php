<?php

use Illuminate\Support\Facades\Route;
use Modules\Discovery\app\Http\Controllers\Student\DashboardController;
use Modules\Discovery\app\Http\Controllers\Student\MentorSearchController;

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
    Route::get('/mentor/dashboard', function () {
        return view('discovery::mentor.dashboard');
    })->name('mentor.dashboard');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'discovery::admin.admin')->name('dashboard');
});

Route::middleware(['web', 'auth', 'active', 'role:student|mentor|admin'])->group(function () {
    Route::get('/student/dashboard', [DashboardController::class, 'index'])->name('student.dashboard');
    Route::get('/student/explore', [MentorSearchController::class, 'index'])->name('student.explore');
    Route::get('/student/mentors', [MentorSearchController::class, 'index'])->name('student.mentors.index');
    Route::get('/student/mentors/{id}', [MentorSearchController::class, 'show'])->name('student.mentors.show');
});
