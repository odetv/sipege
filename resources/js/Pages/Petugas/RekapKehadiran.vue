<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    CalendarCheck,
    Users,
    Search,
    CheckCircle2,
    Clock,
    DollarSign,
    Calendar,
    Download,
    Eye,
    X,
    AlertCircle,
    Info,
    RotateCcw,
    Layers,
    BadgePercent,
    FileSpreadsheet,
    Save,
    Check,
    Filter,
    ChevronDown,
    Building2,
    Sparkles,
    ArrowRight,
    RefreshCw,
} from "lucide-vue-next";
import { downloadRekapAbsenGajiExcel, formatTanggalIndo } from "@/Services/exportPetugasHelper";
import DateRangePicker from "@/Components/DateRangePicker.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";

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
    initialPresensiMap: { type: Object, default: () => ({}) },
    initialAbsensiMap: { type: Object, default: () => ({}) }, // fallback backwards compatibility
});

const page = usePage();

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
    const daysToAdd = mode === "periodik" ? 13 : 27; // 14 hari atau 28 hari
    const end = new Date(base);
    end.setDate(base.getDate() + daysToAdd);
    const y = end.getFullYear();
    const m = String(end.getMonth() + 1).padStart(2, "0");
    const d = String(end.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

function normalizeModeRekap(mode) {
    if (!mode) return "";
    const m = String(mode).toLowerCase();
    if (m === "hari_ini" || m === "today") return "hari_ini";
    if (m === "periode" || m === "periodik" || m === "period") return "periode";
    if (m === "rentang" || m === "bulanan" || m === "range" || m === "custom") return "rentang";
    return "";
}

// Helper default tanggal & periode:
// Gunakan periode terakhir jika hari ini dalam rentangnya, atau bila sudah di luar periode maka gunakan hari ini
function getInitialDateState() {
    const today = todayStr.value;
    const sorted = [...(props.periodes || [])].sort((a, b) => {
        const noA = Number(a.nomor_periode) || 0;
        const noB = Number(b.nomor_periode) || 0;
        if (noB !== noA) return noB - noA;
        return String(b.tanggal_mulai || "").localeCompare(String(a.tanggal_mulai || ""));
    });
    const latest = sorted[0];

    // Default resolusi: periode terakhir yang memuat hari ini
    let defaultRes = null;
    if (latest && latest.tanggal_mulai && latest.tanggal_selesai) {
        const s = String(latest.tanggal_mulai).substring(0, 10);
        const e = String(latest.tanggal_selesai).substring(0, 10);
        if (today >= s && today <= e) {
            defaultRes = {
                start: s,
                end: e,
                mode: "periode",
                periodeId: String(latest.id),
            };
        }
    }

    if (!defaultRes) {
        defaultRes = {
            start: today,
            end: today,
            mode: "hari_ini",
            periodeId: "all",
        };
    }

    if (props.initialTanggalMulai && props.initialTanggalSelesai) {
        const norm = normalizeModeRekap(props.initialMode);
        return {
            start: props.initialTanggalMulai,
            end: props.initialTanggalSelesai,
            mode: norm || defaultRes.mode,
            periodeId: props.initialPeriodeId !== undefined ? String(props.initialPeriodeId) : defaultRes.periodeId,
        };
    }

    return defaultRes;
}

// ─── Mode Skala & Rentang Tanggal ─────────────────────────────────────────────
// Mode: 'hari_ini' | 'periode' | 'rentang'
const initialDateState = getInitialDateState();
const modeSkala = ref(initialDateState.mode);
const tanggalMulai = ref(initialDateState.start);
const tanggalSelesai = ref(initialDateState.end);
const selectedPeriodeId = ref(initialDateState.periodeId);
const selectedJabatanFilter = ref("all");
const selectedStatusFilter = ref("all");
const searchQuery = ref("");
const isFilterAllTime = ref(false);

function onDateFilterChange(filter) {
    if (!filter) return;
    tanggalMulai.value = filter.start;
    tanggalSelesai.value = filter.end;
    modeSkala.value = filter.mode;
    selectedPeriodeId.value = filter.periodeId;
    isFilterAllTime.value = !!filter.isAllTime;

    router.get(
        route("petugas.rekap-kehadiran"),
        {
            tanggal_mulai: filter.start,
            tanggal_selesai: filter.end,
            mode: filter.mode,
            periode_id: filter.periodeId,
        },
        { preserveState: true, preserveScroll: true }
    );
}

// ─── State DateRangePicker (Dua Bulan Menyatu) ─────────────────────────────────
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

        isDatePickerOpen.value = false;
        applyFilterTanggal();
    }
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

const is14HariActive = computed(() => dateColumns.value.length === 14);
const is28HariActive = computed(() => dateColumns.value.length === 28);

const labelSiklus = computed(() => {
    if (selectedPeriode.value) {
        return `Siklus: Periode ${selectedPeriode.value.nomor_periode} (${dateColumns.value.length} Hari Kerja)`;
    }
    if (dateColumns.value.length === 14) return "Siklus: Periodik (14 Hari Kerja)";
    if (dateColumns.value.length === 28) return "Siklus: Bulanan (28 Hari Kerja)";
    return `Siklus: Kustom (${dateColumns.value.length} Hari Kerja)`;
});

// ─── Dynamic Date Columns (Daftar Tanggal dalam Rentang) ───────────────────────
const dateColumns = computed(() => {
    if (!tanggalMulai.value || !tanggalSelesai.value) return [];
    const list = [];
    let cur = new Date(tanggalMulai.value + "T00:00:00");
    const end = new Date(tanggalSelesai.value + "T00:00:00");

    let count = 0;
    const dayNames = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

    while (cur <= end && count < 60) {
        const y = cur.getFullYear();
        const m = String(cur.getMonth() + 1).padStart(2, "0");
        const d = String(cur.getDate()).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const dayIdx = cur.getDay();

        list.push({
            dateStr,
            dayNum: d,
            monthNum: m,
            dayName: dayNames[dayIdx],
            isWeekend: dayIdx === 0 || dayIdx === 6,
            isSunday: dayIdx === 0,
            isToday: dateStr === todayStr.value,
            index: count + 1,
            label: `${d}/${m}`,
        });

        cur.setDate(cur.getDate() + 1);
        count++;
    }
    return list;
});

// ─── State Matriks Presensi Harian ─────────────────────────────────────────────
// Format: matrixPresensi[petugasId][dateStr] = '' | 'H' | 'H2' | 'L' | 'I' | 'S' | 'TK'
const matrixPresensi = ref({});
const isDirty = ref(false);
const isSaving = ref(false);

function initMatrixPresensi() {
    const map = {};
    const dbMap = (props.initialPresensiMap && Object.keys(props.initialPresensiMap).length > 0)
        ? props.initialPresensiMap
        : (props.initialAbsensiMap || {});

    props.petugas.forEach((p) => {
        map[p.id] = {};
        const pDb = dbMap[p.id] || {};

        dateColumns.value.forEach((dt) => {
            if (pDb[dt.dateStr]) {
                map[p.id][dt.dateStr] = pDb[dt.dateStr];
            } else {
                map[p.id][dt.dateStr] = ""; // Default KOSONG jika belum ada catatan presensi
            }
        });
    });

    matrixPresensi.value = map;
    isDirty.value = false;
}

// Watcher agar data dari props backend tersinkronisasi otomatis saat reload/redirect
watch(
    () => [props.initialPresensiMap, props.initialAbsensiMap, props.petugas],
    () => {
        initMatrixPresensi();
    },
    { deep: true }
);

// Set status satu sel
function setCellStatus(petugasId, dateStr, newStatus) {
    if (!matrixPresensi.value[petugasId]) {
        matrixPresensi.value[petugasId] = {};
    }
    matrixPresensi.value[petugasId][dateStr] = newStatus;
    isDirty.value = true;
}

