# تشخيص 404 في Checkpoint 04

هذا التصحيح **تشخيصي فقط**. لا يغير التطبيق أو قاعدة البيانات ولا ينفذ migrations.

## التطبيق

استخرج ZIP فوق جذر المشروع، ثم نفّذ من PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\diagnose-cp04-store-404.ps1
```

## كشف الاستثناء الأصلي

شغّل اختبارًا واحدًا:

```powershell
php artisan test --filter=test_checkout_is_idempotent_for_the_same_converted_cart
```

إضافة `withoutExceptionHandling()` داخل helper مؤقتًا تجعل Laravel يعرض الاستثناء الأصلي ومكانه بدل تحويله إلى HTTP 404. انسخ الـ exception class والـ stack trace كاملًا.

## استعادة ملف الاختبار

بعد أخذ الناتج، أعد الملف الأصلي:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\restore-cp04-store-404-diagnostic.ps1
```

السكريبت يأخذ نسخة احتياطية قبل التعديل، والاستعادة لا تلمس أي ملف آخر.

## معلومات إضافية

اجمع أيضًا ناتج:

```powershell
php artisan route:list --path=branches -v
php artisan route:list --path=cart -v
```

لا تشغل `migrate:fresh` أو `migrate:refresh`، ولا تنفذ أي SQL يدوي. لا حاجة إلى migration جديدة لهذا التشخيص.
