<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class CreateRestaurantStaff extends Command
{
    protected $signature = 'restaurant:staff:create {--branch= : Branch code or ID} {--role=branch_manager : Branch-scoped role}';
    protected $description = 'Create or assign a staff account to a branch';

    public function handle(PermissionRegistrar $registrar): int
    {
        $branchInput = $this->option('branch');
        $branch = $branchInput
            ? Branch::query()->where(function ($query) use ($branchInput): void {
                $query->where('code', (string) $branchInput);
                if (ctype_digit((string) $branchInput)) {
                    $query->orWhere('id', (int) $branchInput);
                }
            })->first()
            : Branch::query()->primary()->first();

        if (! $branch || ! $branch->is_active) {
            $this->error('Active branch not found. Supply --branch=BRANCH_CODE or create the first branch.');
            return self::FAILURE;
        }

        $roleName = (string) $this->option('role');
        if (! array_key_exists($roleName, config('restaurant_access.roles', []))) {
            $this->error('Unknown role. Allowed: '.implode(', ', array_keys(config('restaurant_access.roles', []))));
            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Staff full name'));
        $email = mb_strtolower(trim((string) $this->ask('Staff email')));
        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A name and valid email are required.');
            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $password = (string) $this->secret('Temporary password (minimum 8 characters)');
            $confirmation = (string) $this->secret('Confirm temporary password');

            if (strlen($password) < 8 || ! hash_equals($password, $confirmation)) {
                $this->error('Passwords did not match or were shorter than 8 characters.');
                return self::FAILURE;
            }
        } else {
            if (! $user->is_active) {
                $this->error('This account is inactive. Reactivate it through an audited admin workflow first.');
                return self::FAILURE;
            }
            $this->warn('An existing account will be assigned to this branch; its password will not be changed.');
            $password = null;
        }

        try {
            DB::transaction(function () use ($branch, $name, $email, $password, &$user, $registrar, $roleName): void {
                if (! $user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password),
                        'is_active' => true,
                    ]);
                }

                BranchUserAssignment::updateOrCreate(
                    ['branch_id' => $branch->id, 'user_id' => $user->id],
                    ['is_active' => true, 'assigned_by' => null],
                );

                $registrar->setPermissionsTeamId($branch->id);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $role = Role::findByName($roleName, 'web');
                $user->assignRole($role);
            });
        } catch (Throwable $exception) {
            $this->error('Could not assign staff account: '.$exception->getMessage());
            return self::FAILURE;
        } finally {
            $registrar->setPermissionsTeamId(null);
            $registrar->forgetCachedPermissions();
        }

        $this->info("Staff account {$email} assigned {$roleName} at {$branch->name}.");
        return self::SUCCESS;
    }
}
