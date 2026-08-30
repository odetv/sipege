<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import {
    ShieldCheck,
    Receipt,
    CheckCircle2,
    Clock,
    XCircle,
    Eye,
    Coins,
    Edit3,
    Check,
    Printer,
    FileSpreadsheet,
    Calendar,
    AlertCircle,
    Building2,
    X,
    FileText,
    ArrowRight,
    Send,
} from "lucide-vue-next";

const props = defineProps({
    verifikasiPoList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        default: (num) => {
            if (!num) return "Rp 0";
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                maximumFractionDigits: 0,
            }).format(num);
        },
    },
    formatTanggalIndo: {
        type: Function,
        default: (tgl) => {
            if (!tgl) return "-";
            try {
                const d = new Date(tgl);
                return d.toLocaleDateString("id-ID", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                });
            } catch {
                return tgl;
            }
        },
    },
});

function formatGrossWeight(kg) {
    if (kg === null || kg === undefined || kg === "" || isNaN(Number(kg))) return "0 kg";
    const num = Number(kg);
    if (num <= 0) return "0 kg";
    if (num < 0.001) {
        return `${parseFloat(num.toFixed(4))} kg`;
    } else if (num < 0.01) {
        return `${parseFloat(num.toFixed(3))} kg`;
    } else {
        return `${parseFloat(num.toFixed(2))} kg`;
    }
}

function formatFullLogTimestamp(dt) {
    if (!dt) return "-";
    try {
        const d = new Date(dt);
        if (isNaN(d.getTime())) return dt;
        const dateStr = d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        return `${dateStr}, ${hours}:${minutes}:${seconds} WIB`;
    } catch {
        return dt;
    }
}

function formatDateTimeIndo(dt) {
    if (!dt) return "-";
    try {
        const d = new Date(dt);
        if (isNaN(d.getTime())) return dt;
        const dateStr = d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        return `${dateStr}, ${hours}:${minutes} WIB`;
    } catch {
        return dt;
    }
}

const selectedPo = ref(null);
const showDetailModal = ref(false);
const isProcessing = ref(false);
const isReadOnlyMode = ref(false);
const inputCatatanBaru = ref("");

const activeList = computed(() => {
    return Array.isArray(props.verifikasiPoList) ? props.verifikasiPoList : [];
});

function openVerificationModal(po, readOnly = false) {
    selectedPo.value = JSON.parse(JSON.stringify(po));
    if (!selectedPo.value.items) {
        selectedPo.value.items = [];
    }
    selectedPo.value.riwayat_verifikasi = po.riwayat_verifikasi || po.raw?.riwayat_verifikasi || po.raw?.work_order?.riwayat_verifikasi || [];
    inputCatatanBaru.value = "";
    isReadOnlyMode.value = readOnly;
    showDetailModal.value = true;
}

const grandTotalMasterBiaya = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => {
        const gross = Number(item.gross_kg) || 0;
        const harga = Number(item.harga_master) || 0;
        let sub = Math.round(gross * harga);
        if (gross > 0 && harga > 0 && sub === 0) sub = Math.ceil(gross * harga);
        return acc + sub;
    }, 0);
});

const grandTotalGrossKg = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => acc + (Number(item.gross_kg) || 0), 0);
});

const totalAktualBiaya = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => {
        const gross = Number(item.gross_kg) || 0;
        const harga = Number(item.harga_aktual !== undefined && item.harga_aktual !== null && item.harga_aktual !== '' ? item.harga_aktual : item.harga_master) || 0;
        let sub = Math.round(gross * harga);
        if (gross > 0 && harga > 0 && sub === 0) sub = Math.ceil(gross * harga);
        return acc + sub;
    }, 0);
});

// Aksi 1: Simpan sebagai Draft Verifikasi (Tetap di Verifikasi PO)
function saveAsDraftVerification() {
    if (!selectedPo.value) return;
    isProcessing.value = true;
    
    router.post('/keuangan/po/' + selectedPo.value.db_id + '/verifikasi', {
        status_po: 'Draft Verifikasi',
        catatan: inputCatatanBaru.value,
        items: selectedPo.value.items,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false;
            showDetailModal.value = false;
        },
        onError: () => {
            isProcessing.value = false;
        }
    });
}

