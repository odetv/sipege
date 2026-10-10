<script setup>
import { ref, computed, watch } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DateRangePicker from "@/Components/DateRangePicker.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";
import axios from "axios";
import {
    CreditCard,
    DollarSign,
    Users,
    Calendar,
    CalendarCheck,
    CheckCircle2,
    AlertCircle,
    Download,
    Eye,
    RefreshCw,
    Search,
    Copy,
    Check,
    X,
    Filter,
    ArrowUpDown,
    Building2,
    Clock,
    FileSpreadsheet,
    HelpCircle,
    Info,
    ShieldAlert,
    ChevronDown,
    Sparkles,
} from "lucide-vue-next";

const props = defineProps({
    petugas: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    daftarJabatan: { type: Array, default: () => [] },
    periodes: { type: Array, default: () => [] },
    unitSppg: { type: Object, default: null },
    initialMode: { type: String, default: "presensi" },
    initialTanggalMulai: { type: String, default: "" },
    initialTanggalSelesai: { type: String, default: "" },
    initialPeriodeId: { type: [String, Number], default: "all" },
    initialPresensiMap: { type: Object, default: () => ({}) },
    mandaysMap: { type: Object, default: () => ({}) },
});

// ─── Formatters ───────────────────────────────────────────────────────────────
function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}

function formatRibuan(num) {
    if (num === null || num === undefined || isNaN(num) || num === "") return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}

function formatDateIndo(dateStr) {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    } catch {
        return dateStr;
    }
}

// ─── Format Tanggal Hari Ini ──────────────────────────────────────────────────
const todayStr = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
});

function normalizeModeGaji(m) {
    if (!m) return "";
    const val = String(m).toLowerCase();
    if (val === "hari_ini" || val === "today") return "hari_ini";
    if (val === "periode" || val === "periodik" || val === "period") return "periode";
    if (val === "rentang" || val === "bulanan" || val === "range" || val === "custom") return "rentang";
    return "";
}

// ─── State Filter & Mode Perhitungan ──────────────────────────────────────────
// Sesuai permintaan user: defaultnya hari ini
const modeSkala = ref(normalizeModeGaji(props.initialMode) || "hari_ini");
const selectedPeriodeId = ref(props.initialPeriodeId !== undefined ? String(props.initialPeriodeId) : "all");
const tanggalMulai = ref(props.initialTanggalMulai || todayStr.value);
const tanggalSelesai = ref(props.initialTanggalSelesai || todayStr.value);
const modePerhitungan = ref("presensi");
const isFilterAllTime = ref(false);

const rentangHariCount = computed(() => {
    if (!tanggalMulai.value || !tanggalSelesai.value) return 0;
    const start = new Date(tanggalMulai.value + "T00:00:00");
    const end = new Date(tanggalSelesai.value + "T00:00:00");
    const diffTime = end.getTime() - start.getTime();
    if (diffTime < 0) return 0;
    return Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

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

// Inisialisasi awal sinkronisasi jika periode belum terpilih tapi tanggal cocok dengan suatu periode
if (selectedPeriodeId.value === "all") {
    const matched = findMatchingPeriode(tanggalMulai.value, tanggalSelesai.value);
    if (matched) {
        selectedPeriodeId.value = String(matched.id);
    }
}

const selectedPeriode = computed(() => {
    if (!selectedPeriodeId.value || selectedPeriodeId.value === "all") return null;
    return props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value)) || null;
});

const is14HariActive = computed(() => rentangHariCount.value === 14);
const is28HariActive = computed(() => rentangHariCount.value === 28);

const labelSiklus = computed(() => {
    if (selectedPeriode.value) {
        return `Siklus: Periode ${selectedPeriode.value.nomor_periode} (${rentangHariCount.value} Hari Kerja)`;
    }
    if (rentangHariCount.value === 14) return "Siklus: Periodik (14 Hari Kerja)";
    if (rentangHariCount.value === 28) return "Siklus: Bulanan (28 Hari Kerja)";
    return `Siklus: Kustom (${rentangHariCount.value} Hari Kerja)`;
});

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

        if (!isRemarkCustom.value) {
            remark.value = computeDefaultRemark(newRange.start, newRange.end);
        }

        isDatePickerOpen.value = false;
        applyFilterTanggal();
    }
}

function setModeSkala(newMode) {
    modeSkala.value = newMode;
    const newEnd = getDefaultEndDate(newMode, tanggalMulai.value);
    tanggalSelesai.value = newEnd;

    const matched = findMatchingPeriode(tanggalMulai.value, newEnd);
    if (matched) {
        selectedPeriodeId.value = String(matched.id);
    } else {
        selectedPeriodeId.value = "all";
    }

    if (!isRemarkCustom.value) {
        remark.value = computeDefaultRemark(tanggalMulai.value, newEnd);
    }

    applyFilterTanggal();
}

function onPeriodeSelectChange() {
    if (selectedPeriodeId.value === "all") {
        applyFilterTanggal();
        return;
    }
    const p = props.periodes?.find((it) => String(it.id) === String(selectedPeriodeId.value));
    if (p && p.tanggal_mulai && p.tanggal_selesai) {
        const newStart = p.tanggal_mulai.substring(0, 10);
        const newEnd = p.tanggal_selesai.substring(0, 10);
        tanggalMulai.value = newStart;
        tanggalSelesai.value = newEnd;
        modeSkala.value = "periodik";

        if (!isRemarkCustom.value) {
            remark.value = computeDefaultRemark(newStart, newEnd);
        }

        applyFilterTanggal();
    }
}

function applyFilterTanggal() {
    router.get(
        route("petugas.pembayaran-gaji"),
        {
            tanggal_mulai: tanggalMulai.value,
            tanggal_selesai: tanggalSelesai.value,
            periode_id: selectedPeriodeId.value,
            filter_mode: modeSkala.value,
            mode: modePerhitungan.value,
        },
        { preserveState: true, preserveScroll: true }
    );
}

