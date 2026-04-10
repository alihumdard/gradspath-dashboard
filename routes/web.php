<?php

use Illuminate\Support\Facades\Route;

// Welcome page
Route::get('/', function () {
    return view('welcome');
});

// ========== AUTH ROUTES ==========
// Login routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    // Login logic here
})->name('login.post');

// Register routes
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function () {
    // Register logic here
})->name('register.post');

// Logout
Route::post('/logout', function () {
    // Logout logic
})->name('logout');

// ========== PASSWORD RESET ROUTES ==========
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::post('/forgot-password', function () {
    // Send password reset email logic
})->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset');

Route::post('/reset-password', function () {
    // Update password logic
})->name('password.update');

// ========== STUDENT ROUTES ==========
Route::prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', function () {
        return view('student.dashboard');
    })->name('dashboard');

    Route::get('/store', function () {
        return view('student.store');
    })->name('store');

    Route::get('/institutions', function () {
        return view('student.institutions');
    })->name('institutions');

    Route::get('/institution/{id}', function ($id) {
        return view('student.institution-detail', ['institution' => ['id' => $id, 'name' => 'Institution']]);
    })->name('institution-detail');

    Route::get('/mentors', function () {
        return view('student.mentors');
    })->name('mentors');

    Route::get('/mentor/{id}', function ($id) {
        return view('student.mentor-detail', ['mentor' => ['id' => $id, 'name' => 'Mentor', 'role' => 'Role']]);
    })->name('mentor-detail');

    Route::get('/mentor/{id}/book', function ($id) {
        return view('student.book-mentor', ['mentor' => ['id' => $id]]);
    })->name('book-mentor');

    Route::get('/office-hours', function () {
        return view('student.office-hours');
    })->name('office-hours');

    Route::get('/feedback', function () {
        return view('student.feedback');
    })->name('feedback');

    Route::get('/bookings', function () {
        return view('student.bookings');
    })->name('bookings');

    Route::get('/mentor-notes', function () {
        return view('student.mentor-notes');
    })->name('mentor-notes');

    Route::get('/support', function () {
        return view('student.support');
    })->name('support');

    Route::get('/settings', function () {
        return view('student.settings');
    })->name('settings');
});

// ========== MENTOR ROUTES ==========
Route::prefix('mentor')->name('mentor.')->group(function () {
    Route::get('/dashboard', function () {
        return view('mentor.dashboard', [
            'stats' => [
                'total_bookings' => 24,
                'monthly_earnings' => '1,850',
                'avg_rating' => '4.9',
                'active_students' => 12
            ]
        ]);
    })->name('dashboard');

    Route::get('/bookings', function () {
        return view('mentor.bookings');
    })->name('bookings');

    Route::get('/earnings', function () {
        return view('mentor.earnings');
    })->name('earnings');

    Route::get('/availability', function () {
        return view('mentor.availability');
    })->name('availability');

    Route::get('/students', function () {
        return view('mentor.students');
    })->name('students');

    Route::get('/feedback', function () {
        return view('mentor.feedback');
    })->name('feedback');

    Route::get('/profile', function () {
        return view('mentor.profile');
    })->name('profile');

    Route::get('/settings', function () {
        return view('mentor.settings');
    })->name('settings');
});

// ========== ADMIN ROUTES ==========
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
});
