# Checkpoint 02 — Authentication & Authorization

الحزمة Overlay. استخرجها داخل جذر `restaurant-system`. قبل التنفيذ، احتفظ بنسخة Git commit من حالة Checkpoint 01.

## 1) Git checkpoint قبل التعديل

```powershell
git status --short --branch
git add -A
git commit -m "chore: verify restaurant foundation"
git tag -a checkpoint-01-foundation -m "Checkpoint 01: Foundation verified"
```

إذا كان Tag `checkpoint-01-foundation` موجودًا بالفعل، لا تعِد إنشاءه؛ تحقق عبر `git tag --list`.

## 2) تثبيت حزم Backend

```powershell
composer require laravel/fortify inertiajs/inertia-laravel spatie/laravel-permission:^8.0
```

Spatie Permission v8 يدعم Laravel 12 و13 وPHP 8.3+. Fortify يوفر مسارات ومكوّنات المصادقة الخلفية، وInertia يربطها بواجهات Vue.

## 3) استخراج ZIP

استخرج ملفات `restaurant-checkpoint-02.zip` فوق جذر المشروع. وافق على استبدال `app/Models/User.php` و`app/Modules/Branches/Models/Branch.php` و`vite.config.js`؛ هذه نسخ محدثة من الملفات التي نحتاج تعديلها. راجع أي ملفات أخرى قبل الاستبدال.

## 4) إعداد Spatie Teams قبل Migration

الحزمة تتضمن `config/permission.php` مضبوطًا مسبقًا على:

- `teams => true`
- `team_foreign_key => branch_id`

لا تنشر Config مرة أخرى فوق الملف الحالي.

انشر ملفات Spatie بالطريقة الموثقة للإصدار v8، دون تحديد Tag:

```powershell
composer show spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

**مهم:** لا تضف `--force`. يوجد بالفعل ملف `config/permission.php` مخصص داخل هذه الحزمة، ويجب الحفاظ عليه. أمر النشر الافتراضي لا يستبدل الملفات الموجودة؛ المطلوب أن ينشر Migration فقط ويتجاوز ملف الإعدادات الموجود. إذا أظهر الطرفية أنه سيستبدل `config/permission.php`، ألغِ العملية ولا توافق على الاستبدال.

بعد النشر، تأكد من ظهور ملف Migration جديد في `database/migrations/` اسمه قريب من `*_create_permission_tables.php`، وافتحه وتأكد أن جداول Teams تستخدم العمود `branch_id`. ثم تأكد أن `config/permission.php` يحتوي:

- `'teams' => true`
- `'team_foreign_key' => 'branch_id'`

Spatie يشترط ضبط Teams قبل تشغيل Migration لأول مرة. مرجع التثبيت الرسمي v8: https://spatie.be/docs/laravel-permission/v8/installation-laravel

## 5) إعداد Fortify وInertia

### أ. تسجيل Service Providers

أضف هذين السطرين إلى مصفوفة providers في `bootstrap/providers.php`، ولا تحذف `AppServiceProvider`:

```php
App\Providers\RestaurantServiceProvider::class,
App\Providers\RestaurantFortifyServiceProvider::class,
```

### ب. إضافة Inertia middleware

في `bootstrap/app.php` أضف الاستيراد:

```php
use App\Http\Middleware\HandleInertiaRequests;
```

ثم داخل `withMiddleware`:

```php
$middleware->web(append: [HandleInertiaRequests::class]);
```

### ج. تحميل المسارات

أضف السطر التالي في نهاية `routes/web.php`:

```php
require __DIR__.'/restaurant_auth.php';
```

### د. تثبيت Vue وTailwind

```powershell
npm install vue @inertiajs/vue3
npm install -D @vitejs/plugin-vue tailwindcss @tailwindcss/vite
```

ثم:

```powershell
npm run build
```

ملف `vite.config.js` وملفات Vue وBlade موجودة في الحزمة. إذا كان لديك تخصيص سابق في Vite، ادمج الإضافات بدل حذف تخصيصك. إعداد إرسال روابط استعادة كلمة المرور يحتاج لاحقًا إلى SMTP مضبوط في `.env`؛ تسجيل الدخول والتسجيل لا يتطلبان إعداد البريد.

## 6) تنفيذ Migrations

أولاً راجع القائمة:

```powershell
php artisan config:clear
php artisan migrate:status
```

ثم نفذ:

```powershell
php artisan migrate
```

هذا سيضيف جداول `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` من Spatie بالإضافة إلى:

- `customer_profiles`
- `branch_user_assignments`
- `users.is_active`
- `users.last_login_at`

**مهم:** لا تستخدم `migrate:fresh` أو `migrate:refresh` على قاعدة Supabase الحالية.

## 7) إنشاء المطعم والفرع الأول (إذا لم يكن لهما بيانات)

```powershell
php artisan restaurant:setup:first-branch
```

الأمر ينشئ مطعمًا وفرعًا رئيسيًا واحدًا فقط. إذا كانت بيانات المطعم موجودة بالفعل، لا تشغله.

## 8) مزامنة الأدوار والصلاحيات وإنشاء حساب المالك

```powershell
php artisan restaurant:access:sync
php artisan restaurant:staff:create --branch=MAIN --role=restaurant_owner
```

استبدل `MAIN` بكود الفرع الفعلي. كلمة مرور الحساب تُدخل تفاعليًا ولا تُكتب في سطر الأوامر.

الأدوار الحالية: `restaurant_owner`, `branch_manager`, `cashier`, `kitchen_staff`, `inventory_manager`, `delivery_staff`.

عند إضافة فرع جديد مستقبلًا، نفّذ `restaurant:access:sync` مرة أخرى ثم عيّن الموظفين للفرع الجديد.

## 9) اختبار Auth وRBAC

```powershell
php artisan test --filter=CustomerRegistrationTest
php artisan test --filter=BranchAuthorizationTest
```

ملف `phpunit.xml` في Laravel الجديد يستخدم SQLite in-memory عادةً؛ راجع بيئة الاختبار قبل تشغيله، ولا تجعل الاختبارات تستخدم قاعدة Supabase الإنتاجية. لا تشغّل الاختبارات إن كانت `DB_CONNECTION=pgsql` في بيئة PHPUnit.

## 10) تشغيل الواجهة

```powershell
npm run dev
php artisan serve
```

افتح `/login` أو `/register`. إنشاء الحساب العام ينشئ Customer فقط. حسابات الموظفين تُنشأ من Artisan command ولا يمكن للعميل منح نفسه Role.

للوحة الفرع: سجل دخول بحساب الموظف ثم افتح `/admin/branches/{BRANCH_ID}/dashboard`.

## 11) الاختبارات المطلوبة قبل إغلاق Checkpoint

```powershell
php artisan test --filter=CustomerRegistrationTest
php artisan test --filter=BranchAuthorizationTest
php artisan route:list --except-vendor
npm run build
```

## 12) Git commit بعد نجاح الاختبارات

```powershell
git add -A
git commit -m "feat(auth): add customer auth and branch-scoped permissions"
git tag -a checkpoint-02-auth-authorization -m "Checkpoint 02: Authentication and Authorization"
git push origin main --tags
```

لو لم يتم إعداد GitHub remote بعد، أكمل الإعداد من GitHub أولًا ولا تنفذ `git push` قبل ضبط remote.
