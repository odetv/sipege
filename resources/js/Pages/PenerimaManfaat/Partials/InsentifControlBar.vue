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
    Receipt,
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
    <div class="space-y-4 sm:space-y-5">
        <!-- ─── Top Header (Identik Pembayaran Gaji) ─────────────────────── -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-xl bg-emerald-600 text-white shadow-xs shrink-0">
                        <Coins class="h-6 w-6" />
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">
                            Pembayaran Insentif Penerima Manfaat
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Alokasi & penyaluran insentif tunai langsung untuk Penanggung Jawab Satuan Pendidikan & Kader Posyandu
                            <span v-if="unitSppg" class="font-semibold text-slate-700"> • {{ unitSppg.nama_sppg || unitSppg.nama }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi Kanan: Download Kuitansi PDF -->
            <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap w-full lg:w-auto">
                <button
                    type="button"
                    @click="$emit('printAllReceipts')"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-all shadow-xs cursor-pointer flex-1 sm:flex-none min-w-0"
                    title="Download file PDF kuitansi resmi insentif MBG (1 kuitansi per halaman A4 atau per orangan)"
                >
                    <Receipt class="h-4 w-4 text-emerald-400 shrink-0" />
                    <span>Download Kuitansi</span>
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
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-3.5">
            <!-- Search & Filter Kategori Satuan Pendidikan / Posyandu -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-5 relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                        type="text"
                        placeholder="Cari sekolah, posyandu, nama PIC..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white outline-hidden"
                    />
                </div>

                <div class="sm:col-span-4">
                    <select
                        :value="selectedKategoriFilter"
                        @change="$emit('update:selectedKategoriFilter', $event.target.value)"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white cursor-pointer outline-hidden"
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

            <!-- Panduan Formula Regulasi Resmi BGN -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-extrabold text-slate-800 flex items-center gap-1">
                        <Info class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                        <span>Regulasi Insentif:</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 font-semibold text-slate-700">
                        <b>Sekolah (Tabel 3):</b> &lt;100: 20rb, 100-500: 30rb, 501-750: 50rb, 751-1rb: 60rb, 1rb-2rb: 100rb, &gt;2rb: 200rb/hari
                    </span>
                    <span class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 font-semibold text-pink-700">
                        <b>Posyandu:</b> Rp 1.000 / PM (Ibu Hamil, Menyusui, Balita) / hari
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
