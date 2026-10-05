<script setup>
import {
    X,
    CreditCard,
    CheckCircle2,
    Download,
    FileSpreadsheet,
    AlertCircle,
    Info,
} from "lucide-vue-next";

const props = defineProps({
    show: { type: Boolean, default: false },
    items: { type: Array, default: () => [] },
    rekDebet: { type: String, default: "" },
    tglTransaksi: { type: String, default: "" },
    remark: { type: String, default: "" },
    totalAmount: { type: Number, default: 0 },
    isGenerating: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "confirmGenerate"]);

function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
    >
        <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[85vh]">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-emerald-900 text-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-md">
                        <CreditCard class="h-5 w-5 text-emerald-300" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold tracking-tight">Pratinjau Batch BNI Direct (Inhouse)</h3>
                        <p class="text-xs text-slate-300">
                            Verifikasi parameter transfer sebelum mengunduh file format .CSV
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4 overflow-y-auto">
                <!-- Summary Parameters Box -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div>
                        <div class="text-[10px] font-bold uppercase text-slate-400">Rekening Debet</div>
                        <div class="font-mono font-bold text-slate-800 mt-0.5">{{ rekDebet || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase text-slate-400">Tgl Transaksi</div>
                        <div class="font-semibold text-slate-800 mt-0.5">{{ tglTransaksi || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase text-slate-400">Total Transaksi</div>
                        <div class="font-bold text-slate-800 mt-0.5">{{ items.length }} Records</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase text-slate-400">Total Amount</div>
                        <div class="font-extrabold text-emerald-700 mt-0.5">{{ formatRupiah(totalAmount) }}</div>
                    </div>
                </div>

                <!-- Remark preview -->
                <div class="p-3 rounded-lg bg-emerald-50/60 border border-emerald-100 flex items-center gap-2 text-xs">
                    <Info class="h-4 w-4 text-emerald-600 shrink-0" />
                    <div>
                        <span class="font-bold text-emerald-900">Remark CSV: </span>
                        <span class="font-mono text-emerald-800">{{ remark }}</span>
                    </div>
                </div>

                <!-- Table Preview -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="max-h-64 overflow-y-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead class="bg-slate-100/80 sticky top-0 border-b border-slate-200 text-[10px] font-bold uppercase text-slate-600">
                                <tr>
                                    <th class="py-2 px-3 text-center w-8">No</th>
                                    <th class="py-2 px-3">Rekening Tujuan</th>
                                    <th class="py-2 px-3">Nama Penerima</th>
                                    <th class="py-2 px-3">Kelompok / Tipe</th>
                                    <th class="py-2 px-3 text-right">Nominal (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(it, idx) in items" :key="idx" class="hover:bg-slate-50">
                                    <td class="py-2 px-3 text-center text-slate-400">{{ idx + 1 }}</td>
                                    <td class="py-2 px-3 font-mono font-medium text-slate-800">{{ it.nomor_rekening }}</td>
                                    <td class="py-2 px-3 font-semibold text-slate-800">{{ it.nama_pemilik_rekening || it.nama_pic }}</td>
                                    <td class="py-2 px-3 text-slate-600">{{ it.nama_kelompok }} ({{ it.tipe_penerima }})</td>
                                    <td class="py-2 px-3 text-right font-extrabold text-emerald-700">{{ formatRupiah(it.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
                <div class="text-xs text-slate-500">
                    Format file sesuai standard BNI Direct Upload Inhouse (20 kolom, CRLF).
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-300 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="$emit('confirmGenerate')"
                        :disabled="isGenerating || items.length === 0"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all cursor-pointer disabled:opacity-50"
                    >
                        <Download class="h-4 w-4" />
                        <span>{{ isGenerating ? 'Mengunduh...' : 'Konfirmasi Unduh CSV' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
