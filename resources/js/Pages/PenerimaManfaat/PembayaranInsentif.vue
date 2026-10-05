<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import InsentifControlBar from "./Partials/InsentifControlBar.vue";
import InsentifKpiCards from "./Partials/InsentifKpiCards.vue";
import InsentifTable from "./Partials/InsentifTable.vue";
import InsentifReceiptModal from "./Partials/InsentifReceiptModal.vue";
import { downloadRekapInsentifExcel } from "@/Services/exportInsentifHelper";

const props = defineProps({
    user: { type: Object, default: () => ({}) },
    unitSppg: { type: Object, default: null },
    periodes: { type: Array, default: () => [] },
    activePeriode: { type: Object, default: null },
    insentifList: { type: Array, default: () => [] },
    distribusiCountMap: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    initialMode: { type: String, default: "hari_ini" },
    initialTanggalMulai: { type: String, default: "" },
    initialTanggalSelesai: { type: String, default: "" },
    initialPeriodeId: { type: [String, Number], default: "all" },
    filters: { type: Object, default: () => ({}) },
});

// ─── Tanggal Hari Ini (Today) ──────────────────────────────────────────────────
const todayStr = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
});

function getDefaultEndDate(mode, startStr) {
    const base = startStr ? new Date(startStr + "T00:00:00") : new Date();
    const daysToAdd = mode === "periodik" ? 13 : (mode === "bulanan" ? 27 : 0);
    const end = new Date(base);
    end.setDate(base.getDate() + daysToAdd);
    const y = end.getFullYear();
    const m = String(end.getMonth() + 1).padStart(2, "0");
    const d = String(end.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

// ─── State Filter & Mode Skala ────────────────────────────────────────────────
// Default saat pertama kali dibuka adalah Hari Ini (1 hari kerja)
const modeSkala = ref(props.initialMode || "hari_ini");
const tanggalMulai = ref(props.initialTanggalMulai || todayStr.value);
const tanggalSelesai = ref(props.initialTanggalSelesai || todayStr.value);
const selectedPeriodeId = ref(props.initialPeriodeId !== undefined ? String(props.initialPeriodeId) : "all");
const searchQuery = ref("");
const selectedKategoriFilter = ref("all");

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

// Inisialisasi awal sinkronisasi periode jika ada tanggal yang cocok
if (selectedPeriodeId.value === "all") {
    const matched = findMatchingPeriode(tanggalMulai.value, tanggalSelesai.value);
    if (matched) {
        selectedPeriodeId.value = String(matched.id);
    }
}

const rentangHariCount = computed(() => {
    if (!tanggalMulai.value || !tanggalSelesai.value) return 0;
    const start = new Date(tanggalMulai.value + "T00:00:00");
    const end = new Date(tanggalSelesai.value + "T00:00:00");
    const diffTime = end.getTime() - start.getTime();
    if (diffTime < 0) return 0;
    return Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

const labelSiklus = computed(() => {
    const matched = props.periodes?.find(p => String(p.id) === String(selectedPeriodeId.value));
    if (matched) {
        return `Siklus: Periode ${matched.nomor_periode} (${rentangHariCount.value} Hari Kerja)`;
    }
    if (rentangHariCount.value === 14) return "Siklus: Periodik (14 Hari Kerja)";
    if (rentangHariCount.value === 28) return "Siklus: Bulanan (28 Hari Kerja)";
    return `Siklus: Kustom (${rentangHariCount.value} Hari Kerja)`;
});

// ─── Editable Items State (Dengan Titik Pembilang & Custom Edit Warning) ──────
function mapInitialItems(list) {
    return (list || []).map((it) => ({
        ...it,
        originalAmount: it.amount,
        isCustom: false,
    }));
}

const editableItems = ref(mapInitialItems(props.insentifList));

watch(
    () => props.insentifList,
    (newList) => {
        editableItems.value = mapInitialItems(newList);
    },
    { deep: true }
);

function updateAmount({ id, amount, isCustom = true }) {
    const item = editableItems.value.find(it => it.id === id);
    if (item) {
        item.amount = Math.max(0, amount);
        item.isCustom = isCustom;
        item.is_valid = item.amount > 0;
    }
}

function resetAmount(id) {
    const item = editableItems.value.find(it => it.id === id);
    if (item) {
        item.amount = item.originalAmount;
        item.isCustom = false;
        item.is_valid = item.amount > 0;
    }
}

// ─── Checkbox Selection ───────────────────────────────────────────────────────
const selectedIds = ref(editableItems.value.map(it => it.id));

watch(
    () => editableItems.value,
    (newItems) => {
        selectedIds.value = newItems.filter(it => it.amount > 0).map(it => it.id);
    }
);

function toggleSelect(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
}

const isAllSelected = computed(() => {
    if (filteredItems.value.length === 0) return false;
    return filteredItems.value.every(it => selectedIds.value.includes(it.id));
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = filteredItems.value.map(it => it.id);
    }
}

// ─── Filter & Search Client-Side ──────────────────────────────────────────────
const filteredItems = computed(() => {
    let list = editableItems.value;

    if (searchQuery.value && searchQuery.value.trim() !== "") {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(item => {
            const nama = (item.nama_kelompok || "").toLowerCase();
            const pic = (item.nama_pic || "").toLowerCase();
            const desa = (item.desa_kelurahan || "").toLowerCase();
            return nama.includes(q) || pic.includes(q) || desa.includes(q);
        });
    }

    if (selectedKategoriFilter.value && selectedKategoriFilter.value !== "all") {
        if (selectedKategoriFilter.value === "Sekolah") {
            list = list.filter(item => item.kategori !== "Posyandu");
        } else if (selectedKategoriFilter.value === "Posyandu") {
            list = list.filter(item => item.kategori === "Posyandu");
        } else {
            list = list.filter(item => item.kategori === selectedKategoriFilter.value);
        }
    }

    return list;
});

// Dynamic Computed KPI
const selectedRows = computed(() => {
    return filteredItems.value.filter(it => selectedIds.value.includes(it.id));
});

const totalPayrollAmount = computed(() => {
    return selectedRows.value.reduce((sum, it) => sum + (Number(it.amount) || 0), 0);
});

const insentifSekolah = computed(() => {
    return selectedRows.value
        .filter(it => it.kategori !== "Posyandu")
        .reduce((sum, it) => sum + (Number(it.amount) || 0), 0);
});

const insentifPosyandu = computed(() => {
    return selectedRows.value
        .filter(it => it.kategori === "Posyandu")
        .reduce((sum, it) => sum + (Number(it.amount) || 0), 0);
});

const averagePayroll = computed(() => {
    if (selectedRows.value.length === 0) return 0;
    return Math.round(totalPayrollAmount.value / selectedRows.value.length);
});

const totalPenerimaJiwa = computed(() => {
    return selectedRows.value.reduce((sum, it) => sum + (Number(it.total_penerima) || 0), 0);
});

const computedStats = computed(() => ({
    total_kelompok: selectedRows.value.length,
    total_sekolah: selectedRows.value.filter(it => it.kategori !== "Posyandu").length,
    total_posyandu: selectedRows.value.filter(it => it.kategori === "Posyandu").length,
    total_penerima_jiwa: totalPenerimaJiwa.value,
    hari_operasional: rentangHariCount.value,
    total_anggaran: totalPayrollAmount.value,
    insentif_sekolah: insentifSekolah.value,
    insentif_posyandu: insentifPosyandu.value,
    rata_rata_insentif: averagePayroll.value,
}));

// ─── Filter Tanggal & Periode Navigation ──────────────────────────────────────
function applyFilterTanggal() {
    router.get(
        route("penerima-manfaat.pembayaran-insentif"),
        {
            mode: modeSkala.value,
            tanggal_mulai: tanggalMulai.value,
            tanggal_selesai: tanggalSelesai.value,
            periode_id: selectedPeriodeId.value,
            kategori: selectedKategoriFilter.value,
            search: searchQuery.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
}

function onApplyDateRange(newRange) {
    if (newRange && newRange.start && newRange.end) {
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

        applyFilterTanggal();
    }
}

function changeModeSkala(newMode) {
    modeSkala.value = newMode;
    const newEnd = getDefaultEndDate(newMode, tanggalMulai.value);
    tanggalSelesai.value = newEnd;

    const matched = findMatchingPeriode(tanggalMulai.value, newEnd);
    if (matched) {
        selectedPeriodeId.value = String(matched.id);
    } else {
        selectedPeriodeId.value = "all";
    }

    applyFilterTanggal();
}

function selectPeriode(pId) {
    selectedPeriodeId.value = String(pId);
    if (selectedPeriodeId.value === "all") {
        applyFilterTanggal();
        return;
    }
    const p = props.periodes?.find((it) => String(it.id) === String(selectedPeriodeId.value));
    if (p && p.tanggal_mulai && p.tanggal_selesai) {
        tanggalMulai.value = p.tanggal_mulai.substring(0, 10);
        tanggalSelesai.value = p.tanggal_selesai.substring(0, 10);
        modeSkala.value = "periodik";
        applyFilterTanggal();
    }
}

// ─── Modal Kuitansi Tunai ─────────────────────────────────────────────────────
const selectedReceiptItem = ref(null);
const showReceiptModal = ref(false);

function openReceipt(item) {
    selectedReceiptItem.value = item;
    showReceiptModal.value = true;
}

function printAllReceipts() {
    if (selectedRows.value.length === 0) {
        alert("Pilih minimal 1 titik sasaran untuk mencetak kuitansi.");
        return;
    }
    openReceipt(selectedRows.value[0]);
}

// ─── Ekspor Excel Daftar Pembayaran Tunai ─────────────────────────────────────
const isExporting = ref(false);

async function handleExportExcel() {
    try {
        isExporting.value = true;
        await downloadRekapInsentifExcel({
            insentifList: selectedRows.value.length > 0 ? selectedRows.value : filteredItems.value,
            summary: computedStats.value,
            startDate: tanggalMulai.value,
            endDate: tanggalSelesai.value,
            unitSppg: props.unitSppg,
        });
    } catch (err) {
        console.error("Gagal export Excel:", err);
        alert("Terjadi kesalahan saat mengekspor Excel.");
    } finally {
        isExporting.value = false;
    }
}
</script>

<template>
    <Head title="Pembayaran Insentif Penerima Manfaat - SIPEGE" />

    <AppLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto space-y-4">
            <!-- ─── Komponen 1: Control Bar (Filter & Aksi) ───────────────────── -->
            <InsentifControlBar
                v-model:searchQuery="searchQuery"
                v-model:selectedKategoriFilter="selectedKategoriFilter"
                :modeSkala="modeSkala"
                :selectedPeriodeId="selectedPeriodeId"
                :periodes="periodes"
                :tanggalMulai="tanggalMulai"
                :tanggalSelesai="tanggalSelesai"
                :rentangHariCount="rentangHariCount"
                :labelSiklus="labelSiklus"
                :unitSppg="unitSppg"
                :isExporting="isExporting"
                @changeModeSkala="changeModeSkala"
                @selectPeriode="selectPeriode"
                @applyDateRange="onApplyDateRange"
                @exportExcel="handleExportExcel"
                @printAllReceipts="printAllReceipts"
            />

            <!-- ─── Komponen 2: KPI Statistics Cards (5 Cards) ────────────────── -->
            <InsentifKpiCards
                :stats="computedStats"
                :selectedCount="selectedRows.length"
                :totalRowsCount="filteredItems.length"
                :totalPayrollAmount="totalPayrollAmount"
                :insentifSekolah="insentifSekolah"
                :insentifPosyandu="insentifPosyandu"
                :averagePayroll="averagePayroll"
                :totalPenerimaJiwa="totalPenerimaJiwa"
            />

            <!-- ─── Komponen 3: Interactive Table Penerima Insentif ───────────── -->
            <InsentifTable
                :items="filteredItems"
                :selectedIds="selectedIds"
                :isAllSelected="isAllSelected"
                @toggleSelect="toggleSelect"
                @toggleSelectAll="toggleSelectAll"
                @updateAmount="updateAmount"
                @resetAmount="resetAmount"
                @openReceipt="openReceipt"
            />
        </div>

        <!-- ─── Modal Kuitansi Tanda Terima Kas Tunai ──────────────────────── -->
        <InsentifReceiptModal
            :isOpen="showReceiptModal"
            :item="selectedReceiptItem"
            :unitSppg="unitSppg"
            :tanggalMulai="tanggalMulai"
            :tanggalSelesai="tanggalSelesai"
            :todayStr="todayStr"
            @close="showReceiptModal = false"
        />
    </AppLayout>
</template>
