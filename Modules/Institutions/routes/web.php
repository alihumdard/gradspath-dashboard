<?php

use Illuminate\Support\Facades\Route;
use Modules\Institutions\app\Http\Controllers\Admin\InstitutionsController as AdminInstitutionsController;
use Modules\Institutions\app\Http\Controllers\Admin\UniversityProgramsController as AdminUniversityProgramsController;
use Modules\Institutions\app\Http\Controllers\Student\InstitutionsController;

Route::middleware(['web', 'auth', 'active', 'role:student|mentor|admin'])->group(function () {
	Route::get('/student/institutions', [InstitutionsController::class, 'index'])->name('student.institutions.index');
	Route::get('/student/institutions/{id}', [InstitutionsController::class, 'show'])->name('student.institutions.show');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.institutions.')->group(function () {
	Route::get('/institutions', [AdminInstitutionsController::class, 'index'])->name('index');
	Route::post('/institutions', [AdminInstitutionsController::class, 'store'])->name('store');
	Route::patch('/institutions/{id}', [AdminInstitutionsController::class, 'update'])->name('update');
	Route::delete('/institutions/{id}', [AdminInstitutionsController::class, 'destroy'])->name('destroy');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.programs.')->group(function () {
	Route::get('/programs', [AdminUniversityProgramsController::class, 'index'])->name('index');
	Route::post('/programs', [AdminUniversityProgramsController::class, 'store'])->name('store');
	Route::patch('/programs/{id}', [AdminUniversityProgramsController::class, 'update'])->name('update');
	Route::delete('/programs/{id}', [AdminUniversityProgramsController::class, 'destroy'])->name('destroy');
});
