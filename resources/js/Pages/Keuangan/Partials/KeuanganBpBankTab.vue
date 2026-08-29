<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import { Landmark } from "lucide-vue-next";

defineProps({
    bpBankList: {
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
                        <Landmark class="h-5 w-5 text-primary" />
                        <span>Buku Pembantu (BP) Bank Unit SPPG</span>
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Rekening koran & pencatatan transaksi giro resmi SPPG
                        BGN.
                    </CardDescription>
                </div>
            </CardHeader>
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[750px] text-left text-xs border-collapse"
                >
                    <thead
                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                    >
                        <tr>
                            <th class="p-3.5">No. Referensi</th>
                            <th class="p-3.5">Tanggal</th>
                            <th class="p-3.5">Keterangan Mutasi</th>
                            <th class="p-3.5 text-right">Penerimaan</th>
                            <th class="p-3.5 text-right">Pengeluaran</th>
                            <th class="p-3.5 text-right">Saldo Bank</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="bk in bpBankList"
                            :key="bk.no_ref"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                {{ bk.no_ref }}
                            </td>
                            <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                {{ formatTanggalIndo(bk.tanggal) }}
                            </td>
                            <td class="p-3.5 font-semibold text-slate-900">
                                {{ bk.uraian }}
                            </td>
                            <td
                                class="p-3.5 text-right font-bold text-emerald-700"
                            >
                                {{
                                    bk.penerimaan
                                        ? formatRupiah(bk.penerimaan)
                                        : "-"
                                }}
                            </td>
                            <td
                                class="p-3.5 text-right font-bold text-rose-700"
                            >
                                {{
                                    bk.pengeluaran
                                        ? formatRupiah(bk.pengeluaran)
                                        : "-"
                                }}
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-blue-900"
                            >
                                {{ formatRupiah(bk.saldo) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
