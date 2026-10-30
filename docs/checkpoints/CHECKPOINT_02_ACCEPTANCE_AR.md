# Checkpoint 02 — Acceptance Checklist

لا يُغلق Checkpoint 02 إلا بعد التحقق من:

- [ ] تثبيت `laravel/fortify`, `inertiajs/inertia-laravel`, `spatie/laravel-permission:^8.0`.
- [ ] تثبيت `vue`, `@inertiajs/vue3`, `@vitejs/plugin-vue`, `tailwindcss`, `@tailwindcss/vite`.
- [ ] تفعيل Spatie Teams مع `branch_id` قبل أول migration.
- [ ] تسجيل Service Providers وInertia middleware ومسار `restaurant_auth.php`.
- [ ] تشغيل migrations الإضافية دون `migrate:fresh`.
- [ ] إنشاء المطعم والفرع الأول إن لم يكونا موجودين.
- [ ] مزامنة الأدوار والصلاحيات.
- [ ] إنشاء حساب مالك الفرع بأمر تفاعلي.
- [ ] اختبار تسجيل عميل وعدم منحه صلاحية موظف.
- [ ] اختبار منع مستخدم غير عضو في الفرع.
- [ ] اختبار منع عضو الفرع دون permission.
- [ ] اختبار نجاح المالك في فرعه ورفضه في فرع غير معيّن له.
- [ ] نجاح `npm run build`.
- [ ] نجاح كل اختبارات Feature.
- [ ] Commit + tag + push إلى GitHub بعد تأكيد إعداد remote.
