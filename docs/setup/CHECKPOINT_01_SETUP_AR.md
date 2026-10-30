# Checkpoint 01 — تأسيس المشروع وربط Supabase

> الحزمة دي Overlay: استخرج محتوياتها داخل جذر مشروع Laravel، مع الحفاظ على نفس أسماء المجلدات. هي تضيف ملفات جديدة ولا تستبدل ملفات Laravel القياسية.

## 1) إنشاء مشروع Laravel 13

افتح PowerShell في المكان الذي تريد وضع المشروع فيه:

```powershell
composer create-project laravel/laravel restaurant-system "^13.0"
cd restaurant-system
```

تأكد أن البيئة تستخدم PHP 8.3 أو أحدث:

```powershell
php -v
composer --version
php artisan --version
```

## 2) استخراج الحزمة

استخرج `restaurant-checkpoint-01.zip` داخل مجلد `restaurant-system` نفسه، بحيث تظهر المجلدات التالية بجوار `app` و`bootstrap` و`config` الأصليين:

```text
app/Modules
config/restaurant.php
config/payments.php
database/migrations
docs/
tests/Feature/Foundation
```

وافق على استبدال الملفات فقط لو ظهرت مطالبة تخص ملفًا من الحزمة نفسها. الحزمة الحالية لا تستهدف استبدال ملفات Laravel الافتراضية.

## 3) إعداد Supabase

1. أنشئ مشروعًا جديدًا في Supabase Dashboard.
2. من زر **Connect** اختر **Session pooler** وانسخ Connection String كما يظهر لك؛ لا تركّب اسم الـHost يدويًا.
3. افتح **SQL Editor** ونفّذ الملف `docs/setup/SUPABASE_SCHEMA_SETUP.sql`.
4. لا تضف `restaurant_app` إلى قائمة Exposed Schemas في إعدادات Data API. جداول التطبيق ستُدار من Laravel عبر اتصال PostgreSQL، وليس عبر متصفح العميل.
5. احتفظ بكلمة مرور قاعدة البيانات في مدير كلمات مرور، ولا ترسلها في المحادثة ولا تضعها داخل ملفات ZIP أو Git.

## 4) تعديل `config/database.php` (خطوة يدوية واحدة)

افتح `config/database.php` وابحث داخل إعداد اتصال `pgsql` عن `search_path` و`sslmode`.

عدّل القيم لتصبح:

```php
'search_path' => env('DB_SEARCH_PATH', 'restaurant_app,public'),
'sslmode' => env('DB_SSLMODE', 'require'),
```

لو لم تجد أحد المفتاحين، أضفه داخل مصفوفة إعداد اتصال `pgsql`. لا تستبدل الملف كاملًا بنسخة من الإنترنت.

## 5) تعديل `config/app.php`

تأكد من أن إعدادات الوقت واللغة تقرأ قيم `.env`، وعدّل المفاتيح الموجودة بدل تكرارها:

```php
'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),
'locale' => env('APP_LOCALE', 'ar'),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
```

اترك اتصال قاعدة البيانات مضبوطًا على UTC؛ `Africa/Cairo` هو توقيت العمل والعرض وحساب اليوم التشغيلي.

## 6) إعداد `.env`

أضف أو حدّث القيم التالية في `.env` (لا تضع علامات اقتباس حول كلمات المرور إلا عند الحاجة، واحرص على ترميز الرموز الخاصة داخل DB URL):

```dotenv
APP_TIMEZONE=Africa/Cairo
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en

DB_CONNECTION=pgsql
DB_URL="postgresql://postgres.[PROJECT-REF]:[YOUR-PASSWORD]@[POOLER-HOST]:5432/postgres"
DB_SEARCH_PATH=restaurant_app,public
DB_SSLMODE=require

RESTAURANT_CURRENCY=EGP
RESTAURANT_TIMEZONE=Africa/Cairo
RESTAURANT_LOCALE=ar
RESTAURANT_FALLBACK_LOCALE=en
RESTAURANT_ALLOW_GUEST_CHECKOUT=true

PAYMENT_CURRENCY=EGP
PAYMENT_GATEWAY=paymob
PAYMENT_ONLINE_METHOD=vodafone_cash
PAYMOB_BASE_URL=https://accept.paymob.com
PAYMOB_SECRET_KEY=
PAYMOB_PUBLIC_KEY=
PAYMOB_WALLET_INTEGRATION_ID=
PAYMOB_HMAC_SECRET=
PAYMOB_TIMEOUT_SECONDS=20
```

استبدل `DB_URL` بالقيمة التي نسختها من Supabase. لا تستخدم القيم المثال حرفيًا. لا توجد حاجة الآن لإضافة مفاتيح Supabase Frontend أو `service_role` إلى Vue.

## 7) تشغيل التهيئة والاختبارات

بعد ضبط الاتصال:

```powershell
php artisan config:clear
php artisan migrate:status
php artisan migrate
php artisan test --filter=RestaurantBranchSchemaTest
```

لو `migrate:status` أو الاتصال فشل، لا تعِد إنشاء الجداول يدويًا. أرسل نص الخطأ كاملًا بعد حذف/إخفاء أي Host أو كلمة مرور أو مفتاح سري.

## 8) ما الذي يجب أن يظهر بعد النجاح؟

داخل Schema `restaurant_app` ستظهر جداول Laravel الافتراضية بالإضافة إلى:

- `restaurants`
- `branches`
- `branch_business_hours`
- `branch_special_hours`
- `branch_settings`
- `migrations`

وتأكد من وجود فهرس PostgreSQL الجزئي `branches_single_primary_per_restaurant_unique`.

## 9) تشغيل التطبيق

```powershell
php artisan serve
```

في هذه المرحلة لا توجد واجهة مطعم بعد؛ Checkpoint 01 يثبت الأساس والاتصال ومخطط المطعم والفروع فقط.
