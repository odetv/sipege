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
    FileSpreadsheet,
    FileText,
} from "lucide-vue-next";
import DateRangePicker from "@/Components/DateRangePicker.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
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
    "dateFilterChange",
    "selectPeriode",
    "changeModeSkala",
    "applyDateRange",
    "simpanDistribusi",
    "setSemuaTerkirim",
    "kosongkanSemua",
    "resetDistribusi",
    "openExportModal",
    "downloadHariIniDirect",
    "downloadTandaTerimaHarianDirect",
    "downloadBuktiDistribusiDirect",
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
    <div class="space-y-4 sm:space-y-5">
        <!-- ─── Header Halaman (Identik Pembayaran Gaji) ─────────────────────── -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-emerald-600 text-white shadow-xs shrink-0">
                        <CalendarCheck class="h-6 w-6" />
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">
                            Rekapitulasi Distribusi Penerima Manfaat
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Monitoring alokasi porsi harian & matriks logistik pengiriman MBG Unit SPPG
                            <span v-if="unitSppg" class="font-semibold text-slate-700"> • {{ unitSppg.nama_sppg || unitSppg.nama }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi Kanan: Simpan Data & Ekspor Excel -->
            <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap w-full lg:w-auto justify-between sm:justify-end">
                <!-- Tombol Simpan Presensi/Distribusi (Identik Rekap Kehadiran) -->
                <button
                    type="button"
                    @click="$emit('simpanDistribusi')"
                    :disabled="isSaving"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer shadow-xs disabled:opacity-50',
                        isDirty
                            ? 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white ring-2 ring-emerald-400/50'
                            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200',
                    ]"
                    :title="isDirty ? 'Ada perubahan yang belum disimpan ke database!' : 'Semua data telah tersimpan'"
                >
                    <RefreshCw v-if="isSaving" class="h-4 w-4 animate-spin text-white" />
                    <Save v-else class="h-4 w-4" :class="isDirty ? 'text-white' : 'text-slate-500'" />
                    <span>{{ isSaving ? 'Menyimpan...' : (isDirty ? 'Simpan' : 'Tersimpan') }}</span>
                    <span v-if="isDirty" class="w-2 h-2 rounded-full bg-amber-400"></span>
                </button>

                <!-- Dropdown Quick Tools / Batch Actions -->
                <div class="relative">
                    <button
                        type="button"
                        @click="isToolsMenuOpen = !isToolsMenuOpen"
                        class="inline-flex items-center gap-1.5 px-3 py-2 sm:py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold transition-all shadow-2xs cursor-pointer"
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

                <!-- Dropdown Menu Ekspor Excel & PDF (Identik Rekap Kehadiran) -->
                <div class="relative inline-flex items-stretch rounded-xl shadow-xs">
                    <button
                        type="button"
                        @click="$emit('openExportModal', 'ttd_harian')"
                        :disabled="isExporting"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-2 sm:py-2.5 rounded-l-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                        title="Download Buku Tanda Terima Distribusi Harian / Rekap Excel"
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        <span class="hidden sm:inline">Download</span>
                        <span class="sm:hidden">Excel</span>
                    </button>
                    <button
                        type="button"
                        @click="isExportMenuOpen = !isExportMenuOpen"
                        :disabled="isExporting"
                        class="inline-flex items-center px-2 rounded-r-xl bg-emerald-700 hover:bg-emerald-800 text-white border-l border-emerald-500/60 text-xs transition-colors cursor-pointer disabled:opacity-50"
                        title="Opsi Pilihan Download"
                    >
                        <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': isExportMenuOpen }" />
                    </button>

                    <!-- Backdrop Click Outside untuk Menu Dropdown -->
                    <div
                        v-if="isExportMenuOpen"
                        class="fixed inset-0 z-40"
                        @click="isExportMenuOpen = false"
                    ></div>

                    <!-- Dropdown Menu Cepat (4 Opsi Identik Rekap Kehadiran) -->
                    <div
                        v-if="isExportMenuOpen"
                        class="absolute right-0 top-full mt-1.5 z-50 w-72 max-w-[calc(100vw-2rem)] rounded-xl bg-white border border-slate-200 shadow-xl py-1 text-xs animate-in fade-in zoom-in-95 duration-100 divide-y divide-slate-100 select-none"
                    >
                        <div class="p-1">
                            <!-- Opsi 1: Buku Tanda Terima Distribusi Harian (Per Sheet DD-MM-YYYY) -->
                            <button
                                type="button"
                                @click="isExportMenuOpen = false; $emit('openExportModal', 'ttd_harian')"
                                class="w-full text-left p-2 rounded-lg bg-teal-50/70 hover:bg-teal-100/80 text-teal-950 font-bold flex items-start gap-2.5 cursor-pointer transition-colors"
                            >
                                <div class="p-1.5 rounded-md bg-teal-600 text-white mt-0.5 shrink-0 shadow-2xs">
                                    <FileSpreadsheet class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold leading-tight text-teal-900">Buku Tanda Terima Harian</p>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-teal-200/80 text-teal-800">BARU</span>
                                    </div>
                                    <p class="text-[10px] text-teal-700 font-normal mt-0.5 leading-snug">Pisah per tgl (Sheet DD-MM-YYYY), rincian porsi, PIC & TTD fisik</p>
                                </div>
                            </button>

                            <!-- Opsi 2: Tanda Terima Hari Ini (1 Sheet DD-MM-YYYY) Langsung -->
                            <button
                                type="button"
                                @click="isExportMenuOpen = false; $emit('downloadTandaTerimaHarianDirect')"
                                class="w-full text-left p-2 rounded-lg hover:bg-slate-100 text-slate-800 font-semibold flex items-start gap-2.5 cursor-pointer transition-colors mt-0.5"
                            >
                                <div class="p-1.5 rounded-md bg-emerald-100 text-emerald-700 mt-0.5 shrink-0">
                                    <CalendarCheck class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-bold leading-tight text-slate-900">TTD Distribusi Hari Ini (1 Sheet)</p>
                                    <p class="text-[10px] text-slate-500 font-normal mt-0.5">Langsung download tgl {{ formatTanggalIndo(todayStr) }}</p>
                                </div>
                            </button>

                            <!-- Opsi 3: Rekap Matriks Distribusi Periode -->
                            <button
                                type="button"
                                @click="isExportMenuOpen = false; $emit('openExportModal', 'rekap_matriks')"
                                class="w-full text-left p-2 rounded-lg hover:bg-slate-100 text-slate-800 font-semibold flex items-start gap-2.5 cursor-pointer transition-colors mt-0.5"
                            >
                                <div class="p-1.5 rounded-md bg-slate-100 text-slate-700 mt-0.5 shrink-0">
                                    <Layers class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-bold leading-tight text-slate-900">Rekap Distribusi (Matriks)</p>
                                    <p class="text-[10px] text-slate-500 font-normal mt-0.5">Matriks horizontal periode + ringkasan alokasi & pengesahan</p>
                                </div>
                            </button>

                            <!-- Opsi 4: Bukti Distribusi PM (.pdf) Berdasarkan Filter Aktif -->
                            <button
                                type="button"
                                @click="isExportMenuOpen = false; $emit('downloadBuktiDistribusiDirect')"
                                class="w-full text-left p-2 rounded-lg hover:bg-emerald-50/70 text-slate-800 font-semibold flex items-start gap-2.5 cursor-pointer transition-colors mt-0.5 border-t border-slate-100"
                            >
                                <div class="p-1.5 rounded-md bg-emerald-100 text-emerald-800 mt-0.5 shrink-0">
                                    <FileText class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold leading-tight text-emerald-950">Download Bukti Distribusi (.pdf)</p>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-emerald-200 text-emerald-900">PDF</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-normal mt-0.5">Bukti tanda terima resmi 1 halaman per sasaran PM</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Control Bar: Reusable PeriodDateFilterBar ───────── -->
        <PeriodDateFilterBar
            :startDate="tanggalMulai"
            :endDate="tanggalSelesai"
            :mode="modeSkala"
            :periodeId="selectedPeriodeId"
            :periodes="periodes"
            @change="(payload) => $emit('dateFilterChange', payload)"
        />

        <!-- ─── Secondary Filter Bar (Search & Filter Kategori / Status) ───────── -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-3.5">
            <!-- Search & Filter Kategori / Status -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                        type="text"
                        placeholder="Cari sekolah, posyandu, NPSN, PIC..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white outline-hidden"
                    />
                </div>

                <div>
                    <select
                        :value="selectedKategoriFilter"
                        @change="$emit('update:selectedKategoriFilter', $event.target.value)"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white cursor-pointer outline-hidden"
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
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white cursor-pointer outline-hidden"
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
