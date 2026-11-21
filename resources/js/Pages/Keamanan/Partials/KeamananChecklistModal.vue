<script setup>
import { computed } from 'vue';
import {
    X,
    ShieldCheck,
    ShieldAlert,
    Clock,
    Sun,
    Flame,
    Cloud,
    Wind,
    CloudRain,
    Snowflake,
    CloudLightning,
    Check,
    AlertTriangle,
    FileText,
} from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    detail: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close']);

const dataPengecekan = computed(() => {
    return props.detail?.data_pengecekan || null;
});

const cuaca = computed(() => dataPengecekan.value?.cuaca || '-');
const pengecekanUmum = computed(() => dataPengecekan.value?.pengecekan_umum || []);
const lingkunganKerja = computed(() => dataPengecekan.value?.lingkungan_kerja || []);
const catatanTambahan = computed(() => dataPengecekan.value?.catatan_tambahan || '');

function getCuacaIcon(label) {
    switch (label) {
        case 'Cerah': return Sun;
        case 'Panas': return Flame;
        case 'Berawan': return Cloud;
        case 'Berangin': return Wind;
        case 'Mendung': return Cloud;
        case 'Dingin': return Snowflake;
        case 'Hujan': return CloudRain;
        case 'Badai': return CloudLightning;
        default: return Sun;
    }
}

