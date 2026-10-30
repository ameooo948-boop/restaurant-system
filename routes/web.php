<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Restaurant account and branch administration routes.
require __DIR__.'/restaurant_auth.php';
