<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Coins,
    Users,
    Search,
    CheckCircle2,
    CalendarCheck,
    Download,
    Eye,
    X,
    Filter,
    Layers,
    FileSpreadsheet,
    Building2,
    CreditCard,
    DollarSign,
    Sparkles,
    Save,
    AlertCircle,
    Clock,
    ChevronDown,
    BadgePercent,
    ShieldCheck,
    FileText,
    Loader2,
} from "lucide-vue-next";
import { 
    downloadRekapBonusExcel, 
    downloadBukuTandaTerimaBonusHarianExcel, 
    downloadSlipBonusPdf, 
    formatTanggalIndo 
} from "@/Services/exportPetugasHelper";
import DateRangePicker from "@/Components/DateRangePicker.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";

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
    initialPresensiDetailsMap: { type: Object, default: () => ({}) },
});

const page = usePage();

// ─── Tanggal Hari Ini ────────────────────────────────────────────────────────
const todayStr = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
});

function getDefaultEndDate(mode, startStr) {
    const base = startStr ? new Date(startStr + "T00:00:00") : new Date();
    const daysToAdd = mode === "periodik" ? 13 : 27;
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

// ─── Filter & State Tanggal ──────────────────────────────────────────────────
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
        route("petugas.rekap-bonus"),
        {
            tanggal_mulai: filter.start,
            tanggal_selesai: filter.end,
            mode: filter.mode,
            periode_id: filter.periodeId,
        },
        { preserveState: true, preserveScroll: true }
    );
}

// ─── Dynamic Date Columns ────────────────────────────────────────────────────
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

// ─── State Matriks Presensi Bonus (Otomatis Terisi dari Rekap Kehadiran) ───────
const matrixPresensi = ref({});
const isDirty = ref(false);
const isSaving = ref(false);

function initMatrixPresensi() {
    const map = {};
    const dbMap = props.initialPresensiMap || {};

    props.petugas.forEach((p) => {
        map[p.id] = {};
        const pDb = dbMap[p.id] || {};

        dateColumns.value.forEach((dt) => {
            if (pDb[dt.dateStr]) {
                map[p.id][dt.dateStr] = pDb[dt.dateStr];
            } else {
                map[p.id][dt.dateStr] = ""; // Default kosong
            }
        });
    });

    matrixPresensi.value = map;
    isDirty.value = false;
}

watch(
    () => [props.initialPresensiMap, props.petugas],
    () => {
        initMatrixPresensi();
    },
    { deep: true }
);

onMounted(() => {
    initMatrixPresensi();
});

// Set status satu sel
function setCellStatus(petugasId, dateStr, newStatus) {
    if (!matrixPresensi.value[petugasId]) {
        matrixPresensi.value[petugasId] = {};
    }
    matrixPresensi.value[petugasId][dateStr] = newStatus;
    isDirty.value = true;
}

// Cycle status: "" -> H -> H2 (Hadir ½ Hari) -> L -> I -> S -> TK -> ""
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
    else if (current === "TK") next = "";
    else next = "";

    setCellStatus(petugasId, dateStr, next);
}

function toggleKolomStatus(dateStr) {
    const activePetugas = props.petugas.filter((p) => p.status === "Aktif");
    if (activePetugas.length === 0) return;

    const firstStatus = matrixPresensi.value[activePetugas[0].id]?.[dateStr] || "";
    const isAllSame = activePetugas.every(
        (p) => (matrixPresensi.value[p.id]?.[dateStr] || "") === firstStatus
    );

    let nextSt = "H";
    if (isAllSame) {
        if (firstStatus === "") nextSt = "H";
        else if (firstStatus === "H") nextSt = "H2";
        else if (firstStatus === "H2") nextSt = "L";
        else if (firstStatus === "L") nextSt = "I";
        else if (firstStatus === "I") nextSt = "S";
        else if (firstStatus === "S") nextSt = "TK";
        else if (firstStatus === "TK") nextSt = "";
        else nextSt = "H";
    } else {
        nextSt = "H";
    }

    activePetugas.forEach((p) => {
        if (!matrixPresensi.value[p.id]) matrixPresensi.value[p.id] = {};
        matrixPresensi.value[p.id][dateStr] = nextSt;
    });

    isDirty.value = true;
    showToast(
        nextSt !== ""
            ? `Seluruh personil aktif pada ${formatTanggalIndo(dateStr)} diset: ${nextSt}`
            : `Kolom tanggal ${formatTanggalIndo(dateStr)} dikosongkan`
    );
}

function resetPresensi() {
    initMatrixPresensi();
    showToast("Data presensi bonus dikembalikan ke data tersimpan");
}

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
    showToast("Seluruh catatan presensi bonus dikosongkan");
}

// ─── Simpan Bonus ke Database ────────────────────────────────────────────────
function handleSimpanBonus() {
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
                    showToast("Data presensi & bonus petugas berhasil disimpan ke database!");
                },
                onError: (errors) => {
                    console.error("Gagal simpan bonus:", errors);
                    isSaving.value = false;
                    showToast("Gagal menyimpan data bonus: Silakan periksa kembali data.");
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            }
        );
    } catch (e) {
        console.error("Error simpan bonus:", e);
        showToast("Terjadi kesalahan saat menyimpan data bonus.");
        isSaving.value = false;
    }
}

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

// ─── Formatters & Badges ─────────────────────────────────────────────────────
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
    if (j.includes("pengawas gizi") || j.includes("pengawas keuangan") || j.includes("plog") || j.includes("plok")) return "bg-teal-50 text-teal-700 border-teal-200 font-semibold";
    if (j.includes("lapangan")) return "bg-blue-50 text-blue-700 border-blue-200";
    if (j === "koordinator" || j.startsWith("koordinator ")) return "bg-sky-100 text-sky-800 border-sky-300 font-semibold";
    if (j.includes("chef")) return "bg-amber-50 text-amber-700 border-amber-200";
    if (j.includes("pengolahan") || j.includes("masak")) return "bg-orange-50 text-orange-700 border-orange-200";
    if (j.includes("persiapan")) return "bg-yellow-50 text-yellow-800 border-yellow-200";
    if (j.includes("pemorsian")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
    if (j.includes("pengemudi") || j.includes("distribusi")) return "bg-cyan-50 text-cyan-800 border-cyan-200";
    if (j.includes("cuci")) return "bg-teal-50 text-teal-700 border-teal-200";
    if (j.includes("admin")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
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
    if (status === "H") return "Hadir Penuh (Bonus 100%)";
    if (status === "H2") return "Hadir Setengah Hari (Bonus 50%)";
    if (status === "L") return "Libur Operasional";
    if (status === "I") return "Izin";
    if (status === "S") return "Sakit";
    if (status === "TK") return "Tanpa Keterangan (TK)";
    return "Belum ada catatan (Klik untuk ubah)";
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

        if (selectedJabatanFilter.value !== "all") {
            const jabStr = (p.jabatan || "").toLowerCase();
            const filterVal = selectedJabatanFilter.value.toLowerCase();
            if (!jabStr.includes(filterVal)) return false;
        }

        if (selectedStatusFilter.value !== "all" && p.status !== selectedStatusFilter.value) {
            return false;
        }

        return true;
    });
});

// ─── Perhitungan Statistik Bonus per Petugas & KPI ─────────────────────────────
const computedBonusStats = computed(() => {
    const stats = {};
    const totalDays = dateColumns.value.length || 1;

    props.petugas.forEach((p) => {
        const pAtt = matrixPresensi.value[p.id] || {};
        let h = 0, h2 = 0, l = 0, i = 0, s = 0, tk = 0, kosong = 0;
        let totalBonus = 0;
        const dailyRates = [];
        const defaultBonus = parseInt(p.bonus_harian_mitra) || 0;

        dateColumns.value.forEach((dt) => {
            const st = pAtt[dt.dateStr] || "";
            const snap = props.initialPresensiDetailsMap?.[p.id]?.[dt.dateStr];
            const dailyBonus = (snap && snap.bonus_harian_mitra !== undefined && snap.bonus_harian_mitra !== null)
                ? Number(snap.bonus_harian_mitra)
                : defaultBonus;

            if (st === "H") {
                h++;
                totalBonus += dailyBonus * 1.0;
                dailyRates.push(dailyBonus);
            } else if (st === "H2") {
                h2++;
                totalBonus += dailyBonus * 0.5;
                dailyRates.push(dailyBonus);
            } else if (st === "L") l++;
            else if (st === "I") i++;
            else if (st === "S") s++;
            else if (st === "TK") tk++;
            else kosong++;
        });

        let minBonus = null;
        let maxBonus = null;
        let isBonusVarying = false;
        if (dailyRates.length > 0) {
            minBonus = Math.min(...dailyRates);
            maxBonus = Math.max(...dailyRates);
            isBonusVarying = minBonus !== maxBonus;
        }

        const mandays = h + (h2 * 0.5);
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
            totalBonus: Math.round(totalBonus),
            bonusPerHari: defaultBonus,
            isBonusVarying,
            minBonus,
            maxBonus,
        };
    });

    return stats;
});

