<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\app\Http\Controllers\Mentor\MentorSettingsController;
use Modules\Settings\app\Http\Controllers\Student\StudentSettingsController;
use Modules\Settings\app\Http\Controllers\TimezoneController;

Route::middleware(['web', 'auth', 'active', 'role:student', 'feedback.required'])->group(function () {
	Route::get('/student/settings', [StudentSettingsController::class, 'index'])->name('student.settings.index');
	Route::patch('/student/settings', [StudentSettingsController::class, 'update'])->name('student.settings.update');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor', 'mentor.notes.required'])->group(function () {
	Route::get('/mentor/settings', [MentorSettingsController::class, 'index'])->name('mentor.settings.index');
	Route::get('/mentor/settings/university-programs', [MentorSettingsController::class, 'universityPrograms'])->name('mentor.settings.university-programs');
	Route::middleware('mentor.approved')->group(function () {
		Route::get('/mentor/settings/zoom/connect', [MentorSettingsController::class, 'connectZoom'])->name('mentor.settings.zoom.connect');
		Route::get('/mentor/settings/zoom/callback', [MentorSettingsController::class, 'handleZoomCallback'])->name('mentor.settings.zoom.callback');
		Route::delete('/mentor/settings/zoom', [MentorSettingsController::class, 'disconnectZoom'])->name('mentor.settings.zoom.disconnect');
		Route::patch('/mentor/settings', [MentorSettingsController::class, 'update'])->name('mentor.settings.update');
	});
});

Route::middleware(['web', 'auth', 'active'])->group(function () {
	Route::post('/settings/timezone', [TimezoneController::class, 'store'])->name('settings.timezone.store');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])
	->prefix(config('auth.admin_path'))
	->name('admin.')
	->group(function () {
		Route::get('/settings', [Modules\Settings\app\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('settings');
		Route::patch('/settings/email', [Modules\Settings\app\Http\Controllers\Admin\AdminSettingsController::class, 'updateEmail'])->name('settings.update-email');
		Route::patch('/settings/password', [Modules\Settings\app\Http\Controllers\Admin\AdminSettingsController::class, 'updatePassword'])->name('settings.update-password');
	});