// Cycle status: "" -> H -> H2 (Hadir Setengah Hari) -> L (Libur) -> I (Izin) -> S (Sakit) -> TK (Tanpa Keterangan) -> ""
function cycleStatus(petugasId, dateStr, isAktif = true) {
    if (!isAktif) return;
    const current = matrixPresensi.value[petugasId]?.[dateStr] || "";
    let next = "H";
    if (!current || current === "") next = "H";
    else if (current === "H") next = "H2";
    else if (current === "H2") next = "L";
    else if (current === "L") next = "I";
    else if (current === "I") next = "S";
    else if (current === "S") next = "TK";
    else if (current === "TK") next = ""; // Kosongkan kembali
    else next = "";

    setCellStatus(petugasId, dateStr, next);
}

// Quick Actions
function setSemuaHadirPenuh() {
    props.petugas.forEach((p) => {
        if (p.status === "Aktif") {
            if (!matrixPresensi.value[p.id]) matrixPresensi.value[p.id] = {};
            dateColumns.value.forEach((dt) => {
                matrixPresensi.value[p.id][dt.dateStr] = "H";
            });
        }
    });
    isDirty.value = true;
    showToast(`Seluruh personil aktif diset Hadir Penuh (H) untuk ${dateColumns.value.length} hari kerja`);
}

function kosongkanSemua() {
    props.petugas.forEach((p) => {
        if (!matrixPresensi.value[p.id]) matrixPresensi.value[p.id] = {};
        dateColumns.value.forEach((dt) => {
            matrixPresensi.value[p.id][dt.dateStr] = "";
        });
    });
    isDirty.value = true;
    showToast("Seluruh catatan presensi dikosongkan");
}

function toggleKolomStatus(dateStr) {
    // Jika semua sudah H di tanggal ini, maka kosongkan. Jika belum, set semua H
    const allH = props.petugas.every((p) => p.status !== "Aktif" || matrixPresensi.value[p.id]?.[dateStr] === "H");
    const nextSt = allH ? "" : "H";
    props.petugas.forEach((p) => {
        if (p.status === "Aktif") {
            if (!matrixPresensi.value[p.id]) matrixPresensi.value[p.id] = {};
            matrixPresensi.value[p.id][dateStr] = nextSt;
        }
    });
    isDirty.value = true;
    showToast(nextSt === "H" ? `Semua personil aktif pada tanggal ${dateStr} diset Hadir (H)` : `Kolom tanggal ${dateStr} dikosongkan`);
}

function resetPresensi() {
    initMatrixPresensi();
    showToast("Data presensi dikembalikan ke data tersimpan di database");
}

