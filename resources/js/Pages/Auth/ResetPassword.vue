<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const props = defineProps({ email: String, token: String });
const form = useForm({ token: props.token, email: props.email ?? '', password: '', password_confirmation: '' });
const submit = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="تعيين كلمة مرور جديدة" />
    <main dir="rtl" class="grid min-h-screen place-items-center px-4 py-10">
        <section class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="mb-6 text-2xl font-bold">تعيين كلمة مرور جديدة</h1>
            <form class="space-y-4" @submit.prevent="submit">
                <input v-model="form.email" type="email" required placeholder="البريد الإلكتروني" class="w-full rounded-xl border border-slate-300 px-3 py-3" />
                <input v-model="form.password" type="password" required autocomplete="new-password" placeholder="كلمة المرور الجديدة" class="w-full rounded-xl border border-slate-300 px-3 py-3" />
                <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" placeholder="تأكيد كلمة المرور" class="w-full rounded-xl border border-slate-300 px-3 py-3" />
                <span v-if="form.errors.email || form.errors.password" class="block text-xs text-red-600">{{ form.errors.email || form.errors.password }}</span>
                <button :disabled="form.processing" class="w-full rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white">حفظ كلمة المرور</button>
            </form>
            <Link href="/login" class="mt-5 block text-sm text-amber-700 hover:underline">العودة لتسجيل الدخول</Link>
        </section>
    </main>
</template>
