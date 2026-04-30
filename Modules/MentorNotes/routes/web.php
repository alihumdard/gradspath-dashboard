<?php

use Modules\MentorNotes\app\Http\Controllers\Mentor\MentorNotesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MentorNotes Module — Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'active', 'role:mentor', 'mentor.approved'])->group(function () {
    Route::get('/mentor/notes', [MentorNotesController::class, 'index'])->name('mentor.notes');
    Route::get('/mentor/notes/bookings/{bookingId}', [MentorNotesController::class, 'edit'])->name('mentor.notes.bookings.edit');
    Route::post('/mentor/notes/bookings/{bookingId}', [MentorNotesController::class, 'store'])->name('mentor.notes.bookings.store');
});