const kpiStats = computed(() => {
    let totalPenerima = 0;
    let totalMandays = 0;
    let grandTotalBonus = 0;
    let totalBonusHarianRate = 0;
    let totalHadirPenuh = 0;
    let totalSetengahHari = 0;
    let totalLibur = 0;
    let totalIzin = 0;
    let totalSakit = 0;
    let totalTK = 0;
    let totalTargetMandays = 0;
    const totalDays = dateColumns.value.length || 0;

    props.petugas.forEach((p) => {
        const st = computedBonusStats.value[p.id] || {
            mandays: 0,
            totalBonus: 0,
            bonusPerHari: 0,
            hadir: 0,
            setengahHari: 0,
            libur: 0,
            izin: 0,
            sakit: 0,
            tanpaKeterangan: 0,
        };
        if (p.status === "Aktif" || st.mandays > 0) {
            if (st.mandays > 0) totalPenerima++;
            totalMandays += st.mandays;
            grandTotalBonus += st.totalBonus;
            totalBonusHarianRate += st.bonusPerHari;
            totalHadirPenuh += st.hadir;
            totalSetengahHari += st.setengahHari;
            totalLibur += st.libur;
            totalIzin += st.izin;
            totalSakit += st.sakit;
            totalTK += st.tanpaKeterangan;
            totalTargetMandays += totalDays;
        }
    });

    const persentaseKehadiran = totalTargetMandays > 0
        ? Math.round((totalMandays / totalTargetMandays) * 100)
        : 0;

    return {
        totalPenerima,
        totalMandays,
        grandTotalBonus,
        totalBonusHarianRate,
        totalHadirPenuh,
        totalSetengahHari,
        totalLibur,
        totalIzin,
        totalSakit,
        totalTK,
        persentaseKehadiran,
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

// ─── State Expand Rincian Harian Petugas ─────────────────────────────────────
const expandedPetugasIds = ref(new Set());

function toggleExpandPetugas(id) {
    if (expandedPetugasIds.value.has(id)) {
        expandedPetugasIds.value.delete(id);
    } else {
        expandedPetugasIds.value.add(id);
    }
}

function isPetugasExpanded(id) {
    return expandedPetugasIds.value.has(id);
}

function toggleAllExpand() {
    if (expandedPetugasIds.value.size === props.petugas.length) {
        expandedPetugasIds.value.clear();
    } else {
        props.petugas.forEach((p) => expandedPetugasIds.value.add(p.id));
    }
}

function getDailySnapshot(petugasId, dateStr) {
    return props.initialPresensiDetailsMap?.[petugasId]?.[dateStr] || null;
}

function getDailyBonus(p, dateStr) {
    const snap = getDailySnapshot(p.id, dateStr);
    if (snap && snap.bonus_harian_mitra !== undefined && snap.bonus_harian_mitra !== null) {
        return Number(snap.bonus_harian_mitra);
    }
    return Number(p.bonus_harian_mitra) || 0;
}

function getDailyJabatan(p, dateStr) {
    const snap = getDailySnapshot(p.id, dateStr);
    return snap?.jabatan || p.jabatan || "-";
}

function getDailyHonorBonus(p, dateStr) {
    const st = matrixPresensi.value[p.id]?.[dateStr] || "";
    const b = getDailyBonus(p, dateStr);
    if (st === "H") return b * 1.0;
    if (st === "H2") return b * 0.5;
    return 0;
}

// ─── Tampilan Tab: Matriks vs Log Rincian Harian ─────────────────────────────
const activeViewTab = ref("matriks"); // 'matriks' | 'harian'
const selectedLogDateFilter = ref("all");

const filteredDailyLogs = computed(() => {
    const list = [];
    const dates = selectedLogDateFilter.value === "all"
        ? dateColumns.value
        : dateColumns.value.filter((d) => d.dateStr === selectedLogDateFilter.value);

    dates.forEach((dt) => {
        filteredPetugas.value.forEach((p) => {
            const st = matrixPresensi.value[p.id]?.[dt.dateStr] || "";
            const snap = getDailySnapshot(p.id, dt.dateStr);
            const bonus = getDailyBonus(p, dt.dateStr);
            const jabatan = getDailyJabatan(p, dt.dateStr);
            const hakBonus = getDailyHonorBonus(p, dt.dateStr);
            const statusPetugas = snap?.status_petugas || p.status || "Aktif";

            list.push({
                petugasId: p.id,
                dateStr: dt.dateStr,
                dayName: dt.dayName,
                nama: snap?.nama || p.nama,
                nik: snap?.nik || p.nik,
                jabatan,
                status: st,
                bonusHarian: bonus,
                hakDiterima: hakBonus,
                statusPetugas,
            });
        });
    });

    return list;
});

// ─── Detail Modal Slip Bonus Petugas ─────────────────────────────────────────
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

// ─── Ekspor Excel ────────────────────────────────────────────────────────────
const isExportModalOpen = ref(false);
const isExportMenuOpen = ref(false);
const isExporting = ref(false);
const exportDocType = ref("ttd_harian"); // 'ttd_harian' (Buku TTD Bonus Harian per Sheet DD-MM-YYYY) vs 'rekap_bonus' (Rekap Bonus Periode)

function openExportModal(docType = null) {
    isExportMenuOpen.value = false;
    if (docType) {
        exportDocType.value = docType;
    }
    isExportModalOpen.value = true;
}

function closeExportModal() {
    isExportModalOpen.value = false;
}

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

        await downloadBukuTandaTerimaBonusHarianExcel({
            petugas: props.petugas,
            presensiMap: matrixPresensi.value,
            kehadiranMap: matrixPresensi.value,
            presensiDetailsMap: props.initialPresensiDetailsMap,
            dateList: exportDateList,
            startDate: todayStr.value,
            endDate: todayStr.value,
            unitSppg: props.unitSppg,
            periode: activePeriode,
            user: page.props.auth?.user,
        });

        showToast(`Buku Tanda Terima Bonus Hari Ini (${formatTanggalIndo(todayStr.value)}) berhasil didownload!`);
    } catch (e) {
        console.error("Gagal export bonus hari ini:", e);
        showToast("Gagal mengunduh file Excel: " + (e?.message || e));
    } finally {
        isExporting.value = false;
    }
}

async function executeExportDirect(docType = "ttd_harian") {
    exportDocType.value = docType;
    await handleExecuteExport();
}

async function handleExecuteExport() {
    if (isExporting.value) return;
    isExportMenuOpen.value = false;
    closeExportModal();
    isExporting.value = true;
    try {
        let activePeriode = null;
        if (selectedPeriodeId.value !== "all") {
            activePeriode = props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value)) || null;
        }

        const exportDateList = dateColumns.value.map((dt) => ({
            dateStr: dt.dateStr,
            dayNum: dt.dayNum,
            label: String(dt.index),
        }));

        const days = exportDateList.length;
        const startStr = tanggalMulai.value;
        const endStr = tanggalSelesai.value;

        if (exportDocType.value === "ttd_harian") {
            await downloadBukuTandaTerimaBonusHarianExcel({
                petugas: props.petugas,
                presensiMap: matrixPresensi.value,
                kehadiranMap: matrixPresensi.value,
                presensiDetailsMap: props.initialPresensiDetailsMap,
                dateList: exportDateList,
                startDate: startStr,
                endDate: endStr,
                unitSppg: props.unitSppg,
                periode: activePeriode,
                user: page.props.auth?.user,
            });
            const labelHasil = days === 1 
                ? `Hari Ini (${formatTanggalIndo(startStr)})` 
                : `${days} Hari (Pisah Sheet DD-MM-YYYY)`;
            showToast(`Buku Tanda Terima Bonus Harian (${labelHasil}) berhasil didownload!`);
        } else if (exportDocType.value === "slip_bonus_pdf") {
            await downloadSlipBonusPdf({
                petugas: filteredPetugas.value,
                presensiMap: matrixPresensi.value,
                presensiDetailsMap: props.initialPresensiDetailsMap,
                dateList: exportDateList,
                startDate: startStr,
                endDate: endStr,
                unitSppg: props.unitSppg,
                periode: activePeriode,
                user: page.props.auth?.user,
            });
            const labelHasil = days === 1 ? `Hari Ini (${formatTanggalIndo(startStr)})` : `${days} Hari`;
            showToast(`Slip Bonus Mitra PDF (${labelHasil}) berhasil didownload!`);
        } else {
            await downloadRekapBonusExcel({
                petugas: props.petugas,
                presensiMap: matrixPresensi.value,
                presensiDetailsMap: props.initialPresensiDetailsMap,
                dateList: exportDateList,
                startDate: startStr,
                endDate: endStr,
                unitSppg: props.unitSppg,
                periode: activePeriode,
                modeLabel: modeSkala.value === "periodik" ? "Periodik (14 Hari Kerja)" : "Bulanan (28 Hari Kerja)",
                user: page.props.auth?.user,
            });
            const labelHasil = days === 1 ? `Hari Ini (${formatTanggalIndo(startStr)})` : `${days} Hari`;
            showToast(`File Excel Rekap Bonus (${labelHasil}) berhasil didownload!`);
        }

        closeExportModal();
    } catch (e) {
        console.error("Gagal export:", e);
        showToast("Gagal mengunduh file: " + (e?.message || e));
    } finally {
        isExporting.value = false;
    }
}

