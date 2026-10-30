<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";
import ExcelJS from "exceljs";
import {
    CalendarRange,
    Users,
    Search,
    CheckCircle2,
    Clock,
    Calendar,
    Download,
    X,
    AlertCircle,
    Info,
    RotateCcw,
    Layers,
    FileSpreadsheet,
    Save,
    Check,
    Filter,
    ChevronDown,
    Building2,
    Sparkles,
    ArrowRight,
    RefreshCw,
    UserCheck,
    ArrowLeftRight,
    HelpCircle,
    Sliders,
    XCircle,
    CheckCheck,
    CalendarDays,
    ShieldCheck,
} from "lucide-vue-next";

// ─── Props dari Backend ────────────────────────────────────────────────────────
const props = defineProps({
    petugas: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    daftarJabatan: { type: Array, default: () => [] },
    periodes: { type: Array, default: () => [] },
    unitSppg: { type: Object, default: null },
    initialMode: { type: String, default: "bulanan" },
    initialTanggalMulai: { type: String, default: "" },
    initialTanggalSelesai: { type: String, default: "" },
    initialPeriodeId: { type: [String, Number], default: "all" },
    initialShiftingMap: { type: Object, default: () => ({}) },
});

const page = usePage();

// ─── Tanggal Hari Ini ──────────────────────────────────────────────────────────
const todayStr = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
});

function normalizeMode(mode) {
    if (!mode) return "";
    const m = String(mode).toLowerCase();
    if (m === "hari_ini" || m === "today") return "hari_ini";
    if (m === "periode" || m === "periodik" || m === "period") return "periode";
    if (m === "rentang" || m === "bulanan" || m === "range" || m === "custom") return "rentang";
    return "";
}

function getInitialDateState() {
    const today = todayStr.value;
    const sorted = [...(props.periodes || [])].sort((a, b) => {
        const noA = Number(a.nomor_periode) || 0;
        const noB = Number(b.nomor_periode) || 0;
        if (noB !== noA) return noB - noA;
        return String(b.tanggal_mulai || "").localeCompare(String(a.tanggal_mulai || ""));
    });
    const latest = sorted[0];

    let defaultRes = null;
    if (latest && latest.tanggal_mulai && latest.tanggal_selesai) {
        if (today >= latest.tanggal_mulai && today <= latest.tanggal_selesai) {
            defaultRes = {
                start: latest.tanggal_mulai,
                end: latest.tanggal_selesai,
                mode: "periode",
                periodeId: String(latest.id),
            };
        }
    }
    if (!defaultRes) {
        // default 28 hari dari hari ini
        const start = today;
        const base = new Date();
        base.setDate(base.getDate() + 27);
        const y = base.getFullYear();
        const m = String(base.getMonth() + 1).padStart(2, "0");
        const d = String(base.getDate()).padStart(2, "0");
        const end = `${y}-${m}-${d}`;
        defaultRes = {
            start,
            end,
            mode: "rentang",
            periodeId: "all",
        };
    }

    if (props.initialTanggalMulai && props.initialTanggalSelesai) {
        const norm = normalizeMode(props.initialMode);
        return {
            start: props.initialTanggalMulai,
            end: props.initialTanggalSelesai,
            mode: norm || defaultRes.mode,
            periodeId: props.initialPeriodeId !== undefined ? String(props.initialPeriodeId) : defaultRes.periodeId,
        };
    }

    return defaultRes;
}

// ─── Filter State ─────────────────────────────────────────────────────────────
const initialDateState = getInitialDateState();
const modeSkala = ref(initialDateState.mode);
const tanggalMulai = ref(initialDateState.start);
const tanggalSelesai = ref(initialDateState.end);
const selectedPeriodeId = ref(initialDateState.periodeId);
const selectedJabatanFilter = ref("all");
const selectedStatusFilter = ref("all");
const searchQuery = ref("");

function onDateFilterChange(filter) {
    if (!filter) return;
    tanggalMulai.value = filter.start;
    tanggalSelesai.value = filter.end;
    modeSkala.value = filter.mode;
    selectedPeriodeId.value = filter.periodeId;

    router.get(
        route("petugas.shifting"),
        {
            tanggal_mulai: filter.start,
            tanggal_selesai: filter.end,
            mode: filter.mode,
            periode_id: filter.periodeId,
        },
        { preserveState: true, preserveScroll: true }
    );
}

// ─── Kolom Tanggal Matriks ────────────────────────────────────────────────────
const dateColumns = computed(() => {
    if (!tanggalMulai.value || !tanggalSelesai.value) return [];
    const list = [];
    const current = new Date(tanggalMulai.value + "T00:00:00");
    const end = new Date(tanggalSelesai.value + "T00:00:00");
    const dayNames = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

    let idx = 1;
    while (current <= end) {
        const y = current.getFullYear();
        const m = String(current.getMonth() + 1).padStart(2, "0");
        const d = String(current.getDate()).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const dayOfWeek = current.getDay();

        list.push({
            dateStr,
            dayNum: d,
            dayName: dayNames[dayOfWeek],
            dayOfWeek,
            isSunday: dayOfWeek === 0,
            isSaturday: dayOfWeek === 6,
            isWeekend: dayOfWeek === 0 || dayOfWeek === 6,
            isToday: dateStr === todayStr.value,
            index: idx++,
        });
        current.setDate(current.getDate() + 1);
    }
    return list;
});

// ─── Shifting Matrix State ───────────────────────────────────────────────────
// matrix: { [petugas_id]: { [tanggal]: 'H' | 'L' | '-' } }
const matrixShifting = ref({});
const isDirty = ref(false);

function initializeMatrix() {
    const map = {};
    (props.petugas || []).forEach((p) => {
        map[p.id] = {};
        dateColumns.value.forEach((col) => {
            const existing = props.initialShiftingMap?.[p.id]?.[col.dateStr];
            if (existing) {
                map[p.id][col.dateStr] = existing;
            } else {
                map[p.id][col.dateStr] = ""; // belum diatur
            }
        });
    });
    matrixShifting.value = map;
    isDirty.value = false;
}

watch(
    () => [props.petugas, props.initialShiftingMap, dateColumns.value],
    () => {
        initializeMatrix();
    },
    { immediate: true, deep: true }
);

// ─── Toast Notification ──────────────────────────────────────────────────────
const toastMessage = ref("");
const isToastVisible = ref(false);
let toastTimer = null;

function showToast(msg) {
    toastMessage.value = msg;
    isToastVisible.value = true;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        isToastVisible.value = false;
    }, 3500);
}

// ─── Cell Toggle Interaction ──────────────────────────────────────────────────
// Toggle cycle: '' -> 'H' (Masuk) -> 'L' (Libur) -> ''
function toggleCellShift(petugasId, dateStr) {
    if (!matrixShifting.value[petugasId]) {
        matrixShifting.value[petugasId] = {};
    }
    const cur = matrixShifting.value[petugasId][dateStr] || "";
    let next = "";
    if (cur === "") {
        next = "H";
    } else if (cur === "H" || cur === "H2") {
        next = "L";
    } else {
        next = "";
    }

    matrixShifting.value[petugasId][dateStr] = next;
    isDirty.value = true;
}

function setCellShiftDirect(petugasId, dateStr, val) {
    if (!matrixShifting.value[petugasId]) {
        matrixShifting.value[petugasId] = {};
    }
    matrixShifting.value[petugasId][dateStr] = val;
    isDirty.value = true;
}

// ─── Bulk Column Date Header Actions (Atur Serentak Bergantian: H ➜ L ➜ Kosong) ───
function toggleKolomStatus(dateStr) {
    const list = filteredPetugas.value;
    if (list.length === 0) return;

    // Ambil status personil pertama sebagai acuan
    const firstStatus = matrixShifting.value[list[0].id]?.[dateStr] || "";

    // Cek apakah seluruh personil saat ini memiliki status seragam di tanggal ini
    const isAllSame = list.every(
        (p) => (matrixShifting.value[p.id]?.[dateStr] || "") === firstStatus
    );

    // Tentukan status berikutnya dalam siklus: H ➜ L ➜ Kosong ➜ H
    let nextSt = "H";
    if (isAllSame) {
        if (firstStatus === "") nextSt = "H";
        else if (firstStatus === "H" || firstStatus === "H2") nextSt = "L";
        else if (firstStatus === "L") nextSt = ""; // Kembali kosong
        else nextSt = "H";
    } else {
        // Jika status masih berbeda-beda (atau campuran), samakan ke "H" terlebih dahulu
        nextSt = "H";
    }

    list.forEach((p) => {
        if (!matrixShifting.value[p.id]) {
            matrixShifting.value[p.id] = {};
        }
        matrixShifting.value[p.id][dateStr] = nextSt;
    });

    isDirty.value = true;

    const col = dateColumns.value.find((c) => c.dateStr === dateStr);
    const labelDate = col ? `${col.dayName}, Tgl ${col.dayNum}` : dateStr;
    const labelDesc = nextSt === "H" ? "Shift Masuk (H)" : nextSt === "L" ? "Shift Libur (L)" : "Kosong (·)";
    showToast(
        nextSt !== ""
            ? `${labelDate}: Seluruh personil (${list.length} orang) diset ${labelDesc}`
            : `${labelDate}: Seluruh status kolom berhasil dikosongkan (·)`
    );
}

