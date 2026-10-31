<?php

namespace App\Modules\Delivery\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryStatusHistory extends Model
{
    protected $fillable = ['delivery_id', 'old_status', 'new_status', 'changed_by', 'note'];

    public function delivery(): BelongsTo { return $this->belongsTo(Delivery::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
