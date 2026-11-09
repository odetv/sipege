<script setup>
import {
    FileText,
    Clock,
    FileCheck2,
    BarChart3,
    CheckCircle2,
    ShieldCheck,
    Download,
    Receipt,
    Layers,
    ArrowUpRight,
} from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";

defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <div class="space-y-6 w-full">
        <!-- ─── KARTU FITUR SEGERA HADIR (FULL WIDTH) ─── -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-10 text-center shadow-2xs relative overflow-hidden w-full">
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[600px] h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col items-center max-w-4xl mx-auto">
                <div class="h-16 w-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4 shadow-sm border border-indigo-200">
                    <FileText class="h-8 w-8 text-indigo-600" />
                </div>

                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300 mb-3 shadow-2xs">
                    <Clock class="h-3.5 w-3.5 text-amber-600 animate-spin" style="animation-duration: 4s;" />
                    <span>FITUR SEGERA HADIR</span>
                </div>

                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    Laporan Pertanggungjawaban Dana Harian (LPDH)
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-2 leading-relaxed max-w-2xl">
                    Dokumen pertanggungjawaban resmi harian atas realisasi penggunaan anggaran program Makan Bergizi Gratis (MBG), belanja bahan baku dapur, biaya distribusi per sekolah, dan kelengkapan bukti transaksi.
                </p>
            </div>

            <!-- 4 Feature Highlights Spanning Full Width -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full mt-8 text-left">
                <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-indigo-300 hover:bg-indigo-50/20 transition-all">
                    <div class="h-9 w-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-3">
                        <FileCheck2 class="h-4 w-4" />
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Rekap Belanja Harian</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Akumulasi pengeluaran riil bahan baku & operasional per siklus masak harian secara otomatis.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-emerald-300 hover:bg-emerald-50/20 transition-all">
                    <div class="h-9 w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-3">
                        <BarChart3 class="h-4 w-4" />
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Rasio Porsi & Biaya</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Evaluasi kepatuhan alokasi biaya per porsi (Porsi Kecil Rp 8.000 & Porsi Besar Rp 10.000).
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-amber-300 hover:bg-amber-50/20 transition-all">
                    <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-3">
                        <Receipt class="h-4 w-4" />
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Lampiran Bukti Pengeluaran</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Penggabungan otomatis nota, kuitansi rekanan, dan berita acara serah terima bahan makanan.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-purple-300 hover:bg-purple-50/20 transition-all">
                    <div class="h-9 w-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mb-3">
                        <ShieldCheck class="h-4 w-4" />
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Pengesahan Digital SPPG</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Format PDF siap cetak dengan tanda tangan Kepala SPPG, Akuntan, dan Pengawas Keuangan.
                    </p>
                </div>
            </div>

            <!-- Preview Info Banner -->
            <div class="mt-8 p-4 rounded-xl bg-slate-50 border border-slate-200 text-left flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                        <Layers class="h-4 w-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800">Sedang Dalam Tahap Finalisasi Pengembangan</div>
                        <div class="text-[11px] text-slate-500">Laporan harian ini akan terintegrasi langsung dengan menu Transaksi dan Work Order Gizi.</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('keuangan.transaksi')"
                        class="px-3.5 py-1.5 rounded-lg bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-colors shadow-xs"
                    >
                        Buka Buku Transaksi
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
