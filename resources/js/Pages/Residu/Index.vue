<script setup>
import { ref, computed, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
import ResiduAiAssistantModal from "@/Pages/Residu/Partials/ResiduAiAssistantModal.vue";
import {
    downloadRekapResiduExcel,
    formatTglDdMmYyyy,
} from "@/Services/exportResiduHelper";
import {
    Sparkles,
    Trash2,
    Plus,
    Download,
    Printer,
    FileSpreadsheet,
    FileText,
    Search,
    RotateCcw,
    Edit2,
    ChevronDown,
    AlertTriangle,
    ShieldCheck,
    Scale,
    Layers,
    Recycle,
    CheckCircle2,
    Database,
    Calendar,
    X,
} from "lucide-vue-next";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: () => ({}),
    },
    kopConfig: {
        type: Object,
        default: () => ({}),
    },
    periodeList: {
        type: Array,
        default: () => [],
    },
    activePeriode: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    pejabatTtd: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    allTimeSummary: {
        type: Object,
        default: () => ({}),
    },
    residuList: {
        type: Array,
        default: () => [],
    },
});

// ─── STATE FILTER INTERAKTIF ───
const activeTanggalMulai = ref(props.filters.tanggal_mulai || props.filters.start_date || "");
const activeTanggalSelesai = ref(props.filters.tanggal_selesai || props.filters.end_date || "");
const activeMode = ref(props.filters.mode || "periode");
const activePeriodeId = ref(props.filters.periode_id || "");
const isFilterAllTime = ref(Boolean(props.filters.is_all_time || props.filters.isAllTime));

const searchInput = ref(props.filters.cari || props.filters.search || "");
const selectedJenis = ref(props.filters.jenis || props.filters.jenis_limbah || "all");
const selectedSumber = ref(props.filters.sumber || props.filters.sumber_limbah || "all");
const selectedMetode = ref(props.filters.metode || props.filters.metode_penanganan || "all");
const selectedStatus = ref(props.filters.status || props.filters.status_berbahaya || "all");
const mobileViewMode = ref("card"); // 'card' | 'table' di layar smartphone

// ─── PENCARIAN OTOMATIS SETELAH SELESAI MENGETIK (DEBOUNCE 350ms) ───
let searchDebounceTimer = null;
watch(searchInput, () => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        applyFilters();
    }, 350);
});

// Sinkronisasi saat props.filters diperbarui dari Inertia
watch(
    () => props.filters,
    (f) => {
        if (!f) return;
        activeTanggalMulai.value = f.tanggal_mulai || f.start_date || "";
        activeTanggalSelesai.value = f.tanggal_selesai || f.end_date || "";
        activeMode.value = f.mode || "periode";
        activePeriodeId.value = f.periode_id || "";
        isFilterAllTime.value = Boolean(f.is_all_time || f.isAllTime);

        const fCari = f.cari !== undefined ? f.cari : (f.search || "");
        if (fCari !== searchInput.value) {
            searchInput.value = fCari;
        }
        selectedJenis.value = f.jenis || f.jenis_limbah || "all";
        selectedSumber.value = f.sumber || f.sumber_limbah || "all";
        selectedMetode.value = f.metode || f.metode_penanganan || "all";
        selectedStatus.value = f.status || f.status_berbahaya || "all";
    },
    { deep: true }
);

