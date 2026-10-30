<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="تسجيل الدخول" />
    <main dir="rtl" class="grid min-h-screen place-items-center px-4 py-10">
        <section class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="mb-2 text-sm font-semibold text-amber-700">Restaurant Management</p>
            <h1 class="mb-2 text-2xl font-bold">أهلاً بعودتك</h1>
            <p class="mb-7 text-sm text-slate-500">سجّل الدخول إلى حسابك للمتابعة.</p>
            <div v-if="$page.props.flash?.status" class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ $page.props.flash.status }}</div>
            <form class="space-y-4" @submit.prevent="submit">
                <label class="block text-sm font-medium">البريد الإلكتروني
                    <input v-model="form.email" type="email" autocomplete="username" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 outline-none focus:border-amber-600" />
                    <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
                </label>
                <label class="block text-sm font-medium">كلمة المرور
                    <input v-model="form.password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 outline-none focus:border-amber-600" />
                    <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="form.remember" type="checkbox" class="rounded" /> تذكرني</label>
                <button :disabled="form.processing" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white disabled:opacity-50">{{ form.processing ? 'جارٍ الدخول...' : 'تسجيل الدخول' }}</button>
            </form>
            <div class="mt-5 flex items-center justify-between text-sm">
                <Link href="/forgot-password" class="text-amber-700 hover:underline">نسيت كلمة المرور؟</Link>
                <Link href="/register" class="font-semibold text-slate-700 hover:underline">إنشاء حساب</Link>
            </div>
        </section>
    </main>
</template>
