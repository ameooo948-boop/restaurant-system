<?php

namespace App\Modules\Menu\Models;

use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchProductSetting extends Model
{
    protected $fillable = [
        'branch_id', 'product_variant_id', 'price_override', 'is_available',
        'available_from', 'available_until',
    ];

    protected function casts(): array
    {
        return [
            'price_override' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
