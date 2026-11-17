<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    X,
    LogOut,
    LogIn,
    MapPin,
    Navigation,
    Clock,
    Calendar,
    Users,
    FileText,
    CheckCircle2,
    Map,
    Shield,
    ArrowRight,
    Hourglass,
    Edit3,
} from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    mode: {
        type: String,
        default: 'create', // 'create' | 'kembali' | 'edit'
    },
    izin: {
        type: Object,
        default: null,
    },
    tanggal: {
        type: String,
        default: () => new Date().toISOString().split('T')[0],
    },
    shift: {
        type: String,
        default: '24 Jam',
    },
    petugasKeamanan: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'open-map']);

const isSubmitting = ref(false);
const errorMsg = ref('');

// Form Keluar (Fase 1 Izin, Fase 2 Keluar, Fase 3 Tujuan)
const formKeluar = ref({
    petugas_id: null,
    petugas_nama: '',
    keperluan: '',
    tanggal_izin: props.tanggal,
    waktu_izin: '',
    lokasi_izin: 'Pos Penjagaan SPPG',
    lat_izin: null,
    lng_izin: null,
    tanggal_keluar: props.tanggal,
    waktu_keluar: '',
    lokasi_awal: 'Pos Penjagaan SPPG',
    lat_awal: null,
    lng_awal: null,
    lokasi_tujuan: '',
    lat_tujuan: null,
    lng_tujuan: null,
    waktu_sampai_tujuan: '',
});

// Form Kembali (Fase 4 Kembali + Pelaporan)
const formKembali = ref({
    tanggal_kembali: props.tanggal,
    waktu_kembali: '',
    lokasi_kembali: 'Pos Penjagaan SPPG',
    lat_kembali: null,
    lng_kembali: null,
    laporan_kegiatan: '',
});

