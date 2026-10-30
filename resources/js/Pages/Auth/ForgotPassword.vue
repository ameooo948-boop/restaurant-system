<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const form = useForm({ email: '' });
const submit = () => form.post('/forgot-password');
</script>

<template>
    <Head title="استعادة كلمة المرور" />
    <main dir="rtl" class="grid min-h-screen place-items-center px-4 py-10">
        <section class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="mb-2 text-2xl font-bold">استعادة كلمة المرور</h1>
            <p class="mb-6 text-sm text-slate-500">أدخل بريدك الإلكتروني لإرسال رابط إعادة تعيين كلمة المرور.</p>
            <div v-if="$page.props.flash?.status" class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ $page.props.flash.status }}</div>
            <form class="space-y-4" @submit.prevent="submit">
                <input v-model="form.email" type="email" required placeholder="البريد الإلكتروني" class="w-full rounded-xl border border-slate-300 px-3 py-3" />
                <span v-if="form.errors.email" class="block text-xs text-red-600">{{ form.errors.email }}</span>
                <button :disabled="form.processing" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white">إرسال الرابط</button>
            </form>
            <Link href="/login" class="mt-5 block text-sm text-amber-700 hover:underline">العودة لتسجيل الدخول</Link>
        </section>
    </main>
</template>
