# Vodafone Cash — خطة تكامل الدفع الإلكتروني

## قرار المنتج

طريقة الدفع الإلكتروني المعتمدة في الإصدار الأول هي Vodafone Cash Wallet فقط. لا نعرض بطاقات بنكية أو محافظ أخرى في Checkout إلا بقرار لاحق.

## تكامل مقترح

Paymob هو المرشح الأول لأنه يوثق دعم Mobile Wallets في مصر ومنها Vodafone Cash، ويوفر Intention API وUnified Checkout وCallbacks/Webhooks. هذا لا يعني أن حساب المطعم أو Vodafone Cash integration مفعّل تلقائيًا؛ يلزم حساب تاجر، اختيار/تفعيل Mobile Wallet Integration، واستلام بيانات الاختبار ثم بيانات الإنتاج بعد المراجعة.

## Flow المقترح

1. العميل يختار Vodafone Cash.
2. Laravel يحسب قيمة الطلب من قاعدة البيانات.
3. Laravel ينشئ Order وPayment بحالة `pending` باستخدام Idempotency Key.
4. Laravel ينشئ Payment Intention عند Paymob من Backend فقط، ويحدد Vodafone Cash Wallet Integration ID فقط.
5. العميل ينتقل إلى صفحة Checkout المستضافة لدى مزود الدفع.
6. مزود الدفع يرسل Webhook إلى Laravel بعد نجاح أو فشل العملية.
7. Laravel يتحقق من HMAC، ثم يتأكد من تطابق merchant reference والمبلغ والعملة والطلب.
8. Laravel يسجل الحدث مرة واحدة ويحدث Payment status داخل Transaction.
9. لا يتم تأكيد الطلب المدفوع أو إرساله للمطبخ بناءً على Redirect المتصفح وحده.

## Security requirements

- لا تُرسل Secret Key أو HMAC Secret إلى Vue.
- لا تخزن Wallet PIN أو OTP.
- لا تعتبر query parameters في صفحة النجاح إثباتًا للدفع.
- تحقّق من HMAC على Webhook، ومن مبلغ/عملة/مرجع الطلب.
- امنع إعادة معالجة نفس Provider Event ID.
- خزّن أقل قدر ممكن من بيانات Webhook، مع تنقيح أي حقول حساسة.
- استخدم Test Mode أثناء التطوير، ولا تضع Live credentials قبل مراجعة الإنتاج.

## Environment variables (placeholders only)

```dotenv
PAYMENT_GATEWAY=paymob
PAYMENT_ONLINE_METHOD=vodafone_cash
PAYMENT_CURRENCY=EGP
PAYMOB_BASE_URL=https://accept.paymob.com
PAYMOB_SECRET_KEY=
PAYMOB_PUBLIC_KEY=
PAYMOB_WALLET_INTEGRATION_ID=
PAYMOB_HMAC_SECRET=
```

## قبل تنفيذ Adapter الفعلي نحتاج

- تأكيد أن المطعم سيستخدم Paymob أو مزودًا آخر معتمدًا.
- حساب تاجر مقبول ومفعّل.
- Wallet integration ID للاختبار والإنتاج.
- API Secret / Public Key وHMAC Secret من لوحة التاجر حسب التدفق المطلوب.
- Callback URL عام عبر HTTPS أثناء اختبار Webhook.
- تأكيد الرسوم والتسوية المالية وشروط التفعيل من العقد/لوحة التاجر.

لا ترسل أي Secret أو كلمة مرور في المحادثة. سنضعها في `.env` محليًا أو Secret Manager في بيئة الاستضافة.
