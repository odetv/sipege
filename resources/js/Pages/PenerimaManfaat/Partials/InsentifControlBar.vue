<script setup>
import { ref, watch } from "vue";
import {
    CreditCard,
    DollarSign,
    Coins,
    Calendar,
    Clock,
    Download,
    Printer,
    FileSpreadsheet,
    Search,
    RefreshCw,
    Building2,
    Info,
    Sparkles,
    CheckCircle2,
    HeartPulse,
    ChevronDown,
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
    rentangHariCount: { type: Number, default: 28 },
    labelSiklus: { type: String, default: "" },
    searchQuery: { type: String, default: "" },
    selectedKategoriFilter: { type: String, default: "all" },
    unitSppg: { type: Object, default: null },
    isExporting: { type: Boolean, default: false },
});

const emit = defineEmits([
    "update:searchQuery",
    "update:selectedKategoriFilter",
    "dateFilterChange",
    "changeModeSkala",
    "selectPeriode",
    "applyDateRange",
    "exportExcel",
    "printAllReceipts",
]);

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
        <!-- ─── Top Header (Style Persis Pembayaran Gaji) ─────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <Coins class="h-6 w-6 text-emerald-600" />
                    <span>Pembayaran Insentif Penerima Manfaat</span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Alokasi & penyaluran insentif tunai langsung untuk Penanggung Jawab Satuan Pendidikan & Kader Posyandu
                    <span v-if="unitSppg" class="font-semibold text-slate-700"> • {{ unitSppg.nama_sppg || unitSppg.nama }}</span>
                </p>
            </div>

            <!-- Tombol Aksi Cepat: Ekspor Excel & Cetak Kuitansi -->
            <div class="flex items-center gap-2 self-stretch sm:self-auto flex-wrap">
                <button
                    type="button"
                    @click="$emit('printAllReceipts')"
                    class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-colors cursor-pointer shadow-2xs flex-1 sm:flex-none"
                    title="Cetak formulir tanda terima tunai"
                >
                    <Printer class="h-4 w-4" />
                    <span>Cetak Tanda Terima</span>
                </button>

                <button
                    type="button"
                    @click="$emit('exportExcel')"
                    :disabled="isExporting"
                    class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold transition-colors cursor-pointer shadow-2xs disabled:opacity-50 flex-1 sm:flex-none"
                    title="Unduh Excel lengkap dengan kolom tanda tangan"
                >
                    <FileSpreadsheet class="h-4 w-4" />
                    <span>{{ isExporting ? 'Mengekspor...' : 'Ekspor Excel (Daftar TTD)' }}</span>
                </button>
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
        >
            <template #right>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-100 text-emerald-800 font-bold text-xs border border-emerald-200">
                    <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
                    <span>Metode: 100% Tunai Langsung</span>
                </span>
            </template>
        </PeriodDateFilterBar>

        <!-- ─── Secondary Filter Bar ───────── -->
        <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3">

            <!-- Baris 2: Search & Filter Kategori Satuan Pendidikan / Posyandu -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 pt-2 border-t border-slate-100">
                <div class="sm:col-span-5 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                        type="text"
                        placeholder="Cari sekolah, posyandu, nama PIC..."
                        class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden"
                    />
                </div>

                <div class="sm:col-span-4">
                    <select
                        :value="selectedKategoriFilter"
                        @change="$emit('update:selectedKategoriFilter', $event.target.value)"
                        class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden bg-white cursor-pointer"
                    >
                        <option value="all">Semua Titik (Sekolah & Posyandu)</option>
                        <option value="Sekolah">Satuan Pendidikan / Sekolah Saja</option>
                        <option value="Posyandu">Kader Posyandu Saja</option>
                        <option value="SD">SD / MI</option>
                        <option value="SMP">SMP / MTs</option>
                        <option value="SMA">SMA / SMK / MA</option>
                        <option value="TK">TK / RA / PAUD</option>
                    </select>
                </div>

                <div class="sm:col-span-3 flex items-center justify-end">
                    <div class="text-[11px] font-semibold text-emerald-900/90 flex items-center gap-1.5">
                        <Sparkles class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>{{ labelSiklus }}</span>
                    </div>
                </div>
            </div>

            <!-- Baris 3: Panduan Formula Regulasi Resmi BGN -->
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/70 text-[11px] text-slate-600 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-extrabold text-slate-800 flex items-center gap-1">
                        <Info class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Regulasi Insentif:</span>
                    </span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200 font-semibold text-slate-700">
                        <b>Sekolah (Tabel 3):</b> &lt;100: 20rb, 100-500: 30rb, 501-750: 50rb, 751-1rb: 60rb, 1rb-2rb: 100rb, &gt;2rb: 200rb/hari
                    </span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200 font-semibold text-pink-700">
                        <b>Posyandu:</b> Rp 1.000 / PM (Ibu Hamil, Menyusui, Balita) / hari
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
