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
    ChevronRight,
    BookOpen,
    HelpCircle,
    Users,
    Settings2,
    Send,
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
    selectedAturan: { type: String, default: "aturan_2" },
    selectedKomponenSekolah: { type: String, default: "siswa" },
    unitSppg: { type: Object, default: null },
    isExporting: { type: Boolean, default: false },
    isExportingRelawan: { type: Boolean, default: false },
    isSubmittingTrx: { type: Boolean, default: false },
    statusPengajuan: { type: String, default: null },
    transaksiPengajuan: { type: Object, default: null },
});

const isDownloadDropdownOpen = ref(false);

const emit = defineEmits([
    "update:searchQuery",
    "update:selectedKategoriFilter",
    "changeAturan",
    "changeKomponenSekolah",
    "update:selectedKomponenSekolah",
    "openAturanModal",
    "dateFilterChange",
    "changeModeSkala",
    "selectPeriode",
    "applyDateRange",
    "exportExcel",
    "exportRelawanExcel",
    "printAllReceipts",
    "ajukanPembayaran",
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

function getKomponenLabel(val) {
    switch (val) {
        case "siswa": return "Siswa Saja";
        case "guru": return "Guru Saja";
        case "tendik": return "Tendik Saja";
        case "guru_tendik": return "Guru & Tendik";
        default: return "Semua (Siswa, Guru & Tendik)";
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

            <!-- Tombol Aksi Kanan: Ajukan Pembayaran & Dropdown Download -->
            <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap w-full sm:w-auto">
                <!-- 1. Status Disetujui -->
                <template v-if="statusPengajuan === 'Disetujui'">
                    <button
                        type="button"
                        disabled
                        class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold shadow-2xs cursor-default w-full sm:w-auto min-w-0"
                        title="Pembayaran insentif untuk periode ini telah Disetujui pada Menu Transaksi"
                    >
                        <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600" />
                        <span class="truncate">Disetujui</span>
                    </button>
                </template>

                <!-- 2. Status Ditunda -->
                <template v-else-if="statusPengajuan === 'Ditunda'">
                    <button
                        type="button"
                        disabled
                        class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-800 text-xs font-bold shadow-2xs cursor-default w-full sm:w-auto min-w-0"
                        title="Pembayaran insentif untuk periode ini telah diajukan ke Menu Transaksi (Status: Ditunda) dan sedang menunggu verifikasi"
                    >
                        <Clock class="h-4 w-4 shrink-0 text-amber-600 animate-pulse" />
                        <span class="truncate">Telah Diajukan</span>
                    </button>
                </template>

                <!-- 3. Status Ditolak / Dibatalkan / Belum Diajukan -->
                <template v-else>
                    <button
                        type="button"
                        @click="$emit('ajukanPembayaran')"
                        :disabled="isSubmittingTrx"
                        class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white text-xs font-bold transition-all shadow-xs disabled:opacity-50 cursor-pointer w-full sm:w-auto min-w-0"
                        :title="statusPengajuan === 'Ditolak' ? 'Pengajuan sebelumnya Ditolak di Menu Transaksi. Klik untuk mengajukan ulang.' : 'Ajukan pembayaran insentif PIC KPM ke Menu Transaksi Keuangan dengan status Ditunda'"
                    >
                        <RefreshCw v-if="isSubmittingTrx" class="h-4 w-4 shrink-0 animate-spin" />
                        <Send v-else class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ isSubmittingTrx ? 'Mengajukan...' : 'Ajukan Pembayaran' }}</span>
                    </button>
                </template>

                <!-- Dropdown Download (Kuitansi PDF & Payroll ERP .XLSX) -->
                <div class="relative inline-flex w-full sm:w-auto">
                    <button
                        type="button"
                        @click="isDownloadDropdownOpen = !isDownloadDropdownOpen"
                        :disabled="isExportingRelawan"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold transition-all shadow-xs disabled:opacity-50 cursor-pointer w-full sm:w-auto min-w-0"
                        title="Pilihan download berkas Kuitansi resmi (.pdf) atau Payroll ERP (.xlsx)"
                    >
                        <RefreshCw
                            v-if="isExportingRelawan"
                            class="h-4 w-4 shrink-0 animate-spin"
                        />
                        <Download v-else class="h-4 w-4 shrink-0" />
                        <span class="truncate">
                            {{ isExportingRelawan ? 'Menyiapkan .xlsx...' : 'Download' }}
                        </span>
                        <ChevronDown
                            class="h-3.5 w-3.5 shrink-0 transition-transform"
                            :class="{ 'rotate-180': isDownloadDropdownOpen }"
                        />
                    </button>

                    <!-- Backdrop Click Outside -->
                    <div
                        v-if="isDownloadDropdownOpen"
                        class="fixed inset-0 z-40"
                        @click="isDownloadDropdownOpen = false"
                    ></div>

                    <!-- Dropdown Menu -->
                    <div
                        v-if="isDownloadDropdownOpen"
                        class="absolute right-0 top-full mt-1.5 z-50 w-80 max-w-[calc(100vw-2rem)] rounded-2xl bg-white border border-slate-200 shadow-xl py-1 text-xs animate-in fade-in zoom-in-95 duration-100 divide-y divide-slate-100 select-none"
                    >
                        <div class="p-1.5 space-y-1">
                            <!-- Opsi 1: Download Kuitansi PDF -->
                            <button
                                type="button"
                                @click="
                                    isDownloadDropdownOpen = false;
                                    $emit('printAllReceipts');
                                "
                                class="w-full text-left p-2.5 rounded-xl hover:bg-slate-100 text-slate-800 font-bold flex items-start gap-2.5 cursor-pointer transition-colors"
                            >
                                <div
                                    class="p-2 rounded-lg bg-slate-800 text-white mt-0.5 shrink-0 shadow-2xs"
                                >
                                    <Receipt class="h-4 w-4 text-emerald-400" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-900"
                                            >Download Kuitansi</span
                                        >
                                        <span
                                            class="px-1.5 py-0.2 rounded text-[9px] font-black bg-slate-200 text-slate-800"
                                            >.PDF</span
                                        >
                                    </div>
                                    <p
                                        class="text-[11px] text-slate-500 font-normal mt-0.5 leading-snug"
                                    >
                                        Kuitansi resmi tanda terima insentif (1 lembar per halaman atau per titik sasaran).
                                    </p>
                                </div>
                            </button>

                            <!-- Opsi 2: Download Payroll ERP .XLSX -->
                            <button
                                type="button"
                                @click="
                                    isDownloadDropdownOpen = false;
                                    $emit('exportRelawanExcel');
                                "
                                :disabled="isExportingRelawan"
                                class="w-full text-left p-2.5 rounded-xl hover:bg-emerald-50/80 text-slate-800 font-bold flex items-start gap-2.5 cursor-pointer transition-colors"
                            >
                                <div
                                    class="p-2 rounded-lg bg-emerald-600 text-white mt-0.5 shrink-0 shadow-2xs"
                                >
                                    <FileSpreadsheet class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="text-xs font-bold text-emerald-950"
                                            >Download Payroll ERP</span
                                        >
                                        <span
                                            class="px-1.5 py-0.2 rounded text-[9px] font-black bg-emerald-200 text-emerald-800"
                                            >.XLSX</span
                                        >
                                    </div>
                                    <p
                                        class="text-[11px] text-slate-500 font-normal mt-0.5 leading-snug"
                                    >
                                        Format nominatif resmi PIC Satuan Pendidikan & Posyandu siap unggah ke portal ERP BGN.
                                    </p>
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
            <!-- Search & Filter Kategori Satuan Pendidikan / Posyandu & Pilihan Aturan & Hitungan PM Sekolah -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <!-- Search Input -->
                <div class="sm:col-span-3 relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                        type="text"
                        placeholder="Cari sekolah, posyandu, nama PIC..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white outline-hidden"
                    />
                </div>

                <!-- Kategori Filter -->
                <div class="sm:col-span-2">
                    <select
                        :value="selectedKategoriFilter"
                        @change="$emit('update:selectedKategoriFilter', $event.target.value)"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white cursor-pointer outline-hidden"
                    >
                        <option value="all">Semua Titik (Sekolah & Posyandu)</option>
                        <option value="Sekolah">Sekolah Saja</option>
                        <option value="Posyandu">Posyandu Saja</option>
                        <option value="SD">SD / MI</option>
                        <option value="SMP">SMP / MTs</option>
                        <option value="SMA">SMA / SMK / MA</option>
                        <option value="TK">TK / RA / PAUD</option>
                    </select>
                </div>

                <!-- Hitungan PM Sekolah (Semua, Siswa, Guru, Tendik, Guru & Tendik) -->
                <div class="sm:col-span-3">
                    <div class="relative">
                        <select
                            :value="selectedKomponenSekolah"
                            @change="$emit('changeKomponenSekolah', $event.target.value); $emit('update:selectedKomponenSekolah', $event.target.value)"
                            class="w-full pl-3 pr-8 py-2 rounded-xl border text-xs font-bold bg-indigo-50/70 text-indigo-950 border-indigo-300 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer outline-hidden shadow-2xs"
                            title="Pilih siapa saja yang dihitung dalam jumlah PM KPM Sekolah saat kalkulasi insentif"
                        >
                            <option value="semua">PM Sekolah: Semua (Siswa, Guru, Tendik)</option>
                            <option value="siswa">PM Sekolah: Siswa Saja</option>
                            <option value="guru">PM Sekolah: Guru Saja</option>
                            <option value="tendik">PM Sekolah: Tendik Saja</option>
                            <option value="guru_tendik">PM Sekolah: Guru & Tendik</option>
                        </select>
                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-indigo-700">
                            <ChevronDown class="w-3.5 h-3.5" />
                        </span>
                    </div>
                </div>

                <!-- Pilihan Aturan Perhitungan Insentif -->
                <div class="sm:col-span-2">
                    <div class="relative">
                        <select
                            :value="selectedAturan"
                            @change="$emit('changeAturan', $event.target.value)"
                            class="w-full pl-3 pr-8 py-2 rounded-xl border text-xs font-bold bg-emerald-50/40 text-emerald-950 border-emerald-300 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer outline-hidden shadow-2xs"
                            title="Pilih rumus regulasi insentif yang digunakan"
                        >
                            <option value="aturan_2">Aturan 2 (SK 63551/2026)</option>
                            <option value="aturan_1">Aturan 1 (SK 401.1/2025)</option>
                        </select>
                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-emerald-700">
                            <ChevronDown class="w-3.5 h-3.5" />
                        </span>
                    </div>
                </div>

                <!-- Tombol Petunjuk Rumus -->
                <div class="sm:col-span-2 flex items-center justify-end">
                    <button
                        type="button"
                        @click="$emit('openAturanModal')"
                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200/80 active:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-200 transition-colors shadow-2xs cursor-pointer"
                        title="Buka petunjuk perbandingan rumus dan tabel skema insentif"
                    >
                        <BookOpen class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                        <span>Petunjuk Rumus</span>
                    </button>
                </div>
            </div>

            <!-- Panduan Formula Regulasi Resmi BGN & Status Aturan Terpilih -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 flex flex-col md:flex-row md:items-center md:justify-between gap-2.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-extrabold text-slate-800 flex items-center gap-1.5">
                        <span
                            class="w-2.5 h-2.5 rounded-full"
                            :class="selectedAturan === 'aturan_2' ? 'bg-emerald-600 ring-2 ring-emerald-200' : 'bg-blue-600 ring-2 ring-blue-200'"
                        ></span>
                        <span>Rumus Aktif:</span>
                    </span>

                    <!-- Penjelasan Aturan 2 -->
                    <template v-if="selectedAturan === 'aturan_2'">
                        <span class="px-2 py-0.5 rounded-lg bg-emerald-100/80 border border-emerald-300 font-bold text-emerald-900 flex items-center gap-1.5">
                            <Sparkles class="w-3 h-3 text-emerald-700 shrink-0" />
                            <span>Aturan 2: SK No. 63551 Tahun 2026</span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-emerald-600 text-white">AKTIF</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-lg bg-indigo-100/90 border border-indigo-300 font-bold text-indigo-950 flex items-center gap-1.5">
                            <Users class="w-3 h-3 text-indigo-700 shrink-0" />
                            <span>PM Sekolah: <b>{{ getKomponenLabel(selectedKomponenSekolah) }}</b></span>
                        </span>
                        <span class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 font-medium text-slate-700">
                            <b>Sekolah:</b> Tier Siswa (Tabel 2/3: Rp 20rb - 200rb)
                        </span>
                        <span class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 font-medium text-pink-700">
                            <b>3B Posyandu:</b> Tabel SK 63551 (Khusus & Tingkat 1–6: Rp 1.000/PM s/d Rp 700rb/hari)
                        </span>
                    </template>

                    <!-- Penjelasan Aturan 1 -->
                    <template v-else>
                        <span class="px-2 py-0.5 rounded-lg bg-blue-100/80 border border-blue-300 font-bold text-blue-900 flex items-center gap-1.5">
                            <Info class="w-3 h-3 text-blue-700 shrink-0" />
                            <span>Aturan 1: SK No. 401.1 Tahun 2025</span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-blue-600 text-white">AKTIF</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 font-medium text-slate-700">
                            <b>Sekolah:</b> Tier Siswa (Tabel 2/3: Rp 20rb - 200rb)
                        </span>
                        <span class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 font-medium text-pink-700">
                            <b>Posyandu:</b> Flat Rp 1.000 / PM / hari (Perorangan Posyandu)
                        </span>
                    </template>
                </div>

                <button
                    type="button"
                    @click="$emit('openAturanModal')"
                    class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 hover:underline shrink-0 cursor-pointer"
                >
                    <span>Lihat Tabel & Panduan Rumus</span>
                    <ChevronRight class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>