function onDateFilterChange(payload) {
    if (!payload) return;
    tanggalMulai.value = payload.start;
    tanggalSelesai.value = payload.end;
    modeSkala.value = payload.mode;
    selectedPeriodeId.value = payload.periodeId;
    isFilterAllTime.value = !!payload.isAllTime;

    if (!isRemarkCustom.value) {
        remark.value = computeDefaultRemark(payload.start, payload.end);
    }

    applyFilterTanggal();
}

// BNI Direct Inhouse Headers
const rekDebet = ref(props.summary?.rek_debet_default || "5268080021123800");

const tglTransaksi = computed(() => {
    return tanggalSelesai.value || new Date().toISOString().slice(0, 10);
});

function formatDDMMYYYY(dStr) {
    if (!dStr) {
        const now = new Date();
        const dd = String(now.getDate()).padStart(2, "0");
        const mm = String(now.getMonth() + 1).padStart(2, "0");
        const yyyy = now.getFullYear();
        return `${dd}-${mm}-${yyyy}`;
    }
    const parts = String(dStr).split("-");
    if (parts.length === 3) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    const now = new Date(dStr);
    if (!isNaN(now.getTime())) {
        const dd = String(now.getDate()).padStart(2, "0");
        const mm = String(now.getMonth() + 1).padStart(2, "0");
        const yyyy = now.getFullYear();
        return `${dd}-${mm}-${yyyy}`;
    }
    return dStr;
}

function computeDefaultRemark(startStr, endStr) {
    const start = startStr || tanggalMulai.value;
    const end = endStr || tanggalSelesai.value || start;

    if (!start && !end) {
        const todayFmt = formatDDMMYYYY(new Date().toISOString().slice(0, 10));
        return `Gaji Petugas SPPG ${todayFmt}`;
    }

    const startFmt = formatDDMMYYYY(start);
    const endFmt = formatDDMMYYYY(end);

    // Jika tanggal tunggal (start sama dengan end)
    if (!end || start === end) {
        return `Gaji Petugas SPPG ${startFmt}`;
    }

    // Jika rentang atau periode
    return `Gaji Petugas SPPG ${startFmt} s/d ${endFmt}`;
}

const isRemarkCustom = ref(false);
const remark = ref(computeDefaultRemark(tanggalMulai.value, tanggalSelesai.value));

function resetDefaultRemark() {
    isRemarkCustom.value = false;
    remark.value = computeDefaultRemark(tanggalMulai.value, tanggalSelesai.value);
}

watch([tanggalMulai, tanggalSelesai], ([newStart, newEnd]) => {
    if (!isRemarkCustom.value && newStart && newEnd) {
        remark.value = computeDefaultRemark(newStart, newEnd);
    }
});

// Table Search & Filter
const searchQuery = ref("");
const filterJabatan = ref("all");
const filterRekening = ref("all"); // 'all' | 'siap' | 'invalid_zero' | 'empty' | 'valid'

// ─── Perhitungan Amount Rows Petugas ──────────────────────────────────────────
const rows = ref([]);

function calculateAutoAmount(p, mode) {
    const rateBgn = parseInt(p.gaji_harian_bgn) || 0; // Total Gaji BGN (tanpa menghitung bonus harian mitra)
    if (mode === "presensi") {
        const mandays = props.mandaysMap[p.id]?.mandays ?? 0;
        return Math.round(mandays * rateBgn);
    } else if (mode === "bgn_20") {
        return rateBgn * 20;
    } else if (mode === "penuh_rentang" || mode === "periodik_14" || mode === "bulanan_28") {
        return rateBgn * rentangHariCount.value;
    }
    return 0;
}

function initRows() {
    rows.value = props.petugas.map((p) => {
        const mandaysInfo = props.mandaysMap[p.id] || {
            mandays: 0,
            hadir: 0,
            setengah: 0,
            libur: 0,
            izin: 0,
            sakit: 0,
            tk: 0,
        };
        const autoAmt = calculateAutoAmount(p, modePerhitungan.value);
        const hasRekening = Boolean(p.nomor_rekening && p.nomor_rekening.trim().length >= 5);
        const rateBgn = parseInt(p.gaji_harian_bgn) || 0;
        const bonusMitra = parseInt(p.bonus_harian_mitra) || 0;
        const isValid = p.status === "Aktif" && hasRekening && autoAmt > 0;
        return {
            petugas: p,
            selected: isValid,
            mandaysInfo,
            rateHarian: rateBgn,
            rateHarianBgn: rateBgn,
            bonusHarianMitra: bonusMitra,
            amount: autoAmt,
            isCustom: false,
        };
    });
}

// Inisialisasi awal
initRows();

// Sinkronisasi saat props atau mode berubah
watch(
    () => [props.petugas, props.mandaysMap],
    () => {
        initRows();
    },
    { deep: true }
);

function applyModePerhitungan(newMode) {
    modePerhitungan.value = newMode;
    rows.value.forEach((r) => {
        if (!r.isCustom) {
            r.amount = calculateAutoAmount(r.petugas, newMode);
        }
        const hasRek = Boolean(r.petugas.nomor_rekening && r.petugas.nomor_rekening.trim().length >= 5);
        if (r.amount <= 0 || !hasRek || r.petugas.status !== "Aktif") {
            r.selected = false;
        } else if (!r.isCustom) {
            r.selected = true;
        }
    });
}

function resetRowAmount(r) {
    r.amount = calculateAutoAmount(r.petugas, modePerhitungan.value);
    r.isCustom = false;
    const hasRek = Boolean(r.petugas.nomor_rekening && r.petugas.nomor_rekening.trim().length >= 5);
    r.selected = r.amount > 0 && hasRek && r.petugas.status === "Aktif";
}

