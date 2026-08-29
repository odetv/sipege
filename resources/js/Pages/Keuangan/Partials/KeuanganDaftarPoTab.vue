<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import Badge from "@/Components/ui/Badge.vue";
import { Receipt } from "lucide-vue-next";

defineProps({
    poList: {
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
                        <Receipt class="h-5 w-5 text-primary" />
                        <span
                            >Daftar Purchase Order (PO) & Realisasi Belanja
                            Bahan</span
                        >
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Pesanan pembelian bahan baku makanan bergizi dari
                        formulasi menu Work Order Ahli Gizi.
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
                            <th class="p-3.5">No. PO & WO</th>
                            <th class="p-3.5">Tanggal</th>
                            <th class="p-3.5">Menu Sasaran</th>
                            <th class="p-3.5">Vendor / Rekanan</th>
                            <th class="p-3.5 text-center">Items</th>
                            <th class="p-3.5 text-right">Total Nominal</th>
                            <th class="p-3.5 text-center">Status PO</th>
                            <th class="p-3.5 text-center">Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="po in poList"
                            :key="po.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                <div>{{ po.id }}</div>
                                <div
                                    class="text-[10px] text-slate-400 font-normal"
                                >
                                    {{ po.wo_id }}
                                </div>
                            </td>
                            <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                {{ formatTanggalIndo(po.tanggal) }}
                            </td>
                            <td class="p-3.5 font-semibold text-slate-900">
                                {{ po.menu }}
                            </td>
                            <td class="p-3.5 text-slate-700 font-medium">
                                {{ po.vendor }}
                            </td>
                            <td class="p-3.5 text-center font-bold">
                                {{ po.items_count }} Item
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-emerald-800 text-xs"
                            >
                                {{ formatRupiah(po.total_nominal) }}
                            </td>
                            <td class="p-3.5 text-center">
                                <Badge
                                    variant="outline"
                                    :className="
                                        po.status_po === 'Terverifikasi'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold text-[10.5px]'
                                            : 'bg-amber-50 text-amber-700 border-amber-300 font-bold text-[10.5px]'
                                    "
                                >
                                    {{ po.status_po }}
                                </Badge>
                            </td>
                            <td class="p-3.5 text-center">
                                <Badge
                                    variant="outline"
                                    className="bg-slate-100 text-slate-700 border-slate-300 text-[10.5px]"
                                >
                                    {{ po.status_bayar }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
