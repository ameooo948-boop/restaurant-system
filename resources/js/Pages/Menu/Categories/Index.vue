<script setup>
import { computed, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    branch: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    canManageCatalog: { type: Boolean, default: false },
});

const editingId = ref(null);
const form = useForm({ parent_id: '', name: '', slug: '', description: '', translations: { en: { name: '', description: '' } }, sort_order: 0, is_active: true });
const parentOptions = computed(() => props.categories.filter((item) => item.id !== editingId.value));

function resetForm() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.sort_order = 0;
}

function editCategory(category) {
    editingId.value = category.id;
    form.parent_id = category.parent_id ?? '';
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description ?? '';
    form.translations.en.name = category.translations?.en?.name ?? '';
    form.translations.en.description = category.translations?.en?.description ?? '';
    form.sort_order = category.sort_order;
    form.is_active = category.is_active;
    form.clearErrors();
}

function submit() {
    const url = `/admin/branches/${props.branch.id}/menu/categories`;
    if (editingId.value) {
        form.put(`${url}/${editingId.value}`, { onSuccess: resetForm });
    } else {
        form.post(url, { onSuccess: resetForm });
    }
}

function archiveCategory(category) {
    if (!window.confirm(`أرشفة التصنيف «${category.name}»؟`)) return;
    router.delete(`/admin/branches/${props.branch.id}/menu/categories/${category.id}`);
}
</script>

<template>
    <div dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">{{ branch.name }} · إدارة المنيو</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">تصنيفات المنيو</h1>
                <p class="mt-1 text-sm text-slate-500">نظّم الوجبات والمشروبات والأقسام الفرعية.</p>
            </div>
            <a :href="`/admin/branches/${branch.id}/menu/products`" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">إدارة المنتجات</a>
        </header>

        <div class="grid gap-6 lg:grid-cols-[minmax(280px,360px)_1fr]">
            <form v-if="canManageCatalog" @submit.prevent="submit" class="h-fit space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-slate-900">{{ editingId ? 'تعديل التصنيف' : 'إضافة تصنيف' }}</h2>
                    <button v-if="editingId" type="button" @click="resetForm" class="text-sm text-slate-500 hover:text-slate-900">إلغاء</button>
                </div>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">اسم التصنيف *</span>
                    <input v-model="form.name" required maxlength="120" class="w-full rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500" placeholder="مثلاً: البرجر" />
                    <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                </label>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">الاسم بالإنجليزية (اختياري)</span>
                    <input v-model="form.translations.en.name" dir="ltr" class="w-full rounded-lg border-slate-300" placeholder="Main Meals" />
                </label>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">الرابط المختصر (اختياري)</span>
                    <input v-model="form.slug" dir="ltr" class="w-full rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500" placeholder="burgers" />
                    <span v-if="form.errors.slug" class="text-xs text-red-600">{{ form.errors.slug }}</span>
                </label>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">التصنيف الأب</span>
                    <select v-model="form.parent_id" class="w-full rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">بدون تصنيف أب</option>
                        <option v-for="item in parentOptions" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                    <span v-if="form.errors.parent_id" class="text-xs text-red-600">{{ form.errors.parent_id }}</span>
                </label>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">الوصف</span>
                    <textarea v-model="form.description" rows="3" class="w-full rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500" />
                </label>
                <label class="block space-y-1 text-sm">
                    <span class="font-medium text-slate-700">الوصف بالإنجليزية (اختياري)</span>
                    <textarea v-model="form.translations.en.description" dir="ltr" rows="2" class="w-full rounded-lg border-slate-300" />
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block space-y-1 text-sm">
                        <span class="font-medium text-slate-700">ترتيب العرض</span>
                        <input v-model.number="form.sort_order" type="number" class="w-full rounded-lg border-slate-300" />
                    </label>
                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500" />
                        نشط
                    </label>
                </div>
                <button :disabled="form.processing" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-700 disabled:opacity-50">
                    {{ form.processing ? 'جارٍ الحفظ...' : editingId ? 'حفظ التعديلات' : 'إضافة التصنيف' }}
                </button>
                <p v-if="form.errors.category" class="text-sm text-red-600">{{ form.errors.category }}</p>
            </form>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-bold text-slate-900">التصنيفات الحالية</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ categories.length }} تصنيف</p>
                </div>
                <div v-if="categories.length" class="divide-y divide-slate-100">
                    <div v-for="category in categories" :key="category.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ category.name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ category.slug }} · {{ category.products_count }} منتج<span v-if="category.parent"> · ضمن {{ category.parent.name }}</span></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="category.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="rounded-full px-2.5 py-1 text-xs">{{ category.is_active ? 'نشط' : 'موقوف' }}</span>
                            <button v-if="canManageCatalog" type="button" @click="editCategory(category)" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50">تعديل</button>
                            <button v-if="canManageCatalog" type="button" @click="archiveCategory(category)" class="rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">أرشفة</button>
                        </div>
                    </div>
                </div>
                <div v-else class="p-10 text-center text-sm text-slate-500">لم تتم إضافة تصنيفات بعد.</div>
            </section>
        </div>
    </div>
</template>
