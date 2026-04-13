<?php

use Illuminate\Support\Facades\Route;

// Serve the local university dataset for registration autocomplete
Route::get('/university.json', function () {
    return response()->file(base_path('university.json'));
});

// Root landing page
Route::get('/', function () {
    return view('landing_page.index');
});
