<script setup>
import { ref, computed } from "vue";
import { Calendar, FileText, Download, Printer, Filter, Receipt, CheckCircle2 } from "lucide-vue-next";

const props = defineProps({
    poList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        default: (val) => "Rp " + Number(val || 0).toLocaleString("id-ID"),
    },
    formatTanggalIndo: {
        type: Function,
        default: (val) => val,
    },
});

const selectedDate = ref(new Date().toISOString().split("T")[0]);

const dummyLaporanHarian = [
    {
        kategori: "Belanja Bahan Baku (Bahan Pangan)",
        uraian: "Pengadaan Daging Ayam, Beras, Sayuran & Buah untuk 2.450 Porsi",
        debit: 0,
        kredit: 36750000,
        status: "Lunas / Terbayar",
        dokumen: "PO-20260830-001",
    },
    {
        kategori: "Biaya Operasional",
        uraian: "Operasional Distribusi Logistik Armada SPPG ke 18 Kelompok",
        debit: 0,
        kredit: 1500000,
        status: "Lunas / Terbayar",
        dokumen: "BKO-20260830-002",
    },
    {
        kategori: "Penerimaan Kas / Bank",
        uraian: "Penerimaan Dana Operasional SPPG Tahap II",
        debit: 50000000,
        kredit: 0,
        status: "Masuk Kas",
        dokumen: "BKM-20260830-001",
    },
];

const totalMasuk = computed(() => dummyLaporanHarian.reduce((acc, it) => acc + it.debit, 0));
const totalKeluar = computed(() => dummyLaporanHarian.reduce((acc, it) => acc + it.kredit, 0));
const saldoHarian = computed(() => totalMasuk.value - totalKeluar.value);
</script>

<template>
    <div class="space-y-6">
        <!-- Header Banner -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <Calendar class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-800 tracking-tight">Laporan Keuangan Harian SPPG</h2>
                        <p class="text-xs text-slate-500 font-medium">Rekapitulasi arus kas masuk, pengeluaran belanja bahan, dan operasional harian</p>
                    </div>
                </div>
            </div>

            <!-- Filter Tanggal & Export -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <input
                    v-model="selectedDate"
                    type="date"
                    class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-bold text-slate-700 shadow-2xs focus:outline-none"
                />
                <button
                    type="button"
                    class="px-3 py-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl cursor-pointer flex items-center gap-1.5"
                >
                    <Printer class="h-3.5 w-3.5" />
                    <span>Cetak</span>
                </button>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Penerimaan Kas Harian</span>
                <span class="text-lg font-mono font-black text-emerald-700 mt-1 block">{{ formatRupiah(totalMasuk) }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pengeluaran / Realisasi Harian</span>
                <span class="text-lg font-mono font-black text-rose-700 mt-1 block">{{ formatRupiah(totalKeluar) }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Surplus / Saldo Kas Harian</span>
                <span class="text-lg font-mono font-black text-primary mt-1 block">{{ formatRupiah(saldoHarian) }}</span>
            </div>
        </div>

        <!-- Tabel Rekap Harian -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                            <th class="p-3.5 text-center w-12">No</th>
                            <th class="py-3.5 px-4">Kategori Transaksi</th>
                            <th class="py-3.5 px-4">Uraian / Deskripsi Belanja</th>
                            <th class="py-3.5 px-4">No. Referensi</th>
                            <th class="py-3.5 px-4 text-right">Penerimaan (Debit)</th>
                            <th class="py-3.5 px-4 text-right">Pengeluaran (Kredit)</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(row, idx) in dummyLaporanHarian" :key="idx" class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-3.5 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                            <td class="p-3.5 font-bold text-slate-800">{{ row.kategori }}</td>
                            <td class="p-3.5 text-slate-600 font-medium">{{ row.uraian }}</td>
                            <td class="p-3.5 font-mono text-slate-500">{{ row.dokumen }}</td>
                            <td class="p-3.5 text-right font-mono font-bold text-emerald-700">{{ row.debit > 0 ? formatRupiah(row.debit) : '-' }}</td>
                            <td class="p-3.5 text-right font-mono font-bold text-rose-700">{{ row.kredit > 0 ? formatRupiah(row.kredit) : '-' }}</td>
                            <td class="p-3.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ row.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
