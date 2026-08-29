<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import { ShieldCheck } from "lucide-vue-next";

defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    formatTanggalIndo: {
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
                        <ShieldCheck class="h-5 w-5 text-primary" />
                        <span>Surat Pernyataan Tanggung Jawab (SPTJ) Belanja</span>
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Dokumen formal pertanggungjawaban mutlak atas penggunaan
                        dana operasional SPPG.
                    </CardDescription>
                </div>
            </CardHeader>
            <CardContent
                className="p-6 sm:p-8 space-y-6 max-w-4xl mx-auto border border-slate-200/80 rounded-2xl bg-white my-4 shadow-xs"
            >
                <div class="text-center space-y-1 border-b border-slate-200 pb-4">
                    <h3 class="text-base font-black text-slate-900 uppercase">
                        SURAT PERNYATAAN TANGGUNG JAWAB BELANJA (SPTJ)
                    </h3>
                    <p class="text-xs text-slate-500 font-mono">
                        Nomor: SPTJ/SPPG-BGN/{{
                            new Date().getFullYear()
                        }}/VIII/001
                    </p>
                </div>
                <div
                    class="text-xs sm:text-sm text-slate-700 leading-relaxed space-y-3"
                >
                    <p>Yang bertanda tangan di bawah ini:</p>
                    <div class="pl-4 space-y-1 font-semibold text-slate-900">
                        <p>
                            Nama:
                            <span class="font-normal text-slate-700">{{
                                user.name || "Koordinator SPPG"
                            }}</span>
                        </p>
                        <p>
                            Unit SPPG:
                            <span class="font-normal text-slate-700">{{
                                unitSppg?.nama_unit || "Unit Layanan SPPG"
                            }}</span>
                        </p>
                        <p>
                            Alamat:
                            <span class="font-normal text-slate-700">{{
                                unitSppg?.alamat || "Lokasi Dapur SPPG"
                            }}</span>
                        </p>
                    </div>
                    <p class="pt-2">
                        Menyatakan dengan sesungguhnya bahwa seluruh belanja
                        bahan baku, biaya operasional dapur, dan fasilitas
                        penyediaan Makanan Bergizi Gratis (MBG) telah
                        dilaksanakan sesuai dengan petunjuk teknis Badan Gizi
                        Nasional (BGN) dan didukung dengan bukti
                        pertanggungjawaban yang lengkap dan sah.
                    </p>
                </div>
                <div class="flex justify-end pt-6">
                    <div
                        class="text-center space-y-8 text-xs font-bold text-slate-800"
                    >
                        <p>
                            {{ unitSppg?.kabupaten || "Wilayah SPPG" }},
                            {{ formatTanggalIndo(new Date().toISOString()) }}
                        </p>
                        <div
                            class="w-32 h-14 border border-dashed border-slate-300 rounded flex items-center justify-center text-[10px] text-slate-400 mx-auto"
                        >
                            Materai Rp 10.000
                        </div>
                        <p class="underline">
                            {{ user.name || "Koordinator SPPG" }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
