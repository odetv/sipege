<script setup>
import { computed } from "vue";
import {
    CheckCircle2,
    Clock,
    Phone,
    MapPin,
    Building2,
    CalendarCheck,
    Utensils,
    Eye,
    Info,
    Sparkles,
} from "lucide-vue-next";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";

const props = defineProps({
    items: { type: Array, default: () => [] },
    dateColumns: { type: Array, default: () => [] },
    tanggalMulai: { type: String, default: "" },
    tanggalSelesai: { type: String, default: "" },
    matrixDistribusi: { type: Object, default: () => ({}) },
    matrixStatus: { type: Object, default: () => ({}) }, // fallback compatibility
    woByDateMap: { type: Object, default: () => ({}) },
    matrixDetailMap: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
    "cycleStatus",
    "toggleKolomStatus",
    "openDetail",
    "openWoDetail",
    "openCellDetail",
]);

const activeMatrix = computed(() => {
    return props.matrixDistribusi && Object.keys(props.matrixDistribusi).length > 0
        ? props.matrixDistribusi
        : (props.matrixStatus || {});
});

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

// Ambil status sel: default kosong "" jika belum ada pencatatan (kecuali Minggu default "L")
function getCellStatus(itemId, dateStr, isSunday) {
    const row = activeMatrix.value[itemId];
    if (row && row[dateStr] !== undefined) {
        return row[dateStr];
    }
    // Hari Minggu jika belum diset bisa default L atau kosong
    return isSunday ? "L" : "";
}

// Hitung hari kirim riil (hanya status 'T') per baris
function getRowHariKirim(item) {
    let count = 0;
    props.dateColumns.forEach((col) => {
        const st = getCellStatus(item.id, col.dateStr, col.isSunday);
        if (st === "T") count++;
    });
    return count;
}

// Hitung total akumulasi riil dari porsi WO pada tanggal-tanggal status 'T'
function getRowTotalAkumulasi(item) {
    let sum = 0;
    props.dateColumns.forEach((col) => {
        const st = getCellStatus(item.id, col.dateStr, col.isSunday);
        if (st === "T") {
            const det = props.matrixDetailMap?.[item.id]?.[col.dateStr];
            if (det && det.has_wo) {
                sum += Number(det.total_penerima) || 0;
            } else {
                sum += Number(item.total_porsi_harian) || 0;
            }
        }
    });
    return sum;
}

// Total porsi terkirim pada kolom tanggal tertentu
function getColTotalPorsi(col) {
    if (col.isSunday) return 0;
    let sum = 0;
    props.items.forEach((it) => {
        const st = getCellStatus(it.id, col.dateStr, col.isSunday);
        if (st === "T") {
            const det = props.matrixDetailMap?.[it.id]?.[col.dateStr];
            if (det && det.has_wo) {
                sum += Number(det.total_penerima) || 0;
            } else {
                sum += Number(it.total_porsi_harian) || 0;
            }
        }
    });
    return sum;
}

// Total hari yang memiliki Work Order
const totalHariAdaWo = computed(() => {
    return props.dateColumns.filter((c) => props.woByDateMap && props.woByDateMap[c.dateStr]).length;
});
</script>

