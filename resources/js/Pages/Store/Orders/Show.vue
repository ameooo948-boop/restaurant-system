<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ order: { type: Object, required: true } });
const statusLabels = { pending_confirmation: 'بانتظار تأكيد المطعم', confirmed: 'تم تأكيد الطلب', preparing: 'جارٍ التحضير', ready: 'الطلب جاهز', out_for_delivery: 'خرج للتوصيل', delivered: 'تم التوصيل', completed: 'مكتمل', cancelled: 'ملغي' };
</script>

<template>
    <Head :title="`طلب ${order.order_number}`" />
    <main dir="rtl" class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
        <div class="mx-auto max-w-3xl rounded-3xl border bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold text-emerald-700">تم استلام طلبك</p>
            <h1 class="mt-2 text-2xl font-black">طلب رقم {{ order.order_number }}</h1>
            <div class="mt-5 rounded-2xl bg-amber-50 p-4"><p class="text-sm text-amber-800">حالة الطلب</p><p class="mt-1 text-lg font-black">{{ statusLabels[order.order_status] ?? order.order_status }}</p></div>
            <section class="mt-7"><h2 class="font-bold">تفاصيل المنتجات</h2><div v-for="(item, index) in order.items" :key="index" class="flex justify-between gap-4 border-b py-3 text-sm"><div><p class="font-semibold">{{ item.product_name }} - {{ item.variant_name }} × {{ item.quantity }}</p><p v-for="modifier in item.modifiers" :key="`${modifier.group_name}-${modifier.option_name}`" class="mt-1 text-xs text-slate-500">{{ modifier.group_name }}: {{ modifier.option_name }}</p></div><strong>{{ item.line_subtotal }} {{ order.currency }}</strong></div></section>
            <section class="mt-6 space-y-2 border-t pt-4 text-sm"><div class="flex justify-between"><span>الإجمالي الفرعي</span><strong>{{ order.subtotal }} {{ order.currency }}</strong></div><div class="flex justify-between"><span>التوصيل</span><strong>{{ order.delivery_fee }} {{ order.currency }}</strong></div><div class="flex justify-between text-lg font-black"><span>الإجمالي</span><strong>{{ order.total }} {{ order.currency }}</strong></div></section>
            <p class="mt-6 text-xs text-slate-500">الدفع: {{ order.payment_method === 'cash' ? 'نقدًا' : order.payment_method }} — حالة الدفع: {{ order.payment_status }}</p>
            <p v-if="order.address" class="mt-2 text-sm text-slate-600">عنوان التوصيل: {{ order.address.address_line }}، {{ order.address.area }}، {{ order.address.city }}</p>
            <p class="mt-6 text-xs text-slate-400">يمكنك فتح هذه الصفحة من نفس المتصفح للطلبات كضيف. لا تشارك رابط الطلب مع الآخرين.</p>
        </div>
    </main>
</template>
