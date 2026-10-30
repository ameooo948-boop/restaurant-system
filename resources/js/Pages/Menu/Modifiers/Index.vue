<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    branch: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
    canManageCatalog: { type: Boolean, default: false },
});

const editingId = ref(null);
const newOption = () => ({ id: null, name: '', translations: { en: { name: '' } }, price_delta: '0.00', branch_price_delta: '', is_active: true, branch_is_available: true });
const form = useForm({ name: '', translations: { en: { name: '', description: '' } }, description: '', selection_type: 'single', min_selections: 0, max_selections: 1, sort_order: 0, is_active: true, options: [newOption()] });

function resetForm() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.options = [newOption()];
    form.selection_type = 'single';
    form.max_selections = 1;
    form.is_active = true;
}

function editGroup(group) {
    editingId.value = group.id;
    form.name = group.name;
    form.description = group.description ?? '';
    form.translations.en.name = group.translations?.en?.name ?? '';
    form.translations.en.description = group.translations?.en?.description ?? '';
    form.selection_type = group.selection_type;
    form.min_selections = group.min_selections;
    form.max_selections = group.max_selections ?? '';
    form.sort_order = group.sort_order;
    form.is_active = group.is_active;
    form.options = group.options.map((option) => {
        const branchPrice = option.branch_prices?.[0];
        return {
            id: option.id,
            name: option.name,
            translations: { en: { name: option.translations?.en?.name ?? '' } },
            price_delta: option.price_delta,
            branch_price_delta: branchPrice?.price_delta_override ?? '',
            is_active: option.is_active,
            branch_is_available: branchPrice?.is_available ?? true,
        };
    });
    form.clearErrors();
}

function addOption() { form.options.push(newOption()); }
function removeOption(index) {
    if (form.options.length <= 1) return;
    form.options.splice(index, 1);
}

function submit() {
    const url = `/admin/branches/${props.branch.id}/menu/modifiers`;
    if (form.selection_type === 'single') form.max_selections = 1;
    if (editingId.value) form.put(`${url}/${editingId.value}`, { onSuccess: resetForm });
    else form.post(url, { onSuccess: resetForm });
}

function archiveGroup(group) {
    if (!window.confirm(`أرشفة مجموعة «${group.name}»؟`)) return;
    router.delete(`/admin/branches/${props.branch.id}/menu/modifiers/${group.id}`);
}
</script>

