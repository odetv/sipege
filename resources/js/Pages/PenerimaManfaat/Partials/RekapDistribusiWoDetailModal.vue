<script setup>
import { computed } from "vue";
import {
    X,
    Calendar,
    Utensils,
    Building2,
    Users,
    CheckCircle2,
    Clock,
    AlertCircle,
    ChevronRight,
    ExternalLink,
    ShieldAlert,
    Sparkles,
} from "lucide-vue-next";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    // mode: 'date' (fokus WO pada 1 tanggal) atau 'cell' (fokus 1 KPM pada 1 tanggal) atau 'kpm' (rekap KPM)
    mode: { type: String, default: "date" },
    selectedDate: { type: String, default: "" },
    selectedWo: { type: Object, default: null },
    selectedKpm: { type: Object, default: null },
    selectedDetail: { type: Object, default: null },
    dateColumns: { type: Array, default: () => [] },
});

const emit = defineEmits(["close", "navigateToWo"]);

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}

function getCategoryBadge(kategori) {
    switch (kategori) {
        case "Posyandu":
            return "bg-pink-100 text-pink-700 border-pink-200";
        case "SD":
        case "MI":
            return "bg-red-100 text-red-700 border-red-200";
        case "SMP":
        case "MTs":
            return "bg-blue-100 text-blue-700 border-blue-200";
        case "SMA":
        case "SMK":
        case "MA":
            return "bg-slate-100 text-slate-700 border-slate-200";
        case "TK":
        case "RA":
        case "PAUD":
            return "bg-amber-100 text-amber-700 border-amber-200";
        default:
            return "bg-emerald-100 text-emerald-700 border-emerald-200";
    }
}

function getWoStatusBadge(status) {
    const s = String(status || "").toLowerCase();
    if (s === "selesai" || s === "siap produksi") {
        return "bg-emerald-100 text-emerald-800 border-emerald-200";
    }
    if (s.includes("tolak") || s.includes("batal")) {
        return "bg-red-100 text-red-800 border-red-200";
    }
    if (s.includes("keuangan")) {
        return "bg-amber-100 text-amber-800 border-amber-200";
    }
    return "bg-blue-100 text-blue-800 border-blue-200";
}

