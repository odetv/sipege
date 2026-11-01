<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { Lock, Mail, Eye, EyeOff, ArrowLeft, KeyRound, AlertCircle, CheckCircle2 } from "lucide-vue-next";

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("password.store"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Atur Ulang Kata Sandi - SIPEGE" />

    <div
        class="min-h-screen bg-[#f8fafc] flex flex-col justify-center items-center p-4 sm:p-6 relative overflow-hidden select-none"
    >
        <!-- Card Container with Background Geometric Decorations -->
        <div class="relative w-full max-w-[440px]">
            <!-- Decorative Top-Right Dots -->
            <div
                class="absolute -top-7 -right-7 pointer-events-none z-0"
                aria-hidden="true"
            >
                <svg
                    width="120"
                    height="120"
                    fill="none"
                    viewBox="0 0 120 120"
                    class="text-indigo-400/40"
                >
                    <pattern
                        id="dots-top"
                        x="0"
                        y="0"
                        width="20"
                        height="20"
                        patternUnits="userSpaceOnUse"
                    >
                        <circle cx="3" cy="3" r="1.5" fill="currentColor" />
                    </pattern>
                    <rect width="120" height="120" fill="url(#dots-top)" />
                </svg>
            </div>

            <!-- Decorative Bottom-Left Rounded Box with Dots -->
            <div
                class="absolute -bottom-8 -left-8 pointer-events-none z-0 w-36 h-36 rounded-2xl bg-indigo-50/70 border border-indigo-100/60 p-3.5 flex items-center justify-center shadow-xs"
                aria-hidden="true"
            >
                <svg
                    width="110"
                    height="110"
                    fill="none"
                    viewBox="0 0 110 110"
                    class="text-indigo-400/45"
                >
                    <pattern
                        id="dots-bottom"
                        x="0"
                        y="0"
                        width="20"
                        height="20"
                        patternUnits="userSpaceOnUse"
                    >
                        <circle cx="3" cy="3" r="1.5" fill="currentColor" />
                    </pattern>
                    <rect width="110" height="110" fill="url(#dots-bottom)" />
                </svg>
            </div>

            <!-- Main White Card -->
            <div
                class="relative z-10 w-full bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 px-7 py-8 sm:px-9 sm:py-9"
            >
                <!-- Card Header -->
                <div class="text-center mb-6">
                    <div class="flex justify-center mb-3.5">
                        <img
                            src="/images/logo/BGN_LOGO_MAIN.png"
                            alt="Logo BGN"
                            class="h-16 w-16 object-contain drop-shadow-sm hover:scale-105 transition-transform"
                        />
                    </div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">
                        Atur Ulang Kata Sandi
                    </h1>
                    <p class="text-xs text-slate-500 font-normal mt-1.5">
                        Buat kata sandi baru yang kuat untuk akun SIPEGE Anda.
                    </p>
                </div>

                <!-- Error Alert -->
                <div
                    v-if="Object.keys(form.errors).length > 0"
                    class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-2"
                >
                    <AlertCircle class="h-4 w-4 shrink-0 mt-0.5 text-rose-500" />
                    <div>
                        <p class="font-bold mb-0.5">Terjadi kesalahan:</p>
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li v-for="(err, field) in form.errors" :key="field">
                                {{ err }}
                            </li>
                        </ul>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5"
                        >
                            Alamat Email
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <Mail class="h-4 w-4" />
                            </div>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                readonly
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-100/80 border border-slate-200 rounded-xl text-xs text-slate-600 font-mono cursor-not-allowed"
                            />
                        </div>
                    </div>

                    <!-- Password Baru -->
                    <div>
                        <label
                            for="password"
                            class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5"
                        >
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <Lock class="h-4 w-4" />
                            </div>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autofocus
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all font-medium"
                                :class="{ 'border-rose-400 bg-rose-50/30': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Konfirmasi Password Baru -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5"
                        >
                            Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <Lock class="h-4 w-4" />
                            </div>
                            <input
                                id="password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Ulangi kata sandi baru"
                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all font-medium"
                                :class="{ 'border-rose-400 bg-rose-50/30': form.errors.password_confirmation }"
                            />
                            <button
                                type="button"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer"
                            >
                                <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 hover:shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed pt-3"
                    >
                        <span v-if="form.processing" class="h-3.5 w-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <KeyRound v-else class="h-3.5 w-3.5" />
                        <span>{{ form.processing ? 'Menyimpan Sandi...' : 'Simpan Kata Sandi Baru' }}</span>
                    </button>
                </form>

                <!-- Back to Login -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 transition-colors"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>Kembali ke Halaman Masuk</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
