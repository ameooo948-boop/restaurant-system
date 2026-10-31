<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Exceptions\PaymentGatewayException;

class PaymobCheckoutReadiness
{
    public function isReady(): bool
    {
        return $this->rejectionReason() === null;
    }

    public function assertReady(): void
    {
        $reason = $this->rejectionReason();
        if ($reason !== null) {
            throw new PaymentGatewayException($reason);
        }
    }

    public function rejectionReason(): ?string
    {
        $configured = (bool) config('paymob.enabled')
            && filled(config('paymob.secret_key'))
            && filled(config('paymob.public_key'))
            && filled(config('paymob.hmac_secret'))
            && (int) config('paymob.wallet_integration_id') > 0;

        if (! $configured) {
            return 'الدفع بفودافون كاش غير مفعل بعد. أكمل إعداد حساب التاجر ومعرّف تكامل المحافظ أولاً.';
        }

        if (! $this->isPublicHttpsUrl((string) config('paymob.base_url'))) {
            return 'عنوان Paymob الإقليمي يجب أن يكون HTTPS صحيحًا قبل بدء الدفع.';
        }

        if (! $this->isPublicHttpsUrl((string) config('app.url'))) {
            return 'APP_URL يجب أن يكون عنوان HTTPS عامًا حتى يستطيع العميل العودة إلى صفحة الطلب بعد الدفع. استخدم عنوان نشر أو HTTPS tunnel.';
        }

        $notificationUrl = trim((string) (config('paymob.notification_url') ?: route('webhooks.paymob.transactions', [], true)));
        if (! $this->isPublicHttpsUrl($notificationUrl)) {
            return 'عنوان Webhook يجب أن يكون HTTPS عامًا ويمكن لبوابة Paymob الوصول إليه. استخدم عنوان نشر أو HTTPS tunnel.';
        }

        if (app()->environment('production') && ! filled(config('paymob.expected_wallet_sub_type'))) {
            return 'لا يمكن تفعيل فودافون كاش في الإنتاج قبل ضبط قيمة نوع المحفظة الموثقة من Test callback.';
        }

        return null;
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host'])) {
            return false;
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.test')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }
}
