<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import { Sliders } from "lucide-vue-next";

defineProps({
    bpOperasionalList: {
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
                    <Sliders class="h-5 w-5 text-primary" />
                    <span>Buku Pembantu (BP) Biaya Operasional (20% Alokasi)</span>
                </CardTitle>
                <CardDescription class="text-xs sm:text-sm">
                    Insentif juru masak, BBM armada distribusi, utilitas gas LPG,
                    listrik, dan air bersih.
                </CardDescription>
            </CardHeader>
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[650px] text-left text-xs border-collapse"
                >
                    <thead
                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                    >
                        <tr>
                            <th class="p-3.5">Kode</th>
                            <th class="p-3.5">Rincian Pos Operasional</th>
                            <th class="p-3.5 text-right">Pagu Anggaran</th>
                            <th class="p-3.5 text-right">Realisasi Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="op in bpOperasionalList"
                            :key="op.kode"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                {{ op.kode }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-900">
                                {{ op.rincian }}
                            </td>
                            <td class="p-3.5 text-right text-slate-700">
                                {{ formatRupiah(op.pagu_pos) }}
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-amber-800"
                            >
                                {{ formatRupiah(op.realisasi) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
