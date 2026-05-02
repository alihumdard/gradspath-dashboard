<?php

use Illuminate\Support\Facades\Route;
use Modules\Feedback\app\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use Modules\Feedback\app\Http\Controllers\Student\FeedbackController as StudentFeedbackController;

Route::middleware(['web', 'auth', 'active'])->group(function () {
	Route::get('/feedback', [StudentFeedbackController::class, 'index'])->name('feedback.index');

	Route::get('/student/feedback', [StudentFeedbackController::class, 'index'])->name('student.feedback.index');
});

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
	Route::post('/student/feedback', [StudentFeedbackController::class, 'store'])->name('student.feedback.store');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.feedback.')->group(function () {
	Route::patch('/feedback/{id}', [AdminFeedbackController::class, 'update'])->name('update');
	Route::delete('/feedback/{id}', [AdminFeedbackController::class, 'destroy'])->name('destroy');
});

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin/manual-actions')->name('admin.manual-actions.')->group(function () {
	Route::patch('/feedback', [AdminFeedbackController::class, 'update'])->name('feedback.update');
});
