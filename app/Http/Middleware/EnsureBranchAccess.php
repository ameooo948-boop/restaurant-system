<?php

namespace App\Http\Middleware;

use App\Modules\Branches\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    public function __construct(private readonly PermissionRegistrar $permissions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $branch = $request->route('branch');

        abort_unless($user, 401);
        abort_unless($branch instanceof Branch, 404);
        abort_unless(
            $branch->is_active
                && $branch->restaurant()->where('is_active', true)->exists()
                && $user->hasActiveBranchAssignment($branch),
            403,
        );

        $this->permissions->setPermissionsTeamId($branch->getKey());
        $user->unsetRelation('roles')->unsetRelation('permissions');

        try {
            return $next($request);
        } finally {
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->permissions->setPermissionsTeamId(null);
        }
    }
}
