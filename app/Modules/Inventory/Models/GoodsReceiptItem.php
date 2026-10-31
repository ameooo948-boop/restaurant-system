<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;

class GoodsReceiptItem extends Model
{
    protected $fillable = ['goods_receipt_id', 'purchase_order_item_id', 'ingredient_id', 'received_qty', 'unit_cost'];
    protected function casts(): array { return ['received_qty' => 'decimal:3', 'unit_cost' => 'decimal:4']; }
    public function receipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id'); } public function purchaseOrderItem(): BelongsTo { return $this->belongsTo(PurchaseOrderItem::class); } public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
}