<template>
    <div class="rounded-xl bg-white border border-slate-200 shadow-2xs overflow-hidden">
        <!-- Toolbar Tabel & Keterangan Status Distribusi (Persis Rekap Kehadiran) -->
        <div class="px-3.5 sm:px-4 py-2.5 sm:py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-slate-50/60">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Matriks Distribusi ({{ items.length }} Titik Sasaran)
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                    {{ dateColumns.length }} Kolom Tanggal
                </span>
                <span
                    v-if="totalHariAdaWo > 0"
                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-100 text-teal-800 border border-teal-200 flex items-center gap-1"
                    title="Jumlah hari dalam periode yang memiliki data Work Order terencana"
                >
                    <Utensils class="w-3 h-3 text-teal-600" />
                    {{ totalHariAdaWo }} Hari Terjadwal WO
                </span>
            </div>

            <!-- Petunjuk Status & Interaktivitas Klik -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 text-[11px] text-slate-600 font-medium">
                <span class="text-slate-400 hidden xs:inline">Klik sel untuk ubah:</span>
                <span class="inline-flex items-center gap-1 font-bold text-emerald-700" title="Terkirim Lengkap">
                    <span class="w-2.5 h-2.5 rounded-sm bg-emerald-600"></span>T (Terkirim)
                </span>
                <span class="inline-flex items-center gap-1 font-bold text-blue-700" title="Proses / Jadwal">
                    <span class="w-2.5 h-2.5 rounded-sm bg-blue-600"></span>P (Proses)
                </span>
                <span class="inline-flex items-center gap-1 font-bold text-slate-600" title="Libur Operasional">
                    <span class="w-2.5 h-2.5 rounded-sm bg-slate-300"></span>L (Libur)
                </span>
                <span class="inline-flex items-center gap-1 font-medium text-slate-400" title="Belum Dicatat (Kosong)">
                    <span class="w-2.5 h-2.5 rounded-sm border border-dashed border-slate-300 bg-white"></span>(Kosong)
                </span>
                <span class="inline-flex items-center gap-1 font-bold text-teal-800 bg-teal-50 px-1.5 py-0.5 rounded border border-teal-200" title="Kolom Tanggal Terhubung dengan Work Order (WO)">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>Ada WO
                </span>
            </div>
        </div>

        <!-- Kontainer Tabel dengan Horizontal Scroll Presisi -->
        <div class="overflow-x-auto relative">
            <table class="w-full text-left text-xs border-collapse">
                <!-- Table Head: Baris 1 Visualisasi Rentang Terpilih -->
                <thead class="sticky top-0 z-20">
                    <tr class="border-b border-emerald-700/60 text-white">
                        <th colspan="3" class="py-1 px-3 bg-slate-800 text-slate-200 text-[10px] font-bold uppercase tracking-wider sticky left-0 z-30 border-r border-slate-700">
                            Informasi Titik Penerima Manfaat
                        </th>
                        <th
                            :colspan="dateColumns.length"
                            class="py-1 px-2 text-center bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-800 text-[11px] font-black tracking-wider uppercase border-r border-emerald-600 shadow-inner"
                        >
                            Rentang Tanggal: {{ formatTanggalIndo(tanggalMulai) }} – {{ formatTanggalIndo(tanggalSelesai) }}
                        </th>
                        <th colspan="5" class="py-1 px-2 text-center bg-slate-800 text-slate-200 text-[10px] font-bold uppercase tracking-wider">
                            Rekapitulasi Akumulasi Porsi
                        </th>
                    </tr>

                    <!-- Table Head: Baris 2 Rincian Kolom & Hari -->
                    <tr class="bg-slate-100/95 backdrop-blur-xs text-[11px] font-bold text-slate-700 border-b border-slate-200">
                        <!-- Sticky Kolom 1: No -->
                        <th class="py-2.5 px-2 text-center w-9 sticky left-0 z-20 bg-slate-100 border-r border-slate-200">
                            No
                        </th>
                        <!-- Sticky Kolom 2: Nama Kelompok -->
                        <th class="py-2.5 px-3 min-w-[200px] sticky left-9 z-20 bg-slate-100 border-r border-slate-200">
                            Kelompok & NPSN
                        </th>
                        <!-- Sticky Kolom 3: Kategori & PIC (Sticky di Desktop, Normal di Mobile agar mudah swipe tanggal) -->
                        <th class="py-2.5 px-3 min-w-[140px] sm:min-w-[150px] static sm:sticky sm:left-[236px] z-10 sm:z-20 bg-slate-100 border-r border-slate-300 shadow-none sm:shadow-[2px_0_4px_-1px_rgba(0,0,0,0.06)]">
                            PIC & Sasaran PM
                        </th>

                        <!-- Dinamis Kolom Tanggal (Header bisa diklik untuk toggle 1 kolom) -->
                        <th
                            v-for="col in dateColumns"
                            :key="col.dateStr"
                            @click="$emit('toggleKolomStatus', col.dateStr)"
                            :class="[
                                'py-1.5 px-0.5 text-center min-w-[36px] max-w-[36px] border-r border-slate-200 select-none transition-all cursor-pointer hover:bg-slate-200/80 active:scale-95 relative group/col',
                                col.isToday
                                    ? 'bg-emerald-100/80 font-black text-emerald-950'
                                    : (col.isSunday ? 'bg-red-50/70 text-red-700 font-semibold' : (col.isWeekend ? 'bg-amber-50/50 text-amber-800' : ''))
                            ]"
                            :title="woByDateMap && woByDateMap[col.dateStr] 
                                ? `${col.dayName}, ${col.dateStr}\nWO: ${woByDateMap[col.dateStr].nomor_wo} (${woByDateMap[col.dateStr].nama_menu})\nTotal Sasaran: ${woByDateMap[col.dateStr].total_pm} PM\nStatus WO: ${woByDateMap[col.dateStr].status}\n(Klik tanggal untuk ubah status seluruh baris. Klik badge WO untuk rincian menu)`
                                : `${col.dayName}, ${col.dateStr} (Klik tanggal untuk ganti status: T ➜ P ➜ L ➜ Kosong)`"
                        >
                            <div class="text-[9px] leading-tight opacity-75 uppercase">{{ col.dayName }}</div>
                            <div class="text-[11px] leading-tight font-extrabold mt-0.5">{{ col.dayNum }}</div>

                            <!-- Indikator Badge Work Order jika ada WO pada tanggal ini -->
                            <div v-if="woByDateMap && woByDateMap[col.dateStr]" class="mt-0.5 flex justify-center">
                                <button
                                    type="button"
                                    @click.stop="$emit('openWoDetail', col.dateStr)"
                                    class="px-1 py-0.2 rounded text-[8px] font-black tracking-tighter bg-emerald-700 hover:bg-emerald-800 text-white shadow-2xs hover:scale-105 active:scale-95 transition-all cursor-pointer flex items-center gap-0.5"
                                    title="Klik untuk melihat Detail Work Order & Menu MBG"
                                >
                                    <span>WO</span>
                                </button>
                            </div>
                        </th>

                        <!-- Kolom Rekapitulasi Sisi Kanan -->
                        <th class="py-2.5 px-2 text-center min-w-[60px] bg-slate-100/90 border-r border-slate-200">
                            Hari Kirim
                        </th>
                        <th class="py-2.5 px-2 text-right min-w-[70px] bg-slate-100/90 border-r border-slate-200">
                            Porsi Kecil
                        </th>
                        <th class="py-2.5 px-2 text-right min-w-[70px] bg-slate-100/90 border-r border-slate-200">
                            Porsi Besar
                        </th>
                        <th class="py-2.5 px-2 text-right min-w-[80px] bg-slate-100/90 border-r border-slate-200">
                            Porsi/Hari
                        </th>
                        <th class="py-2.5 px-3 text-right min-w-[100px] bg-emerald-50 text-emerald-950 font-black">
                            Total Akumulasi
                        </th>
                    </tr>
                </thead>

                <!-- Body Data Rows -->
                <tbody class="divide-y divide-slate-100 text-xs">
                    <tr
                        v-for="(item, idx) in items"
                        :key="item.id"
                        class="hover:bg-slate-50/80 transition-colors group"
                    >
                        <!-- Sticky 1: No -->
                        <td class="py-2 px-2 text-center font-semibold text-slate-400 sticky left-0 z-10 bg-white group-hover:bg-slate-50 border-r border-slate-100">
                            {{ idx + 1 }}
                        </td>

                        <!-- Sticky 2: Nama & Identitas -->
                        <td class="py-2 px-3 sticky left-9 z-10 bg-white group-hover:bg-slate-50 border-r border-slate-100">
                            <div class="flex items-center justify-between gap-1.5">
                                <div
                                    class="font-bold text-slate-900 line-clamp-1 cursor-pointer hover:text-emerald-700 transition-colors"
                                    :title="item.nama_kelompok + ' (Klik untuk lihat rekap riwayat WO & porsi)'"
                                    @click="$emit('openDetail', item)"
                                >
                                    {{ item.nama_kelompok }}
                                </div>
                                <button
                                    type="button"
                                    @click.stop="$emit('openDetail', item)"
                                    class="p-0.5 text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 rounded transition-colors shrink-0"
                                    title="Lihat Rekap Riwayat KPM & Detail WO"
                                >
                                    <Eye class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <div class="text-[10px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                <span class="font-medium text-slate-600">{{ item.tipe_identitas || 'NPSN' }}: {{ item.kode_identitas || '-' }}</span>
                                <span class="text-slate-400">• {{ item.desa_kelurahan || '-' }}</span>
                            </div>
                        </td>

                        <!-- Kolom 3: PIC & Sasaran PM (Sticky di Desktop, Normal di Mobile) -->
                        <td class="py-2 px-3 static sm:sticky sm:left-[236px] z-0 sm:z-10 bg-white group-hover:bg-slate-50 border-r border-slate-300 shadow-none sm:shadow-[2px_0_4px_-1px_rgba(0,0,0,0.06)]">
                            <div class="flex items-center gap-1.5">
                                <span :class="['px-1.5 py-0.2 rounded text-[10px] font-bold border', getCategoryBadge(item.kategori)]">
                                    {{ item.kategori }}
                                </span>
                                <span class="font-extrabold text-slate-800 text-[11px]">
                                    {{ formatNumber(item.total_penerima) }} PM
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5 truncate">
                                <span>PIC: {{ item.nama_pic || '-' }}</span>
                            </div>
                        </td>

                        <!-- Cell Status Matriks per Tanggal (Interaktif Berubah Saat Diklik) -->
                        <td
                            v-for="col in dateColumns"
                            :key="col.dateStr"
                            :class="[
                                'py-1 px-0.5 text-center border-r border-slate-100 transition-colors relative group/cell',
                                col.isToday
                                    ? 'bg-emerald-50/50'
                                    : (col.isSunday ? 'bg-red-50/20' : (col.isWeekend ? 'bg-amber-50/15' : ''))
                            ]"
                        >
                            <div class="flex items-center justify-center relative">
                                <!-- Status: T (Terkirim - Emerald) -->
                                <button
                                    v-if="getCellStatus(item.id, col.dateStr, col.isSunday) === 'T'"
                                    type="button"
                                    @click="$emit('cycleStatus', item.id, col.dateStr)"
                                    class="w-6 h-6 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-black text-[10px] flex items-center justify-center shadow-2xs transition-transform active:scale-95 cursor-pointer"
                                    :title="matrixDetailMap?.[item.id]?.[col.dateStr]?.has_wo
                                        ? `${item.nama_kelompok} (${col.dateStr})\nWO: ${matrixDetailMap[item.id][col.dateStr].nomor_wo} - ${matrixDetailMap[item.id][col.dateStr].nama_menu} (${matrixDetailMap[item.id][col.dateStr].wo_status})\nPorsi: ${matrixDetailMap[item.id][col.dateStr].porsi_kecil} PK • ${matrixDetailMap[item.id][col.dateStr].porsi_besar} PB (Total: ${matrixDetailMap[item.id][col.dateStr].total_penerima} PM)\nStatus Distribusi: Terkirim (T)\nKlik tombol untuk ubah ke Proses.`
                                        : `${item.nama_kelompok} (${col.dateStr}): Terkirim (${item.total_porsi_harian} Porsi). Klik untuk ubah ke Proses.`"
                                >
                                    T
                                </button>

                                <!-- Status: P (Proses / Terjadwal - Biru) -->
                                <button
                                    v-else-if="getCellStatus(item.id, col.dateStr, col.isSunday) === 'P'"
                                    type="button"
                                    @click="$emit('cycleStatus', item.id, col.dateStr)"
                                    class="w-6 h-6 rounded-md bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] flex items-center justify-center shadow-2xs transition-transform active:scale-95 cursor-pointer"
                                    :title="matrixDetailMap?.[item.id]?.[col.dateStr]?.has_wo
                                        ? `${item.nama_kelompok} (${col.dateStr})\nWO: ${matrixDetailMap[item.id][col.dateStr].nomor_wo} - ${matrixDetailMap[item.id][col.dateStr].nama_menu} (${matrixDetailMap[item.id][col.dateStr].wo_status})\nPorsi Terjadwal: ${matrixDetailMap[item.id][col.dateStr].total_penerima} PM\nStatus Distribusi: Proses / Terjadwal (P)\nKlik tombol untuk ubah ke Libur.`
                                        : `${item.nama_kelompok} (${col.dateStr}): Terjadwal/Proses. Klik untuk ubah ke Libur.`"
                                >
                                    P
                                </button>

                                <!-- Status: L (Libur Operasional - Slate) -->
                                <button
                                    v-else-if="getCellStatus(item.id, col.dateStr, col.isSunday) === 'L'"
                                    type="button"
                                    @click="$emit('cycleStatus', item.id, col.dateStr)"
                                    class="w-6 h-6 rounded-md bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-[10px] flex items-center justify-center transition-transform active:scale-95 cursor-pointer"
                                    :title="matrixDetailMap?.[item.id]?.[col.dateStr]?.has_wo
                                        ? `${item.nama_kelompok} (${col.dateStr})\nWO: ${matrixDetailMap[item.id][col.dateStr].nomor_wo} - ${matrixDetailMap[item.id][col.dateStr].nama_menu}\nStatus Jadwal: Libur / Tidak Menerima\nKlik tombol untuk kosongkan.`
                                        : `${item.nama_kelompok} (${col.dateStr}): Libur Operasional. Klik untuk kosongkan.`"
                                >
                                    L
                                </button>

                                <!-- Status: Kosong (Default jika belum ada pencatatan) -->
                                <button
                                    v-else
                                    type="button"
                                    @click="$emit('cycleStatus', item.id, col.dateStr)"
                                    class="w-6 h-6 rounded-md border border-dashed border-slate-200 hover:border-emerald-400 hover:bg-emerald-50 text-slate-300 hover:text-emerald-700 text-[11px] font-medium flex items-center justify-center transition-all cursor-pointer"
                                    :title="`${item.nama_kelompok} (${col.dateStr}): Belum ada pencatatan. Klik untuk set Terkirim (T).`"
                                >
                                    -
                                </button>

                                <!-- Tombol Kecil Info WO Titik Ini (Muncul saat hover jika ada WO) -->
                                <button
                                    v-if="matrixDetailMap?.[item.id]?.[col.dateStr]?.has_wo"
                                    type="button"
                                    @click.stop="$emit('openCellDetail', item, col.dateStr)"
                                    class="opacity-0 group-hover/cell:opacity-100 absolute -top-1 -right-1 z-10 w-3.5 h-3.5 rounded-full bg-slate-800 hover:bg-emerald-700 text-white flex items-center justify-center text-[8px] font-bold shadow-xs transition-opacity cursor-pointer"
                                    :title="'Lihat Rincian WO Titik Ini (' + matrixDetailMap[item.id][col.dateStr].nomor_wo + ')'"
                                >
                                    i
                                </button>
                            </div>
                        </td>

                        <!-- Hari Kirim (Dihitung dari Jumlah 'T') -->
                        <td class="py-2 px-2 text-center font-bold text-slate-800 border-r border-slate-100">
                            {{ getRowHariKirim(item) }} hr
                        </td>

                        <!-- PK Harian -->
                        <td class="py-2 px-2 text-right font-medium text-slate-700 border-r border-slate-100">
                            {{ formatNumber(item.porsi_kecil_harian) }}
                        </td>

                        <!-- PB Harian -->
                        <td class="py-2 px-2 text-right font-medium text-slate-700 border-r border-slate-100">
                            {{ formatNumber(item.porsi_besar_harian) }}
                        </td>

                        <!-- Porsi/Hari -->
                        <td class="py-2 px-2 text-right font-bold text-emerald-800 bg-emerald-50/40 border-r border-slate-100">
                            {{ formatNumber(item.total_porsi_harian) }}
                        </td>

                        <!-- Total Akumulasi Riil -->
                        <td class="py-2 px-3 text-right font-black text-emerald-950 bg-emerald-50/70">
                            {{ formatNumber(getRowTotalAkumulasi(item)) }}
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="!items || items.length === 0">
                        <td :colspan="8 + dateColumns.length" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <Building2 class="h-10 w-10 text-slate-300" />
                                <div class="text-sm font-semibold text-slate-600">Tidak ada data titik distribusi ditemukan</div>
                                <div class="text-xs text-slate-400">Sesuaikan filter pencarian atau tanggal yang dipilih.</div>
                            </div>
                        </td>
                    </tr>
                </tbody>

                <!-- Table Footer (Total Row) -->
                <tfoot v-if="items && items.length > 0" class="bg-slate-100 text-xs font-bold border-t-2 border-slate-300">
                    <tr>
                        <td colspan="3" class="py-2.5 px-3 text-right uppercase tracking-wider text-slate-700 sticky left-0 z-10 bg-slate-100 border-r border-slate-300">
                            Total Alokasi Porsi Harian:
                        </td>
                        <td
                            v-for="col in dateColumns"
                            :key="'tot-' + col.dateStr"
                            class="py-2 px-0.5 text-center text-[10px] font-black border-r border-slate-200"
                        >
                            <span v-if="col.isSunday" class="text-slate-400">-</span>
                            <span v-else class="text-emerald-800">
                                {{ formatNumber(getColTotalPorsi(col)) }}
                            </span>
                        </td>
                        <td class="py-2.5 px-2 text-center text-slate-700 border-r border-slate-200">
                            -
                        </td>
                        <td class="py-2.5 px-2 text-right text-slate-800 border-r border-slate-200">
                            {{ formatNumber(items.reduce((s, it) => s + (Number(it.porsi_kecil_harian) || 0), 0)) }}
                        </td>
                        <td class="py-2.5 px-2 text-right text-slate-800 border-r border-slate-200">
                            {{ formatNumber(items.reduce((s, it) => s + (Number(it.porsi_besar_harian) || 0), 0)) }}
                        </td>
                        <td class="py-2.5 px-2 text-right text-emerald-800 bg-emerald-100/60 border-r border-slate-200 font-extrabold">
                            {{ formatNumber(items.reduce((s, it) => s + (Number(it.total_porsi_harian) || 0), 0)) }}
                        </td>
                        <td class="py-2.5 px-3 text-right text-emerald-950 bg-emerald-100 font-black">
                            {{ formatNumber(items.reduce((s, it) => s + getRowTotalAkumulasi(it), 0)) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