// ─── Bulk Row Staff Actions (Atur Serentak Satu Baris: H ➜ L ➜ Kosong) ───────────
function toggleBarisStatus(petugasId) {
    const dates = dateColumns.value;
    if (dates.length === 0) return;
    if (!matrixShifting.value[petugasId]) {
        matrixShifting.value[petugasId] = {};
    }

    // Ambil acuan dari hari kerja pertama (bukan weekend jika ada), atau hari pertama
    const workDays = dates.filter((d) => !d.isWeekend);
    const sampleDay = workDays.length > 0 ? workDays[0] : dates[0];
    const curStatus = matrixShifting.value[petugasId][sampleDay.dateStr] || "";

    // Cek apakah seragam di semua hari kerja
    const checkDays = workDays.length > 0 ? workDays : dates;
    const isAllSame = checkDays.every(
        (d) => (matrixShifting.value[petugasId][d.dateStr] || "") === curStatus
    );

    // Siklus: H (Masuk, Sabtu & Minggu Libur) ➜ L (Semua Libur) ➜ Kosong (·) ➜ H
    let nextSt = "H";
    if (isAllSame) {
        if (curStatus === "") nextSt = "H";
        else if (curStatus === "H" || curStatus === "H2") nextSt = "L";
        else if (curStatus === "L") nextSt = "";
        else nextSt = "H";
    } else {
        nextSt = "H";
    }

    dates.forEach((col) => {
        if (nextSt === "H") {
            // Jika H: hari kerja diset H, akhir pekan (Sabtu & Minggu) diset L
            matrixShifting.value[petugasId][col.dateStr] = col.isWeekend ? "L" : "H";
        } else {
            matrixShifting.value[petugasId][col.dateStr] = nextSt;
        }
    });

    isDirty.value = true;
    const p = props.petugas.find((x) => x.id === petugasId);
    const pNama = p?.nama || "Petugas";
    const labelDesc =
        nextSt === "H"
            ? "Shift Masuk (H) [Sabtu & Minggu Libur]"
            : nextSt === "L"
            ? "Shift Libur (L)"
            : "Kosong (·)";

    showToast(
        nextSt !== ""
            ? `${pNama}: Seluruh hari pada baris ini diset ${labelDesc}`
            : `${pNama}: Seluruh hari pada baris ini berhasil dikosongkan (·)`
    );
}

// ─── Filtered Petugas List ────────────────────────────────────────────────────
const filteredPetugas = computed(() => {
    return props.petugas.filter((p) => {
        if (selectedStatusFilter.value !== "all") {
            if ((p.status || "Aktif") !== selectedStatusFilter.value) {
                return false;
            }
        }
        if (selectedJabatanFilter.value !== "all") {
            if ((p.jabatan || "") !== selectedJabatanFilter.value) {
                return false;
            }
        }
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim();
            const matchNama = (p.nama || "").toLowerCase().includes(q);
            const matchNik = (p.nik || "").toLowerCase().includes(q);
            const matchJabatan = (p.jabatan || "").toLowerCase().includes(q);
            if (!matchNama && !matchNik && !matchJabatan) {
                return false;
            }
        }
        return true;
    });
});

// ─── Per-Petugas Totals ───────────────────────────────────────────────────────
function getPetugasShiftStats(petugasId) {
    const dates = dateColumns.value;
    let hadir = 0;
    let libur = 0;
    let totalScheduled = 0;

    dates.forEach((d) => {
        const val = matrixShifting.value[petugasId]?.[d.dateStr] || "";
        if (val === "H" || val === "H2") {
            hadir += val === "H2" ? 0.5 : 1;
            totalScheduled++;
        } else if (val === "L") {
            libur++;
            totalScheduled++;
        } else if (val) {
            // I, S, TK
            totalScheduled++;
        }
    });

    const totalDays = dates.length || 1;
    const rasioKerja = totalScheduled > 0 ? Math.round((hadir / totalScheduled) * 100) : 0;

    return {
        hadir,
        libur,
        totalScheduled,
        totalDays,
        rasioKerja,
    };
}

// ─── Per-Date Totals (Footer Summary) ─────────────────────────────────────────
const perDateSummary = computed(() => {
    const res = {};
    const list = filteredPetugas.value;

    dateColumns.value.forEach((col) => {
        let masukCount = 0;
        let liburCount = 0;
        let unassignedCount = 0;

        list.forEach((p) => {
            const st = matrixShifting.value[p.id]?.[col.dateStr] || "";
            if (st === "H") masukCount++;
            else if (st === "H2") masukCount += 0.5;
            else if (st === "L") liburCount++;
            else unassignedCount++;
        });

        const totalActive = list.length;
        const rasioMasuk = totalActive > 0 ? Math.round((masukCount / totalActive) * 100) : 0;

        res[col.dateStr] = {
            masukCount,
            liburCount,
            unassignedCount,
            totalActive,
            rasioMasuk,
        };
    });

    return res;
});

// ─── Global KPI Metrics ───────────────────────────────────────────────────────
const kpiMetrics = computed(() => {
    const totalList = filteredPetugas.value.length;
    const dates = dateColumns.value;
    if (dates.length === 0 || totalList === 0) {
        return {
            totalPersonil: totalList,
            rata2MasukPerHari: 0,
            rata2LiburPerHari: 0,
            keseimbanganShift: "0%",
            totalHari: dates.length,
        };
    }

    let sumMasuk = 0;
    let sumLibur = 0;
    dates.forEach((d) => {
        const s = perDateSummary.value[d.dateStr];
        if (s) {
            sumMasuk += s.masukCount;
            sumLibur += s.liburCount;
        }
    });

    const avgMasuk = Math.round((sumMasuk / dates.length) * 10) / 10;
    const avgLibur = Math.round((sumLibur / dates.length) * 10) / 10;
    const rasio = totalList > 0 ? Math.round((avgMasuk / totalList) * 100) : 0;

    return {
        totalPersonil: totalList,
        rata2MasukPerHari: avgMasuk,
        rata2LiburPerHari: avgLibur,
        keseimbanganShift: `${rasio}% Masuk`,
        totalHari: dates.length,
    };
});

// ─── Modal Generator Pola Shifting ───────────────────────────────────────────
const isGeneratorModalOpen = ref(false);
const generatorPattern = ref("divisi_rotasi"); // 'divisi_rotasi' | 'rotasi_2_tim' | 'senin_jumat'
const generatorBlokHari = ref(1); // 1 | 2 | 3 | 5
const generatorDivisiRatio = ref("50"); // '50' | '67' | '1_orang' | 'kustom'
const generatorTargetDivisi = ref("all");
const generatorStartType = ref("tim_a_first"); // 'tim_a_first' | 'tim_b_first'
const generatorTeamMethod = ref("ganjil_genap"); // 'ganjil_genap' | 'separuh'
const generatorExcludeWeekend = ref(true); // default: Sabtu dan Minggu Libur
const generatorSingleStaffFullWork = ref(true); // Divisi dengan 1 personil (Kepala/Gizi) masuk penuh
const generatorCustomDays = ref(10);

const totalWorkDaysCount = computed(() => {
    if (generatorExcludeWeekend.value) {
        return dateColumns.value.filter((d) => !d.isWeekend).length;
    }
    return dateColumns.value.length;
});

const targetHadir50 = computed(() => {
    return Math.floor(totalWorkDaysCount.value / 2);
});

const targetHadir67 = computed(() => {
    return Math.round((totalWorkDaysCount.value * 2) / 3);
});

const simulationPreviewText = computed(() => {
    if (generatorPattern.value !== "divisi_rotasi") return "";
    const b = generatorBlokHari.value;
    const bLabel = b === 1 ? "1 hari" : `${b} hari berturut-turut`;
    if (generatorDivisiRatio.value === "1_orang") {
        return `Giliran 1 personil per ${bLabel}: Hari 1-${b}: Personil A ➜ Hari ${b + 1}-${b * 2}: Personil B ➜ Hari ${b * 2 + 1}-${b * 3}: Personil C... (Saling bergantian)`;
    } else if (generatorDivisiRatio.value === "50") {
        return `Giliran 50% personil per ${bLabel}: Hari 1-${b}: Regu A ➜ Hari ${b + 1}-${b * 2}: Regu B ➜ Hari ${b * 2 + 1}-${b * 3}: Regu A... (Saling bergantian)`;
    } else if (generatorDivisiRatio.value === "67") {
        return `Giliran 67% personil per ${bLabel}: 2 hari masuk & 1 hari libur berputar antar personil divisi.`;
    } else {
        return `Giliran kustom ${generatorCustomDays.value} hari hadir: Tersebar merata per ${bLabel} antar personil divisi.`;
    }
});

watch(
    () => totalWorkDaysCount.value,
    (newVal) => {
        if (newVal > 0 && (!generatorCustomDays.value || generatorCustomDays.value > newVal)) {
            generatorCustomDays.value = Math.max(1, Math.floor(newVal / 2));
        }
    },
    { immediate: true }
);

function setCustomPercent(pct) {
    if (totalWorkDaysCount.value > 0) {
        generatorCustomDays.value = Math.max(1, Math.round((totalWorkDaysCount.value * pct) / 100));
    }
}

// ─── Personil Wajib Selalu Hadir (Bebas Shifting) ──────────────────────────
const defaultRoleCategories = [
    { name: "Kepala SPPG", keywords: ["kepala sppg"] },
    { name: "Pengawas Gizi", keywords: ["pengawas gizi", "ahli gizi", "gizi"] },
    { name: "Pengawas Keuangan", keywords: ["pengawas keuangan", "keuangan", "akuntan"] },
    { name: "Koordinator Lapangan", keywords: ["koordinator lapangan"] },
    { name: "Koordinator", keywords: ["koordinator"] },
    { name: "Admin", keywords: ["admin"] },
    { name: "Kebersihan", keywords: ["kebersihan", "cleaning"] },
    { name: "Keamanan", keywords: ["keamanan", "security", "satpam"] },
    { name: "Chef", keywords: ["chef"] },
    { name: "Pengemudi", keywords: ["pengemudi", "driver", "sopir"] },
];

function isDefaultWajibHadir(jabatan) {
    if (!jabatan) return false;
    const j = jabatan.toLowerCase();
    return defaultRoleCategories.some((cat) =>
        cat.keywords.some((kw) => j.includes(kw))
    );
}

const wajibHadirStaffIds = ref([]);
const isWajibHadirListExpanded = ref(false);

function resetWajibHadirToDefault() {
    const list = [];
    (props.petugas || []).forEach((p) => {
        if (p.status !== "Nonaktif" && isDefaultWajibHadir(p.jabatan)) {
            list.push(p.id);
        }
    });
    wajibHadirStaffIds.value = list;
}

watch(
    () => props.petugas,
    () => {
        if (wajibHadirStaffIds.value.length === 0) {
            resetWajibHadirToDefault();
        }
    },
    { immediate: true, deep: true }
);

function getStaffByKeywords(keywords) {
    return (props.petugas || []).filter((p) => {
        if (p.status === "Nonaktif") return false;
        const j = (p.jabatan || "").toLowerCase();
        return keywords.some((kw) => j.includes(kw));
    });
}

function isRoleCategoryFullySelected(keywords) {
    const matching = getStaffByKeywords(keywords);
    if (matching.length === 0) return false;
    return matching.every((p) => wajibHadirStaffIds.value.includes(p.id));
}

function isRoleCategoryPartiallySelected(keywords) {
    const matching = getStaffByKeywords(keywords);
    if (matching.length === 0) return false;
    const count = matching.filter((p) => wajibHadirStaffIds.value.includes(p.id)).length;
    return count > 0 && count < matching.length;
}

