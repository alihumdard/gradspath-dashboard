<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\app\Http\Controllers\Admin\ManualActionsController;
use Modules\Payments\app\Http\Controllers\Admin\ServicesController;
use Modules\Payments\app\Http\Controllers\Payments\StripeWebhookController;
use Modules\Payments\app\Http\Controllers\Student\BookingCheckoutController;
use Modules\Payments\app\Http\Controllers\Student\CreditsController;

Route::middleware(['web', 'auth', 'active', 'role:student'])->group(function () {
	Route::get('/student/store', [CreditsController::class, 'index'])->name('student.store');
	Route::get('/student/credits/balance', [CreditsController::class, 'balance'])->name('student.credits.balance');
	Route::post('/student/store/purchase', [CreditsController::class, 'purchase'])->name('student.store.purchase');
	Route::post('/student/store/checkout', [CreditsController::class, 'checkout'])->name('student.store.checkout');
	Route::get('/student/store/success', [CreditsController::class, 'success'])->name('student.store.success');
	Route::post('/student/bookings/checkout', [BookingCheckoutController::class, 'store'])->name('student.bookings.checkout.store');
	Route::get('/student/bookings/checkout/success', [BookingCheckoutController::class, 'success'])->name('student.bookings.checkout.success');
});

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
	Route::get('/services', [ServicesController::class, 'index'])->name('services.index');
	Route::post('/services', [ServicesController::class, 'store'])->name('services.store');
	Route::patch('/services/{id}', [ServicesController::class, 'update'])->name('services.update');

	Route::post('/manual/credits/adjust', [ManualActionsController::class, 'adjustCredits'])->name('manual.credits.adjust');
	Route::post('/manual/mentors/amend', [ManualActionsController::class, 'amendMentor'])->name('manual.mentors.amend');
});
