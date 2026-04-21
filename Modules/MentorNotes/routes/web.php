<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MentorNotes Module — Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
    Route::view('/mentor/notes', 'mentor-notes::mentor.notes')->name('mentor.notes');
});
