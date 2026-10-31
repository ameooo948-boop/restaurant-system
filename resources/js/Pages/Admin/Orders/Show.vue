<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    branch: { type: Object, required: true }, order: { type: Object, required: true },
    allowedTransitions: { type: Array, default: () => [] }, permissions: { type: Object, required: true }, statusOptions: { type: Array, default: () => [] },
});
const form = useForm({ status: '', note: '' });
const cashPaymentForm = useForm({ note: '' });
function recordCashPayment() { cashPaymentForm.post(`/admin/branches/${props.branch.id}/orders/${props.order.id}/payments/cash/record`, { preserveScroll: true }); }
const labels = {
    pending_confirmation: 'بانتظار التأكيد', confirmed: 'تم التأكيد', preparing: 'قيد التحضير', ready: 'جاهز',
    out_for_delivery: 'خرج للتوصيل', delivered: 'تم التسليم', completed: 'مكتمل', cancelled: 'ملغي',
};
const typeLabels = { delivery: 'توصيل', pickup: 'استلام من المطعم', dine_in: 'داخل المطعم' };
const paymentLabels = { pending: 'معلق', succeeded: 'ناجح', failed: 'فشل', cancelled: 'ملغي', refunded: 'مسترد', partially_refunded: 'مسترد جزئيًا' };
const ticketLabels = { queued: 'في الانتظار', preparing: 'قيد التحضير', ready: 'جاهزة', cancelled: 'ملغاة' };
const money = (value, currency = props.order.currency) => new Intl.NumberFormat('ar-EG', { style: 'currency', currency, maximumFractionDigits: 2 }).format(Number(value || 0));
const dateTime = (value) => value ? new Date(value).toLocaleString('ar-EG') : '—';
function updateStatus(status) {
    form.status = status;
    form.patch(`/admin/branches/${props.branch.id}/orders/${props.order.id}/status`, { preserveScroll: true });
}
function transitionLabel(status) { return labels[status] || status; }
</script>

