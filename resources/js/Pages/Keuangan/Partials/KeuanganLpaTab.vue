<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import { FileCheck2 } from "lucide-vue-next";

defineProps({
    summary: {
        type: Object,
        required: true,
    },
    formatRupiah: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-6">
        <Card className="bg-white border-slate-200 shadow-xs">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div>
                    <CardTitle
                        className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                    >
                        <FileCheck2 class="h-5 w-5 text-primary" />
                        <span
                            >Laporan Pertanggungjawaban Anggaran (LPA) SPPG</span
                        >
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Rekapitulasi serapan anggaran realisasi belanja per
                        periode operasional MBG.
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent className="p-4 sm:p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        class="p-4 rounded-xl border border-slate-200 bg-slate-50/60"
                    >
                        <p
                            class="text-[11px] font-semibold text-slate-500 uppercase"
                        >
                            Total Pagu BGN
                        </p>
                        <h3 class="text-xl font-extrabold text-slate-900 mt-1">
                            {{ formatRupiah(summary.estimasi_bulanan_total) }}
                        </h3>
                    </div>
                    <div
                        class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50"
                    >
                        <p
                            class="text-[11px] font-semibold text-emerald-700 uppercase"
                        >
                            Total Realisasi Belanja
                        </p>
                        <h3 class="text-xl font-extrabold text-emerald-900 mt-1">
                            {{
                                formatRupiah(
                                    summary.estimasi_bulanan_total * 0.85,
                                )
                            }}
                        </h3>
                    </div>
                    <div
                        class="p-4 rounded-xl border border-blue-200 bg-blue-50/50"
                    >
                        <p
                            class="text-[11px] font-semibold text-blue-700 uppercase"
                        >
                            Persentase Serapan
                        </p>
                        <h3 class="text-xl font-extrabold text-blue-900 mt-1">
                            85.4%
                            <span
                                class="text-xs font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded"
                                >Optimal</span
                            >
                        </h3>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
