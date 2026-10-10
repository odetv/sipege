<script setup>
import { computed } from "vue";
import {
    Users,
    Coins,
    DollarSign,
    Building2,
    HeartPulse,
    Clock,
} from "lucide-vue-next";

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    selectedCount: { type: Number, default: 0 },
    totalRowsCount: { type: Number, default: 0 },
    totalPayrollAmount: { type: Number, default: 0 },
    insentifSekolah: { type: Number, default: 0 },
    insentifPosyandu: { type: Number, default: 0 },
    averagePayroll: { type: Number, default: 0 },
    totalPenerimaJiwa: { type: Number, default: 0 },
});

const displaySelected = computed(() => {
    return props.selectedCount > 0 ? props.selectedCount : (props.stats?.total_kelompok ?? 0);
});

const displayTotalRows = computed(() => {
    return props.totalRowsCount > 0 ? props.totalRowsCount : (props.stats?.total_rows ?? props.stats?.total_kelompok ?? 0);
});

const displayPayroll = computed(() => {
    return props.totalPayrollAmount > 0 ? props.totalPayrollAmount : (props.stats?.total_anggaran ?? 0);
});

const displaySekolah = computed(() => {
    return props.insentifSekolah > 0 ? props.insentifSekolah : (props.stats?.insentif_sekolah ?? 0);
});

const displayPosyandu = computed(() => {
    return props.insentifPosyandu > 0 ? props.insentifPosyandu : (props.stats?.insentif_posyandu ?? 0);
});

const displayJiwa = computed(() => {
    return props.totalPenerimaJiwa > 0 ? props.totalPenerimaJiwa : (props.stats?.total_penerima_jiwa ?? 0);
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
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
        <!-- Card 1: Penerima Terpilih -->
        <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
            <div class="p-2 sm:p-3 rounded-lg bg-emerald-50 text-emerald-600 shrink-0">
                <Users class="h-4 w-4 sm:h-5 sm:w-5" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                    Penerima Terpilih
                </p>
                <p class="text-base sm:text-xl font-black text-slate-900 leading-tight">
                    {{ displaySelected }} <span class="text-xs font-normal text-slate-400">/ {{ displayTotalRows }} Titik</span>
                </p>
                <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                    Total {{ formatNumber(displayJiwa) }} PM Terlayani
                </p>
            </div>
        </div>

        <!-- Card 2: Total Dana Insentif Tunai (Span 2 Kolom di HP) -->
        <div class="p-3.5 sm:p-4 rounded-xl bg-emerald-50/60 border border-emerald-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2.5 sm:gap-3.5 col-span-2 lg:col-span-2">
            <div class="p-2.5 sm:p-3 rounded-lg bg-emerald-600 text-white shrink-0 shadow-xs">
                <DollarSign class="h-5 w-5 sm:h-6 sm:w-6" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-bold text-emerald-800 uppercase tracking-wider">
                    Total Anggaran Insentif Tunai
                </p>
                <p class="text-xl sm:text-2xl font-black text-emerald-950 leading-tight truncate">
                    {{ formatRupiah(displayPayroll) }}
                </p>
                <p class="text-[10px] text-emerald-700 mt-0.5">
                    Penyaluran kas tunai langsung kepada Penanggung Jawab Satdik & Posyandu
                </p>
            </div>
        </div>

        <!-- Card 3: Alokasi Satuan Pendidikan -->
        <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
            <div class="p-2 sm:p-3 rounded-lg bg-blue-50 text-blue-600 shrink-0">
                <Building2 class="h-4 w-4 sm:h-5 sm:w-5" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                    Satuan Pendidikan
                </p>
                <p class="text-xs sm:text-base font-bold text-slate-900 leading-tight truncate">
                    {{ formatRupiah(displaySekolah) }}
                </p>
                <p class="text-[9.5px] sm:text-[10px] text-blue-600 font-medium mt-0.5 truncate">
                    {{ props.stats?.total_sekolah ?? 0 }} Satuan Pendidikan
                </p>
            </div>
        </div>

        <!-- Card 4: Alokasi Posyandu -->
        <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
            <div class="p-2 sm:p-3 rounded-lg bg-pink-50 text-pink-600 shrink-0">
                <HeartPulse class="h-4 w-4 sm:h-5 sm:w-5" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                    Kader Posyandu
                </p>
                <p class="text-xs sm:text-base font-bold text-slate-900 leading-tight truncate">
                    {{ formatRupiah(displayPosyandu) }}
                </p>
                <p class="text-[9.5px] sm:text-[10px] text-pink-600 font-medium mt-0.5 truncate">
                    {{ props.stats?.total_posyandu ?? 0 }} Posyandu (Balita & Bumil)
                </p>
            </div>
        </div>
    </div>
</template>
