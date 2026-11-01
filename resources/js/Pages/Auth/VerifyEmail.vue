<script setup>
import { computed } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { MailCheck, Send, LogOut, CheckCircle2, ShieldCheck } from "lucide-vue-next";

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
});

const page = usePage();
const userEmail = computed(() => page.props.auth?.user?.email || "email Anda");

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent"
);
</script>

<template>
    <Head title="Verifikasi Alamat Email - SIPEGE" />

    <div
        class="min-h-screen bg-[#f8fafc] flex flex-col justify-center items-center p-4 sm:p-6 relative overflow-hidden select-none"
    >
        <!-- Card Container with Background Geometric Decorations -->
        <div class="relative w-full max-w-[460px]">
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
                class="relative z-10 w-full bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 px-7 py-8 sm:px-9 sm:py-9 text-center"
            >
                <!-- Logo & Badge -->
                <div class="flex justify-center mb-3.5">
                    <img
                        src="/images/logo/BGN_LOGO_MAIN.png"
                        alt="Logo BGN"
                        class="h-16 w-16 object-contain drop-shadow-sm hover:scale-105 transition-transform"
                    />
                </div>

                <!-- Animated Icon -->
                <div class="mx-auto w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner mb-4">
                    <MailCheck class="h-7 w-7" />
                </div>

                <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">
                    Verifikasi Alamat Email Anda
                </h1>

                <p class="text-xs text-slate-600 leading-relaxed mt-2.5">
                    Terima kasih telah mendaftar di <strong>SIPEGE</strong>! Tautan verifikasi telah kami kirimkan ke:
                </p>

                <!-- Box Alamat Email -->
                <div class="my-3 py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 font-mono text-xs font-bold text-indigo-700 break-all select-all">
                    {{ userEmail }}
                </div>

                <p class="text-[11.5px] text-slate-500 leading-relaxed">
                    Silakan buka email Anda dan klik tombol <strong>"Verifikasi Email Sekarang"</strong> untuk mengaktifkan akun dan melanjutkan ke aplikasi.
                </p>

                <!-- Status Tautan Berhasil Dikirim Ulang -->
                <div
                    v-if="verificationLinkSent"
                    class="my-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-center gap-2"
                >
                    <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600" />
                    <span class="font-medium text-left">Tautan verifikasi baru berhasil dikirim ke email Anda!</span>
                </div>

                <!-- Petunjuk jika belum menerima -->
                <div class="my-4 p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 text-[11px] text-amber-900 text-left flex items-start gap-2">
                    <ShieldCheck class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                    <p class="leading-relaxed">
                        Belum menerima email? Periksa juga folder <strong>Spam / Junk</strong> Anda, atau tekan tombol kirim ulang di bawah.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 hover:shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing" class="h-3.5 w-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <Send v-else class="h-3.5 w-3.5" />
                        <span>{{ form.processing ? 'Mengirim Ulang...' : 'Kirim Ulang Email Verifikasi' }}</span>
                    </button>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                        <span>Keluar / Ganti Akun</span>
                    </Link>
                </form>
            </div>
        </div>
    </div>
</template>
