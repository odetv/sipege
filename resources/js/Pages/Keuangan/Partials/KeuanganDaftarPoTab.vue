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
    exportPoExcel,
    exportPoWord,
    exportPoPdf,
} from "@/Services/exportDocHelper";
import {
    Receipt,
    CheckCircle2,
    Clock,
    XCircle,
    Eye,
    ShieldCheck,
    Coins,
    Edit3,
    Check,
    Printer,
    FileSpreadsheet,
    FileText,
    FilePenLine,
    Calendar,
    AlertCircle,
    Building2,
    X,
} from "lucide-vue-next";

const props = defineProps({
    poList: {
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

const activePoList = computed(() => {
    return Array.isArray(props.poList) ? props.poList : [];
});

function openDetailModal(po) {
    selectedPo.value = JSON.parse(JSON.stringify(po));
    if (!selectedPo.value.items) {
        selectedPo.value.items = [];
    }
    showDetailModal.value = true;
}

const grandTotalGrossKg = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => acc + (Number(item.gross_kg) || 0), 0);
});

const totalAktualBiaya = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => acc + ((Number(item.gross_kg) || 0) * (Number(item.harga_aktual) || Number(item.harga_master) || 0)), 0);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Card 1: Tabel Utama Daftar PO Resmi -->
        <div class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[170px]">Kode PO & Tanggal</th>
                            <th class="py-3.5 px-5 min-w-[260px]">Nama Menu & Kandungan</th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">Sasaran Porsi</th>
                            <th class="py-3.5 px-4 text-right min-w-[140px]">Total Belanja (PO)</th>
                            <th class="py-3.5 px-4 min-w-[170px]">Riwayat Persetujuan</th>
                            <th class="py-3.5 px-4 text-center min-w-[120px]">Status Bayar</th>
                            <th class="py-3.5 px-4 text-center min-w-[190px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="(po, index) in activePoList"
                            :key="po.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- 1. No Urut -->
                            <td class="py-4 px-4 text-center font-bold text-slate-400">
                                {{ index + 1 }}
                            </td>

                            <!-- 2. Kode PO, WO & Tanggal Distribusi -->
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    <div>
                                        <span class="font-mono font-bold text-xs text-primary bg-primary/10 px-2 py-0.5 rounded inline-block">
                                            {{ po.id }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] font-mono text-slate-500 font-medium">
                                        Ref: {{ po.wo_id }}
                                    </p>
                                    <p class="text-xs font-semibold text-slate-700 flex items-center gap-1">
                                        <Calendar class="h-3 w-3 text-slate-400 shrink-0" />
                                        <span>{{ formatTanggalIndo(po.tanggal) }}</span>
                                    </p>
                                </div>
                            </td>

                            <!-- 3. Nama Menu & Kandungan Nutrisi -->
                            <td class="py-4 px-5 max-w-sm">
                                <div class="space-y-1">
                                    <p class="font-bold text-slate-900 leading-snug text-xs sm:text-sm">
                                        {{ po.menu }}
                                    </p>
                                    <div v-if="po.energi_pk || po.energi_pb" class="flex items-center gap-1.5 flex-wrap text-[11px] font-medium text-slate-500">
                                        <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px]">
                                            PK: {{ po.energi_pk || 0 }} kkal • {{ po.protein_pk || 0 }}g Prot
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-bold text-[10px]">
                                            PB: {{ po.energi_pb || 0 }} kkal • {{ po.protein_pb || 0 }}g Prot
                                        </span>
                                    </div>
                                    <div class="text-[10.5px] text-slate-500 font-normal">
                                        Vendor: <span class="font-semibold text-slate-700">{{ po.vendor || 'Rekanan Pangan SPPG' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- 4. Sasaran Porsi -->
                            <td class="py-4 px-4 text-center">
                                <span class="font-black text-slate-900 block text-xs">
                                    {{ Number(po.total_porsi || 0).toLocaleString("id-ID") }} PM
                                </span>
                                <span class="text-[10px] text-slate-500 block mt-0.5">
                                    {{ po.porsi_pk || 0 }} PK / {{ po.porsi_pb || 0 }} PB
                                </span>
                            </td>

                            <!-- 5. Total Belanja (PO) -->
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <div class="font-mono font-black text-emerald-900 text-xs sm:text-[13px]">
                                    {{ formatRupiah(po.total_nominal) }}
                                </div>
                                <div class="text-[10.5px] font-bold text-slate-500 mt-0.5">
                                    {{ po.items_count || po.items?.length || 0 }} Item Bahan
                                </div>
                            </td>

                            <!-- 6. Riwayat Persetujuan -->
                            <td class="py-4 px-4 whitespace-nowrap text-[10.5px]">
                                <div class="text-emerald-700 font-semibold">
                                    Disetujui: {{ formatDateTimeIndo(po.diverifikasi_pada || po.updated_at) }}
                                </div>
                                <div class="text-slate-400 text-[10px] mt-0.5">
                                    Dibuat: {{ formatDateTimeIndo(po.created_at) }}
                                </div>
                            </td>

                            <!-- 7. Status Bayar -->
                            <td class="py-4 px-4 text-center">
                                <span
                                    :class="[
                                        'px-2.5 py-1 text-[10.5px] font-bold rounded-lg border inline-block whitespace-nowrap',
                                        po.status_bayar === 'Lunas'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                            : 'bg-amber-50 text-amber-700 border-amber-300',
                                    ]"
                                >
                                    {{ po.status_bayar || 'Belum Bayar' }}
                                </span>
                            </td>

                            <!-- 8. Aksi (Detail, Excel, Word, PDF) -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <!-- 1. Tombol Detail -->
                                    <button
                                        type="button"
                                        @click="openDetailModal(po)"
                                        class="h-8 w-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Lihat Detail Rincian PO Resmi"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>

                                    <!-- Divider Pemisah -->
                                    <span class="h-4 w-px bg-slate-200 mx-0.5"></span>

                                    <!-- 2. Export Excel -->
                                    <button
                                        type="button"
                                        @click="exportPoExcel(po)"
                                        class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download Dokumen Excel (.xls)"
                                    >
                                        <FileSpreadsheet class="h-4 w-4" />
                                    </button>

                                    <!-- 3. Export Word -->
                                    <button
                                        type="button"
                                        @click="exportPoWord(po)"
                                        class="h-8 w-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download Dokumen Word (.doc)"
                                    >
                                        <FilePenLine class="h-4 w-4" />
                                    </button>

                                    <!-- 4. Export PDF -->
                                    <button
                                        type="button"
                                        @click="exportPoPdf(po)"
                                        class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download / Cetak PDF"
                                    >
                                        <FileText class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="activePoList.length === 0">
                            <td colspan="8" class="p-12 text-center text-slate-400 font-medium">
                                <div class="flex flex-col items-center justify-center gap-2.5">
                                    <Receipt class="h-10 w-10 text-slate-300" />
                                    <p class="text-sm font-semibold text-slate-600">Belum ada Purchase Order (PO) resmi yang disetujui.</p>
                                    <p class="text-xs text-slate-400 max-w-sm">Silakan buka sub-menu <strong>Verifikasi PO</strong> untuk menyetujui pengajuan belanja dari Tim Gizi dan menerbitkannya ke Daftar PO resmi ini.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Detail PO Resmi (Read Only) -->
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
                            class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"
                        >
                            <Receipt class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-black text-slate-900">
                                    Purchase Order Resmi ({{ selectedPo.id }})
                                </h3>
                                <Badge
                                    variant="outline"
                                    className="bg-emerald-50 text-emerald-700 border-emerald-300 font-bold"
                                >
                                    ✓ Disetujui & Siap Belanja
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
                    <!-- Detail Info PO & Waktu Lengkap -->
                    <div
                        class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs"
                    >
                        <div>
                            <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Tanggal Distribusi</span>
                            <span class="font-bold text-slate-800">{{ formatTanggalIndo(selectedPo.tanggal) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Vendor / Rekanan</span>
                            <span class="font-bold text-slate-800">{{ selectedPo.vendor || 'Rekanan Pangan SPPG' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Waktu Disetujui</span>
                            <span class="font-bold text-emerald-800">{{ formatDateTimeIndo(selectedPo.diverifikasi_pada || selectedPo.updated_at) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Total Anggaran PO</span>
                            <span class="font-black font-mono text-emerald-900 text-sm">{{ formatRupiah(totalAktualBiaya || selectedPo.total_nominal) }}</span>
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
                                    <th class="p-3 text-right">Harga Disetujui (Rp/kg)</th>
                                    <th class="p-3 text-right">Subtotal Belanja</th>
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
                                    <td class="p-3 text-right font-mono text-slate-700">
                                        {{ formatRupiah(item.harga_aktual || item.harga_master) }}
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-emerald-800 whitespace-nowrap">
                                        {{ formatRupiah(item.gross_kg * (item.harga_aktual || item.harga_master || 0)) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50/80 font-bold border-t border-slate-200">
                                <tr>
                                    <td colspan="3" class="p-3 text-right uppercase text-[11px] text-slate-600">
                                        Total Belanja Bahan Disetujui:
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-slate-800">
                                        {{ formatGrossWeight(grandTotalGrossKg) }}
                                    </td>
                                    <td class="p-3"></td>
                                    <td class="p-3 text-right font-mono font-black text-emerald-900 text-sm whitespace-nowrap">
                                        {{ formatRupiah(totalAktualBiaya || selectedPo.total_nominal) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Catatan Verifikator -->
                    <div v-if="selectedPo.catatan" class="space-y-1">
                        <span class="text-xs font-bold text-slate-700 block">Catatan Verifikator Keuangan:</span>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 font-medium">
                            {{ selectedPo.catatan }}
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-start">
                        <button
                            type="button"
                            @click="exportPoExcel(selectedPo)"
                            class="px-3 py-2 text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        >
                            <FileSpreadsheet class="h-4 w-4" />
                            <span>Export Excel</span>
                        </button>
                        <button
                            type="button"
                            @click="exportPoWord(selectedPo)"
                            class="px-3 py-2 text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        >
                            <FilePenLine class="h-4 w-4" />
                            <span>Export Word</span>
                        </button>
                        <button
                            type="button"
                            @click="exportPoPdf(selectedPo)"
                            class="px-3 py-2 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        >
                            <FileText class="h-4 w-4" />
                            <span>Cetak PDF</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button
                            type="button"
                            @click="showDetailModal = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer"
                        >
                            Tutup
                        </button>
                        <button
                            type="button"
                            @click="exportPoPdf(selectedPo)"
                            class="px-4 py-2 text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer"
                        >
                            <Printer class="h-3.5 w-3.5" />
                            <span>Cetak Purchase Order</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
