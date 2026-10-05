<script setup>
import { computed } from "vue";
import {
    Users,
    Utensils,
    PackageCheck,
    Building2,
    Calendar,
    HeartPulse,
    TrendingUp,
} from "lucide-vue-next";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
});

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Sasaran Jiwa -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Total Sasaran PM
                </span>
                <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <Users class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ formatNumber(stats.total_penerima) }}
                    <span class="text-xs font-semibold text-slate-500">Jiwa</span>
                </div>
                <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1 font-medium text-blue-600">
                        {{ stats.total_sekolah || 0 }} Sekolah
                    </span>
                    <span>•</span>
                    <span class="inline-flex items-center gap-1 font-medium text-pink-600">
                        {{ stats.total_posyandu || 0 }} Posyandu
                    </span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
        </div>

        <!-- Card 2: Porsi Harian -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Porsi Harian
                </span>
                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <Utensils class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ formatNumber(stats.total_porsi_harian) }}
                    <span class="text-xs font-semibold text-slate-500">Porsi / Hari</span>
                </div>
                <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-medium text-emerald-600">
                        PK: {{ formatNumber(stats.total_porsi_kecil_harian) }}
                    </span>
                    <span>•</span>
                    <span class="font-medium text-teal-600">
                        PB: {{ formatNumber(stats.total_porsi_besar_harian) }}
                    </span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
        </div>

        <!-- Card 3: Porsi Akumulasi Periode -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Porsi Terakumulasi
                </span>
                <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <PackageCheck class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ formatNumber(stats.total_porsi_akumulasi) }}
                    <span class="text-xs font-semibold text-slate-500">Total Porsi</span>
                </div>
                <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                    <Calendar class="h-3.5 w-3.5 text-purple-500" />
                    <span>Rentang {{ stats.total_hari || 1 }} Hari Operasional</span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
        </div>

        <!-- Card 4: Titik Layanan -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    Titik Layanan Aktif
                </span>
                <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <Building2 class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ stats.total_kelompok || 0 }}
                    <span class="text-xs font-semibold text-slate-500">Kelompok / Lokasi</span>
                </div>
                <div class="mt-1 flex items-center gap-1 text-xs text-emerald-600 font-semibold">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>100% Terlayani Tepat Waktu</span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
        </div>
    </div>
</template>
