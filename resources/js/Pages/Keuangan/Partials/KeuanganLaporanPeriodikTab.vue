<script setup>
import { ref, computed } from "vue";
import { BarChart3, CalendarDays, Download, Printer, TrendingUp, PieChart, Layers } from "lucide-vue-next";

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({}),
    },
    formatRupiah: {
        type: Function,
        default: (val) => "Rp " + Number(val || 0).toLocaleString("id-ID"),
    },
});

const selectedPeriode = ref("bulan_ini");

const dummyLaporanPeriodik = [
    {
        pos: "1. Belanja Bahan Baku Pangan (Bahan Pokok, Lauk, Sayur, Buah)",
        alokasi_pagu: 980000000,
        realisasi_lalu: 450000000,
        realisasi_ini: 245000000,
        total_realisasi: 695000000,
        persentase: 70.9,
    },
    {
        pos: "2. Belanja Operasional & Distribusi (BBM, Logistik, Kebersihan)",
        alokasi_pagu: 120000000,
        realisasi_lalu: 55000000,
        realisasi_ini: 28000000,
        total_realisasi: 83000000,
        persentase: 69.2,
    },
    {
        pos: "3. Fasilitas, Sarpras & Pemeliharaan Alat Masak",
        alokasi_pagu: 60000000,
        realisasi_lalu: 22000000,
        realisasi_ini: 11000000,
        total_realisasi: 33000000,
        persentase: 55.0,
    },
];

const grandPagu = computed(() => dummyLaporanPeriodik.reduce((acc, it) => acc + it.alokasi_pagu, 0));
const grandRealisasi = computed(() => dummyLaporanPeriodik.reduce((acc, it) => acc + it.total_realisasi, 0));
const sisaPagu = computed(() => grandPagu.value - grandRealisasi.value);
const grandPersen = computed(() => grandPagu.value > 0 ? (grandRealisasi.value / grandPagu.value) * 100 : 0);
</script>

<template>
    <div class="space-y-6">
        <!-- Header Banner -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <BarChart3 class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-800 tracking-tight">Laporan Keuangan Periodik SPPG</h2>
                        <p class="text-xs text-slate-500 font-medium">Evaluasi kumulatif serapan anggaran belanja dan realisasi pertanggungjawaban keuangan</p>
                    </div>
                </div>
            </div>

            <!-- Filter Periode & Cetak -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <select
                    v-model="selectedPeriode"
                    class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-bold text-slate-700 shadow-2xs focus:outline-none"
                >
                    <option value="minggu_ini">Minggu Ini</option>
                    <option value="bulan_ini">Bulan Agustus 2026</option>
                    <option value="triwulan_3">Triwulan III 2026</option>
                    <option value="semester_2">Semester II 2026</option>
                </select>
                <button
                    type="button"
                    class="px-3 py-2 text-xs font-bold bg-primary hover:bg-primary/90 text-white rounded-xl cursor-pointer flex items-center gap-1.5 shadow-xs"
                >
                    <Download class="h-3.5 w-3.5" />
                    <span>Ekspor Excel</span>
                </button>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Pagu Anggaran</span>
                <span class="text-base font-mono font-black text-slate-800 mt-1 block">{{ formatRupiah(grandPagu) }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Serapan Realisasi</span>
                <span class="text-base font-mono font-black text-emerald-700 mt-1 block">{{ formatRupiah(grandRealisasi) }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Sisa Alokasi Pagu</span>
                <span class="text-base font-mono font-black text-amber-700 mt-1 block">{{ formatRupiah(sisaPagu) }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Persentase Serapan</span>
                <span class="text-base font-mono font-black text-primary mt-1 block">{{ grandPersen.toFixed(1) }}%</span>
            </div>
        </div>

        <!-- Tabel Rekap Periodik -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                            <th class="p-3.5 text-center w-12">No</th>
                            <th class="py-3.5 px-4">Pos Anggaran Belanja</th>
                            <th class="py-3.5 px-4 text-right">Pagu Alokasi</th>
                            <th class="py-3.5 px-4 text-right">Realisasi S.D Lalu</th>
                            <th class="py-3.5 px-4 text-right">Realisasi Periode Ini</th>
                            <th class="py-3.5 px-4 text-right">Total Realisasi Kumulatif</th>
                            <th class="py-3.5 px-4 text-right">% Serapan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(row, idx) in dummyLaporanPeriodik" :key="idx" class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-3.5 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                            <td class="p-3.5 font-bold text-slate-800">{{ row.pos }}</td>
                            <td class="p-3.5 text-right font-mono font-bold text-slate-700">{{ formatRupiah(row.alokasi_pagu) }}</td>
                            <td class="p-3.5 text-right font-mono text-slate-500">{{ formatRupiah(row.realisasi_lalu) }}</td>
                            <td class="p-3.5 text-right font-mono text-slate-600">{{ formatRupiah(row.realisasi_ini) }}</td>
                            <td class="p-3.5 text-right font-mono font-black text-emerald-800">{{ formatRupiah(row.total_realisasi) }}</td>
                            <td class="p-3.5 text-right font-mono font-bold text-primary">{{ row.persentase }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
