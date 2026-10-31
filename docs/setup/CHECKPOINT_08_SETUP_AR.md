# Checkpoint 08.3 — Payments & Delivery

## 1) ما الذي يضيفه هذا الإصدار؟

- دفع Vodafone Cash عبر Paymob Intention API + Unified Checkout.
- HMAC-SHA512 verification لـ Transaction Processed callback.
- اشتراط تطابق `obj.source_data.sub_type` مع القيمة الفعلية التي أكدها اختبار التاجر؛ لا نقبل Orange Cash أو محفظة أخرى.
- تحديث حالة الدفع من Webhook الموقّع فقط، وليس من رابط رجوع المتصفح.
- منع إعادة استخدام `client_secret` (أحادي الاستخدام) أو إنشاء محاولة جديدة بمجرد انتهاء الوقت.
- إعادة المحاولة فقط بعد فشل مؤكد أو رفض مؤكد لطلب إنشاء النية.
- نطاقات توصيل حسب المدينة والمنطقة مع الرسوم والحد الأدنى.
- إنشاء سجل توصيل عند تأكيد طلب Delivery، وتعيين مندوب وتسجيل الاستلام والتسليم والتعذر.
- تسجيل تحصيل الطلبات النقدية يدويًا، أو عند تسليم COD بواسطة المندوب المعين.

## 2) متغيرات البيئة

أضف القيم التالية يدويًا إلى `.env` المحلي لديك. لا ترسل هذا الملف ولا ترفعه إلى Git:

```dotenv
PAYMOB_ENABLED=false
PAYMOB_BASE_URL=https://accept.paymob.com
PAYMOB_SECRET_KEY=
PAYMOB_PUBLIC_KEY=
PAYMOB_VODAFONE_CASH_INTEGRATION_ID=
PAYMOB_HMAC_SECRET=
PAYMOB_VODAFONE_CASH_SOURCE_SUB_TYPE=
PAYMOB_NOTIFICATION_URL=
PAYMOB_INTENTION_EXPIRATION_SECONDS=1800
PAYMOB_HTTP_TIMEOUT_SECONDS=20
```

- `PAYMOB_BASE_URL` هو أصل Paymob الإقليمي لمصر. Unified Checkout يكوّن الرابط من هذا الأصل مع `/unifiedcheckout/`.
- `PAYMOB_VODAFONE_CASH_INTEGRATION_ID` يجب أن يطابق وضع المفاتيح: Test مع Test أو Live مع Live.
- `PAYMOB_VODAFONE_CASH_SOURCE_SUB_TYPE` يساوي القيمة الدقيقة لحقل `obj.source_data.sub_type` التي تظهر في أول Transaction Processed callback تجريبي لفودافون كاش. لا تخمّن هذه القيمة.
- `PAYMOB_NOTIFICATION_URL` اختياري. عند تركه فارغًا، يستخدم النظام رابط webhook المطلق من `APP_URL`. أثناء التطوير المحلي، ضع هنا عنوان HTTPS عام (مثل tunnel) ينتهي بـ `/webhooks/paymob/transactions`، لأن Paymob لا يستطيع الوصول إلى localhost.
- `APP_URL` يجب أن يكون عنوان الموقع العام HTTPS عند اختبار Checkout، حتى يكون رابط الرجوع مناسبًا للعميل. لا تتركه localhost عند تفعيل بوابة الدفع.

اترك `PAYMOB_ENABLED=false` حتى تحصل على حساب تاجر وتفعيل Mobile Wallets ومعرّف التكامل ومفاتيح Test. Paymob يضم Vodafone Cash ضمن مجموعة Mobile Wallets في مصر، لكن توافر الوسائل يعتمد على إعداد الحساب؛ اطلب من مسؤول Paymob تفعيل Vodafone Cash فقط أو استبعاد المحافظ الأخرى من التكامل. لا نفترض أن Wallet Integration ID يقيّد اختيار العميل إلى Vodafone Cash من تلقاء نفسه.

## 3) اكتشاف نوع محفظة Vodafone من Test callback

1. استخدم مفاتيح Test ومعرّف Wallet Test من نفس حساب Paymob.
2. وجّه `PAYMOB_NOTIFICATION_URL` إلى عنوان HTTPS عام يمكنه استقبال callback، ثم أنشئ محاولة اختبار.
3. إذا لم يتم ضبط `PAYMOB_VODAFONE_CASH_SOURCE_SUB_TYPE`، سيُرفض callback دون تحديث الدفع، ويُسجل النظام فقط `transaction_id`, `integration_id`, `source_data.type`, `source_data.sub_type` (لا يسجل PAN أو OTP أو جسم callback كاملًا).
4. انسخ قيمة `source_data.sub_type` كما وردت من callback تجريبي حقيقي وضعها في `PAYMOB_VODAFONE_CASH_SOURCE_SUB_TYPE`، ثم نفّذ `php artisan config:clear`.
5. أعد تسليم callback التجريبي أو أنشئ محاولة Test جديدة، وتأكد من HMAC وتطابق المبلغ والعملة والتكامل ونوع المحفظة.

