<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="إنشاء حساب" />
    <main dir="rtl" class="grid min-h-screen place-items-center px-4 py-10">
        <section class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="mb-2 text-sm font-semibold text-amber-700">Restaurant</p>
            <h1 class="mb-2 text-2xl font-bold">إنشاء حساب عميل</h1>
            <p class="mb-7 text-sm text-slate-500">حساب العميل لا يمنح أي صلاحيات لإدارة المطعم أو فروعه.</p>
            <form class="space-y-4" @submit.prevent="submit">
                <label class="block text-sm font-medium">الاسم الكامل
                    <input v-model="form.name" autocomplete="name" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3" />
                    <span v-if="form.errors.name" class="mt-1 block text-xs text-red-600">{{ form.errors.name }}</span>
                </label>
                <label class="block text-sm font-medium">البريد الإلكتروني
                    <input v-model="form.email" type="email" autocomplete="email" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3" />
                    <span v-if="form.errors.email" class="mt-1 block text-xs text-red-600">{{ form.errors.email }}</span>
                </label>
                <label class="block text-sm font-medium">رقم الهاتف (اختياري)
                    <input v-model="form.phone" type="tel" autocomplete="tel" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3" />
                    <span v-if="form.errors.phone" class="mt-1 block text-xs text-red-600">{{ form.errors.phone }}</span>
                </label>
                <label class="block text-sm font-medium">كلمة المرور
                    <input v-model="form.password" type="password" autocomplete="new-password" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3" />
                    <span v-if="form.errors.password" class="mt-1 block text-xs text-red-600">{{ form.errors.password }}</span>
                </label>
                <label class="block text-sm font-medium">تأكيد كلمة المرور
                    <input v-model="form.password_confirmation" type="password" autocomplete="new-password" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3" />
                </label>
                <button :disabled="form.processing" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white disabled:opacity-50">{{ form.processing ? 'جارٍ إنشاء الحساب...' : 'إنشاء الحساب' }}</button>
            </form>
            <p class="mt-5 text-sm text-slate-600">لديك حساب؟ <Link href="/login" class="font-semibold text-amber-700 hover:underline">تسجيل الدخول</Link></p>
        </section>
    </main>
</template>