<template>
    <Head :title="`الطلب ${order.order_number}`" />
    <main dir="rtl" class="mx-auto min-h-screen max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div><Link :href="`/admin/branches/${branch.id}/orders`" class="text-sm font-bold text-amber-700">العودة للطلبات</Link><h1 class="mt-2 text-3xl font-black text-slate-900">{{ order.order_number }}</h1><p class="mt-1 text-sm text-slate-500">{{ branch.name }} · {{ dateTime(order.placed_at) }}</p></div>
            <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-800">{{ labels[order.order_status] }}</span>
        </header>

        <section class="mt-7 grid gap-5 lg:grid-cols-[1.4fr_1fr]">
            <div class="space-y-5">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-900">عناصر الطلب</h2>
                    <div class="mt-4 divide-y divide-slate-100">
                        <div v-for="item in order.items" :key="item.id" class="py-4 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-slate-900">{{ item.quantity }} × {{ item.product_name }}</p><p class="mt-1 text-sm text-slate-500">{{ item.variant_name }}</p></div><p class="font-bold">{{ money(item.line_subtotal) }}</p></div>
                            <div v-if="item.modifiers.length" class="mt-2 space-y-1 text-xs text-slate-600"><p v-for="modifier in item.modifiers" :key="`${item.id}-${modifier.option_name}`">{{ modifier.group_name }}: {{ modifier.option_name }} × {{ modifier.quantity }} <span v-if="Number(modifier.line_total) !== 0">({{ money(modifier.line_total) }})</span></p></div>
                            <p v-if="item.notes" class="mt-2 rounded-lg bg-amber-50 p-2 text-xs text-amber-900">ملاحظة: {{ item.notes }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-900">حالة الطلب وتاريخ التغييرات</h2>
                    <div class="mt-4 space-y-4">
                        <div v-for="(entry, index) in order.history" :key="`${entry.created_at}-${index}`" class="relative border-r-2 border-slate-200 pr-4">
                            <span class="absolute -right-[5px] top-1 h-2 w-2 rounded-full bg-amber-500"></span>
                            <p class="font-bold text-slate-800">{{ transitionLabel(entry.new_status) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ dateTime(entry.created_at) }} <span v-if="entry.changed_by">· {{ entry.changed_by }}</span> · {{ entry.source }}</p>
                            <p v-if="entry.note" class="mt-1 text-sm text-slate-600">{{ entry.note }}</p>
                        </div>
                        <p v-if="!order.history.length" class="text-sm text-slate-500">لا يوجد سجل حالات.</p>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-900">تذاكر المطبخ</h2>
                    <div v-if="order.kitchen_tickets.length" class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div v-for="ticket in order.kitchen_tickets" :key="ticket.id" class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-2"><p class="font-bold">{{ ticket.station?.name || 'محطة غير معروفة' }}</p><span class="rounded-full bg-slate-100 px-2 py-1 text-xs">{{ ticketLabels[ticket.status] }}</span></div>
                            <p class="mt-1 text-xs text-slate-500">{{ ticket.ticket_number }}</p>
                            <ul class="mt-3 space-y-1 text-sm text-slate-700"><li v-for="(item, index) in ticket.items" :key="index">{{ item.quantity }} × {{ item.product_name }} — {{ ticketLabels[item.status] }}</li></ul>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">لا توجد تذاكر مطبخ لهذا الطلب.</p>
                </article>
            </div>

            <aside class="space-y-5">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-900">بيانات العميل</h2>
                    <dl class="mt-4 space-y-3 text-sm"><div><dt class="text-slate-500">الاسم</dt><dd class="mt-1 font-bold">{{ order.customer_name }}</dd></div><div><dt class="text-slate-500">الهاتف</dt><dd class="mt-1 font-bold" dir="ltr">{{ order.customer_phone }}</dd></div><div v-if="order.customer_email"><dt class="text-slate-500">البريد</dt><dd class="mt-1">{{ order.customer_email }}</dd></div><div><dt class="text-slate-500">نوع الطلب</dt><dd class="mt-1 font-bold">{{ typeLabels[order.order_type] }}</dd></div><div v-if="order.address"><dt class="text-slate-500">عنوان التوصيل</dt><dd class="mt-1">{{ [order.address.address_line, order.address.area, order.address.city].filter(Boolean).join('، ') }}</dd></div><div v-if="order.table_label"><dt class="text-slate-500">الترابيزة</dt><dd class="mt-1">{{ order.table_label }}</dd></div><div v-if="order.customer_notes"><dt class="text-slate-500">ملاحظات العميل</dt><dd class="mt-1">{{ order.customer_notes }}</dd></div></dl>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-900">ملخص الحساب</h2>
                    <dl class="mt-4 space-y-3 text-sm"><div class="flex justify-between"><dt class="text-slate-500">المجموع الفرعي</dt><dd>{{ money(order.subtotal) }}</dd></div><div class="flex justify-between"><dt class="text-slate-500">الخصم</dt><dd>{{ money(order.discount_total) }}</dd></div><div class="flex justify-between"><dt class="text-slate-500">الضريبة</dt><dd>{{ money(order.tax_total) }}</dd></div><div class="flex justify-between"><dt class="text-slate-500">التوصيل</dt><dd>{{ money(order.delivery_fee) }}</dd></div><div class="flex justify-between border-t border-slate-100 pt-3 text-base font-black"><dt>الإجمالي</dt><dd>{{ money(order.total) }}</dd></div></dl>
                    <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm"><p>طريقة الدفع: <strong>{{ order.payment_method === 'cash' ? 'نقدي' : 'Vodafone Cash' }}</strong></p><p class="mt-1">حالة الدفع: <strong>{{ paymentLabels[order.payment_status] }}</strong></p></div>
                    <div v-if="permissions.view_payments && order.payments.length" class="mt-4 space-y-2"><div v-for="payment in order.payments" :key="payment.id" class="rounded-xl border border-slate-100 p-3 text-xs"><p class="font-bold">{{ payment.method }} · {{ paymentLabels[payment.status] }}</p><p class="mt-1 text-slate-500">{{ money(payment.amount, payment.currency) }} · {{ dateTime(payment.paid_at) }}</p><p v-if="payment.manual_review_required" class="mt-2 rounded-lg bg-amber-50 p-2 text-xs font-semibold text-amber-900">هذه العملية تتطلب مراجعة مالية يدوية قبل أي إجراء إضافي. السبب: {{ payment.manual_review_reason }}</p></div></div>
                    <form v-if="permissions.record_payment && order.payment_method === 'cash' && order.payment_status !== 'succeeded' && order.order_status !== 'cancelled'" @submit.prevent="recordCashPayment" class="mt-4 space-y-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <label class="block text-xs font-semibold text-emerald-950">ملاحظة التحصيل (اختيارية)<input v-model="cashPaymentForm.note" maxlength="500" class="mt-1 w-full rounded-lg border-emerald-200 text-sm" placeholder="مثال: تم استلام المبلغ عند الكاشير" /></label>
                        <button :disabled="cashPaymentForm.processing" class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-bold text-white disabled:opacity-50">تأكيد استلام النقدية</button>
                        <p v-if="cashPaymentForm.errors.payment" class="text-xs text-rose-700">{{ cashPaymentForm.errors.payment }}</p>
                    </form>
                </article>

                <article v-if="allowedTransitions.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                    <h2 class="font-black text-amber-950">إجراءات الطلب</h2>
                    <label class="mt-3 block text-sm font-semibold text-amber-950">ملاحظة (اختيارية)<textarea v-model="form.note" rows="2" maxlength="1000" class="mt-1 w-full rounded-xl border-amber-200 text-sm focus:border-amber-500 focus:ring-amber-500" placeholder="سبب أو توضيح التحديث"></textarea></label>
                    <div class="mt-3 grid gap-2"><button v-for="status in allowedTransitions" :key="status" :disabled="form.processing || (status === 'confirmed' && !permissions.confirm) || (status === 'cancelled' && !permissions.cancel) || (!['confirmed', 'cancelled'].includes(status) && !permissions.update_status)" @click="updateStatus(status)" class="rounded-xl px-4 py-2.5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40" :class="status === 'cancelled' ? 'bg-rose-700 hover:bg-rose-800' : 'bg-slate-900 hover:bg-slate-700'">{{ status === 'cancelled' ? 'إلغاء الطلب' : `تحديث إلى: ${labels[status]}` }}</button></div>
                    <p v-if="form.errors.status" class="mt-3 text-sm font-semibold text-rose-700">{{ form.errors.status }}</p>
                </article>
            </aside>
        </section>
    </main>
</template>
