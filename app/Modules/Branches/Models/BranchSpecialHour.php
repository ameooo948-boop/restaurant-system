<?php

namespace App\Modules\Branches\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSpecialHour extends Model
{
    protected $fillable = [
        'branch_id',
        'service_date',
        'label',
        'opens_at',
        'closes_at',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date:Y-m-d',
            'is_closed' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
