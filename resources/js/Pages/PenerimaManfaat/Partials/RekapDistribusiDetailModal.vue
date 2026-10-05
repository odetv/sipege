<script setup>
import {
    X,
    Building2,
    Users,
    Utensils,
    MapPin,
    Phone,
    Mail,
    Calendar,
    Layers,
} from "lucide-vue-next";

const props = defineProps({
    show: { type: Boolean, default: false },
    item: { type: Object, default: null },
});

const emit = defineEmits(["close"]);

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}
</script>

<template>
    <div
        v-if="show && item"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
    >
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-700 to-teal-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-md">
                        <Building2 class="h-5 w-5 text-emerald-200" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold tracking-tight">{{ item.nama_kelompok }}</h3>
                        <p class="text-xs text-emerald-100/90">
                            {{ item.tipe_identitas || 'NPSN' }}: {{ item.kode_identitas || '-' }} • {{ item.kategori }}
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

            <!-- Modal Content -->
            <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                <!-- Grid Stats Sasaran & Porsi -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Sasaran</div>
                        <div class="text-lg font-extrabold text-slate-800 mt-0.5">{{ formatNumber(item.total_penerima) }}</div>
                        <div class="text-[10px] text-slate-500">Jiwa</div>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Porsi Kecil</div>
                        <div class="text-lg font-extrabold text-emerald-800 mt-0.5">{{ formatNumber(item.porsi_kecil_harian) }}</div>
                        <div class="text-[10px] text-emerald-600">porsi/hari</div>
                    </div>
                    <div class="p-3 rounded-xl bg-teal-50/70 border border-teal-100 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-teal-600">Porsi Besar</div>
                        <div class="text-lg font-extrabold text-teal-800 mt-0.5">{{ formatNumber(item.porsi_besar_harian) }}</div>
                        <div class="text-[10px] text-teal-600">porsi/hari</div>
                    </div>
                    <div class="p-3 rounded-xl bg-purple-50/70 border border-purple-100 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-purple-600">Total Harian</div>
                        <div class="text-lg font-extrabold text-purple-800 mt-0.5">{{ formatNumber(item.total_porsi_harian) }}</div>
                        <div class="text-[10px] text-purple-600">porsi/hari</div>
                    </div>
                </div>

                <!-- Kontak PIC & Kepala -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Informasi Kontak & PIC</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/40">
                            <div class="text-xs font-bold text-slate-800">PIC Distribusi</div>
                            <div class="text-sm font-semibold text-slate-700 mt-0.5">{{ item.nama_pic || '-' }}</div>
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                                <Phone class="h-3.5 w-3.5 text-emerald-600" />
                                <span>{{ item.telepon_pic || '-' }}</span>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/40">
                            <div class="text-xs font-bold text-slate-800">Kepala Satuan / Kader</div>
                            <div class="text-sm font-semibold text-slate-700 mt-0.5">{{ item.nama_kepala || '-' }}</div>
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                                <Phone class="h-3.5 w-3.5 text-blue-600" />
                                <span>{{ item.telepon_kepala || '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alamat & Lokasi -->
                <div class="space-y-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Alamat Lengkap</h4>
                    <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/40 flex items-start gap-2.5">
                        <MapPin class="h-4 w-4 text-emerald-600 mt-0.5 shrink-0" />
                        <div class="text-xs text-slate-700 leading-relaxed">
                            {{ item.alamat_lengkap || `${item.desa_kelurahan}, ${item.kecamatan}` }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-300 transition-colors cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
