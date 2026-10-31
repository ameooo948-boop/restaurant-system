<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ branch: Object, filters: Object, report: Object });
const form = reactive({ from: props.filters.from, to: props.filters.to });
const base = `/admin/branches/${props.branch.id}`;
const money = (value) => new Intl.NumberFormat('ar-EG', { style: 'currency', currency: 'EGP', maximumFractionDigits: 2 }).format(Number(value || 0));
const number = (value) => new Intl.NumberFormat('ar-EG').format(Number(value || 0));
const apply = () => router.get(`${base}/reports`, form, { preserveState: true, replace: true });
const exportUrl = () => `${base}/reports/export.csv?from=${encodeURIComponent(form.from)}&to=${encodeURIComponent(form.to)}`;
const maxDaily = Math.max(1, ...props.report.daily_sales.map((row) => Number(row.sales)));
const typeLabels = { delivery: 'توصيل', pickup: 'استلام', dine_in: 'داخل المطعم' };
const paymentLabels = { cash: 'نقدي', vodafone_cash: 'فودافون كاش' };
</script>

<template>
    <Head title="التقارير والتحليلات" />
    <main dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-amber-700">Restaurant Analytics</p>
                <h1 class="mt-1 text-3xl font-black text-slate-900">التقارير والتحليلات</h1>
                <p class="mt-1 text-sm text-slate-500">{{ branch.name }} · {{ report.period.from }} — {{ report.period.to }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link :href="`${base}/expenses`" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:border-amber-400">إدارة المصروفات</Link>
                <a :href="exportUrl()" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700">تصدير CSV</a>
            </div>
        </header>

        <form @submit.prevent="apply" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
            <label class="grid gap-1 text-sm font-semibold text-slate-700">من تاريخ<input v-model="form.from" type="date" class="rounded-lg border-slate-300 text-sm"></label>
            <label class="grid gap-1 text-sm font-semibold text-slate-700">إلى تاريخ<input v-model="form.to" type="date" class="rounded-lg border-slate-300 text-sm"></label>
            <button class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-black text-white hover:bg-amber-600">تطبيق الفترة</button>
        </form>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">صافي المبيعات المسلّمة</p><p class="mt-2 text-2xl font-black text-slate-900">{{ money(report.summary.net_sales) }}</p><p class="mt-1 text-xs text-slate-500">{{ number(report.summary.fulfilled_orders) }} طلب مكتمل أو تم تسليمه</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">المدفوعات المحصلة</p><p class="mt-2 text-2xl font-black text-emerald-700">{{ money(report.summary.collected_payments) }}</p><p class="mt-1 text-xs text-slate-500">تأكيدات دفع ناجحة داخل الفترة</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">متوسط قيمة الطلب</p><p class="mt-2 text-2xl font-black text-slate-900">{{ money(report.summary.average_order_value) }}</p><p class="mt-1 text-xs text-slate-500">محسوب على الطلبات المسلّمة أو المكتملة</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">المصروفات المسجلة</p><p class="mt-2 text-2xl font-black text-rose-700">{{ money(report.summary.expenses_total) }}</p><p class="mt-1 text-xs text-slate-500">{{ number(report.summary.expenses_count) }} مصروف مرحّل</p></article>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-lg font-black">المبيعات اليومية</h2><span class="text-xs text-slate-500">قيمة الطلبات المنفذة</span></div>
                <div v-if="report.daily_sales.length" class="mt-5 flex h-52 items-end gap-2 overflow-x-auto border-b border-slate-200 pb-1">
                    <div v-for="row in report.daily_sales" :key="row.date" class="flex h-full min-w-12 flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-[10px] font-bold text-slate-600">{{ number(row.sales) }}</span>
                        <div class="w-full rounded-t-lg bg-amber-400" :style="{ height: `${Math.max(4, Number(row.sales) / maxDaily * 76)}%` }" :title="money(row.sales)"></div>
                        <span class="text-[10px] text-slate-500">{{ row.date.slice(5) }}</span>
                    </div>
                </div>
                <p v-else class="mt-8 text-center text-sm text-slate-500">لا توجد مبيعات منفذة في الفترة المحددة.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-black">ملخص الطلبات</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">إجمالي الطلبات</dt><dd class="font-black">{{ number(report.summary.orders_placed) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">طلبات منفذة</dt><dd class="font-black text-emerald-700">{{ number(report.summary.fulfilled_orders) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">طلبات ملغاة</dt><dd class="font-black text-rose-700">{{ number(report.summary.cancelled_orders) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">طلبات غير محصلة</dt><dd class="font-black">{{ number(report.summary.unpaid_fulfilled_orders) }}</dd></div>
                    <div class="flex justify-between border-t pt-3"><dt class="text-slate-500">قيمة غير محصلة</dt><dd class="font-black">{{ money(report.summary.unpaid_fulfilled_amount) }}</dd></div>
                </dl>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b p-4"><h2 class="font-black">الأكثر مبيعًا</h2></div>
                <div v-if="report.top_products.length" class="overflow-x-auto"><table class="w-full text-right text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="p-3">المنتج</th><th class="p-3">النوع</th><th class="p-3">الكمية</th><th class="p-3">المبيعات</th></tr></thead><tbody class="divide-y"><tr v-for="item in report.top_products" :key="item.product + item.variant"><td class="p-3 font-bold">{{ item.product }}</td><td class="p-3 text-slate-500">{{ item.variant }}</td><td class="p-3">{{ number(item.quantity) }}</td><td class="p-3 font-semibold">{{ money(item.sales) }}</td></tr></tbody></table></div>
                <p v-else class="p-5 text-sm text-slate-500">لا توجد أصناف مباعة في الفترة.</p>
            </article>
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b p-4"><h2 class="font-black">المبيعات حسب نوع الطلب</h2></div>
                <div v-if="report.orders_by_type.length" class="divide-y"><div v-for="item in report.orders_by_type" :key="item.type" class="flex items-center justify-between gap-3 p-4"><div><p class="font-bold">{{ typeLabels[item.type] || item.label }}</p><p class="mt-1 text-xs text-slate-500">{{ number(item.count) }} طلب</p></div><p class="font-black">{{ money(item.sales) }}</p></div></div>
                <p v-else class="p-5 text-sm text-slate-500">لا توجد بيانات.</p>
            </article>
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b p-4"><h2 class="font-black">التحصيل حسب وسيلة الدفع</h2></div>
                <div v-if="report.payment_mix.length" class="divide-y"><div v-for="item in report.payment_mix" :key="item.method" class="flex items-center justify-between gap-3 p-4"><div><p class="font-bold">{{ paymentLabels[item.method] || item.label }}</p><p class="mt-1 text-xs text-slate-500">{{ number(item.count) }} عملية ناجحة</p></div><p class="font-black text-emerald-700">{{ money(item.amount) }}</p></div></div>
                <p v-else class="p-5 text-sm text-slate-500">لا توجد عمليات تحصيل ناجحة في الفترة.</p>
            </article>
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b p-4"><h2 class="font-black">المصروفات حسب التصنيف</h2></div>
                <div v-if="report.expenses_by_category.length" class="divide-y"><div v-for="item in report.expenses_by_category" :key="item.category" class="flex items-center justify-between gap-3 p-4"><div><p class="font-bold">{{ item.category }}</p><p class="mt-1 text-xs text-slate-500">{{ number(item.count) }} مصروف</p></div><p class="font-black text-rose-700">{{ money(item.amount) }}</p></div></div>
                <p v-else class="p-5 text-sm text-slate-500">لا توجد مصروفات مسجلة في الفترة.</p>
            </article>
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b p-4"><h2 class="font-black">استهلاك المكونات</h2></div>
                <div v-if="report.stock_consumption.length" class="divide-y"><div v-for="item in report.stock_consumption" :key="item.ingredient" class="flex items-center justify-between gap-3 p-4"><p class="font-bold">{{ item.ingredient }}</p><p class="font-black">{{ number(item.quantity) }} {{ item.unit }}</p></div></div>
                <p v-else class="p-5 text-sm text-slate-500">لا توجد حركات استهلاك مسجلة.</p>
            </article>
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b p-4"><h2 class="font-black">تنبيهات المخزون المنخفض</h2></div>
                <div v-if="report.low_stock.length" class="divide-y"><div v-for="item in report.low_stock" :key="item.ingredient_id" class="flex items-center justify-between gap-3 p-4"><div><p class="font-bold">{{ item.name }}</p><p class="mt-1 text-xs text-slate-500">المتاح {{ number(item.available) }} {{ item.unit }}</p></div><span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700">منخفض</span></div></div>
                <p v-else class="p-5 text-sm text-slate-500">لا توجد تنبيهات.</p>
            </article>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">قيمة استلامات المشتريات</p><p class="mt-2 text-xl font-black">{{ money(report.summary.purchase_receipts_total) }}</p><p class="mt-1 text-xs text-slate-500">قيمة الكميات المستلمة وفق تكلفة أمر الشراء.</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">تم تسليمها</p><p class="mt-2 text-xl font-black text-emerald-700">{{ number(report.delivery.delivered) }}</p><p class="mt-1 text-xs text-slate-500">متوسط من استلام المندوب حتى التسليم: {{ report.delivery.average_delivery_minutes ?? '—' }} دقيقة</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">تعذر توصيلها</p><p class="mt-2 text-xl font-black text-rose-700">{{ number(report.delivery.failed) }}</p><p class="mt-1 text-xs text-slate-500">حسب سجلات التوصيل داخل الفترة.</p></article>
        </section>

        <aside class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-7 text-amber-950">
            <p class="font-black">ملاحظات على تعريف المؤشرات</p>
            <p>{{ report.definitions.sales_basis }}</p>
            <p>{{ report.definitions.collections_basis }}</p>
            <p>{{ report.definitions.profitability }}</p>
            <p v-if="report.summary.refund_marked_orders_excluded > 0" class="font-bold">تم استبعاد {{ number(report.summary.refund_marked_orders_excluded) }} طلب موسوم باسترداد كلي أو جزئي؛ لا يوجد دفتر مبالغ استرداد تفصيلي في هذه النسخة.</p>
        </aside>
    </main>
</template>
