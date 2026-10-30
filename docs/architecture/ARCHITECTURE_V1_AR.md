# Architecture v1.0 — نظام المطعم

## قرارات المشروع المعتمدة

- منتج لمطعم واحد في البداية، مع فرع واحد فعليًا وتصميم يسمح بإضافة فروع مستقبلًا.
- قنوات الطلب: Delivery وPickup وDine-in.
- العميل يستطيع الطلب كضيف أو بحساب.
- المخزون متقدم: مكونات، وحدات قياس، تحويل وحدات، وصفات، حجوزات، حركات مخزون، موردون ومشتريات.
- اللغة العربية افتراضيًا مع الإنجليزية، والعملة EGP، والمنطقة الزمنية Africa/Cairo.
- الدفع الإلكتروني المطلوب: Vodafone Cash فقط. مزود التكامل الأول المرشح Paymob، ويظل مشروطًا بحساب تاجر وتفعيل Wallet Integration وبيانات الاختبار والإنتاج.

## Stack

| Layer | Technology | Responsibility |
|---|---|---|
| Backend | Laravel 13 / PHP 8.3+ | Business rules, auth, permissions, APIs, validation |
| Frontend | Vue 3 + Inertia.js + Tailwind CSS + Vite | Customer website, admin, kitchen display |
| Database | Supabase PostgreSQL | مصدر بيانات التطبيق الأساسي |
| Storage | Supabase Storage عبر Laravel | صور المنيو والملفات |
| Realtime | Laravel Reverb + Broadcasting | أحداث المطبخ والطلبات |
| Cache / Queue | Redis عند الحاجة | Cache and asynchronous jobs |
| Tests | PHPUnit + Laravel tests | Unit, feature, integration, authorization |

## Architecture style

Modular Monolith. لا نبدأ بـ Microservices. كل Module يمتلك مسؤولية تجارية واضحة، مع حدود معروفة بين الوحدات.

```text
app/
├── Modules/
│   ├── Restaurants/
│   ├── Branches/
│   ├── Authentication/
│   ├── Customers/
│   ├── Menu/
│   ├── Cart/
│   ├── Orders/
│   ├── Payments/
│   ├── Kitchen/
│   ├── Inventory/
│   ├── Delivery/
│   ├── Dining/
│   ├── Promotions/
│   ├── Reports/
│   └── Notifications/
├── Shared/
│   ├── DTOs/
│   ├── Enums/
│   ├── Exceptions/
│   └── Contracts/
└── Infrastructure/
    ├── Supabase/
    ├── Payments/
    └── Broadcasting/
```

## Dependency rules

1. Controllers لا تحتوي Business Logic.
2. Form Requests للتحقق من المدخلات.
3. Policies/Gates للتفويض، مع التحقق من نطاق الفرع في كل عملية داخلية.
4. Actions للعمليات التجارية المحددة، وServices للمنطق المشترك أو المركب.
5. DTOs لنقل البيانات المنظمة بين الطبقات.
6. Events/Listeners لإعلان النتائج، وJobs للمهام الخلفية.
7. Infrastructure adapters تعزل مزودي الدفع والتخزين والبث.
8. لا نضيف Repository لمجرد النمط؛ يضاف عند وجود سبب لاختبار/عزل مصدر البيانات.
9. Laravel هو مصدر الحقيقة للمنطق التجاري؛ لا ننفذ الطلبات أو الدفع أو المخزون مباشرة من المتصفح إلى Supabase.

## Supabase boundary

- PostgreSQL هو قاعدة البيانات الرئيسية.
- Laravel يتصل عبر PostgreSQL PDO.
- جداول التطبيق توضع في Schema خاصة `restaurant_app` وليس `public`.
- لا تُضاف `restaurant_app` إلى Exposed Schemas في Data API.
- ملفات العميل ترفع إلى Storage من خلال Laravel بعد التحقق من الصلاحيات.
- لا نضع `service_role` أو مفاتيح سرية في كود Vue.
- نستخدم Laravel migrations كمصدر وحيد لتاريخ تغييرات Schema التطبيق.

## Payment boundary

`PaymentGateway` عقد داخلي مستقل عن Paymob أو غيره. واجهة Checkout تتعامل مع `vodafone_cash` فقط في الدفع الإلكتروني، بينما Laravel يحفظ محاولة الدفع ويربطها بالطلب. مزود الدفع يعيد جلسة Checkout، والـWebhook الموقّع هو مصدر الحقيقة لتحديث حالة الدفع. Redirect العميل لا يثبت نجاح الدفع.

## Cross-cutting requirements

- Database transactions للطلب والدفع والمخزون.
- Unique idempotency keys لمنع تكرار العمليات.
- Transactional Outbox للأحداث الحرجة التي يجب بثها بعد Commit.
- Audit log للتعديلات الحساسة.
- Money values باستخدام DECIMAL في قاعدة البيانات، وتحويلها إلى minor units عند إرسالها لمزود الدفع.
- لا نحذف السجلات المالية أو حركات المخزون؛ نستخدم التسويات والـreversals.
- اختبارات عزل الفروع وقيود المخزون والتزامن والتوقيعات.
