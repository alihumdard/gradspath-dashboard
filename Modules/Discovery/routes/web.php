<?php

use Illuminate\Support\Facades\Route;
use Modules\Discovery\app\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use Modules\Discovery\app\Http\Controllers\Mentor\DashboardController as MentorDashboardController;
use Modules\Discovery\app\Http\Controllers\Mentor\MentorSearchController as MentorMentorSearchController;
use Modules\Discovery\app\Http\Controllers\Student\DashboardController;
use Modules\Discovery\app\Http\Controllers\Student\MentorSearchController as StudentMentorSearchController;

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
    Route::get('/mentor/dashboard', [MentorDashboardController::class, 'index'])->name('mentor.dashboard');
    Route::get('/mentor/mentors', [MentorMentorSearchController::class, 'index'])->name('mentor.mentors.index');
    Route::get('/mentor/mentors/{id}', [MentorMentorSearchController::class, 'show'])->name('mentor.mentors.show');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/overview', [AdminDashboardController::class, 'overview'])->name('overview');
    Route::get('/dashboard/users', [AdminDashboardController::class, 'users'])->name('users');
    Route::get('/dashboard/mentors', [AdminDashboardController::class, 'mentors'])->name('mentors');
    Route::get('/dashboard/services', [AdminDashboardController::class, 'services'])->name('services');
    Route::get('/dashboard/revenue', [AdminDashboardController::class, 'revenue'])->name('revenue');
    Route::get('/dashboard/rankings', [AdminDashboardController::class, 'rankings'])->name('rankings');
    Route::get('/dashboard/manual-actions', [AdminDashboardController::class, 'manualActions'])->name('manual-actions');
});

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
    Route::get('/student/dashboard', [DashboardController::class, 'index'])->name('student.dashboard');
});

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
    Route::get('/student/explore', [StudentMentorSearchController::class, 'index'])->name('student.explore');
    Route::get('/student/mentors', [StudentMentorSearchController::class, 'index'])->name('student.mentors.index');
    Route::get('/student/mentors/{id}', [StudentMentorSearchController::class, 'show'])->name('student.mentors.show');
});
