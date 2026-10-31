<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    branch: { type: Object, required: true },
    orders: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    typeOptions: { type: Array, default: () => [] },
});

const form = reactive({ ...props.filters });
const statusLabels = Object.fromEntries(props.statusOptions.map((item) => [item.value, item.label]));
const statusClasses = {
    pending_confirmation: 'bg-amber-100 text-amber-800', confirmed: 'bg-sky-100 text-sky-800',
    preparing: 'bg-indigo-100 text-indigo-800', ready: 'bg-emerald-100 text-emerald-800',
    out_for_delivery: 'bg-violet-100 text-violet-800', delivered: 'bg-teal-100 text-teal-800',
    completed: 'bg-slate-100 text-slate-700', cancelled: 'bg-rose-100 text-rose-800',
};
const typeLabels = { delivery: 'توصيل', pickup: 'استلام', dine_in: 'داخل المطعم' };
const paymentLabels = { pending: 'معلق', succeeded: 'مدفوع', failed: 'فشل', cancelled: 'ملغي', refunded: 'مسترد', partially_refunded: 'مسترد جزئيًا' };
const money = (value, currency = 'EGP') => new Intl.NumberFormat('ar-EG', { style: 'currency', currency, maximumFractionDigits: 2 }).format(Number(value || 0));
const dateTime = (value) => value ? new Date(value).toLocaleString('ar-EG') : '—';

function applyFilters() {
    router.get(`/admin/branches/${props.branch.id}/orders`, { ...form }, { preserveState: true, replace: true });
}
function clearFilters() {
    Object.keys(form).forEach((key) => { form[key] = ''; });
    applyFilters();
}
</script>

<template>
    <Head title="إدارة الطلبات" />
    <main dir="rtl" class="mx-auto min-h-screen max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div><Link :href="`/admin/branches/${branch.id}/dashboard`" class="text-sm font-bold text-amber-700">لوحة الفرع</Link><h1 class="mt-2 text-3xl font-black text-slate-900">إدارة الطلبات</h1><p class="mt-1 text-sm text-slate-500">{{ branch.name }} · متابعة وتشغيل الطلبات</p></div>
            <div class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">{{ orders.total }} طلب</div>
        </header>

        <form @submit.prevent="applyFilters" class="mt-7 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-3">
            <label class="text-sm font-semibold text-slate-700">بحث
                <input v-model="form.q" type="search" placeholder="رقم الطلب أو اسم العميل أو الهاتف" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500" />
            </label>
            <label class="text-sm font-semibold text-slate-700">حالة الطلب
                <select v-model="form.status" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"><option value="">كل الحالات</option><option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option></select>
            </label>
            <label class="text-sm font-semibold text-slate-700">نوع الطلب
                <select v-model="form.type" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"><option value="">كل الأنواع</option><option v-for="type in typeOptions" :key="type.value" :value="type.value">{{ type.label }}</option></select>
            </label>
            <label class="text-sm font-semibold text-slate-700">حالة الدفع
                <select v-model="form.payment_status" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"><option value="">كل حالات الدفع</option><option v-for="(label, value) in paymentLabels" :key="value" :value="value">{{ label }}</option></select>
            </label>
            <label class="text-sm font-semibold text-slate-700">من تاريخ
                <input v-model="form.from" type="date" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500" />
            </label>
            <label class="text-sm font-semibold text-slate-700">إلى تاريخ
                <input v-model="form.to" type="date" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500" />
            </label>
            <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-3"><button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">تطبيق الفلاتر</button><button type="button" @click="clearFilters" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700">مسح</button></div>
        </form>

        <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div v-if="orders.data.length" class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-right text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-5 py-4">رقم الطلب</th><th class="px-5 py-4">العميل</th><th class="px-5 py-4">النوع</th><th class="px-5 py-4">الحالة</th><th class="px-5 py-4">الدفع</th><th class="px-5 py-4">العناصر</th><th class="px-5 py-4">الإجمالي</th><th class="px-5 py-4">التاريخ</th><th class="px-5 py-4"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-black text-slate-900">{{ order.order_number }}</td><td class="px-5 py-4"><div class="font-semibold">{{ order.customer_name }}</div><div class="mt-1 text-xs text-slate-500">{{ order.customer_phone }}</div></td>
                            <td class="px-5 py-4">{{ typeLabels[order.order_type] }}</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClasses[order.order_status]">{{ statusLabels[order.order_status] }}</span></td>
                            <td class="px-5 py-4">{{ paymentLabels[order.payment_status] || order.payment_status }}</td><td class="px-5 py-4">{{ order.items_count }}</td><td class="px-5 py-4 font-bold">{{ money(order.total, order.currency) }}</td><td class="px-5 py-4 text-slate-500">{{ dateTime(order.placed_at) }}</td>
                            <td class="px-5 py-4"><Link :href="`/admin/branches/${branch.id}/orders/${order.id}`" class="font-bold text-amber-700 hover:text-amber-900">فتح</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-12 text-center"><p class="font-bold text-slate-800">لا توجد طلبات مطابقة</p><p class="mt-1 text-sm text-slate-500">جرّب تغيير الفلاتر أو البحث.</p></div>
            <nav v-if="orders.links?.length > 3" class="flex flex-wrap gap-2 border-t border-slate-100 p-4">
                <template v-for="link in orders.links" :key="link.label"><Link v-if="link.url" :href="link.url" preserve-scroll class="rounded-lg border px-3 py-2 text-xs font-bold" :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700'" v-html="link.label" /><span v-else class="rounded-lg border border-slate-100 px-3 py-2 text-xs text-slate-300" v-html="link.label" /></template>
            </nav>
        </section>
    </main>
</template>