function handleAmountInput(r, valStr) {
    const cleanDigits = String(valStr).replace(/\D/g, "");
    const intVal = cleanDigits === "" ? 0 : parseInt(cleanDigits, 10);
    r.amount = intVal;
    r.isCustom = true;
    const hasRek = Boolean(r.petugas.nomor_rekening && r.petugas.nomor_rekening.trim().length >= 5);
    if (intVal <= 0) {
        r.selected = false;
    } else if (hasRek && r.petugas.status === "Aktif") {
        r.selected = true;
    }
}

// ─── Filtered Table Rows ──────────────────────────────────────────────────────
const filteredRows = computed(() => {
    return rows.value.filter((r) => {
        const p = r.petugas;
        // Search text
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim();
            const matchNama = (p.nama || "").toLowerCase().includes(q);
            const matchNik = (p.nik || "").toLowerCase().includes(q);
            const matchRek = (p.nomor_rekening || "").toLowerCase().includes(q);
            const matchJab = (p.jabatan || "").toLowerCase().includes(q);
            if (!matchNama && !matchNik && !matchRek && !matchJab) {
                return false;
            }
        }

        // Filter Jabatan
        if (filterJabatan.value !== "all" && p.jabatan !== filterJabatan.value) {
            return false;
        }

        // Filter Status Rekening & Validitas Penggajian
        const hasRek = Boolean(p.nomor_rekening && p.nomor_rekening.trim().length >= 5);
        if (filterRekening.value === "siap" && (!hasRek || r.amount <= 0 || p.status !== "Aktif")) return false;
        if (filterRekening.value === "invalid_zero" && r.amount > 0) return false;
        if (filterRekening.value === "empty" && hasRek) return false;
        if (filterRekening.value === "valid" && !hasRek) return false;

        return true;
    });
});

// Selection All Toggle (Hanya centang personil valid: rekening ada & gaji > 0)
const selectableFilteredRows = computed(() => {
    return filteredRows.value.filter((r) => {
        const hasRek = Boolean(r.petugas.nomor_rekening && r.petugas.nomor_rekening.trim().length >= 5);
        return r.petugas.status === "Aktif" && hasRek && r.amount > 0;
    });
});

const isAllFilteredSelected = computed(() => {
    const validRows = selectableFilteredRows.value;
    if (validRows.length === 0) return false;
    return validRows.every((r) => r.selected);
});

function toggleSelectAll() {
    const targetState = !isAllFilteredSelected.value;
    filteredRows.value.forEach((r) => {
        const hasRek = Boolean(r.petugas.nomor_rekening && r.petugas.nomor_rekening.trim().length >= 5);
        const isValid = r.petugas.status === "Aktif" && hasRek && r.amount > 0;
        if (targetState) {
            r.selected = isValid;
        } else {
            r.selected = false;
        }
    });
}

// ─── KPI Summaries ────────────────────────────────────────────────────────────
const selectedRows = computed(() => rows.value.filter((r) => r.selected));
const totalSelectedCount = computed(() => selectedRows.value.length);
const totalPayrollAmount = computed(() => {
    return selectedRows.value.reduce((acc, r) => acc + (r.amount || 0), 0);
});
const averagePayroll = computed(() => {
    if (totalSelectedCount.value === 0) return 0;
    return Math.round(totalPayrollAmount.value / totalSelectedCount.value);
});
const readyBniCount = computed(() => {
    return selectedRows.value.filter((r) => {
        const rek = r.petugas.nomor_rekening;
        return rek && rek.trim().length >= 5;
    }).length;
});
const missingRekeningCount = computed(() => {
    return selectedRows.value.filter((r) => {
        const rek = r.petugas.nomor_rekening;
        return !rek || rek.trim().length < 5;
    }).length;
});

