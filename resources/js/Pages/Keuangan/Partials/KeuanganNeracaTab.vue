<script setup>
import {
    Scale,
    Clock,
    TrendingUp,
    Landmark,
    PieChart,
    ShieldCheck,
    Coins,
    BarChart3,
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
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[600px] h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col items-center max-w-4xl mx-auto">
                <div class="h-16 w-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 shadow-sm border border-emerald-200">
                    <Scale class="h-8 w-8 text-emerald-600" />
                </div>

                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300 mb-3 shadow-2xs">
                    <Clock class="h-3.5 w-3.5 text-amber-600 animate-spin" style="animation-duration: 4s;" />
                    <span>FITUR SEGERA HADIR</span>
                </div>

                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    Neraca Keuangan & Posisi Aset SPPG
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-2 leading-relaxed max-w-2xl">
                    Laporan posisi keuangan resmi (Aktiva, Kewajiban, dan Ekuitas Dana) Satuan Pelayanan Program Gizi. Menyajikan ringkasan aset lancar kas & bank, nilai persediaan bahan pangan gudang, dan akumulasi ekuitas unit secara periodik.
                </p>
            </div>

            <!-- 4 Feature Highlights Spanning Full Width -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full mt-8 text-left">
                <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-emerald-300 hover:bg-emerald-50/20 transition-all">
                    <div class="h-9 w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-3">
                        <Landmark class="h-4 w-4" />
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Aset Kas & Bank</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Rekapitulasi saldo kas tunai brankas, rekening bank operasional, dan petty cash aktif.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-amber-300 hover:bg-amber-50/20 transition-all">
                    <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-3">
                        <PieChart class="h-4 w-4" />
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Nilai Persediaan Bahan</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Kalkulasi nilai buku stok bahan kering dan basah yang tersisa di gudang penyimpanan dapur.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-blue-300 hover:bg-blue-50/20 transition-all">
                    <div class="h-9 w-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-3">
                        <TrendingUp class="h-4 w-4" />
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Ekuitas & Akumulasi Dana</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Keseimbangan penerimaan dana operasional pemerintah dengan realisasi penyerapan belanja.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-purple-300 hover:bg-purple-50/20 transition-all">
                    <div class="h-9 w-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mb-3">
                        <ShieldCheck class="h-4 w-4" />
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Audit & Akuntabilitas BGN</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Standar format neraca akuntansi pemerintah yang siap diaudit oleh Badan Gizi Nasional.
                    </p>
                </div>
            </div>

            <!-- Preview Info Banner -->
            <div class="mt-8 p-4 rounded-xl bg-slate-50 border border-slate-200 text-left flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <BarChart3 class="h-4 w-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800">Sedang Dalam Tahap Finalisasi Pengembangan</div>
                        <div class="text-[11px] text-slate-500">Neraca keuangan akan otomatis terhitung dari pergerakan saldo kas, bank, dan stok persediaan.</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('keuangan.penyesuaian')"
                        class="px-3.5 py-1.5 rounded-lg bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-colors shadow-xs"
                    >
                        Penyesuaian Keuangan
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
