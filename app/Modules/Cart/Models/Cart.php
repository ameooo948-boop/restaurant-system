<?php

namespace App\Modules\Cart\Models;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cart extends Model
{
    protected $fillable = [
        'public_id', 'branch_id', 'user_id', 'guest_token_hash', 'active_owner_key',
        'currency', 'status', 'expires_at', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where(function (Builder $nested): void {
            $nested->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
