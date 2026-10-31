<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Restaurants\Models\Restaurant;

class MeasurementUnit extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'symbol', 'dimension', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); } public function ingredients(): HasMany { return $this->hasMany(Ingredient::class, 'base_unit_id'); }
}
