<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    DatabaseBackup,
    Database,
    ShieldCheck,
    Archive,
    Clock,
    CalendarClock,
    Plus,
    RotateCcw,
    Download,
    Trash2,
    Search,
    Filter,
    HardDrive,
    Cloud,
    CloudDownload,
    UploadCloud,
    FileCode2,
    FileArchive,
    CheckCircle2,
    AlertTriangle,
    Info,
    Settings2,
    Lock,
    FileCheck,
    RefreshCw,
    X,
    Eye,
} from "lucide-vue-next";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    backupHistory: {
        type: Array,
        default: () => [],
    },
});

// Active Tab State
const activeTab = ref("riwayat"); // 'riwayat' | 'buat' | 'restore' | 'jadwal'

// Filter & Search State
const searchQuery = ref("");
const filterTipe = ref("semua");

// Form Buat Cadangan Baru (State Preview)
const formBackup = ref({
    lingkup: "full", // 'full' | 'database' | 'media'
    format_kompresi: "zip", // 'zip' | 'gzip'
    gunakan_enkripsi: false,
    kata_sandi: "",
    sertakan_catatan: true,
    catatan: "",
});

// Form Konfigurasi Jadwal Otomatis (State Preview)
const formJadwal = ref({
    auto_backup: true,
    frekuensi: "harian", // 'harian' | 'mingguan' | 'bulanan'
    jam_eksekusi: "01:00",
    retensi_hari: 14,
    simpan_cloud: true,
    tipe_default: "full",
});

// Modal States
const isCreateModalOpen = ref(false);
const isRestoreModalOpen = ref(false);
const selectedBackupForRestore = ref(null);
const restoreConfirmationInput = ref("");
const isNotificationBannerVisible = ref(true);

// Filtered History
const filteredHistory = computed(() => {
    let list = props.backupHistory || [];

    if (filterTipe.value !== "semua") {
        list = list.filter((item) => {
            if (filterTipe.value === "database") return item.tipe.toLowerCase().includes("database");
            if (filterTipe.value === "media") return item.tipe.toLowerCase().includes("media");
            if (filterTipe.value === "full") return item.tipe.toLowerCase().includes("full");
            return true;
        });
    }

    if (searchQuery.value.trim() !== "") {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (item) =>
                item.filename.toLowerCase().includes(q) ||
                item.dibuat_oleh.toLowerCase().includes(q) ||
                (item.keterangan && item.keterangan.toLowerCase().includes(q))
        );
    }

    return list;
});

function openRestoreModal(item) {
    selectedBackupForRestore.value = item;
    restoreConfirmationInput.value = "";
    isRestoreModalOpen.value = true;
}

function closeRestoreModal() {
    isRestoreModalOpen.value = false;
    selectedBackupForRestore.value = null;
    restoreConfirmationInput.value = "";
}

function handleMockAction(pesan) {
    alert(`[Mode Pratinjau Layout UI]\n${pesan}\n\nFitur ini saat ini hanya berupa tampilan antarmuka dan belum difungsikan/dieksekusi ke server.`);
}
</script>

