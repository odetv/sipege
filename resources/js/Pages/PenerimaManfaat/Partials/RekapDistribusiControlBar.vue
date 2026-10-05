<script setup>
import { ref, watch } from "vue";
import {
    CalendarCheck,
    Clock,
    Calendar,
    Filter,
    Search,
    Download,
    ChevronDown,
    Building2,
    Sparkles,
    RefreshCw,
    Save,
    Check,
    RotateCcw,
    Layers,
} from "lucide-vue-next";
import DateRangePicker from "@/Components/DateRangePicker.vue";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";

const props = defineProps({
    modeSkala: { type: String, default: "bulanan" },
    selectedPeriodeId: { type: [String, Number], default: "all" },
    periodes: { type: Array, default: () => [] },
    tanggalMulai: { type: String, default: "" },
    tanggalSelesai: { type: String, default: "" },
    dateColumns: { type: Array, default: () => [] },
    labelSiklus: { type: String, default: "" },
    searchQuery: { type: String, default: "" },
    selectedKategoriFilter: { type: String, default: "all" },
    selectedStatusFilter: { type: String, default: "all" },
    unitSppg: { type: Object, default: null },
    todayStr: { type: String, default: "" },
    isExporting: { type: Boolean, default: false },
    isDirty: { type: Boolean, default: false },
    isSaving: { type: Boolean, default: false },
});

const emit = defineEmits([
    "update:modeSkala",
    "update:searchQuery",
    "update:selectedKategoriFilter",
    "update:selectedStatusFilter",
    "selectPeriode",
    "changeModeSkala",
    "applyDateRange",
    "simpanDistribusi",
    "setSemuaTerkirim",
    "kosongkanSemua",
    "resetDistribusi",
    "openExportModal",
    "downloadHariIniDirect",
]);

const isExportMenuOpen = ref(false);
const isToolsMenuOpen = ref(false);
const isDatePickerOpen = ref(false);

const datePickerRange = ref({
    start: props.tanggalMulai,
    end: props.tanggalSelesai,
});

watch(
    () => [props.tanggalMulai, props.tanggalSelesai],
    ([newStart, newEnd]) => {
        datePickerRange.value = {
            start: newStart,
            end: newEnd,
        };
    }
);

function onApplyDateRange(newRange) {
    if (newRange && newRange.start && newRange.end) {
        emit("applyDateRange", newRange);
        isDatePickerOpen.value = false;
    }
}
</script>

