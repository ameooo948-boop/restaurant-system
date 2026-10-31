<script setup>
import { Head, useForm } from '@inertiajs/vue3';
const props = defineProps({ branch: Object, cart: Object, cartPublicId: String, idempotencyKey: String, customer: Object, paymentMethods: Array, vodafoneCashNotice: String });
const form = useForm({
    cart_public_id: props.cartPublicId, idempotency_key: props.idempotencyKey,
    order_type: 'delivery', payment_method: 'cash', customer_name: props.customer.name,
    customer_phone: props.customer.phone, customer_email: props.customer.email,
    address_line: '', city: '', area: '', building: '', floor: '', apartment: '', landmark: '',
    table_label: '', customer_notes: '',
});
function submit() { form.post(`/branches/${props.branch.id}/checkout`); }
</script>

<template>
    <Head title="إتمام الطلب" />
    <main dir="rtl" class="min-h-screen bg-slate-50 px-4 py-8 text-slate-900">
        <div class="mx-auto max-w-5xl">
            <header><p class="font-semibold text-amber-700">{{ branch.name }}</p><h1 class="text-2xl font-black">إتمام الطلب</h1></header>
            <form class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px]" @submit.prevent="submit">
                <div class="space-y-5">
                    <section class="rounded-2xl border bg-white p-5"><h2 class="font-bold">نوع الطلب</h2><div class="mt-3 grid gap-2 sm:grid-cols-3"><label v-for="type in [{value:'delivery',label:'توصيل'},{value:'pickup',label:'استلام من المطعم'},{value:'dine_in',label:'داخل المطعم'}]" :key="type.value" class="rounded-xl border p-3 text-sm"><input v-model="form.order_type" type="radio" :value="type.value" class="ms-2" />{{ type.label }}</label></div></section>
                    <section class="rounded-2xl border bg-white p-5"><h2 class="font-bold">بيانات التواصل</h2><div class="mt-4 grid gap-3 sm:grid-cols-2"><label class="text-sm">الاسم<input v-model="form.customer_name" required class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm">رقم الهاتف<input v-model="form.customer_phone" required class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm sm:col-span-2">البريد الإلكتروني (اختياري)<input v-model="form.customer_email" type="email" class="mt-1 w-full rounded-lg border-slate-300" /></label></div></section>
                    <section v-if="form.order_type === 'delivery'" class="rounded-2xl border bg-white p-5"><h2 class="font-bold">عنوان التوصيل</h2><div class="mt-4 grid gap-3 sm:grid-cols-2"><label class="text-sm sm:col-span-2">العنوان بالتفصيل<input v-model="form.address_line" required class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm">المدينة<input v-model="form.city" required class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm">المنطقة<input v-model="form.area" class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm">المبنى<input v-model="form.building" class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm">الدور<input v-model="form.floor" class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm">الشقة<input v-model="form.apartment" class="mt-1 w-full rounded-lg border-slate-300" /></label><label class="text-sm sm:col-span-2">علامة مميزة<input v-model="form.landmark" class="mt-1 w-full rounded-lg border-slate-300" /></label></div></section>
                    <section v-if="form.order_type === 'dine_in'" class="rounded-2xl border bg-white p-5"><label class="text-sm font-semibold">رقم الطاولة (اختياري)<input v-model="form.table_label" class="mt-1 w-full rounded-lg border-slate-300" /></label></section>
                    <section class="rounded-2xl border bg-white p-5"><h2 class="font-bold">طريقة الدفع</h2><label v-for="method in paymentMethods" :key="method.value" class="mt-3 flex items-center gap-2 rounded-xl border p-3 text-sm" :class="!method.enabled ? 'opacity-50' : ''"><input v-model="form.payment_method" type="radio" :value="method.value" :disabled="!method.enabled" />{{ method.label }}</label><p class="mt-3 text-xs text-slate-500">{{ vodafoneCashNotice }}</p></section>
                    <section class="rounded-2xl border bg-white p-5"><label class="text-sm font-semibold">ملاحظات إضافية<textarea v-model="form.customer_notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300"></textarea></label></section>
                    <div v-if="Object.keys(form.errors).length" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
                </div>
                <aside class="h-fit rounded-2xl border bg-white p-5"><h2 class="font-bold">ملخص الطلب</h2><div v-for="item in cart.items" :key="item.id" class="mt-3 flex justify-between gap-3 text-sm"><span>{{ item.product_name }} × {{ item.quantity }}</span><strong>{{ item.line_total }} {{ cart.currency }}</strong></div><div class="mt-4 flex justify-between border-t pt-4 font-black"><span>الإجمالي الفرعي</span><span>{{ cart.subtotal }} {{ cart.currency }}</span></div><p class="mt-2 text-xs text-slate-500">سيتم تحديد رسوم التوصيل حسب نطاق المدينة والمنطقة وإعادة حساب السعر على الخادم.</p><button type="submit" :disabled="form.processing" class="mt-5 w-full rounded-xl bg-amber-500 px-4 py-3 font-black text-slate-950 disabled:opacity-50">{{ form.processing ? 'جارٍ إرسال الطلب...' : (form.payment_method === 'vodafone_cash' ? 'المتابعة إلى فودافون كاش' : 'تأكيد الطلب') }}</button></aside>
            </form>
        </div>
    </main>
</template>