function getNowWitaTime() {
    const d = new Date();
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${hh}.${mm} WITA`;
}

watch(
    () => props.show,
    (val) => {
        if (val) {
            errorMsg.value = '';
            const nowTime = getNowWitaTime();
            if (props.mode === 'create') {
                const defaultPetugas = props.petugasKeamanan[0] || null;
                formKeluar.value = {
                    petugas_id: defaultPetugas ? defaultPetugas.id : null,
                    petugas_nama: defaultPetugas ? defaultPetugas.nama : '',
                    keperluan: '',
                    tanggal_izin: props.tanggal,
                    waktu_izin: nowTime,
                    lokasi_izin: 'Pos Penjagaan SPPG',
                    lat_izin: null,
                    lng_izin: null,
                    tanggal_keluar: props.tanggal,
                    waktu_keluar: nowTime,
                    lokasi_awal: 'Pos Penjagaan SPPG',
                    lat_awal: null,
                    lng_awal: null,
                    lokasi_tujuan: '',
                    lat_tujuan: null,
                    lng_tujuan: null,
                    waktu_sampai_tujuan: '',
                };
            } else if (props.mode === 'kembali' && props.izin) {
                formKembali.value = {
                    tanggal_kembali: props.tanggal,
                    waktu_kembali: nowTime,
                    lokasi_kembali: 'Pos Penjagaan SPPG',
                    lat_kembali: null,
                    lng_kembali: null,
                    laporan_kegiatan: '',
                };
            } else if (props.mode === 'edit' && props.izin) {
                const iz = props.izin;
                formKeluar.value = {
                    petugas_id: iz.petugas_id,
                    petugas_nama: iz.petugas_nama || '',
                    keperluan: iz.keperluan || '',
                    tanggal_izin: iz.tanggal_izin || props.tanggal,
                    waktu_izin: iz.waktu_izin || '',
                    lokasi_izin: iz.lokasi_izin || 'Pos Penjagaan SPPG',
                    lat_izin: iz.lat_izin,
                    lng_izin: iz.lng_izin,
                    tanggal_keluar: iz.tanggal_keluar || props.tanggal,
                    waktu_keluar: iz.waktu_keluar || '',
                    lokasi_awal: iz.lokasi_awal || 'Pos Penjagaan SPPG',
                    lat_awal: iz.lat_awal,
                    lng_awal: iz.lng_awal,
                    lokasi_tujuan: iz.lokasi_tujuan || '',
                    lat_tujuan: iz.lat_tujuan,
                    lng_tujuan: iz.lng_tujuan,
                    waktu_sampai_tujuan: iz.waktu_sampai_tujuan || '',
                };
                formKembali.value = {
                    status: iz.status || 'Sedang Keluar',
                    tanggal_kembali: iz.tanggal_kembali || props.tanggal,
                    waktu_kembali: iz.waktu_kembali || '',
                    lokasi_kembali: iz.lokasi_kembali || 'Pos Penjagaan SPPG',
                    lat_kembali: iz.lat_kembali,
                    lng_kembali: iz.lng_kembali,
                    laporan_kegiatan: iz.laporan_kegiatan || '',
                };
            }
        }
    }
);

function onPetugasChange() {
    const found = props.petugasKeamanan.find((p) => p.id === formKeluar.value.petugas_id);
    if (found) {
        formKeluar.value.petugas_nama = found.nama;
    }
}

// Deteksi Lokasi (GPS)
const locatingField = ref(null);
function fetchLocation(target) {
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung deteksi lokasi.');
        return;
    }
    locatingField.value = target;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = Number(pos.coords.latitude.toFixed(7));
            const lng = Number(pos.coords.longitude.toFixed(7));
            if (target === 'izin') {
                formKeluar.value.lat_izin = lat;
                formKeluar.value.lng_izin = lng;
            } else if (target === 'awal') {
                formKeluar.value.lat_awal = lat;
                formKeluar.value.lng_awal = lng;
            } else if (target === 'tujuan') {
                formKeluar.value.lat_tujuan = lat;
                formKeluar.value.lng_tujuan = lng;
            } else if (target === 'kembali') {
                formKembali.value.lat_kembali = lat;
                formKembali.value.lng_kembali = lng;
            }
            locatingField.value = null;
        },
        (err) => {
            locatingField.value = null;
            alert(`Gagal mengambil lokasi: ${err.message || 'Izin akses lokasi ditolak.'}`);
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

// Buka peta untuk melihat / memilih titik
function openMapSelector(target) {
    let item = null;
    let title = '';
    if (target === 'izin') {
        title = 'Titik Lokasi Pengajuan Izin';
        item = {
            latitude: formKeluar.value.lat_izin,
            longitude: formKeluar.value.lng_izin,
            lokasi_nama: formKeluar.value.lokasi_izin,
            _targetField: 'izin',
        };
    } else if (target === 'awal') {
        title = 'Titik Lokasi Keluar Penjagaan';
        item = {
            latitude: formKeluar.value.lat_awal,
            longitude: formKeluar.value.lng_awal,
            lokasi_nama: formKeluar.value.lokasi_awal,
            _targetField: 'awal',
        };
    } else if (target === 'tujuan') {
        title = 'Titik Tempat Izin / Tujuan';
        item = {
            latitude: formKeluar.value.lat_tujuan,
            longitude: formKeluar.value.lng_tujuan,
            lokasi_nama: formKeluar.value.lokasi_tujuan,
            _targetField: 'tujuan',
        };
    } else if (target === 'kembali') {
        title = 'Titik Lokasi Saat Kembali ke Penjagaan';
        item = {
            latitude: formKembali.value.lat_kembali,
            longitude: formKembali.value.lng_kembali,
            lokasi_nama: formKembali.value.lokasi_kembali,
            _targetField: 'kembali',
        };
    }
    emit('open-map', { item, title, target });
}

// Menangani kembalian dari map modal di parent
function updateCoordsFromMap({ target, latitude, longitude, lokasi_nama }) {
    if (target === 'izin') {
        formKeluar.value.lat_izin = latitude;
        formKeluar.value.lng_izin = longitude;
        if (lokasi_nama) formKeluar.value.lokasi_izin = lokasi_nama;
    } else if (target === 'awal') {
        formKeluar.value.lat_awal = latitude;
        formKeluar.value.lng_awal = longitude;
        if (lokasi_nama) formKeluar.value.lokasi_awal = lokasi_nama;
    } else if (target === 'tujuan') {
        formKeluar.value.lat_tujuan = latitude;
        formKeluar.value.lng_tujuan = longitude;
        if (lokasi_nama) formKeluar.value.lokasi_tujuan = lokasi_nama;
    } else if (target === 'kembali') {
        formKembali.value.lat_kembali = latitude;
        formKembali.value.lng_kembali = longitude;
        if (lokasi_nama) formKembali.value.lokasi_kembali = lokasi_nama;
    }
}

defineExpose({
    updateCoordsFromMap,
});

// Hitung perkiraan durasi waktu secara realtime saat di form kembali
const kalkulasiDurasiRealtime = computed(() => {
    if (!props.izin) return null;

    const tKeluarStr = props.izin.waktu_keluar || '';
    const tKembaliStr = formKembali.value.waktu_kembali || '';

    function parseToMinutes(tStr) {
        if (!tStr) return null;
        const clean = tStr.replace(/[^0-9:\.]/g, '').replace('.', ':');
        const parts = clean.split(':');
        if (parts.length >= 2) {
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }
        return null;
    }

    const mKeluar = parseToMinutes(tKeluarStr);
    const mKembali = parseToMinutes(tKembaliStr);

    if (mKeluar !== null && mKembali !== null) {
        let diff = mKembali - mKeluar;
        if (diff < 0) diff += 1440; // Melewati tengah malam
        const jam = Math.floor(diff / 60);
        const menit = diff % 60;
        let text = '';
        if (jam > 0) text += `${jam} Jam `;
        text += `${menit} Menit`;
        return {
            totalMenit: diff,
            text: text,
        };
    }
    return null;
});

// Submit Form Keluar
function submitKeluar() {
    if (!formKeluar.value.petugas_id) {
        errorMsg.value = 'Silakan pilih petugas keamanan yang akan izin keluar.';
        return;
    }
    if (!formKeluar.value.keperluan.trim()) {
        errorMsg.value = 'Uraian keperluan izin keluar wajib diisi.';
        return;
    }
    if (!formKeluar.value.lokasi_tujuan.trim()) {
        errorMsg.value = 'Tempat izin / tujuan wajib diisi.';
        return;
    }

    // Default jika titik izin kosong, samakan dengan titik awal
    if (!formKeluar.value.lat_izin && formKeluar.value.lat_awal) {
        formKeluar.value.lat_izin = formKeluar.value.lat_awal;
        formKeluar.value.lng_izin = formKeluar.value.lng_awal;
    }

    isSubmitting.value = true;
    errorMsg.value = '';

    if (props.mode === 'edit' && props.izin) {
        router.put(
            route('keamanan.izin.update', props.izin.id),
            {
                petugas_id: formKeluar.value.petugas_id,
                keperluan: formKeluar.value.keperluan,
                lokasi_tujuan: formKeluar.value.lokasi_tujuan,
                waktu_keluar: formKeluar.value.waktu_keluar,
                lokasi_awal: formKeluar.value.lokasi_awal,
                lat_awal: formKeluar.value.lat_awal,
                lng_awal: formKeluar.value.lng_awal,
                lat_tujuan: formKeluar.value.lat_tujuan,
                lng_tujuan: formKeluar.value.lng_tujuan,
                status: formKembali.value.status || props.izin.status || 'Sedang Keluar',
                waktu_kembali: formKembali.value.waktu_kembali,
                lokasi_kembali: formKembali.value.lokasi_kembali,
                lat_kembali: formKembali.value.lat_kembali,
                lng_kembali: formKembali.value.lng_kembali,
                laporan_kegiatan: formKembali.value.laporan_kegiatan,
            },
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
        return;
    }

    router.post(
        route('keamanan.izin.store'),
        {
            tanggal: props.tanggal,
            shift: props.shift,
            petugas_id: formKeluar.value.petugas_id,
            keperluan: formKeluar.value.keperluan,
            tanggal_izin: formKeluar.value.tanggal_izin,
            waktu_izin: formKeluar.value.waktu_izin,
            lokasi_izin: formKeluar.value.lokasi_izin,
            lat_izin: formKeluar.value.lat_izin,
            lng_izin: formKeluar.value.lng_izin,
            tanggal_keluar: formKeluar.value.tanggal_keluar,
            waktu_keluar: formKeluar.value.waktu_keluar,
            lokasi_awal: formKeluar.value.lokasi_awal,
            lat_awal: formKeluar.value.lat_awal,
            lng_awal: formKeluar.value.lng_awal,
            lokasi_tujuan: formKeluar.value.lokasi_tujuan,
            lat_tujuan: formKeluar.value.lat_tujuan,
            lng_tujuan: formKeluar.value.lng_tujuan,
            waktu_sampai_tujuan: formKeluar.value.waktu_sampai_tujuan,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                emit('close');
            },
            onError: (err) => {
                isSubmitting.value = false;
                errorMsg.value = Object.values(err)[0] || 'Terjadi kesalahan saat menyimpan.';
            },
        }
    );
}

// Submit Form Kembali
function submitKembali() {
    if (!formKembali.value.laporan_kegiatan.trim()) {
        errorMsg.value = 'Pelaporan kegiatan / uraian hasil selama izin keluar wajib diisi.';
        return;
    }

    isSubmitting.value = true;
    errorMsg.value = '';

    router.put(
        route('keamanan.izin.kembali', props.izin.id),
        {
            tanggal_kembali: formKembali.value.tanggal_kembali,
            waktu_kembali: formKembali.value.waktu_kembali,
            lokasi_kembali: formKembali.value.lokasi_kembali,
            lat_kembali: formKembali.value.lat_kembali,
            lng_kembali: formKembali.value.lng_kembali,
            laporan_kegiatan: formKembali.value.laporan_kegiatan,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                emit('close');
            },
            onError: (err) => {
                isSubmitting.value = false;
                errorMsg.value = Object.values(err)[0] || 'Terjadi kesalahan saat menyimpan.';
            },
        }
    );
}

// Preset keperluan
function setKeperluan(text) {
    formKeluar.value.keperluan = text;
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/75 backdrop-blur-xs overflow-y-auto"
            style="z-index: 99999;"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden my-auto flex flex-col max-h-[92vh]"
            >
                <!-- Modal Header -->
                <div
                    class="px-5 py-4 text-white flex items-center justify-between shrink-0"
                    :class="mode === 'create' ? 'bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800' : (mode === 'edit' ? 'bg-gradient-to-r from-indigo-700 via-blue-800 to-slate-900' : 'bg-gradient-to-r from-emerald-700 via-teal-800 to-emerald-900')"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0"
                        >
                            <LogOut v-if="mode === 'create'" class="h-5 w-5" />
                            <Edit3 v-else-if="mode === 'edit'" class="h-5 w-5" />
                            <LogIn v-else class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold">
                                {{ mode === 'create' ? 'Catat Izin Keluar Penjagaan (Fase Izin & Titik Lokasi)' : (mode === 'edit' ? 'Edit Laporan Izin Keluar Penjagaan' : 'Konfirmasi Kembali ke Penjagaan & Pelaporan Petugas') }}
                            </h3>
                            <p class="text-[11px] text-white/80">
                                {{ mode === 'create' ? 'Merekam waktu izin, waktu keluar, titik lokasi awal, dan tujuan' : (mode === 'edit' ? 'Perbarui data izin keluar, rincian keperluan, titik lokasi, dan status kembali' : 'Menghitung durasi waktu izin hingga kembali, posisi pos, dan laporan hasil') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="h-8 w-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Pesan Error -->
                <div v-if="errorMsg" class="mx-5 mt-4 p-3 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-800 font-bold flex items-center gap-2">
                    <span>⚠️ {{ errorMsg }}</span>
                </div>

                <!-- Modal Body -->
                <div class="p-5 sm:p-6 overflow-y-auto space-y-4 text-xs">
                    <!-- MODE 1 & EDIT: FORM IZIN KELUAR -->
                    <template v-if="mode === 'create' || mode === 'edit'">
                        <!-- Petugas Keamanan (Hanya 2 Petugas Keamanan) -->
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">
                                Petugas Keamanan yang Izin Keluar <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="formKeluar.petugas_id"
                                @change="onPetugasChange"
                                class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:ring-1 focus:ring-amber-500 p-2.5"
                            >
                                <option :value="null">-- Pilih Petugas Keamanan --</option>
                                <option
                                    v-for="p in petugasKeamanan"
                                    :key="p.id"
                                    :value="p.id"
                                >
                                    {{ p.nama }} ({{ p.jabatan || 'Keamanan' }})
                                </option>
                            </select>
                            <p class="text-[10.5px] text-slate-400 mt-1">
                                Pilihan khusus 2 petugas divisi keamanan SPPG.
                            </p>
                        </div>

                        <!-- FASE 1: PENGAJUAN / WAKTU IZIN -->
                        <div class="p-3.5 bg-blue-50/70 rounded-xl border border-blue-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-blue-900 flex items-center gap-1.5">
                                    <span class="h-5 w-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold">1</span>
                                    <span>Fase 1: Waktu &amp; Posisi Pengajuan Izin</span>
                                </span>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="fetchLocation('izin')"
                                        :disabled="locatingField === 'izin'"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-bold bg-blue-100 hover:bg-blue-200 text-blue-800 border border-blue-300 cursor-pointer"
                                    >
                                        <Navigation class="h-3 w-3" :class="locatingField === 'izin' ? 'animate-spin' : ''" />
                                        <span>Ambil Lokasi</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="openMapSelector('izin')"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 cursor-pointer"
                                    >
                                        <Map class="h-3 w-3 text-slate-500" />
                                        <span>Lihat</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-0.5">Waktu Izin Diajukan</label>
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            v-model="formKeluar.waktu_izin"
                                            placeholder="00.00 WITA"
                                            class="w-full text-xs rounded-xl border-slate-200 focus:ring-1 focus:ring-blue-500 p-2 font-mono font-bold bg-white"
                                        />
                                        <button
                                            type="button"
                                            @click="formKeluar.waktu_izin = getNowWitaTime()"
                                            class="px-2 py-2 rounded-xl text-[10.5px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap cursor-pointer"
                                        >
                                            Saat Ini
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-0.5">Lokasi Pengajuan Izin</label>
                                    <input
                                        type="text"
                                        v-model="formKeluar.lokasi_izin"
                                        placeholder="Pos Penjagaan SPPG"
                                        class="w-full text-xs rounded-xl border-slate-200 p-2 font-medium bg-white"
                                    />
                                </div>
                            </div>
                            <div v-if="formKeluar.lat_izin && formKeluar.lng_izin" class="text-[10px] font-mono text-blue-700 flex items-center gap-1">
                                <span>📍 Titik Izin:</span>
                                <span class="font-bold">{{ formKeluar.lat_izin }}, {{ formKeluar.lng_izin }}</span>
                            </div>
                        </div>

                        <!-- FASE 2: KELUAR PENJAGAAN (POS AWAL) -->
                        <div class="p-3.5 bg-amber-50/70 rounded-xl border border-amber-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-amber-900 flex items-center gap-1.5">
                                    <span class="h-5 w-5 rounded-full bg-amber-600 text-white flex items-center justify-center text-[10px] font-bold">2</span>
                                    <span>Fase 2: Waktu &amp; Titik Posisi Keluar Penjagaan</span>
                                </span>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="fetchLocation('awal')"
                                        :disabled="locatingField === 'awal'"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-bold bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300 cursor-pointer"
                                    >
                                        <Navigation class="h-3 w-3" :class="locatingField === 'awal' ? 'animate-spin' : ''" />
                                        <span>Ambil Lokasi</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="openMapSelector('awal')"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 cursor-pointer"
                                    >
                                        <Map class="h-3 w-3 text-slate-500" />
                                        <span>Lihat</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-0.5">Waktu Keluar Penjagaan <span class="text-rose-500">*</span></label>
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            v-model="formKeluar.waktu_keluar"
                                            placeholder="00.00 WITA"
                                            class="w-full text-xs rounded-xl border-slate-200 focus:ring-1 focus:ring-amber-500 p-2 font-mono font-bold bg-white"
                                        />
                                        <button
                                            type="button"
                                            @click="formKeluar.waktu_keluar = getNowWitaTime()"
                                            class="px-2 py-2 rounded-xl text-[10.5px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap cursor-pointer"
                                        >
                                            Saat Ini
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-0.5">Nama Pos Awal Keluar</label>
                                    <input
                                        type="text"
                                        v-model="formKeluar.lokasi_awal"
                                        placeholder="Pos Penjagaan SPPG"
                                        class="w-full text-xs rounded-xl border-slate-200 p-2 font-medium bg-white"
                                    />
                                </div>
                            </div>
                            <div v-if="formKeluar.lat_awal && formKeluar.lng_awal" class="text-[10px] font-mono text-amber-800 flex items-center gap-1">
                                <span>🚪 Titik Keluar:</span>
                                <span class="font-bold">{{ formKeluar.lat_awal }}, {{ formKeluar.lng_awal }}</span>
                            </div>
                        </div>

                        <!-- FASE 3: KEPERLUAN & TEMPAT TUJUAN IZIN -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <span class="h-5 w-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-[10px] font-bold">3</span>
                                    <span>Fase 3: Tempat Izin / Lokasi Tujuan &amp; Keperluan</span>
                                </span>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="fetchLocation('tujuan')"
                                        :disabled="locatingField === 'tujuan'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 cursor-pointer"
                                    >
                                        <Navigation class="h-3 w-3" :class="locatingField === 'tujuan' ? 'animate-spin' : ''" />
                                        <span>Ambil Lokasi</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="openMapSelector('tujuan')"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 cursor-pointer"
                                    >
                                        <Map class="h-3 w-3 text-slate-500" />
                                        <span>Lihat</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">
                                    Nama Tempat Izin / Tujuan <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="formKeluar.lokasi_tujuan"
                                    placeholder="Contoh: Toko Elektronik Sukasada / Perimeter Luar SPPG / Kantor Desa..."
                                    class="w-full text-xs rounded-xl border-slate-200 p-2 font-medium focus:ring-1 focus:ring-primary bg-white"
                                />
                                <div v-if="formKeluar.lat_tujuan && formKeluar.lng_tujuan" class="text-[10px] font-mono text-rose-700 mt-1 flex items-center gap-1">
                                    <span>🎯 Titik Tujuan:</span>
                                    <span class="font-bold">{{ formKeluar.lat_tujuan }}, {{ formKeluar.lng_tujuan }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">
                                    Uraian Keperluan Izin Keluar <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    v-model="formKeluar.keperluan"
                                    rows="2"
                                    placeholder="Jelaskan alasan izin keluar (misal: Patroli keliling perimeter batas luar SPPG, beli logistik pos jaga, koordinasi lingkungan sekitar)..."
                                    class="w-full text-xs rounded-xl border-slate-200 p-2 font-medium focus:ring-1 focus:ring-primary bg-white"
                                ></textarea>
                                <!-- Presets -->
                                <div class="flex items-center gap-1 flex-wrap mt-1">
                                    <button
                                        type="button"
                                        @click="setKeperluan('Patroli pemantauan keliling perimeter batas luar area SPPG.')"
                                        class="text-[10px] text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 px-2 py-0.5 rounded cursor-pointer"
                                    >
                                        + Patroli Luar Lingkungan
                                    </button>
                                    <button
                                        type="button"
                                        @click="setKeperluan('Pembelian perlengkapan dan logistik pos penjagaan.')"
                                        class="text-[10px] text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 px-2 py-0.5 rounded cursor-pointer"
                                    >
                                        + Beli Logistik Pos
                                    </button>
                                    <button
                                        type="button"
                                        @click="setKeperluan('Koordinasi lapangan dengan aparat lingkungan / Babinsa / Bhabinkamtibmas.')"
                                        class="text-[10px] text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 px-2 py-0.5 rounded cursor-pointer"
                                    >
                                        + Koordinasi Lingkungan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- FASE EDIT TAMBAHAN: STATUS & KEMBALI (KHUSUS MODE EDIT) -->
                        <div v-if="mode === 'edit'" class="p-3.5 bg-slate-100 rounded-xl border border-slate-300 space-y-3">
                            <span class="font-bold text-slate-800 flex items-center gap-1.5">
                                <span class="h-5 w-5 rounded-full bg-slate-700 text-white flex items-center justify-center text-[10px] font-bold">4</span>
                                <span>Status &amp; Data Kepulangan Petugas</span>
                            </span>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-0.5">Status Izin</label>
                                    <select
                                        v-model="formKembali.status"
                                        class="w-full text-xs font-semibold rounded-xl border-slate-300 bg-white p-2"
                                    >
                                        <option value="Sedang Keluar">Sedang Keluar</option>
                                        <option value="Kembali ke Pos">Kembali ke Pos (Selesai)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-0.5">Waktu Kembali</label>
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            v-model="formKembali.waktu_kembali"
                                            placeholder="00.00 WITA"
                                            class="w-full text-xs rounded-xl border-slate-300 bg-white p-2 font-mono font-bold"
                                        />
                                        <button
                                            type="button"
                                            @click="formKembali.waktu_kembali = getNowWitaTime()"
                                            class="px-2 py-2 rounded-xl text-[10px] font-bold bg-white hover:bg-slate-200 text-slate-700 border border-slate-300 whitespace-nowrap cursor-pointer"
                                        >
                                            Saat Ini
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="formKembali.status === 'Kembali ke Pos'">
                                <label class="block font-semibold text-slate-700 mb-0.5">Laporan Hasil / Kegiatan Kembali</label>
                                <textarea
                                    v-model="formKembali.laporan_kegiatan"
                                    rows="2"
                                    placeholder="Uraian laporan atau hasil setelah kembali ke pos jaga..."
                                    class="w-full text-xs rounded-xl border-slate-300 bg-white p-2 font-medium"
                                ></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- MODE 2: KONFIRMASI KEMBALI KE PENJAGAAN & PELAPORAN -->
                    <template v-else-if="mode === 'kembali' && izin">
                        <!-- Ringkasan Alur Fase Sebelumnya -->
                        <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-slate-500 font-medium">Petugas yang Izin:</span>
                                <span class="font-bold text-slate-900">{{ izin.petugas_nama }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] pt-1 border-t border-slate-200">
                                <div>
                                    <span class="text-slate-400 block">Waktu Izin &amp; Keluar:</span>
                                    <span class="font-mono font-bold text-slate-800">
                                        {{ izin.waktu_izin || izin.waktu_keluar }} ➔ Keluar: {{ izin.waktu_keluar }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Tempat Tujuan:</span>
                                    <span class="font-semibold text-slate-800">{{ izin.lokasi_tujuan }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- FASE 4: KEMBALI KE PENJAGAAN -->
                        <div class="p-3.5 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-emerald-900 flex items-center gap-1.5">
                                    <span class="h-5 w-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold">4</span>
                                    <span>Fase 4: Waktu &amp; Posisi Kembali ke Penjagaan</span>
                                </span>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="fetchLocation('kembali')"
                                        :disabled="locatingField === 'kembali'"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-bold bg-emerald-100 hover:bg-emerald-200 text-emerald-900 border border-emerald-300 cursor-pointer"
                                    >
                                        <Navigation class="h-3 w-3" :class="locatingField === 'kembali' ? 'animate-spin' : ''" />
                                        <span>Ambil Lokasi</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="openMapSelector('kembali')"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10.5px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 cursor-pointer"
                                    >
                                        <Map class="h-3 w-3 text-slate-500" />
                                        <span>Lihat</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-0.5">Waktu Kembali <span class="text-rose-500">*</span></label>
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            v-model="formKembali.waktu_kembali"
                                            placeholder="00.00 WITA"
                                            class="w-full text-xs rounded-xl border-slate-200 focus:ring-1 focus:ring-emerald-500 p-2 font-mono font-bold bg-white"
                                        />
                                        <button
                                            type="button"
                                            @click="formKembali.waktu_kembali = getNowWitaTime()"
                                            class="px-2 py-2 rounded-xl text-[10.5px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 whitespace-nowrap cursor-pointer"
                                        >
                                            Saat Ini
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-0.5">Posisi Saat Kembali</label>
                                    <input
                                        type="text"
                                        v-model="formKembali.lokasi_kembali"
                                        placeholder="Pos Penjagaan SPPG"
                                        class="w-full text-xs rounded-xl border-slate-200 p-2 font-medium bg-white"
                                    />
                                </div>
                            </div>
                            <div v-if="formKembali.lat_kembali && formKembali.lng_kembali" class="text-[10px] font-mono text-emerald-800 flex items-center gap-1">
                                <span>🏁 Titik Kembali:</span>
                                <span class="font-bold">{{ formKembali.lat_kembali }}, {{ formKembali.lng_kembali }}</span>
                            </div>
                        </div>

                        <!-- HASIL HITUNGAN DURASI WAKTU OTOMATIS -->
                        <div v-if="kalkulasiDurasiRealtime" class="p-3 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <div class="h-8 w-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold">
                                    <Hourglass class="h-4 w-4" />
                                </div>
                                <div>
                                    <span class="text-slate-500 text-[10.5px] block">Perhitungan Total Waktu di Luar:</span>
                                    <span class="font-extrabold text-blue-950 text-sm">
                                        {{ kalkulasiDurasiRealtime.text }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 border border-blue-300">
                                {{ kalkulasiDurasiRealtime.totalMenit }} Menit
                            </span>
                        </div>

                        <!-- PELAPORAN DARI PETUGAS YANG JAGA -->
                        <div>
                            <label class="block font-bold text-slate-800 mb-1">
                                Laporan Hasil Kegiatan &amp; Situasi Lingkungan <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                v-model="formKembali.laporan_kegiatan"
                                rows="3"
                                placeholder="Tuliskan pelaporan mutasi/hasil patroli dari petugas yang bersangkutan (misal: Selesai berpatroli, situasi jalan sekitar kondusif, logistik pos lengkap, kembali ke pos jaga dengan aman)..."
                                class="w-full text-xs rounded-xl border-slate-200 p-2.5 font-medium focus:ring-1 focus:ring-emerald-500"
                            ></textarea>
                            <p class="text-[10.5px] text-slate-400 mt-1">
                                Pelaporan ini akan langsung dicatat dalam jurnal mutasi keamanan harian.
                            </p>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-200 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>

                    <button
                        v-if="mode === 'create'"
                        type="button"
                        @click="submitKeluar"
                        :disabled="isSubmitting"
                        class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-xs transition-colors cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                        <span>{{ isSubmitting ? 'Menyimpan...' : 'Catat Izin Keluar' }}</span>
                    </button>

                    <button
                        v-else-if="mode === 'edit'"
                        type="button"
                        @click="submitKeluar"
                        :disabled="isSubmitting"
                        class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-colors cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        <span>{{ isSubmitting ? 'Menyimpan Perubahan...' : 'Simpan Perubahan Izin' }}</span>
                    </button>

                    <button
                        v-else-if="mode === 'kembali'"
                        type="button"
                        @click="submitKembali"
                        :disabled="isSubmitting"
                        class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition-colors cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <LogIn class="h-3.5 w-3.5" />
                        <span>{{ isSubmitting ? 'Menyimpan...' : 'Konfirmasi Kembali & Simpan Laporan' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
