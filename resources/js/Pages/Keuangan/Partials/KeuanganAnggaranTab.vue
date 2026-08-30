<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    CircleDollarSign,
    Wallet,
    Coins,
    CreditCard,
    School,
} from "lucide-vue-next";

defineProps({
    summary: {
        type: Object,
        required: true,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    posAlokasi: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-6">
        <!-- 1. Header Metrics Card -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100"
                    >
                        <CircleDollarSign class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                        >
                            Estimasi Belanja / Hari
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-extrabold text-emerald-800 mt-0.5 truncate"
                        >
                            {{ formatRupiah(summary.estimasi_harian_total) }}
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100"
                    >
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                        >
                            Estimasi Belanja / Bulan
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-extrabold text-blue-800 mt-0.5 truncate"
                        >
                            {{ formatRupiah(summary.estimasi_bulanan_total) }}
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100"
                    >
                        <Coins class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                        >
                            Porsi Kecil (PK)
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-bold text-amber-800 mt-0.5"
                        >
                            {{ formatRupiah(summary.cost_porsi_kecil) }}
                            <span class="text-xs font-normal text-slate-500"
                                >/porsi</span
                            >
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100"
                    >
                        <CreditCard class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                        >
                            Porsi Besar (PB)
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-bold text-indigo-800 mt-0.5"
                        >
                            {{ formatRupiah(summary.cost_porsi_besar) }}
                            <span class="text-xs font-normal text-slate-500"
                                >/porsi</span
                            >
                        </h3>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card className="bg-white border-slate-200 shadow-xs">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
            >
                <CardTitle
                    className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                >
                    <Coins class="h-5 w-5 text-primary" />
                    <span>Struktur Anggaran & Biaya per Porsi MBG</span>
                </CardTitle>
                <CardDescription class="text-xs sm:text-sm">
                    Standar pagu alokasi makanan bergizi gratis BGN berdasarkan
                    target penerima manfaat unit SPPG.
                </CardDescription>
            </CardHeader>
            <CardContent className="p-4 sm:p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        class="p-4 rounded-xl border border-amber-200 bg-amber-50/50 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-extrabold text-amber-900 text-sm">
                                Porsi Kecil (PK) - PAUD/TK/SD Kelas 1-3
                            </h4>
                            <Badge
                                className="bg-amber-100 text-amber-800 font-bold border-amber-200"
                                >Pagu Rp 8.000</Badge
                            >
                        </div>
                        <p class="text-xs text-amber-800/80">
                            Kebutuhan energi: 450 - 550 kkal • Protein: 15 - 18g
                        </p>
                        <div class="text-xs font-bold text-slate-700">
                            Total Sasaran:
                            <span class="text-amber-900 text-sm"
                                >{{ summary.total_porsi_kecil }} Siswa</span
                            >
                        </div>
                    </div>
                    <div
                        class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/50 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-extrabold text-indigo-900 text-sm">
                                Porsi Besar (PB) - SD Kelas 4-6 / SMP / SMA
                            </h4>
                            <Badge
                                className="bg-indigo-100 text-indigo-800 font-bold border-indigo-200"
                                >Pagu Rp 10.000</Badge
                            >
                        </div>
                        <p class="text-xs text-indigo-800/80">
                            Kebutuhan energi: 650 - 750 kkal • Protein: 22 - 27g
                        </p>
                        <div class="text-xs font-bold text-slate-700">
                            Total Sasaran:
                            <span class="text-indigo-900 text-sm"
                                >{{ summary.total_porsi_besar }} Siswa</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Distribusi Alokasi -->
                <div class="space-y-3 pt-2">
                    <h4
                        class="text-xs font-extrabold text-slate-800 uppercase tracking-wider"
                    >
                        Distribusi Alokasi Standar BGN (70% - 20% - 10%)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div
                            v-for="pos in posAlokasi"
                            :key="pos.nama"
                            class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/60 space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-bold text-slate-800"
                                    >{{ pos.nama }}</span
                                >
                                <span
                                    class="text-xs font-black text-primary"
                                    >{{ pos.persen }}%</span
                                >
                            </div>
                            <p class="text-[11px] text-slate-500 leading-snug">
                                {{ pos.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Tabel Anggaran per Kelompok -->
        <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
            >
                <CardTitle
                    className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                >
                    <School class="h-5 w-5 text-primary" />
                    <span>Kebutuhan Anggaran per Kelompok Sasaran</span>
                </CardTitle>
            </CardHeader>
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[700px] text-left text-xs border-collapse"
                >
                    <thead
                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                    >
                        <tr>
                            <th class="py-3.5 px-4">Nama Kelompok</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Porsi Kecil (PK)</th>
                            <th class="py-3.5 px-4">Porsi Besar (PB)</th>
                            <th class="py-3.5 px-4">Biaya Harian</th>
                            <th class="py-3.5 px-4">Biaya Bulanan (20 Hari)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="k in kelompokList"
                            :key="k.id"
                            class="hover:bg-slate-50/60/70 transition-colors"
                        >
                            <td class="p-3.5 font-bold text-slate-900">
                                {{ k.nama_kelompok }}
                            </td>
                            <td class="p-3.5">
                                <Badge
                                    variant="outline"
                                    className="font-semibold text-xs"
                                >
                                    {{ k.kategori }}
                                </Badge>
                            </td>
                            <td class="p-3.5 font-semibold text-amber-700">
                                {{ k.total_porsi_kecil || 0 }} PK
                            </td>
                            <td class="p-3.5 font-semibold text-indigo-700">
                                {{ k.total_porsi_besar || 0 }} PB
                            </td>
                            <td class="p-3.5 font-bold text-emerald-800">
                                {{
                                    formatRupiah(
                                        (k.total_porsi_kecil || 0) *
                                            summary.cost_porsi_kecil +
                                            (k.total_porsi_besar || 0) *
                                                summary.cost_porsi_besar,
                                    )
                                }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-900">
                                {{
                                    formatRupiah(
                                        ((k.total_porsi_kecil || 0) *
                                            summary.cost_porsi_kecil +
                                            (k.total_porsi_besar || 0) *
                                                summary.cost_porsi_besar) *
                                            20,
                                    )
                                }}
                            </td>
                        </tr>
                        <tr v-if="kelompokList.length === 0">
                            <td
                                colspan="6"
                                class="p-8 text-center text-slate-400 font-semibold"
                            >
                                Belum ada data kelompok penerima manfaat.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
