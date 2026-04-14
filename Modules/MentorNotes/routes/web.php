<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MentorNotes Module — Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
    Route::view('/mentor/notes', 'mentornotes::mentor.notes')->name('mentor.notes');
});
