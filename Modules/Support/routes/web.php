<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\app\Http\Controllers\Admin\SupportTicketsController;
use Modules\Support\app\Http\Controllers\Support\TicketsController;

Route::middleware(['web', 'auth', 'active', 'role:student|mentor'])->group(function () {
	Route::get('/student/support', [TicketsController::class, 'index'])->name('student.support.index');
	Route::get('/student/support/my-tickets', [TicketsController::class, 'myTickets'])->name('student.support.my-tickets');
	Route::post('/student/support', [TicketsController::class, 'store'])->name('student.support.store');
	Route::get('/student/support/{id}', [TicketsController::class, 'show'])->name('student.support.show');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.support.')->group(function () {
	Route::get('/tickets', [SupportTicketsController::class, 'index'])->name('tickets.index');
	Route::get('/tickets/{id}', [SupportTicketsController::class, 'show'])->name('tickets.show');
	Route::patch('/tickets/{id}', [SupportTicketsController::class, 'update'])->name('tickets.update');
});
