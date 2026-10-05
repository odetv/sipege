<script setup>
import { computed } from "vue";
import {
    Calendar,
    Search,
    Download,
    RefreshCw,
    Filter,
    Building2,
    Truck,
    Sparkles,
} from "lucide-vue-next";
import DateRangePicker from "@/Components/DateRangePicker.vue";

const props = defineProps({
    mode: { type: String, default: "hari_ini" },
    tanggalMulai: { type: String, default: "" },
    tanggalSelesai: { type: String, default: "" },
    periodeId: { type: [String, Number], default: "all" },
    periodes: { type: Array, default: () => [] },
    kategori: { type: String, default: "" },
    search: { type: String, default: "" },
    isExporting: { type: Boolean, default: false },
});

const emit = defineEmits([
    "update:mode",
    "update:tanggalMulai",
    "update:tanggalSelesai",
    "update:periodeId",
    "update:kategori",
    "update:search",
    "applyFilters",
    "resetFilters",
    "exportExcel",
]);

const modeButtons = [
    { id: "hari_ini", label: "Hari Ini" },
    { id: "periodik", label: "Periodik (14 Hari)" },
    { id: "bulanan", label: "Bulanan (28 Hari)" },
    { id: "custom", label: "Rentang Khusus" },
];

const categoryPills = [
    { id: "", label: "Semua Kategori" },
    { id: "Sekolah", label: "Satuan Pendidikan (14)" },
    { id: "Posyandu", label: "Posyandu (4)" },
];

function handleModeChange(newMode) {
    emit("update:mode", newMode);
}

function handleDateRangeChange(range) {
    if (range && range.start && range.end) {
        emit("update:tanggalMulai", range.start);
        emit("update:tanggalSelesai", range.end);
        emit("applyFilters");
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Top Banner Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-700 via-teal-700 to-cyan-800 p-6 shadow-lg text-white">
            <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-xs font-semibold tracking-wide text-emerald-100 border border-white/20">
                        <Truck class="h-3.5 w-3.5" />
                        <span>Sistem Logistik & Distribusi MBG</span>
                    </div>
                    <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight">
                        Rekapitulasi Distribusi Penerima Manfaat
                    </h1>
                    <p class="text-sm text-emerald-100/90 max-w-2xl">
                        Monitoring alokasi porsi harian dan periodik untuk Satuan Pendidikan (TK, SD, SMP, dsb.) dan Posyandu secara presisi.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        @click="$emit('exportExcel')"
                        :disabled="isExporting"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-emerald-800 font-bold text-xs lg:text-sm hover:bg-emerald-50 active:scale-95 transition-all shadow-md disabled:opacity-50 cursor-pointer"
                    >
                        <Download class="h-4 w-4 text-emerald-700" />
                        <span>{{ isExporting ? 'Mengekspor...' : 'Ekspor Rekap Excel' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter & Control Card -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-xs space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Mode Buttons -->
                <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/80 self-start">
                    <button
                        v-for="btn in modeButtons"
                        :key="btn.id"
                        type="button"
                        @click="handleModeChange(btn.id)"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            mode === btn.id
                                ? 'bg-white text-emerald-700 shadow-xs'
                                : 'text-slate-600 hover:text-slate-900',
                        ]"
                    >
                        {{ btn.label }}
                    </button>
                </div>

                <!-- Date Filter Section -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Single Date (Hari Ini) -->
                    <div v-if="mode === 'hari_ini'" class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-500">Tanggal:</span>
                        <input
                            type="date"
                            :value="tanggalMulai"
                            @input="$emit('update:tanggalMulai', $event.target.value); $emit('applyFilters')"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        />
                    </div>

                    <!-- Periode Selector -->
                    <div v-else-if="mode === 'periodik' || mode === 'bulanan'" class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-500">Siklus Periode:</span>
                        <select
                            :value="periodeId"
                            @change="$emit('update:periodeId', $event.target.value); $emit('applyFilters')"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white"
                        >
                            <option value="all">Hitung Berdasarkan Tanggal Mulai</option>
                            <option v-for="p in periodes" :key="p.id" :value="p.id">
                                Periode Ke-{{ p.nomor_periode }} ({{ p.tanggal_mulai }} s/d {{ p.tanggal_selesai }})
                            </option>
                        </select>
                        <input
                            type="date"
                            :value="tanggalMulai"
                            @input="$emit('update:tanggalMulai', $event.target.value); $emit('applyFilters')"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            title="Tanggal Mulai"
                        />
                    </div>

                    <!-- Custom Range -->
                    <div v-else class="flex items-center gap-2">
                        <DateRangePicker
                            :model-value="{ start: tanggalMulai, end: tanggalSelesai }"
                            @update:model-value="handleDateRangeChange"
                        />
                    </div>

                    <button
                        type="button"
                        @click="$emit('resetFilters')"
                        title="Reset Filter"
                        class="p-2 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition-colors cursor-pointer"
                    >
                        <RefreshCw class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Search and Category Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
                <!-- Category Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="pill in categoryPills"
                        :key="pill.id"
                        type="button"
                        @click="$emit('update:kategori', pill.id); $emit('applyFilters')"
                        :class="[
                            'px-3 py-1 rounded-full text-xs font-semibold transition-colors cursor-pointer',
                            kategori === pill.id
                                ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border border-transparent',
                        ]"
                    >
                        {{ pill.label }}
                    </button>
                </div>

                <!-- Instant Search Input -->
                <div class="relative w-full sm:w-72">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                    <input
                        type="text"
                        :value="search"
                        @input="$emit('update:search', $event.target.value)"
                        placeholder="Cari sekolah, posyandu, PIC..."
                        class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
