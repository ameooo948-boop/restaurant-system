<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCount extends Model
{
    protected $fillable = ['public_id', 'branch_id', 'status', 'counted_by', 'posted_by', 'counted_at', 'posted_at', 'notes'];
    protected function casts(): array { return ['counted_at' => 'datetime', 'posted_at' => 'datetime']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); } public function items(): HasMany { return $this->hasMany(InventoryCountItem::class); }
}
