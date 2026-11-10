<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Modal from "@/Components/Modal.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
import { SATUAN_LIST } from "@/Services/satuanConfig";
import {
    ArrowDownLeft,
    ArrowUpRight,
    CreditCard,
    Plus,
    Search,
    Package,
    Eye,
    Edit3,
    Trash2,
    Building2,
    Sliders,
    X,
    Receipt,
    RotateCcw,
    Download,
    ChevronDown,
    FileSpreadsheet,
    Printer,
    FileText,
    Table,
    ClipboardPaste,
    Sparkles,
    Check,
} from "lucide-vue-next";
import jsPDF from "jspdf";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";
import { getActiveKopConfig } from "@/Services/kopDokumenHelper";
import { LOGO_BGN_BASE64, LOGO_YAYASAN_BASE64 } from "@/Services/logoBase64Helper";
import { exportPoExcel } from "@/Services/exportDocHelper";
import TransaksiReceiptModal from "./TransaksiReceiptModal.vue";

const props = defineProps({
    transaksiList: {
        type: Array,
        default: () => [],
    },
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    periodes: {
        type: Array,
        default: () => [],
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    formatRupiah: {
        type: Function,
        default: (val) => {
            const num = Number(val) || 0;
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(num);
        },
    },
    formatTanggalIndo: {
        type: Function,
        default: (dateStr) => {
            if (!dateStr) return "-";
            try {
                const date = new Date(dateStr);
                return new Intl.DateTimeFormat("id-ID", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                }).format(date);
            } catch {
                return dateStr;
            }
        },
    },
});

// Format today's date YYYY-MM-DD
const getTodayStr = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

// Helper default tanggal & filter dari URL jika tersedia (menyesuaikan URL seperti halaman lainnya)
function getInitialFilterState() {
    const today = getTodayStr();
    if (typeof window === "undefined") {
        return {
            mode: "hari_ini",
            start: today,
            end: today,
            periodeId: "",
            isAllTime: false,
            status: "semua",
            kategori: "semua",
            search: "",
        };
    }

    const params = new URLSearchParams(window.location.search);
    const urlMode = params.get("mode");
    const urlPeriodeId = params.get("periode_id");
    const urlTanggalMulai = params.get("tanggal_mulai");
    const urlTanggalSelesai = params.get("tanggal_selesai");
    const urlIsAllTime = params.get("is_all_time") === "1" || params.get("is_all_time") === "true";
    const urlStatus = params.get("status");
    const urlKategori = params.get("kategori");
    const urlSearch = params.get("search") || params.get("cari");

    let resolvedMode = "hari_ini";
    let resolvedStart = today;
    let resolvedEnd = today;
    let resolvedPeriodeId = "";
    let resolvedIsAllTime = false;

    if (urlIsAllTime || urlMode === "semua" || urlMode === "all") {
        resolvedIsAllTime = true;
        resolvedMode = "semua";
    } else if (urlMode || urlTanggalMulai || urlTanggalSelesai || urlPeriodeId) {
        const m = (urlMode || "").toLowerCase();
        if (m === "periode" || m === "periodik" || urlPeriodeId) {
            resolvedMode = "periode";
            resolvedPeriodeId = urlPeriodeId || "";
            resolvedStart = urlTanggalMulai || "";
            resolvedEnd = urlTanggalSelesai || resolvedStart || "";

            if ((!resolvedStart || !resolvedEnd) && resolvedPeriodeId && props.periodes) {
                const foundP = props.periodes.find((p) => String(p.id) === String(resolvedPeriodeId));
                if (foundP && foundP.tanggal_mulai && foundP.tanggal_selesai) {
                    resolvedStart = String(foundP.tanggal_mulai).substring(0, 10);
                    resolvedEnd = String(foundP.tanggal_selesai).substring(0, 10);
                }
            }
        } else if (m === "rentang" || m === "range" || m === "custom") {
            resolvedMode = "rentang";
            resolvedStart = urlTanggalMulai || today;
            resolvedEnd = urlTanggalSelesai || resolvedStart || today;
        } else {
            resolvedMode = "hari_ini";
            resolvedStart = urlTanggalMulai || today;
            resolvedEnd = urlTanggalSelesai || resolvedStart || today;
        }
    }

    return {
        mode: resolvedMode,
        start: resolvedStart,
        end: resolvedEnd,
        periodeId: resolvedPeriodeId,
        isAllTime: resolvedIsAllTime,
        status: urlStatus || "semua",
        kategori: urlKategori || "semua",
        search: urlSearch || "",
    };
}

const initialFilters = getInitialFilterState();

// Filter Waktu (PeriodDateFilterBar - default hari ini atau sesuai URL)
const filterStartDate = ref(initialFilters.start);
const filterEndDate = ref(initialFilters.end);
const filterDateMode = ref(initialFilters.mode);
const filterPeriodeId = ref(initialFilters.periodeId);
const isFilterAllTime = ref(initialFilters.isAllTime);

// Filter Lainnya
const searchQuery = ref(initialFilters.search);
const statusFilter = ref(initialFilters.status);
const kategoriFilter = ref(initialFilters.kategori);

// Flag untuk mencegah overwrite URL pada inisialisasi awal jika URL tidak memiliki query string
let isInitialMount = true;

// Helper sinkronisasi query parameter ke URL agar konsisten dengan halaman lain
function syncFilterToUrl() {
    if (typeof window === "undefined") return;
    if (isInitialMount && !window.location.search) return;

    const params = new URLSearchParams();

    if (isFilterAllTime.value) {
        params.set("mode", "semua");
        params.set("is_all_time", "1");
    } else {
        const normMode =
            filterDateMode.value === "today" || filterDateMode.value === "hari_ini"
                ? "hari_ini"
                : filterDateMode.value;

        if (normMode) {
            params.set("mode", normMode);
        }

        if (normMode === "periode" && filterPeriodeId.value && filterPeriodeId.value !== "all") {
            params.set("periode_id", String(filterPeriodeId.value));
        }

        if (filterStartDate.value) {
            params.set("tanggal_mulai", filterStartDate.value);
        }

        if (filterEndDate.value) {
            params.set("tanggal_selesai", filterEndDate.value);
        }
    }

    if (statusFilter.value && statusFilter.value !== "semua") {
        params.set("status", statusFilter.value);
    }

    if (kategoriFilter.value && kategoriFilter.value !== "semua") {
        params.set("kategori", kategoriFilter.value);
    }

    if (searchQuery.value && searchQuery.value.trim() !== "") {
        params.set("search", searchQuery.value.trim());
    }

    const currentQuery = window.location.search.replace(/^\?/, "");
    const newQuery = params.toString();

    if (currentQuery === newQuery) return;

    const newUrl = `${window.location.pathname}${newQuery ? `?${newQuery}` : ""}`;
    window.history.replaceState({}, "", newUrl);
}

// Handler perubahan filter waktu dari PeriodDateFilterBar
function handleDateFilterChange(filterPayload) {
    if (!filterPayload) return;
    isInitialMount = false;
    filterStartDate.value = filterPayload.start || filterPayload.startDate || "";
    filterEndDate.value = filterPayload.end || filterPayload.endDate || "";
    filterDateMode.value = filterPayload.mode || "hari_ini";
    filterPeriodeId.value =
        filterPayload.periodeId !== undefined && filterPayload.periodeId !== null
            ? String(filterPayload.periodeId)
            : "";
    isFilterAllTime.value = Boolean(filterPayload.isAllTime);

    syncFilterToUrl();
}

// State Modal Tambah/Edit Transaksi
const JENIS_TRANSAKSI_OPERASIONAL = [
    "Gaji Pengawas Gizi",
    "Gaji Pengawas Keuangan",
    "Gaji Relawan",
    "Insentif Satuan Pendidikan",
    "Insentif Satuan Posyandu",
    "Iuran Sampah",
    "Iuran BPJS Ketenagakerjaan",
    "Bensin",
    "Gas",
    "Listrik",
    "Air Desa",
    "Air PDAM",
    "Air Galon",
    "Air Minum Kemasan",
    "Internet Pulsa",
    "Internet Kuota",
    "Internet Wifi",
    "ATK Pulpen",
    "ATK Map Berkas",
    "ATK Kertas",
    "ATK Tinta Printer",
    "APD Masker Hijab",
    "APD Masker Non-Hijab",
    "APD Sarung Tangan Plastik",
    "APD Sarung Tangan Karet",
    "APD Penutup Kepala",
    "Tali Rafia",
    "Trash Bag",
    "Kresek",
    "Plastik",
    "Cairan Pembersih",
    "Sabun Cuci",
    "Tisu",
    "Wrap Makanan",
    "Perjalanan Survei Pasar",
    "Rapat",
    "Lainnya",
];

const showManualModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isSubmitting = ref(false);

const manualForm = ref({
    perlu_npo: true,
    tanggal_pesanan: "",
    tanggal: "", // Tanggal Pembayaran
    work_order_id: "", // Sandingkan Work Order (awalnya kosong)
    kategori: "",
    jenis_transaksi: "", // Khusus Operasional
    arus: "Keluar", // 'Masuk' atau 'Keluar' (wajib diisi disemua kategori)
    nama_transaksi: "", // Nama Barang/Bahan / Uraian Transaksi
    volume: "",
    satuan: "",
    nominal: "",
    status: "",
    supplier_id: "",
    vendor: "",
    keterangan: "",
    metode_bayar: "Tunai",
});


function onJenisOperasionalChange() {
    // Tetap kosongkan / jangan otomatis isi nama_transaksi agar diisi secara manual oleh pengguna
}

