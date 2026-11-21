<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    ShieldCheck,
    Users,
    Search,
    CheckCircle2,
    Download,
    Eye,
    X,
    Filter,
    FileSpreadsheet,
    Building2,
    DollarSign,
    Edit3,
    AlertCircle,
    Check,
    HeartPulse,
    Sliders,
    Sparkles,
    Coins,
} from "lucide-vue-next";
import { downloadRekapBpjsTkExcel, formatTanggalIndo } from "@/Services/exportPetugasHelper";

const props = defineProps({
    petugas: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    daftarJabatan: { type: Array, default: () => [] },
    unitSppg: { type: Object, default: null },
});

const searchQuery = ref("");
const filterJabatan = ref("all");
const filterBpjsStatus = ref("all"); // 'all', 'terdaftar', 'belum'
const filterStatusPegawai = ref("all"); // 'all', 'Aktif', 'Nonaktif'
const isExporting = ref(false);

// Modal Edit Iuran Per Personil
const isEditModalOpen = ref(false);
const editingPetugas = ref(null);
const editIuranForm = ref({
    petugas_id: null,
    iuran_bpjs_tk: 0,
});
const isSavingEdit = ref(false);

// Modal Set Iuran Serentak
const isBatchModalOpen = ref(false);
const batchNominal = ref(16800);
const isSavingBatch = ref(false);

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

// Helper Parser Multi-Jabatan (Sinkron dengan Rekap Bonus)
function parseJabatanList(str) {
    if (!str || str === "-") return [];
    if (Array.isArray(str)) return str;
    const normalized = String(str)
        .replace(/\(a\.k\.a\.?\s*([^)]+)\)/gi, ", $1")
        .replace(/\ba\.k\.a\.?\s+/gi, ", ")
        .replace(/\((Koordinator[^)]*)\)/gi, ", $1")
        .replace(/\bKoordinator\s*[12]\b/gi, "Koordinator");
    const parts = normalized.split(/[,&/]/);
    return parts
        .map((s) => s.trim().replace(/^[\s()]+|[\s()]+$/g, ""))
        .filter(Boolean);
}

// Helper Filter Multi-Jabatan
function matchJabatan(petugasJabatan, filterVal) {
    if (!filterVal || filterVal === "all") return true;
    const list = parseJabatanList(petugasJabatan);
    return (
        list.some((j) => j.toLowerCase() === filterVal.toLowerCase()) ||
        (petugasJabatan || "").toLowerCase().includes(filterVal.toLowerCase())
    );
}

function getJabatanBadgeClass(jabatan) {
    if (!jabatan) return "bg-slate-100 text-slate-700 border-slate-200";
    const j = jabatan.toLowerCase();
    if (j.includes("kepala sppg")) return "bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold";
    if (j.includes("pengawas gizi") || j.includes("pengawas keuangan") || j.includes("plog") || j.includes("plok")) return "bg-teal-50 text-teal-700 border-teal-200 font-semibold";
    if (j.includes("lapangan")) return "bg-blue-50 text-blue-700 border-blue-200";
    if (j === "koordinator" || j.startsWith("koordinator ")) return "bg-sky-100 text-sky-800 border-sky-300 font-semibold";
    if (j.includes("chef") || j.includes("koki")) return "bg-amber-50 text-amber-700 border-amber-200";
    if (j.includes("pengolahan") || j.includes("masak")) return "bg-orange-50 text-orange-700 border-orange-200";
    if (j.includes("persiapan") || j.includes("potong")) return "bg-yellow-50 text-yellow-800 border-yellow-200";
    if (j.includes("pemorsian") || j.includes("packing") || j.includes("pengemasan")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
    if (j.includes("pengemudi") || j.includes("distribusi") || j.includes("driver") || j.includes("kurir")) return "bg-cyan-50 text-cyan-800 border-cyan-200";
    if (j.includes("cuci")) return "bg-teal-50 text-teal-700 border-teal-200";
    if (j.includes("admin") || j.includes("administrasi")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
    if (j.includes("kebersihan") || j.includes("cleaning")) return "bg-purple-50 text-purple-700 border-purple-200";
    if (j.includes("keamanan") || j.includes("security")) return "bg-rose-50 text-rose-700 border-rose-200";
    return "bg-slate-50 text-slate-700 border-slate-200";
}

// Filter Data Petugas
const filteredPetugas = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    return props.petugas.filter((p) => {
        const matchesSearch =
            !q ||
            (p.nama && p.nama.toLowerCase().includes(q)) ||
            (p.nik && p.nik.includes(q)) ||
            parseJabatanList(p.jabatan).some((j) => j.toLowerCase().includes(q)) ||
            (p.jabatan && p.jabatan.toLowerCase().includes(q));

        const matchesJabatan = matchJabatan(p.jabatan, filterJabatan.value);

        const isTerdaftar = (parseInt(p.iuran_bpjs_tk) || 0) > 0;
        let matchesStatus = true;
        if (filterBpjsStatus.value === "terdaftar") matchesStatus = isTerdaftar;
        else if (filterBpjsStatus.value === "belum") matchesStatus = !isTerdaftar;

        let matchesPegawai = true;
        if (filterStatusPegawai.value !== "all") {
            matchesPegawai = p.status === filterStatusPegawai.value;
        }

        return matchesSearch && matchesJabatan && matchesStatus && matchesPegawai;
    }).slice().sort((a, b) => {
        const aNon = a.status === 'Nonaktif' ? 1 : 0;
        const bNon = b.status === 'Nonaktif' ? 1 : 0;
        if (aNon !== bNon) return aNon - bNon;
        return (a.urutan ?? 0) - (b.urutan ?? 0) || (a.id ?? 0) - (b.id ?? 0);
    });
});

