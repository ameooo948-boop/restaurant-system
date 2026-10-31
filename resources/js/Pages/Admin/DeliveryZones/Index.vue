<script setup>
import { useForm, router, Head } from '@inertiajs/vue3';
const props = defineProps({ branch: Object, zones: Array });
const form = useForm({ name: '', city: '', area: '', delivery_fee: '0.00', minimum_order_amount: '0.00', estimated_minutes: '', is_active: true });
function createZone() { form.post(`/admin/branches/${props.branch.id}/delivery-zones`, { onSuccess: () => form.reset('name', 'city', 'area', 'delivery_fee', 'minimum_order_amount', 'estimated_minutes') }); }
function toggleZone(zone) { router.put(`/admin/branches/${props.branch.id}/delivery-zones/${zone.id}`, { ...zone, is_active: !zone.is_active }); }
function removeZone(zone) { if (confirm('حذف نطاق التوصيل؟')) router.delete(`/admin/branches/${props.branch.id}/delivery-zones/${zone.id}`); }
</script>

<template>
  <Head title="نطاقات التوصيل" />
  <main dir="rtl" class="min-h-screen bg-slate-50 p-5 text-slate-900">
    <div class="mx-auto max-w-6xl space-y-6">
      <header><p class="text-sm text-amber-700">{{ branch.name }}</p><h1 class="text-2xl font-black">نطاقات التوصيل</h1><p class="mt-1 text-sm text-slate-500">أضف مدينة ومنطقة ورسومًا وحدًا أدنى مستقلًا لكل نطاق.</p></header>
      <form @submit.prevent="createZone" class="grid gap-3 rounded-2xl border bg-white p-5 md:grid-cols-3">
        <label class="text-sm">اسم النطاق<input v-model="form.name" required class="mt-1 w-full rounded-lg border-slate-300" /></label>
        <label class="text-sm">المدينة<input v-model="form.city" required class="mt-1 w-full rounded-lg border-slate-300" /></label>
        <label class="text-sm">المنطقة (اتركها فارغة لتشمل المدينة كلها)<input v-model="form.area" class="mt-1 w-full rounded-lg border-slate-300" /></label>
        <label class="text-sm">رسوم التوصيل بالجنيه<input v-model="form.delivery_fee" required type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300" /></label>
        <label class="text-sm">الحد الأدنى للطلب<input v-model="form.minimum_order_amount" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-slate-300" /></label>
        <label class="text-sm">الوقت المتوقع بالدقائق<input v-model="form.estimated_minutes" type="number" min="1" class="mt-1 w-full rounded-lg border-slate-300" /></label>
        <div class="md:col-span-3"><button :disabled="form.processing" class="rounded-xl bg-slate-900 px-5 py-3 font-bold text-white">إضافة النطاق</button></div>
      </form>
      <section class="overflow-hidden rounded-2xl border bg-white">
        <div v-if="!zones.length" class="p-8 text-center text-slate-500">لم تتم إضافة نطاقات بعد. عندها سيستخدم النظام رسوم التوصيل العامة القديمة للفرع حتى تضيف أول نطاق نشط.</div>
        <table v-else class="w-full text-sm"><thead class="bg-slate-100 text-right"><tr><th class="p-3">النطاق</th><th class="p-3">المدينة / المنطقة</th><th class="p-3">الرسوم</th><th class="p-3">الحد الأدنى</th><th class="p-3">الحالة</th><th class="p-3">إجراءات</th></tr></thead><tbody><tr v-for="zone in zones" :key="zone.id" class="border-t"><td class="p-3 font-bold">{{ zone.name }}</td><td class="p-3">{{ zone.city }} / {{ zone.area || 'كل المدينة' }}</td><td class="p-3">{{ zone.delivery_fee }} EGP</td><td class="p-3">{{ zone.minimum_order_amount }} EGP</td><td class="p-3">{{ zone.is_active ? 'نشط' : 'متوقف' }}</td><td class="flex gap-2 p-3"><button @click="toggleZone(zone)" class="rounded-lg border px-3 py-1">{{ zone.is_active ? 'إيقاف' : 'تفعيل' }}</button><button @click="removeZone(zone)" class="rounded-lg border border-rose-200 px-3 py-1 text-rose-700">حذف</button></td></tr></tbody></table>
      </section>
    </div>
  </main>
</template>