function toggleRoleCategory(keywords) {
    const matching = getStaffByKeywords(keywords);
    if (matching.length === 0) return;
    const allSelected = matching.every((p) => wajibHadirStaffIds.value.includes(p.id));
    if (allSelected) {
        wajibHadirStaffIds.value = wajibHadirStaffIds.value.filter(
            (id) => !matching.some((m) => m.id === id)
        );
    } else {
        const toAdd = matching
            .map((m) => m.id)
            .filter((id) => !wajibHadirStaffIds.value.includes(id));
        wajibHadirStaffIds.value = [...wajibHadirStaffIds.value, ...toAdd];
    }
}

const selectedWajibHadirCount = computed(() => wajibHadirStaffIds.value.length);

function openGeneratorModal() {
    isGeneratorModalOpen.value = true;
}

function closeGeneratorModal() {
    isGeneratorModalOpen.value = false;
}

function applyGeneratorPattern() {
    const allDates = dateColumns.value;
    const workDates = generatorExcludeWeekend.value
        ? allDates.filter((d) => !d.isWeekend)
        : allDates;

    const M = workDates.length;
    if (M === 0) {
        showToast("Rentang tanggal tidak memiliki hari operasional kerja.");
        return;
    }

    const activeStaff = props.petugas.filter((p) => p.status !== "Nonaktif");

    // 1. Terapkan Masuk Penuh (100% Hari Kerja) untuk Personil Wajib Selalu Hadir (Bebas Shifting)
    const wajibSet = new Set(wajibHadirStaffIds.value);
    let wajibCount = 0;
    activeStaff.forEach((p) => {
        if (wajibSet.has(p.id)) {
            wajibCount++;
            if (!matrixShifting.value[p.id]) matrixShifting.value[p.id] = {};
            allDates.forEach((col) => {
                if (generatorExcludeWeekend.value && col.isWeekend) {
                    matrixShifting.value[p.id][col.dateStr] = "L";
                } else {
                    matrixShifting.value[p.id][col.dateStr] = "H";
                }
            });
        }
    });

    // 2. Personil sisanya yang BUKAN Wajib Hadir akan dikenakan pola rotasi shifting
    const shiftingStaff = activeStaff.filter((p) => !wajibSet.has(p.id));

    // Tentukan divisi yang akan diproses untuk personil shifting
    let targetDivisiList = [];
    if (generatorTargetDivisi.value === "all") {
        targetDivisiList = [...new Set(shiftingStaff.map((p) => p.jabatan || "Umum"))];
    } else {
        targetDivisiList = [generatorTargetDivisi.value];
    }

    // Inisialisasi awal seluruh tanggal personil shifting:
    const affectedStaffIds = new Set();
    shiftingStaff.forEach((p) => {
        const jbt = p.jabatan || "Umum";
        if (targetDivisiList.includes(jbt)) {
            affectedStaffIds.add(p.id);
            if (!matrixShifting.value[p.id]) matrixShifting.value[p.id] = {};
            allDates.forEach((col) => {
                if (generatorExcludeWeekend.value && col.isWeekend) {
                    matrixShifting.value[p.id][col.dateStr] = "L";
                } else {
                    matrixShifting.value[p.id][col.dateStr] = "L"; // default libur, nanti diisi H oleh algoritma
                }
            });
        }
    });

    if (generatorPattern.value === "senin_jumat") {
        shiftingStaff.forEach((p) => {
            if (affectedStaffIds.has(p.id)) {
                workDates.forEach((col) => {
                    matrixShifting.value[p.id][col.dateStr] = "H";
                });
            }
        });
    } else if (generatorPattern.value === "rotasi_2_tim") {
        const targetList = shiftingStaff.filter((p) => affectedStaffIds.has(p.id));
        const N = targetList.length;
        if (N > 0) {
            const K = Math.floor(M / 2);
            let ordered = [...targetList];
            const teamA = [];
            const teamB = [];
            targetList.forEach((p, idx) => {
                if (idx % 2 === 0) teamA.push(p);
                else teamB.push(p);
            });
            if (generatorStartType.value === "tim_b_first") ordered = [...teamB, ...teamA];
            else ordered = [...teamA, ...teamB];

            let ptr = 0;
            workDates.forEach((col, j) => {
                const prevSum = Math.floor((j * N * K) / M);
                const nextSum = Math.floor(((j + 1) * N * K) / M);
                const countForDay = nextSum - prevSum;
                for (let c = 0; c < countForDay; c++) {
                    const s = ordered[(ptr + c) % N];
                    matrixShifting.value[s.id][col.dateStr] = "H";
                }
                ptr = (ptr + countForDay) % N;
            });
        }
    } else if (generatorPattern.value === "divisi_rotasi") {
        // ─── SHIFTING BERGANTIAN DI MASING-MASING DIVISI / TIM ───
        const blokB = Math.max(1, parseInt(generatorBlokHari.value, 10) || 1);

        targetDivisiList.forEach((divisiName) => {
            const members = shiftingStaff.filter((p) => (p.jabatan || "Umum") === divisiName);
            const n = members.length;
            if (n === 0) return;

            if (n === 1 && generatorSingleStaffFullWork.value) {
                const singleP = members[0];
                workDates.forEach((col) => {
                    matrixShifting.value[singleP.id][col.dateStr] = "H";
                });
                return;
            }

            let K = 0;
            if (generatorDivisiRatio.value === "50") {
                K = Math.floor(M / 2);
            } else if (generatorDivisiRatio.value === "67") {
                K = Math.round((M * 2) / 3);
            } else if (generatorDivisiRatio.value === "1_orang") {
                K = Math.max(1, Math.floor(M / n));
            } else if (generatorDivisiRatio.value === "kustom") {
                const val = parseInt(generatorCustomDays.value, 10) || Math.floor(M / 2);
                K = Math.max(1, Math.min(M, val));
            }

            const assignedCount = {};
            members.forEach((m) => (assignedCount[m.id] = 0));

            let personTurn = 0;
            workDates.forEach((col, j) => {
                const prevSum = Math.floor((j * n * K) / M);
                const nextSum = Math.floor(((j + 1) * n * K) / M);
                const countForDay = Math.min(n, Math.max(0, nextSum - prevSum));

                if (j > 0 && Math.floor(j / blokB) !== Math.floor((j - 1) / blokB)) {
                    personTurn = (personTurn + Math.max(1, Math.round(countForDay))) % n;
                }

                let picked = 0;
                for (let step = 0; step < n && picked < countForDay; step++) {
                    const candidateIdx = (personTurn + step) % n;
                    const candidate = members[candidateIdx];
                    if (assignedCount[candidate.id] < K) {
                        matrixShifting.value[candidate.id][col.dateStr] = "H";
                        assignedCount[candidate.id]++;
                        picked++;
                    }
                }

                if (picked < countForDay) {
                    for (let step = 0; step < n && picked < countForDay; step++) {
                        const candidate = members[step];
                        if (
                            matrixShifting.value[candidate.id][col.dateStr] !== "H" &&
                            assignedCount[candidate.id] < K
                        ) {
                            matrixShifting.value[candidate.id][col.dateStr] = "H";
                            assignedCount[candidate.id]++;
                            picked++;
                        }
                    }
                }
            });

            members.forEach((m) => {
                while (assignedCount[m.id] < K) {
                    const openDay = workDates.find(
                        (col) => matrixShifting.value[m.id][col.dateStr] !== "H"
                    );
                    if (openDay) {
                        matrixShifting.value[m.id][openDay.dateStr] = "H";
                        assignedCount[m.id]++;
                    } else {
                        break;
                    }
                }
            });
        });
    }

    isDirty.value = true;
    closeGeneratorModal();
    showToast(
        `Pola shifting berhasil diterapkan: ${wajibCount} personil Wajib Masuk Penuh, ${affectedStaffIds.size} personil rotasi bergantian!`
    );
}

// ─── Modal Tukar Shift (Swap Shift) ──────────────────────────────────────────
const isSwapModalOpen = ref(false);
const swapDate = ref(todayStr.value);
const swapPetugas1Id = ref("");
const swapPetugas2Id = ref("");

function openSwapModal(initialDate = null) {
    if (initialDate) {
        swapDate.value = initialDate;
    } else if (dateColumns.value.length > 0) {
        swapDate.value = dateColumns.value[0].dateStr;
    }
    swapPetugas1Id.value = "";
    swapPetugas2Id.value = "";
    isSwapModalOpen.value = true;
}

function closeSwapModal() {
    isSwapModalOpen.value = false;
}

function executeSwapShift() {
    if (!swapDate.value) {
        showToast("Pilih tanggal shift yang akan ditukar.");
        return;
    }
    if (!swapPetugas1Id.value || !swapPetugas2Id.value) {
        showToast("Pilih 2 petugas yang akan ditukar shift kerjanya.");
        return;
    }
    if (swapPetugas1Id.value === swapPetugas2Id.value) {
        showToast("Petugas 1 dan Petugas 2 tidak boleh orang yang sama.");
        return;
    }

    const p1Id = swapPetugas1Id.value;
    const p2Id = swapPetugas2Id.value;
    const d = swapDate.value;

    if (!matrixShifting.value[p1Id]) matrixShifting.value[p1Id] = {};
    if (!matrixShifting.value[p2Id]) matrixShifting.value[p2Id] = {};

    const st1 = matrixShifting.value[p1Id][d] || "";
    const st2 = matrixShifting.value[p2Id][d] || "";

    matrixShifting.value[p1Id][d] = st2;
    matrixShifting.value[p2Id][d] = st1;

    const p1 = props.petugas.find((x) => String(x.id) === String(p1Id));
    const p2 = props.petugas.find((x) => String(x.id) === String(p2Id));

    isDirty.value = true;
    closeSwapModal();
    showToast(`Shift tgl ${formatTanggalIndo(d)} antara ${p1?.nama || 'Petugas 1'} & ${p2?.nama || 'Petugas 2'} berhasil ditukar!`);
}

// ─── Quick Actions (Set Semua Masuk/Libur) ─────────────────────────────────────
function setAllIn() {
    const list = filteredPetugas.value;
    const dates = dateColumns.value;
    list.forEach((p) => {
        if (!matrixShifting.value[p.id]) matrixShifting.value[p.id] = {};
        dates.forEach((d) => {
            matrixShifting.value[p.id][d.dateStr] = "H";
        });
    });
    isDirty.value = true;
    showToast(`Seluruh personil diset Masuk (H) untuk rentang ini.`);
}

