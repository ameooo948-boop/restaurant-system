<?php

namespace App\Modules\Delivery\Models;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    protected $fillable = [
        'order_id', 'branch_id', 'delivery_zone_id', 'driver_user_id', 'status',
        'assigned_at', 'picked_up_at', 'delivered_at', 'failed_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function zone(): BelongsTo { return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id'); }
    public function driver(): BelongsTo { return $this->belongsTo(User::class, 'driver_user_id'); }
    public function statusHistory(): HasMany { return $this->hasMany(DeliveryStatusHistory::class)->orderBy('created_at'); }
}
