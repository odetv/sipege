<script setup>
import { Head } from '@inertiajs/vue3';
import {
    ShieldAlert,
    AlertTriangle,
    QrCode,
    RefreshCw,
    PhoneCall,
    Home,
    Sparkles,
} from 'lucide-vue-next';

const props = defineProps({
    unitSppg: {
        type: Object,
        default: () => ({}),
    },
    isOldToken: {
        type: Boolean,
        default: true,
    },
    title: {
        type: String,
        default: 'Tautan Formulir Telah Berubah',
    },
    message: {
        type: String,
        default: 'Tautan mutasi/laporan keamanan yang Anda buka telah diganti atau diperbarui oleh pengelola SPPG.',
    },
});

function reloadPage() {
    window.location.reload();
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-800 to-slate-950 text-white font-sans flex items-center justify-center p-4">
        <Head title="Tautan Telah Berubah - Form Keamanan SPPG" />

        <div class="w-full max-w-lg bg-white/10 backdrop-blur-md rounded-3xl border border-white/15 p-6 sm:p-8 shadow-2xl text-center space-y-6 animate-in fade-in zoom-in-95 duration-200">
            <!-- Icon -->
            <div class="relative mx-auto w-20 h-20 rounded-3xl bg-amber-500/20 border-2 border-amber-400/40 flex items-center justify-center text-amber-400 shadow-lg">
                <ShieldAlert class="w-10 h-10" />
                <div class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-rose-500 flex items-center justify-center text-white text-xs font-black animate-pulse">
                    !
                </div>
            </div>

            <!-- Header Text -->
            <div class="space-y-2">
                <span class="inline-block px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-400/30">
                    Tautan Formulir Tidak Aktif
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    {{ title }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-sm mx-auto">
                    {{ message }}
                </p>
            </div>

            <!-- Card Info SPPG -->
            <div class="p-4 rounded-2xl bg-white/5 border border-white/10 text-left space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium">Satuan Pelayanan:</span>
                    <span class="font-bold text-emerald-400">{{ unitSppg?.nama || 'SPPG' }}</span>
                </div>
                <div v-if="unitSppg?.kode_sppg" class="flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium">Kode SPPG:</span>
                    <span class="font-mono font-bold text-slate-200">{{ unitSppg.kode_sppg }}</span>
                </div>
                <p v-if="unitSppg?.alamat_lengkap" class="text-[11px] text-slate-400 pt-1 border-t border-white/10">
                    {{ unitSppg.alamat_lengkap }}
                </p>
            </div>

            <!-- Petunjuk Langkah untuk Petugas Keamanan -->
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-left space-y-2.5">
                <span class="text-xs font-bold text-amber-300 flex items-center gap-1.5 uppercase tracking-wider">
                    <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                    <span>Petunjuk untuk Petugas Jaga:</span>
                </span>
                <ul class="text-xs text-slate-200 space-y-2 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-amber-400 shrink-0">1.</span>
                        <span>Hubungi <strong>Kepala SPPG / Koordinator</strong> untuk meminta tautan (link) formulir input terbaru.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-amber-400 shrink-0">2.</span>
                        <span>Atau <strong>pindai (scan) ulang QR Code fisik</strong> yang terpasang di Pos Penjagaan SPPG.</span>
                    </li>
                </ul>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-2 flex items-center justify-center gap-3 flex-wrap">
                <button
                    type="button"
                    @click="reloadPage"
                    class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/15 cursor-pointer transition-all flex items-center gap-1.5"
                >
                    <RefreshCw class="w-3.5 h-3.5" />
                    <span>Muat Ulang Halaman</span>
                </button>
            </div>
        </div>
    </div>
</template>

