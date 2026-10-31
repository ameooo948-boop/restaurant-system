<?php

namespace App\Modules\Reports\Http\Requests;

use App\Modules\Branches\Models\Branch;
use App\Modules\Reports\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999'],
            'expense_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['required', 'in:cash,bank_transfer,card,wallet,other'],
            'payee' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $branch = $this->route('branch');
            if (! $branch instanceof Branch || ! $this->filled('expense_category_id')) return;

            $valid = ExpenseCategory::query()
                ->where('restaurant_id', $branch->restaurant_id)
                ->where('is_active', true)
                ->whereKey((int) $this->input('expense_category_id'))
                ->exists();

            if (! $valid) {
                $validator->errors()->add('expense_category_id', 'تصنيف المصروف لا يتبع هذا المطعم أو غير نشط.');
            }
        });
    }
}
