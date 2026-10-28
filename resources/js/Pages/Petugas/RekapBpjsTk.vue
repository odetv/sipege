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

// Filter Data Petugas
const filteredPetugas = computed(() => {
    return props.petugas.filter((p) => {
        const matchesSearch =
            !searchQuery.value ||
            p.nama.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            p.nik.includes(searchQuery.value);

        const matchesJabatan =
            filterJabatan.value === "all" ||
            p.jabatan === filterJabatan.value ||
            (p.jabatan && p.jabatan.toLowerCase().includes(filterJabatan.value.toLowerCase()));

        const isTerdaftar = (parseInt(p.iuran_bpjs_tk) || 0) > 0;
        let matchesStatus = true;
        if (filterBpjsStatus.value === "terdaftar") matchesStatus = isTerdaftar;
        else if (filterBpjsStatus.value === "belum") matchesStatus = !isTerdaftar;

        return matchesSearch && matchesJabatan && matchesStatus;
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

function getJabatanBadgeClass(jabatan) {
    const j = (jabatan || "").toLowerCase();
    if (j.includes("kepala") || j.includes("manager")) return "bg-purple-50 text-purple-700 border-purple-200";
    if (j.includes("gizi")) return "bg-emerald-50 text-emerald-700 border-emerald-200";
    if (j.includes("keuangan") || j.includes("akuntan")) return "bg-blue-50 text-blue-700 border-blue-200";
    if (j === "koordinator" || j.startsWith("koordinator ")) return "bg-sky-100 text-sky-800 border-sky-300 font-semibold";
    if (j.includes("masak") || j.includes("koki") || j.includes("chef")) return "bg-amber-50 text-amber-700 border-amber-200";
    if (j.includes("distribusi") || j.includes("driver")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
    return "bg-slate-50 text-slate-700 border-slate-200";
}
</script>

<template>
    <Head title="Rekap BPJS TK Petugas SPPG" />

    <AppLayout>
        <div class="space-y-4 max-w-full pb-16">
            <!-- ─── HEADER UTAMA ─── -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-lg bg-sky-50 text-sky-600 border border-sky-200 shadow-2xs">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                Rekap BPJS Ketenagakerjaan Petugas
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-300">
                                    JKK & JKM
                                </span>
                            </h1>
                            <p class="text-xs text-slate-500">
                                Rekapitulasi status kepesertaan dan iuran jaminan sosial BPJS TK (Kecelakaan Kerja & Kematian).
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="openBatchModal"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                    >
                        <Sliders class="h-4 w-4 text-slate-500" />
                        <span>Set Iuran Serentak</span>
                    </button>

                    <button
                        type="button"
                        @click="handleExportExcel"
                        :disabled="isExporting"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        <span>{{ isExporting ? 'Menyiapkan...' : 'Export Excel' }}</span>
                    </button>
                </div>
            </div>

            <!-- ─── KPI SUMMARY CARDS ─── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Card 1: Total Personil -->
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-slate-100 text-slate-700">
                        <Users class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Total Personil</p>
                        <p class="text-base font-bold text-slate-900 leading-tight">
                            {{ kpiStats.totalPetugas }} <span class="text-xs font-normal text-slate-500">({{ kpiStats.totalAktif }} aktif)</span>
                        </p>
                    </div>
                </div>

                <!-- Card 2: Terdaftar BPJS TK -->
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-600">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Terdaftar BPJS TK</p>
                        <p class="text-base font-bold text-emerald-900 leading-tight">
                            {{ kpiStats.terdaftar }} <span class="text-xs font-semibold text-emerald-700">({{ kpiStats.coverage }}%)</span>
                        </p>
                        <p class="text-[9.5px] text-slate-400 mt-0.5">{{ kpiStats.belum }} belum didaftarkan</p>
                    </div>
                </div>

                <!-- Card 3: Total Iuran Bulanan -->
                <div class="p-3.5 rounded-xl bg-sky-50/70 border border-sky-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-sky-600 text-white shadow-xs">
                        <DollarSign class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold text-sky-800 uppercase tracking-wider">Total Iuran / Bulan</p>
                        <p class="text-lg font-black text-sky-950 leading-tight truncate">
                            {{ formatRupiah(kpiStats.totalIuranBulan) }}
                        </p>
                        <p class="text-[9.5px] text-sky-700 mt-0.5">Akumulasi iuran bulanan aktif</p>
                    </div>
                </div>

                <!-- Card 4: Iuran Periodik (14 Hari) -->
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-indigo-50 text-indigo-600">
                        <HeartPulse class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Iuran Periodik (14 Hari)</p>
                        <p class="text-base font-bold text-indigo-900 leading-tight truncate">
                            {{ formatRupiah(kpiStats.totalIuranPeriodik) }}
                        </p>
                        <p class="text-[9.5px] text-slate-500 mt-0.5">Proporsional per siklus 2 minggu</p>
                    </div>
                </div>
            </div>

            <!-- ─── BARIS FILTER PENCARIAN ─── -->
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
                        class="py-1.5 px-2.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-primary focus:border-primary w-full sm:w-48"
                    >
                        <option value="all">Semua Divisi</option>
                        <option v-for="jab in props.daftarJabatan" :key="jab" :value="jab">
                            {{ jab }}
                        </option>
                    </select>

                    <select
                        v-model="filterBpjsStatus"
                        class="py-1.5 px-2.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-primary focus:border-primary w-full sm:w-44"
                    >
                        <option value="all">Status: Semua</option>
                        <option value="terdaftar">Terdaftar BPJS (> 0)</option>
                        <option value="belum">Belum Terdaftar (0)</option>
                    </select>
                </div>
            </div>

            <!-- ─── TABEL REKAP BPJS TK ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/90 text-slate-700 border-b border-slate-200 font-bold">
                                <th class="py-2.5 px-3 text-center w-12 border-r border-slate-200">No</th>
                                <th class="py-2.5 px-3 min-w-[200px] border-r border-slate-200">Nama Petugas & NIK</th>
                                <th class="py-2.5 px-2 text-center w-14 border-r border-slate-200">L/P</th>
                                <th class="py-2.5 px-3 min-w-[140px] border-r border-slate-200">Tgl Lahir / Usia</th>
                                <th class="py-2.5 px-3 min-w-[160px] border-r border-slate-200">Divisi / Jabatan</th>
                                <th class="py-2.5 px-2 text-center w-20 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 text-right min-w-[150px] border-r border-slate-200">Iuran BPJS TK / Bulan</th>
                                <th class="py-2.5 px-3 min-w-[150px] border-r border-slate-200">Cakupan Program</th>
                                <th class="py-2.5 px-2 text-center w-16">Aksi</th>
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
                                    <p class="text-[11px] text-slate-500 font-mono mt-0.5">NIK: {{ p.nik }}</p>
                                </td>

                                <td class="py-2.5 px-2 text-center font-semibold text-slate-700 border-r border-slate-200">
                                    {{ p.jenis_kelamin }}
                                </td>

                                <td class="py-2.5 px-3 text-slate-600 border-r border-slate-200 text-[11px]">
                                    <p class="font-medium text-slate-800">{{ formatTanggalIndo(p.tanggal_lahir) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">{{ p.umur ? `${p.umur} tahun` : '-' }}</p>
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="jab in (p.jabatan || '').split(/[,&/]/).map(s => s.trim()).filter(Boolean)"
                                            :key="jab"
                                            :class="['px-2 py-0.5 rounded text-[10px] border leading-none font-medium', getJabatanBadgeClass(jab)]"
                                        >
                                            {{ jab }}
                                        </span>
                                    </div>
                                </td>

                                <td class="py-2.5 px-2 text-center border-r border-slate-200">
                                    <span
                                        :class="[
                                            'px-1.5 py-0.5 rounded text-[10px] font-bold',
                                            p.status === 'Aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600',
                                        ]"
                                    >
                                        {{ p.status }}
                                    </span>
                                </td>

                                <td class="py-2.5 px-3 text-right font-bold border-r border-slate-200">
                                    <span :class="p.iuran_bpjs_tk > 0 ? 'text-sky-900' : 'text-slate-400 font-normal italic'">
                                        {{ formatRupiah(p.iuran_bpjs_tk) }}
                                    </span>
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    <div v-if="p.iuran_bpjs_tk > 0" class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sky-100 text-sky-800 border border-sky-200">
                                            <Check class="h-3 w-3" /> JKK & JKM
                                        </span>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400 italic">
                                        Belum didaftarkan
                                    </span>
                                </td>

                                <td class="py-2.5 px-2 text-center">
                                    <button
                                        type="button"
                                        @click="openEditModal(p)"
                                        class="p-1 rounded-md text-sky-600 hover:text-sky-900 hover:bg-sky-50 transition-colors cursor-pointer"
                                        title="Ubah Nominal Iuran BPJS TK"
                                    >
                                        <Edit3 class="h-4 w-4" />
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
                                <td colspan="6" class="py-3 px-3 text-right uppercase tracking-wider text-xs border-r border-slate-300">
                                    Total Iuran Premi Bulanan (Aktif):
                                </td>
                                <td class="py-3 px-3 text-right text-sky-900 text-xs font-black border-r border-slate-300">
                                    {{ formatRupiah(kpiStats.totalIuranBulan) }}
                                </td>
                                <td colspan="2" class="py-3 px-3 text-slate-500 font-normal text-[11px]">
                                    {{ kpiStats.terdaftar }} dari {{ kpiStats.totalPetugas }} personil terdaftar
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
                                    <p class="text-[11px] text-sky-100">{{ editingPetugas.nama }}</p>
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