function formatWaktuWita(val) {
    if (!val) return '-';
    const clean = String(val).replace(/\s*WITA/gi, '').trim();
    return clean ? `${clean} WITA` : '-';
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150"
    >
        <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header Modal -->
            <div class="px-5 py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-400 flex items-center justify-center">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-black tracking-tight">
                            Rincian Checklist Pengecekan Lingkungan
                        </h3>
                        <p class="text-[11px] text-slate-300">
                            <span v-if="detail?.hari_tanggal">{{ detail.hari_tanggal }} • </span>
                            Rentang: <strong>{{ detail?.jam }}</strong>
                            <span v-if="detail?.waktu_cek"> (Pukul {{ formatWaktuWita(detail.waktu_cek) }})</span>
                            • Petugas: <strong>{{ detail?.petugas_nama || '-' }}</strong>
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="emit('close')"
                    class="h-8 w-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Body Modal (Scrollable) -->
            <div class="p-5 overflow-y-auto space-y-4 text-slate-800 text-xs">
                <!-- Status & Cuaca -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Cuaca -->
                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 flex items-center gap-3">
                        <component :is="getCuacaIcon(cuaca)" class="h-8 w-8 text-amber-500 shrink-0" />
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Kondisi Cuaca</span>
                            <span class="text-sm font-black text-slate-900">{{ cuaca }}</span>
                        </div>
                    </div>

                    <!-- Status Kondisi -->
                    <div
                        class="p-3 rounded-2xl border flex items-center gap-3"
                        :class="detail?.kondisi === 'Tidak Aman' ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900'"
                    >
                        <ShieldAlert v-if="detail?.kondisi === 'Tidak Aman'" class="h-8 w-8 text-rose-600 shrink-0" />
                        <ShieldCheck v-else class="h-8 w-8 text-emerald-600 shrink-0" />
                        <div>
                            <span class="text-[10px] font-bold uppercase block opacity-75">Status Keseluruhan</span>
                            <span class="text-sm font-black">{{ detail?.kondisi === 'Tidak Aman' ? '⚠️ Tidak Aman' : '✓ Aman Terkendali' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bagian 1: Pengecekan Umum -->
                <div class="space-y-2">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <span class="w-1.5 h-3 bg-blue-600 rounded-full"></span>
                        <span>Pengecekan Umum</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div
                            v-for="it in pengecekanUmum"
                            :key="it.key"
                            class="p-2.5 rounded-xl border text-xs"
                            :class="[
                                it.status === 'Tidak Aman' || (it.status === 'Ada' && it.key === 'relawan')
                                    ? 'bg-rose-50/80 border-rose-300'
                                    : it.status === 'Ada' && (it.key === 'kunjungan_tamu' || it.key === 'kedatangan_supplier')
                                      ? 'bg-sky-50/80 border-sky-300'
                                      : 'bg-slate-50 border-slate-200',
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800">{{ it.label }}</span>
                                <span
                                    class="text-[10.5px] font-extrabold px-2 py-0.5 rounded-md"
                                    :class="[
                                        it.status === 'Tidak Aman' || (it.status === 'Ada' && it.key === 'relawan')
                                            ? 'bg-rose-600 text-white'
                                            : it.status === 'Ada' && (it.key === 'kunjungan_tamu' || it.key === 'kedatangan_supplier')
                                              ? 'bg-sky-600 text-white'
                                              : 'bg-emerald-100 text-emerald-800',
                                    ]"
                                >
                                    {{ it.status }}
                                </span>
                            </div>
                            <!-- Rincian Kendala Petugas/Relawan jika ada data personil -->
                            <div v-if="it.key === 'relawan' && (it.petugas_nama || it.catatan)" class="mt-1.5 p-2 bg-white/90 rounded-lg border border-rose-200 text-[11px] space-y-1">
                                <div v-if="it.petugas_nama" class="font-bold text-slate-900 flex items-center gap-1.5 flex-wrap">
                                    <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 text-[10px] font-extrabold uppercase">Petugas Terkendala</span>
                                    <span>{{ it.petugas_nama }} - {{ it.petugas_jabatan || 'Petugas SPPG' }}</span>
                                </div>
                                <p v-if="it.catatan || it.uraian" class="text-rose-700 italic">
                                    💬 {{ it.catatan || it.uraian }}
                                </p>
                            </div>
                            <!-- Kunjungan Tamu / Kedatangan Supplier (Bukan Kendala) -->
                            <p
                                v-else-if="it.uraian && (it.key === 'kunjungan_tamu' || it.key === 'kedatangan_supplier')"
                                class="mt-1 text-[11px] text-sky-800 font-medium bg-sky-50 p-1.5 rounded border border-sky-200"
                            >
                                📦 {{ it.uraian }}
                            </p>
                            <p v-else-if="it.uraian" class="mt-1 text-[11px] text-rose-700 italic bg-white/80 p-1.5 rounded border border-rose-200">
                                💬 {{ it.uraian }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bagian 2: Pengecekan Lingkungan Kerja (21 Area) -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <span class="w-1.5 h-3 bg-emerald-600 rounded-full"></span>
                        <span>Pengecekan Lingkungan Kerja (21 Area)</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div
                            v-for="(it, idx) in lingkunganKerja"
                            :key="it.key"
                            class="p-2.5 rounded-xl border text-xs"
                            :class="it.status === 'Tidak Aman' ? 'bg-rose-50 border-rose-300' : 'bg-slate-50 border-slate-200'"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-800">
                                    <span class="text-slate-400 font-mono text-[10px] mr-1">{{ idx + 1 }}.</span>
                                    {{ it.label }}
                                </span>
                                <span
                                    class="text-[10px] font-extrabold px-1.5 py-0.5 rounded"
                                    :class="it.status === 'Tidak Aman' ? 'bg-rose-600 text-white' : 'bg-emerald-100 text-emerald-800'"
                                >
                                    {{ it.status || 'Aman' }}
                                </span>
                            </div>
                            <p v-if="it.uraian" class="mt-1 text-[11px] text-rose-700 italic bg-white/80 p-1.5 rounded border border-rose-200">
                                💬 {{ it.uraian }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Catatan Tambahan -->
                <div v-if="catatanTambahan" class="p-3 bg-amber-50 rounded-2xl border border-amber-200 space-y-1">
                    <span class="text-[10.5px] font-bold text-amber-900 uppercase block">Catatan Tambahan:</span>
                    <p class="text-xs text-amber-950 whitespace-pre-line leading-relaxed">
                        {{ catatanTambahan }}
                    </p>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="p-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-colors cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

