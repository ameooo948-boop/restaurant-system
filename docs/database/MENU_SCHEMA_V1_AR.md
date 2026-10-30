# Menu & Products Schema — v1

## 1. Menu Categories

`menu_categories`

- `restaurant_id`: مالك التصنيف.
- `parent_id`: تصنيف أب اختياري يدعم الهرمية.
- `name`, `slug`, `description`, `image_path`, `translations` (English overrides).
- `sort_order`, `is_active`, timestamps, `deleted_at`.
- Unique: `(restaurant_id, slug)`.

## 2. Products

`products`

- `restaurant_id`: المنتج مشترك على مستوى المطعم.
- `category_id`: تصنيف اختياري.
- `name`, `slug`, `description`, `image_path`, `translations` (English overrides).
- `sort_order`, `preparation_time_minutes`, `is_active`.
- Unique: `(restaurant_id, slug)`.
- Soft deletion للحفاظ على المرجع التاريخي.

## 3. Product Variants

`product_variants`

- `product_id`, `name`, `translations`, `sku`, `base_price`.
- `is_default`, `is_active`, `sort_order`.
- SKU فريد عند عدم كونه NULL؛ التطبيق يحوله إلى أحرف كبيرة قبل الحفظ لتوحيد الإدخال.
- في PostgreSQL يوجد Partial Unique Index يضمن Variant افتراضيًا واحدًا فقط لكل منتج.

## 4. Branch Product Settings

`branch_product_settings`

- `branch_id`, `product_variant_id`.
- `price_override` nullable: NULL يعني استخدام `base_price`.
- `is_available`: مستقل عن السعر.
- نافذة توفر اختيارية `available_from` و`available_until`.
- Unique: `(branch_id, product_variant_id)`.

## 5. Modifier Groups & Options

`modifier_groups`

- `restaurant_id`, `name`, `translations`, `selection_type` (`single` / `multiple`).
- `min_selections`, `max_selections`, `is_active`.

`modifier_options`

- `modifier_group_id`, `name`, `translations`, `price_delta`, `is_active`, `sort_order`.
- `price_delta` يسمح بالقيم السالبة لدعم إزالة/خصم مكونات؛ يجب على محرك التسعير منع وصول قيمة الطلب النهائية إلى أقل من صفر.

`product_modifier_groups`

- Pivot يربط المنتجات بمجموعات الإضافات مع `sort_order`.

## 6. Branch Modifier Overrides

`branch_modifier_prices`

- `branch_id`, `modifier_option_id`.
- `price_delta_override` nullable؛ NULL يعني استخدام `modifier_options.price_delta`.
- `is_available` مستقل عن السعر.

## 7. Trust boundaries

- كل Controller يقيّد التصنيفات والمنتجات والمجموعات بـ `branch.restaurant_id`.
- تعديل الكتالوج المشترك يتطلب `menu.catalog.manage` الممنوحة لدور `restaurant_owner`؛ الصلاحيات الفرعية لا تمنح تلقائيًا حق تعديل الكتالوج المشترك.
- `EnsureBranchAccess` يتحقق من تعيين الموظف للفرع.
- `EnsureBranchPermission` يتحقق من `menu.view` أو `menu.catalog.manage` بعد تعيين Spatie Team ID للفرع.
- لا يُقبل سعر من المتصفح باعتباره سعرًا نهائيًا؛ هذه القيم هي إعدادات كتالوج فقط. Checkout لاحقًا يعيد حساب السعر من DB.
