<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
import { 
    formatTanggalIndo, 
    applyKopSurat, 
    applyLembarPengesahan, 
    colToLetter 
} from "@/Services/exportPetugasHelper";
import { 
    LOGO_BGN_RAW_BASE64, 
    LOGO_YAYASAN_RAW_BASE64 
} from "@/Services/logoBase64Helper";
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
    DollarSign,
    Banknote,
    Pencil,
    Scale,
    Calculator,
    ChevronUp,
    ClipboardPaste,
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
    initialTarifMap: { type: Object, default: () => ({}) },
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
function isStaffKeamanan(p) {
    if (!p) return false;
    const j = (p.jabatan || "").toLowerCase();
    return j.includes("keamanan") || j.includes("security") || j.includes("satpam");
}

function toggleBarisStatus(petugasId) {
    const dates = dateColumns.value;
    if (dates.length === 0) return;
    if (!matrixShifting.value[petugasId]) {
        matrixShifting.value[petugasId] = {};
    }

    const p = props.petugas.find((x) => x.id === petugasId);
    const isKeam = isStaffKeamanan(p);

    // Ambil acuan dari hari kerja pertama (khusus keamanan mencakup semua hari), atau hari pertama
    const workDays = isKeam ? dates : dates.filter((d) => !d.isWeekend);
    const sampleDay = workDays.length > 0 ? workDays[0] : dates[0];
    const curStatus = matrixShifting.value[petugasId][sampleDay.dateStr] || "";

    // Cek apakah seragam di semua hari kerja
    const checkDays = workDays.length > 0 ? workDays : dates;
    const isAllSame = checkDays.every(
        (d) => (matrixShifting.value[petugasId][d.dateStr] || "") === curStatus
    );

    // Siklus: H (Masuk) ➜ L (Semua Libur) ➜ Kosong (·) ➜ H
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
            // Jika H: hari kerja diset H; khusus Keamanan tetap masuk (H) di Sabtu & Minggu, personil lain weekend diset L
            if (col.isWeekend && !isKeam) {
                matrixShifting.value[petugasId][col.dateStr] = "L";
            } else {
                matrixShifting.value[petugasId][col.dateStr] = "H";
            }
        } else {
            matrixShifting.value[petugasId][col.dateStr] = nextSt;
        }
    });

    isDirty.value = true;
    const pNama = p?.nama || "Petugas";
    const labelDesc =
        nextSt === "H"
            ? (isKeam ? "Shift Masuk (H) [Termasuk Sabtu & Minggu]" : "Shift Masuk (H) [Sabtu & Minggu Libur]")
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

// ─── Currency Helpers ─────────────────────────────────────────────────────────
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

// ─── Custom Shifting Tarif (Hanya berlaku untuk Shifting, tidak mengubah gaji utama BGN) ───
const customTarifShifting = ref({});

function initTarifMap() {
    const map = {};
    (props.petugas || []).forEach((p) => {
        if (props.initialTarifMap && props.initialTarifMap[p.id] !== undefined) {
            map[p.id] = Number(props.initialTarifMap[p.id]);
        } else if (p.gaji_harian_shifting !== null && p.gaji_harian_shifting !== undefined) {
            map[p.id] = Number(p.gaji_harian_shifting);
        }
    });
    customTarifShifting.value = map;
}

initTarifMap();

watch(
    () => [props.petugas, props.initialTarifMap],
    () => {
        initTarifMap();
    },
    { deep: true }
);

function getTarifShifting(petugasId) {
    if (customTarifShifting.value[petugasId] !== undefined) {
        return Number(customTarifShifting.value[petugasId]) || 0;
    }
    const p = props.petugas.find((x) => x.id === petugasId);
    return Number(p?.gaji_harian_shifting ?? p?.gaji_harian_bgn) || 0;
}

function getTarifMaster(petugasId) {
    const p = props.petugas.find((x) => x.id === petugasId);
    return Number(p?.gaji_harian_bgn) || 0;
}

function isCustomTarif(petugasId) {
    const current = getTarifShifting(petugasId);
    const master = getTarifMaster(petugasId);
    return current !== master;
}

// ─── Inline Cell Editing Tarif Shifting (Langsung di Tabel tanpa Modal) ─────
const editingPetugasTarifId = ref(null);
const inlineTarifInput = ref("");
const inlineInputRef = ref(null);

function startInlineEditTarif(p) {
    if (!p) return;
    editingPetugasTarifId.value = p.id;
    const curVal = getTarifShifting(p.id);
    inlineTarifInput.value = curVal > 0 ? formatRibuan(curVal) : "";
    nextTick(() => {
        if (inlineInputRef.value) {
            inlineInputRef.value.focus();
            inlineInputRef.value.select();
        }
    });
}

function onInlineTarifInput(e) {
    const raw = e.target.value.replace(/\D/g, "");
    const num = parseInt(raw, 10) || 0;
    inlineTarifInput.value = num > 0 ? formatRibuan(num) : "";
}

function commitInlineTarifEdit(p) {
    if (editingPetugasTarifId.value !== p.id) return;

    const raw = String(inlineTarifInput.value).replace(/\D/g, "");
    const newRate = parseInt(raw, 10) || 0;
    const oldRate = getTarifShifting(p.id);

    editingPetugasTarifId.value = null;

    if (newRate === oldRate) return;

    applyAndSaveTarif(p, newRate);
}

function cancelInlineTarifEdit() {
    editingPetugasTarifId.value = null;
    inlineTarifInput.value = "";
}

function resetTarifToMaster(p, e) {
    if (e) e.stopPropagation();
    const masterRate = getTarifMaster(p.id);
    delete customTarifShifting.value[p.id];
    customTarifShifting.value = { ...customTarifShifting.value };
    isDirty.value = true;

    // Kirim request reset ke backend
    router.post(
        route("petugas.shifting.update-tarif"),
        {
            petugas_id: p.id,
            tarif_harian: null,
            is_reset: true,
            tanggal_mulai: tanggalMulai.value,
            tanggal_selesai: tanggalSelesai.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showToast(`Tarif ${p.nama} dikembalikan ke tarif master BGN (${formatRupiah(masterRate)}/hari).`);
            },
        }
    );
}

function applyAndSaveTarif(p, newRate) {
    const masterRate = getTarifMaster(p.id);
    const isReset = newRate === masterRate;

    if (isReset) {
        delete customTarifShifting.value[p.id];
        customTarifShifting.value = { ...customTarifShifting.value };
    } else {
        customTarifShifting.value = { ...customTarifShifting.value, [p.id]: newRate };
    }
    isDirty.value = true;

    router.post(
        route("petugas.shifting.update-tarif"),
        {
            petugas_id: p.id,
            tarif_harian: isReset ? null : newRate,
            is_reset: isReset,
            tanggal_mulai: tanggalMulai.value,
            tanggal_selesai: tanggalSelesai.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showToast(
                    isReset
                        ? `Tarif ${p.nama} disesuaikan sama dengan master BGN (${formatRupiah(masterRate)}/hari).`
                        : `Tarif shifting ${p.nama} diubah ke ${formatRupiah(newRate)}/hari (hanya shifting, master aman).`
                );
            },
            onError: () => {
                showToast("Tarif shifting diubah lokal (gagal sinkron ke server).");
            },
        }
    );
}

// ─── Bulk Paste Deret Kolom Tarif Shifting (Excel / Spreadsheet) ─────────────
const isPasteTarifModalOpen = ref(false);
const pasteTarifRawText = ref("");
const pasteTarifStartPetugasId = ref(null);

function parseCurrencyString(str) {
    if (!str) return 0;
    let s = String(str).trim();
    // Hilangkan desimal seperti ,00 atau .00 di akhir jika ada
    s = s.replace(/[,.]00$/, "");
    const num = parseInt(s.replace(/\D/g, ""), 10);
    return isNaN(num) ? 0 : num;
}

function handlePasteTarifColumn(e, startPetugas) {
    const clipboardData = e.clipboardData || window.clipboardData;
    const pastedText = clipboardData ? clipboardData.getData("text") : "";
    if (!pastedText) return;

    const lines = pastedText
        .split(/\r?\n/)
        .map((l) => l.trim())
        .filter((l) => l.length > 0);

    // Jika hanya 1 baris dan sedang fokus di inline input cell yang sama, biarkan paste bawaan input
    if (lines.length <= 1 && editingPetugasTarifId.value === startPetugas.id) {
        return;
    }

    // Jika multi-baris atau dipaste langsung di cell (bukan mode edit input 1 baris)
    e.preventDefault();

    const startIdx = filteredPetugas.value.findIndex((p) => p.id === startPetugas.id);
    if (startIdx === -1) return;

    const updates = {};
    let appliedCount = 0;

    lines.forEach((line, offset) => {
        const target = filteredPetugas.value[startIdx + offset];
        if (!target) return;
        const rate = parseCurrencyString(line);
        if (rate >= 0) {
            updates[target.id] = rate;
            appliedCount++;
        }
    });

    if (appliedCount > 0) {
        customTarifShifting.value = { ...customTarifShifting.value, ...updates };
        isDirty.value = true;
        editingPetugasTarifId.value = null;
        showToast(`Berhasil menempelkan ${appliedCount} tarif harian sekaligus mulai dari ${startPetugas.nama}!`);
    } else {
        showToast("Tidak ditemukan angka nominal yang valid pada data yang ditempel.");
    }
}

function openPasteTarifModal(startPetugasId = null) {
    pasteTarifStartPetugasId.value = startPetugasId || filteredPetugas.value[0]?.id || null;
    pasteTarifRawText.value = "";
    isPasteTarifModalOpen.value = true;
}

function closePasteTarifModal() {
    isPasteTarifModalOpen.value = false;
    pasteTarifRawText.value = "";
}

// Preview hasil paste deret di modal
const pasteTarifPreview = computed(() => {
    if (!pasteTarifRawText.value) return [];
    const lines = pasteTarifRawText.value
        .split(/\r?\n/)
        .map((l) => l.trim())
        .filter((l) => l.length > 0);

    const startIdx = filteredPetugas.value.findIndex(
        (p) => p.id === pasteTarifStartPetugasId.value
    );
    if (startIdx === -1) return [];

    return lines.map((line, offset) => {
        const target = filteredPetugas.value[startIdx + offset] || null;
        const newRate = parseCurrencyString(line);
        const curRate = target ? getTarifShifting(target.id) : 0;
        return {
            lineIndex: offset + 1,
            rawText: line,
            petugas: target,
            newRate,
            curRate,
            isChanged: target ? newRate !== curRate : false,
            isOutOfRange: !target,
        };
    });
});

const pasteTarifValidCount = computed(() => {
    return pasteTarifPreview.value.filter((item) => item.petugas && item.newRate >= 0).length;
});

function applyBatchPastedTarif() {
    const preview = pasteTarifPreview.value;
    const updates = {};
    let count = 0;

    preview.forEach((item) => {
        if (item.petugas && item.newRate >= 0) {
            updates[item.petugas.id] = item.newRate;
            count++;
        }
    });

    if (count > 0) {
        customTarifShifting.value = { ...customTarifShifting.value, ...updates };
        isDirty.value = true;
        showToast(`Berhasil menerapkan ${count} tarif harian sekaligus dari hasil paste!`);
        closePasteTarifModal();
    } else {
        showToast("Tidak ada data tarif yang valid untuk diterapkan.");
    }
}

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

    const p = props.petugas.find((x) => x.id === petugasId);
    const tarifHarian = getTarifShifting(petugasId);
    const totalHonor = Math.round(hadir * tarifHarian);

    return {
        hadir,
        libur,
        totalScheduled,
        totalDays,
        rasioKerja,
        tarifHarian,
        totalHonor,
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
        let dailyHonor = 0;

        list.forEach((p) => {
            const st = matrixShifting.value[p.id]?.[col.dateStr] || "";
            const rate = getTarifShifting(p.id);
            if (st === "H") {
                masukCount++;
                dailyHonor += rate;
            } else if (st === "H2") {
                masukCount += 0.5;
                dailyHonor += rate * 0.5;
            } else if (st === "L") {
                liburCount++;
            } else {
                unassignedCount++;
            }
        });

        const totalActive = list.length;
        const rasioMasuk = totalActive > 0 ? Math.round((masukCount / totalActive) * 100) : 0;

        res[col.dateStr] = {
            masukCount,
            liburCount,
            unassignedCount,
            totalActive,
            rasioMasuk,
            dailyHonor: Math.round(dailyHonor),
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
            totalMandays: 0,
            totalGajiHarian: 0,
            totalHonorTerhitung: 0,
            rata2HonorPerHari: 0,
            rata2HonorHariKerja: 0,
        };
    }

    let sumMasuk = 0;
    let sumLibur = 0;
    let totalHonorTerhitung = 0;
    let totalGajiHarian = 0;

    filteredPetugas.value.forEach((p) => {
        const stats = getPetugasShiftStats(p.id);
        totalHonorTerhitung += stats.totalHonor;
        totalGajiHarian += stats.tarifHarian;
    });

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

    // Rata-rata estimasi beban gaji harian
    const rata2HonorPerHari = dates.length > 0 ? Math.round(totalHonorTerhitung / dates.length) : 0;
    const activeDatesCount = dates.filter((d) => (perDateSummary.value[d.dateStr]?.dailyHonor || 0) > 0).length;
    const rata2HonorHariKerja = activeDatesCount > 0 ? Math.round(totalHonorTerhitung / activeDatesCount) : rata2HonorPerHari;

    return {
        totalPersonil: totalList,
        rata2MasukPerHari: avgMasuk,
        rata2LiburPerHari: avgLibur,
        keseimbanganShift: `${rasio}% Masuk`,
        totalHari: dates.length,
        totalMandays: Math.round(sumMasuk * 10) / 10,
        totalGajiHarian,
        totalHonorTerhitung,
        rata2HonorPerHari,
        rata2HonorHariKerja,
    };
});

// ─── Area Bantu & Simulasi 2 Opsi Skema Shifting ───────────────────────────
const isSimulasiExpanded = ref(true);
const simulasiFilterDivisi = ref("all");

// Map data kustom simulasi per personil: { [petugasId]: { penguranganGaji: number, hariKerjaSkema1: number, hariKerjaSkema2: number } }
const simulasiCustomMap = ref({});

function getPersonilFullDays(p) {
    return isStaffKeamanan(p)
        ? (dateColumns.value.length || 1)
        : (totalWorkDaysCount.value || dateColumns.value.length || 1);
}

function syncSimulasiWithTable() {
    (props.petugas || []).forEach((p) => {
        const stats = getPetugasShiftStats(p.id);
        const fullDays = getPersonilFullDays(p);
        if (!simulasiCustomMap.value[p.id]) {
            simulasiCustomMap.value[p.id] = {
                penguranganGaji: 25000,
                hariKerjaSkema1: fullDays,
                hariKerjaSkema2: stats.hadir,
            };
        } else {
            simulasiCustomMap.value[p.id].hariKerjaSkema1 = fullDays;
            simulasiCustomMap.value[p.id].hariKerjaSkema2 = stats.hadir;
        }
    });
}

// Otomatis sinkronkan ketika matriks shifting, tarif lokal shifting, tanggal, atau petugas berubah
watch(
    [matrixShifting, dateColumns, customTarifShifting, () => props.petugas],
    () => {
        syncSimulasiWithTable();
    },
    { deep: true, immediate: true }
);

function getPersonilSimulasi(p) {
    const fullDays = getPersonilFullDays(p);
    const stats = getPetugasShiftStats(p.id);
    const data = simulasiCustomMap.value[p.id] || {
        penguranganGaji: 25000,
        hariKerjaSkema1: fullDays,
        hariKerjaSkema2: stats.hadir,
    };
    // Gaji awal di shifting bersifat lokal mengikuti tarif yang ditentukan pada halaman shifting ini
    const gajiAwal = getTarifShifting(p.id);

    // Skema 1: Pengurangan Tarif Gaji (Hari Kerja Tetap Penuh Sesuai Kalender Atas)
    const pengurangan = Number(data.penguranganGaji) || 0;
    const gajiFinalSkema1 = Math.max(0, gajiAwal - pengurangan);
    const hariKerjaSkema1 = Number(data.hariKerjaSkema1) || fullDays;
    const totalGajiSkema1 = Math.round(gajiFinalSkema1 * hariKerjaSkema1);

    // Skema 2: Pengurangan Hari Kerja (Tarif Gaji Tetap Utuh, Hari Kerja Otomatis Mengikuti Jadwal Shifting)
    const gajiFinalSkema2 = gajiAwal;
    const hariKerjaSkema2 = typeof data.hariKerjaSkema2 === "number" ? data.hariKerjaSkema2 : stats.hadir;
    const totalGajiSkema2 = Math.round(gajiFinalSkema2 * hariKerjaSkema2);
    const penguranganHariKerja = Math.max(0, hariKerjaSkema1 - hariKerjaSkema2);

    // Skema 3: Kombinasi Skema 1 & 2 (Tarif Dipotong & Hari Kerja Shifting)
    const gajiFinalSkema3 = gajiFinalSkema1;
    const hariKerjaSkema3 = hariKerjaSkema2;
    const totalGajiSkema3 = Math.round(gajiFinalSkema3 * hariKerjaSkema3);

    // Selisih
    const selisihGaji = totalGajiSkema1 - totalGajiSkema2; // S1 vs S2
    const selisihGajiSkema3Vs1 = totalGajiSkema1 - totalGajiSkema3; // S1 vs S3 (penghematan kombinasi)

    // Rincian perhitungan harian / per 1 hari kerja
    const hariAcuan = hariKerjaSkema1 > 0 ? hariKerjaSkema1 : 1;
    const gajiPerHariSkema1 = Math.round(totalGajiSkema1 / hariAcuan);
    const gajiPerHariSkema2 = Math.round(totalGajiSkema2 / hariAcuan);
    const gajiPerHariSkema3 = Math.round(totalGajiSkema3 / hariAcuan);
    const selisihGajiPerHari = Math.round(selisihGaji / hariAcuan);

    return {
        gajiAwal,
        pengurangan,
        gajiFinalSkema1,
        hariKerjaSkema1,
        totalGajiSkema1,
        gajiPerHariSkema1,
        gajiFinalSkema2,
        hariKerjaSkema2,
        penguranganHariKerja,
        totalGajiSkema2,
        gajiPerHariSkema2,
        gajiFinalSkema3,
        hariKerjaSkema3,
        totalGajiSkema3,
        gajiPerHariSkema3,
        selisihGaji,
        selisihGajiPerHari,
        selisihGajiSkema3Vs1,
    };
}

function updateSimulasiPengurangan(petugasId, val) {
    if (!simulasiCustomMap.value[petugasId]) {
        const fullDays = getPersonilFullDays(props.petugas?.find((p) => p.id === petugasId) || {});
        const stats = getPetugasShiftStats(petugasId);
        simulasiCustomMap.value[petugasId] = { penguranganGaji: 0, hariKerjaSkema1: fullDays, hariKerjaSkema2: stats.hadir };
    }
    const cleanNum = parseInt(String(val).replace(/\D/g, ""), 10) || 0;
    simulasiCustomMap.value[petugasId].penguranganGaji = cleanNum;
}

