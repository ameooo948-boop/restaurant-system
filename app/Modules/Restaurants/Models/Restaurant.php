<?php

namespace App\Modules\Restaurants\Models;

use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'phone',
        'email',
        'logo_path',
        'currency',
        'timezone',
        'default_locale',
        'supported_locales',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'supported_locales' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function activeBranches(): HasMany
    {
        return $this->branches()->where('is_active', true);
    }

    public function primaryBranch(): HasOne
    {
        return $this->hasOne(Branch::class)->where('is_primary', true);
    }
}
