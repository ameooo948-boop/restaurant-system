<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    branch: { type: Object, required: true },
    stations: { type: Array, default: () => [] },
    tickets: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ queued: 0, preparing: 0, ready_last_hour: 0 }) },
    selectedStationId: { type: Number, default: null },
    canManageStations: { type: Boolean, default: false },
    canUpdateTickets: { type: Boolean, default: false },
});

const selectedStation = ref(props.selectedStationId ? String(props.selectedStationId) : '');
let refreshHandle = null;
const queuedTickets = computed(() => props.tickets.filter((ticket) => ticket.status === 'queued'));
const preparingTickets = computed(() => props.tickets.filter((ticket) => ticket.status === 'preparing'));

function applyFilter() {
    const url = `/admin/branches/${props.branch.id}/kitchen`;
    router.get(url, selectedStation.value ? { station_id: selectedStation.value } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function updateStatus(ticket, status) {
    router.patch(`/admin/branches/${props.branch.id}/kitchen/tickets/${ticket.id}/status`, { status }, {
        preserveScroll: true,
    });
}

function timeLabel(value) {
    if (!value) return '—';
    return new Date(value).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
    refreshHandle = window.setInterval(() => {
        router.reload({ only: ['tickets', 'summary'], preserveScroll: true, preserveState: true });
    }, 8000);
});

onUnmounted(() => {
    if (refreshHandle) window.clearInterval(refreshHandle);
});
</script>

<template>
    <main dir="rtl" class="mx-auto max-w-[1600px] space-y-6 p-4 sm:p-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm text-slate-500">{{ branch.name }} · تشغيل المطبخ</p>
                <h1 class="mt-1 text-2xl font-black text-slate-900">شاشة طلبات المطبخ</h1>
                <p class="mt-1 text-sm text-slate-500">تحديث تلقائي كل 8 ثوانٍ · رتّب التحضير حسب وقت وصول التذكرة.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a v-if="canManageStations" :href="`/admin/branches/${branch.id}/kitchen/stations`" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">إدارة المحطات</a>
                <select v-model="selectedStation" @change="applyFilter" class="rounded-xl border-slate-200 bg-white px-3 py-2 text-sm">
                    <option value="">كل المحطات</option>
                    <option v-for="station in stations" :key="station.id" :value="String(station.id)">{{ station.name }}</option>
                </select>
            </div>
        </header>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4"><p class="text-sm text-amber-800">في الانتظار</p><p class="mt-1 text-3xl font-black text-amber-950">{{ summary.queued }}</p></div>
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4"><p class="text-sm text-blue-800">قيد التحضير</p><p class="mt-1 text-3xl font-black text-blue-950">{{ summary.preparing }}</p></div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4"><p class="text-sm text-emerald-800">جاهز خلال آخر ساعة</p><p class="mt-1 text-3xl font-black text-emerald-950">{{ summary.ready_last_hour }}</p></div>
        </section>

        <section class="grid items-start gap-5 xl:grid-cols-2">
            <div class="space-y-3">
                <div class="flex items-center justify-between"><h2 class="text-lg font-black text-slate-900">طلبات جديدة</h2><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">{{ queuedTickets.length }}</span></div>
                <article v-for="ticket in queuedTickets" :key="ticket.id" class="space-y-4 rounded-2xl border border-amber-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><p class="text-xs font-bold text-amber-700">{{ ticket.station.name }} · {{ ticket.ticket_number }}</p><h3 class="mt-1 text-xl font-black text-slate-900">{{ ticket.order.order_number }}</h3><p class="mt-1 text-xs text-slate-500">{{ ticket.order.customer_name }} · {{ ticket.order.order_type }} · {{ timeLabel(ticket.queued_at) }}</p></div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">في الانتظار</span>
                    </div>
                    <div class="space-y-3 border-t border-slate-100 pt-3">
                        <div v-for="item in ticket.items" :key="item.id" class="flex gap-3">
                            <span class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-900 px-2 text-sm font-black text-white">{{ item.quantity }}×</span>
                            <div class="min-w-0 flex-1"><p class="font-bold text-slate-900">{{ item.product_name }} <span class="font-medium text-slate-500">({{ item.variant_name }})</span></p><p v-for="modifier in item.modifiers" :key="`${modifier.group}-${modifier.name}`" class="mt-1 text-xs text-slate-600">{{ modifier.group }}: {{ modifier.name }} <span v-if="modifier.quantity > 1">× {{ modifier.quantity }}</span></p><p v-if="item.notes" class="mt-1 rounded-lg bg-amber-50 p-2 text-xs text-amber-900">ملاحظة: {{ item.notes }}</p></div>
                        </div>
                    </div>
                    <p v-if="ticket.order.customer_notes" class="rounded-lg bg-slate-50 p-2 text-xs text-slate-600">ملاحظة الطلب: {{ ticket.order.customer_notes }}</p>
                    <button v-if="canUpdateTickets" @click="updateStatus(ticket, 'preparing')" class="w-full rounded-xl bg-blue-700 px-4 py-3 font-bold text-white hover:bg-blue-800">بدء التحضير</button>
                </article>
                <div v-if="!queuedTickets.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">لا توجد تذاكر جديدة حاليًا.</div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between"><h2 class="text-lg font-black text-slate-900">قيد التحضير</h2><span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">{{ preparingTickets.length }}</span></div>
                <article v-for="ticket in preparingTickets" :key="ticket.id" class="space-y-4 rounded-2xl border border-blue-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-bold text-blue-700">{{ ticket.station.name }} · {{ ticket.ticket_number }}</p><h3 class="mt-1 text-xl font-black text-slate-900">{{ ticket.order.order_number }}</h3><p class="mt-1 text-xs text-slate-500">{{ ticket.order.customer_name }} · بدأ {{ timeLabel(ticket.started_at) }}</p></div><span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">قيد التحضير</span></div>
                    <div class="space-y-3 border-t border-slate-100 pt-3">
                        <div v-for="item in ticket.items" :key="item.id" class="flex gap-3"><span class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-900 px-2 text-sm font-black text-white">{{ item.quantity }}×</span><div><p class="font-bold text-slate-900">{{ item.product_name }} ({{ item.variant_name }})</p><p v-for="modifier in item.modifiers" :key="`${modifier.group}-${modifier.name}`" class="mt-1 text-xs text-slate-600">{{ modifier.group }}: {{ modifier.name }}</p><p v-if="item.notes" class="mt-1 text-xs text-amber-800">{{ item.notes }}</p></div></div>
                    </div>
                    <button v-if="canUpdateTickets" @click="updateStatus(ticket, 'ready')" class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-bold text-white hover:bg-emerald-800">الطلب جاهز</button>
                </article>
                <div v-if="!preparingTickets.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">لا توجد تذاكر قيد التحضير.</div>
            </div>
        </section>
    </main>
</template>
