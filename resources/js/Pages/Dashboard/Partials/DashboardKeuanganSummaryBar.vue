<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import {
    Wallet,
    ArrowDownLeft,
    ArrowUpRight,
    TrendingUp,
    TrendingDown,
    SlidersHorizontal,
    CreditCard,
    Calendar,
    ChevronRight,
    CheckCircle2,
    AlertCircle,
    Landmark,
    Clock,
} from "lucide-vue-next";

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            saldo_akhir: 0,
            saldo_awal: 0,
            has_saldo_awal: false,
            uang_masuk_hari_ini: 0,
            uang_keluar_hari_ini: 0,
            net_hari_ini: 0,
            tanggal_hari_ini: "",
            jumlah_tertunda: 0,
            nominal_tertunda: 0,
        }),
    },
});

const formatRupiah = (val) => {
    const num = Number(val) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatTanggalIndo = (dateStr) => {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
        }).format(d);
    } catch {
        return dateStr;
    }
};

const netToday = computed(() => {
    return Number(props.summary?.net_hari_ini) || 0;
});
</script>

<template>
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 mb-6 shadow-xs transition-all duration-200">
        <!-- Header Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <Wallet class="h-5 w-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-800 tracking-tight">
                            Ringkasan Keuangan SPPG
                        </h3>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Real-Time
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Posisi saldo akhir kas & pergerakan arus kas unit hari ini &bull; {{ formatTanggalIndo(summary.tanggal_hari_ini) }}
                    </p>
                </div>
            </div>

            <!-- Quick Action Links -->
            <div class="flex items-center gap-2 self-end sm:self-auto">
                <Link
                    :href="route('keuangan.penyesuaian')"
                    class="h-8 px-3 rounded-lg text-xs font-bold bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200/80 flex items-center gap-1.5 transition-colors shadow-2xs"
                    title="Buka menu Penyesuaian & Saldo Awal"
                >
                    <SlidersHorizontal class="h-3.5 w-3.5 text-slate-500" />
                    <span>Penyesuaian</span>
                </Link>

                <Link
                    :href="route('keuangan.transaksi')"
                    class="h-8 px-3 rounded-lg text-xs font-bold bg-primary/10 hover:bg-primary/15 text-primary border border-primary/20 flex items-center gap-1.5 transition-colors shadow-2xs"
                    title="Buka Riwayat Transaksi"
                >
                    <CreditCard class="h-3.5 w-3.5" />
                    <span>Transaksi</span>
                    <span
                        v-if="summary.jumlah_tertunda > 0"
                        class="ml-0.5 px-1.5 py-0.2 rounded-full text-[10px] font-black bg-amber-500 text-white"
                        title="Ada transaksi tertunda yang perlu diverifikasi"
                    >
                        {{ summary.jumlah_tertunda }}
                    </span>
                </Link>
            </div>
        </div>

        <!-- 4 Financial Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4">
            <!-- 1. Saldo Akhir (Kas & Bank) -->
            <div class="relative p-4 rounded-xl border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/40 to-blue-50/20 shadow-2xs flex flex-col justify-between overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-1">
                            Saldo Akhir (Kas & Bank)
                        </span>
                        <div class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">
                            {{ formatRupiah(summary.saldo_akhir) }}
                        </div>
                    </div>
                    <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0">
                        <Landmark class="h-4 w-4" />
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                    <span v-if="summary.has_saldo_awal" class="text-slate-500 font-medium">
                        Saldo awal: <strong class="text-slate-700">{{ formatRupiah(summary.saldo_awal) }}</strong>
                    </span>
                    <Link
                        v-else
                        :href="route('keuangan.penyesuaian')"
                        class="text-amber-600 hover:text-amber-700 font-semibold flex items-center gap-1"
                    >
                        <AlertCircle class="h-3 w-3" />
                        <span>Tetapkan Saldo Awal &rarr;</span>
                    </Link>
                    <span class="font-bold text-[10px] uppercase text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">
                        Posisi Kas
                    </span>
                </div>
            </div>

            <!-- 2. Uang Masuk Hari Ini -->
            <div class="relative p-4 rounded-xl border border-emerald-200/80 bg-gradient-to-br from-white via-slate-50/40 to-emerald-50/30 shadow-2xs flex flex-col justify-between overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 block mb-1">
                            Uang Masuk Hari Ini
                        </span>
                        <div class="text-2xl lg:text-3xl font-black text-emerald-700 tracking-tight">
                            + {{ formatRupiah(summary.uang_masuk_hari_ini) }}
                        </div>
                    </div>
                    <div class="h-9 w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 shadow-2xs">
                        <ArrowDownLeft class="h-4 w-4" />
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-emerald-100 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500 font-medium">
                        Penerimaan & penyesuaian masuk
                    </span>
                    <span class="font-bold text-[10px] uppercase text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full">
                        Hari Ini
                    </span>
                </div>
            </div>

            <!-- 3. Uang Keluar Hari Ini -->
            <div class="relative p-4 rounded-xl border border-rose-200/80 bg-gradient-to-br from-white via-slate-50/40 to-rose-50/30 shadow-2xs flex flex-col justify-between overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-rose-800 block mb-1">
                            Uang Keluar Hari Ini
                        </span>
                        <div class="text-2xl lg:text-3xl font-black text-rose-700 tracking-tight">
                            - {{ formatRupiah(summary.uang_keluar_hari_ini) }}
                        </div>
                    </div>
                    <div class="h-9 w-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 shadow-2xs">
                        <ArrowUpRight class="h-4 w-4" />
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-rose-100 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500 font-medium">
                        Belanja & pengeluaran operasional
                    </span>
                    <span class="font-bold text-[10px] uppercase text-rose-800 bg-rose-100 px-2 py-0.5 rounded-full">
                        Hari Ini
                    </span>
                </div>
            </div>

            <!-- 4. Transaksi Tertunda (Perlu Verifikasi) -->
            <Link
                :href="route('keuangan.transaksi') + '?status=Ditunda'"
                class="relative p-4 rounded-xl border transition-all duration-200 shadow-2xs flex flex-col justify-between overflow-hidden group hover:shadow-md cursor-pointer"
                :class="summary.jumlah_tertunda > 0
                    ? 'border-amber-300 bg-gradient-to-br from-white via-amber-50/40 to-amber-100/30 hover:border-amber-400'
                    : 'border-slate-200/90 bg-gradient-to-br from-white via-slate-50/40 to-slate-100/20 hover:border-slate-300'"
                title="Klik untuk membuka Transaksi Tertunda di Menu Keuangan"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <span
                            class="text-[11px] font-bold uppercase tracking-wider block mb-1"
                            :class="summary.jumlah_tertunda > 0 ? 'text-amber-800' : 'text-slate-500'"
                        >
                            Transaksi Tertunda
                        </span>
                        <div
                            class="text-2xl lg:text-3xl font-black tracking-tight"
                            :class="summary.jumlah_tertunda > 0 ? 'text-amber-700' : 'text-slate-700'"
                        >
                            {{ formatRupiah(summary.nominal_tertunda) }}
                        </div>
                    </div>
                    <div
                        class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 shadow-2xs transition-transform group-hover:scale-105"
                        :class="summary.jumlah_tertunda > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'"
                    >
                        <Clock class="h-4 w-4" />
                    </div>
                </div>

                <div
                    class="mt-3 pt-2.5 border-t flex items-center justify-between text-[11px]"
                    :class="summary.jumlah_tertunda > 0 ? 'border-amber-100' : 'border-slate-100'"
                >
                    <span
                        class="font-medium truncate pr-1"
                        :class="summary.jumlah_tertunda > 0 ? 'text-amber-900 font-semibold' : 'text-slate-500'"
                    >
                        {{ summary.jumlah_tertunda > 0 ? `${summary.jumlah_tertunda} transaksi belum disetujui` : 'Tidak ada transaksi tertunda' }}
                    </span>
                    <span
                        class="font-bold text-[10px] uppercase px-2 py-0.5 rounded-full shrink-0 flex items-center gap-1"
                        :class="summary.jumlah_tertunda > 0
                            ? 'text-amber-800 bg-amber-100 animate-pulse'
                            : 'text-slate-600 bg-slate-100'"
                    >
                        <span v-if="summary.jumlah_tertunda > 0" class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                        {{ summary.jumlah_tertunda > 0 ? 'Verifikasi' : 'Nihil' }}
                    </span>
                </div>
            </Link>
        </div>

        <!-- Sub-footer: Arus Kas Bersih Hari Ini -->
        <div class="mt-3.5 pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-slate-500 font-medium">Arus Kas Bersih Hari Ini:</span>
                <span
                    class="font-extrabold px-2 py-0.5 rounded-md text-xs"
                    :class="[
                        netToday > 0
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : (netToday < 0
                                ? 'bg-rose-50 text-rose-700 border border-rose-200'
                                : 'bg-slate-100 text-slate-700')
                    ]"
                >
                    {{ netToday > 0 ? '+' : '' }}{{ formatRupiah(netToday) }}
                </span>
            </div>

            <Link
                :href="route('keuangan.transaksi')"
                class="text-xs font-semibold text-primary hover:text-primary/80 flex items-center gap-1 transition-colors"
            >
                <span>Lihat Buku Kas Umum & Transaksi Lengkap</span>
                <ChevronRight class="h-3.5 w-3.5" />
            </Link>
        </div>
    </div>
</template>

