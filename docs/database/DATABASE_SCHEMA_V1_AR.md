# Database Schema v1.0 — التصميم المستهدف

> هذه الوثيقة هي مخطط النظام الكامل المقترح. Checkpoint 01 ينفّذ جداول المطعم والفروع وساعات العمل فقط. بقية الجداول ستدخل في Checkpoints منفصلة بعد مراجعة العلاقات والاختبارات.

## قواعد عامة

- Primary keys: `BIGINT` identity في الجداول الداخلية.
- جميع الجداول المملوكة لفرع تحمل `branch_id` مباشرة أو عبر علاقة واضحة.
- العملة: `CHAR(3)` للـISO code، والمبالغ `NUMERIC(12,2)`؛ لا تستخدم Float للأموال.
- الكميات: `NUMERIC(14,3)` مع وحدة قياس أساسية لكل مكوّن.
- التوقيت: `TIMESTAMPTZ` للأحداث، مع تخزين UTC وعرضها بتوقيت `Africa/Cairo`.
- أسعار الطلبات والعناوين وأسماء المنتجات تُحفظ كـSnapshots لحظة الشراء.
- كل FK له سياسة حذف صريحة. البيانات المالية والمخزنية لا تُحذف بعد الترحيل.
- الفهارس المركبة تبدأ غالبًا بـ`branch_id` عندما تكون الاستعلامات محصورة في الفرع.

## 1. Restaurants & Branches

| Table | Key columns / constraints |
|---|---|
| `restaurants` | name, slug UNIQUE, legal_name, phone, email, logo_path, currency, timezone, default_locale, supported_locales JSON, is_active |
| `branches` | restaurant_id FK, code, name, slug, phone, email, address_line, city, area, latitude, longitude, is_primary, is_active, deleted_at; UNIQUE(restaurant_id, code), UNIQUE(restaurant_id, slug); PostgreSQL partial unique index لضمان فرع رئيسي واحد غير محذوف لكل مطعم |
| `branch_business_hours` | branch_id FK, day_of_week (0 Sunday..6 Saturday), opens_at, closes_at, is_closed; UNIQUE(branch_id, day_of_week) |
| `branch_special_hours` | branch_id FK, service_date, label, opens_at, closes_at, is_closed; UNIQUE(branch_id, service_date) |
| `branch_settings` | branch_id FK, setting_key, setting_value JSON; UNIQUE(branch_id, setting_key) |

## 2. Identity & Access

| Table | Purpose |
|---|---|
| `users` | Laravel accounts and credentials |
| `customer_profiles` | optional profile linked 1:1 to user |
| `customer_addresses` | saved addresses for registered customers |
| `branch_user_assignments` | staff-to-branch membership; one user may access multiple assigned branches |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie Permission RBAC tables |

العميل الضيف لا يحتاج User row. بيانات الاسم والهاتف والعنوان تُحفظ Snapshot داخل الطلب. موظفو المطعم يجب أن يمر كل وصول لهم عبر Policy ونطاق فرع مُتحقق منه؛ مجرد معرفة ID لا تمنح صلاحية.

## 3. Menu & Pricing

| Table | Purpose / key columns |
|---|---|
| `menu_categories` | parent_id, name, slug, image_path, sort_order, is_active |
| `products` | category_id, name, slug, description, image_path, is_active |
| `product_variants` | product_id, name, sku, base_price, is_default |
| `branch_product_settings` | branch_id, product_variant_id, price, is_available; UNIQUE(branch_id, product_variant_id) |
| `modifier_groups` | name, min_selections, max_selections, is_required |
| `modifier_options` | modifier_group_id, name, price_delta, is_active |
| `product_modifier_groups` | product_id, modifier_group_id, sort_order |
| `branch_modifier_prices` | branch_id, modifier_option_id, price_delta |

السعر النهائي يُحسب على الخادم من سعر الفرع والـVariant والـModifiers والخصومات والضرائب. لا يثق الخادم في مبالغ يرسلها المتصفح.

## 4. Cart & Orders

| Table | Purpose / key columns |
|---|---|
| `carts` | customer_profile_id nullable, guest_token_hash, branch_id, expires_at |
| `cart_items` | cart_id, product_variant_id, quantity, notes |
| `cart_item_modifiers` | cart_item_id, modifier_option_id, quantity |
| `orders` | public_id, branch_id, customer_profile_id nullable, business_date, order_number, order_type, order_status, payment_status, payment_method, order_source, totals, guest/customer snapshots, delivery_address_snapshot, table_id nullable, idempotency_key UNIQUE, placed_at |
| `order_items` | order_id, product_variant_id nullable, product/variant/SKU snapshots, quantity, unit_price, tax, line_total |
| `order_item_modifiers` | order_item_id, option_name_snapshot, price_delta, quantity |
| `order_status_histories` | order_id, old_status, new_status, changed_by, note, created_at |
| `order_number_sequences` | branch_id, business_date, last_number; UNIQUE(branch_id, business_date) |
| `order_discounts` | order_id, coupon_id nullable, discount_amount, description_snapshot |

