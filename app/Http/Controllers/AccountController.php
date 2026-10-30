<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AccountController
{
    public function __invoke(): Response
    {
        return Inertia::render('Account/Index');
    }
}
