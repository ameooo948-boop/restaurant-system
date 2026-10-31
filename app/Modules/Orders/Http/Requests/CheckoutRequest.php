<?php

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Payments\Services\EgyptianMobilePhoneNormalizer;
use App\Modules\Payments\Services\PaymobCheckoutReadiness;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $paymentMethods = ['cash'];
        if (app(PaymobCheckoutReadiness::class)->isReady()) {
            $paymentMethods[] = 'vodafone_cash';
        }

        return [
            'cart_public_id' => ['required', 'uuid'],
            'idempotency_key' => ['required', 'uuid'],
            'order_type' => ['required', Rule::in(['delivery', 'pickup', 'dine_in'])],
            'payment_method' => ['required', Rule::in($paymentMethods)],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => [
                'required', 'string', 'max:30',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('payment_method') === 'vodafone_cash'
                        && ! app(EgyptianMobilePhoneNormalizer::class)->normalize(is_string($value) ? $value : null)) {
                        $fail('أدخل رقم محمول مصريًا صحيحًا لاستخدام فودافون كاش.');
                    }
                },
            ],
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