رقم الطلب unique داخل الفرع واليوم التشغيلي. `idempotency_key` يمنع تكرار Checkout عند الضغط المتكرر أو إعادة إرسال الطلب. حالة الطلب وحالة الدفع مساران منفصلان.

### Order types

- `delivery`
- `pickup`
- `dine_in`

### Order statuses (first release)

- `pending_confirmation`
- `confirmed`
- `preparing`
- `ready`
- `out_for_delivery`
- `delivered`
- `completed`
- `cancelled`

أي تغيير حالة يجب أن يمر عبر Transition Action يتحقق من الانتقال المسموح ويسجل history.

## 5. Payments — Vodafone Cash

| Table | Purpose / key columns |
|---|---|
| `payments` | order_id, provider, method=`vodafone_cash` or `cash`, provider_reference, provider_transaction_id, amount, currency, status, paid_at |
| `refunds` | payment_id, amount, status, reason, provider_reference |
| `payment_webhook_events` | provider, event_id UNIQUE per provider, payload JSON, signature_verified_at, processed_at |

Paymob هو مزود مرشح لتوصيل Vodafone Cash Mobile Wallets، وليس قرارًا تجاريًا نهائيًا قبل فتح حساب التاجر ومراجعة شروط التفعيل والرسوم. لا يخزن الموقع Wallet PIN أو OTP أو بيانات حساسة للمحفظة. إنشاء Checkout يتم من Backend، ويجب التحقق من HMAC في Webhook. Redirect المستخدم لا يعتبر إثبات دفع.

## 6. Kitchen Display System

| Table | Purpose / key columns |
|---|---|
| `kitchen_stations` | branch_id, name, code, is_active |
| `kitchen_station_products` | kitchen_station_id, product_id |
| `kitchen_tickets` | order_id, kitchen_station_id, status, started_at, completed_at |
| `kitchen_ticket_items` | kitchen_ticket_id, order_item_id, status, quantity |

الطلب الذي يحتوي عناصر من محطات مختلفة ينشئ Ticket لكل محطة مع ربط العناصر الأصلية، ويكتمل الطلب عندما تنتهي كل التذاكر المطلوبة.

## 7. Advanced Inventory

| Table | Purpose / key columns |
|---|---|
| `measurement_units` | name, symbol, dimension (mass/volume/count) |
| `ingredients` | name, sku, base_unit_id, reorder_level, is_active |
| `ingredient_unit_conversions` | ingredient_id, unit_id, to_base_factor |
| `recipes` | product_variant_id, version, is_active |
| `recipe_items` | recipe_id, ingredient_id, quantity_in_base_unit, waste_percentage |
| `modifier_option_ingredients` | modifier_option_id, ingredient_id, quantity_in_base_unit |
| `branch_ingredient_stocks` | branch_id, ingredient_id, on_hand_qty, reserved_qty; UNIQUE(branch_id, ingredient_id) |
| `stock_movements` | branch_id, ingredient_id, movement_type, quantity_delta, source_type, source_id, actor_id, occurred_at |
| `stock_reservations` | order_id, ingredient_id, reserved_qty, status |
| `suppliers` | name, phone, address, tax_number nullable |
| `purchase_orders` | branch_id, supplier_id, status, total |
| `purchase_order_items` | purchase_order_id, ingredient_id, quantity, unit_cost |
| `goods_receipts` | purchase_order_id, received_by, received_at |
| `goods_receipt_items` | goods_receipt_id, ingredient_id, received_qty, unit_cost |
| `inventory_counts` | branch_id, status, counted_by, posted_at |
| `inventory_count_items` | inventory_count_id, ingredient_id, expected_qty, counted_qty |

مخزون الفرع يُدار بوحدة أساسية للمكوّن. لا يتم تحديث الكمية مباشرة من واجهة الإدارة. كل زيادة أو خصم أو تسوية يولّد Stock Movement. الحجز والاستهلاك والتحرير تتم داخل Transactions مع Row Locks لمنع overselling.

## 8. Delivery, Dining & Promotions

| Table | Purpose / key columns |
|---|---|
| `delivery_zones` | branch_id, name, fee, min_order_amount, polygon/coverage metadata |
| `deliveries` | order_id, driver_user_id, status, assigned_at, delivered_at |
| `restaurant_tables` | branch_id, table_number, capacity, is_active |
| `table_sessions` | restaurant_table_id, opened_at, closed_at, status |
| `coupons` | code, discount_type, discount_value, usage_limit, starts_at, expires_at, is_active |
| `coupon_redemptions` | coupon_id, order_id, customer_profile_id nullable, discount_amount |

## 9. Reliability & Audit

| Table | Purpose |
|---|---|
| `audit_logs` | من نفذ أي تعديل حساس ومتى وعلى أي سجل |
| `outbox_messages` | أحداث تُحفظ مع الـtransaction ثم يعالجها worker بعد commit |
| `notifications` | Laravel database notifications |
| `jobs`, `failed_jobs`, `job_batches` | Laravel queues حسب إعدادات النشر |

## تنفيذ الـSchema

لا ننشئ كل هذه الجداول في Migration واحدة. كل Checkpoint يضيف مجموعة مترابطة، وبعد كل مجموعة نشغل migrations والاختبارات ونراجع القيود والفهارس قبل المتابعة.
