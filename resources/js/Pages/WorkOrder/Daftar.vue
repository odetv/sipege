<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Modal.vue";
import DateRangePicker from "@/Components/DateRangePicker.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";
import {
    FileSpreadsheet,
    Archive,
    Plus,
    Search,
    Edit3,
    Cpu,
    PenTool,
    Eye,
    Trash2,
    Calendar,
    CalendarDays,
    Users,
    CheckCircle2,
    AlertCircle,
    AlertTriangle,
    X,
    Filter,
    UtensilsCrossed,
    Clock,
    ChevronDown,
    ChevronUp,
    RotateCcw,
    CheckSquare,
    EyeOff,
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

// ─── State & Method Pemilihan Banyak WO (Bulk Select & Bulk Delete) ─────────
const selectedWoIds = ref([]);
const showBulkDeleteModal = ref(false);
const isDeletingBulk = ref(false);

const getWoKey = (wo) => String(wo.uuid || wo.id);

const isWoSelected = (wo) => {
    return selectedWoIds.value.includes(getWoKey(wo));
};

const toggleSelectWo = (wo) => {
    const key = getWoKey(wo);
    const idx = selectedWoIds.value.indexOf(key);
    if (idx > -1) {
        selectedWoIds.value.splice(idx, 1);
    } else {
        selectedWoIds.value.push(key);
    }
};

const isAllSelected = computed(() => {
    return (
        filteredWorkOrders.value.length > 0 &&
        filteredWorkOrders.value.every((wo) =>
            selectedWoIds.value.includes(getWoKey(wo)),
        )
    );
});

const isSomeSelected = computed(() => {
    return selectedWoIds.value.length > 0 && !isAllSelected.value;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedWoIds.value = [];
    } else {
        selectedWoIds.value = filteredWorkOrders.value.map(getWoKey);
    }
};

const clearSelection = () => {
    selectedWoIds.value = [];
};

const showAllBulkItems = ref(false);

const selectedWorkOrdersList = computed(() => {
    const list = props.workOrdersList || [];
    return list.filter((wo) => selectedWoIds.value.includes(getWoKey(wo)));
});

const displayedBulkWoList = computed(() => {
    if (showAllBulkItems.value) {
        return selectedWorkOrdersList.value;
    }
    return selectedWorkOrdersList.value.slice(0, 5);
});

function openBulkDeleteModal() {
    if (selectedWoIds.value.length === 0) return;
    showAllBulkItems.value = false;
    showBulkDeleteModal.value = true;
}

function cancelBulkDelete() {
    showBulkDeleteModal.value = false;
    showAllBulkItems.value = false;
}

function executeBulkDeleteWo() {
    if (selectedWoIds.value.length === 0) return;
    isDeletingBulk.value = true;
    router.delete(route("work-order.bulk-destroy"), {
        data: { ids: selectedWoIds.value },
        preserveScroll: true,
        onSuccess: () => {
            selectedWoIds.value = [];
            showBulkDeleteModal.value = false;
            isDeletingBulk.value = false;
        },
        onError: () => {
            isDeletingBulk.value = false;
        },
        onFinish: () => {
            isDeletingBulk.value = false;
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

// Urutan periode dari terbaru ke terlama (nomor periode terbesar atau tanggal_mulai paling baru)
const sortedPeriodes = computed(() => {
    if (!props.periodes || props.periodes.length === 0) return [];
    return [...props.periodes].sort((a, b) => {
        const numA = Number(a.nomor_periode) || 0;
        const numB = Number(b.nomor_periode) || 0;
        if (numB !== numA) return numB - numA;
        return String(b.tanggal_mulai || "").localeCompare(String(a.tanggal_mulai || ""));
    });
});

// Periode paling terbaru
const latestPeriode = computed(() => {
    return sortedPeriodes.value[0] || null;
});

const latestPeriodeLabel = computed(() => {
    if (!latestPeriode.value) return "";
    return latestPeriode.value.label || `Periode ${latestPeriode.value.nomor_periode}`;
});

// Helper cek apakah suatu periode mencakup tanggal tertentu
function isPeriodeContainingDate(periode, dateStr) {
    if (!periode || !periode.tanggal_mulai || !periode.tanggal_selesai || !dateStr) return false;
    const s = String(periode.tanggal_mulai).substring(0, 10);
    const e = String(periode.tanggal_selesai).substring(0, 10);
    return dateStr >= s && dateStr <= e;
}

// Inisialisasi awal: tampilkan periode terbaru bilamana rentang periodenya masih dalam hari ini,
// bila tidak maka tampilkan saja per hari ini
function getInitialFilterState() {
    const today = todayStr.value;
    const periodesList = props.periodes || [];
    let latest = null;
    if (periodesList.length > 0) {
        const sorted = [...periodesList].sort((a, b) => {
            const numA = Number(a.nomor_periode) || 0;
            const numB = Number(b.nomor_periode) || 0;
            if (numB !== numA) return numB - numA;
            return String(b.tanggal_mulai || "").localeCompare(String(a.tanggal_mulai || ""));
        });
        latest = sorted[0];
    }

    if (latest && isPeriodeContainingDate(latest, today)) {
        return {
            mode: "periode",
            start: String(latest.tanggal_mulai).substring(0, 10),
            end: String(latest.tanggal_selesai).substring(0, 10),
            periodeId: String(latest.id),
        };
    }

    return {
        mode: "hari_ini",
        start: today,
        end: today,
        periodeId: latest ? String(latest.id) : "all",
    };
}

const initialFilter = getInitialFilterState();
const activeFilterMode = ref(initialFilter.mode); // 'hari_ini' | 'periode' | 'rentang'
const tanggalMulai = ref(initialFilter.start);
const tanggalSelesai = ref(initialFilter.end);
const selectedPeriodeId = ref(initialFilter.periodeId);
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

// Reset otomatis pilihan WO jika berganti rentang tanggal, periode, atau mode filter
watch(
    [
        tanggalMulai,
        tanggalSelesai,
        selectedPeriodeId,
        isFilterAllTime,
        activeFilterMode,
    ],
    () => {
        if (selectedWoIds.value.length > 0) {
            selectedWoIds.value = [];
        }
    },
);

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

const isHariIniActive = computed(() => {
    return (
        !isFilterAllTime.value &&
        activeFilterMode.value === "hari_ini" &&
        tanggalMulai.value === todayStr.value &&
        tanggalSelesai.value === todayStr.value
    );
});

const selectedPeriode = computed(() => {
    if (!selectedPeriodeId.value || selectedPeriodeId.value === "all") return null;
    return props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value)) || null;
});

function pilihFilterMode(mode) {
    isFilterAllTime.value = false;

    if (mode === "hari_ini") {
        activeFilterMode.value = "hari_ini";
        isDatePickerOpen.value = false;
        tanggalMulai.value = todayStr.value;
        tanggalSelesai.value = todayStr.value;
    } else if (mode === "periode") {
        activeFilterMode.value = "periode";
        isDatePickerOpen.value = false;
        // Default ke periode terakhir jika belum valid
        let targetPeriode = null;
        if (selectedPeriodeId.value && selectedPeriodeId.value !== "all") {
            targetPeriode = props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value));
        }
        if (!targetPeriode && latestPeriode.value) {
            targetPeriode = latestPeriode.value;
            selectedPeriodeId.value = String(targetPeriode.id);
        }
        if (targetPeriode && targetPeriode.tanggal_mulai && targetPeriode.tanggal_selesai) {
            tanggalMulai.value = String(targetPeriode.tanggal_mulai).substring(0, 10);
            tanggalSelesai.value = String(targetPeriode.tanggal_selesai).substring(0, 10);
        }
    } else if (mode === "rentang") {
        activeFilterMode.value = "rentang";
        isDatePickerOpen.value = false;
    }
}

function onPeriodeSelectChange() {
    if (!selectedPeriodeId.value || selectedPeriodeId.value === "all") return;
    const p = props.periodes?.find((it) => String(it.id) === String(selectedPeriodeId.value));
    if (p && p.tanggal_mulai && p.tanggal_selesai) {
        tanggalMulai.value = String(p.tanggal_mulai).substring(0, 10);
        tanggalSelesai.value = String(p.tanggal_selesai).substring(0, 10);
    }
}

function onApplyDateRange(newRange) {
    if (newRange && newRange.start && newRange.end) {
        isFilterAllTime.value = false;
        activeFilterMode.value = "rentang";
        tanggalMulai.value = newRange.start;
        tanggalSelesai.value = newRange.end;

        const matched = findMatchingPeriode(newRange.start, newRange.end);
        if (matched) {
            selectedPeriodeId.value = String(matched.id);
        } else {
            selectedPeriodeId.value = "all";
        }
        isDatePickerOpen.value = false;
    }
}

function resetFilterSemua() {
    isFilterAllTime.value = true;
    isDatePickerOpen.value = false;
}

function kembalikanFilterSemula() {
    isFilterAllTime.value = false;
    isDatePickerOpen.value = false;

    if (activeFilterMode.value === "periode") {
        let p = props.periodes?.find((it) => String(it.id) === String(selectedPeriodeId.value));
        if (!p && latestPeriode.value) {
            p = latestPeriode.value;
            selectedPeriodeId.value = String(p.id);
        }
        if (p && p.tanggal_mulai && p.tanggal_selesai) {
            tanggalMulai.value = String(p.tanggal_mulai).substring(0, 10);
            tanggalSelesai.value = String(p.tanggal_selesai).substring(0, 10);
        }
    } else if (activeFilterMode.value === "hari_ini") {
        tanggalMulai.value = todayStr.value;
        tanggalSelesai.value = todayStr.value;
    }
}

watch(
    () => activeFilterMode.value,
    (mode) => {
        if (mode === "periode") {
            const exists = sortedPeriodes.value.some((p) => String(p.id) === String(selectedPeriodeId.value));
            if (!exists && latestPeriode.value) {
                selectedPeriodeId.value = String(latestPeriode.value.id);
                if (latestPeriode.value.tanggal_mulai && latestPeriode.value.tanggal_selesai) {
                    tanggalMulai.value = String(latestPeriode.value.tanggal_mulai).substring(0, 10);
                    tanggalSelesai.value = String(latestPeriode.value.tanggal_selesai).substring(0, 10);
                }
            }
        }
    }
);

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

// Stats Summary (Menghitung seluruh data yang ada)
const totalWo = computed(() => (props.workOrdersList || []).length);
const totalSistem = computed(
    () => (props.workOrdersList || []).filter((w) => w.metode_wo !== "manual").length
);
const totalManual = computed(
    () => (props.workOrdersList || []).filter((w) => w.metode_wo === "manual").length
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
                        <Cpu class="h-6 w-6 text-blue-500" />
                    </div>

                    <div class="p-3 rounded-2xl bg-amber-50/70 border border-amber-100 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-amber-700 uppercase">Metode Manual</span>
                            <h4 class="text-lg font-black text-amber-900">{{ totalManual }}</h4>
                        </div>
                        <PenTool class="h-6 w-6 text-amber-500" />
                    </div>
                </div>

                <!-- Filter Waktu: Komponen Reusable PeriodDateFilterBar -->
                <PeriodDateFilterBar
                    v-model:startDate="tanggalMulai"
                    v-model:endDate="tanggalSelesai"
                    v-model:mode="activeFilterMode"
                    v-model:periodeId="selectedPeriodeId"
                    v-model:isAllTime="isFilterAllTime"
                    :periodes="periodes"
                />

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

                <!-- Card Grid List & Bulk Selection Control -->
                <div v-else class="space-y-3 pt-2">
                    <!-- Bulk Selection Control Bar -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 sm:px-4 rounded-xl border transition-all"
                        :class="[
                            selectedWoIds.length > 0
                                ? 'bg-rose-50/80 border-rose-300 text-rose-950 shadow-2xs'
                                : 'bg-slate-50 border-slate-200/80 text-slate-700',
                        ]"
                    >
                        <div class="flex items-center gap-3 flex-wrap">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 cursor-pointer"
                                />
                                <span class="text-xs font-black">
                                    {{ isAllSelected ? "Batalkan Pilih Semua" : "Pilih Semua WO" }}
                                </span>
                            </label>
                            <span class="text-[11px] text-slate-300">|</span>
                            <span class="text-xs font-semibold text-slate-600">
                                Total: <strong class="text-slate-800">{{ filteredWorkOrders.length }}</strong> WO
                            </span>
                            <span
                                v-if="selectedWoIds.length > 0"
                                class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-200/90 text-rose-900 border border-rose-300 flex items-center gap-1.5 shadow-2xs"
                            >
                                <CheckSquare class="h-3 w-3" />
                                <span>{{ selectedWoIds.length }} Terpilih</span>
                            </span>
                        </div>

                        <!-- Action Buttons saat ada WO yang dipilih -->
                        <div v-if="selectedWoIds.length > 0" class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="clearSelection"
                                class="px-3 py-1.5 text-xs font-bold text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl transition cursor-pointer shadow-2xs"
                            >
                                Batal Pilihan
                            </button>
                            <button
                                type="button"
                                @click="openBulkDeleteModal"
                                class="px-4 py-1.5 text-xs font-black text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition cursor-pointer shadow-xs flex items-center gap-1.5"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                <span>Hapus Sekaligus ({{ selectedWoIds.length }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Grid List Kartu Work Order -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="wo in filteredWorkOrders"
                            :key="wo.id"
                            :class="[
                                'p-4 rounded-2xl border transition-all space-y-3 flex flex-col justify-between cursor-pointer select-none',
                                isWoSelected(wo)
                                    ? 'border-rose-400 bg-rose-50/20 ring-2 ring-rose-400/30 shadow-xs'
                                    : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-xs',
                            ]"
                            @click="toggleSelectWo(wo)"
                        >
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <input
                                            type="checkbox"
                                            :checked="isWoSelected(wo)"
                                            @change.stop="toggleSelectWo(wo)"
                                            @click.stop
                                            class="h-4 w-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 cursor-pointer transition-all"
                                            :title="isWoSelected(wo) ? 'Batal pilih WO ini' : 'Pilih WO ini untuk aksi massal'"
                                        />
                                        <span class="font-mono text-xs font-black text-slate-900">
                                            {{ wo.nomor_wo }}
                                        </span>
                                    </div>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider shrink-0',
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

                            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100" @click.stop>
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
                                    title="Hapus Work Order Ini"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
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

        <!-- Modal Konfirmasi Hapus Sekaligus (Bulk Delete) -->
        <Modal
            :show="showBulkDeleteModal"
            @close="cancelBulkDelete"
            max-width="lg"
        >
            <div class="p-5 sm:p-6 space-y-4">
                <div class="flex items-start gap-3.5">
                    <div class="h-11 w-11 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 border border-rose-200 shadow-2xs">
                        <Trash2 class="h-6 w-6 stroke-[2.2]" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-black text-slate-900 leading-snug">
                            Hapus {{ selectedWoIds.length }} Work Order Sekaligus?
                        </h3>
                        <p class="text-xs font-semibold text-slate-500">
                            Konfirmasi Penghapusan Massal Dokumen Work Order
                        </p>
                    </div>
                </div>

                <div class="bg-rose-50/80 border border-rose-200/90 rounded-2xl p-4 space-y-2.5 text-xs text-rose-950 leading-relaxed shadow-2xs">
                    <p class="font-bold">
                        Apakah Anda yakin ingin menghapus sekaligus <strong>{{ selectedWoIds.length }} Work Order</strong> terpilih berikut ini?
                    </p>
                    <p class="text-slate-600 text-[11.5px]">
                        Tindakan ini akan menghapus seluruh data perencanaan produksi, alokasi porsi, dan kebutuhan bahan baku terkait secara permanen.
                    </p>

                    <!-- Preview Daftar WO yang dipilih dengan Opsi Lihat Lainnya -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-0.5">
                            <span>Daftar Work Order Terpilih:</span>
                            <button
                                v-if="selectedWorkOrdersList.length > 5"
                                type="button"
                                @click="showAllBulkItems = !showAllBulkItems"
                                class="text-rose-700 hover:text-rose-900 underline cursor-pointer text-[10.5px] flex items-center gap-1 font-black transition-colors"
                            >
                                <Eye v-if="!showAllBulkItems" class="h-3 w-3" />
                                <EyeOff v-else class="h-3 w-3" />
                                <span>{{ showAllBulkItems ? 'Tampilkan 5 Saja' : `Lihat Semua (${selectedWorkOrdersList.length} WO)` }}</span>
                            </button>
                        </div>

                        <div class="p-2.5 bg-white rounded-xl border border-rose-200 divide-y divide-slate-100 max-h-60 sm:max-h-72 overflow-y-auto shadow-2xs">
                            <div
                                v-for="(woItem, idx) in displayedBulkWoList"
                                :key="woItem.id || idx"
                                class="py-2 flex items-center justify-between text-[11px] first:pt-0 last:pb-0"
                            >
                                <div class="space-y-0.5 pr-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-black text-slate-900">{{ woItem.nomor_wo }}</span>
                                        <span
                                            :class="[
                                                'px-1.5 py-0.2 rounded text-[9px] font-black uppercase',
                                                woItem.metode_wo === 'manual' ? 'bg-amber-100 text-amber-900' : 'bg-blue-100 text-blue-900',
                                            ]"
                                        >
                                            {{ woItem.metode_wo === 'manual' ? 'Manual' : 'Sistem' }}
                                        </span>
                                    </div>
                                    <span class="text-slate-600 font-semibold block text-[10.5px] truncate max-w-xs">{{ woItem.nama_menu }}</span>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 whitespace-nowrap">{{ formatTanggalIndo(woItem.tanggal_distribusi) }}</span>
                            </div>

                            <!-- Tombol Lihat Lainnya Interaktif di dalam list -->
                            <div
                                v-if="!showAllBulkItems && selectedWorkOrdersList.length > 5"
                                class="pt-2 text-center"
                            >
                                <button
                                    type="button"
                                    @click="showAllBulkItems = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 text-[11px] font-black border border-rose-200 transition cursor-pointer shadow-2xs"
                                >
                                    <ChevronDown class="h-3.5 w-3.5" />
                                    <span>Lihat {{ selectedWorkOrdersList.length - 5 }} Work Order lainnya</span>
                                </button>
                            </div>
                            <div
                                v-else-if="showAllBulkItems && selectedWorkOrdersList.length > 5"
                                class="pt-2 text-center"
                            >
                                <button
                                    type="button"
                                    @click="showAllBulkItems = false"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 text-[10.5px] font-bold border border-slate-200 transition cursor-pointer"
                                >
                                    <ChevronUp class="h-3 w-3" />
                                    <span>Tampilkan lebih sedikit (5 saja)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 pt-3 border-t border-slate-100">
                    <button
                        type="button"
                        @click="cancelBulkDelete"
                        :disabled="isDeletingBulk"
                        class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 rounded-xl transition cursor-pointer text-center"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeBulkDeleteWo"
                        :disabled="isDeletingBulk"
                        class="w-full sm:w-auto px-5 py-2.5 text-xs font-black text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition cursor-pointer shadow-xs text-center disabled:opacity-50 flex items-center justify-center gap-1.5"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>{{ isDeletingBulk ? 'Menghapus Sekaligus...' : `Ya, Hapus ${selectedWoIds.length} WO Sekaligus` }}</span>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Floating Bottom Bulk Action Bar -->
        <div
            v-if="selectedWoIds.length > 0"
            class="fixed bottom-6 inset-x-4 sm:inset-x-auto sm:right-8 sm:left-auto z-40 bg-slate-900/95 text-white p-3 sm:p-4 rounded-2xl shadow-2xl border border-slate-700/80 backdrop-blur-md flex items-center justify-between gap-4 animate-in slide-in-from-bottom-5"
        >
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black text-xs shrink-0 shadow-xs">
                    {{ selectedWoIds.length }}
                </div>
                <div>
                    <p class="text-xs font-black text-white leading-tight">
                        {{ selectedWoIds.length }} Work Order Terpilih
                    </p>
                    <p class="text-[10px] text-slate-300 hidden sm:block">
                        Klik tombol untuk menghapus seluruh WO terpilih
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="clearSelection"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="openBulkDeleteModal"
                    class="px-4 py-1.5 rounded-xl text-xs font-black bg-rose-600 hover:bg-rose-700 text-white shadow-md flex items-center gap-1.5 cursor-pointer transition-all"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>Hapus Sekaligus ({{ selectedWoIds.length }})</span>
                </button>
            </div>
        </div>

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
