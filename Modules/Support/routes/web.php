<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\app\Http\Controllers\Admin\SupportTicketsController;
use Modules\Support\app\Http\Controllers\Mentor\TicketsController as MentorTicketsController;
use Modules\Support\app\Http\Controllers\Student\TicketsController as StudentTicketsController;

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
	Route::get('/student/support', [StudentTicketsController::class, 'index'])->name('student.support.index');
	Route::post('/student/support', [StudentTicketsController::class, 'store'])->middleware('throttle:5,60')->name('student.support.store');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
	Route::get('/mentor/support', [MentorTicketsController::class, 'index'])->name('mentor.support.index');
	Route::post('/mentor/support', [MentorTicketsController::class, 'store'])->middleware('throttle:5,60')->name('mentor.support.store');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.support.')->group(function () {
	Route::get('/tickets', [SupportTicketsController::class, 'index'])->name('tickets.index');
	Route::get('/tickets/{id}', [SupportTicketsController::class, 'show'])->name('tickets.show');
	Route::patch('/tickets/{id}', [SupportTicketsController::class, 'update'])->name('tickets.update');
});
