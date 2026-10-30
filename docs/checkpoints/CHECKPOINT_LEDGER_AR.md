# سجل مشروع المطعم — Checkpoint Ledger

## القرارات الثابتة
- Laravel 13 / PHP 8.3+، Vue 3 + Inertia.js + Tailwind + Vite.
- Modular Monolith؛ Laravel هو مالك منطق الأعمال والمصادقة والتفويض.
- Supabase PostgreSQL هو قاعدة البيانات الرئيسية، وSupabase Storage للملفات عند تنفيذ تكامله.
- فرع واحد عند الإطلاق مع دعم عدة فروع.
- Delivery وPickup وDine-in؛ Guest وRegistered Customers.
- Advanced Inventory.
- EGP، Africa/Cairo، العربية افتراضيًا والإنجليزية اختيارية في حقول المحتوى.
- Vodafone Cash هو وسيلة الدفع الإلكتروني المخطط لها؛ مزود الربط التجاري يحتاج اعتمادًا قبل التحصيل الحقيقي.

## Checkpoints
| المرحلة | الحالة | الدليل |
|---|---|---|
| Checkpoint 00 — Architecture | معتمد | `docs/architecture/ARCHITECTURE_V1_AR.md` في حزمة 01 |
| Checkpoint 01 — Foundation | أكد المستخدم نجاح migrations واختبارين / 5 assertions | `RestaurantBranchSchemaTest` |
| Checkpoint 02 — Authentication & Authorization | أكد المستخدم نجاح الإصلاحات واختبارات Auth وBranch Authorization | `CustomerRegistrationTest`, `BranchAuthorizationTest` |
| Checkpoint 03 — Menu & Products | تم تسليم الملفات؛ ينتظر تنفيذ المستخدم واختباراته | هذا الإصدار |

## Git / GitHub
لم يتم التحقق في المحادثة من وجود remote أو من رفع `main` إلى GitHub. يجب التحقق باستخدام `git status`, `git log`, `git remote -v` قبل افتراض ذلك.

## سجل التسليمات
| الإصدار | الملف | الوصف |
|---|---|---|
| 0.1.0 | `restaurant-checkpoint-01.zip` | Foundation والمطعم والفروع |
| 0.2.0 | `restaurant-checkpoint-02.zip` | Authentication والصلاحيات وتعيينات الفروع |
| 0.2.1 | `restaurant-checkpoint-02-hotfix-0.2.1-overlay.zip` | تصحيح نشر Migration Spatie |
| 0.2.2 | `restaurant-checkpoint-02-hotfix-0.2.2-overlay.zip` | تسجيل Providers وتحميل المسارات |
| 0.3.0 | `restaurant-checkpoint-03.zip` | التصنيفات والمنتجات والأحجام والإضافات |

## بروتوكول العمل
- كل تعديل ملفات يُسلّم في ZIP Overlay مستقل بمسارات المشروع النسبية.
- كل ZIP يتضمن CHANGELOG وCHECKPOINT ودليل تشغيل عند الحاجة.
- لا تحتوي الحزم على `.env` أو كلمات مرور أو مفاتيح سرية.
- خطوات إعداد الجهاز والأوامر اليدوية تُكتب منفصلة ولا يلزم لها ZIP.
- لا يُغلق أي Checkpoint إلا بعد نتائج الاختبارات وBuild من جهاز المستخدم.