function updateSimulasiHariKerjaSkema2(petugasId, val) {
    if (!simulasiCustomMap.value[petugasId]) {
        const fullDays = getPersonilFullDays(props.petugas?.find((p) => p.id === petugasId) || {});
        const stats = getPetugasShiftStats(petugasId);
        simulasiCustomMap.value[petugasId] = { penguranganGaji: 0, hariKerjaSkema1: fullDays, hariKerjaSkema2: stats.hadir };
    }
    const num = parseFloat(String(val).replace(/,/g, ".")) || 0;
    simulasiCustomMap.value[petugasId].hariKerjaSkema2 = Math.max(0, num);
}

const customPenguranganInput = ref("25.000");

function setBatchPenguranganSimulasi(amount) {
    const list = filteredPetugasSimulasi.value;
    list.forEach((p) => {
        if (!simulasiCustomMap.value[p.id]) {
            simulasiCustomMap.value[p.id] = {
                penguranganGaji: 0,
                hariKerjaSkema1: getPersonilFullDays(p),
                hariKerjaSkema2: getPetugasShiftStats(p.id).hadir,
            };
        }
        simulasiCustomMap.value[p.id].penguranganGaji = amount;
    });
    customPenguranganInput.value = amount > 0 ? amount.toLocaleString("id-ID") : "";
    showToast(`Pengurangan gaji ${formatRupiah(amount)} diterapkan ke ${list.length} personil pada Skema 1.`);
}

function applyCustomPenguranganSimulasi() {
    const cleanNum = parseInt(String(customPenguranganInput.value).replace(/\D/g, ""), 10) || 0;
    setBatchPenguranganSimulasi(cleanNum);
}

function applySkema1TarifToShiftingTable() {
    let appliedCount = 0;
    (props.petugas || []).forEach((p) => {
        const sim = getPersonilSimulasi(p);
        if (sim.gajiFinalSkema1 > 0) {
            customTarifShifting.value[p.id] = sim.gajiFinalSkema1;
            appliedCount++;
        }
    });
    isDirty.value = true;
    showToast(`Tarif final Skema 1 berhasil diterapkan ke kolom Tarif Harian Shifting untuk ${appliedCount} personil!`);
}

const filteredPetugasSimulasi = computed(() => {
    let list = props.petugas || [];
    if (simulasiFilterDivisi.value !== "all") {
        list = list.filter((p) => (p.jabatan || "Umum") === simulasiFilterDivisi.value);
    }
    return list;
});

const simulasiSummary = computed(() => {
    let totalBebanSkema1 = 0;
    let totalBebanSkema2 = 0;
    let totalBebanSkema3 = 0;
    let totalGajiAwalNormal = 0;
    let totalHariSkema1 = 0;
    let totalHariSkema2 = 0;
    let totalHariSkema3 = 0;

    const list = filteredPetugasSimulasi.value;
    list.forEach((p) => {
        const sim = getPersonilSimulasi(p);
        totalBebanSkema1 += sim.totalGajiSkema1;
        totalBebanSkema2 += sim.totalGajiSkema2;
        totalBebanSkema3 += sim.totalGajiSkema3;
        totalGajiAwalNormal += sim.gajiAwal * sim.hariKerjaSkema1;
        totalHariSkema1 += sim.hariKerjaSkema1;
        totalHariSkema2 += sim.hariKerjaSkema2;
        totalHariSkema3 += sim.hariKerjaSkema3;
    });

    const count = list.length || 1;
    const selisihBeban = totalBebanSkema1 - totalBebanSkema2;
    const selisihBebanSkema3Vs1 = totalBebanSkema1 - totalBebanSkema3;
    const selisihBebanSkema2Vs3 = totalBebanSkema2 - totalBebanSkema3;
    const hematVsNormalSkema1 = Math.max(0, totalGajiAwalNormal - totalBebanSkema1);
    const hematVsNormalSkema2 = Math.max(0, totalGajiAwalNormal - totalBebanSkema2);
    const hematVsNormalSkema3 = Math.max(0, totalGajiAwalNormal - totalBebanSkema3);

    // Hari kerja acuan untuk menghitung nilai per 1 hari (otomatis sesuai kalender kerja aktif di atas)
    const hariKerjaAcuan = totalWorkDaysCount.value || dateColumns.value.length || 1;
    const safeHari = hariKerjaAcuan > 0 ? hariKerjaAcuan : 1;

    // Beban Total per 1 Hari untuk Unit SPPG
    const bebanHarianUnitSkema1 = Math.round(totalBebanSkema1 / safeHari);
    const bebanHarianUnitSkema2 = Math.round(totalBebanSkema2 / safeHari);
    const bebanHarianUnitSkema3 = Math.round(totalBebanSkema3 / safeHari);
    const selisihBebanHarianUnit = Math.round(selisihBeban / safeHari);
    const selisihBebanHarianUnit3Vs1 = Math.round(selisihBebanSkema3Vs1 / safeHari);

    // Rata-rata per orang
    const rata2HonorSkema1 = Math.round(totalBebanSkema1 / count);
    const rata2HonorSkema2 = Math.round(totalBebanSkema2 / count);
    const rata2HonorSkema3 = Math.round(totalBebanSkema3 / count);
    const rata2PerHariPerOrangSkema1 = Math.round(bebanHarianUnitSkema1 / count);
    const rata2PerHariPerOrangSkema2 = Math.round(bebanHarianUnitSkema2 / count);
    const rata2PerHariPerOrangSkema3 = Math.round(bebanHarianUnitSkema3 / count);
    const rata2SelisihStafPerHari = Math.round(selisihBebanHarianUnit / count);

    return {
        totalBebanSkema1,
        totalBebanSkema2,
        totalBebanSkema3,
        totalGajiAwalNormal,
        selisihBeban,
        selisihBebanSkema3Vs1,
        selisihBebanSkema2Vs3,
        bebanHarianUnitSkema1,
        bebanHarianUnitSkema2,
        bebanHarianUnitSkema3,
        selisihBebanHarianUnit,
        selisihBebanHarianUnit3Vs1,
        hematVsNormalSkema1,
        hematVsNormalSkema2,
        hematVsNormalSkema3,
        rata2HonorSkema1,
        rata2HonorSkema2,
        rata2HonorSkema3,
        rata2PerHariPerOrangSkema1,
        rata2PerHariPerOrangSkema2,
        rata2PerHariPerOrangSkema3,
        rata2SelisihStafPerHari,
        totalHariSkema1,
        totalHariSkema2,
        totalHariSkema3,
        hariKerjaAcuan,
        countPersonil: list.length,
    };
});

// ─── Analisis Harian per Tim / Divisi (Komparasi 3 Skema) ─────────────────────
const analisisHarianFilterDivisi = ref("all");
const expandedDatesAnalysis = ref({});
const isAllDatesAnalysisExpanded = ref(true);

function toggleDateAnalysis(dateStr) {
    if (expandedDatesAnalysis.value[dateStr] === undefined) {
        expandedDatesAnalysis.value[dateStr] = !isAllDatesAnalysisExpanded.value;
    } else {
        expandedDatesAnalysis.value[dateStr] = !expandedDatesAnalysis.value[dateStr];
    }
}

function isDateAnalysisExpanded(dateStr) {
    if (expandedDatesAnalysis.value[dateStr] !== undefined) {
        return expandedDatesAnalysis.value[dateStr];
    }
    return isAllDatesAnalysisExpanded.value;
}

function toggleAllDatesAnalysis() {
    isAllDatesAnalysisExpanded.value = !isAllDatesAnalysisExpanded.value;
    dateColumns.value.forEach((d) => {
        expandedDatesAnalysis.value[d.dateStr] = isAllDatesAnalysisExpanded.value;
    });
}

const listDivisiAktif = computed(() => {
    const set = new Set();
    (props.petugas || []).forEach((p) => {
        if (p.jabatan) set.add(p.jabatan);
    });
    return Array.from(set).sort();
});

const dailyAnalysisData = computed(() => {
    const dates = dateColumns.value;
    const petugasList = props.petugas || [];
    const divisions = listDivisiAktif.value.length > 0 ? listDivisiAktif.value : ["Umum"];

    return dates.map((col) => {
        let grandPersonilShift = 0;
        let grandPersonilSkema1 = 0;
        let grandTotalSkema1 = 0;
        let grandTotalSkema2 = 0;
        let grandTotalSkema3 = 0;

        const divisiList = divisions.map((divisiName) => {
            const teamMembers = petugasList.filter((p) => (p.jabatan || "Umum") === divisiName);
            let hadirCount = 0;
            let skema1Count = 0;
            let sumSkema1 = 0;
            let sumSkema2 = 0;
            let sumSkema3 = 0;

            teamMembers.forEach((p) => {
                const sim = getPersonilSimulasi(p);
                const isKeam = isStaffKeamanan(p);
                const isWorkDayNormal = isKeam ? true : !col.isWeekend;

                // Skema 1
                if (isWorkDayNormal) {
                    skema1Count++;
                    sumSkema1 += sim.gajiFinalSkema1;
                }

                // Shifting (Skema 2 & Skema 3)
                const val = matrixShifting.value[p.id]?.[col.dateStr] || "";
                if (val === "H" || val === "H2") {
                    hadirCount++;
                    sumSkema2 += sim.gajiFinalSkema2;
                    sumSkema3 += sim.gajiFinalSkema1;
                }
            });

            grandPersonilShift += hadirCount;
            grandPersonilSkema1 += skema1Count;
            grandTotalSkema1 += sumSkema1;
            grandTotalSkema2 += sumSkema2;
            grandTotalSkema3 += sumSkema3;

            return {
                divisi: divisiName,
                totalAnggota: teamMembers.length,
                personilMasukShift: hadirCount,
                personilMasukSkema1: skema1Count,
                totalSkema1: sumSkema1,
                totalSkema2: sumSkema2,
                totalSkema3: sumSkema3,
                selisih1Vs2: sumSkema1 - sumSkema2,
                selisih1Vs3: sumSkema1 - sumSkema3,
                selisih2Vs3: sumSkema2 - sumSkema3,
            };
        });

        const filteredDivisi = analisisHarianFilterDivisi.value === "all"
            ? divisiList
            : divisiList.filter((d) => d.divisi === analisisHarianFilterDivisi.value);

        return {
            dateStr: col.dateStr,
            dayName: col.dayName,
            dayNum: col.dayNum,
            isWeekend: col.isWeekend,
            isSunday: col.isSunday,
            isSaturday: col.isSaturday,
            isToday: col.isToday,
            totalAnggotaUnit: petugasList.length,
            grandPersonilShift,
            grandPersonilSkema1,
            grandTotalSkema1,
            grandTotalSkema2,
            grandTotalSkema3,
            selisihUnit1Vs2: grandTotalSkema1 - grandTotalSkema2,
            selisihUnit1Vs3: grandTotalSkema1 - grandTotalSkema3,
            selisihUnit2Vs3: grandTotalSkema2 - grandTotalSkema3,
            divisiBreakdown: filteredDivisi,
        };
    });
});

const dailyAnalysisGrandTotal = computed(() => {
    let totalHadirShift = 0;
    let totalBiayaS1 = 0;
    let totalBiayaS2 = 0;
    let totalBiayaS3 = 0;

    dailyAnalysisData.value.forEach((d) => {
        totalHadirShift += d.grandPersonilShift;
        totalBiayaS1 += d.grandTotalSkema1;
        totalBiayaS2 += d.grandTotalSkema2;
        totalBiayaS3 += d.grandTotalSkema3;
    });

    return {
        totalHadirShift,
        totalBiayaS1,
        totalBiayaS2,
        totalBiayaS3,
        selisih1Vs2: totalBiayaS1 - totalBiayaS2,
        selisih1Vs3: totalBiayaS1 - totalBiayaS3,
        selisih2Vs3: totalBiayaS2 - totalBiayaS3,
    };
});

// ─── Modal Generator Pola Shifting ───────────────────────────────────────────
const isGeneratorModalOpen = ref(false);
const generatorPattern = ref("rotasi_mingguan"); // 'rotasi_mingguan' | 'divisi_rotasi' | 'rotasi_2_tim' | 'senin_jumat'
const generatorBlokHari = ref(1); // 1 | 2 | 3 | 5
const generatorDivisiRatio = ref("50"); // '50' | '67' | '1_orang' | 'kustom'
const generatorTargetDivisi = ref("all");
const generatorStartType = ref("tim_a_first"); // 'tim_a_first' | 'tim_b_first'
const generatorTeamMethod = ref("ganjil_genap"); // 'ganjil_genap' | 'separuh'
const generatorExcludeWeekend = ref(true); // default: Sabtu dan Minggu Libur
const generatorSingleStaffFullWork = ref(true); // Divisi dengan 1 personil (Kepala/Gizi) masuk penuh
const generatorCustomDays = ref(10);

// ─── Pola Rotasi Mingguan (Per Minggu Bergilir Orang) ─────────────────────────
const generatorWeeklyType = ref("bagi_rata"); // 'bagi_rata' | 'bagi_minggu' | '1_orang'
const generatorWeeklyWorkDays = ref("senin_jumat"); // 'senin_jumat' | 'senin_sabtu' | 'semua' | 'kustom'
const generatorWeeklyCustomDays = ref([1, 2, 3, 4, 5]); // 1=Sen, 2=Sel, 3=Rab, 4=Kam, 5=Jum
const generatorWeeklyGrouping = ref("blok_7"); // 'blok_7' | 'kalender'
const previewDivisi = ref("");

const daysOfWeekList = [
    { num: 1, label: "Sen", fullName: "Senin" },
    { num: 2, label: "Sel", fullName: "Selasa" },
    { num: 3, label: "Rab", fullName: "Rabu" },
    { num: 4, label: "Kam", fullName: "Kamis" },
    { num: 5, label: "Jum", fullName: "Jumat" },
    { num: 6, label: "Sab", fullName: "Sabtu" },
    { num: 0, label: "Min", fullName: "Minggu" },
];

function toggleWeeklyCustomDay(dayNum) {
    const idx = generatorWeeklyCustomDays.value.indexOf(dayNum);
    if (idx >= 0) {
        if (generatorWeeklyCustomDays.value.length > 1) {
            generatorWeeklyCustomDays.value.splice(idx, 1);
        }
    } else {
        generatorWeeklyCustomDays.value.push(dayNum);
    }
}

function setWeeklyPresetDays(preset) {
    generatorWeeklyWorkDays.value = preset;
    if (preset === "senin_jumat") {
        generatorWeeklyCustomDays.value = [1, 2, 3, 4, 5];
    } else if (preset === "senin_sabtu") {
        generatorWeeklyCustomDays.value = [1, 2, 3, 4, 5, 6];
    } else if (preset === "semua") {
        generatorWeeklyCustomDays.value = [1, 2, 3, 4, 5, 6, 0];
    }
}

function formatTglSingkat(dateStr) {
    if (!dateStr) return "-";
    const parts = dateStr.split("-");
    if (parts.length < 3) return dateStr;
    const bulanPendek = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];
    const bln = bulanPendek[parseInt(parts[1], 10) - 1] || parts[1];
    return `${parts[2]} ${bln}`;
}

// Menghitung indeks personil aktif bergilir yang dibagi rata
function getWeeklyActiveIndices(n, weekIdx, totalWeeks, weeklyType = "bagi_rata", startType = "tim_a_first", teamMethod = "ganjil_genap") {
    if (n <= 1) return [0];

    const offset = startType === "tim_b_first" ? 1 : 0;

    if (weeklyType === "bagi_rata") {
        // Bagi rata 50% seluruh personil menjadi 2 regu sama banyak
        const reguA = [];
        const reguB = [];

        if (teamMethod === "separuh") {
            const half = Math.ceil(n / 2);
            for (let i = 0; i < n; i++) {
                if (i < half) reguA.push(i);
                else reguB.push(i);
            }
        } else {
            // Default: selang-seling (ganjil-genap)
            for (let i = 0; i < n; i++) {
                if (i % 2 === 0) reguA.push(i);
                else reguB.push(i);
            }
        }

        const isReguATurn = ((weekIdx + offset) % 2) === 0;
        return isReguATurn ? reguA : reguB;
    } else if (weeklyType === "bagi_minggu") {
        // Bagi rata seluruh personil sebanyak jumlah minggu dalam periode
        const W = Math.max(1, totalWeeks);
        const active = [];
        for (let i = 0; i < n; i++) {
            const assignedWeek = (i + offset) % W;
            if (assignedWeek === (weekIdx % W)) {
                active.push(i);
            }
        }
        return active.length > 0 ? active : [0];
    } else if (weeklyType === "1_orang") {
        const turnIndex = (weekIdx + offset) % n;
        return [turnIndex];
    }

    return [0];
}

const weekPeriods = computed(() => {
    const cols = dateColumns.value;
    if (!cols || cols.length === 0) return [];

    const weeks = [];
    if (generatorWeeklyGrouping.value === "kalender") {
        let currentWeek = [];
        let weekNum = 1;
        cols.forEach((col, idx) => {
            if (idx > 0 && col.dayOfWeek === 1) {
                weeks.push({
                    weekNum,
                    label: `Minggu ${weekNum}`,
                    dates: currentWeek,
                    startDate: currentWeek[0]?.dateStr,
                    endDate: currentWeek[currentWeek.length - 1]?.dateStr,
                });
                weekNum++;
                currentWeek = [];
            }
            currentWeek.push(col);
        });
        if (currentWeek.length > 0) {
            weeks.push({
                weekNum,
                label: `Minggu ${weekNum}`,
                dates: currentWeek,
                startDate: currentWeek[0]?.dateStr,
                endDate: currentWeek[currentWeek.length - 1]?.dateStr,
            });
        }
    } else {
        // Blok 7 Hari (Hari 1-7, 8-14, dst)
        const totalDates = cols.length;
        const totalWeeks = Math.ceil(totalDates / 7);
        for (let w = 1; w <= totalWeeks; w++) {
            const startIdx = (w - 1) * 7;
            const endIdx = Math.min(totalDates, w * 7);
            const chunk = cols.slice(startIdx, endIdx);
            if (chunk.length > 0) {
                weeks.push({
                    weekNum: w,
                    label: `Minggu ${w}`,
                    dates: chunk,
                    startDate: chunk[0]?.dateStr,
                    endDate: chunk[chunk.length - 1]?.dateStr,
                });
            }
        }
    }
    return weeks;
});

// Ringkasan divisi shifting untuk preview simulasi bagi rata
const shiftingDivisiSummary = computed(() => {
    const activeStaff = (props.petugas || []).filter((p) => p.status !== "Nonaktif");
    const wajibSet = new Set(wajibHadirStaffIds.value);
    const shiftingStaff = activeStaff.filter((p) => !wajibSet.has(p.id));

    const grouped = {};
    shiftingStaff.forEach((p) => {
        const j = p.jabatan || "Umum";
        if (!grouped[j]) grouped[j] = [];
        grouped[j].push(p);
    });

    return Object.keys(grouped).map((name) => ({
        name,
        count: grouped[name].length,
        staff: grouped[name],
    }));
});

