<?php
namespace App\Modules\Inventory\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Restaurants\Models\Restaurant;

class Supplier extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'contact_name', 'phone', 'email', 'address', 'notes', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); } public function purchaseOrders(): HasMany { return $this->hasMany(PurchaseOrder::class); }
}
