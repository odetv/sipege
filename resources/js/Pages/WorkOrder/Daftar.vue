<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Modal.vue";
import DateRangePicker from "@/Components/DateRangePicker.vue";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";
import {
    FileSpreadsheet,
    Plus,
    Search,
    Edit3,
    Sparkles,
    Eye,
    Trash2,
    Calendar,
    Users,
    CheckCircle2,
    AlertCircle,
    AlertTriangle,
    X,
    Filter,
    ChefHat,
    UtensilsCrossed,
    Clock,
    ChevronDown,
    RotateCcw,
} from "lucide-vue-next";
import WorkOrderManualEditModal from "./Partials/WorkOrderManualEditModal.vue";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    periodes: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

// Search & Filter State
const searchQuery = ref("");
const filterMetode = ref("all"); // 'all' | 'sistem' | 'manual'
const filterStatus = ref("all");

// Modal Manual Editor (Gizi & Alergi)
const showManualModal = ref(false);
const selectedManualWo = ref(null);

function openManualEditor(wo) {
    selectedManualWo.value = wo;
    showManualModal.value = true;
}

function closeManualEditor() {
    showManualModal.value = false;
    selectedManualWo.value = null;
}

function handleManualSaved() {
    router.reload({ only: ["workOrdersList"] });
}

function handleEditWo(wo) {
    const targetUuid = wo.uuid || wo.id;
    if (wo.metode_wo === "manual") {
        router.visit(route("work-order.edit-manual", { id: targetUuid }));
    } else {
        router.visit(route("gizi.rancang-menu", { id: targetUuid, step: "work_order" }));
    }
}

// Helper validasi kelengkapan porsi tambahan
const isPorsiTambahanIncomplete = (pt) => {
    if (!pt || typeof pt !== "object") return true;
    const isValEmpty = (v) =>
        v === "" || v === null || v === undefined || isNaN(Number(v));
    return (
        isValEmpty(pt.organoleptik?.pk) ||
        isValEmpty(pt.organoleptik?.pb) ||
        isValEmpty(pt.sampel?.pk) ||
        isValEmpty(pt.sampel?.pb) ||
        isValEmpty(pt.buffer?.pk) ||
        isValEmpty(pt.buffer?.pb)
    );
};

const getTotalPorsiTambahan = (pt) => {
    if (!pt || typeof pt !== "object") return 0;
    if (typeof pt.total === "number") return pt.total;
    const pk = Number(
        pt.total_pk ??
            ((Number(pt.organoleptik?.pk) || 0) +
                (Number(pt.sampel?.pk) || 0) +
                (Number(pt.buffer?.pk) || 0)),
    );
    const pb = Number(
        pt.total_pb ??
            ((Number(pt.organoleptik?.pb) || 0) +
                (Number(pt.sampel?.pb) || 0) +
                (Number(pt.buffer?.pb) || 0)),
    );
    return pk + pb;
};

// State Modal Konfirmasi Hapus Work Order
const showDeleteConfirmModal = ref(false);
const woToDelete = ref(null);
const isDeletingWo = ref(false);

function handleDeleteWo(wo) {
    woToDelete.value = wo;
    showDeleteConfirmModal.value = true;
}

function cancelDeleteWo() {
    showDeleteConfirmModal.value = false;
    woToDelete.value = null;
}

function executeDeleteWo() {
    if (!woToDelete.value) return;
    isDeletingWo.value = true;
    router.delete(route("work-order.destroy", woToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeletingWo.value = false;
            showDeleteConfirmModal.value = false;
            woToDelete.value = null;
        },
        onError: () => {
            isDeletingWo.value = false;
        },
    });
}

// ─── Rentang Tanggal & Periode SPPG Filter State ─────────────────────────────
const todayStr = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
});

function getDefaultStartDate() {
    return todayStr.value;
}

