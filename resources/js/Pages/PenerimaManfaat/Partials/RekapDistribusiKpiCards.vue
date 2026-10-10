<script setup>
import { computed } from "vue";
import {
    Users,
    Calendar,
    BadgePercent,
    Utensils,
    PackageCheck,
    Building2,
} from "lucide-vue-next";

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    dateColumns: { type: Array, default: () => [] },
    modeSkala: { type: String, default: "bulanan" },
});

const activeSummary = computed(() => {
    return (props.summary && Object.keys(props.summary).length > 0)
        ? props.summary
        : (props.stats && Object.keys(props.stats).length > 0 ? props.stats : {});
});

const totalHariDisplay = computed(() => {
    if (props.dateColumns && props.dateColumns.length > 0) {
        return props.dateColumns.length;
    }
    return activeSummary.value.total_hari || 1;
});

const modeLabelDisplay = computed(() => {
    const count = totalHariDisplay.value;
    if (count === 1) return "Harian";
    return `Rentang ${count} Hari`;
});

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3">
        <!-- Card 1: Titik Distribusi -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Titik PM</p>
                <Building2 class="h-3.5 w-3.5 text-emerald-600" />
            </div>
            <p class="text-base sm:text-lg font-bold text-slate-900 mt-1">
                {{ activeSummary.total_kelompok ?? 0 }} <span class="text-xs font-normal text-slate-500">titik</span>
            </p>
            <p class="text-[10px] text-emerald-600 font-semibold mt-0.5 truncate">
                {{ activeSummary.total_sekolah ?? 0 }} Sekolah • {{ activeSummary.total_posyandu ?? 0 }} Posyandu
            </p>
        </div>

        <!-- Card 2: Rentang Hari -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Rentang Hari</p>
                <Calendar class="h-3.5 w-3.5 text-blue-600" />
            </div>
            <p class="text-base sm:text-lg font-bold text-slate-900 mt-1">
                {{ totalHariDisplay }} <span class="text-xs font-normal text-slate-500">Hari</span>
            </p>
            <p class="text-[10px] text-blue-600 font-medium mt-0.5 truncate">
                {{ modeLabelDisplay }}
            </p>
        </div>

        <!-- Card 3: Tingkat Layanan -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Tingkat Layanan</p>
                <BadgePercent class="h-3.5 w-3.5 text-teal-600" />
            </div>
            <p class="text-base sm:text-lg font-bold text-teal-700 mt-1">
                {{ activeSummary.persentase_layanan ?? 100 }}%
            </p>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                <div class="bg-teal-500 h-1.5 rounded-full" :style="{ width: `${Math.min(100, activeSummary.persentase_layanan ?? 100)}%` }"></div>
            </div>
        </div>

        <!-- Card 4: Total Sasaran PM -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Sasaran PM</p>
                <Users class="h-3.5 w-3.5 text-indigo-600" />
            </div>
            <p class="text-base sm:text-lg font-bold text-indigo-700 mt-1">
                {{ formatNumber(activeSummary.total_penerima) }} <span class="text-xs font-normal text-slate-500">PM</span>
            </p>
            <p class="text-[10px] text-slate-500 mt-0.5 truncate">
                Siswa, Balita & Bumil
            </p>
        </div>

        <!-- Card 5: Porsi Harian -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Porsi Harian</p>
                <Utensils class="h-3.5 w-3.5 text-amber-600" />
            </div>
            <p class="text-base sm:text-lg font-bold text-amber-700 mt-1">
                {{ formatNumber(activeSummary.total_porsi_harian) }} <span class="text-xs font-normal text-slate-500">porsi/hr</span>
            </p>
            <p class="text-[10px] text-slate-500 mt-0.5 truncate">
                PK: {{ formatNumber(activeSummary.total_porsi_kecil_harian) }} • PB: {{ formatNumber(activeSummary.total_porsi_besar_harian) }}
            </p>
        </div>

        <!-- Card 6: Akumulasi Porsi Periode -->
        <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
            <div class="flex items-center justify-between">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Akumulasi Porsi</p>
                <PackageCheck class="h-3.5 w-3.5 text-emerald-600" />
            </div>
            <p class="text-sm sm:text-base font-bold text-emerald-800 mt-1 truncate">
                {{ formatNumber(activeSummary.total_porsi_akumulasi) }} <span class="text-xs font-normal text-slate-500">porsi</span>
            </p>
            <p class="text-[10px] text-emerald-600 font-semibold mt-0.5 truncate">
                Rentang {{ totalHariDisplay }} Hari
            </p>
        </div>
    </div>
</template>
