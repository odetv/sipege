<script setup>
import {
    Users,
    CreditCard,
    CheckCircle2,
    AlertCircle,
    Coins,
    TrendingUp,
} from "lucide-vue-next";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
});

function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Penerima Insentif -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Penerima Terdaftar
                </span>
                <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <Users class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ formatNumber(stats.total_penerima_insentif) }}
                    <span class="text-xs font-semibold text-slate-500">Orang</span>
                </div>
                <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-medium text-pink-600">{{ stats.total_kader || 0 }} Kader Posyandu</span>
                    <span>•</span>
                    <span class="font-medium text-blue-600">{{ stats.total_sekolah || 0 }} PIC Sekolah</span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
        </div>

        <!-- Card 2: Total Anggaran Insentif -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Total Anggaran Insentif
                </span>
                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <Coins class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-emerald-700 tracking-tight">
                    {{ formatRupiah(stats.total_anggaran) }}
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    Total dialokasikan untuk {{ stats.total_kelompok || 0 }} titik distribusi
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
        </div>

        <!-- Card 3: Status Rekening -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Status Rekening
                </span>
                <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <CreditCard class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ stats.rekening_valid_count || 0 }}
                    <span class="text-xs font-semibold text-slate-500">/ {{ stats.total_kelompok || 0 }} Siap</span>
                </div>
                <div class="mt-1 flex items-center gap-1.5 text-xs">
                    <span v-if="(stats.rekening_tidak_valid_count || 0) === 0" class="text-emerald-600 font-semibold inline-flex items-center gap-1">
                        <CheckCircle2 class="h-3.5 w-3.5" /> 100% Rekening Valid
                    </span>
                    <span v-else class="text-amber-600 font-semibold inline-flex items-center gap-1">
                        <AlertCircle class="h-3.5 w-3.5" /> {{ stats.rekening_tidak_valid_count }} Rekening Kosong
                    </span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
        </div>

        <!-- Card 4: Rata-rata Insentif -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Rata-rata Insentif / Titik
                </span>
                <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <TrendingUp class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ formatRupiah(stats.rata_rata_insentif) }}
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    Per sekolah / posyandu per periode
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
        </div>
    </div>
</template>
