<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;
use App\Modules\Kitchen\Models\KitchenTicketItem;

class StockReservation extends Model
{
    protected $fillable = ['branch_id', 'order_id', 'kitchen_ticket_id', 'kitchen_ticket_item_id', 'ingredient_id', 'reserved_qty', 'consumed_qty', 'status', 'consumed_at', 'released_at'];
    protected function casts(): array { return ['reserved_qty' => 'decimal:3', 'consumed_qty' => 'decimal:3', 'consumed_at' => 'datetime', 'released_at' => 'datetime']; }
    public function ticketItem(): BelongsTo { return $this->belongsTo(KitchenTicketItem::class, 'kitchen_ticket_item_id'); } public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); } public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
