<script setup>
import { computed } from 'vue';
import { X, Printer, Download, ShieldCheck, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    mutasi: {
        type: Object,
        default: null,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    pejabatSppg: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close']);

function printDocument() {
    window.print();
}

function formatMenitPrint(menit) {
    if (!menit || menit <= 0) return '-';
    const jam = Math.floor(menit / 60);
    const sisa = menit % 60;
    if (jam > 0 && sisa > 0) return `${jam}j ${sisa}m`;
    if (jam > 0) return `${jam} Jam`;
    return `${sisa} Menit`;
}

function formatWaktuWita(val) {
    if (!val) return '-';
    const clean = String(val).replace(/\s*WITA/gi, '').trim();
    return clean ? `${clean} WITA` : '-';
}

const formattedTanggal = computed(() => {
    if (!props.mutasi?.tanggal) return '-';
    try {
        const d = new Date(props.mutasi.tanggal + 'T00:00:00');
        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch {
        return props.mutasi.tanggal;
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/70 backdrop-blur-xs overflow-y-auto print:p-0 print:bg-white print:static"
            style="z-index: 99999;"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl overflow-hidden my-auto flex flex-col max-h-[92vh] print:max-h-none print:shadow-none print:border-none print:w-full print:rounded-none"
            >
                <!-- Modal Bar (Hidden on print) -->
                <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between shrink-0 print:hidden">
                    <div class="flex items-center gap-2">
                        <Printer class="h-4 w-4 text-emerald-400" />
                        <span class="text-sm font-bold">Cetak Berita Acara &amp; Jurnal Mutasi Keamanan SPPG (24 Jam)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="printDocument"
                            class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                        >
                            <Printer class="h-3.5 w-3.5" />
                            <span>Cetak Sekarang</span>
                        </button>
                        <button
                            type="button"
                            @click="emit('close')"
                            class="h-8 w-8 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Printable Document Area -->
                <div id="printable-security-area" class="overflow-y-auto p-6 sm:p-8 flex-1 print:p-0 font-serif text-slate-900 leading-relaxed">
                    <!-- KOP DOKUMEN -->
                    <div class="text-center pb-3 border-b-2 border-slate-900">
                        <h2 class="text-base sm:text-lg font-black uppercase tracking-wider text-slate-950">
                            {{ unitSppg?.nama || 'SATUAN PELAYANAN PROGRAM GIZI (SPPG)' }}
                        </h2>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-700">
                            BERITA ACARA &amp; BUKU MUTASI PATROLI KEAMANAN 24 JAM
                        </h3>
                        <p class="text-[11px] text-slate-500 font-sans mt-0.5">
                            {{ unitSppg?.alamat || 'Wilayah Operasional Pelayanan MBG' }} • Telp: {{ unitSppg?.no_telp || '-' }}
                        </p>
                    </div>

                    <!-- IDENTITAS MUTASI -->
                    <div class="grid grid-cols-2 gap-4 my-4 p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs font-sans">
                        <div>
                            <table class="w-full">
                                <tr>
                                    <td class="w-32 text-slate-500 py-0.5">Hari / Tanggal</td>
                                    <td class="font-bold py-0.5">: {{ formattedTanggal }}</td>
                                </tr>
                                <tr>
                                    <td class="text-slate-500 py-0.5">Siklus Patroli</td>
                                    <td class="font-bold py-0.5">: 24 Jam Penuh (24 Interval Pengecekan)</td>
                                </tr>
                            </table>
                        </div>
                        <div>
                            <table class="w-full">
                                <tr>
                                    <td class="w-32 text-slate-500 py-0.5">Koordinator Jaga</td>
                                    <td class="font-bold text-slate-900 py-0.5">: {{ mutasi?.petugas_nama || mutasi?.petugas?.nama || 'Petugas Keamanan' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-slate-500 py-0.5">Status Lingkungan</td>
                                    <td class="font-extrabold py-0.5" :class="mutasi?.status_keseluruhan === 'Tidak Aman' ? 'text-rose-700' : 'text-emerald-700'">
                                        : {{ mutasi?.status_keseluruhan === 'Tidak Aman' ? '⚠️ TERDAPAT INSIDEN' : '✓ AMAN TERKENDALI' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- CATATAN SERAH TERIMA -->
                    <div v-if="mutasi?.catatan_serah_terima" class="mb-4 p-3 bg-amber-50/60 border border-amber-200 rounded-xl text-xs font-sans">
                        <span class="font-bold text-amber-900 block mb-0.5">Catatan Serah Terima / Inventaris Jaga:</span>
                        <p class="text-slate-700 whitespace-pre-line">{{ mutasi.catatan_serah_terima }}</p>
                    </div>

                    <!-- TABEL MUTASI 24 JAM -->
                    <div class="my-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5 font-sans">
                            I. JURNAL PENGECEKAN PATROLI KEAMANAN 24 JAM
                        </h4>
                        <div class="border border-slate-300 rounded-lg overflow-hidden">
                            <table class="w-full text-[11px] font-sans border-collapse text-left">
                                <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300 text-[10px] uppercase">
                                    <tr>
                                        <th class="py-2 px-2 w-10 text-center">No</th>
                                        <th class="py-2 px-2 w-36 text-center">Rentang Jam</th>
                                        <th class="py-2 px-2.5 w-32">Petugas Giliran Jaga</th>
                                        <th class="py-2 px-2 w-20 text-center">Kondisi</th>
                                        <th class="py-2 px-2.5">Uraian Kejadian &amp; Aktivitas Jam Ini</th>
                                        <th class="py-2 px-2.5 w-36 text-center">Titik Koordinat / Pos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    <tr
                                        v-for="(d, idx) in mutasi?.details || []"
                                        :key="idx"
                                        :class="d.kondisi === 'Tidak Aman' ? 'bg-rose-50/40' : ''"
                                    >
                                        <td class="py-1.5 px-2 text-center font-bold text-slate-500">{{ idx + 1 }}</td>
                                        <td class="py-1.5 px-2 text-center font-bold font-mono text-slate-900 whitespace-nowrap">
                                            <div>{{ d.jam }}</div>
                                            <div v-if="d.waktu_cek" class="text-[9px] font-mono text-slate-500 font-normal">
                                                Pukul {{ formatWaktuWita(d.waktu_cek) }}
                                            </div>
                                        </td>
                                        <td class="py-1.5 px-2.5 font-semibold text-slate-800">
                                            {{ d.petugas_nama || d.petugas?.nama || '-' }}
                                        </td>
                                        <td class="py-1.5 px-2 text-center">
                                            <span
                                                class="inline-block px-1.5 py-0.5 rounded text-[9.5px] font-extrabold"
                                                :class="d.kondisi === 'Tidak Aman' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'"
                                            >
                                                {{ d.kondisi }}
                                            </span>
                                        </td>
                                        <td class="py-1.5 px-2.5 text-slate-800">
                                            {{ d.uraian_kejadian || 'Situasi aman terkendali, area steril dan terpantau normal.' }}
                                        </td>
                                        <td class="py-1.5 px-2.5 text-center text-[10px]">
                                            <div class="font-semibold text-slate-800">{{ d.lokasi_nama || 'Pos Penjagaan' }}</div>
                                            <div v-if="d.latitude && d.longitude" class="font-mono text-slate-500 text-[9px]">
                                                {{ Number(d.latitude).toFixed(6) }}, {{ Number(d.longitude).toFixed(6) }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!mutasi?.details || mutasi.details.length === 0">
                                        <td colspan="6" class="py-6 text-center text-slate-400 italic font-sans">
                                            Belum ada data rincian patroli 24 jam untuk tanggal ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TABEL IZIN KELUAR PENJAGAAN & PELAPORAN (JIKA ADA) -->
                    <div v-if="mutasi?.izins && mutasi.izins.length > 0" class="my-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5 font-sans">
                            II. REKAP MUTASI IZIN KELUAR PENJAGAAN &amp; PELAPORAN PETUGAS
                        </h4>
                        <div class="border border-slate-300 rounded-lg overflow-hidden">
                            <table class="w-full text-[11px] font-sans border-collapse text-left">
                                <thead class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300 text-[10px] uppercase">
                                    <tr>
                                        <th class="py-2 px-2 w-10 text-center">No</th>
                                        <th class="py-2 px-2.5 w-28">Nama Petugas</th>
                                        <th class="py-2 px-2.5 w-36">Waktu &amp; Posisi Keluar</th>
                                        <th class="py-2 px-2.5">Keperluan &amp; Lokasi Tujuan</th>
                                        <th class="py-2 px-2.5 w-36">Waktu &amp; Posisi Kembali</th>
                                        <th class="py-2 px-2 w-24 text-center">Durasi Keluar</th>
                                        <th class="py-2 px-2.5 min-w-[180px]">Pelaporan Kegiatan Petugas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-800">
                                    <tr v-for="(iz, izIdx) in mutasi.izins" :key="izIdx">
                                        <td class="py-2 px-2 text-center font-bold text-slate-500">{{ izIdx + 1 }}</td>
                                        <td class="py-2 px-2.5 font-bold text-slate-900">{{ iz.petugas_nama }}</td>
                                        <td class="py-2 px-2.5">
                                            <div class="font-bold text-slate-800">{{ iz.waktu_keluar }}</div>
                                            <div class="text-[10px] text-slate-600">{{ iz.lokasi_awal || 'Pos SPPG' }}</div>
                                            <div v-if="iz.lat_awal && iz.lng_awal" class="text-[9px] font-mono text-slate-400">
                                                {{ Number(iz.lat_awal).toFixed(5) }}, {{ Number(iz.lng_awal).toFixed(5) }}
                                            </div>
                                        </td>
                                        <td class="py-2 px-2.5">
                                            <div class="font-semibold text-slate-900">{{ iz.lokasi_tujuan }}</div>
                                            <div class="text-[10.5px] text-slate-600 italic">{{ iz.keperluan }}</div>
                                            <div v-if="iz.lat_tujuan && iz.lng_tujuan" class="text-[9px] font-mono text-slate-400">
                                                Tujuan: {{ Number(iz.lat_tujuan).toFixed(5) }}, {{ Number(iz.lng_tujuan).toFixed(5) }}
                                            </div>
                                        </td>
                                        <td class="py-2 px-2.5">
                                            <template v-if="iz.status === 'Sudah Kembali'">
                                                <div class="font-bold text-emerald-800">{{ iz.waktu_kembali || '-' }}</div>
                                                <div class="text-[10px] text-slate-600">{{ iz.lokasi_kembali || 'Pos SPPG' }}</div>
                                                <div v-if="iz.lat_kembali && iz.lng_kembali" class="text-[9px] font-mono text-slate-400">
                                                    {{ Number(iz.lat_kembali).toFixed(5) }}, {{ Number(iz.lng_kembali).toFixed(5) }}
                                                </div>
                                            </template>
                                            <span v-else class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                                Sedang Keluar
                                            </span>
                                        </td>
                                        <td class="py-2 px-2 text-center font-bold text-slate-900">
                                            {{ formatMenitPrint(iz.durasi_keluar_menit) }}
                                        </td>
                                        <td class="py-2 px-2.5 text-[10.5px]">
                                            <div v-if="iz.laporan_kegiatan" class="text-slate-800 whitespace-pre-line">
                                                {{ iz.laporan_kegiatan }}
                                            </div>
                                            <span v-else class="text-slate-400 italic">Belum ada pelaporan (sedang bertugas)</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TANDA TANGAN PENGESAHAN -->
                    <div class="grid grid-cols-2 gap-8 mt-10 pt-4 text-xs font-sans text-center">
                        <div>
                            <p class="text-slate-500 mb-1">Petugas Jaga Keamanan,</p>
                            <div class="h-16"></div>
                            <p class="font-bold text-slate-900 underline uppercase">
                                {{ mutasi?.petugas_nama || mutasi?.petugas?.nama || 'Petugas Keamanan' }}
                            </p>
                            <p class="text-[11px] text-slate-500">Divisi Keamanan SPPG</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-1">Mengetahui,</p>
                            <p class="text-slate-500 mb-1">Kepala SPPG / Koordinator,</p>
                            <div class="h-14"></div>
                            <p class="font-bold text-slate-900 underline uppercase">
                                {{ pejabatSppg?.kepala_sppg || 'Kepala SPPG' }}
                            </p>
                            <p class="text-[11px] text-slate-500">Satuan Pelayanan Program Gizi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printable-security-area,
    #printable-security-area * {
        visibility: visible;
    }
    #printable-security-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 20px;
    }
}
</style>
