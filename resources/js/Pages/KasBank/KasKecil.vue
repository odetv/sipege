<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Banknote,
    Clock,
    ArrowLeft,
    CheckCircle2,
    Coins,
    Receipt,
    Repeat,
    ShieldCheck,
    CreditCard,
    Sparkles,
} from "lucide-vue-next";

defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Kas Kecil (Petty Cash) - Kas & Bank SPPG" />

    <AppLayout>
        <div class="space-y-6 w-full pb-16">
            <!-- ─── HEADER UTAMA ─── -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="h-11 w-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm shadow-emerald-600/30">
                        <Banknote class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                                Kas Kecil (Petty Cash Operasional)
                            </h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                Segera Hadir
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pengelolaan kas tunai darurat dapur dan operasional mikro dengan sistem dana tetap (Imprest Fund).
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('kas-bank.rekening-bank')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Rekening Bank</span>
                    </Link>
                    <Link
                        :href="route('kas-bank.saldo-awal')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs"
                    >
                        <span>Saldo Awal</span>
                    </Link>
                </div>
            </div>

            <!-- ─── KARTU FITUR SEGERA HADIR (FULL WIDTH) ─── -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-10 text-center shadow-2xs relative overflow-hidden w-full">
                <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[600px] h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col items-center max-w-4xl mx-auto">
                    <div class="h-16 w-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 shadow-sm border border-emerald-200">
                        <Banknote class="h-8 w-8 text-emerald-600" />
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300 mb-3 shadow-2xs">
                        <Clock class="h-3.5 w-3.5 text-amber-600 animate-spin" style="animation-duration: 4s;" />
                        <span>FITUR SEGERA HADIR</span>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        Sistem Pengelolaan Kas Kecil Dapur & Darurat
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-2 leading-relaxed max-w-2xl">
                        Pengendalian uang tunai di tangan (cash on hand) bendahara SPPG untuk pembelian bumbu pelengkap mendadak, biaya kurir, perbaikan kecil peralatan dapur, dan pengeluaran darurat yang memerlukan pencairan tunai cepat.
                    </p>
                </div>

                <!-- 4 Feature Highlights Spanning Full Width -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full mt-8 text-left">
                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-emerald-300 hover:bg-emerald-50/20 transition-all">
                        <div class="h-9 w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-3">
                            <Coins class="h-4 w-4" />
                        </div>
                        <h4 class="text-xs font-bold text-slate-900">Pembentukan Plafon Kas</h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Penetapan batas nominal maksimum dana petty cash yang dipegang bendahara per periode.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-amber-300 hover:bg-amber-50/20 transition-all">
                        <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-3">
                            <Receipt class="h-4 w-4" />
                        </div>
                        <h4 class="text-xs font-bold text-slate-900">Pencatatan Bon Sementara</h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Pencatatan pengeluaran mikro kas tunai disertai unggah foto struk/nota belanja warung.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-blue-300 hover:bg-blue-50/20 transition-all">
                        <div class="h-9 w-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mb-3">
                            <Repeat class="h-4 w-4" />
                        </div>
                        <h4 class="text-xs font-bold text-slate-900">Reimbursement Otomatis</h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Pengajuan pengisian kembali (replenishment) dari rekening bank utama saat saldo mencapai batas aman.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:border-indigo-300 hover:bg-indigo-50/20 transition-all">
                        <div class="h-9 w-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-3">
                            <ShieldCheck class="h-4 w-4" />
                        </div>
                        <h4 class="text-xs font-bold text-slate-900">Opname Kas Berkala</h4>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Verifikasi fisik uang tunai di brankas bersama pengawas keuangan SPPG secara berkala.
                        </p>
                    </div>
                </div>

                <!-- Preview Info Banner -->
                <div class="mt-8 p-4 rounded-xl bg-slate-50 border border-slate-200 text-left flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                            <ShieldCheck class="h-4 w-4" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-800">Sedang Dalam Tahap Finalisasi Pengembangan</div>
                            <div class="text-[11px] text-slate-500">Fitur ini akan segera diaktifkan pada pembaruan modul keuangan selanjutnya.</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('kas-bank.rekening-bank')"
                            class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition-colors"
                        >
                            Kelola Rekening Bank
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
