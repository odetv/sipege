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
import Modal from "@/Components/Modal.vue";
import { CheckCircle2, X, Send, AlertCircle, RefreshCw, ArrowRight } from "lucide-vue-next";

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
    komponenSekolah: { type: String, default: "semua" },
    initialMode: { type: String, default: "hari_ini" },
    initialTanggalMulai: { type: String, default: "" },
    initialTanggalSelesai: { type: String, default: "" },
    initialPeriodeId: { type: [String, Number], default: "all" },
    transaksiPengajuan: { type: Object, default: null },
    statusPengajuan: { type: String, default: null },
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
const selectedKomponenSekolah = ref(props.komponenSekolah || props.filters?.komponen_sekolah || "semua");

watch(
    () => [props.komponenSekolah, props.filters?.komponen_sekolah],
    ([newK, newFilterK]) => {
        if (newK) selectedKomponenSekolah.value = newK;
        else if (newFilterK) selectedKomponenSekolah.value = newFilterK;
    }
);

function changeKomponenSekolah(newKomponen) {
    selectedKomponenSekolah.value = newKomponen;
    applyFilterTanggal();
}

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
const selectedIds = ref(
    editableItems.value
        .filter(it => (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0)
        .map(it => it.id)
);

watch(
    () => editableItems.value,
    (newItems) => {
        selectedIds.value = newItems
            .filter(it => (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0)
            .map(it => it.id);
    }
);

function toggleSelect(id) {
    const it = editableItems.value.find(item => item.id === id);
    if (it && ((parseInt(it.hari_distribusi, 10) || 0) <= 0 || (Number(it.amount) || 0) <= 0)) {
        return; // Tidak dapat memilih sasaran yang tidak menerima MBG
    }
    const idx = selectedIds.value.indexOf(id);
    if (idx >= 0) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
}

const isAllSelected = computed(() => {
    const selectable = filteredItems.value.filter(
        it => (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0
    );
    if (selectable.length === 0) return false;
    return selectable.every(it => selectedIds.value.includes(it.id));
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = filteredItems.value
            .filter(it => (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0)
            .map(it => it.id);
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

const selectedTitikCount = computed(() => {
    return selectedRows.value.reduce((acc, it) => {
        if (it.rincian_posyandu && it.rincian_posyandu.length > 0) {
            const activePos = it.rincian_posyandu.filter(
                sub => (parseInt(sub.hari_distribusi, 10) || 0) > 0 && (Number(sub.amount) || 0) > 0
            ).length;
            return acc + activePos;
        }
        const isDistributed = (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0;
        return acc + (isDistributed ? 1 : 0);
    }, 0);
});

const totalTitikFiltered = computed(() => {
    return filteredItems.value.reduce((acc, it) => {
        return acc + (it.rincian_posyandu && it.rincian_posyandu.length > 0 ? it.rincian_posyandu.length : 1);
    }, 0);
});

const averagePayroll = computed(() => {
    if (selectedTitikCount.value === 0) return 0;
    return Math.round(totalPayrollAmount.value / selectedTitikCount.value);
});

const totalPenerimaJiwa = computed(() => {
    return selectedRows.value.reduce((sum, it) => {
        if (it.rincian_posyandu && it.rincian_posyandu.length > 0) {
            const posPm = it.rincian_posyandu
                .filter(sub => (parseInt(sub.hari_distribusi, 10) || 0) > 0 && (Number(sub.amount) || 0) > 0)
                .reduce((s, sub) => s + (Number(sub.total_penerima) || 0), 0);
            return sum + posPm;
        }
        if ((parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0) {
            return sum + (Number(it.total_penerima) || 0);
        }
        return sum;
    }, 0);
});

const computedStats = computed(() => {
    const sekolahCount = selectedRows.value.filter(
        it => it.kategori !== "Posyandu" && (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0
    ).length;

    let posyanduCount = 0;
    selectedRows.value.forEach(it => {
        if (it.kategori === "Posyandu") {
            if (it.rincian_posyandu && it.rincian_posyandu.length > 0) {
                posyanduCount += it.rincian_posyandu.filter(
                    sub => (parseInt(sub.hari_distribusi, 10) || 0) > 0 && (Number(sub.amount) || 0) > 0
                ).length;
            } else if ((parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0) {
                posyanduCount += 1;
            }
        }
    });

    const totalTitik = sekolahCount + posyanduCount;

    return {
        total_kelompok: totalTitik,
        total_rows: totalTitikFiltered.value,
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
            komponen_sekolah: selectedKomponenSekolah.value,
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
            komponen_sekolah: selectedKomponenSekolah.value,
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
    if (!item) return;
    const hari = Number(item.hari_distribusi ?? item.hari_operasional) || 0;
    const amount = Number(item.amount) || 0;
    if (hari <= 0 || amount <= 0) {
        alert(`Titik sasaran "${item.nama_kelompok}" tidak menerima MBG pada tanggal/periode ini sehingga tidak memiliki kuitansi insentif.`);
        return;
    }
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
        const baseSource = selectedRows.value.length > 0 ? selectedRows.value : filteredItems.value;
        const distributedList = baseSource.filter(
            (it) => (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0
        );
        await downloadRekapInsentifExcel({
            insentifList: distributedList,
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
    // Ambil data yang terdistribusi (hari_distribusi > 0 dan amount > 0)
    const selectedDistributed = selectedRows.value.filter(
        (it) => (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0
    );
    const candidates = selectedDistributed.length > 0
        ? selectedDistributed
        : filteredItems.value.filter(
            (it) => (parseInt(it.hari_distribusi, 10) || 0) > 0 && (Number(it.amount) || 0) > 0
        );

    if (candidates.length === 0) {
        alert("Tidak ada sasaran sekolah atau posyandu yang memiliki catatan distribusi (hari distribusi > 0) pada periode/rentang tanggal ini.");
        return;
    }

    isExportingRelawan.value = true;
    try {
        // Jika terdapat baris gabungan yang memiliki rincian_posyandu, uraikan HANYA posyandu yang benar-benar terdistribusi aktif
        const flattenedItems = [];
        candidates.forEach((it) => {
            if (it.rincian_posyandu && it.rincian_posyandu.length > 0) {
                it.rincian_posyandu.forEach((sub) => {
                    const subHari = parseInt(sub.hari_distribusi, 10) || 0;
                    const subAmount = Number(sub.amount) || 0;
                    if (subHari > 0 && subAmount > 0) {
                        flattenedItems.push(sub);
                    }
                });
            } else {
                const itHari = parseInt(it.hari_distribusi, 10) || 0;
                const itAmount = Number(it.amount) || 0;
                if (itHari > 0 && itAmount > 0) {
                    flattenedItems.push(it);
                }
            }
        });

        if (flattenedItems.length === 0) {
            alert("Tidak ada sasaran sekolah atau posyandu yang terdistribusi aktif (hari distribusi > 0) untuk diekspor ke format Payroll ERP.");
            isExportingRelawan.value = false;
            return;
        }

        const payloadItems = flattenedItems.map((it, idx) => {
            const isPosyandu = String(it.kategori || "").toLowerCase() === "posyandu" || String(it.nama_kelompok || "").toLowerCase().includes("posyandu");
            const peran = isPosyandu ? "Kader Posyandu" : "Penanggung Jawab Satuan Pendidikan";
            const hari = parseInt(it.hari_distribusi, 10) || 0;
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

// ─── Modal Konfirmasi Ajukan Pembayaran Insentif ke Menu Transaksi ─────────────
const showAjukanModal = ref(false);
const isSubmittingTrx = ref(false);
const currentStatusPengajuan = ref(props.statusPengajuan);
watch(
    () => props.statusPengajuan,
    (val) => {
        currentStatusPengajuan.value = val;
    }
);
const showResultModal = ref(false);
const resultModalData = ref({
    title: "",
    message: "",
    nomorTransaksi: "",
    isError: false,
});

function handleAjukanPembayaranInsentif() {
    if (selectedTitikCount.value === 0 || totalPayrollAmount.value <= 0) {
        resultModalData.value = {
            title: "Tidak Ada Sasaran",
            message: "Tidak ada titik sasaran aktif dengan hak insentif untuk diajukan pembayarannya.",
            nomorTransaksi: "",
            isError: true,
        };
        showResultModal.value = true;
        return;
    }
    showAjukanModal.value = true;
}

async function confirmAjukanPembayaranInsentif() {
    const tgl = tanggalSelesai.value || tanggalMulai.value || new Date().toISOString().slice(0, 10);

    isSubmittingTrx.value = true;
    try {
        const response = await axios.post(route("penerima-manfaat.pembayaran-insentif.ajukan-pembayaran"), {
            tanggal_transaksi: tgl,
            tanggal_mulai: tanggalMulai.value,
            tanggal_selesai: tanggalSelesai.value,
            periode_id: selectedPeriodeId.value,
            periode_label: labelSiklus.value || "",
            nominal: totalPayrollAmount.value,
            total_titik: selectedTitikCount.value,
            total_pm: totalPenerimaJiwa.value,
            metode_bayar: "Tunai",
        });

        showAjukanModal.value = false;
        currentStatusPengajuan.value = response.data.status === 'already_approved' ? 'Disetujui' : 'Ditunda';
        resultModalData.value = {
            title: response.data.status === 'already_approved' ? 'Pengajuan Sudah Disetujui' : 'Pengajuan Berhasil Dicatat!',
            message: response.data.message || 'Pengajuan pembayaran insentif berhasil diajukan ke Menu Transaksi.',
            nomorTransaksi: response.data.data?.nomor_transaksi || '',
            isError: false,
        };
        showResultModal.value = true;
    } catch (err) {
        console.error("Gagal mengajukan pembayaran insentif:", err);
        const errMsg = err.response?.data?.message || err.response?.data?.error || "Terjadi kesalahan saat mengajukan pembayaran insentif ke Menu Transaksi.";
        resultModalData.value = {
            title: "Gagal Mengajukan Pembayaran",
            message: errMsg,
            nomorTransaksi: "",
            isError: true,
        };
        showResultModal.value = true;
    } finally {
        isSubmittingTrx.value = false;
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
                v-model:selectedKomponenSekolah="selectedKomponenSekolah"
                :modeSkala="modeSkala"
                :selectedPeriodeId="selectedPeriodeId"
                :periodes="periodes"
                :tanggalMulai="tanggalMulai"
                :tanggalSelesai="tanggalSelesai"
                :rentangHariCount="rentangHariCount"
                :labelSiklus="labelSiklus"
                :selectedAturan="selectedAturan"
                :selectedKomponenSekolah="selectedKomponenSekolah"
                :unitSppg="unitSppg"
                :isExportingRelawan="isExportingRelawan"
                :isSubmittingTrx="isSubmittingTrx"
                :statusPengajuan="currentStatusPengajuan"
                :transaksiPengajuan="transaksiPengajuan"
                @changeKomponenSekolah="changeKomponenSekolah"
                @changeAturan="changeAturan"
                @openAturanModal="showAturanModal = true"
                @dateFilterChange="onDateFilterChange"
                @changeModeSkala="changeModeSkala"
                @selectPeriode="selectPeriode"
                @applyDateRange="onApplyDateRange"
                @printAllReceipts="printAllReceipts"
                @exportRelawanExcel="handleExportRelawanExcel"
                @ajukanPembayaran="handleAjukanPembayaranInsentif"
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
                :komponenSekolah="selectedKomponenSekolah"
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

        <!-- ─── Modal Konfirmasi Ajukan Pembayaran Insentif ke Menu Transaksi ─── -->
        <Modal :show="showAjukanModal" @close="showAjukanModal = false" max-width="md">
            <div class="p-5 sm:p-6 space-y-4 text-xs">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600">
                            <Send class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Konfirmasi Ajukan Pembayaran Insentif</h3>
                            <p class="text-[11px] text-slate-500">Pencatatan ke Menu Transaksi Keuangan</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showAjukanModal = false"
                        class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Info Alert -->
                <div class="p-3 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-900 space-y-1">
                    <div class="font-bold flex items-center gap-1.5 text-xs text-amber-800">
                        <AlertCircle class="h-4 w-4 shrink-0 text-amber-600" />
                        <span>Status Awal: DITUNDA</span>
                    </div>
                    <p class="text-[11px] text-amber-700 leading-relaxed">
                        Data pembayaran akan otomatis dicatat sebagai <b>Uang Keluar</b> kategori <b>Operasional</b> dengan status <b>Ditunda</b>. Transaksi baru sah terhitung ke dalam realisasi keuangan setelah disetujui di Menu Transaksi.
                    </p>
                </div>

                <!-- Rincian Parameter Pengajuan -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Periode Pembayaran:</span>
                        <span class="font-bold text-slate-800">{{ labelSiklus || `${tanggalMulai} s/d ${tanggalSelesai}` }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Titik Sasaran Terlayani:</span>
                        <span class="font-bold text-slate-800">{{ selectedTitikCount }} Titik</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Total Penerima Manfaat:</span>
                        <span class="font-bold text-slate-800">{{ totalPenerimaJiwa.toLocaleString('id-ID') }} PM</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Metode Pembayaran:</span>
                        <span class="font-bold text-slate-800">Tunai Langsung</span>
                    </div>
                    <div class="flex items-baseline justify-between pt-2 border-t border-slate-200">
                        <span class="text-slate-700 font-extrabold uppercase">Total Nominal Insentif:</span>
                        <span class="text-base font-black text-amber-600">Rp {{ totalPayrollAmount.toLocaleString('id-ID') }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showAjukanModal = false"
                        :disabled="isSubmittingTrx"
                        class="px-4 py-2 text-xs font-bold rounded-xl text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-50"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmAjukanPembayaranInsentif"
                        :disabled="isSubmittingTrx"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white cursor-pointer shadow-xs transition-colors disabled:opacity-50"
                    >
                        <RefreshCw v-if="isSubmittingTrx" class="h-3.5 w-3.5 animate-spin" />
                        <Send v-else class="h-3.5 w-3.5" />
                        <span>{{ isSubmittingTrx ? 'Memproses Pengajuan...' : 'Ya, Ajukan Pembayaran' }}</span>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- ─── Modal Feedback Hasil Ajukan Transaksi Insentif ─── -->
        <Modal :show="showResultModal" @close="showResultModal = false" max-width="md">
            <div class="p-6 text-center space-y-4">
                <div
                    class="mx-auto flex items-center justify-center h-14 w-14 rounded-full"
                    :class="resultModalData.isError ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'"
                >
                    <AlertCircle v-if="resultModalData.isError" class="h-8 w-8" />
                    <CheckCircle2 v-else class="h-8 w-8" />
                </div>

                <div class="space-y-1.5">
                    <h3 class="text-base font-extrabold text-slate-900">
                        {{ resultModalData.title }}
                    </h3>
                    <div v-if="resultModalData.nomorTransaksi">
                        <span class="text-xs font-mono font-bold text-amber-700 bg-amber-50 px-3 py-1 rounded-full border border-amber-200 inline-block">
                            {{ resultModalData.nomorTransaksi }}
                        </span>
                    </div>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed max-w-sm mx-auto">
                    {{ resultModalData.message }}
                </p>

                <div
                    v-if="!resultModalData.isError"
                    class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-left text-[11px] text-slate-600 space-y-1"
                >
                    <div class="font-bold text-slate-800 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Status Saat Ini: Ditunda</span>
                    </div>
                    <p class="text-slate-500">
                        Data telah masuk ke <b>Menu Keuangan &rarr; Sub Menu Transaksi</b>. Silakan review dan setujui transaksi tersebut agar sah mengurangi saldo kas operasional.
                    </p>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2">
                    <button
                        type="button"
                        @click="showResultModal = false"
                        class="w-full sm:w-auto px-4 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 cursor-pointer transition-colors"
                    >
                        Tutup
                    </button>
                    <button
                        v-if="!resultModalData.isError"
                        type="button"
                        @click="router.visit(route('keuangan.transaksi'))"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer shadow-xs transition-colors"
                    >
                        <span>Lihat di Menu Transaksi</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
