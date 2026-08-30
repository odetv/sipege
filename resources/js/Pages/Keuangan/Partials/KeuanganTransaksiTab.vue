<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import Badge from "@/Components/ui/Badge.vue";
import { CreditCard } from "lucide-vue-next";

defineProps({
    transaksiList: {
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
                        <CreditCard class="h-5 w-5 text-primary" />
                        <span>Pencatatan Riwayat Transaksi Keuangan SPPG</span>
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Seluruh arus kas masuk penerimaan dana dan pengeluaran
                        operasional.
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
                            <th class="py-3.5 px-4">ID Transaksi</th>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Uraian Transaksi</th>
                            <th class="py-3.5 px-4">PJ</th>
                            <th class="py-3.5 px-4 text-right">Nominal</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="trx in transaksiList"
                            :key="trx.id"
                            class="hover:bg-slate-50/60/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                {{ trx.id }}
                            </td>
                            <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                {{ formatTanggalIndo(trx.tanggal) }}
                            </td>
                            <td class="p-3.5">
                                <Badge
                                    variant="outline"
                                    className="font-semibold text-[10.5px]"
                                >
                                    {{ trx.kategori }}
                                </Badge>
                            </td>
                            <td
                                class="p-3.5 font-semibold text-slate-900 max-w-sm"
                            >
                                {{ trx.uraian }}
                            </td>
                            <td class="p-3.5 text-slate-600">{{ trx.pj }}</td>
                            <td
                                class="p-3.5 text-right font-bold text-xs"
                                :class="
                                    trx.tipe === 'masuk'
                                        ? 'text-emerald-700'
                                        : 'text-rose-700'
                                "
                            >
                                {{ trx.tipe === "masuk" ? "+" : "-" }}
                                {{ formatRupiah(trx.jumlah) }}
                            </td>
                            <td class="p-3.5 text-center">
                                <Badge
                                    variant="outline"
                                    className="bg-emerald-50 text-emerald-700 border-emerald-200 text-[10.5px] font-bold"
                                >
                                    {{ trx.status }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
