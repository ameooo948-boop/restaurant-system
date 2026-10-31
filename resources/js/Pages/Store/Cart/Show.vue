<script setup>
import { Head, router } from '@inertiajs/vue3';
const props = defineProps({ branch: { type: Object, required: true }, cart: { type: Object, required: true }, checkoutUrl: { type: String, required: true } });
function update(item, event) {
    const quantity = Number(event.target.value);
    if (!Number.isInteger(quantity) || quantity < 1 || quantity > 20) return;
    router.patch(`/branches/${props.branch.id}/cart/items/${item.id}`, { quantity, notes: item.notes ?? '' }, { preserveScroll: true });
}
function remove(item) { router.delete(`/branches/${props.branch.id}/cart/items/${item.id}`, { preserveScroll: true }); }
</script>

<template>
    <Head title="سلة الطلب" />
    <main dir="rtl" class="min-h-screen bg-slate-50 px-4 py-8 text-slate-900">
        <div class="mx-auto max-w-4xl">
            <header class="flex items-center justify-between"><div><p class="text-sm font-semibold text-amber-700">{{ branch.name }}</p><h1 class="text-2xl font-black">سلة الطلب</h1></div><a :href="`/branches/${branch.id}/menu`" class="text-sm font-bold text-amber-700">العودة للمنيو</a></header>
            <section v-if="!cart.items.length" class="mt-8 rounded-2xl border border-dashed bg-white p-12 text-center"><p class="font-bold">السلة فارغة</p><a :href="`/branches/${branch.id}/menu`" class="mt-4 inline-block rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white">تصفح المنيو</a></section>
            <section v-else class="mt-8 grid gap-6 lg:grid-cols-[1fr_300px]">
                <div class="space-y-3">
                    <article v-for="item in cart.items" :key="item.id" class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3"><div><h2 class="font-bold">{{ item.product_name }} - {{ item.variant_name }}</h2><p v-for="modifier in item.modifiers" :key="modifier.option_id" class="mt-1 text-xs text-slate-500">{{ modifier.group_name }}: {{ modifier.name }} × {{ modifier.quantity }}</p><p v-if="item.notes" class="mt-1 text-xs text-slate-500">ملاحظات: {{ item.notes }}</p></div><strong>{{ item.line_total }} {{ cart.currency }}</strong></div>
                        <div class="mt-4 flex items-center justify-between"><label class="text-sm">الكمية <input :value="item.quantity" type="number" min="1" max="20" class="ms-2 w-20 rounded-lg border-slate-300 text-sm" @change="update(item, $event)" /></label><button class="text-sm font-semibold text-rose-600" @click="remove(item)">حذف</button></div>
                    </article>
                </div>
                <aside class="h-fit rounded-2xl border bg-white p-5"><h2 class="font-bold">ملخص الطلب</h2><div class="mt-4 flex justify-between text-sm"><span>الإجمالي الفرعي</span><strong>{{ cart.subtotal }} {{ cart.currency }}</strong></div><div class="mt-2 flex justify-between text-sm"><span>التوصيل</span><strong>يحسب عند إتمام الطلب</strong></div><div class="mt-4 border-t pt-4 flex justify-between text-lg font-black"><span>الإجمالي الحالي</span><span>{{ cart.total }} {{ cart.currency }}</span></div><a :href="checkoutUrl" class="mt-5 block rounded-xl bg-amber-500 px-4 py-3 text-center font-black text-slate-950">متابعة إلى إتمام الطلب</a></aside>
            </section>
        </div>
    </main>
</template>
