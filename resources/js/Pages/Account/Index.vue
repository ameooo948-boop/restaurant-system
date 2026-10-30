<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const logout = useForm({});
</script>

<template>
    <Head title="حسابي" />
    <main dir="rtl" class="mx-auto max-w-4xl px-4 py-12">
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold text-amber-700">حساب العميل</p>
            <h1 class="mt-2 text-2xl font-bold">أهلاً {{ $page.props.auth.user?.name }}</h1>
            <p class="mt-2 text-slate-500">تم تسجيل الدخول بنجاح. هنا سنضيف لاحقًا العناوين والطلبات والمفضلة.</p>
            <p class="mt-4 text-sm text-slate-600">{{ $page.props.auth.user?.email }}</p>
            <div class="mt-7 flex flex-wrap gap-3">
                <Link href="/" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">العودة للموقع</Link>
                <button @click="logout.post('/logout')" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">تسجيل الخروج</button>
            </div>
        </section>
    </main>
</template>