watch(
    () => shiftingDivisiSummary.value,
    (list) => {
        if (list.length > 0 && (!previewDivisi.value || !list.some((d) => d.name === previewDivisi.value))) {
            const best = [...list].sort((a, b) => b.count - a.count)[0];
            previewDivisi.value = best?.name || list[0].name;
        }
    },
    { immediate: true, deep: true }
);

const weeklySimulationSummary = computed(() => {
    if (generatorPattern.value !== "rotasi_mingguan") return [];
    const weeks = weekPeriods.value;
    if (weeks.length === 0) return [];

    const activeStaff = (props.petugas || []).filter((p) => p.status !== "Nonaktif");
    const wajibSet = new Set(wajibHadirStaffIds.value);
    const shiftingStaff = activeStaff.filter((p) => !wajibSet.has(p.id));

    let sampleStaff = [];
    let currentDivisiName = "";

    if (generatorTargetDivisi.value !== "all") {
        currentDivisiName = generatorTargetDivisi.value;
        sampleStaff = shiftingStaff.filter((p) => (p.jabatan || "Umum") === currentDivisiName);
    } else {
        currentDivisiName = previewDivisi.value || shiftingDivisiSummary.value[0]?.name || "Umum";
        sampleStaff = shiftingStaff.filter((p) => (p.jabatan || "Umum") === currentDivisiName);
        if (sampleStaff.length === 0 && shiftingStaff.length > 0) {
            sampleStaff = shiftingStaff;
        }
    }

    const n = sampleStaff.length;

    return weeks.map((wk, wIdx) => {
        let activeNames = [];
        let offNames = [];

        if (n === 0) {
            activeNames = ["Personil Divisi Shifting"];
            offNames = [];
        } else if (n === 1 && generatorSingleStaffFullWork.value) {
            activeNames = [sampleStaff[0].nama + " (Masuk Penuh)"];
            offNames = [];
        } else {
            const activeIndices = getWeeklyActiveIndices(
                n,
                wIdx,
                weeks.length,
                generatorWeeklyType.value,
                generatorStartType.value,
                generatorTeamMethod.value
            );

            sampleStaff.forEach((s, idx) => {
                if (activeIndices.includes(idx)) {
                    activeNames.push(s.nama);
                } else {
                    offNames.push(s.nama);
                }
            });
        }

        return {
            weekNum: wk.weekNum,
            label: wk.label,
            startDate: wk.startDate,
            endDate: wk.endDate,
            daysCount: wk.dates.length,
            divisiName: currentDivisiName,
            totalStaff: n,
            activeCount: activeNames.length,
            offCount: offNames.length,
            activeNames,
            offNames,
        };
    });
});

