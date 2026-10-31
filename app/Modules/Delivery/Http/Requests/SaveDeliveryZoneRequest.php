<?php

namespace App\Modules\Delivery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