async function downloadSlipBonusDirect() {
    isExportMenuOpen.value = false;
    isExporting.value = true;
    try {
        let activePeriode = null;
        if (selectedPeriodeId.value !== "all") {
            activePeriode = props.periodes?.find((p) => String(p.id) === String(selectedPeriodeId.value)) || null;
        }

        const exportDateList = dateColumns.value.map((dt) => ({
            dateStr: dt.dateStr,
            dayNum: dt.dayNum,
            label: String(dt.index),
        }));

        const days = exportDateList.length;
        const startStr = tanggalMulai.value;
        const endStr = tanggalSelesai.value;

        await downloadSlipBonusPdf({
            petugas: filteredPetugas.value,
            presensiMap: matrixPresensi.value,
            presensiDetailsMap: props.initialPresensiDetailsMap,
            dateList: exportDateList,
            startDate: startStr,
            endDate: endStr,
            unitSppg: props.unitSppg,
            periode: activePeriode,
            user: page.props.auth?.user,
        });

        const labelHasil = days === 1 ? `Hari Ini (${formatTanggalIndo(startStr)})` : `${days} Hari`;
        showToast(`Slip Bonus Mitra PDF (${labelHasil}) berhasil didownload!`);
    } catch (e) {
        console.error("Gagal export Slip Bonus PDF:", e);
        showToast("Gagal mengunduh Slip Bonus PDF: " + (e?.message || e));
    } finally {
        isExporting.value = false;
    }
}

