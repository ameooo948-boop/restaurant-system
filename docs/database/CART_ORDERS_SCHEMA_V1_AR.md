# Cart & Orders Database Schema — v1

## carts
- `public_id`: UUID عام لا يمنح صلاحية الوصول وحده.
- `branch_id`: الفرع الذي تنتمي له السلة.
- `user_id`: مالك السلة للمستخدم المسجل أو NULL للضيف.
- `guest_token_hash`: SHA-256 لرمز عشوائي مخزن في Session، ولا نخزن الرمز الخام في قاعدة البيانات.
- `active_owner_key`: مفتاح مشتق من المالك والفرع، وفريد مع branch_id للسلال النشطة.
- `status`: active / converted / expired / abandoned.

## cart_items / cart_item_modifiers
تخزن الاختيارات فقط، وليس السعر النهائي الموثوق. يعاد تسعير كل عنصر على الخادم عند العرض وCheckout.

## orders
تخزن بيانات العميل والعنوان كـSnapshots، وتحتوي `idempotency_key` فريدًا و`cart_id` فريدًا لمنع تكرار إنشاء الطلب من السلة نفسها.

## order_items / order_item_modifiers
تحفظ أسماء المنتجات والأحجام ومجموعات الإضافات واختياراتها وأسعارها وقت الشراء. حذف أو تعديل المنيو مستقبلًا لا يغير الطلب القديم.

## order_status_histories
سجل زمني لا يعتمد على الحالة الحالية فقط.

## payments
يسجل وسيلة الدفع والمبلغ وحالة التحصيل. لا يتم ضبط `succeeded` إلا من مسار خادم موثوق بعد التحقق من جهة التحصيل أو إجراء نقدي معتمد.

## Financial calculation
تُجرى العمليات الحسابية بوحدات القرش Integer Minor Units لتجنب أخطاء الكسور العشرية. تحفظ القيم في PostgreSQL بصيغة `DECIMAL(12,2)`.
