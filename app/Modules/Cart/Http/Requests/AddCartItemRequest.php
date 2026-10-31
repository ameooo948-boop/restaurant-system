<?php

namespace App\Modules\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'between:1,20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'modifiers' => ['sometimes', 'array', 'max:30'],
            'modifiers.*.modifier_option_id' => ['required', 'integer', 'distinct'],
            'modifiers.*.quantity' => ['required', 'integer', 'between:1,10'],
        ];
    }
}
