<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import InsentifControlBar from "./Partials/InsentifControlBar.vue";
import InsentifKpiCards from "./Partials/InsentifKpiCards.vue";
import InsentifTable from "./Partials/InsentifTable.vue";
import InsentifReceiptModal from "./Partials/InsentifReceiptModal.vue";
import InsentifAturanModal from "./Partials/InsentifAturanModal.vue";
import { downloadRekapInsentifExcel } from "@/Services/exportInsentifHelper";
import axios from "axios";
import { CheckCircle2, X } from "lucide-vue-next";

const props = defineProps({
    user: { type: Object, default: () => ({}) },
    unitSppg: { type: Object, default: null },
    periodes: { type: Array, default: () => [] },
    activePeriode: { type: Object, default: null },
    insentifList: { type: Array, default: () => [] },
    distribusiCountMap: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    aturan: { type: String, default: "aturan_2" },
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

const selectedPeriode = computed(() => {
    if (!selectedPeriodeId.value || selectedPeriodeId.value === "all") return null;
    return props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value)) || null;
});

const labelSiklus = computed(() => {
    if (selectedPeriode.value) {
        return `Siklus: Periode ${selectedPeriode.value.nomor_periode} (${rentangHariCount.value} Hari Kerja)`;
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

        // Jika baris memiliki rincian_posyandu (seperti global 3B), sinkronkan alokasi masing-masing posyandu
        if (item.rincian_posyandu && item.rincian_posyandu.length > 0) {
            let allocated = 0;
            const count = item.rincian_posyandu.length;
            const totalPm = Number(item.total_penerima) || 1;
            const nominalRata = count > 0 ? Math.floor(item.amount / count) : 0;

            item.rincian_posyandu.forEach((sub, idx) => {
                sub.nominal_rata = nominalRata;
                const pDays = Number(sub.hari_distribusi) || 1;
                if (idx === count - 1) {
                    sub.amount = Math.max(0, item.amount - allocated);
                    sub.nominal_proporsional = sub.amount;
                } else {
                    const share = (Number(sub.total_penerima) || 0) / totalPm;
                    sub.nominal_proporsional = Math.round(share * item.amount);
                    sub.amount = sub.nominal_proporsional;
                    allocated += sub.nominal_proporsional;
                }
                sub.tarif_harian = pDays > 0 ? Math.round(sub.amount / pDays) : sub.amount;
            });
        }
    }
}

function resetAmount(id) {
    const item = editableItems.value.find(it => it.id === id);
    if (item) {
        item.amount = item.originalAmount;
        item.isCustom = false;
        item.is_valid = item.amount > 0;

        if (item.rincian_posyandu && item.rincian_posyandu.length > 0) {
            let allocated = 0;
            const count = item.rincian_posyandu.length;
            const totalPm = Number(item.total_penerima) || 1;
            const nominalRata = count > 0 ? Math.floor(item.amount / count) : 0;

            item.rincian_posyandu.forEach((sub, idx) => {
                sub.nominal_rata = nominalRata;
                const pDays = Number(sub.hari_distribusi) || 1;
                if (idx === count - 1) {
                    sub.amount = Math.max(0, item.amount - allocated);
                    sub.nominal_proporsional = sub.amount;
                } else {
                    const share = (Number(sub.total_penerima) || 0) / totalPm;
                    sub.nominal_proporsional = Math.round(share * item.amount);
                    sub.amount = sub.nominal_proporsional;
                    allocated += sub.nominal_proporsional;
                }
                sub.tarif_harian = pDays > 0 ? Math.round(sub.amount / pDays) : sub.amount;
            });
        }
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

const selectedTitikCount = computed(() => {
    return selectedRows.value.reduce((acc, it) => {
        return acc + (it.rincian_posyandu && it.rincian_posyandu.length > 0 ? it.rincian_posyandu.length : 1);
    }, 0);
});

const totalTitikFiltered = computed(() => {
    return filteredItems.value.reduce((acc, it) => {
        return acc + (it.rincian_posyandu && it.rincian_posyandu.length > 0 ? it.rincian_posyandu.length : 1);
    }, 0);
});

const computedStats = computed(() => {
    const sekolahCount = selectedRows.value.filter(it => it.kategori !== "Posyandu").length;
    let posyanduCount = 0;
    selectedRows.value.forEach(it => {
        if (it.kategori === "Posyandu") {
            posyanduCount += (it.rincian_posyandu && it.rincian_posyandu.length > 0 ? it.rincian_posyandu.length : 1);
        }
    });

    const totalTitik = sekolahCount + posyanduCount;

    return {
        total_kelompok: totalTitik,
        total_rows: totalTitik,
        total_sekolah: sekolahCount,
        total_posyandu: posyanduCount,
        total_penerima_jiwa: totalPenerimaJiwa.value,
        hari_operasional: rentangHariCount.value,
        total_anggaran: totalPayrollAmount.value,
        insentif_sekolah: insentifSekolah.value,
        insentif_posyandu: insentifPosyandu.value,
        rata_rata_insentif: averagePayroll.value,
    };
});

// ─── Pilihan Aturan Perhitungan Insentif & Modal Petunjuk ─────────────────────
const selectedAturan = ref(props.aturan || props.filters?.aturan || "aturan_2");
const showAturanModal = ref(false);

watch(
    () => [props.aturan, props.filters?.aturan],
    ([newAturan, newFilterAturan]) => {
        if (newAturan) selectedAturan.value = newAturan;
        else if (newFilterAturan) selectedAturan.value = newFilterAturan;
    }
);

function changeAturan(newAturan) {
    selectedAturan.value = newAturan;
    applyFilterTanggal();
}

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
            aturan: selectedAturan.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
}

function onDateFilterChange(filter) {
    if (!filter) return;
    tanggalMulai.value = filter.start;
    tanggalSelesai.value = filter.end;
    modeSkala.value = filter.mode;
    selectedPeriodeId.value = filter.periodeId;

    router.get(
        route("penerima-manfaat.pembayaran-insentif"),
        {
            mode: filter.mode,
            tanggal_mulai: filter.start,
            tanggal_selesai: filter.end,
            periode_id: filter.periodeId,
            kategori: selectedKategoriFilter.value,
            search: searchQuery.value,
            aturan: selectedAturan.value,
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
            modeSkala.value = "periode";
        } else {
            selectedPeriodeId.value = "all";
            modeSkala.value = "rentang";
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
    if (selectedRows.value.length === 0 && editableItems.value.length === 0) {
        alert("Tidak ada titik sasaran untuk mengunduh kuitansi.");
        return;
    }
    selectedReceiptItem.value = null;
    showReceiptModal.value = true;
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

// ─── Ekspor Excel Template Resmi Daftar Relawan Harian (.XLSX) ─────────────────
const isExportingRelawan = ref(false);
const exportRelawanSuccess = ref(false);

async function handleExportRelawanExcel() {
    // Ambil data yang terdistribusi (hari_distribusi > 0)
    const candidates = selectedRows.value.length > 0 ? selectedRows.value : filteredItems.value;
    const distributedItems = candidates.filter(
        (it) => (parseInt(it.hari_distribusi, 10) || 0) > 0 && (parseInt(it.amount, 10) || 0) > 0
    );

    if (distributedItems.length === 0) {
        alert("Tidak ada sasaran sekolah atau posyandu yang memiliki catatan distribusi (hari distribusi > 0) pada periode/rentang tanggal ini.");
        return;
    }

    isExportingRelawan.value = true;
    try {
        // Jika terdapat baris gabungan yang memiliki rincian_posyandu, uraikan ke masing-masing posyandu
        const flattenedItems = [];
        distributedItems.forEach((it) => {
            if (it.rincian_posyandu && it.rincian_posyandu.length > 0) {
                it.rincian_posyandu.forEach((sub) => {
                    flattenedItems.push(sub);
                });
            } else {
                flattenedItems.push(it);
            }
        });

        const payloadItems = flattenedItems.map((it, idx) => {
            const isPosyandu = String(it.kategori || "").toLowerCase() === "posyandu" || String(it.nama_kelompok || "").toLowerCase().includes("posyandu");
            const peran = isPosyandu ? "Kader Posyandu" : "Penanggung Jawab Satuan Pendidikan";
            const hari = parseInt(it.hari_distribusi, 10) || 1;
            const tarif = Number(it.tarif_harian) > 0 ? Number(it.tarif_harian) : (hari > 0 ? Math.round((Number(it.amount) || 0) / hari) : 0);

            return {
                no: idx + 1,
                nama_kelompok: String(it.nama_kelompok || "").trim(),
                kategori: it.kategori,
                peran: peran,
                hari_distribusi: hari,
                tarif_harian: tarif,
                amount: it.amount,
                metode_bayar: "Tunai",
                tanggal_bayar: tanggalSelesai.value || tanggalMulai.value,
                nama_pic: it.nama_pic || "",
                keterangan: it.nama_pic ? `PIC: ${it.nama_pic}` : "",
            };
        });

        const response = await axios.post(
            route("penerima-manfaat.pembayaran-insentif.download-relawan-harian"),
            {
                items: payloadItems,
                tgl_transaksi: tanggalSelesai.value || tanggalMulai.value,
                tanggal_mulai: tanggalMulai.value,
                tanggal_selesai: tanggalSelesai.value,
                periode_label: labelSiklus.value || "",
            },
            { responseType: "blob" }
        );

        const blob = new Blob([response.data], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        const formatDmy = (val) => {
            if (!val) return "";
            const parts = String(val).split("-");
            return parts.length === 3 && parts[0].length === 4
                ? `${parts[2]}-${parts[1]}-${parts[0]}`
                : val;
        };

        const mulaiDmy = formatDmy(tanggalMulai.value);
        const selesaiDmy = formatDmy(tanggalSelesai.value);
        const dateLabel = (mulaiDmy && selesaiDmy && mulaiDmy !== selesaiDmy)
            ? `${mulaiDmy}_sd_${selesaiDmy}`
            : (mulaiDmy || selesaiDmy || formatDmy(new Date().toISOString().slice(0, 10)));

        link.setAttribute("download", `Payroll ERP Insentif PIC KPM ${dateLabel}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        exportRelawanSuccess.value = true;
        setTimeout(() => {
            exportRelawanSuccess.value = false;
        }, 5000);
    } catch (err) {
        console.error("Gagal export Payroll ERP Insentif PIC KPM:", err);
        let errorMsg = "Terjadi kesalahan saat mengekspor Payroll ERP Insentif PIC KPM.";
        if (err.response?.data instanceof Blob) {
            try {
                const text = await err.response.data.text();
                const json = JSON.parse(text);
                if (json.message) errorMsg = json.message;
                else if (json.error) errorMsg = json.error;
            } catch (_) {}
        } else if (err.response?.data?.message) {
            errorMsg = err.response.data.message;
        } else if (err.response?.data?.error) {
            errorMsg = err.response.data.error;
        } else if (err.message) {
            errorMsg = err.message;
        }
        alert(errorMsg);
    } finally {
        isExportingRelawan.value = false;
    }
}
</script>

<template>
    <Head title="Pembayaran Insentif - SIPEGE" />

    <AppLayout>
        <div class="space-y-6 pb-16">
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
                :selectedAturan="selectedAturan"
                :unitSppg="unitSppg"
                :isExportingRelawan="isExportingRelawan"
                @changeAturan="changeAturan"
                @openAturanModal="showAturanModal = true"
                @dateFilterChange="onDateFilterChange"
                @changeModeSkala="changeModeSkala"
                @selectPeriode="selectPeriode"
                @applyDateRange="onApplyDateRange"
                @printAllReceipts="printAllReceipts"
                @exportRelawanExcel="handleExportRelawanExcel"
            />

            <!-- Relawan Harian Insentif Download Success Alert -->
            <div
                v-if="exportRelawanSuccess"
                class="flex items-center justify-between p-4 rounded-xl bg-blue-50 border border-blue-300 text-blue-900 text-xs shadow-2xs animate-in fade-in duration-200"
            >
                <div class="flex items-center gap-2.5">
                    <CheckCircle2 class="h-5 w-5 text-blue-600 shrink-0" />
                    <div>
                        <p class="font-bold">File Payroll ERP Insentif PIC KPM Berhasil Diunduh!</p>
                        <p class="text-[11px] text-blue-700 mt-0.5">
                            File format Payroll ERP telah terisi data nominatif nama sekolah/posyandu, PIC, peran (PJ Satuan Pendidikan & Kader Posyandu), hari distribusi, tarif insentif harian, metode Tunai, dan rekap per peran otomatis.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="exportRelawanSuccess = false"
                    class="p-1 rounded-md text-blue-600 hover:bg-blue-100/60"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- ─── Komponen 2: KPI Statistics Cards (5 Cards) ────────────────── -->
            <InsentifKpiCards
                :stats="computedStats"
                :selectedCount="selectedTitikCount"
                :totalRowsCount="totalTitikFiltered"
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

        <!-- ─── Modal Kuitansi Pembayaran Insentif Resmi ─── -->
        <InsentifReceiptModal
            :show="showReceiptModal"
            :item="selectedReceiptItem"
            :initial-item="selectedReceiptItem"
            :selected-rows="selectedRows"
            :all-rows="editableItems"
            :unit-sppg="unitSppg"
            :periodes="periodes"
            :selected-periode="selectedPeriode"
            :start-date="tanggalMulai"
            :end-date="tanggalSelesai"
            :rentang-hari-count="rentangHariCount"
            @close="showReceiptModal = false"
        />

        <!-- ─── Modal Petunjuk & Pedoman Rumus Perhitungan Insentif ─── -->
        <InsentifAturanModal
            :show="showAturanModal"
            :current-aturan="selectedAturan"
            @selectAturan="changeAturan"
            @close="showAturanModal = false"
        />
    </AppLayout>
</template>
