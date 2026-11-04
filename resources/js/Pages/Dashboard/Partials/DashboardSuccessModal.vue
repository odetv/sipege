<script setup>
import { CheckCircle2, Sparkles, Building2, User, ArrowRight, ShieldCheck } from "lucide-vue-next";
import Button from "@/Components/ui/Button.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    user: {
        type: Object,
        default: () => ({}),
    },
    message: {
        type: String,
        default: "Selamat! Data telah berhasil di inisialisasi dan siap untuk digunakan.",
    },
});

const emit = defineEmits(["close"]);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6 transition-all"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="relative bg-white rounded-2xl shadow-2xl border border-slate-200/90 w-full max-w-lg p-6 sm:p-8 text-center animate-in fade-in zoom-in-95 duration-200 overflow-hidden"
            >
                <!-- Decorative background elements -->
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-emerald-100/60 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-blue-100/60 rounded-full blur-2xl pointer-events-none"></div>

                <!-- Celebration Icon -->
                <div class="relative mx-auto w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-lg shadow-emerald-500/25 mb-4">
                    <div class="w-full h-full bg-white rounded-[14px] flex items-center justify-center text-emerald-600">
                        <CheckCircle2 class="w-9 h-9" />
                    </div>
                </div>

                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold mb-3">
                    <Sparkles class="w-3.5 h-3.5 text-emerald-600" />
                    <span>Inisialisasi Selesai</span>
                </div>

                <h2 class="text-2xl font-black text-slate-900 tracking-tight mb-2">
                    Inisialisasi Berhasil!
                </h2>

                <p class="text-sm sm:text-base font-semibold text-slate-700 leading-relaxed mb-6">
                    {{ message }}
                </p>

                <!-- Information Card -->
                <div v-if="unitSppg || user" class="bg-slate-50/90 rounded-xl p-4 border border-slate-200/80 text-left space-y-2.5 mb-6 shadow-2xs">
                    <div v-if="unitSppg?.id_sppg" class="flex items-center justify-between text-xs pb-2 border-b border-slate-200">
                        <span class="text-slate-500 font-medium">ID SPPG:</span>
                        <span class="font-mono font-bold text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-200">{{ unitSppg.id_sppg }}</span>
                    </div>
                    <div v-if="unitSppg?.nama" class="flex items-center justify-between text-xs pb-2 border-b border-slate-200">
                        <span class="text-slate-500 font-medium">Unit SPPG:</span>
                        <span class="font-bold text-slate-900 truncate max-w-[220px]">{{ unitSppg.nama }}</span>
                    </div>
                    <div v-if="user?.nama || user?.name" class="flex items-center justify-between text-xs pb-2 border-b border-slate-200">
                        <span class="text-slate-500 font-medium">Kepala SPPG:</span>
                        <span class="font-semibold text-slate-800 truncate max-w-[220px]">{{ user.nama || user.name }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-medium">Status Operasional:</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            {{ unitSppg?.status || 'Operasional' }}
                        </span>
                    </div>
                </div>

                <Button
                    type="button"
                    class="w-full py-3 text-sm font-bold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-md hover:shadow-lg transition-all rounded-xl cursor-pointer flex items-center justify-center gap-2 group"
                    @click="emit('close')"
                >
                    <span>Mulai Gunakan Sistem</span>
                    <ArrowRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                </Button>
            </div>
        </div>
    </Teleport>
</template>

