<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = ['public_id', 'idempotency_key', 'purchase_order_id', 'branch_id', 'receipt_number', 'received_by', 'received_at', 'notes'];
    protected function casts(): array { return ['received_at' => 'datetime']; }
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); } public function items(): HasMany { return $this->hasMany(GoodsReceiptItem::class); }
}
