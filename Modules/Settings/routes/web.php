<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\app\Http\Controllers\Student\SettingsController;

Route::middleware(['web', 'auth', 'active', 'role:student|mentor|admin'])->group(function () {
	Route::get('/student/settings', [SettingsController::class, 'index'])->name('student.settings.index');
	Route::patch('/student/settings', [SettingsController::class, 'update'])->name('student.settings.update');
});