// ─── Filter & Navigasi Rentang Tanggal ─────────────────────────────────────────
function applyFilterTanggal() {
    router.get(
        route("petugas.rekap-kehadiran"),
        {
            mode: modeSkala.value,
            tanggal_mulai: tanggalMulai.value,
            tanggal_selesai: tanggalSelesai.value,
            periode_id: selectedPeriodeId.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
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

    applyFilterTanggal();
}

function onPeriodeSelectChange() {
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

onMounted(() => {
    initMatrixPresensi();
});

// ─── Simpan Presensi ke Database ──────────────────────────────────────────────
function handleSimpanPresensi() {
    if (isSaving.value) return;
    isSaving.value = true;

    try {
        const payload = JSON.stringify(matrixPresensi.value);

        router.post(
            route("petugas.rekap-kehadiran.simpan"),
            {
                json_data: payload,
                mode: modeSkala.value,
                tanggal_mulai: tanggalMulai.value,
                tanggal_selesai: tanggalSelesai.value,
                periode_id: selectedPeriodeId.value,
            },
            {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    isDirty.value = false;
                    isSaving.value = false;
                    showToast("Data presensi petugas berhasil disimpan ke database!");
                },
                onError: (errors) => {
                    console.error("Gagal simpan presensi:", errors);
                    isSaving.value = false;
                    showToast("Gagal menyimpan presensi: Silakan periksa kembali data.");
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            }
        );
    } catch (e) {
        console.error("Error simpan presensi:", e);
        showToast("Terjadi kesalahan saat menyimpan data presensi.");
        isSaving.value = false;
    }
}

// ─── Toast Notification ────────────────────────────────────────────────────────
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

// ─── Formatters & Badges ───────────────────────────────────────────────────────
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

function getJabatanBadgeClass(jabatan) {
    if (!jabatan) return "bg-slate-100 text-slate-700 border-slate-200";
    const j = jabatan.toLowerCase();
    if (j.includes("kepala sppg")) return "bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold";
    if (j.includes("plog") || j.includes("plok")) return "bg-teal-50 text-teal-700 border-teal-200 font-semibold";
    if (j.includes("lapangan")) return "bg-blue-50 text-blue-700 border-blue-200";
    if (j.includes("chef")) return "bg-amber-50 text-amber-700 border-amber-200";
    if (j.includes("pengolahan") || j.includes("masak")) return "bg-orange-50 text-orange-700 border-orange-200";
    if (j.includes("persiapan")) return "bg-yellow-50 text-yellow-800 border-yellow-200";
    if (j.includes("pemorsian")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
    if (j.includes("pengemudi") || j.includes("distribusi")) return "bg-sky-50 text-sky-700 border-sky-200";
    if (j.includes("cuci")) return "bg-cyan-50 text-cyan-700 border-cyan-200";
    if (j.includes("kebersihan")) return "bg-purple-50 text-purple-700 border-purple-200";
    if (j.includes("keamanan")) return "bg-rose-50 text-rose-700 border-rose-200";
    return "bg-slate-50 text-slate-700 border-slate-200";
}

function getStatusBadgeStyle(status) {
    if (status === "H") return "bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-2xs";
    if (status === "H2") return "bg-teal-500 hover:bg-teal-600 text-white font-black shadow-2xs ring-1 ring-teal-400";
    if (status === "L") return "bg-slate-500 hover:bg-slate-600 text-white font-bold shadow-2xs";
    if (status === "I") return "bg-amber-500 hover:bg-amber-600 text-white font-bold shadow-2xs";
    if (status === "S") return "bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-2xs";
    if (status === "TK") return "bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-2xs";
    return "bg-slate-50 hover:bg-slate-100 text-slate-300 hover:text-slate-600 border border-dashed border-slate-200 hover:border-slate-300";
}

function getStatusLabel(status) {
    if (status === "H2") return "½";
    if (!status || status === "") return "·";
    return status;
}

function getStatusTitle(status) {
    if (status === "H") return "Hadir Penuh (1 Hari - Gaji Penuh)";
    if (status === "H2") return "Hadir Setengah Hari (½ Hari - Gaji 50%)";
    if (status === "L") return "Libur Operasional";
    if (status === "I") return "Izin";
    if (status === "S") return "Sakit";
    if (status === "TK") return "Tanpa Keterangan (TK)";
    return "Belum ada catatan presensi (Klik untuk tentukan)";
}

// ─── Filtered Data ────────────────────────────────────────────────────────────
const filteredPetugas = computed(() => {
    return props.petugas.filter((p) => {
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim();
            const matchNama = (p.nama || "").toLowerCase().includes(q);
            const matchNik = (p.nik || "").toLowerCase().includes(q);
            const matchJabatan = (p.jabatan || "").toLowerCase().includes(q);
            if (!matchNama && !matchNik && !matchJabatan) return false;
        }

        if (selectedJabatanFilter.value !== "all" && p.jabatan !== selectedJabatanFilter.value) {
            return false;
        }

        if (selectedStatusFilter.value !== "all" && p.status !== selectedStatusFilter.value) {
            return false;
        }

        return true;
    });
});

// ─── Perhitungan Rekap Presensi Personil & KPI ─────────────────────────────────
const computedPetugasStats = computed(() => {
    const stats = {};
    const totalDays = dateColumns.value.length || 1;

    props.petugas.forEach((p) => {
        const pAtt = matrixPresensi.value[p.id] || {};
        let h = 0, h2 = 0, l = 0, i = 0, s = 0, tk = 0, kosong = 0;

        if (p.status === "Aktif") {
            dateColumns.value.forEach((dt) => {
                const st = pAtt[dt.dateStr] || "";
                if (st === "H") h++;
                else if (st === "H2") h2++;
                else if (st === "L") l++;
                else if (st === "I") i++;
                else if (st === "S") s++;
                else if (st === "TK") tk++;
                else kosong++;
            });
        }

        const honorPerHari = (parseInt(p.gaji_harian_bgn) || 0) + (parseInt(p.bonus_harian_mitra) || 0);
        // Hadir Penuh dihitung 1 manday, Hadir Setengah Hari dihitung 0.5 manday (gaji harian dipotong setengah)
        const mandays = h + (h2 * 0.5);
        const totalHonor = mandays * honorPerHari;
        const rate = Math.round((mandays / totalDays) * 100);

        stats[p.id] = {
            hadir: h,
            setengahHari: h2,
            libur: l,
            izin: i,
            sakit: s,
            tanpaKeterangan: tk,
            kosong,
            mandays,
            rate,
            totalHonor,
            honorPerHari,
        };
    });

    return stats;
});

const kpiStats = computed(() => {
    let totalHadirPenuh = 0;
    let totalSetengahHari = 0;
    let totalLibur = 0;
    let totalIzin = 0;
    let totalSakit = 0;
    let totalTK = 0;
    let totalMandays = 0;
    let totalHonorTerhitung = 0;
    let totalTargetMandays = 0;

    const totalDays = dateColumns.value.length || 0;

    props.petugas.forEach((p) => {
        if (p.status === "Aktif") {
            const st = computedPetugasStats.value[p.id] || {
                hadir: 0,
                setengahHari: 0,
                libur: 0,
                izin: 0,
                sakit: 0,
                tanpaKeterangan: 0,
                mandays: 0,
                totalHonor: 0,
            };
            totalHadirPenuh += st.hadir;
            totalSetengahHari += st.setengahHari;
            totalLibur += st.libur;
            totalIzin += st.izin;
            totalSakit += st.sakit;
            totalTK += st.tanpaKeterangan;
            totalMandays += st.mandays;
            totalHonorTerhitung += st.totalHonor;
            totalTargetMandays += totalDays;
        }
    });

    const persentaseKehadiran = totalTargetMandays > 0
        ? Math.round((totalMandays / totalTargetMandays) * 100)
        : 0;

    return {
        totalHadirPenuh,
        totalSetengahHari,
        totalLibur,
        totalIzin,
        totalSakit,
        totalTK,
        totalMandays,
        totalHonorTerhitung,
        persentaseKehadiran,
        totalTargetMandays,
    };
});

// Total hadir per tanggal (menghitung hadir penuh + 0.5 hadir setengah)
const hadirPerTanggal = computed(() => {
    const map = {};
    dateColumns.value.forEach((dt) => {
        let count = 0;
        props.petugas.forEach((p) => {
            if (p.status === "Aktif") {
                const st = matrixPresensi.value[p.id]?.[dt.dateStr] || "";
                if (st === "H") count += 1;
                else if (st === "H2") count += 0.5;
            }
        });
        map[dt.dateStr] = count;
    });
    return map;
});

// ─── Modal Download Excel Dinamis (Hari Ini / Bulanan / Periodik & Kustom) ────
const isExportModalOpen = ref(false);
const isExportMenuOpen = ref(false);
const isExporting = ref(false);
const exportOption = ref("current"); // 'today', 'bulanan', 'periodik', 'current'
const exportStartDate = ref(tanggalMulai.value);
const exportEndDate = ref(tanggalSelesai.value);

function openExportModal(defaultOpt = null) {
    isExportMenuOpen.value = false;
    if (defaultOpt) {
        exportOption.value = defaultOpt;
    } else {
        exportOption.value = modeSkala.value === "periodik" ? "periodik" : "bulanan";
    }

    if (exportOption.value === "today") {
        exportStartDate.value = todayStr.value;
        exportEndDate.value = todayStr.value;
    } else if (exportOption.value === "current") {
        exportStartDate.value = tanggalMulai.value;
        exportEndDate.value = tanggalSelesai.value;
    } else if (exportOption.value === "bulanan") {
        exportStartDate.value = tanggalMulai.value;
        exportEndDate.value = getDefaultEndDate("bulanan", exportStartDate.value);
    } else if (exportOption.value === "periodik") {
        exportStartDate.value = tanggalMulai.value;
        exportEndDate.value = getDefaultEndDate("periodik", exportStartDate.value);
    }
    isExportModalOpen.value = true;
}

function closeExportModal() {
    isExportModalOpen.value = false;
}

watch(exportOption, (newOpt) => {
    if (newOpt === "today") {
        exportStartDate.value = todayStr.value;
        exportEndDate.value = todayStr.value;
    } else if (newOpt === "bulanan") {
        exportEndDate.value = getDefaultEndDate("bulanan", exportStartDate.value);
    } else if (newOpt === "periodik") {
        exportEndDate.value = getDefaultEndDate("periodik", exportStartDate.value);
    } else if (newOpt === "current") {
        exportStartDate.value = tanggalMulai.value;
        exportEndDate.value = tanggalSelesai.value;
    }
});

async function downloadHariIniDirect() {
    isExportMenuOpen.value = false;
    isExporting.value = true;
    try {
        let activePeriode = null;
        if (selectedPeriodeId.value !== "all") {
            activePeriode = props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value)) || null;
        }

        const now = new Date();
        const d = String(now.getDate()).padStart(2, "0");
        const exportDateList = [{
            dateStr: todayStr.value,
            dayNum: d,
            label: "1",
        }];

        await downloadRekapAbsenGajiExcel({
            petugas: props.petugas,
            presensiMap: matrixPresensi.value,
            kehadiranMap: matrixPresensi.value,
            dateList: exportDateList,
            startDate: todayStr.value,
            endDate: todayStr.value,
            unitSppg: props.unitSppg,
            periode: activePeriode,
            modeHariKerja: 1,
        });

        showToast(`File Excel Presensi Hari Ini (${formatTanggalIndo(todayStr.value)}) berhasil didownload!`);
    } catch (e) {
        console.error("Gagal export excel hari ini:", e);
        showToast("Gagal mengunduh file Excel: " + (e?.message || e));
    } finally {
        isExporting.value = false;
    }
}

async function handleExecuteExport() {
    isExporting.value = true;
    try {
        let activePeriode = null;
        if (selectedPeriodeId.value !== "all") {
            activePeriode = props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value)) || null;
        }

        // Hitung list tanggal untuk export
        const exportDateList = [];
        let cur = new Date(exportStartDate.value + "T00:00:00");
        const end = new Date(exportEndDate.value + "T00:00:00");
        let idx = 1;
        while (cur <= end && idx <= 60) {
            const y = cur.getFullYear();
            const m = String(cur.getMonth() + 1).padStart(2, "0");
            const d = String(cur.getDate()).padStart(2, "0");
            exportDateList.push({
                dateStr: `${y}-${m}-${d}`,
                dayNum: d,
                label: String(idx),
            });
            cur.setDate(cur.getDate() + 1);
            idx++;
        }

        const days = exportDateList.length;
        const modeHari = exportOption.value === "today" ? 1 : (exportOption.value === "bulanan" ? 28 : (exportOption.value === "periodik" ? 14 : days));

        await downloadRekapAbsenGajiExcel({
            petugas: props.petugas,
            presensiMap: matrixPresensi.value,
            kehadiranMap: matrixPresensi.value,
            dateList: exportDateList,
            startDate: exportStartDate.value,
            endDate: exportEndDate.value,
            unitSppg: props.unitSppg,
            periode: activePeriode,
            modeHariKerja: modeHari,
        });

        const labelHasil = exportOption.value === "today" || days === 1 ? `Hari Ini (${formatTanggalIndo(exportStartDate.value)})` : `${days} Hari`;
        showToast(`File Excel Rekap Presensi & Gaji (${labelHasil}) berhasil didownload!`);
        closeExportModal();
    } catch (e) {
        console.error("Gagal export excel:", e);
        showToast("Gagal mengunduh file Excel: " + (e?.message || e));
    } finally {
        isExporting.value = false;
    }
}

