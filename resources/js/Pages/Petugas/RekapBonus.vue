<script setup>
import { ref, computed, watch } from "vue";
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
} from "lucide-vue-next";
import { downloadRekapBonusExcel, formatTanggalIndo } from "@/Services/exportPetugasHelper";
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
});

const page = usePage();

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

const currentMode = ref(props.initialMode || "bulanan");
const selectedPeriodeId = ref(props.initialPeriodeId || "all");
const tanggalMulai = ref(props.initialTanggalMulai || todayStr.value);
const tanggalSelesai = ref(props.initialTanggalSelesai || getDefaultEndDate(currentMode.value, tanggalMulai.value));
const presensiState = ref({ ...(props.initialPresensiMap || {}) });

const searchQuery = ref("");
const filterJabatan = ref("all");
const isExporting = ref(false);

// Detail Modal Slip Bonus
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

// Format Rupiah
function formatRupiah(val) {
    const n = Number(val) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(n);
}

function formatRibuan(val) {
    const n = Number(val) || 0;
    return new Intl.NumberFormat("id-ID").format(n);
}

// Generate Date Columns
const dateColumns = computed(() => {
    if (!tanggalMulai.value || !tanggalSelesai.value) return [];
    const list = [];
    let cur = new Date(tanggalMulai.value + "T00:00:00");
    const end = new Date(tanggalSelesai.value + "T00:00:00");
    let safeLoop = 0;

    const DAY_NAMES = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
    const MONTH_SHORT = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agt", "Sep", "Okt", "Nov", "Des"];

    while (cur <= end && safeLoop < 60) {
        safeLoop++;
        const y = cur.getFullYear();
        const m = String(cur.getMonth() + 1).padStart(2, "0");
        const d = String(cur.getDate()).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const dayIdx = cur.getDay();

        list.push({
            dateStr,
            dayName: DAY_NAMES[dayIdx],
            dayNum: cur.getDate(),
            monthShort: MONTH_SHORT[cur.getMonth()],
            isWeekend: dayIdx === 0,
        });

        cur.setDate(cur.getDate() + 1);
    }
    return list;
});

// Hitung Statistik Bonus per Petugas
const computedBonusStats = computed(() => {
    const stats = {};
    const cols = dateColumns.value;

    props.petugas.forEach((p) => {
        let h = 0;
        let h2 = 0;
        const pAtt = presensiState.value[p.id] || {};

        if (p.status === "Aktif") {
            cols.forEach((dt) => {
                const st = pAtt[dt.dateStr] || "";
                if (st === "H") h++;
                else if (st === "H2") h2++;
            });
        }

        const mandays = h + (h2 * 0.5);
        const bonusHarian = parseInt(p.bonus_harian_mitra) || 0;
        const totalBonus = mandays * bonusHarian;

        stats[p.id] = {
            hadirPenuh: h,
            setengahHari: h2,
            mandays,
            bonusHarian,
            totalBonus,
        };
    });

    return stats;
});

// KPI Summary
const kpiStats = computed(() => {
    let totalPenerima = 0;
    let totalMandays = 0;
    let grandTotalBonus = 0;
    let totalBonusHarianRate = 0;

    props.petugas.forEach((p) => {
        if (p.status === "Aktif") {
            const st = computedBonusStats.value[p.id] || { mandays: 0, totalBonus: 0, bonusHarian: 0 };
            totalPenerima++;
            totalMandays += st.mandays;
            grandTotalBonus += st.totalBonus;
            totalBonusHarianRate += st.bonusHarian;
        }
    });

    return {
        totalPenerima,
        totalMandays,
        grandTotalBonus,
        totalBonusHarianRate,
    };
});

// Filter Petugas
const filteredPetugas = computed(() => {
    return props.petugas.filter((p) => {
        const matchesSearch =
            !searchQuery.value ||
            p.nama.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            p.nik.includes(searchQuery.value);
        const matchesJabatan =
            filterJabatan.value === "all" || p.jabatan === filterJabatan.value;
        return matchesSearch && matchesJabatan;
    });
});

// Handle Period Filter Changes
function handleFilterChange(filterData) {
    if (filterData.mode) currentMode.value = filterData.mode;
    if (filterData.periodeId !== undefined) selectedPeriodeId.value = filterData.periodeId;
    if (filterData.tanggalMulai) tanggalMulai.value = filterData.tanggalMulai;
    if (filterData.tanggalSelesai) tanggalSelesai.value = filterData.tanggalSelesai;

    router.get(
        route("petugas.rekap-bonus"),
        {
            mode: currentMode.value,
            periode_id: selectedPeriodeId.value,
            tanggal_mulai: tanggalMulai.value,
            tanggal_selesai: tanggalSelesai.value,
        },
        { preserveState: true, preserveScroll: true }
    );
}

