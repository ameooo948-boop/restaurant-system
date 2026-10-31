<?php

namespace App\Modules\Kitchen\Models;

use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenTicketItem extends Model
{
    protected $fillable = [
        'kitchen_ticket_id', 'order_item_id', 'status', 'product_name_snapshot',
        'variant_name_snapshot', 'quantity', 'modifiers_snapshot', 'notes_snapshot',
        'started_at', 'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'modifiers_snapshot' => 'array',
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(KitchenTicket::class, 'kitchen_ticket_id'); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