async function downloadSlipBonusSinglePetugas(p) {
    if (!p) return;
    try {
        let activePeriode = null;
        if (selectedPeriodeId.value !== "all") {
            activePeriode = props.periodes?.find((pr) => String(pr.id) === String(selectedPeriodeId.value)) || null;
        }
        const exportDateList = dateColumns.value.map((dt) => ({
            dateStr: dt.dateStr,
            dayNum: dt.dayNum,
            label: String(dt.index),
        }));
        await downloadSlipBonusPdf({
            petugas: [p],
            presensiMap: matrixPresensi.value,
            presensiDetailsMap: props.initialPresensiDetailsMap,
            dateList: exportDateList,
            startDate: tanggalMulai.value,
            endDate: tanggalSelesai.value,
            unitSppg: props.unitSppg,
            periode: activePeriode,
            user: page.props.auth?.user,
        });
        showToast(`Slip Bonus PDF untuk ${p.nama} berhasil didownload!`);
    } catch (e) {
        showToast("Gagal mengunduh Slip Bonus PDF: " + (e?.message || e));
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Rekap Bonus - SIPEGE" />

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
                    <div class="p-2 sm:p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 shadow-2xs shrink-0">
                        <Coins class="h-5 w-5 sm:h-6 sm:w-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight leading-tight">
                                Rekap Bonus Petugas SPPG
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
                            Otomatis sinkron dengan Rekap Kehadiran, dapat disesuaikan per hari dan disimpan
                        </p>
                    </div>
                </div>

                <!-- Action Buttons: Icon + Text (Responsive Mobile & Desktop) -->
                <div class="flex items-center gap-1.5 sm:gap-2 w-full sm:w-auto justify-end flex-wrap">
                    <!-- Link ke Daftar Petugas -->
                    <Link
                        :href="route('petugas.index')"
                        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                        title="Daftar Petugas"
                    >
                        <Users class="h-4 w-4 text-slate-500" />
                        <span class="hidden md:inline">Daftar Petugas</span>
                    </Link>

                    <!-- Tombol Download Excel Rekap & Bonus dengan Menu Cepat -->
                    <div class="relative inline-flex items-stretch rounded-lg shadow-xs">
                        <button
                            type="button"
                            @click="executeExportDirect('ttd_harian')"
                            :disabled="isExporting"
                            class="inline-flex items-center gap-1.5 px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-l-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors cursor-pointer disabled:opacity-50"
                            title="Download Buku Tanda Terima Bonus Harian"
                        >
                            <Loader2 v-if="isExporting" class="h-4 w-4 animate-spin" />
                            <FileSpreadsheet v-else class="h-4 w-4" />
                            <span class="hidden sm:inline">{{ isExporting ? 'Mengekspor...' : 'Download' }}</span>
                            <span class="sm:hidden">{{ isExporting ? '...' : 'Excel' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="isExportMenuOpen = !isExportMenuOpen"
                            :disabled="isExporting"
                            class="inline-flex items-center px-1.5 sm:px-2 rounded-r-lg bg-emerald-700 hover:bg-emerald-800 text-white border-l border-emerald-500/60 text-xs transition-colors cursor-pointer disabled:opacity-50"
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
                            class="absolute right-0 top-full mt-1.5 z-50 w-72 max-w-[calc(100vw-2rem)] rounded-xl bg-white border border-slate-200 shadow-xl py-1 text-xs animate-in fade-in zoom-in-95 duration-100 divide-y divide-slate-100 select-none"
                        >
                            <div class="p-1">
                                <!-- Opsi 1: Buku Tanda Terima Bonus Harian (Per Sheet DD-MM-YYYY) Langsung -->
                                <button
                                    type="button"
                                    @click="executeExportDirect('ttd_harian')"
                                    :disabled="isExporting"
                                    class="w-full text-left p-2 rounded-lg bg-amber-50/70 hover:bg-amber-100/80 text-amber-950 font-bold flex items-start gap-2.5 cursor-pointer transition-colors disabled:opacity-50"
                                >
                                    <div class="p-1.5 rounded-md bg-amber-600 text-white mt-0.5 shrink-0 shadow-2xs">
                                        <FileSpreadsheet class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <p class="text-xs font-bold leading-tight text-amber-900">Buku Tanda Terima Bonus</p>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-amber-200/80 text-amber-800">BARU</span>
                                        </div>
                                        <p class="text-[10px] text-amber-700 font-normal mt-0.5 leading-snug">Pisah per tgl (Sheet DD-MM-YYYY), total nominal bonus, rekap hadir & TTD fisik</p>
                                    </div>
                                </button>

                                <!-- Opsi 2: Tanda Terima Hari Ini (1 Sheet DD-MM-YYYY) Langsung -->
                                <button
                                    type="button"
                                    @click="downloadHariIniDirect"
                                    :disabled="isExporting"
                                    class="w-full text-left p-2 rounded-lg hover:bg-slate-100 text-slate-800 font-semibold flex items-start gap-2.5 cursor-pointer transition-colors mt-0.5 disabled:opacity-50"
                                >
                                    <div class="p-1.5 rounded-md bg-emerald-100 text-emerald-700 mt-0.5 shrink-0">
                                        <CalendarCheck class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold leading-tight text-slate-900">TTD Hari Ini (1 Sheet)</p>
                                        <p class="text-[10px] text-slate-500 font-normal mt-0.5">Langsung download tgl {{ formatTanggalIndo(todayStr) }}</p>
                                    </div>
                                </button>

                                <!-- Opsi 3: Rekap Bonus Periode Langsung -->
                                <button
                                    type="button"
                                    @click="executeExportDirect('rekap_bonus')"
                                    :disabled="isExporting"
                                    class="w-full text-left p-2 rounded-lg hover:bg-slate-100 text-slate-800 font-semibold flex items-start gap-2.5 cursor-pointer transition-colors mt-0.5 disabled:opacity-50"
                                >
                                    <div class="p-1.5 rounded-md bg-slate-100 text-slate-700 mt-0.5 shrink-0">
                                        <Layers class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold leading-tight text-slate-900">Rekap Bonus (Matriks & Detail)</p>
                                        <p class="text-[10px] text-slate-500 font-normal mt-0.5">Rekapitulasi hak bonus periode + divisi & rincian</p>
                                    </div>
                                </button>

                                <!-- Opsi 4: Slip Bonus (.pdf) Berdasarkan Filter Aktif Langsung -->
                                <button
                                    type="button"
                                    @click="downloadSlipBonusDirect"
                                    :disabled="isExporting"
                                    class="w-full text-left p-2 rounded-lg hover:bg-orange-50/70 text-slate-800 font-semibold flex items-start gap-2.5 cursor-pointer transition-colors mt-0.5 border-t border-slate-100 disabled:opacity-50"
                                >
                                    <div class="p-1.5 rounded-md bg-orange-100 text-orange-800 mt-0.5 shrink-0">
                                        <FileText class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <p class="text-xs font-bold leading-tight text-orange-950">Download Slip Bonus (.pdf)</p>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-orange-200 text-orange-900">PDF</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 font-normal mt-0.5">Slip cetak resmi per personil (rentang filter aktif)</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Simpan -->
                    <button
                        type="button"
                        @click="handleSimpanBonus"
                        :disabled="isSaving"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-xs font-bold shadow-xs transition-all cursor-pointer disabled:opacity-50',
                            isDirty
                                ? 'bg-primary hover:bg-primary-hover text-white ring-2 ring-primary/30'
                                : 'bg-slate-800 hover:bg-slate-900 text-white',
                        ]"
                        title="Simpan penyesuaian bonus ke database"
                    >
                        <Save class="h-4 w-4" />
                        <span>{{ isSaving ? 'Menyimpan...' : 'Simpan' }}</span>
                    </button>
                </div>
            </div>

            <!-- ─── Control Bar: Reusable PeriodDateFilterBar & Filter Interaktif ───────── -->
            <PeriodDateFilterBar
                v-model:startDate="tanggalMulai"
                v-model:endDate="tanggalSelesai"
                v-model:mode="modeSkala"
                v-model:periodeId="selectedPeriodeId"
                :periodes="props.periodes"
                @filter-change="onDateFilterChange"
            >
                <template #extra-filters>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full md:w-auto md:flex md:items-center">
                        <!-- Search Box -->
                        <div class="relative w-full md:w-56">
                            <Search class="h-3.5 w-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama, NIK..."
                                class="w-full pl-8 pr-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-primary focus:border-primary outline-hidden bg-white shadow-2xs"
                            />
                        </div>

                        <!-- Dropdown Filter Jabatan -->
                        <select
                            v-model="selectedJabatanFilter"
                            class="w-full md:w-auto px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary outline-hidden bg-white shadow-2xs"
                        >
                            <option value="all">Semua Divisi / Jabatan</option>
                            <option v-for="j in props.daftarJabatan" :key="j" :value="j">{{ j }}</option>
                        </select>

                        <!-- Dropdown Status Pegawai -->
                        <select
                            v-model="selectedStatusFilter"
                            class="w-full md:w-auto px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary outline-hidden bg-white shadow-2xs"
                        >
                            <option value="all">Semua Status</option>
                            <option value="Aktif">Personil Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </template>
            </PeriodDateFilterBar>

            <!-- ─── KPI SUMMARY CARDS ─── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                <!-- Card 1: Penerima Aktif -->
                <div class="p-2.5 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Penerima Hak Bonus</p>
                        <Users class="h-3.5 w-3.5 text-slate-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-slate-900 mt-1">
                        {{ kpiStats.totalPenerima }} <span class="text-xs font-normal text-slate-500">orang</span>
                    </p>
                    <p class="text-[10px] text-slate-500 mt-0.5 truncate">
                        Dari total {{ props.petugas.length }} personil SPPG
                    </p>
                </div>

                <!-- Card 2: Total Mandays Terhitung -->
                <div class="p-2.5 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Total Mandays</p>
                        <CalendarCheck class="h-3.5 w-3.5 text-blue-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-blue-900 mt-1">
                        {{ formatRibuan(kpiStats.totalMandays) }} <span class="text-xs font-normal text-slate-500">hari kerja</span>
                    </p>
                    <p class="text-[10px] text-slate-500 mt-0.5 truncate">
                        H:{{ kpiStats.totalHadirPenuh }} • ½:{{ kpiStats.totalSetengahHari }}
                    </p>
                </div>

                <!-- Card 3: Total Tarif Harian -->
                <div class="p-2.5 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Tarif Harian Total</p>
                        <Coins class="h-3.5 w-3.5 text-amber-600" />
                    </div>
                    <p class="text-xs sm:text-base font-bold text-amber-900 mt-1 truncate" :title="formatRupiah(kpiStats.totalBonusHarianRate)">
                        {{ formatRupiah(kpiStats.totalBonusHarianRate) }}
                    </p>
                    <p class="text-[10px] text-amber-700 font-semibold mt-0.5 truncate">
                        Total rate bonus per hari
                    </p>
                </div>

                <!-- Card 4: Grand Total Bonus Terhitung -->
                <div class="p-2.5 sm:p-3.5 rounded-xl bg-amber-50/70 border border-amber-300 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-bold text-amber-800 uppercase tracking-wider">Total Hak Bonus</p>
                        <DollarSign class="h-3.5 w-3.5 text-amber-700" />
                    </div>
                    <p class="text-xs sm:text-base font-black text-amber-950 mt-1 truncate" :title="formatRupiah(kpiStats.grandTotalBonus)">
                        {{ formatRupiah(kpiStats.grandTotalBonus) }}
                    </p>
                    <p class="text-[10px] text-amber-700 font-semibold mt-0.5 truncate">
                        Sesuai mandays riil
                    </p>
                </div>
            </div>

            <!-- ─── Segmented Tab Switcher: Matriks Kalender vs Log Rincian Harian ──── -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="w-full sm:w-auto grid grid-cols-2 sm:inline-flex p-1 rounded-xl bg-slate-200/80 border border-slate-300/80 text-xs font-semibold shadow-inner">
                    <button
                        type="button"
                        @click="activeViewTab = 'matriks'"
                        :class="[
                            'flex items-center justify-center gap-2 px-3.5 py-1.5 rounded-lg transition-all cursor-pointer',
                            activeViewTab === 'matriks'
                                ? 'bg-white text-emerald-800 font-bold shadow-xs ring-1 ring-slate-200'
                                : 'text-slate-600 hover:text-slate-900',
                        ]"
                    >
                        <CalendarCheck class="h-4 w-4 text-emerald-600 shrink-0" />
                        <span class="truncate">Matriks (Kalender)</span>
                    </button>
                    <button
                        type="button"
                        @click="activeViewTab = 'harian'"
                        :class="[
                            'flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer',
                            activeViewTab === 'harian'
                                ? 'bg-white text-emerald-800 font-bold shadow-xs ring-1 ring-slate-200'
                                : 'text-slate-600 hover:text-slate-900',
                        ]"
                    >
                        <Clock class="h-4 w-4 text-emerald-600 shrink-0" />
                        <span class="truncate">Log Harian</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-emerald-100 text-emerald-800 font-mono hidden xs:inline">
                            {{ filteredDailyLogs.length }}
                        </span>
                    </button>
                </div>

                <div v-if="activeViewTab === 'harian'" class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-600">Filter Tanggal:</span>
                    <select
                        v-model="selectedLogDateFilter"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold bg-white focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 shadow-2xs"
                    >
                        <option value="all">Semua Tanggal ({{ dateColumns.length }} Hari)</option>
                        <option v-for="dt in dateColumns" :key="dt.dateStr" :value="dt.dateStr">
                            {{ formatTanggalIndo(dt.dateStr) }} ({{ dt.dayName }}){{ dt.dateStr === todayStr ? ' - Hari Ini' : '' }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- ─── TABEL MATRIKS PENCATATAN BONUS (SAMA PERSIS KEHADIRAN & BISA DIUBAH) ─── -->
            <div v-if="activeViewTab === 'matriks'" class="rounded-xl bg-white border border-slate-200 shadow-2xs overflow-hidden">
                <!-- Toolbar Tabel & Keterangan Status Presensi -->
                <div class="px-3.5 sm:px-4 py-2.5 sm:py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-slate-50/60">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Matriks Bonus ({{ filteredPetugas.length }} Personil)
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                            {{ dateColumns.length }} Kolom Tanggal
                        </span>
                        <button
                            type="button"
                            @click="toggleAllExpand"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                            :title="expandedPetugasIds.size === props.petugas.length ? 'Tutup semua rincian harian' : 'Buka rincian harian seluruh personil'"
                        >
                            <ChevronDown
                                class="h-3.5 w-3.5 text-slate-500 transition-transform duration-200"
                                :class="{ 'rotate-180 text-emerald-600': expandedPetugasIds.size > 0 }"
                            />
                            <span>{{ expandedPetugasIds.size === props.petugas.length ? 'Tutup Semua Rincian' : 'Buka Semua Rincian Harian' }}</span>
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 text-[11px] text-slate-600 font-medium">
                        <span class="text-slate-400 hidden xs:inline">Pilihan:</span>
                        <span class="inline-flex items-center gap-1 font-semibold text-slate-500" title="Belum Ada Catatan Presensi (Default)"><span class="w-2.5 h-2.5 rounded-sm border border-dashed border-slate-400 bg-slate-100 flex items-center justify-center text-[8px] text-slate-400">·</span>Kosong</span>
                        <span class="inline-flex items-center gap-1 font-bold text-emerald-700" title="Hadir Penuh (Bonus 100%)"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>H (Hadir)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-teal-700" title="Hadir Setengah Hari (Bonus 50%)"><span class="w-2 h-2 rounded-full bg-teal-500"></span>½ (Setengah)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-slate-700" title="Libur Operasional"><span class="w-2 h-2 rounded-full bg-slate-500"></span>L (Libur)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-amber-700" title="Izin"><span class="w-2 h-2 rounded-full bg-amber-500"></span>I (Izin)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-blue-700" title="Sakit"><span class="w-2 h-2 rounded-full bg-blue-500"></span>S (Sakit)</span>
                        <span class="inline-flex items-center gap-1 font-bold text-rose-700" title="Tanpa Keterangan"><span class="w-2 h-2 rounded-full bg-rose-500"></span>TK (Tanpa Ket)</span>
                    </div>
                </div>

                <!-- Mobile Swipe Hint -->
                <div class="sm:hidden px-3.5 py-1.5 bg-emerald-50/70 border-b border-emerald-100 flex items-center justify-between text-[11px] text-emerald-800 font-medium">
                    <span>👉 Geser tabel ke kanan untuk melihat rincian tanggal & honor</span>
                </div>

                <!-- Kontainer Tabel Horizontal Scroll -->
                <div class="overflow-x-auto relative">
                    <table class="w-full text-left text-xs border-collapse min-w-[1100px]">
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

                                <!-- Kolom Matriks Tanggal Interaktif -->
                                <th
                                    v-for="dt in dateColumns"
                                    :key="dt.dateStr"
                                    :class="[
                                        'py-2 px-1 text-center min-w-[38px] max-w-[42px] border-r select-none group transition-colors',
                                        dt.dateStr === todayStr
                                            ? 'bg-emerald-600 text-white border-emerald-700 shadow-xs'
                                            : (dt.isWeekend ? 'bg-amber-50/70 border-amber-200/60 text-slate-700' : 'bg-emerald-50/50 border-emerald-200/50 text-slate-700'),
                                    ]"
                                    :title="`${dt.dayName}, ${dt.dateStr}${dt.dateStr === todayStr ? ' (HARI INI)' : ''} (Klik tanggal untuk ganti status: H ➜ ½ ➜ L ➜ I ➜ S ➜ TK ➜ Kosong)`"
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
                                                'mt-0.5 px-1 py-0.5 rounded text-[11px] font-black transition-all cursor-pointer hover:scale-105 active:scale-95',
                                                dt.dateStr === todayStr
                                                    ? 'bg-white text-emerald-800 hover:bg-emerald-100 ring-2 ring-emerald-300'
                                                    : 'hover:bg-emerald-200/70 hover:text-emerald-900',
                                            ]"
                                            :title="`Klik tanggal ${dt.dayNum} untuk ganti status seluruh personil: H ➜ ½ ➜ L ➜ I ➜ S ➜ TK ➜ Kosong`"
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
                            <template v-for="(p, idx) in filteredPetugas" :key="p.id">
                                <tr
                                    :class="[
                                        'transition-colors',
                                        isPetugasExpanded(p.id) ? 'bg-emerald-50/30' : (p.status === 'Nonaktif' ? 'bg-slate-50/70 opacity-60' : 'hover:bg-slate-50/80'),
                                    ]"
                                >
                                    <!-- No (Sticky Left) -->
                                    <td class="py-2.5 px-2 text-center font-medium text-slate-500 sticky left-0 bg-white z-10 border-r border-slate-200 text-xs">
                                        <div class="flex items-center justify-center gap-0.5">
                                            <button
                                                type="button"
                                                @click="toggleExpandPetugas(p.id)"
                                                class="p-0.5 rounded hover:bg-emerald-100 text-slate-400 hover:text-emerald-700 transition-colors cursor-pointer"
                                                :title="isPetugasExpanded(p.id) ? 'Tutup Rincian Harian' : 'Buka Rincian Log Harian Bonus'"
                                            >
                                                <ChevronDown
                                                    class="h-3.5 w-3.5 transition-transform duration-200"
                                                    :class="{ 'rotate-180 text-emerald-600': isPetugasExpanded(p.id) }"
                                                />
                                            </button>
                                            <span>{{ idx + 1 }}</span>
                                        </div>
                                    </td>

                                    <!-- Personil & Divisi (Sticky Left) -->
                                    <td class="py-2.5 px-3 sticky left-9 bg-white z-10 border-r border-slate-200">
                                        <div class="min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <p
                                                    class="font-bold text-slate-900 truncate leading-snug text-xs cursor-pointer hover:text-emerald-700 transition-colors"
                                                    @click="toggleExpandPetugas(p.id)"
                                                    :title="isPetugasExpanded(p.id) ? 'Klik untuk tutup rincian harian' : 'Klik untuk lihat rincian log per hari'"
                                                >
                                                    {{ p.nama }}
                                                </p>
                                                <span
                                                    v-if="isPetugasExpanded(p.id)"
                                                    class="px-1.5 py-0.2 rounded text-[8.5px] font-bold bg-emerald-100 text-emerald-800 shrink-0"
                                                >
                                                    Detail Buka
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                                                <span
                                                    v-for="jab in (p.jabatan || '').split(/[,&/]/).map(s => s.trim()).filter(Boolean)"
                                                    :key="jab"
                                                    :class="['px-1.5 py-0.5 rounded text-[9px] border leading-none font-medium', getJabatanBadgeClass(jab)]"
                                                >
                                                    {{ jab }}
                                                </span>
                                                <span v-if="p.status === 'Nonaktif'" class="px-1.5 py-0.5 rounded text-[9px] bg-slate-200 text-slate-600 font-semibold">
                                                    Nonaktif
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Tarif Bonus Harian (Mitra) -->
                                    <td class="py-2 px-3 text-right font-medium text-slate-800 border-r border-slate-200">
                                        <template v-if="computedBonusStats[p.id]?.isBonusVarying">
                                            <p class="font-semibold text-slate-900 text-xs">Bervariasi</p>
                                            <p class="text-[9px] text-emerald-600 font-mono" title="Rentang tarif bonus harian">
                                                {{ formatRibuan(computedBonusStats[p.id]?.minBonus) }} - {{ formatRibuan(computedBonusStats[p.id]?.maxBonus) }}
                                            </p>
                                        </template>
                                        <template v-else>
                                            <p class="font-semibold text-slate-900 text-xs">
                                                {{ formatRupiah(computedBonusStats[p.id]?.bonusPerHari ?? p.bonus_harian_mitra) }}
                                            </p>
                                            <p class="text-[9px] text-slate-500">Mitra / Hari</p>
                                        </template>
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
                                        <button
                                            v-if="p.status === 'Aktif' || matrixPresensi[p.id]?.[dt.dateStr]"
                                            type="button"
                                            @click="cycleStatus(p.id, dt.dateStr, true)"
                                            :class="[
                                                'w-7 h-7 rounded-md text-[11px] font-black transition-transform active:scale-90 flex items-center justify-center mx-auto cursor-pointer select-none',
                                                getStatusBadgeStyle(matrixPresensi[p.id]?.[dt.dateStr] || ''),
                                            ]"
                                            :title="`${p.nama} (${dt.dayName}, ${dt.dateStr}): ${getStatusTitle(matrixPresensi[p.id]?.[dt.dateStr] || '')}${initialPresensiDetailsMap?.[p.id]?.[dt.dateStr]?.bonus_harian_mitra !== undefined ? ' • Bonus: ' + formatRupiah(initialPresensiDetailsMap[p.id][dt.dateStr].bonus_harian_mitra) : ''} (Klik untuk pilih status)`"
                                        >
                                            {{ getStatusLabel(matrixPresensi[p.id]?.[dt.dateStr] || '') }}
                                        </button>
                                        <span v-else class="text-slate-300 text-[11px]">-</span>
                                    </td>

                                    <!-- Rekap Hadir Penuh (H) -->
                                    <td class="py-2 px-2 text-center font-bold text-emerald-800 bg-emerald-50/60 border-r border-slate-200">
                                        {{ computedBonusStats[p.id]?.hadir ?? 0 }}
                                    </td>

                                    <!-- Rekap Hadir Setengah Hari (½) -->
                                    <td class="py-2 px-2 text-center font-bold text-teal-800 bg-teal-50/60 border-r border-slate-200">
                                        {{ computedBonusStats[p.id]?.setengahHari ?? 0 }}
                                    </td>

                                    <!-- Rekap Libur (L) -->
                                    <td class="py-2 px-2 text-center font-bold text-slate-700 bg-slate-50 border-r border-slate-200">
                                        {{ computedBonusStats[p.id]?.libur ?? 0 }}
                                    </td>

                                    <!-- Rekap Izin (I) -->
                                    <td class="py-2 px-2 text-center font-bold text-amber-800 bg-amber-50/60 border-r border-slate-200">
                                        {{ computedBonusStats[p.id]?.izin ?? 0 }}
                                    </td>

                                    <!-- Rekap Sakit (S) -->
                                    <td class="py-2 px-2 text-center font-bold text-blue-800 bg-blue-50/60 border-r border-slate-200">
                                        {{ computedBonusStats[p.id]?.sakit ?? 0 }}
                                    </td>

                                    <!-- Rekap Tanpa Keterangan (TK) -->
                                    <td class="py-2 px-2 text-center font-bold text-rose-800 bg-rose-50/60 border-r border-slate-200">
                                        {{ computedBonusStats[p.id]?.tanpaKeterangan ?? 0 }}
                                    </td>

                                    <!-- Mandays -->
                                    <td class="py-2 px-2 text-center font-black text-emerald-950 bg-emerald-100/50 border-r border-slate-200">
                                        {{ computedBonusStats[p.id]?.mandays ?? 0 }}
                                    </td>

                                    <!-- % Hadir -->
                                    <td class="py-2 px-2 text-center border-r border-slate-200">
                                        <span
                                            :class="[
                                                'px-1.5 py-0.5 rounded text-[10px] font-bold',
                                                (computedBonusStats[p.id]?.rate ?? 0) >= 90
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : (computedBonusStats[p.id]?.rate ?? 0) >= 70
                                                    ? 'bg-amber-100 text-amber-800'
                                                    : 'bg-rose-100 text-rose-800',
                                            ]"
                                        >
                                            {{ computedBonusStats[p.id]?.rate ?? 0 }}%
                                        </span>
                                    </td>

                                    <!-- Total Hak Bonus -->
                                    <td class="py-2 px-3 text-right font-bold text-emerald-900 border-r border-slate-200">
                                        {{ formatRupiah(computedBonusStats[p.id]?.totalBonus ?? 0) }}
                                    </td>

                                    <!-- Aksi Detail Modal -->
                                    <td class="py-2 px-2 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                type="button"
                                                @click="toggleExpandPetugas(p.id)"
                                                class="p-1 rounded-md text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer"
                                                :title="isPetugasExpanded(p.id) ? 'Tutup Rincian Harian' : 'Buka Rincian Log Harian Bonus'"
                                            >
                                                <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180 text-emerald-600': isPetugasExpanded(p.id) }" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="openDetailModal(p)"
                                                class="p-1 rounded-md text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                                                title="Rincian Slip Bonus Petugas"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Baris Accordion Rincian Harian Bonus Petugas -->
                                <tr
                                    v-if="isPetugasExpanded(p.id)"
                                    class="bg-gradient-to-r from-emerald-50/40 via-teal-50/20 to-slate-50 border-b-2 border-emerald-300"
                                >
                                    <td :colspan="10 + dateColumns.length" class="p-3 sm:p-4">
                                        <div class="bg-white rounded-xl border border-emerald-200 shadow-xs overflow-hidden">
                                            <!-- Header Box Accordion -->
                                            <div class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <Coins class="h-4 w-4 text-emerald-200 shrink-0" />
                                                    <span class="font-bold text-xs">
                                                        Rincian Log Hak Bonus Harian: {{ p.nama }}
                                                    </span>
                                                    <span class="text-[10px] text-emerald-100 hidden sm:inline">
                                                        ({{ dateColumns.length }} Hari Kerja)
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2.5 text-xs flex-wrap">
                                                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded text-white font-mono">
                                                        NIK: {{ p.nik }}
                                                    </span>
                                                    <span class="text-[10px] bg-emerald-500/40 px-2 py-0.5 rounded text-white font-semibold">
                                                        Mandays: {{ computedBonusStats[p.id]?.mandays ?? 0 }} hari
                                                    </span>
                                                    <span class="text-[11px] bg-white text-emerald-950 font-bold px-2 py-0.5 rounded shadow-2xs">
                                                        Total Bonus: {{ formatRupiah(computedBonusStats[p.id]?.totalBonus ?? 0) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Tabel Rincian Per Hari -->
                                            <div class="overflow-x-auto max-h-72 overflow-y-auto">
                                                <table class="w-full text-xs text-left">
                                                    <thead class="bg-slate-100/90 text-slate-700 font-bold text-[11px] sticky top-0 z-10 border-b border-slate-200">
                                                        <tr>
                                                            <th class="py-2 px-3 w-10 text-center">No</th>
                                                            <th class="py-2 px-3">Tanggal & Hari</th>
                                                            <th class="py-2 px-3 text-center">Status Presensi</th>
                                                            <th class="py-2 px-3">Jabatan (Snapshot)</th>
                                                            <th class="py-2 px-3 text-right">Tarif Bonus Mitra</th>
                                                            <th class="py-2 px-3 text-right font-bold text-emerald-800">Hak Bonus Diterima</th>
                                                            <th class="py-2 px-3 text-center">Status Pegawai</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 text-[11px]">
                                                        <tr
                                                            v-for="(dt, dIdx) in dateColumns"
                                                            :key="dt.dateStr"
                                                            :class="[
                                                                'hover:bg-slate-50/80 transition-colors',
                                                                dt.dateStr === todayStr ? 'bg-emerald-50/40 font-semibold' : '',
                                                            ]"
                                                        >
                                                            <td class="py-2 px-3 text-center text-slate-400">{{ dIdx + 1 }}</td>
                                                            <td class="py-2 px-3 font-medium">
                                                                <span class="font-bold text-slate-800">{{ formatTanggalIndo(dt.dateStr) }}</span>
                                                                <span class="text-slate-500 ml-1.5">({{ dt.dayName }})</span>
                                                                <span v-if="dt.dateStr === todayStr" class="ml-1.5 px-1.5 py-0.2 rounded text-[9px] bg-emerald-100 text-emerald-800 font-bold">Hari Ini</span>
                                                            </td>
                                                            <td class="py-2 px-3 text-center">
                                                                <span
                                                                    :class="[
                                                                        'px-2 py-0.5 rounded text-[10px] font-bold inline-flex items-center gap-1',
                                                                        getStatusBadgeStyle(matrixPresensi[p.id]?.[dt.dateStr] || ''),
                                                                    ]"
                                                                >
                                                                    {{ getStatusTitle(matrixPresensi[p.id]?.[dt.dateStr] || '') }}
                                                                </span>
                                                            </td>
                                                            <td class="py-2 px-3 text-slate-700">
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] border font-medium" :class="getJabatanBadgeClass(getDailyJabatan(p, dt.dateStr))">
                                                                    {{ getDailyJabatan(p, dt.dateStr) }}
                                                                </span>
                                                            </td>
                                                            <td class="py-2 px-3 text-right font-mono text-slate-800">
                                                                {{ formatRupiah(getDailyBonus(p, dt.dateStr)) }}
                                                            </td>
                                                            <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">
                                                                {{ formatRupiah(getDailyHonorBonus(p, dt.dateStr)) }}
                                                            </td>
                                                            <td class="py-2 px-3 text-center">
                                                                <span
                                                                    :class="[
                                                                        'px-1.5 py-0.5 rounded text-[9px] font-semibold',
                                                                        (getDailySnapshot(p.id, dt.dateStr)?.status_petugas || p.status) === 'Aktif'
                                                                            ? 'bg-emerald-100 text-emerald-800'
                                                                            : 'bg-slate-200 text-slate-600'
                                                                    ]"
                                                                >
                                                                    {{ getDailySnapshot(p.id, dt.dateStr)?.status_petugas || p.status }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

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
                                    {{ formatRupiah(kpiStats.grandTotalBonus) }}
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
                            @click="handleSimpanBonus"
                            :disabled="isSaving"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition-colors cursor-pointer shadow-xs disabled:opacity-50"
                        >
                            <Save class="h-3.5 w-3.5" />
                            <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Bonus' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── TABEL LOG RINCIAN HARIAN BONUS (TABEL LENGKAP PER HARI) ─── -->
            <div v-if="activeViewTab === 'harian'" class="rounded-xl bg-white border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/70 flex flex-wrap items-center justify-between gap-2.5">
                    <div>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <Clock class="h-4 w-4 text-amber-600" />
                            <span>Log Rincian Harian Hak Bonus Petugas</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Menampilkan data riil kehadiran dan snapshot hak bonus masing-masing personil per hari
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800">
                            {{ filteredDailyLogs.length }} Catatan Log
                        </span>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 flex items-center gap-1">
                            <Eye class="h-3.5 w-3.5 text-slate-500" />
                            <span>Hanya Lihat (Read-Only)</span>
                        </span>
                    </div>
                </div>

                <!-- Mobile Swipe Hint -->
                <div class="sm:hidden px-3.5 py-1.5 bg-amber-50/70 border-b border-amber-100 flex items-center justify-between text-[11px] text-amber-800 font-medium">
                    <span>👉 Geser tabel ke kanan untuk melihat rincian snapshot bonus</span>
                </div>

                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-xs text-left border-collapse min-w-[850px]">
                        <thead class="bg-slate-100/95 text-slate-700 font-bold sticky top-0 z-20 border-b border-slate-200 text-[11px]">
                            <tr>
                                <th class="py-2.5 px-3 w-12 text-center">No</th>
                                <th class="py-2.5 px-3 min-w-[130px]">Tanggal & Hari</th>
                                <th class="py-2.5 px-3 min-w-[170px]">Nama Petugas & NIK</th>
                                <th class="py-2.5 px-3 min-w-[140px]">Jabatan (Snapshot)</th>
                                <th class="py-2.5 px-3 text-center min-w-[130px]">Status Presensi</th>
                                <th class="py-2.5 px-3 text-right min-w-[110px]">Bonus Harian</th>
                                <th class="py-2.5 px-3 text-right min-w-[120px] font-bold text-amber-800">Hak Bonus Diterima</th>
                                <th class="py-2.5 px-3 text-center min-w-[100px]">Status Pegawai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(log, idx) in filteredDailyLogs"
                                :key="`${log.petugasId}-${log.dateStr}`"
                                :class="[
                                    'hover:bg-slate-50/80 transition-colors',
                                    log.dateStr === todayStr ? 'bg-amber-50/30 font-semibold' : '',
                                ]"
                            >
                                <td class="py-2 px-3 text-center text-slate-400">{{ idx + 1 }}</td>
                                <td class="py-2 px-3 font-medium">
                                    <span class="font-bold text-slate-800">{{ formatTanggalIndo(log.dateStr) }}</span>
                                    <span class="text-slate-500 ml-1.5">({{ log.dayName }})</span>
                                    <span v-if="log.dateStr === todayStr" class="ml-1.5 px-1.5 py-0.2 rounded text-[9px] bg-emerald-100 text-emerald-800 font-bold">Hari Ini</span>
                                </td>
                                <td class="py-2 px-3">
                                    <p class="font-bold text-slate-900 leading-snug">{{ log.nama }}</p>
                                    <p class="text-[10px] text-slate-500 font-mono mt-0.5">NIK: {{ log.nik }}</p>
                                </td>
                                <td class="py-2 px-3 text-slate-700">
                                    <span class="px-2 py-0.5 rounded text-[10px] border font-medium" :class="getJabatanBadgeClass(log.jabatan)">
                                        {{ log.jabatan }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-md text-[10px] font-bold inline-flex items-center gap-1 shadow-2xs select-none',
                                            getStatusBadgeStyle(log.status),
                                        ]"
                                    >
                                        {{ getStatusTitle(log.status) }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-right font-mono text-slate-800">
                                    {{ formatRupiah(log.bonusHarian) }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-amber-700">
                                    {{ formatRupiah(log.hakDiterima) }}
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <span
                                        :class="[
                                            'px-1.5 py-0.5 rounded text-[9px] font-semibold',
                                            log.statusPetugas === 'Aktif'
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-slate-200 text-slate-600'
                                        ]"
                                    >
                                        {{ log.statusPetugas }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filteredDailyLogs.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <Clock class="h-8 w-8 mx-auto mb-2 text-slate-300" />
                                    <p class="font-medium">Tidak ada log presensi bonus yang cocok dengan filter tanggal ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ─── MODAL EXPORT DOKUMEN EXCEL REKAP BONUS ─────────────────── -->
            <Teleport to="body">
                <div
                    v-if="isExportModalOpen"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                    style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
                >
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <!-- Modal Header -->
                        <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 via-teal-600 to-teal-700 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="p-2 rounded-xl bg-white/10 backdrop-blur-xs">
                                    <FileSpreadsheet class="h-5 w-5 text-emerald-200" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold leading-tight">
                                        {{ exportDocType === 'ttd_harian' ? 'Download Buku Tanda Terima Bonus Harian' : 'Download Rekapitulasi Hak Bonus Petugas' }}
                                    </h3>
                                    <p class="text-[11px] text-emerald-100">
                                        {{ exportDocType === 'ttd_harian' ? 'File Excel khusus tanda terima per hari (1 sheet per tanggal DD-MM-YYYY)' : 'Format rekapitulasi hak bonus periode, ringkasan divisi & rincian log' }}
                                    </p>
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

                        <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                            <!-- Pilihan Jenis Dokumen Excel -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Pilih Jenis Dokumen Excel
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label
                                        :class="[
                                            'flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                            exportDocType === 'ttd_harian'
                                                ? 'border-teal-500 bg-teal-50/80 ring-2 ring-teal-500/20'
                                                : 'border-slate-200 hover:bg-slate-50',
                                        ]"
                                    >
                                        <input type="radio" value="ttd_harian" v-model="exportDocType" class="sr-only" />
                                        <div class="p-1.5 rounded-lg mt-0.5 shrink-0" :class="exportDocType === 'ttd_harian' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600'">
                                            <FileSpreadsheet class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-900">Buku TTD Bonus Harian</span>
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-teal-200 text-teal-800">BARU</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                                1 Sheet per Tanggal (DD-MM-YYYY), kolom TTD fisik, total nominal & rekapitulasi kehadiran.
                                            </p>
                                        </div>
                                    </label>

                                    <label
                                        :class="[
                                            'flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                            exportDocType === 'rekap_bonus'
                                                ? 'border-emerald-500 bg-emerald-50/80 ring-2 ring-emerald-500/20'
                                                : 'border-slate-200 hover:bg-slate-50',
                                        ]"
                                    >
                                        <input type="radio" value="rekap_bonus" v-model="exportDocType" class="sr-only" />
                                        <div class="p-1.5 rounded-lg mt-0.5 shrink-0" :class="exportDocType === 'rekap_bonus' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'">
                                            <Layers class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-slate-900">Rekap Bonus Periode</span>
                                            <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                                Rekapitulasi periode, ringkasan per divisi & log rincian harian lengkap (.xlsx).
                                            </p>
                                        </div>
                                    </label>

                                    <!-- Pilihan 3: Slip Bonus PDF -->
                                    <label
                                        :class="[
                                            'flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer transition-all col-span-1 sm:col-span-2',
                                            exportDocType === 'slip_bonus_pdf'
                                                ? 'border-orange-500 bg-orange-50/80 ring-2 ring-orange-500/20'
                                                : 'border-slate-200 hover:bg-slate-50',
                                        ]"
                                    >
                                        <input type="radio" value="slip_bonus_pdf" v-model="exportDocType" class="sr-only" />
                                        <div class="p-1.5 rounded-lg mt-0.5 shrink-0" :class="exportDocType === 'slip_bonus_pdf' ? 'bg-orange-600 text-white' : 'bg-slate-100 text-slate-600'">
                                            <FileText class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-900">Slip Bonus Mitra Petugas (.pdf)</span>
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-orange-200 text-orange-900">PDF RESMI</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                                Cetak slip bonus per personil (1 slip per lembar A4 portrait / 1 halaman per orang) memuat rincian mandays, tarif bonus harian, total hak bonus diterima, rekening BNI, dan pengesahan 3 pihak.
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Info Rentang Tanggal Filter Aktif (Sesuai Filter Tabel Halaman) -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 rounded-lg bg-teal-100 text-teal-700 shrink-0">
                                        <CalendarCheck class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Rentang Tanggal Dokumen (Sesuai Filter)</p>
                                        <p class="font-bold text-slate-800 text-xs mt-0.5">
                                            {{ formatTanggalIndo(tanggalMulai) }}
                                            <span v-if="tanggalMulai !== tanggalSelesai"> s.d. {{ formatTanggalIndo(tanggalSelesai) }}</span>
                                        </p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-teal-100/70 border border-teal-200 text-teal-800 font-bold text-[11px] shrink-0">
                                    {{ dateColumns.length }} Hari Kerja
                                </span>
                            </div>

                            <!-- Info Preview Dokumen Berdasarkan exportDocType -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                                <div class="flex items-center justify-between text-slate-700 font-semibold border-b border-slate-200/80 pb-2">
                                    <span>Unit SPPG:</span>
                                    <span class="text-slate-900 font-bold">{{ unitSppg?.nama ?? 'SPPG Buleleng Sukasada Tegallinggah' }}</span>
                                </div>

                                <!-- Preview Khusus Slip Bonus PDF -->
                                <div v-if="exportDocType === 'slip_bonus_pdf'" class="space-y-1.5 text-slate-700">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium">Format Dokumen:</span>
                                        <span class="text-orange-800 font-bold">PDF Resmi (1 Slip per Lembar A4 Portrait / 1 Halaman per Orang)</span>
                                    </div>
                                    <div class="p-2.5 rounded-lg bg-orange-50/70 border border-orange-200 text-[11px] text-orange-950 space-y-1">
                                        <p class="font-bold flex items-center gap-1.5">
                                            <CheckCircle2 class="h-3.5 w-3.5 text-orange-600 shrink-0" />
                                            <span>Rincian Setiap Lembar Slip:</span>
                                        </p>
                                        <ul class="list-disc list-inside space-y-0.5 pl-1 text-slate-700">
                                            <li>Kop resmi SPPG (Logo BGN & Logo Yayasan Pesantren Miftahul Ulum)</li>
                                            <li>Identitas personil (Nama, NIK, Jabatan, Bank/Rekening, Jam Kerja)</li>
                                            <li>Rekapitulasi kehadiran (Hadir H, ½ Hari, Libur, Izin, Sakit, TK & Total Mandays)</li>
                                            <li>Perhitungan bonus mandays (Mandays x Tarif Bonus Harian Mitra)</li>
                                            <li>Callout total hak bonus diterima + kalimat terbilang bahasa Indonesia</li>
                                            <li>Tanda tangan 3 pihak: Penerima, Pengawas Keuangan, dan Kepala SPPG</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Preview Khusus Buku TTD Bonus Harian -->
                                <div v-else-if="exportDocType === 'ttd_harian'" class="space-y-1.5 text-slate-700">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium">Struktur Lembar Excel:</span>
                                        <span class="text-teal-800 font-bold">{{ dateColumns.length }} Sheet (1 Sheet per Tanggal: DD-MM-YYYY)</span>
                                    </div>
                                    <div class="p-2.5 rounded-lg bg-teal-50/70 border border-teal-200 text-[11px] text-teal-900 space-y-1">
                                        <p class="font-bold flex items-center gap-1.5">
                                            <CheckCircle2 class="h-3.5 w-3.5 text-teal-600 shrink-0" />
                                            <span>Format Setiap Sheet (Nama Sheet: DD-MM-YYYY):</span>
                                        </p>
                                        <ul class="list-disc list-inside space-y-0.5 pl-1 text-slate-700">
                                            <li>Tabel personil + kolom tanda tangan fisik zig-zag (tinggi 28pt)</li>
                                            <li>Total nominal harian (Tarif Bonus Harian & Hak Bonus Diterima)</li>
                                            <li>Rekapitulasi kehadiran (jumlah Hadir, Hadir ½ Hari, Izin, Sakit, Alpa, Libur)</li>
                                            <li>Pengesahan tanda tangan: Kepala SPPG & Pengawas Keuangan / Gizi</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Preview Khusus Rekap Bonus Periode -->
                                <div v-else class="space-y-1.5 text-slate-700">
                                    <div class="flex items-center justify-between">
                                        <span>Sheet 1:</span>
                                        <span class="text-teal-800 font-bold">Rekapitulasi Hak Bonus Petugas SPPG</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Sheet 2:</span>
                                        <span class="text-slate-800 font-bold">Ringkasan per Divisi (Executive Summary)</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Sheet 3:</span>
                                        <span class="text-teal-800 font-bold">Rincian Detail Harian (Log Bonus Lengkap)</span>
                                    </div>
                                    <div class="pt-1 text-[11px] text-teal-800 font-medium flex items-center gap-1.5">
                                        <CheckCircle2 class="h-3.5 w-3.5 text-teal-600 shrink-0" />
                                        <span>Dilengkapi lembar pengesahan Kepala SPPG & Pengawas Keuangan/Gizi.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-end gap-2 sm:gap-2.5">
                            <button
                                type="button"
                                @click="closeExportModal"
                                class="w-full sm:w-auto px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer text-center"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="handleExecuteExport"
                                :disabled="isExporting"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <component :is="exportDocType === 'slip_bonus_pdf' ? FileText : FileSpreadsheet" class="h-4 w-4" />
                                <span class="truncate">
                                    {{ isExporting ? 'Mengekspor File...' : (exportDocType === 'slip_bonus_pdf' ? 'Download Slip Bonus (.pdf)' : (exportDocType === 'ttd_harian' ? 'Download Buku TTD Bonus (.xlsx)' : 'Download Rekap Bonus (.xlsx)')) }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- ─── MODAL DETAIL SLIP BONUS PETUGAS ───────────────────────── -->
            <Teleport to="body">
                <div
                    v-if="isDetailModalOpen && detailPetugas"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                    style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
                >
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="px-5 py-4 bg-gradient-to-r from-amber-600 to-emerald-700 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <Coins class="h-5 w-5 text-amber-200" />
                                <div>
                                    <h3 class="text-sm font-bold">Rincian Slip Hak Bonus Petugas</h3>
                                    <p class="text-[11px] text-amber-100">Slip perhitungan insentif operasional personil SPPG</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeDetailModal"
                                class="p-1 rounded-lg text-amber-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
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
                                        Rek: {{ detailPetugas.jenis_bank || 'BNI' }} - {{ detailPetugas.nomor_rekening || '-' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Komponen Bonus Harian (Mitra) -->
                            <div class="p-3.5 rounded-xl border border-slate-200 space-y-2">
                                <p class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                    Komponen Tarif Bonus Harian (Mitra)
                                </p>
                                <div class="flex justify-between text-xs text-slate-600 py-1 border-b border-slate-100">
                                    <span>Bonus Harian Standar:</span>
                                    <span class="font-bold text-slate-900">{{ formatRupiah(detailPetugas.bonus_harian_mitra) }}</span>
                                </div>
                                <div class="flex justify-between text-xs font-bold text-slate-800 pt-1">
                                    <span>Total Rate per Hari Kerja:</span>
                                    <span class="text-amber-700">
                                        {{ formatRupiah(detailPetugas.bonus_harian_mitra) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Rekapitulasi Presensi Bonus Periode Ini -->
                            <div class="p-3.5 rounded-xl border border-slate-200 space-y-2.5">
                                <p class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                    Rekap Presensi (Rentang {{ dateColumns.length }} Hari)
                                </p>
                                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center">
                                    <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                                        <p class="text-[10px] text-emerald-700 font-bold uppercase">Hadir (H)</p>
                                        <p class="text-base font-bold text-emerald-900">{{ computedBonusStats[detailPetugas.id]?.hadir ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-teal-50 border border-teal-200" title="Bonus Dipotong Setengah (50%)">
                                        <p class="text-[10px] text-teal-700 font-bold uppercase">½ Hari</p>
                                        <p class="text-base font-bold text-teal-900">{{ computedBonusStats[detailPetugas.id]?.setengahHari ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200">
                                        <p class="text-[10px] text-slate-700 font-bold uppercase">Libur (L)</p>
                                        <p class="text-base font-bold text-slate-900">{{ computedBonusStats[detailPetugas.id]?.libur ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-amber-50 border border-amber-200">
                                        <p class="text-[10px] text-amber-700 font-bold uppercase">Izin (I)</p>
                                        <p class="text-base font-bold text-amber-900">{{ computedBonusStats[detailPetugas.id]?.izin ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-blue-50 border border-blue-200">
                                        <p class="text-[10px] text-blue-700 font-bold uppercase">Sakit (S)</p>
                                        <p class="text-base font-bold text-blue-900">{{ computedBonusStats[detailPetugas.id]?.sakit ?? 0 }}</p>
                                    </div>
                                    <div class="p-2 rounded-lg bg-rose-50 border border-rose-200">
                                        <p class="text-[10px] text-rose-700 font-bold uppercase">Tanpa Ket</p>
                                        <p class="text-base font-bold text-rose-900">{{ computedBonusStats[detailPetugas.id]?.tanpaKeterangan ?? 0 }}</p>
                                    </div>
                                </div>

                                <!-- Total Hari Kerja Terhitung -->
                                <div class="p-2.5 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-between text-xs">
                                    <span class="text-slate-600 font-medium">Hari Kerja Terhitung (Mandays):</span>
                                    <span class="font-bold text-slate-900">
                                        {{ computedBonusStats[detailPetugas.id]?.hadir ?? 0 }} penuh + {{ computedBonusStats[detailPetugas.id]?.setengahHari ?? 0 }} (½) =
                                        <strong class="text-emerald-700 text-sm">{{ computedBonusStats[detailPetugas.id]?.mandays ?? 0 }} mandays</strong>
                                    </span>
                                </div>

                                <!-- Total Hak Bonus Bersih -->
                                <div class="p-3.5 rounded-xl bg-amber-100/70 border border-amber-300 mt-2 flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-bold text-amber-800 uppercase tracking-wider">Total Hak Bonus Diterima</p>
                                        <p class="text-[11px] text-amber-700 mt-0.5">
                                            <span v-if="computedBonusStats[detailPetugas.id]?.isBonusVarying">
                                                Tarif bervariasi (Rp {{ formatRibuan(computedBonusStats[detailPetugas.id]?.minBonus) }} - Rp {{ formatRibuan(computedBonusStats[detailPetugas.id]?.maxBonus) }})
                                            </span>
                                            <span v-else>
                                                {{ computedBonusStats[detailPetugas.id]?.mandays ?? 0 }} mandays x {{ formatRibuan(computedBonusStats[detailPetugas.id]?.bonusPerHari ?? 0) }}
                                            </span>
                                        </p>
                                    </div>
                                    <p class="text-xl font-black text-amber-950">
                                        {{ formatRupiah(computedBonusStats[detailPetugas.id]?.totalBonus ?? 0) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Tabel Rincian Riwayat Harian di Modal Slip -->
                            <div class="p-3.5 rounded-xl border border-slate-200 space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <Clock class="h-3.5 w-3.5 text-amber-600" />
                                        <span>Rincian Log Harian ({{ dateColumns.length }} Hari Kerja)</span>
                                    </p>
                                    <span class="text-[10px] text-slate-500 font-mono">
                                        {{ formatTanggalIndo(tanggalMulai) }} – {{ formatTanggalIndo(tanggalSelesai) }}
                                    </span>
                                </div>

                                <div class="overflow-x-auto max-h-56 overflow-y-auto rounded-lg border border-slate-200">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-slate-100 text-slate-700 font-bold text-[10px] sticky top-0 z-10 border-b border-slate-200">
                                            <tr>
                                                <th class="py-1.5 px-2.5 w-8 text-center">No</th>
                                                <th class="py-1.5 px-2.5">Tanggal</th>
                                                <th class="py-1.5 px-2 text-center">Presensi</th>
                                                <th class="py-1.5 px-2.5">Jabatan Snapshot</th>
                                                <th class="py-1.5 px-2.5 text-right">Tarif Bonus</th>
                                                <th class="py-1.5 px-2.5 text-right font-bold text-amber-800">Hak Bonus</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-[10px]">
                                            <tr
                                                v-for="(dt, idx) in dateColumns"
                                                :key="dt.dateStr"
                                                :class="[
                                                    'hover:bg-slate-50 transition-colors',
                                                    dt.dateStr === todayStr ? 'bg-amber-50/50 font-semibold' : '',
                                                ]"
                                            >
                                                <td class="py-1.5 px-2.5 text-center text-slate-400">{{ idx + 1 }}</td>
                                                <td class="py-1.5 px-2.5">
                                                    <span class="font-medium text-slate-800">{{ formatTanggalIndo(dt.dateStr) }}</span>
                                                    <span class="text-slate-400 ml-1">({{ dt.dayName }})</span>
                                                </td>
                                                <td class="py-1.5 px-2 text-center">
                                                    <span
                                                        :class="[
                                                            'px-1.5 py-0.5 rounded font-bold text-[9px]',
                                                            getStatusBadgeStyle(matrixPresensi[detailPetugas.id]?.[dt.dateStr] || ''),
                                                        ]"
                                                    >
                                                        {{ getStatusLabel(matrixPresensi[detailPetugas.id]?.[dt.dateStr] || '') }}
                                                    </span>
                                                </td>
                                                <td class="py-1.5 px-2.5 text-slate-700">
                                                    {{ getDailyJabatan(detailPetugas, dt.dateStr) }}
                                                </td>
                                                <td class="py-1.5 px-2.5 text-right font-mono text-slate-600">
                                                    {{ formatRupiah(getDailyBonus(detailPetugas, dt.dateStr)) }}
                                                </td>
                                                <td class="py-1.5 px-2.5 text-right font-mono font-bold text-amber-700">
                                                    {{ formatRupiah(getDailyHonorBonus(detailPetugas, dt.dateStr)) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2">
                            <button
                                type="button"
                                @click="downloadSlipBonusSinglePetugas(detailPetugas)"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-2xs transition-colors cursor-pointer text-center"
                                title="Download Slip Bonus Mitra resmi dalam format PDF"
                            >
                                <FileText class="h-3.5 w-3.5 shrink-0" />
                                <span>Download Slip Bonus (.pdf)</span>
                            </button>
                            <button
                                type="button"
                                @click="closeDetailModal"
                                class="w-full sm:w-auto px-4 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer text-center"
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
