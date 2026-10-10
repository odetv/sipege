<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import RekapDistribusiControlBar from "./Partials/RekapDistribusiControlBar.vue";
import RekapDistribusiKpiCards from "./Partials/RekapDistribusiKpiCards.vue";
import RekapDistribusiMatrixTable from "./Partials/RekapDistribusiMatrixTable.vue";
import RekapDistribusiExportModal from "./Partials/RekapDistribusiExportModal.vue";
import { downloadRekapDistribusiExcel } from "@/Services/exportDistribusiHelper";

const props = defineProps({
    user: { type: Object, default: () => ({}) },
    unitSppg: { type: Object, default: null },
    periodes: { type: Array, default: () => [] },
    activePeriode: { type: Object, default: null },
    distribusiList: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    initialMode: { type: String, default: "bulanan" },
    initialTanggalMulai: { type: String, default: "" },
    initialTanggalSelesai: { type: String, default: "" },
    initialPeriodeId: { type: [String, Number], default: "all" },
    initialDistribusiMap: { type: Object, default: () => ({}) },
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
    const daysToAdd = mode === "periodik" ? 13 : 27;
    const end = new Date(base);
    end.setDate(base.getDate() + daysToAdd);
    const y = end.getFullYear();
    const m = String(end.getMonth() + 1).padStart(2, "0");
    const d = String(end.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

function normalizeModeDist(mode) {
    if (!mode) return "";
    const m = String(mode).toLowerCase();
    if (m === "hari_ini" || m === "today") return "hari_ini";
    if (m === "periode" || m === "periodik" || m === "period") return "periode";
    if (m === "rentang" || m === "bulanan" || m === "range" || m === "custom") return "rentang";
    return "";
}

// Helper default tanggal & periode:
// Sesuai permintaan user: di rekap distribusi default dengan periode bukan hari ini
function getInitialDateState() {
    const today = todayStr.value;
    const sorted = [...(props.periodes || [])].sort((a, b) => {
        const noA = Number(a.nomor_periode) || 0;
        const noB = Number(b.nomor_periode) || 0;
        if (noB !== noA) return noB - noA;
        return String(b.tanggal_mulai || "").localeCompare(String(a.tanggal_mulai || ""));
    });
    const latest = sorted[0];

    let defaultPeriodeRes = null;
    if (latest && latest.tanggal_mulai && latest.tanggal_selesai) {
        defaultPeriodeRes = {
            start: String(latest.tanggal_mulai).substring(0, 10),
            end: String(latest.tanggal_selesai).substring(0, 10),
            mode: "periode",
            periodeId: String(latest.id),
        };
    } else {
        defaultPeriodeRes = {
            start: today,
            end: today,
            mode: "hari_ini",
            periodeId: "all",
        };
    }

    if (props.initialTanggalMulai && props.initialTanggalSelesai) {
        const norm = normalizeModeDist(props.initialMode);
        return {
            start: props.initialTanggalMulai,
            end: props.initialTanggalSelesai,
            mode: norm || defaultPeriodeRes.mode,
            periodeId: props.initialPeriodeId !== undefined ? String(props.initialPeriodeId) : defaultPeriodeRes.periodeId,
        };
    }

    return defaultPeriodeRes;
}

// ─── State Filter & Mode Skala ────────────────────────────────────────────────
const initialDateState = getInitialDateState();
const modeSkala = ref(initialDateState.mode);
const tanggalMulai = ref(initialDateState.start);
const tanggalSelesai = ref(initialDateState.end);
const selectedPeriodeId = ref(initialDateState.periodeId);
const searchQuery = ref("");
const selectedKategoriFilter = ref("all");
const selectedStatusFilter = ref("all");

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

// ─── Dynamic Date Columns (Mirip Persis Rekap Kehadiran) ──────────────────────
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


// ─── State Matriks Distribusi Harian (Interaktif) ──────────────────────────────
const matrixDistribusi = ref({});
const isDirty = ref(false);
const isSaving = ref(false);

function initMatrixDistribusi() {
    const map = {};
    const dbMap = props.initialDistribusiMap || {};

    (props.distribusiList || []).forEach((item) => {
        map[item.id] = {};
        const rowDb = dbMap[item.id] || {};

        dateColumns.value.forEach((col) => {
            if (rowDb[col.dateStr] !== undefined) {
                map[item.id][col.dateStr] = rowDb[col.dateStr];
            } else {
                // Default KOSONG jika belum ada catatan pencatatan
                map[item.id][col.dateStr] = "";
            }
        });
    });

    matrixDistribusi.value = map;
    isDirty.value = false;
}

watch(
    () => [props.initialDistribusiMap, props.distribusiList, dateColumns.value],
    () => {
        initMatrixDistribusi();
    },
    { deep: true }
);

onMounted(() => {
    initMatrixDistribusi();
});

// Cycle status: "" (Kosong) -> T (Terkirim) -> P (Proses) -> L (Libur) -> "" (Kembali Kosong)
function cycleStatus(itemId, dateStr) {
    if (!matrixDistribusi.value[itemId]) {
        matrixDistribusi.value[itemId] = {};
    }
    const current = matrixDistribusi.value[itemId][dateStr] || "";
    let next = "T";
    if (!current || current === "") next = "T";
    else if (current === "T") next = "P";
    else if (current === "P") next = "L";
    else if (current === "L") next = "";
    else next = "";

    matrixDistribusi.value[itemId][dateStr] = next;
    isDirty.value = true;
}

function toggleKolomStatus(dateStr) {
    const allT = props.distribusiList.every(
        (it) => matrixDistribusi.value[it.id]?.[dateStr] === "T"
    );
    const nextSt = allT ? "" : "T";
    props.distribusiList.forEach((it) => {
        if (!matrixDistribusi.value[it.id]) matrixDistribusi.value[it.id] = {};
        matrixDistribusi.value[it.id][dateStr] = nextSt;
    });
    isDirty.value = true;
    showToast(nextSt === "T" ? `Seluruh titik pada tanggal ${dateStr} diset Terkirim (T)` : `Kolom tanggal ${dateStr} dikosongkan`);
}

function setSemuaTerkirim() {
    props.distribusiList.forEach((it) => {
        if (!matrixDistribusi.value[it.id]) matrixDistribusi.value[it.id] = {};
        dateColumns.value.forEach((col) => {
            if (!col.isSunday) {
                matrixDistribusi.value[it.id][col.dateStr] = "T";
            }
        });
    });
    isDirty.value = true;
    showToast("Seluruh titik pada hari kerja diset Terkirim (T)");
}

function kosongkanSemua() {
    props.distribusiList.forEach((it) => {
        if (!matrixDistribusi.value[it.id]) matrixDistribusi.value[it.id] = {};
        dateColumns.value.forEach((col) => {
            matrixDistribusi.value[it.id][col.dateStr] = "";
        });
    });
    isDirty.value = true;
    showToast("Seluruh pencatatan distribusi dikosongkan");
}

function resetDistribusi() {
    initMatrixDistribusi();
    showToast("Data dikembalikan ke data tersimpan di database");
}

// ─── Simpan Distribusi ke Server ──────────────────────────────────────────────
function handleSimpanDistribusi() {
    if (isSaving.value) return;
    isSaving.value = true;

    try {
        const payload = JSON.stringify(matrixDistribusi.value);

        router.post(
            route("penerima-manfaat.rekap-distribusi.simpan"),
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
                    showToast("Data rekap distribusi berhasil disimpan ke database!");
                },
                onError: (err) => {
                    console.error("Gagal simpan distribusi:", err);
                    isSaving.value = false;
                    showToast("Gagal menyimpan rekap distribusi.");
                },
                onFinish: () => {
                    isSaving.value = false;
                },
            }
        );
    } catch (e) {
        console.error("Error simpan distribusi:", e);
        isSaving.value = false;
        showToast("Terjadi kesalahan saat menyimpan data.");
    }
}

// ─── Filter & Navigasi Rentang Tanggal ─────────────────────────────────────────
function applyFilterTanggal() {
    router.get(
        route("penerima-manfaat.rekap-distribusi"),
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

function onDateFilterChange(filter) {
    if (!filter) return;
    tanggalMulai.value = filter.start;
    tanggalSelesai.value = filter.end;
    modeSkala.value = filter.mode;
    selectedPeriodeId.value = filter.periodeId;

    router.get(
        route("penerima-manfaat.rekap-distribusi"),
        {
            mode: filter.mode,
            tanggal_mulai: filter.start,
            tanggal_selesai: filter.end,
            periode_id: filter.periodeId,
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

// Filter Client-Side Instant
const filteredItems = computed(() => {
    let list = props.distribusiList || [];

    if (searchQuery.value && searchQuery.value.trim() !== "") {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(item => {
            const nama = (item.nama_kelompok || "").toLowerCase();
            const npsn = (item.kode_identitas || "").toLowerCase();
            const pic = (item.nama_pic || "").toLowerCase();
            const desa = (item.desa_kelurahan || "").toLowerCase();
            return nama.includes(q) || npsn.includes(q) || pic.includes(q) || desa.includes(q);
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

// Dynamic Computed Summary
const computedSummary = computed(() => {
    const list = filteredItems.value;
    const totalPorsiKecil = list.reduce((sum, it) => sum + (Number(it.porsi_kecil_harian) || 0), 0);
    const totalPorsiBesar = list.reduce((sum, it) => sum + (Number(it.porsi_besar_harian) || 0), 0);
    const totalPorsiHarian = list.reduce((sum, it) => sum + (Number(it.total_porsi_harian) || 0), 0);
    
    // Hitung total akumulasi riil dari data matrix & persentase layanan
    let totalPorsiAkumulasi = 0;
    let totalSlotTerkirim = 0;
    const totalHariKerja = dateColumns.value.filter(col => !col.isSunday).length || dateColumns.value.length || 1;
    const totalSlotHarusKirim = list.length * totalHariKerja;

    list.forEach(it => {
        let hariKirim = 0;
        dateColumns.value.forEach(col => {
            if (matrixDistribusi.value[it.id]?.[col.dateStr] === "T") {
                hariKirim++;
                totalSlotTerkirim++;
            }
        });
        totalPorsiAkumulasi += (Number(it.total_porsi_harian) || 0) * hariKirim;
    });

    const totalPenerima = list.reduce((sum, it) => sum + (Number(it.total_penerima) || 0), 0);
    const persentaseLayanan = totalSlotHarusKirim > 0
        ? Math.min(100, Math.round((totalSlotTerkirim / totalSlotHarusKirim) * 100))
        : 100;

    return {
        total_kelompok: list.length,
        total_sekolah: list.filter(it => it.kategori !== "Posyandu").length,
        total_posyandu: list.filter(it => it.kategori === "Posyandu").length,
        total_penerima: totalPenerima,
        total_porsi_kecil_harian: totalPorsiKecil,
        total_porsi_besar_harian: totalPorsiBesar,
        total_porsi_harian: totalPorsiHarian,
        total_porsi_akumulasi: totalPorsiAkumulasi,
        total_hari: dateColumns.value.length,
        persentase_layanan: persentaseLayanan,
        rata_rata_porsi_per_kelompok: list.length > 0 ? Math.round(totalPorsiHarian / list.length) : 0,
    };
});

// ─── Ekspor Excel ─────────────────────────────────────────────────────────────
const isExporting = ref(false);
const showExportModal = ref(false);
const exportModalMode = ref("current");

function openExportModal(mode = "current") {
    exportModalMode.value = mode;
    showExportModal.value = true;
}

async function handleDownloadExcel(customStart = null, customEnd = null) {
    try {
        isExporting.value = true;
        // Tangkal jika dipanggil dari DOM event object (bukan string tanggal)
        const sDate = (typeof customStart === "string" && customStart.length >= 8) ? customStart : tanggalMulai.value;
        const eDate = (typeof customEnd === "string" && customEnd.length >= 8) ? customEnd : tanggalSelesai.value;

        await downloadRekapDistribusiExcel({
            distribusiList: filteredItems.value,
            matrixDistribusi: matrixDistribusi.value,
            dateColumns: dateColumns.value,
            stats: computedSummary.value,
            startDate: sDate,
            endDate: eDate,
            mode: modeSkala.value,
            unitSppg: props.unitSppg,
        });
        showExportModal.value = false;
        showToast("Laporan Excel Rekap Distribusi berhasil diunduh.");
    } catch (err) {
        console.error("Gagal export Excel:", err);
        alert("Terjadi kesalahan saat mengekspor Excel: " + (err?.message || err));
    } finally {
        isExporting.value = false;
    }
}

// Direct 1 Hari Export
async function downloadHariIniDirect() {
    await handleDownloadExcel(todayStr.value, todayStr.value);
}

// ─── Toast System ─────────────────────────────────────────────────────────────
const toastMessage = ref("");
const showToastNotification = ref(false);
let toastTimer = null;

function showToast(msg) {
    toastMessage.value = msg;
    showToastNotification.value = true;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        showToastNotification.value = false;
    }, 4000);
}
</script>

<template>
    <Head title="Rekap Distribusi Penerima Manfaat - SIPEGE" />

    <AppLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto space-y-4">
            <!-- ─── Toast Notification ───────────────────────────────────────── -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showToastNotification"
                    class="fixed bottom-5 right-5 z-50 max-w-sm rounded-xl bg-slate-900 text-white px-4 py-3 shadow-xl border border-slate-700 flex items-center gap-3 text-xs font-medium"
                >
                    <div class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></div>
                    <span>{{ toastMessage }}</span>
                </div>
            </transition>

            <!-- ─── Komponen 1: Control Bar (Filter & Aksi) ───────────────────── -->
            <RekapDistribusiControlBar
                v-model:modeSkala="modeSkala"
                v-model:searchQuery="searchQuery"
                v-model:selectedKategoriFilter="selectedKategoriFilter"
                v-model:selectedStatusFilter="selectedStatusFilter"
                :selectedPeriodeId="selectedPeriodeId"
                :periodes="periodes"
                :tanggalMulai="tanggalMulai"
                :tanggalSelesai="tanggalSelesai"
                :dateColumns="dateColumns"
                :labelSiklus="labelSiklus"
                :unitSppg="unitSppg"
                :todayStr="todayStr"
                :isExporting="isExporting"
                :isDirty="isDirty"
                :isSaving="isSaving"
                @dateFilterChange="onDateFilterChange"
                @selectPeriode="selectPeriode"
                @changeModeSkala="changeModeSkala"
                @applyDateRange="onApplyDateRange"
                @simpanDistribusi="handleSimpanDistribusi"
                @setSemuaTerkirim="setSemuaTerkirim"
                @kosongkanSemua="kosongkanSemua"
                @resetDistribusi="resetDistribusi"
                @openExportModal="openExportModal"
                @downloadHariIniDirect="downloadHariIniDirect"
            />

            <!-- ─── Komponen 2: KPI Statistics Cards (6 Cards) ────────────────── -->
            <RekapDistribusiKpiCards
                :summary="computedSummary"
                :stats="computedSummary"
                :dateColumns="dateColumns"
                :modeSkala="modeSkala"
            />

            <!-- ─── Komponen 3: Matriks Harian Distribusi (Identik Presensi) ───── -->
            <RekapDistribusiMatrixTable
                :items="filteredItems"
                :dateColumns="dateColumns"
                :tanggalMulai="tanggalMulai"
                :tanggalSelesai="tanggalSelesai"
                :matrixDistribusi="matrixDistribusi"
                @cycleStatus="cycleStatus"
                @toggleKolomStatus="toggleKolomStatus"
            />
        </div>

        <!-- ─── Modal Dialog Ekspor Excel ───────────────────────────────────── -->
        <RekapDistribusiExportModal
            :show="showExportModal"
            :isOpen="showExportModal"
            :mode="exportModalMode"
            :initialMode="exportModalMode"
            :startDate="tanggalMulai"
            :endDate="tanggalSelesai"
            :tanggalMulai="tanggalMulai"
            :tanggalSelesai="tanggalSelesai"
            :todayStr="todayStr"
            :periodes="periodes"
            :totalDays="dateColumns.length"
            :isExporting="isExporting"
            @close="showExportModal = false"
            @confirmExport="handleDownloadExcel"
            @confirm="handleDownloadExcel"
        />
    </AppLayout>
</template>
