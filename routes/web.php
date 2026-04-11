<?php

use Illuminate\Support\Facades\Route;

// Root landing page
Route::get('/', function () {
    return view('landing_page.index');
});