const totalWorkDaysCount = computed(() => {
    if (generatorPattern.value === "rotasi_mingguan") {
        if (generatorWeeklyWorkDays.value === "senin_jumat") {
            return dateColumns.value.filter((d) => d.dayOfWeek >= 1 && d.dayOfWeek <= 5).length;
        } else if (generatorWeeklyWorkDays.value === "senin_sabtu") {
            return dateColumns.value.filter((d) => d.dayOfWeek >= 1 && d.dayOfWeek <= 6).length;
        } else if (generatorWeeklyWorkDays.value === "semua") {
            return dateColumns.value.length;
        } else if (generatorWeeklyWorkDays.value === "kustom") {
            return dateColumns.value.filter((d) => generatorWeeklyCustomDays.value.includes(d.dayOfWeek)).length;
        }
    }
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
    generatorPattern.value = "rotasi_mingguan";
    generatorWeeklyType.value = "bagi_rata";
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
            const isKeam = isStaffKeamanan(p);
            allDates.forEach((col) => {
                // Khusus personil keamanan tetap masuk (H) walaupun hari Sabtu & Minggu
                if (!isKeam && generatorExcludeWeekend.value && col.isWeekend) {
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
            const isKeam = isStaffKeamanan(p);
            allDates.forEach((col) => {
                if (isKeam && col.isWeekend) {
                    // Khusus keamanan: tetap masuk di hari sabtu dan minggu
                    matrixShifting.value[p.id][col.dateStr] = "H";
                } else if (!isKeam && generatorExcludeWeekend.value && col.isWeekend) {
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
                const isKeam = isStaffKeamanan(p);
                (isKeam ? allDates : workDates).forEach((col) => {
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
    } else if (generatorPattern.value === "rotasi_mingguan") {
        // ─── POLA ROTASI BERGILIR MINGGUAN (PER MINGGU BERGANTIAN ORANG) ───
        const weeks = weekPeriods.value;
        if (weeks.length === 0) {
            showToast("Rentang tanggal tidak mencukupi untuk pembagian mingguan.");
            return;
        }

        const isDayActive = (col) => {
            if (generatorWeeklyWorkDays.value === "senin_jumat") {
                return col.dayOfWeek >= 1 && col.dayOfWeek <= 5;
            } else if (generatorWeeklyWorkDays.value === "senin_sabtu") {
                return col.dayOfWeek >= 1 && col.dayOfWeek <= 6;
            } else if (generatorWeeklyWorkDays.value === "semua") {
                return true;
            } else if (generatorWeeklyWorkDays.value === "kustom") {
                return generatorWeeklyCustomDays.value.includes(col.dayOfWeek);
            }
            return !col.isWeekend;
        };

        targetDivisiList.forEach((divisiName) => {
            const members = shiftingStaff.filter((p) => (p.jabatan || "Umum") === divisiName);
            const n = members.length;
            if (n === 0) return;

            // Jika hanya 1 orang di divisi dan opsi single staff aktif: masuk penuh pada seluruh hari kerja aktif
            if (n === 1 && generatorSingleStaffFullWork.value) {
                const singleP = members[0];
                const isKeam = isStaffKeamanan(singleP);
                allDates.forEach((col) => {
                    if (isKeam && col.isWeekend) {
                        matrixShifting.value[singleP.id][col.dateStr] = "H";
                    } else if (isDayActive(col)) {
                        matrixShifting.value[singleP.id][col.dateStr] = "H";
                    } else {
                        matrixShifting.value[singleP.id][col.dateStr] = "L";
                    }
                });
                return;
            }

            // Jika 2 orang atau lebih: rotasi bergilir mingguan dibagi rata
            weeks.forEach((wk, wIdx) => {
                const activeIndices = getWeeklyActiveIndices(
                    n,
                    wIdx,
                    weeks.length,
                    generatorWeeklyType.value,
                    generatorStartType.value,
                    generatorTeamMethod.value
                );

                members.forEach((m, idx) => {
                    const isTurnThisWeek = activeIndices.includes(idx);
                    const isKeam = isStaffKeamanan(m);

                    wk.dates.forEach((col) => {
                        if (isTurnThisWeek) {
                            if (isKeam && col.isWeekend) {
                                matrixShifting.value[m.id][col.dateStr] = "H";
                            } else if (isDayActive(col)) {
                                matrixShifting.value[m.id][col.dateStr] = "H";
                            } else {
                                matrixShifting.value[m.id][col.dateStr] = "L";
                            }
                        } else {
                            if (isKeam && col.isWeekend && !generatorExcludeWeekend.value) {
                                matrixShifting.value[m.id][col.dateStr] = "H";
                            } else {
                                matrixShifting.value[m.id][col.dateStr] = "L";
                            }
                        }
                    });
                });
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
        custom_tarif: JSON.stringify(customTarifShifting.value),
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

        let logoBgnId = null;
        let logoYayasanId = null;
        try {
            logoBgnId = workbook.addImage({
                base64: LOGO_BGN_RAW_BASE64,
                extension: "png",
            });
            logoYayasanId = workbook.addImage({
                base64: LOGO_YAYASAN_RAW_BASE64,
                extension: "png",
            });
        } catch (e) {
            console.warn("Gagal memuat logo ke workbook Excel:", e);
        }

        const sheet = workbook.addWorksheet("Jadwal Shifting", {
            pageSetup: {
                orientation: "landscape",
                paperSize: 9, // A4
                fitToPage: true,
                fitToWidth: 1,
                fitToHeight: 0,
            },
        });

        // Hitung total kolom & batas
        // 1: No, 2: Nama, 3: Jabatan, 4: Tarif Harian
        // 5..(4 + dates.length): Tanggal
        // +1: Total Masuk, +2: Total Libur, +3: Rasio Kerja, +4: Total Estimasi Gaji
        const totalCols = 8 + dateColumns.value.length;
        const lastColIndex = 4 + dateColumns.value.length;
        const lastColLetter = colToLetter(totalCols);

        // Lebar kolom diatur awal agar penempatan logo simetris akurat
        sheet.getColumn(1).width = 6;  // No
        sheet.getColumn(2).width = 28; // Nama Personil
        sheet.getColumn(3).width = 24; // Jabatan / Divisi
        sheet.getColumn(4).width = 16; // Tarif Harian (Rp)
        for (let i = 5; i <= lastColIndex; i++) {
            sheet.getColumn(i).width = 11; // Kolom Tanggal
        }
        sheet.getColumn(lastColIndex + 1).width = 15; // Total Masuk (H)
        sheet.getColumn(lastColIndex + 2).width = 15; // Total Libur (L)
        sheet.getColumn(lastColIndex + 3).width = 15; // Rasio Kerja (%)
        sheet.getColumn(lastColIndex + 4).width = 22; // Total Estimasi Gaji (Rp)

        // 1. Terapkan Kop Surat Resmi SPPG di Baris 1-4
        applyKopSurat(sheet, totalCols, logoBgnId, logoYayasanId, props.unitSppg);

        // 2. Judul Dokumen & Keterangan (Baris 5-9)
        sheet.addRow([]);
        sheet.getRow(5).height = 10;

        const unitName = props.unitSppg?.nama_unit || props.unitSppg?.nama || "SPPG BGN";
        const rJudul = sheet.addRow([`JADWAL SHIFTING HARI KERJA PERSONIL - ${unitName}`]);
        sheet.mergeCells(`A${rJudul.number}:${lastColLetter}${rJudul.number}`);
        rJudul.getCell(1).font = { name: "Arial", size: 12, bold: true, color: { argb: "FF0F172A" } };
        rJudul.getCell(1).alignment = { horizontal: "center", vertical: "middle" };
        rJudul.height = 22;

        const rSub = sheet.addRow([`Rentang: ${formatTanggalIndo(tanggalMulai.value)} s/d ${formatTanggalIndo(tanggalSelesai.value)} (${dateColumns.value.length} Hari Kerja) | Standar Shifting SPPG`]);
        sheet.mergeCells(`A${rSub.number}:${lastColLetter}${rSub.number}`);
        rSub.getCell(1).font = { name: "Arial", size: 9.5, italic: true, color: { argb: "FF475569" } };
        rSub.getCell(1).alignment = { horizontal: "center", vertical: "middle" };
        rSub.height = 18;

        const rNote = sheet.addRow(["Keterangan Kode Shift: [H] = Shift Masuk / Kerja Penuh | [H½] = Hadir Setengah Hari | [L] = Shift Libur / Off"]);
        sheet.mergeCells(`A${rNote.number}:${lastColLetter}${rNote.number}`);
        rNote.getCell(1).font = { name: "Arial", size: 9, bold: true, color: { argb: "FF047857" } };
        rNote.getCell(1).alignment = { horizontal: "center", vertical: "middle" };
        rNote.height = 16;

        const rSpace = sheet.addRow([]);
        rSpace.height = 8;

        // 3. Table Headers (Baris 10: Nama Hari, Baris 11: Tanggal Angka)
        const headerRow1 = ["No", "Nama Personil", "Jabatan / Divisi", "Tarif Harian (Rp)"];
        const headerRow2 = ["", "", "", ""];

        dateColumns.value.forEach((col) => {
            headerRow1.push(col.dayName);
            headerRow2.push(col.dayNum);
        });

        headerRow1.push("Total Masuk (H)", "Total Libur (L)", "Rasio Kerja (%)", "Total Estimasi Gaji (Rp)");
        headerRow2.push("", "", "", "");

        const rHead1 = sheet.addRow(headerRow1);
        const rHead2 = sheet.addRow(headerRow2);
        rHead1.height = 20;
        rHead2.height = 20;

        // Merge kolom identitas secara vertikal (Baris 10 - 11)
        sheet.mergeCells("A10:A11");
        sheet.mergeCells("B10:B11");
        sheet.mergeCells("C10:C11");
        sheet.mergeCells("D10:D11");

        // Merge kolom rekap kanan secara vertikal
        sheet.mergeCells(10, lastColIndex + 1, 11, lastColIndex + 1);
        sheet.mergeCells(10, lastColIndex + 2, 11, lastColIndex + 2);
        sheet.mergeCells(10, lastColIndex + 3, 11, lastColIndex + 3);
        sheet.mergeCells(10, lastColIndex + 4, 11, lastColIndex + 4);

        // Styling Table Headers
        for (let r = 10; r <= 11; r++) {
            const row = sheet.getRow(r);
            row.eachCell((cell, colNum) => {
                cell.font = { name: "Arial", size: 9.5, bold: true, color: { argb: "FFFFFFFF" } };
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

        // Highlight header hari libur / Minggu (Merah)
        dateColumns.value.forEach((col, idx) => {
            const colNum = 5 + idx;
            if (col.isSunday) {
                const c1 = sheet.getCell(10, colNum);
                const c2 = sheet.getCell(11, colNum);
                c1.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFDC2626" } };
                c2.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFDC2626" } };
            }
        });

        // 4. Freeze Panes yang Tepat:
        // Freeze hingga Baris 11 (Kop & Header Tabel tetap terlihat saat scroll ke bawah)
        // Freeze hingga Kolom C (No, Nama, dan Jabatan tetap terlihat saat scroll ke kanan)
        // Data pertama di Baris 12 (D12) akan scroll normal tanpa ada baris petugas yang terjebak
        sheet.views = [
            {
                state: "frozen",
                xSplit: 3,
                ySplit: 11,
                topLeftCell: "D12",
                activeCell: "D12",
                showGridLines: true,
            },
        ];

        // 5. Body Rows (Mulai Baris 12)
        filteredPetugas.value.forEach((p, pIdx) => {
            const stats = getPetugasShiftStats(p.id);
            const rowData = [pIdx + 1, p.nama, p.jabatan || "-", stats.tarifHarian];

            dateColumns.value.forEach((col) => {
                const st = matrixShifting.value[p.id]?.[col.dateStr] || "-";
                rowData.push(st === "H2" ? "½" : st);
            });

            rowData.push(stats.hadir, stats.libur, `${stats.rasioKerja}%`, stats.totalHonor);
            const insertedRow = sheet.addRow(rowData);
            insertedRow.height = 20;

            insertedRow.eachCell((cell, colNum) => {
                cell.font = { name: "Arial", size: 9 };
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
                    cell.font = { name: "Arial", size: 9.5, bold: true };
                } else if (colNum === 3) {
                    cell.alignment = { horizontal: "left", vertical: "middle" };
                } else if (colNum === 4) {
                    cell.alignment = { horizontal: "right", vertical: "middle" };
                    cell.numFmt = "#,##0";
                } else if (colNum >= 5 && colNum <= lastColIndex) {
                    cell.alignment = { horizontal: "center", vertical: "middle" };
                    const val = cell.value;
                    if (val === "H") {
                        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFD1FAE5" } }; // green light
                        cell.font = { name: "Arial", size: 9, bold: true, color: { argb: "FF065F46" } };
                    } else if (val === "½" || val === "H2") {
                        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFCCFBF1" } }; // teal light
                        cell.font = { name: "Arial", size: 9, bold: true, color: { argb: "FF0F766E" } };
                    } else if (val === "L") {
                        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFF1F5F9" } }; // slate light
                        cell.font = { name: "Arial", size: 9, bold: true, color: { argb: "FF64748B" } };
                    }
                } else if (colNum === lastColIndex + 4) {
                    cell.alignment = { horizontal: "right", vertical: "middle" };
                    cell.font = { name: "Arial", size: 9.5, bold: true, color: { argb: "FF065F46" } };
                    cell.numFmt = "#,##0";
                } else {
                    cell.alignment = { horizontal: "center", vertical: "middle" };
                    cell.font = { name: "Arial", size: 9, bold: true };
                }
            });
        });

        // 6. Footer Summary Rows
        // Row 1: Total Shift Masuk
        const footerRowData = ["", "TOTAL SHIFT MASUK (H)", "", kpiMetrics.value.totalGajiHarian];
        dateColumns.value.forEach((col) => {
            const s = perDateSummary.value[col.dateStr];
            footerRowData.push(s ? s.masukCount : 0);
        });
        footerRowData.push(kpiMetrics.value.totalMandays, "", "", kpiMetrics.value.totalHonorTerhitung);
        const footerRow = sheet.addRow(footerRowData);
        footerRow.height = 22;

        sheet.mergeCells(footerRow.number, 1, footerRow.number, 3);
        footerRow.eachCell((cell, colNum) => {
            cell.font = { name: "Arial", size: 9.5, bold: true, color: { argb: "FF065F46" } };
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFECFDF5" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
            if (colNum === 4 || colNum === lastColIndex + 4) {
                cell.alignment = { horizontal: "right", vertical: "middle" };
                cell.numFmt = "#,##0";
            }
            cell.border = {
                top: { style: "medium", color: { argb: "FF10B981" } },
                bottom: { style: "thin", color: { argb: "FF10B981" } },
                left: { style: "thin", color: { argb: "FF10B981" } },
                right: { style: "thin", color: { argb: "FF10B981" } },
            };
        });

        // Row 2: Beban Honor Harian & Rata-rata
        const payrollRowData = ["", "ESTIMASI BEBAN HONOR / HARI", "", "Tarif/Hr"];
        dateColumns.value.forEach((col) => {
            const s = perDateSummary.value[col.dateStr];
            payrollRowData.push(s ? s.dailyHonor : 0);
        });
        payrollRowData.push("", "", `Rata2: Rp ${formatRibuan(kpiMetrics.value.rata2HonorPerHari)}/hr`, kpiMetrics.value.totalHonorTerhitung);
        const payrollRow = sheet.addRow(payrollRowData);
        payrollRow.height = 22;

        sheet.mergeCells(payrollRow.number, 1, payrollRow.number, 3);
        payrollRow.eachCell((cell, colNum) => {
            cell.font = { name: "Arial", size: 9, bold: true, color: { argb: "FF064E3B" } };
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFD1FAE5" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
            if (colNum >= 5 && colNum <= lastColIndex) {
                cell.alignment = { horizontal: "center", vertical: "middle" };
                cell.numFmt = "#,##0";
            }
            if (colNum === lastColIndex + 3) {
                cell.alignment = { horizontal: "center", vertical: "middle" };
            }
            if (colNum === lastColIndex + 4) {
                cell.alignment = { horizontal: "right", vertical: "middle" };
                cell.numFmt = "#,##0";
            }
            cell.border = {
                top: { style: "thin", color: { argb: "FF10B981" } },
                bottom: { style: "medium", color: { argb: "FF10B981" } },
                left: { style: "thin", color: { argb: "FF10B981" } },
                right: { style: "thin", color: { argb: "FF10B981" } },
            };
        });

        // 7. Terapkan Lembar Pengesahan Tanda Tangan Resmi
        applyLembarPengesahan(sheet, {
            maxCols: totalCols,
            leftStartCol: 2,
            leftEndCol: 4,
            rightColsWidth: 4,
            unitSppg: props.unitSppg,
            petugas: props.petugas,
            user: page.props.auth?.user,
        });

        // 8. Download File Excel
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

        showToast("File Jadwal Shifting (.xlsx) dengan Kop Resmi & Freeze Panes berhasil didownload!");
    } catch (e) {
        console.error("Gagal export shifting excel:", e);
        showToast("Gagal mendownload file Excel: " + (e?.message || e));
    } finally {
        isExporting.value = false;
    }
}

// ─── Badge Helper ─────────────────────────────────────────────────────────────
function getJabatanBadgeClass(jabatan) {
    if (!jabatan) return "bg-slate-100 text-slate-800 border-slate-300";
    const j = jabatan.toLowerCase();
    if (j.includes("kepala")) return "bg-emerald-100/90 text-emerald-900 border-emerald-300 font-bold";
    if (j.includes("gizi") || j.includes("keuangan")) return "bg-emerald-100/80 text-emerald-900 border-emerald-300 font-semibold";
    if (j.includes("chef") || j.includes("masak") || j.includes("pengolahan")) return "bg-amber-100/90 text-amber-950 border-amber-300 font-medium";
    if (j.includes("persiapan")) return "bg-slate-200/90 text-slate-800 border-slate-300 font-medium";
    if (j.includes("pemorsian")) return "bg-purple-100/90 text-purple-900 border-purple-300 font-medium";
    if (j.includes("distribusi") || j.includes("driver") || j.includes("pengemudi")) return "bg-teal-100/90 text-teal-900 border-teal-300 font-medium";
    if (j.includes("cuci") || j.includes("ompreng")) return "bg-orange-100/90 text-orange-950 border-orange-300 font-medium";
    if (j.includes("kebersihan")) return "bg-rose-100/90 text-rose-900 border-rose-300 font-medium";
    if (j.includes("keamanan") || j.includes("security")) return "bg-indigo-100/90 text-indigo-900 border-indigo-300 font-medium";
    return "bg-slate-100 text-slate-700 border-slate-300";
}

// ─── Team / Divisi Helpers (Aksesibel untuk Buta Warna Parsial / Color Vision Deficiency) ─
function getPrimaryTeamKey(jabatan) {
    if (!jabatan) return "umum";
    const j = jabatan.toLowerCase();
    if (j.includes("kepala") || j.includes("pengawas")) return "manajemen";
    if (j.includes("koordinator lapangan")) return "korlap";
    if (j.includes("chef") || j.includes("masak") || j.includes("pengolahan")) return "pengolahan";
    if (j.includes("persiapan")) return "persiapan";
    if (j.includes("pemorsian")) return "pemorsian";
    if (j.includes("pengemudi") || j.includes("driver") || j.includes("distribusi")) return "pengemudi";
    if (j.includes("cuci") || j.includes("ompreng")) return "cuci_ompreng";
    if (j.includes("kebersihan")) return "kebersihan";
    if (j.includes("keamanan") || j.includes("security")) return "keamanan";
    return "umum";
}

function isFirstOfTeam(pIdx) {
    if (pIdx <= 0) return false;
    const curr = filteredPetugas.value[pIdx];
    const prev = filteredPetugas.value[pIdx - 1];
    if (!curr || !prev) return false;
    return getPrimaryTeamKey(curr.jabatan) !== getPrimaryTeamKey(prev.jabatan);
}

function getTeamRowClass(jabatan) {
    const key = getPrimaryTeamKey(jabatan);
    switch (key) {
        case "manajemen":
            return "bg-emerald-50/45 hover:bg-emerald-100/50";
        case "korlap":
            return "bg-sky-50/70 hover:bg-sky-100/70";
        case "pengolahan":
            return "bg-amber-100/50 hover:bg-amber-100/75";
        case "persiapan":
            return "bg-slate-100/60 hover:bg-slate-200/50";
        case "pemorsian":
            return "bg-purple-100/40 hover:bg-purple-100/60";
        case "pengemudi":
            return "bg-teal-50/70 hover:bg-teal-100/70";
        case "cuci_ompreng":
            return "bg-orange-100/45 hover:bg-orange-100/65";
        case "kebersihan":
            return "bg-rose-50/50 hover:bg-rose-100/60";
        case "keamanan":
            return "bg-indigo-50/50 hover:bg-indigo-100/60";
        default:
            return "bg-white hover:bg-slate-50";
    }
}

function getTeamStickyClass(jabatan) {
    const key = getPrimaryTeamKey(jabatan);
    switch (key) {
        case "manajemen":
            return "bg-[#edf8f1] group-hover:bg-[#ddf2e4]";
        case "korlap":
            return "bg-[#edf6fd] group-hover:bg-[#d8ecfa]";
        case "pengolahan":
            return "bg-[#fffbeb] group-hover:bg-[#fef3c7]";
        case "persiapan":
            return "bg-[#f1f5f9] group-hover:bg-[#e2e8f0]";
        case "pemorsian":
            return "bg-[#f8f4ff] group-hover:bg-[#ede5ff]";
        case "pengemudi":
            return "bg-[#eefcf8] group-hover:bg-[#d2f9ee]";
        case "cuci_ompreng":
            return "bg-[#fff6ee] group-hover:bg-[#fee9d6]";
        case "kebersihan":
            return "bg-[#fff1f3] group-hover:bg-[#ffe0e4]";
        case "keamanan":
            return "bg-[#eef2ff] group-hover:bg-[#dbeafe]";
        default:
            return "bg-white group-hover:bg-slate-50";
    }
}

function getTeamBorderClass(jabatan) {
    const key = getPrimaryTeamKey(jabatan);
    switch (key) {
        case "manajemen":
            return "border-l-4 border-l-emerald-600";
        case "korlap":
            return "border-l-4 border-l-blue-600";
        case "pengolahan":
            return "border-l-4 border-l-amber-600";
        case "persiapan":
            return "border-l-4 border-l-slate-600";
        case "pemorsian":
            return "border-l-4 border-l-purple-700";
        case "pengemudi":
            return "border-l-4 border-l-teal-700";
        case "cuci_ompreng":
            return "border-l-4 border-l-orange-600";
        case "kebersihan":
            return "border-l-4 border-l-rose-600";
        case "keamanan":
            return "border-l-4 border-l-indigo-700";
        default:
            return "border-l-4 border-l-slate-400";
    }
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

            <!-- ─── KPI Summary Cards (Responsive 2 cols mobile, 5 cols desktop) ─── -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3">
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

                <!-- Card 5: Total Estimasi Hak Gaji / Honor -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs flex items-center gap-3 col-span-2 sm:col-span-1">
                    <div class="p-2 sm:p-2.5 rounded-lg bg-emerald-50 text-emerald-600 shrink-0">
                        <DollarSign class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs font-medium text-slate-500 uppercase tracking-wider truncate">Total Estimasi Gaji</p>
                        <p class="text-sm sm:text-base font-bold text-emerald-800 leading-tight truncate" :title="formatRupiah(kpiMetrics.totalHonorTerhitung)">
                            {{ formatRupiah(kpiMetrics.totalHonorTerhitung) }}
                        </p>
                        <p class="text-[10px] text-emerald-600 font-semibold mt-0.5 truncate" :title="`Rata-rata: ${formatRupiah(kpiMetrics.rata2HonorPerHari)}/hari kalender (${formatRupiah(kpiMetrics.rata2HonorHariKerja)}/hari operasional)`">
                            Rata-rata {{ formatRupiah(kpiMetrics.rata2HonorPerHari) }} / hari
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

            <!-- ─── Interactive Shifting Matrix Table (Mirip Rekap Presensi) ─── -->
            <div class="rounded-xl bg-white border border-slate-200/80 shadow-2xs overflow-hidden">
                <!-- Bar di atas Tabel: Title Matriks & Legend Status Shifting -->
                <div class="px-4 py-3 border-b border-slate-200/80 bg-white flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-800 text-xs uppercase tracking-wide">
                            MATRIKS SHIFTING ({{ filteredPetugas.length }} PERSONIL)
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                            {{ dateColumns.length }} Kolom Tanggal
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 text-[11px] text-slate-600 font-medium">
                        <span class="text-slate-400 hidden xs:inline">Pilihan:</span>
                        <span class="inline-flex items-center gap-1 font-semibold text-slate-500" title="Belum Diatur (Kosong)">
                            <span class="w-2.5 h-2.5 rounded-sm border border-dashed border-slate-400 bg-slate-100 flex items-center justify-center text-[8px] text-slate-400">·</span>Kosong
                        </span>
                        <span class="inline-flex items-center gap-1 font-bold text-emerald-700" title="Shift Masuk (Kerja)">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>H (Hadir)
                        </span>
                        <span class="inline-flex items-center gap-1 font-bold text-teal-700" title="Shift Setengah Hari">
                            <span class="w-2 h-2 rounded-full bg-teal-500"></span>½ (Setengah)
                        </span>
                        <span class="inline-flex items-center gap-1 font-bold text-slate-700" title="Shift Libur (Off)">
                            <span class="w-2 h-2 rounded-full bg-slate-500"></span>L (Libur)
                        </span>
                    </div>
                </div>

                <!-- Mobile Swipe Hint -->
                <div class="sm:hidden px-3.5 py-1.5 bg-emerald-50/70 border-b border-emerald-100 flex items-center justify-between text-[11px] text-emerald-800 font-medium">
                    <span>👉 Geser tabel ke kanan untuk melihat kalender tanggal & status shifting</span>
                </div>

                <!-- Mobile Horizontal Scroll Container with Minimum Width -->
                <div class="overflow-x-auto relative max-h-[75vh] touch-pan-x overscroll-x-contain">
                    <table class="w-full text-left border-collapse min-w-[1100px]">
                        <!-- Table Head: Baris 1 Visualisasi Rentang Terpilih (Sama Persis Rekap Kehadiran) -->
                        <thead class="sticky top-0 z-20 shadow-xs select-none">
                            <!-- Strip Indikator Rentang Tanggal Terpilih Menyatu di Header -->
                            <tr class="border-b border-emerald-700/60 text-white">
                                <th colspan="3" class="py-1 px-3 bg-slate-800 text-slate-200 text-[10px] font-bold uppercase tracking-wider sm:sticky sm:left-0 z-10 sm:z-30 border-r border-slate-700">
                                    Informasi Personil & Tarif
                                </th>
                                <th
                                    :colspan="dateColumns.length"
                                    class="py-1 px-2 text-center bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-800 text-[11px] font-black tracking-wider uppercase border-r border-emerald-600 shadow-inner"
                                >
                                    Rentang Kalender Terpilih: {{ formatTanggalIndo(tanggalMulai) }} – {{ formatTanggalIndo(tanggalSelesai) }} ({{ totalWorkDaysCount }} Hari Kerja Operasional)
                                </th>
                                <th colspan="4" class="py-1 px-2 text-center bg-slate-800 text-slate-200 text-[10px] font-bold uppercase tracking-wider">
                                    Rekapitulasi Shift & Hak Honor
                                </th>
                            </tr>

                            <!-- Baris 2: Kolom Header Utama -->
                            <tr class="bg-slate-100 text-slate-700 border-b border-slate-200 text-xs">
                                <!-- Kolom Tetap Kiri: No, Personil & Divisi -->
                                <th class="py-2.5 px-2.5 w-10 sm:w-12 text-center sm:sticky sm:left-0 bg-slate-100 z-10 sm:z-30 border-r border-slate-200 font-bold">
                                    No
                                </th>
                                <th class="py-2.5 px-3 min-w-[200px] sm:min-w-[260px] w-[220px] sm:w-[260px] sm:sticky sm:left-10 sm:left-12 bg-slate-100 z-10 sm:z-30 border-r border-slate-200 font-bold">
                                    Personil & Divisi
                                </th>
                                <th class="py-2.5 px-3 text-right min-w-[125px] border-r border-slate-200 font-bold text-slate-800">
                                    <div class="flex items-center justify-end gap-1.5" title="Tarif harian shifting per personil (klik nominal di baris untuk ubah satuan, atau klik Paste Deret untuk tempel banyak baris sekaligus)">
                                        <span>Tarif Harian</span>
                                        <button
                                            type="button"
                                            @click="openPasteTarifModal(null)"
                                            class="inline-flex items-center gap-1 text-[9.5px] text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 px-1.5 py-0.5 rounded-md font-bold transition shadow-xs cursor-pointer active:scale-95"
                                            title="Tempel / Paste satu deret kolom gaji dari Excel / Spreadsheet sekaligus"
                                        >
                                            <ClipboardPaste class="w-3 h-3 text-emerald-600" />
                                            <span>Paste Deret</span>
                                        </button>
                                    </div>
                                </th>

                                <!-- Kolom Matriks Tanggal (Warna Rentang Menyatu & Highlight Hari Ini) -->
                                <th
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    :class="[
                                        'py-2 px-1 text-center min-w-[65px] sm:min-w-[72px] border-r select-none group transition-colors cursor-pointer',
                                        col.isToday
                                            ? 'bg-emerald-600 text-white border-emerald-700 shadow-xs'
                                            : (col.isWeekend ? 'bg-amber-50/70 border-amber-200/60 text-slate-700 hover:bg-amber-100/80' : 'bg-emerald-50/50 border-emerald-200/50 text-slate-700 hover:bg-emerald-100/70'),
                                    ]"
                                    :title="`Klik tanggal ${col.dayNum} (${col.dayName}) untuk ganti serentak seluruh personil: H ➜ L ➜ Kosong`"
                                    @click="toggleKolomStatus(col.dateStr)"
                                >
                                    <div class="flex flex-col items-center">
                                        <span
                                            :class="[
                                                'text-[9px] font-bold uppercase tracking-tight',
                                                col.isToday
                                                    ? 'text-emerald-100 font-black'
                                                    : (col.isSunday ? 'text-rose-600' : 'text-slate-500'),
                                            ]"
                                        >
                                            {{ col.dayName }}
                                        </span>
                                        <button
                                            type="button"
                                            :class="[
                                                'mt-0.5 px-1 py-0.5 rounded text-[11px] font-black transition-all cursor-pointer hover:scale-105 active:scale-95',
                                                col.isToday
                                                    ? 'bg-white text-emerald-800 hover:bg-emerald-100 ring-2 ring-emerald-300'
                                                    : 'hover:bg-emerald-200/70 hover:text-emerald-900 text-slate-800',
                                            ]"
                                        >
                                            {{ col.dayNum }}
                                        </button>
                                        <span v-if="col.isToday" class="text-[7.5px] font-extrabold uppercase tracking-tighter text-emerald-200 leading-none mt-0.5">
                                            Hari Ini
                                        </span>
                                    </div>
                                </th>

                                <!-- Kolom Rekap Kanan -->
                                <th class="py-2.5 px-2 text-center w-12 bg-emerald-50 text-emerald-800 font-bold border-r border-slate-200" title="Total Shift Masuk (H)">
                                    H
                                </th>
                                <th class="py-2.5 px-2 text-center w-12 bg-slate-100 text-slate-700 font-bold border-r border-slate-200" title="Total Shift Libur (L)">
                                    L
                                </th>
                                <th class="py-2.5 px-2 text-center min-w-[55px] bg-emerald-100/60 text-emerald-900 font-black border-r border-slate-200" title="Rasio Beban Kerja">
                                    %
                                </th>
                                <th class="py-2.5 px-3 text-right min-w-[155px] sm:min-w-[175px] font-bold text-slate-800" title="Total Hak Honor Berdasarkan Jadwal Shift Masuk">
                                    Total Hak Honor
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            <tr
                                v-for="(p, pIdx) in filteredPetugas"
                                :key="p.id"
                                :class="[
                                    'transition-colors group',
                                    getTeamRowClass(p.jabatan),
                                    isFirstOfTeam(pIdx) ? '!border-t-2 !border-slate-300 sm:!border-slate-400/80 shadow-[0_-1px_0_0_rgba(148,163,184,0.3)]' : '',
                                ]"
                            >
                                <!-- Sticky Left 1: No (Sticky on Desktop, Natural Flow on Mobile) -->
                                <td
                                    :class="[
                                        'sm:sticky sm:left-0 z-0 sm:z-10 px-2 sm:px-3 py-2 text-center text-slate-500 hover:text-emerald-700 font-medium w-10 sm:w-12 min-w-[40px] sm:min-w-[48px] border-r border-slate-200 cursor-pointer select-none transition-colors',
                                        getTeamStickyClass(p.jabatan),
                                        getTeamBorderClass(p.jabatan),
                                    ]"
                                    :title="`Klik untuk ganti serentak baris ${p.nama}: H ➜ L ➜ Kosong`"
                                    @click="toggleBarisStatus(p.id)"
                                >
                                    {{ pIdx + 1 }}
                                </td>

                                <!-- Sticky Left 2: Petugas & Divisi (Sticky on Desktop, Natural Flow on Mobile) -->
                                <td
                                    :class="[
                                        'sm:sticky sm:left-10 sm:left-12 z-0 sm:z-10 px-3 sm:px-3.5 py-2 border-r border-slate-200 cursor-pointer select-none transition-colors w-[220px] sm:w-[260px] min-w-[200px] sm:min-w-[260px]',
                                        getTeamStickyClass(p.jabatan),
                                    ]"
                                    :title="`Klik nama ${p.nama} untuk ganti serentak satu baris: H ➜ L ➜ Kosong`"
                                    @click="toggleBarisStatus(p.id)"
                                >
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-900 text-xs break-words leading-tight hover:text-emerald-700 transition-colors">
                                             {{ p.nama }}
                                        </p>
                                        <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                                            <span
                                                v-for="jab in (p.jabatan || '').split(/[,&/]/).map(s => s.trim()).filter(Boolean)"
                                                :key="jab"
                                                :class="[
                                                    'px-1.5 py-0.5 rounded text-[9px] border leading-none font-medium',
                                                    getJabatanBadgeClass(jab),
                                                ]"
                                            >
                                                {{ jab }}
                                            </span>
                                            <span
                                                v-if="wajibHadirStaffIds.includes(p.id)"
                                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0 leading-none"
                                                title="Personil Wajib Selalu Hadir (Bebas Shifting)"
                                            >
                                                <ShieldCheck class="h-2.5 w-2.5" />
                                                <span>Tetap</span>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom Tarif Harian (BGN / Khusus Shifting - Edit Cell & Paste Deret Sekaligus) -->
                                <td
                                    class="py-1 px-2.5 text-right font-medium text-slate-800 border-r border-slate-200 select-none group transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-inset"
                                    :class="editingPetugasTarifId === p.id ? 'bg-emerald-50/90 ring-2 ring-emerald-500 ring-inset z-10' : 'hover:bg-emerald-50/40 cursor-pointer'"
                                    tabindex="0"
                                    @click="editingPetugasTarifId !== p.id && startInlineEditTarif(p)"
                                    @paste="handlePasteTarifColumn($event, p)"
                                    :title="editingPetugasTarifId === p.id ? '' : 'Klik cell untuk mengedit manual, atau tekan Ctrl+V langsung untuk menempel 1 deret kolom gaji mulai dari baris ini'"
                                >
                                    <!-- Input Mode Aktif di Cell -->
                                    <div v-if="editingPetugasTarifId === p.id" class="flex items-center justify-end gap-1 py-0.5">
                                        <span class="text-[10px] font-bold text-slate-400 select-none">Rp</span>
                                        <input
                                            ref="inlineInputRef"
                                            type="text"
                                            :value="inlineTarifInput"
                                            @input="onInlineTarifInput"
                                            @paste="handlePasteTarifColumn($event, p)"
                                            @blur="commitInlineTarifEdit(p)"
                                            @keydown.enter.prevent="commitInlineTarifEdit(p)"
                                            @keydown.esc.prevent="cancelInlineTarifEdit"
                                            placeholder="0"
                                            class="w-24 sm:w-28 py-1 px-1.5 text-right font-bold text-slate-900 text-xs font-mono rounded border border-emerald-500 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 shadow-xs"
                                        />
                                    </div>

                                    <!-- Tampilan Normal Cell -->
                                    <div v-else class="flex items-center justify-end gap-1.5">
                                        <!-- Tombol Cepat Paste Deret Mulai Baris Ini -->
                                        <button
                                            type="button"
                                            @click.stop="openPasteTarifModal(p.id)"
                                            class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-emerald-100 text-slate-400 hover:text-emerald-700 transition cursor-pointer"
                                            title="Tempel deret kolom gaji mulai dari personil ini ke bawah"
                                        >
                                            <ClipboardPaste class="w-3 h-3" />
                                        </button>

                                        <!-- Tombol Reset ke Master BGN jika tarif sedang kustom -->
                                        <button
                                            v-if="isCustomTarif(p.id)"
                                            type="button"
                                            @click.stop="resetTarifToMaster(p, $event)"
                                            class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-rose-100 text-slate-400 hover:text-rose-600 transition cursor-pointer"
                                            title="Kembalikan tarif ke master BGN resmi"
                                        >
                                            <RotateCcw class="w-3 h-3" />
                                        </button>

                                        <span
                                            v-else
                                            class="opacity-0 group-hover:opacity-100 p-0.5 rounded text-slate-300 group-hover:text-slate-500 transition"
                                        >
                                            <Pencil class="w-3 h-3" />
                                        </span>

                                        <div>
                                            <p class="font-bold text-slate-900 text-xs font-mono leading-tight">
                                                {{ formatRupiah(getTarifShifting(p.id)) }}
                                            </p>
                                            <div class="flex items-center justify-end gap-1 mt-0.5">
                                                <span
                                                    v-if="isCustomTarif(p.id)"
                                                    class="inline-flex items-center gap-0.5 text-[8.5px] font-bold text-emerald-800 bg-emerald-100 border border-emerald-300 px-1 py-0.2 rounded leading-none"
                                                    title="Tarif khusus shifting aktif (master gaji BGN tidak berubah)"
                                                >
                                                    Shifting
                                                </span>
                                                <span v-else class="text-[9px] text-slate-400 leading-none">
                                                    BGN / Hari
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Interactive Date Matrix Cells (Style Mirip Rekap Kehadiran) -->
                                <td
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    class="py-1.5 px-0.5 text-center border-r border-slate-100/80"
                                    :class="[
                                        col.isToday ? 'bg-emerald-50/40 ring-1 ring-emerald-300/40' : (col.isWeekend ? 'bg-amber-50/20' : ''),
                                    ]"
                                >
                                    <button
                                        type="button"
                                        @click="toggleCellShift(p.id, col.dateStr)"
                                        :class="[
                                            'w-7 h-7 mx-auto rounded-md text-[11px] font-black transition-transform active:scale-90 flex items-center justify-center cursor-pointer select-none',
                                            matrixShifting[p.id]?.[col.dateStr] === 'H'
                                                ? 'bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-2xs'
                                                : matrixShifting[p.id]?.[col.dateStr] === 'H2'
                                                ? 'bg-teal-500 hover:bg-teal-600 text-white font-black shadow-2xs ring-1 ring-teal-400'
                                                : matrixShifting[p.id]?.[col.dateStr] === 'L'
                                                ? 'bg-slate-500 hover:bg-slate-600 text-white font-bold shadow-2xs'
                                                : 'bg-slate-50 hover:bg-slate-100 text-slate-300 hover:text-slate-600 border border-dashed border-slate-200 hover:border-slate-300',
                                        ]"
                                        :title="`${p.nama} (${col.dayName}, ${col.dayNum}): ${matrixShifting[p.id]?.[col.dateStr] === 'H' ? 'Shift Masuk (H)' : matrixShifting[p.id]?.[col.dateStr] === 'L' ? 'Shift Libur (L)' : 'Belum diatur (Klik untuk pilih status)'}`"
                                    >
                                        {{
                                            matrixShifting[p.id]?.[col.dateStr] === 'H'
                                                ? 'H'
                                                : matrixShifting[p.id]?.[col.dateStr] === 'H2'
                                                ? '½'
                                                : matrixShifting[p.id]?.[col.dateStr] === 'L'
                                                ? 'L'
                                                : '·'
                                        }}
                                    </button>
                                </td>

                                <!-- Summary 1: Total Masuk (H) -->
                                <td class="py-2 px-2 text-center font-bold text-slate-800 bg-emerald-50/30 border-r border-slate-200 text-xs">
                                    {{ getPetugasShiftStats(p.id).hadir }}
                                </td>

                                <!-- Summary 2: Total Libur (L) -->
                                <td class="py-2 px-2 text-center font-medium text-slate-600 bg-slate-50/50 border-r border-slate-200 text-xs">
                                    {{ getPetugasShiftStats(p.id).libur }}
                                </td>

                                <!-- Summary 3: Rasio Kerja % -->
                                <td class="py-2 px-2 text-center border-r border-slate-200 text-xs">
                                    <span
                                        :class="[
                                            'px-1.5 py-0.5 rounded text-[10px] font-bold font-mono',
                                            getPetugasShiftStats(p.id).rasioKerja >= 60
                                                ? 'text-emerald-700 bg-emerald-50/80 border border-emerald-200/60'
                                                : getPetugasShiftStats(p.id).rasioKerja >= 40
                                                ? 'text-blue-700 bg-blue-50/80 border border-blue-200/60'
                                                : 'text-rose-600 bg-rose-50/80 border border-rose-200/60',
                                        ]"
                                    >
                                        {{ getPetugasShiftStats(p.id).rasioKerja }}%
                                    </span>
                                </td>

                                <!-- Summary 4: Total Hak Honor -->
                                <td class="py-2 px-3 text-right font-bold text-emerald-950 border-r border-slate-200 select-none bg-emerald-50/20">
                                    <p class="font-bold text-emerald-800 text-xs font-mono">
                                        {{ formatRupiah(getPetugasShiftStats(p.id).totalHonor) }}
                                    </p>
                                    <p class="text-[9px] text-emerald-600/80">
                                        {{ getPetugasShiftStats(p.id).hadir }} shift
                                    </p>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredPetugas.length === 0">
                                <td :colspan="dateColumns.length + 7" class="py-12 text-center text-slate-400">
                                    <AlertCircle class="h-8 w-8 mx-auto mb-2 text-slate-300" />
                                    <p class="font-medium text-slate-600">Tidak ada data personil yang sesuai dengan filter.</p>
                                    <p class="text-[11px] text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter divisi.</p>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Table Footer: Staffing Health, Daily Totals & Total Honor -->
                        <tfoot class="bg-slate-100/90 text-slate-800 border-t-2 border-slate-300 font-bold sticky bottom-0 z-20 select-none shadow-xs text-xs">
                            <!-- Footer Row 1: Total Petugas Masuk (On Duty) & Akumulasi Honor -->
                            <tr class="bg-emerald-50/80 text-emerald-950">
                                <td colspan="2" class="sm:sticky sm:left-0 z-10 sm:z-30 bg-emerald-100/90 px-3 sm:px-3.5 py-2 text-right font-black border-r border-emerald-300">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider">Harian</span>
                                        <span>Total Shift Masuk (H):</span>
                                    </div>
                                </td>
                                <td class="px-2.5 py-2 text-right font-black text-xs text-slate-900 font-mono border-r border-emerald-300" title="Total Tarif Harian Semua Personil (1 Hari Penuh)">
                                    {{ formatRupiah(kpiMetrics.totalGajiHarian) }}
                                </td>
                                <td
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    class="py-2 px-0.5 text-center font-black border-r border-emerald-200/80"
                                    :title="`${col.dayName}, ${col.dayNum}: ${perDateSummary[col.dateStr]?.masukCount || 0} Petugas Masuk (Beban Honor: Rp ${formatRibuan(perDateSummary[col.dateStr]?.dailyHonor || 0)})`"
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
                                <td class="px-2 py-2 text-center font-black text-emerald-900 border-r border-slate-200">
                                    {{ kpiMetrics.keseimbanganShift }}
                                </td>
                                <td class="px-3 py-2 text-right bg-emerald-200/90 border-r border-emerald-300 select-none">
                                    <div class="flex flex-col items-end leading-tight">
                                        <span class="text-xs sm:text-sm font-black text-emerald-950 font-mono" title="Total Seluruh Estimasi Hak Honor Sesuai Shifting">
                                            {{ formatRupiah(kpiMetrics.totalHonorTerhitung) }}
                                        </span>
                                        <span class="text-[9.5px] font-bold text-emerald-800/90 font-mono mt-0.5" title="Rata-rata Pengeluaran Honor per Hari">
                                            ~{{ formatRupiah(kpiMetrics.rata2HonorPerHari) }} / hr
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Footer Row 2: Total Petugas Libur (Off) -->
                            <tr class="bg-slate-100/90 text-slate-700">
                                <td colspan="2" class="sm:sticky sm:left-0 z-10 sm:z-30 bg-slate-200/90 px-3 sm:px-3.5 py-1.5 text-right font-bold border-r border-slate-300">
                                    <span>Total Shift Libur (L):</span>
                                </td>
                                <td class="px-2.5 py-1.5 text-right font-medium text-[11px] text-slate-400 border-r border-slate-300">
                                    -
                                </td>
                                <td
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    class="py-1 px-0.5 text-center font-semibold text-slate-600 border-r border-slate-200"
                                >
                                    <span class="text-[11px]">
                                        {{ perDateSummary[col.dateStr]?.liburCount || 0 }}
                                    </span>
                                </td>
                                <td class="px-2 py-1.5 text-center text-slate-400 border-r border-slate-200">-</td>
                                <td class="px-2 py-1.5 text-center font-bold text-slate-800 border-r border-slate-300">
                                    {{ kpiMetrics.rata2LiburPerHari }}
                                </td>
                                <td class="px-2 py-1.5 text-center text-slate-400 border-r border-slate-200">-</td>
                                <td class="px-3 py-1.5 text-right text-slate-400 font-mono text-[11px]">-</td>
                            </tr>

                            <!-- Footer Row 3: Estimasi Beban Honor Harian & Rata-rata -->
                            <tr class="bg-emerald-100/70 text-emerald-950 border-t border-emerald-300">
                                <td colspan="2" class="sm:sticky sm:left-0 z-10 sm:z-30 bg-emerald-200/90 px-3 sm:px-3.5 py-2 text-right font-bold border-r border-emerald-300">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-bold text-emerald-800 uppercase tracking-wider">Payroll</span>
                                        <span class="text-xs">Beban Honor / Hari:</span>
                                    </div>
                                </td>
                                <td class="px-2.5 py-2 text-right font-bold text-[10px] text-emerald-900 font-mono border-r border-emerald-300" title="Total Tarif Pokok Harian Personil">
                                    Tarif/Hr
                                </td>
                                <td
                                    v-for="col in dateColumns"
                                    :key="col.dateStr"
                                    class="py-2 px-1 text-center border-r border-emerald-200/90 bg-emerald-100/40"
                                    :title="`${col.dayName}, ${col.dayNum}: Beban Honor Rp ${formatRibuan(perDateSummary[col.dateStr]?.dailyHonor || 0)}`"
                                >
                                    <div class="flex flex-col items-center justify-center">
                                        <span
                                            v-if="(perDateSummary[col.dateStr]?.dailyHonor || 0) > 0"
                                            class="text-xs sm:text-[13px] font-black text-emerald-950 font-mono tracking-tighter leading-none"
                                        >
                                            {{ formatRibuan(perDateSummary[col.dateStr]?.dailyHonor || 0) }}
                                        </span>
                                        <span v-else class="text-slate-400 font-bold text-xs">-</span>
                                    </div>
                                </td>
                                <td colspan="3" class="px-2.5 py-2 text-right font-bold text-xs text-emerald-900 border-r border-emerald-300 leading-tight">
                                    <div class="font-bold text-xs">Total Keseluruhan:</div>
                                    <div class="text-[10.5px] text-emerald-800 font-semibold mt-0.5">Rata-rata / Hari:</div>
                                </td>
                                <td class="px-3 py-2 text-right bg-emerald-300/90">
                                    <div class="flex flex-col items-end leading-tight">
                                        <span class="text-xs sm:text-sm font-black text-emerald-950 font-mono" title="Total Keseluruhan Estimasi Hak Honor">
                                            {{ formatRupiah(kpiMetrics.totalHonorTerhitung) }}
                                        </span>
                                        <span class="text-[11px] font-black text-emerald-900 font-mono mt-0.5 flex items-center gap-1 justify-end" title="Rata-rata Estimasi Beban Honor per Hari">
                                            <span>{{ formatRupiah(kpiMetrics.rata2HonorPerHari) }}</span>
                                            <span class="text-[9.5px] font-normal text-emerald-800">/ hari</span>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ─── Area Bantu & Simulasi 2 Opsi Skema Kompensasi & Shifting ───── -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden transition-all duration-200">
                <!-- Header Area Bantu -->
                <div class="p-4 sm:p-5 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-indigo-600/90 text-white shadow-md shadow-indigo-500/20 shrink-0">
                            <Scale class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-black text-sm sm:text-base tracking-tight text-white">
                                    Simulasi & Komparasi 2 Opsi Skema Kompensasi
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                    Area Bantu Analisis
                                </span>
                            </div>
                            <p class="text-[11px] sm:text-xs text-slate-300 mt-0.5">
                                Perbandingan <strong>Skema 1 (Pengurangan Tarif Gaji, Kerja Penuh)</strong> vs <strong>Skema 2 (Pengurangan Hari Kerja akibat Shifting, Tarif Utuh)</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Expand Toggle -->
                    <div class="flex items-center gap-2 self-end md:self-auto">
                        <button
                            type="button"
                            @click="isSimulasiExpanded = !isSimulasiExpanded"
                            class="p-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition cursor-pointer"
                            :title="isSimulasiExpanded ? 'Sembunyikan area bantu' : 'Tampilkan area bantu'"
                        >
                            <ChevronUp v-if="isSimulasiExpanded" class="h-4 w-4" />
                            <ChevronDown v-else class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Konten Body Area Bantu (Bisa di-collapse) -->
                <div v-show="isSimulasiExpanded" class="p-4 sm:p-5 space-y-4">
                    <!-- Top Summary KPI Comparison Cards (3 Skema) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Card 1: Skema 1 (Pengurangan Tarif) -->
                        <div class="p-3.5 sm:p-4 rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50/70 to-blue-50/50 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-700 bg-indigo-100/80 px-2 py-0.5 rounded-md border border-indigo-200">
                                    Skema 1: Potong Tarif
                                </span>
                                <span class="text-[10px] font-semibold text-slate-500">
                                    Kerja Penuh ({{ totalWorkDaysCount }} hr)
                                </span>
                            </div>
                            <div class="mt-1">
                                <p class="text-xs text-slate-500 font-medium">Beban Harian Unit:</p>
                                <p class="text-lg sm:text-xl font-black text-indigo-950 font-mono">
                                    {{ formatRupiah(simulasiSummary.bebanHarianUnitSkema1) }}
                                    <span class="text-xs font-semibold text-indigo-600">/ hari</span>
                                </p>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-indigo-200/60 space-y-1 text-[11px] text-slate-600">
                                <div class="flex items-center justify-between">
                                    <span>Total Periode:</span>
                                    <span class="font-bold font-mono text-indigo-900">{{ formatRupiah(simulasiSummary.totalBebanSkema1) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Rata-rata/orang/hari:</span>
                                    <span class="font-bold font-mono text-indigo-900">{{ formatRupiah(simulasiSummary.rata2PerHariPerOrangSkema1) }} / hari</span>
                                </div>
                                <div class="flex items-center justify-between text-emerald-700">
                                    <span>Hemat vs Tarif Shifting:</span>
                                    <span class="font-bold font-mono">-{{ formatRupiah(simulasiSummary.hematVsNormalSkema1) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Skema 2 (Pengurangan Hari Kerja) -->
                        <div class="p-3.5 sm:p-4 rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50/70 to-teal-50/50 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-300">
                                    Skema 2: Potong Hari
                                </span>
                                <span class="text-[10px] font-semibold text-slate-500">
                                    Tarif Utuh (Ikut Shift)
                                </span>
                            </div>
                            <div class="mt-1">
                                <p class="text-xs text-slate-500 font-medium">Beban Harian Unit:</p>
                                <p class="text-lg sm:text-xl font-black text-emerald-950 font-mono">
                                    {{ formatRupiah(simulasiSummary.bebanHarianUnitSkema2) }}
                                    <span class="text-xs font-semibold text-emerald-600">/ hari</span>
                                </p>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-emerald-200/60 space-y-1 text-[11px] text-slate-600">
                                <div class="flex items-center justify-between">
                                    <span>Total Periode:</span>
                                    <span class="font-bold font-mono text-emerald-900">{{ formatRupiah(simulasiSummary.totalBebanSkema2) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Rata-rata/orang/hari:</span>
                                    <span class="font-bold font-mono text-emerald-900">{{ formatRupiah(simulasiSummary.rata2PerHariPerOrangSkema2) }} / hari</span>
                                </div>
                                <div class="flex items-center justify-between text-emerald-700">
                                    <span>Hemat vs Tarif Shifting:</span>
                                    <span class="font-bold font-mono">-{{ formatRupiah(simulasiSummary.hematVsNormalSkema2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Skema 3 (Kombinasi Tarif & Hari Kerja) -->
                        <div class="p-3.5 sm:p-4 rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50/70 to-fuchsia-50/50 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-purple-800 bg-purple-100/80 px-2 py-0.5 rounded-md border border-purple-300">
                                    Skema 3: Kombinasi (1 & 2)
                                </span>
                                <span class="text-[10px] font-semibold text-purple-700">
                                    Tarif & Shift Potong
                                </span>
                            </div>
                            <div class="mt-1">
                                <p class="text-xs text-slate-500 font-medium">Beban Harian Unit:</p>
                                <p class="text-lg sm:text-xl font-black text-purple-950 font-mono">
                                    {{ formatRupiah(simulasiSummary.bebanHarianUnitSkema3) }}
                                    <span class="text-xs font-semibold text-purple-600">/ hari</span>
                                </p>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-purple-200/60 space-y-1 text-[11px] text-slate-600">
                                <div class="flex items-center justify-between">
                                    <span>Total Periode:</span>
                                    <span class="font-bold font-mono text-purple-900">{{ formatRupiah(simulasiSummary.totalBebanSkema3) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Rata-rata/orang/hari:</span>
                                    <span class="font-bold font-mono text-purple-900">{{ formatRupiah(simulasiSummary.rata2PerHariPerOrangSkema3) }} / hari</span>
                                </div>
                                <div class="flex items-center justify-between text-purple-700">
                                    <span>Hemat Maks vs Tarif Shifting:</span>
                                    <span class="font-bold font-mono">-{{ formatRupiah(simulasiSummary.hematVsNormalSkema3) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Komparasi Efisiensi Anggaran -->
                        <div class="p-3.5 sm:p-4 rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50/70 to-orange-50/50 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-amber-800 bg-amber-100 px-2 py-0.5 rounded-md border border-amber-300">
                                    Komparasi 3 Skema
                                </span>
                                <span class="text-[10px] font-bold text-amber-800">
                                    {{ simulasiSummary.selisihBebanHarianUnit >= 0 ? 'Skema 2 Lebih Hemat S1' : 'Skema 1 Lebih Hemat S2' }}
                                </span>
                            </div>
                            <div class="mt-1">
                                <p class="text-xs text-slate-500 font-medium">Selisih Beban Harian (S1 - S2):</p>
                                <p class="text-lg sm:text-xl font-black font-mono" :class="simulasiSummary.selisihBebanHarianUnit >= 0 ? 'text-emerald-700' : 'text-blue-700'">
                                    {{ formatRupiah(Math.abs(simulasiSummary.selisihBebanHarianUnit)) }}
                                    <span class="text-xs font-semibold text-slate-500">/ hari</span>
                                </p>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-amber-200/60 space-y-1 text-[11px] leading-tight text-slate-600">
                                <div class="flex items-center justify-between">
                                    <span>Efisiensi S3 vs S1:</span>
                                    <span class="font-bold font-mono text-purple-900">-{{ formatRupiah(simulasiSummary.selisihBebanHarianUnit3Vs1) }} / hari</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Total Selisih S1 vs S2:</span>
                                    <span class="font-bold font-mono text-amber-950">{{ formatRupiah(Math.abs(simulasiSummary.selisihBeban)) }}</span>
                                </div>
                                <p class="text-[10px] text-slate-500 pt-0.5">
                                    💡 <strong>Skema 3 (Kombinasi)</strong> memberikan efisiensi anggaran paling optimal bagi unit SPPG.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar Aksi Cepat Simulasi -->
                    <div class="flex flex-wrap items-center justify-between gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <!-- Filter Divisi & Preset Pengurangan -->
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="font-bold text-slate-700 flex items-center gap-1">
                                <Sliders class="h-3.5 w-3.5 text-slate-500" />
                                Preset Skema 1:
                            </span>
                            <button
                                type="button"
                                @click="setBatchPenguranganSimulasi(25000)"
                                class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold transition cursor-pointer"
                                title="Set pengurangan Rp 25.000 untuk personil (sesuai contoh skema)"
                            >
                                Potong 25 rb
                            </button>
                            <button
                                type="button"
                                @click="setBatchPenguranganSimulasi(20000)"
                                class="px-2 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold transition cursor-pointer"
                            >
                                20 rb
                            </button>
                            <button
                                type="button"
                                @click="setBatchPenguranganSimulasi(15000)"
                                class="px-2 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-semibold transition cursor-pointer"
                            >
                                15 rb
                            </button>
                            <button
                                type="button"
                                @click="setBatchPenguranganSimulasi(0)"
                                class="px-2 py-1 rounded-lg bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 font-semibold transition cursor-pointer"
                            >
                                Reset 0
                            </button>

                            <!-- Input Nominal Kustom (Desain Seamless & Modern) -->
                            <div class="inline-flex items-center rounded-lg border border-slate-300 bg-white shadow-2xs hover:border-slate-400 focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all overflow-hidden">
                                <span class="px-2 py-1 bg-slate-50 border-r border-slate-200 text-[11px] font-bold text-slate-500 select-none">
                                    Rp
                                </span>
                                <input
                                    type="text"
                                    v-model="customPenguranganInput"
                                    @keyup.enter="applyCustomPenguranganSimulasi"
                                    placeholder="Kustom..."
                                    class="w-20 px-2 py-1 text-xs font-mono font-bold text-slate-800 bg-white border-none outline-none ring-0 focus:ring-0 focus:outline-none placeholder:text-slate-400 placeholder:font-normal"
                                    title="Ketik nominal kustom lalu tekan Enter atau klik Terapkan"
                                />
                                <button
                                    type="button"
                                    @click="applyCustomPenguranganSimulasi"
                                    class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-[11px] font-bold border-l border-indigo-600 transition-colors cursor-pointer"
                                    title="Terapkan nominal kustom ini ke personil"
                                >
                                    Terapkan
                                </button>
                            </div>

                            <span class="text-slate-300 mx-1 hidden sm:inline">|</span>

                            <!-- Filter Divisi -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-500 font-medium hidden sm:inline">Divisi:</span>
                                <select
                                    v-model="simulasiFilterDivisi"
                                    class="py-1 px-2 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 font-medium focus:ring-1 focus:ring-indigo-500"
                                >
                                    <option value="all">Semua Divisi ({{ props.petugas.length }})</option>
                                    <option v-for="j in daftarJabatan" :key="j" :value="j">{{ j }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons: Terapkan Tarif -->
                        <div class="flex items-center gap-2 flex-wrap text-xs">
                            <button
                                type="button"
                                @click="applySkema1TarifToShiftingTable"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-xs transition cursor-pointer"
                                title="Terapkan tarif final Skema 1 ke kolom Tarif Harian Shifting di atas"
                            >
                                <Check class="h-3.5 w-3.5" />
                                <span>Terapkan Tarif Skema 1 ke Tabel Atas</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Komparasi 3 Skema -->
                    <div class="overflow-x-auto border border-slate-200 rounded-xl max-h-[60vh] shadow-2xs">
                        <table class="w-full text-left border-collapse min-w-[1100px] text-xs">
                            <!-- Table Header -->
                            <thead class="sticky top-0 z-20 shadow-xs select-none">
                                <!-- Baris 1: Pengelompokan Skema -->
                                <tr class="text-white text-[10px] font-bold uppercase tracking-wider text-center">
                                    <th colspan="3" class="py-1.5 px-3 bg-slate-800 border-r border-slate-700">
                                        Data Personil (Tarif Shifting)
                                    </th>
                                    <th colspan="4" class="py-1.5 px-2 bg-gradient-to-r from-indigo-800 to-blue-800 border-r border-indigo-600">
                                        SKEMA 1: PENGURANGAN TARIF (Kerja Penuh)
                                    </th>
                                    <th colspan="4" class="py-1.5 px-2 bg-gradient-to-r from-emerald-800 to-teal-800 border-r border-emerald-600">
                                        SKEMA 2: PENGURANGAN HARI (Tarif Utuh)
                                    </th>
                                    <th colspan="3" class="py-1.5 px-2 bg-gradient-to-r from-purple-800 to-fuchsia-800 border-r border-purple-600">
                                        SKEMA 3: KOMBINASI (Tarif Potong + Shift)
                                    </th>
                                    <th class="py-1.5 px-3 bg-amber-900 border-l border-amber-800">
                                        SELISIH
                                    </th>
                                </tr>

                                <!-- Baris 2: Sub-kolom rincian -->
                                <tr class="bg-slate-100 text-slate-700 border-b border-slate-200 text-xs font-bold">
                                    <!-- Personil -->
                                    <th class="py-2 px-2 text-center w-10 border-r border-slate-200">No</th>
                                    <th class="py-2 px-3 min-w-[170px] border-r border-slate-200">Nama Personil & Divisi</th>
                                    <th class="py-2 px-2.5 text-right min-w-[90px] border-r border-slate-200 bg-slate-100 text-slate-800" title="Gaji awal mengikuti tarif shifting yang ditentukan di tabel atas">
                                        Gaji Shifting
                                    </th>

                                    <!-- Skema 1 Kolom -->
                                    <th class="py-2 px-2 text-right min-w-[100px] border-r border-slate-200 bg-indigo-50/60 text-indigo-900" title="Ketik nominal potongan tarif harian">
                                        Potongan
                                    </th>
                                    <th class="py-2 px-2 text-right min-w-[85px] border-r border-slate-200 bg-indigo-50/60 text-indigo-900">
                                        Gaji Final
                                    </th>
                                    <th class="py-2 px-1 text-center w-14 border-r border-slate-200 bg-indigo-50/60 text-indigo-900">
                                        Hari Kerja
                                    </th>
                                    <th class="py-2 px-2.5 text-right min-w-[95px] border-r border-slate-200 bg-indigo-100/70 text-indigo-950 font-black">
                                        Total (S1)
                                    </th>

                                    <!-- Skema 2 Kolom -->
                                    <th class="py-2 px-2 text-right min-w-[85px] border-r border-slate-200 bg-emerald-50/60 text-emerald-900">
                                        Gaji Utuh
                                    </th>
                                    <th class="py-2 px-1.5 text-center min-w-[65px] border-r border-slate-200 bg-emerald-50/60 text-emerald-900">
                                        Hari Shift
                                    </th>
                                    <th class="py-2 px-1 text-center min-w-[60px] border-r border-slate-200 bg-emerald-50/60 text-emerald-900">
                                        Potong Hr
                                    </th>
                                    <th class="py-2 px-2.5 text-right min-w-[95px] border-r border-slate-200 bg-emerald-100/70 text-emerald-950 font-black">
                                        Total (S2)
                                    </th>

                                    <!-- Skema 3 Kolom (Kombinasi) -->
                                    <th class="py-2 px-2 text-right min-w-[85px] border-r border-slate-200 bg-purple-50/60 text-purple-900" title="Tarif setelah dipotong (sama dengan Skema 1)">
                                        Tarif Potong
                                    </th>
                                    <th class="py-2 px-1 text-center w-14 border-r border-slate-200 bg-purple-50/60 text-purple-900" title="Hari kerja hadir shift (sama dengan Skema 2)">
                                        Hari Shift
                                    </th>
                                    <th class="py-2 px-2.5 text-right min-w-[95px] border-r border-slate-200 bg-purple-100/70 text-purple-950 font-black">
                                        Total (S3)
                                    </th>

                                    <!-- Delta -->
                                    <th class="py-2 px-2.5 text-right min-w-[120px] bg-amber-50 text-amber-950 font-black">
                                        Selisih
                                    </th>
                                </tr>
                            </thead>

                            <!-- Table Body -->
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr
                                    v-for="(p, idx) in filteredPetugasSimulasi"
                                    :key="p.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <!-- No -->
                                    <td class="py-2 px-2 text-center text-slate-400 font-mono border-r border-slate-200">
                                        {{ idx + 1 }}
                                    </td>

                                    <!-- Nama Personil -->
                                    <td class="py-2 px-3 border-r border-slate-200">
                                        <p class="font-bold text-slate-900 text-xs leading-tight">
                                            {{ p.nama }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">
                                            {{ p.jabatan || '-' }}
                                        </p>
                                    </td>

                                    <!-- Gaji Awal Master BGN -->
                                    <td class="py-2 px-2.5 text-right font-bold text-slate-800 font-mono border-r border-slate-200 bg-slate-50/40">
                                        {{ formatRibuan(getPersonilSimulasi(p).gajiAwal) }}
                                    </td>

                                    <!-- SKEMA 1: Pengurangan Input -->
                                    <td class="py-1 px-1.5 text-right border-r border-slate-200 bg-indigo-50/20">
                                        <div class="relative flex items-center justify-end">
                                            <input
                                                type="number"
                                                :value="getPersonilSimulasi(p).pengurangan"
                                                @input="updateSimulasiPengurangan(p.id, $event.target.value)"
                                                min="0"
                                                step="5000"
                                                class="w-20 py-1 px-1.5 text-right font-mono text-xs font-bold rounded border border-indigo-200 bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                            />
                                        </div>
                                    </td>

                                    <!-- SKEMA 1: Gaji Final -->
                                    <td class="py-2 px-2 text-right font-mono font-bold text-indigo-900 border-r border-slate-200 bg-indigo-50/20">
                                        {{ formatRibuan(getPersonilSimulasi(p).gajiFinalSkema1) }}
                                    </td>

                                    <!-- SKEMA 1: Hari Kerja -->
                                    <td class="py-2 px-1 text-center font-mono font-bold text-slate-700 border-r border-slate-200 bg-indigo-50/20">
                                        {{ getPersonilSimulasi(p).hariKerjaSkema1 }} hr
                                    </td>

                                    <!-- SKEMA 1: Total Gaji -->
                                    <td class="py-2 px-2.5 text-right font-mono border-r border-slate-200 bg-indigo-100/40">
                                        <div class="font-black text-xs text-indigo-950">
                                            {{ formatRupiah(getPersonilSimulasi(p).totalGajiSkema1) }}
                                        </div>
                                        <div class="text-[10px] font-bold text-indigo-700">
                                            {{ formatRupiah(getPersonilSimulasi(p).gajiPerHariSkema1) }}<span class="font-normal text-slate-500">/hr</span>
                                        </div>
                                    </td>

                                    <!-- SKEMA 2: Gaji Final Utuh -->
                                    <td class="py-2 px-2 text-right font-mono font-bold text-emerald-900 border-r border-slate-200 bg-emerald-50/20">
                                        {{ formatRibuan(getPersonilSimulasi(p).gajiFinalSkema2) }}
                                    </td>

                                    <!-- SKEMA 2: Hari Kerja Input -->
                                    <td class="py-1 px-1.5 text-center border-r border-slate-200 bg-emerald-50/20">
                                        <div class="flex items-center justify-center gap-1">
                                            <input
                                                type="number"
                                                :value="getPersonilSimulasi(p).hariKerjaSkema2"
                                                @input="updateSimulasiHariKerjaSkema2(p.id, $event.target.value)"
                                                min="0"
                                                :max="getPersonilSimulasi(p).hariKerjaSkema1"
                                                step="0.5"
                                                class="w-14 py-1 px-1 text-center font-mono text-xs font-bold rounded border border-emerald-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                                            />
                                            <span class="text-[10px] text-slate-400">hr</span>
                                        </div>
                                    </td>

                                    <!-- SKEMA 2: Pengurangan Hari -->
                                    <td class="py-2 px-1 text-center font-mono text-xs border-r border-slate-200 bg-emerald-50/20">
                                        <span
                                            v-if="getPersonilSimulasi(p).penguranganHariKerja > 0"
                                            class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800"
                                        >
                                            -{{ getPersonilSimulasi(p).penguranganHariKerja }} hr
                                        </span>
                                        <span v-else class="text-slate-400 font-bold">-</span>
                                    </td>

                                    <!-- SKEMA 2: Total Gaji -->
                                    <td class="py-2 px-2.5 text-right font-mono border-r border-slate-200 bg-emerald-100/40">
                                        <div class="font-black text-xs text-emerald-950">
                                            {{ formatRupiah(getPersonilSimulasi(p).totalGajiSkema2) }}
                                        </div>
                                        <div class="text-[10px] font-bold text-emerald-700">
                                            {{ formatRupiah(getPersonilSimulasi(p).gajiPerHariSkema2) }}<span class="font-normal text-slate-500">/hr</span>
                                        </div>
                                    </td>

                                    <!-- SKEMA 3: Tarif Potong (Kombinasi) -->
                                    <td class="py-2 px-2 text-right font-mono font-bold text-purple-900 border-r border-slate-200 bg-purple-50/20">
                                        {{ formatRibuan(getPersonilSimulasi(p).gajiFinalSkema3) }}
                                    </td>

                                    <!-- SKEMA 3: Hari Shift -->
                                    <td class="py-2 px-1 text-center font-mono font-bold text-purple-900 border-r border-slate-200 bg-purple-50/20">
                                        {{ getPersonilSimulasi(p).hariKerjaSkema3 }} hr
                                    </td>

                                    <!-- SKEMA 3: Total Gaji -->
                                    <td class="py-2 px-2.5 text-right font-mono border-r border-slate-200 bg-purple-100/40">
                                        <div class="font-black text-xs text-purple-950">
                                            {{ formatRupiah(getPersonilSimulasi(p).totalGajiSkema3) }}
                                        </div>
                                        <div class="text-[10px] font-bold text-purple-700">
                                            {{ formatRupiah(getPersonilSimulasi(p).gajiPerHariSkema3) }}<span class="font-normal text-slate-500">/hr</span>
                                        </div>
                                    </td>

                                    <!-- KOMPARASI: Selisih -->
                                    <td class="py-2 px-2.5 text-right font-mono bg-amber-50/40">
                                        <div class="font-black text-xs text-slate-800">
                                            {{ getPersonilSimulasi(p).selisihGaji > 0 ? '+' : '' }}{{ formatRupiah(getPersonilSimulasi(p).selisihGaji) }}
                                        </div>
                                        <div
                                            class="mt-0.5 inline-block px-1.5 py-0.5 rounded text-[10px] font-bold font-mono"
                                            :class="getPersonilSimulasi(p).selisihGajiPerHari >= 0 ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
                                        >
                                            {{ getPersonilSimulasi(p).selisihGajiPerHari > 0 ? '+' : '' }}{{ formatRupiah(getPersonilSimulasi(p).selisihGajiPerHari) }} / hari
                                        </div>
                                        <div class="mt-1 pt-0.5 border-t border-amber-200/60 text-[9px] text-purple-900 font-bold">
                                            S3: -{{ formatRupiah(getPersonilSimulasi(p).selisihGajiSkema3Vs1) }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- Table Footer Summary -->
                            <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 text-xs">
                                <!-- Baris 1: Total Akumulasi Periode/Mingguan -->
                                <tr class="bg-slate-100">
                                    <td colspan="2" class="py-2.5 px-3 text-right font-black text-slate-800 border-r border-slate-300">
                                        TOTAL PERIODE ({{ filteredPetugasSimulasi.length }} Personil):
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-mono font-bold border-r border-slate-300 text-slate-800">
                                        -
                                    </td>

                                    <!-- Skema 1 Footer -->
                                    <td colspan="3" class="py-2.5 px-2 text-right font-black text-indigo-900 border-r border-slate-200 bg-indigo-50/80">
                                        Total Skema 1:
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-mono font-black text-sm text-indigo-950 border-r border-slate-300 bg-indigo-100">
                                        {{ formatRupiah(simulasiSummary.totalBebanSkema1) }}
                                    </td>

                                    <!-- Skema 2 Footer -->
                                    <td colspan="3" class="py-2.5 px-2 text-right font-black text-emerald-900 border-r border-slate-200 bg-emerald-50/80">
                                        Total Skema 2:
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-mono font-black text-sm text-emerald-950 border-r border-slate-300 bg-emerald-100">
                                        {{ formatRupiah(simulasiSummary.totalBebanSkema2) }}
                                    </td>

                                    <!-- Skema 3 Footer -->
                                    <td colspan="2" class="py-2.5 px-2 text-right font-black text-purple-900 border-r border-slate-200 bg-purple-50/80">
                                        Total Skema 3:
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-mono font-black text-sm text-purple-950 border-r border-slate-300 bg-purple-100">
                                        {{ formatRupiah(simulasiSummary.totalBebanSkema3) }}
                                    </td>

                                    <!-- Delta Footer -->
                                    <td class="py-2.5 px-2.5 text-right font-mono font-black text-xs bg-amber-100/90 text-amber-950">
                                        Δ {{ formatRupiah(Math.abs(simulasiSummary.selisihBeban)) }}
                                    </td>
                                </tr>

                                <!-- Baris 2: TOTAL & SELISIH AKHIR PER 1 HARI (HARIAN) -->
                                <tr class="bg-amber-50/80 border-t border-amber-200 text-amber-950">
                                    <td colspan="2" class="py-2.5 px-3 text-right font-black text-amber-950 border-r border-amber-200">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                                            <span class="tracking-wide">TOTAL PER 1 HARI KERJA (HARIAN):</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-mono font-bold border-r border-amber-200 text-slate-500">
                                        -
                                    </td>

                                    <!-- Skema 1 Harian -->
                                    <td colspan="3" class="py-2.5 px-2 text-right font-bold text-indigo-950 border-r border-amber-200 bg-indigo-50/90">
                                        Beban S1 / Hari:
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-mono font-black text-sm text-indigo-950 border-r border-amber-200 bg-indigo-100/90">
                                        <div>{{ formatRupiah(simulasiSummary.bebanHarianUnitSkema1) }}</div>
                                        <div class="text-[10px] font-semibold text-indigo-700">/ 1 hari unit</div>
                                    </td>

                                    <!-- Skema 2 Harian -->
                                    <td colspan="3" class="py-2.5 px-2 text-right font-bold text-emerald-950 border-r border-amber-200 bg-emerald-50/90">
                                        Beban S2 / Hari:
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-mono font-black text-sm text-emerald-950 border-r border-amber-200 bg-emerald-100/90">
                                        <div>{{ formatRupiah(simulasiSummary.bebanHarianUnitSkema2) }}</div>
                                        <div class="text-[10px] font-semibold text-emerald-700">/ 1 hari unit</div>
                                    </td>

                                    <!-- Skema 3 Harian -->
                                    <td colspan="2" class="py-2.5 px-2 text-right font-bold text-purple-950 border-r border-amber-200 bg-purple-50/90">
                                        Beban S3 / Hari:
                                    </td>
                                    <td class="py-2.5 px-2.5 text-right font-mono font-black text-sm text-purple-950 border-r border-amber-200 bg-purple-100/90">
                                        <div>{{ formatRupiah(simulasiSummary.bebanHarianUnitSkema3) }}</div>
                                        <div class="text-[10px] font-semibold text-purple-700">/ 1 hari unit</div>
                                    </td>

                                    <!-- Delta Harian -->
                                    <td class="py-2.5 px-2.5 text-right font-mono font-black bg-amber-200/90 text-amber-950">
                                        <div class="text-sm">Δ {{ formatRupiah(Math.abs(simulasiSummary.selisihBebanHarianUnit)) }}</div>
                                        <div class="text-[10px] font-bold text-amber-800">/ 1 hari unit</div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- ─── Section Baru: Analisis Harian per Tim / Divisi (Komparasi 3 Skema) ─── -->
                    <div class="mt-6 pt-5 border-t-2 border-slate-200 space-y-4">
                        <!-- Header Section Analisis Harian -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-4 rounded-xl text-white shadow-xs">
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                    <CalendarDays class="h-5 w-5" />
                                </span>
                                <div>
                                    <h4 class="text-sm sm:text-base font-black tracking-wide flex items-center gap-2">
                                        Analisis Beban Harian per Tim / Divisi (Komparasi 3 Skema)
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                            {{ dateColumns.length }} Hari Kalender
                                        </span>
                                    </h4>
                                    <p class="text-xs text-slate-300 mt-0.5">
                                        Rincian jumlah personil aktif dan total nominal beban harian masing-masing dari 3 skema per tim/divisi.
                                    </p>
                                </div>
                            </div>

                            <!-- Filter & Toggle Action -->
                            <div class="flex items-center gap-2 flex-wrap text-xs">
                                <!-- Filter Divisi Analisis -->
                                <div class="flex items-center gap-1.5 bg-slate-800/90 border border-slate-700 px-2.5 py-1 rounded-lg">
                                    <span class="text-slate-400 font-medium">Tim:</span>
                                    <select
                                        v-model="analisisHarianFilterDivisi"
                                        class="bg-transparent text-white font-bold text-xs focus:outline-none cursor-pointer"
                                    >
                                        <option value="all" class="bg-slate-800 text-white">Semua Tim / Divisi ({{ listDivisiAktif.length }})</option>
                                        <option v-for="d in listDivisiAktif" :key="d" :value="d" class="bg-slate-800 text-white">{{ d }}</option>
                                    </select>
                                </div>

                                <!-- Toggle Expand / Collapse All -->
                                <button
                                    type="button"
                                    @click="toggleAllDatesAnalysis"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition cursor-pointer shadow-xs"
                                >
                                    <Layers class="h-3.5 w-3.5" />
                                    <span>{{ isAllDatesAnalysisExpanded ? 'Tutup Rincian Tim' : 'Buka Rincian Tim' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tabel Analisis Harian per Tanggal & Tim -->
                        <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-xs max-h-[70vh]">
                            <table class="w-full text-left border-collapse min-w-[980px] text-xs">
                                <thead class="sticky top-0 z-20 shadow-xs select-none bg-slate-800 text-white text-[11px] font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="py-2.5 px-3 min-w-[150px] border-r border-slate-700">Tanggal & Hari</th>
                                        <th class="py-2.5 px-3 min-w-[160px] border-r border-slate-700">Tim / Divisi</th>
                                        <th class="py-2.5 px-2 text-center w-28 border-r border-slate-700">Personil Hadir</th>
                                        <th class="py-2.5 px-3 text-right min-w-[120px] border-r border-slate-700 bg-indigo-900/60 text-indigo-200">
                                            Total Skema 1
                                        </th>
                                        <th class="py-2.5 px-3 text-right min-w-[120px] border-r border-slate-700 bg-emerald-900/60 text-emerald-200">
                                            Total Skema 2
                                        </th>
                                        <th class="py-2.5 px-3 text-right min-w-[120px] border-r border-slate-700 bg-purple-900/60 text-purple-200">
                                            Total Skema 3 (Kombinasi)
                                        </th>
                                        <th class="py-2.5 px-3 text-right min-w-[120px] bg-amber-900/60 text-amber-200">
                                            Selisih (S1 - S2)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <template v-for="d in dailyAnalysisData" :key="d.dateStr">
                                        <!-- Master Row: Tanggal & Total Keseluruhan Unit pada Hari Tersebut -->
                                        <tr
                                            class="bg-slate-100/90 font-bold hover:bg-slate-200/70 transition-colors cursor-pointer border-t border-slate-300"
                                            @click="toggleDateAnalysis(d.dateStr)"
                                        >
                                            <!-- Tanggal & Hari -->
                                            <td class="py-2 px-3 border-r border-slate-200">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-1.5">
                                                        <span
                                                            class="inline-flex items-center justify-center w-5 h-5 rounded-md text-[10px] font-mono font-bold"
                                                            :class="d.isWeekend ? 'bg-rose-100 text-rose-700' : 'bg-indigo-100 text-indigo-700'"
                                                        >
                                                            {{ d.dayNum }}
                                                        </span>
                                                        <span class="font-bold text-slate-800 text-xs">
                                                            {{ d.dayName }}, {{ formatTanggalIndo(d.dateStr) }}
                                                        </span>
                                                    </div>
                                                    <span
                                                        v-if="d.isToday"
                                                        class="text-[9px] uppercase font-black px-1.5 py-0.2 rounded bg-indigo-600 text-white"
                                                    >
                                                        Hari Ini
                                                    </span>
                                                    <span
                                                        v-else-if="d.isWeekend"
                                                        class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-rose-100 text-rose-700"
                                                    >
                                                        Weekend
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Label Divisi / Ringkasan -->
                                            <td class="py-2 px-3 border-r border-slate-200 text-slate-700">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-bold text-slate-900">
                                                        {{ analisisHarianFilterDivisi === 'all' ? 'TOTAL SELURUH TIM' : analisisHarianFilterDivisi }}
                                                    </span>
                                                    <span class="text-[10px] text-slate-500 font-normal">
                                                        ({{ isDateAnalysisExpanded(d.dateStr) ? 'Sembunyikan' : 'Klik utk Rincian' }})
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Personil Hadir Unit -->
                                            <td class="py-2 px-2 text-center font-mono border-r border-slate-200">
                                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-900 border border-indigo-200">
                                                    {{ d.grandPersonilShift }} / {{ d.totalAnggotaUnit }} org
                                                </span>
                                            </td>

                                            <!-- Total Skema 1 -->
                                            <td class="py-2 px-3 text-right font-mono font-bold text-indigo-950 border-r border-slate-200 bg-indigo-50/50">
                                                {{ formatRupiah(d.grandTotalSkema1) }}
                                            </td>

                                            <!-- Total Skema 2 -->
                                            <td class="py-2 px-3 text-right font-mono font-bold text-emerald-950 border-r border-slate-200 bg-emerald-50/50">
                                                {{ formatRupiah(d.grandTotalSkema2) }}
                                            </td>

                                            <!-- Total Skema 3 -->
                                            <td class="py-2 px-3 text-right font-mono font-bold text-purple-950 border-r border-slate-200 bg-purple-50/50">
                                                {{ formatRupiah(d.grandTotalSkema3) }}
                                            </td>

                                            <!-- Selisih Efisiensi Harian -->
                                            <td class="py-2 px-3 text-right font-mono font-black bg-amber-50/60 text-amber-950">
                                                <span class="block">
                                                    {{ d.selisihUnit1Vs2 >= 0 ? '+' : '' }}{{ formatRupiah(d.selisihUnit1Vs2) }}
                                                </span>
                                                <span class="text-[9px] font-normal text-purple-800">
                                                    S3 hemat: -{{ formatRupiah(d.selisihUnit1Vs3) }}
                                                </span>
                                            </td>
                                        </tr>

                                        <!-- Child Rows: Rincian per Divisi pada Tanggal Tersebut -->
                                        <template v-if="isDateAnalysisExpanded(d.dateStr)">
                                            <tr
                                                v-for="div in d.divisiBreakdown"
                                                :key="div.divisi"
                                                class="bg-white hover:bg-slate-50 text-[11px] transition-colors"
                                            >
                                                <!-- Spacer Indent Tanggal -->
                                                <td class="py-1.5 px-3 border-r border-slate-100 text-slate-400 pl-8">
                                                    <span class="text-[10px]">↳ {{ d.dayName }}</span>
                                                </td>

                                                <!-- Nama Divisi -->
                                                <td class="py-1.5 px-3 border-r border-slate-100">
                                                    <span class="font-semibold text-slate-800 flex items-center gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                        {{ div.divisi }}
                                                    </span>
                                                </td>

                                                <!-- Kehadiran Divisi -->
                                                <td class="py-1.5 px-2 text-center font-mono border-r border-slate-100">
                                                    <span
                                                        class="inline-block px-1.5 py-0.2 rounded font-bold"
                                                        :class="div.personilMasukShift > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-400'"
                                                    >
                                                        {{ div.personilMasukShift }} / {{ div.totalAnggota }} org
                                                    </span>
                                                </td>

                                                <!-- Skema 1 Divisi -->
                                                <td class="py-1.5 px-3 text-right font-mono text-indigo-900 border-r border-slate-100 bg-indigo-50/20">
                                                    {{ formatRupiah(div.totalSkema1) }}
                                                </td>

                                                <!-- Skema 2 Divisi -->
                                                <td class="py-1.5 px-3 text-right font-mono text-emerald-900 border-r border-slate-100 bg-emerald-50/20">
                                                    {{ formatRupiah(div.totalSkema2) }}
                                                </td>

                                                <!-- Skema 3 Divisi -->
                                                <td class="py-1.5 px-3 text-right font-mono text-purple-900 border-r border-slate-100 bg-purple-50/20">
                                                    {{ formatRupiah(div.totalSkema3) }}
                                                </td>

                                                <!-- Selisih Divisi -->
                                                <td class="py-1.5 px-3 text-right font-mono text-amber-900 bg-amber-50/20">
                                                    <span class="font-bold">
                                                        {{ div.selisih1Vs2 >= 0 ? '+' : '' }}{{ formatRupiah(div.selisih1Vs2) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>

                                <!-- Footer Grand Total Analisis Harian -->
                                <tfoot class="sticky bottom-0 z-10 bg-slate-900 text-white font-bold text-xs border-t-2 border-slate-700">
                                    <tr>
                                        <td colspan="2" class="py-2.5 px-3 text-right font-black uppercase tracking-wider text-slate-200 border-r border-slate-700">
                                            TOTAL AKUMULASI PERIODE ({{ dateColumns.length }} HARI):
                                        </td>
                                        <td class="py-2.5 px-2 text-center font-mono font-black border-r border-slate-700 text-indigo-300">
                                            {{ dailyAnalysisGrandTotal.totalHadirShift }} shift
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-black text-sm text-indigo-300 border-r border-slate-700 bg-indigo-950/80">
                                            {{ formatRupiah(dailyAnalysisGrandTotal.totalBiayaS1) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-black text-sm text-emerald-300 border-r border-slate-700 bg-emerald-950/80">
                                            {{ formatRupiah(dailyAnalysisGrandTotal.totalBiayaS2) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-black text-sm text-purple-300 border-r border-slate-700 bg-purple-950/80">
                                            {{ formatRupiah(dailyAnalysisGrandTotal.totalBiayaS3) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-black text-amber-300 bg-amber-950/80">
                                            Δ {{ formatRupiah(Math.abs(dailyAnalysisGrandTotal.selisih1Vs2)) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
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
                        class="fixed inset-0 z-[99999] h-screen h-[100dvh] w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 lg:p-6"
                        style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
                    >
                        <div class="relative w-full max-w-[96vw] xl:max-w-[1360px] 2xl:max-w-[1480px] rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[96dvh] sm:max-h-[92vh] my-auto animate-in fade-in zoom-in-95 duration-200">
                            <!-- Modal Header -->
                            <div class="p-3.5 sm:p-4.5 lg:p-5 flex items-center justify-between bg-slate-50/90 border-b border-slate-100 shrink-0">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 rounded-xl bg-primary/10 text-primary border border-primary/20">
                                        <Sparkles class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                            Generator Pola Shifting Hari Kerja
                                        </h3>
                                        <p class="text-[11px] sm:text-xs text-slate-500">
                                            Terapkan pola rotasi otomatis ke personil SPPG
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="closeGeneratorModal"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Modal Body: 2 Kolom di Desktop (Memanfaatkan Sisi Lebar Layar), 1 Kolom Rapi di Mobile -->
                            <div class="p-3 sm:p-5 lg:p-6 text-xs overflow-y-auto flex-1 overscroll-contain">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 items-start">
                                    
                                    <!-- ─── Kolom Kiri (Desktop): Personil Wajib & Pengaturan Lingkup ─── -->
                                    <div class="lg:col-span-4 xl:col-span-4 space-y-4">
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
                                                            Hari Sabtu & Minggu otomatis diatur Libur (L). Khusus petugas Keamanan tetap dijadwalkan Masuk (H).
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ─── Kolom Kanan (Desktop): Pilihan Pola Shifting ─── -->
                                    <div class="lg:col-span-8 xl:col-span-8 space-y-4">

                                        <!-- Opsi Pola Rotasi -->
                                        <div class="space-y-2">
                                            <label class="block font-bold text-slate-800 text-xs">
                                                Pilih Model Pola Shifting (Untuk Personil Lainnya):
                                            </label>
                                            <div class="grid grid-cols-1 gap-3">
                                                <!-- Pola 0: Rotasi Bergilir Mingguan (Bagi Rata Personil per Tim) -->
                                                <label
                                                    :class="[
                                                        'p-3.5 sm:p-4 rounded-xl border flex items-start gap-3 cursor-pointer transition-colors',
                                                        generatorPattern === 'rotasi_mingguan'
                                                            ? 'bg-primary/5 border-primary ring-1 ring-primary/30 shadow-2xs'
                                                            : 'border-slate-200 hover:bg-slate-50',
                                                    ]"
                                                >
                                                    <input
                                                        type="radio"
                                                        v-model="generatorPattern"
                                                        value="rotasi_mingguan"
                                                        class="mt-1 text-primary focus:ring-primary h-4 w-4 shrink-0 cursor-pointer"
                                                    />
                                                    <div class="w-full space-y-3">
                                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <p class="font-bold text-slate-900 text-xs sm:text-sm">
                                                                    Rotasi Bergilir Mingguan (Bagi Rata Personil per Tim)
                                                                </p>
                                                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-primary text-white shadow-2xs">
                                                                    Rekomendasi
                                                                </span>
                                                            </div>
                                                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 self-start sm:self-auto">
                                                                Bagi Rata Mingguan
                                                            </span>
                                                        </div>
                                                        <p class="text-slate-500 text-[11px] sm:text-xs leading-relaxed">
                                                            Berapa pun jumlah personil di tiap divisi, seluruhnya dibagi rata menjadi 2 regu bergantian setiap minggu (misal: 6 orang ➜ 3 masuk & 3 libur di Minggu 1, lalu bertukar di Minggu 2). Hari kerja aktif mingguan dan status tiap hari tetap bebas disesuaikan.
                                                        </p>

                                                        <!-- Konfigurasi Khusus Rotasi Mingguan -->
                                                        <div
                                                            v-if="generatorPattern === 'rotasi_mingguan'"
                                                            class="pt-3 border-t border-slate-200 space-y-3.5"
                                                            @click.stop
                                                        >
                                                            <!-- 1. Model Giliran Mingguan (Bagi Rata Seluruh Personil) -->
                                                            <div class="space-y-2">
                                                                <div class="flex items-center justify-between gap-2">
                                                                    <label class="block font-bold text-slate-800 text-xs">
                                                                        Model Pembagian Giliran Mingguan:
                                                                    </label>
                                                                    <button
                                                                        type="button"
                                                                        @click="generatorWeeklyType = generatorWeeklyType === '1_orang' ? 'bagi_rata' : '1_orang'"
                                                                        class="text-[11px] text-primary hover:underline font-semibold cursor-pointer"
                                                                    >
                                                                        {{ generatorWeeklyType === '1_orang' ? 'Kembali ke Bagi Rata Tim' : 'Mode Khusus 1 Org Saja' }}
                                                                    </button>
                                                                </div>

                                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                    <button
                                                                        type="button"
                                                                        @click="generatorWeeklyType = 'bagi_rata'"
                                                                        :class="[
                                                                            'p-3 rounded-xl text-left border transition-all cursor-pointer flex items-center justify-between gap-2',
                                                                            generatorWeeklyType === 'bagi_rata'
                                                                                ? 'bg-primary text-white border-primary shadow-xs ring-2 ring-primary/20'
                                                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                        ]"
                                                                    >
                                                                        <div>
                                                                            <p class="font-bold text-xs sm:text-sm">Bagi Rata Personil (50% : 50%)</p>
                                                                            <p :class="generatorWeeklyType === 'bagi_rata' ? 'text-white/85' : 'text-slate-500'" class="text-[11px] font-normal mt-0.5">
                                                                                Jumlah personil tim dibagi rata sama banyak (Rekomendasi)
                                                                            </p>
                                                                        </div>
                                                                        <div :class="generatorWeeklyType === 'bagi_rata' ? 'bg-white text-primary' : 'bg-slate-100 text-transparent'" class="p-1 rounded-full shrink-0">
                                                                            <Check class="h-3.5 w-3.5" />
                                                                        </div>
                                                                    </button>

                                                                    <button
                                                                        type="button"
                                                                        @click="generatorWeeklyType = 'bagi_minggu'"
                                                                        :class="[
                                                                            'p-3 rounded-xl text-left border transition-all cursor-pointer flex items-center justify-between gap-2',
                                                                            generatorWeeklyType === 'bagi_minggu'
                                                                                ? 'bg-primary text-white border-primary shadow-xs ring-2 ring-primary/20'
                                                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50',
                                                                        ]"
                                                                    >
                                                                        <div>
                                                                            <p class="font-bold text-xs sm:text-sm">Bagi Rata ke Seluruh Minggu</p>
                                                                            <p :class="generatorWeeklyType === 'bagi_minggu' ? 'text-white/85' : 'text-slate-500'" class="text-[11px] font-normal mt-0.5">
                                                                                Dibagi rata ke {{ weekPeriods.length }} kelompok minggu
                                                                            </p>
                                                                        </div>
                                                                        <div :class="generatorWeeklyType === 'bagi_minggu' ? 'bg-white text-primary' : 'bg-slate-100 text-transparent'" class="p-1 rounded-full shrink-0">
                                                                            <Check class="h-3.5 w-3.5" />
                                                                        </div>
                                                                    </button>
                                                                </div>

                                                                <!-- Penjelasan Bagi Rata -->
                                                                <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-xs leading-relaxed flex items-start gap-2">
                                                                    <Sparkles class="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" />
                                                                    <div>
                                                                        <strong class="font-bold">Sistem Bagi Rata Personil:</strong> Berapa pun jumlah personil di tim, seluruhnya dibagi rata menjadi 2 regu seimbang dan bergantian tiap minggu (misal: tim isi 8 orang ➜ 4 masuk & 4 libur di Minggu 1, lalu bertukar di Minggu 2; jika tim 6 orang ➜ 3 dan 3; jika tim 4 orang ➜ 2 dan 2; jika tim 2 orang ➜ 1 dan 1).
                                                                    </div>
                                                                </div>

                                                                <!-- Sub-opsi Metode Regu jika Bagi Rata -->
                                                                <div v-if="generatorWeeklyType === 'bagi_rata'" class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                                    <span class="text-xs text-slate-700 font-bold">Metode Pembagian Regu:</span>
                                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                                        <button
                                                                            type="button"
                                                                            @click="generatorTeamMethod = 'ganjil_genap'"
                                                                            :class="[
                                                                                'px-3 py-1 rounded-lg text-xs font-bold border transition-colors cursor-pointer',
                                                                                generatorTeamMethod === 'ganjil_genap'
                                                                                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100',
                                                                            ]"
                                                                        >
                                                                            Selang-seling (Ganjil/Genap)
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            @click="generatorTeamMethod = 'separuh'"
                                                                            :class="[
                                                                                'px-3 py-1 rounded-lg text-xs font-bold border transition-colors cursor-pointer',
                                                                                generatorTeamMethod === 'separuh'
                                                                                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100',
                                                                            ]"
                                                                        >
                                                                            Separuh Tim (Depan/Belakang)
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- 2. Hari Kerja Aktif dalam Seminggu (Kustom Hari) -->
                                                            <div class="space-y-2 p-3 bg-slate-50 rounded-xl border border-slate-200">
                                                                <div class="flex items-center justify-between gap-2">
                                                                    <label class="block font-bold text-slate-800 text-xs">
                                                                        Hari Kerja Aktif Mingguan (Kustom Hari):
                                                                    </label>
                                                                    <span class="text-[11px] font-bold text-primary px-2 py-0.5 rounded-md bg-primary/10">
                                                                        {{ totalWorkDaysCount }} Hari Aktif per Periode
                                                                    </span>
                                                                </div>

                                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                                    <button
                                                                        type="button"
                                                                        @click="setWeeklyPresetDays('senin_jumat')"
                                                                        :class="[
                                                                            'py-1.5 px-2 rounded-lg text-center text-xs font-bold border transition-colors cursor-pointer',
                                                                            generatorWeeklyWorkDays === 'senin_jumat'
                                                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100',
                                                                        ]"
                                                                    >
                                                                        Senin–Jumat (5 Hari)
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        @click="setWeeklyPresetDays('senin_sabtu')"
                                                                        :class="[
                                                                            'py-1.5 px-2 rounded-lg text-center text-xs font-bold border transition-colors cursor-pointer',
                                                                            generatorWeeklyWorkDays === 'senin_sabtu'
                                                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100',
                                                                        ]"
                                                                    >
                                                                        Senin–Sabtu (6 Hari)
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        @click="setWeeklyPresetDays('semua')"
                                                                        :class="[
                                                                            'py-1.5 px-2 rounded-lg text-center text-xs font-bold border transition-colors cursor-pointer',
                                                                            generatorWeeklyWorkDays === 'semua'
                                                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100',
                                                                        ]"
                                                                    >
                                                                        Semua Hari (7 Hari)
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        @click="generatorWeeklyWorkDays = 'kustom'"
                                                                        :class="[
                                                                            'py-1.5 px-2 rounded-lg text-center text-xs font-bold border transition-colors cursor-pointer',
                                                                            generatorWeeklyWorkDays === 'kustom'
                                                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs'
                                                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100',
                                                                        ]"
                                                                    >
                                                                        Pilih Manual
                                                                    </button>
                                                                </div>

                                                                <!-- Checklist Hari Kustom jika dipilih Kustom -->
                                                                <div
                                                                    v-if="generatorWeeklyWorkDays === 'kustom'"
                                                                    class="pt-2 border-t border-slate-200 flex items-center gap-1.5 flex-wrap"
                                                                >
                                                                    <span class="text-[11px] font-bold text-slate-600 mr-1">Centang Hari Aktif:</span>
                                                                    <button
                                                                        v-for="d in daysOfWeekList"
                                                                        :key="d.num"
                                                                        type="button"
                                                                        @click="toggleWeeklyCustomDay(d.num)"
                                                                        :class="[
                                                                            'px-2.5 py-1 rounded-md text-xs font-bold border transition-colors cursor-pointer flex items-center gap-1',
                                                                            generatorWeeklyCustomDays.includes(d.num)
                                                                                ? 'bg-emerald-600 text-white border-emerald-600'
                                                                                : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-100',
                                                                        ]"
                                                                    >
                                                                        <Check v-if="generatorWeeklyCustomDays.includes(d.num)" class="h-3 w-3" />
                                                                        <span>{{ d.label }}</span>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <!-- 3. Giliran Awal & Metode Pembagian Minggu -->
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                                <div class="space-y-1">
                                                                    <label class="block font-bold text-slate-700 text-xs">
                                                                        Giliran Masuk Minggu 1:
                                                                    </label>
                                                                    <select
                                                                        v-model="generatorStartType"
                                                                        class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                                    >
                                                                        <option value="tim_a_first">Regu A Mulai di Minggu 1 (Gantian M2)</option>
                                                                        <option value="tim_b_first">Regu B Mulai di Minggu 1 (Gantian M2)</option>
                                                                    </select>
                                                                </div>

                                                                <div class="space-y-1">
                                                                    <label class="block font-bold text-slate-700 text-xs">
                                                                        Metode Hitung Minggu:
                                                                    </label>
                                                                    <select
                                                                        v-model="generatorWeeklyGrouping"
                                                                        class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                                    >
                                                                        <option value="blok_7">Siklus 7 Hari (Hari 1-7 = M1, Hari 8-14 = M2)</option>
                                                                        <option value="kalender">Kalender Mingguan (Senin s/d Minggu)</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <!-- 4. Pratinjau Simulasi Bagi Rata per Minggu -->
                                                            <div class="space-y-2 pt-1">
                                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                                    <label class="block font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                                                        <Sparkles class="h-4 w-4 text-primary" />
                                                                        <span>Pratinjau Simulasi Bagi Rata per Minggu ({{ weekPeriods.length }} Minggu Terdeteksi):</span>
                                                                    </label>
                                                                    <div v-if="generatorTargetDivisi === 'all' && shiftingDivisiSummary.length > 1" class="flex items-center gap-1.5">
                                                                        <span class="text-xs text-slate-500 font-medium">Lihat Divisi:</span>
                                                                        <select
                                                                            v-model="previewDivisi"
                                                                            class="py-1 px-2.5 text-xs font-bold rounded-lg border border-slate-300 bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-primary"
                                                                        >
                                                                            <option v-for="d in shiftingDivisiSummary" :key="d.name" :value="d.name">
                                                                                {{ d.name }} ({{ d.count }} Org)
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <!-- Ringkasan chips rasio bagi rata semua divisi jika target all -->
                                                                <div v-if="generatorTargetDivisi === 'all' && shiftingDivisiSummary.length > 0" class="flex items-center gap-1.5 flex-wrap p-2 rounded-xl bg-slate-50 border border-slate-200/80">
                                                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mr-1">Rasio Bagi Rata:</span>
                                                                    <span
                                                                        v-for="d in shiftingDivisiSummary"
                                                                        :key="d.name"
                                                                        class="px-2 py-0.5 rounded-lg text-xs font-medium bg-white border border-slate-200 text-slate-700 shadow-2xs"
                                                                    >
                                                                        <strong>{{ d.name }}:</strong>
                                                                        {{ d.count === 1 ? '1 Masuk Penuh' : `${Math.ceil(d.count / 2)} Masuk / ${Math.floor(d.count / 2)} Libur` }}
                                                                    </span>
                                                                </div>

                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-64 sm:max-h-60 overflow-y-auto pr-1">
                                                                    <div
                                                                        v-for="wk in weeklySimulationSummary"
                                                                        :key="wk.weekNum"
                                                                        class="p-3 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-2"
                                                                    >
                                                                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                                                            <div class="flex items-center gap-1.5">
                                                                                <span class="font-bold text-slate-900 text-xs sm:text-sm">{{ wk.label }}</span>
                                                                                <span class="text-[11px] text-slate-400 font-medium">({{ wk.divisiName }})</span>
                                                                            </div>
                                                                            <span class="text-[11px] text-slate-500 font-medium">
                                                                                {{ formatTglSingkat(wk.startDate) }} – {{ formatTglSingkat(wk.endDate) }}
                                                                            </span>
                                                                        </div>
                                                                        <div class="space-y-2">
                                                                            <div>
                                                                                <div class="flex items-center gap-1.5 mb-1">
                                                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                                                        Masuk: {{ wk.activeCount }} Personil
                                                                                    </span>
                                                                                </div>
                                                                                <div class="flex flex-wrap gap-1">
                                                                                    <span
                                                                                        v-for="name in wk.activeNames"
                                                                                        :key="name"
                                                                                        class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-900 border border-emerald-200"
                                                                                    >
                                                                                        {{ name }}
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                            <div v-if="wk.offNames.length > 0">
                                                                                <div class="flex items-center gap-1.5 mb-1">
                                                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800">
                                                                                        Libur: {{ wk.offCount }} Personil
                                                                                    </span>
                                                                                </div>
                                                                                <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto pr-1">
                                                                                    <span
                                                                                        v-for="name in wk.offNames"
                                                                                        :key="name"
                                                                                        class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200"
                                                                                    >
                                                                                        {{ name }}
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- 5. Banner Fleksibilitas Kustom Langsung -->
                                                            <div class="p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-start gap-2.5">
                                                                <Info class="h-4.5 w-4.5 shrink-0 text-blue-600 mt-0.5" />
                                                                <div>
                                                                    <span class="font-bold">Kustomisasi Bebas: </span>
                                                                    <span>Setelah generator menerapkan jadwal mingguan ini, seluruh sel tanggal tetap dapat Anda klik dan ubah manual (H, L, I, S, C) di tabel matriks kapan saja sesuai kebutuhan.</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>

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
                            <div class="p-3.5 sm:p-4.5 flex flex-col sm:flex-row items-center justify-between sm:justify-end gap-2.5 bg-slate-50/90 border-t border-slate-100 shrink-0">
                                <div class="text-[11px] text-slate-500 hidden sm:block mr-auto">
                                    Pola yang dipilih akan mengisi otomatis jadwal rotasi pada matriks shifting.
                                </div>
                                <div class="flex items-center gap-2 w-full sm:w-auto">
                                    <button
                                        type="button"
                                        @click="closeGeneratorModal"
                                        class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 bg-white hover:bg-slate-100 font-semibold text-xs sm:text-sm transition-colors cursor-pointer text-center"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        @click="applyGeneratorPattern"
                                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-bold text-xs sm:text-sm shadow-xs transition-colors cursor-pointer text-center"
                                    >
                                        <Sparkles class="h-4 w-4" />
                                        <span>Terapkan Pola ke Matriks</span>
                                    </button>
                                </div>
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

            <!-- Modal Tempel / Paste Deret Kolom Gaji (Excel / Spreadsheet) -->
            <Teleport to="body">
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="isPasteTarifModalOpen"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                        @click.self="closePasteTarifModal"
                    >
                        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]">
                            <!-- Header -->
                            <div class="p-4 sm:p-5 border-b border-slate-100 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 rounded-xl bg-white/10 ring-1 ring-white/20">
                                        <ClipboardPaste class="h-5 w-5 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-base text-white leading-tight">
                                            Tempel / Paste Deret Kolom Gaji
                                        </h3>
                                        <p class="text-xs text-emerald-100">
                                            Salin 1 kolom nominal dari Excel / Spreadsheet lalu tempel di sini sekaligus.
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="closePasteTarifModal"
                                    class="p-1 rounded-lg text-emerald-100 hover:text-white hover:bg-white/10 transition cursor-pointer"
                                >
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="p-4 sm:p-6 overflow-y-auto space-y-4 flex-1">
                                <!-- Petugas Awal Selector -->
                                <div>
                                    <label class="block font-bold text-xs text-slate-700 mb-1">
                                        Mulai tempel dari personil:
                                    </label>
                                    <select
                                        v-model="pasteTarifStartPetugasId"
                                        class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    >
                                        <option
                                            v-for="(p, pIdx) in filteredPetugas"
                                            :key="p.id"
                                            :value="p.id"
                                        >
                                            #{{ pIdx + 1 }} - {{ p.nama }} ({{ p.jabatan || '-' }}) - Tarif saat ini: {{ formatRupiah(getTarifShifting(p.id)) }}
                                        </option>
                                    </select>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Baris ke-1 akan dipasang ke personil ini, baris ke-2 ke personil berikutnya, dan seterusnya.
                                    </p>
                                </div>

                                <!-- Textarea Paste -->
                                <div>
                                    <label class="block font-bold text-xs text-slate-700 mb-1 flex items-center justify-between">
                                        <span>Area Tempel (Tekan Ctrl+V di sini):</span>
                                        <span v-if="pasteTarifPreview.length > 0" class="text-[11px] font-semibold text-emerald-700">
                                            Terdeteksi {{ pasteTarifPreview.length }} baris data
                                        </span>
                                    </label>
                                    <textarea
                                        v-model="pasteTarifRawText"
                                        rows="6"
                                        placeholder="Contoh salinan kolom dari Excel:&#10;100000&#10;125.000&#10;Rp 100.000&#10;125000"
                                        class="w-full font-mono text-xs p-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50/50"
                                    ></textarea>
                                    <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                                        <span>Format fleksibel: otomatis membersihkan "Rp", titik, spasi, atau desimal.</span>
                                        <button
                                            v-if="pasteTarifRawText"
                                            type="button"
                                            @click="pasteTarifRawText = ''"
                                            class="text-rose-500 hover:text-rose-700 font-semibold cursor-pointer"
                                        >
                                            Bersihkan
                                        </button>
                                    </div>
                                </div>

                                <!-- Live Preview Table -->
                                <div v-if="pasteTarifPreview.length > 0" class="border border-slate-200 rounded-xl overflow-hidden">
                                    <div class="bg-slate-100/80 px-3 py-2 border-b border-slate-200 flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700">
                                            Pratinjau Pemetaan Personil ({{ pasteTarifValidCount }} akan diterapkan)
                                        </span>
                                    </div>
                                    <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 text-xs">
                                        <div
                                            v-for="item in pasteTarifPreview"
                                            :key="item.lineIndex"
                                            :class="[
                                                'px-3 py-2 flex items-center justify-between gap-2',
                                                item.isOutOfRange ? 'bg-rose-50/50 text-slate-400' : (item.isChanged ? 'bg-emerald-50/40' : 'bg-white'),
                                            ]"
                                        >
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="w-6 text-center font-mono text-[10px] text-slate-400">
                                                    #{{ item.lineIndex }}
                                                </span>
                                                <div v-if="item.petugas" class="min-w-0">
                                                    <p class="font-bold text-slate-800 truncate">{{ item.petugas.nama }}</p>
                                                    <p class="text-[10px] text-slate-400 truncate">{{ item.petugas.jabatan || '-' }}</p>
                                                </div>
                                                <div v-else class="text-rose-500 italic text-[11px]">
                                                    (Melebihi sisa personil di tabel)
                                                </div>
                                            </div>

                                            <div v-if="item.petugas" class="flex items-center gap-2 shrink-0 font-mono text-[11px]">
                                                <span class="text-slate-400 line-through">
                                                    {{ formatRupiah(item.curRate) }}
                                                </span>
                                                <ArrowRight class="w-3 h-3 text-slate-400" />
                                                <span :class="item.isChanged ? 'font-bold text-emerald-700' : 'text-slate-600'">
                                                    {{ formatRupiah(item.newRate) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="p-4 sm:p-5 flex items-center justify-between bg-slate-50/80 border-t border-slate-100">
                                <p class="text-[11px] text-slate-500">
                                    * Tarif tersimpan khusus untuk sesi shifting ini.
                                </p>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="closePasteTarifModal"
                                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold text-xs transition-colors cursor-pointer"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        @click="applyBatchPastedTarif"
                                        :disabled="pasteTarifValidCount === 0"
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-white font-bold text-xs shadow-xs transition-colors cursor-pointer',
                                            pasteTarifValidCount > 0 ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-300 cursor-not-allowed',
                                        ]"
                                    >
                                        <Check class="h-4 w-4" />
                                        <span>Terapkan {{ pasteTarifValidCount }} Tarif</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </Teleport>
        </div>
    </AppLayout>
</template>

