<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    branch: { type: Object, required: true },
    products: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    modifierGroups: { type: Array, default: () => [] },
    canManageCatalog: { type: Boolean, default: false },
});

const editingId = ref(null);
const blankVariant = () => ({ id: null, name: 'عادي', translations: { en: { name: 'Regular' } }, sku: '', base_price: '0.00', is_default: true, is_active: true, branch_price: '', branch_is_available: true });
const form = useForm({ category_id: '', name: '', slug: '', description: '', translations: { en: { name: '', description: '' } }, sort_order: 0, preparation_time_minutes: '', is_active: true, variants: [blankVariant()], modifier_group_ids: [] });

function resetForm() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.variants = [blankVariant()];
    form.is_active = true;
}

function editProduct(product) {
    editingId.value = product.id;
    form.category_id = product.category_id ?? '';
    form.name = product.name;
    form.slug = product.slug;
    form.description = product.description ?? '';
    form.translations.en.name = product.translations?.en?.name ?? '';
    form.translations.en.description = product.translations?.en?.description ?? '';
    form.sort_order = product.sort_order;
    form.preparation_time_minutes = product.preparation_time_minutes ?? '';
    form.is_active = product.is_active;
    form.variants = product.variants.map((variant) => {
        const setting = variant.branch_settings?.[0];
        return {
            id: variant.id,
            name: variant.name,
            translations: { en: { name: variant.translations?.en?.name ?? '' } },
            sku: variant.sku ?? '',
            base_price: variant.base_price,
            is_default: variant.is_default,
            is_active: variant.is_active,
            branch_price: setting?.price_override ?? '',
            branch_is_available: setting?.is_available ?? true,
        };
    });
    form.modifier_group_ids = product.modifier_groups.map((group) => group.id);
    form.clearErrors();
}

function setDefaultVariant(index) {
    form.variants.forEach((item, currentIndex) => { item.is_default = currentIndex === index; });
}

function addVariant() {
    form.variants.push({ ...blankVariant(), name: `حجم ${form.variants.length + 1}`, is_default: false });
}

function removeVariant(index) {
    if (form.variants.length <= 1) return;
    const wasDefault = form.variants[index].is_default;
    form.variants.splice(index, 1);
    if (wasDefault) form.variants[0].is_default = true;
}

function submit() {
    const url = `/admin/branches/${props.branch.id}/menu/products`;
    if (editingId.value) form.put(`${url}/${editingId.value}`, { onSuccess: resetForm });
    else form.post(url, { onSuccess: resetForm });
}

function effectiveBranchPrice(variant) {
    return variant.branch_settings?.[0]?.price_override ?? variant.base_price;
}

function archiveProduct(product) {
    if (!window.confirm(`أرشفة المنتج «${product.name}»؟`)) return;
    router.delete(`/admin/branches/${props.branch.id}/menu/products/${product.id}`);
}
</script>

