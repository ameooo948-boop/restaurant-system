<script setup>
import { reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    branch: { type: Object, required: true },
    stations: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const createForm = useForm({ code: '', name: '', description: '', sort_order: 0, is_active: true });
const assignments = reactive({});
for (const station of props.stations) assignments[station.id] = { product_ids: [...station.product_ids] };

function createStation() {
    createForm.post(`/admin/branches/${props.branch.id}/kitchen/stations`, { preserveScroll: true, onSuccess: () => createForm.reset() });
}
function saveProducts(station) {
    router.put(`/admin/branches/${props.branch.id}/kitchen/stations/${station.id}/products`, assignments[station.id], { preserveScroll: true });
}
function toggleStation(station) {
    router.put(`/admin/branches/${props.branch.id}/kitchen/stations/${station.id}`, {
        code: station.code,
        name: station.name,
        description: station.description ?? '',
        sort_order: station.sort_order,
        is_active: !station.is_active,
    }, { preserveScroll: true });
}
</script>

<template>
    <main dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        <header class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm text-slate-500">{{ branch.name }} · إعدادات التشغيل</p><h1 class="mt-1 text-2xl font-black text-slate-900">محطات التحضير</h1><p class="mt-1 text-sm text-slate-500">اربط كل منتج بمحطة واحدة داخل هذا الفرع. العناصر غير الموزعة تنتقل للمطبخ العام.</p></div><a :href="`/admin/branches/${branch.id}/kitchen`" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white">العودة لشاشة المطبخ</a></header>
        <form @submit.prevent="createStation" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
            <label class="space-y-1 text-sm"><span class="font-semibold">كود المحطة</span><input v-model="createForm.code" required maxlength="30" pattern="[A-Z0-9_]+" dir="ltr" placeholder="GRILL" class="w-full rounded-lg border-slate-300"/><small class="text-slate-500">حروف إنجليزية كبيرة وأرقام وشرطة سفلية.</small></label>
            <label class="space-y-1 text-sm"><span class="font-semibold">اسم المحطة</span><input v-model="createForm.name" required maxlength="100" class="w-full rounded-lg border-slate-300" placeholder="الجريل"/></label>
            <label class="space-y-1 text-sm"><span class="font-semibold">الوصف</span><input v-model="createForm.description" maxlength="500" class="w-full rounded-lg border-slate-300" placeholder="المشويات والبرجر"/></label>
            <label class="space-y-1 text-sm"><span class="font-semibold">الترتيب</span><input v-model.number="createForm.sort_order" type="number" min="0" class="w-full rounded-lg border-slate-300"/></label>
            <div class="flex items-end"><button :disabled="createForm.processing" class="w-full rounded-lg bg-amber-600 px-4 py-2.5 font-bold text-white">إضافة محطة</button></div>
            <div v-if="Object.keys(createForm.errors).length" class="text-sm text-red-600 sm:col-span-2 lg:col-span-5"><p v-for="(error, key) in createForm.errors" :key="key">{{ error }}</p></div>
        </form>

        <section class="grid gap-4 xl:grid-cols-2">
            <article v-for="station in stations" :key="station.id" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><div class="flex items-center gap-2"><span dir="ltr" class="rounded bg-slate-100 px-2 py-1 text-xs font-bold">{{ station.code }}</span><span v-if="station.is_default" class="rounded bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">افتراضية</span><span :class="station.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'" class="rounded px-2 py-1 text-xs">{{ station.is_active ? 'نشطة' : 'متوقفة' }}</span></div><h2 class="mt-2 text-lg font-black">{{ station.name }}</h2><p class="text-xs text-slate-500">{{ station.description || 'بدون وصف' }}</p></div><button v-if="!station.is_default" @click="toggleStation(station)" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold">{{ station.is_active ? 'إيقاف المحطة' : 'تفعيل المحطة' }}</button></div>
                <div class="max-h-64 space-y-2 overflow-y-auto rounded-xl bg-slate-50 p-3"><label v-for="product in products" :key="product.id" class="flex items-center gap-2 rounded-lg bg-white p-2 text-sm"><input v-model="assignments[station.id].product_ids" type="checkbox" :value="product.id" class="rounded border-slate-300 text-amber-600"/><span class="flex-1">{{ product.name }}</span><span class="text-xs text-slate-400">{{ product.category || 'بدون تصنيف' }}</span></label><p v-if="!products.length" class="text-sm text-slate-500">لا توجد منتجات نشطة.</p></div>
                <button @click="saveProducts(station)" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold hover:bg-slate-50">حفظ توزيع المنتجات</button>
            </article>
            <div v-if="!stations.length" class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 xl:col-span-2">لم يتم إنشاء محطات بعد. شغّل `php artisan restaurant:kitchen:setup` لإنشاء المحطة العامة.</div>
        </section>
    </main>
</template>
