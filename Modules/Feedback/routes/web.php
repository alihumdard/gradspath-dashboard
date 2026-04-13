<?php

use Illuminate\Support\Facades\Route;
use Modules\Feedback\app\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use Modules\Feedback\app\Http\Controllers\Student\FeedbackController;

Route::middleware(['web', 'auth', 'active', 'role:student|mentor|admin'])->group(function () {
	Route::get('/student/feedback', [FeedbackController::class, 'index'])->name('student.feedback.index');
});

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
	Route::post('/student/feedback', [FeedbackController::class, 'store'])->name('student.feedback.store');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.feedback.')->group(function () {
	Route::patch('/feedback/{id}', [AdminFeedbackController::class, 'update'])->name('update');
	Route::delete('/feedback/{id}', [AdminFeedbackController::class, 'destroy'])->name('destroy');
});