<template>
    <div dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">{{ branch.name }} · إدارة المنيو</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">المنتجات والأحجام</h1>
                <p class="mt-1 text-sm text-slate-500">السعر الأساسي للمنتج مع إمكانية تحديد سعر وتوفر مختلف لكل فرع.</p>
            </div>
            <div class="flex gap-2">
                <a :href="`/admin/branches/${branch.id}/menu/categories`" class="rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-white">التصنيفات</a>
                <a :href="`/admin/branches/${branch.id}/menu/modifiers`" class="rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700">الإضافات</a>
            </div>
        </header>

        <div class="grid gap-6 xl:grid-cols-[minmax(320px,440px)_1fr]">
            <form v-if="canManageCatalog" @submit.prevent="submit" class="h-fit space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-bold text-slate-900">{{ editingId ? 'تعديل المنتج' : 'إضافة منتج' }}</h2>
                    <button v-if="editingId" type="button" @click="resetForm" class="text-sm text-slate-500">منتج جديد</button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block space-y-1 text-sm sm:col-span-2">
                        <span class="font-medium text-slate-700">اسم المنتج *</span>
                        <input v-model="form.name" required maxlength="160" class="w-full rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500" placeholder="مثلاً: برجر كلاسيك" />
                        <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                    </label>
                    <label class="block space-y-1 text-sm">
                        <span class="font-medium text-slate-700">الاسم بالإنجليزية (اختياري)</span>
                        <input v-model="form.translations.en.name" dir="ltr" class="w-full rounded-lg border-slate-300" placeholder="Classic Burger" />
                    </label>
                    <label class="block space-y-1 text-sm">
                        <span class="font-medium text-slate-700">التصنيف</span>
                        <select v-model="form.category_id" class="w-full rounded-lg border-slate-300">
                            <option value="">بدون تصنيف</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </label>
                    <label class="block space-y-1 text-sm">
                        <span class="font-medium text-slate-700">وقت التحضير (دقيقة)</span>
                        <input v-model="form.preparation_time_minutes" type="number" min="0" class="w-full rounded-lg border-slate-300" />
                    </label>
                    <label class="block space-y-1 text-sm sm:col-span-2">
                        <span class="font-medium text-slate-700">الوصف</span>
                        <textarea v-model="form.description" rows="2" class="w-full rounded-lg border-slate-300" />
                    </label>
                    <label class="block space-y-1 text-sm sm:col-span-2">
                        <span class="font-medium text-slate-700">الوصف بالإنجليزية (اختياري)</span>
                        <textarea v-model="form.translations.en.description" dir="ltr" rows="2" class="w-full rounded-lg border-slate-300" />
                    </label>
                </div>

                <section class="space-y-3 rounded-xl bg-slate-50 p-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-800">الأحجام والأسعار</h3>
                        <button type="button" @click="addVariant" class="text-sm font-semibold text-amber-700">+ إضافة حجم</button>
                    </div>
                    <div v-for="(variant, index) in form.variants" :key="variant.id ?? `new-${index}`" class="space-y-2 rounded-lg border border-slate-200 bg-white p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500">الحجم {{ index + 1 }}</span>
                            <button v-if="form.variants.length > 1" type="button" @click="removeVariant(index)" class="text-xs text-red-600">إزالة من النموذج</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input v-model="variant.name" required maxlength="100" placeholder="اسم الحجم" class="rounded-lg border-slate-300 text-sm" />
                            <input v-model="variant.translations.en.name" dir="ltr" placeholder="English size name" class="rounded-lg border-slate-300 text-sm" />
                            <input v-model="variant.sku" dir="ltr" placeholder="SKU (اختياري)" class="rounded-lg border-slate-300 text-sm" />
                            <label class="space-y-1 text-xs text-slate-600">
                                السعر الأساسي (EGP)
                                <input v-model="variant.base_price" type="number" min="0" step="0.01" required class="w-full rounded-lg border-slate-300 text-sm" />
                            </label>
                            <label class="space-y-1 text-xs text-slate-600">
                                سعر هذا الفرع (اختياري)
                                <input v-model="variant.branch_price" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm" placeholder="استخدم السعر الأساسي" />
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-4 text-xs text-slate-600">
                            <label class="flex items-center gap-1.5"><input :checked="variant.is_default" type="radio" :name="`default-variant-${editingId ?? 'new'}`" @change="setDefaultVariant(index)" /> افتراضي</label>
                            <label class="flex items-center gap-1.5"><input v-model="variant.is_active" type="checkbox" /> نشط</label>
                            <label class="flex items-center gap-1.5"><input v-model="variant.branch_is_available" type="checkbox" /> متاح بهذا الفرع</label>
                        </div>
                    </div>
                    <p v-if="form.errors.variants" class="text-xs text-red-600">{{ form.errors.variants }}</p>
                </section>

                <section v-if="modifierGroups.length" class="space-y-2">
                    <h3 class="text-sm font-bold text-slate-800">مجموعات الإضافات</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="group in modifierGroups" :key="group.id" class="flex items-center gap-2 rounded-lg border border-slate-200 p-2 text-sm">
                            <input v-model="form.modifier_group_ids" type="checkbox" :value="group.id" class="rounded border-slate-300 text-amber-600" />
                            <span>{{ group.name }}</span>
                        </label>
                    </div>
                </section>
                <label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="form.is_active" type="checkbox" /> المنتج نشط</label>
                <button :disabled="form.processing" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-700 disabled:opacity-50">{{ form.processing ? 'جارٍ الحفظ...' : editingId ? 'حفظ التعديلات' : 'إضافة المنتج' }}</button>
                <div v-if="Object.keys(form.errors).length" class="space-y-1 text-xs text-red-600"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
            </form>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-bold text-slate-900">قائمة المنتجات</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ products.total }} منتج</p>
                </div>
                <div v-if="products.data.length" class="divide-y divide-slate-100">
                    <article v-for="product in products.data" :key="product.id" class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-slate-900">{{ product.name }}</h3>
                                <span :class="product.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="rounded-full px-2 py-0.5 text-xs">{{ product.is_active ? 'نشط' : 'موقوف' }}</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ product.category?.name ?? 'بدون تصنيف' }} · {{ product.variants.length }} أحجام</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span v-for="variant in product.variants" :key="variant.id" class="rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ variant.name }}: {{ Number(effectiveBranchPrice(variant)).toFixed(2) }} ج.م<span v-if="variant.branch_settings?.[0] && !variant.branch_settings[0].is_available"> · غير متاح</span></span>
                            </div>
                            <p v-if="product.modifier_groups.length" class="mt-2 text-xs text-slate-500">الإضافات: {{ product.modifier_groups.map((group) => group.name).join('، ') }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button v-if="canManageCatalog" type="button" @click="editProduct(product)" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50">تعديل</button>
                            <button v-if="canManageCatalog" type="button" @click="archiveProduct(product)" class="rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">أرشفة</button>
                        </div>
                    </article>
                </div>
                <div v-else class="p-10 text-center text-sm text-slate-500">لم تتم إضافة منتجات بعد.</div>
                <div v-if="products.links?.length > 3" class="flex flex-wrap gap-2 border-t border-slate-100 p-4">
                    <a v-for="link in products.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label" :class="[link.active ? 'bg-slate-900 text-white' : 'border border-slate-200 text-slate-600', !link.url ? 'pointer-events-none opacity-40' : '']" class="rounded-md px-3 py-1.5 text-xs" />
                </div>
            </section>
        </div>
    </div>
</template>
