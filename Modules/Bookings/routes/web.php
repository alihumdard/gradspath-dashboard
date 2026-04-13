<?php

use Illuminate\Support\Facades\Route;
use Modules\Bookings\app\Http\Controllers\Mentor\BookingsController;
use Modules\Bookings\app\Http\Controllers\Student\BookingController;

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
	Route::get('/student/bookings', [BookingController::class, 'index'])->name('student.bookings.index');
	Route::get('/student/bookings/create', [BookingController::class, 'create'])->name('student.bookings.create');
	Route::get('/student/book-mentor/{id}', [BookingController::class, 'create'])->name('student.book-mentor');
	Route::get('/student/mentor/{id}/book', [BookingController::class, 'create'])->name('student.mentor.book');
	Route::post('/student/bookings', [BookingController::class, 'store'])->name('student.bookings.store');
	Route::get('/student/bookings/{id}', [BookingController::class, 'show'])->middleware('booking.participant')->name('student.bookings.show');
	Route::patch('/student/bookings/{id}/cancel', [BookingController::class, 'cancel'])->middleware('booking.participant')->name('student.bookings.cancel');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
	Route::get('/mentor/bookings', [BookingsController::class, 'index'])->name('mentor.bookings.index');
	Route::get('/mentor/bookings/{id}', [BookingsController::class, 'show'])->middleware('booking.participant')->name('mentor.bookings.show');
});
