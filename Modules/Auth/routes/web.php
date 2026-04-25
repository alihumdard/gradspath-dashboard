<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\app\Http\Controllers\AuthController;
use Modules\Auth\app\Http\Controllers\Admin\AdminUserController;

/*
|--------------------------------------------------------------------------
| Auth Module - Web Routes
|--------------------------------------------------------------------------
| Guest routes: login, register, password reset
| Authenticated routes: logout, email verification
| Admin routes: user management, mentor approval, admin logs
*/

Route::middleware(['web', 'guest'])->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::get('/universities/search', [AuthController::class, 'searchUniversities'])->name('universities.search');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register.post');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware(['web'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AuthController::class, 'showAdminLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'adminLogin'])->name('login.post');
});

Route::middleware(['web', 'auth', 'active'])->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerifyEmailNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{id}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::get('/mentors/pending', [AdminUserController::class, 'pendingMentors'])->name('mentors.pending');
        Route::patch('/mentors/{id}/approve', [AdminUserController::class, 'approveMentor'])->name('mentors.approve');
        Route::patch('/mentors/{id}/reject', [AdminUserController::class, 'rejectMentor'])->name('mentors.reject');
        Route::patch('/mentors/{id}/pause', [AdminUserController::class, 'pauseMentor'])->name('mentors.pause');

        Route::get('/logs', [AdminUserController::class, 'logs'])->name('logs');
    });
});