function getDefaultEndDate(mode, startStr) {
    const base = startStr ? new Date(startStr + "T00:00:00") : new Date();
    const daysToAdd = mode === "periodik" ? 13 : 27; // 14 hari atau 28 hari
    const end = new Date(base);
    end.setDate(base.getDate() + daysToAdd);
    const y = end.getFullYear();
    const m = String(end.getMonth() + 1).padStart(2, "0");
    const d = String(end.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

// Mode Skala: 'bulanan' (28 Hari) atau 'periodik' (14 Hari) atau 'custom'
const modeSkala = ref("bulanan");
const tanggalMulai = ref(getDefaultStartDate());
const tanggalSelesai = ref(getDefaultEndDate(modeSkala.value, tanggalMulai.value));
const selectedPeriodeId = ref("all");
const isFilterAllTime = ref(false);

// Helper cari periode yang cocok dengan rentang tanggal
function findMatchingPeriode(startStr, endStr) {
    if (!props.periodes || !startStr || !endStr) return null;
    const s = String(startStr).substring(0, 10);
    const e = String(endStr).substring(0, 10);
    return (
        props.periodes.find((p) => {
            const pStart = p.tanggal_mulai ? p.tanggal_mulai.substring(0, 10) : "";
            const pEnd = p.tanggal_selesai ? p.tanggal_selesai.substring(0, 10) : "";
            return pStart === s && pEnd === e;
        }) || null
    );
}

// Inisialisasi awal kecocokan periode jika ada
const matchedInitial = findMatchingPeriode(tanggalMulai.value, tanggalSelesai.value);
if (matchedInitial) {
    selectedPeriodeId.value = String(matchedInitial.id);
}

// State DateRangePicker (Dua Bulan Menyatu)
const isDatePickerOpen = ref(false);
const datePickerRange = ref({
    start: tanggalMulai.value,
    end: tanggalSelesai.value,
});

watch([tanggalMulai, tanggalSelesai], () => {
    datePickerRange.value = {
        start: tanggalMulai.value,
        end: tanggalSelesai.value,
    };
});

// Dynamic Date Columns (Daftar Tanggal dalam Rentang)
const dateColumns = computed(() => {
    if (!tanggalMulai.value || !tanggalSelesai.value) return [];
    const list = [];
    let cur = new Date(tanggalMulai.value + "T00:00:00");
    const end = new Date(tanggalSelesai.value + "T00:00:00");
    let count = 0;
    while (cur <= end && count < 60) {
        const y = cur.getFullYear();
        const m = String(cur.getMonth() + 1).padStart(2, "0");
        const d = String(cur.getDate()).padStart(2, "0");
        list.push(`${y}-${m}-${d}`);
        cur.setDate(cur.getDate() + 1);
        count++;
    }
    return list;
});

const is14HariActive = computed(() => !isFilterAllTime.value && dateColumns.value.length === 14);
const is28HariActive = computed(() => !isFilterAllTime.value && dateColumns.value.length === 28);

const selectedPeriode = computed(() => {
    if (!selectedPeriodeId.value || selectedPeriodeId.value === "all") return null;
    return props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value)) || null;
});

const labelSiklus = computed(() => {
    if (isFilterAllTime.value) return "Menampilkan Semua Arsip";
    if (selectedPeriode.value) {
        return `Siklus: Periode ${selectedPeriode.value.nomor_periode} (${dateColumns.value.length} Hari Kerja)`;
    }
    if (dateColumns.value.length === 14) return "Siklus: Periodik (14 Hari Kerja)";
    if (dateColumns.value.length === 28) return "Siklus: Bulanan (28 Hari Kerja)";
    return `Siklus: Kustom (${dateColumns.value.length} Hari Kerja)`;
});

function setModeSkala(newMode) {
    isFilterAllTime.value = false;
    modeSkala.value = newMode;
    const newEnd = getDefaultEndDate(newMode, tanggalMulai.value);
    tanggalSelesai.value = newEnd;

    const matched = findMatchingPeriode(tanggalMulai.value, newEnd);
    if (matched) {
        selectedPeriodeId.value = String(matched.id);
    } else {
        selectedPeriodeId.value = "all";
    }
}

function onPeriodeSelectChange() {
    if (selectedPeriodeId.value === "all") {
        return;
    }
    const p = props.periodes?.find((it) => String(it.id) === String(selectedPeriodeId.value));
    if (p && p.tanggal_mulai && p.tanggal_selesai) {
        isFilterAllTime.value = false;
        tanggalMulai.value = p.tanggal_mulai.substring(0, 10);
        tanggalSelesai.value = p.tanggal_selesai.substring(0, 10);
        modeSkala.value = "periodik";
    }
}

