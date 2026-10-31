<?php

namespace App\Modules\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'between:1,20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