// ─── Modal Detail Slip Presensi Petugas ────────────────────────────────────────
const isDetailModalOpen = ref(false);
const detailPetugas = ref(null);

function openDetailModal(p) {
    detailPetugas.value = p;
    isDetailModalOpen.value = true;
}

function closeDetailModal() {
    isDetailModalOpen.value = false;
    detailPetugas.value = null;
}
</script>

<template>
    <AppLayout>
        <Head title="Rekap Presensi Petugas SPPG - SIPEGE" />

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
                    <div class="p-2 sm:p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-2xs shrink-0">
                        <CalendarCheck class="h-5 w-5 sm:h-6 sm:w-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight leading-tight">
                                Rekap Presensi Petugas
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
                            Pencatatan matriks harian (Bulanan 28H / Periodik 14H) & hak honor
                        </p>
                    </div>
                </div>

                <!-- Action Buttons: Icon + Text (Responsive Icon-Only di HP) -->
                <div class="flex items-center gap-1.5 sm:gap-2 self-end sm:self-auto shrink-0">
                    <!-- Link ke Master Petugas -->
                    <Link
                        :href="route('petugas.index')"
                        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                        title="Daftar Master Petugas"
                    >
                        <Users class="h-4 w-4 text-slate-500" />
                        <span class="hidden md:inline">Master Petugas</span>
                    </Link>

                    <!-- Tombol Download Excel Rekap & Gaji dengan Menu Cepat -->
                    <div class="relative inline-flex items-stretch rounded-lg shadow-xs">
                        <button
                            type="button"
                            @click="openExportModal()"
                            class="inline-flex items-center gap-1.5 px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-l-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors cursor-pointer"
                            title="Download File Excel Rekap Absen & Gaji"
                        >
                            <FileSpreadsheet class="h-4 w-4" />
                            <span class="hidden sm:inline">Download Excel</span>
                            <span class="sm:hidden">Excel</span>
                        </button>
                        <button
                            type="button"
                            @click="isExportMenuOpen = !isExportMenuOpen"
                            class="inline-flex items-center px-1.5 sm:px-2 rounded-r-lg bg-emerald-700 hover:bg-emerald-800 text-white border-l border-emerald-500/60 text-xs transition-colors cursor-pointer"
                            title="Opsi Pilihan Download"
                        >
                            <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': isExportMenuOpen }" />
                        </button>

                        <!-- Backdrop Click Outside untuk Menu Dropdown -->
                        <div
                            v-if="isExportMenuOpen"
                            class="fixed inset-0 z-40"
                            @click="isExportMenuOpen = false"
                        ></div>

                        <!-- Dropdown Menu Cepat -->
                        <div
                            v-if="isExportMenuOpen"
                            class="absolute right-0 top-full mt-1.5 z-50 w-64 rounded-xl bg-white border border-slate-200 shadow-xl py-1 text-xs animate-in fade-in zoom-in-95 duration-100 divide-y divide-slate-100 select-none"
                        >
                            <div class="p-1">
                                <button
                                    type="button"
                                    @click="downloadHariIniDirect"
                                    class="w-full text-left p-2.5 rounded-lg hover:bg-emerald-50 text-slate-800 hover:text-emerald-900 font-bold flex items-start gap-2.5 cursor-pointer transition-colors"
                                >
                                    <div class="p-1.5 rounded-md bg-emerald-100 text-emerald-700 mt-0.5 shrink-0">
                                        <CalendarCheck class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold leading-tight text-emerald-800">Download Hari Ini Saja</p>
                                        <p class="text-[10px] text-slate-500 font-normal mt-0.5">Tanggal {{ formatTanggalIndo(todayStr) }} (1 Hari)</p>
                                    </div>
                                </button>
                            </div>
                            <div class="p-1">
                                <button
                                    type="button"
                                    @click="openExportModal('today')"
                                    class="w-full text-left px-2.5 py-1.5 rounded-md hover:bg-slate-100 text-slate-700 flex items-center justify-between cursor-pointer transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <Calendar class="h-3.5 w-3.5 text-emerald-600" />
                                        <span>Hari Ini (Buka Modal)</span>
                                    </span>
                                    <span class="text-[10px] text-slate-400">1 Hari</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openExportModal('bulanan')"
                                    class="w-full text-left px-2.5 py-1.5 rounded-md hover:bg-slate-100 text-slate-700 flex items-center justify-between cursor-pointer transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <Clock class="h-3.5 w-3.5 text-slate-400" />
                                        <span>Rekap Bulanan</span>
                                    </span>
                                    <span class="text-[10px] text-slate-400">28 Hari</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openExportModal('periodik')"
                                    class="w-full text-left px-2.5 py-1.5 rounded-md hover:bg-slate-100 text-slate-700 flex items-center justify-between cursor-pointer transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                        <span>Rekap Periodik</span>
                                    </span>
                                    <span class="text-[10px] text-slate-400">14 Hari</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openExportModal('current')"
                                    class="w-full text-left px-2.5 py-1.5 rounded-md hover:bg-slate-100 text-slate-700 flex items-center justify-between cursor-pointer transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <Filter class="h-3.5 w-3.5 text-slate-400" />
                                        <span>Sesuai Tabel / Kustom...</span>
                                    </span>
                                    <span class="text-[10px] text-slate-400">{{ dateColumns.length }} Hari</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Simpan Presensi -->
                    <button
                        type="button"
                        @click="handleSimpanPresensi"
                        :disabled="isSaving"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs font-bold shadow-xs transition-all cursor-pointer disabled:opacity-50',
                            isDirty
                                ? 'bg-primary hover:bg-primary-hover text-white ring-2 ring-primary/30'
                                : 'bg-slate-800 hover:bg-slate-900 text-white',
                        ]"
                        title="Simpan perubahan presensi ke database"
                    >
                        <Save class="h-4 w-4" />
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan' }}</span>
                        <span class="hidden md:inline" v-if="!isSaving">Presensi</span>
                    </button>
                </div>
            </div>

            <!-- ─── Control Bar: Reusable PeriodDateFilterBar & Filter Interaktif ───────── -->
            <PeriodDateFilterBar
                v-model:startDate="tanggalMulai"
                v-model:endDate="tanggalSelesai"
                v-model:mode="modeSkala"
                v-model:periodeId="selectedPeriodeId"
                v-model:isAllTime="isFilterAllTime"
                :periodes="periodes"
                @change="onDateFilterChange"
            >
                <template #right>
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <button
                            type="button"
                            @click="setSemuaHadirPenuh"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold transition-colors cursor-pointer"
                            title="Set seluruh personil aktif menjadi Hadir Penuh (H)"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
                            <span class="hidden sm:inline">Set Semua Hadir</span>
                            <span class="sm:hidden">Semua H</span>
                        </button>
                        <button
                            type="button"
                            @click="kosongkanSemua"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition-colors cursor-pointer"
                            title="Kosongkan seluruh presensi di rentang tanggal ini"
                        >
                            <X class="h-3.5 w-3.5 text-rose-500" />
                            <span class="hidden sm:inline">Kosongkan Semua</span>
                            <span class="sm:hidden">Kosong</span>
                        </button>
                        <button
                            type="button"
                            @click="resetPresensi"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition-colors cursor-pointer"
                            title="Kembalikan nilai ke data tersimpan di database"
                        >
                            <RotateCcw class="h-3.5 w-3.5 text-slate-500" />
                            <span class="hidden sm:inline">Reset</span>
                        </button>
                    </div>
                </template>
            </PeriodDateFilterBar>

            <div class="p-3.5 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3">

                <!-- Baris 3: Secondary Filters (Search, Jabatan, Status) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3 pt-1 border-t border-slate-100">
                    <div class="relative">
                        <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari personil, NIK, jabatan..."
                            class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden"
                        />
                    </div>
                    <div>
                        <select
                            v-model="selectedJabatanFilter"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden bg-white cursor-pointer"
                        >
                            <option value="all">Semua Jabatan / Divisi ({{ daftarJabatan.length }})</option>
                            <option v-for="j in daftarJabatan" :key="j" :value="j">{{ j }}</option>
                        </select>
                    </div>
                    <div>
                        <select
                            v-model="selectedStatusFilter"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden bg-white cursor-pointer"
                        >
                            <option value="all">Semua Status (Aktif & Nonaktif)</option>
                            <option value="Aktif">Hanya Personil Aktif</option>
                            <option value="Nonaktif">Hanya Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ─── KPI / Summary Cards (Responsive 2 / 3 / 6 Kolom) ───────── -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3">
                <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Personil</p>
                        <Users class="h-3.5 w-3.5 text-emerald-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-slate-900 mt-1">
                        {{ summary.total_petugas ?? 0 }} <span class="text-xs font-normal text-slate-500">org</span>
                    </p>
                    <p class="text-[10px] text-emerald-600 font-semibold mt-0.5 truncate">
                        {{ summary.total_aktif ?? 0 }} Aktif • {{ summary.total_nonaktif ?? 0 }} Non
                    </p>
                </div>

                <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Rentang Hari</p>
                        <Calendar class="h-3.5 w-3.5 text-blue-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-slate-900 mt-1">
                        {{ dateColumns.length }} <span class="text-xs font-normal text-slate-500">Hari</span>
                    </p>
                    <p class="text-[10px] text-blue-600 font-medium mt-0.5 truncate">
                        {{ modeSkala === 'bulanan' ? 'Bulanan (28 Hari)' : (modeSkala === 'periodik' ? 'Periodik (14 Hari)' : 'Custom Rentang') }}
                    </p>
                </div>

                <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Kehadiran</p>
                        <BadgePercent class="h-3.5 w-3.5 text-teal-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-teal-700 mt-1">
                        {{ kpiStats.persentaseKehadiran }}%
                    </p>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                        <div class="bg-teal-500 h-1.5 rounded-full" :style="{ width: `${Math.min(100, kpiStats.persentaseKehadiran)}%` }"></div>
                    </div>
                </div>

                <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Presensi Hadir</p>
                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-emerald-700 mt-1">
                        {{ formatRibuan(kpiStats.totalMandays) }} <span class="text-xs font-normal text-slate-500">mandays</span>
                    </p>
                    <p class="text-[10px] text-slate-500 mt-0.5 truncate">
                        H:{{ kpiStats.totalHadirPenuh }} • ½:{{ kpiStats.totalSetengahHari }}
                    </p>
                </div>

                <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Izin/Sakit/TK/Libur</p>
                        <AlertCircle class="h-3.5 w-3.5 text-amber-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-amber-700 mt-1">
                        {{ kpiStats.totalIzin + kpiStats.totalSakit + kpiStats.totalTK + kpiStats.totalLibur }} <span class="text-xs font-normal text-slate-500">hari</span>
                    </p>
                    <p class="text-[10px] text-slate-500 mt-0.5 truncate">
                        I:{{ kpiStats.totalIzin }} • S:{{ kpiStats.totalSakit }} • TK:{{ kpiStats.totalTK }} • L:{{ kpiStats.totalLibur }}
                    </p>
                </div>

                <div class="p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Total Hak Honor</p>
                        <DollarSign class="h-3.5 w-3.5 text-emerald-600" />
                    </div>
                    <p class="text-sm sm:text-base font-bold text-emerald-800 mt-1 truncate" :title="formatRupiah(kpiStats.totalHonorTerhitung)">
                        {{ formatRupiah(kpiStats.totalHonorTerhitung) }}
                    </p>
                    <p class="text-[10px] text-emerald-600 font-semibold mt-0.5 truncate">
                        Sesuai catatan riil
                    </p>
                </div>
            </div>

            <!-- ─── TABEL MATRIKS PENCATATAN PRESENSI ──────────────────────── -->
            <div class="rounded-xl bg-white border border-slate-200 shadow-2xs overflow-hidden">
                <!-- Toolbar Tabel & Keterangan Status Presensi -->
                <div class="px-3.5 sm:px-4 py-2.5 sm:py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Matriks Presensi ({{ filteredPetugas.length }} Personil)
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                            {{ dateColumns.length }} Kolom Tanggal
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 text-[11px] text-slate-600 font-medium">
                        <span class="text-slate-400 hidden xs:inline">Pilihan:</span>
                        <span class="inline-flex items-center gap-1 font-semibold text-slate-500" title="Belum Ada Catatan Presensi (Default)"><span class="w-2.5 h-2.5 rounded-sm border border-dashed border-slate-400 bg-slate-100 flex items-center justify-center text-[8px] text-slate-400">·</span>Kosong</span>
                        <span class="inline-flex items-center gap-1 font-bold text-emerald-700" title="Hadir Penuh (Gaji 100%)"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>H (Hadir)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-teal-700" title="Hadir Setengah Hari (Gaji 50%)"><span class="w-2 h-2 rounded-full bg-teal-500"></span>½ (Setengah)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-slate-700" title="Libur Operasional"><span class="w-2 h-2 rounded-full bg-slate-500"></span>L (Libur)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-amber-700" title="Izin"><span class="w-2 h-2 rounded-full bg-amber-500"></span>I (Izin)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-blue-700" title="Sakit"><span class="w-2 h-2 rounded-full bg-blue-500"></span>S (Sakit)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-rose-700" title="Tanpa Keterangan"><span class="w-2 h-2 rounded-full bg-rose-500"></span>TK (Tanpa Ket)</span>
                    </div>
                </div>

                <!-- Kontainer Tabel dengan Horizontal Scroll Presisi -->
                <div class="overflow-x-auto relative">
                    <table class="w-full text-left text-xs border-collapse">
                        <!-- Table Head: Baris 1 Visualisasi Rentang Terpilih -->
                        <thead class="sticky top-0 z-20">
                            <!-- Strip Indikator Rentang Tanggal Terpilih Menyatu di Header -->
                            <tr class="border-b border-emerald-700/60 text-white">
                                <th colspan="3" class="py-1 px-3 bg-slate-800 text-slate-200 text-[10px] font-bold uppercase tracking-wider sticky left-0 z-30 border-r border-slate-700">
                                    Informasi Personil
                                </th>
                                <th
                                    :colspan="dateColumns.length"
                                    class="py-1 px-2 text-center bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-800 text-[11px] font-black tracking-wider uppercase border-r border-emerald-600 shadow-inner"
                                >
                                    Rentang Kalender Terpilih: {{ formatTanggalIndo(tanggalMulai) }} – {{ formatTanggalIndo(tanggalSelesai) }} ({{ dateColumns.length }} Hari Kerja)
                                </th>
                                <th colspan="10" class="py-1 px-2 text-center bg-slate-800 text-slate-200 text-[10px] font-bold uppercase tracking-wider">
                                    Rekapitulasi Akumulasi & Hak Honor
                                </th>
                            </tr>

                            <!-- Baris 2: Kolom Header Utama -->
                            <tr class="bg-slate-100 text-slate-700 border-b border-slate-200">
                                <!-- Kolom Tetap Kiri: No, Personil & Divisi, Tarif -->
                                <th class="py-2.5 px-2.5 w-9 text-center sticky left-0 bg-slate-100 z-30 border-r border-slate-200 font-bold">
                                    No
                                </th>
                                <th class="py-2.5 px-3 min-w-[170px] sm:min-w-[210px] sticky left-9 bg-slate-100 z-30 border-r border-slate-200 font-bold">
                                    Personil & Divisi
                                </th>
                                <th class="py-2.5 px-3 text-right min-w-[100px] border-r border-slate-200 font-bold">
                                    Tarif Harian
                                </th>

                                <!-- Kolom Matriks Tanggal (Warna Rentang Menyatu & Highlight Hari Ini) -->
                                <th
                                    v-for="dt in dateColumns"
                                    :key="dt.dateStr"
                                    :class="[
                                        'py-2 px-1 text-center min-w-[38px] max-w-[42px] border-r select-none group transition-colors',
                                        dt.dateStr === todayStr
                                            ? 'bg-emerald-600 text-white border-emerald-700 shadow-xs'
                                            : (dt.isWeekend ? 'bg-amber-50/70 border-amber-200/60 text-slate-700' : 'bg-emerald-50/50 border-emerald-200/50 text-slate-700'),
                                    ]"
                                    :title="`${dt.dayName}, ${dt.dateStr}${dt.dateStr === todayStr ? ' (HARI INI)' : ''} (Klik tanggal untuk set Hadir / Kosongkan)`"
                                >
                                    <div class="flex flex-col items-center">
                                        <span
                                            :class="[
                                                'text-[9px] font-bold uppercase tracking-tight',
                                                dt.dateStr === todayStr
                                                    ? 'text-emerald-100 font-black'
                                                    : (dt.isSunday ? 'text-rose-600' : 'text-slate-500'),
                                            ]"
                                        >
                                            {{ dt.dayName }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="toggleKolomStatus(dt.dateStr)"
                                            :class="[
                                                'mt-0.5 px-1 py-0.5 rounded text-[11px] font-black transition-colors cursor-pointer',
                                                dt.dateStr === todayStr
                                                    ? 'bg-white text-emerald-800 hover:bg-emerald-100 ring-2 ring-emerald-300'
                                                    : 'hover:bg-emerald-200/70 hover:text-emerald-900',
                                            ]"
                                            :title="`Klik untuk set Hadir (H) atau kosongkan kolom tanggal ${dt.dateStr}`"
                                        >
                                            {{ dt.dayNum }}
                                        </button>
                                        <span v-if="dt.dateStr === todayStr" class="text-[7.5px] font-extrabold uppercase tracking-tighter text-emerald-200 leading-none mt-0.5">
                                            Hari Ini
                                        </span>
                                    </div>
                                </th>

                                <!-- Kolom Rekap Kanan -->
                                <th class="py-2.5 px-2 text-center w-8 sm:w-9 bg-emerald-50 text-emerald-800 font-bold border-r border-slate-200" title="Hadir Penuh (H)">
                                    H
                                </th>
                                <th class="py-2.5 px-2 text-center w-8 sm:w-9 bg-teal-50 text-teal-800 font-bold border-r border-slate-200" title="Hadir Setengah Hari (½)">
                                    ½
                                </th>
                                <th class="py-2.5 px-2 text-center w-8 sm:w-9 bg-slate-100 text-slate-700 font-bold border-r border-slate-200" title="Libur Operasional (L)">
                                    L
                                </th>
                                <th class="py-2.5 px-2 text-center w-8 sm:w-9 bg-amber-50 text-amber-800 font-bold border-r border-slate-200" title="Total Izin (I)">
                                    I
                                </th>
                                <th class="py-2.5 px-2 text-center w-8 sm:w-9 bg-blue-50 text-blue-800 font-bold border-r border-slate-200" title="Total Sakit (S)">
                                    S
                                </th>
                                <th class="py-2.5 px-2 text-center w-8 sm:w-9 bg-rose-50 text-rose-800 font-bold border-r border-slate-200" title="Tanpa Keterangan (TK)">
                                    TK
                                </th>
                                <th class="py-2.5 px-2 text-center min-w-[50px] bg-emerald-100/60 text-emerald-900 font-black border-r border-slate-200" title="Total Hari Kerja Terhitung (H + 0.5*½)">
                                    Mandays
                                </th>
                                <th class="py-2.5 px-2 text-center min-w-[55px] border-r border-slate-200 font-bold">
                                    %
                                </th>
                                <th class="py-2.5 px-3 text-right min-w-[120px] font-bold text-slate-800">
                                    Total Hak Honor
                                </th>
                                <th class="py-2.5 px-2 text-center w-10">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(p, idx) in filteredPetugas"
                                :key="p.id"
                                :class="[
                                    'transition-colors',
                                    p.status === 'Nonaktif' ? 'bg-slate-50/70 opacity-60' : 'hover:bg-slate-50/80',
                                ]"
                            >
                                <!-- No (Sticky Left) -->
                                <td class="py-2.5 px-2.5 text-center font-medium text-slate-500 sticky left-0 bg-white z-10 border-r border-slate-200 text-xs">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Personil & Divisi (Sticky Left - Tanpa Logo Profile) -->
                                <td class="py-2.5 px-3 sticky left-9 bg-white z-10 border-r border-slate-200">
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-900 truncate leading-snug text-xs">
                                            {{ p.nama }}
                                        </p>
                                        <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                                            <span :class="['px-1.5 py-0.5 rounded text-[9px] border leading-none font-medium', getJabatanBadgeClass(p.jabatan)]">
                                                {{ p.jabatan }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Tarif Harian -->
                                <td class="py-2 px-3 text-right font-medium text-slate-800 border-r border-slate-200">
                                    <p class="font-semibold text-slate-900 text-xs">
                                        {{ formatRupiah((parseInt(p.gaji_harian_bgn) || 0) + (parseInt(p.bonus_harian_mitra) || 0)) }}
                                    </p>
                                    <p class="text-[9px] text-slate-500">
                                        BGN {{ formatRibuan(p.gaji_harian_bgn) }}
                                    </p>
                                </td>

                                <!-- Sel Matriks Interaktif per Tanggal -->
                                <td
                                    v-for="dt in dateColumns"
                                    :key="dt.dateStr"
                                    :class="[
                                        'py-1.5 px-0.5 text-center border-r border-slate-100/80',
                                        dt.dateStr === todayStr ? 'bg-emerald-50/40 ring-1 ring-emerald-300/40' : (dt.isWeekend ? 'bg-amber-50/20' : ''),
                                    ]"
                                >
                                    <!-- Tombol Badge Interaktif (Klik untuk switch status) -->
                                    <button
                                        v-if="p.status === 'Aktif'"
                                        type="button"
                                        @click="cycleStatus(p.id, dt.dateStr, true)"
                                        :class="[
                                            'w-7 h-7 rounded-md text-[11px] font-black transition-transform active:scale-90 flex items-center justify-center mx-auto cursor-pointer select-none',
                                            getStatusBadgeStyle(matrixPresensi[p.id]?.[dt.dateStr] || ''),
                                        ]"
                                        :title="`${p.nama} (${dt.dayName}, ${dt.dateStr}): ${getStatusTitle(matrixPresensi[p.id]?.[dt.dateStr] || '')} (Klik untuk pilih status)`"
                                    >
                                        {{ getStatusLabel(matrixPresensi[p.id]?.[dt.dateStr] || '') }}
                                    </button>
                                    <span v-else class="text-slate-300 text-[11px]">-</span>
                                </td>

                                <!-- Rekap Hadir Penuh (H) -->
                                <td class="py-2 px-2 text-center font-bold text-emerald-800 bg-emerald-50/60 border-r border-slate-200">
                                    {{ computedPetugasStats[p.id]?.hadir ?? 0 }}
                                </td>

                                <!-- Rekap Hadir Setengah Hari (½) -->
                                <td class="py-2 px-2 text-center font-bold text-teal-800 bg-teal-50/60 border-r border-slate-200">
                                    {{ computedPetugasStats[p.id]?.setengahHari ?? 0 }}
                                </td>

                                <!-- Rekap Libur (L) -->
                                <td class="py-2 px-2 text-center font-bold text-slate-700 bg-slate-50 border-r border-slate-200">
                                    {{ computedPetugasStats[p.id]?.libur ?? 0 }}
                                </td>

                                <!-- Rekap Izin (I) -->
                                <td class="py-2 px-2 text-center font-bold text-amber-800 bg-amber-50/60 border-r border-slate-200">
                                    {{ computedPetugasStats[p.id]?.izin ?? 0 }}
                                </td>

                                <!-- Rekap Sakit (S) -->
                                <td class="py-2 px-2 text-center font-bold text-blue-800 bg-blue-50/60 border-r border-slate-200">
                                    {{ computedPetugasStats[p.id]?.sakit ?? 0 }}
                                </td>

                                <!-- Rekap Tanpa Keterangan (TK) -->
                                <td class="py-2 px-2 text-center font-bold text-rose-800 bg-rose-50/60 border-r border-slate-200">
                                    {{ computedPetugasStats[p.id]?.tanpaKeterangan ?? 0 }}
                                </td>

                                <!-- Mandays (Hari Kerja Efektif) -->
                                <td class="py-2 px-2 text-center font-black text-emerald-950 bg-emerald-100/50 border-r border-slate-200">
                                    {{ computedPetugasStats[p.id]?.mandays ?? 0 }}
                                </td>

                                <!-- % Hadir -->
                                <td class="py-2 px-2 text-center border-r border-slate-200">
                                    <span
                                        :class="[
                                            'px-1.5 py-0.5 rounded text-[10px] font-bold',
                                            (computedPetugasStats[p.id]?.rate ?? 0) >= 90
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : (computedPetugasStats[p.id]?.rate ?? 0) >= 70
                                                ? 'bg-amber-100 text-amber-800'
                                                : 'bg-rose-100 text-rose-800',
                                        ]"
                                    >
                                        {{ computedPetugasStats[p.id]?.rate ?? 0 }}%
                                    </span>
                                </td>

                                <!-- Total Hak Honor -->
                                <td class="py-2 px-3 text-right font-bold text-emerald-900 border-r border-slate-200">
                                    {{ formatRupiah(computedPetugasStats[p.id]?.totalHonor ?? 0) }}
                                </td>

                                <!-- Aksi Detail Modal -->
                                <td class="py-2 px-2 text-center">
                                    <button
                                        type="button"
                                        @click="openDetailModal(p)"
                                        class="p-1 rounded-md text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                                        title="Rincian Slip Honor Presensi Petugas"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="filteredPetugas.length === 0">
                                <td :colspan="10 + dateColumns.length" class="py-10 text-center text-slate-500">
                                    <Users class="h-8 w-8 mx-auto text-slate-300 mb-2" />
                                    <p class="text-xs font-semibold">Tidak ada data petugas yang cocok dengan filter.</p>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Table Footer: Total Row -->
                        <tfoot class="bg-slate-100/90 text-slate-800 border-t-2 border-slate-300 font-bold sticky bottom-0 z-20">
                            <tr>
                                <td colspan="3" class="py-3 px-3 text-right uppercase tracking-wider text-xs border-r border-slate-300">
                                    Total Mandays Hadir:
                                </td>
                                <!-- Total Hadir per Tanggal -->
                                <td
                                    v-for="dt in dateColumns"
                                    :key="'tot-' + dt.dateStr"
                                    :class="[
                                        'py-2.5 px-0.5 text-center text-[11px] font-black border-r border-slate-200',
                                        dt.dateStr === todayStr ? 'bg-emerald-100 text-emerald-900' : 'text-emerald-800',
                                    ]"
                                    :title="`Total Hadir di tanggal ${dt.dateStr}: ${hadirPerTanggal[dt.dateStr]} orang`"
                                >
                                    {{ hadirPerTanggal[dt.dateStr] }}
                                </td>
                                <!-- Grand Totals Kanan -->
                                <td class="py-3 px-2 text-center text-emerald-800 bg-emerald-100/70 border-r border-slate-300 text-xs">
                                    {{ kpiStats.totalHadirPenuh }}
                                </td>
                                <td class="py-3 px-2 text-center text-teal-800 bg-teal-100/70 border-r border-slate-300 text-xs">
                                    {{ kpiStats.totalSetengahHari }}
                                </td>
                                <td class="py-3 px-2 text-center text-slate-700 bg-slate-200/70 border-r border-slate-300 text-xs">
                                    {{ kpiStats.totalLibur }}
                                </td>
                                <td class="py-3 px-2 text-center text-amber-800 bg-amber-100/70 border-r border-slate-300 text-xs">
                                    {{ kpiStats.totalIzin }}
                                </td>
                                <td class="py-3 px-2 text-center text-blue-800 bg-blue-100/70 border-r border-slate-300 text-xs">
                                    {{ kpiStats.totalSakit }}
                                </td>
                                <td class="py-3 px-2 text-center text-rose-800 bg-rose-100/70 border-r border-slate-300 text-xs">
                                    {{ kpiStats.totalTK }}
                                </td>
                                <td class="py-3 px-2 text-center text-emerald-950 bg-emerald-200/70 border-r border-slate-300 text-xs font-black">
                                    {{ kpiStats.totalMandays }}
                                </td>
                                <td class="py-3 px-2 text-center text-xs border-r border-slate-300">
                                    {{ kpiStats.persentaseKehadiran }}%
                                </td>
                                <td class="py-3 px-3 text-right text-emerald-900 text-xs font-black">
                                    {{ formatRupiah(kpiStats.totalHonorTerhitung) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Footer Summary Bar -->
                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <div class="text-slate-600">
                        Menampilkan <span class="font-bold text-slate-900">{{ filteredPetugas.length }}</span> personil • Rentang <span class="font-bold text-slate-900">{{ dateColumns.length }}</span> Hari Kerja
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="handleSimpanPresensi"
                            :disabled="isSaving"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition-colors cursor-pointer shadow-xs disabled:opacity-50"
                        >
                            <Save class="h-3.5 w-3.5" />
                            <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Presensi' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── MODAL DOWNLOAD EXCEL (Bulanan / Periodik & Rentang Tanggal) ─── -->
            <Teleport to="body">
                <div
                    v-if="isExportModalOpen"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                    style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
                >
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <FileSpreadsheet class="h-5 w-5 text-emerald-200" />
                                <div>
                                    <h3 class="text-sm font-bold">Download File Excel Rekap Presensi & Gaji</h3>
                                    <p class="text-[11px] text-emerald-100">Pilih skala siklus kerja dan rentang tanggal export</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeExportModal"
                                class="p-1 rounded-lg text-emerald-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="p-5 space-y-4">
                            <!-- Pilihan Format Skala -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Pilihan Format Siklus
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <label
                                        :class="[
                                            'flex flex-col p-2.5 rounded-xl border cursor-pointer transition-all',
                                            exportOption === 'today'
                                                ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500'
                                                : 'border-slate-200 hover:bg-slate-50',
                                        ]"
                                    >
                                        <input type="radio" value="today" v-model="exportOption" class="sr-only" />
                                        <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Hari Ini
                                        </span>
                                        <span class="text-[10px] text-slate-500">1 Hari Saja</span>
                                    </label>

                                    <label
                                        :class="[
                                            'flex flex-col p-2.5 rounded-xl border cursor-pointer transition-all',
                                            exportOption === 'bulanan'
                                                ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500'
                                                : 'border-slate-200 hover:bg-slate-50',
                                        ]"
                                    >
                                        <input type="radio" value="bulanan" v-model="exportOption" class="sr-only" />
                                        <span class="text-xs font-bold text-slate-900">Bulanan</span>
                                        <span class="text-[10px] text-slate-500">28 Hari Kerja</span>
                                    </label>

                                    <label
                                        :class="[
                                            'flex flex-col p-2.5 rounded-xl border cursor-pointer transition-all',
                                            exportOption === 'periodik'
                                                ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500'
                                                : 'border-slate-200 hover:bg-slate-50',
                                        ]"
                                    >
                                        <input type="radio" value="periodik" v-model="exportOption" class="sr-only" />
                                        <span class="text-xs font-bold text-slate-900">Periodik</span>
                                        <span class="text-[10px] text-slate-500">14 Hari Kerja</span>
                                    </label>

                                    <label
                                        :class="[
                                            'flex flex-col p-2.5 rounded-xl border cursor-pointer transition-all',
                                            exportOption === 'current'
                                                ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500'
                                                : 'border-slate-200 hover:bg-slate-50',
                                        ]"
                                    >
                                        <input type="radio" value="current" v-model="exportOption" class="sr-only" />
                                        <span class="text-xs font-bold text-slate-900">Tabel Aktif</span>
                                        <span class="text-[10px] text-slate-500">{{ dateColumns.length }} Hari</span>
                                    </label>
                                </div>
                                <div v-if="exportOption === 'today'" class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
                                    <CalendarCheck class="h-4 w-4 text-emerald-600 shrink-0" />
                                    <span>Mengekspor daftar presensi personil khusus untuk <strong>Hari Ini ({{ formatTanggalIndo(exportStartDate) }})</strong>.</span>
                                </div>
                            </div>

                            <!-- Rentang Tanggal Export -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Rentang Tanggal Export
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <span class="text-[11px] text-slate-500">Dari Tanggal:</span>
                                        <input
                                            v-model="exportStartDate"
                                            type="date"
                                            class="mt-1 w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden"
                                        />
                                    </div>
                                    <div>
                                        <span class="text-[11px] text-slate-500">Sampai Tanggal:</span>
                                        <input
                                            v-model="exportEndDate"
                                            type="date"
                                            class="mt-1 w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-hidden"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Info Preview Dokumen -->
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1.5">
                                <div class="flex items-center justify-between text-slate-700 font-semibold">
                                    <span>Unit SPPG:</span>
                                    <span class="text-slate-900 font-bold">{{ unitSppg?.nama ?? 'SPPG Buleleng Sukasada Tegallinggah' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-700">
                                    <span>Sheet 1:</span>
                                    <span class="text-emerald-700 font-bold">Rekap Presensi & Gaji (Kop & Tanda Tangan)</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-700">
                                    <span>Sheet 2:</span>
                                    <span class="text-teal-700 font-bold">Ringkasan per Divisi</span>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                @click="closeExportModal"
                                class="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="handleExecuteExport"
                                :disabled="isExporting"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <FileSpreadsheet class="h-4 w-4" />
                                <span>{{ isExporting ? 'Mengekspor File...' : 'Download File Excel (.xlsx)' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- ─── MODAL DETAIL SLIP PRESENSI PETUGAS ─────────────────────── -->
            <Teleport to="body">
                <div
                    v-if="isDetailModalOpen && detailPetugas"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                    style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
                >
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <CalendarCheck class="h-5 w-5 text-emerald-200" />
                                <div>
                                    <h3 class="text-sm font-bold">Rincian Presensi & Hak Honorarium</h3>
                                    <p class="text-[11px] text-emerald-100">Slip perhitungan per personil kerja operasional</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeDetailModal"
                                class="p-1 rounded-lg text-emerald-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                            <!-- Info Petugas -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <h4 class="font-bold text-slate-900 text-base leading-tight">{{ detailPetugas.nama }}</h4>
                                <p class="text-xs text-slate-500 font-mono mt-0.5">NIK: {{ detailPetugas.nik }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-semibold border', getJabatanBadgeClass(detailPetugas.jabatan)]">
                                        {{ detailPetugas.jabatan }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 font-mono">
                                        Jam Kerja: {{ detailPetugas.jam_kerja }}
                                    </span>
                                </div>
                            </div>

                            <!-- Komponen Honorarium Harian -->
                            <div class="p-3.5 rounded-xl border border-slate-200 space-y-2">
                                <p class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                    Komponen Honor Harian
                                </p>
                                <div class="flex justify-between text-xs text-slate-600 py-1 border-b border-slate-100">
                                    <span>Gaji Harian BGN:</span>
                                    <span class="font-bold text-slate-900">{{ formatRupiah(detailPetugas.gaji_harian_bgn) }}</span>
                                </div>
                                <div class="flex justify-between text-xs text-slate-600 py-1 border-b border-slate-100">
                                    <span>Bonus Harian Mitra:</span>
                                    <span class="font-bold text-slate-900">{{ formatRupiah(detailPetugas.bonus_harian_mitra) }}</span>
                                </div>
                                <div class="flex justify-between text-xs font-bold text-slate-800 pt-1">
                                    <span>Total Honor per Hari:</span>
                                    <span class="text-emerald-700">
                                        {{ formatRupiah((parseInt(detailPetugas.gaji_harian_bgn) || 0) + (parseInt(detailPetugas.bonus_harian_mitra) || 0)) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Rekapitulasi Presensi Periode Ini -->
                            <div class="p-3.5 rounded-xl border border-slate-200 space-y-2.5">
                                <p class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                    Rekap Presensi (Rentang {{ dateColumns.length }} Hari)
                                </p>
                                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center">
                                    <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                                        <p class="text-[10px] text-emerald-700 font-bold uppercase">Hadir (H)</p>
                                        <p class="text-base font-bold text-emerald-900">{{ computedPetugasStats[detailPetugas.id]?.hadir ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-teal-50 border border-teal-200" title="Gaji Harian Dipotong Setengah">
                                        <p class="text-[10px] text-teal-700 font-bold uppercase">½ Hari</p>
                                        <p class="text-base font-bold text-teal-900">{{ computedPetugasStats[detailPetugas.id]?.setengahHari ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200">
                                        <p class="text-[10px] text-slate-700 font-bold uppercase">Libur (L)</p>
                                        <p class="text-base font-bold text-slate-900">{{ computedPetugasStats[detailPetugas.id]?.libur ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-amber-50 border border-amber-200">
                                        <p class="text-[10px] text-amber-700 font-bold uppercase">Izin (I)</p>
                                        <p class="text-base font-bold text-amber-900">{{ computedPetugasStats[detailPetugas.id]?.izin ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-blue-50 border border-blue-200">
                                        <p class="text-[10px] text-blue-700 font-bold uppercase">Sakit (S)</p>
                                        <p class="text-base font-bold text-blue-900">{{ computedPetugasStats[detailPetugas.id]?.sakit ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-rose-50 border border-rose-200">
                                        <p class="text-[10px] text-rose-700 font-bold uppercase">Tanpa Ket (TK)</p>
                                        <p class="text-base font-bold text-rose-900">{{ computedPetugasStats[detailPetugas.id]?.tanpaKeterangan ?? 0 }}</p>
                                    </div>
                                </div>

                                <!-- Total Hari Kerja Terhitung -->
                                <div class="p-2.5 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-between text-xs">
                                    <span class="text-slate-600 font-medium">Hari Kerja Terhitung (Mandays):</span>
                                    <span class="font-bold text-slate-900">
                                        {{ computedPetugasStats[detailPetugas.id]?.hadir ?? 0 }} penuh + {{ computedPetugasStats[detailPetugas.id]?.setengahHari ?? 0 }} (½ hari) =
                                        <strong class="text-emerald-700 text-sm">{{ computedPetugasStats[detailPetugas.id]?.mandays ?? 0 }} mandays</strong>
                                    </span>
                                </div>

                                <!-- Total Penerimaan Bersih -->
                                <div class="p-3.5 rounded-xl bg-emerald-100/70 border border-emerald-300 mt-2 flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Total Hak Honor Diterima</p>
                                        <p class="text-[11px] text-emerald-700 mt-0.5">
                                            {{ computedPetugasStats[detailPetugas.id]?.mandays ?? 0 }} mandays x {{ formatRibuan(computedPetugasStats[detailPetugas.id]?.honorPerHari ?? 0) }}
                                        </p>
                                    </div>
                                    <p class="text-xl font-black text-emerald-950">
                                        {{ formatRupiah(computedPetugasStats[detailPetugas.id]?.totalHonor ?? 0) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                            <button
                                type="button"
                                @click="closeDetailModal"
                                class="px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>
