<?php

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cart_public_id' => ['required', 'uuid'],
            'idempotency_key' => ['required', 'uuid'],
            'order_type' => ['required', Rule::in(['delivery', 'pickup', 'dine_in'])],
            // Vodafone Cash will be enabled only after the merchant gateway is configured in CP08.
            'payment_method' => ['required', Rule::in(['cash'])],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'address_line' => ['required_if:order_type,delivery', 'nullable', 'string', 'max:500'],
            'city' => ['required_if:order_type,delivery', 'nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'building' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:30'],
            'apartment' => ['nullable', 'string', 'max:30'],
            'landmark' => ['nullable', 'string', 'max:300'],
            'table_label' => ['nullable', 'string', 'max:60'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
