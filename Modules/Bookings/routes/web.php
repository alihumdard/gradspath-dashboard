<?php

use Illuminate\Support\Facades\Route;
use Modules\Bookings\app\Http\Controllers\Admin\BookingOutcomeController;
use Modules\Bookings\app\Http\Controllers\BookingChatController;
use Modules\Bookings\app\Http\Controllers\Mentor\AvailabilityController as MentorAvailabilityController;
use Modules\Bookings\app\Http\Controllers\Mentor\BookingController as MentorBookingController;
use Modules\Bookings\app\Http\Controllers\Mentor\BookingsController;
use Modules\Bookings\app\Http\Controllers\Student\BookingAvailabilityController;
use Modules\Bookings\app\Http\Controllers\Student\BookingController;
use Modules\Bookings\app\Http\Controllers\Webhooks\ZoomWebhookController;

Route::post('/webhooks/zoom', [ZoomWebhookController::class, 'handle'])->name('webhooks.zoom');

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
    Route::get('/student/bookings', [BookingController::class, 'index'])->name('student.bookings.index');
    Route::get('/student/bookings/availability/months', [BookingAvailabilityController::class, 'months'])->name('student.bookings.availability.months');
    Route::get('/student/bookings/availability/days', [BookingAvailabilityController::class, 'days'])->name('student.bookings.availability.days');
    Route::get('/student/bookings/availability/times', [BookingAvailabilityController::class, 'times'])->name('student.bookings.availability.times');
    Route::get('/student/bookings/create', [BookingController::class, 'create'])->name('student.bookings.create');
    Route::get('/student/book-mentor/{id}', [BookingController::class, 'create'])->name('student.book-mentor');
    Route::get('/student/mentor/{id}/book', [BookingController::class, 'create'])->name('student.mentor.book');
    Route::post('/student/bookings', [BookingController::class, 'store'])->name('student.bookings.store');
    Route::get('/student/bookings/{id}', [BookingController::class, 'show'])->middleware('booking.participant')->name('student.bookings.show');
    Route::patch('/student/bookings/{id}/cancel', [BookingController::class, 'cancel'])->middleware('booking.participant')->name('student.bookings.cancel');
    Route::get('/student/bookings/{id}/chat', [BookingChatController::class, 'index'])->middleware('booking.participant')->name('student.bookings.chat.index');
    Route::post('/student/bookings/{id}/chat', [BookingChatController::class, 'store'])->middleware('booking.participant')->name('student.bookings.chat.store');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
    Route::get('/mentor/bookings/availability/months', [BookingAvailabilityController::class, 'months'])->name('mentor.bookings.availability.months');
    Route::get('/mentor/bookings/availability/days', [BookingAvailabilityController::class, 'days'])->name('mentor.bookings.availability.days');
    Route::get('/mentor/bookings/availability/times', [BookingAvailabilityController::class, 'times'])->name('mentor.bookings.availability.times');
    Route::get('/mentor/bookings/create', [MentorBookingController::class, 'create'])->name('mentor.bookings.create');
    Route::get('/mentor/book-mentor/{id}', [MentorBookingController::class, 'create'])->name('mentor.book-mentor');
    Route::get('/mentor/mentor/{id}/book', [MentorBookingController::class, 'create'])->name('mentor.mentor.book');
    Route::post('/mentor/bookings', [MentorBookingController::class, 'store'])->name('mentor.bookings.store');
    Route::get('/mentor/bookings', [BookingsController::class, 'index'])->name('mentor.bookings.index');
    Route::get('/mentor/bookings/{id}', [BookingsController::class, 'show'])->middleware('booking.participant')->name('mentor.bookings.show');
    Route::patch('/mentor/bookings/{id}/cancel', [MentorBookingController::class, 'cancel'])->middleware('booking.participant')->name('mentor.bookings.cancel');
    Route::get('/mentor/bookings/{id}/chat', [BookingChatController::class, 'index'])->middleware('booking.participant')->name('mentor.bookings.chat.index');
    Route::post('/mentor/bookings/{id}/chat', [BookingChatController::class, 'store'])->middleware('booking.participant')->name('mentor.bookings.chat.store');
    Route::get('/mentor/availability', [MentorAvailabilityController::class, 'index'])->name('mentor.availability.index');
    Route::patch('/mentor/availability', [MentorAvailabilityController::class, 'update'])->name('mentor.availability.update');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin/manual-actions')->name('admin.manual-actions.')->group(function () {
    Route::patch('/bookings/outcome', [BookingOutcomeController::class, 'update'])->name('bookings.outcome.update');
});