// ─── HANDLING FILTER DENGAN VARIABEL BAHASA INDONESIA ───
function applyFilters() {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }

    const params = {
        mode: activeMode.value,
        periode_id: activePeriodeId.value,
    };

    if (isFilterAllTime.value) {
        params.is_all_time = 1;
    } else {
        if (activeTanggalMulai.value) params.tanggal_mulai = activeTanggalMulai.value;
        if (activeTanggalSelesai.value) params.tanggal_selesai = activeTanggalSelesai.value;
    }

    if (searchInput.value && searchInput.value.trim() !== "") {
        params.cari = searchInput.value.trim();
    }
    if (selectedJenis.value && selectedJenis.value !== "all") {
        params.jenis = selectedJenis.value;
    }
    if (selectedSumber.value && selectedSumber.value !== "all") {
        params.sumber = selectedSumber.value;
    }
    if (selectedMetode.value && selectedMetode.value !== "all") {
        params.metode = selectedMetode.value;
    }
    if (selectedStatus.value && selectedStatus.value !== "all") {
        params.status = selectedStatus.value;
    }

    router.get(route("residu.index"), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function handleDateFilterChange(filterPayload) {
    if (!filterPayload) return;
    activeTanggalMulai.value = filterPayload.start || "";
    activeTanggalSelesai.value = filterPayload.end || "";
    activeMode.value = filterPayload.mode || "periode";
    activePeriodeId.value = filterPayload.periodeId || "";
    isFilterAllTime.value = Boolean(filterPayload.isAllTime);
    applyFilters();
}

function resetFilters() {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
    searchInput.value = "";
    selectedJenis.value = "all";
    selectedSumber.value = "all";
    selectedMetode.value = "all";
    selectedStatus.value = "all";
    applyFilters();
}

// ─── MODAL FORM TAMBAH / UBAH ───
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const todayYmd = new Date().toISOString().split("T")[0];

const form = useForm({
    tanggal: todayYmd,
    nama_limbah: "",
    jumlah: "",
    satuan: "Kg",
    jenis_limbah: "Organik",
    sumber_limbah: "Persiapan",
    metode_penanganan: "Kompos",
    status_berbahaya: "Tidak Berbahaya",
    keterangan: "",
});

function openAddModal() {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.tanggal = todayYmd;
    form.satuan = "Kg";
    form.jenis_limbah = "Organik";
    form.sumber_limbah = "Persiapan";
    form.metode_penanganan = "Kompos";
    form.status_berbahaya = "Tidak Berbahaya";
    form.clearErrors();
    isFormModalOpen.value = true;
}

function openEditModal(item) {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();

    const tglYmd = item.tanggal ? String(item.tanggal).substring(0, 10) : todayYmd;

    form.tanggal = tglYmd;
    form.nama_limbah = item.nama_limbah;
    form.jumlah = item.jumlah;
    form.satuan = item.satuan || "Kg";
    form.jenis_limbah = item.jenis_limbah || "Organik";
    form.sumber_limbah = item.sumber_limbah || "Persiapan";
    form.metode_penanganan = item.metode_penanganan || "Kompos";
    form.status_berbahaya = item.status_berbahaya || "Tidak Berbahaya";
    form.keterangan = item.keterangan || "";
    isFormModalOpen.value = true;
}

function closeFormModal() {
    isFormModalOpen.value = false;
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (isEditing.value) {
        form.put(route("residu.update", editingId.value), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(route("residu.store"), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    }
}

// ─── MODAL HAPUS ───
const isDeleteModalOpen = ref(false);
const deletingItem = ref(null);
const isDeleting = ref(false);

function openDeleteModal(item) {
    deletingItem.value = item;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
    deletingItem.value = null;
}

function confirmDelete() {
    if (!deletingItem.value) return;
    isDeleting.value = true;
    router.delete(route("residu.destroy", deletingItem.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

// ─── FITUR SELEKSI BEBERAPA / SEMUA UNTUK HAPUS SEKALIGUS ───
const selectedIds = ref([]);
const isBulkDeleteModalOpen = ref(false);
const isBulkDeleting = ref(false);

const isAllSelected = computed(() => {
    if (!props.residuList || props.residuList.length === 0) return false;
    return props.residuList.every((it) => selectedIds.value.includes(it.id));
});

const isPartiallySelected = computed(() => {
    return selectedIds.value.length > 0 && !isAllSelected.value;
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.residuList.map((it) => it.id);
    }
}

function toggleSelectItem(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
}

function clearSelection() {
    selectedIds.value = [];
}

function openBulkDeleteModal() {
    if (selectedIds.value.length === 0) return;
    isBulkDeleteModalOpen.value = true;
}

function closeBulkDeleteModal() {
    isBulkDeleteModalOpen.value = false;
}

function confirmBulkDelete() {
    if (selectedIds.value.length === 0) return;
    isBulkDeleting.value = true;
    router.post(
        route("residu.bulk-destroy"),
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeBulkDeleteModal();
                selectedIds.value = [];
            },
            onFinish: () => {
                isBulkDeleting.value = false;
            },
        }
    );
}

// ─── MODAL AI ASSISTANT RESIDU ───
const isAiModalOpen = ref(false);

function openAiModal() {
    isAiModalOpen.value = true;
}

function handleAiSaved(savedCount) {
    router.reload({ preserveScroll: true });
}

// ─── DOWNLOAD REKAP LAPORAN EXCEL (4 TTD DIAMBIL OTOMATIS DARI DAFTAR PETUGAS) ───
function triggerDownloadExcel() {
    downloadRekapResiduExcel({
        residuList: props.residuList,
        summary: props.summary,
        startDate: activeTanggalMulai.value,
        endDate: activeTanggalSelesai.value,
        unitSppg: props.unitSppg,
        pejabatTtd: props.pejabatTtd,
    });
}

function formatNumber(val) {
    if (val === null || val === undefined || isNaN(val)) return "0";
    return parseFloat(val).toLocaleString("id-ID", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
</script>

<template>
    <Head title="Residu - SIPEGE" />

    <AppLayout>
        <div class="space-y-6 pb-16">
            <!-- ─── HEADER UTAMA: IDENTIK DENGAN PEMBAYARAN GAJI ─── -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-emerald-600 text-white shadow-xs">
                            <Trash2 class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                    Pencatatan Residu & Limbah Harian
                                </h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    Operasional SPPG
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                                Logbook inventarisasi limbah harian, pemilahan organik/anorganik, dan penanganan terstandar ramah lingkungan.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Header (Urutan: Download, Catat Pintar, Tambah Baru) -->
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                    <!-- 1. Tombol Download Excel (.xlsx) -->
                    <button
                        type="button"
                        @click="triggerDownloadExcel"
                        class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-3.5 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
                        title="Download Rekap Residu & Limbah (.xlsx)"
                    >
                        <FileSpreadsheet class="h-4 w-4 shrink-0" />
                        <span class="truncate">Download Excel</span>
                    </button>

                    <!-- 2. Tombol Catat Otomatis dg AI -->
                    <button
                        type="button"
                        @click="openAiModal"
                        class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-3.5 py-2 sm:py-2.5 rounded-xl bg-gradient-to-r from-violet-600 via-indigo-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 active:from-violet-800 text-white text-xs font-bold shadow-xs transition-all cursor-pointer hover:shadow-md w-full sm:w-auto"
                        title="Catat Residu Otomatis dengan AI Gemini (Pesan Singkat / File Excel)"
                    >
                        <Sparkles class="h-4 w-4 text-amber-300 animate-pulse shrink-0" />
                        <span class="truncate">Catat Pintar</span>
                    </button>

                    <!-- 3. Tombol Tambah Baru (Span 2 di HP, Full Width, Prominent CTA!) -->
                    <button
                        type="button"
                        @click="openAddModal"
                        class="col-span-2 sm:col-span-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 sm:py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 active:bg-slate-950 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer w-full sm:w-auto"
                    >
                        <Plus class="h-4 w-4 shrink-0" />
                        <span>Catat Limbah Baru</span>
                    </button>
                </div>
            </div>

            <!-- ─── FILTER TANGGAL: REUSABLE PERIODDATEFILTERBAR ─── -->
            <div class="space-y-2">
                <PeriodDateFilterBar
                    :startDate="activeTanggalMulai"
                    :endDate="activeTanggalSelesai"
                    :mode="activeMode"
                    :periodeId="activePeriodeId"
                    :isAllTime="isFilterAllTime"
                    :periodes="periodeList"
                    @change="handleDateFilterChange"
                />
            </div>

            <!-- ─── KPI SUMMARY CARDS: MENAMPILKAN PERIODE AKTIF & TOTAL SELURUH DATABASE ─── -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
                <!-- Card 1: Total Catatan (Row 1 Col 1 di HP, Col 1 di LG) -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col justify-between col-span-1 lg:row-start-1 lg:col-start-1 lg:col-span-1">
                    <div>
                        <div class="flex items-center justify-between gap-1.5 mb-2">
                            <div class="p-2 sm:p-2.5 rounded-lg bg-emerald-50 text-emerald-600 shrink-0">
                                <Layers class="h-4 w-4 sm:h-5 sm:w-5" />
                            </div>
                            <span
                                :class="[
                                    'px-1.5 py-0.5 rounded text-[9px] font-bold shrink-0',
                                    isFilterAllTime ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800'
                                ]"
                            >
                                {{ isFilterAllTime ? 'Semua' : 'Periode' }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">
                                Total Catatan
                            </p>
                            <p class="text-base sm:text-xl font-black text-slate-900 leading-tight mt-0.5">
                                {{ summary.total_catatan || 0 }} <span class="text-xs font-normal text-slate-400">entri</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500">
                        <span class="flex items-center gap-1 text-slate-400 truncate">
                            <Database class="h-3 w-3 shrink-0" /> Total DB:
                        </span>
                        <span class="font-bold text-slate-700 font-mono shrink-0 ml-1">
                            {{ allTimeSummary.total_catatan || 0 }}
                        </span>
                    </div>
                </div>

                <!-- Card 3: Akumulasi Cairan Liter (Row 1 Col 2 di HP, Col 4 di LG) -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col justify-between col-span-1 lg:row-start-1 lg:col-start-4 lg:col-span-1">
                    <div>
                        <div class="flex items-center justify-between gap-1.5 mb-2">
                            <div class="p-2 sm:p-2.5 rounded-lg bg-blue-50 text-blue-600 shrink-0">
                                <Recycle class="h-4 w-4 sm:h-5 sm:w-5" />
                            </div>
                            <span
                                :class="[
                                    'px-1.5 py-0.5 rounded text-[9px] font-bold shrink-0',
                                    isFilterAllTime ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800'
                                ]"
                            >
                                {{ isFilterAllTime ? 'Semua' : 'Periode' }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">
                                Akumulasi Cairan
                            </p>
                            <p class="text-base sm:text-xl font-black text-slate-900 leading-tight mt-0.5">
                                {{ formatNumber(summary.total_volume_l) }} <span class="text-xs font-bold text-blue-700">L</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500">
                        <span class="flex items-center gap-1 text-slate-400 truncate">
                            <Database class="h-3 w-3 shrink-0" /> Total DB:
                        </span>
                        <span class="font-bold text-blue-700 font-mono shrink-0 ml-1">
                            {{ formatNumber(allTimeSummary.total_volume_l) }} L
                        </span>
                    </div>
                </div>

                <!-- Card 2: Akumulasi Berat Kg (Row 2 Span 2 Kolom di HP, Col 2-3 di LG) -->
                <div class="p-3.5 sm:p-4 rounded-xl bg-emerald-50/70 border border-emerald-300/80 shadow-2xs col-span-2 lg:row-start-1 lg:col-start-2 lg:col-span-2 space-y-2.5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 sm:p-2.5 rounded-lg bg-emerald-600 text-white shrink-0 shadow-xs">
                                <Scale class="h-4 w-4 sm:h-5 sm:w-5" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-[10px] sm:text-[11px] font-bold text-emerald-800 uppercase tracking-wider">
                                        Akumulasi Berat Limbah
                                    </p>
                                    <span
                                        :class="[
                                            'px-1.5 py-0.2 rounded text-[9px] font-bold shrink-0',
                                            isFilterAllTime ? 'bg-indigo-200/90 text-indigo-950' : 'bg-emerald-200/90 text-emerald-900'
                                        ]"
                                    >
                                        {{ isFilterAllTime ? 'Semua Data' : 'Periode Ini' }}
                                    </span>
                                </div>
                                <p class="text-lg sm:text-2xl font-black text-emerald-950 leading-tight">
                                    {{ formatNumber(summary.total_berat_kg) }} <span class="text-xs sm:text-sm font-bold text-emerald-800">Kg</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-1.5 pt-1 sm:pt-0 border-t sm:border-t-0 border-emerald-200/60">
                            <span class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-emerald-200/80 text-emerald-900 border border-emerald-300/90 shrink-0">
                                Khusus Satuan Kg
                            </span>
                            <div class="flex items-center gap-1 text-[10px] font-medium text-emerald-900 bg-white/80 px-2 py-0.5 rounded border border-emerald-300/70">
                                <Database class="h-3 w-3 text-emerald-700 shrink-0" />
                                <span>Total DB:</span>
                                <strong class="font-mono font-bold">{{ formatNumber(allTimeSummary.total_berat_kg) }} Kg</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Sangat Jelas: Organik vs Anorganik -->
                    <div class="grid grid-cols-2 gap-2 pt-1 border-t border-emerald-200/90">
                        <div class="p-2 rounded-lg bg-white/95 border border-emerald-300 shadow-2xs space-y-1">
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0 ring-2 ring-emerald-200"></span>
                                    <span class="text-[11px] font-bold text-slate-800 truncate">Organik</span>
                                </div>
                                <span class="text-xs font-black text-emerald-700 shrink-0 font-mono">
                                    {{ formatNumber(summary.total_organik_kg) }} <span class="text-[10px] font-bold text-slate-500">Kg</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[9.5px] text-slate-400 border-t border-slate-100 pt-0.5">
                                <span>Total DB:</span>
                                <span class="font-mono font-semibold text-slate-600">{{ formatNumber(allTimeSummary.total_organik_kg) }} Kg</span>
                            </div>
                        </div>
                        <div class="p-2 rounded-lg bg-white/95 border border-slate-300 shadow-2xs space-y-1">
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0 ring-2 ring-amber-200"></span>
                                    <span class="text-[11px] font-bold text-slate-800 truncate">Anorganik</span>
                                </div>
                                <span class="text-xs font-black text-slate-800 shrink-0 font-mono">
                                    {{ formatNumber(summary.total_anorganik_kg) }} <span class="text-[10px] font-bold text-slate-500">Kg</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[9.5px] text-slate-400 border-t border-slate-100 pt-0.5">
                                <span>Total DB:</span>
                                <span class="font-mono font-semibold text-slate-600">{{ formatNumber(allTimeSummary.total_anorganik_kg) }} Kg</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Limbah Berbahaya (Row 3 Span 2 Kolom di HP, Col 5 di LG) -->
                <div
                    :class="[
                        'p-3 sm:p-4 rounded-xl border shadow-2xs flex flex-col justify-between col-span-2 lg:row-start-1 lg:col-start-5 lg:col-span-1',
                        summary.total_berbahaya > 0 ? 'bg-rose-50/70 border-rose-200' : 'bg-white border-slate-200',
                    ]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-1.5 mb-2">
                            <div
                                :class="[
                                    'p-2 sm:p-2.5 rounded-lg shrink-0',
                                    summary.total_berbahaya > 0 ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600',
                                ]"
                            >
                                <AlertTriangle class="h-4 w-4 sm:h-5 sm:w-5" />
                            </div>
                            <span
                                :class="[
                                    'px-1.5 py-0.5 rounded text-[9px] font-bold shrink-0',
                                    isFilterAllTime ? 'bg-indigo-100 text-indigo-800' : (summary.total_berbahaya > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600')
                                ]"
                            >
                                {{ isFilterAllTime ? 'Semua' : 'Periode' }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p
                                :class="[
                                    'text-[10px] sm:text-[11px] font-bold uppercase tracking-wider truncate',
                                    summary.total_berbahaya > 0 ? 'text-rose-800' : 'text-slate-500',
                                ]"
                            >
                                Limbah Berbahaya
                            </p>
                            <p
                                :class="[
                                    'text-base sm:text-xl font-black leading-tight mt-0.5',
                                    summary.total_berbahaya > 0 ? 'text-rose-950' : 'text-slate-900',
                                ]"
                            >
                                {{ summary.total_berbahaya || 0 }} <span class="text-xs font-normal">catatan</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500">
                        <span class="flex items-center gap-1 text-slate-400 truncate">
                            <Database class="h-3 w-3 shrink-0" /> Total DB:
                        </span>
                        <span class="font-bold text-slate-700 font-mono shrink-0 ml-1">
                            {{ allTimeSummary.total_berbahaya || 0 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ─── BARIS PENCARIAN & FILTER SEPERTI PEMBAYARAN GAJI ─── -->
            <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-2.5 sm:gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 flex-1 w-full">
                    <!-- Search Input dengan Auto-Search dan Clear Button -->
                    <div class="relative w-full sm:flex-1 sm:min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                        <input
                            v-model="searchInput"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Cari nama limbah, sisa olahan..."
                            class="w-full pl-9 pr-8 py-2 sm:py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white"
                        />
                        <button
                            v-if="searchInput"
                            type="button"
                            @click="searchInput = ''; applyFilters();"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 cursor-pointer"
                            title="Hapus pencarian"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- 3 Dropdown Filter: Grid 3 Kolom Rata di Layar HP! -->
                    <div class="grid grid-cols-3 sm:flex sm:items-center gap-1.5 sm:gap-2 w-full sm:w-auto">
                        <!-- Dropdown Filter Jenis -->
                        <select
                            v-model="selectedJenis"
                            @change="applyFilters"
                            class="w-full sm:w-auto px-2 sm:px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white cursor-pointer truncate text-center sm:text-left"
                        >
                            <option value="all">Semua Jenis</option>
                            <option value="Organik">Organik</option>
                            <option value="Anorganik">Anorganik</option>
                        </select>

                        <!-- Dropdown Filter Sumber -->
                        <select
                            v-model="selectedSumber"
                            @change="applyFilters"
                            class="w-full sm:w-auto px-2 sm:px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white cursor-pointer truncate text-center sm:text-left"
                        >
                            <option value="all">Semua Sumber</option>
                            <option value="Persiapan">Persiapan</option>
                            <option value="Pengolahan">Pengolahan</option>
                            <option value="Ompreng">Ompreng</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>

                        <!-- Dropdown Filter Bahaya -->
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="w-full sm:w-auto px-2 sm:px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white cursor-pointer truncate text-center sm:text-left"
                        >
                            <option value="all">Semua Status</option>
                            <option value="Tidak Berbahaya">Aman</option>
                            <option value="Berbahaya">Berbahaya</option>
                        </select>
                    </div>
                </div>

                <!-- Info Jumlah Data & Tombol Reset -->
                <div class="flex items-center justify-between sm:justify-end gap-2 text-xs text-slate-500 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100 w-full md:w-auto shrink-0">
                    <span>
                        Terdata: <b class="text-emerald-700 font-bold">{{ residuList.length }}</b> catatan
                    </span>
                    <button
                        type="button"
                        @click="resetFilters"
                        title="Reset Filter"
                        class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors flex items-center gap-1 cursor-pointer"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span class="text-[11px] font-medium sm:hidden">Reset</span>
                    </button>
                </div>
            </div>

            <!-- ─── FLOATING / STICKY ACTION BAR KETIKA ADA ITEM TERPILIH (BULK ACTION) ─── -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="selectedIds.length > 0"
                    class="p-3 sm:p-3.5 rounded-2xl bg-slate-900 text-white shadow-xl flex items-center justify-between flex-wrap gap-3 border border-slate-800"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500 text-white font-black text-xs shadow-xs">
                            {{ selectedIds.length }}
                        </span>
                        <div>
                            <p class="text-xs font-bold text-white leading-tight">
                                {{ selectedIds.length }} Catatan Terpilih
                            </p>
                            <p class="text-[10px] text-slate-400">
                                Dari total {{ residuList.length }} catatan di periode ini
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 sm:gap-2 w-full sm:w-auto justify-between sm:justify-end flex-wrap sm:flex-nowrap">
                        <!-- Tombol Pilih Semua -->
                        <button
                            type="button"
                            @click="toggleSelectAll"
                            class="px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 transition-colors cursor-pointer"
                        >
                            {{ isAllSelected ? 'Batalkan Semua' : 'Pilih Semua (' + residuList.length + ')' }}
                        </button>

                        <!-- Tombol Batal Pilih -->
                        <button
                            type="button"
                            @click="clearSelection"
                            class="px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-semibold bg-transparent hover:bg-slate-800 text-slate-400 hover:text-white transition-colors cursor-pointer"
                        >
                            Batal
                        </button>

                        <!-- Tombol Hapus Sekaligus (Berbahaya) -->
                        <button
                            type="button"
                            @click="openBulkDeleteModal"
                            class="inline-flex items-center gap-1 px-3 sm:px-3.5 py-1.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-md transition-colors cursor-pointer"
                        >
                            <Trash2 class="h-3.5 w-3.5 shrink-0" />
                            <span>Hapus {{ selectedIds.length }} Sekaligus</span>
                        </button>
                    </div>
                </div>
            </transition>

            <!-- ─── TABEL PENCATATAN RESIDU ─── -->
            <div class="rounded-2xl border border-slate-200/90 shadow-2xs bg-white overflow-hidden">
                <!-- Toolbar Tabel -->
                <div class="px-4 sm:px-5 py-3 sm:py-3.5 border-b border-slate-100 flex items-center justify-between gap-2.5 bg-slate-50/40">
                    <div class="flex items-center gap-2">
                        <span class="text-xs sm:text-sm font-bold text-slate-900 tracking-tight uppercase">
                            Logbook Residu & Limbah
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            {{ residuList.length }} Catatan
                        </span>
                    </div>

                    <!-- Switcher Mode Tampilan di Layar HP (Kartu vs Tabel) -->
                    <div class="flex items-center sm:hidden bg-slate-200/80 p-0.5 rounded-lg text-[10.5px] font-bold">
                        <button
                            type="button"
                            @click="mobileViewMode = 'card'"
                            class="px-2.5 py-1 rounded-md transition-all cursor-pointer"
                            :class="mobileViewMode === 'card' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600'"
                        >
                            Kartu
                        </button>
                        <button
                            type="button"
                            @click="mobileViewMode = 'table'"
                            class="px-2.5 py-1 rounded-md transition-all cursor-pointer"
                            :class="mobileViewMode === 'table' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600'"
                        >
                            Tabel
                        </button>
                    </div>
                </div>

                <!-- TAMPILAN 1: KARTU RESPONSIF KHUSUS HP (Sangat Rapi & Rata) -->
                <div v-if="mobileViewMode === 'card'" class="sm:hidden divide-y divide-slate-100">
                    <div
                        v-for="(item, idx) in residuList"
                        :key="item.id"
                        :class="[
                            'p-3.5 space-y-2.5 transition-colors',
                            selectedIds.includes(item.id)
                                ? 'bg-emerald-50/70'
                                : item.status_berbahaya === 'Berbahaya'
                                ? 'bg-rose-50/20'
                                : 'hover:bg-slate-50/50',
                        ]"
                    >
                        <!-- Baris 1: Checkbox, No, Tanggal, & Badge Jenis/Bahaya -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(item.id)"
                                    @change="toggleSelectItem(item.id)"
                                    class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer h-4 w-4"
                                />
                                <span class="text-xs font-mono font-bold text-slate-400">#{{ idx + 1 }}</span>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded-md">
                                    <Calendar class="h-3 w-3 text-slate-500" />
                                    {{ item.tanggal_formatted || formatTglDdMmYyyy(item.tanggal) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-full text-[10px] font-bold border',
                                        item.jenis_limbah === 'Organik'
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                            : 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                    ]"
                                >
                                    {{ item.jenis_limbah }}
                                </span>
                                <span
                                    v-if="item.status_berbahaya === 'Berbahaya'"
                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9.5px] font-bold bg-rose-100 text-rose-800 border border-rose-300"
                                >
                                    <AlertTriangle class="h-2.5 w-2.5 text-rose-600" />
                                    B3
                                </span>
                            </div>
                        </div>

                        <!-- Baris 2: Nama Limbah (Bold) & Jumlah + Satuan (Besar) -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-slate-900 leading-snug">
                                    {{ item.nama_limbah }}
                                </h4>
                                <p v-if="item.keterangan" class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">
                                    {{ item.keterangan }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-black font-mono leading-tight" :class="item.satuan === 'L' ? 'text-blue-700' : 'text-emerald-800'">
                                    {{ formatNumber(item.jumlah) }}
                                    <span class="text-xs font-bold">{{ item.satuan }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Baris 3: Tag Sumber & Metode Penanganan + Tombol Aksi (Ubah & Hapus) -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-wrap text-[10.5px]">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium">
                                    {{ item.sumber_limbah }}
                                </span>
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-md font-bold',
                                        item.metode_penanganan === 'Kompos' ? 'bg-teal-50 text-teal-800 border border-teal-200' :
                                        item.metode_penanganan === 'Daur Ulang' ? 'bg-blue-50 text-blue-800 border border-blue-200' :
                                        item.metode_penanganan === 'Donasi' ? 'bg-amber-50 text-amber-800 border border-amber-200' :
                                        'bg-slate-100 text-slate-700 border border-slate-200',
                                    ]"
                                >
                                    {{ item.metode_penanganan }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    @click="openEditModal(item)"
                                    title="Ubah Catatan"
                                    class="p-1.5 rounded-lg bg-slate-100 hover:bg-blue-50 text-blue-600 transition-colors cursor-pointer"
                                >
                                    <Edit2 class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    type="button"
                                    @click="openDeleteModal(item)"
                                    title="Hapus Catatan"
                                    class="p-1.5 rounded-lg bg-slate-100 hover:bg-rose-50 text-rose-600 transition-colors cursor-pointer"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State di Card View -->
                    <div v-if="residuList.length === 0" class="py-12 text-center text-slate-400 p-4">
                        <Trash2 class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                        <p class="text-sm font-semibold text-slate-600">Belum ada catatan limbah pada filter ini.</p>
                        <p class="text-xs text-slate-400 mt-1">Klik tombol '+ Catat Limbah Baru' untuk mulai menginput.</p>
                    </div>
                </div>

                <!-- TAMPILAN 2: TABEL STANDAR (Aktif di Layar Besar atau Saat Dipilih di HP) -->
                <div :class="[mobileViewMode === 'card' ? 'hidden sm:block' : 'block', 'overflow-x-auto']">
                    <!-- Petunjuk geser di HP saat mode tabel aktif -->
                    <div class="sm:hidden px-3 py-2 bg-slate-100/80 border-b border-slate-200 text-[11px] text-slate-500 text-center font-medium">
                        👈 Geser tabel ke samping untuk melihat seluruh kolom 👉
                    </div>

                    <table class="w-full text-left text-xs text-slate-600 border-collapse min-w-[1050px]">
                        <thead class="bg-slate-100/80 text-slate-700 uppercase font-bold text-[10.5px] tracking-wider border-b border-slate-200">
                            <tr>
                                <!-- Checkbox Select All -->
                                <th class="py-3 px-3 text-center w-10 border-r border-slate-200">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        :indeterminate.prop="isPartiallySelected"
                                        @change="toggleSelectAll"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer h-4 w-4"
                                        title="Pilih Semua / Batal Pilih"
                                    />
                                </th>
                                <th class="py-3 px-3.5 text-center w-12 border-r border-slate-200">No</th>
                                <th class="py-3 px-3.5 text-center w-28 border-r border-slate-200">Tanggal</th>
                                <th class="py-3 px-4 min-w-[200px] border-r border-slate-200">Nama Limbah</th>
                                <th class="py-3 px-3.5 text-right w-24 border-r border-slate-200">Jumlah</th>
                                <th class="py-3 px-3 text-center w-16 border-r border-slate-200">Satuan</th>
                                <th class="py-3 px-3.5 text-center w-28 border-r border-slate-200">Jenis Limbah</th>
                                <th class="py-3 px-3.5 text-center w-28 border-r border-slate-200">Sumber</th>
                                <th class="py-3 px-3.5 text-center w-32 border-r border-slate-200">Penanganan</th>
                                <th class="py-3 px-3.5 text-center w-32 border-r border-slate-200">Status Bahaya</th>
                                <th class="py-3 px-4 min-w-[180px] border-r border-slate-200">Keterangan</th>
                                <th class="py-3 px-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, idx) in residuList"
                                :key="item.id"
                                :class="[
                                    'transition-colors',
                                    selectedIds.includes(item.id)
                                        ? 'bg-emerald-50/70 hover:bg-emerald-100/60'
                                        : item.status_berbahaya === 'Berbahaya'
                                        ? 'bg-rose-50/20 hover:bg-slate-50/80'
                                        : 'hover:bg-slate-50/80',
                                ]"
                            >
                                <!-- Checkbox Baris -->
                                <td class="py-3 px-3 text-center border-r border-slate-100">
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.includes(item.id)"
                                        @change="toggleSelectItem(item.id)"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer h-4 w-4"
                                    />
                                </td>

                                <!-- No -->
                                <td class="py-3 px-3.5 text-center font-medium text-slate-400 border-r border-slate-100">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Tanggal (DD-MM-YYYY) -->
                                <td class="py-3 px-3.5 text-center font-semibold text-slate-800 border-r border-slate-100 whitespace-nowrap">
                                    {{ item.tanggal_formatted || formatTglDdMmYyyy(item.tanggal) }}
                                </td>

                                <!-- Nama Limbah -->
                                <td class="py-3 px-4 font-bold text-slate-900 border-r border-slate-100">
                                    {{ item.nama_limbah }}
                                </td>

                                <!-- Jumlah (Desimal) -->
                                <td class="py-3 px-3.5 text-right font-black text-slate-900 border-r border-slate-100 font-mono">
                                    {{ formatNumber(item.jumlah) }}
                                </td>

                                <!-- Satuan (Kg/L) -->
                                <td class="py-3 px-3 text-center font-bold text-slate-700 border-r border-slate-100">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200 text-[11px]">
                                        {{ item.satuan }}
                                    </span>
                                </td>

                                <!-- Jenis Limbah (Organik, Anorganik) -->
                                <td class="py-3 px-3.5 text-center border-r border-slate-100">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10.5px] font-bold border',
                                            item.jenis_limbah === 'Organik'
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                : 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                        ]"
                                    >
                                        {{ item.jenis_limbah }}
                                    </span>
                                </td>

                                <!-- Sumber Limbah (Persiapan, Pengolahan, Ompreng, Lainnya) -->
                                <td class="py-3 px-3.5 text-center border-r border-slate-100">
                                    <span class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        {{ item.sumber_limbah }}
                                    </span>
                                </td>

                                <!-- Metode Penanganan (Donasi, Kompos, Daur Ulang, Pembuangan) -->
                                <td class="py-3 px-3.5 text-center border-r border-slate-100">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-lg text-[11px] font-bold',
                                            item.metode_penanganan === 'Kompos' ? 'bg-teal-50 text-teal-800 border border-teal-200' :
                                            item.metode_penanganan === 'Daur Ulang' ? 'bg-blue-50 text-blue-800 border border-blue-200' :
                                            item.metode_penanganan === 'Donasi' ? 'bg-amber-50 text-amber-800 border border-amber-200' :
                                            'bg-slate-100 text-slate-700 border border-slate-200',
                                        ]"
                                    >
                                        {{ item.metode_penanganan }}
                                    </span>
                                </td>

                                <!-- Status Berbahaya (Tidak Berbahaya, Berbahaya) -->
                                <td class="py-3 px-3.5 text-center border-r border-slate-100 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-bold border',
                                            item.status_berbahaya === 'Berbahaya'
                                                ? 'bg-rose-100 text-rose-800 border-rose-300'
                                                : 'bg-slate-100 text-slate-700 border-slate-200',
                                        ]"
                                    >
                                        <AlertTriangle v-if="item.status_berbahaya === 'Berbahaya'" class="h-3 w-3 text-rose-600" />
                                        <ShieldCheck v-else class="h-3 w-3 text-slate-500" />
                                        {{ item.status_berbahaya }}
                                    </span>
                                </td>

                                <!-- Keterangan -->
                                <td class="py-3 px-4 text-slate-500 border-r border-slate-100 text-[11.5px]">
                                    {{ item.keterangan || '-' }}
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(item)"
                                            title="Ubah Catatan"
                                            class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                                        >
                                            <Edit2 class="h-3.5 w-3.5 text-blue-600" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteModal(item)"
                                            title="Hapus Catatan"
                                            class="p-1.5 rounded-lg hover:bg-rose-100 text-slate-600 transition-colors"
                                        >
                                            <Trash2 class="h-3.5 w-3.5 text-rose-600" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="residuList.length === 0">
                                <td colspan="11" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Trash2 class="h-8 w-8 text-slate-300" />
                                        <p class="text-sm font-semibold text-slate-600">Belum ada catatan limbah pada periode/filter ini.</p>
                                        <p class="text-xs text-slate-400">Klik tombol '+ Catat Limbah Baru' di kanan atas untuk mulai menginput.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Footer Tabel Akumulasi -->
                        <tfoot v-if="residuList.length > 0" class="bg-emerald-50/50 font-bold text-slate-900 border-t-2 border-slate-200">
                            <tr>
                                <td colspan="3" class="py-3 px-4 text-center border-r border-slate-200">
                                    TOTAL KESELURUHAN ({{ residuList.length }} Catatan)
                                </td>
                                <td colspan="2" class="py-3 px-3.5 text-right font-black text-emerald-900 border-r border-slate-200">
                                    {{ formatNumber(summary.total_berat_kg) }} Kg & {{ formatNumber(summary.total_volume_l) }} L
                                </td>
                                <td colspan="6" class="py-3 px-4 text-xs font-normal text-slate-600">
                                    Organik: <strong class="text-slate-900 font-bold">{{ formatNumber(summary.total_organik_kg) }} Kg</strong> •
                                    Anorganik: <strong class="text-slate-900 font-bold">{{ formatNumber(summary.total_anorganik_kg) }} Kg</strong> •
                                    Berbahaya: <strong class="text-rose-700 font-bold">{{ summary.total_berbahaya }} item</strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- ─── MODAL TAMBAH / UBAH (TELEPORTED TO BODY SUPAYA BACKDROP 100% LAYAR) ─── -->
        <Teleport to="body">
            <div
                v-if="isFormModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150 overflow-y-auto"
            >
                <div
                    @click.stop
                    class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-xl overflow-hidden animate-in zoom-in-95 duration-150 my-auto max-h-[92vh] flex flex-col"
                >
                    <!-- Modal Header -->
                    <div class="px-5 sm:px-6 py-3.5 sm:py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-emerald-600 text-white shadow-xs shrink-0">
                                <Trash2 class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                    {{ isEditing ? 'Ubah Catatan Limbah' : 'Catat Limbah Harian Baru' }}
                                </h3>
                                <p class="text-[11px] sm:text-xs text-slate-500">
                                    Isikan rincian data limbah hasil operasional SPPG sesuai formulir resmi.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeFormModal"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer shrink-0"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitForm" class="p-4 sm:p-6 space-y-3.5 sm:space-y-4 overflow-y-auto flex-1">
                        <!-- Baris 1: Tanggal & Satuan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Tanggal -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Tanggal Pencatatan <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.tanggal"
                                    type="date"
                                    required
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                />
                                <p class="text-[10.5px] text-slate-400 mt-1">Format laporan tampil: DD-MM-YYYY</p>
                                <p v-if="form.errors.tanggal" class="text-[11px] text-rose-500 mt-0.5">
                                    {{ form.errors.tanggal }}
                                </p>
                            </div>

                            <!-- Jumlah & Satuan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Jumlah & Satuan <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="form.jumlah"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="Contoh: 14.5"
                                        required
                                        class="flex-1 px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold"
                                    />
                                    <select
                                        v-model="form.satuan"
                                        required
                                        class="w-24 px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold bg-slate-50 cursor-pointer"
                                    >
                                        <option value="Kg">Kg</option>
                                        <option value="L">L (Liter)</option>
                                    </select>
                                </div>
                                <p class="text-[10.5px] text-slate-400 mt-1">Bilangan desimal diperbolehkan (misal: 2.5)</p>
                                <p v-if="form.errors.jumlah" class="text-[11px] text-rose-500 mt-0.5">
                                    {{ form.errors.jumlah }}
                                </p>
                            </div>
                        </div>

                        <!-- Baris 2: Nama Limbah -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Nama Limbah <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.nama_limbah"
                                type="text"
                                placeholder="Contoh: Kulit & Bonggol Sayuran, Minyak Jelantah, Sisa Tulang"
                                required
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                            />
                            <p v-if="form.errors.nama_limbah" class="text-[11px] text-rose-500 mt-0.5">
                                {{ form.errors.nama_limbah }}
                            </p>
                        </div>

                        <!-- Baris 3: Jenis Limbah & Sumber Limbah -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Jenis Limbah (Pilihan hanya: Organik, Anorganik) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Jenis Limbah <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.jenis_limbah"
                                    required
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold cursor-pointer"
                                >
                                    <option value="Organik">Organik</option>
                                    <option value="Anorganik">Anorganik</option>
                                </select>
                                <p v-if="form.errors.jenis_limbah" class="text-[11px] text-rose-500 mt-0.5">
                                    {{ form.errors.jenis_limbah }}
                                </p>
                            </div>

                            <!-- Sumber Limbah (Pilihan hanya: Persiapan, Pengolahan, Ompreng, Lainnya) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Sumber Limbah <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.sumber_limbah"
                                    required
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold cursor-pointer"
                                >
                                    <option value="Persiapan">Persiapan (Kupasan, Potongan Bahan)</option>
                                    <option value="Pengolahan">Pengolahan (Dapur Masak, Minyak)</option>
                                    <option value="Ompreng">Ompreng (Sisa Makanan Sekolah)</option>
                                    <option value="Lainnya">Lainnya (Pecahan/Kemasan/Lain)</option>
                                </select>
                                <p v-if="form.errors.sumber_limbah" class="text-[11px] text-rose-500 mt-0.5">
                                    {{ form.errors.sumber_limbah }}
                                </p>
                            </div>
                        </div>

                        <!-- Baris 4: Metode Penanganan & Status Berbahaya -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Metode Penanganan (Pilihan hanya: Donasi, Kompos, Daur Ulang, Pembuangan) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Metode Penanganan <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.metode_penanganan"
                                    required
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold cursor-pointer"
                                >
                                    <option value="Donasi">Donasi (Makanan Layak Konsumsi)</option>
                                    <option value="Kompos">Kompos (Pupuk Organik)</option>
                                    <option value="Daur Ulang">Daur Ulang (Biodiesel / Bank Sampah)</option>
                                    <option value="Pembuangan">Pembuangan (TPA Resmi / IPAL)</option>
                                </select>
                                <p v-if="form.errors.metode_penanganan" class="text-[11px] text-rose-500 mt-0.5">
                                    {{ form.errors.metode_penanganan }}
                                </p>
                            </div>

                            <!-- Status Berbahaya (Pilihan hanya: Tidak Berbahaya, Berbahaya) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Status Berbahaya <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.status_berbahaya"
                                    required
                                    :class="[
                                        'w-full px-3 py-2 text-xs rounded-xl border focus:outline-none focus:ring-2 font-bold cursor-pointer',
                                        form.status_berbahaya === 'Berbahaya'
                                            ? 'border-rose-400 bg-rose-50 text-rose-800 focus:ring-rose-500/20'
                                            : 'border-slate-300 text-slate-700 focus:ring-emerald-500/20',
                                    ]"
                                >
                                    <option value="Tidak Berbahaya">Tidak Berbahaya (Aman)</option>
                                    <option value="Berbahaya">Berbahaya (Minyak Jelantah, Pecahan Kaca, dsb)</option>
                                </select>
                                <p v-if="form.errors.status_berbahaya" class="text-[11px] text-rose-500 mt-0.5">
                                    {{ form.errors.status_berbahaya }}
                                </p>
                            </div>
                        </div>

                        <!-- Baris 5: Keterangan (Opsional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Keterangan Tambahan (Opsional)
                            </label>
                            <textarea
                                v-model="form.keterangan"
                                rows="2"
                                placeholder="Catatan penanganan atau keterangan mitra pengangkut..."
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                            ></textarea>
                        </div>

                        <!-- Tombol Aksi Modal -->
                        <div class="pt-3 border-t border-slate-100 grid grid-cols-2 sm:flex sm:items-center sm:justify-end gap-2 sm:gap-2.5 shrink-0">
                            <button
                                type="button"
                                @click="closeFormModal"
                                class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 cursor-pointer text-center"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white shadow-xs transition-colors disabled:opacity-50 cursor-pointer text-center"
                            >
                                {{ isEditing ? 'Simpan Perubahan' : 'Tambah Catatan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ─── MODAL KONFIRMASI HAPUS (TELEPORTED TO BODY) ─── -->
        <Teleport to="body">
            <div
                v-if="isDeleteModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150 overflow-y-auto"
            >
                <div @click.stop class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md p-5 sm:p-6 animate-in zoom-in-95 duration-150 my-auto">
                    <div class="flex items-center gap-3 text-rose-600 mb-3">
                        <div class="p-2.5 rounded-xl bg-rose-100">
                            <Trash2 class="h-6 w-6" />
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900">Hapus Catatan Limbah?</h4>
                            <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 mb-5 leading-relaxed">
                        Anda yakin ingin menghapus data limbah
                        <strong class="text-slate-900">"{{ deletingItem?.nama_limbah }}"</strong>
                        sebanyak
                        <strong class="text-slate-900">{{ deletingItem?.jumlah }} {{ deletingItem?.satuan }}</strong>
                        tanggal {{ deletingItem?.tanggal_formatted }}?
                    </p>

                    <div class="grid grid-cols-2 sm:flex sm:items-center sm:justify-end gap-2 sm:gap-2.5">
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 cursor-pointer text-center"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="confirmDelete"
                            :disabled="isDeleting"
                            class="px-5 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white shadow-xs transition-colors disabled:opacity-50 cursor-pointer text-center"
                        >
                            {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Data' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── MODAL KONFIRMASI HAPUS SEKALIGUS (BULK DELETE) ─── -->
        <Teleport to="body">
            <div
                v-if="isBulkDeleteModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150 overflow-y-auto"
            >
                <div @click.stop class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md p-5 sm:p-6 animate-in zoom-in-95 duration-150 my-auto">
                    <div class="flex items-center gap-3 text-rose-600 mb-3">
                        <div class="p-2.5 rounded-xl bg-rose-100">
                            <Trash2 class="h-6 w-6" />
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-slate-900">
                                Hapus {{ selectedIds.length }} Catatan Sekaligus?
                            </h4>
                            <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 mb-5 leading-relaxed">
                        Anda yakin ingin menghapus sebanyak
                        <strong class="text-rose-700 text-sm font-black">{{ selectedIds.length }} catatan limbah terpilih</strong>
                        dari sistem? Seluruh data yang dipilih akan dihapus secara permanen dari database.
                    </p>

                    <div class="grid grid-cols-2 sm:flex sm:items-center sm:justify-end gap-2 sm:gap-2.5">
                        <button
                            type="button"
                            @click="closeBulkDeleteModal"
                            class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 cursor-pointer text-center"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="confirmBulkDelete"
                            :disabled="isBulkDeleting"
                            class="px-5 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white shadow-xs transition-colors disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5 text-center"
                        >
                            <Loader2 v-if="isBulkDeleting" class="h-4 w-4 animate-spin shrink-0" />
                            <span>{{ isBulkDeleting ? 'Menghapus...' : 'Ya, Hapus ' + selectedIds.length + ' Catatan' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── MODAL ASISTEN AI RESIDU ─── -->
        <ResiduAiAssistantModal
            v-model="isAiModalOpen"
            @saved="handleAiSaved"
        />
    </AppLayout>
</template>