<template>
    <div class="space-y-3">
        <!-- ─── Header Halaman (Style Persis Rekap Kehadiran) ─────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <CalendarCheck class="h-6 w-6 text-emerald-600" />
                    <span>Rekapitulasi Distribusi Penerima Manfaat</span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Monitoring alokasi porsi harian & matriks logistik pengiriman MBG Unit SPPG
                    <span v-if="unitSppg" class="font-semibold text-slate-700"> • {{ unitSppg.nama_sppg || unitSppg.nama }}</span>
                </p>
            </div>

            <!-- Tombol Aksi Kanan: Simpan Data & Ekspor Excel -->
            <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
                <!-- Tombol Simpan Presensi/Distribusi (Identik Rekap Kehadiran) -->
                <button
                    type="button"
                    @click="$emit('simpanDistribusi')"
                    :disabled="isSaving"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs font-black transition-all cursor-pointer shadow-xs disabled:opacity-50',
                        isDirty
                            ? 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white ring-2 ring-emerald-400/50'
                            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200',
                    ]"
                    :title="isDirty ? 'Ada perubahan yang belum disimpan ke database!' : 'Semua data telah tersimpan'"
                >
                    <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin text-white" />
                    <Save v-else class="h-4 w-4" :class="isDirty ? 'text-white' : 'text-slate-500'" />
                    <span>{{ isSaving ? 'Menyimpan...' : (isDirty ? 'Simpan Rekap' : 'Tersimpan') }}</span>
                    <span v-if="isDirty" class="w-2 h-2 rounded-full bg-amber-400"></span>
                </button>

                <!-- Dropdown Quick Tools / Batch Actions -->
                <div class="relative">
                    <button
                        type="button"
                        @click="isToolsMenuOpen = !isToolsMenuOpen"
                        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold cursor-pointer shadow-2xs"
                        title="Tindakan Cepat Matriks"
                    >
                        <Layers class="h-3.5 w-3.5 text-slate-500" />
                        <span class="hidden sm:inline">Aksi Cepat</span>
                        <ChevronDown class="h-3 w-3 text-slate-400 transition-transform" :class="{ 'rotate-180': isToolsMenuOpen }" />
                    </button>

                    <div v-if="isToolsMenuOpen" class="fixed inset-0 z-40" @click="isToolsMenuOpen = false"></div>

                    <div
                        v-if="isToolsMenuOpen"
                        class="absolute right-0 top-full mt-1.5 z-50 w-56 rounded-xl bg-white border border-slate-200 shadow-xl py-1 text-xs divide-y divide-slate-100"
                    >
                        <div class="p-1">
                            <button
                                type="button"
                                @click="isToolsMenuOpen = false; $emit('setSemuaTerkirim')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-emerald-50 text-emerald-800 font-bold flex items-center gap-2 cursor-pointer"
                            >
                                <Check class="h-3.5 w-3.5 text-emerald-600" />
                                <span>Set Semua Terkirim (T)</span>
                            </button>
                            <button
                                type="button"
                                @click="isToolsMenuOpen = false; $emit('kosongkanSemua')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-rose-50 text-rose-700 font-medium flex items-center gap-2 cursor-pointer mt-0.5"
                            >
                                <RotateCcw class="h-3.5 w-3.5 text-rose-500" />
                                <span>Kosongkan Semua Sel</span>
                            </button>
                        </div>
                        <div class="p-1">
                            <button
                                type="button"
                                @click="isToolsMenuOpen = false; $emit('resetDistribusi')"
                                class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-600 font-medium flex items-center gap-2 cursor-pointer"
                            >
                                <RefreshCw class="h-3.5 w-3.5 text-slate-400" />
                                <span>Kembalikan ke Database</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Menu Ekspor Excel -->
                <div class="relative">
                    <div class="inline-flex rounded-lg shadow-2xs">
                        <button
                            type="button"
                            @click="$emit('openExportModal', 'current')"
                            :disabled="isExporting"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-l-lg bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 text-white text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                            title="Buka dialog ekspor Excel"
                        >
                            <Download class="h-4 w-4" />
                            <span>{{ isExporting ? 'Mengekspor...' : 'Ekspor Excel' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="isExportMenuOpen = !isExportMenuOpen"
                            :disabled="isExporting"
                            class="px-2 py-1.5 sm:py-2 rounded-r-lg bg-emerald-800 hover:bg-emerald-900 text-white text-xs font-bold transition-colors border-l border-emerald-600 cursor-pointer disabled:opacity-50"
                            title="Opsi ekspor cepat"
                        >
                            <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': isExportMenuOpen }" />
                        </button>
                    </div>

                    <div v-if="isExportMenuOpen" class="fixed inset-0 z-40" @click="isExportMenuOpen = false"></div>

                    <div
                        v-if="isExportMenuOpen"
                        class="absolute right-0 top-full mt-1.5 z-50 w-64 rounded-xl bg-white border border-slate-200 shadow-xl py-1 text-xs divide-y divide-slate-100 select-none"
                    >
                        <div class="p-1">
                            <button
                                type="button"
                                @click="isExportMenuOpen = false; $emit('downloadHariIniDirect')"
                                class="w-full text-left p-2.5 rounded-lg hover:bg-emerald-50 text-slate-800 hover:text-emerald-900 font-bold flex items-start gap-2.5 cursor-pointer transition-colors"
                            >
                                <div class="p-1.5 rounded-md bg-emerald-100 text-emerald-700 mt-0.5 shrink-0">
                                    <CalendarCheck class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-bold leading-tight text-emerald-800">Download Hari Ini Saja</p>
                                    <p class="text-[10px] text-slate-500 font-normal mt-0.5">Tanggal {{ formatTanggalIndo(todayStr) }} (1 Hari)</p>
                                </div>
                            </button>
                        </div>
                        <div class="p-1">
                            <button
                                type="button"
                                @click="isExportMenuOpen = false; $emit('openExportModal', 'bulanan')"
                                class="w-full text-left px-2.5 py-1.5 rounded-md hover:bg-slate-100 text-slate-700 flex items-center justify-between cursor-pointer transition-colors"
                            >
                                <span class="flex items-center gap-2">
                                    <Clock class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Rekap Bulanan</span>
                                </span>
                                <span class="text-[10px] text-slate-400">28 Hari</span>
                            </button>
                            <button
                                type="button"
                                @click="isExportMenuOpen = false; $emit('openExportModal', 'periodik')"
                                class="w-full text-left px-2.5 py-1.5 rounded-md hover:bg-slate-100 text-slate-700 flex items-center justify-between cursor-pointer transition-colors"
                            >
                                <span class="flex items-center gap-2">
                                    <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Rekap Periodik</span>
                                </span>
                                <span class="text-[10px] text-slate-400">14 Hari</span>
                            </button>
                            <button
                                type="button"
                                @click="isExportMenuOpen = false; $emit('openExportModal', 'current')"
                                class="w-full text-left px-2.5 py-1.5 rounded-md hover:bg-slate-100 text-slate-700 flex items-center justify-between cursor-pointer transition-colors"
                            >
                                <span class="flex items-center gap-2">
                                    <Filter class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Sesuai Tabel / Kustom...</span>
                                </span>
                                <span class="text-[10px] text-slate-400">{{ dateColumns.length }} Hari</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Control Bar: Kalender Menyatu & Filter Interaktif ───────── -->
        <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3">
            <!-- Baris 1: Mode Skala, Dropdown Periode & DateRangePicker Capsule -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2.5">
                <!-- Left: Switcher Skala (Bulanan 28H vs Periodik 14H) & Dropdown Periode -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Switcher Skala -->
                    <div class="inline-flex p-1 rounded-lg bg-slate-100 border border-slate-200/80">
                        <button
                            type="button"
                            @click="$emit('changeModeSkala', 'bulanan')"
                            :class="[
                                'px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-md text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                dateColumns.length === 28
                                    ? 'bg-white text-emerald-700 shadow-xs ring-1 ring-emerald-500/20'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <Clock class="h-3.5 w-3.5" />
                            <span class="hidden xs:inline">Bulanan (28 Hari)</span>
                            <span class="xs:hidden">28 Hari</span>
                        </button>
                        <button
                            type="button"
                            @click="$emit('changeModeSkala', 'periodik')"
                            :class="[
                                'px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-md text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                dateColumns.length === 14
                                    ? 'bg-white text-teal-700 shadow-xs ring-1 ring-teal-500/20'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                        >
                            <Calendar class="h-3.5 w-3.5" />
                            <span class="hidden xs:inline">Periodik (14 Hari)</span>
                            <span class="xs:hidden">14 Hari</span>
                        </button>
                    </div>

                    <!-- Dropdown Periode SPPG (Lengkap Tanggal seperti Rekap Kehadiran) -->
                    <div class="relative flex-1 sm:flex-none">
                        <select
                            :value="selectedPeriodeId"
                            @change="$emit('selectPeriode', $event.target.value)"
                            class="w-full sm:w-auto pl-2.5 pr-8 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden bg-white cursor-pointer"
                        >
                            <option value="all">Pilih Periode SPPG...</option>
                            <option v-for="p in periodes" :key="p.id" :value="String(p.id)">
                                {{ p.label || ('Periode ' + p.nomor_periode) }}
                            </option>
                        </select>
                    </div>

                    <!-- DateRangePicker Trigger Button Capsule -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isDatePickerOpen = !isDatePickerOpen"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50/70 border border-emerald-300 hover:border-emerald-500 text-xs font-bold text-slate-800 transition-all cursor-pointer group shadow-2xs"
                            title="Klik untuk membuka kalender rentang tanggal"
                        >
                            <Calendar class="h-3.5 w-3.5 text-emerald-600" />
                            <span class="text-emerald-800 font-extrabold">{{ formatTanggalIndo(tanggalMulai) }}</span>
                            <span class="text-emerald-500 font-black">➜</span>
                            <span class="text-emerald-800 font-extrabold">{{ formatTanggalIndo(tanggalSelesai) }}</span>
                            <ChevronDown class="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 transition-transform" :class="{ 'rotate-180': isDatePickerOpen }" />
                        </button>

                        <div
                            v-if="isDatePickerOpen"
                            class="fixed inset-0 z-40 bg-black/20 sm:bg-transparent"
                            @click="isDatePickerOpen = false"
                        ></div>

                        <div
                            v-if="isDatePickerOpen"
                            class="fixed inset-x-2 top-20 sm:absolute sm:inset-x-auto sm:left-0 sm:top-full sm:mt-2 z-50 flex justify-center sm:block"
                        >
                            <DateRangePicker
                                v-model="datePickerRange"
                                :isOpen="isDatePickerOpen"
                                @apply="onApplyDateRange"
                                @close="isDatePickerOpen = false"
                            />
                        </div>
                    </div>

                    <!-- Badge Durasi Hari Kerja -->
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-700 text-white font-black text-xs shadow-2xs whitespace-nowrap">
                        {{ dateColumns.length }} Hari
                    </span>
                </div>

                <!-- Right: Label Siklus -->
                <div class="text-[11px] font-semibold text-emerald-900/90 hidden lg:flex items-center gap-1.5">
                    <Sparkles class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                    <span>{{ labelSiklus }}</span>
                </div>
            </div>

            <!-- Baris 2: Search & Filter Kategori / Status -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2 border-t border-slate-100">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                        type="text"
                        placeholder="Cari sekolah, posyandu, NPSN, PIC..."
                        class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden"
                    />
                </div>

                <div>
                    <select
                        :value="selectedKategoriFilter"
                        @change="$emit('update:selectedKategoriFilter', $event.target.value)"
                        class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden bg-white cursor-pointer"
                    >
                        <option value="all">Semua Kategori</option>
                        <option value="Sekolah">Satuan Pendidikan / Sekolah</option>
                        <option value="Posyandu">Posyandu Balita & Bumil</option>
                        <option value="TK">TK / RA / PAUD</option>
                        <option value="SD">SD / MI</option>
                        <option value="SMP">SMP / MTs</option>
                        <option value="SMA">SMA / SMK / MA</option>
                    </select>
                </div>

                <div>
                    <select
                        :value="selectedStatusFilter"
                        @change="$emit('update:selectedStatusFilter', $event.target.value)"
                        class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden bg-white cursor-pointer"
                    >
                        <option value="all">Semua Status Layanan</option>
                        <option value="Terkirim">Status Terkirim</option>
                        <option value="Terjadwal">Status Terjadwal</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
