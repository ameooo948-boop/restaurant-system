<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    branch: { type: Object, required: true },
    permissions: { type: Array, default: () => [] },
    stats: { type: Object, default: null },
    recentOrders: { type: Array, default: () => [] },
});

const logout = useForm({});
const can = (permission) => props.permissions.includes(permission);
const statusLabels = {
    pending_confirmation: 'بانتظار التأكيد', confirmed: 'تم التأكيد', preparing: 'قيد التحضير',
    ready: 'جاهز', out_for_delivery: 'خرج للتوصيل', delivered: 'تم التسليم',
    completed: 'مكتمل', cancelled: 'ملغي',
};
const statusClasses = {
    pending_confirmation: 'bg-amber-100 text-amber-800', confirmed: 'bg-sky-100 text-sky-800',
    preparing: 'bg-indigo-100 text-indigo-800', ready: 'bg-emerald-100 text-emerald-800',
    out_for_delivery: 'bg-violet-100 text-violet-800', delivered: 'bg-teal-100 text-teal-800',
    completed: 'bg-slate-100 text-slate-700', cancelled: 'bg-rose-100 text-rose-800',
};
const money = (value, currency = 'EGP') => new Intl.NumberFormat('ar-EG', {
    style: 'currency', currency, maximumFractionDigits: 2,
}).format(Number(value || 0));
const dateTime = (value) => value ? new Date(value).toLocaleString('ar-EG') : '—';
</script>

<template>
    <Head title="لوحة إدارة المطعم" />
    <main dir="rtl" class="mx-auto min-h-screen max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-amber-700">Restaurant Operations</p>
                <h1 class="mt-1 text-3xl font-black text-slate-900">{{ branch.name }}</h1>
                <p class="mt-1 text-sm text-slate-500">الفرع {{ branch.code }} · لوحة إدارة وتشغيل الفرع</p>
            </div>
            <button @click="logout.post('/logout')" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700">تسجيل الخروج</button>
        </header>

        <section v-if="stats" class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">طلبات اليوم</p>
                <p class="mt-2 text-3xl font-black text-slate-900">{{ stats.today_orders }}</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-sm text-amber-800">بانتظار التأكيد</p>
                <p class="mt-2 text-3xl font-black text-amber-900">{{ stats.pending_confirmation }}</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                <p class="text-sm text-indigo-800">قيد التحضير</p>
                <p class="mt-2 text-3xl font-black text-indigo-900">{{ stats.preparing }}</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-sm text-emerald-800">جاهزة</p>
                <p class="mt-2 text-3xl font-black text-emerald-900">{{ stats.ready }}</p>
            </article>
            <article class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
                <p class="text-sm text-violet-800">خرجت للتوصيل</p>
                <p class="mt-2 text-3xl font-black text-violet-900">{{ stats.out_for_delivery }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">المبالغ المحصلة اليوم</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ money(stats.paid_today) }}</p>
                <p class="mt-1 text-xs text-slate-500">الطلبات ذات حالة دفع ناجحة فقط</p>
            </article>
        </section>

        <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link v-if="can('orders.view')" :href="`/admin/branches/${branch.id}/orders`" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300">
                <p class="text-sm font-bold text-amber-700">تشغيل المطعم</p><h2 class="mt-2 text-xl font-black">إدارة الطلبات</h2><p class="mt-1 text-sm text-slate-500">متابعة الطلبات وتحديث مراحل التنفيذ.</p>
            </Link>
            <Link v-if="can('menu.view')" :href="`/admin/branches/${branch.id}/menu/products`" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300">
                <p class="text-sm font-bold text-amber-700">الكتالوج</p><h2 class="mt-2 text-xl font-black">المنتجات والمنيو</h2><p class="mt-1 text-sm text-slate-500">الأصناف والأحجام والإضافات والأسعار.</p>
            </Link>
            <Link v-if="can('kitchen.view')" :href="`/admin/branches/${branch.id}/kitchen`" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300">
                <p class="text-sm font-bold text-amber-700">المطبخ</p><h2 class="mt-2 text-xl font-black">شاشة التحضير</h2><p class="mt-1 text-sm text-slate-500">تذاكر المحطات وحالات التجهيز.</p>
            </Link>
            <Link v-if="can('kitchen.stations.manage')" :href="`/admin/branches/${branch.id}/kitchen/stations`" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300">
                <p class="text-sm font-bold text-amber-700">الإعدادات التشغيلية</p><h2 class="mt-2 text-xl font-black">محطات المطبخ</h2><p class="mt-1 text-sm text-slate-500">إدارة المحطات وربط المنتجات بها.</p>
            </Link>
        </section>

        <section v-if="can('orders.view')" class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                <div><h2 class="text-lg font-black text-slate-900">أحدث الطلبات</h2><p class="mt-1 text-sm text-slate-500">آخر الطلبات المسجلة في هذا الفرع.</p></div>
                <Link :href="`/admin/branches/${branch.id}/orders`" class="text-sm font-bold text-amber-700 hover:text-amber-900">عرض كل الطلبات</Link>
            </div>
            <div v-if="recentOrders.length" class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-right text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-5 py-3">رقم الطلب</th><th class="px-5 py-3">العميل</th><th class="px-5 py-3">الحالة</th><th class="px-5 py-3">الإجمالي</th><th class="px-5 py-3">التاريخ</th><th class="px-5 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-bold">{{ order.order_number }}</td><td class="px-5 py-4">{{ order.customer_name }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClasses[order.order_status]">{{ statusLabels[order.order_status] }}</span></td>
                            <td class="px-5 py-4 font-semibold">{{ money(order.total, order.currency) }}</td><td class="px-5 py-4 text-slate-500">{{ dateTime(order.placed_at) }}</td>
                            <td class="px-5 py-4"><Link :href="`/admin/branches/${branch.id}/orders/${order.id}`" class="font-bold text-amber-700">التفاصيل</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="p-8 text-center text-sm text-slate-500">لا توجد طلبات حتى الآن.</p>
        </section>

        <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-slate-900">صلاحيات حسابك في هذا الفرع</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                <span v-for="permission in permissions" :key="permission" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">{{ permission }}</span>
                <span v-if="permissions.length === 0" class="text-sm text-slate-500">لا توجد صلاحيات مسجلة لهذا الحساب.</span>
            </div>
        </section>
    </main>
</template>
