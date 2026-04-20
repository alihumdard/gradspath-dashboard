<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\app\Http\Controllers\Admin\SupportTicketsController;
use Modules\Support\app\Http\Controllers\Mentor\TicketsController as MentorTicketsController;
use Modules\Support\app\Http\Controllers\Student\TicketsController as StudentTicketsController;

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
	Route::get('/student/support', [StudentTicketsController::class, 'index'])->name('student.support.index');
	Route::get('/student/support/my-tickets', [StudentTicketsController::class, 'myTickets'])->name('student.support.my-tickets');
	Route::post('/student/support', [StudentTicketsController::class, 'store'])->name('student.support.store');
	Route::get('/student/support/{id}', [StudentTicketsController::class, 'show'])->name('student.support.show');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
	Route::get('/mentor/support', [MentorTicketsController::class, 'index'])->name('mentor.support.index');
	Route::get('/mentor/support/my-tickets', [MentorTicketsController::class, 'myTickets'])->name('mentor.support.my-tickets');
	Route::post('/mentor/support', [MentorTicketsController::class, 'store'])->name('mentor.support.store');
	Route::get('/mentor/support/{id}', [MentorTicketsController::class, 'show'])->name('mentor.support.show');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.support.')->group(function () {
	Route::get('/tickets', [SupportTicketsController::class, 'index'])->name('tickets.index');
	Route::get('/tickets/{id}', [SupportTicketsController::class, 'show'])->name('tickets.show');
	Route::patch('/tickets/{id}', [SupportTicketsController::class, 'update'])->name('tickets.update');
});
