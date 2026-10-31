<?php

namespace App\Modules\Kitchen\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenTicketStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'kitchen_ticket_id', 'old_status', 'new_status', 'changed_by', 'source', 'note', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(KitchenTicket::class, 'kitchen_ticket_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
