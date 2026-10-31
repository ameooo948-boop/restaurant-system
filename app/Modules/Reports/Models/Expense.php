<?php

namespace App\Modules\Reports\Models;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'branch_id', 'expense_category_id', 'created_by', 'voided_by', 'amount', 'currency',
        'expense_date', 'payment_method', 'payee', 'reference', 'description', 'status',
        'voided_at', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function category(): BelongsTo { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function voider(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
}
