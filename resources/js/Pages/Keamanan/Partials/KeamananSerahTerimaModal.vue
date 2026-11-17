<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, ArrowLeftRight, Users, Clock, Building2, FileText, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    serahTerima: {
        type: Object,
        default: null,
    },
    petugasKeamanan: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const isSubmitting = ref(false);
const errorMsg = ref('');

const form = ref({
    petugas_id: null,
    jenis: 'Masuk Jaga',
    waktu: '',
    catatan: '',
    lokasi_nama: 'Pos Penjagaan SPPG',
    latitude: null,
    longitude: null,
});

watch(
    () => props.show,
    (val) => {
        if (val && props.serahTerima) {
            errorMsg.value = '';
            form.value = {
                petugas_id: props.serahTerima.petugas_id || null,
                jenis: props.serahTerima.jenis || 'Masuk Jaga',
                waktu: props.serahTerima.waktu || '',
                catatan: props.serahTerima.catatan || '',
                lokasi_nama: props.serahTerima.lokasi_nama || 'Pos Penjagaan SPPG',
                latitude: props.serahTerima.latitude || null,
                longitude: props.serahTerima.longitude || null,
            };
        }
    }
);

function submitEdit() {
    if (!props.serahTerima) return;
    if (!form.value.petugas_id) {
        errorMsg.value = 'Silakan pilih petugas keamanan.';
        return;
    }
    if (!form.value.jenis) {
        errorMsg.value = 'Pilih jenis serah terima (Masuk Jaga atau Lepas Jaga).';
        return;
    }
    if (!form.value.waktu?.trim()) {
        errorMsg.value = 'Waktu serah terima wajib diisi.';
        return;
    }

    isSubmitting.value = true;
    errorMsg.value = '';

    router.put(
        route('keamanan.serah-terima.update', props.serahTerima.id),
        form.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                emit('close');
            },
            onError: (err) => {
                isSubmitting.value = false;
                errorMsg.value = Object.values(err)[0] || 'Terjadi kesalahan saat mengupdate data.';
            },
        }
    );
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/75 backdrop-blur-xs overflow-y-auto animate-in fade-in duration-150"
            style="z-index: 99999;"
        >
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden my-auto flex flex-col">
                <!-- Modal Header -->
                <div class="px-5 py-4 bg-indigo-900 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-white/15 text-white flex items-center justify-center font-bold">
                            <ArrowLeftRight class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">
                                Edit Laporan Serah Terima Jaga
                            </h3>
                            <p class="text-[11px] text-indigo-200">
                                Koreksi data serah terima masuk/lepas dinas keamanan
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="h-8 w-8 rounded-lg bg-white/10 hover:bg-white/20 text-indigo-200 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Form Content -->
                <div class="p-5 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <!-- Error Alert -->
                    <div v-if="errorMsg" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl font-medium">
                        {{ errorMsg }}
                    </div>

                    <!-- 1. Petugas Keamanan -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5 uppercase tracking-wider">
                            <Users class="h-3.5 w-3.5 text-indigo-600" />
                            <span>1. Petugas Keamanan</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.petugas_id"
                            class="w-full text-xs rounded-xl border border-slate-300 p-2.5 font-bold text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option :value="null" disabled>-- Pilih Petugas Keamanan --</option>
                            <option v-for="p in petugasKeamanan" :key="p.id" :value="p.id">
                                {{ p.nama }} ({{ p.jabatan || 'Keamanan SPPG' }})
                            </option>
                        </select>
                    </div>

                    <!-- 2. Status Serah Terima (Masuk Jaga / Lepas Jaga) -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5 uppercase tracking-wider">
                            <ArrowLeftRight class="h-3.5 w-3.5 text-indigo-600" />
                            <span>2. Status Serah Terima</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label
                                :class="[
                                    form.jenis === 'Masuk Jaga'
                                        ? 'bg-emerald-50 border-emerald-500 text-emerald-950 font-black ring-2 ring-emerald-500/20'
                                        : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                    'p-2.5 rounded-xl border flex items-center justify-center gap-2 cursor-pointer transition-all text-xs text-center'
                                ]"
                            >
                                <input type="radio" value="Masuk Jaga" v-model="form.jenis" class="sr-only" />
                                <span>Masuk Jaga</span>
                            </label>
                            <label
                                :class="[
                                    form.jenis === 'Lepas Jaga'
                                        ? 'bg-slate-100 border-slate-500 text-slate-900 font-black ring-2 ring-slate-400/20'
                                        : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                    'p-2.5 rounded-xl border flex items-center justify-center gap-2 cursor-pointer transition-all text-xs text-center'
                                ]"
                            >
                                <input type="radio" value="Lepas Jaga" v-model="form.jenis" class="sr-only" />
                                <span>Lepas Jaga</span>
                            </label>
                        </div>
                    </div>

                    <!-- 3. Waktu Pencatatan (WITA) -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5 uppercase tracking-wider">
                            <Clock class="h-3.5 w-3.5 text-indigo-600" />
                            <span>3. Waktu Pencatatan (WITA)</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.waktu"
                            placeholder="Contoh: 18:01 WITA"
                            class="w-full text-xs rounded-xl border border-slate-300 p-2.5 font-mono font-bold text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- 4. Tempat Posisi Serah Terima -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5 uppercase tracking-wider">
                            <Building2 class="h-3.5 w-3.5 text-indigo-600" />
                            <span>4. Tempat Posisi Serah Terima</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.lokasi_nama"
                            placeholder="Contoh: Pos Penjagaan SPPG / Pintu Gerbang Utama..."
                            class="w-full text-xs rounded-xl border border-slate-300 p-2.5 font-medium text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- 5. Catatan Serah Terima -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5 uppercase tracking-wider">
                            <FileText class="h-3.5 w-3.5 text-indigo-600" />
                            <span>5. Catatan / Inventaris Serah Terima</span>
                        </label>
                        <textarea
                            v-model="form.catatan"
                            rows="3"
                            placeholder="Kunci pos lengkap, handy talky standby, inventaris pos aman terkendali..."
                            class="w-full text-xs rounded-xl border border-slate-300 p-2.5 font-medium text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2 shrink-0">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold cursor-pointer transition-colors"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        @click="submitEdit"
                        :disabled="isSubmitting"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold cursor-pointer transition-colors shadow-2xs flex items-center gap-1.5 disabled:opacity-50"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

