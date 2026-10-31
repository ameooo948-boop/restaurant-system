<?php

namespace App\Modules\Kitchen\Models;

use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenStationProduct extends Model
{
    protected $fillable = ['branch_id', 'kitchen_station_id', 'product_id'];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function station(): BelongsTo { return $this->belongsTo(KitchenStation::class, 'kitchen_station_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
