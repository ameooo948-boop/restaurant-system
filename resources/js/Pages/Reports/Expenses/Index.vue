<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const props = defineProps({ branch: Object, currency: { type: String, default: 'EGP' }, categories: Array, expenses: Object, summary: Object });
const base = `/admin/branches/${props.branch.id}`;
const localToday = (() => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; })();
const form = useForm({ expense_category_id: '', amount: '', expense_date: localToday, payment_method: 'cash', payee: '', reference: '', description: '' });
const voidForm = useForm({ reason: '' });
const money = (v) => new Intl.NumberFormat('ar-EG', { style: 'currency', currency: props.currency, maximumFractionDigits: 2 }).format(Number(v || 0));
const submit = () => form.post(`${base}/expenses`, { preserveScroll: true, onSuccess: () => form.reset('amount', 'payee', 'reference', 'description') });
const voidExpense = (expense) => {
    const reason = window.prompt('سبب إلغاء المصروف (سيظل محفوظًا في السجل):');
    if (!reason || reason.trim().length < 5) return;
    voidForm.reason = reason.trim();
    voidForm.patch(`${base}/expenses/${expense.id}/void`, { preserveScroll: true, onFinish: () => voidForm.reset() });
};
const paymentLabels = { cash: 'نقدي', bank_transfer: 'تحويل بنكي', card: 'بطاقة', wallet: 'محفظة', other: 'أخرى' };
</script>

<template>
    <Head title="إدارة المصروفات" />
    <main dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        <header class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-sm font-semibold text-amber-700">Branch Expenses</p><h1 class="mt-1 text-3xl font-black">إدارة المصروفات</h1><p class="mt-1 text-sm text-slate-500">{{ branch.name }} · تسجيل المصروفات وإلغاء السجلات مع الاحتفاظ بالتاريخ.</p></div><Link :href="`${base}/reports`" class="rounded-xl border bg-white px-4 py-2.5 text-sm font-bold">العودة للتقارير</Link></header>
        <section class="grid gap-3 sm:grid-cols-2"><article class="rounded-2xl border bg-white p-5"><p class="text-sm text-slate-500">مصروفات الشهر الحالية</p><p class="mt-2 text-2xl font-black text-rose-700">{{ money(summary.amount) }}</p></article><article class="rounded-2xl border bg-white p-5"><p class="text-sm text-slate-500">عدد السجلات المرحلة</p><p class="mt-2 text-2xl font-black">{{ summary.count }}</p></article></section>
        <section class="rounded-2xl border bg-white p-5"><h2 class="text-lg font-black">تسجيل مصروف جديد</h2><form @submit.prevent="submit" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <label class="grid gap-1 text-sm font-semibold">التصنيف<select v-model="form.expense_category_id" class="rounded-lg border-slate-300"><option value="" disabled>اختر التصنيف</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><span v-if="form.errors.expense_category_id" class="text-xs text-rose-600">{{ form.errors.expense_category_id }}</span></label>
            <label class="grid gap-1 text-sm font-semibold">المبلغ<input v-model="form.amount" type="number" min="0.01" step="0.01" class="rounded-lg border-slate-300" placeholder="0.00"><span v-if="form.errors.amount" class="text-xs text-rose-600">{{ form.errors.amount }}</span></label>
            <label class="grid gap-1 text-sm font-semibold">التاريخ<input v-model="form.expense_date" type="date" class="rounded-lg border-slate-300"><span v-if="form.errors.expense_date" class="text-xs text-rose-600">{{ form.errors.expense_date }}</span></label>
            <label class="grid gap-1 text-sm font-semibold">طريقة الدفع<select v-model="form.payment_method" class="rounded-lg border-slate-300"><option value="cash">نقدي</option><option value="bank_transfer">تحويل بنكي</option><option value="card">بطاقة</option><option value="wallet">محفظة</option><option value="other">أخرى</option></select></label>
            <label class="grid gap-1 text-sm font-semibold">المستفيد / المورد<input v-model="form.payee" maxlength="150" class="rounded-lg border-slate-300"></label>
            <label class="grid gap-1 text-sm font-semibold">مرجع / رقم إيصال<input v-model="form.reference" maxlength="120" class="rounded-lg border-slate-300"></label>
            <label class="grid gap-1 text-sm font-semibold sm:col-span-2 lg:col-span-3">الوصف<textarea v-model="form.description" maxlength="500" required class="rounded-lg border-slate-300" rows="2"></textarea><span v-if="form.errors.description" class="text-xs text-rose-600">{{ form.errors.description }}</span></label>
            <div class="sm:col-span-2 lg:col-span-3"><button :disabled="form.processing" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-black text-white disabled:opacity-50">حفظ المصروف</button></div>
        </form></section>
        <section class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-4"><h2 class="font-black">سجل المصروفات</h2></div><div class="overflow-x-auto"><table class="w-full min-w-[850px] text-right text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="p-3">التاريخ</th><th class="p-3">التصنيف</th><th class="p-3">الوصف</th><th class="p-3">طريقة الدفع</th><th class="p-3">المبلغ</th><th class="p-3">الحالة</th><th class="p-3">الإجراء</th></tr></thead><tbody class="divide-y"><tr v-for="expense in expenses.data" :key="expense.id"><td class="p-3">{{ expense.expense_date }}</td><td class="p-3 font-bold">{{ expense.category?.name }}</td><td class="p-3"><p>{{ expense.description }}</p><p v-if="expense.payee" class="text-xs text-slate-500">{{ expense.payee }}</p></td><td class="p-3">{{ paymentLabels[expense.payment_method] || expense.payment_method }}</td><td class="p-3 font-bold">{{ money(expense.amount) }}</td><td class="p-3"><span :class="expense.status === 'posted' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'" class="rounded-full px-2.5 py-1 text-xs font-bold">{{ expense.status === 'posted' ? 'مرحّل' : 'ملغي' }}</span></td><td class="p-3"><button v-if="expense.status === 'posted'" @click="voidExpense(expense)" class="font-bold text-rose-700">إلغاء</button><span v-else class="text-xs text-slate-400">{{ expense.void_reason }}</span></td></tr><tr v-if="!expenses.data.length"><td colspan="7" class="p-8 text-center text-slate-500">لا توجد مصروفات مسجلة.</td></tr></tbody></table></div><div class="flex items-center justify-between border-t p-4 text-sm"><span>إجمالي السجلات: {{ expenses.total }}</span><div class="flex gap-3"><Link v-if="expenses.prev_page_url" :href="expenses.prev_page_url" class="font-bold">السابق</Link><Link v-if="expenses.next_page_url" :href="expenses.next_page_url" class="font-bold">التالي</Link></div></div></section>
    </main>
</template>
