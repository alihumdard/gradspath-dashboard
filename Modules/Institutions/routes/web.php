<?php

use Illuminate\Support\Facades\Route;
use Modules\Institutions\app\Http\Controllers\Admin\InstitutionsController as AdminInstitutionsController;
use Modules\Institutions\app\Http\Controllers\Admin\UniversityProgramsController as AdminUniversityProgramsController;
use Modules\Institutions\app\Http\Controllers\Mentor\InstitutionsController as MentorInstitutionsController;
use Modules\Institutions\app\Http\Controllers\Student\InstitutionsController as StudentInstitutionsController;

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
	Route::get('/student/institutions', [StudentInstitutionsController::class, 'index'])->name('student.institutions.index');
	Route::get('/student/institutions/{id}', [StudentInstitutionsController::class, 'show'])->name('student.institutions.show');
});

Route::middleware(['web', 'auth', 'active', 'role:mentor'])->group(function () {
	Route::get('/mentor/institutions', [MentorInstitutionsController::class, 'index'])->name('mentor.institutions.index');
	Route::get('/mentor/institutions/{id}', [MentorInstitutionsController::class, 'show'])->name('mentor.institutions.show');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.institutions.')->group(function () {
	Route::get('/institutions', [AdminInstitutionsController::class, 'index'])->name('index');
	Route::post('/institutions', [AdminInstitutionsController::class, 'store'])->name('store');
	Route::patch('/institutions/{id}', [AdminInstitutionsController::class, 'update'])->name('update');
	Route::delete('/institutions/{id}', [AdminInstitutionsController::class, 'destroy'])->name('destroy');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin/manual-actions')->name('admin.manual-actions.')->group(function () {
	Route::post('/institutions', [AdminInstitutionsController::class, 'store'])->name('institutions.store');
	Route::get('/universities/search', [AdminUniversityProgramsController::class, 'searchUniversities'])->name('universities.search');
	Route::post('/programs', [AdminUniversityProgramsController::class, 'store'])->name('programs.store');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.programs.')->group(function () {
	Route::get('/programs', [AdminUniversityProgramsController::class, 'index'])->name('index');
	Route::post('/programs', [AdminUniversityProgramsController::class, 'store'])->name('store');
	Route::patch('/programs/{id}', [AdminUniversityProgramsController::class, 'update'])->name('update');
	Route::delete('/programs/{id}', [AdminUniversityProgramsController::class, 'destroy'])->name('destroy');
});
