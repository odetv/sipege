<script setup>
import { ref, computed, watch } from "vue";
import {
    X,
    Calendar,
    Layers,
    FileSpreadsheet,
    FileText,
    CheckCircle2,
} from "lucide-vue-next";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";

const props = defineProps({
    show: { type: Boolean, default: false },
    isOpen: { type: Boolean, default: false },
    mode: { type: String, default: "ttd_harian" },
    initialDocType: { type: String, default: "ttd_harian" },
    startDate: { type: String, default: "" },
    tanggalMulai: { type: String, default: "" },
    endDate: { type: String, default: "" },
    tanggalSelesai: { type: String, default: "" },
    totalDays: { type: Number, default: 28 },
    isExporting: { type: Boolean, default: false },
    unitSppg: { type: Object, default: null },
    todayStr: { type: String, default: "" },
});

const emit = defineEmits(["close", "confirm", "confirmExport"]);

const isModalVisible = computed(() => props.show || props.isOpen);
const effectiveStartDate = computed(() => props.startDate || props.tanggalMulai || props.todayStr);
const effectiveEndDate = computed(() => props.endDate || props.tanggalSelesai || effectiveStartDate.value);

const exportDocType = ref("ttd_harian");

watch(
    () => [props.mode, props.initialDocType, isModalVisible.value],
    () => {
        const candidate = props.initialDocType || props.mode;
        if (["ttd_harian", "rekap_matriks", "bukti_distribusi_pdf"].includes(candidate)) {
            exportDocType.value = candidate;
        } else {
            exportDocType.value = "ttd_harian";
        }
    },
    { immediate: true }
);

function onConfirmClick() {
    emit("confirm", exportDocType.value, effectiveStartDate.value, effectiveEndDate.value);
    emit("confirmExport", exportDocType.value, effectiveStartDate.value, effectiveEndDate.value);
}
</script>

