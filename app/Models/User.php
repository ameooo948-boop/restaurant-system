<?php

namespace App\Models;

use App\Modules\Branches\Models\Branch;
use App\Modules\Customers\Models\CustomerProfile;
use App\Modules\Staff\Models\BranchUserAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function branchAssignments()
    {
        return $this->hasMany(BranchUserAssignment::class);
    }

    public function hasActiveBranchAssignment(Branch|int $branch): bool
    {
        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;

        return $this->branchAssignments()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->exists();
    }
}