// KPI Stats
const kpiStats = computed(() => {
    const totalPetugas = props.petugas.length;
    const totalAktif = props.petugas.filter((p) => p.status === "Aktif").length;
    const terdaftar = props.petugas.filter((p) => (parseInt(p.iuran_bpjs_tk) || 0) > 0).length;
    const belum = totalPetugas - terdaftar;
    const coverage = totalPetugas > 0 ? Math.round((terdaftar / totalPetugas) * 100) : 0;

    let totalIuranBulan = 0;
    props.petugas.forEach((p) => {
        if (p.status === "Aktif") {
            totalIuranBulan += parseInt(p.iuran_bpjs_tk) || 0;
        }
    });

    const totalIuranPeriodik = Math.round(totalIuranBulan / 2);

    return {
        totalPetugas,
        totalAktif,
        terdaftar,
        belum,
        coverage,
        totalIuranBulan,
        totalIuranPeriodik,
    };
});

// Buka Modal Edit Per Orang
function openEditModal(p) {
    editingPetugas.value = p;
    editIuranForm.value = {
        petugas_id: p.id,
        iuran_bpjs_tk: p.iuran_bpjs_tk || 16800,
    };
    isEditModalOpen.value = true;
}

function closeEditModal() {
    isEditModalOpen.value = false;
    editingPetugas.value = null;
}

function submitEditIuran() {
    isSavingEdit.value = true;
    router.post(
        route("petugas.rekap-bpjs-tk.update-iuran"),
        {
            petugas_id: editIuranForm.value.petugas_id,
            iuran_bpjs_tk: editIuranForm.value.iuran_bpjs_tk,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
            },
            onFinish: () => {
                isSavingEdit.value = false;
            },
        }
    );
}

// Buka Modal Set Serentak
function openBatchModal() {
    batchNominal.value = 16800;
    isBatchModalOpen.value = true;
}

function closeBatchModal() {
    isBatchModalOpen.value = false;
}

function submitBatchIuran() {
    isSavingBatch.value = true;
    router.post(
        route("petugas.rekap-bpjs-tk.update-iuran"),
        {
            nominal_serentak: batchNominal.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeBatchModal();
            },
            onFinish: () => {
                isSavingBatch.value = false;
            },
        }
    );
}

// Export Excel
async function handleExportExcel() {
    isExporting.value = true;
    try {
        await downloadRekapBpjsTkExcel({
            petugas: props.petugas,
            unitSppg: props.unitSppg,
        });
    } catch (e) {
        console.error("Gagal export excel:", e);
        alert("Gagal mendownload file Excel.");
    } finally {
        isExporting.value = false;
    }
}
</script>

