<?php

namespace App\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchDashboardController
{
    public function __invoke(Request $request, Branch $branch): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'branch' => $branch->only(['id', 'code', 'name']),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->values(),
        ]);
    }
}
