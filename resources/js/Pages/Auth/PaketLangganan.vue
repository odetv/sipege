<script setup>
import { ref, computed } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardFooter from "@/Components/ui/CardFooter.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    CreditCard,
    CheckCircle2,
    Clock,
    AlertCircle,
    Building2,
    Copy,
    Check,
    LogOut,
    Sparkles,
    ShieldCheck,
    ArrowRight,
    HelpCircle,
    Receipt,
    RefreshCw,
    Zap,
    Crown,
    Star,
    Gift,
    Calendar,
    Send,
} from "lucide-vue-next";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    daftar_paket: {
        type: Object,
        default: () => ({}),
    },
    invoice: {
        type: Object,
        required: true,
    },
    rekening: {
        type: Array,
        default: () => [],
    },
});

const selectedSiklus = ref(props.user.siklus_langganan === 'tahunan' ? 'tahunan' : 'bulanan');
const copiedIndex = ref(null);
const isSimulating = ref(false);
const isSwitchingPaket = ref(false);
const isSubmittingFree = ref(false);
const showManualModal = ref(false);

const manualForm = useForm({
    catatan: "",
});

function copyToClipboard(text, index) {
    if (navigator?.clipboard?.writeText) {
        navigator.clipboard.writeText(text);
        copiedIndex.value = index;
        setTimeout(() => {
            copiedIndex.value = null;
        }, 2000);
    }
}

function formatRupiah(val) {
    const num = Number(val) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(num);
}

function gantiSiklus(siklus) {
    selectedSiklus.value = siklus;
    if (props.user.paket_langganan !== 'free') {
        pilihPaket(props.user.paket_langganan, siklus);
    }
}

function pilihPaket(kodePaket, siklus = selectedSiklus.value) {
    if (props.user.paket_langganan === kodePaket && props.user.siklus_langganan === siklus) return;
    isSwitchingPaket.value = true;
    router.post(route("paket-langganan.update-paket"), {
        paket_langganan: kodePaket,
        siklus_langganan: kodePaket === 'free' ? '3_hari' : siklus,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSwitchingPaket.value = false;
        },
    });
}

function handleAjukanFree() {
    isSubmittingFree.value = true;
    router.post(route("paket-langganan.ajukan-free"), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSubmittingFree.value = false;
        },
    });
}

function handleSimulasiBayar() {
    const isFree = props.user.paket_langganan === 'free';
    const confirmMsg = isFree
        ? "Simulasikan persetujuan Paket Free 3 Hari oleh Admin sekarang?\n\nSetelah disetujui, akun otomatis menjadi MEMBER dan Anda langsung diarahkan melengkapi data profil Kepala SPPG & Unit SPPG."
        : "Simulasikan pembayaran paket langganan sebagai LUNAS / VALID sekarang?\n\nSetelah valid, status akun Anda otomatis menjadi MEMBER dan Anda langsung diarahkan melengkapi data profil Kepala SPPG & Unit SPPG.";

    if (confirm(confirmMsg)) {
        isSimulating.value = true;
        router.post(route("paket-langganan.simulasi"), {}, {
            onFinish: () => {
                isSimulating.value = false;
            },
        });
    }
}

function handleManualSubmit() {
    manualForm.post(route("paket-langganan.konfirmasi"), {
        onSuccess: () => {
            showManualModal.value = false;
        },
    });
}

function handleLogout() {
    router.post(route("logout"));
}
</script>