// Kategori yang disederhanakan: Insentif Fasilitas & Dana Bantuan Pemerintah
const isSimplifiedCategory = computed(() => {
    return ["Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(manualForm.value.kategori);
});

// Penyesuaian otomatis ketika kategori berubah
watch(
    () => manualForm.value.kategori,
    (newKat) => {
        if (newKat !== "Operasional") {
            manualForm.value.jenis_transaksi = "";
        }
        if (newKat === "Dana Bantuan Pemerintah" && !isEditing.value) {
            manualForm.value.arus = "Masuk";
        }
        if (["Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(newKat)) {
            manualForm.value.perlu_npo = false;
            manualForm.value.tanggal_pesanan = manualForm.value.tanggal;
            manualForm.value.work_order_id = "manual";
            manualForm.value.volume = 1;
            manualForm.value.satuan = "-";
            manualForm.value.supplier_id = "";
            manualForm.value.vendor = "-";
        } else {
            if (manualForm.value.satuan === "-") {
                manualForm.value.satuan = "";
            }
            if (manualForm.value.vendor === "-") {
                manualForm.value.vendor = "";
            }
            if (manualForm.value.volume === 1 && !isEditing.value) {
                manualForm.value.volume = "";
            }
        }
    }
);

// Watch perlu_npo: jika Tidak, tanggal_pesanan otomatis disamakan dengan tanggal pembayaran
watch(
    () => manualForm.value.perlu_npo,
    (val) => {
        if (!val) {
            manualForm.value.tanggal_pesanan = manualForm.value.tanggal;
        }
    }
);

watch(
    () => manualForm.value.tanggal,
    (val) => {
        if (!manualForm.value.perlu_npo || isSimplifiedCategory.value) {
            manualForm.value.tanggal_pesanan = val;
        }
    }
);

// Work Orders yang bertepatan dengan tanggal pesanan atau tanggal pembayaran
const matchingWorkOrders = computed(() => {
    const tglPesanan = manualForm.value.tanggal_pesanan;
    const tglBayar = manualForm.value.tanggal;
    if (!tglPesanan && !tglBayar) return [];

    return (props.workOrdersList || []).filter((wo) => {
        const woDate = String(wo.tanggal_distribusi || "").substring(0, 10);
        return woDate && (woDate === tglPesanan || woDate === tglBayar);
    });
});

const otherWorkOrders = computed(() => {
    const matchIds = new Set(matchingWorkOrders.value.map((w) => w.id));
    return (props.workOrdersList || []).filter((wo) => !matchIds.has(wo.id));
});

// Watcher untuk mengarahkan pilihan dropdown ke WO bila bertepatan dengan tanggal
watch(
    [() => manualForm.value.tanggal_pesanan, () => manualForm.value.tanggal],
    ([newPesanan, newBayar]) => {
        if (!showManualModal.value) return;

        const matched = (props.workOrdersList || []).find((wo) => {
            const woDate = String(wo.tanggal_distribusi || "").substring(0, 10);
            return woDate && (woDate === newPesanan || woDate === newBayar);
        });

        if (matched) {
            manualForm.value.work_order_id = matched.id;
        } else {
            if (manualForm.value.work_order_id && manualForm.value.work_order_id !== "manual") {
                const currentStillMatches = (props.workOrdersList || []).some(
                    (wo) => wo.id === manualForm.value.work_order_id &&
                    String(wo.tanggal_distribusi || "").substring(0, 10) === (newPesanan || newBayar)
                );
                if (!currentStillMatches) {
                    manualForm.value.work_order_id = "manual";
                }
            }
        }
    }
);

// ─── STATE & METHOD TRANSAKSI MULTI (BATCH / TABEL / EXCEL PASTE) ─────────────
const showAddDropdown = ref(false);
const showMultiModal = ref(false);
const isSubmittingMulti = ref(false);
const showPasteBox = ref(false);
const pasteTextRaw = ref("");

// Nilai default cepat untuk transaksi multi
const multiBatchDefaults = ref({
    tanggal: getTodayStr(),
    tanggal_pesanan: getTodayStr(),
    kategori: "Bahan Baku",
    jenis_transaksi: "",
    arus: "Keluar",
    status: "Disetujui",
    perlu_npo: true,
    work_order_id: "manual",
    supplier_id: "",
    satuan: "Kg",
});

// Daftar baris input multi
const multiRows = ref([]);

function createNewMultiRow(overrides = {}) {
    const tgl = overrides.tanggal || multiBatchDefaults.value.tanggal || getTodayStr();
    const kat = overrides.kategori || multiBatchDefaults.value.kategori || "Bahan Baku";
    const isSimp = ["Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(kat);

    const rowArus = overrides.arus || (kat === "Dana Bantuan Pemerintah" ? "Masuk" : (multiBatchDefaults.value.arus || "Keluar"));

    let jenisOp = overrides.jenis_transaksi || "";
    if (kat === "Operasional" && !jenisOp && overrides.nama_transaksi) {
        if (JENIS_TRANSAKSI_OPERASIONAL.includes(overrides.nama_transaksi)) {
            jenisOp = overrides.nama_transaksi;
        } else {
            jenisOp = "Lainnya";
        }
    }

    return {
        id: "mrow_" + Date.now() + "_" + Math.random().toString(36).substring(2, 7),
        tanggal: tgl,
        tanggal_pesanan: isSimp ? tgl : (overrides.tanggal_pesanan || multiBatchDefaults.value.tanggal_pesanan || tgl),
        kategori: kat,
        jenis_transaksi: jenisOp,
        arus: rowArus,
        status: overrides.status || multiBatchDefaults.value.status || "Disetujui",
        perlu_npo: isSimp ? false : (overrides.perlu_npo !== undefined ? overrides.perlu_npo : multiBatchDefaults.value.perlu_npo),
        work_order_id: isSimp ? "manual" : (overrides.work_order_id || multiBatchDefaults.value.work_order_id || "manual"),
        nama_transaksi: overrides.nama_transaksi || "",
        volume: isSimp ? 1 : (overrides.volume !== undefined ? overrides.volume : ""),
        satuan: isSimp ? "-" : (overrides.satuan || multiBatchDefaults.value.satuan || "Kg"),
        supplier_id: isSimp ? "" : (overrides.supplier_id || multiBatchDefaults.value.supplier_id || ""),
        vendor: overrides.vendor || "",
        nominal: overrides.nominal !== undefined && overrides.nominal !== null ? overrides.nominal : "",
        keterangan: overrides.keterangan || "",
    };
}

function onMultiRowCategoryChange(row) {
    if (row.kategori === "Dana Bantuan Pemerintah") {
        row.arus = "Masuk";
    }
    if (row.kategori !== "Operasional") {
        row.jenis_transaksi = "";
    }
    if (["Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(row.kategori)) {
        row.perlu_npo = false;
        row.tanggal_pesanan = row.tanggal;
        row.work_order_id = "manual";
        row.volume = 1;
        row.satuan = "-";
        row.supplier_id = "";
        row.vendor = "-";
    } else {
        if (row.satuan === "-") row.satuan = "Kg";
        if (row.vendor === "-") row.vendor = "";
        if (row.volume === 1) row.volume = "";
    }
}


function onMultiRowJenisOperasionalChange(row) {
    // Tetap kosongkan / jangan otomatis isi row.nama_transaksi agar diisi secara manual oleh pengguna
}

function onMultiRowDateChange(row) {
    if (!row.perlu_npo || ["Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(row.kategori)) {
        row.tanggal_pesanan = row.tanggal;
    }
}

function openAddMultiModal() {
    multiBatchDefaults.value.tanggal = getTodayStr();
    multiBatchDefaults.value.tanggal_pesanan = getTodayStr();

    if (multiRows.value.length === 0) {
        multiRows.value = [createNewMultiRow()];
    }
    showPasteBox.value = false;
    pasteTextRaw.value = "";
    showMultiModal.value = true;
}

function addMultiRow() {
    multiRows.value.push(createNewMultiRow());
}

function removeMultiRow(idx) {
    if (multiRows.value.length <= 1) {
        multiRows.value = [createNewMultiRow()];
        return;
    }
    multiRows.value.splice(idx, 1);
}

function resetMultiRows() {
    if (confirm("Kosongkan dan reset semua baris tabel?")) {
        multiRows.value = [createNewMultiRow()];
    }
}

function applyDefaultsToAllRows() {
    if (multiRows.value.length === 0) return;
    const def = multiBatchDefaults.value;
    const isSimp = ["Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(def.kategori);

    multiRows.value.forEach((row) => {
        row.tanggal = def.tanggal;
        row.kategori = def.kategori;
        row.arus = def.arus || "Keluar";
        row.status = def.status;
        if (def.kategori === "Operasional") {
            if (def.jenis_transaksi) {
                row.jenis_transaksi = def.jenis_transaksi;
            }
        } else {
            row.jenis_transaksi = "";
        }
        if (isSimp) {
            row.perlu_npo = false;
            row.tanggal_pesanan = def.tanggal;
            row.work_order_id = "manual";
            row.volume = 1;
            row.satuan = "-";
            row.supplier_id = "";
            row.vendor = "-";
        } else {
            row.perlu_npo = def.perlu_npo;
            row.tanggal_pesanan = def.perlu_npo ? def.tanggal_pesanan : def.tanggal;
            row.work_order_id = def.work_order_id;
            if (def.supplier_id) row.supplier_id = def.supplier_id;
            if (def.satuan) row.satuan = def.satuan;
        }
    });
}

const totalNominalMulti = computed(() => {
    return multiRows.value.reduce((sum, r) => sum + (Number(r.nominal) || 0), 0);
});

const filledMultiRowsCount = computed(() => {
    return multiRows.value.filter((r) => r.nama_transaksi && r.nama_transaksi.trim()).length;
});

function formatNominalRupiah(val) {
    if (val === "" || val === null || val === undefined) return "";
    const clean = String(val).replace(/\D/g, "");
    if (!clean) return "";
    const num = parseInt(clean, 10);
    return "Rp " + num.toLocaleString("id-ID");
}

function onMultiRowNominalInput(row, event) {
    const raw = event.target.value;
    const digitsOnly = raw.replace(/\D/g, "");
    if (!digitsOnly) {
        row.nominal = "";
        event.target.value = "";
        return;
    }
    const num = parseInt(digitsOnly, 10);
    row.nominal = num;
    event.target.value = "Rp " + num.toLocaleString("id-ID");
}

// Parser data salinan dari Excel / Spreadsheet
function parsePastedMultiText(text) {
    if (!text || typeof text !== "string") return [];

    const lines = text.trim().split(/\r?\n/);
    const parsed = [];
    const supplierList = props.suppliers || [];

    const cleanNumber = (val) => {
        if (!val) return "";
        const numStr = String(val).replace(/Rp|\.|\s/gi, "").replace(",", ".");
        const parsedNum = parseFloat(numStr);
        return !isNaN(parsedNum) ? Math.round(parsedNum) : "";
    };

    const cleanFloat = (val) => {
        if (!val) return "";
        const numStr = String(val).replace(",", ".").replace(/[^\d.]/g, "");
        const parsedNum = parseFloat(numStr);
        return !isNaN(parsedNum) ? parsedNum : "";
    };

    for (let rawLine of lines) {
        const line = rawLine.trim();
        if (!line) continue;

        let cells = [];
        if (line.includes("\t")) {
            cells = line.split("\t").map((c) => c.trim());
        } else if (line.includes("|")) {
            cells = line.split("|").map((c) => c.trim());
        } else if (line.includes(";")) {
            cells = line.split(";").map((c) => c.trim());
        } else {
            cells = [line];
        }

        if (cells.length === 0 || (cells.length === 1 && !cells[0])) continue;

        const firstLower = cells[0].toLowerCase();
        if (
            firstLower.includes("nama") ||
            firstLower.includes("uraian") ||
            firstLower.includes("barang") ||
            firstLower.includes("tanggal") ||
            firstLower === "no"
        ) {
            continue;
        }

        let nama = "";
        let vol = "";
        let satuan = multiBatchDefaults.value.satuan || "Kg";
        let nominal = "";
        let supplierId = multiBatchDefaults.value.supplier_id || "";
        let vendor = "";
        let ket = "";
        let tgl = multiBatchDefaults.value.tanggal || getTodayStr();
        let kategori = multiBatchDefaults.value.kategori || "Bahan Baku";

        if (cells.length === 1) {
            nama = cells[0];
        } else if (cells.length === 2) {
            nama = cells[0];
            nominal = cleanNumber(cells[1]);
        } else if (cells.length === 3) {
            nama = cells[0];
            vol = cleanFloat(cells[1]);
            nominal = cleanNumber(cells[2]);
        } else if (cells.length === 4) {
            nama = cells[0];
            vol = cleanFloat(cells[1]);
            satuan = cells[2] || satuan;
            nominal = cleanNumber(cells[3]);
        } else if (cells.length === 5) {
            nama = cells[0];
            vol = cleanFloat(cells[1]);
            satuan = cells[2] || satuan;
            nominal = cleanNumber(cells[3]);
            const supMatch = supplierList.find((s) => s.nama_usaha.toLowerCase().includes(cells[4].toLowerCase()));
            if (supMatch) {
                supplierId = supMatch.id;
                vendor = supMatch.nama_usaha;
            } else {
                ket = cells[4];
            }
        } else if (cells.length === 6) {
            nama = cells[0];
            vol = cleanFloat(cells[1]);
            satuan = cells[2] || satuan;
            nominal = cleanNumber(cells[3]);
            const supMatch = supplierList.find((s) => s.nama_usaha.toLowerCase().includes(cells[4].toLowerCase()));
            if (supMatch) {
                supplierId = supMatch.id;
                vendor = supMatch.nama_usaha;
            } else {
                vendor = cells[4];
            }
            ket = cells[5];
        } else if (cells.length >= 7) {
            if (cells[0].match(/^\d{4}-\d{2}-\d{2}$/)) {
                tgl = cells[0];
            } else if (cells[0].match(/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}$/)) {
                const parts = cells[0].split(/[\/\-]/);
                tgl = `${parts[2]}-${parts[1].padStart(2, "0")}-${parts[0].padStart(2, "0")}`;
            }

            if (["Bahan Baku", "Operasional", "Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(cells[1])) {
                kategori = cells[1];
            }

            nama = cells[2];
            vol = cleanFloat(cells[3]);
            satuan = cells[4] || satuan;
            nominal = cleanNumber(cells[5]);

            if (cells[6]) {
                const supMatch = supplierList.find((s) => s.nama_usaha.toLowerCase().includes(cells[6].toLowerCase()));
                if (supMatch) {
                    supplierId = supMatch.id;
                    vendor = supMatch.nama_usaha;
                } else {
                    vendor = cells[6];
                }
            }
            if (cells[7]) {
                ket = cells[7];
            }
        }

        parsed.push({
            tanggal: tgl,
            tanggal_pesanan: tgl,
            kategori: kategori,
            nama_transaksi: nama,
            volume: vol,
            satuan: satuan,
            nominal: nominal,
            supplier_id: supplierId,
            vendor: vendor,
            keterangan: ket,
        });
    }

    return parsed;
}

function handleApplyPasteBox() {
    if (!pasteTextRaw.value.trim()) {
        showPasteBox.value = false;
        return;
    }

    const items = parsePastedMultiText(pasteTextRaw.value);
    if (items.length === 0) {
        alert("Tidak ada baris data valid yang terdeteksi dari teks yang ditempel.");
        return;
    }

    const isOnlyEmptyRows = multiRows.value.every((r) => !r.nama_transaksi && !r.nominal);
    if (isOnlyEmptyRows) {
        multiRows.value = [];
    }

    items.forEach((item) => {
        multiRows.value.push(createNewMultiRow(item));
    });

    pasteTextRaw.value = "";
    showPasteBox.value = false;
}

function handleTablePasteEvent(event, targetRowIdx = null) {
    const clipboardData = event.clipboardData || window.clipboardData;
    if (!clipboardData) return;
    const pastedText = clipboardData.getData("text");
    if (!pastedText) return;

    if (pastedText.includes("\n") || pastedText.includes("\t")) {
        event.preventDefault();
        const items = parsePastedMultiText(pastedText);
        if (items.length === 0) return;

        const startIdx = targetRowIdx !== null ? targetRowIdx : multiRows.value.length;

        items.forEach((item, offset) => {
            const currentIdx = startIdx + offset;
            if (currentIdx < multiRows.value.length) {
                const existing = multiRows.value[currentIdx];
                existing.nama_transaksi = item.nama_transaksi || existing.nama_transaksi;
                if (item.volume !== "") existing.volume = item.volume;
                if (item.satuan) existing.satuan = item.satuan;
                if (item.nominal !== "") existing.nominal = item.nominal;
                if (item.supplier_id) existing.supplier_id = item.supplier_id;
                if (item.keterangan) existing.keterangan = item.keterangan;
            } else {
                multiRows.value.push(createNewMultiRow(item));
            }
        });
    }
}

function submitMultiTransactions() {
    const filledRows = multiRows.value.filter((r) => (r.nama_transaksi && r.nama_transaksi.trim()) || (r.kategori === 'Operasional' && r.jenis_transaksi));
    for (let i = 0; i < filledRows.length; i++) {
        const r = filledRows[i];
        if (r.kategori === 'Operasional' && !r.jenis_transaksi) {
            alert("Baris ke-" + (i + 1) + ": Jenis transaksi operasional wajib dipilih.");
            return;
        }
        if (r.kategori === 'Operasional' && r.jenis_transaksi === 'Lainnya' && (!r.nama_transaksi || !r.nama_transaksi.trim())) {
            alert("Baris ke-" + (i + 1) + ": Rincian transaksi operasional wajib diisi untuk pilihan 'Lainnya'.");
            return;
        }
    }

    if (filledRows.length === 0) {
        alert("Harap isi setidaknya satu baris transaksi pada kolom Nama Barang / Uraian.");
        return;
    }

    for (let i = 0; i < filledRows.length; i++) {
        const row = filledRows[i];
        const rowNum = i + 1;
        const isSimp = ["Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(row.kategori);

        if (!row.tanggal) {
            alert(`Baris #${rowNum}: Tanggal pembayaran wajib diisi.`);
            return;
        }
        if (!row.kategori) {
            alert(`Baris #${rowNum}: Kategori transaksi wajib dipilih.`);
            return;
        }
        if (!row.arus || !["Masuk", "Keluar"].includes(row.arus)) {
            alert(`Baris #${rowNum}: Arus transaksi wajib dipilih (Masuk atau Keluar).`);
            return;
        }
        if (!row.nama_transaksi || !row.nama_transaksi.trim()) {
            alert(`Baris #${rowNum}: ${isSimp ? 'Uraian transaksi' : 'Nama barang/bahan'} wajib diisi.`);
            return;
        }
        if (row.nominal === "" || row.nominal === null || row.nominal === undefined || Number(row.nominal) < 0) {
            alert(`Baris #${rowNum}: Nominal transaksi wajib diisi.`);
            return;
        }

        if (!isSimp) {
            const cleanVol = String(row.volume || "").replace(",", ".");
            const volNum = parseFloat(cleanVol);
            if (row.volume === "" || row.volume === null || row.volume === undefined || isNaN(volNum) || volNum <= 0) {
                alert(`Baris #${rowNum}: Volume (jumlah) wajib diisi angka lebih dari 0.`);
                return;
            }
            if (!row.satuan) {
                alert(`Baris #${rowNum}: Satuan wajib dipilih.`);
                return;
            }
            if (!row.supplier_id) {
                alert(`Baris #${rowNum}: Supplier wajib dipilih.`);
                return;
            }
        }
    }

    const payloadItems = filledRows.map((r) => {
        const isSimp = ["Insentif Fasilitas", "Dana Bantuan Pemerintah"].includes(r.kategori);
        let supName = r.vendor;
        if (!isSimp && r.supplier_id) {
            const foundSup = (props.suppliers || []).find((s) => String(s.id) === String(r.supplier_id));
            if (foundSup) {
                supName = foundSup.nama_usaha;
            }
        }

        return {
            tanggal: r.tanggal,
            tanggal_pesanan: isSimp ? r.tanggal : (r.perlu_npo ? (r.tanggal_pesanan || r.tanggal) : r.tanggal),
            kategori: r.kategori,
            arus: r.arus || "Keluar",
            status: r.status || "Disetujui",
            perlu_npo: isSimp ? false : Boolean(r.perlu_npo),
            work_order_id: isSimp ? null : (r.work_order_id === "manual" ? null : (r.work_order_id || null)),
            nama_transaksi: r.nama_transaksi.trim(),
            volume: isSimp ? 1 : parseFloat(String(r.volume || "").replace(",", ".")),
            satuan: isSimp ? "-" : r.satuan,
            supplier_id: isSimp ? null : (r.supplier_id || null),
            vendor: isSimp ? "-" : (supName || "-"),
            nominal: Number(r.nominal),
            keterangan: r.keterangan || null,
            metode_bayar: "Tunai",
        };
    });

    isSubmittingMulti.value = true;

    router.post(
        "/keuangan/transaksi/batch",
        { items: payloadItems },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmittingMulti.value = false;
                showMultiModal.value = false;
                multiRows.value = [];
            },
            onError: () => {
                isSubmittingMulti.value = false;
                alert("Terjadi kesalahan saat menyimpan transaksi multi. Silakan periksa kembali data Anda.");
            },
        }
    );
}

// Formatter dua arah untuk field input nominal (dengan format Rp dan titik ribuan)
const formattedNominal = computed({
    get() {
        if (manualForm.value.nominal === "" || manualForm.value.nominal === null || manualForm.value.nominal === undefined) {
            return "";
        }
        const num = Math.round(Number(manualForm.value.nominal) || 0);
        return num > 0 ? num.toLocaleString("id-ID") : (num === 0 ? "0" : "");
    },
    set(val) {
        if (!val) {
            manualForm.value.nominal = "";
            return;
        }
        const clean = String(val).replace(/\D/g, "");
        manualForm.value.nominal = clean ? parseInt(clean, 10) : "";
    },
});

function onSupplierSelectChange() {
    if (!manualForm.value.supplier_id) {
        manualForm.value.vendor = "";
        return;
    }
    const found = (props.suppliers || []).find((s) => String(s.id) === String(manualForm.value.supplier_id));
    if (found) {
        manualForm.value.vendor = found.nama_usaha;
    }
}

// State Modal Detail
const showDetailModal = ref(false);
const selectedTrx = ref(null);

// State Modal Konfirmasi Hapus
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

// State Dropdown & Modal Download (Nota PO & Kuitansi)
const showDownloadDropdown = ref(false);
const showReceiptModal = ref(false);
const selectedReceiptTrx = ref(null);
const isDownloadingNotaPo = ref(false);

// State Dropdown Aksi Download per Baris Tabel
const activeRowDropdownId = ref(null);

function toggleRowDropdown(id) {
    if (activeRowDropdownId.value === id) {
        activeRowDropdownId.value = null;
    } else {
        activeRowDropdownId.value = id;
    }
}

function openReceiptModal(trx = null) {
    selectedReceiptTrx.value = trx;
    showReceiptModal.value = true;
}

function angkaKeTerbilang(nilai) {
    const angka = Math.floor(Math.abs(Number(nilai) || 0));
    if (angka === 0) return "Nol";
    const satuan = [
        "",
        "Satu",
        "Dua",
        "Tiga",
        "Empat",
        "Lima",
        "Enam",
        "Tujuh",
        "Delapan",
        "Sembilan",
        "Sepuluh",
        "Sebelas",
    ];
    if (angka < 12) return satuan[angka];
    if (angka < 20) return angkaKeTerbilang(angka - 10) + " Belas";
    if (angka < 100) {
        const sisa = angka % 10;
        return (
            angkaKeTerbilang(Math.floor(angka / 10)) +
            " Puluh" +
            (sisa !== 0 ? " " + satuan[sisa] : "")
        );
    }
    if (angka < 200) {
        const sisa = angka - 100;
        return "Seratus" + (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "");
    }
    if (angka < 1000) {
        const sisa = angka % 100;
        return (
            angkaKeTerbilang(Math.floor(angka / 100)) +
            " Ratus" +
            (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
        );
    }
    if (angka < 2000) {
        const sisa = angka - 1000;
        return "Seribu" + (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "");
    }
    if (angka < 1000000) {
        const sisa = angka % 1000;
        return (
            angkaKeTerbilang(Math.floor(angka / 1000)) +
            " Ribu" +
            (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
        );
    }
    if (angka < 1000000000) {
        const sisa = angka % 1000000;
        return (
            angkaKeTerbilang(Math.floor(angka / 1000000)) +
            " Juta" +
            (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
        );
    }
    if (angka < 1000000000000) {
        const sisa = angka % 1000000000;
        return (
            angkaKeTerbilang(Math.floor(angka / 1000000000)) +
            " Miliar" +
            (sisa !== 0 ? " " + angkaKeTerbilang(sisa) : "")
        );
    }
    return (
        angkaKeTerbilang(Math.floor(angka / 1000000000000)) +
        " Triliun" +
        (angka % 1000000000000 !== 0 ? " " + angkaKeTerbilang(angka % 1000000000000) : "")
    );
}

// Download Kuitansi PDF khusus untuk 1 baris transaksi ini saja (Langsung Unduh)
async function handleDownloadKuitansiSingleDirect(trx) {
    if (!trx) return;
    try {
        const doc = new jsPDF({
            orientation: "portrait",
            unit: "mm",
            format: "a4",
        });

        const noKwt = trx.nomor_transaksi ? `KWT-${trx.nomor_transaksi}` : `KWT/TRX/${new Date().toISOString().slice(0, 10).replace(/-/g, "")}/001`;
        const kopConfig = getActiveKopConfig();
        const fullSppgName = kopConfig?.nama_instansi_1 || props.unitSppg?.nama_sppg || "SPPG BULELENG SUKASADA TEGALLINGGAH";

        // 1. Kop Dokumen Resmi
        try {
            if (LOGO_BGN_BASE64) {
                doc.addImage(LOGO_BGN_BASE64, "PNG", 18, 14, 18, 18);
            }
        } catch (e) {}

        try {
            if (LOGO_YAYASAN_BASE64) {
                doc.addImage(LOGO_YAYASAN_BASE64, "PNG", 174, 14, 18, 18);
            }
        } catch (e) {}

        doc.setFont("times", "bold");
        doc.setFontSize(11.5);
        doc.setTextColor(0, 0, 0);
        doc.text(
            (kopConfig?.nama_instansi_1 || "SPPG BULELENG SUKASADA TEGALLINGGAH").toUpperCase(),
            105,
            18.5,
            { align: "center" }
        );

        doc.setFontSize(10.5);
        doc.text(
            (kopConfig?.nama_instansi_2 || "YAYASAN PESANTREN MIFTAHUL ULUM").toUpperCase(),
            105,
            23.5,
            { align: "center" }
        );

        doc.setFont("times", "normal");
        doc.setFontSize(8.5);
        doc.setTextColor(30, 41, 59);
        doc.text(
            kopConfig?.alamat_lengkap ||
                "Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali",
            105,
            28,
            { align: "center" }
        );

        const emailStr = kopConfig?.email ? `E-mail: ${kopConfig.email}` : "";
        if (emailStr) {
            doc.text(emailStr, 105, 32, { align: "center" });
        }

        // Garis Ganda Pembatas Kop
        doc.setDrawColor(0, 0, 0);
        doc.setLineWidth(0.6);
        doc.line(18, 36, 192, 36);
        doc.setLineWidth(0.2);
        doc.line(18, 37, 192, 37);

        // 2. Judul K U I T A N S I & Nomor
        doc.setFont("times", "bold");
        doc.setFontSize(15);
        doc.setTextColor(0, 0, 0);
        doc.text("K U I T A N S I", 105, 47, { align: "center" });

        doc.setLineWidth(0.4);
        doc.line(82, 48.5, 128, 48.5);

        doc.setFont("times", "normal");
        doc.setFontSize(10.5);
        doc.setTextColor(30, 41, 59);
        doc.text(noKwt, 105, 54, { align: "center" });

        // 3. Rincian Badan Kuitansi
        let curY = 66;
        doc.setFont("times", "normal");
        doc.setFontSize(11);
        doc.setTextColor(0, 0, 0);

        // Baris 1: Sudah Terima dari
        doc.text("Sudah Terima dari", 20, curY);
        doc.text(":", 58, curY);
        doc.text(`Staf Pengawas Keuangan ${fullSppgName}`, 62, curY);

        curY += 5.5;
        doc.setFont("times", "normal");
        doc.text("ID SPPG : ", 62, curY);
        const idW = doc.getTextWidth("ID SPPG : ");
        doc.setFont("times", "bold");
        doc.text(props.unitSppg?.id_sppg || "QQCV0LUG", 62 + idW, curY);

        // Baris 2: Uang Sejumlah
        curY += 10.5;
        doc.setFont("times", "normal");
        doc.text("Uang Sejumlah", 20, curY);
        doc.text(":", 58, curY);

        doc.setFont("times", "italic");
        doc.setFontSize(10.5);
        const terbilangText = `# ${angkaKeTerbilang(trx.nominal || 0)} Rupiah #`;
        const terbilangLines = doc.splitTextToSize(terbilangText, 122);
        const boxH = Math.max(9, terbilangLines.length * 4.8 + 4.5);

        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(203, 213, 225);
        doc.setLineWidth(0.25);
        doc.roundedRect(62, curY - 5, 128, boxH, 1.5, 1.5, "FD");

        doc.setTextColor(15, 23, 42);
        doc.text(terbilangLines, 65, curY);

        // Nominal Rp
        curY += boxH + 4.5;
        doc.setFont("times", "bold");
        doc.setFontSize(13);
        doc.setTextColor(0, 0, 0);
        const fmtNom = new Intl.NumberFormat("id-ID").format(trx.nominal || 0);
        doc.text(`Rp. ${fmtNom},-`, 62, curY);

        // Baris 3: Untuk Pembayaran
        curY += 10.5;
        doc.setFont("times", "normal");
        doc.setFontSize(11);
        doc.text("Untuk Pembayaran", 20, curY);
        doc.text(":", 58, curY);

        const uraianParts = [];
        if (trx.nama_transaksi) uraianParts.push(`Pembelian ${trx.nama_transaksi}`);
        if (trx.volume || trx.qty) uraianParts.push(`(${trx.volume || trx.qty} ${trx.satuan && trx.satuan !== '-' ? trx.satuan : 'Kg'})`);
        if (trx.kategori) uraianParts.push(`- Kategori ${trx.kategori}`);
        if (trx.vendor && trx.vendor !== "-") uraianParts.push(`Supplier: ${trx.vendor}`);
        if (trx.keterangan && trx.keterangan !== "-") uraianParts.push(`- ${trx.keterangan}`);
        const ketText = uraianParts.join(" ");
        const ketLines = doc.splitTextToSize(ketText, 128);
        doc.text(ketLines, 62, curY);

        // 4. Tanda Tangan 2 Kolom
        const sigY = Math.max(curY + ketLines.length * 5.2 + 20, 205);
        const tglBayar = trx.tanggal ? formatTanggalIndo(trx.tanggal) : formatTanggalIndo(new Date());

        doc.setFont("times", "normal");
        doc.setFontSize(11);
        doc.text(`Desa Tegallinggah, ${tglBayar}`, 152, sigY, { align: "center" });

        doc.text("Staf Pengawas Keuangan", 58, sigY + 6, { align: "center" });
        doc.text(fullSppgName, 58, sigY + 11.5, { align: "center" });

        doc.setFont("times", "bold");
        const namaKiri = "( I Gusti Ayu Made Padmi Swari, S.Ak. )";
        doc.text(namaKiri, 58, sigY + 45, { align: "center" });
        const namaKiriW = doc.getTextWidth(namaKiri);
        doc.setLineWidth(0.3);
        doc.line(58 - namaKiriW / 2, sigY + 46, 58 + namaKiriW / 2, sigY + 46);

        doc.setFont("times", "normal");
        doc.text("Yang Menerima,", 152, sigY + 6, { align: "center" });

        doc.setFont("times", "bold");
        const penerimaNama = trx.vendor && trx.vendor !== '-' ? trx.vendor : (trx.supplier?.nama_usaha || "Penyedia / Rekanan");
        const namaKanan = `( ${penerimaNama} )`;
        doc.text(namaKanan, 152, sigY + 45, { align: "center" });
        const namaKananW = doc.getTextWidth(namaKanan);
        doc.setLineWidth(0.3);
        doc.line(152 - namaKananW / 2, sigY + 46, 152 + namaKananW / 2, sigY + 46);

        const cleanNoTrx = (trx.nomor_transaksi || 'TRX').replace(/[^a-zA-Z0-9_-]/g, '_');
        doc.save(`Kuitansi_${cleanNoTrx}.pdf`);
    } catch (e) {
        console.error("Gagal generate PDF kuitansi:", e);
        alert("Gagal mengunduh PDF kuitansi: " + e.message);
    }
}

// Download Nota PO Excel khusus untuk 1 baris transaksi ini saja (Langsung Unduh 1 Lembar Resmi)
async function handleDownloadNotaPoSingle(trx) {
    if (!trx) return;
    try {
        isDownloadingNotaPo.value = true;
        const supObj = (props.suppliers || []).find((s) => String(s.id) === String(trx.supplier_id)) || trx.supplier || {};
        const vol = Number(trx.volume) || Number(trx.qty) || 1;
        const nom = Number(trx.nominal) || 0;
        const cleanNoTrx = (trx.nomor_transaksi || 'TRX').replace(/[^a-zA-Z0-9_-]/g, '_');
        const filename = `Nota_PO_${cleanNoTrx}.xlsx`;

        const item = {
            nama: trx.nama_transaksi,
            nama_bahan: trx.nama_transaksi,
            qty_beli_po_kg: vol,
            gross_kg: vol,
            stok_digunakan_kg: 0,
            satuan: trx.satuan && trx.satuan !== '-' ? trx.satuan : 'Kg',
            harga_aktual: vol > 0 ? Math.round(nom / vol) : nom,
            harga_master: vol > 0 ? Math.round(nom / vol) : nom,
            total_harga: nom,
            jenis_transaksi: (trx.kategori || '').toLowerCase().includes('operasional') ? 'Operasional' : 'Bahan Baku',
            kategori: trx.kategori || 'Bahan Baku',
            supplier: {
                id: trx.supplier_id || supObj.id || null,
                nama_usaha: supObj.nama_usaha || trx.vendor || 'Supplier',
                namaPemilik: supObj.nama_pemilik || '-',
                alamat: supObj.alamat_lengkap || supObj.alamat || 'Jl. Raya Angling Darma, Sukasada'
            },
            vendor: supObj.nama_usaha || trx.vendor || 'Supplier',
            keterangan: trx.keterangan && trx.keterangan !== '-' ? trx.keterangan : '-'
        };

        const basePesananDate = trx.tanggal_pesanan || trx.tanggal || getTodayStr();
        const poData = {
            nomor_transaksi: trx.nomor_transaksi,
            tanggal: trx.tanggal || getTodayStr(),
            tanggal_pesanan: basePesananDate,
            created_at: basePesananDate,
            kategori: trx.kategori || 'Bahan Baku',
            total_nominal: nom,
            porsi_pk: 0,
            porsi_pb: 0,
            total_porsi: 0,
            items: [item],
            supplier: item.supplier,
            vendor: item.vendor
        };

        await exportPoExcel(poData, {
            tanggalPesanan: basePesananDate,
            filename: filename,
            singleOnly: true,
            poNumber: trx.nomor_transaksi
        });
    } catch (err) {
        console.error("Gagal download Nota PO:", err);
        alert("Gagal mengunduh file Nota PO: " + err.message);
    } finally {
        isDownloadingNotaPo.value = false;
    }
}

async function handleDownloadNotaPoAll() {
    const list = filteredTransactions.value.filter(t => t.perlu_npo !== false);
    if (list.length === 0) {
        alert("Tidak ada data transaksi dengan status Perlu NPO pada filter yang dipilih.");
        return;
    }

    try {
        isDownloadingNotaPo.value = true;
        const items = list.map((trx) => {
            const supObj = (props.suppliers || []).find((s) => String(s.id) === String(trx.supplier_id)) || trx.supplier || {};
            const vol = Number(trx.volume) || Number(trx.qty) || 1;
            const nom = Number(trx.nominal) || 0;
            return {
                nama: trx.nama_transaksi,
                nama_bahan: trx.nama_transaksi,
                qty_beli_po_kg: vol,
                gross_kg: vol,
                stok_digunakan_kg: 0,
                satuan: trx.satuan && trx.satuan !== '-' ? trx.satuan : 'Kg',
                harga_aktual: vol > 0 ? Math.round(nom / vol) : nom,
                harga_master: vol > 0 ? Math.round(nom / vol) : nom,
                total_harga: nom,
                jenis_transaksi: (trx.kategori || '').toLowerCase().includes('operasional') ? 'Operasional' : 'Bahan Baku',
                kategori: trx.kategori || 'Bahan Baku',
                supplier: {
                    id: trx.supplier_id || supObj.id || null,
                    nama_usaha: supObj.nama_usaha || trx.vendor || 'Supplier',
                    namaPemilik: supObj.nama_pemilik || '-',
                    alamat: supObj.alamat_lengkap || supObj.alamat || 'Jl. Raya Angling Darma, Sukasada'
                },
                vendor: supObj.nama_usaha || trx.vendor || 'Supplier',
                keterangan: trx.keterangan && trx.keterangan !== '-' ? trx.keterangan : '-'
            };
        });

        const totalNom = items.reduce((sum, it) => sum + (Number(it.total_harga) || 0), 0);
        const basePesananDate = filterStartDate.value || getTodayStr();
        const poData = {
            tanggal: filterEndDate.value || filterStartDate.value || getTodayStr(),
            tanggal_pesanan: basePesananDate,
            created_at: basePesananDate,
            total_nominal: totalNom,
            porsi_pk: 0,
            porsi_pb: 0,
            total_porsi: 0,
            items: items
        };

        await exportPoExcel(poData, { tanggalPesanan: basePesananDate });
    } catch (err) {
        console.error("Gagal download Nota PO:", err);
        alert("Gagal mengunduh file Nota PO: " + err.message);
    } finally {
        isDownloadingNotaPo.value = false;
    }
}

// Filter data berdasarkan rentang waktu
const dateFilteredList = computed(() => {
    return (props.transaksiList || []).filter((trx) => {
        if (!isFilterAllTime.value) {
            const tgl = String(trx.tanggal || "").substring(0, 10);
            if (tgl) {
                if (filterStartDate.value && tgl < filterStartDate.value) return false;
                if (filterEndDate.value && tgl > filterEndDate.value) return false;
            }
        }
        return true;
    });
});

// Perhitungan 4 Card Banner (Kategori Transaksi) mencakup Total, Masuk & Keluar
function calculateCategoryStats(kategoriName) {
    const items = dateFilteredList.value.filter(
        (t) => (t.kategori || "").toLowerCase() === kategoriName.toLowerCase() && t.status === "Disetujui"
    );
    const masukItems = items.filter((it) => it.arus === "Masuk");
    const keluarItems = items.filter((it) => it.arus !== "Masuk");

    return {
        nominal: items.reduce((sum, it) => sum + (Number(it.nominal) || 0), 0),
        count: items.length,
        masuk: masukItems.reduce((sum, it) => sum + (Number(it.nominal) || 0), 0),
        masukCount: masukItems.length,
        keluar: keluarItems.reduce((sum, it) => sum + (Number(it.nominal) || 0), 0),
        keluarCount: keluarItems.length,
    };
}

const totalBahanBaku = computed(() => calculateCategoryStats("Bahan Baku"));
const totalOperasional = computed(() => calculateCategoryStats("Operasional"));
const totalInsentifFasilitas = computed(() => calculateCategoryStats("Insentif Fasilitas"));
const totalDanaBantuanPemerintah = computed(() => calculateCategoryStats("Dana Bantuan Pemerintah"));

// Toggle filter kategori via Banner Card
function toggleCategoryFilter(cat) {
    if (kategoriFilter.value === cat) {
        kategoriFilter.value = "semua";
    } else {
        kategoriFilter.value = cat;
    }
}

// Data tabel yang telah disaring (Tanggal + Status + Kategori + Search)
const filteredTransactions = computed(() => {
    return dateFilteredList.value.filter((trx) => {
        // Status Filter: Hanya ada Ditolak atau Disetujui (atau Semua)
        if (statusFilter.value !== "semua") {
            if (trx.status !== statusFilter.value) return false;
        }

        // Kategori Filter
        if (kategoriFilter.value !== "semua") {
            if (trx.kategori !== kategoriFilter.value) return false;
        }

        // Search Query
        if (searchQuery.value && searchQuery.value.trim() !== "") {
            const q = searchQuery.value.toLowerCase().trim();
            const noRef = String(trx.nomor_transaksi || "").toLowerCase();
            const nama = String(trx.nama_transaksi || "").toLowerCase();
            const vendor = String(trx.vendor || "").toLowerCase();
            const ket = String(trx.keterangan || "").toLowerCase();
            const kat = String(trx.kategori || "").toLowerCase();

            if (
                !noRef.includes(q) &&
                !nama.includes(q) &&
                !vendor.includes(q) &&
                !ket.includes(q) &&
                !kat.includes(q)
            ) {
                return false;
            }
        }

        return true;
    });
});

// Total nominal data tabel saat ini
const totalNominalTabel = computed(() => {
    return filteredTransactions.value.reduce(
        (sum, it) => sum + (Number(it.nominal) || 0),
        0
    );
});

// Buka Modal Tambah Transaksi
function openAddManualModal() {
    isEditing.value = false;
    editingId.value = null;
    manualForm.value = {
        perlu_npo: true,
        tanggal_pesanan: "",
        tanggal: "",
        work_order_id: "",
        kategori: "",
        jenis_transaksi: "",
        arus: "Keluar",
        nama_transaksi: "",
        volume: "",
        satuan: "",
        nominal: "",
        status: "",
        supplier_id: "",
        vendor: "",
        keterangan: "",
        metode_bayar: "Tunai",
    };
    showManualModal.value = true;
}

// Buka Modal Edit Transaksi
function openEditManualModal(trx) {
    if (trx.source_type !== "manual") return;
    isEditing.value = true;
    editingId.value = trx.manual_id || trx.db_id;

    // Cek apakah supplier_id cocok dengan salah satu suppliers yang ada
    let supId = trx.supplier_id || "";
    let vendorVal = trx.vendor !== "-" ? trx.vendor : "";
    if (!supId && vendorVal) {
        const found = (props.suppliers || []).find((s) => s.nama_usaha.toLowerCase() === vendorVal.toLowerCase());
        if (found) {
            supId = found.id;
        } else {
            supId = "manual";
        }
    }

    let jenisOperasional = "";
    if (trx.kategori === "Operasional") {
        if (trx.nama_transaksi && JENIS_TRANSAKSI_OPERASIONAL.includes(trx.nama_transaksi)) {
            jenisOperasional = trx.nama_transaksi;
        } else if (trx.nama_transaksi) {
            jenisOperasional = "Lainnya";
        }
    }

    manualForm.value = {
        perlu_npo: trx.perlu_npo !== undefined ? Boolean(trx.perlu_npo) : true,
        tanggal_pesanan: trx.tanggal_pesanan || trx.tanggal || getTodayStr(),
        tanggal: trx.tanggal || getTodayStr(),
        work_order_id: trx.work_order_id || (trx.work_order?.id || "manual"),
        kategori: trx.kategori || "Bahan Baku",
        jenis_transaksi: jenisOperasional,
        arus: trx.arus || (trx.kategori === "Dana Bantuan Pemerintah" ? "Masuk" : "Keluar"),
        nama_transaksi: trx.nama_transaksi || "",
        volume: trx.volume !== undefined && trx.volume !== null ? trx.volume : (trx.qty || ""),
        satuan: trx.satuan && trx.satuan !== "-" ? trx.satuan : "Kg",
        nominal: trx.nominal || "",
        status: trx.status || "Disetujui",
        supplier_id: supId,
        vendor: vendorVal,
        keterangan: trx.keterangan !== "-" ? trx.keterangan : "",
        metode_bayar: trx.metode_bayar || "Tunai",
    };
    showManualModal.value = true;
}

// Submit Simpan / Update Transaksi
function submitManualTransaction() {
    if (!manualForm.value.kategori) {
        alert("Kategori transaksi wajib dipilih.");
        return;
    }
    if (manualForm.value.kategori === "Operasional" && !manualForm.value.jenis_transaksi) {
        alert("Jenis transaksi operasional wajib dipilih.");
        return;
    }
    if (!manualForm.value.arus) {
        alert("Arus transaksi (Masuk / Keluar) wajib dipilih.");
        return;
    }
    if (!manualForm.value.status) {
        alert("Status transaksi wajib dipilih.");
        return;
    }
    if (!manualForm.value.tanggal) {
        alert("Tanggal pembayaran wajib diisi.");
        return;
    }

    if (!isSimplifiedCategory.value) {
        if (manualForm.value.perlu_npo && !manualForm.value.tanggal_pesanan) {
            alert("Tanggal pesanan wajib diisi.");
            return;
        }
        if (!manualForm.value.work_order_id) {
            alert("Sandingkan Work Order wajib dipilih.");
            return;
        }
        if (!manualForm.value.nama_transaksi || !manualForm.value.nama_transaksi.trim()) {
            alert("Nama barang/bahan wajib diisi.");
            return;
        }
        if (manualForm.value.volume === "" || manualForm.value.volume === null || manualForm.value.volume === undefined) {
            alert("Volume (jumlah) wajib diisi.");
            return;
        }
        if (!manualForm.value.satuan) {
            alert("Satuan wajib dipilih.");
            return;
        }
        if (!manualForm.value.supplier_id) {
            alert("Supplier wajib dipilih dari daftar supplier yang tersedia.");
            return;
        }
        if (manualForm.value.nominal === "" || manualForm.value.nominal === null || manualForm.value.nominal === undefined) {
            alert("Harga barang/bahan wajib diisi.");
            return;
        }
    } else {
        if (!manualForm.value.nama_transaksi || !manualForm.value.nama_transaksi.trim()) {
            alert("Uraian transaksi wajib diisi.");
            return;
        }
        if (manualForm.value.nominal === "" || manualForm.value.nominal === null || manualForm.value.nominal === undefined) {
            alert("Nominal wajib diisi.");
            return;
        }

        // Kunci nilai khusus Insentif Fasilitas & Dana Bantuan Pemerintah
        manualForm.value.perlu_npo = false;
        manualForm.value.tanggal_pesanan = manualForm.value.tanggal;
        manualForm.value.work_order_id = "manual";
        manualForm.value.volume = 1;
        manualForm.value.satuan = "-";
        manualForm.value.supplier_id = "";
        manualForm.value.vendor = "-";
    }

    // Pastikan jika perlu_npo = false, tanggal_pesanan diisi tanggal pembayaran
    if (!manualForm.value.perlu_npo) {
        manualForm.value.tanggal_pesanan = manualForm.value.tanggal;
    }

    // Sinkronkan nama vendor dari supplier yang dipilih jika ada
    if (manualForm.value.supplier_id) {
        const found = (props.suppliers || []).find((s) => String(s.id) === String(manualForm.value.supplier_id));
        if (found) {
            manualForm.value.vendor = found.nama_usaha;
        }
    }

    isSubmitting.value = true;

    const payload = {
        ...manualForm.value,
        supplier_id: manualForm.value.supplier_id || null,
        volume: manualForm.value.volume,
        satuan: manualForm.value.satuan,
    };

    if (isEditing.value && editingId.value) {
        router.put(
            `/keuangan/transaksi/${editingId.value}`,
            payload,
            {
                preserveScroll: true,
                onSuccess: () => {
                    isSubmitting.value = false;
                    showManualModal.value = false;
                },
                onError: () => {
                    isSubmitting.value = false;
                },
            }
        );
    } else {
        router.post("/keuangan/transaksi", payload, {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                showManualModal.value = false;
            },
            onError: () => {
                isSubmitting.value = false;
            },
        });
    }
}

// Konfirmasi Hapus Transaksi
function confirmDelete(trx) {
    itemToDelete.value = trx;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!itemToDelete.value) return;
    const targetId = itemToDelete.value.manual_id || itemToDelete.value.db_id;
    isSubmitting.value = true;

    router.delete(`/keuangan/transaksi/${targetId}`, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

// ─── Setujui Cepat Transaksi (Ditunda -> Disetujui) ─────────────────────────
const isUpdatingStatus = ref(false);

function quickApproveTrx(trx) {
    if (!trx) return;
    const targetId = trx.manual_id || trx.db_id || trx.id;
    if (!targetId) return;

    const confirmMsg = `Setujui transaksi "${trx.nama_transaksi}" (${formatRupiah(trx.nominal)})?\n\nSetelah disetujui, transaksi ini akan berstatus "Disetujui" dan sah terhitung ke dalam realisasi keuangan.`;
    if (!confirm(confirmMsg)) return;

    isUpdatingStatus.value = true;
    router.patch(
        `/transaksi/${targetId}/status`,
        { status: "Disetujui" },
        {
            preserveScroll: true,
            onSuccess: () => {
                isUpdatingStatus.value = false;
                trx.status = "Disetujui";
                if (selectedTrx.value && (selectedTrx.value.manual_id === targetId || selectedTrx.value.db_id === targetId || selectedTrx.value.id === targetId)) {
                    selectedTrx.value.status = "Disetujui";
                }
            },
            onError: (err) => {
                isUpdatingStatus.value = false;
                console.error("Gagal menyetujui transaksi:", err);
                alert("Gagal menyetujui transaksi. Silakan coba lagi.");
            },
        }
    );
}

// Buka Modal Detail Transaksi
function openDetail(trx) {
    selectedTrx.value = trx;
    showDetailModal.value = true;
}

// Reset semua filter
function resetFilters() {
    isInitialMount = false;
    searchQuery.value = "";
    statusFilter.value = "semua";
    kategoriFilter.value = "semua";
    filterStartDate.value = getTodayStr();
    filterEndDate.value = getTodayStr();
    filterDateMode.value = "hari_ini";
    filterPeriodeId.value = "";
    isFilterAllTime.value = false;
    syncFilterToUrl();
}

// Watchers untuk sinkronisasi filter status & kategori ke URL
watch(statusFilter, () => {
    isInitialMount = false;
    syncFilterToUrl();
});

watch(kategoriFilter, () => {
    isInitialMount = false;
    syncFilterToUrl();
});

let searchDebounceTimer = null;
watch(searchQuery, () => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        isInitialMount = false;
        syncFilterToUrl();
    }, 250);
});

// Sinkronisasi jika periode list dimuat belakangan
watch(
    () => props.periodes,
    (newPeriodes) => {
        if (!newPeriodes || newPeriodes.length === 0) return;
        if (filterDateMode.value === "periode" && filterPeriodeId.value && (!filterStartDate.value || !filterEndDate.value)) {
            const foundP = newPeriodes.find((p) => String(p.id) === String(filterPeriodeId.value));
            if (foundP && foundP.tanggal_mulai && foundP.tanggal_selesai) {
                filterStartDate.value = String(foundP.tanggal_mulai).substring(0, 10);
                filterEndDate.value = String(foundP.tanggal_selesai).substring(0, 10);
                syncFilterToUrl();
            }
        }
    },
    { immediate: true }
);

// Listener browser back / forward button (popstate)
function handlePopState() {
    const state = getInitialFilterState();
    filterStartDate.value = state.start;
    filterEndDate.value = state.end;
    filterDateMode.value = state.mode;
    filterPeriodeId.value = state.periodeId;
    isFilterAllTime.value = state.isAllTime;
    statusFilter.value = state.status;
    kategoriFilter.value = state.kategori;
    searchQuery.value = state.search;
}

onMounted(() => {
    window.addEventListener("popstate", handlePopState);
});

onUnmounted(() => {
    window.removeEventListener("popstate", handlePopState);
});
</script>

<template>
    <div class="space-y-6">
        <!-- CARD BANNER DI ATAS: KATEGORI TRANSAKSI -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Bahan Baku -->
            <div
                @click="toggleCategoryFilter('Bahan Baku')"
                class="relative p-5 rounded-2xl border transition-all duration-200 cursor-pointer overflow-hidden group select-none shadow-xs"
                :class="[
                    kategoriFilter === 'Bahan Baku'
                        ? 'bg-gradient-to-br from-emerald-500 to-teal-700 text-white border-emerald-600 shadow-md ring-2 ring-emerald-500/40'
                        : 'bg-white hover:bg-emerald-50/40 border-slate-200 hover:border-emerald-300 text-slate-800'
                ]"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[11px] font-extrabold uppercase tracking-wider"
                                :class="kategoriFilter === 'Bahan Baku' ? 'text-emerald-100' : 'text-emerald-700'"
                            >
                                Kategori Transaksi
                            </span>
                            <span
                                v-if="kategoriFilter === 'Bahan Baku'"
                                class="px-1.5 py-0.2 rounded text-[10px] font-black bg-white/20 text-white"
                            >
                                Filter Aktif
                            </span>
                        </div>
                        <h4
                            class="text-base sm:text-lg font-black mt-1"
                            :class="kategoriFilter === 'Bahan Baku' ? 'text-white' : 'text-slate-900'"
                        >
                            Bahan Baku
                        </h4>
                    </div>
                    <div
                        class="p-2.5 rounded-xl transition-colors shrink-0"
                        :class="[
                            kategoriFilter === 'Bahan Baku'
                                ? 'bg-white/20 text-white'
                                : 'bg-emerald-100/80 text-emerald-700 group-hover:bg-emerald-200/80'
                        ]"
                    >
                        <Package class="h-5 w-5" />
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t" :class="kategoriFilter === 'Bahan Baku' ? 'border-white/20' : 'border-slate-100'">
                    <div class="flex items-baseline justify-between gap-2">
                        <span
                            class="text-xl sm:text-2xl font-black tracking-tight"
                            :class="kategoriFilter === 'Bahan Baku' ? 'text-white' : 'text-emerald-700'"
                        >
                            {{ formatRupiah(totalBahanBaku.nominal) }}
                        </span>
                        <span
                            class="text-xs font-bold shrink-0"
                            :class="kategoriFilter === 'Bahan Baku' ? 'text-emerald-100' : 'text-slate-500'"
                        >
                            {{ totalBahanBaku.count }} Transaksi
                        </span>
                    </div>

                    <!-- Rincian Arus Masuk & Keluar -->
                    <div class="grid grid-cols-2 gap-2 mt-2.5 pt-2 border-t" :class="kategoriFilter === 'Bahan Baku' ? 'border-white/15' : 'border-slate-100'">
                        <div
                            class="p-1.5 px-2 rounded-xl flex items-center justify-between gap-1 text-[11px]"
                            :class="kategoriFilter === 'Bahan Baku'
                                ? 'bg-white/15 text-white'
                                : 'bg-emerald-50 text-emerald-800 border border-emerald-100/80'"
                        >
                            <span class="inline-flex items-center gap-1 font-extrabold text-[10px]" :class="kategoriFilter === 'Bahan Baku' ? 'text-emerald-100' : 'text-emerald-700'">
                                <ArrowDownLeft class="w-3 h-3 text-emerald-500" />
                                Masuk:
                            </span>
                            <span class="font-black truncate" :title="formatRupiah(totalBahanBaku.masuk)">
                                {{ formatRupiah(totalBahanBaku.masuk) }}
                            </span>
                        </div>
                        <div
                            class="p-1.5 px-2 rounded-xl flex items-center justify-between gap-1 text-[11px]"
                            :class="kategoriFilter === 'Bahan Baku'
                                ? 'bg-white/15 text-white'
                                : 'bg-rose-50 text-rose-800 border border-rose-100/80'"
                        >
                            <span class="inline-flex items-center gap-1 font-extrabold text-[10px]" :class="kategoriFilter === 'Bahan Baku' ? 'text-rose-100' : 'text-rose-700'">
                                <ArrowUpRight class="w-3 h-3 text-rose-500" />
                                Keluar:
                            </span>
                            <span class="font-black truncate" :title="formatRupiah(totalBahanBaku.keluar)">
                                {{ formatRupiah(totalBahanBaku.keluar) }}
                            </span>
                        </div>
                    </div>

                    <p
                        class="text-[11px] mt-2 font-medium"
                        :class="kategoriFilter === 'Bahan Baku' ? 'text-emerald-100' : 'text-slate-400'"
                    >
                        Pengadaan bahan pangan mentah, sembako & bumbu dapur
                    </p>
                </div>
            </div>

            <!-- Card 2: Operasional -->
            <div
                @click="toggleCategoryFilter('Operasional')"
                class="relative p-5 rounded-2xl border transition-all duration-200 cursor-pointer overflow-hidden group select-none shadow-xs"
                :class="[
                    kategoriFilter === 'Operasional'
                        ? 'bg-gradient-to-br from-indigo-600 to-purple-800 text-white border-indigo-600 shadow-md ring-2 ring-indigo-500/40'
                        : 'bg-white hover:bg-indigo-50/40 border-slate-200 hover:border-indigo-300 text-slate-800'
                ]"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[11px] font-extrabold uppercase tracking-wider"
                                :class="kategoriFilter === 'Operasional' ? 'text-indigo-100' : 'text-indigo-700'"
                            >
                                Kategori Transaksi
                            </span>
                            <span
                                v-if="kategoriFilter === 'Operasional'"
                                class="px-1.5 py-0.2 rounded text-[10px] font-black bg-white/20 text-white"
                            >
                                Filter Aktif
                            </span>
                        </div>
                        <h4
                            class="text-base sm:text-lg font-black mt-1"
                            :class="kategoriFilter === 'Operasional' ? 'text-white' : 'text-slate-900'"
                        >
                            Operasional
                        </h4>
                    </div>
                    <div
                        class="p-2.5 rounded-xl transition-colors shrink-0"
                        :class="[
                            kategoriFilter === 'Operasional'
                                ? 'bg-white/20 text-white'
                                : 'bg-indigo-100/80 text-indigo-700 group-hover:bg-indigo-200/80'
                        ]"
                    >
                        <Sliders class="h-5 w-5" />
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t" :class="kategoriFilter === 'Operasional' ? 'border-white/20' : 'border-slate-100'">
                    <div class="flex items-baseline justify-between gap-2">
                        <span
                            class="text-xl sm:text-2xl font-black tracking-tight"
                            :class="kategoriFilter === 'Operasional' ? 'text-white' : 'text-indigo-700'"
                        >
                            {{ formatRupiah(totalOperasional.nominal) }}
                        </span>
                        <span
                            class="text-xs font-bold shrink-0"
                            :class="kategoriFilter === 'Operasional' ? 'text-indigo-100' : 'text-slate-500'"
                        >
                            {{ totalOperasional.count }} Transaksi
                        </span>
                    </div>

                    <!-- Rincian Arus Masuk & Keluar -->
                    <div class="grid grid-cols-2 gap-2 mt-2.5 pt-2 border-t" :class="kategoriFilter === 'Operasional' ? 'border-white/15' : 'border-slate-100'">
                        <div
                            class="p-1.5 px-2 rounded-xl flex items-center justify-between gap-1 text-[11px]"
                            :class="kategoriFilter === 'Operasional'
                                ? 'bg-white/15 text-white'
                                : 'bg-emerald-50 text-emerald-800 border border-emerald-100/80'"
                        >
                            <span class="inline-flex items-center gap-1 font-extrabold text-[10px]" :class="kategoriFilter === 'Operasional' ? 'text-emerald-100' : 'text-emerald-700'">
                                <ArrowDownLeft class="w-3 h-3 text-emerald-500" />
                                Masuk:
                            </span>
                            <span class="font-black truncate" :title="formatRupiah(totalOperasional.masuk)">
                                {{ formatRupiah(totalOperasional.masuk) }}
                            </span>
                        </div>
                        <div
                            class="p-1.5 px-2 rounded-xl flex items-center justify-between gap-1 text-[11px]"
                            :class="kategoriFilter === 'Operasional'
                                ? 'bg-white/15 text-white'
                                : 'bg-rose-50 text-rose-800 border border-rose-100/80'"
                        >
                            <span class="inline-flex items-center gap-1 font-extrabold text-[10px]" :class="kategoriFilter === 'Operasional' ? 'text-rose-100' : 'text-rose-700'">
                                <ArrowUpRight class="w-3 h-3 text-rose-500" />
                                Keluar:
                            </span>
                            <span class="font-black truncate" :title="formatRupiah(totalOperasional.keluar)">
                                {{ formatRupiah(totalOperasional.keluar) }}
                            </span>
                        </div>
                    </div>

                    <p
                        class="text-[11px] mt-2 font-medium"
                        :class="kategoriFilter === 'Operasional' ? 'text-indigo-100' : 'text-slate-400'"
                    >
                        Biaya gas LPG, utilitas dapur, kebersihan & logistik
                    </p>
                </div>
            </div>

            <!-- Card 3: Insentif Fasilitas -->
            <div
                @click="toggleCategoryFilter('Insentif Fasilitas')"
                class="relative p-5 rounded-2xl border transition-all duration-200 cursor-pointer overflow-hidden group select-none shadow-xs"
                :class="[
                    kategoriFilter === 'Insentif Fasilitas'
                        ? 'bg-gradient-to-br from-blue-600 to-cyan-800 text-white border-blue-600 shadow-md ring-2 ring-blue-500/40'
                        : 'bg-white hover:bg-blue-50/40 border-slate-200 hover:border-blue-300 text-slate-800'
                ]"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[11px] font-extrabold uppercase tracking-wider"
                                :class="kategoriFilter === 'Insentif Fasilitas' ? 'text-blue-100' : 'text-blue-700'"
                            >
                                Kategori Transaksi
                            </span>
                            <span
                                v-if="kategoriFilter === 'Insentif Fasilitas'"
                                class="px-1.5 py-0.2 rounded text-[10px] font-black bg-white/20 text-white"
                            >
                                Filter Aktif
                            </span>
                        </div>
                        <h4
                            class="text-base sm:text-lg font-black mt-1"
                            :class="kategoriFilter === 'Insentif Fasilitas' ? 'text-white' : 'text-slate-900'"
                        >
                            Insentif Fasilitas
                        </h4>
                    </div>
                    <div
                        class="p-2.5 rounded-xl transition-colors shrink-0"
                        :class="[
                            kategoriFilter === 'Insentif Fasilitas'
                                ? 'bg-white/20 text-white'
                                : 'bg-blue-100/80 text-blue-700 group-hover:bg-blue-200/80'
                        ]"
                    >
                        <Building2 class="h-5 w-5" />
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t" :class="kategoriFilter === 'Insentif Fasilitas' ? 'border-white/20' : 'border-slate-100'">
                    <div class="flex items-baseline justify-between gap-2">
                        <span
                            class="text-xl sm:text-2xl font-black tracking-tight"
                            :class="kategoriFilter === 'Insentif Fasilitas' ? 'text-white' : 'text-blue-700'"
                        >
                            {{ formatRupiah(totalInsentifFasilitas.nominal) }}
                        </span>
                        <span
                            class="text-xs font-bold shrink-0"
                            :class="kategoriFilter === 'Insentif Fasilitas' ? 'text-blue-100' : 'text-slate-500'"
                        >
                            {{ totalInsentifFasilitas.count }} Transaksi
                        </span>
                    </div>

                    <!-- Rincian Arus Masuk & Keluar -->
                    <div class="grid grid-cols-2 gap-2 mt-2.5 pt-2 border-t" :class="kategoriFilter === 'Insentif Fasilitas' ? 'border-white/15' : 'border-slate-100'">
                        <div
                            class="p-1.5 px-2 rounded-xl flex items-center justify-between gap-1 text-[11px]"
                            :class="kategoriFilter === 'Insentif Fasilitas'
                                ? 'bg-white/15 text-white'
                                : 'bg-emerald-50 text-emerald-800 border border-emerald-100/80'"
                        >
                            <span class="inline-flex items-center gap-1 font-extrabold text-[10px]" :class="kategoriFilter === 'Insentif Fasilitas' ? 'text-emerald-100' : 'text-emerald-700'">
                                <ArrowDownLeft class="w-3 h-3 text-emerald-500" />
                                Masuk:
                            </span>
                            <span class="font-black truncate" :title="formatRupiah(totalInsentifFasilitas.masuk)">
                                {{ formatRupiah(totalInsentifFasilitas.masuk) }}
                            </span>
                        </div>
                        <div
                            class="p-1.5 px-2 rounded-xl flex items-center justify-between gap-1 text-[11px]"
                            :class="kategoriFilter === 'Insentif Fasilitas'
                                ? 'bg-white/15 text-white'
                                : 'bg-rose-50 text-rose-800 border border-rose-100/80'"
                        >
                            <span class="inline-flex items-center gap-1 font-extrabold text-[10px]" :class="kategoriFilter === 'Insentif Fasilitas' ? 'text-rose-100' : 'text-rose-700'">
                                <ArrowUpRight class="w-3 h-3 text-rose-500" />
                                Keluar:
                            </span>
                            <span class="font-black truncate" :title="formatRupiah(totalInsentifFasilitas.keluar)">
                                {{ formatRupiah(totalInsentifFasilitas.keluar) }}
                            </span>
                        </div>
                    </div>

                    <p
                        class="text-[11px] mt-2 font-medium"
                        :class="kategoriFilter === 'Insentif Fasilitas' ? 'text-blue-100' : 'text-slate-400'"
                    >
                        Sewa sarpras, fasilitas unit SPPG & pemeliharaan alat
                    </p>
                </div>
            </div>

            <!-- Card 4: Dana Bantuan Pemerintah -->
            <div
                @click="toggleCategoryFilter('Dana Bantuan Pemerintah')"
                class="relative p-5 rounded-2xl border transition-all duration-200 cursor-pointer overflow-hidden group select-none shadow-xs"
                :class="[
                    kategoriFilter === 'Dana Bantuan Pemerintah'
                        ? 'bg-gradient-to-br from-purple-600 to-indigo-900 text-white border-purple-600 shadow-md ring-2 ring-purple-500/40'
                        : 'bg-white hover:bg-purple-50/40 border-slate-200 hover:border-purple-300 text-slate-800'
                ]"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[11px] font-extrabold uppercase tracking-wider"
                                :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'text-purple-100' : 'text-purple-700'"
                            >
                                Kategori Transaksi
                            </span>
                            <span
                                v-if="kategoriFilter === 'Dana Bantuan Pemerintah'"
                                class="px-1.5 py-0.2 rounded text-[10px] font-black bg-white/20 text-white"
                            >
                                Filter Aktif
                            </span>
                        </div>
                        <h4
                            class="text-base sm:text-lg font-black mt-1"
                            :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'text-white' : 'text-slate-900'"
                        >
                            Dana Bantuan Pemerintah
                        </h4>
                    </div>
                    <div
                        class="p-2.5 rounded-xl transition-colors shrink-0"
                        :class="[
                            kategoriFilter === 'Dana Bantuan Pemerintah'
                                ? 'bg-white/20 text-white'
                                : 'bg-purple-100/80 text-purple-700 group-hover:bg-purple-200/80'
                        ]"
                    >
                        <Building2 class="h-5 w-5" />
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t" :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'border-white/20' : 'border-slate-100'">
                    <div class="flex items-baseline justify-between gap-2">
                        <span
                            class="text-xl sm:text-2xl font-black tracking-tight"
                            :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'text-white' : 'text-purple-700'"
                        >
                            {{ formatRupiah(totalDanaBantuanPemerintah.nominal) }}
                        </span>
                        <span
                            class="text-xs font-bold shrink-0"
                            :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'text-purple-100' : 'text-slate-500'"
                        >
                            {{ totalDanaBantuanPemerintah.count }} Transaksi
                        </span>
                    </div>

                    <!-- Rincian Arus Masuk & Keluar -->
                    <div class="grid grid-cols-2 gap-2 mt-2.5 pt-2 border-t" :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'border-white/15' : 'border-slate-100'">
                        <div
                            class="p-1.5 px-2 rounded-xl flex items-center justify-between gap-1 text-[11px]"
                            :class="kategoriFilter === 'Dana Bantuan Pemerintah'
                                ? 'bg-white/15 text-white'
                                : 'bg-emerald-50 text-emerald-800 border border-emerald-100/80'"
                        >
                            <span class="inline-flex items-center gap-1 font-extrabold text-[10px]" :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'text-emerald-100' : 'text-emerald-700'">
                                <ArrowDownLeft class="w-3 h-3 text-emerald-500" />
                                Masuk:
                            </span>
                            <span class="font-black truncate" :title="formatRupiah(totalDanaBantuanPemerintah.masuk)">
                                {{ formatRupiah(totalDanaBantuanPemerintah.masuk) }}
                            </span>
                        </div>
                        <div
                            class="p-1.5 px-2 rounded-xl flex items-center justify-between gap-1 text-[11px]"
                            :class="kategoriFilter === 'Dana Bantuan Pemerintah'
                                ? 'bg-white/15 text-white'
                                : 'bg-rose-50 text-rose-800 border border-rose-100/80'"
                        >
                            <span class="inline-flex items-center gap-1 font-extrabold text-[10px]" :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'text-rose-100' : 'text-rose-700'">
                                <ArrowUpRight class="w-3 h-3 text-rose-500" />
                                Keluar:
                            </span>
                            <span class="font-black truncate" :title="formatRupiah(totalDanaBantuanPemerintah.keluar)">
                                {{ formatRupiah(totalDanaBantuanPemerintah.keluar) }}
                            </span>
                        </div>
                    </div>

                    <p
                        class="text-[11px] mt-2 font-medium"
                        :class="kategoriFilter === 'Dana Bantuan Pemerintah' ? 'text-purple-100' : 'text-slate-400'"
                    >
                        Penerimaan / pembiayaan bantuan pemerintah unit SPPG
                    </p>
                </div>
            </div>
        </div>

        <!-- FILTER RENTANG WAKTU (DEFAULT HARI INI ATAU SESUAI URL) -->
        <PeriodDateFilterBar
            v-model:startDate="filterStartDate"
            v-model:endDate="filterEndDate"
            v-model:mode="filterDateMode"
            v-model:periodeId="filterPeriodeId"
            v-model:isAllTime="isFilterAllTime"
            :periodes="periodes"
            @change="handleDateFilterChange"
        />

        <!-- TOOLBAR: SEARCH, FILTER STATUS (TEKS SAJA TANPA ICON), FILTER KATEGORI (TEKS SAJA), TOMBOL TAMBAH TRANSAKSI -->
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <!-- Sisi Kiri: Filter Status & Kategori -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Filter Status Transaksi: Cukup teks saja tanpa icon (Tinggi diselaraskan h-10) -->
                <div class="inline-flex items-center h-10 rounded-xl bg-slate-100 p-1 border border-slate-200/80 shadow-2xs">
                    <button
                        type="button"
                        @click="statusFilter = 'semua'"
                        class="h-full px-3.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center select-none"
                        :class="[
                            statusFilter === 'semua'
                                ? 'bg-white text-slate-900 shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Semua Status
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'Disetujui'"
                        class="h-full px-3.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center select-none"
                        :class="[
                            statusFilter === 'Disetujui'
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Disetujui
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'Ditunda'"
                        class="h-full px-3.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center select-none"
                        :class="[
                            statusFilter === 'Ditunda'
                                ? 'bg-amber-500 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Ditunda
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'Ditolak'"
                        class="h-full px-3.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center select-none"
                        :class="[
                            statusFilter === 'Ditolak'
                                ? 'bg-rose-600 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Ditolak
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'Dibatalkan'"
                        class="h-full px-3.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center select-none"
                        :class="[
                            statusFilter === 'Dibatalkan'
                                ? 'bg-slate-700 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Dibatalkan
                    </button>
                </div>

                <!-- Dropdown Filter Kategori: Tinggi disamakan dengan pembungkus status (h-10), padding kanan diperluas agar panah tidak menutupi teks -->
                <div class="relative inline-block">
                    <select
                        v-model="kategoriFilter"
                        class="h-10 pl-3.5 pr-9 text-xs font-bold rounded-xl border border-slate-300 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer shadow-2xs appearance-none min-w-[190px]"
                    >
                        <option value="semua">Semua Kategori</option>
                        <option value="Bahan Baku">Bahan Baku</option>
                        <option value="Operasional">Operasional</option>
                        <option value="Insentif Fasilitas">Insentif Fasilitas</option>
                        <option value="Dana Bantuan Pemerintah">Dana Bantuan Pemerintah</option>
                    </select>
                    <ChevronDown class="h-3.5 w-3.5 text-slate-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                </div>

                <button
                    v-if="statusFilter !== 'semua' || kategoriFilter !== 'semua' || searchQuery"
                    type="button"
                    @click="resetFilters"
                    class="h-10 px-3 text-xs font-bold text-slate-500 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors flex items-center gap-1 cursor-pointer"
                    title="Reset Filter"
                >
                    <RotateCcw class="h-3.5 w-3.5" />
                    <span>Reset</span>
                </button>
            </div>

            <!-- Sisi Kanan: Search & Download & Tambah Transaksi (Tinggi disamakan h-10) -->
            <div class="flex items-center gap-2">
                <div class="relative w-full sm:w-64">
                    <Search class="h-3.5 w-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari transaksi, PO, supplier..."
                        class="h-10 w-full pl-8 pr-3 text-xs rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 shadow-2xs"
                    />
                </div>

                <!-- Tombol Dropdown Download (Nota PO & Kuitansi) -->
                <div class="relative inline-block text-left shrink-0">
                    <button
                        type="button"
                        @click="showDownloadDropdown = !showDownloadDropdown"
                        class="h-10 px-3.5 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs flex items-center gap-1.5 cursor-pointer transition-colors"
                        title="Download Format Nota PO (.xlsx) atau Kuitansi (.pdf)"
                    >
                        <Download class="h-4 w-4" />
                        <span>Download</span>
                        <ChevronDown class="h-3.5 w-3.5 opacity-80" />
                    </button>

                    <!-- Popover Dropdown Menu -->
                    <div
                        v-if="showDownloadDropdown"
                        class="absolute right-0 mt-1.5 w-56 rounded-2xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                        @mouseleave="showDownloadDropdown = false"
                    >
                        <div class="px-3 py-1.5 border-b border-slate-100 text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">
                            Pilihan Format Unduh
                        </div>
                        <button
                            type="button"
                            @click="handleDownloadNotaPoAll(); showDownloadDropdown = false;"
                            :disabled="isDownloadingNotaPo"
                            class="w-full text-left px-3.5 py-2.5 text-xs font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 flex items-center gap-2.5 transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-700">
                                <FileSpreadsheet class="h-4 w-4" />
                            </div>
                            <div>
                                <span class="block text-slate-900 font-extrabold">Download Nota PO</span>
                                <span class="block text-[10.5px] text-slate-500 font-normal">Format Excel (.xlsx) Resmi</span>
                            </div>
                        </button>
                        <button
                            type="button"
                            @click="openReceiptModal(null); showDownloadDropdown = false;"
                            class="w-full text-left px-3.5 py-2.5 text-xs font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 flex items-center gap-2.5 transition-colors cursor-pointer"
                        >
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-700">
                                <Receipt class="h-4 w-4" />
                            </div>
                            <div>
                                <span class="block text-slate-900 font-extrabold">Download Kuitansi</span>
                                <span class="block text-[10.5px] text-slate-500 font-normal">Format PDF Resmi Tanda Terima</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Tombol Dropdown Tambah Transaksi (Tunggal vs Multi) -->
                <div class="relative inline-block text-left shrink-0">
                    <button
                        type="button"
                        @click="showAddDropdown = !showAddDropdown"
                        class="h-10 px-4 text-xs font-bold rounded-xl bg-primary hover:bg-primary/90 text-white shadow-xs flex items-center gap-2 cursor-pointer shrink-0 transition-colors"
                        title="Pilih Metode Tambah Transaksi (Tunggal atau Multi)"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Transaksi</span>
                        <ChevronDown class="h-3.5 w-3.5 opacity-80" />
                    </button>

                    <!-- Backdrop Close on Click Outside -->
                    <div
                        v-if="showAddDropdown"
                        class="fixed inset-0 z-40 cursor-default"
                        @click="showAddDropdown = false"
                    ></div>

                    <!-- Menu Dropdown Tambah Transaksi -->
                    <div
                        v-if="showAddDropdown"
                        class="absolute right-0 mt-1.5 w-64 rounded-2xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                    >
                        <div class="px-3.5 py-1.5 border-b border-slate-100 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                            Pilihan Metode Input
                        </div>
                        
                        <!-- 1. Transaksi Tunggal -->
                        <button
                            type="button"
                            @click="openAddManualModal(); showAddDropdown = false;"
                            class="w-full text-left px-3.5 py-2.5 text-xs font-bold text-slate-700 hover:bg-blue-50 hover:text-blue-800 flex items-center gap-3 transition-colors cursor-pointer"
                        >
                            <div class="p-2 rounded-xl bg-blue-100 text-blue-700 shrink-0">
                                <FileText class="h-4 w-4" />
                            </div>
                            <div>
                                <span class="block text-slate-900 font-extrabold">Transaksi Tunggal</span>
                                <span class="block text-[10.5px] text-slate-500 font-normal">Input 1 transaksi lewat form rincian</span>
                            </div>
                        </button>

                        <!-- 2. Transaksi Multi (Tabel & Excel Paste) -->
                        <button
                            type="button"
                            @click="openAddMultiModal(); showAddDropdown = false;"
                            class="w-full text-left px-3.5 py-2.5 text-xs font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 flex items-center gap-3 transition-colors cursor-pointer border-t border-slate-100"
                        >
                            <div class="p-2 rounded-xl bg-emerald-100 text-emerald-700 shrink-0">
                                <Table class="h-4 w-4" />
                            </div>
                            <div>
                                <span class="block text-slate-900 font-extrabold">Transaksi Multi</span>
                                <span class="block text-[10.5px] text-slate-500 font-normal">Input banyak data via tabel / paste Excel</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL TRANSAKSI -->
        <Card className="bg-white border-slate-200/80 shadow-xs overflow-hidden">
            <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <CardTitle className="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <CreditCard class="h-5 w-5 text-primary" />
                        <span>Riwayat Transaksi Keuangan</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-bold">
                            {{ filteredTransactions.length }} Data
                        </span>
                    </CardTitle>
                    <CardDescription className="text-xs text-slate-500">
                        Menampilkan transaksi dari Rancang Menu Otomatis & transaksi pencatatan yang disetujui / ditolak.
                    </CardDescription>
                </div>
                <div class="text-right">
                    <span class="text-[11px] uppercase font-bold text-slate-400 block">Total Transaksi Sesuai Filter</span>
                    <span class="text-base sm:text-lg font-black text-slate-900">{{ formatRupiah(totalNominalTabel) }}</span>
                </div>
            </CardHeader>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse min-w-[950px]">
                    <thead class="bg-slate-50 text-slate-700 font-extrabold uppercase text-[10.5px] border-b border-slate-200">
                        <tr>
                            <th class="p-3.5 text-center w-12">No</th>
                            <th class="p-3.5 whitespace-nowrap">Tanggal</th>
                            <th class="p-3.5 whitespace-nowrap">No. Transaksi</th>
                            <th class="p-3.5">Nama Barang/Bahan</th>
                            <th class="p-3.5 text-center">Volume</th>
                            <th class="p-3.5 text-center">Kategori</th>
                            <th class="p-3.5 text-center">Arus</th>
                            <th class="p-3.5 text-center">PO</th>
                            <th class="p-3.5 text-right whitespace-nowrap">Nominal (Rp)</th>
                            <th class="p-3.5">Supplier</th>
                            <th class="p-3.5 text-center">Sumber</th>
                            <th class="p-3.5 text-center">Status</th>
                            <th class="p-3.5 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="(trx, idx) in filteredTransactions"
                            :key="trx.id || idx"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- 1. No -->
                            <td class="p-3.5 text-center font-bold text-slate-400">
                                {{ idx + 1 }}
                            </td>

                            <!-- 2. Tanggal (Isinya Dibagi 2: Tanggal Pesanan & Tanggal Pembayaran) -->
                            <td class="p-3.5 whitespace-nowrap text-xs">
                                <div class="space-y-1">
                                    <div>
                                        <span class="text-[9.5px] font-extrabold text-slate-400 uppercase tracking-wider block leading-tight">Tanggal Pesanan</span>
                                        <span class="font-semibold text-slate-700 text-xs">
                                            {{ trx.tanggal_pesanan ? formatTanggalIndo(trx.tanggal_pesanan) : (trx.tanggal ? formatTanggalIndo(trx.tanggal) : '-') }}
                                        </span>
                                    </div>
                                    <div class="pt-0.5 border-t border-slate-100">
                                        <span class="text-[9.5px] font-extrabold text-emerald-600 uppercase tracking-wider block leading-tight">Tanggal Pembayaran</span>
                                        <span class="font-bold text-slate-900 text-xs">
                                            {{ trx.tanggal ? formatTanggalIndo(trx.tanggal) : '-' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 3. No Transaksi / Ref PO -->
                            <td class="p-3.5 font-mono font-bold whitespace-nowrap">
                                <span class="text-primary hover:underline cursor-pointer" @click="openDetail(trx)">
                                    {{ trx.nomor_transaksi || '-' }}
                                </span>
                            </td>

                            <!-- 4. Nama Barang/Bahan -->
                            <td class="p-3.5 font-semibold text-slate-900 max-w-xs">
                                <div>{{ trx.nama_transaksi }}</div>
                                <div v-if="trx.work_order" class="inline-flex items-center gap-1 mt-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-1.5 py-0.5 rounded-md">
                                    <Package class="w-3 h-3 text-indigo-500" />
                                    <span>{{ trx.work_order.nomor_wo }}</span>
                                </div>
                                <div v-if="trx.keterangan && trx.keterangan !== '-'" class="text-[10.5px] text-slate-400 truncate max-w-xs mt-0.5">
                                    {{ trx.keterangan }}
                                </div>
                            </td>

                            <!-- 4b. Volume & Satuan -->
                            <td class="p-3.5 text-center whitespace-nowrap font-bold text-slate-700">
                                <span v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(trx.kategori) && (trx.volume || trx.qty)">
                                    {{ trx.volume || trx.qty }} {{ trx.satuan && trx.satuan !== '-' ? trx.satuan : 'Kg' }}
                                </span>
                                <span v-else class="text-slate-300">-</span>
                            </td>

                            <!-- 5. Kategori -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2.5 py-0.5 text-[10.5px] font-bold rounded-full border inline-block',
                                        trx.kategori === 'Bahan Baku'
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                            : trx.kategori === 'Operasional'
                                            ? 'bg-indigo-50 text-indigo-800 border-indigo-200'
                                            : trx.kategori === 'Dana Bantuan Pemerintah'
                                            ? 'bg-purple-50 text-purple-800 border-purple-200'
                                            : 'bg-blue-50 text-blue-800 border-blue-200'
                                    ]"
                                >
                                    {{ trx.kategori }}
                                </span>
                            </td>

                            <!-- 5b. Arus Transaksi (Masuk / Keluar) -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span
                                    v-if="trx.arus === 'Masuk'"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                >
                                    <ArrowDownLeft class="h-3 w-3 text-emerald-600" />
                                    Masuk
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-rose-50 text-rose-800 border border-rose-200"
                                >
                                    <ArrowUpRight class="h-3 w-3 text-rose-600" />
                                    Keluar
                                </span>
                            </td>

                            <!-- 6. PO (Nota PO) -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-0.5 text-[10px] font-bold rounded-full border inline-block',
                                        trx.perlu_npo
                                            ? 'bg-teal-50 text-teal-800 border-teal-200'
                                            : 'bg-slate-100 text-slate-500 border-slate-200'
                                    ]"
                                >
                                    {{ trx.perlu_npo ? 'Ya' : 'Tidak' }}
                                </span>
                            </td>

                            <!-- 7. Nominal -->
                            <td class="p-3.5 text-right font-black text-slate-900 whitespace-nowrap">
                                {{ formatRupiah(trx.nominal) }}
                            </td>

                            <!-- 8. Supplier -->
                            <td class="p-3.5 text-slate-600 max-w-[140px] truncate" :title="trx.vendor">
                                <span v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(trx.kategori)">
                                    {{ trx.vendor || '-' }}
                                </span>
                                <span v-else class="text-slate-300">-</span>
                            </td>

                            <!-- 9. Sumber (PO Otomatis vs Manual) -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-0.5 text-[10px] font-bold rounded-md border',
                                        trx.source_type === 'manual'
                                            ? 'bg-amber-50 text-amber-800 border-amber-200'
                                            : 'bg-cyan-50 text-cyan-800 border-cyan-200'
                                    ]"
                                >
                                    {{ trx.source_type === 'manual' ? 'Manual' : 'PO Menu' }}
                                </span>
                            </td>

                            <!-- 10. Status: Disetujui, Ditunda, Ditolak, Dibatalkan -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2.5 py-0.5 text-[10.5px] font-bold rounded-full border inline-block',
                                        trx.status === 'Disetujui'
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                            : (trx.status === 'Ditunda'
                                                ? 'bg-amber-50 text-amber-800 border-amber-200'
                                                : (trx.status === 'Ditolak'
                                                    ? 'bg-rose-50 text-rose-800 border-rose-200'
                                                    : 'bg-slate-100 text-slate-700 border-slate-300'))
                                    ]"
                                >
                                    {{ trx.status }}
                                </span>
                            </td>

                            <!-- 11. Aksi -->
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- 0. Quick Approve jika Status Ditunda -->
                                    <button
                                        v-if="trx.status === 'Ditunda'"
                                        type="button"
                                        @click="quickApproveTrx(trx)"
                                        :disabled="isUpdatingStatus"
                                        class="p-1.5 rounded-lg text-emerald-700 bg-emerald-50/80 hover:bg-emerald-100 hover:text-emerald-800 border border-emerald-200/80 transition-all cursor-pointer shadow-2xs"
                                        title="Setujui Transaksi (Ditunda -> Disetujui)"
                                    >
                                        <Check class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- 1. Lihat Detail -->
                                    <button
                                        type="button"
                                        @click="openDetail(trx)"
                                        class="p-1.5 rounded-lg text-blue-600 bg-blue-50/80 hover:bg-blue-100 hover:text-blue-700 border border-blue-200/80 transition-all cursor-pointer shadow-2xs"
                                        title="Lihat Detail Transaksi"
                                    >
                                        <Eye class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- 2. Dropdown Download (Kuitansi PDF & Nota PO Excel) -->
                                    <div class="relative inline-block text-left">
                                        <!-- Backdrop Click Outside -->
                                        <div
                                            v-if="activeRowDropdownId === trx.id"
                                            class="fixed inset-0 z-40 cursor-default"
                                            @click.stop="activeRowDropdownId = null"
                                        ></div>

                                        <button
                                            type="button"
                                            @click.stop="toggleRowDropdown(trx.id)"
                                            class="p-1.5 rounded-lg text-emerald-700 bg-emerald-50/80 hover:bg-emerald-100 border border-emerald-200/80 transition-all cursor-pointer shadow-2xs flex items-center gap-0.5"
                                            :class="{ 'bg-emerald-100 ring-2 ring-emerald-400/30': activeRowDropdownId === trx.id }"
                                            title="Download Dokumen Transaksi (Kuitansi / Nota PO)"
                                        >
                                            <Download class="h-3.5 w-3.5 text-emerald-700" />
                                            <ChevronDown class="h-2.5 w-2.5 text-emerald-600" />
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div
                                            v-if="activeRowDropdownId === trx.id"
                                            :class="[
                                                'absolute right-0 w-48 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-left',
                                                idx >= filteredTransactions.length - 2 && filteredTransactions.length > 2
                                                    ? 'bottom-full mb-1.5'
                                                    : 'top-full mt-1.5'
                                            ]"
                                        >
                                            <div class="px-3 py-1 border-b border-slate-100 text-[10px] font-extrabold uppercase text-slate-400 tracking-wider">
                                                Download Dokumen
                                            </div>

                                            <!-- Download Kuitansi (PDF) -->
                                            <button
                                                type="button"
                                                @click="handleDownloadKuitansiSingleDirect(trx); activeRowDropdownId = null"
                                                class="w-full px-3 py-2 text-left text-xs font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 flex items-center gap-2 cursor-pointer transition-colors"
                                            >
                                                <div class="h-6 w-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                                    <Receipt class="h-3.5 w-3.5" />
                                                </div>
                                                <div>
                                                    <div class="leading-tight font-bold">Kuitansi (PDF)</div>
                                                    <div class="text-[10px] font-normal text-slate-400">Tanda terima resmi SPPG</div>
                                                </div>
                                            </button>

                                            <!-- Download Nota PO (Excel) -->
                                            <button
                                                v-if="trx.perlu_npo"
                                                type="button"
                                                @click="handleDownloadNotaPoSingle(trx); activeRowDropdownId = null"
                                                class="w-full px-3 py-2 text-left text-xs font-bold text-slate-700 hover:bg-blue-50 hover:text-blue-800 flex items-center gap-2 cursor-pointer transition-colors border-t border-slate-100"
                                            >
                                                <div class="h-6 w-6 rounded-md bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                                    <FileSpreadsheet class="h-3.5 w-3.5" />
                                                </div>
                                                <div>
                                                    <div class="leading-tight font-bold">Nota PO (Excel)</div>
                                                    <div class="text-[10px] font-normal text-slate-400">Format pesanan supplier</div>
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 3. Edit Transaksi (Hanya Transaksi Manual) -->
                                    <button
                                        v-if="trx.source_type === 'manual'"
                                        type="button"
                                        @click="openEditManualModal(trx)"
                                        class="p-1.5 rounded-lg text-amber-700 bg-amber-50/80 hover:bg-amber-100 hover:text-amber-800 border border-amber-200/80 transition-all cursor-pointer shadow-2xs"
                                        title="Edit Transaksi"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- 4. Hapus Transaksi (Hanya Transaksi Manual) -->
                                    <button
                                        v-if="trx.source_type === 'manual'"
                                        type="button"
                                        @click="confirmDelete(trx)"
                                        class="p-1.5 rounded-lg text-rose-700 bg-rose-50/80 hover:bg-rose-100 hover:text-rose-800 border border-rose-200/80 transition-all cursor-pointer shadow-2xs"
                                        title="Hapus Transaksi"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="filteredTransactions.length === 0">
                            <td colspan="12" class="py-12 text-center text-slate-400">
                                <Receipt class="h-10 w-10 mx-auto text-slate-300 mb-2" />
                                <p class="text-sm font-bold text-slate-600">Tidak ada data transaksi</p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Belum ada transaksi pada rentang waktu atau kriteria filter yang dipilih.
                                </p>
                                <div class="mt-4 flex items-center justify-center gap-2">
                                    <button
                                        type="button"
                                        @click="resetFilters"
                                        class="h-10 px-4 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-colors cursor-pointer flex items-center justify-center"
                                    >
                                        Reset Filter
                                    </button>
                                    <button
                                        type="button"
                                        @click="openAddManualModal"
                                        class="h-10 px-3.5 text-xs font-bold bg-primary hover:bg-primary/90 text-white rounded-xl transition-colors cursor-pointer shadow-xs flex items-center justify-center gap-1.5"
                                    >
                                        <FileText class="h-4 w-4" />
                                        <span>Transaksi Tunggal</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="openAddMultiModal"
                                        class="h-10 px-3.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors cursor-pointer shadow-xs flex items-center justify-center gap-1.5"
                                    >
                                        <Table class="h-4 w-4" />
                                        <span>Transaksi Multi</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- MODAL TAMBAH TRANSAKSI MULTI (INPUT TABEL & COPY-PASTE EXCEL) -->
        <Modal :show="showMultiModal" @close="showMultiModal = false" max-width="full">
            <div class="p-4 sm:p-6 space-y-4 max-h-[90vh] flex flex-col">
                <!-- Header Modal Multi -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-2xl bg-emerald-100 text-emerald-700">
                            <Table class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-extrabold text-slate-900">
                                    Tambah Transaksi Multi (Banyak Data)
                                </h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Masukkan beberapa transaksi sekaligus secara langsung pada baris tabel atau tempel (copy-paste) dari spreadsheet.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showMultiModal = false"
                        class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Bar Pengaturan Cepat / Batch Defaults -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/90 text-xs shrink-0">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="text-[11px] font-black uppercase text-slate-500 tracking-wider">
                                Nilai Bawaan:
                            </span>

                            <!-- Default Tanggal Pembayaran -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-600 font-bold">Tgl:</span>
                                <input
                                    v-model="multiBatchDefaults.tanggal"
                                    type="date"
                                    class="h-8 px-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white"
                                />
                            </div>

                            <!-- Default Kategori -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-600 font-bold">Kategori:</span>
                                <select
                                    v-model="multiBatchDefaults.kategori"
                                    class="h-8 px-2 text-xs font-bold rounded-lg border border-slate-300 bg-white"
                                >
                                    <option value="Bahan Baku">Bahan Baku</option>
                                    <option value="Operasional">Operasional</option>
                                    <option value="Insentif Fasilitas">Insentif Fasilitas</option>
                                    <option value="Dana Bantuan Pemerintah">Dana Bantuan Pemerintah</option>
                                </select>
                            </div>

                            <!-- Default Jenis Transaksi (Khusus Operasional) -->
                            <div v-if="multiBatchDefaults.kategori === 'Operasional'" class="flex items-center gap-1.5">
                                <span class="text-amber-800 font-bold">Jenis:</span>
                                <select
                                    v-model="multiBatchDefaults.jenis_transaksi"
                                    class="h-8 px-2 text-xs font-semibold rounded-lg border border-amber-300 bg-amber-50/50 max-w-[150px] truncate"
                                >
                                    <option value="">-- Tanpa Default --</option>
                                    <option v-for="item in JENIS_TRANSAKSI_OPERASIONAL" :key="item" :value="item">
                                        {{ item }}
                                    </option>
                                </select>
                            </div>

                            <!-- Default Arus Transaksi -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-600 font-bold">Arus:</span>
                                <select
                                    v-model="multiBatchDefaults.arus"
                                    class="h-8 px-2 text-xs font-extrabold rounded-lg border border-slate-300 bg-white"
                                    :class="multiBatchDefaults.arus === 'Masuk' ? 'text-emerald-700' : 'text-rose-700'"
                                >
                                    <option value="Keluar">Keluar</option>
                                    <option value="Masuk">Masuk</option>
                                </select>
                            </div>

                            <!-- Default Status -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-600 font-bold">Status:</span>
                                <select
                                    v-model="multiBatchDefaults.status"
                                    class="h-8 px-2 text-xs font-bold rounded-lg border border-slate-300 bg-white"
                                >
                                    <option value="Disetujui">Disetujui</option>
                                    <option value="Ditunda">Ditunda</option>
                                    <option value="Ditolak">Ditolak</option>
                                    <option value="Dibatalkan">Dibatalkan</option>
                                </select>
                            </div>

                            <!-- Default Satuan -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-600 font-bold">Satuan:</span>
                                <select
                                    v-model="multiBatchDefaults.satuan"
                                    class="h-8 px-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white"
                                >
                                    <option v-for="s in SATUAN_LIST" :key="s.value" :value="s.value">
                                        {{ s.short || s.value }}
                                    </option>
                                </select>
                            </div>

                            <!-- Default Supplier -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-600 font-bold">Supplier:</span>
                                <select
                                    v-model="multiBatchDefaults.supplier_id"
                                    class="h-8 px-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white max-w-[140px] truncate"
                                >
                                    <option value="">-- Tanpa Default --</option>
                                    <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
                                        {{ sup.nama_usaha }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Tombol Terapkan ke Semua Baris -->
                        <button
                            type="button"
                            @click="applyDefaultsToAllRows"
                            class="h-8 px-3 text-xs font-bold rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 transition-colors flex items-center justify-center gap-1.5 shrink-0 shadow-2xs cursor-pointer"
                            title="Terapkan tanggal, kategori, dan status default ke semua baris di bawah"
                        >
                            <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                            <span>Terapkan ke Semua Baris</span>
                        </button>
                    </div>
                </div>

                <!-- Toolbar Tabel & Kotak Paste Box -->
                <div class="space-y-3 shrink-0">
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2">
                            <!-- Tombol Tambah Baris -->
                            <button
                                type="button"
                                @click="addMultiRow"
                                class="h-8 px-3 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                <span>Tambah Baris</span>
                            </button>

                            <!-- Tombol Toggle Paste Box -->
                            <button
                                type="button"
                                @click="showPasteBox = !showPasteBox"
                                class="h-8 px-3 text-xs font-bold rounded-lg border border-emerald-300 bg-emerald-50/80 hover:bg-emerald-100 text-emerald-800 shadow-2xs flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <ClipboardPaste class="h-3.5 w-3.5" />
                                <span>{{ showPasteBox ? 'Tutup Kotak Paste' : 'Tempel/Paste' }}</span>
                            </button>

                            <!-- Tombol Reset / Bersihkan -->
                            <button
                                type="button"
                                @click="resetMultiRows"
                                class="h-8 px-2.5 text-xs font-bold rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-600 transition-colors cursor-pointer"
                                title="Kosongkan Semua Baris"
                            >
                                <RotateCcw class="h-3 w-3" />
                            </button>
                        </div>

                        <!-- Ringkasan Baris & Total Nominal -->
                        <div class="flex items-center gap-3 bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-xs shadow-2xs">
                            <span class="text-slate-500 font-medium">
                                Terisi: <strong class="text-slate-900">{{ filledMultiRowsCount }}</strong> dari {{ multiRows.length }} Baris
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-500 font-medium">
                                Total Nominal: <strong class="text-emerald-700 font-black text-sm">{{ formatRupiah(totalNominalMulti) }}</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Kotak Tempel Cepat dari Excel (Collapsible) -->
                    <div
                        v-if="showPasteBox"
                        class="p-4 rounded-2xl bg-emerald-50/90 border border-emerald-200 text-xs space-y-2.5 animate-in fade-in slide-in-from-top-1 duration-150"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-bold text-emerald-900 flex items-center gap-1.5">
                                    <ClipboardPaste class="h-4 w-4 text-emerald-700" />
                                    <span>Tempel Data Langsung dari Excel / Google Sheets</span>
                                </h4>
                                <p class="text-[11px] text-emerald-700 mt-0.5">
                                    Salin baris tabel dari spreadsheet Anda, lalu tempel (Ctrl+V) pada kotak di bawah. Kolom yang didukung otomatis:
                                    <span class="font-semibold underline">Nama Barang | Volume | Satuan | Harga/Nominal | Supplier</span> (atau cukup <span class="font-semibold underline">Nama Barang | Nominal</span>).
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="showPasteBox = false"
                                class="p-1 text-emerald-600 hover:text-emerald-800 rounded-md hover:bg-emerald-100 cursor-pointer"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <textarea
                            v-model="pasteTextRaw"
                            rows="4"
                            placeholder="Tempel (Ctrl+V) baris teks atau tabel Excel di sini..."
                            class="w-full p-2.5 text-xs font-mono rounded-xl border border-emerald-300 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 text-slate-800"
                        ></textarea>

                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-slate-500 italic">
                                * Tips: Anda juga bisa langsung menekan Ctrl+V di baris tabel mana saja di bawah.
                            </span>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="showPasteBox = false"
                                    class="h-8 px-3 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-bold transition-colors cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="handleApplyPasteBox"
                                    class="h-8 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition-colors cursor-pointer shadow-xs flex items-center gap-1.5"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    <span>Terapkan ke Tabel</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABEL INPUT MULTI TRANSAKSI -->
                <div class="overflow-x-auto border border-slate-200/90 rounded-2xl bg-white flex-1 min-h-[300px] max-h-[55vh] shadow-inner overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse min-w-[1400px]">
                        <thead class="bg-slate-100 text-slate-700 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200 sticky top-0 z-10 shadow-2xs">
                            <tr>
                                <th class="p-2.5 text-center w-12 bg-slate-100">#</th>
                                <th class="p-2.5 w-32 bg-slate-100">Tgl Pembayaran *</th>
                                <th class="p-2.5 w-40 bg-slate-100">Kategori *</th>
                                <th class="p-2.5 w-48 bg-slate-100">Jenis Transaksi</th>
                                <th class="p-2.5 w-28 bg-slate-100">Arus *</th>
                                <th class="p-2.5 w-32 bg-slate-100">Status *</th>
                                <th class="p-2.5 text-center w-24 bg-slate-100">PO</th>
                                <th class="p-2.5 w-32 bg-slate-100">Tgl Pesanan</th>
                                <th class="p-2.5 w-44 bg-slate-100">Sandingkan WO</th>
                                <th class="p-2.5 min-w-[200px] bg-slate-100">Nama Barang / Uraian *</th>
                                <th class="p-2.5 w-24 bg-slate-100">Volume</th>
                                <th class="p-2.5 w-24 bg-slate-100">Satuan</th>
                                <th class="p-2.5 w-44 bg-slate-100">Supplier *</th>
                                <th class="p-2.5 w-36 bg-slate-100">Nominal (Rp) *</th>
                                <th class="p-2.5 w-36 bg-slate-100">Keterangan</th>
                                <th class="p-2.5 text-center w-12 bg-slate-100">Hapus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <tr
                                v-for="(row, idx) in multiRows"
                                :key="row.id || idx"
                                class="hover:bg-slate-50/80 transition-colors group"
                                @paste="handleTablePasteEvent($event, idx)"
                            >
                                <!-- 1. Nomor Baris -->
                                <td class="p-2 text-center font-bold text-slate-400 bg-slate-50/40">
                                    {{ idx + 1 }}
                                </td>

                                <!-- 2. Tanggal Pembayaran -->
                                <td class="p-2">
                                    <input
                                        v-model="row.tanggal"
                                        type="date"
                                        required
                                        @change="onMultiRowDateChange(row)"
                                        class="h-8 w-full px-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    />
                                </td>

                                <!-- 3. Kategori Transaksi -->
                                <td class="p-2">
                                    <select
                                        v-model="row.kategori"
                                        required
                                        @change="onMultiRowCategoryChange(row)"
                                        class="h-8 w-full px-2 text-xs font-bold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option value="Bahan Baku">Bahan Baku</option>
                                        <option value="Operasional">Operasional</option>
                                        <option value="Insentif Fasilitas">Insentif Fasilitas</option>
                                        <option value="Dana Bantuan Pemerintah">Dana Bantuan Pemerintah</option>
                                    </select>
                                </td>

                                <!-- 3a. Jenis Transaksi (Khusus Operasional) -->
                                <td class="p-2">
                                    <select
                                        v-if="row.kategori === 'Operasional'"
                                        v-model="row.jenis_transaksi"
                                        @change="onMultiRowJenisOperasionalChange(row)"
                                        required
                                        class="h-8 w-full px-2 text-xs font-semibold rounded-lg border border-amber-300 bg-amber-50/50 focus:outline-none focus:ring-1 focus:ring-amber-500 text-slate-800"
                                    >
                                        <option value="" disabled>-- Pilih Jenis --</option>
                                        <option v-for="item in JENIS_TRANSAKSI_OPERASIONAL" :key="item" :value="item">
                                            {{ item }}
                                        </option>
                                    </select>
                                    <span v-else class="text-xs text-slate-400 font-bold block text-center">
                                        -
                                    </span>
                                </td>

                                <!-- 3b. Arus Transaksi (Masuk / Keluar) -->
                                <td class="p-2">
                                    <select
                                        v-model="row.arus"
                                        required
                                        class="h-8 w-full px-2 text-xs font-black rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                        :class="row.arus === 'Masuk' ? 'text-emerald-700 bg-emerald-50/50 border-emerald-300' : 'text-rose-700 bg-rose-50/50 border-rose-300'"
                                    >
                                        <option value="Keluar">Keluar</option>
                                        <option value="Masuk">Masuk</option>
                                    </select>
                                </td>

                                <!-- 3c. Status Transaksi -->
                                <td class="p-2">
                                    <select
                                        v-model="row.status"
                                        required
                                        class="h-8 w-full px-2 text-xs font-bold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                        :class="row.status === 'Disetujui' ? 'text-emerald-700' : (row.status === 'Ditunda' ? 'text-amber-700' : (row.status === 'Ditolak' ? 'text-rose-700' : 'text-slate-700'))"
                                    >
                                        <option value="Disetujui">Disetujui</option>
                                        <option value="Ditunda">Ditunda</option>
                                        <option value="Ditolak">Ditolak</option>
                                        <option value="Dibatalkan">Dibatalkan</option>
                                    </select>
                                </td>

                                <!-- 4. PO (Perlu Nota PO)? -->
                                <td class="p-2 text-center">
                                    <div v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(row.kategori)" class="inline-flex rounded-lg bg-slate-200/80 p-0.5 border border-slate-300">
                                        <button
                                            type="button"
                                            @click="row.perlu_npo = true"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded cursor-pointer transition-all"
                                            :class="row.perlu_npo ? 'bg-white text-emerald-800 shadow-2xs' : 'text-slate-600'"
                                        >
                                            Ya
                                        </button>
                                        <button
                                            type="button"
                                            @click="row.perlu_npo = false; row.tanggal_pesanan = row.tanggal;"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded cursor-pointer transition-all"
                                            :class="!row.perlu_npo ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-600'"
                                        >
                                            Tidak
                                        </button>
                                    </div>
                                    <span v-else class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-400 border border-slate-200">
                                        Tidak
                                    </span>
                                </td>

                                <!-- 5. Tanggal Pesanan -->
                                <td class="p-2">
                                    <input
                                        v-if="row.perlu_npo && !['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(row.kategori)"
                                        v-model="row.tanggal_pesanan"
                                        type="date"
                                        class="h-8 w-full px-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    />
                                    <span v-else class="text-xs text-slate-400 font-medium px-2 italic">
                                        (Sesuai tgl bayar)
                                    </span>
                                </td>

                                <!-- 6. Sandingkan Work Order -->
                                <td class="p-2">
                                    <select
                                        v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(row.kategori)"
                                        v-model="row.work_order_id"
                                        class="h-8 w-full px-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option value="manual">Manual</option>
                                        <option
                                            v-for="wo in (workOrdersList || [])"
                                            :key="wo.id"
                                            :value="wo.id"
                                        >
                                            {{ wo.nomor_wo ? wo.nomor_wo + ' - ' : '' }}{{ wo.nama_menu }}
                                        </option>
                                    </select>
                                    <span v-else class="text-xs text-slate-400 font-medium px-2 italic">
                                        Manual
                                    </span>
                                </td>

                                <!-- 7. Nama Barang / Uraian Transaksi -->
                                <td class="p-2">
                                    <input
                                        v-model="row.nama_transaksi"
                                        type="text"
                                        required
                                        :placeholder="['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(row.kategori) ? 'Uraian transaksi...' : (row.kategori === 'Operasional' ? 'Nama barang / bahan / uraian...' : 'Nama barang / bahan...')"
                                        class="h-8 w-full px-2.5 text-xs font-semibold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 placeholder:text-slate-300"
                                    />
                                </td>

                                <!-- 8. Volume (Mendukung Desimal seperti 0,1) -->
                                <td class="p-2">
                                    <input
                                        v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(row.kategori)"
                                        v-model.number="row.volume"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        placeholder="0.1"
                                        class="h-8 w-full px-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 text-center"
                                    />
                                    <span v-else class="text-xs text-slate-400 font-bold block text-center">
                                        1
                                    </span>
                                </td>

                                <!-- 9. Satuan (Hanya Singkatan Saja Tanpa Kurung) -->
                                <td class="p-2">
                                    <select
                                        v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(row.kategori)"
                                        v-model="row.satuan"
                                        class="h-8 w-full px-1.5 text-xs font-semibold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option v-for="s in SATUAN_LIST" :key="s.value" :value="s.value">
                                            {{ s.short || s.value }}
                                        </option>
                                    </select>
                                    <span v-else class="text-xs text-slate-400 font-bold block text-center">
                                        -
                                    </span>
                                </td>

                                <!-- 10. Supplier -->
                                <td class="p-2">
                                    <select
                                        v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(row.kategori)"
                                        v-model="row.supplier_id"
                                        required
                                        class="h-8 w-full px-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option value="" disabled>-- Pilih Supplier --</option>
                                        <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
                                            {{ sup.nama_usaha }}
                                        </option>
                                    </select>
                                    <span v-else class="text-xs text-slate-400 font-bold block text-center">
                                        -
                                    </span>
                                </td>

                                <!-- 11. Nominal / Harga Langsung Berisi Rp dan Titik Ribuan -->
                                <td class="p-2">
                                    <input
                                        :value="formatNominalRupiah(row.nominal)"
                                        @input="onMultiRowNominalInput(row, $event)"
                                        type="text"
                                        required
                                        placeholder="Rp 0"
                                        class="h-8 w-full px-2 text-xs font-bold text-slate-900 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 text-right font-mono"
                                    />
                                </td>

                                <!-- 12. Keterangan Menggunakan Textarea -->
                                <td class="p-2">
                                    <textarea
                                        v-model="row.keterangan"
                                        rows="1"
                                        placeholder="Keterangan..."
                                        class="h-8 py-1.5 px-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 placeholder:text-slate-300 resize-y min-h-[32px] w-full"
                                    ></textarea>
                                </td>

                                <!-- 13. Tombol Hapus Baris -->
                                <td class="p-2 text-center">
                                    <button
                                        type="button"
                                        @click="removeMultiRow(idx)"
                                        class="p-1 rounded-lg text-rose-500 hover:bg-rose-100 hover:text-rose-700 transition-colors cursor-pointer"
                                        title="Hapus Baris Ini"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Modal Multi -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100 shrink-0">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="addMultiRow"
                            class="h-9 px-3 text-xs font-bold rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer flex items-center gap-1.5 shadow-2xs"
                        >
                            <Plus class="h-4 w-4 text-emerald-600" />
                            <span>Tambah 1 Baris Lagi</span>
                        </button>
                        <span class="text-xs text-slate-400">
                            Baris kosong tanpa Nama Barang/Uraian akan otomatis dilewati saat disimpan.
                        </span>
                    </div>

                    <div class="flex items-center gap-2.5 justify-end">
                        <button
                            type="button"
                            @click="showMultiModal = false"
                            class="h-9 px-4 text-xs font-bold rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>

                        <button
                            type="button"
                            @click="submitMultiTransactions"
                            :disabled="isSubmittingMulti || filledMultiRowsCount === 0"
                            class="h-9 px-5 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors cursor-pointer flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isSubmittingMulti">Menyimpan...</span>
                            <span v-else>Simpan {{ filledMultiRowsCount }} Transaksi ({{ formatRupiah(totalNominalMulti) }})</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- MODAL TAMBAH / EDIT TRANSAKSI -->
        <Modal :show="showManualModal" @close="showManualModal = false" max-width="lg">
            <div class="p-5 sm:p-6 space-y-4">
                <!-- Header Modal -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-primary/10 text-primary">
                            <Plus v-if="!isEditing" class="h-5 w-5" />
                            <Edit3 v-else class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                {{ isEditing ? 'Edit Transaksi' : 'Tambah Transaksi' }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                Masukkan rincian transaksi pengeluaran atau pembiayaan unit SPPG.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showManualModal = false"
                        class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitManualTransaction" class="space-y-4 text-xs">
                    <!-- 1. Kategori, Arus Transaksi & Status Transaksi -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <!-- Kategori Transaksi -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Kategori Transaksi <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="manualForm.kategori"
                                required
                                class="w-full h-[34px] px-3 py-1.5 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800"
                            >
                                <option value="" disabled>-- Pilih Kategori --</option>
                                <option value="Bahan Baku">Bahan Baku</option>
                                <option value="Operasional">Operasional</option>
                                <option value="Insentif Fasilitas">Insentif Fasilitas</option>
                                <option value="Dana Bantuan Pemerintah">Dana Bantuan Pemerintah</option>
                            </select>
                        </div>

                        <!-- Arus Transaksi (Wajib di semua kategori) -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Arus Transaksi <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-1 p-0.5 bg-slate-100 rounded-xl border border-slate-300 h-[34px]">
                                <button
                                    type="button"
                                    @click="manualForm.arus = 'Masuk'"
                                    class="h-full flex items-center justify-center gap-1 rounded-lg text-xs font-black transition-all cursor-pointer"
                                    :class="manualForm.arus === 'Masuk'
                                        ? 'bg-emerald-600 text-white shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'"
                                >
                                    <ArrowDownLeft class="h-3.5 w-3.5" />
                                    <span>Masuk</span>
                                </button>
                                <button
                                    type="button"
                                    @click="manualForm.arus = 'Keluar'"
                                    class="h-full flex items-center justify-center gap-1 rounded-lg text-xs font-black transition-all cursor-pointer"
                                    :class="manualForm.arus === 'Keluar'
                                        ? 'bg-rose-600 text-white shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'"
                                >
                                    <ArrowUpRight class="h-3.5 w-3.5" />
                                    <span>Keluar</span>
                                </button>
                            </div>
                        </div>

                        <!-- Status Transaksi -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Status Transaksi <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="manualForm.status"
                                required
                                class="w-full h-[34px] px-3 py-1.5 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800"
                                :class="manualForm.status === 'Disetujui' ? 'text-emerald-700' : (manualForm.status === 'Ditunda' ? 'text-amber-700' : (manualForm.status === 'Ditolak' ? 'text-rose-700' : (manualForm.status === 'Dibatalkan' ? 'text-slate-700' : '')))"
                            >
                                <option value="" disabled>-- Pilih Status --</option>
                                <option value="Disetujui">Disetujui</option>
                                <option value="Ditunda">Ditunda</option>
                                <option value="Ditolak">Ditolak</option>
                                <option value="Dibatalkan">Dibatalkan</option>
                            </select>
                        </div>
                    </div>

                    <!-- 1b. Jenis Transaksi Khusus Operasional -->
                    <div v-if="manualForm.kategori === 'Operasional'" class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200">
                        <label class="block font-bold text-amber-950 mb-1 text-xs">
                            Jenis Transaksi <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="manualForm.jenis_transaksi"
                            @change="onJenisOperasionalChange"
                            required
                            class="w-full h-[36px] px-3 py-1.5 border border-amber-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 text-xs font-bold text-slate-800"
                        >
                            <option value="" disabled>-- Pilih Jenis Transaksi Operasional --</option>
                            <option v-for="item in JENIS_TRANSAKSI_OPERASIONAL" :key="item" :value="item">
                                {{ item }}
                            </option>
                        </select>
                        <p class="text-[11px] text-amber-800/80 mt-1">
                            Pilih jenis operasional standar. Nama barang/bahan/rincian diisi secara manual di bawah.
                        </p>
                    </div>

                    <!-- 2. Pertanyaan Perlu NPO (Nota PO)? (Hanya Untuk Bahan Baku & Operasional, Dikunci Tidak untuk Insentif & Dana Bantuan) -->
                    <div v-if="!isSimplifiedCategory" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="font-bold text-slate-800 text-xs block">
                                    Perlu NPO (Nota PO)? <span class="text-rose-500">*</span>
                                </span>
                                <span class="text-[11px] text-slate-500">
                                    Pilih apakah transaksi ini memerlukan penerbitan Nota Purchase Order.
                                </span>
                            </div>
                            <div class="inline-flex rounded-lg bg-slate-200/80 p-0.5 border border-slate-300 shrink-0">
                                <button
                                    type="button"
                                    @click="manualForm.perlu_npo = true"
                                    class="px-3.5 py-1 rounded-md text-xs font-bold transition-all cursor-pointer"
                                    :class="manualForm.perlu_npo ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                >
                                    Ya
                                </button>
                                <button
                                    type="button"
                                    @click="manualForm.perlu_npo = false"
                                    class="px-3.5 py-1 rounded-md text-xs font-bold transition-all cursor-pointer"
                                    :class="!manualForm.perlu_npo ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                >
                                    Tidak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Tanggal: Tanggal Pesanan (jika NPO Ya) & Tanggal Pembayaran -->
                    <div v-if="!isSimplifiedCategory" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- Tanggal Pesanan: Otomatis tampil bila memilih Ya -->
                        <div v-if="manualForm.perlu_npo">
                            <label class="block font-bold text-slate-700 mb-1">
                                Tanggal Pesanan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="manualForm.tanggal_pesanan"
                                type="date"
                                required
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-semibold"
                            />
                        </div>

                        <!-- Tanggal Pembayaran -->
                        <div :class="{ 'sm:col-span-2': !manualForm.perlu_npo }">
                            <label class="block font-bold text-slate-700 mb-1">
                                Tanggal Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="manualForm.tanggal"
                                type="date"
                                required
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-semibold"
                            />
                            <p v-if="!manualForm.perlu_npo" class="text-[10.5px] text-slate-400 mt-1 italic">
                                * Tanggal pesanan otomatis disamakan dengan tanggal pembayaran karena tanpa NPO.
                            </p>
                        </div>
                    </div>

                    <!-- Tanggal Pembayaran (Khusus Insentif Fasilitas & Dana Bantuan Pemerintah: Tanggal Pesanan Kunci Langsung Sesuai Tanggal Pembayaran) -->
                    <div v-else>
                        <label class="block font-bold text-slate-700 mb-1">
                            Tanggal Pembayaran <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="manualForm.tanggal"
                            type="date"
                            required
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-semibold"
                        />
                    </div>

                    <!-- 4. Sandingkan Work Order (Hanya Untuk Bahan Baku & Operasional, Kunci Manual untuk Insentif & Dana Bantuan) -->
                    <div v-if="!isSimplifiedCategory">
                        <label class="block font-bold text-slate-700 mb-1">
                            Sandingkan Work Order <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="manualForm.work_order_id"
                            required
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-semibold text-slate-800"
                        >
                            <option value="" disabled>-- Pilih Sandingkan Work Order --</option>
                            
                            <!-- Pilihan WO yang bertepatan dengan tanggal pesanan/pembayaran -->
                            <optgroup v-if="matchingWorkOrders.length > 0" label="Sesuai Tanggal Pesanan / Pembayaran">
                                <option
                                    v-for="wo in matchingWorkOrders"
                                    :key="wo.id"
                                    :value="wo.id"
                                >
                                    {{ wo.nomor_wo ? wo.nomor_wo + ' - ' : '' }}{{ wo.nama_menu }} ({{ formatTanggalIndo(wo.tanggal_distribusi) }})
                                </option>
                            </optgroup>

                            <!-- Opsi Manual bila WO tidak tersedia atau transaksi umum tanpa WO -->
                            <optgroup label="Tanpa Kaitan Work Order">
                                <option value="manual">Manual</option>
                            </optgroup>

                            <!-- Pilihan Work Order Tanggal Lainnya -->
                            <optgroup v-if="otherWorkOrders.length > 0" label="Work Order Tanggal Lainnya">
                                <option
                                    v-for="wo in otherWorkOrders"
                                    :key="wo.id"
                                    :value="wo.id"
                                >
                                    {{ wo.nomor_wo ? wo.nomor_wo + ' - ' : '' }}{{ wo.nama_menu }} ({{ formatTanggalIndo(wo.tanggal_distribusi) }})
                                </option>
                            </optgroup>
                        </select>
                        <p v-if="matchingWorkOrders.length > 0" class="text-[11px] text-emerald-600 font-medium mt-1">
                            ✓ Ditemukan {{ matchingWorkOrders.length }} Work Order bertepatan dengan tanggal yang dipilih.
                        </p>
                        <p v-else-if="manualForm.tanggal || manualForm.tanggal_pesanan" class="text-[11px] text-slate-500 mt-1 italic">
                            Tidak ada Work Order pada tanggal pesanan/pembayaran yang dipilih. Silakan pilih "Manual".
                        </p>
                    </div>

                    <!-- 5. Nama Barang/Bahan ATAU Uraian Transaksi -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            {{ manualForm.kategori === 'Operasional' ? 'Nama Barang / Bahan (Operasional)' : (isSimplifiedCategory ? 'Uraian Transaksi' : 'Nama Barang/Bahan') }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="manualForm.nama_transaksi"
                            type="text"
                            required
                            :placeholder="manualForm.kategori === 'Operasional' ? 'Tuliskan nama atau rincian operasional...' : (isSimplifiedCategory ? 'Contoh: Pembayaran insentif sarana prasarana unit SPPG, Penyaluran dana bantuan operasional...' : 'Contoh: Daging Ayam, Beras, Gas LPG 12kg, Sayur Bayam...')"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs"
                        />
                    </div>

                    <!-- 6. Volume dan Satuan (Hanya Bahan Baku & Operasional, Dihapus/Kunci 1 untuk Insentif & Dana Bantuan) -->
                    <div v-if="!isSimplifiedCategory" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Volume <span class="text-slate-400 font-normal">(Jumlah)</span> <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model.number="manualForm.volume"
                                type="number"
                                step="0.1"
                                min="0.1"
                                required
                                placeholder="Contoh: 10 atau 2.5"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Satuan <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="manualForm.satuan"
                                required
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-bold text-slate-800"
                            >
                                <option value="" disabled>-- Pilih Satuan --</option>
                                <option v-for="s in SATUAN_LIST" :key="s.value" :value="s.value">
                                    {{ s.value }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- 7. Supplier (Hanya Bahan Baku & Operasional, Dihapus untuk Insentif & Dana Bantuan) -->
                    <div v-if="!isSimplifiedCategory">
                        <label class="block font-bold text-slate-700 mb-1">
                            Supplier <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="manualForm.supplier_id"
                            @change="onSupplierSelectChange"
                            required
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-semibold text-slate-800"
                        >
                            <option value="" disabled>-- Pilih Supplier Terdaftar --</option>
                            <option v-for="s in (props.suppliers || [])" :key="s.id" :value="s.id">
                                {{ s.nama_usaha }} {{ s.jenis_supplier ? `(${s.jenis_supplier})` : '' }}
                            </option>
                        </select>
                        <p v-if="!props.suppliers || props.suppliers.length === 0" class="text-[11px] text-amber-600 font-medium mt-1">
                            Belum ada data supplier terdaftar. Silakan tambahkan supplier terlebih dahulu di menu Supplier.
                        </p>
                    </div>

                    <!-- 8. Harga Barang/Bahan ATAU Nominal -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            {{ isSimplifiedCategory ? 'Nominal' : 'Harga Barang/Bahan' }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3 font-extrabold text-slate-500 pointer-events-none select-none text-xs">Rp</span>
                            <input
                                v-model="formattedNominal"
                                type="text"
                                inputmode="numeric"
                                required
                                placeholder="0"
                                @keypress="(e) => !/[0-9]/.test(e.key) && e.preventDefault()"
                                class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs font-black text-slate-900"
                            />
                        </div>
                    </div>

                    <!-- 8. Keterangan (Cukup Namanya Keterangan Saja) -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Keterangan</label>
                        <textarea
                            v-model="manualForm.keterangan"
                            rows="2"
                            placeholder="Keterangan transaksi..."
                            class="w-full p-2.5 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-xs"
                        ></textarea>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showManualModal = false"
                            class="px-4 py-2 text-xs font-bold rounded-xl text-slate-600 hover:bg-slate-100 cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-5 py-2 text-xs font-black rounded-xl bg-primary hover:bg-primary/90 text-white shadow-xs cursor-pointer disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Transaksi') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL DETAIL TRANSAKSI -->
        <Modal :show="showDetailModal" @close="showDetailModal = false" max-width="md">
            <div v-if="selectedTrx" class="p-5 sm:p-6 space-y-4 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-primary/10 text-primary">
                            <Receipt class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Rincian Transaksi</h3>
                            <p class="text-[11px] font-mono text-slate-500">{{ selectedTrx.nomor_transaksi }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">
                            {{ ['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(selectedTrx.kategori) ? 'Uraian Transaksi:' : 'Nama Barang/Bahan:' }}
                        </span>
                        <span class="font-bold text-slate-900">{{ selectedTrx.nama_transaksi }}</span>
                    </div>
                    <div v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(selectedTrx.kategori)" class="flex items-center justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500 font-semibold">Sandingan Work Order:</span>
                        <span v-if="selectedTrx.work_order" class="font-bold text-indigo-700">
                            {{ selectedTrx.work_order.nomor_wo }} - {{ selectedTrx.work_order.nama_menu }}
                        </span>
                        <span v-else class="font-semibold text-slate-500">
                            Manual (Tanpa Work Order)
                        </span>
                    </div>
                    <div v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(selectedTrx.kategori) && (selectedTrx.volume || selectedTrx.qty)" class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Volume & Satuan:</span>
                        <span class="font-bold text-slate-800">{{ selectedTrx.volume || selectedTrx.qty }} {{ selectedTrx.satuan && selectedTrx.satuan !== '-' ? selectedTrx.satuan : 'Kg' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Tanggal Pembayaran:</span>
                        <span class="font-bold text-slate-800">{{ formatTanggalIndo(selectedTrx.tanggal) }}</span>
                    </div>
                    <div v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(selectedTrx.kategori) && selectedTrx.tanggal_pesanan" class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Tanggal Pesanan:</span>
                        <span class="font-bold text-slate-800">{{ formatTanggalIndo(selectedTrx.tanggal_pesanan) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Kategori:</span>
                        <span class="font-bold text-slate-800">{{ selectedTrx.kategori }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Arus Transaksi:</span>
                        <span
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold border',
                                selectedTrx.arus === 'Masuk'
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-rose-50 text-rose-800 border-rose-200'
                            ]"
                        >
                            <ArrowDownLeft v-if="selectedTrx.arus === 'Masuk'" class="h-3 w-3 text-emerald-600" />
                            <ArrowUpRight v-else class="h-3 w-3 text-rose-600" />
                            {{ selectedTrx.arus === 'Masuk' ? 'Uang Masuk' : 'Uang Keluar' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Status:</span>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded-full text-[10.5px] font-bold border',
                                selectedTrx.status === 'Disetujui'
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : (selectedTrx.status === 'Ditunda'
                                        ? 'bg-amber-50 text-amber-800 border-amber-200'
                                        : (selectedTrx.status === 'Ditolak'
                                            ? 'bg-rose-50 text-rose-800 border-rose-200'
                                            : 'bg-slate-100 text-slate-700 border-slate-300'))
                            ]"
                        >
                            {{ selectedTrx.status }}
                        </span>
                    </div>
                    <div v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(selectedTrx.kategori)" class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Perlu NPO (Nota PO):</span>
                        <span class="font-bold" :class="selectedTrx.perlu_npo ? 'text-emerald-700' : 'text-slate-500'">
                            {{ selectedTrx.perlu_npo ? 'Ya' : 'Tidak' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Sumber:</span>
                        <span class="font-bold text-slate-800">
                            {{ selectedTrx.source_type === 'manual' ? 'Pencatatan Manual' : 'PO Rancang Menu Otomatis' }}
                        </span>
                    </div>
                    <div v-if="!['Insentif Fasilitas', 'Dana Bantuan Pemerintah'].includes(selectedTrx.kategori)" class="flex items-center justify-between">
                        <span class="text-slate-500 font-semibold">Supplier:</span>
                        <span class="font-bold text-slate-800">{{ selectedTrx.vendor || '-' }}</span>
                    </div>
                    <div class="flex items-baseline justify-between pt-2 border-t border-slate-200">
                        <span class="text-slate-700 font-extrabold uppercase">Total Nominal:</span>
                        <span class="text-base font-black text-emerald-700">{{ formatRupiah(selectedTrx.nominal) }}</span>
                    </div>
                </div>

                <div v-if="selectedTrx.keterangan && selectedTrx.keterangan !== '-'" class="p-3 rounded-xl bg-white border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block mb-0.5">Keterangan:</span>
                    <p class="text-slate-700 font-medium">{{ selectedTrx.keterangan }}</p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <button
                            v-if="selectedTrx.status === 'Ditunda'"
                            type="button"
                            @click="quickApproveTrx(selectedTrx)"
                            :disabled="isUpdatingStatus"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white flex items-center gap-1.5 cursor-pointer shadow-2xs transition-colors disabled:opacity-50"
                            title="Setujui transaksi ini agar sah terhitung ke realisasi keuangan"
                        >
                            <Check class="h-3.5 w-3.5" />
                            <span>Setujui Transaksi</span>
                        </button>
                        <button
                            type="button"
                            @click="handleDownloadKuitansiSingleDirect(selectedTrx)"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 flex items-center gap-1.5 cursor-pointer shadow-2xs transition-colors"
                            title="Download Kuitansi Transaksi Ini (.pdf)"
                        >
                            <Receipt class="h-3.5 w-3.5 text-emerald-600" />
                            <span>Kuitansi (PDF)</span>
                        </button>
                        <button
                            v-if="selectedTrx.perlu_npo"
                            type="button"
                            @click="handleDownloadNotaPoSingle(selectedTrx)"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl bg-blue-50 text-blue-800 hover:bg-blue-100 border border-blue-200 flex items-center gap-1.5 cursor-pointer shadow-2xs transition-colors"
                            title="Download Laporan Nota PO Format Excel (.xlsx)"
                        >
                            <FileSpreadsheet class="h-3.5 w-3.5 text-blue-600" />
                            <span>Nota PO (Excel)</span>
                        </button>
                    </div>
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>

        <!-- MODAL KONFIRMASI HAPUS -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="sm">
            <div class="p-5 space-y-4 text-xs text-center">
                <div class="h-12 w-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                    <Trash2 class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Hapus Transaksi?</h3>
                    <p class="text-slate-500 mt-1">
                        Apakah Anda yakin ingin menghapus transaksi
                        <span class="font-bold text-slate-800">"{{ itemToDelete?.nama_transaksi }}"</span>?
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2 pt-2">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        :disabled="isSubmitting"
                        class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl cursor-pointer disabled:opacity-50"
                    >
                        {{ isSubmitting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- MODAL KUITANSI RESMI TRANSAKSI (PDF & CETAK) -->
        <TransaksiReceiptModal
            :show="showReceiptModal"
            :item="selectedReceiptTrx"
            :all-rows="filteredTransactions"
            :unit-sppg="props.unitSppg"
            @close="showReceiptModal = false"
        />
    </div>
</template>
