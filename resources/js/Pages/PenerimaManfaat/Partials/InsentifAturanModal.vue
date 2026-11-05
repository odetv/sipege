<script setup>
import { ref } from "vue";
import {
    X,
    BookOpen,
    Check,
    CheckCircle2,
    Building2,
    HeartPulse,
    Info,
    ArrowRight,
    Sparkles,
} from "lucide-vue-next";

const props = defineProps({
    show: { type: Boolean, default: false },
    currentAturan: { type: String, default: "aturan_2" },
});

const emit = defineEmits(["close", "selectAturan"]);

const activeTab = ref(props.currentAturan || "aturan_2");

function selectAndApply(aturanKey) {
    activeTab.value = aturanKey;
    emit("selectAturan", aturanKey);
    emit("close");
}

function formatRupiah(val) {
    if (!val) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 animate-in fade-in duration-200"
            @click.self="$emit('close')"
        >
            <div
                class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]"
                role="dialog"
                aria-modal="true"
            >
                <!-- Header Modal -->
                <div class="px-5 py-4 bg-gradient-to-r from-emerald-700 via-teal-700 to-slate-800 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/10 rounded-xl backdrop-blur-xs">
                            <BookOpen class="w-5 h-5 text-emerald-300" />
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold tracking-tight">
                                Petunjuk & Pedoman Rumus Perhitungan Insentif
                            </h2>
                            <p class="text-xs text-emerald-100/90 mt-0.5">
                                Fleksibilitas pilihan regulasi resmi BGN untuk Satuan Pendidikan & Posyandu
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="p-1.5 rounded-lg text-emerald-200 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Tab Switcher Header -->
                <div class="px-5 pt-3 bg-slate-50 border-b border-slate-200 shrink-0">
                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="activeTab = 'aturan_2'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-xl transition-all border-t border-x cursor-pointer flex items-center gap-2',
                                activeTab === 'aturan_2'
                                    ? 'bg-white text-emerald-800 border-slate-200 border-b-white -mb-px shadow-2xs font-extrabold'
                                    : 'bg-slate-100/70 text-slate-500 hover:text-slate-800 border-transparent hover:bg-slate-200/50'
                            ]"
                        >
                            <Sparkles class="w-3.5 h-3.5 text-emerald-600" />
                            <span>Aturan 2: SK No. 63551 Tahun 2026</span>
                            <span v-if="currentAturan === 'aturan_2'" class="px-1.5 py-0.2 rounded text-[9px] font-black bg-emerald-600 text-white">
                                AKTIF
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'aturan_1'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-xl transition-all border-t border-x cursor-pointer flex items-center gap-2',
                                activeTab === 'aturan_1'
                                    ? 'bg-white text-emerald-800 border-slate-200 border-b-white -mb-px shadow-2xs font-extrabold'
                                    : 'bg-slate-100/70 text-slate-500 hover:text-slate-800 border-transparent hover:bg-slate-200/50'
                            ]"
                        >
                            <Info class="w-3.5 h-3.5 text-slate-500" />
                            <span>Aturan 1: SK No. 401.1 Tahun 2025</span>
                            <span v-if="currentAturan === 'aturan_1'" class="px-1.5 py-0.2 rounded text-[9px] font-black bg-blue-600 text-white">
                                AKTIF
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Content Modal (Scrollable) -->
                <div class="p-5 overflow-y-auto space-y-5 text-xs text-slate-700">
                    <!-- ═════════ TAB ATURAN 2 (TERBARU) ═════════ -->
                    <div v-if="activeTab === 'aturan_2'" class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200/90 flex items-start gap-3">
                            <CheckCircle2 class="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" />
                            <div>
                                <div class="font-extrabold text-emerald-950 text-sm">
                                    Aturan 2: SK No. 63551 Tahun 2026 (Mulai 02 November 2026)
                                </div>
                                <p class="text-xs text-emerald-800 mt-1 leading-relaxed">
                                    Merupakan aturan standar yang digunakan saat ini. Perhitungan 3B Posyandu seluruhnya dikompulir se-SPPG terlebih dahulu, lalu disandingkan dengan Tabel Tingkat 1–6 (Rp 60.000 s/d Rp 700.000/hari) dan dialokasikan proporsional per posyandu.
                                </p>
                            </div>
                        </div>

                        <!-- Rumus Sekolah (Tabel 2 & 3) -->
                        <div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-2xs">
                            <div class="px-4 py-2.5 bg-slate-100 border-b border-slate-200 font-bold text-slate-800 flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-blue-600" />
                                <span>1. Satuan Pendidikan / Sekolah (Sesuai Tabel 2 & 3)</span>
                            </div>
                            <div class="p-3.5 space-y-2">
                                <p class="text-[11.5px] text-slate-600">
                                    Dihitung harian per satuan pendidikan berdasarkan jumlah siswa penerima manfaat:
                                </p>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse border border-slate-200 text-xs">
                                        <thead>
                                            <tr class="bg-slate-50 text-[10.5px] font-bold text-slate-700 border-b">
                                                <th class="py-1.5 px-3 border-r w-12 text-center">Tier</th>
                                                <th class="py-1.5 px-3 border-r">Sasaran Penerima Manfaat</th>
                                                <th class="py-1.5 px-3 text-right">Besaran Insentif (per hari pelayanan)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-medium">
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">1</td><td class="py-1.5 px-3 border-r">&lt; 100 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 20.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">2</td><td class="py-1.5 px-3 border-r">100 s.d. 500 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 30.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">3</td><td class="py-1.5 px-3 border-r">501 s.d. 750 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 50.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">4</td><td class="py-1.5 px-3 border-r">751 s.d. 1.000 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 60.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">5</td><td class="py-1.5 px-3 border-r">1.001 s.d. 2.000 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 100.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">6</td><td class="py-1.5 px-3 border-r">2.001 s.d. 3.000 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 200.000,-</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Rumus Posyandu (Tabel 1) -->
                        <div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-2xs">
                            <div class="px-4 py-2.5 bg-pink-50/80 border-b border-pink-200 font-bold text-pink-900 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <HeartPulse class="w-4 h-4 text-pink-600" />
                                    <span>2. 3B Posyandu (Sesuai Tabel 1 Kompulir SPPG)</span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-pink-200 text-pink-900">
                                    Total Kompulir se-SPPG
                                </span>
                            </div>
                            <div class="p-3.5 space-y-2">
                                <p class="text-[11.5px] text-slate-600">
                                    Seluruh sasaran Posyandu 3B (Balita, Ibu Hamil, Ibu Menyusui) dijumlahkan secara kompulir se-SPPG, lalu disandingkan ke tabel tingkatan insentif harian berikut:
                                </p>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse border border-slate-200 text-xs">
                                        <thead>
                                            <tr class="bg-pink-50/40 text-[10.5px] font-bold text-slate-700 border-b">
                                                <th class="py-1.5 px-3 border-r w-16 text-center">Tingkat</th>
                                                <th class="py-1.5 px-3 border-r">Jumlah Penerima Manfaat 3B per SPPG</th>
                                                <th class="py-1.5 px-3 text-right">Besaran Insentif (per hari pelayanan)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-medium">
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">1</td><td class="py-1.5 px-3 border-r">&le; 100 penerima manfaat</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 60.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">2</td><td class="py-1.5 px-3 border-r">101 s.d. 300 penerima manfaat</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 150.000,-</td></tr>
                                            <tr class="bg-emerald-50/40 font-bold"><td class="py-1.5 px-3 text-center border-r font-bold text-emerald-900">3 ★</td><td class="py-1.5 px-3 border-r text-emerald-950">301 s.d. 500 penerima manfaat (SPPG Tegallinggah: 326 PM)</td><td class="py-1.5 px-3 text-right font-black text-emerald-900">Rp 250.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">4</td><td class="py-1.5 px-3 border-r">501 s.d. 1.000 penerima manfaat</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 400.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">5</td><td class="py-1.5 px-3 border-r">1.001 s.d. 1.500 penerima manfaat</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 550.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">6</td><td class="py-1.5 px-3 border-r">&gt; 1.500 penerima manfaat</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 700.000,-</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-[11px] text-slate-600 mt-2">
                                    💡 <b>Tampilan Tabel:</b> Ditampilkan sebagai 1 baris utama <b>3B Posyandu</b> (Rp 250.000/hari) dengan fitur <i>Expand</i> rincian untuk melihat dan mencetak kwitansi proporsional masing-masing 4 Posyandu.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═════════ TAB ATURAN 1 (STANDAR) ═════════ -->
                    <div v-else class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200/90 flex items-start gap-3">
                            <Info class="w-5 h-5 text-blue-700 shrink-0 mt-0.5" />
                            <div>
                                <div class="font-extrabold text-blue-950 text-sm">
                                    Aturan 1: SK No. 401.1 Tahun 2025
                                </div>
                                <p class="text-xs text-blue-800 mt-1 leading-relaxed">
                                    Setiap Posyandu dihitung secara individual perorangan berdasarkan tarif flat Rp 1.000 per sasaran PM per hari operasional.
                                </p>
                            </div>
                        </div>

                        <!-- Rumus Sekolah (Tabel 2 & 3) Identik dengan Aturan 2 -->
                        <div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-2xs">
                            <div class="px-4 py-2.5 bg-slate-100 border-b border-slate-200 font-bold text-slate-800 flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-blue-600" />
                                <span>1. Satuan Pendidikan / Sekolah (Sesuai Tabel 2 & 3)</span>
                            </div>
                            <div class="p-3.5 space-y-2">
                                <p class="text-[11.5px] text-slate-600">
                                    Dihitung harian per satuan pendidikan berdasarkan jumlah siswa penerima manfaat:
                                </p>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse border border-slate-200 text-xs">
                                        <thead>
                                            <tr class="bg-slate-50 text-[10.5px] font-bold text-slate-700 border-b">
                                                <th class="py-1.5 px-3 border-r w-12 text-center">Tier</th>
                                                <th class="py-1.5 px-3 border-r">Sasaran Penerima Manfaat</th>
                                                <th class="py-1.5 px-3 text-right">Besaran Insentif (per hari pelayanan)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-medium">
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">1</td><td class="py-1.5 px-3 border-r">&lt; 100 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 20.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">2</td><td class="py-1.5 px-3 border-r">100 s.d. 500 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 30.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">3</td><td class="py-1.5 px-3 border-r">501 s.d. 750 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 50.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">4</td><td class="py-1.5 px-3 border-r">751 s.d. 1.000 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 60.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">5</td><td class="py-1.5 px-3 border-r">1.001 s.d. 2.000 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 100.000,-</td></tr>
                                            <tr><td class="py-1.5 px-3 text-center border-r font-bold">6</td><td class="py-1.5 px-3 border-r">2.001 s.d. 3.000 siswa</td><td class="py-1.5 px-3 text-right font-bold text-emerald-800">Rp 200.000,-</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Rumus Posyandu (Lampiran 26) -->
                        <div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-2xs">
                            <div class="px-4 py-2.5 bg-pink-50/80 border-b border-pink-200 font-bold text-pink-900 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <HeartPulse class="w-4 h-4 text-pink-600" />
                                    <span>2. Kader Posyandu (Sesuai Lampiran 26 Poin h)</span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-pink-100 text-pink-800">
                                    Rp 1.000 / PM / Hari
                                </span>
                            </div>
                            <div class="p-3.5 space-y-2.5">
                                <p class="text-[11.5px] text-slate-700 leading-relaxed italic bg-pink-50/30 p-2.5 rounded-lg border border-pink-100">
                                    "h) Insentif kader Posyandu dan kader lainnya untuk distribusi MBG kepada ibu hamil, ibu menyusui dan anak balita... besarannya dengan <b>rata-rata Rp1.000/penerima manfaat per hari operasional</b>."
                                </p>
                                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                                    <div class="font-bold text-slate-800 mb-1">Simulasi Perhitungan Riil (Aturan 1):</div>
                                    <ul class="list-disc list-inside space-y-1 text-[11px] text-slate-600">
                                        <li>Posyandu Melati Mundukkunci: 128 PM &times; Rp 1.000 = <b>Rp 128.000 / hari</b></li>
                                        <li>Posyandu Ratna Tegallinggah Atas: 79 PM &times; Rp 1.000 = <b>Rp 79.000 / hari</b></li>
                                        <li>Posyandu Mawar Tegallinggah Bawah: 65 PM &times; Rp 1.000 = <b>Rp 65.000 / hari</b></li>
                                        <li>Posyandu Sandat Lebah Pupuan: 54 PM &times; Rp 1.000 = <b>Rp 54.000 / hari</b></li>
                                    </ul>
                                    <div class="mt-2 pt-2 border-t border-slate-200 font-extrabold text-slate-800 flex justify-between">
                                        <span>Total Insentif 4 Posyandu (Aturan 1):</span>
                                        <span class="text-emerald-800">Rp 326.000 / hari</span>
                                    </div>
                                </div>
                                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-[11px] text-slate-600">
                                    💡 <b>Tampilan Tabel:</b> Muncul sebagai 4 baris individual terpisah masing-masing posyandu (total 18 baris di tabel).
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perbandingan Ringkas Aturan 1 vs Aturan 2 -->
                    <div class="p-3.5 rounded-xl bg-slate-100 border border-slate-200 space-y-2">
                        <div class="font-extrabold text-slate-800 text-xs uppercase tracking-wider">
                            Rangkuman Perbedaan Aturan 1 & Aturan 2:
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                            <div class="p-2.5 rounded-lg bg-white border border-slate-200 space-y-1">
                                <div class="font-bold text-blue-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    <span>Aturan 1: SK No. 401.1 Tahun 2025:</span>
                                </div>
                                <p class="text-slate-600">• Sekolah: Sesuai Tabel 2/3 (Tier 1-6)</p>
                                <p class="text-slate-600">• Posyandu: Flat Rp 1.000 / PM per posyandu</p>
                                <p class="text-slate-600">• Tampilan: 4 Posyandu muncul individual</p>
                            </div>
                            <div class="p-2.5 rounded-lg bg-white border border-emerald-300 space-y-1 bg-emerald-50/20">
                                <div class="font-bold text-emerald-900 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                    <span>Aturan 2: SK No. 63551 Tahun 2026:</span>
                                </div>
                                <p class="text-slate-600">• Sekolah: Sesuai Tabel 2/3 (Tier 1-6)</p>
                                <p class="text-slate-600">• Posyandu: Kompulir SPPG Tabel 1 (Tingkat 1-6)</p>
                                <p class="text-slate-600">• Tampilan: 1 baris utama 3B Posyandu (Expandable)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal Action -->
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 shrink-0">
                    <div class="text-[11px] text-slate-500">
                        Status saat ini:
                        <b class="text-slate-900 font-extrabold">
                            {{ currentAturan === 'aturan_2' ? 'Aturan 2: SK No. 63551 Tahun 2026' : 'Aturan 1: SK No. 401.1 Tahun 2025' }}
                        </b>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="currentAturan !== 'aturan_1'"
                            type="button"
                            @click="selectAndApply('aturan_1')"
                            class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs transition-colors cursor-pointer"
                        >
                            Gunakan Aturan 1
                        </button>
                        <button
                            v-if="currentAturan !== 'aturan_2'"
                            type="button"
                            @click="selectAndApply('aturan_2')"
                            class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors shadow-2xs cursor-pointer flex items-center gap-1.5"
                        >
                            <Sparkles class="w-3.5 h-3.5" />
                            <span>Gunakan Aturan 2</span>
                        </button>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition-colors cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
