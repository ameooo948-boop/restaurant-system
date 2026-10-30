<script setup>
import { Head, useForm } from '@inertiajs/vue3';
defineProps({ branch: { type: Object, required: true }, permissions: { type: Array, default: () => [] } });
const logout = useForm({});
</script>

<template>
    <Head title="لوحة إدارة الفرع" />
    <main dir="rtl" class="mx-auto max-w-6xl px-4 py-10">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-amber-700">Restaurant Admin</p>
                <h1 class="mt-1 text-2xl font-bold">{{ branch.name }}</h1>
                <p class="mt-1 text-sm text-slate-500">الفرع {{ branch.code }} · لوحة تأسيسية للصلاحيات</p>
            </div>
            <button @click="logout.post('/logout')" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">تسجيل الخروج</button>
        </header>
        <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold">الصلاحيات الممنوحة لهذا الفرع</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                <span v-for="permission in permissions" :key="permission" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">{{ permission }}</span>
                <span v-if="permissions.length === 0" class="text-sm text-slate-500">لا توجد صلاحيات مسجلة لهذا الحساب.</span>
            </div>
        </section>
    </main>
</template>
