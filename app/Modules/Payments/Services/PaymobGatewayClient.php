<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Exceptions\PaymentGatewayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PaymobGatewayClient
{
    public function createIntention(array $payload): array
    {
        $secret = (string) config('paymob.secret_key', '');
        if ($secret === '') {
            throw new PaymentGatewayException('بوابة الدفع غير مكتملة الإعداد.');
        }

        try {
            $response = Http::baseUrl((string) config('paymob.base_url'))
                ->acceptJson()
                ->asJson()
                ->connectTimeout(8)
                ->timeout((int) config('paymob.http_timeout_seconds', 20))
                ->withHeaders(['Authorization' => 'Token '.$secret])
                ->post((string) config('paymob.intention_path', '/v1/intention/'), $payload);
        } catch (ConnectionException) {
            throw new PaymentGatewayException(
                'تعذر تأكيد نتيجة الاتصال ببوابة الدفع. لن ننشئ محاولة ثانية تلقائيًا لأن الطلب قد يكون وصل إلى Paymob.',
                true,
            );
        }

        if (! $response->successful()) {
            $unknown = $response->serverError() || $response->status() === 408;
            throw new PaymentGatewayException(
                'لم تؤكد بوابة الدفع إنشاء العملية. راجع حالة التكامل وسجل Paymob قبل إعادة المحاولة.',
                $unknown,
            );
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['client_secret']) || empty($data['intention_order_id']) || empty($data['id'])) {
            throw new PaymentGatewayException(
                'استجابة Paymob الناجحة لا تحتوي كل معرفات عملية الدفع المطلوبة. تم إيقاف إعادة المحاولة التلقائية لتجنب تكرار العملية.',
                true,
            );
        }

        return $data;
    }
}
