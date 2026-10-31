<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id', 'ingredient_id', 'quantity_ordered', 'quantity_received', 'unit_cost'];
    protected function casts(): array { return ['quantity_ordered' => 'decimal:3', 'quantity_received' => 'decimal:3', 'unit_cost' => 'decimal:4']; }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); } public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
}