لا تستخدم Live credentials لاكتشاف الحقول. لا تعتمد تفعيل الإنتاج حتى يؤكد Paymob أن التكامل مقيّد بفودافون كاش، وتنجح دورة الدفع التجريبية.

## 4) جاهزية Checkout

لن يظهر Vodafone Cash في صفحة الدفع إلا إذا كان `PAYMOB_ENABLED=true` ومفاتيح التاجر ومعرّف التكامل موجودة، وكان عنوان الموقع `APP_URL` وعنوان Paymob الإقليمي وعنوان Webhook صالحين عبر HTTPS عام. في الإنتاج، يجب أيضًا ضبط نوع المحفظة الذي تم التحقق منه في Test callback. لذلك استخدم HTTPS tunnel أثناء التطوير المحلي، ولا تعتمد على `localhost`.

## 5) HMAC والـWebhook

- Endpoint: `POST /webhooks/paymob/transactions`.
- يستخدم `hmac` من Query String أو الحقل الجذري.
- يجب أن تكون قيمة HMAC مطابقة لخوارزمية Paymob SHA-512 وبالترتيب الرسمي للحقول داخل `obj`.
- لا يتم تعديل الدفع عند غياب HMAC أو فشله أو عدم تطابق نوع المحفظة أو التكامل أو المبلغ/العملة.
- Webhook هو مصدر الحقيقة؛ لا تستخدم redirect URL لإثبات الدفع.
- لا يتم إرسال الطلب للمطبخ قبل callback صالح ويؤكد الدفع.
- راجع أول callback حقيقي للمحفظة في وضع Test، لأن Paymob ينبه إلى اختلاف الحقول الفعلية بين أنواع الدفع، ويجب التحقق من القيم التي تدخل HMAC باستخدام HMAC Signature Troubleshooter.

## 6) سياسة إعادة المحاولة

- لا يعاد استخدام `client_secret`؛ فهو أحادي الاستخدام.
- لا يتم إنشاء محاولة دفع ثانية لمجرد أن `expires_at` مرّ.
- المحاولة الجديدة مسموحة فقط بعد حالة `failed` مؤكدة من Paymob أو رفض مؤكد لإنشاء النية.
- إذا كانت النتيجة غير معروفة بسبب timeout/connection loss، يلزم التحقق من Paymob/Transaction Inquiry أو دعم التاجر قبل المحاولة التالية.
- لا يمكن إلغاء طلب Vodafone Cash pending/succeeded من لوحة الإدارة في هذه المرحلة؛ لا توجد آلية Refund آلية مضافة هنا. يجب حسم الدفع والتعامل مع الاسترداد يدويًا من Paymob Dashboard حسب سياسة المطعم.

## 7) إعداد مناطق التوصيل

أضف مناطق الفرع من:

`/admin/branches/{BRANCH_ID}/delivery-zones`

إذا لم توجد مناطق نشطة، يستخدم النظام مؤقتًا إعداد الفرع القديم `delivery_fee`. بمجرد إضافة أول نطاق نشط، يصبح التوصيل متاحًا فقط عند تطابق المدينة والمنطقة. `area` الفارغة تغطي المدينة بأكملها، ويُفضَّل النطاق المحدد على نطاق المدينة العام.

أضف الموظفين إلى الفرع وامنح المندوب دور `delivery_staff`. مدير التوصيل يحتاج `deliveries.assign` لتعيين المندوب؛ المندوب يرى التوصيلات المسندة إليه فقط ولا يستطيع تعيين مندوبين آخرين.

## 8) أوامر التشغيل

```powershell
php artisan optimize:clear
php artisan route:list --path=payments
php artisan route:list --path=deliveries
php artisan test --filter=PaymobPreflightFailureTest
php artisan test --filter=PaymobCheckoutReadinessTest
php artisan test --filter=OrderCancellationDeliveryCleanupTest
php artisan migrate:status
php artisan migrate
php artisan restaurant:access:sync
npm run build
```

لا تستخدم `migrate:fresh` أو `migrate:refresh` على Supabase.

## 9) Git

بعد نجاح الاختبارات والبناء محليًا:

```powershell
git add -A
git commit -m "feat(payments): add Vodafone Cash and delivery management"
git tag -a checkpoint-08-payments-delivery -m "Checkpoint 08 verified"
git push origin main --tags
```
