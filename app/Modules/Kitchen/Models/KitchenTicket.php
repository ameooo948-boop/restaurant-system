<?php

namespace App\Modules\Kitchen\Models;

use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenTicket extends Model
{
    protected $fillable = [
        'public_id', 'branch_id', 'order_id', 'kitchen_station_id', 'ticket_number',
        'status', 'queued_at', 'started_at', 'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function station(): BelongsTo { return $this->belongsTo(KitchenStation::class, 'kitchen_station_id'); }
    public function items(): HasMany { return $this->hasMany(KitchenTicketItem::class); }
    public function stockReservations(): HasMany { return $this->hasMany(\App\Modules\Inventory\Models\StockReservation::class); }
    public function statusHistory(): HasMany { return $this->hasMany(KitchenTicketStatusHistory::class)->orderBy('created_at'); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['queued', 'preparing']);
    }
}
