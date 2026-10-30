# سجل Checkpoints — Restaurant Management System

## الحالة العامة

- Project code name: `restaurant-system`
- Current architecture: `v1.0`
- Current delivery: `Checkpoint 01 — Project Foundation`
- Status: ملفات الحزمة جاهزة؛ بانتظار إنشاء مشروع Laravel محليًا، إعداد Supabase، تشغيل migrations والاختبارات للتحقق على بيئة المستخدم.
- Local verification: PHP syntax lint passed for all shipped PHP files. Laravel tests were not run here because this workspace does not contain Composer or the Laravel vendor dependencies.

## Decisions locked

- Laravel 13 / PHP 8.3+
- Vue 3 + Inertia.js + Tailwind CSS + Vite
- Supabase PostgreSQL؛ Laravel هو طبقة منطق العمل الوحيدة
- Schema خاصة `restaurant_app`
- فرع واحد في الإطلاق مع دعم عدة فروع
- Delivery / Pickup / Dine-in
- Guest + Registered checkout
- Advanced inventory
- Vodafone Cash كوسيلة الدفع الإلكتروني الوحيدة في الإصدار الأول
- Paymob مرشح تكامل أول، غير معتمد تجاريًا قبل حساب التاجر والبيانات
- العربية افتراضيًا، الإنجليزية اختيارية، EGP، Africa/Cairo

## Checkpoint 00 — Discovery & Architecture

- [x] نطاق النظام وتحديد الوحدات.
- [x] Stack ومعمارية Modular Monolith.
- [x] تقسيم مسؤولية Laravel وSupabase.
- [x] تصميم مبدئي لقاعدة البيانات.
- [x] تحديد Vodafone Cash كوسيلة دفع إلكتروني.

## Checkpoint 01 — Project Foundation

- [x] إضافة Restaurant config.
- [x] إضافة Payments config placeholders.
- [x] إضافة Restaurant / Branch models.
- [x] إضافة migrations للمطعم والفروع وساعات العمل والإعدادات.
- [x] إضافة Payment gateway contract وDTOs مبدئية.
- [x] إضافة اختبارات تأسيسية.
- [x] فحص Syntax لجميع ملفات PHP المضافة.
- [ ] تنفيذ اختبارات Laravel فعليًا على جهاز المستخدم بعد تثبيت Composer dependencies.
- [x] إضافة دليل Supabase setup.
- [ ] إنشاء Laravel project 13 على جهاز المستخدم.
- [ ] إنشاء Supabase schema `restaurant_app`.
- [ ] ضبط اتصال PostgreSQL + SSL + search_path.
- [ ] تشغيل migrations بنجاح.
- [ ] تشغيل `RestaurantBranchSchemaTest` بنجاح.

## Next checkpoint — Checkpoint 02

بعد إثبات Checkpoint 01:
- تثبيت Inertia + Vue 3 + Tailwind CSS على النسخ المتوافقة.
- تجهيز مجلدات Modules والـproviders/bootstrapping اللازمة.
- Authentication and authorization baseline.
- Restaurant owner / branch staff / customer boundaries.
- إعداد roles & permissions واختبارات عزل الفروع.

## سجل التسليمات

| Version | ZIP | Contents | Status |
|---|---|---|---|
| 0.1.0 | `restaurant-checkpoint-01.zip` | Foundation config, models, migrations, payment contract, docs, tests | Ready for user setup and verification |

## Protocol

كل تسليم تعديل سيكون ZIP overlay جديدًا بمسارات المشروع النسبية، مع `CHANGELOG` وملف Checkpoint. لا نضع `.env` أو credentials أو بيانات إنتاج في ZIP. كل خطوة يدوية لا تحتاج ملفات ستُشرح منفصلة.
