<?php

namespace App\Modules\Kitchen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKitchenTicketStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['preparing', 'ready'])],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