function setAllOff() {
    const list = filteredPetugas.value;
    const dates = dateColumns.value;
    list.forEach((p) => {
        if (!matrixShifting.value[p.id]) matrixShifting.value[p.id] = {};
        dates.forEach((d) => {
            matrixShifting.value[p.id][d.dateStr] = "L";
        });
    });
    isDirty.value = true;
    showToast(`Seluruh personil diset Libur (L) untuk rentang ini.`);
}

function resetSchedule() {
    if (!confirm("Apakah Anda yakin ingin mereset perubahan jadwal shifting ini kembali ke data tersimpan?")) {
        return;
    }
    initializeMatrix();
    showToast("Jadwal shifting dikembalikan ke data awal tersimpan.");
}

// ─── Simpan ke Backend ────────────────────────────────────────────────────────
const isSaving = ref(false);

function simpanShifting() {
    isSaving.value = true;

    // Siapkan payload
    const payload = {
        json_data: JSON.stringify(matrixShifting.value),
        mode: modeSkala.value,
        tanggal_mulai: tanggalMulai.value,
        tanggal_selesai: tanggalSelesai.value,
        periode_id: selectedPeriodeId.value,
    };

    router.post(route("petugas.shifting.simpan"), payload, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isDirty.value = false;
            showToast("Konfigurasi jadwal shifting berhasil disimpan ke database!");
        },
        onError: (err) => {
            console.error("Gagal simpan shifting:", err);
            showToast("Gagal menyimpan jadwal shifting: " + JSON.stringify(err));
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
}

// ─── Export Roster Shifting ke Excel ───────────────────────────────────────────
const isExporting = ref(false);

async function exportShiftingExcel() {
    isExporting.value = true;
    try {
        const workbook = new ExcelJS.Workbook();
        workbook.creator = "SIPEGE SPPG";
        workbook.created = new Date();

        const sheet = workbook.addWorksheet("Jadwal Shifting", {
            views: [{ showGridLines: true, state: "frozen", ySplit: 7, xSplit: 3 }],
        });

        // 1. Header Title
        sheet.mergeCells("A1:N1");
        const titleCell = sheet.getCell("A1");
        titleCell.value = `JADWAL SHIFTING HARI KERJA PERSONIL - ${props.unitSppg?.nama_unit || "SPPG BGN"}`;
        titleCell.font = { name: "Calibri", size: 14, bold: true, color: { argb: "FF0F172A" } };
        titleCell.alignment = { horizontal: "left", vertical: "middle" };

        sheet.mergeCells("A2:N2");
        const subCell = sheet.getCell("A2");
        subCell.value = `Rentang: ${formatTanggalIndo(tanggalMulai.value)} s/d ${formatTanggalIndo(tanggalSelesai.value)} (${dateColumns.value.length} Hari)`;
        subCell.font = { name: "Calibri", size: 11, italic: true, color: { argb: "FF475569" } };

        sheet.mergeCells("A3:N3");
        const noteCell = sheet.getCell("A3");
        noteCell.value = "Keterangan Kode: [H] = Shift Masuk / Kerja | [L] = Shift Libur / Off";
        noteCell.font = { name: "Calibri", size: 10, bold: true, color: { argb: "FF047857" } };

        sheet.addRow([]);

        // 2. Table Headers (Row 5: Day Name, Row 6: Day Num)
        const headerRow1 = ["No", "Nama Personil", "Jabatan / Divisi"];
        const headerRow2 = ["", "", ""];

        dateColumns.value.forEach((col) => {
            headerRow1.push(col.dayName);
            headerRow2.push(col.dayNum);
        });

        headerRow1.push("Total Masuk (H)", "Total Libur (L)", "Rasio Kerja (%)");
        headerRow2.push("", "", "");

        sheet.addRow(headerRow1);
        sheet.addRow(headerRow2);

        // Merge No, Nama, Jabatan vertically
        sheet.mergeCells("A5:A6");
        sheet.mergeCells("B5:B6");
        sheet.mergeCells("C5:C6");

        // Merge summary columns vertically
        const lastColIndex = 3 + dateColumns.value.length;
        sheet.mergeCells(5, lastColIndex + 1, 6, lastColIndex + 1);
        sheet.mergeCells(5, lastColIndex + 2, 6, lastColIndex + 2);
        sheet.mergeCells(5, lastColIndex + 3, 6, lastColIndex + 3);

        // Style Table Headers
        for (let r = 5; r <= 6; r++) {
            const row = sheet.getRow(r);
            row.height = 20;
            row.eachCell((cell, colNum) => {
                cell.font = { name: "Calibri", size: 10, bold: true, color: { argb: "FFFFFFFF" } };
                cell.alignment = { horizontal: "center", vertical: "middle" };
                cell.fill = {
                    type: "pattern",
                    pattern: "solid",
                    fgColor: { argb: "FF1E293B" }, // dark slate
                };
                cell.border = {
                    top: { style: "thin", color: { argb: "FF94A3B8" } },
                    left: { style: "thin", color: { argb: "FF94A3B8" } },
                    bottom: { style: "thin", color: { argb: "FF94A3B8" } },
                    right: { style: "thin", color: { argb: "FF94A3B8" } },
                };
            });
        }

        // Highlight weekend headers
        dateColumns.value.forEach((col, idx) => {
            const colNum = 4 + idx;
            if (col.isSunday) {
                const c1 = sheet.getCell(5, colNum);
                const c2 = sheet.getCell(6, colNum);
                c1.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFDC2626" } };
                c2.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFDC2626" } };
            }
        });

        // 3. Body Rows
        filteredPetugas.value.forEach((p, pIdx) => {
            const stats = getPetugasShiftStats(p.id);
            const rowData = [pIdx + 1, p.nama, p.jabatan || "-"];

            dateColumns.value.forEach((col) => {
                const st = matrixShifting.value[p.id]?.[col.dateStr] || "-";
                rowData.push(st);
            });

            rowData.push(stats.hadir, stats.libur, `${stats.rasioKerja}%`);
            const insertedRow = sheet.addRow(rowData);
            insertedRow.height = 20;

            insertedRow.eachCell((cell, colNum) => {
                cell.font = { name: "Calibri", size: 10 };
                cell.border = {
                    top: { style: "thin", color: { argb: "FFE2E8F0" } },
                    left: { style: "thin", color: { argb: "FFE2E8F0" } },
                    bottom: { style: "thin", color: { argb: "FFE2E8F0" } },
                    right: { style: "thin", color: { argb: "FFE2E8F0" } },
                };

                if (colNum === 1) {
                    cell.alignment = { horizontal: "center", vertical: "middle" };
                } else if (colNum === 2) {
                    cell.alignment = { horizontal: "left", vertical: "middle" };
                    cell.font = { name: "Calibri", size: 10, bold: true };
                } else if (colNum === 3) {
                    cell.alignment = { horizontal: "left", vertical: "middle" };
                } else if (colNum >= 4 && colNum <= 3 + dateColumns.value.length) {
                    cell.alignment = { horizontal: "center", vertical: "middle" };
                    const val = cell.value;
                    if (val === "H") {
                        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFD1FAE5" } }; // green light
                        cell.font = { name: "Calibri", size: 10, bold: true, color: { argb: "FF065F46" } };
                    } else if (val === "L") {
                        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFF1F5F9" } }; // slate light
                        cell.font = { name: "Calibri", size: 10, bold: true, color: { argb: "FF64748B" } };
                    }
                } else {
                    cell.alignment = { horizontal: "center", vertical: "middle" };
                    cell.font = { name: "Calibri", size: 10, bold: true };
                }
            });
        });

        // 4. Footer Summary Row
        const footerRowData = ["", "TOTAL BERTUGAS (MASUK)", ""];
        dateColumns.value.forEach((col) => {
            const s = perDateSummary.value[col.dateStr];
            footerRowData.push(s ? s.masukCount : 0);
        });
        footerRowData.push("", "", "");
        const footerRow = sheet.addRow(footerRowData);
        footerRow.height = 22;

        sheet.mergeCells(footerRow.number, 1, footerRow.number, 3);
        footerRow.eachCell((cell) => {
            cell.font = { name: "Calibri", size: 10, bold: true, color: { argb: "FF065F46" } };
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFECFDF5" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
            cell.border = {
                top: { style: "medium", color: { argb: "FF10B981" } },
                bottom: { style: "medium", color: { argb: "FF10B981" } },
                left: { style: "thin", color: { argb: "FF10B981" } },
                right: { style: "thin", color: { argb: "FF10B981" } },
            };
        });

        // Column widths
        sheet.getColumn(1).width = 6;
        sheet.getColumn(2).width = 28;
        sheet.getColumn(3).width = 22;
        for (let i = 4; i <= 3 + dateColumns.value.length; i++) {
            sheet.getColumn(i).width = 6;
        }
        sheet.getColumn(lastColIndex + 1).width = 16;
        sheet.getColumn(lastColIndex + 2).width = 16;
        sheet.getColumn(lastColIndex + 3).width = 16;

        // Write and download
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `Jadwal_Shifting_SPPG_${tanggalMulai.value}_sd_${tanggalSelesai.value}.xlsx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        showToast("File Jadwal Shifting (.xlsx) berhasil didownload!");
    } catch (e) {
        console.error("Gagal export shifting excel:", e);
        showToast("Gagal mendownload file Excel: " + (e?.message || e));
    } finally {
        isExporting.value = false;
    }
}

// ─── Badge Helper ─────────────────────────────────────────────────────────────
function getJabatanBadgeClass(jabatan) {
    if (!jabatan) return "bg-slate-100 text-slate-700 border-slate-200";
    const j = jabatan.toLowerCase();
    if (j.includes("kepala")) return "bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold";
    if (j.includes("gizi") || j.includes("keuangan")) return "bg-teal-50 text-teal-700 border-teal-200 font-semibold";
    if (j.includes("chef") || j.includes("masak") || j.includes("pengolahan")) return "bg-amber-50 text-amber-700 border-amber-200";
    if (j.includes("persiapan")) return "bg-yellow-50 text-yellow-800 border-yellow-200";
    if (j.includes("pemorsian")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
    if (j.includes("distribusi") || j.includes("driver")) return "bg-cyan-50 text-cyan-800 border-cyan-200";
    if (j.includes("kebersihan") || j.includes("cuci")) return "bg-purple-50 text-purple-700 border-purple-200";
    return "bg-slate-50 text-slate-700 border-slate-200";
}
</script>

<template>
    <AppLayout>
        <Head title="Shifting - SIPEGE" />

        <div class="space-y-3.5 sm:space-y-4">
            <!-- ─── Notification Toast ────────────────────────────────────── -->
            <transition
                enter-active-class="transform transition ease-out duration-300"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isToastVisible"
                    class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-5 z-[99999] flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-900 text-white shadow-xl border border-slate-700 text-xs"
                >
                    <CheckCircle2 class="h-4 w-4 text-emerald-400 shrink-0" />
                    <span class="font-medium">{{ toastMessage }}</span>
                </div>
            </transition>

            <!-- ─── Header Section (Responsive Mobile & Desktop) ───────────── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <div class="p-2 sm:p-2.5 rounded-xl bg-primary/10 text-primary border border-primary/20 shadow-2xs shrink-0">
                        <CalendarRange class="h-5 w-5 sm:h-6 sm:w-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight leading-tight">
                                Shifting Hari Kerja Petugas
                            </h1>
                            <span
                                v-if="isDirty"
                                class="px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse"
                            >
                                Belum Disimpan
                            </span>
                            <span
                                v-else
                                class="px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200"
                            >
                                Tersimpan
                            </span>
                        </div>
                        <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5 truncate">
                            Tabel bantu konfigurasi rotasi shift & pembagian jadwal operasional personil SPPG
                        </p>
                    </div>
                </div>

                <!-- Action Buttons: Icon + Text (Responsive Mobile & Desktop) -->
                <div class="flex items-center gap-1.5 sm:gap-2 w-full sm:w-auto justify-end flex-wrap">
                    <!-- Tombol Reset (Jika Dirty) -->
                    <button
                        v-if="isDirty"
                        type="button"
                        @click="resetSchedule"
                        class="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                        title="Batal Perubahan"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span class="hidden sm:inline">Reset</span>
                    </button>

                    <!-- Tombol Tukar Shift -->
                    <button
                        type="button"
                        @click="openSwapModal()"
                        class="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                        title="Tukar Shift Antar Petugas"
                    >
                        <ArrowLeftRight class="h-3.5 w-3.5" />
                        <span>Tukar Shift</span>
                    </button>

                    <!-- Tombol Terapkan Pola Generator -->
                    <button
                        type="button"
                        @click="openGeneratorModal"
                        class="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-primary/10 border border-primary/20 hover:bg-primary/20 text-primary text-xs font-bold shadow-2xs transition-colors cursor-pointer"
                        title="Automasi Pola Rotasi Shifting"
                    >
                        <Sparkles class="h-3.5 w-3.5" />
                        <span>Pola Shifting</span>
                    </button>

                    <!-- Tombol Export Excel -->
                    <button
                        type="button"
                        @click="exportShiftingExcel"
                        :disabled="isExporting"
                        class="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        title="Download Jadwal Shifting (.xlsx)"
                    >
                        <FileSpreadsheet class="h-3.5 w-3.5" />
                        <span class="hidden sm:inline">Export Excel</span>
                        <span class="sm:hidden">Excel</span>
                    </button>

                    <!-- Tombol Simpan Jadwal -->
                    <button
                        type="button"
                        @click="simpanShifting"
                        :disabled="isSaving || !isDirty"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs font-bold shadow-xs transition-colors cursor-pointer',
                            isDirty
                                ? 'bg-primary text-white hover:bg-primary/90 shadow-md ring-2 ring-primary/30'
                                : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed',
                        ]"
                        title="Simpan Jadwal Shifting ke Database"
                    >
                        <Save class="h-3.5 w-3.5" />
                        <span>{{ isSaving ? "Menyimpan..." : "Simpan Jadwal" }}</span>
                    </button>
                </div>
            </div>

            <!-- ─── Filter Bar Periode & Rentang Tanggal ─────────────────────── -->
            <PeriodDateFilterBar
                :start-date="tanggalMulai"
                :end-date="tanggalSelesai"
                :mode="modeSkala"
                :periode-id="selectedPeriodeId"
                :periodes="periodes"
                :show-archive="false"
                @change="onDateFilterChange"
            />

            <!-- ─── KPI Summary Cards (Responsive 2 cols mobile, 4 cols desktop) ─── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                <!-- Card 1: Total Personil -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs flex items-center gap-3">
                    <div class="p-2 sm:p-2.5 rounded-lg bg-blue-50 text-blue-600 shrink-0">
                        <Users class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs font-medium text-slate-500 truncate">Total Personil</p>
                        <p class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                            {{ kpiMetrics.totalPersonil }} <span class="text-xs font-normal text-slate-500">Orang</span>
                        </p>
                    </div>
                </div>

                <!-- Card 2: Rata-rata On Duty / Hari -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs flex items-center gap-3">
                    <div class="p-2 sm:p-2.5 rounded-lg bg-emerald-50 text-emerald-600 shrink-0">
                        <UserCheck class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs font-medium text-slate-500 truncate">Rata-rata On Duty / Hari</p>
                        <p class="text-base sm:text-lg font-bold text-emerald-700 leading-tight">
                            {{ kpiMetrics.rata2MasukPerHari }} <span class="text-xs font-normal text-slate-500">Petugas</span>
                        </p>
                    </div>
                </div>

                <!-- Card 3: Keseimbangan Shift -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs flex items-center gap-3">
                    <div class="p-2 sm:p-2.5 rounded-lg bg-indigo-50 text-indigo-600 shrink-0">
                        <Sliders class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs font-medium text-slate-500 truncate">Rasio Shift Masuk</p>
                        <p class="text-base sm:text-lg font-bold text-indigo-700 leading-tight">
                            {{ kpiMetrics.keseimbanganShift }}
                        </p>
                    </div>
                </div>

                <!-- Card 4: Hari Operasional -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs flex items-center gap-3">
                    <div class="p-2 sm:p-2.5 rounded-lg bg-amber-50 text-amber-600 shrink-0">
                        <CalendarDays class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs font-medium text-slate-500 truncate">Rentang Waktu</p>
                        <p class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                            {{ kpiMetrics.totalHari }} <span class="text-xs font-normal text-slate-500">Hari</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── Filter & Search Bar + Quick Actions ──────────────────────── -->
            <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs space-y-2.5">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2.5">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[200px] max-w-md">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, NIK, atau divisi..."
                            class="w-full pl-8.5 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-800 placeholder:text-slate-400"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <X class="h-3 w-3" />
                        </button>
                    </div>

                    <!-- Filter Dropdowns & Quick Action Buttons -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Filter Divisi / Jabatan -->
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="text-slate-500 hidden sm:inline">Divisi:</span>
                            <select
                                v-model="selectedJabatanFilter"
                                class="py-1.5 pl-2.5 pr-7 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-slate-700"
                            >
                                <option value="all">Semua Divisi ({{ props.petugas.length }})</option>
                                <option v-for="j in daftarJabatan" :key="j" :value="j">
                                    {{ j }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Status Petugas -->
                        <div class="flex items-center gap-1.5 text-xs">
                            <select
                                v-model="selectedStatusFilter"
                                class="py-1.5 pl-2.5 pr-7 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 text-slate-700"
                            >
                                <option value="all">Semua Status</option>
                                <option value="Aktif">Hanya Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>

                        <!-- Divider -->
                        <div class="h-5 w-px bg-slate-200 hidden sm:block"></div>

                        <!-- Quick Bulk Buttons -->
                        <button
                            type="button"
                            @click="setAllIn"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-semibold transition-colors cursor-pointer"
                            title="Set semua personil masuk di rentang ini"
                        >
                            <CheckCheck class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Semua Masuk</span>
                        </button>

                        <button
                            type="button"
                            @click="setAllOff"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-semibold transition-colors cursor-pointer"
                            title="Set semua personil libur di rentang ini"
                        >
                            <XCircle class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Semua Libur</span>
                        </button>
                    </div>
                </div>

                <!-- Legend & Mobile Swipe Hint -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-medium text-slate-700">Panduan Status Shift:</span>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-emerald-600 text-white font-bold text-[10px] shadow-2xs">H</span>
                            <span>Shift Masuk (Kerja)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-slate-500 text-white font-bold text-[10px] shadow-2xs">L</span>
                            <span>Shift Libur (Off)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-md border border-dashed border-slate-300 text-slate-400 font-bold text-[10px]">·</span>
                            <span>Belum Diatur</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-primary text-[10px] sm:text-[11px] font-medium">
                        <Sparkles class="h-3.5 w-3.5 shrink-0" />
                        <span>Tip: Klik langsung tanggal di kolom atau nama petugas di baris untuk ganti serentak: Masuk (H) ➜ Libur (L) ➜ Kosong (·).</span>
                    </div>
                </div>
            </div>

            <!-- ─── Interactive Shifting Matrix Table ────────────────────────── -->
            <div class="rounded-xl bg-white border border-slate-200/80 shadow-2xs overflow-hidden">
                <!-- Mobile Hint Bar -->
                <div class="sm:hidden px-3 py-1.5 bg-blue-50/90 border-b border-blue-100 text-blue-700 text-[11px] flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <Sparkles class="h-3.5 w-3.5 text-blue-500 shrink-0" />
                        <span>Geser tabel untuk melihat seluruh tanggal</span>
                    </span>
                    <span class="font-bold text-[10px] uppercase bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded">Geser ↔</span>
                </div>

                <!-- Mobile Horizontal Scroll Container with Minimum Width -->
                <div class="overflow-x-auto relative max-h-[70vh] touch-pan-x overscroll-x-contain">
                    <table class="w-full text-left border-collapse min-w-[1050px]">
                        <!-- Table Head -->
                        <thead class="sticky top-0 z-20 bg-slate-900 text-white text-xs select-none shadow-xs">
                            <tr>
                                <!-- Sticky Left 1: No (Sticky on Desktop, Natural Flow on Mobile) -->
                                <th class="sm:sticky sm:left-0 z-10 sm:z-30 bg-slate-900 px-2 sm:px-3 py-2.5 text-center font-bold w-10 sm:w-12 min-w-[40px] sm:min-w-[48px] border-b border-r border-slate-800">
                                    No
                                </th>
                                <!-- Sticky Left 2: Petugas & Divisi (Sticky on Desktop, Natural Flow on Mobile) -->
                                <th class="sm:sticky sm:left-10 sm:left-12 z-10 sm:z-30 bg-slate-900 px-3 sm:px-3.5 py-2.5 font-bold w-[220px] sm:w-[260px] min-w-[200px] sm:min-w-[260px] border-b border-r border-slate-800">
                                    Nama Petugas & Divisi
                                </th>

                                <!-- Dynamic Date Columns (Klik Langsung Bergantian: H ➜ L ➜ Kosong) -->
                                <th
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    :class="[
                                        'px-1.5 py-2 text-center border-b border-r border-slate-800 font-bold min-w-[42px] cursor-pointer hover:bg-slate-800 transition-colors select-none group',
                                        col.isSunday ? 'bg-rose-950/80 text-rose-200 hover:bg-rose-900/90' : (col.isSaturday ? 'bg-rose-950/60 text-rose-200 hover:bg-rose-900/80' : ''),
                                        col.isToday ? 'bg-primary/40 ring-1 ring-primary/60' : '',
                                    ]"
                                    :title="`Klik tanggal ${col.dayNum} (${col.dayName}) untuk ganti serentak seluruh personil: H ➜ L ➜ Kosong`"
                                    @click="toggleKolomStatus(col.dateStr)"
                                >
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-[10px] uppercase font-semibold tracking-wider text-slate-300 group-hover:text-white">
                                            {{ col.dayName }}
                                        </span>
                                        <button
                                            type="button"
                                            class="mt-0.5 px-1 py-0.5 rounded text-xs font-black transition-all cursor-pointer group-hover:scale-110 active:scale-95"
                                            :class="col.isToday ? 'text-primary-foreground font-black underline' : 'text-white'"
                                        >
                                            {{ col.dayNum }}
                                        </button>
                                        <span v-if="col.isToday" class="text-[7.5px] font-extrabold uppercase tracking-tighter text-emerald-300 leading-none mt-0.5">
                                            Hari Ini
                                        </span>
                                    </div>
                                </th>

                                <!-- Summary Right 1: Total Masuk (H) -->
                                <th class="px-3 py-2.5 text-center font-bold min-w-[80px] bg-emerald-950 text-emerald-200 border-b border-r border-slate-800">
                                    Masuk (H)
                                </th>
                                <!-- Summary Right 2: Total Libur (L) -->
                                <th class="px-3 py-2.5 text-center font-bold min-w-[80px] bg-slate-800 text-slate-200 border-b border-r border-slate-800">
                                    Libur (L)
                                </th>
                                <!-- Summary Right 3: Beban Kerja % -->
                                <th class="px-3 py-2.5 text-center font-bold min-w-[90px] bg-slate-900 text-slate-200 border-b border-slate-800">
                                    Rasio Kerja
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            <tr
                                v-for="(p, pIdx) in filteredPetugas"
                                :key="p.id"
                                class="hover:bg-slate-50/80 transition-colors group"
                            >
                                <!-- Sticky Left 1: No (Sticky on Desktop, Natural Flow on Mobile) -->
                                <td
                                    class="sm:sticky sm:left-0 z-0 sm:z-10 bg-white group-hover:bg-slate-50 hover:!bg-emerald-50/80 px-2 sm:px-3 py-2 text-center text-slate-400 hover:text-emerald-700 font-medium w-10 sm:w-12 min-w-[40px] sm:min-w-[48px] border-r border-slate-200/80 cursor-pointer select-none transition-colors"
                                    :title="`Klik untuk ganti serentak baris ${p.nama}: H ➜ L ➜ Kosong`"
                                    @click="toggleBarisStatus(p.id)"
                                >
                                    {{ pIdx + 1 }}
                                </td>

                                <!-- Sticky Left 2: Petugas & Divisi (Sticky on Desktop, Natural Flow on Mobile) -->
                                <td
                                    class="sm:sticky sm:left-10 sm:left-12 z-0 sm:z-10 bg-white group-hover:bg-slate-50 hover:!bg-emerald-50/80 px-3 sm:px-3.5 py-2 border-r border-slate-200/80 cursor-pointer select-none transition-colors w-[220px] sm:w-[260px] min-w-[200px] sm:min-w-[260px]"
                                    :title="`Klik nama ${p.nama} untuk ganti serentak satu baris: H ➜ L ➜ Kosong`"
                                    @click="toggleBarisStatus(p.id)"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <!-- Avatar Initials -->
                                        <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold flex items-center justify-center text-[10px] shrink-0">
                                            {{ p.nama?.slice(0, 2)?.toUpperCase() }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-slate-900 text-xs break-words leading-tight">
                                                {{ p.nama }}
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                                <span
                                                    :class="[
                                                        'px-1.5 py-0.5 rounded text-[10px] border break-words leading-none',
                                                        getJabatanBadgeClass(p.jabatan),
                                                    ]"
                                                    :title="p.jabatan"
                                                >
                                                    {{ p.jabatan || "-" }}
                                                </span>
                                                <span
                                                    v-if="wajibHadirStaffIds.includes(p.id)"
                                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0 leading-none"
                                                    title="Personil Wajib Selalu Hadir (Bebas Shifting)"
                                                >
                                                    <ShieldCheck class="h-2.5 w-2.5" />
                                                    <span>Tetap</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Interactive Date Matrix Cells -->
                                <td
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    class="p-1 text-center border-r border-slate-100/80"
                                    :class="[
                                        col.isWeekend ? 'bg-rose-50/25' : '',
                                        col.isToday ? 'bg-primary/5' : '',
                                    ]"
                                >
                                    <button
                                        type="button"
                                        @click="toggleCellShift(p.id, col.dateStr)"
                                        :class="[
                                            'w-8 h-8 mx-auto rounded-lg font-bold text-xs flex items-center justify-center transition-all cursor-pointer shadow-2xs select-none',
                                            matrixShifting[p.id]?.[col.dateStr] === 'H'
                                                ? 'bg-emerald-600 hover:bg-emerald-700 text-white ring-1 ring-emerald-500'
                                                : matrixShifting[p.id]?.[col.dateStr] === 'H2'
                                                ? 'bg-teal-500 hover:bg-teal-600 text-white ring-1 ring-teal-400'
                                                : matrixShifting[p.id]?.[col.dateStr] === 'L'
                                                ? 'bg-slate-500 hover:bg-slate-600 text-white ring-1 ring-slate-400'
                                                : 'bg-slate-50 hover:bg-slate-100 text-slate-300 hover:text-slate-500 border border-dashed border-slate-200 hover:border-slate-300',
                                        ]"
                                        :title="`${p.nama} (${col.dayName}, ${col.dayNum}): ${matrixShifting[p.id]?.[col.dateStr] === 'H' ? 'Shift Masuk' : matrixShifting[p.id]?.[col.dateStr] === 'L' ? 'Shift Libur' : 'Klik untuk tentukan shift'}`"
                                    >
                                        {{
                                            matrixShifting[p.id]?.[col.dateStr] === "H"
                                                ? "H"
                                                : matrixShifting[p.id]?.[col.dateStr] === "H2"
                                                ? "½"
                                                : matrixShifting[p.id]?.[col.dateStr] === "L"
                                                ? "L"
                                                : "·"
                                        }}
                                    </button>
                                </td>

                                <!-- Summary 1: Total Masuk -->
                                <td class="px-3 py-2 text-center font-bold text-emerald-700 bg-emerald-50/40 border-r border-slate-200/80">
                                    {{ getPetugasShiftStats(p.id).hadir }}
                                </td>

                                <!-- Summary 2: Total Libur -->
                                <td class="px-3 py-2 text-center font-bold text-slate-600 bg-slate-50/60 border-r border-slate-200/80">
                                    {{ getPetugasShiftStats(p.id).libur }}
                                </td>

                                <!-- Summary 3: Rasio Kerja % -->
                                <td class="px-3 py-2 text-center">
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold"
                                        :class="[
                                            getPetugasShiftStats(p.id).rasioKerja >= 60
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : getPetugasShiftStats(p.id).rasioKerja >= 40
                                                ? 'bg-blue-100 text-blue-800'
                                                : 'bg-amber-100 text-amber-800',
                                        ]"
                                    >
                                        {{ getPetugasShiftStats(p.id).rasioKerja }}%
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredPetugas.length === 0">
                                <td :colspan="dateColumns.length + 5" class="py-12 text-center text-slate-400">
                                    <AlertCircle class="h-8 w-8 mx-auto mb-2 text-slate-300" />
                                    <p class="font-medium text-slate-600">Tidak ada data personil yang sesuai dengan filter.</p>
                                    <p class="text-[11px] text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter divisi.</p>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Table Footer: Staffing Health & Daily Totals -->
                        <tfoot class="sticky bottom-0 z-20 bg-slate-50 text-xs font-bold border-t-2 border-slate-300 select-none shadow-xs">
                            <!-- Footer Row 1: Total Petugas Masuk (On Duty) -->
                            <tr class="bg-emerald-50/80 text-emerald-950">
                                <td colspan="2" class="sm:sticky sm:left-0 z-10 sm:z-30 bg-emerald-100/90 px-3 sm:px-3.5 py-2 text-right font-black border-r border-emerald-300">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider">Harian</span>
                                        <span>Total Shift Masuk (H):</span>
                                    </div>
                                </td>
                                <td
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    class="p-1 text-center font-black border-r border-emerald-200/80"
                                    :title="`${col.dayName}, ${col.dayNum}: ${perDateSummary[col.dateStr]?.masukCount || 0} Petugas Masuk`"
                                >
                                    <span class="inline-flex items-center justify-center w-7 h-6 rounded bg-emerald-600 text-white text-xs shadow-2xs font-bold">
                                        {{ perDateSummary[col.dateStr]?.masukCount || 0 }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center font-black bg-emerald-200/80 text-emerald-900 border-r border-emerald-300">
                                    {{ kpiMetrics.rata2MasukPerHari }}
                                </td>
                                <td class="px-2 py-2 text-center font-black bg-slate-200/80 text-slate-800 border-r border-slate-300">
                                    {{ kpiMetrics.rata2LiburPerHari }}
                                </td>
                                <td class="px-2 py-2 text-center font-black text-emerald-900">
                                    {{ kpiMetrics.keseimbanganShift }}
                                </td>
                            </tr>

                            <!-- Footer Row 2: Total Petugas Libur (Off) -->
                            <tr class="bg-slate-100/90 text-slate-700">
                                <td colspan="2" class="sm:sticky sm:left-0 z-10 sm:z-30 bg-slate-200/90 px-3 sm:px-3.5 py-1.5 text-right font-bold border-r border-slate-300">
                                    <span>Total Shift Libur (L):</span>
                                </td>
                                <td
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    class="p-1 text-center font-semibold text-slate-600 border-r border-slate-200"
                                >
                                    <span class="text-[11px]">
                                        {{ perDateSummary[col.dateStr]?.liburCount || 0 }}
                                    </span>
                                </td>
                                <td colspan="3" class="px-3 py-1.5 text-center text-[11px] text-slate-500 font-normal">
                                    Rata-rata On Duty Harian
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ─── Modal Generator Pola Shifting ───────────────────────────── -->
            <Teleport to="body">
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="isGeneratorModalOpen"
                        class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-5 lg:p-6"
                        style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
                    >
                        <div class="relative w-full max-w-lg lg:max-w-4xl xl:max-w-5xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100 my-auto animate-in fade-in zoom-in-95 duration-200">
                            <!-- Modal Header -->
                            <div class="p-4 sm:p-5 flex items-center justify-between bg-slate-50/80">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 rounded-xl bg-primary/10 text-primary border border-primary/20">
                                        <Sparkles class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                            Generator Pola Shifting Hari Kerja
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Terapkan pola rotasi otomatis ke personil SPPG
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="closeGeneratorModal"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Modal Body: 2 Kolom di Desktop (Memanfaatkan Sisi Lebar Layar), 1 Kolom Rapi di Mobile -->
                            <div class="p-4 sm:p-6 text-xs max-h-[82vh] overflow-y-auto">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                                    
                                    <!-- ─── Kolom Kiri (Desktop): Personil Wajib & Pengaturan Lingkup ─── -->
                                    <div class="lg:col-span-5 space-y-4">
                                        <!-- Bagian 1: Personil Wajib Selalu Hadir (Bebas Shifting) -->
                                        <div class="p-3 sm:p-3.5 rounded-xl border border-amber-200/90 bg-amber-50/50 space-y-2.5">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="p-1.5 rounded-lg bg-amber-100 text-amber-800 shrink-0">
                                                        <ShieldCheck class="h-4 w-4" />
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-slate-900 text-xs">
                                                            Personil Wajib Hadir (100%)
                                                        </p>
                                                        <p class="text-slate-500 text-[11px]">
                                                            Bebas rotasi shifting (selalu hadir hari operasional).
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1.5 text-[11px] self-end sm:self-auto">
                                                    <span class="px-2 py-0.5 rounded-md font-bold bg-amber-200 text-amber-900 text-[10px]">
                                                        {{ selectedWajibHadirCount }} Org
                                                    </span>
                                                    <button
                                                        type="button"
                                                        @click="resetWajibHadirToDefault"
                                                        class="px-2 py-0.5 rounded border border-amber-300 bg-white hover:bg-amber-100 text-amber-900 text-[10px] font-semibold transition-colors cursor-pointer"
                                                        title="Kembalikan ke jabatan default"
                                                    >
                                                        Reset
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Quick Select Buttons per Kategori Jabatan Default -->
                                            <div class="space-y-1.5 pt-1 border-t border-amber-200/60">
                                                <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">
                                                    Pilih Cepat Jabatan Wajib:
                                                </span>
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <button
                                                        v-for="cat in defaultRoleCategories"
                                                        :key="cat.name"
                                                        type="button"
                                                        @click="toggleRoleCategory(cat.keywords)"
                                                        :class="[
                                                            'px-2 py-1 rounded-lg text-[10px] font-bold border transition-colors cursor-pointer flex items-center gap-1',
                                                            isRoleCategoryFullySelected(cat.keywords)
                                                                ? 'bg-amber-600 text-white border-amber-600 shadow-2xs'
                                                                : isRoleCategoryPartiallySelected(cat.keywords)
                                                                ? 'bg-amber-100 text-amber-900 border-amber-300'
                                                                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
                                                        ]"
                                                    >
                                                        <Check v-if="isRoleCategoryFullySelected(cat.keywords)" class="h-3 w-3" />
                                                        <span>{{ cat.name }}</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Toggle Expand Detail Personil Satu per Satu -->
                                            <div class="pt-1 border-t border-amber-200/60">
                                                <button
                                                    type="button"
                                                    @click="isWajibHadirListExpanded = !isWajibHadirListExpanded"
                                                    class="text-[11px] font-bold text-amber-900 hover:text-amber-700 flex items-center gap-1 cursor-pointer"
                                                >
                                                    <span>{{ isWajibHadirListExpanded ? 'Sembunyikan Daftar Personil' : 'Lihat / Pilih Personil Satu per Satu (' + selectedWajibHadirCount + ' Terpilih)' }}</span>
                                                    <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': isWajibHadirListExpanded }" />
                                                </button>

                                                <div
                                                    v-if="isWajibHadirListExpanded"
                                                    class="mt-2 max-h-48 overflow-y-auto space-y-1 p-2 bg-white rounded-lg border border-slate-200 divide-y divide-slate-100"
                                                >
                                                    <label
                                                        v-for="p in props.petugas"
                                                        :key="p.id"
                                                        class="flex items-center justify-between py-1 px-1.5 hover:bg-slate-50 rounded cursor-pointer text-xs"
                                                    >
                                                        <div class="flex items-center gap-2 min-w-0 flex-1 mr-2">
                                                            <input
                                                                type="checkbox"
                                                                :value="p.id"
                                                                v-model="wajibHadirStaffIds"
                                                                class="rounded text-amber-600 focus:ring-amber-500 h-3.5 w-3.5 cursor-pointer shrink-0"
                                                            />
                                                            <span class="font-medium text-slate-800 break-words leading-tight">{{ p.nama }}</span>
                                                        </div>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
                                                            {{ p.jabatan || '-' }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bagian 2: Opsi Lingkup Divisi & Hari Libur Operasional -->
                                        <div class="p-3 sm:p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 space-y-3">
                                            <p class="font-bold text-slate-900 text-xs">
                                                Lingkup Penerapan & Hari Kerja:
                                            </p>

                                            <div>
                                                <label class="block font-bold text-slate-700 mb-1 text-[11px]">
                                                    Terapkan Shifting Ke Divisi:
                                                </label>
                                                <select
                                                    v-model="generatorTargetDivisi"
                                                    class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                >
                                                    <option value="all">Semua Divisi Sekaligus (Masing-masing mandiri)</option>
                                                    <option v-for="j in daftarJabatan" :key="j" :value="j">
                                                        Hanya Divisi: {{ j }}
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="pt-1 border-t border-slate-200">
                                                <label class="flex items-start gap-2.5 cursor-pointer select-none">
                                                    <input
                                                        type="checkbox"
                                                        v-model="generatorExcludeWeekend"
                                                        class="rounded text-primary focus:ring-primary mt-0.5 h-4 w-4"
                                                    />
                                                    <div>
                                                        <span class="text-slate-900 font-bold text-xs">
                                                            Sabtu dan Minggu Libur
                                                        </span>
                                                        <p class="text-slate-500 text-[11px] mt-0.5">
                                                            Hari Sabtu & Minggu otomatis diatur Libur (L) pada pola rotasi ini.
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ─── Kolom Kanan (Desktop): Pilihan Pola Shifting ─── -->
                                    <div class="lg:col-span-7 space-y-3">

                                <!-- Opsi Pola Rotasi -->
                                <div class="space-y-1.5">
                                    <label class="block font-bold text-slate-700">
                                        Pilih Model Pola Shifting (Untuk Personil Lainnya):
                                    </label>
                                    <div class="grid grid-cols-1 gap-2.5">
                                        <!-- Pola 1: Shifting Bergantian Antar Personil di Masing-masing Divisi / Tim (Rekomendasi SPPG) -->
                                        <label
                                            :class="[
                                                'p-3.5 rounded-xl border flex items-start gap-3 cursor-pointer transition-colors',
                                                generatorPattern === 'divisi_rotasi'
                                                    ? 'bg-primary/5 border-primary ring-1 ring-primary/30 shadow-2xs'
                                                    : 'border-slate-200 hover:bg-slate-50',
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="generatorPattern"
                                                value="divisi_rotasi"
                                                class="mt-0.5 text-primary focus:ring-primary"
                                            />
                                            <div class="w-full space-y-2.5">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="font-bold text-slate-900 text-xs">
                                                        Shifting Bergantian di Masing-masing Divisi / Tim (Rekomendasi)
                                                    </p>
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 shrink-0">
                                                        Per Divisi & Orang
                                                    </span>
                                                </div>
                                                <p class="text-slate-500 text-[11px] leading-relaxed">
                                                    Personil di masing-masing divisi/tim saling bergantian hari kerja (misal: Si A hari sekian, digantikan Si B hari sekian dst). Setiap hari kerja operasional selalu tersedia personil yang bertugas di divisi tersebut.
                                                </p>

                                                <!-- Konfigurasi Khusus Shifting Per Divisi -->
                                                <div
                                                    v-if="generatorPattern === 'divisi_rotasi'"
                                                    class="pt-2 border-t border-slate-200/80 space-y-2.5"
                                                    @click.stop
                                                >
                                                    <!-- Pilihan Blok Hari Kerja -->
                                                    <div class="space-y-1">
                                                        <label class="block font-bold text-slate-700 text-[11px]">
                                                            Durasi Giliran / Blok Hari Kerja:
                                                        </label>
                                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                                            <button
                                                                type="button"
                                                                @click="generatorBlokHari = 1"
                                                                :class="[
                                                                    'px-2 py-1.5 rounded-lg text-center text-[10px] font-bold border transition-colors cursor-pointer',
                                                                    generatorBlokHari === 1
                                                                        ? 'bg-primary text-white border-primary shadow-2xs'
                                                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                ]"
                                                            >
                                                                1 Hari Bergantian
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="generatorBlokHari = 2"
                                                                :class="[
                                                                    'px-2 py-1.5 rounded-lg text-center text-[10px] font-bold border transition-colors cursor-pointer',
                                                                    generatorBlokHari === 2
                                                                        ? 'bg-primary text-white border-primary shadow-2xs'
                                                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                ]"
                                                            >
                                                                2 Hari Berturut
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="generatorBlokHari = 3"
                                                                :class="[
                                                                    'px-2 py-1.5 rounded-lg text-center text-[10px] font-bold border transition-colors cursor-pointer',
                                                                    generatorBlokHari === 3
                                                                        ? 'bg-primary text-white border-primary shadow-2xs'
                                                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                ]"
                                                            >
                                                                3 Hari Berturut
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="generatorBlokHari = 5"
                                                                :class="[
                                                                    'px-2 py-1.5 rounded-lg text-center text-[10px] font-bold border transition-colors cursor-pointer',
                                                                    generatorBlokHari === 5
                                                                        ? 'bg-primary text-white border-primary shadow-2xs'
                                                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                ]"
                                                            >
                                                                Mingguan (5 Hari)
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Pilihan Kapasitas / Rasio Kehadiran Divisi -->
                                                    <div class="space-y-1">
                                                        <label class="block font-bold text-slate-700 text-[11px]">
                                                            Kapasitas Hadir di Masing-masing Divisi:
                                                        </label>
                                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                                            <button
                                                                type="button"
                                                                @click="generatorDivisiRatio = '50'"
                                                                :class="[
                                                                    'px-2 py-1.5 rounded-lg text-center text-[10px] font-bold border transition-colors cursor-pointer',
                                                                    generatorDivisiRatio === '50'
                                                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                ]"
                                                            >
                                                                50% Hadir (Separuh)
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="generatorDivisiRatio = '1_orang'"
                                                                :class="[
                                                                    'px-2 py-1.5 rounded-lg text-center text-[10px] font-bold border transition-colors cursor-pointer',
                                                                    generatorDivisiRatio === '1_orang'
                                                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                ]"
                                                            >
                                                                1 Orang per Hari
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="generatorDivisiRatio = '67'"
                                                                :class="[
                                                                    'px-2 py-1.5 rounded-lg text-center text-[10px] font-bold border transition-colors cursor-pointer',
                                                                    generatorDivisiRatio === '67'
                                                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                ]"
                                                            >
                                                                67% (2 Kerja 1 Libur)
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="generatorDivisiRatio = 'kustom'"
                                                                :class="[
                                                                    'px-2 py-1.5 rounded-lg text-center text-[10px] font-bold border transition-colors cursor-pointer',
                                                                    generatorDivisiRatio === 'kustom'
                                                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                ]"
                                                            >
                                                                Kustom Hari
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Input Tambahan jika Kustom -->
                                                    <div
                                                        v-if="generatorDivisiRatio === 'kustom'"
                                                        class="flex items-center gap-2 p-2 bg-white rounded-lg border border-slate-200"
                                                    >
                                                        <label class="text-[11px] font-bold text-slate-700">Target Hadir per Personil:</label>
                                                        <input
                                                            type="number"
                                                            v-model.number="generatorCustomDays"
                                                            min="1"
                                                            :max="totalWorkDaysCount"
                                                            class="w-16 px-2 py-1 text-xs font-bold text-center rounded border border-slate-300"
                                                        />
                                                        <span class="text-slate-500 text-[10px]">Hari (dari total {{ totalWorkDaysCount }} hari kerja)</span>
                                                    </div>

                                                    <!-- Live Preview Simulasi Alur Giliran -->
                                                    <div class="p-2.5 rounded-lg bg-emerald-50/90 border border-emerald-200 text-emerald-800 text-[11px] flex items-start gap-2">
                                                        <Sparkles class="h-4 w-4 shrink-0 text-emerald-600 mt-0.5" />
                                                        <div>
                                                            <span class="font-bold">Simulasi Alur Giliran: </span>
                                                            <span>{{ simulationPreviewText }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Pola 2: Rotasi 2 Tim Global (Tim A & Tim B Bergantian) -->
                                        <label
                                            :class="[
                                                'p-3 rounded-xl border flex items-start gap-3 cursor-pointer transition-colors',
                                                generatorPattern === 'rotasi_2_tim'
                                                    ? 'bg-primary/5 border-primary ring-1 ring-primary/30'
                                                    : 'border-slate-200 hover:bg-slate-50',
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="generatorPattern"
                                                value="rotasi_2_tim"
                                                class="mt-0.5 text-primary focus:ring-primary"
                                            />
                                            <div class="w-full">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="font-bold text-slate-900 text-xs">
                                                        Rotasi 2 Tim Global (Tim A & Tim B Selang-Seling)
                                                    </p>
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 text-indigo-800 shrink-0">
                                                        50% Global
                                                    </span>
                                                </div>
                                                <p class="text-slate-500 text-[11px] mt-0.5">
                                                    Membagi seluruh personil ke dalam 2 tim besar yang masuk bergantian selang-seling (1 hari kerja, 1 hari libur).
                                                </p>

                                                <!-- Sub-opsi Khusus Rotasi 2 Tim -->
                                                <div
                                                    v-if="generatorPattern === 'rotasi_2_tim'"
                                                    class="pt-2.5 mt-2 border-t border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-2"
                                                    @click.stop
                                                >
                                                    <div>
                                                        <label class="block font-bold text-slate-700 mb-1 text-[11px]">
                                                            Mulai Hari Pertama:
                                                        </label>
                                                        <select
                                                            v-model="generatorStartType"
                                                            class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                        >
                                                            <option value="tim_a_first">Tim A Masuk Terlebih Dahulu</option>
                                                            <option value="tim_b_first">Tim B Masuk Terlebih Dahulu</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block font-bold text-slate-700 mb-1 text-[11px]">
                                                            Metode Pembagian Tim:
                                                        </label>
                                                        <select
                                                            v-model="generatorTeamMethod"
                                                            class="w-full py-1.5 px-2 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                        >
                                                            <option value="ganjil_genap">Selang-seling (Ganjil=A, Genap=B)</option>
                                                            <option value="separuh">Separuh (50% Depan=A, 50% Belakang=B)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Pola 3: Operasional SPPG Penuh (Senin–Jumat Masuk, Sabtu & Minggu Libur) -->
                                        <label
                                            :class="[
                                                'p-3 rounded-xl border flex items-start gap-3 cursor-pointer transition-colors',
                                                generatorPattern === 'senin_jumat'
                                                    ? 'bg-primary/5 border-primary ring-1 ring-primary/30'
                                                    : 'border-slate-200 hover:bg-slate-50',
                                            ]"
                                        >
                                            <input
                                                type="radio"
                                                v-model="generatorPattern"
                                                value="senin_jumat"
                                                class="mt-0.5 text-primary focus:ring-primary"
                                            />
                                            <div class="w-full">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="font-bold text-slate-900 text-xs">
                                                        Pola Operasional Penuh (Senin–Jumat Masuk Penuh, Sabtu & Minggu Libur)
                                                    </p>
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-800 shrink-0">
                                                        100% Masuk
                                                    </span>
                                                </div>
                                                <p class="text-slate-500 text-[11px] mt-0.5">
                                                    Seluruh personil masuk penuh setiap hari kerja operasional ({{ totalWorkDaysCount }} hari hadir).
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                            <!-- Modal Footer -->
                            <div class="p-4 sm:p-5 flex items-center justify-end gap-2 bg-slate-50/80">
                                <button
                                    type="button"
                                    @click="closeGeneratorModal"
                                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold transition-colors cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="applyGeneratorPattern"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white font-bold shadow-xs transition-colors cursor-pointer"
                                >
                                    <Sparkles class="h-4 w-4" />
                                    <span>Terapkan Pola ke Matriks</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
            </Teleport>

            <!-- ─── Modal Tukar Shift (Swap Shift) ───────────────────────────── -->
            <Teleport to="body">
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="isSwapModalOpen"
                        class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4"
                        style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
                    >
                        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100 my-auto animate-in fade-in zoom-in-95 duration-200">
                            <!-- Header -->
                            <div class="p-4 sm:p-5 flex items-center justify-between bg-slate-50/80">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100">
                                        <ArrowLeftRight class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                            Tukar Shift Antar Personil
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Tukar jadwal kerja antara 2 petugas pada tanggal tertentu
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="closeSwapModal"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="p-4 sm:p-6 space-y-3.5 text-xs">
                                <!-- Tanggal Shift -->
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">
                                        Pilih Tanggal Shift:
                                    </label>
                                    <select
                                        v-model="swapDate"
                                        class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    >
                                        <option v-for="col in dateColumns" :key="col.dateStr" :value="col.dateStr">
                                            {{ col.dayName }}, {{ formatTanggalIndo(col.dateStr) }} (Tgl {{ col.dayNum }})
                                        </option>
                                    </select>
                                </div>

                                <!-- Petugas 1 -->
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">
                                        Petugas 1:
                                    </label>
                                    <select
                                        v-model="swapPetugas1Id"
                                        class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    >
                                        <option value="" disabled>-- Pilih Petugas 1 --</option>
                                        <option v-for="p in props.petugas" :key="p.id" :value="p.id">
                                            {{ p.nama }} ({{ p.jabatan || '-' }}) - Status tgl ini: [{{ matrixShifting[p.id]?.[swapDate] || '-' }}]
                                        </option>
                                    </select>
                                </div>

                                <!-- Swap Arrow Indicator -->
                                <div class="flex items-center justify-center py-1 text-indigo-500">
                                    <ArrowLeftRight class="h-5 w-5 animate-pulse" />
                                </div>

                                <!-- Petugas 2 -->
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">
                                        Petugas 2:
                                    </label>
                                    <select
                                        v-model="swapPetugas2Id"
                                        class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    >
                                        <option value="" disabled>-- Pilih Petugas 2 --</option>
                                        <option v-for="p in props.petugas" :key="p.id" :value="p.id">
                                            {{ p.nama }} ({{ p.jabatan || '-' }}) - Status tgl ini: [{{ matrixShifting[p.id]?.[swapDate] || '-' }}]
                                        </option>
                                    </select>
                                </div>

                                <!-- Warning / Explanation -->
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 text-[11px] leading-relaxed">
                                    Status kerja Petugas 1 dan Petugas 2 pada tanggal yang dipilih akan otomatis saling bertukar. Klik simpan jadwal untuk menyimpan permanen.
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="p-4 sm:p-5 flex items-center justify-end gap-2 bg-slate-50/80">
                                <button
                                    type="button"
                                    @click="closeSwapModal"
                                    class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold transition-colors cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="executeSwapShift"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-xs transition-colors cursor-pointer"
                                >
                                    <ArrowLeftRight class="h-4 w-4" />
                                    <span>Tukar Jadwal Sekarang</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
            </Teleport>
        </div>
    </AppLayout>
</template>

