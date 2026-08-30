<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import Badge from "@/Components/ui/Badge.vue";
import { FileSignature } from "lucide-vue-next";

defineProps({
    bapsdList: {
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
                        <FileSignature class="h-5 w-5 text-primary" />
                        <span
                            >Berita Acara Pembayaran & Serah Terima Dokumen
                            (BAPSD)</span
                        >
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Berita acara serah terima penerimaan barang pangan dan
                        tagihan rekanan vendor.
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
                            <th class="py-3.5 px-4">No. BAPSD</th>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Rekanan / Vendor</th>
                            <th class="py-3.5 px-4">
                                Komoditas Barang Diserahterimakan
                            </th>
                            <th class="py-3.5 px-4 text-right">Nilai Pembayaran</th>
                            <th class="py-3.5 px-4 text-center">
                                Status Berita Acara
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="bap in bapsdList"
                            :key="bap.no_bap"
                            class="hover:bg-slate-50/60/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                {{ bap.no_bap }}
                            </td>
                            <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                {{ formatTanggalIndo(bap.tanggal) }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-900">
                                {{ bap.rekanan }}
                            </td>
                            <td class="p-3.5 text-slate-700">
                                {{ bap.komoditas }}
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-emerald-800"
                            >
                                {{ formatRupiah(bap.nominal) }}
                            </td>
                            <td class="p-3.5 text-center">
                                <Badge
                                    variant="outline"
                                    className="bg-emerald-50 text-emerald-700 border-emerald-300 font-bold text-[10.5px]"
                                >
                                    {{ bap.status }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
