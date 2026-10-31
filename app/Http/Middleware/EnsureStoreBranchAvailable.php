<?php

namespace App\Http\Middleware;

use App\Modules\Branches\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreBranchAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        abort_unless($route !== null, 404);

        $branchParameter = $route->parameter('branch');

        // Do not depend on middleware priority for implicit route-model binding.
        // The storefront routes constrain branch IDs to numeric values, so safely
        // resolve a scalar route parameter here when SubstituteBindings has not
        // converted it to a Branch model yet.
        if (! $branchParameter instanceof Branch) {
            abort_unless(
                is_int($branchParameter)
                || (is_string($branchParameter) && ctype_digit($branchParameter)),
                404
            );

            $branchParameter = Branch::query()->findOrFail((int) $branchParameter);
            $route->setParameter('branch', $branchParameter);
        }

        $branch = $branchParameter;
        $branch->loadMissing('restaurant');

        abort_unless(
            $branch->is_active && $branch->restaurant?->is_active,
            404
        );

        return $next($request);
    }
}
