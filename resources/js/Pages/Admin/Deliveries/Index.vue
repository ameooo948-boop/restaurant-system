<script setup>
import { useForm, Head } from '@inertiajs/vue3';
const props = defineProps({ branch: Object, deliveries: Object, drivers: Array });
const assignForms = {};
function assignForm(id) { if (!assignForms[id]) assignForms[id] = useForm({ driver_user_id: '' }); return assignForms[id]; }
function assign(id) { const f = assignForm(id); f.post(`/admin/branches/${props.branch.id}/deliveries/${id}/assign`); }
function statusForm(id) { return useForm({ status: 'picked_up', note: '', failure_reason: '' }); }
const forms = {};
function formFor(id) { if (!forms[id]) forms[id] = statusForm(id); return forms[id]; }
function updateStatus(id) { formFor(id).patch(`/admin/branches/${props.branch.id}/deliveries/${id}/status`); }
</script>

<template>
  <Head title="إدارة التوصيل" />
  <main dir="rtl" class="min-h-screen bg-slate-50 p-5 text-slate-900">
    <div class="mx-auto max-w-7xl space-y-5">
      <header><p class="text-sm text-amber-700">{{ branch.name }}</p><h1 class="text-2xl font-black">إدارة التوصيل</h1></header>
      <section v-if="!deliveries.data.length" class="rounded-2xl border bg-white p-10 text-center text-slate-500">لا توجد طلبات توصيل مؤكدة حاليًا.</section>
      <section v-for="delivery in deliveries.data" :key="delivery.id" class="grid gap-4 rounded-2xl border bg-white p-5 lg:grid-cols-[1fr_360px]">
        <div class="space-y-2"><div class="flex flex-wrap items-center gap-2"><h2 class="font-black">{{ delivery.order?.order_number }}</h2><span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ delivery.status }}</span></div><p>{{ delivery.order?.customer_name }} — {{ delivery.order?.customer_phone }}</p><p class="text-sm text-slate-600">{{ delivery.order?.delivery_address_snapshot?.address_line }}، {{ delivery.order?.delivery_address_snapshot?.area }}، {{ delivery.order?.delivery_address_snapshot?.city }}</p><p class="text-sm text-slate-500">النطاق: {{ delivery.zone || 'غير محدد' }} · المندوب: {{ delivery.driver?.name || 'لم يتم التعيين' }}</p><p class="font-bold">{{ delivery.order?.total }} {{ delivery.order?.currency }}</p></div>
        <div class="space-y-3">
          <form v-if="['pending_assignment','failed','assigned'].includes(delivery.status)" @submit.prevent="assign(delivery.id)" class="flex gap-2"><select v-model="assignForm(delivery.id).driver_user_id" required class="min-w-0 flex-1 rounded-lg border-slate-300"><option value="">اختر المندوب</option><option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option></select><button class="rounded-lg bg-slate-900 px-4 py-2 text-white">تعيين</button></form>
          <form v-if="['assigned','picked_up'].includes(delivery.status)" @submit.prevent="updateStatus(delivery.id)" class="space-y-2"><select v-model="formFor(delivery.id).status" class="w-full rounded-lg border-slate-300"><option v-if="delivery.status === 'assigned'" value="picked_up">تم استلام الطلب</option><option v-if="delivery.status === 'picked_up'" value="delivered">تم التسليم</option><option v-if="delivery.status === 'picked_up'" value="failed">تعذر التوصيل</option></select><input v-if="formFor(delivery.id).status === 'failed'" v-model="formFor(delivery.id).failure_reason" placeholder="سبب تعذر التوصيل" class="w-full rounded-lg border-slate-300" /><button class="w-full rounded-lg bg-amber-400 px-4 py-2 font-bold">تحديث الحالة</button></form>
        </div>
      </section>
      <div v-if="deliveries.links" class="flex flex-wrap gap-2"><a v-for="link in deliveries.links" :key="link.label" :href="link.url || '# '" v-html="link.label" :class="['rounded border px-3 py-2 text-sm', link.active ? 'bg-slate-900 text-white' : 'bg-white']" /></div>
    </div>
  </main>
</template>