<template>
    <div dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">{{ branch.name }} · إدارة المنيو</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">الإضافات والاختيارات</h1>
                <p class="mt-1 text-sm text-slate-500">أنشئ مجموعات مثل الجبن الإضافي، الصوصات، واختيار حجم المشروب.</p>
            </div>
            <div class="flex gap-2">
                <a :href="`/admin/branches/${branch.id}/menu/categories`" class="rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-white">التصنيفات</a>
                <a :href="`/admin/branches/${branch.id}/menu/products`" class="rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700">المنتجات</a>
            </div>
        </header>

        <div class="grid gap-6 xl:grid-cols-[minmax(320px,440px)_1fr]">
            <form v-if="canManageCatalog" @submit.prevent="submit" class="h-fit space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-slate-900">{{ editingId ? 'تعديل المجموعة' : 'مجموعة إضافات جديدة' }}</h2>
                    <button v-if="editingId" type="button" @click="resetForm" class="text-sm text-slate-500">إلغاء</button>
                </div>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">اسم المجموعة *</span>
                    <input v-model="form.name" required maxlength="120" class="w-full rounded-lg border-slate-300" placeholder="مثلاً: إضافات البرجر" />
                </label>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">الاسم بالإنجليزية (اختياري)</span>
                    <input v-model="form.translations.en.name" dir="ltr" class="w-full rounded-lg border-slate-300" />
                </label>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">الوصف</span>
                    <textarea v-model="form.description" rows="2" class="w-full rounded-lg border-slate-300" />
                </label>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">الوصف بالإنجليزية (اختياري)</span>
                    <textarea v-model="form.translations.en.description" dir="ltr" rows="2" class="w-full rounded-lg border-slate-300" />
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="space-y-1 text-xs text-slate-600">
                        نوع الاختيار
                        <select v-model="form.selection_type" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="single">اختيار واحد</option>
                            <option value="multiple">متعدد</option>
                        </select>
                    </label>
                    <label class="space-y-1 text-xs text-slate-600">
                        الحد الأدنى
                        <input v-model.number="form.min_selections" type="number" min="0" max="30" class="w-full rounded-lg border-slate-300 text-sm" />
                    </label>
                    <label class="space-y-1 text-xs text-slate-600">
                        الحد الأقصى
                        <input v-model.number="form.max_selections" type="number" min="1" max="30" :disabled="form.selection_type === 'single'" class="w-full rounded-lg border-slate-300 text-sm disabled:bg-slate-100" />
                    </label>
                </div>
                <p v-if="form.errors.min_selections || form.errors.max_selections" class="text-xs text-red-600">{{ form.errors.min_selections || form.errors.max_selections }}</p>

                <section class="space-y-3 rounded-xl bg-slate-50 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-bold text-slate-800">الاختيارات</h3>
                        <button type="button" @click="addOption" class="text-sm font-semibold text-amber-700">+ إضافة اختيار</button>
                    </div>
                    <div v-for="(option, index) in form.options" :key="option.id ?? `new-${index}`" class="space-y-2 rounded-lg border border-slate-200 bg-white p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500">اختيار {{ index + 1 }}</span>
                            <button v-if="form.options.length > 1" type="button" @click="removeOption(index)" class="text-xs text-red-600">إزالة</button>
                        </div>
                        <input v-model="option.name" required maxlength="120" placeholder="اسم الاختيار" class="w-full rounded-lg border-slate-300 text-sm" />
                        <input v-model="option.translations.en.name" dir="ltr" maxlength="120" placeholder="English option name" class="w-full rounded-lg border-slate-300 text-sm" />
                        <div class="grid grid-cols-2 gap-2">
                            <label class="space-y-1 text-xs text-slate-600">فرق السعر الأساسي
                                <input v-model="option.price_delta" type="number" step="0.01" class="w-full rounded-lg border-slate-300 text-sm" />
                            </label>
                            <label class="space-y-1 text-xs text-slate-600">فرق سعر هذا الفرع
                                <input v-model="option.branch_price_delta" type="number" step="0.01" class="w-full rounded-lg border-slate-300 text-sm" placeholder="استخدم الأساسي" />
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-4 text-xs text-slate-600">
                            <label class="flex items-center gap-1.5"><input v-model="option.is_active" type="checkbox" /> نشط</label>
                            <label class="flex items-center gap-1.5"><input v-model="option.branch_is_available" type="checkbox" /> متاح بالفرع</label>
                        </div>
                    </div>
                    <p v-if="form.errors.options" class="text-xs text-red-600">{{ form.errors.options }}</p>
                </section>
                <label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="form.is_active" type="checkbox" /> المجموعة نشطة</label>
                <button :disabled="form.processing" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-700 disabled:opacity-50">{{ form.processing ? 'جارٍ الحفظ...' : editingId ? 'حفظ التعديلات' : 'إنشاء المجموعة' }}</button>
                <div v-if="Object.keys(form.errors).length" class="space-y-1 text-xs text-red-600"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
            </form>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-bold text-slate-900">مجموعات الإضافات</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ groups.length }} مجموعة</p>
                </div>
                <div v-if="groups.length" class="divide-y divide-slate-100">
                    <article v-for="group in groups" :key="group.id" class="space-y-3 px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ group.name }}</h3>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ group.selection_type === 'single' ? 'اختيار واحد' : 'اختيارات متعددة' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ group.products_count }} منتج · الحد الأدنى {{ group.min_selections }} · الحد الأقصى {{ group.max_selections ?? 'غير محدد' }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button v-if="canManageCatalog" type="button" @click="editGroup(group)" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50">تعديل</button>
                                <button v-if="canManageCatalog" type="button" @click="archiveGroup(group)" class="rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">أرشفة</button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="option in group.options" :key="option.id" class="rounded-lg bg-slate-50 px-3 py-1.5 text-xs text-slate-700">{{ option.name }} · {{ Number(option.price_delta).toFixed(2) }} ج.م</span>
                        </div>
                    </article>
                </div>
                <div v-else class="p-10 text-center text-sm text-slate-500">لم تتم إضافة مجموعات بعد.</div>
            </section>
        </div>
    </div>
</template>
