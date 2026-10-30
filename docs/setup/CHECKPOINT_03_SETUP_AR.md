# دليل تنفيذ Checkpoint 03 — المنيو والمنتجات

> نفّذ هذه الخطوات من PowerShell داخل `C:\laravel\restaurant-system`.

## 1. احفظ Checkpoint 02 في Git أولاً

تحقق من الحالة:

```powershell
git status --short --branch
git log -3 --oneline
git tag --list
```

إذا لم تكن تعديلات Checkpoint 02 محفوظة، وبعد التأكد من استبعاد `.env`:

```powershell
git add -A
git commit -m "feat(auth): finish customer auth and branch access"
git tag -a checkpoint-02-auth-authorization -m "Checkpoint 02 verified"
```

إذا كان الـTag موجودًا بالفعل فلا تنشئه مرة أخرى.

## 2. استخرج الحزمة

استخرج `restaurant-checkpoint-03.zip` فوق جذر المشروع. الحزمة لا تحتوي `.env` ولا ملفات Vendor ولا Build output.

## 3. اربط Routes الجديدة

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\apply-checkpoint-03-routes.ps1
```

السكريبت يضيف `require` واحدًا في نهاية `routes/restaurant_auth.php`، ويحتفظ بنسخة احتياطية باسم `routes/restaurant_auth.php.pre-cp03.bak`. لا يغيّر ملفات قاعدة البيانات.

## 4. نظّف إعدادات Laravel وتحقق من المسارات

```powershell
php artisan optimize:clear
php artisan route:list --path=admin/branches
```

يجب أن تظهر المسارات التالية تحت فرع محدد:

- `GET admin/branches/{branch}/menu/categories`
- `POST admin/branches/{branch}/menu/categories`
- `PUT admin/branches/{branch}/menu/categories/{category}`
- `DELETE admin/branches/{branch}/menu/categories/{category}`
- `GET admin/branches/{branch}/menu/products`
- `POST admin/branches/{branch}/menu/products`
- `PUT admin/branches/{branch}/menu/products/{product}`
- `DELETE admin/branches/{branch}/menu/products/{product}`
- `GET admin/branches/{branch}/menu/modifiers`
- `POST admin/branches/{branch}/menu/modifiers`
- `PUT admin/branches/{branch}/menu/modifiers/{group}`
- `DELETE admin/branches/{branch}/menu/modifiers/{group}`

## 5. راجع Migrations قبل التنفيذ

```powershell
php artisan migrate:status
```

راجع أن الملفات الجديدة هي `000009` حتى `000016`. ثم شغّل:

```powershell
php artisan migrate
```

**ممنوع:** `migrate:fresh` أو `migrate:refresh` أو حذف جداول Supabase. المرحلة تضيف 8 جداول فقط:

1. `menu_categories`
2. `products`
3. `product_variants`
4. `branch_product_settings`
5. `modifier_groups`
6. `modifier_options`
7. `product_modifier_groups`
8. `branch_modifier_prices`

لا تحتاج هذه المرحلة نشر Migration من Supabase Dashboard؛ Laravel Migrations هي مصدر التغيير.

## 6. تأكد من صلاحيات المنيو

صلاحية `menu.view` تسمح بعرض المنيو. أضفنا `menu.catalog.manage` لتعديل الكتالوج المشترك على مستوى المطعم، وتُمنح افتراضيًا لدور `restaurant_owner` فقط؛ مدير الفرع يستطيع العرض لكن لا يستطيع تعديل بيانات مشتركة تؤثر على فروع أخرى. صلاحية `menu.manage` محفوظة لعمليات إعدادات المنيو الخاصة بالفرع التي سنضيفها لاحقًا.

بعد استخراج الحزمة شغّل مزامنة الأدوار لإضافة الصلاحية الجديدة للدور المالك:

```powershell
php artisan restaurant:access:sync
```

هذا الأمر يزامن الصلاحيات والأدوار ولا ينشئ طلبات أو منتجات.

## 7. شغّل الاختبارات

```powershell
php artisan test --filter=MenuManagementTest
php artisan test --filter=CustomerRegistrationTest
php artisan test --filter=BranchAuthorizationTest
```

اختبارات Laravel يجب أن تستخدم SQLite in-memory كما هو مضبوط في `phpunit.xml`، وليس قاعدة Supabase الحقيقية. لا تعدّل إعدادات الاختبار إلى بيانات إنتاج.

## 8. ابنِ الواجهة

```powershell
npm run build
```

للتطوير المحلي استخدم نافذتين:

```powershell
php artisan serve
```

```powershell
npm run dev
```

## 9. افتح صفحات المنيو

استخرج ID الفرع MAIN:

```powershell
php artisan tinker --execute="dump(\App\Modules\Branches\Models\Branch::where('code', 'MAIN')->value('id'));"
```

بعد تسجيل الدخول بحساب مالك المطعم لديه `menu.view` و`menu.catalog.manage` افتح، مع استبدال `1` بالـID الذي ظهر:

- `/admin/branches/1/menu/categories`
- `/admin/branches/1/menu/products`
- `/admin/branches/1/menu/modifiers`

يجب أن تظهر روابط الصفحات أيضًا في لوحة الفرع للحسابات التي لديها `menu.view`.

## 10. Git بعد النجاح

بعد نجاح كل الاختبارات والـBuild:

```powershell
git add -A
git commit -m "feat(menu): add categories products variants and modifiers"
git tag -a checkpoint-03-menu-products -m "Checkpoint 03 verified"
```

إذا كان GitHub remote مضبوطًا:

```powershell
git push origin main --tags
```

## تصميم الأسعار

- `product_variants.base_price` هو السعر الافتراضي العام للمنتج.
- `branch_product_settings.price_override` اختياري؛ إذا كان NULL يُستخدم السعر الأساسي. قيمة `0.00` تعتبر سعرًا محددًا ولا تُعامل كـNULL.
- `modifier_options.price_delta` هو فرق السعر الأساسي للإضافة.
- حقول `translations` تخزن الترجمة الإنجليزية الاختيارية بالشكل `{'en': {'name': '...', 'description': '...'}}`، بينما يظل حقل `name` الأساسي باللغة الافتراضية العربية.
- `branch_modifier_prices.price_delta_override` يتيح تخصيص فرق السعر في فرع معيّن.
- يتم تخزين التوفر بصورة مستقلة عن السعر لكل فرع.
- هذه المرحلة لا تنشئ Orders ولا تخصم المخزون. السعر النهائي يُعاد حسابه في Checkout لاحقًا من بيانات قاعدة البيانات، ولا يؤخذ من المتصفح.

## ملاحظات حدود المرحلة

- رفع الصور إلى Supabase Storage سيتم في مرحلة مستقلة بعد وضع سياسة الملفات والصلاحيات؛ حقول `image_path` موجودة فقط كبنية بيانات.
- الحذف في الواجهة هو أرشفة Soft Delete للمنتج/التصنيف/مجموعة الإضافات، ولا يحذف سجلات المعاملات.
- خيارات الـModifiers غير المرسلة عند تعديل المجموعة يتم تعطيلها بدل حذفها.
- حذف تصنيف مرتبط بمنتجات أو تصنيفات فرعية مرفوض؛ انقل العناصر أولاً.
