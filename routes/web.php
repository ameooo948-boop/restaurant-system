<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Restaurant account and branch administration routes.
require __DIR__.'/restaurant_auth.php';

// Load storefront routes on every Laravel application boot.
// Do not use require here: PHPUnit may reboot the application between tests,
// and each boot needs a fresh route collection.
require __DIR__.'/restaurant_store.php';

// Kitchen display and station management routes.
require __DIR__.'/restaurant_kitchen.php';

require __DIR__.'/restaurant_admin.php';