function getDistStatusBadge(status) {
    if (status === "T") {
        return {
            label: "Terkirim (T)",
            class: "bg-emerald-600 text-white font-bold",
        };
    }
    if (status === "P") {
        return {
            label: "Proses / Terjadwal (P)",
            class: "bg-blue-600 text-white font-bold",
        };
    }
    if (status === "L") {
        return {
            label: "Libur Operasional (L)",
            class: "bg-slate-200 text-slate-700 font-semibold",
        };
    }
    return {
        label: "Belum Ditentukan (-)",
        class: "border border-dashed border-slate-300 text-slate-400 bg-white",
    };
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 transition-all"
        @click.self="$emit('close')"
    >
        <div
            class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200"
        >
            <!-- ─── MODAL HEADER ───────────────────────────────────────────── -->
            <div
                class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-400"
                    >
                        <Utensils v-if="mode === 'date'" class="w-5 h-5" />
                        <Building2 v-else class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-white tracking-tight">
                                <span v-if="mode === 'date'">Detail Work Order Harian</span>
                                <span v-else-if="mode === 'cell'">Detail Alokasi Titik Penerima Manfaat</span>
                                <span v-else>Rekap Riwayat Distribusi Titik Sasaran</span>
                            </h3>
                            <span
                                v-if="selectedDate"
                                class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"
                            >
                                {{ formatTanggalIndo(selectedDate) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 mt-0.5">
                            <span v-if="mode === 'date'">
                                Rincian rencana produksi, menu makanan bergizi, dan alokasi penerima manfaat dari Work Order.
                            </span>
                            <span v-else-if="mode === 'cell'">
                                Informasi detail WO, jumlah porsi penerima manfaat (PM), rincian kelas, dan status alergi.
                            </span>
                            <span v-else>
                                Riwayat alokasi porsi dan pengiriman pada seluruh rentang tanggal rekapitulasi.
                            </span>
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="$emit('close')"
                    class="w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/60 flex items-center justify-center transition-colors cursor-pointer"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- ─── MODAL BODY ─────────────────────────────────────────────── -->
            <div class="p-5 sm:p-6 overflow-y-auto space-y-5 text-slate-700 text-xs">
                <!-- ========================================================= -->
                <!-- MODE 1: DETAIL WORK ORDER HARIAN (PER TANGGAL)            -->
                <!-- ========================================================= -->
                <template v-if="mode === 'date'">
                    <div v-if="selectedWo" class="space-y-4">
                        <!-- WO Highlight Card -->
                        <div
                            class="p-4 rounded-xl bg-gradient-to-br from-emerald-50/70 via-white to-teal-50/50 border border-emerald-200/80 shadow-2xs"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                            {{ selectedWo.nomor_wo }}
                                        </span>
                                        <span :class="['px-2 py-0.5 rounded text-[11px] font-bold border', getWoStatusBadge(selectedWo.status)]">
                                            Status WO: {{ selectedWo.status || 'Draft' }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            Siklus Ke-{{ selectedWo.siklus_ke || 1 }}
                                        </span>
                                    </div>
                                    <h4 class="text-base sm:text-lg font-black text-slate-900 mt-1.5 flex items-center gap-2">
                                        {{ selectedWo.nama_menu }}
                                    </h4>
                                </div>

                                <a
                                    v-if="selectedWo.uuid"
                                    :href="route('work-order.buat', { id: selectedWo.uuid })"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition-colors shrink-0"
                                >
                                    <span>Buka Dokumen WO</span>
                                    <ExternalLink class="w-3.5 h-3.5" />
                                </a>
                            </div>

                            <!-- Sub Menus -->
                            <div v-if="selectedWo.sub_menus && selectedWo.sub_menus.length > 0" class="mt-3 pt-3 border-t border-emerald-100/80">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                    <Utensils class="w-3.5 h-3.5 text-emerald-600" />
                                    Komponen Sub-Menu Makanan:
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="(sm, idx) in selectedWo.sub_menus"
                                        :key="idx"
                                        class="px-2.5 py-1 rounded-md bg-white border border-emerald-200 text-slate-800 font-semibold text-xs shadow-2xs"
                                    >
                                        {{ sm }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- KPI Stats Cards for this WO -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Sasaran PM</div>
                                <div class="text-lg font-black text-slate-900 mt-0.5">
                                    {{ formatNumber(selectedWo.total_pm) }}
                                    <span class="text-xs font-semibold text-slate-500">Jiwa</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-200/80">
                                <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Porsi Kecil (PK)</div>
                                <div class="text-lg font-black text-emerald-900 mt-0.5">
                                    {{ formatNumber(selectedWo.total_pk) }}
                                    <span class="text-xs font-semibold text-emerald-700">porsi</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-teal-50/60 border border-teal-200/80">
                                <div class="text-[10px] font-bold text-teal-800 uppercase tracking-wider">Porsi Besar (PB)</div>
                                <div class="text-lg font-black text-teal-900 mt-0.5">
                                    {{ formatNumber(selectedWo.total_pb) }}
                                    <span class="text-xs font-semibold text-teal-700">porsi</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-200/80">
                                <div class="text-[10px] font-bold text-blue-800 uppercase tracking-wider">Jadwal Titik Sasaran</div>
                                <div class="text-lg font-black text-blue-900 mt-0.5">
                                    {{ selectedWo.total_kelompok_menerima || selectedWo.total_kelompok || 0 }}
                                    <span class="text-xs font-semibold text-blue-700">Menerima</span>
                                    <span v-if="selectedWo.total_kelompok_libur > 0" class="text-[11px] font-normal text-slate-500 ml-1">
                                        ({{ selectedWo.total_kelompok_libur }} Libur)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Kelompok Distribution List in this WO -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <Users class="w-3.5 h-3.5 text-emerald-600" />
                                    Daftar Titik Sasaran Penerima Manfaat pada WO Tanggal Ini
                                </h5>
                                <span class="text-[11px] text-slate-500">
                                    {{ selectedWo.kelompoks?.length || 0 }} Titik Terdaftar
                                </span>
                            </div>

                            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs max-h-[360px] overflow-y-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-100 text-slate-700 font-bold sticky top-0 z-10 border-b border-slate-200">
                                        <tr>
                                            <th class="py-2 px-2 text-center w-8">No</th>
                                            <th class="py-2 px-3">Nama Kelompok Sasaran</th>
                                            <th class="py-2 px-2 text-center w-24">Kategori</th>
                                            <th class="py-2 px-2 text-center w-28">Status Jadwal</th>
                                            <th class="py-2 px-2 text-right w-20">PK</th>
                                            <th class="py-2 px-2 text-right w-20">PB</th>
                                            <th class="py-2 px-2 text-right w-24">Total PM</th>
                                            <th class="py-2 px-3 w-32">Catatan Alergi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr
                                            v-for="(k, idx) in selectedWo.kelompoks"
                                            :key="k.id || idx"
                                            :class="['hover:bg-slate-50 transition-colors', !k.is_menerima ? 'bg-slate-50/60 opacity-70' : '']"
                                        >
                                            <td class="py-2 px-2 text-center text-slate-400 font-medium">{{ idx + 1 }}</td>
                                            <td class="py-2 px-3 font-bold text-slate-900">
                                                {{ k.nama_kelompok }}
                                            </td>
                                            <td class="py-2 px-2 text-center">
                                                <span :class="['px-1.5 py-0.5 rounded text-[10px] font-bold border', getCategoryBadge(k.kategori)]">
                                                    {{ k.kategori }}
                                                </span>
                                            </td>
                                            <td class="py-2 px-2 text-center">
                                                <span
                                                    v-if="k.is_menerima"
                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"
                                                >
                                                    <CheckCircle2 class="w-3 h-3 text-emerald-600" />
                                                    Menerima
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"
                                                >
                                                    Libur / Off
                                                </span>
                                            </td>
                                            <td class="py-2 px-2 text-right font-medium text-slate-700">
                                                {{ k.is_menerima ? formatNumber(k.porsi_kecil) : 0 }}
                                            </td>
                                            <td class="py-2 px-2 text-right font-medium text-slate-700">
                                                {{ k.is_menerima ? formatNumber(k.porsi_besar) : 0 }}
                                            </td>
                                            <td class="py-2 px-2 text-right font-black text-slate-900 bg-slate-50/50">
                                                {{ k.is_menerima ? formatNumber(k.total_penerima) : 0 }}
                                            </td>
                                            <td class="py-2 px-3">
                                                <div v-if="k.detail_alergi && k.detail_alergi.length > 0" class="flex flex-wrap gap-1">
                                                    <span
                                                        v-for="(al, aIdx) in k.detail_alergi.slice(0, 2)"
                                                        :key="aIdx"
                                                        class="px-1.5 py-0.2 rounded text-[10px] bg-red-50 text-red-700 border border-red-200"
                                                        :title="`${al.jenis_alergi}: PK ${al.porsi_kecil || 0}, PB ${al.porsi_besar || 0}`"
                                                    >
                                                        {{ al.jenis_alergi }} ({{ (al.porsi_kecil || 0) + (al.porsi_besar || 0) }})
                                                    </span>
                                                    <span v-if="k.detail_alergi.length > 2" class="text-[10px] text-slate-400">
                                                        +{{ k.detail_alergi.length - 2 }}
                                                    </span>
                                                </div>
                                                <span v-else class="text-slate-400 text-[11px]">-</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- State jika tidak ada WO pada tanggal ini -->
                    <div v-else class="py-12 text-center text-slate-400 flex flex-col items-center justify-center gap-2">
                        <AlertCircle class="w-10 h-10 text-slate-300" />
                        <div class="text-sm font-bold text-slate-600">Belum Ada Work Order (WO) untuk Tanggal Ini</div>
                        <div class="text-xs text-slate-400 max-w-sm">
                            Tidak ditemukan data rancangan menu dan alokasi penerima manfaat untuk tanggal {{ formatTanggalIndo(selectedDate) }}.
                        </div>
                    </div>
                </template>

                <!-- ========================================================= -->
                <!-- MODE 2: DETAIL ALOKASI KPM SPESIFIK PADA TANGGAL INI      -->
                <!-- ========================================================= -->
                <template v-else-if="mode === 'cell'">
                    <div v-if="selectedKpm" class="space-y-4">
                        <!-- Profil KPM Header Card -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold border', getCategoryBadge(selectedKpm.kategori)]">
                                        {{ selectedKpm.kategori }}
                                    </span>
                                    <span class="text-xs text-slate-500 font-medium">
                                        {{ selectedKpm.tipe_identitas || 'NPSN' }}: {{ selectedKpm.kode_identitas || '-' }}
                                    </span>
                                    <span class="text-slate-400">• {{ selectedKpm.desa_kelurahan || '-' }}</span>
                                </div>
                                <h4 class="text-base font-black text-slate-900 mt-1">
                                    {{ selectedKpm.nama_kelompok }}
                                </h4>
                                <div class="text-xs text-slate-500 mt-0.5 flex flex-wrap gap-x-4 gap-y-1">
                                    <span>PIC: <strong class="text-slate-700">{{ selectedKpm.nama_pic || '-' }}</strong></span>
                                    <span>Telepon: <strong class="text-slate-700">{{ selectedKpm.telepon_pic || '-' }}</strong></span>
                                </div>
                            </div>

                            <!-- Status Distribusi Hari Ini -->
                            <div class="flex flex-col sm:items-end gap-1">
                                <span class="text-[10px] uppercase font-bold text-slate-500">Status Distribusi Tanggal Ini:</span>
                                <div class="flex items-center gap-1.5">
                                    <span :class="['px-2.5 py-1 rounded-md text-xs', getDistStatusBadge(selectedDetail?.status).class]">
                                        {{ getDistStatusBadge(selectedDetail?.status).label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Work Order pada Tanggal Ini -->
                        <div v-if="selectedDetail?.has_wo" class="p-4 rounded-xl bg-gradient-to-r from-emerald-50/80 via-white to-teal-50/60 border border-emerald-200 shadow-2xs space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                                            {{ selectedDetail.nomor_wo }}
                                        </span>
                                        <span :class="['px-2 py-0.5 rounded text-[10px] font-bold border', getWoStatusBadge(selectedDetail.wo_status)]">
                                            {{ selectedDetail.wo_status }}
                                        </span>
                                    </div>
                                    <h5 class="text-sm font-black text-slate-900 mt-1 flex items-center gap-1.5">
                                        <Utensils class="w-4 h-4 text-emerald-600" />
                                        Menu MBG: {{ selectedDetail.nama_menu }}
                                    </h5>
                                </div>

                                <div v-if="selectedDetail.wo_uuid">
                                    <a
                                        :href="route('work-order.buat', { id: selectedDetail.wo_uuid })"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-800 underline"
                                    >
                                        <span>Rincian Lengkap WO</span>
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>

                            <!-- Sub Menus -->
                            <div v-if="selectedDetail.sub_menus && selectedDetail.sub_menus.length > 0" class="pt-2 border-t border-emerald-100 flex flex-wrap gap-1.5">
                                <span
                                    v-for="(sm, sIdx) in selectedDetail.sub_menus"
                                    :key="sIdx"
                                    class="px-2 py-0.5 rounded bg-white text-slate-700 font-medium text-[11px] border border-emerald-200 shadow-2xs"
                                >
                                    {{ sm }}
                                </span>
                            </div>
                        </div>

                        <!-- KPI Alokasi Porsi dari WO -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-center">
                                <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Porsi Kecil (PK)</div>
                                <div class="text-xl font-black text-emerald-950 mt-1">
                                    {{ formatNumber(selectedDetail?.porsi_kecil) }}
                                </div>
                                <div class="text-[10px] text-emerald-700 mt-0.5">TK / SD Kelas 1-3 / Balita</div>
                            </div>
                            <div class="p-3 rounded-xl bg-teal-50 border border-teal-200 text-center">
                                <div class="text-[10px] font-bold text-teal-800 uppercase tracking-wider">Porsi Besar (PB)</div>
                                <div class="text-xl font-black text-teal-950 mt-1">
                                    {{ formatNumber(selectedDetail?.porsi_besar) }}
                                </div>
                                <div class="text-[10px] text-teal-700 mt-0.5">SD 4-6 / SMP / SMA / Guru</div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-100 border border-slate-300 text-center">
                                <div class="text-[10px] font-bold text-slate-700 uppercase tracking-wider">Total PM Tanggal Ini</div>
                                <div class="text-xl font-black text-slate-900 mt-1">
                                    {{ formatNumber(selectedDetail?.total_penerima) }}
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">Jiwa Penerima Sasaran</div>
                            </div>
                        </div>

                        <!-- Rincian Kelas & Sub Kategori dari WO -->
                        <div v-if="selectedDetail?.rincian && selectedDetail.rincian.length > 0" class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <div class="px-3.5 py-2.5 bg-slate-100 border-b border-slate-200 font-bold text-xs text-slate-800 flex items-center justify-between">
                                <span>Rincian Penerima Berdasarkan Kelas / Kelompok Sasaran</span>
                                <span class="text-[11px] text-slate-500">{{ selectedDetail.rincian.length }} Kategori</span>
                            </div>
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                    <tr>
                                        <th class="py-2 px-3">Sub Kategori / Kelas</th>
                                        <th class="py-2 px-2">Jenis Porsi</th>
                                        <th class="py-2 px-2 text-right">Laki-Laki</th>
                                        <th class="py-2 px-2 text-right">Perempuan</th>
                                        <th class="py-2 px-3 text-right font-black">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(r, rIdx) in selectedDetail.rincian" :key="rIdx" class="hover:bg-slate-50">
                                        <td class="py-1.5 px-3 font-semibold text-slate-800">{{ r.sub_kategori }}</td>
                                        <td class="py-1.5 px-2">
                                            <span :class="['px-1.5 py-0.2 rounded text-[10px] font-bold', r.jenis_porsi === 'Porsi Kecil' ? 'bg-emerald-100 text-emerald-800' : 'bg-teal-100 text-teal-800']">
                                                {{ r.jenis_porsi }}
                                            </span>
                                        </td>
                                        <td class="py-1.5 px-2 text-right text-slate-600">{{ r.jumlah_laki_laki || 0 }}</td>
                                        <td class="py-1.5 px-2 text-right text-slate-600">{{ r.jumlah_perempuan || 0 }}</td>
                                        <td class="py-1.5 px-3 text-right font-black text-slate-900 bg-slate-50/50">{{ r.total || 0 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Catatan Alergi jika ada -->
                        <div v-if="selectedDetail?.detail_alergi && selectedDetail.detail_alergi.length > 0" class="p-3.5 rounded-xl bg-red-50/60 border border-red-200">
                            <div class="text-xs font-bold text-red-900 flex items-center gap-1.5 mb-2">
                                <ShieldAlert class="w-4 h-4 text-red-600" />
                                Rincian Alergi Khusus yang Tercatat pada Work Order:
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div
                                    v-for="(al, alIdx) in selectedDetail.detail_alergi"
                                    :key="alIdx"
                                    class="p-2 rounded-lg bg-white border border-red-200 shadow-2xs text-[11px]"
                                >
                                    <div class="font-bold text-red-900">{{ al.jenis_alergi }}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        PK: <strong>{{ al.porsi_kecil || 0 }}</strong> • PB: <strong>{{ al.porsi_besar || 0 }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ========================================================= -->
                <!-- MODE 3: REKAP RIWAYAT KPM DI SELURUH TANGGAL PERIODE      -->
                <!-- ========================================================= -->
                <template v-else-if="mode === 'kpm'">
                    <div v-if="selectedKpm" class="space-y-4">
                        <!-- Profil KPM Header -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="flex items-center gap-2">
                                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold border', getCategoryBadge(selectedKpm.kategori)]">
                                    {{ selectedKpm.kategori }}
                                </span>
                                <span class="text-xs text-slate-500 font-medium">
                                    {{ selectedKpm.tipe_identitas || 'NPSN' }}: {{ selectedKpm.kode_identitas || '-' }}
                                </span>
                                <span class="text-slate-400">• {{ selectedKpm.desa_kelurahan || '-' }}</span>
                            </div>
                            <h4 class="text-base font-black text-slate-900 mt-1">
                                {{ selectedKpm.nama_kelompok }}
                            </h4>
                            <div class="text-xs text-slate-500 mt-0.5 flex flex-wrap gap-x-4 gap-y-1">
                                <span>Alamat: <strong class="text-slate-700">{{ selectedKpm.alamat_lengkap || '-' }}</strong></span>
                                <span>PIC: <strong class="text-slate-700">{{ selectedKpm.nama_pic || '-' }} ({{ selectedKpm.telepon_pic || '-' }})</strong></span>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="p-3 rounded-xl bg-slate-100 border border-slate-200 text-center">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Sasaran Base</div>
                                <div class="text-lg font-black text-slate-900 mt-0.5">{{ formatNumber(selectedKpm.total_penerima) }} PM</div>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-center">
                                <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Total Hari Terkirim</div>
                                <div class="text-lg font-black text-emerald-900 mt-0.5">{{ selectedKpm.total_hari_kirim || 0 }} Hari</div>
                            </div>
                            <div class="p-3 rounded-xl bg-teal-50 border border-teal-200 text-center">
                                <div class="text-[10px] font-bold text-teal-800 uppercase tracking-wider">Tingkat Layanan</div>
                                <div class="text-lg font-black text-teal-900 mt-0.5">{{ selectedKpm.persentase_layanan || 0 }}%</div>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-100/70 border border-emerald-300 text-center">
                                <div class="text-[10px] font-bold text-emerald-900 uppercase tracking-wider">Total Akumulasi Porsi</div>
                                <div class="text-lg font-black text-emerald-950 mt-0.5">{{ formatNumber(selectedKpm.total_porsi_akumulasi) }}</div>
                            </div>
                        </div>

                        <!-- Riwayat Kolom Tanggal -->
                        <div>
                            <h5 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">
                                Matriks Riwayat Work Order & Status Pengiriman per Tanggal:
                            </h5>

                            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs max-h-[320px] overflow-y-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 sticky top-0 z-10">
                                        <tr>
                                            <th class="py-2 px-3">Tanggal & Hari</th>
                                            <th class="py-2 px-3">Work Order (WO)</th>
                                            <th class="py-2 px-3">Menu Makanan MBG</th>
                                            <th class="py-2 px-2 text-center">Jadwal WO</th>
                                            <th class="py-2 px-2 text-right">PK</th>
                                            <th class="py-2 px-2 text-right">PB</th>
                                            <th class="py-2 px-2 text-right font-black">Total PM</th>
                                            <th class="py-2 px-3 text-center">Status Distribusi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr
                                            v-for="col in dateColumns"
                                            :key="col.dateStr"
                                            class="hover:bg-slate-50 transition-colors"
                                        >
                                            <td class="py-2 px-3 font-semibold text-slate-800">
                                                {{ col.dayName }}, {{ formatTanggalIndo(col.dateStr) }}
                                            </td>
                                            <td class="py-2 px-3 font-mono text-[11px] text-slate-600">
                                                <span v-if="selectedKpm.history_details?.[col.dateStr]?.nomor_wo" class="font-bold text-slate-800">
                                                    {{ selectedKpm.history_details[col.dateStr].nomor_wo }}
                                                </span>
                                                <span v-else class="text-slate-400 italic">Tidak ada WO</span>
                                            </td>
                                            <td class="py-2 px-3 text-slate-800 font-medium">
                                                {{ selectedKpm.history_details?.[col.dateStr]?.nama_menu || '-' }}
                                            </td>
                                            <td class="py-2 px-2 text-center">
                                                <span
                                                    v-if="selectedKpm.history_details?.[col.dateStr]?.has_wo"
                                                    :class="['px-1.5 py-0.5 rounded text-[10px] font-bold', selectedKpm.history_details[col.dateStr].is_menerima ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600']"
                                                >
                                                    {{ selectedKpm.history_details[col.dateStr].is_menerima ? 'Menerima' : 'Libur' }}
                                                </span>
                                                <span v-else class="text-slate-400 text-[10px]">-</span>
                                            </td>
                                            <td class="py-2 px-2 text-right text-slate-600">
                                                {{ formatNumber(selectedKpm.history_details?.[col.dateStr]?.porsi_kecil ?? selectedKpm.porsi_kecil_harian) }}
                                            </td>
                                            <td class="py-2 px-2 text-right text-slate-600">
                                                {{ formatNumber(selectedKpm.history_details?.[col.dateStr]?.porsi_besar ?? selectedKpm.porsi_besar_harian) }}
                                            </td>
                                            <td class="py-2 px-2 text-right font-black text-slate-900 bg-slate-50/50">
                                                {{ formatNumber(selectedKpm.history_details?.[col.dateStr]?.total_penerima ?? selectedKpm.total_porsi_harian) }}
                                            </td>
                                            <td class="py-2 px-3 text-center">
                                                <span :class="['px-2 py-0.5 rounded text-[11px] font-bold', getDistStatusBadge(selectedKpm.history_dates?.[col.dateStr]).class]">
                                                    {{ selectedKpm.history_dates?.[col.dateStr] || '-' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ─── MODAL FOOTER ───────────────────────────────────────────── -->
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                    <Sparkles class="w-3.5 h-3.5 text-emerald-600" />
                    <span>Data distribusi otomatis terintegrasi dengan modul Work Order & Perencanaan Gizi.</span>
                </div>

                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition-colors shadow-2xs cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