// Aksi 2: Setujui & Lanjutkan ke Daftar PO Resmi
function approveAndProceedToPoList() {
    if (!selectedPo.value) return;
    isProcessing.value = true;
    
    router.post('/keuangan/po/' + selectedPo.value.db_id + '/verifikasi', {
        status_po: 'Terverifikasi',
        catatan: inputCatatanBaru.value,
        items: selectedPo.value.items,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false;
            showDetailModal.value = false;
            // Navigasi ke Daftar PO resmi
            router.visit('/keuangan/daftar-po');
        },
        onError: () => {
            isProcessing.value = false;
        }
    });
}

// Aksi 3: Tolak PO
function rejectPo() {
    if (!selectedPo.value) return;
    if (!selectedPo.value.catatan || !selectedPo.value.catatan.trim()) {
        alert('Harap masukkan catatan/alasan penolakan agar Tim Gizi dapat melakukan perbaikan.');
        return;
    }
    isProcessing.value = true;
    
    router.post('/keuangan/po/' + selectedPo.value.db_id + '/verifikasi', {
        status_po: 'Ditolak',
        catatan: inputCatatanBaru.value,
        items: selectedPo.value.items,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false;
            showDetailModal.value = false;
        },
        onError: () => {
            isProcessing.value = false;
        }
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Card 1: Tabel Utama Verifikasi PO -->
        <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div>
                    <CardTitle
                        className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                    >
                        <ShieldCheck class="h-5 w-5 text-primary" />
                        <span>Verifikasi Pengajuan Purchase Order (PO)</span>
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Modul telaah, verifikasi harga pasar, dan persetujuan pengajuan belanja bahan baku dari rancangan menu Tim Gizi sebelum diterbitkan ke Daftar PO resmi.
                    </CardDescription>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 text-xs font-extrabold rounded-lg bg-blue-50 text-blue-700 border border-blue-200">
                        {{ activeList.filter(p => p.status_po === 'Menunggu Verifikasi' || p.status_po === 'Diajukan ke Keuangan').length }} Pengajuan Menunggu
                    </span>
                </div>
            </CardHeader>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px] text-left text-xs border-collapse">
                    <thead
                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                    >
                        <tr>
                            <th class="p-3.5">No. PO & WO</th>
                            <th class="p-3.5">Tanggal Distribusi</th>
                            <th class="p-3.5">Menu Sasaran & Vendor</th>
                            <th class="p-3.5">Waktu Pengajuan</th>
                            <th class="p-3.5 text-center">Items</th>
                            <th class="p-3.5 text-right">Estimasi Nominal</th>
                            <th class="p-3.5 text-center">Status Verifikasi</th>
                            <th class="p-3.5 text-center min-w-[170px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="po in activeList"
                            :key="po.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                <div>{{ po.id }}</div>
                                <div class="text-[10px] text-slate-400 font-normal">
                                    {{ po.wo_id }}
                                </div>
                            </td>
                            <td class="p-3.5 font-medium text-slate-600 whitespace-nowrap">
                                {{ formatTanggalIndo(po.tanggal) }}
                            </td>
                            <td class="p-3.5 font-bold text-slate-900 max-w-xs">
                                <div>{{ po.menu }}</div>
                                <div class="text-[10.5px] text-slate-500 font-normal">
                                    Vendor: {{ po.vendor || 'Rekanan Pangan SPPG' }}
                                </div>
                            </td>
                            <td class="p-3.5 whitespace-nowrap text-[10.5px]">
                                <div class="text-slate-600">
                                    <span class="text-slate-400">Diajukan:</span> {{ formatDateTimeIndo(po.created_at) }}
                                </div>
                                <div v-if="po.diverifikasi_pada" class="text-rose-600 font-semibold mt-0.5">
                                    Ditolak: {{ formatDateTimeIndo(po.diverifikasi_pada) }}
                                </div>
                            </td>
                            <td class="p-3.5 text-center font-bold text-slate-700">
                                {{ po.items_count || po.items?.length || 0 }} Item
                            </td>
                            <td class="p-3.5 text-right font-mono font-black text-slate-900 whitespace-nowrap">
                                {{ formatRupiah(po.total_nominal_master || po.total_nominal) }}
                            </td>
                            <td class="p-3.5 text-center">
                                <span
                                    :class="[
                                        'px-2.5 py-0.5 text-[10.5px] font-bold rounded-lg border inline-block whitespace-nowrap',
                                        po.status_po === 'Draft Verifikasi'
                                            ? 'bg-amber-50 text-amber-700 border-amber-300'
                                            : po.status_po === 'Ditolak'
                                              ? 'bg-rose-50 text-rose-700 border-rose-300'
                                              : 'bg-blue-50 text-blue-700 border-blue-300 animate-pulse',
                                    ]"
                                >
                                    {{ po.status_po }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <!-- Status Menunggu Verifikasi atau Draft Verifikasi -> Tombol Verifikasi Belanja Aktif -->
                                <div v-if="po.status_po === 'Menunggu Verifikasi' || po.status_po === 'Diajukan ke Keuangan' || po.status_po === 'Draft Verifikasi'">
                                    <Button
                                        type="button"
                                        @click="openVerificationModal(po, false)"
                                        className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-3 h-8 rounded-lg shadow-xs transition-colors cursor-pointer flex items-center gap-1 mx-auto"
                                        title="Buka Form Verifikasi & Persetujuan"
                                    >
                                        <ShieldCheck class="h-3.5 w-3.5" />
                                        <span>Verifikasi Belanja</span>
                                    </Button>
                                </div>

                                <!-- Status Ditolak -> Tombol Verifikasi gabisa diklik lagi, tampilkan tombol Lihat Hasil -->
                                <div v-else-if="po.status_po === 'Ditolak'" class="flex items-center justify-center gap-1.5">
                                    <Button
                                        type="button"
                                        @click="openVerificationModal(po, true)"
                                        className="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold px-2.5 h-7 rounded-lg transition-colors cursor-pointer"
                                        title="Lihat Alasan Penolakan"
                                    >
                                        <Eye class="h-3.5 w-3.5 mr-1" />
                                        Lihat Hasil
                                    </Button>
                                    <span class="text-[10px] text-rose-600 font-bold bg-rose-50 px-1 py-0.5 rounded">
                                        Menunggu Revisi
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="activeList.length === 0">
                            <td colspan="8" class="p-12 text-center text-slate-400 font-medium">
                                <div class="flex flex-col items-center justify-center gap-2.5">
                                    <ShieldCheck class="h-10 w-10 text-slate-300" />
                                    <p class="text-sm font-semibold text-slate-600">Tidak ada pengajuan PO yang perlu diverifikasi.</p>
                                    <p class="text-xs text-slate-400 max-w-sm">Ketika Tim Gizi mengajukan rancangan menu pada modul Gizi, pengajuan belanja bahan baku akan muncul di halaman ini.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Modal Verifikasi Detail Pembelian Bahan (Akuntan) -->
        <Modal
            :show="showDetailModal"
            @close="showDetailModal = false"
            maxWidth="4xl"
        >
            <div
                v-if="selectedPo"
                class="bg-white rounded-2xl overflow-hidden shadow-2xl border border-slate-200 text-slate-800"
            >
                <!-- Modal Header -->
                <div
                    class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0"
                        >
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-black text-slate-900">
                                    {{ isReadOnlyMode ? 'Detail Pengajuan PO' : 'Form Telaah & Verifikasi PO' }} ({{ selectedPo.id }})
                                </h3>
                                <Badge
                                    variant="outline"
                                    :className="
                                        selectedPo.status_po === 'Ditolak'
                                            ? 'bg-rose-50 text-rose-700 border-rose-300 font-bold'
                                            : 'bg-blue-50 text-blue-700 border-blue-300 font-bold'
                                    "
                                >
                                    {{ selectedPo.status_po }}
                                </Badge>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Referensi WO: <strong class="text-slate-800">{{ selectedPo.wo_id }}</strong> • Menu: {{ selectedPo.menu }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 sm:p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Banner Info Mode -->
                    <div
                        v-if="!isReadOnlyMode"
                        class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-2.5 text-xs text-blue-900"
                    >
                        <AlertCircle class="h-4 w-4 text-blue-600 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold">Instruksi Verifikasi Akuntan:</span> Periksa kuantitas kebutuhan kotor (kg) dan sesuaikan harga satuan aktual belanja pasar/supplier rekanan SPPG. Anda dapat menyimpan sebagai <strong>Draft Verifikasi</strong> atau langsung <strong>Setujui & Lanjutkan ke Daftar PO</strong>.
                        </div>
                    </div>
                    <div
                        v-else-if="selectedPo.status_po === 'Ditolak'"
                        class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-2.5 text-xs text-rose-900"
                    >
                        <XCircle class="h-4 w-4 text-rose-600 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold">Status Penolakan PO:</span> Pengajuan PO ini telah ditolak dan sedang menunggu revisi/perbaikan bahan dari Tim Gizi.
                        </div>
                    </div>

                    <!-- Detail Info PO & Waktu Lengkap -->
                    <div
                        class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs"
                    >
                        <div>
                            <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Tanggal Distribusi</span>
                            <span class="font-bold text-slate-800">{{ formatTanggalIndo(selectedPo.tanggal) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Waktu Pengajuan</span>
                            <span class="font-bold text-slate-800">{{ formatDateTimeIndo(selectedPo.created_at) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Estimasi Master Gizi</span>
                            <span class="font-bold font-mono text-slate-800">{{ formatRupiah(grandTotalMasterBiaya) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Total Aktual Verifikasi</span>
                            <span class="font-black font-mono text-emerald-800 text-sm">{{ formatRupiah(totalAktualBiaya) }}</span>
                        </div>
                    </div>

                    <!-- Tabel Item Bahan Baku PO -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead
                                class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                            >
                                <tr>
                                    <th class="p-3 text-center w-10">No</th>
                                    <th class="p-3">Nama Bahan Pangan</th>
                                    <th class="p-3 text-center">Tipe</th>
                                    <th class="p-3 text-right">Kebutuhan (Kg)</th>
                                    <th class="p-3 text-right">Harga Master (Rp/kg)</th>
                                    <th class="p-3 text-right w-36">Harga Aktual Belanja (Rp/kg)</th>
                                    <th class="p-3 text-right">Subtotal Aktual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                                <tr
                                    v-for="(item, idx) in selectedPo.items"
                                    :key="idx"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td class="p-3 text-center font-bold text-slate-400">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ item.nama }}</div>
                                        <div class="text-[10px] text-slate-400">{{ item.kategori }}</div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 text-[9.5px] font-bold rounded-md',
                                                item.tipe === 'Alergi' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600',
                                            ]"
                                        >
                                            {{ item.tipe }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-slate-800">
                                        {{ formatGrossWeight(item.gross_kg) }}
                                    </td>
                                    <td class="p-3 text-right font-mono text-slate-500">
                                        {{ formatRupiah(item.harga_master) }}
                                    </td>
                                    <td class="p-2 text-right">
                                        <div v-if="!isReadOnlyMode" class="relative flex items-center">
                                            <span class="absolute left-2.5 text-xs text-slate-400 font-bold">Rp</span>
                                            <input
                                                v-model.number="item.harga_aktual"
                                                type="number"
                                                step="100"
                                                class="w-full pl-8 pr-2 py-1 text-right text-xs font-mono font-bold border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary/30"
                                            />
                                        </div>
                                        <div v-else class="font-mono font-bold text-slate-800">
                                            {{ formatRupiah(item.harga_aktual || item.harga_master) }}
                                        </div>
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-emerald-800 whitespace-nowrap">
                                        {{ formatRupiah(Math.round(item.gross_kg * (item.harga_aktual !== undefined && item.harga_aktual !== null && item.harga_aktual !== "" ? item.harga_aktual : item.harga_master || 0)) || (item.gross_kg > 0 ? Math.ceil(item.gross_kg * (item.harga_aktual || item.harga_master || 0)) : 0)) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50/80 font-bold border-t border-slate-200">
                                <tr>
                                    <td colspan="3" class="p-3 text-right uppercase text-[11px] text-slate-600">
                                        Total Belanja Bahan:
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-slate-800">
                                        {{ formatGrossWeight(grandTotalGrossKg) }}
                                    </td>
                                    <td class="p-3 text-right font-mono text-slate-500">
                                        {{ formatRupiah(grandTotalMasterBiaya) }}
                                    </td>
                                    <td class="p-3"></td>
                                    <td class="p-3 text-right font-mono font-black text-emerald-900 text-sm whitespace-nowrap">
                                        {{ formatRupiah(totalAktualBiaya) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Riwayat Log Catatan & Verifikasi PO -->
                    <div class="space-y-3 p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <Clock class="h-4 w-4 text-primary" />
                                <span>Riwayat Log Catatan & Telaah Verifikasi ({{ (selectedPo.riwayat_verifikasi?.length || (selectedPo.catatan ? 1 : 0)) }} Entri)</span>
                            </h4>
                        </div>

                        <!-- Daftar Log Riwayat -->
                        <div v-if="selectedPo.riwayat_verifikasi && selectedPo.riwayat_verifikasi.length > 0" class="space-y-2.5 max-h-48 overflow-y-auto pr-1">
                            <div
                                v-for="(log, lIdx) in selectedPo.riwayat_verifikasi"
                                :key="lIdx"
                                :class="[
                                    'p-3 rounded-xl border text-xs space-y-1',
                                    log.status === 'Ditolak'
                                        ? 'bg-rose-50/70 border-rose-200 text-rose-950'
                                        : log.status === 'Terverifikasi' || log.status === 'Siap Produksi'
                                          ? 'bg-emerald-50/70 border-emerald-200 text-emerald-950'
                                          : 'bg-white border-slate-200 text-slate-900'
                                ]"
                            >
                                <div class="flex items-center justify-between gap-2 flex-wrap text-[11px]">
                                    <div class="flex items-center gap-1.5 font-bold">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded text-[10px] font-black',
                                                log.status === 'Ditolak'
                                                    ? 'bg-rose-200 text-rose-900'
                                                    : log.status === 'Terverifikasi' || log.status === 'Siap Produksi'
                                                      ? 'bg-emerald-200 text-emerald-900'
                                                      : 'bg-blue-100 text-blue-800'
                                            ]"
                                        >
                                            {{ log.status }}
                                        </span>
                                        <span class="text-slate-800">{{ log.user_nama }} ({{ log.role || 'Pengguna' }})</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-slate-500 font-mono text-[10.5px] bg-white/80 px-2 py-0.5 rounded border border-slate-200/60 shadow-2xs">
                                        <Clock class="h-3 w-3 text-slate-400 shrink-0" />
                                        <span>{{ formatFullLogTimestamp(log.waktu) }}</span>
                                    </div>
                                </div>
                                <p class="text-xs font-medium pl-1 leading-relaxed whitespace-pre-wrap">
                                    {{ log.catatan || '(Tidak ada catatan tertulis)' }}
                                </p>
                            </div>
                        </div>
                        <div v-else-if="selectedPo.catatan" class="p-3 bg-white rounded-xl border border-slate-200 text-xs">
                            <span class="text-slate-500 font-bold block text-[10.5px]">Catatan Terakhir:</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ selectedPo.catatan }}</p>
                        </div>
                        <div v-else class="text-xs text-slate-400 font-medium py-1">
                            Belum ada riwayat catatan pada pengajuan PO ini.
                        </div>

                        <!-- Form Tambah Catatan Verifikator -->
                        <div v-if="!isReadOnlyMode" class="pt-2 border-t border-slate-200 space-y-1.5">
                            <label class="text-xs font-bold text-slate-800 block">
                                Tambah Catatan Verifikasi / Instruksi Revisi:
                            </label>
                            <textarea
                                v-model="inputCatatanBaru"
                                rows="2"
                                placeholder="Tulis instruksi revisi, alasan penolakan, atau persetujuan harga belanja di sini..."
                                class="w-full p-2.5 text-xs rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 shadow-2xs font-medium"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-3"
                >
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer w-full sm:w-auto"
                    >
                        Tutup
                    </button>
                    <div v-if="!isReadOnlyMode" class="flex items-center gap-2 flex-wrap w-full sm:w-auto justify-end">
                        <!-- 1. Tolak PO -->
                        <button
                            type="button"
                            @click="rejectPo"
                            :disabled="isProcessing"
                            class="px-4 py-2 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl cursor-pointer disabled:opacity-50"
                        >
                            Tolak PO
                        </button>

                        <!-- 2. Simpan Draft Verifikasi -->
                        <button
                            type="button"
                            @click="saveAsDraftVerification"
                            :disabled="isProcessing"
                            class="px-4 py-2 text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-xl cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <FileText class="h-3.5 w-3.5" />
                            <span>Simpan Draft Verifikasi</span>
                        </button>

                        <!-- 3. Setujui & Lanjutkan ke Daftar PO -->
                        <button
                            type="button"
                            @click="approveAndProceedToPoList"
                            :disabled="isProcessing"
                            class="px-5 py-2 text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            <span>Setujui & Lanjutkan ke Daftar PO</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
