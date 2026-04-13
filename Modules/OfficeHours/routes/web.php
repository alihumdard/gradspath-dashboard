<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| OfficeHours Module — Web Routes
|--------------------------------------------------------------------------
*/

// Add office hours routes here
Route::middleware(['web', 'auth', 'active', 'role:student|mentor'])->group(function () {
    // Keep the existing student URL working for both students and mentors.
    Route::get('/student/office-hours', function () {
        return Auth::user()?->hasRole('mentor')
            ? view('office-hours::mentor.schedules')
            : view('office-hours::student.index');
    })->name('student.office-hours');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
    Route::view('/mentor/office-hours', 'office-hours::mentor.schedules')->name('mentor.office-hours');
});
