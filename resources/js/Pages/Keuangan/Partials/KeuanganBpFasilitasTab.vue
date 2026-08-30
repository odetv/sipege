<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import { Building } from "lucide-vue-next";

defineProps({
    bpFasilitasList: {
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
                    <Building class="h-5 w-5 text-primary" />
                    <span
                        >Buku Pembantu (BP) Fasilitas & Sarpras (10%
                        Alokasi)</span
                    >
                </CardTitle>
                <CardDescription class="text-xs sm:text-sm">
                    Sewa dapur, wadah ompreng food grade stainless steel, dan
                    sterilisator peralatan makan.
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
                            <th class="py-3.5 px-4">Kode</th>
                            <th class="py-3.5 px-4">Rincian Pos Fasilitas</th>
                            <th class="py-3.5 px-4 text-right">Pagu Anggaran</th>
                            <th class="py-3.5 px-4 text-right">Realisasi Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="fas in bpFasilitasList"
                            :key="fas.kode"
                            class="hover:bg-slate-50/60/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                {{ fas.kode }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-900">
                                {{ fas.rincian }}
                            </td>
                            <td class="p-3.5 text-right text-slate-700">
                                {{ formatRupiah(fas.pagu_pos) }}
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-indigo-800"
                            >
                                {{ formatRupiah(fas.realisasi) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
