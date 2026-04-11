<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\app\Http\Controllers\AuthController;
use Modules\Auth\app\Http\Controllers\SocialAuthController;
use Modules\Auth\app\Http\Controllers\Admin\AdminUserController;

/*
|--------------------------------------------------------------------------
| Auth Module — Web Routes
|--------------------------------------------------------------------------
| Guest routes: login, register, password reset, Google OAuth
| Authenticated routes: logout
| Admin routes: user management, mentor approval, admin logs
*/

// Login routes
Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',   [AuthController::class, 'login'])->name('auth.login.post');

// Register routes
Route::get('/register',  [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register.post');

// Password reset routes
Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('auth.password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('auth.password.email');

Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('auth.password.reset');
Route::post('/reset-password',        [AuthController::class, 'resetPassword'])->name('auth.password.update');

// Google OAuth routes
Route::get('/auth/google',          [SocialAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');

// Logout route
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/users',                         [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}',                    [AdminUserController::class, 'show'])->name('users.show');
    Route::delete('/users/{id}',                 [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{id}/toggle-active',    [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');

    // Mentor approval workflow
    Route::get('/mentors/pending',               [AdminUserController::class, 'pendingMentors'])->name('mentors.pending');
    Route::patch('/mentors/{id}/approve',        [AdminUserController::class, 'approveMentor'])->name('mentors.approve');
    Route::patch('/mentors/{id}/reject',         [AdminUserController::class, 'rejectMentor'])->name('mentors.reject');
    Route::patch('/mentors/{id}/pause',          [AdminUserController::class, 'pauseMentor'])->name('mentors.pause');

    // Admin audit logs
    Route::get('/logs', [AdminUserController::class, 'logs'])->name('logs');
});