// Download Excel
async function handleExportExcel() {
    isExporting.value = true;
    try {
        const matchedPeriode = props.periodes.find(
            (p) => String(p.id) === String(selectedPeriodeId.value)
        );

        await downloadRekapBonusExcel({
            petugas: props.petugas,
            presensiMap: presensiState.value,
            dateList: dateColumns.value,
            startDate: tanggalMulai.value,
            endDate: tanggalSelesai.value,
            unitSppg: props.unitSppg,
            periode: matchedPeriode,
            modeLabel: currentMode.value === "periodik" ? "Periodik (14 Hari Kerja)" : "Bulanan (28 Hari Kerja)",
        });
    } catch (e) {
        console.error("Gagal export excel:", e);
        alert("Gagal mendownload file Excel.");
    } finally {
        isExporting.value = false;
    }
}

function getJabatanBadgeClass(jabatan) {
    const j = (jabatan || "").toLowerCase();
    if (j.includes("kepala") || j.includes("manager")) return "bg-purple-50 text-purple-700 border-purple-200";
    if (j.includes("ahli gizi")) return "bg-emerald-50 text-emerald-700 border-emerald-200";
    if (j.includes("akuntan") || j.includes("keuangan")) return "bg-blue-50 text-blue-700 border-blue-200";
    if (j.includes("masak") || j.includes("koki") || j.includes("chef")) return "bg-amber-50 text-amber-700 border-amber-200";
    if (j.includes("distribusi") || j.includes("driver")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
    return "bg-slate-50 text-slate-700 border-slate-200";
}
</script>

<template>
    <Head title="Rekap Bonus Petugas SPPG" />

    <AppLayout>
        <div class="space-y-4 max-w-full pb-16">
            <!-- ─── HEADER UTAMA ─── -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 shadow-2xs">
                            <Coins class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                Rekap Bonus Petugas SPPG
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    Insentif Operasional
                                </span>
                            </h1>
                            <p class="text-xs text-slate-500">
                                Rekapitulasi hak bonus harian petugas SPPG berdasarkan total hari kerja operasional (mandays).
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="handleExportExcel"
                        :disabled="isExporting"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        <span>{{ isExporting ? 'Menyiapkan...' : 'Export Excel' }}</span>
                    </button>
                </div>
            </div>

            <!-- ─── KPI SUMMARY CARDS ─── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Card 1: Penerima Aktif -->
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-slate-100 text-slate-700">
                        <Users class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Penerima Aktif</p>
                        <p class="text-base font-bold text-slate-900 leading-tight">
                            {{ kpiStats.totalPenerima }} <span class="text-xs font-normal text-slate-500">orang</span>
                        </p>
                    </div>
                </div>

                <!-- Card 2: Total Mandays Terhitung -->
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-blue-50 text-blue-600">
                        <CalendarCheck class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Total Mandays</p>
                        <p class="text-base font-bold text-blue-900 leading-tight">
                            {{ kpiStats.totalMandays }} <span class="text-xs font-normal text-slate-500">hari kerja</span>
                        </p>
                    </div>
                </div>

                <!-- Card 3: Total Tarif Harian -->
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-amber-50 text-amber-600">
                        <Coins class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Tarif Harian Total</p>
                        <p class="text-base font-bold text-amber-900 leading-tight truncate">
                            {{ formatRupiah(kpiStats.totalBonusHarianRate) }}
                        </p>
                    </div>
                </div>

                <!-- Card 4: Grand Total Bonus Terhitung -->
                <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-emerald-600 text-white shadow-xs">
                        <DollarSign class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Total Hak Bonus</p>
                        <p class="text-lg font-black text-emerald-950 leading-tight truncate">
                            {{ formatRupiah(kpiStats.grandTotalBonus) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── BARIS FILTER PERIODE & PENCARIAN ─── -->
            <PeriodDateFilterBar
                :mode="currentMode"
                :periode-id="selectedPeriodeId"
                :tanggal-mulai="tanggalMulai"
                :tanggal-selesai="tanggalSelesai"
                :periodes="props.periodes"
                @filter-change="handleFilterChange"
            />

            <!-- Filter Search & Divisi -->
            <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-72">
                    <Search class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama atau NIK..."
                        class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-primary focus:border-primary"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Filter class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                    <select
                        v-model="filterJabatan"
                        class="py-1.5 px-2.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-primary focus:border-primary w-full sm:w-56"
                    >
                        <option value="all">Semua Divisi / Jabatan</option>
                        <option v-for="jab in props.daftarJabatan" :key="jab" :value="jab">
                            {{ jab }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- ─── TABEL REKAP BONUS ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/90 text-slate-700 border-b border-slate-200 font-bold">
                                <th class="py-2.5 px-3 text-center w-12 border-r border-slate-200">No</th>
                                <th class="py-2.5 px-3 min-w-[200px] border-r border-slate-200">Nama Petugas & Divisi</th>
                                <th class="py-2.5 px-3 text-right min-w-[120px] border-r border-slate-200">Bonus Harian</th>
                                <th class="py-2.5 px-2 text-center w-14 bg-emerald-50 text-emerald-800 border-r border-slate-200" title="Hadir Penuh (H)">H</th>
                                <th class="py-2.5 px-2 text-center w-14 bg-teal-50 text-teal-800 border-r border-slate-200" title="Hadir Setengah Hari (½)">½</th>
                                <th class="py-2.5 px-2 text-center min-w-[70px] bg-emerald-100/60 text-emerald-950 font-black border-r border-slate-200">Mandays</th>
                                <th class="py-2.5 px-3 text-right min-w-[140px] font-bold text-slate-800 border-r border-slate-200">Total Hak Bonus</th>
                                <th class="py-2.5 px-3 min-w-[160px] border-r border-slate-200">Rekening BNI</th>
                                <th class="py-2.5 px-2 text-center w-12">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(p, idx) in filteredPetugas"
                                :key="p.id"
                                :class="[
                                    'transition-colors',
                                    p.status === 'Nonaktif' ? 'bg-slate-50/70 opacity-60' : 'hover:bg-slate-50/80',
                                ]"
                            >
                                <td class="py-2.5 px-3 text-center font-medium text-slate-500 border-r border-slate-200">
                                    {{ idx + 1 }}
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    <p class="font-bold text-slate-900 leading-snug">{{ p.nama }}</p>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span :class="['px-1.5 py-0.5 rounded text-[9px] border leading-none font-medium', getJabatanBadgeClass(p.jabatan)]">
                                            {{ p.jabatan }}
                                        </span>
                                        <span v-if="p.status === 'Nonaktif'" class="px-1.5 py-0.5 rounded text-[9px] bg-slate-200 text-slate-600 font-semibold">
                                            Nonaktif
                                        </span>
                                    </div>
                                </td>

                                <td class="py-2.5 px-3 text-right font-medium text-amber-700 border-r border-slate-200">
                                    {{ formatRupiah(p.bonus_harian_mitra) }}
                                </td>

                                <td class="py-2.5 px-2 text-center font-bold text-emerald-800 bg-emerald-50/60 border-r border-slate-200">
                                    {{ computedBonusStats[p.id]?.hadirPenuh ?? 0 }}
                                </td>

                                <td class="py-2.5 px-2 text-center font-bold text-teal-800 bg-teal-50/60 border-r border-slate-200">
                                    {{ computedBonusStats[p.id]?.setengahHari ?? 0 }}
                                </td>

                                <td class="py-2.5 px-2 text-center font-black text-emerald-950 bg-emerald-100/50 border-r border-slate-200">
                                    {{ computedBonusStats[p.id]?.mandays ?? 0 }}
                                </td>

                                <td class="py-2.5 px-3 text-right font-bold text-emerald-900 border-r border-slate-200">
                                    {{ formatRupiah(computedBonusStats[p.id]?.totalBonus ?? 0) }}
                                </td>

                                <td class="py-2.5 px-3 text-slate-600 border-r border-slate-200 text-[11px]">
                                    <div v-if="p.nomor_rekening" class="font-mono">
                                        <span class="font-semibold text-slate-800">{{ p.nomor_rekening }}</span>
                                        <span class="text-[9px] text-slate-400 block">{{ p.jenis_bank || 'BNI' }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Belum ada</span>
                                </td>

                                <td class="py-2.5 px-2 text-center">
                                    <button
                                        type="button"
                                        @click="openDetailModal(p)"
                                        class="p-1 rounded-md text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
                                        title="Rincian Slip Bonus Petugas"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="filteredPetugas.length === 0">
                                <td colspan="9" class="py-10 text-center text-slate-500">
                                    <Users class="h-8 w-8 mx-auto text-slate-300 mb-2" />
                                    <p class="text-xs font-semibold">Tidak ada data petugas yang cocok dengan filter.</p>
                                </td>
                            </tr>
                        </tbody>

                        <tfoot class="bg-slate-100/90 text-slate-800 border-t-2 border-slate-300 font-bold">
                            <tr>
                                <td colspan="3" class="py-3 px-3 text-right uppercase tracking-wider text-xs border-r border-slate-300">
                                    Total Terhitung:
                                </td>
                                <td colspan="2" class="py-3 px-2 border-r border-slate-300"></td>
                                <td class="py-3 px-2 text-center text-emerald-950 bg-emerald-200/70 border-r border-slate-300 text-xs font-black">
                                    {{ kpiStats.totalMandays }}
                                </td>
                                <td class="py-3 px-3 text-right text-emerald-900 text-xs font-black border-r border-slate-300">
                                    {{ formatRupiah(kpiStats.grandTotalBonus) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                    <div>
                        Menampilkan <span class="font-bold text-slate-900">{{ filteredPetugas.length }}</span> personil • Rentang <span class="font-bold text-slate-900">{{ dateColumns.length }}</span> Hari Kerja
                    </div>
                </div>
            </div>

            <!-- ─── MODAL DETAIL SLIP BONUS ─── -->
            <Teleport to="body">
                <div
                    v-if="isDetailModalOpen && detailPetugas"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                >
                    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="px-5 py-4 bg-gradient-to-r from-amber-600 to-amber-700 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <Coins class="h-5 w-5 text-amber-200" />
                                <div>
                                    <h3 class="text-sm font-bold">Slip Rincian Hak Bonus Petugas</h3>
                                    <p class="text-[11px] text-amber-100">Akumulasi insentif mandays presensi</p>
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

                        <div class="p-5 space-y-4">
                            <!-- Profil Singkat -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-sm font-bold text-slate-900">{{ detailPetugas.nama }}</p>
                                <p class="text-xs text-slate-500 font-mono mt-0.5">NIK: {{ detailPetugas.nik }}</p>
                                <div class="flex items-center gap-1.5 mt-2">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-semibold border', getJabatanBadgeClass(detailPetugas.jabatan)]">
                                        {{ detailPetugas.jabatan }}
                                    </span>
                                    <span class="text-[10px] text-slate-500">
                                        Rekening: {{ detailPetugas.nomor_rekening || 'Belum diisi' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Komponen Bonus -->
                            <div class="p-3.5 rounded-xl border border-slate-200 space-y-2">
                                <p class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                    Rincian Perhitungan Bonus
                                </p>
                                <div class="flex justify-between text-xs text-slate-600 py-1 border-b border-slate-100">
                                    <span>Bonus Harian:</span>
                                    <span class="font-bold text-slate-900">{{ formatRupiah(detailPetugas.bonus_harian_mitra) }}</span>
                                </div>
                                <div class="flex justify-between text-xs text-slate-600 py-1 border-b border-slate-100">
                                    <span>Hadir Penuh (H):</span>
                                    <span class="font-bold text-slate-900">{{ computedBonusStats[detailPetugas.id]?.hadirPenuh ?? 0 }} hari</span>
                                </div>
                                <div class="flex justify-between text-xs text-slate-600 py-1 border-b border-slate-100">
                                    <span>Hadir ½ Hari (50%):</span>
                                    <span class="font-bold text-slate-900">{{ computedBonusStats[detailPetugas.id]?.setengahHari ?? 0 }} hari</span>
                                </div>
                                <div class="flex justify-between text-xs font-bold text-slate-800 pt-1">
                                    <span>Total Mandays Terhitung:</span>
                                    <span class="text-emerald-700">{{ computedBonusStats[detailPetugas.id]?.mandays ?? 0 }} mandays</span>
                                </div>
                            </div>

                            <!-- Total Bonus Diterima -->
                            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold text-amber-800 uppercase tracking-wider">Total Hak Bonus Diterima</p>
                                    <p class="text-[11px] text-amber-700 mt-0.5">
                                        {{ computedBonusStats[detailPetugas.id]?.mandays ?? 0 }} mandays x {{ formatRibuan(detailPetugas.bonus_harian_mitra) }}
                                    </p>
                                </div>
                                <p class="text-xl font-black text-amber-950">
                                    {{ formatRupiah(computedBonusStats[detailPetugas.id]?.totalBonus ?? 0) }}
                                </p>
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
