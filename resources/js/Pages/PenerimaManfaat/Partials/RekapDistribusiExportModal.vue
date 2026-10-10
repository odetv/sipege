<script setup>
import { computed } from "vue";
import {
    X,
    Download,
    FileSpreadsheet,
    Calendar,
    Clock,
    Filter,
} from "lucide-vue-next";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";

const props = defineProps({
    show: { type: Boolean, default: false },
    isOpen: { type: Boolean, default: false },
    mode: { type: String, default: "current" },
    initialMode: { type: String, default: "current" },
    startDate: { type: String, default: "" },
    tanggalMulai: { type: String, default: "" },
    endDate: { type: String, default: "" },
    tanggalSelesai: { type: String, default: "" },
    totalDays: { type: Number, default: 28 },
    isExporting: { type: Boolean, default: false },
    periodes: { type: Array, default: () => [] },
    todayStr: { type: String, default: "" },
});

const emit = defineEmits(["close", "confirmExport", "confirm"]);

const isModalVisible = computed(() => props.show || props.isOpen);
const effectiveStartDate = computed(() => props.startDate || props.tanggalMulai || props.todayStr);
const effectiveEndDate = computed(() => props.endDate || props.tanggalSelesai || effectiveStartDate.value);

function onConfirmClick() {
    emit("confirmExport", effectiveStartDate.value, effectiveEndDate.value);
    emit("confirm", effectiveStartDate.value, effectiveEndDate.value);
}
</script>

<template>
    <div
        v-if="isModalVisible"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
    >
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-700 to-teal-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <FileSpreadsheet class="h-5 w-5 text-emerald-300" />
                    <h3 class="text-base font-bold">Ekspor Rekap Distribusi Excel</h3>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 space-y-4">
                <p class="text-xs text-slate-600 leading-relaxed">
                    Dokumen Excel akan digenerate dengan format standar resmi SPPG, mencakup Kop Surat Yayasan, alokasi porsi harian, dan rekapitulasi akumulasi.
                </p>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Rentang Tanggal:</span>
                        <span class="font-bold text-slate-800">{{ formatTanggalIndo(effectiveStartDate) }} s/d {{ formatTanggalIndo(effectiveEndDate) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Jumlah Hari:</span>
                        <span class="font-bold text-emerald-700">{{ totalDays }} Hari</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium">Kop Dokumen:</span>
                        <span class="font-semibold text-slate-700">SPPG Resmi BGN & Yayasan</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-300 transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="onConfirmClick"
                    :disabled="isExporting"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all cursor-pointer disabled:opacity-50"
                >
                    <Download class="h-4 w-4" />
                    <span>{{ isExporting ? 'Mengekspor...' : 'Unduh File Excel' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
