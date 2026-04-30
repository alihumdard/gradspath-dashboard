<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\app\Http\Controllers\Admin\ManualActionsController;
use Modules\Payments\app\Http\Controllers\Admin\PayoutsController;
use Modules\Payments\app\Http\Controllers\Admin\ServicesController;
use Modules\Payments\app\Http\Controllers\Mentor\MentorPayoutController;
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

Route::middleware(['web', 'auth', 'active', 'role:mentor', 'mentor.approved'])->group(function () {
	Route::post('/mentor/bookings/checkout', [BookingCheckoutController::class, 'store'])->name('mentor.bookings.checkout.store');
	Route::get('/mentor/bookings/checkout/success', [BookingCheckoutController::class, 'success'])->name('mentor.bookings.checkout.success');
	Route::get('/mentor/payouts/connect', [MentorPayoutController::class, 'connect'])->name('mentor.payouts.connect');
	Route::get('/mentor/payouts/refresh', [MentorPayoutController::class, 'refresh'])->name('mentor.payouts.refresh');
	Route::get('/mentor/payouts/return', [MentorPayoutController::class, 'return'])->name('mentor.payouts.return');
	Route::get('/mentor/payouts/status', [MentorPayoutController::class, 'status'])->name('mentor.payouts.status');
});

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');
Route::post('/webhooks/stripe/connect', [StripeWebhookController::class, 'handleConnect'])->name('webhooks.stripe.connect');

Route::middleware(['web', 'auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
	Route::get('/payouts', [PayoutsController::class, 'index'])->name('payouts');
	Route::get('/payouts/{id}', [PayoutsController::class, 'show'])->name('payouts.show');

	Route::get('/services', [ServicesController::class, 'index'])->name('services.index');
	Route::post('/services', [ServicesController::class, 'store'])->name('services.store');
	Route::patch('/services/{id}', [ServicesController::class, 'update'])->name('services.update');
	Route::delete('/services/{id}', [ServicesController::class, 'destroy'])->name('services.destroy');

	Route::prefix('manual-actions')->name('manual-actions.')->group(function () {
		Route::post('/credits/adjust', [ManualActionsController::class, 'adjustCredits'])->name('credits.adjust');
		Route::post('/mentors/update', [ManualActionsController::class, 'amendMentor'])->name('mentors.update');
		Route::post('/services', [ServicesController::class, 'store'])->name('services.store');
		Route::patch('/services/pricing', [ServicesController::class, 'update'])->name('services.pricing.update');
	});

	Route::post('/manual/credits/adjust', [ManualActionsController::class, 'adjustCredits'])->name('manual.credits.adjust');
	Route::post('/manual/mentors/amend', [ManualActionsController::class, 'amendMentor'])->name('manual.mentors.amend');
});