<template>
    <Head title="Pilihan Paket Langganan - SIPEGE" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-slate-100/70 to-blue-50/50 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header Brand & Logout -->
            <div class="flex items-center justify-between pb-2 border-b border-slate-200/80">
                <div class="flex items-center gap-3">
                    <img
                        src="/images/logo/BGN_LOGO_MAIN.png"
                        alt="Logo BGN"
                        class="h-10 w-10 object-contain drop-shadow-sm"
                    />
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-xl tracking-tight text-slate-900">SIPEGE</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Paket Langganan</span>
                        </div>
                        <p class="text-xs text-slate-500">Sistem Pengelolaan Satuan Pelayanan Pemenuhan Gizi</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- User Profile Capsule -->
                    <div class="h-11 flex items-center gap-2.5 px-3 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-slate-700 to-slate-950 text-white font-black text-xs flex items-center justify-center shadow-xs shrink-0">
                            {{ (user.nama || 'U').charAt(0).toUpperCase() }}
                        </div>
                        <div class="text-left hidden sm:block">
                            <span class="text-xs font-bold text-slate-900 block leading-tight max-w-[150px] truncate">
                                {{ user.nama }}
                            </span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-[10px] font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span>Guest</span>
                                </span>
                                <span class="text-[10px] text-slate-400 truncate max-w-[110px]">{{ user.email }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Keluar Modern (Kotak Persegi Presisi Sejajar) -->
                    <button
                        @click="handleLogout"
                        class="h-11 w-11 flex items-center justify-center rounded-xl bg-white border border-slate-200/90 hover:border-rose-200 hover:bg-rose-50 text-slate-600 hover:text-rose-600 transition shadow-2xs hover:shadow-xs cursor-pointer shrink-0"
                        title="Keluar dari akun"
                        aria-label="Keluar dari akun"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Banner Alur Pendaftaran & Status -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5 sm:p-6">
                <!-- Stepper mini alur -->
                <div class="mb-6 pb-6 border-b border-slate-100">
                    <div class="flex items-center justify-between max-w-xl mx-auto relative">
                        <!-- Garis penghubung -->
                        <div class="absolute left-6 right-6 top-1/2 -translate-y-1/2 h-0.5 bg-slate-200 -z-0"></div>
                        <div class="absolute left-6 w-1/2 top-1/2 -translate-y-1/2 h-0.5 bg-blue-500 -z-0"></div>

                        <!-- Step 1: Registrasi Akun (Guest) -->
                        <div class="relative z-10 flex flex-col items-center bg-white px-2">
                            <div class="w-9 h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                <Check class="w-4 h-4 stroke-[3]" />
                            </div>
                            <span class="text-xs font-bold text-slate-800 mt-1.5">1. Buat Akun</span>
                            <span class="text-[10px] text-emerald-600 font-medium">Guest (Selesai)</span>
                        </div>

                        <!-- Step 2: Cek Status Bayar Paket Langganan (Aktif) -->
                        <div class="relative z-10 flex flex-col items-center bg-white px-2">
                            <div class="w-9 h-9 rounded-full bg-blue-600 text-white ring-4 ring-blue-100 flex items-center justify-center font-bold text-xs shadow-sm animate-pulse">
                                2
                            </div>
                            <span class="text-xs font-extrabold text-blue-700 mt-1.5">2. Pilih Langganan</span>
                            <span class="text-[10px] text-amber-600 font-semibold">Menuju Role Member</span>
                        </div>

                        <!-- Step 3: Pengisian Data Onboarding -->
                        <div class="relative z-10 flex flex-col items-center bg-white px-2">
                            <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-300 text-slate-400 flex items-center justify-center font-bold text-xs">
                                3
                            </div>
                            <span class="text-xs font-medium text-slate-400 mt-1.5">3. Lengkapi Data</span>
                            <span class="text-[10px] text-slate-400">Profil & Unit SPPG</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200/80 text-amber-700 text-xs font-semibold mb-2">
                            <Clock class="w-3.5 h-3.5" />
                            <span>Status: Akun Guest (Belum Aktif Member)</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Pilih Paket Langganan SIPEGE
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-2xl leading-relaxed">
                            Pilih paket langganan sesuai kebutuhan operasional unit SPPG Anda. Tersedia pilihan uji coba <strong>Free 3 Hari</strong> (bila disetujui admin) serta paket berbayar <strong>Bulanan</strong> maupun <strong>Tahunan</strong>.
                        </p>
                    </div>

                    <!-- Fast Action / Demo Mode -->
                    <div class="flex flex-col sm:flex-row md:flex-col gap-2 shrink-0">
                        <Button
                            @click="handleSimulasiBayar"
                            :disabled="isSimulating"
                            class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold shadow-md shadow-emerald-500/20 px-4 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <Sparkles class="w-4 h-4" />
                            <span>{{ isSimulating ? 'Memvalidasi...' : (user.paket_langganan === 'free' ? 'Simulasi Setujui Free (Dev Mode)' : 'Simulasi Pembayaran Valid (Dev Mode)') }}</span>
                        </Button>
                        <p class="text-[11px] text-slate-400 text-center md:text-right">
                            *Uji coba langsung ke status Member
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pilihan Paket Langganan & Switcher Durasi -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <Star class="w-4 h-4 text-amber-500 fill-amber-500" />
                            <span>4 Pilihan Paket Layanan</span>
                        </h2>
                        <p class="text-xs text-slate-500">Klik paket untuk memilih tagihan Anda</p>
                    </div>

                    <!-- Switcher Bulanan / Tahunan -->
                    <div class="inline-flex items-center p-1 rounded-xl bg-slate-200/80 border border-slate-300 self-start sm:self-auto">
                        <button
                            type="button"
                            @click="gantiSiklus('bulanan')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer',
                                selectedSiklus === 'bulanan'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            Tagihan Bulanan
                        </button>
                        <button
                            type="button"
                            @click="gantiSiklus('tahunan')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer',
                                selectedSiklus === 'tahunan'
                                    ? 'bg-white text-blue-700 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            <span>Tagihan Tahunan</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800 font-extrabold">Hemat 2 Bln</span>
                        </button>
                    </div>
                </div>

                <!-- 4 Cards Grid: Free, Basic, Standart, Pro -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- 1. Paket Free (3 Hari - Bila Disetujui) -->
                    <div
                        @click="pilihPaket('free')"
                        :class="[
                            'relative p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between bg-white',
                            user.paket_langganan === 'free'
                                ? 'border-teal-600 shadow-md ring-2 ring-teal-500/20 bg-teal-50/20'
                                : 'border-slate-200 hover:border-slate-300 hover:shadow-xs'
                        ]"
                    >
                        <div v-if="user.paket_langganan === 'free'" class="absolute -top-2.5 right-4 px-2 py-0.5 rounded-full bg-teal-600 text-white text-[10px] font-bold">
                            Dipilih
                        </div>
                        <div v-else class="absolute -top-2.5 right-4 px-2 py-0.5 rounded-full bg-teal-100 text-teal-800 border border-teal-200 text-[10px] font-bold">
                            Trial
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-extrabold text-slate-900 text-base">Free</span>
                                <Gift class="w-4 h-4 text-teal-600" />
                            </div>
                            <p class="text-xs text-slate-500 mb-3">
                                Uji coba gratis seluruh fitur operasional SPPG.
                            </p>
                            <div class="text-xl font-black text-slate-900">
                                Rp 0
                                <span class="text-xs font-normal text-slate-500 block text-[11px] text-teal-700 font-semibold mt-0.5">
                                    3 Hari (Bila Disetujui)
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span :class="user.paket_langganan === 'free' ? 'font-bold text-teal-700' : 'text-slate-500'">
                                {{ user.paket_langganan === 'free' ? 'Paket Aktif' : 'Pilih Paket Free' }}
                            </span>
                            <Check v-if="user.paket_langganan === 'free'" class="w-4 h-4 text-teal-600 stroke-[3]" />
                        </div>
                    </div>

                    <!-- 2. Paket Basic (100rb/bln) -->
                    <div
                        @click="pilihPaket('basic')"
                        :class="[
                            'relative p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between bg-white',
                            user.paket_langganan === 'basic'
                                ? 'border-blue-600 shadow-md ring-2 ring-blue-500/20 bg-blue-50/20'
                                : 'border-slate-200 hover:border-slate-300 hover:shadow-xs'
                        ]"
                    >
                        <div v-if="user.paket_langganan === 'basic'" class="absolute -top-2.5 right-4 px-2 py-0.5 rounded-full bg-blue-600 text-white text-[10px] font-bold">
                            Dipilih
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-extrabold text-slate-900 text-base">Basic</span>
                                <Zap class="w-4 h-4 text-blue-500" />
                            </div>
                            <p class="text-xs text-slate-500 mb-3">
                                Akses Standar Operasional SPPG, Menu & Distribusi.
                            </p>
                            <div class="text-xl font-black text-slate-900">
                                <template v-if="selectedSiklus === 'tahunan'">
                                    {{ formatRupiah(daftar_paket.basic?.harga_tahunan || 1000000) }}
                                    <span class="text-xs font-normal text-slate-500 block text-[11px]">/ tahun (hemat 200rb)</span>
                                </template>
                                <template v-else>
                                    {{ formatRupiah(daftar_paket.basic?.harga_bulanan || 100000) }}
                                    <span class="text-xs font-normal text-slate-500">/ bulan</span>
                                </template>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span :class="user.paket_langganan === 'basic' ? 'font-bold text-blue-700' : 'text-slate-500'">
                                {{ user.paket_langganan === 'basic' ? 'Paket Aktif' : 'Pilih Basic' }}
                            </span>
                            <Check v-if="user.paket_langganan === 'basic'" class="w-4 h-4 text-blue-600 stroke-[3]" />
                        </div>
                    </div>

                    <!-- 3. Paket Standart (200rb/bln) -->
                    <div
                        @click="pilihPaket('standart')"
                        :class="[
                            'relative p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between bg-white',
                            user.paket_langganan === 'standart'
                                ? 'border-blue-600 shadow-md ring-2 ring-blue-500/20 bg-blue-50/20'
                                : 'border-slate-200 hover:border-slate-300 hover:shadow-xs'
                        ]"
                    >
                        <div v-if="user.paket_langganan === 'standart'" class="absolute -top-2.5 right-4 px-2 py-0.5 rounded-full bg-blue-600 text-white text-[10px] font-bold">
                            Dipilih
                        </div>
                        <div v-else class="absolute -top-2.5 right-4 px-2 py-0.5 rounded-full bg-amber-500 text-white text-[10px] font-bold">
                            Populer
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-extrabold text-slate-900 text-base">Standart</span>
                                <Star class="w-4 h-4 text-amber-500 fill-amber-500" />
                            </div>
                            <p class="text-xs text-slate-500 mb-3">
                                Akses Lengkap SPPG, Manajemen Petugas & KPM.
                            </p>
                            <div class="text-xl font-black text-slate-900">
                                <template v-if="selectedSiklus === 'tahunan'">
                                    {{ formatRupiah(daftar_paket.standart?.harga_tahunan || 2000000) }}
                                    <span class="text-xs font-normal text-slate-500 block text-[11px]">/ tahun (hemat 400rb)</span>
                                </template>
                                <template v-else>
                                    {{ formatRupiah(daftar_paket.standart?.harga_bulanan || 200000) }}
                                    <span class="text-xs font-normal text-slate-500">/ bulan</span>
                                </template>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span :class="user.paket_langganan === 'standart' ? 'font-bold text-blue-700' : 'text-slate-500'">
                                {{ user.paket_langganan === 'standart' ? 'Paket Aktif' : 'Pilih Standart' }}
                            </span>
                            <Check v-if="user.paket_langganan === 'standart'" class="w-4 h-4 text-blue-600 stroke-[3]" />
                        </div>
                    </div>

                    <!-- 4. Paket Pro (350rb/bln) -->
                    <div
                        @click="pilihPaket('pro')"
                        :class="[
                            'relative p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between bg-white',
                            user.paket_langganan === 'pro'
                                ? 'border-purple-600 shadow-md ring-2 ring-purple-500/20 bg-purple-50/20'
                                : 'border-slate-200 hover:border-slate-300 hover:shadow-xs'
                        ]"
                    >
                        <div v-if="user.paket_langganan === 'pro'" class="absolute -top-2.5 right-4 px-2 py-0.5 rounded-full bg-purple-600 text-white text-[10px] font-bold">
                            Dipilih
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-extrabold text-slate-900 text-base">Pro</span>
                                <Crown class="w-4 h-4 text-purple-600" />
                            </div>
                            <p class="text-xs text-slate-500 mb-3">
                                Prioritas Unlimited, Analisis AI Residu & Export Lengkap.
                            </p>
                            <div class="text-xl font-black text-slate-900">
                                <template v-if="selectedSiklus === 'tahunan'">
                                    {{ formatRupiah(daftar_paket.pro?.harga_tahunan || 3500000) }}
                                    <span class="text-xs font-normal text-slate-500 block text-[11px]">/ tahun (hemat 700rb)</span>
                                </template>
                                <template v-else>
                                    {{ formatRupiah(daftar_paket.pro?.harga_bulanan || 350000) }}
                                    <span class="text-xs font-normal text-slate-500">/ bulan</span>
                                </template>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span :class="user.paket_langganan === 'pro' ? 'font-bold text-purple-700' : 'text-slate-500'">
                                {{ user.paket_langganan === 'pro' ? 'Paket Aktif' : 'Pilih Pro' }}
                            </span>
                            <Check v-if="user.paket_langganan === 'pro'" class="w-4 h-4 text-purple-600 stroke-[3]" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Invoice & Opsi Eksekusi -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Kolom Kiri: Rincian Tagihan (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <Card class="border-slate-200 shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-950 p-5 text-white">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <Receipt class="w-5 h-5 text-blue-400" />
                                    <span class="text-xs uppercase tracking-widest text-slate-300 font-bold">Rincian Paket Terpilih</span>
                                </div>
                                <span
                                    :class="[
                                        'px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border',
                                        invoice.is_free
                                            ? 'bg-teal-400/20 text-teal-300 border-teal-400/30'
                                            : 'bg-amber-400/20 text-amber-300 border-amber-400/30'
                                    ]"
                                >
                                    {{ invoice.is_free ? 'TRIAL 3 HARI (BILA DISETUJUI)' : 'MENUNGGU PEMBAYARAN' }}
                                </span>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <div>
                                    <span class="text-xs text-slate-400">{{ invoice.is_free ? 'Biaya Langganan:' : 'Total Tagihan:' }}</span>
                                    <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                                        {{ formatRupiah(invoice.total) }}
                                    </h3>
                                </div>
                                <div class="text-right">
                                    <span class="text-[11px] text-slate-400 block">Nomor Invoice</span>
                                    <span class="text-xs font-mono font-bold text-blue-200">{{ invoice.nomor }}</span>
                                </div>
                            </div>
                        </div>

                        <CardContent class="p-5 sm:p-6 space-y-4 text-xs sm:text-sm">
                            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                                <div>
                                    <span class="text-slate-400 text-xs block">Tanggal Invoice:</span>
                                    <span class="font-semibold text-slate-800">{{ invoice.tanggal }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 text-xs block">Batas Waktu:</span>
                                    <span class="font-semibold text-rose-600">{{ invoice.jatuh_tempo }}</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Item Layanan</span>
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ invoice.item }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">Durasi Layanan: {{ invoice.periode }}</p>
                                        <p class="text-[11px] text-emerald-600 font-semibold mt-1">
                                            ✓ Mengaktifkan Akun Pengguna ke Role Member
                                        </p>
                                    </div>
                                    <span class="font-extrabold text-slate-900 shrink-0">{{ formatRupiah(invoice.subtotal) }}</span>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-100 space-y-1.5">
                                <div class="flex justify-between text-slate-500 text-xs">
                                    <span>Subtotal Tagihan</span>
                                    <span>{{ formatRupiah(invoice.subtotal) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-500 text-xs">
                                    <span>Pajak (PPN 0%)</span>
                                    <span>Rp 0</span>
                                </div>
                                <div class="flex justify-between font-black text-slate-900 text-base pt-2 border-t border-slate-200">
                                    <span>Total Yang Harus Dibayar</span>
                                    <span class="text-blue-700">{{ formatRupiah(invoice.total) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Alert Info Konfirmasi Catatan User jika ada -->
                    <div v-if="user.catatan_pembayaran" class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-800 flex items-start gap-2.5">
                        <AlertCircle class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold block">Status Catatan / Pengajuan Anda:</span>
                            <p class="text-blue-700 mt-0.5">{{ user.catatan_pembayaran }}</p>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Pembayaran / Pengajuan Free (5 cols) -->
                <div class="lg:col-span-5 space-y-6">
                    <!-- Jika Memilih Paket Free (Trial 3 Hari) -->
                    <Card v-if="invoice.is_free" class="border-teal-200 bg-gradient-to-b from-teal-50/50 to-white shadow-sm">
                        <CardHeader class="pb-3 border-b border-teal-100">
                            <CardTitle class="text-base font-bold text-teal-900 flex items-center gap-2">
                                <Gift class="w-4 h-4 text-teal-600" />
                                <span>Pengajuan Paket Free (Trial 3 Hari)</span>
                            </CardTitle>
                            <CardDescription class="text-xs text-teal-700">
                                Paket Free 3 Hari tidak dipungut biaya transfer, namun membutuhkan persetujuan oleh Administrator sebelum aktivasi role Member.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-4 space-y-3">
                            <div class="p-3 rounded-xl bg-white border border-teal-200 text-xs text-teal-900 space-y-1.5">
                                <span class="font-bold block">Syarat Paket Free 3 Hari:</span>
                                <ul class="list-disc pl-4 space-y-1 text-[11px] text-teal-800">
                                    <li>Berlaku untuk 1 kali uji coba operasional per akun SPPG.</li>
                                    <li>Masa aktif 3 hari kalender setelah disetujui.</li>
                                    <li>Dapat ditingkatkan ke paket Basic, Standart, atau Pro kapan saja.</li>
                                </ul>
                            </div>
                        </CardContent>
                        <CardFooter class="p-4 pt-0 flex flex-col gap-2">
                            <Button
                                @click="handleAjukanFree"
                                :disabled="isSubmittingFree"
                                class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs py-2.5 rounded-xl shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <Send class="w-3.5 h-3.5" />
                                <span>{{ isSubmittingFree ? 'Mengirim Pengajuan...' : 'Ajukan Paket Free ke Admin' }}</span>
                            </Button>
                        </CardFooter>
                    </Card>

                    <!-- Jika Memilih Paket Berbayar (Basic / Standart / Pro) -->
                    <Card v-else class="border-slate-200 shadow-sm">
                        <CardHeader class="pb-3 border-b border-slate-100">
                            <CardTitle class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <CreditCard class="w-4 h-4 text-blue-600" />
                                <span>Rekening Tujuan Transfer</span>
                            </CardTitle>
                            <CardDescription class="text-xs text-slate-500">
                                Transfer tepat sesuai total tagihan ke salah satu rekening resmi di bawah ini:
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-4 space-y-3">
                            <div
                                v-for="(bank, idx) in rekening"
                                :key="bank.bank"
                                class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-white hover:border-blue-300 transition"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-black px-2 py-0.5 rounded bg-slate-800 text-white tracking-wider">
                                        {{ bank.bank }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 font-medium">Bank Transfer</span>
                                </div>

                                <div class="flex items-center justify-between mt-2">
                                    <div>
                                        <span class="text-xs text-slate-400 block">Nomor Rekening:</span>
                                        <span class="font-mono font-black text-slate-900 text-sm tracking-wide">{{ bank.nomor }}</span>
                                    </div>
                                    <button
                                        @click="copyToClipboard(bank.nomor.replace(/-/g, ''), idx)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 shadow-sm transition cursor-pointer"
                                    >
                                        <template v-if="copiedIndex === idx">
                                            <Check class="w-3.5 h-3.5 text-emerald-600" />
                                            <span class="text-emerald-700 text-[11px]">Tersalin</span>
                                        </template>
                                        <template v-else>
                                            <Copy class="w-3.5 h-3.5 text-slate-500" />
                                            <span class="text-[11px]">Salin</span>
                                        </template>
                                    </button>
                                </div>

                                <div class="mt-1.5 pt-1.5 border-t border-slate-200/60 text-[11px] text-slate-500">
                                    a.n. <strong class="text-slate-700">{{ bank.atas_nama }}</strong>
                                </div>
                            </div>
                        </CardContent>

                        <CardFooter class="pt-0 p-4 flex flex-col gap-2">
                            <button
                                @click="showManualModal = true"
                                class="w-full py-2.5 text-xs font-bold text-slate-700 hover:text-blue-700 bg-slate-100 hover:bg-blue-50 border border-slate-200 rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>Konfirmasi Bukti Transfer Manual</span>
                            </button>
                        </CardFooter>
                    </Card>

                    <!-- Keamanan & Garansi -->
                    <div class="p-4 rounded-xl border border-slate-200/70 bg-white text-xs text-slate-500 flex items-center gap-3">
                        <ShieldCheck class="w-6 h-6 text-emerald-600 shrink-0" />
                        <div>
                            <span class="font-bold text-slate-800 block">Aktivasi Otomatis Role Member</span>
                            <span>Setelah pembayaran terverifikasi valid atau disetujui, akun langsung naik ke status Member untuk pengisian data unit SPPG.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Manual -->
        <div v-if="showManualModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h4 class="font-bold text-slate-900 text-sm">Konfirmasi Pembayaran Manual</h4>
                    <button @click="showManualModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold cursor-pointer">✕</button>
                </div>

                <p class="text-xs text-slate-600">
                    Sudah melakukan transfer manual via ATM/M-Banking? Tuliskan nama bank pengirim dan nomor referensi transaksi Anda di bawah ini:
                </p>

                <div>
                    <textarea
                        v-model="manualForm.catatan"
                        rows="3"
                        placeholder="Contoh: Transfer via BCA a.n Budi Santoso, Jam 14:30 WIB, Ref: 882910"
                        class="w-full text-xs rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button variant="outline" size="sm" @click="showManualModal = false">Batal</Button>
                    <Button size="sm" @click="handleManualSubmit" :disabled="manualForm.processing">
                        Kirim Konfirmasi
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
