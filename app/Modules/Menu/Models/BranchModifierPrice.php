<?php

namespace App\Modules\Menu\Models;

use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchModifierPrice extends Model
{
    protected $fillable = [
        'branch_id', 'modifier_option_id', 'price_delta_override', 'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price_delta_override' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ModifierOption::class, 'modifier_option_id');
    }
}