function onApplyDateRange(newRange) {
    if (newRange && newRange.start && newRange.end) {
        isFilterAllTime.value = false;
        tanggalMulai.value = newRange.start;
        tanggalSelesai.value = newRange.end;

        const matched = findMatchingPeriode(newRange.start, newRange.end);
        if (matched) {
            selectedPeriodeId.value = String(matched.id);
            modeSkala.value = "periodik";
        } else {
            selectedPeriodeId.value = "all";
            const diffDays =
                Math.round(
                    (new Date(newRange.end + "T00:00:00") - new Date(newRange.start + "T00:00:00")) /
                        (1000 * 60 * 60 * 24)
                ) + 1;
            if (diffDays === 14) modeSkala.value = "periodik";
            else if (diffDays === 28) modeSkala.value = "bulanan";
            else modeSkala.value = "custom";
        }

        isDatePickerOpen.value = false;
    }
}

function resetFilterSemua() {
    isFilterAllTime.value = true;
    selectedPeriodeId.value = "all";
}

// Filtered List
const filteredWorkOrders = computed(() => {
    let list = props.workOrdersList || [];

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter((wo) => {
            const no = (wo.nomor_wo || "").toLowerCase();
            const nama = (wo.nama_menu || "").toLowerCase();
            const tgl = (wo.tanggal_distribusi || "").toLowerCase();
            return no.includes(q) || nama.includes(q) || tgl.includes(q);
        });
    }

    if (filterMetode.value !== "all") {
        list = list.filter((wo) => wo.metode_wo === filterMetode.value);
    }

    if (filterStatus.value !== "all") {
        list = list.filter((wo) => {
            const s = (wo.status || "Draft").toLowerCase();
            return s === filterStatus.value.toLowerCase();
        });
    }

    if (!isFilterAllTime.value) {
        if (tanggalMulai.value) {
            list = list.filter((wo) => {
                const tgl = String(wo.tanggal_distribusi || "").substring(0, 10);
                return tgl >= tanggalMulai.value;
            });
        }
        if (tanggalSelesai.value) {
            list = list.filter((wo) => {
                const tgl = String(wo.tanggal_distribusi || "").substring(0, 10);
                return tgl <= tanggalSelesai.value;
            });
        }
    }

    return list;
});

// Stats Summary
const totalWo = computed(() => filteredWorkOrders.value.length);
const totalSistem = computed(
    () => filteredWorkOrders.value.filter((w) => w.metode_wo !== "manual").length
);
const totalManual = computed(
    () => filteredWorkOrders.value.filter((w) => w.metode_wo === "manual").length
);
</script>

