<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import Badge from "@/Components/ui/Badge.vue";
import { Package } from "lucide-vue-next";

defineProps({
    bpBahanBakuList: {
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
        <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
            >
                <CardTitle
                    className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                >
                    <Package class="h-5 w-5 text-primary" />
                    <span
                        >Buku Pembantu (BP) Belanja Bahan Baku (70%
                        Alokasi)</span
                    >
                </CardTitle>
                <CardDescription class="text-xs sm:text-sm">
                    Rincian belanja bahan makanan pokok, lauk hewani/nabati,
                    sayuran, dan buah-buahan.
                </CardDescription>
            </CardHeader>
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[700px] text-left text-xs border-collapse"
                >
                    <thead
                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                    >
                        <tr>
                            <th class="py-3.5 px-4">Kode Pos</th>
                            <th class="py-3.5 px-4">Kelompok Bahan</th>
                            <th class="py-3.5 px-4">Item Komoditas Pangan</th>
                            <th class="py-3.5 px-4 text-right">Pagu Pos</th>
                            <th class="py-3.5 px-4 text-right">Realisasi Belanja</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="bb in bpBahanBakuList"
                            :key="bb.kode"
                            class="hover:bg-slate-50/60/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                {{ bb.kode }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-900">
                                {{ bb.kelompok }}
                            </td>
                            <td class="p-3.5 text-slate-600">{{ bb.item }}</td>
                            <td
                                class="p-3.5 text-right font-medium text-slate-700"
                            >
                                {{ formatRupiah(bb.pagu_pos) }}
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-emerald-800"
                            >
                                {{ formatRupiah(bb.realisasi) }}
                            </td>
                            <td class="p-3.5 text-center">
                                <Badge
                                    variant="outline"
                                    className="bg-emerald-50 text-emerald-700 border-emerald-200 font-bold text-[10.5px]"
                                >
                                    {{ bb.status }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