<template>
    <Head title="Rekap Asuransi - SIPEGE" />

    <AppLayout>
        <div class="space-y-3.5 max-w-full pb-16">
            <!-- ─── HEADER UTAMA (Style Rekap Bonus) ─── -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 sm:p-2.5 rounded-lg bg-sky-50 text-sky-600 border border-sky-200 shadow-2xs shrink-0">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2 flex-wrap">
                                Rekapitulasi Asuransi Petugas SPPG
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-300">
                                    BPJS TK (JKK & JKM)
                                </span>
                            </h1>
                            <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">
                                Status kepesertaan jaminan perlindungan kerja & rekapitulasi iuran bulanan / periodik personil SPPG
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start md:self-auto flex-wrap">
                    <button
                        type="button"
                        @click="openBatchModal"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                        title="Setel nominal iuran serentak untuk seluruh petugas aktif"
                    >
                        <Sliders class="h-3.5 w-3.5 text-slate-500" />
                        <span>Set Iuran Serentak</span>
                    </button>

                    <button
                        type="button"
                        @click="handleExportExcel"
                        :disabled="isExporting"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        title="Download Rekap Asuransi format Excel"
                    >
                        <FileSpreadsheet class="h-3.5 w-3.5" />
                        <span>{{ isExporting ? 'Menyiapkan...' : 'Export Excel' }}</span>
                    </button>
                </div>
            </div>

            <!-- ─── KPI SUMMARY CARDS (Style Rekap Bonus) ─── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                <!-- Card 1: Total Personil -->
                <div class="p-2.5 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Total Personil</p>
                        <Users class="h-3.5 w-3.5 text-slate-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-slate-900 mt-1">
                        {{ kpiStats.totalPetugas }} <span class="text-xs font-normal text-slate-500">orang</span>
                    </p>
                    <p class="text-[10px] text-slate-500 mt-0.5 truncate">
                        {{ kpiStats.totalAktif }} personil aktif operasional
                    </p>
                </div>

                <!-- Card 2: Terdaftar BPJS TK -->
                <div class="p-2.5 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Terdaftar Asuransi</p>
                        <ShieldCheck class="h-3.5 w-3.5 text-emerald-600" />
                    </div>
                    <p class="text-base sm:text-lg font-bold text-emerald-900 mt-1">
                        {{ kpiStats.terdaftar }} <span class="text-xs font-semibold text-emerald-700">({{ kpiStats.coverage }}%)</span>
                    </p>
                    <p class="text-[10px] text-slate-500 mt-0.5 truncate">
                        {{ kpiStats.belum }} personil belum didaftarkan
                    </p>
                </div>

                <!-- Card 3: Total Iuran Bulanan -->
                <div class="p-2.5 sm:p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">Iuran Premi / Bulan</p>
                        <DollarSign class="h-3.5 w-3.5 text-sky-600" />
                    </div>
                    <p class="text-xs sm:text-base font-bold text-sky-950 mt-1 truncate" :title="formatRupiah(kpiStats.totalIuranBulan)">
                        {{ formatRupiah(kpiStats.totalIuranBulan) }}
                    </p>
                    <p class="text-[10px] text-sky-700 font-semibold mt-0.5 truncate">
                        Total premi akumulasi bulanan
                    </p>
                </div>

                <!-- Card 4: Iuran Periodik (14 Hari) -->
                <div class="p-2.5 sm:p-3.5 rounded-xl bg-sky-50/70 border border-sky-300 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] sm:text-[11px] font-bold text-sky-800 uppercase tracking-wider">Iuran Periodik (14 Hari)</p>
                        <HeartPulse class="h-3.5 w-3.5 text-sky-700" />
                    </div>
                    <p class="text-xs sm:text-base font-black text-sky-950 mt-1 truncate" :title="formatRupiah(kpiStats.totalIuranPeriodik)">
                        {{ formatRupiah(kpiStats.totalIuranPeriodik) }}
                    </p>
                    <p class="text-[10px] text-sky-700 font-semibold mt-0.5 truncate">
                        Proporsional per siklus 2 minggu
                    </p>
                </div>
            </div>

            <!-- ─── BARIS FILTER PENCARIAN (Style Rekap Bonus) ─── -->
            <div class="p-3 sm:p-3.5 bg-white rounded-xl border border-slate-200 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-2.5 sm:gap-3">
                <!-- Search Box -->
                <div class="relative w-full md:w-64">
                    <Search class="h-3.5 w-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari personil, NIK, divisi / jabatan..."
                        class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-primary focus:border-primary outline-hidden bg-white shadow-2xs"
                    />
                </div>

                <!-- Dropdown Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full md:w-auto md:flex md:items-center">
                    <!-- Dropdown Filter Divisi / Jabatan -->
                    <select
                        v-model="filterJabatan"
                        class="w-full md:w-auto px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary outline-hidden bg-white shadow-2xs cursor-pointer"
                    >
                        <option value="all">Semua Divisi / Jabatan</option>
                        <option v-for="j in props.daftarJabatan" :key="j" :value="j">{{ j }}</option>
                    </select>

                    <!-- Dropdown Status Asuransi -->
                    <select
                        v-model="filterBpjsStatus"
                        class="w-full md:w-auto px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary outline-hidden bg-white shadow-2xs cursor-pointer"
                    >
                        <option value="all">Status Asuransi: Semua</option>
                        <option value="terdaftar">Terdaftar BPJS (> 0)</option>
                        <option value="belum">Belum Terdaftar (0)</option>
                    </select>

                    <!-- Dropdown Status Pegawai -->
                    <select
                        v-model="filterStatusPegawai"
                        class="w-full md:w-auto px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary outline-hidden bg-white shadow-2xs cursor-pointer"
                    >
                        <option value="all">Semua Status</option>
                        <option value="Aktif">Personil Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- ─── TABEL REKAP ASURANSI (Style Rekap Bonus) ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <!-- Mobile Swipe Hint -->
                <div class="sm:hidden px-3.5 py-1.5 bg-sky-50/70 border-b border-sky-100 flex items-center justify-between text-[11px] text-sky-800 font-medium">
                    <span>👉 Geser tabel ke kanan untuk melihat rincian iuran BPJS TK</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs min-w-[980px]">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 border-b border-slate-200 font-bold">
                                <!-- Col 1: No (Sticky Left) -->
                                <th class="py-2.5 px-2.5 w-10 text-center sticky left-0 bg-slate-100 z-30 border-r border-slate-200 font-bold">
                                    No
                                </th>
                                <!-- Col 2: Personil & Divisi (Sticky Left) -->
                                <th class="py-2.5 px-3 min-w-[200px] sm:min-w-[240px] sticky left-10 bg-slate-100 z-30 border-r border-slate-200 font-bold">
                                    Personil & Divisi
                                </th>
                                <!-- Col 3: L/P -->
                                <th class="py-2.5 px-2 text-center w-12 border-r border-slate-200 font-bold">
                                    L/P
                                </th>
                                <!-- Col 4: Tgl Lahir / Usia -->
                                <th class="py-2.5 px-3 min-w-[130px] border-r border-slate-200 font-bold">
                                    Tgl Lahir / Usia
                                </th>
                                <!-- Col 5: Status Asuransi -->
                                <th class="py-2.5 px-3 text-center min-w-[120px] border-r border-slate-200 font-bold">
                                    Status Asuransi
                                </th>
                                <!-- Col 6: Iuran / Bulan -->
                                <th class="py-2.5 px-3 text-right min-w-[140px] border-r border-slate-200 font-bold">
                                    Iuran / Bulan
                                </th>
                                <!-- Col 7: Iuran Periodik (14 Hari) -->
                                <th class="py-2.5 px-3 text-right min-w-[140px] border-r border-slate-200 font-bold">
                                    Iuran 14 Hari
                                </th>
                                <!-- Col 8: Cakupan Program -->
                                <th class="py-2.5 px-3 min-w-[140px] border-r border-slate-200 font-bold">
                                    Cakupan Program
                                </th>
                                <!-- Col 9: Aksi -->
                                <th class="py-2.5 px-2 text-center w-14 font-bold">
                                    Aksi
                                </th>
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
                                <!-- No (Sticky Left) -->
                                <td class="py-2.5 px-2.5 text-center font-medium text-slate-500 border-r border-slate-200 sticky left-0 bg-white z-10">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Personil & Divisi (Sticky Left - Style Rekap Bonus) -->
                                <td class="py-2.5 px-3 sticky left-10 bg-white z-10 border-r border-slate-200">
                                    <div class="min-w-0">
                                        <div class="flex items-center justify-between gap-1">
                                            <p class="font-bold text-slate-900 truncate leading-snug text-xs">
                                                {{ p.nama }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                                            <span
                                                v-for="jab in parseJabatanList(p.jabatan)"
                                                :key="jab"
                                                :class="['px-1.5 py-0.5 rounded text-[9px] border leading-none font-medium', getJabatanBadgeClass(jab)]"
                                            >
                                                {{ jab }}
                                            </span>
                                            <span
                                                v-if="p.status === 'Nonaktif'"
                                                class="px-1.5 py-0.5 rounded text-[9px] bg-slate-200 text-slate-600 font-semibold"
                                            >
                                                Nonaktif
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">NIK: {{ p.nik }}</p>
                                    </div>
                                </td>

                                <!-- L/P -->
                                <td class="py-2.5 px-2 text-center font-semibold text-slate-700 border-r border-slate-200">
                                    {{ p.jenis_kelamin || '-' }}
                                </td>

                                <!-- Tgl Lahir / Usia -->
                                <td class="py-2.5 px-3 text-slate-600 border-r border-slate-200 text-xs">
                                    <p class="font-medium text-slate-800">{{ formatTanggalIndo(p.tanggal_lahir) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">{{ p.umur ? `${p.umur} tahun` : '-' }}</p>
                                </td>

                                <!-- Status Asuransi -->
                                <td class="py-2.5 px-3 text-center border-r border-slate-200">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold inline-flex items-center gap-1 leading-tight',
                                            (parseInt(p.iuran_bpjs_tk) || 0) > 0
                                                ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                                                : 'bg-slate-100 text-slate-600 border border-slate-200',
                                        ]"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="(parseInt(p.iuran_bpjs_tk) || 0) > 0 ? 'bg-emerald-600' : 'bg-slate-400'"></span>
                                        {{ (parseInt(p.iuran_bpjs_tk) || 0) > 0 ? 'Terdaftar' : 'Belum' }}
                                    </span>
                                </td>

                                <!-- Iuran Bulanan -->
                                <td class="py-2.5 px-3 text-right font-bold border-r border-slate-200">
                                    <span :class="(parseInt(p.iuran_bpjs_tk) || 0) > 0 ? 'text-sky-950 font-mono' : 'text-slate-400 font-normal italic'">
                                        {{ formatRupiah(p.iuran_bpjs_tk) }}
                                    </span>
                                </td>

                                <!-- Iuran Periodik (14 Hari) -->
                                <td class="py-2.5 px-3 text-right font-bold border-r border-slate-200">
                                    <span :class="(parseInt(p.iuran_bpjs_tk) || 0) > 0 ? 'text-indigo-950 font-mono' : 'text-slate-400 font-normal italic'">
                                        {{ formatRupiah(Math.round((parseInt(p.iuran_bpjs_tk) || 0) / 2)) }}
                                    </span>
                                </td>

                                <!-- Cakupan Program -->
                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    <div v-if="(parseInt(p.iuran_bpjs_tk) || 0) > 0" class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sky-100 text-sky-800 border border-sky-200">
                                            <Check class="h-3 w-3" /> JKK & JKM
                                        </span>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400 italic">
                                        Belum didaftarkan
                                    </span>
                                </td>

                                <!-- Aksi Edit -->
                                <td class="py-2.5 px-2 text-center">
                                    <button
                                        type="button"
                                        @click="openEditModal(p)"
                                        class="p-1 rounded-md text-sky-600 hover:text-sky-900 hover:bg-sky-50 transition-colors cursor-pointer"
                                        title="Ubah Nominal Iuran BPJS TK"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
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

                        <!-- Footer Summary Row (Style Rekap Bonus) -->
                        <tfoot class="bg-slate-100 text-slate-800 border-t-2 border-slate-300 font-bold text-xs">
                            <tr>
                                <td colspan="5" class="py-3 px-3 text-right uppercase tracking-wider border-r border-slate-300 font-black">
                                    Total Iuran Premi Petugas Aktif:
                                </td>
                                <td class="py-3 px-3 text-right text-sky-900 font-black border-r border-slate-300 font-mono">
                                    {{ formatRupiah(kpiStats.totalIuranBulan) }}
                                </td>
                                <td class="py-3 px-3 text-right text-indigo-900 font-black border-r border-slate-300 font-mono">
                                    {{ formatRupiah(kpiStats.totalIuranPeriodik) }}
                                </td>
                                <td colspan="2" class="py-3 px-3 text-slate-600 font-normal text-[11px]">
                                    {{ kpiStats.terdaftar }} dari {{ kpiStats.totalPetugas }} personil terdaftar ({{ kpiStats.coverage }}%)
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                    <div>
                        Menampilkan <span class="font-bold text-slate-900">{{ filteredPetugas.length }}</span> personil SPPG
                    </div>
                </div>
            </div>

            <!-- ─── MODAL EDIT IURAN PER PERSONIL ─── -->
            <Teleport to="body">
                <div
                    v-if="isEditModalOpen && editingPetugas"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                >
                    <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="px-5 py-4 bg-gradient-to-r from-sky-600 to-sky-700 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <ShieldCheck class="h-5 w-5 text-sky-200" />
                                <div>
                                    <h3 class="text-sm font-bold">Ubah Iuran BPJS TK</h3>
                                    <p class="text-[11px] text-sky-100 flex items-center gap-1.5 flex-wrap">
                                        <span class="font-bold">{{ editingPetugas.nama }}</span>
                                        <span v-if="parseJabatanList(editingPetugas.jabatan).length > 0" class="text-[10px] text-sky-200">
                                            &bull; {{ parseJabatanList(editingPetugas.jabatan).join(', ') }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="p-1 rounded-lg text-sky-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <form @submit.prevent="submitEditIuran" class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Besaran Iuran BPJS TK per Bulan (Rp)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input
                                        v-model.number="editIuranForm.iuran_bpjs_tk"
                                        type="number"
                                        min="0"
                                        step="100"
                                        required
                                        placeholder="16800"
                                        class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-xs font-mono font-semibold focus:ring-1 focus:ring-sky-500 focus:border-sky-500"
                                    />
                                </div>
                                <p class="text-[10px] text-slate-500 mt-1">
                                    Standar premi JKK & JKM adalah Rp 16.800 / bulan.
                                </p>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="closeEditModal"
                                    class="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSavingEdit"
                                    class="px-4 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                                >
                                    {{ isSavingEdit ? 'Menyimpan...' : 'Simpan Iuran' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- ─── MODAL SET IURAN SERENTAK ─── -->
            <Teleport to="body">
                <div
                    v-if="isBatchModalOpen"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                >
                    <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="px-5 py-4 bg-gradient-to-r from-sky-600 to-indigo-700 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Sliders class="h-5 w-5 text-sky-200" />
                                <div>
                                    <h3 class="text-sm font-bold">Atur Iuran BPJS TK Serentak</h3>
                                    <p class="text-[11px] text-sky-100">Setel premi ke seluruh petugas aktif</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeBatchModal"
                                class="p-1 rounded-lg text-sky-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <form @submit.prevent="submitBatchIuran" class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Nominal Iuran Serentak (Rp / Bulan)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input
                                        v-model.number="batchNominal"
                                        type="number"
                                        min="0"
                                        step="100"
                                        required
                                        placeholder="16800"
                                        class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-xs font-mono font-semibold focus:ring-1 focus:ring-sky-500 focus:border-sky-500"
                                    />
                                </div>
                                <div class="mt-2.5 p-2.5 rounded-lg bg-sky-50 border border-sky-200 text-[11px] text-sky-800">
                                    Seluruh petugas dengan status <strong>Aktif</strong> ({{ kpiStats.totalAktif }} orang) akan disetel ke nominal Rp {{ formatRibuan(batchNominal) }}.
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="closeBatchModal"
                                    class="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSavingBatch"
                                    class="px-4 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                                >
                                    {{ isSavingBatch ? 'Menerapkan...' : 'Terapkan Serentak' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>