<template>
    <div
        v-if="isModalVisible"
        class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4"
        style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
    >
        <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <component :is="exportDocType === 'bukti_distribusi_pdf' ? FileText : FileSpreadsheet" class="h-5 w-5 text-emerald-200" />
                    <div>
                        <h3 class="text-sm font-bold">Download Laporan Distribusi MBG</h3>
                        <p class="text-[11px] text-emerald-100">Buku Tanda Terima Fisik Harian & Rekapitulasi Alokasi Porsi</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-1 rounded-lg text-emerald-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                <!-- Pilihan Format Dokumen -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wider">
                        Pilih Format Dokumen Distribusi:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Pilihan 1: Buku Tanda Terima Harian (Per Sheet DD-MM-YYYY) -->
                        <label
                            :class="[
                                'flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                exportDocType === 'ttd_harian'
                                    ? 'border-teal-500 bg-teal-50/80 ring-2 ring-teal-500/20'
                                    : 'border-slate-200 hover:bg-slate-50',
                            ]"
                        >
                            <input type="radio" value="ttd_harian" v-model="exportDocType" class="sr-only" />
                            <div class="p-1.5 rounded-lg mt-0.5 shrink-0" :class="exportDocType === 'ttd_harian' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600'">
                                <FileSpreadsheet class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900">Buku Tanda Terima Harian</span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-teal-200/80 text-teal-800">BARU • MULTI-SHEET</span>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                    1 Sheet per tanggal (DD-MM-YYYY), target sekolah PM, rincian porsi kecil/besar & kolom paraf fisik zig-zag.
                                </p>
                            </div>
                        </label>

                        <!-- Pilihan 2: Rekap Matriks Distribusi Periode -->
                        <label
                            :class="[
                                'flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                exportDocType === 'rekap_matriks'
                                    ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20'
                                    : 'border-slate-200 hover:bg-slate-50',
                            ]"
                        >
                            <input type="radio" value="rekap_matriks" v-model="exportDocType" class="sr-only" />
                            <div class="p-1.5 rounded-lg mt-0.5 shrink-0" :class="exportDocType === 'rekap_matriks' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'">
                                <Layers class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-xs font-bold text-slate-900">Rekap Matriks Periode</span>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                    Matriks horizontal tanggal 1 s.d N & ringkasan alokasi per kategori dilengkapi lembar pengesahan (.xlsx).
                                </p>
                            </div>
                        </label>

                        <!-- Pilihan 3: Bukti Distribusi PM PDF -->
                        <label
                            :class="[
                                'flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all col-span-1 sm:col-span-2',
                                exportDocType === 'bukti_distribusi_pdf'
                                    ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20'
                                    : 'border-slate-200 hover:bg-slate-50',
                            ]"
                        >
                            <input type="radio" value="bukti_distribusi_pdf" v-model="exportDocType" class="sr-only" />
                            <div class="p-1.5 rounded-lg mt-0.5 shrink-0" :class="exportDocType === 'bukti_distribusi_pdf' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600'">
                                <FileText class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900">Bukti Tanda Terima Distribusi PM (.pdf)</span>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-emerald-200 text-emerald-900">PDF RESMI</span>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                    Cetak bukti serah terima resmi (1 tanda terima per lembar A4 portrait / 1 halaman per sasaran PM) memuat kop resmi BGN & Yayasan, rincian porsi kecil & besar, total terbilang, dan tanda tangan 3 pihak: Penerima PM, Petugas Distribusi, dan Kepala SPPG.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Info Rentang Tanggal Filter Aktif -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-teal-100 text-teal-700 shrink-0">
                            <Calendar class="h-4 w-4" />
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Rentang Tanggal Dokumen (Sesuai Filter)</p>
                            <p class="font-bold text-slate-800 text-xs mt-0.5">
                                {{ formatTanggalIndo(effectiveStartDate) }}
                                <span v-if="effectiveStartDate !== effectiveEndDate"> s.d. {{ formatTanggalIndo(effectiveEndDate) }}</span>
                            </p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-teal-100/70 border border-teal-200 text-teal-800 font-bold text-[11px] shrink-0">
                        {{ totalDays }} Hari
                    </span>
                </div>

                <!-- Info Preview Dokumen Berdasarkan exportDocType -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                    <div class="flex items-center justify-between text-slate-700 font-semibold border-b border-slate-200/80 pb-2">
                        <span>Unit SPPG:</span>
                        <span class="text-slate-900 font-bold">{{ unitSppg?.nama ?? unitSppg?.nama_sppg ?? 'SPPG Buleleng Sukasada Tegallinggah' }}</span>
                    </div>

                    <!-- Preview Khusus Bukti Distribusi PM PDF -->
                    <div v-if="exportDocType === 'bukti_distribusi_pdf'" class="space-y-1.5 text-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">Format Dokumen:</span>
                            <span class="text-emerald-800 font-bold">PDF Resmi (1 Lembar A4 Portrait per Sasaran PM)</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-emerald-50/70 border border-emerald-200 text-[11px] text-emerald-950 space-y-1">
                            <p class="font-bold flex items-center gap-1.5">
                                <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                <span>Rincian Setiap Lembar Tanda Terima:</span>
                            </p>
                            <ul class="list-disc list-inside space-y-0.5 pl-1 text-slate-700">
                                <li>Kop resmi SPPG (Logo BGN & Logo Yayasan Pesantren Miftahul Ulum seimbang)</li>
                                <li>Identitas lembaga (Nama Kelompok PM, NPSN/Kode, PIC, Desa/Kelurahan)</li>
                                <li>Alokasi porsi harian terdistribusi (Porsi Kecil PK & Porsi Besar PB)</li>
                                <li>Total porsi terkirim callout box & terbilang porsi Bahasa Indonesia</li>
                                <li>Tanda tangan 3 pihak: Penerima PM (Sekolah), Petugas Distribusi SPPG, dan Mengetahui Kepala SPPG</li>
                                <li>Footer resmi lengkap dengan tanggal dan timestamp cetak WITA</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Preview Khusus Buku TTD Harian -->
                    <div v-else-if="exportDocType === 'ttd_harian'" class="space-y-1.5 text-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">Struktur Lembar Excel:</span>
                            <span class="text-teal-800 font-bold">1 Sheet per Tanggal (DD-MM-YYYY)</span>
                        </div>
                        <div class="p-2.5 rounded-lg bg-teal-50/70 border border-teal-200 text-[11px] text-teal-900 space-y-1">
                            <p class="font-bold flex items-center gap-1.5">
                                <CheckCircle2 class="h-3.5 w-3.5 text-teal-600 shrink-0" />
                                <span>Format Setiap Sheet (Nama Sheet: DD-MM-YYYY):</span>
                            </p>
                            <ul class="list-disc list-inside space-y-0.5 pl-1 text-slate-700">
                                <li>Tabel daftar lembaga PM + kolom tanda tangan fisik zig-zag (tinggi 28pt)</li>
                                <li>Rincian porsi kecil (PK), porsi besar (PB), dan total porsi per sekolah</li>
                                <li>Informasi PIC lembaga dan rekap total porsi harian SPPG</li>
                                <li>Pengesahan tanda tangan resmi: Petugas Distribusi & Kepala SPPG</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Preview Khusus Rekap Matriks Periode -->
                    <div v-else class="space-y-1.5 text-slate-700">
                        <div class="flex items-center justify-between">
                            <span>Sheet 1:</span>
                            <span class="text-emerald-700 font-bold">Matriks Distribusi Harian & Pengesahan</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Sheet 2:</span>
                            <span class="text-slate-800 font-bold">Ringkasan Alokasi per Kategori PM</span>
                        </div>
                        <div class="pt-1 text-[11px] text-emerald-800 font-medium flex items-center gap-1.5">
                            <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                            <span>Dilengkapi lembar pengesahan resmi Kepala SPPG & Petugas Distribusi.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="onConfirmClick"
                    :disabled="isExporting"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                >
                    <component :is="exportDocType === 'bukti_distribusi_pdf' ? FileText : FileSpreadsheet" class="h-4 w-4" />
                    <span>
                        {{
                            isExporting
                                ? 'Mengekspor File...'
                                : (exportDocType === 'bukti_distribusi_pdf'
                                    ? 'Download Bukti Distribusi (.pdf)'
                                    : (exportDocType === 'ttd_harian'
                                        ? 'Download Buku TTD Harian (.xlsx)'
                                        : 'Download Rekap Distribusi (.xlsx)'))
                        }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
