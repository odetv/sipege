<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import { BookOpen } from "lucide-vue-next";

defineProps({
    bkuList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        required: true,
    },
    formatTanggalIndo: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-6">
        <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div>
                    <CardTitle
                        className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                    >
                        <BookOpen class="h-5 w-5 text-primary" />
                        <span>Buku Kas Umum (BKU) SPPG</span>
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Pembukuan kas induk mencatat seluruh mutasi debit,
                        kredit, dan saldo kumulatif.
                    </CardDescription>
                </div>
            </CardHeader>
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[800px] text-left text-xs border-collapse"
                >
                    <thead
                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                    >
                        <tr>
                            <th class="p-3.5">No. Bukti</th>
                            <th class="p-3.5">Tanggal</th>
                            <th class="p-3.5">Uraian Transaksi</th>
                            <th class="p-3.5 text-right">Penerimaan (Debit)</th>
                            <th class="p-3.5 text-right">
                                Pengeluaran (Kredit)
                            </th>
                            <th class="p-3.5 text-right">Saldo Kas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="b in bkuList"
                            :key="b.no_bukti"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                {{ b.no_bukti }}
                            </td>
                            <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                {{ formatTanggalIndo(b.tanggal) }}
                            </td>
                            <td class="p-3.5 font-semibold text-slate-900">
                                {{ b.uraian }}
                            </td>
                            <td
                                class="p-3.5 text-right font-bold text-emerald-700"
                            >
                                {{ b.debit ? formatRupiah(b.debit) : "-" }}
                            </td>
                            <td
                                class="p-3.5 text-right font-bold text-rose-700"
                            >
                                {{ b.kredit ? formatRupiah(b.kredit) : "-" }}
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-blue-900"
                            >
                                {{ formatRupiah(b.saldo) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
