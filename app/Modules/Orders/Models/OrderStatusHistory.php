<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'old_status', 'new_status', 'changed_by', 'source', 'note', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
