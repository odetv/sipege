<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import { Banknote } from "lucide-vue-next";

defineProps({
    bpPettyCashList: {
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
                        <Banknote class="h-5 w-5 text-primary" />
                        <span
                            >Buku Pembantu (BP) Petty Cash / Kas Kecil
                            Dapur</span
                        >
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Pengeluaran operasional tunai tak terduga dapur SPPG
                        harian.
                    </CardDescription>
                </div>
            </CardHeader>
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[700px] text-left text-xs border-collapse"
                >
                    <thead
                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                    >
                        <tr>
                            <th class="py-3.5 px-4">No. Voucher</th>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Uraian Belanja Tunai</th>
                            <th class="py-3.5 px-4 text-right">Kas Masuk</th>
                            <th class="py-3.5 px-4 text-right">Kas Keluar</th>
                            <th class="py-3.5 px-4 text-right">Sisa Kas Kecil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="pc in bpPettyCashList"
                            :key="pc.no_voucher"
                            class="hover:bg-slate-50/60/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                {{ pc.no_voucher }}
                            </td>
                            <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                {{ formatTanggalIndo(pc.tanggal) }}
                            </td>
                            <td class="p-3.5 font-semibold text-slate-900">
                                {{ pc.uraian }}
                            </td>
                            <td
                                class="p-3.5 text-right font-bold text-emerald-700"
                            >
                                {{ pc.masuk ? formatRupiah(pc.masuk) : "-" }}
                            </td>
                            <td
                                class="p-3.5 text-right font-bold text-rose-700"
                            >
                                {{ pc.keluar ? formatRupiah(pc.keluar) : "-" }}
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-amber-900"
                            >
                                {{ formatRupiah(pc.sisa) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
