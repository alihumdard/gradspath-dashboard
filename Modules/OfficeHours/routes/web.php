<?php

use Illuminate\Support\Facades\Route;
use Modules\OfficeHours\app\Http\Controllers\Mentor\OfficeHoursController as MentorOfficeHoursController;
use Modules\OfficeHours\app\Http\Controllers\Student\OfficeHoursController as StudentOfficeHoursController;

/*
|--------------------------------------------------------------------------
| OfficeHours Module — Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
    Route::get('/student/office-hours', [StudentOfficeHoursController::class, 'index'])->name('student.office-hours');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
    Route::get('/mentor/office-hours', [MentorOfficeHoursController::class, 'index'])->name('mentor.office-hours');
});
