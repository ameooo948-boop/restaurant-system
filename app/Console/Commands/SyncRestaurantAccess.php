<?php

namespace App\Console\Commands;

use App\Modules\Branches\Models\Branch;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class SyncRestaurantAccess extends Command
{
    protected $signature = 'restaurant:access:sync';
    protected $description = 'Synchronize restaurant permissions and branch-scoped roles';

    public function handle(PermissionRegistrar $registrar): int
    {
        $accessConfig = config('restaurant_access');
        $branches = Branch::query()->active()->orderBy('id')->get();

        if ($branches->isEmpty()) {
            $this->error('No active branch exists. Run php artisan restaurant:setup:first-branch first.');
            return self::FAILURE;
        }

        $registrar->forgetCachedPermissions();

        try {
            foreach ($accessConfig['permissions'] as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach ($branches as $branch) {
                $registrar->setPermissionsTeamId($branch->id);

                foreach ($accessConfig['roles'] as $roleName => $permissions) {
                    $role = Role::findOrCreate($roleName, 'web');
                    $role->syncPermissions($permissions);
                }

                $this->line("Synchronized roles for branch {$branch->code} (#{$branch->id}).");
            }
        } catch (Throwable $exception) {
            $this->error('Access synchronization failed: '.$exception->getMessage());
            return self::FAILURE;
        } finally {
            $registrar->setPermissionsTeamId(null);
            $registrar->forgetCachedPermissions();
        }

        $this->info('Restaurant permissions and branch roles are synchronized.');

        return self::SUCCESS;
    }
}
