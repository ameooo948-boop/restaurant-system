<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Branches\Models\Branch;
use App\Models\User;
use LogicException;

class StockMovement extends Model
{
    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Stock movements are immutable. Record a compensating movement instead.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Stock movements cannot be deleted. Record a compensating movement instead.');
        });
    }

    protected $fillable = ['branch_id', 'ingredient_id', 'movement_type', 'quantity_delta', 'unit_cost', 'source_type', 'source_id', 'reference', 'note', 'created_by', 'occurred_at'];
    protected function casts(): array { return ['quantity_delta' => 'decimal:3', 'unit_cost' => 'decimal:4', 'occurred_at' => 'datetime']; }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); } public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); } public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
