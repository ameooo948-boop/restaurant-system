<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Orders\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::cases()))],
            'type' => ['nullable', Rule::in(['delivery', 'pickup', 'dine_in'])],
            'payment_status' => ['nullable', Rule::in(['pending', 'succeeded', 'failed', 'cancelled', 'refunded', 'partially_refunded'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
