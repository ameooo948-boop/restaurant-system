<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Cart\Models\Cart;
use App\Modules\Customers\Models\CustomerProfile;
use App\Modules\Kitchen\Models\KitchenTicket;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Enums\OrderType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'public_id', 'restaurant_id', 'branch_id', 'cart_id', 'user_id', 'customer_profile_id',
        'order_number', 'idempotency_key', 'order_type', 'order_status', 'payment_method',
        'payment_status', 'order_source', 'currency', 'subtotal', 'discount_total', 'tax_total',
        'delivery_fee', 'total', 'customer_name', 'customer_phone', 'customer_email',
        'delivery_address_snapshot', 'table_label', 'customer_notes', 'guest_token_hash',
        'placed_at', 'confirmed_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'delivery_address_snapshot' => 'array',
            'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'tax_total' => 'decimal:2',
            'delivery_fee' => 'decimal:2', 'total' => 'decimal:2',
            'placed_at' => 'datetime', 'confirmed_at' => 'datetime',
            'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function customerProfile(): BelongsTo { return $this->belongsTo(CustomerProfile::class); }
    public function cart(): BelongsTo { return $this->belongsTo(Cart::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function statusHistory(): HasMany { return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function kitchenTickets(): HasMany { return $this->hasMany(KitchenTicket::class); }

    public function isOwnedBy(User $user): bool { return (int) $this->user_id === (int) $user->id; }
    public function scopePublicId(Builder $query, string $publicId): Builder { return $query->where('public_id', $publicId); }
}