// ─── BNI Direct CSV Generator (Clean & Compliant) ─────────────────────────────
function sanitizeBniText(text, maxLen = 40) {
    if (!text) return "";
    const restricted = [',', '`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '_', '{', '}', '<', '>', '[', ']', '=', '\\', ';', '"', "'"];
    let cleaned = String(text);
    restricted.forEach((c) => {
        cleaned = cleaned.split(c).join(" ");
    });
    cleaned = cleaned.replace(/\s+/g, " ").trim();
    return cleaned.slice(0, maxLen);
}

function splitBniRemarks(fullRemark) {
    const cleaned = sanitizeBniText(fullRemark || "", 83);
    if (!cleaned) {
        return { remark1: "", remark2: "" };
    }
    if (cleaned.length <= 33) {
        return { remark1: cleaned, remark2: "" };
    }
    const slice33 = cleaned.slice(0, 33);
    const lastSpace = slice33.lastIndexOf(" ");
    if (lastSpace > 15) {
        const rem1 = cleaned.slice(0, lastSpace).trim();
        const rem2 = cleaned.slice(lastSpace + 1, lastSpace + 1 + 50).trim();
        return { remark1: rem1, remark2: rem2 };
    }
    const rem1 = cleaned.slice(0, 33).trim();
    const rem2 = cleaned.slice(33, 33 + 50).trim();
    return { remark1: rem1, remark2: rem2 };
}

const splitRemarkPreview = computed(() => {
    return splitBniRemarks(remark.value);
});

function buildBniDirectCsvContent() {
    const items = selectedRows.value.filter((r) => r.amount > 0);
    const now = new Date();
    const pad = (n) => String(n).padStart(2, "0");

    const YYYY = now.getFullYear();
    const MM = pad(now.getMonth() + 1);
    const DD = pad(now.getDate());
    const HH = pad(now.getHours());
    const mm = pad(now.getMinutes());
    const ss = pad(now.getSeconds());

    const timestampCreation = `${YYYY}/${MM}/${DD}_${HH}:${mm}:${ss}`;
    const timestampFile = `${YYYY}${MM}${DD}_${HH}${mm}${ss}`;

    const cleanRekDebet = String(rekDebet.value).replace(/\D/g, "").slice(0, 16) || "5268080021123800";
    const cleanTglTransaksi = tglTransaksi.value
        ? tglTransaksi.value.replace(/-/g, "")
        : `${YYYY}${MM}${DD}`;
    const { remark1: cleanRemark1, remark2: cleanRemark2 } = splitBniRemarks(remark.value);

    const totalRecords = items.length;
    const totalAmount = items.reduce((sum, r) => sum + (parseInt(r.amount, 10) || 0), 0);

    // Line 1: Timestamp dan total baris (records + 2 baris header), diikuti 18 koma (total 20 kolom)
    const line1 = `${timestampCreation},${totalRecords + 2}${",".repeat(18)}`;

    // Line 2: 'P', TglTransaksi, RekDebet, TotalRecord, TotalAmount, diikuti 15 koma (total 20 kolom)
    const line2 = `P,${cleanTglTransaksi},${cleanRekDebet},${totalRecords},${totalAmount}${",".repeat(15)}`;

    const csvLines = [line1, line2];

    items.forEach((r) => {
        const rekTujuan = String(r.petugas.nomor_rekening || "").replace(/\D/g, "").slice(0, 16);
        const namaClean = sanitizeBniText(r.petugas.nama || "", 40);
        const amount = parseInt(r.amount, 10) || 0;
        const email = (r.petugas.email || "").trim();
        const hasEmail = email.includes("@") && email.includes(".");

        // 20 columns:
        // Col 0: Rek. Tujuan(16)
        // Col 1: Nama Penerima(40)
        // Col 2: Amount
        // Col 3: Remark1(33)
        // Col 4: Remark2(50)
        // Col 5..15: Empty
        // Col 16: EMAIL FLAG(1) -> 'N' atau 'Y'
        // Col 17: Email(100)
        // Col 18: Reff Num(16) -> Empty
        // Col 19: FLAG(1) -> 'N'
        const cols = [
            rekTujuan,
            namaClean,
            String(amount),
            cleanRemark1,
            cleanRemark2,
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            hasEmail ? "Y" : "N",
            hasEmail ? email : "",
            "",
            "N",
        ];
        csvLines.push(cols.join(","));
    });

    return {
        csvString: csvLines.join("\r\n") + "\r\n",
        filename: `Uploadfile_IH_${timestampFile}.csv`,
        totalRecords,
        totalAmount,
    };
}

// ─── Trigger Download CSV ─────────────────────────────────────────────────────
const isDownloading = ref(false);
const downloadSuccess = ref(false);

function triggerDownloadCsv() {
    if (totalSelectedCount.value === 0) {
        alert("Pilih minimal 1 petugas untuk digaji.");
        return;
    }

    if (missingRekeningCount.value > 0) {
        const proceed = confirm(
            `Ada ${missingRekeningCount.value} petugas terpilih yang belum memiliki nomor rekening BNI valid. Lanjutkan pembuatan CSV hanya untuk rekening yang terisi?`
        );
        if (!proceed) return;
    }

    isDownloading.value = true;
    try {
        const { csvString, filename } = buildBniDirectCsvContent();
        const blob = new Blob([csvString], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        downloadSuccess.value = true;
        setTimeout(() => {
            downloadSuccess.value = false;
        }, 4000);
    } catch (e) {
        console.error("Gagal membuat CSV BNI Direct:", e);
        alert("Gagal membuat file CSV: " + e.message);
    } finally {
        isDownloading.value = false;
    }
}

// ─── Trigger Download Template .XLS BNI Direct (Hanya 3 Kolom) ───────────────
const isDownloadingExcel = ref(false);
const downloadExcelSuccess = ref(false);

async function triggerDownloadExcel() {
    if (totalSelectedCount.value === 0) {
        alert("Pilih minimal 1 petugas untuk ekspor ke template .xls.");
        return;
    }

    if (missingRekeningCount.value > 0) {
        const proceed = confirm(
            `Ada ${missingRekeningCount.value} petugas terpilih yang belum memiliki nomor rekening BNI valid. Lanjutkan ekspor template .xls hanya untuk rekening yang terisi?`
        );
        if (!proceed) return;
    }

    isDownloadingExcel.value = true;
    try {
        const validItems = selectedRows.value
            .filter((r) => (parseInt(r.amount, 10) || 0) > 0)
            .map((r) => ({
                rek_tujuan: String(r.petugas?.nomor_rekening || "").replace(/\D/g, "").slice(0, 16),
                nama: String(r.petugas?.nama || "").trim(),
                amount: parseInt(r.amount, 10) || 0,
                email: String(r.petugas?.email || "").trim(),
            }));

        if (validItems.length === 0) {
            alert("Tidak ada petugas terpilih dengan nominal gaji di atas 0.");
            return;
        }

        const response = await axios.post(
            route("petugas.pembayaran-gaji.download-xls"),
            {
                items: validItems,
                rek_debet: rekDebet.value,
                tgl_transaksi: tglTransaksi.value,
                remark: remark.value,
            },
            { responseType: "blob" }
        );

        const blob = new Blob([response.data], { type: "application/vnd.ms-excel" });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", "BNIDIRECT-EXCEL_TEMPLATE_v1.9.6.xls");
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        downloadExcelSuccess.value = true;
        setTimeout(() => {
            downloadExcelSuccess.value = false;
        }, 5000);
    } catch (e) {
        console.error("Gagal mendownload template .xls BNI Direct:", e);
        let errorMsg = "Terjadi kesalahan saat memproses file template .xls.";
        if (e.response?.data instanceof Blob) {
            try {
                const text = await e.response.data.text();
                const json = JSON.parse(text);
                if (json.message) errorMsg = json.message;
                else if (json.error) errorMsg = json.error;
                else if (json.errors) errorMsg = Object.values(json.errors).flat().join("\n");
            } catch (_) {}
        } else if (e.response?.data?.message) {
            errorMsg = e.response.data.message;
        } else if (e.response?.data?.error) {
            errorMsg = e.response.data.error;
        } else if (e.message) {
            errorMsg = e.message;
        }
        alert(errorMsg);
    } finally {
        isDownloadingExcel.value = false;
    }
}

// ─── Preview Modal State ──────────────────────────────────────────────────────
const isPreviewModalOpen = ref(false);
const previewCsvText = ref("");
const previewFilename = ref("");
const isCopied = ref(false);

function openPreviewModal() {
    if (totalSelectedCount.value === 0) {
        alert("Pilih minimal 1 petugas untuk dipratinjau.");
        return;
    }
    const { csvString, filename } = buildBniDirectCsvContent();
    previewCsvText.value = csvString;
    previewFilename.value = filename;
    isPreviewModalOpen.value = true;
}

function closePreviewModal() {
    isPreviewModalOpen.value = false;
}

function copyPreviewToClipboard() {
    navigator.clipboard.writeText(previewCsvText.value);
    isCopied.value = true;
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
}

// Salin nomor rekening individual
const copiedRekRowId = ref(null);
function copyRowRekening(id, text) {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copiedRekRowId.value = id;
    setTimeout(() => {
        copiedRekRowId.value = null;
    }, 2000);
}
</script>

<template>
    <Head title="Pembayaran Gaji & Payroll BNI Direct - SIPEGE" />

    <AppLayout>
        <div class="space-y-6 pb-16">
            <!-- ─── Page Header & Navigation Breadcrumb ───────────────────── -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <!-- Breadcrumbs / Submenu Nav Tab -->
                    <div class="flex items-center gap-2 mb-2 text-xs font-medium">
                        <Link
                            :href="route('petugas.index')"
                            class="text-slate-500 hover:text-slate-800 transition-colors"
                        >
                            Daftar Petugas
                        </Link>
                        <span class="text-slate-300">/</span>
                        <Link
                            :href="route('petugas.rekap-kehadiran')"
                            class="text-slate-500 hover:text-slate-800 transition-colors"
                        >
                            Rekap Kehadiran
                        </Link>
                        <span class="text-slate-300">/</span>
                        <span class="text-primary font-bold">Pembayaran Gaji (BNI Direct)</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-emerald-600 text-white shadow-xs">
                            <CreditCard class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                                <span>Pembayaran Gaji Petugas SPPG</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    BNI Direct Inhouse
                                </span>
                            </h1>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Generator otomatis batch transfer payroll BNI Direct (Sheet Inhouse) sesuai kehadiran & kompensasi personil
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Action Buttons -->
                <div class="flex items-center gap-2 sm:gap-2.5 flex-wrap w-full lg:w-auto">
                    <button
                        type="button"
                        @click="openPreviewModal"
                        class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold transition-all shadow-2xs cursor-pointer flex-1 sm:flex-none"
                        title="Lihat isi format file CSV BNI Direct"
                    >
                        <Eye class="h-4 w-4 text-slate-500" />
                        <span>Preview CSV</span>
                    </button>

                    <!-- Download Template .XLS (Hanya 3 Kolom Diisi) -->
                    <button
                        type="button"
                        @click="triggerDownloadExcel"
                        :disabled="isDownloadingExcel || totalSelectedCount === 0"
                        title="Download template .xls resmi BNI Direct dengan hanya mengisi data di 3 kolom (Rek. Tujuan, Nama, Amount) tanpa mengubah format template"
                        class="inline-flex items-center justify-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-xs disabled:opacity-50 cursor-pointer flex-1 sm:flex-none"
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        <span v-if="isDownloadingExcel">Menyiapkan .xls...</span>
                        <span v-else>Download Template .XLS</span>
                    </button>

                    <!-- Download CSV BNI Direct -->
                    <button
                        type="button"
                        @click="triggerDownloadCsv"
                        :disabled="isDownloading || totalSelectedCount === 0"
                        class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-xs disabled:opacity-50 cursor-pointer w-full sm:w-auto"
                    >
                        <Download class="h-4 w-4" />
                        <span>Download CSV BNI Direct</span>
                    </button>
                </div>
            </div>

            <!-- Download Success Alert -->
            <div
                v-if="downloadSuccess"
                class="flex items-center justify-between p-4 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs shadow-2xs animate-in fade-in duration-200"
            >
                <div class="flex items-center gap-2.5">
                    <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0" />
                    <div>
                        <p class="font-bold">File CSV BNI Direct Berhasil Dibuat!</p>
                        <p class="text-[11px] text-emerald-700 mt-0.5">
                            File format <b>Uploadfile_IH_...csv</b> telah diunduh dan siap diupload langsung ke portal perbankan BNI Direct.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="downloadSuccess = false"
                    class="p-1 rounded-md text-emerald-600 hover:bg-emerald-100/60"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Excel Download Success Alert -->
            <div
                v-if="downloadExcelSuccess"
                class="flex items-center justify-between p-4 rounded-xl bg-teal-50 border border-teal-300 text-teal-900 text-xs shadow-2xs animate-in fade-in duration-200"
            >
                <div class="flex items-center gap-2.5">
                    <CheckCircle2 class="h-5 w-5 text-teal-600 shrink-0" />
                    <div>
                        <p class="font-bold">File Template .XLS BNI Direct Berhasil Diunduh!</p>
                        <p class="text-[11px] text-teal-700 mt-0.5">
                            File template <b>BNIDIRECT-EXCEL_TEMPLATE_v1.9.6.xls</b> telah diisikan datanya pada 3 kolom (Rek. Tujuan, Nama, Amount) tanpa mengubah format maupun macro template bawaan.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="downloadExcelSuccess = false"
                    class="p-1 rounded-md text-teal-600 hover:bg-teal-100/60"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- ─── Control Bar: Reusable PeriodDateFilterBar & Mode Perhitungan ─── -->
            <PeriodDateFilterBar
                v-model:startDate="tanggalMulai"
                v-model:endDate="tanggalSelesai"
                v-model:mode="modeSkala"
                v-model:periodeId="selectedPeriodeId"
                v-model:isAllTime="isFilterAllTime"
                :periodes="periodes"
                @change="onDateFilterChange"
            />

            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-3.5">

                <!-- Baris 3: Parameter BNI Inhouse (Rek. Debet & Remark) -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5 pt-3 border-t border-slate-100">
                    <!-- Rekening Debet SPPG (Rekening Sumber) -->
                    <div class="md:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center justify-between">
                            <span>Rekening Debet SPPG</span>
                            <span class="text-[10px] text-emerald-600 font-bold">16 Digit BNI</span>
                        </label>
                        <div class="relative">
                            <Building2 class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                            <input
                                v-model="rekDebet"
                                type="text"
                                maxlength="20"
                                placeholder="5268080021123800"
                                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white"
                            />
                        </div>
                    </div>

                    <!-- Keterangan Transfer (Remark BNI) -->
                    <div class="md:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center justify-between">
                            <span class="flex items-center gap-1.5 flex-wrap">
                                <span>Keterangan Transfer (Remark BNI)</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">
                                    {{ tanggalMulai === tanggalSelesai ? 'Gaji Petugas SPPG DD-MM-YYYY' : 'Gaji Petugas SPPG DD-MM-YYYY s/d DD-MM-YYYY' }}
                                </span>
                            </span>
                            <button
                                v-if="isRemarkCustom"
                                type="button"
                                @click="resetDefaultRemark"
                                class="inline-flex items-center gap-1 text-[11px] text-emerald-700 hover:text-emerald-800 font-bold cursor-pointer"
                                title="Kembalikan ke format default"
                            >
                                <RefreshCw class="h-3 w-3" />
                                <span>Reset Default</span>
                            </button>
                        </label>
                        <div class="relative">
                            <input
                                v-model="remark"
                                @input="isRemarkCustom = true"
                                type="text"
                                maxlength="83"
                                :placeholder="tanggalMulai === tanggalSelesai ? 'Gaji Petugas SPPG DD-MM-YYYY' : 'Gaji Petugas SPPG DD-MM-YYYY s/d DD-MM-YYYY'"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-900 focus:ring-1 focus:ring-primary focus:border-primary bg-white"
                            />
                        </div>
                        <div class="mt-1 flex items-center justify-between text-[10px] text-slate-500">
                            <span v-if="splitRemarkPreview.remark2" class="truncate max-w-[80%]" :title="`Remark1: ${splitRemarkPreview.remark1} | Remark2: ${splitRemarkPreview.remark2}`">
                                <b class="text-slate-700">R1:</b> "{{ splitRemarkPreview.remark1 }}" 
                                <span class="text-slate-300 mx-1">|</span> 
                                <b class="text-slate-700">R2:</b> "{{ splitRemarkPreview.remark2 }}"
                            </span>
                            <span v-else>
                                <b class="text-slate-700">Remark1:</b> "{{ splitRemarkPreview.remark1 }}"
                            </span>
                            <span class="font-mono text-slate-400 shrink-0">{{ remark.length }}/83</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── KPI Summary Cards (Responsive 2 Kolom di HP) ───────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
                <!-- Card 1: Penerima Terpilih -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-emerald-50 text-emerald-600 shrink-0">
                        <Users class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Penerima Terpilih
                        </p>
                        <p class="text-base sm:text-xl font-black text-slate-900 leading-tight">
                            {{ totalSelectedCount }} <span class="text-xs font-normal text-slate-400">/ {{ rows.length }}</span>
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            Personil payroll
                        </p>
                    </div>
                </div>

                <!-- Card 2: Total Nominal Payroll (Span 2 Kolom di HP) -->
                <div class="p-3.5 sm:p-4 rounded-xl bg-emerald-50/60 border border-emerald-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2.5 sm:gap-3.5 col-span-2 lg:col-span-2">
                    <div class="p-2.5 sm:p-3 rounded-lg bg-emerald-600 text-white shrink-0 shadow-xs">
                        <DollarSign class="h-5 w-5 sm:h-6 sm:w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-bold text-emerald-800 uppercase tracking-wider">
                            Total Gaji BGN (Tanpa Bonus Mitra)
                        </p>
                        <p class="text-xl sm:text-2xl font-black text-emerald-950 leading-tight truncate">
                            {{ formatRupiah(totalPayrollAmount) }}
                        </p>
                        <p class="text-[10px] text-emerald-700 mt-0.5">
                            Total dana payroll BNI dari akumulasi Gaji BGN
                        </p>
                    </div>
                </div>

                <!-- Card 3: Status Rekening BNI -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-blue-50 text-blue-600 shrink-0">
                        <CreditCard class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Rekening BNI
                        </p>
                        <p class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                            <span class="text-emerald-600 font-extrabold">{{ readyBniCount }}</span> Siap
                        </p>
                        <p v-if="missingRekeningCount > 0" class="text-[9.5px] sm:text-[10px] text-rose-600 font-semibold mt-0.5 truncate">
                            {{ missingRekeningCount }} belum rekening!
                        </p>
                        <p v-else class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            Semua terisi lengkap
                        </p>
                    </div>
                </div>

                <!-- Card 4: Rata-rata Honor -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-amber-50 text-amber-600 shrink-0">
                        <Clock class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Rata-Rata Gaji
                        </p>
                        <p class="text-xs sm:text-base font-bold text-slate-900 leading-tight truncate">
                            {{ formatRupiah(averagePayroll) }}
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            Per personil
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── Table Search & Filter Bar ─────────────────────────────── -->
            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3 flex-1 min-w-[280px]">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari personil, NIK, rekening..."
                            class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-primary focus:border-primary"
                        />
                    </div>

                    <!-- Filter Jabatan -->
                    <select
                        v-model="filterJabatan"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-1 focus:ring-primary focus:border-primary bg-white"
                    >
                        <option value="all">Semua Jabatan</option>
                        <option v-for="j in daftarJabatan" :key="j" :value="j">
                            {{ j }}
                        </option>
                    </select>

                    <!-- Filter Status Penggajian & Rekening -->
                    <select
                        v-model="filterRekening"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-1 focus:ring-primary focus:border-primary bg-white cursor-pointer"
                    >
                        <option value="all">Semua Status</option>
                        <option value="siap">● Siap Transfer (Valid & Gaji > 0)</option>
                        <option value="invalid_zero">✖ Tidak Valid (Gaji Rp 0)</option>
                        <option value="empty">⚠ Rekening Belum Ada</option>
                    </select>
                </div>

                <!-- Selection Status Text -->
                <div class="text-xs text-slate-500 flex items-center gap-3">
                    <span>
                        Terpilih: <b class="text-emerald-700 font-bold">{{ totalSelectedCount }}</b> dari <b>{{ rows.length }}</b> personil
                    </span>
                    <button
                        type="button"
                        @click="toggleSelectAll"
                        class="px-2.5 py-1 rounded-md border border-slate-200 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer"
                    >
                        {{ isAllFilteredSelected ? "Batal Pilih Semua" : "Pilih Semua" }}
                    </button>
                </div>
            </div>

            <!-- ─── Interactive Payroll Table ─────────────────────────────── -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-3 py-3 w-10 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="isAllFilteredSelected"
                                        @change="toggleSelectAll"
                                        class="rounded text-primary focus:ring-primary border-slate-300"
                                    />
                                </th>
                                <th class="px-3.5 py-3 w-12 text-center">No</th>
                                <th class="px-4 py-3 min-w-[200px]">Personil & Divisi</th>
                                <th class="px-4 py-3 min-w-[190px]">Rek. Tujuan (16)</th>
                                <th class="px-4 py-3 min-w-[170px]">Kehadiran & Mandays</th>
                                <th class="px-4 py-3 min-w-[140px] text-right">Tarif Gaji BGN</th>
                                <th class="px-4 py-3 min-w-[200px] text-right">Total Gaji BGN (Rp)</th>
                                <th class="px-3.5 py-3 w-24 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="(r, idx) in filteredRows"
                                :key="r.petugas.id"
                                :class="[
                                    'transition-colors group',
                                    r.selected ? 'bg-white hover:bg-emerald-50/20' : 'bg-slate-50/40 opacity-70 hover:opacity-100'
                                ]"
                            >
                                <!-- Checkbox -->
                                <td class="px-3 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        v-model="r.selected"
                                        class="rounded text-primary focus:ring-primary border-slate-300 cursor-pointer"
                                    />
                                </td>

                                <!-- No -->
                                <td class="px-3.5 py-3 text-center text-slate-400 font-medium text-[11px]">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Personil & Divisi -->
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-2.5">
                                        <div
                                            :class="[
                                                'h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5',
                                                r.petugas.jenis_kelamin === 'L'
                                                    ? 'bg-blue-50 text-blue-600 border border-blue-200'
                                                    : 'bg-rose-50 text-rose-600 border border-rose-200'
                                            ]"
                                        >
                                            {{ r.petugas.nama ? r.petugas.nama.charAt(0).toUpperCase() : 'P' }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 leading-snug truncate">
                                                {{ r.petugas.nama }}
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                <span class="text-[10px] px-1.5 py-0.2 rounded font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                    {{ r.petugas.jabatan }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 font-mono">
                                                    {{ r.petugas.nik }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Rek. Tujuan (16) -->
                                <td class="px-4 py-3">
                                    <div v-if="r.petugas.nomor_rekening" class="space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ r.petugas.jenis_bank || 'BNI' }}
                                            </span>
                                            <span class="font-mono font-bold text-slate-900 text-xs">
                                                {{ r.petugas.nomor_rekening }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="copyRowRekening(r.petugas.id, r.petugas.nomor_rekening)"
                                                class="p-1 rounded text-slate-400 hover:text-slate-700 transition-colors"
                                                title="Salin Rekening"
                                            >
                                                <Check v-if="copiedRekRowId === r.petugas.id" class="h-3 w-3 text-emerald-600" />
                                                <Copy v-else class="h-3 w-3" />
                                            </button>
                                        </div>
                                        <p v-if="r.petugas.email" class="text-[10px] text-slate-400 truncate max-w-[180px]">
                                            {{ r.petugas.email }}
                                        </p>
                                    </div>
                                    <div v-else class="flex items-center gap-1 text-[11px] text-rose-600 font-medium">
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        <span>Belum ada rekening</span>
                                    </div>
                                </td>

                                <!-- Kehadiran & Mandays -->
                                <td class="px-4 py-3">
                                    <div v-if="modePerhitungan === 'presensi'" class="space-y-0.5 text-xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-emerald-700 text-sm">
                                                {{ r.mandaysInfo.mandays }}
                                            </span>
                                            <span class="text-slate-500 font-medium text-[11px]">mandays</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 flex items-center gap-1.5">
                                            <span>H: {{ r.mandaysInfo.hadir }}</span>
                                            <span>•</span>
                                            <span>½: {{ r.mandaysInfo.setengah }}</span>
                                            <span>•</span>
                                            <span>S/I: {{ r.mandaysInfo.sakit + r.mandaysInfo.izin }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-slate-600">
                                        <span class="font-semibold text-teal-800">
                                            Penuh {{ rentangHariCount }} Hari Kerja
                                        </span>
                                    </div>
                                </td>

                                <!-- Tarif Gaji BGN -->
                                <td class="px-4 py-3 text-right">
                                    <span class="font-mono font-bold text-slate-800 text-xs">
                                        {{ formatRupiah(r.rateHarianBgn) }}
                                    </span>
                                    <p v-if="r.bonusHarianMitra > 0" class="text-[9.5px] text-slate-400 font-medium">
                                        +{{ formatRibuan(r.bonusHarianMitra) }} mitra
                                    </p>
                                </td>

                                <!-- Amount (Input Editable) -->
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <div class="relative w-36">
                                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400">Rp</span>
                                            <input
                                                type="text"
                                                :value="formatRibuan(r.amount)"
                                                @input="e => handleAmountInput(r, e.target.value)"
                                                :class="[
                                                    'w-full pl-8 pr-2 py-1 rounded-lg border text-xs font-mono font-bold text-right focus:ring-1 focus:ring-primary focus:border-primary',
                                                    r.isCustom ? 'border-amber-400 bg-amber-50/20 text-amber-900' : 'border-slate-300 text-slate-900'
                                                ]"
                                            />
                                        </div>
                                        <button
                                            v-if="r.isCustom"
                                            type="button"
                                            @click="resetRowAmount(r)"
                                            class="p-1 rounded text-slate-400 hover:text-amber-700 transition-colors"
                                            title="Kembalikan ke nominal otomatis"
                                        >
                                            <RefreshCw class="h-3 w-3" />
                                        </button>
                                    </div>
                                    <p v-if="r.isCustom" class="text-[9.5px] text-amber-600 font-semibold mt-0.5">
                                        *Nominal diedit manual
                                    </p>
                                </td>

                                <!-- Status -->
                                <td class="px-3.5 py-3 text-center">
                                    <span
                                        v-if="!r.amount || r.amount <= 0"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 whitespace-nowrap"
                                        title="Total gaji Rp 0 (tidak ada presensi/mandays)"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Tidak Valid
                                    </span>
                                    <span
                                        v-else-if="!r.petugas.nomor_rekening || r.petugas.nomor_rekening.trim().length < 5"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap"
                                        title="Nomor rekening BNI belum terisi"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        No Rekening
                                    </span>
                                    <span
                                        v-else-if="r.petugas.status !== 'Aktif'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 whitespace-nowrap"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap"
                                        title="Siap ditransfer via BNI Direct"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Siap
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="filteredRows.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                                    <p class="font-medium text-sm text-slate-600">
                                        Tidak ada petugas yang cocok dengan filter pencarian
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Ubah kata kunci pencarian atau reset filter.
                                    </p>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Table Footer Total -->
                        <tfoot class="bg-slate-50 border-t-2 border-slate-300 font-bold text-xs text-slate-800">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-left">
                                    TOTAL PAYROLL TERPILIH ({{ totalSelectedCount }} PERSONIL)
                                </td>
                                <td colspan="3" class="px-4 py-3 text-right text-slate-500 font-normal">
                                    Total Akumulasi Siap Debet:
                                </td>
                                <td class="px-4 py-3 text-right font-black text-emerald-800 text-sm font-mono">
                                    {{ formatRupiah(totalPayrollAmount) }}
                                </td>
                                <td class="px-3.5 py-3 text-center">
                                    <span class="text-[10.5px] font-bold text-emerald-700">
                                        {{ readyBniCount }} Valid
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- ─── Modal Preview CSV BNI Direct ────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="isPreviewModalOpen"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <div
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="closePreviewModal"
                ></div>

                <div
                    class="relative z-10 w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-slate-300 my-8 overflow-hidden animate-in zoom-in-95 duration-150"
                >
                    <!-- Header Modal -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-emerald-100 text-emerald-800">
                                <FileSpreadsheet class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">
                                    Pratinjau Format File CSV BNI Direct
                                </h3>
                                <p class="text-xs text-slate-500 font-mono">
                                    {{ previewFilename }} • {{ totalSelectedCount }} Baris Transaksi
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closePreviewModal"
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 cursor-pointer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Body Modal: Raw Code View -->
                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5">
                            <Info class="h-4 w-4 text-amber-700 shrink-0 mt-0.5" />
                            <div class="space-y-1">
                                <p class="font-bold">Format Resmi Batch Transfer BNI Direct (Inhouse):</p>
                                <p class="text-[11px] text-amber-800 leading-relaxed">
                                    • Baris 1: Timestamp & Jumlah Baris Keseluruhan<br />
                                    • Baris 2: Kode 'P', Tanggal Transaksi, Rekening Debet, Total Record, Total Nominal<br />
                                    • Baris 3 dst: Rekening Tujuan, Nama Penerima, Nominal Gaji, Remark, dan Flag Notifikasi.
                                </p>
                            </div>
                        </div>

                        <!-- Monospace Code Box -->
                        <div class="relative rounded-xl bg-slate-950 p-4 text-emerald-400 font-mono text-[11px] overflow-x-auto border border-slate-800 shadow-inner">
                            <pre class="whitespace-pre leading-relaxed select-all">{{ previewCsvText }}</pre>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-between px-6 py-4 border-t border-slate-200 bg-slate-50">
                        <span class="text-xs text-slate-500 font-mono">
                            Total: <b>{{ totalSelectedCount }} records</b> • <b>{{ formatRupiah(totalPayrollAmount) }}</b>
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="copyPreviewToClipboard"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition-colors cursor-pointer shadow-2xs"
                            >
                                <Check v-if="isCopied" class="h-4 w-4 text-emerald-600" />
                                <Copy v-else class="h-4 w-4" />
                                <span>{{ isCopied ? "Tersalin!" : "Salin Semua Teks" }}</span>
                            </button>
                            <button
                                type="button"
                                @click="() => { closePreviewModal(); triggerDownloadCsv(); }"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors cursor-pointer shadow-xs"
                            >
                                <Download class="h-4 w-4" />
                                <span>Download File CSV</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
