<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\app\Http\Controllers\Mentor\MentorSettingsController;
use Modules\Settings\app\Http\Controllers\Student\StudentSettingsController;
use Modules\Settings\app\Http\Controllers\TimezoneController;

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
	Route::get('/student/settings', [StudentSettingsController::class, 'index'])->name('student.settings.index');
	Route::patch('/student/settings', [StudentSettingsController::class, 'update'])->name('student.settings.update');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
	Route::get('/mentor/settings', [MentorSettingsController::class, 'index'])->name('mentor.settings.index');
	Route::get('/mentor/settings/university-programs', [MentorSettingsController::class, 'universityPrograms'])->name('mentor.settings.university-programs');
	Route::patch('/mentor/settings', [MentorSettingsController::class, 'update'])->name('mentor.settings.update');
});

Route::middleware(['web', 'auth', 'active'])->group(function () {
	Route::post('/settings/timezone', [TimezoneController::class, 'store'])->name('settings.timezone.store');
});
