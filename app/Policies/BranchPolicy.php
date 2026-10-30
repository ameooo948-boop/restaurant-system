<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Branches\Models\Branch;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branchAssignments()->active()->exists();
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->hasActiveBranchAssignment($branch);
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasActiveBranchAssignment($branch) && $user->can('settings.manage');
    }
}
