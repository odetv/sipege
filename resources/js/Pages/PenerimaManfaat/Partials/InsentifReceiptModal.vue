<script setup>
import {
    X,
    Printer,
    Coins,
    CheckCircle2,
    Building2,
    Calendar,
} from "lucide-vue-next";
import { formatTanggalIndo } from "@/Services/exportPetugasHelper";

const props = defineProps({
    show: { type: Boolean, default: false },
    item: { type: Object, default: null },
    unitSppg: { type: Object, default: null },
    startDate: { type: String, default: "" },
    endDate: { type: String, default: "" },
});

const emit = defineEmits(["close"]);

function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}

function angkaKeTerbilang(nilai) {
    const angka = Math.floor(Math.abs(Number(nilai) || 0));
    const satuan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    if (angka < 12) return satuan[angka];
    if (angka < 20) return angkaKeTerbilang(angka - 10) + " Belas";
    if (angka < 100) return angkaKeTerbilang(Math.floor(angka / 10)) + " Puluh " + satuan[angka % 10];
    if (angka < 200) return "Seratus " + angkaKeTerbilang(angka - 100);
    if (angka < 1000) return angkaKeTerbilang(Math.floor(angka / 100)) + " Ratus " + angkaKeTerbilang(angka % 100);
    if (angka < 2000) return "Seribu " + angkaKeTerbilang(angka - 1000);
    if (angka < 1000000) return angkaKeTerbilang(Math.floor(angka / 1000)) + " Ribu " + angkaKeTerbilang(angka % 1000);
    if (angka < 1000000000) return angkaKeTerbilang(Math.floor(angka / 1000000)) + " Juta " + angkaKeTerbilang(angka % 1000000);
    return angkaKeTerbilang(Math.floor(angka / 1000000000)) + " Miliar " + angkaKeTerbilang(angka % 1000000000);
}

function printReceipt() {
    window.print();
}
</script>

<template>
    <div
        v-if="show && item"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
    >
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[90vh]">
            <!-- Modal Header (Non-Printable) -->
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-800 to-teal-900 text-white flex items-center justify-between shrink-0 print:hidden">
                <div class="flex items-center gap-2.5">
                    <Coins class="h-5 w-5 text-emerald-300" />
                    <div>
                        <h3 class="text-base font-bold">Kuitansi Tanda Terima Kas Tunai</h3>
                        <p class="text-xs text-emerald-100/90">Bukti resmi pengeluaran kas insentif operasional MBG</p>
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

            <!-- Receipt Content (Printable Area) -->
            <div id="receipt-print-area" class="p-8 space-y-6 overflow-y-auto font-serif text-slate-900 bg-white">
                <!-- Kop Dokumen Resmi -->
                <div class="text-center border-b-2 border-slate-900 pb-3">
                    <p class="text-xs tracking-widest font-sans font-bold uppercase text-slate-600">Badan Gizi Nasional (BGN) Republik Indonesia</p>
                    <h2 class="text-base font-sans font-black uppercase text-slate-900 mt-0.5 tracking-tight">
                        {{ unitSppg?.nama_sppg || 'SPPG BULELENG SUKASADA TEGALLINGGAH' }}
                    </h2>
                    <p class="text-[11px] font-sans text-slate-600 mt-0.5">
                        Alamat: Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali
                    </p>
                </div>

                <!-- Judul & No Kuitansi -->
                <div class="flex items-center justify-between font-sans border-b border-dashed border-slate-300 pb-2 text-xs">
                    <div>
                        <span class="text-slate-500">No. Bukti Kas: </span>
                        <span class="font-mono font-bold text-slate-800">BKK-PM/SPPG/{{ item.id }}/{{ new Date().getFullYear() }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Metode: </span>
                        <span class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">TUNAI LANGSUNG</span>
                    </div>
                </div>

                <!-- Rincian Kuitansi -->
                <div class="space-y-3.5 text-xs font-sans">
                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-3 text-slate-500 font-semibold">Telah Diterima Dari</div>
                        <div class="col-span-9 font-bold text-slate-900">: Bendahara {{ unitSppg?.nama_sppg || 'SPPG Buleleng Sukasada Tegallinggah' }}</div>
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-3 text-slate-500 font-semibold">Penerima / PIC</div>
                        <div class="col-span-9 font-bold text-slate-900">: {{ item.nama_pic }} ({{ item.nama_kelompok }})</div>
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-3 text-slate-500 font-semibold">Uang Sejumlah</div>
                        <div class="col-span-9 font-black text-emerald-800 text-sm">: {{ formatRupiah(item.amount) }}</div>
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-3 text-slate-500 font-semibold">Terbilang</div>
                        <div class="col-span-9 italic font-serif bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-slate-800 leading-relaxed">
                            "{{ angkaKeTerbilang(item.amount) }} Rupiah"
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-3 text-slate-500 font-semibold">Untuk Pembayaran</div>
                        <div class="col-span-9 text-slate-800 leading-relaxed">
                            : Insentif Penanggung Jawab Distribusi MBG Periode {{ formatTanggalIndo(startDate) }} s/d {{ formatTanggalIndo(endDate) }} ({{ item.hari_operasional }} Hari Operasional).
                        </div>
                    </div>

                    <!-- Kotak Formula Perhitungan -->
                    <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-200/80 text-[11px] space-y-1">
                        <div class="font-bold text-emerald-950">Rincian Perhitungan Resmi BGN:</div>
                        <div class="text-emerald-900 font-mono">
                            • Sasaran PM: {{ item.total_penerima }} PM ({{ item.skema_tier }})<br>
                            • Tarif Harian: {{ item.deskripsi_tarif }} × {{ item.hari_operasional }} Hari Operasional = <b>{{ formatRupiah(item.amount) }}</b>
                        </div>
                    </div>
                </div>

                <!-- Tanda Tangan 3 Kolom -->
                <div class="pt-4 grid grid-cols-3 gap-4 text-center font-sans text-xs">
                    <div>
                        <p class="text-slate-500">Mengetahui,</p>
                        <p class="font-bold text-slate-800 mt-0.5">Kepala Unit SPPG</p>
                        <div class="h-16"></div>
                        <p class="font-bold text-slate-900 border-t border-slate-300 pt-1">
                            ( {{ unitSppg?.nama_kepala || 'Kepala SPPG' }} )
                        </p>
                    </div>

                    <div>
                        <p class="text-slate-500">Diserahkan Oleh,</p>
                        <p class="font-bold text-slate-800 mt-0.5">Bendahara SPPG</p>
                        <div class="h-16"></div>
                        <p class="font-bold text-slate-900 border-t border-slate-300 pt-1">
                            ( {{ unitSppg?.nama_bendahara || 'Bendahara SPPG' }} )
                        </p>
                    </div>

                    <div>
                        <p class="text-slate-500">Diterima Oleh,</p>
                        <p class="font-bold text-slate-800 mt-0.5">Penerima Manfaat / PIC</p>
                        <div class="h-16"></div>
                        <p class="font-bold text-slate-900 border-t border-slate-300 pt-1">
                            ( {{ item.nama_pic }} )
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Non-Printable) -->
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0 print:hidden">
                <div class="text-[11px] text-slate-500">
                    Kuitansi sah tanda terima kas tunai SPPG.
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-300 transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                    <button
                        type="button"
                        @click="printReceipt"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all cursor-pointer"
                    >
                        <Printer class="h-4 w-4" />
                        <span>Cetak Kuitansi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #receipt-print-area, #receipt-print-area * {
        visibility: visible;
    }
    #receipt-print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
    }
}
</style>