<template>
    <AppLayout
        title="Daftar Work Order"
        subtitle="Kelola & pantau seluruh arsip Work Order produksi MBG SPPG"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Work Order - Daftar WO" />

        <div class="space-y-6">
            <!-- Header Halaman & Tombol Aksi -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-2xs"
            >
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                        <FileSpreadsheet class="h-5 w-5 text-primary" />
                        <span>Daftar Work Order Produksi MBG</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Menampilkan seluruh riwayat dan status produksi Work Order SPPG.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('work-order.buat')"
                        class="px-4 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white font-bold text-xs flex items-center gap-1.5 shadow-xs transition-all cursor-pointer shrink-0"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Buat WO Baru</span>
                    </Link>
                </div>
            </div>

            <!-- Flash Alert (Success / Error) -->
            <div
                v-if="page.props.flash?.success"
                class="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs font-bold flex items-center justify-between gap-2 shadow-2xs animate-in fade-in"
            >
                <div class="flex items-center gap-2">
                    <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0" />
                    <span>{{ page.props.flash.success }}</span>
                </div>
            </div>

            <div
                v-if="page.props.flash?.error"
                class="p-4 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 text-xs font-bold flex items-center justify-between gap-2 shadow-2xs animate-in fade-in"
            >
                <div class="flex items-center gap-2">
                    <AlertCircle class="h-5 w-5 text-rose-600 shrink-0" />
                    <span>{{ page.props.flash.error }}</span>
                </div>
            </div>

            <!-- Quick Stats & Filter Bar -->
            <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200 shadow-2xs space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pb-3 border-b border-slate-100">
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase">Total Work Order</span>
                            <h4 class="text-lg font-black text-slate-900">{{ totalWo }}</h4>
                        </div>
                        <FileSpreadsheet class="h-6 w-6 text-slate-400" />
                    </div>

                    <div class="p-3 rounded-2xl bg-blue-50/70 border border-blue-100 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-blue-700 uppercase">Metode Sistem</span>
                            <h4 class="text-lg font-black text-blue-900">{{ totalSistem }}</h4>
                        </div>
                        <ChefHat class="h-6 w-6 text-blue-500" />
                    </div>

                    <div class="p-3 rounded-2xl bg-amber-50/70 border border-amber-100 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-amber-700 uppercase">Metode Manual</span>
                            <h4 class="text-lg font-black text-amber-900">{{ totalManual }}</h4>
                        </div>
                        <Sparkles class="h-6 w-6 text-amber-500" />
                    </div>
                </div>

                <!-- Baris 1: Mode Skala, Dropdown Periode & Switcher -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Switcher Skala (Bulanan 28 Hari vs Periodik 14 Hari) -->
                        <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200/80">
                            <button
                                type="button"
                                @click="setModeSkala('bulanan')"
                                :class="[
                                    'px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                    is28HariActive
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
                                @click="setModeSkala('periodik')"
                                :class="[
                                    'px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                    is14HariActive
                                        ? 'bg-white text-teal-700 shadow-xs ring-1 ring-teal-500/20'
                                        : 'text-slate-600 hover:text-slate-900',
                                ]"
                            >
                                <Calendar class="h-3.5 w-3.5" />
                                <span class="hidden xs:inline">Periodik (14 Hari)</span>
                                <span class="xs:hidden">14 Hari</span>
                            </button>
                        </div>

                        <!-- Dropdown Periode SPPG -->
                        <div class="relative flex-1 sm:flex-none">
                            <select
                                v-model="selectedPeriodeId"
                                @change="onPeriodeSelectChange"
                                class="w-full sm:w-auto pl-2.5 pr-8 py-1.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-white cursor-pointer"
                            >
                                <option value="all">Pilih Periode SPPG...</option>
                                <option v-for="p in periodes" :key="p.id" :value="String(p.id)">
                                    {{ p.label || ('Periode ' + p.nomor_periode) }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Baris 2: Unified Date Range Picker Menyatu dengan Popover Kalender Dua Bulan -->
                <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 p-2.5 sm:p-3 rounded-2xl bg-gradient-to-r from-emerald-50/90 via-teal-50/60 to-slate-50 border border-emerald-200 shadow-2xs">
                    <!-- Left: Unified Range Capsule Button -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-emerald-950 flex items-center gap-1 shrink-0">
                            <Calendar class="h-3.5 w-3.5 text-emerald-600" />
                            <span class="hidden md:inline">Rentang Terpilih:</span>
                        </span>

                        <!-- Interactive Range Capsule Trigger -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="isDatePickerOpen = !isDatePickerOpen"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-emerald-300 hover:border-emerald-500 shadow-2xs text-xs font-bold text-slate-800 transition-all cursor-pointer group"
                                title="Klik untuk membuka pemilih rentang tanggal kalender"
                            >
                                <span class="text-emerald-700 font-extrabold">{{ formatTanggalIndo(tanggalMulai) }}</span>
                                <span class="text-emerald-500 font-black">➜</span>
                                <span class="text-emerald-700 font-extrabold">{{ formatTanggalIndo(tanggalSelesai) }}</span>
                                <ChevronDown class="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600 transition-transform" :class="{ 'rotate-180': isDatePickerOpen }" />
                            </button>

                            <!-- Backdrop Click Outside -->
                            <div
                                v-if="isDatePickerOpen"
                                class="fixed inset-0 z-40 bg-black/20 sm:bg-transparent backdrop-blur-[1px] sm:backdrop-blur-none transition-opacity"
                                @click="isDatePickerOpen = false"
                            ></div>

                            <!-- Popover Kalender Dua Bulan Sesuai Desain Rekap Kehadiran -->
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
                            {{ dateColumns.length }} Hari Kerja
                        </span>
                    </div>

                    <!-- Right: Info Siklus & Opsi Lihat Semua Arsip -->
                    <div class="flex items-center gap-2 text-[11px] font-semibold text-emerald-900/90">
                        <div class="hidden lg:flex items-center gap-1.5">
                            <Sparkles class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                            <span>{{ labelSiklus }}</span>
                        </div>
                        <button
                            v-if="!isFilterAllTime"
                            type="button"
                            @click="resetFilterSemua"
                            class="text-[11px] font-medium text-emerald-800 hover:text-emerald-950 underline cursor-pointer ml-auto sm:ml-0"
                            title="Tampilkan semua data tanpa filter rentang tanggal"
                        >
                            Lihat Semua Arsip
                        </button>
                        <button
                            v-else
                            type="button"
                            @click="isFilterAllTime = false"
                            class="text-[11px] font-medium text-emerald-800 hover:text-emerald-950 underline cursor-pointer ml-auto sm:ml-0"
                            title="Terapkan kembali filter rentang tanggal"
                        >
                            Terapkan Rentang
                        </button>
                    </div>
                </div>

                <!-- Baris 3: Search and Dropdown Filter (Metode & Status) -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-1 border-t border-slate-100">
                    <div class="relative w-full sm:w-80">
                        <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nomor WO, menu, atau tanggal..."
                            class="w-full pl-9 pr-7 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400 font-medium"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <X class="h-3 w-3" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select
                            v-model="filterMetode"
                            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none cursor-pointer"
                        >
                            <option value="all">Semua Metode</option>
                            <option value="sistem">Metode Sistem</option>
                            <option value="manual">Metode Manual</option>
                        </select>

                        <select
                            v-model="filterStatus"
                            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none cursor-pointer"
                        >
                            <option value="all">Semua Status</option>
                            <option value="Draft">Draft</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredWorkOrders.length === 0" class="py-16 text-center text-slate-400 space-y-2">
                    <FileSpreadsheet class="h-12 w-12 mx-auto text-slate-300" />
                    <p class="text-sm font-bold text-slate-600">
                        {{
                            searchQuery || filterMetode !== "all" || filterStatus !== "all" || !isFilterAllTime
                                ? "Tidak ada Work Order yang cocok dengan filter atau rentang tanggal ini."
                                : "Belum ada Work Order yang terdaftar."
                        }}
                    </p>
                    <p class="text-xs text-slate-400">
                        Silakan sesuaikan rentang tanggal atau buat perencanaan Work Order baru.
                    </p>
                    <div class="pt-2 flex items-center justify-center gap-2">
                        <button
                            v-if="!isFilterAllTime"
                            type="button"
                            @click="resetFilterSemua"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
                        >
                            <RotateCcw class="h-3.5 w-3.5 text-slate-500" />
                            <span>Lihat Semua Arsip</span>
                        </button>
                        <Link
                            :href="route('work-order.buat')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl shadow-xs hover:bg-primary/90"
                        >
                            <Plus class="h-4 w-4" />
                            <span>Buat WO Baru</span>
                        </Link>
                    </div>
                </div>

                <!-- Card Grid List -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                    <div
                        v-for="wo in filteredWorkOrders"
                        :key="wo.id"
                        class="p-4 rounded-2xl border border-slate-200 bg-white hover:border-slate-300 hover:shadow-xs transition-all space-y-3 flex flex-col justify-between"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-xs font-black text-slate-900">
                                    {{ wo.nomor_wo }}
                                </span>
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider',
                                        wo.metode_wo === 'manual'
                                            ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                            : 'bg-blue-100 text-blue-900 border border-blue-300',
                                    ]"
                                >
                                    {{ wo.metode_wo === 'manual' ? '✍️ Manual' : '⚙️ Sistem' }}
                                </span>
                            </div>

                            <h5 class="text-sm font-black text-slate-800 leading-snug line-clamp-2">
                                {{ wo.nama_menu }}
                            </h5>

                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                                <div>
                                    <span>📅 Tanggal:</span>
                                    <strong class="block text-slate-700 font-bold">{{ formatTanggalIndo(wo.tanggal_distribusi) }}</strong>
                                </div>
                                <div>
                                    <span>🎯 Sasaran PM:</span>
                                    <strong class="block text-slate-700 font-bold">{{ wo.total_pm }} Porsi</strong>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] text-slate-400 font-bold">Status:</span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10.5px] font-bold uppercase',
                                            wo.status === 'Disetujui' || wo.status === 'Completed'
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : wo.status === 'In Progress'
                                                    ? 'bg-blue-100 text-blue-800'
                                                    : 'bg-slate-100 text-slate-700',
                                        ]"
                                    >
                                        {{ wo.status || 'Draft' }}
                                    </span>
                                </div>

                                <!-- Badge Indikator Porsi Tambahan -->
                                <span
                                    v-if="isPorsiTambahanIncomplete(wo.porsi_tambahan)"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200"
                                    title="Porsi tambahan belum diisi lengkap (wajib dilengkapi)"
                                >
                                    <AlertCircle class="h-3 w-3 text-rose-500 shrink-0" />
                                    <span>Porsi Tambahan Kosong</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                    title="Total porsi tambahan produksi yang telah dialokasikan"
                                >
                                    <span>⚡ Tambahan: {{ getTotalPorsiTambahan(wo.porsi_tambahan) }} Porsi</span>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="handleEditWo(wo)"
                                :class="[
                                    'p-2 rounded-xl transition-colors cursor-pointer text-xs font-bold flex items-center gap-1.5',
                                    isPorsiTambahanIncomplete(wo.porsi_tambahan)
                                        ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-black'
                                        : 'bg-slate-100 hover:bg-slate-200 text-slate-700',
                                ]"
                                :title="wo.metode_wo === 'manual' ? 'Edit Work Order Manual' : 'Edit di Rancang Menu'"
                            >
                                <AlertCircle
                                    v-if="isPorsiTambahanIncomplete(wo.porsi_tambahan)"
                                    class="h-3.5 w-3.5 text-rose-600 shrink-0 animate-pulse"
                                />
                                <Edit3 v-else class="h-3.5 w-3.5" />
                                <span>{{ isPorsiTambahanIncomplete(wo.porsi_tambahan) ? 'Lengkapi Porsi' : 'Edit' }}</span>
                            </button>

                            <button
                                type="button"
                                @click="handleDeleteWo(wo)"
                                class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 transition-colors cursor-pointer"
                                title="Hapus Work Order"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Work Order -->
        <Modal
            :show="showDeleteConfirmModal"
            @close="cancelDeleteWo"
            max-width="md"
        >
            <div class="p-5 sm:p-6 space-y-4">
                <div class="flex items-start gap-3.5">
                    <div class="h-11 w-11 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 border border-rose-200 shadow-2xs">
                        <Trash2 class="h-6 w-6 stroke-[2.2]" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-black text-slate-900 leading-snug">
                            Hapus Work Order
                        </h3>
                        <p class="text-xs font-semibold text-slate-500">
                            Konfirmasi Penghapusan Dokumen Work Order
                        </p>
                    </div>
                </div>

                <div class="bg-rose-50/80 border border-rose-200/90 rounded-2xl p-4 space-y-2 text-xs text-rose-950 leading-relaxed shadow-2xs">
                    <p class="font-bold">
                        Apakah Anda yakin ingin menghapus Work Order ini?
                    </p>
                    <div v-if="woToDelete" class="p-2.5 bg-white rounded-xl border border-rose-200 text-slate-800 space-y-1">
                        <div class="font-extrabold text-rose-900 text-xs">{{ woToDelete.nomor_wo }}</div>
                        <div class="text-[11px] font-semibold text-slate-600">{{ woToDelete.nama_menu || 'Belum ada nama menu' }}</div>
                        <div class="text-[10px] text-slate-400">Tanggal: {{ woToDelete.tanggal_distribusi }} &bull; Metode: {{ woToDelete.metode_wo === 'manual' ? 'Manual' : 'Sistem' }}</div>
                    </div>
                    <p class="text-slate-600 text-[11.5px]">
                        Seluruh item menu, formulasi bahan, dan data terkait dalam Work Order ini akan dihapus secara permanen dari sistem.
                    </p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-3 border-t border-slate-100">
                    <button
                        type="button"
                        @click="cancelDeleteWo"
                        :disabled="isDeletingWo"
                        class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 rounded-xl transition cursor-pointer text-center"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDeleteWo"
                        :disabled="isDeletingWo"
                        class="w-full sm:w-auto px-4 py-2.5 text-xs font-black text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition cursor-pointer shadow-xs text-center disabled:opacity-50"
                    >
                        {{ isDeletingWo ? 'Menghapus...' : 'Ya, Hapus Work Order' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal Manual Edit (Gizi & Alergi) -->
        <WorkOrderManualEditModal
            :show="showManualModal"
            :work-order="selectedManualWo"
            :kelompok-list="kelompokList"
            @close="closeManualEditor"
            @saved="handleManualSaved"
        />
    </AppLayout>
</template>