<template>
    <Head title="Pengaturan Backup & Restore Sistem" />

    <AppLayout>
        <div class="space-y-4 max-w-full pb-16">
            <!-- ─── BANNER NOTIFIKASI PRATINJAU LAYOUT UI ─── -->
            <div
                v-if="isNotificationBannerVisible"
                class="p-3.5 rounded-xl bg-gradient-to-r from-amber-50 via-amber-50/80 to-white border border-amber-200 shadow-2xs flex items-start justify-between gap-3 text-amber-900"
            >
                <div class="flex items-start gap-2.5">
                    <div class="p-1.5 rounded-lg bg-amber-100 text-amber-700 shrink-0 mt-0.5">
                        <Info class="h-4 w-4" />
                    </div>
                    <div class="text-xs">
                        <p class="font-bold text-amber-950">
                            Pratinjau Antarmuka Pengguna (Layout UI Saja)
                        </p>
                        <p class="text-amber-800/90 mt-0.5">
                            Halaman <strong>Backup & Restore</strong> ini telah dirancang lengkap dengan seluruh komponen visual, formulir wizard, tab riwayat, dan modal konfirmasi. Sesuai instruksi, fungsi eksekusi backend belum diaktifkan.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="isNotificationBannerVisible = false"
                    class="p-1 rounded-lg text-amber-600 hover:bg-amber-100 hover:text-amber-800 transition-colors"
                    title="Tutup pemberitahuan"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- ─── HEADER UTAMA ─── -->
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="p-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-200 shadow-2xs"
                        >
                            <DatabaseBackup class="h-5 w-5" />
                        </div>
                        <div>
                            <h1
                                class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2"
                            >
                                Backup & Pemulihan Sistem (Backup & Restore)
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-300"
                                >
                                    Keamanan Data SPPG
                                </span>
                            </h1>
                            <p class="text-xs text-slate-500">
                                Pencadangan dan pemulihan arsip basis data, aset media, dan integritas data operasional SIPEGE.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="activeTab = 'restore'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                        title="Buka Wizard Pemulihan Data"
                    >
                        <UploadCloud class="h-3.5 w-3.5 text-slate-500" />
                        <span>Pulihkan Data (Restore)</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'buat'"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-2xs transition-colors cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Buat Cadangan Baru</span>
                    </button>
                </div>
            </div>

            <!-- ─── KPI SUMMARY CARDS ─── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Card 1: Status Database -->
                <div
                    class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3"
                >
                    <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">
                            Status Basis Data
                        </p>
                        <p class="text-base font-bold text-slate-900 leading-tight">
                            Optimal & Sehat
                        </p>
                        <p class="text-[10.5px] text-slate-500 mt-0.5 truncate">
                            {{ summary.db_driver ?? 'MYSQL' }} • {{ summary.total_tabel ?? 38 }} Tabel Aktif
                        </p>
                    </div>
                </div>

                <!-- Card 2: Cadangan Terakhir -->
                <div
                    class="p-3.5 rounded-xl bg-indigo-50/60 border border-indigo-200 shadow-2xs flex items-center gap-3"
                >
                    <div class="p-2.5 rounded-lg bg-indigo-600 text-white shadow-xs">
                        <Clock class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">
                            Cadangan Terakhir
                        </p>
                        <p class="text-base font-bold text-indigo-950 leading-tight">
                            20 Okt 2026
                        </p>
                        <p class="text-[10.5px] text-indigo-800/80 font-medium mt-0.5 truncate">
                            Pukul 01:00 WITA (42.8 MB)
                        </p>
                    </div>
                </div>

                <!-- Card 3: Total Arsip Tersimpan -->
                <div
                    class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3"
                >
                    <div class="p-2.5 rounded-lg bg-teal-50 text-teal-600 border border-teal-100">
                        <Archive class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">
                            Total Berkas Arsip
                        </p>
                        <p class="text-base font-bold text-slate-900 leading-tight">
                            {{ summary.total_cadangan ?? 4 }} <span class="text-xs font-normal text-slate-500">berkas</span>
                        </p>
                        <p class="text-[10.5px] text-slate-500 mt-0.5 truncate">
                            Total Ukuran: {{ summary.total_ukuran ?? '95.9 MB' }}
                        </p>
                    </div>
                </div>

                <!-- Card 4: Jadwal Otomatis -->
                <div
                    class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3"
                >
                    <div class="p-2.5 rounded-lg bg-amber-50 text-amber-600 border border-amber-100">
                        <CalendarClock class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">
                            Cadangan Terjadwal
                        </p>
                        <p class="text-base font-bold text-slate-900 leading-tight">
                            Aktif (Harian)
                        </p>
                        <p class="text-[10.5px] text-slate-500 mt-0.5 truncate">
                            Pukul 01:00 WITA • Retensi 14 Hari
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── TAB NAVIGASI UTAMA ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-1">
                <div class="flex flex-wrap items-center gap-1">
                    <button
                        type="button"
                        @click="activeTab = 'riwayat'"
                        :class="[
                            'flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            activeTab === 'riwayat'
                                ? 'bg-indigo-50 text-indigo-700 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50',
                        ]"
                    >
                        <Archive class="h-4 w-4" />
                        <span>Riwayat Arsip Cadangan</span>
                        <span
                            class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                            :class="activeTab === 'riwayat' ? 'bg-indigo-200 text-indigo-800' : 'bg-slate-100 text-slate-600'"
                        >
                            {{ backupHistory.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'buat'"
                        :class="[
                            'flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            activeTab === 'buat'
                                ? 'bg-indigo-50 text-indigo-700 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50',
                        ]"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Buat Cadangan Manual</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'restore'"
                        :class="[
                            'flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            activeTab === 'restore'
                                ? 'bg-indigo-50 text-indigo-700 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50',
                        ]"
                    >
                        <RotateCcw class="h-4 w-4" />
                        <span>Pemulihan Data (Restore)</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'jadwal'"
                        :class="[
                            'flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            activeTab === 'jadwal'
                                ? 'bg-indigo-50 text-indigo-700 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50',
                        ]"
                    >
                        <Settings2 class="h-4 w-4" />
                        <span>Konfigurasi & Jadwal Otomatis</span>
                    </button>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════ -->
            <!-- TAB 1: RIWAYAT ARSIP CADANGAN (TABLE VIEW) -->
            <!-- ════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'riwayat'" class="space-y-3">
                <!-- Toolbar Pencarian & Filter -->
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 flex-1 max-w-xl">
                        <!-- Search Input -->
                        <div class="relative flex-1 min-w-[200px]">
                            <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Cari nama berkas cadangan, keterangan, atau pembuat..."
                                class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <!-- Tipe Filter Dropdown -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <Filter class="h-3.5 w-3.5 text-slate-400" />
                            <select
                                v-model="filterTipe"
                                class="py-1.5 px-2.5 rounded-lg border border-slate-300 text-xs text-slate-700 font-medium focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer"
                            >
                                <option value="semua">Semua Jenis Cadangan</option>
                                <option value="full">Full Backup (DB + Media)</option>
                                <option value="database">Database SQL</option>
                                <option value="media">Media & Berkas Lampiran</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            @click="handleMockAction('Memperbarui daftar arsip cadangan...')"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold transition-colors cursor-pointer"
                        >
                            <RefreshCw class="h-3 w-3" />
                            <span>Segarkan</span>
                        </button>
                    </div>
                </div>

                <!-- Tabel Riwayat Berkas Cadangan -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                                    <th class="py-3 px-3.5 w-12 text-center">#</th>
                                    <th class="py-3 px-3 min-w-[260px]">Berkas Cadangan</th>
                                    <th class="py-3 px-3 w-32">Tipe Cadangan</th>
                                    <th class="py-3 px-3 w-28">Ukuran</th>
                                    <th class="py-3 px-3 min-w-[150px]">Waktu Pencadangan</th>
                                    <th class="py-3 px-3 min-w-[150px]">Dibuat Oleh</th>
                                    <th class="py-3 px-3 w-28 text-center">Status</th>
                                    <th class="py-3 px-3.5 w-36 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(item, idx) in filteredHistory"
                                    :key="item.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td class="py-3 px-3.5 text-center font-bold text-slate-500">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-start gap-2">
                                            <div class="p-1.5 rounded-md bg-slate-100 text-slate-600 shrink-0 mt-0.5">
                                                <FileArchive v-if="item.filename.endsWith('.zip') || item.filename.endsWith('.tar.gz')" class="h-4 w-4 text-indigo-600" />
                                                <FileCode2 v-else class="h-4 w-4 text-emerald-600" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-900 font-mono text-[11.5px] truncate" :title="item.filename">
                                                    {{ item.filename }}
                                                </p>
                                                <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                                    {{ item.keterangan || 'Cadangan berkas sistem SIPEGE.' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold"
                                            :class="[
                                                item.tipe_badge === 'Full Backup'
                                                    ? 'bg-indigo-50 text-indigo-700 border border-indigo-200'
                                                    : item.tipe_badge === 'Database'
                                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                        : 'bg-teal-50 text-teal-700 border border-teal-200'
                                            ]"
                                        >
                                            {{ item.tipe_badge }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-mono font-bold text-slate-700">
                                        {{ item.ukuran }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-600">
                                        {{ item.created_at }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 font-medium">
                                        {{ item.dibuat_oleh }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <CheckCircle2 class="h-3 w-3 text-emerald-600" />
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Download -->
                                            <button
                                                type="button"
                                                @click="handleMockAction(`Mengunduh berkas: ${item.filename}`)"
                                                class="p-1.5 rounded-md text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                                                title="Unduh Berkas Cadangan Ini"
                                            >
                                                <Download class="h-3.5 w-3.5" />
                                            </button>

                                            <!-- Restore -->
                                            <button
                                                type="button"
                                                @click="openRestoreModal(item)"
                                                class="p-1.5 rounded-md text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition-colors cursor-pointer"
                                                title="Pulihkan Basis Data dari Berkas Ini"
                                            >
                                                <RotateCcw class="h-3.5 w-3.5" />
                                            </button>

                                            <!-- Delete -->
                                            <button
                                                type="button"
                                                @click="handleMockAction(`Menghapus berkas arsip: ${item.filename}`)"
                                                class="p-1.5 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                title="Hapus Berkas Cadangan"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredHistory.length === 0">
                                    <td colspan="8" class="py-12 text-center text-slate-500">
                                        <Archive class="h-8 w-8 mx-auto text-slate-300 mb-2" />
                                        <p class="font-bold text-slate-700">Tidak ada berkas arsip yang cocok.</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Silakan sesuaikan kata kunci pencarian atau filter tipe cadangan.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════ -->
            <!-- TAB 2: BUAT CADANGAN BARU (NEW BACKUP WIZARD) -->
            <!-- ════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'buat'" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Kolom Kiri: Form Wizard Konfigurasi Backup -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs space-y-5">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <Plus class="h-4 w-4 text-indigo-600" />
                                Formulir Pembuatan Cadangan Data Manual
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tentukan lingkup data yang ingin dicadangkan dan kompresi arsip sistem.
                            </p>
                        </div>

                        <!-- 1. Lingkup Cadangan -->
                        <div class="space-y-2.5">
                            <label class="block text-xs font-bold text-slate-800">
                                1. Pilih Lingkup Cadangan Data <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Option Full -->
                                <label
                                    class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                    :class="[
                                        formBackup.lingkup === 'full'
                                            ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20'
                                            : 'border-slate-200 hover:border-slate-300 bg-white'
                                    ]"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <Archive class="h-4 w-4 text-indigo-600" />
                                            <input
                                                type="radio"
                                                value="full"
                                                v-model="formBackup.lingkup"
                                                class="text-indigo-600 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <p class="text-xs font-bold text-slate-900 mt-2">Full System Snapshot</p>
                                        <p class="text-[11px] text-slate-500 leading-tight">
                                            Basis data SQL lengkap beserta seluruh folder aset media & kop dokumen.
                                        </p>
                                    </div>
                                    <span class="mt-3 text-[10px] font-bold text-indigo-700 bg-indigo-100/70 px-2 py-0.5 rounded w-max">
                                        Rekomendasi
                                    </span>
                                </label>

                                <!-- Option DB Only -->
                                <label
                                    class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                    :class="[
                                        formBackup.lingkup === 'database'
                                            ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20'
                                            : 'border-slate-200 hover:border-slate-300 bg-white'
                                    ]"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <Database class="h-4 w-4 text-emerald-600" />
                                            <input
                                                type="radio"
                                                value="database"
                                                v-model="formBackup.lingkup"
                                                class="text-indigo-600 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <p class="text-xs font-bold text-slate-900 mt-2">Hanya Basis Data</p>
                                        <p class="text-[11px] text-slate-500 leading-tight">
                                            Dump seluruh tabel database (master data, WO, menu gizi, transaksi gaji).
                                        </p>
                                    </div>
                                    <span class="mt-3 text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded w-max">
                                        Cepat & Ringan
                                    </span>
                                </label>

                                <!-- Option Media Only -->
                                <label
                                    class="p-3.5 rounded-xl border cursor-pointer transition-all flex flex-col justify-between"
                                    :class="[
                                        formBackup.lingkup === 'media'
                                            ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20'
                                            : 'border-slate-200 hover:border-slate-300 bg-white'
                                    ]"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <HardDrive class="h-4 w-4 text-teal-600" />
                                            <input
                                                type="radio"
                                                value="media"
                                                v-model="formBackup.lingkup"
                                                class="text-indigo-600 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <p class="text-xs font-bold text-slate-900 mt-2">Aset Media & Berkas</p>
                                        <p class="text-[11px] text-slate-500 leading-tight">
                                            Foto laporan harian, tanda tangan digital, aset kop, dan berkas ekspor.
                                        </p>
                                    </div>
                                    <span class="mt-3 text-[10px] font-bold text-teal-700 bg-teal-100/70 px-2 py-0.5 rounded w-max">
                                        Berkas Media
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- 2. Format Kompresi & Opsi Keamanan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-800">
                                    Format Kompresi Arsip
                                </label>
                                <select
                                    v-model="formBackup.format_kompresi"
                                    class="w-full text-xs rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                >
                                    <option value="zip">ZIP Archive (.zip) - Standar Portabel</option>
                                    <option value="gzip">GZIP Compressed (.gz / .sql.gz) - Kompresi Tinggi</option>
                                    <option value="tar">TAR GZ (.tar.gz) - Standar Linux</option>
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <Lock class="h-3.5 w-3.5 text-slate-400" />
                                        Enkripsi Password (Opsional)
                                    </label>
                                    <input
                                        type="checkbox"
                                        v-model="formBackup.gunakan_enkripsi"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </div>
                                <input
                                    type="password"
                                    v-model="formBackup.kata_sandi"
                                    :disabled="!formBackup.gunakan_enkripsi"
                                    placeholder="Masukkan password proteksi arsip..."
                                    class="w-full text-xs rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:bg-slate-100 disabled:text-slate-400"
                                />
                            </div>
                        </div>

                        <!-- 3. Catatan Cadangan -->
                        <div class="space-y-1.5 pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-800">
                                Catatan / Deskripsi Cadangan
                            </label>
                            <textarea
                                v-model="formBackup.catatan"
                                rows="3"
                                placeholder="Contoh: Pencadangan manual sebelum pergantian periode anggaran atau pembaruan menu bulanan..."
                                class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            ></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                @click="activeTab = 'riwayat'"
                                class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="handleMockAction(`Memulai proses pencadangan lingkup '${formBackup.lingkup.toUpperCase()}' format '${formBackup.format_kompresi}'...`)"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-2xs transition-colors cursor-pointer"
                            >
                                <DatabaseBackup class="h-4 w-4" />
                                <span>Mulai Buat Cadangan Sekarang</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Panduan & Informasi Teknis -->
                <div class="space-y-4">
                    <div class="bg-gradient-to-br from-indigo-50/80 to-slate-50 p-4 rounded-xl border border-indigo-100 shadow-2xs space-y-3">
                        <h3 class="text-xs font-bold text-indigo-950 uppercase tracking-wider flex items-center gap-1.5">
                            <Info class="h-3.5 w-3.5 text-indigo-600" />
                            Panduan Praktik Terbaik Backup
                        </h3>
                        <ul class="text-xs text-slate-600 space-y-2">
                            <li class="flex items-start gap-1.5">
                                <span class="text-indigo-600 font-bold">•</span>
                                <span>Lakukan pencadangan sebelum melakukan perubahan besar pada <strong>data penerima manfaat</strong> atau <strong>verifikasi payroll gaji</strong>.</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="text-indigo-600 font-bold">•</span>
                                <span>Simpan salinan berkas cadangan di media penyimpanan eksternal (Flashdisk, Google Drive) secara berkala.</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="text-indigo-600 font-bold">•</span>
                                <span>Fitur jadwal otomatis akan otomatis mengarsipkan database setiap malam tanpa mengganggu operasional dapur.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs space-y-2.5 text-xs">
                        <p class="font-bold text-slate-900">Spesifikasi Lingkungan Server:</p>
                        <div class="space-y-1.5 text-slate-600">
                            <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                <span>Database Engine:</span>
                                <span class="font-mono font-bold text-slate-800">{{ summary.db_driver ?? 'MYSQL' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                <span>Nama Database:</span>
                                <span class="font-mono font-bold text-slate-800">{{ summary.db_name ?? 'sipege_db' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                <span>Total Tabel Terpetakan:</span>
                                <span class="font-mono font-bold text-slate-800">{{ summary.total_tabel ?? 38 }} Tabel</span>
                            </div>
                            <div class="flex items-center justify-between py-1">
                                <span>Status Storage Backup:</span>
                                <span class="font-bold text-emerald-600">Tersedia (Ready)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════ -->
            <!-- TAB 3: PEMULIHAN DATA (RESTORE WIZARD) -->
            <!-- ════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'restore'" class="space-y-4">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs space-y-5">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <RotateCcw class="h-4 w-4 text-amber-600" />
                            Wizard Pemulihan Data (Restore Database & Media)
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Kembalikan data sistem dari berkas cadangan arsip yang sebelumnya telah dibuat.
                        </p>
                    </div>

                    <!-- Callout Peringatan Penting -->
                    <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs flex items-start gap-2.5">
                        <AlertTriangle class="h-4 w-4 text-rose-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="font-bold text-rose-950">PERINGATAN RISIKO PEMULIHAN DATA:</p>
                            <p class="text-rose-800/90 mt-0.5 leading-relaxed">
                                Tindakan pemulihan (restore) akan <strong>menggantikan data aktif saat ini</strong> dengan seluruh data yang ada di dalam berkas cadangan. Data transaksi baru yang dibuat setelah tanggal cadangan dapat terhapus. Sangat disarankan untuk membuat cadangan baru terlebih dahulu sebelum melanjutkan.
                            </p>
                        </div>
                    </div>

                    <!-- Drag & Drop Upload Zone -->
                    <div
                        class="border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-2xl p-8 text-center transition-all bg-slate-50/50 hover:bg-indigo-50/20 cursor-pointer"
                        @click="handleMockAction('Memilih berkas arsip lokal untuk diunggah...')"
                    >
                        <div class="max-w-md mx-auto space-y-2.5">
                            <div class="h-12 w-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center mx-auto shadow-2xs">
                                <UploadCloud class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">
                                    Klik untuk memilih berkas atau seret berkas arsip ke sini
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    Mendukung format: <span class="font-mono font-bold text-slate-700">.sql</span>, <span class="font-mono font-bold text-slate-700">.sql.gz</span>, <span class="font-mono font-bold text-slate-700">.zip</span>, <span class="font-mono font-bold text-slate-700">.tar.gz</span> (Maksimal 200 MB)
                                </p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-slate-300 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors"
                            >
                                <FolderKanban class="h-3.5 w-3.5 text-slate-500" />
                                <span>Jelajahi Berkas Komputer</span>
                            </button>
                        </div>
                    </div>

                    <!-- Opsi Mode Pemulihan -->
                    <div class="space-y-3 pt-2 border-t border-slate-100 text-xs">
                        <label class="block font-bold text-slate-800">
                            Pilihan Metode Pemulihan:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="p-3 rounded-xl border border-slate-200 bg-white hover:border-indigo-300 transition-colors cursor-pointer flex items-start gap-2.5">
                                <input
                                    type="radio"
                                    name="mode_restore"
                                    checked
                                    class="text-indigo-600 focus:ring-indigo-500 mt-0.5"
                                />
                                <div>
                                    <p class="font-bold text-slate-900">Uji Validasi Skema Dahulu (Dry Run)</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Memverifikasi integritas berkas cadangan tanpa langsung menimpa data aktif.
                                    </p>
                                </div>
                            </label>

                            <label class="p-3 rounded-xl border border-slate-200 bg-white hover:border-indigo-300 transition-colors cursor-pointer flex items-start gap-2.5">
                                <input
                                    type="radio"
                                    name="mode_restore"
                                    class="text-indigo-600 focus:ring-indigo-500 mt-0.5"
                                />
                                <div>
                                    <p class="font-bold text-slate-900">Pemulihan Penuh Langsung (Direct Restore)</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Mengeksekusi query database dan menimpa record saat ini secara permanen.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="activeTab = 'riwayat'"
                            class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="handleMockAction('Memulai validasi dan verifikasi berkas cadangan sebelum pemulihan...')"
                            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-2xs transition-colors cursor-pointer"
                        >
                            <FileCheck class="h-4 w-4" />
                            <span>Verifikasi & Pulihkan Data</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════ -->
            <!-- TAB 4: KONFIGURASI JADWAL OTOMATIS (AUTO-BACKUP) -->
            <!-- ════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'jadwal'" class="space-y-4">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs space-y-5">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <Settings2 class="h-4 w-4 text-indigo-600" />
                            Pengaturan Jadwal Pencadangan Otomatis (Cron Job)
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Konfigurasi waktu eksekusi otomatis oleh server untuk mencadangkan database secara berkala.
                        </p>
                    </div>

                    <!-- Toggle Status Auto-Backup -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold text-slate-900">Aktifkan Pencadangan Otomatis Server</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Server akan menjalankan perintah background otomatis untuk membuat arsip cadangan.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="formJadwal.auto_backup"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Detail Pengaturan Jadwal -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-800">Frekuensi Pencadangan</label>
                            <select
                                v-model="formJadwal.frekuensi"
                                :disabled="!formJadwal.auto_backup"
                                class="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:bg-slate-100 disabled:text-slate-400"
                            >
                                <option value="harian">Setiap Hari (Harian)</option>
                                <option value="mingguan">Setiap Minggu (Mingguan)</option>
                                <option value="bulanan">Setiap Bulan (Bulanan)</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-800">Jam Eksekusi Otomatis</label>
                            <input
                                type="time"
                                v-model="formJadwal.jam_eksekusi"
                                :disabled="!formJadwal.auto_backup"
                                class="w-full rounded-lg border border-slate-300 p-2 text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:bg-slate-100 disabled:text-slate-400"
                            />
                            <p class="text-[10px] text-slate-400">Direkomendasikan dini hari (00:00 - 03:00 WITA).</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-800">Retensi Penyimpanan (Hari)</label>
                            <select
                                v-model="formJadwal.retensi_hari"
                                :disabled="!formJadwal.auto_backup"
                                class="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:bg-slate-100 disabled:text-slate-400"
                            >
                                <option :value="7">Simpan 7 Hari Terakhir</option>
                                <option :value="14">Simpan 14 Hari Terakhir (Default)</option>
                                <option :value="30">Simpan 30 Hari Terakhir</option>
                                <option :value="60">Simpan 60 Hari Terakhir</option>
                            </select>
                            <p class="text-[10px] text-slate-400">Berkas lebih lama dari batas retensi otomatis dihapus.</p>
                        </div>
                    </div>

                    <!-- Integrasi Cloud Storage -->
                    <div class="pt-3 border-t border-slate-100 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <Cloud class="h-4 w-4 text-indigo-600" />
                                    Sinkronisasi ke Penyimpanan Eksternal (Cloud)
                                </p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Kirim salinan berkas cadangan ke Google Drive, AWS S3, atau server remote SFTP.
                                </p>
                            </div>
                            <input
                                type="checkbox"
                                v-model="formJadwal.simpan_cloud"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                        </div>

                        <div v-if="formJadwal.simpan_cloud" class="p-3 bg-indigo-50/50 rounded-xl border border-indigo-100 text-xs text-slate-700 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                                <span>Penyimpanan Lokal Server & Cadangan Cloud Terhubung.</span>
                            </div>
                            <span class="font-mono text-[11px] font-bold text-indigo-700">Lokal + Cloud</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="handleMockAction('Menyimpan pengaturan jadwal pencadangan otomatis...')"
                            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-2xs transition-colors cursor-pointer"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            <span>Simpan Konfigurasi Jadwal</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════ -->
            <!-- MODAL: KONFIRMASI PEMULIHAN (RESTORE MODAL) -->
            <!-- ════════════════════════════════════════════════════════════ -->
            <div
                v-if="isRestoreModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150"
            >
                <div class="bg-white rounded-2xl max-w-lg w-full border border-slate-200 shadow-2xl p-5 space-y-4">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2.5 text-rose-600">
                            <div class="p-2 rounded-xl bg-rose-100">
                                <AlertTriangle class="h-5 w-5 text-rose-600" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Konfirmasi Pemulihan Database</h3>
                                <p class="text-xs text-slate-500">Tindakan ini akan menimpa data aktif saat ini.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeRestoreModal"
                            class="text-slate-400 hover:text-slate-600 p-1 rounded-lg"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 font-mono space-y-1">
                            <p class="font-bold text-slate-800">Berkas yang akan dipulihkan:</p>
                            <p class="text-indigo-700 font-bold truncate">{{ selectedBackupForRestore?.filename }}</p>
                            <p class="text-slate-500 text-[11px]">Ukuran: {{ selectedBackupForRestore?.ukuran }} • Dibuat: {{ selectedBackupForRestore?.created_at }}</p>
                        </div>

                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs">
                            <p class="font-bold">Perhatian Khusus:</p>
                            <p class="mt-0.5 text-rose-800/90 leading-relaxed">
                                Pastikan Anda memahami bahwa seluruh data transaksi setelah tanggal berkas ini dibuat akan digantikan. Ketik kata <strong>PULIHKAN</strong> di bawah untuk membuka tombol konfirmasi.
                            </p>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-800">
                                Ketik "PULIHKAN" untuk konfirmasi:
                            </label>
                            <input
                                type="text"
                                v-model="restoreConfirmationInput"
                                placeholder="Ketik PULIHKAN..."
                                class="w-full text-xs font-mono font-bold p-2.5 rounded-lg border border-slate-300 uppercase focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                            />
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="closeRestoreModal"
                            class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            :disabled="restoreConfirmationInput.trim().toUpperCase() !== 'PULIHKAN'"
                            @click="handleMockAction(`Menjalankan simulasi pemulihan dari berkas: ${selectedBackupForRestore?.filename}`); closeRestoreModal();"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-2xs transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <RotateCcw class="h-3.5 w-3.5" />
                            <span>Mulai Pulihkan Data</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
