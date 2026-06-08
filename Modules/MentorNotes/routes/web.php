<?php

use Illuminate\Support\Facades\Route;
use Modules\MentorNotes\app\Http\Controllers\Mentor\MentorNotesController as MentorMentorNotesController;

/*
|--------------------------------------------------------------------------
| MentorNotes Module — Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'active', 'role:mentor', 'mentor.approved'])->group(function () {
    Route::get('/mentor/notes', [MentorMentorNotesController::class, 'index'])->name('mentor.notes');
    Route::get('/mentor/notes/bookings/{bookingId}', [MentorMentorNotesController::class, 'edit'])->name('mentor.notes.bookings.edit');
    Route::post('/mentor/notes/bookings/{bookingId}', [MentorMentorNotesController::class, 'store'])->name('mentor.notes.bookings.store');
});
