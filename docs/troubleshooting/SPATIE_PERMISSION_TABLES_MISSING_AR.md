# حل مشكلة جداول Spatie غير الموجودة

## الأعراض

- `vendor:publish ... --tag=migrations` يعرض `No publishable resources for tag [migrations]`.
- `restaurant:access:sync` يفشل برسالة `relation "permissions" does not exist`.

## السبب

لم يتم نشر Migration الخاصة بحزمة `spatie/laravel-permission`، وبالتالي لم تُنشأ جداول `permissions` و`roles` والجداول الوسيطة. في تعليمات Spatie v8 الحالية يتم نشر إعدادات الحزمة وMigration عن طريق Service Provider دون تمرير `--tag=migrations`.

## الحل الآمن

نفّذ من جذر المشروع:

```powershell
composer show spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

لا تستخدم `--force`. احتفظ بملف `config/permission.php` المخصص الموجود بالفعل؛ إذا طُلب منك استبداله فألغِ العملية.

قبل تشغيل Migration:

1. تأكد أن Migration جديدة لـ`create_permission_tables` ظهرت في `database/migrations`.
2. تأكد أن `config/permission.php` يحتوي `teams => true` و`team_foreign_key => branch_id`.
3. نفّذ `php artisan config:clear`.
4. نفّذ `php artisan migrate:status` لمراجعة Migration الجديدة.
5. نفّذ `php artisan migrate` فقط. **لا تستخدم `migrate:fresh` أو `migrate:refresh` أو `db:wipe`.**
6. بعد نجاح Migration نفّذ `php artisan restaurant:access:sync`.

ثم تحقق:

```powershell
php artisan permission:show
php artisan migrate:status
```

## ملاحظات

- المطعم والفرع الأول تم إنشاؤهما بالفعل؛ لا تعِد تشغيل `restaurant:setup:first-branch`.
- هذا الإصلاح لا يحتاج إلى حذف أو تعديل أي بيانات من قاعدة Supabase الحالية.
