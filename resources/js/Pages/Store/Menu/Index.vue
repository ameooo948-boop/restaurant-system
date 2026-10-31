<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    branch: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    cartUrl: { type: String, required: true },
});

const forms = reactive({});
for (const product of props.products) {
    const defaultVariant = product.variants.find((variant) => variant.is_default && variant.is_available)
        ?? product.variants.find((variant) => variant.is_available);
    forms[product.id] = useForm({
        product_variant_id: defaultVariant?.id ?? '',
        quantity: 1,
        notes: '',
        modifiers: [],
    });
}

function toggleModifier(productId, optionId, checked, group) {
    const form = forms[productId];
    if (checked && group.selection_type === 'single') {
        const groupOptionIds = group.options.map((option) => option.id);
        form.modifiers = form.modifiers.filter((item) => !groupOptionIds.includes(item.modifier_option_id));
    }
    const existing = form.modifiers.findIndex((item) => item.modifier_option_id === optionId);
    if (checked && existing === -1) form.modifiers.push({ modifier_option_id: optionId, quantity: 1 });
    if (!checked && existing !== -1) form.modifiers.splice(existing, 1);
}

function add(product) {
    const form = forms[product.id];
    form.post(`/branches/${props.branch.id}/cart/items`, {
        preserveScroll: true,
        onSuccess: () => {
            form.quantity = 1;
            form.notes = '';
            form.modifiers = [];
        },
    });
}

const availableProducts = computed(() => props.products.filter((product) => product.variants.some((variant) => variant.is_available)));
const displayCategories = computed(() => [...props.categories, ...(props.products.some((product) => !product.category) ? [{ id: null, name: 'أصناف أخرى' }] : [])]);
</script>

<template>
    <Head :title="`منيو ${branch.name}`" />
    <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                <div><p class="text-xs font-semibold text-amber-700">قائمة الطعام</p><h1 class="text-xl font-black">{{ branch.name }}</h1></div>
                <a :href="cartUrl" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white">السلة</a>
            </div>
        </header>
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 lg:grid-cols-[220px_1fr]">
            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-4">
                <h2 class="font-bold">التصنيفات</h2>
                <nav class="mt-3 flex flex-wrap gap-2 lg:flex-col">
                    <a v-for="category in displayCategories" :key="category.id ?? 'uncategorized'" :href="category.id ? `#category-${category.id}` : '#category-other'" class="rounded-lg bg-slate-100 px-3 py-2 text-sm">{{ category.name }}</a>
                </nav>
            </aside>
            <section class="space-y-8">
                <div v-if="!availableProducts.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">لا توجد منتجات متاحة حاليًا.</div>
                <section v-for="category in displayCategories" :id="category.id ? `category-${category.id}` : 'category-other'" :key="category.id ?? 'uncategorized'" class="space-y-4">
                    <h2 class="text-xl font-black">{{ category.name }}</h2>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="product in availableProducts.filter((item) => category.id ? item.category?.id === category.id : !item.category)" :key="product.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <img v-if="product.image_path" :src="product.image_path" :alt="product.name" class="h-40 w-full object-cover" loading="lazy" />
                            <div class="p-4">
                                <h3 class="font-extrabold">{{ product.name }}</h3>
                                <p v-if="product.description" class="mt-1 text-sm text-slate-500">{{ product.description }}</p>
                                <form class="mt-4 space-y-3" @submit.prevent="add(product)">
                                    <label class="block text-sm font-semibold">الحجم
                                        <select v-model="forms[product.id].product_variant_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                            <option v-for="variant in product.variants.filter((item) => item.is_available)" :key="variant.id" :value="variant.id">{{ variant.name }} - {{ Number(variant.price).toFixed(2) }} EGP</option>
                                        </select>
                                    </label>
                                    <div v-for="group in product.modifier_groups" :key="group.id" class="rounded-xl bg-slate-50 p-3">
                                        <p class="text-sm font-bold">{{ group.name }} <span v-if="group.min_selections" class="text-xs text-rose-600">مطلوب</span></p>
                                        <label v-for="option in group.options.filter((item) => item.is_available)" :key="option.id" class="mt-2 flex items-center justify-between gap-2 text-sm">
                                            <span class="flex items-center gap-2"><input :type="group.selection_type === 'single' ? 'radio' : 'checkbox'" :name="`product-${product.id}-group-${group.id}`" :checked="forms[product.id].modifiers.some((item) => item.modifier_option_id === option.id)" @change="toggleModifier(product.id, option.id, $event.target.checked, group)" />{{ option.name }}</span>
                                            <span class="text-slate-500">{{ Number(option.price_delta) > 0 ? `+${Number(option.price_delta).toFixed(2)}` : Number(option.price_delta).toFixed(2) }} EGP</span>
                                        </label>
                                    </div>
                                    <label class="block text-sm font-semibold">ملاحظة على المنتج (اختياري)
                                        <textarea v-model="forms[product.id].notes" rows="2" maxlength="500" class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="مثال: بدون بصل"></textarea>
                                    </label>
                                    <div class="grid grid-cols-[90px_1fr] gap-2">
                                        <input v-model.number="forms[product.id].quantity" type="number" min="1" max="20" class="rounded-lg border-slate-300 text-sm" aria-label="الكمية" />
                                        <button type="submit" :disabled="forms[product.id].processing || !forms[product.id].product_variant_id" class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-black text-slate-950 disabled:opacity-50">{{ forms[product.id].processing ? 'جارٍ الإضافة...' : 'أضف إلى السلة' }}</button>
                                    </div>
                                    <p v-if="forms[product.id].errors.modifiers" class="text-xs text-rose-600">{{ forms[product.id].errors.modifiers }}</p>
                                    <p v-if="forms[product.id].errors.product_variant_id" class="text-xs text-rose-600">{{ forms[product.id].errors.product_variant_id }}</p>
                                </form>
                            </div>
                        </article>
                    </div>
                </section>
            </section>
        </div>
    </main>
</template>
