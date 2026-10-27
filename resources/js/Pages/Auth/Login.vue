<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { Lock, Mail, AlertCircle, Eye, EyeOff, Info } from "lucide-vue-next";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);
const showDemoInfo = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const fillDemoAccount = () => {
    form.email = "admin@sipege.com";
    form.password = "Password@123";
    showDemoInfo.value = false;
};

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Masuk ke Sistem" />

    <div
        class="min-h-screen bg-[#f8fafc] flex flex-col justify-center items-center p-4 sm:p-6 relative overflow-hidden select-none"
    >
        <!-- Card Container with Background Geometric Decorations -->
        <div class="relative w-full max-w-[420px]">
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
                <!-- Tombol Info Akun Demo (Pojok Kanan Atas di dalam Card) -->
                <div class="absolute top-4 right-4 sm:top-5 sm:right-5 z-20">
                    <button
                        type="button"
                        @click.stop="showDemoInfo = !showDemoInfo"
                        class="w-7 h-7 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-indigo-600 border border-slate-200/80 shadow-2xs hover:shadow-xs flex items-center justify-center transition-all cursor-pointer focus:outline-none"
                        :class="{ 'text-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/50 border-indigo-200': showDemoInfo }"
                        title="Info Akun Demo"
                    >
                        <Info class="h-3.5 w-3.5" />
                    </button>

                    <!-- Tooltip / Popover Bubble -->
                    <transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 translate-y-1 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="opacity-100 translate-y-0 scale-100"
                        leave-to-class="opacity-0 translate-y-1 scale-95"
                    >
                        <div
                            v-if="showDemoInfo"
                            class="absolute top-full right-0 mt-2 w-64 p-3 bg-white border border-slate-200 rounded-2xl shadow-xl text-left z-50 select-text"
                        >
                            <!-- Tooltip Arrow pointing up right -->
                            <div
                                class="absolute -top-1.5 right-2.5 w-3 h-3 bg-white border-t border-l border-slate-200 rotate-45"
                            ></div>

                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 mb-2">
                                <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <Info class="h-3.5 w-3.5 text-indigo-500" />
                                    Akun Demo
                                </span>
                                <button
                                    type="button"
                                    @click="showDemoInfo = false"
                                    class="text-slate-400 hover:text-slate-600 text-xs cursor-pointer p-0.5 leading-none"
                                >
                                    ✕
                                </button>
                            </div>

                            <div class="space-y-1 text-xs">
                                <div class="flex items-center justify-between bg-slate-50 px-2 py-1.5 rounded-lg border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px]">Email:</span>
                                    <span class="font-mono font-semibold text-slate-700 text-[11px]">admin@sipege.com</span>
                                </div>
                                <div class="flex items-center justify-between bg-slate-50 px-2 py-1.5 rounded-lg border border-slate-100">
                                    <span class="text-slate-500 text-[10.5px]">Password:</span>
                                    <span class="font-mono font-semibold text-slate-700 text-[11px]">Password@123</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="fillDemoAccount"
                                class="w-full mt-2.5 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50/80 hover:bg-indigo-100 rounded-xl transition-colors cursor-pointer flex items-center justify-center gap-1.5"
                            >
                                <span>Gunakan Akun Ini</span>
                            </button>
                        </div>
                    </transition>

                    <!-- Transparent overlay to close on outside click -->
                    <div
                        v-if="showDemoInfo"
                        class="fixed inset-0 z-40"
                        @click="showDemoInfo = false"
                    ></div>
                </div>

                <!-- Card Header (Logo & Title inside card like example) -->
                <div class="text-center mb-6">
                    <div class="flex justify-center mb-3.5">
                        <img
                            src="/images/logo/BGN_LOGO_MAIN.png"
                            alt="Logo BGN"
                            class="h-16 w-16 object-contain drop-shadow-sm hover:scale-105 transition-transform"
                        />
                    </div>
                    <h1
                        class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight"
                    >
                        SIPEGE
                    </h1>
                    <p class="text-xs text-slate-500 font-normal mt-1">
                        Silahkan masuk untuk memulai aplikasi.
                    </p>
                </div>

                <!-- Status Message -->
                <div
                    v-if="status"
                    class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium"
                >
                    {{ status }}
                </div>

                <!-- General Errors -->
                <div
                    v-if="Object.keys(form.errors).length > 0"
                    class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2"
                >
                    <AlertCircle class="h-4 w-4 shrink-0 mt-0.5 text-red-500" />
                    <div>
                        <p class="font-semibold mb-0.5">
                            Terjadi kesalahan login:
                        </p>
                        <ul class="list-disc pl-4 space-y-0.5">
                            <li
                                v-for="(err, field) in form.errors"
                                :key="field"
                            >
                                {{ err }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email / Username Input -->
                    <div class="space-y-1.5">
                        <label
                            for="email"
                            class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider"
                        >
                            Email <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
                            >
                                <Mail class="h-4 w-4" />
                            </div>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                placeholder="Masukkan email atau username"
                                required
                                autofocus
                                autocomplete="username"
                                class="w-full h-11 pl-10 pr-3.5 rounded-xl border border-slate-200/90 bg-[#f8fafc] hover:bg-[#f1f5f9]/70 focus:bg-white text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/15 transition-all"
                            />
                        </div>
                        <p
                            v-if="form.errors.email"
                            class="text-xs text-rose-600 font-medium mt-1"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label
                                for="password"
                                class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider"
                            >
                                Password <span class="text-rose-500">*</span>
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs text-indigo-600 hover:text-indigo-700 font-medium hover:underline"
                            >
                                Lupa Password?
                            </Link>
                        </div>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
                            >
                                <Lock class="h-4 w-4" />
                            </div>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                                class="w-full h-11 pl-10 pr-10 rounded-xl border border-slate-200/90 bg-[#f8fafc] hover:bg-[#f1f5f9]/70 focus:bg-white text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/15 transition-all"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="text-xs text-rose-600 font-medium mt-1"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between pt-0.5">
                        <label
                            class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-2xs focus:ring-indigo-500/20"
                            />
                            <span>Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Submit Button: Only "Masuk" without icon as requested -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full h-11 bg-[#4f5ff7] hover:bg-[#4351df] active:bg-[#3845c4] text-white font-semibold text-sm rounded-xl shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed mt-2"
                    >
                        <span>{{
                            form.processing ? "Memproses..." : "Masuk"
                        }}</span>
                    </button>
                </form>

                <!-- Footer link inside card -->
                <div
                    class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-500"
                >
                    Belum memiliki akun?
                    <Link
                        :href="route('register')"
                        class="text-indigo-600 font-semibold hover:underline ml-1"
                    >
                        Daftar Baru
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
