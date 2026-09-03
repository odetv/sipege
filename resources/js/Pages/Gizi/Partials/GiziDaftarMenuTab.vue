<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import {
    exportWorkOrderExcel,
    exportWorkOrderWord,
    exportWorkOrderPdf,
} from "@/Services/exportDocHelper";
import {
    FileSpreadsheet,
    Users,
    Activity,
    Coins,
    Search,
    Plus,
    CheckCircle2,
    Clock,
    FileText,
    FilePenLine,
    Printer,
    Send,
    Eye,
    Edit3,
    Trash2,
    AlertCircle,
    Utensils,
    School,
    ShieldAlert,
    X,
    Sparkles,
    Lock,
    XCircle,
    Calendar,
} from "lucide-vue-next";

const props = defineProps({
    workOrdersList: {
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

const emit = defineEmits(["openRancangMenu"]);

const searchDaftarMenu = ref("");
const statusFilterDaftarMenu = ref("semua");

// State Modal Detail
const showDetailModal = ref(false);
const selectedMenu = ref(null);
const detailActiveTab = ref("resep"); // 'resep' | 'pm' | 'akg'

// State Modal Konfirmasi Hapus
const showDeleteConfirmModal = ref(false);
const menuToDelete = ref(null);
const isDeleting = ref(false);

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

// Helper hitung berat bersih total item
function getItemNetKg(it, menu) {
    if (it.total_net_kg !== undefined && Number(it.total_net_kg) > 0) return Number(it.total_net_kg);
    const pk = Number(it.gram_pk !== undefined ? it.gram_pk : (it.gram_bersih_pk || 0)) || 0;
    const pb = Number(it.gram_pb !== undefined ? it.gram_pb : (it.gram_bersih_pb || 0)) || 0;
    const targetPK = it.tipe_porsi === 'alergi' ? (Number(menu?.total_alergi_pk) || 1) : (Number(menu?.porsi_pk) || 0);
    const targetPB = it.tipe_porsi === 'alergi' ? (Number(menu?.total_alergi_pb) || 1) : (Number(menu?.porsi_pb) || 0);
    return ((pk * targetPK) + (pb * targetPB)) / 1000;
}

// Helper hitung kebutuhan kotor item jika data lama di db bernilai 0
function getItemGrossKg(it, menu) {
    const val = Number(it.total_gross_kg !== undefined ? it.total_gross_kg : it.gross_kg) || 0;
    if (val > 0) return val;
    const pk = Number(it.gram_pk) || 0;
    const pb = Number(it.gram_pb) || 0;
    const bdd = (Number(it.bdd) || 100) / 100;
    const buffer = 1 + (Number(it.buffer) || 0) / 100;
    const targetPK = it.tipe_porsi === 'alergi' ? (Number(menu?.total_alergi_pk) || 1) : (Number(menu?.porsi_pk) || 0);
    const targetPB = it.tipe_porsi === 'alergi' ? (Number(menu?.total_alergi_pb) || 1) : (Number(menu?.porsi_pb) || 0);
    const grossPK = ((pk / bdd) * buffer * targetPK) / 1000;
    const grossPB = ((pb / bdd) * buffer * targetPB) / 1000;
    return grossPK + grossPB;
}

function getItemSubtotal(it, menu) {
    const rawSubtotal = Number(it.subtotal_master !== undefined ? it.subtotal_master : it.subtotal_aktual) || 0;
    if (rawSubtotal > 0) return rawSubtotal;
    const gross = getItemGrossKg(it, menu);
    const harga = Number(it.harga_master || it.harga_aktual) || 0;
    const calc = Math.round(gross * harga);
    if (gross > 0 && harga > 0 && calc === 0) {
        return Math.ceil(gross * harga);
    }
    return calc;
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

const daftarMenuList = computed(() => {
    if (props.workOrdersList && props.workOrdersList.length > 0) {
        return props.workOrdersList.map((wo, i) => ({
            id: wo.nomor_wo,
            db_id: wo.id,
            uuid: wo.uuid || wo.id,
            nama: wo.nama_menu,
            tanggal: typeof wo.tanggal_distribusi === 'string' ? wo.tanggal_distribusi : (wo.tanggal_distribusi ? wo.tanggal_distribusi.substring(0, 10) : ''),
            siklus: 'Hari ke-' + (wo.siklus_ke || (i + 1)),
            porsi_pk: wo.total_pk,
            porsi_pb: wo.total_pb,
            total_porsi: wo.total_pm,
            total_kelompok: wo.total_kelompok || (wo.kelompoks ? wo.kelompoks.length : 0),
            total_alergi: wo.total_alergi || 0,
            energi_pk: wo.akg_pk?.energi || 0,
            protein_pk: wo.akg_pk?.protein || 0,
            lemak_pk: wo.akg_pk?.lemak || 0,
            karbo_pk: wo.akg_pk?.karbohidrat || 0,
            serat_pk: wo.akg_pk?.serat || 0,
            energi_pb: wo.akg_pb?.energi || 0,
            protein_pb: wo.akg_pb?.protein || 0,
            lemak_pb: wo.akg_pb?.lemak || 0,
            karbo_pb: wo.akg_pb?.karbohidrat || 0,
            serat_pb: wo.akg_pb?.serat || 0,
            cost_pk: wo.food_cost_pk || 0,
            cost_pb: wo.food_cost_pb || 0,
            total_anggaran: wo.total_anggaran_master || 0,
            status_akg: 'memenuhi',
            status_wo: wo.status || 'Draft',
            catatan_keuangan: wo.catatan_keuangan || wo.catatan || '',
            created_at: wo.created_at,
            updated_at: wo.updated_at,
            diajukan_pada: wo.diajukan_pada,
            disetujui_pada: wo.disetujui_pada,
            ditolak_pada: wo.ditolak_pada,
            riwayat_verifikasi: wo.riwayat_verifikasi || (wo.purchase_order ? wo.purchase_order.riwayat_verifikasi : []) || [],
            po_no: wo.purchase_order ? wo.purchase_order.nomor_po : ('PO-' + wo.nomor_wo.replace('WO-MBG-', '')),
            items: wo.items || [],
            kelompoks: wo.kelompoks || [],
            raw: wo,
        }));
    }
    return [];
});

const filteredDaftarMenu = computed(() => {
    return daftarMenuList.value.filter((m) => {
        const matchSearch =
            !searchDaftarMenu.value ||
            m.id.toLowerCase().includes(searchDaftarMenu.value.toLowerCase()) ||
            m.nama.toLowerCase().includes(searchDaftarMenu.value.toLowerCase()) ||
            m.tanggal.includes(searchDaftarMenu.value);
        const matchStatus =
            statusFilterDaftarMenu.value === "semua" ||
            m.status_wo.toLowerCase().includes(statusFilterDaftarMenu.value.toLowerCase());
        return matchSearch && matchStatus;
    });
});

function openDetailModal(m) {
    selectedMenu.value = m;
    detailActiveTab.value = "resep";
    showDetailModal.value = true;
}

function handleEditWo(m) {
    if (m.status_wo === 'Diajukan ke Keuangan') {
        alert('Rancangan menu ini sedang dalam proses verifikasi Keuangan dan tidak dapat diedit saat ini.');
        return;
    }
    if (m.status_wo === 'Siap Produksi' || m.status_wo === 'Terverifikasi') {
        alert('Rancangan menu ini telah disetujui/terverifikasi oleh Keuangan dan tidak dapat diedit lagi.');
        return;
    }
    if (m.uuid || m.db_id) {
        router.visit('/gizi/rancang-menu?wo_id=' + (m.uuid || m.db_id));
    } else {
        emit('openRancangMenu');
    }
}

function confirmDeleteWo(m) {
    const statusLower = (m.status_wo || '').toLowerCase();
    const canDelete = statusLower === 'draft' || statusLower.includes('ditolak');
    if (!canDelete) {
        alert(`Menu berstatus "${m.status_wo}" tidak dapat dihapus. Hanya menu yang belum siap produksi / belum di-ACC (Draft atau Ditolak) yang dapat dihapus.`);
        return;
    }
    menuToDelete.value = m;
    showDeleteConfirmModal.value = true;
}

function executeDeleteWo() {
    if (!menuToDelete.value) return;
    const woTarget = menuToDelete.value.uuid || menuToDelete.value.db_id;
    if (woTarget) {
        isDeleting.value = true;
        router.delete('/gizi/work-order/' + woTarget, {
            preserveScroll: true,
            onSuccess: () => {
                isDeleting.value = false;
                showDeleteConfirmModal.value = false;
                menuToDelete.value = null;
            },
            onError: () => {
                isDeleting.value = false;
            }
        });
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Metrics Ringkasan Menu -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100"
                    >
                        <FileSpreadsheet class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            Total Menu Terencana
                        </p>
                        <h3 class="text-lg sm:text-xl font-extrabold text-blue-900 mt-0.5">
                            {{ daftarMenuList.length }} Menu WO
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100"
                    >
                        <Users class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            Total Porsi Sasaran
                        </p>
                        <h3 class="text-lg sm:text-xl font-extrabold text-emerald-900 mt-0.5">
                            {{
                                daftarMenuList
                                    .reduce((acc, m) => acc + (Number(m.total_porsi) || 0), 0)
                                    .toLocaleString("id-ID")
                            }} Porsi
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100"
                    >
                        <Coins class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            Pagu Standar PK
                        </p>
                        <h3 class="text-lg sm:text-xl font-bold text-amber-900 mt-0.5">
                            Rp 8.000 <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1 py-0.5 rounded">Standar BGN</span>
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100"
                    >
                        <Utensils class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                            Pagu Standar PB
                        </p>
                        <h3 class="text-lg sm:text-xl font-bold text-indigo-900 mt-0.5">
                            Rp 10.000 <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1 py-0.5 rounded">Standar BGN</span>
                        </h3>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Card Tabel Utama Daftar Menu -->
        <div class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[170px]">Kode WO & Tanggal</th>
                            <th class="py-3.5 px-5 min-w-[260px]">Nama Menu & Kandungan</th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">Sasaran Porsi</th>
                            <th class="py-3.5 px-4 text-right min-w-[130px]">Biaya / Porsi</th>
                            <th class="py-3.5 px-4 min-w-[170px]">Riwayat Waktu</th>
                            <th class="py-3.5 px-4 text-center min-w-[120px]">Status Telaah</th>
                            <th class="py-3.5 px-4 text-center min-w-[210px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="(menu, index) in filteredDaftarMenu"
                            :key="menu.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- 1. No Urut -->
                            <td class="py-4 px-4 text-center font-bold text-slate-400">
                                {{ index + 1 }}
                            </td>

                            <!-- 2. Kode WO & Tanggal Distribusi -->
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    <div>
                                        <span class="font-mono font-bold text-xs text-primary bg-primary/10 px-2 py-0.5 rounded inline-block">
                                            {{ menu.id }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700 flex items-center gap-1">
                                        <Calendar class="h-3 w-3 text-slate-400 shrink-0" />
                                        <span>{{ formatTanggalIndo(menu.tanggal) }}</span>
                                    </p>
                                </div>
                            </td>

                            <!-- 3. Nama Menu & Kandungan Nutrisi -->
                            <td class="py-4 px-5 max-w-sm">
                                <div class="space-y-1">
                                    <p class="font-bold text-slate-900 leading-snug text-xs sm:text-sm">
                                        {{ menu.nama }}
                                    </p>
                                    <div class="flex items-center gap-1.5 flex-wrap text-[11px] font-medium text-slate-500">
                                        <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px]">
                                            PK: {{ menu.energi_pk }} kkal • {{ menu.protein_pk }}g Prot
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-bold text-[10px]">
                                            PB: {{ menu.energi_pb }} kkal • {{ menu.protein_pb }}g Prot
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 4. Sasaran Porsi -->
                            <td class="py-4 px-4 text-center">
                                <span class="font-black text-slate-900 block text-xs">
                                    {{ Number(menu.total_porsi || 0).toLocaleString("id-ID") }} PM
                                </span>
                                <span class="text-[10px] text-slate-500 block mt-0.5">
                                    {{ menu.porsi_pk }} PK / {{ menu.porsi_pb }} PB
                                </span>
                            </td>

                            <!-- 5. Biaya / Porsi -->
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <div class="text-[11px] font-bold text-amber-800 font-mono">
                                    PK: {{ formatRupiah(menu.cost_pk) }}
                                </div>
                                <div class="text-[11px] font-bold text-indigo-800 font-mono mt-0.5">
                                    PB: {{ formatRupiah(menu.cost_pb) }}
                                </div>
                            </td>

                            <!-- 6. Riwayat Waktu -->
                            <td class="py-4 px-4 whitespace-nowrap text-[10.5px]">
                                <div class="text-slate-500">
                                    <span class="text-slate-400">Dibuat:</span> {{ formatDateTimeIndo(menu.created_at) }}
                                </div>
                                <div class="text-slate-500 mt-0.5">
                                    <span class="text-slate-400">Diperbarui:</span> {{ formatDateTimeIndo(menu.updated_at) }}
                                </div>
                                <div v-if="menu.diajukan_pada && menu.status_wo === 'Diajukan ke Keuangan'" class="text-blue-700 font-semibold mt-0.5">
                                    Diajukan: {{ formatDateTimeIndo(menu.diajukan_pada) }}
                                </div>
                                <div v-if="menu.disetujui_pada && (menu.status_wo === 'Siap Produksi' || menu.status_wo === 'Terverifikasi')" class="text-emerald-700 font-semibold mt-0.5">
                                    Disetujui: {{ formatDateTimeIndo(menu.disetujui_pada) }}
                                </div>
                                <div v-if="menu.ditolak_pada && menu.status_wo === 'Ditolak'" class="text-rose-700 font-semibold mt-0.5">
                                    Ditolak: {{ formatDateTimeIndo(menu.ditolak_pada) }}
                                </div>
                            </td>

                            <!-- 7. Status Telaah -->
                            <td class="py-4 px-4 text-center">
                                <span
                                    :class="[
                                        'px-2.5 py-1 text-[10.5px] font-bold rounded-lg border inline-block whitespace-nowrap',
                                        menu.status_wo === 'Siap Produksi' || menu.status_wo === 'Terverifikasi'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                            : menu.status_wo === 'Diajukan ke Keuangan'
                                              ? 'bg-blue-50 text-blue-700 border-blue-300'
                                              : menu.status_wo === 'Ditolak'
                                                ? 'bg-rose-50 text-rose-700 border-rose-300'
                                                : 'bg-amber-50 text-amber-700 border-amber-300',
                                    ]"
                                >
                                    {{ menu.status_wo }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <!-- 1. Tombol Lihat Detail -->
                                    <button
                                        type="button"
                                        @click="openDetailModal(menu)"
                                        class="h-8 w-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Lihat Detail Resep, Sasaran PM & Waktu"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>

                                    <!-- 2. Tombol Edit (Bisa jika Draft atau Ditolak Keuangan) -->
                                    <button
                                        v-if="menu.status_wo === 'Draft' || menu.status_wo.toLowerCase().includes('ditolak')"
                                        type="button"
                                        @click="handleEditWo(menu)"
                                        class="h-8 w-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        :title="menu.status_wo.toLowerCase().includes('ditolak') ? 'Perbaiki & Edit Menu yang Ditolak' : 'Buka & Edit Formulasi Rancang Menu'"
                                    >
                                        <Edit3 class="h-4 w-4" />
                                    </button>
                                    <span
                                        v-else
                                        class="h-8 px-2 rounded-lg text-[10.5px] font-bold text-slate-400 bg-slate-100 border border-slate-200 flex items-center justify-center gap-1 cursor-not-allowed shadow-2xs"
                                        :title="menu.status_wo === 'Diajukan ke Keuangan' ? 'Sedang diverifikasi Keuangan (Terkunci)' : 'Telah disetujui Keuangan (Terkunci)'"
                                    >
                                        <Lock class="h-3.5 w-3.5 text-slate-400" />
                                    </span>

                                    <!-- 3. Tombol Hapus (Bisa jika Draft atau Ditolak Keuangan) -->
                                    <button
                                        v-if="menu.status_wo === 'Draft' || menu.status_wo.toLowerCase().includes('ditolak')"
                                        type="button"
                                        @click="confirmDeleteWo(menu)"
                                        class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Hapus Rancangan Menu Ini"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>

                                    <!-- Divider Pemisah -->
                                    <span class="h-4 w-px bg-slate-200 mx-0.5"></span>

                                    <!-- 4. Export Excel -->
                                    <button
                                        type="button"
                                        @click="exportWorkOrderExcel(menu)"
                                        class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download Dokumen Excel (.xls)"
                                    >
                                        <FileSpreadsheet class="h-4 w-4" />
                                    </button>

                                    <!-- 5. Export Word -->
                                    <button
                                        type="button"
                                        @click="exportWorkOrderWord(menu)"
                                        class="h-8 w-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download Dokumen Word (.doc)"
                                    >
                                        <FilePenLine class="h-4 w-4" />
                                    </button>

                                    <!-- 6. Export PDF -->
                                    <button
                                        type="button"
                                        @click="exportWorkOrderPdf(menu)"
                                        class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download / Cetak PDF"
                                    >
                                        <FileText class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredDaftarMenu.length === 0">
                            <td
                                colspan="8"
                                class="p-10 text-center text-slate-400 font-semibold"
                            >
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <FileSpreadsheet class="h-8 w-8 text-slate-300" />
                                    <p>Tidak ada menu yang sesuai dengan filter pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL PREVIEW DETAIL LENGKAP WORK ORDER -->
        <Modal
            :show="showDetailModal"
            max-width="4xl"
            @close="showDetailModal = false"
        >
            <div v-if="selectedMenu" class="p-6 space-y-6 max-h-[90vh] overflow-y-auto">
                <!-- Header Modal -->
                <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded">
                                {{ selectedMenu.id }}
                            </span>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 text-xs font-bold rounded-lg border',
                                    selectedMenu.status_wo === 'Siap Produksi' || selectedMenu.status_wo === 'Terverifikasi'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : selectedMenu.status_wo === 'Diajukan ke Keuangan'
                                          ? 'bg-blue-50 text-blue-700 border-blue-200'
                                          : selectedMenu.status_wo === 'Ditolak'
                                            ? 'bg-rose-50 text-rose-700 border-rose-200'
                                            : 'bg-amber-50 text-amber-700 border-amber-200',
                                ]"
                            >
                                {{ selectedMenu.status_wo }}
                            </span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 leading-snug">
                            {{ selectedMenu.nama }}
                        </h3>
                        <p class="text-xs text-slate-500 font-medium">
                            Jadwal Distribusi: <strong class="text-slate-800">{{ formatTanggalIndo(selectedMenu.tanggal) }}</strong>
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Log Riwayat Catatan & Verifikasi Keuangan (Audit Trail) -->
                <div
                    v-if="selectedMenu.catatan_keuangan || (selectedMenu.riwayat_verifikasi && selectedMenu.riwayat_verifikasi.length > 0) || selectedMenu.status_wo.toLowerCase().includes('ditolak')"
                    class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 text-xs space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-black text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <Clock class="h-4 w-4 text-primary shrink-0" />
                            Riwayat Catatan & Status Verifikasi
                        </span>
                        <span class="text-[10.5px] font-bold text-slate-500">
                            {{ selectedMenu.riwayat_verifikasi?.length || 1 }} Riwayat Tercatat
                        </span>
                    </div>

                    <!-- List Log Catatan -->
                    <div v-if="selectedMenu.riwayat_verifikasi && selectedMenu.riwayat_verifikasi.length > 0" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <div
                            v-for="(log, lIdx) in selectedMenu.riwayat_verifikasi"
                            :key="lIdx"
                            :class="[
                                'p-3 rounded-xl border text-xs space-y-1',
                                log.status === 'Ditolak' || log.status?.includes('Ditolak')
                                    ? 'bg-rose-50/80 border-rose-200 text-rose-950'
                                    : log.status === 'Terverifikasi' || log.status === 'Siap Produksi'
                                      ? 'bg-emerald-50/80 border-emerald-200 text-emerald-950'
                                      : 'bg-white border-slate-200 text-slate-900'
                            ]"
                        >
                            <div class="flex items-center justify-between gap-2 flex-wrap text-[11px]">
                                <div class="flex items-center gap-1.5 font-bold">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-black',
                                            log.status === 'Ditolak' || log.status?.includes('Ditolak')
                                                ? 'bg-rose-200 text-rose-900'
                                                : log.status === 'Terverifikasi' || log.status === 'Siap Produksi'
                                                  ? 'bg-emerald-200 text-emerald-900'
                                                  : 'bg-blue-100 text-blue-800'
                                        ]"
                                    >
                                        {{ log.status }}
                                    </span>
                                    <span class="text-slate-800 font-bold">{{ log.user_nama }} ({{ log.role || 'Pengguna' }})</span>
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
                    <!-- Fallback catatan terakhir jika log array belum ada -->
                    <div v-else class="p-3 bg-white rounded-xl border border-rose-200 text-xs">
                        <span class="text-rose-800 font-bold block text-[10.5px]">Catatan Verifikator Keuangan:</span>
                        <p class="font-medium text-slate-800 mt-0.5">{{ selectedMenu.catatan_keuangan }}</p>
                    </div>
                </div>

                <!-- Timeline Log Waktu Lengkap -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs">
                    <div>
                        <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Waktu Dibuat</span>
                        <span class="font-bold text-slate-800">{{ formatDateTimeIndo(selectedMenu.created_at) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Waktu Diperbarui</span>
                        <span class="font-bold text-slate-800">{{ formatDateTimeIndo(selectedMenu.updated_at) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Waktu Diajukan</span>
                        <span class="font-bold text-blue-800">{{ formatDateTimeIndo(selectedMenu.diajukan_pada) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[10.5px] uppercase font-bold block">{{ selectedMenu.status_wo.toLowerCase().includes('ditolak') ? 'Waktu Ditolak' : 'Waktu Disetujui' }}</span>
                        <span :class="selectedMenu.status_wo.toLowerCase().includes('ditolak') ? 'font-bold text-rose-800' : 'font-bold text-emerald-800'">
                            {{ formatDateTimeIndo(selectedMenu.ditolak_pada || selectedMenu.disetujui_pada) }}
                        </span>
                    </div>
                </div>

                <!-- Navigation Tabs di dalam Modal -->
                <!-- Tab Navigation Modal -->
                <div class="flex items-center gap-2 border-b border-slate-200">
                    <button
                        type="button"
                        @click="detailActiveTab = 'resep'"
                        :class="[
                            'px-4 py-2.5 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer',
                            detailActiveTab === 'resep'
                                ? 'border-primary text-primary bg-primary/5'
                                : 'border-transparent text-slate-600 hover:text-slate-900',
                        ]"
                    >
                        <Utensils class="h-3.5 w-3.5" />
                        <span>Bahan Pangan</span>
                    </button>
                    <button
                        type="button"
                        @click="detailActiveTab = 'pm'"
                        :class="[
                            'px-4 py-2.5 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer',
                            detailActiveTab === 'pm'
                                ? 'border-primary text-primary bg-primary/5'
                                : 'border-transparent text-slate-600 hover:text-slate-900',
                        ]"
                    >
                        <School class="h-3.5 w-3.5" />
                        <span>Sasaran KPM</span>
                    </button>
                    <button
                        type="button"
                        @click="detailActiveTab = 'akg'"
                        :class="[
                            'px-4 py-2.5 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer',
                            detailActiveTab === 'akg'
                                ? 'border-primary text-primary bg-primary/5'
                                : 'border-transparent text-slate-600 hover:text-slate-900',
                        ]"
                    >
                        <Activity class="h-3.5 w-3.5" />
                        <span>AKG</span>
                    </button>
                </div>

                <!-- TAB 1: RESEP & BAHAN BAKU -->
                <div v-if="detailActiveTab === 'resep'" class="space-y-4">
                    <div class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white">
                        <div class="overflow-x-auto">
                        <table class="w-full min-w-[1100px] text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none">
                                    <th class="p-3 text-center w-10">No</th>
                                    <th class="p-3 min-w-[150px]">Bahan Pangan</th>
                                    <th class="p-3 text-center min-w-[130px]">Peruntukan</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-center min-w-[90px]">PK / PB (g)</th>
                                    <th class="p-3 text-right bg-amber-50/40 text-amber-950 min-w-[85px]">Kg Bersih</th>
                                    <th class="p-3 text-right bg-blue-50/40 text-blue-950 min-w-[85px]">Kg Kotor</th>
                                    <th class="p-3 text-right min-w-[95px]">Energi (Kkal)</th>
                                    <th class="p-3 text-right min-w-[90px]">Protein (g)</th>
                                    <th class="p-3 text-right min-w-[90px]">Lemak (g)</th>
                                    <th class="p-3 text-right min-w-[90px]">Karbo (g)</th>
                                    <th class="p-3 text-right min-w-[90px]">Serat (g)</th>
                                    <th class="p-3 text-right min-w-[110px]">Harga / Kg</th>
                                    <th class="p-3 text-right min-w-[95px]">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-if="!selectedMenu.items || selectedMenu.items.length === 0">
                                    <td colspan="14" class="p-8 text-center text-slate-400 text-xs font-medium">
                                        Data item bahan formulasi tidak tersedia.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(it, idx) in selectedMenu.items"
                                    :key="idx"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td class="p-3 text-center align-top pt-4 font-bold text-slate-500">{{ idx + 1 }}</td>
                                    <td class="p-3 font-bold text-slate-900 align-top pt-4">
                                        <div>{{ it.nama_po || it.nama }}</div>
                                        <div v-if="it.nama_po && it.nama_po !== it.nama" class="text-[10.5px] text-slate-500 font-medium mt-0.5">
                                            TKPI: {{ it.nama }}
                                        </div>
                                        <span v-if="it.alergen" class="block text-[9.5px] text-amber-700 font-normal mt-0.5">
                                            Alergen: {{ it.alergen }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center align-top pt-3.5">
                                        <span
                                            :class="[
                                                'px-2.5 py-1 text-[10.5px] font-bold rounded-lg border inline-block',
                                                it.tipe_porsi === 'alergi' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-slate-50 text-slate-700 border-slate-200',
                                            ]"
                                        >
                                            {{ it.tipe_porsi === 'alergi' ? ('Alergi: ' + (it.jenis_alergi || 'Khusus')) : 'Normal' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-600 align-top pt-4">{{ it.kategori }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-slate-800 align-top pt-4 whitespace-nowrap">
                                        {{ it.gram_pk || 0 }}g / {{ it.gram_pb || 0 }}g
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-amber-950 bg-amber-50/20 align-top pt-4 whitespace-nowrap">
                                        {{ formatGrossWeight(getItemNetKg(it, selectedMenu)) }}
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-blue-950 bg-blue-50/20 align-top pt-4 whitespace-nowrap">
                                        {{ formatGrossWeight(getItemGrossKg(it, selectedMenu)) }}
                                    </td>
                                    <td class="p-3 text-right align-top pt-3.5 whitespace-nowrap">
                                        <div class="text-[10.5px] text-slate-600 font-medium">PK: {{ it.nutrisi_pk?.energi || 0 }}</div>
                                        <div class="text-[10.5px] text-slate-600 font-medium">PB: {{ it.nutrisi_pb?.energi || 0 }}</div>
                                    </td>
                                    <td class="p-3 text-right align-top pt-3.5 whitespace-nowrap">
                                        <div class="text-[10.5px] text-slate-600 font-medium">PK: {{ it.nutrisi_pk?.protein || 0 }}g</div>
                                        <div class="text-[10.5px] text-slate-600 font-medium">PB: {{ it.nutrisi_pb?.protein || 0 }}g</div>
                                    </td>
                                    <td class="p-3 text-right align-top pt-3.5 whitespace-nowrap">
                                        <div class="text-[10.5px] text-slate-600 font-medium">PK: {{ it.nutrisi_pk?.lemak || 0 }}g</div>
                                        <div class="text-[10.5px] text-slate-600 font-medium">PB: {{ it.nutrisi_pb?.lemak || 0 }}g</div>
                                    </td>
                                    <td class="p-3 text-right align-top pt-3.5 whitespace-nowrap">
                                        <div class="text-[10.5px] text-slate-600 font-medium">PK: {{ it.nutrisi_pk?.karbohidrat || 0 }}g</div>
                                        <div class="text-[10.5px] text-slate-600 font-medium">PB: {{ it.nutrisi_pb?.karbohidrat || 0 }}g</div>
                                    </td>
                                    <td class="p-3 text-right align-top pt-3.5 whitespace-nowrap">
                                        <div class="text-[10.5px] text-slate-600 font-medium">PK: {{ it.nutrisi_pk?.serat || 0 }}g</div>
                                        <div class="text-[10.5px] text-slate-600 font-medium">PB: {{ it.nutrisi_pb?.serat || 0 }}g</div>
                                    </td>
                                    <td class="p-3 text-right font-mono text-slate-600 align-top pt-4 whitespace-nowrap">
                                        {{ formatRupiah(it.harga_master || it.harga_aktual || 0) }}
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-emerald-800 align-top pt-4 whitespace-nowrap">
                                        {{ formatRupiah(getItemSubtotal(it, selectedMenu)) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="selectedMenu.items && selectedMenu.items.length > 0" class="bg-slate-50/80 font-bold border-t border-slate-200">
                                <tr>
                                    <td colspan="5" class="p-3.5 text-right uppercase text-[11px] text-slate-600 font-extrabold">
                                        Grand Total Estimasi Biaya Belanja Bahan:
                                    </td>
                                    <td class="p-3.5 text-right font-mono font-black text-amber-950 bg-amber-100/40">
                                        {{
                                            formatGrossWeight(
                                                selectedMenu.items.reduce((acc, it) => acc + getItemNetKg(it, selectedMenu), 0)
                                            )
                                        }}
                                    </td>
                                    <td class="p-3.5 text-right font-mono font-black text-blue-950 bg-blue-100/40">
                                        {{
                                            formatGrossWeight(
                                                selectedMenu.items.reduce((acc, it) => acc + getItemGrossKg(it, selectedMenu), 0)
                                            )
                                        }}
                                    </td>
                                    <td colspan="5" class="p-3.5 text-center text-slate-500 font-normal text-[10.5px]">
                                        5 Zat Gizi Terhitung
                                    </td>
                                    <td class="p-3.5"></td>
                                    <td class="p-3.5 text-right font-mono font-black text-emerald-900 text-sm whitespace-nowrap">
                                        {{
                                            formatRupiah(
                                                selectedMenu.items.reduce((acc, it) => acc + getItemSubtotal(it, selectedMenu), 0)
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                </div>

                <!-- TAB 2: DETAIL SASARAN PM TERJANGKAU (KELAS & Porsi Alergi) -->
                <div v-if="detailActiveTab === 'pm'" class="space-y-4">
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Daftar Kelompok Sasaran Terjangkau Pada Menu Ini
                            </span>
                            <div class="flex items-center gap-2 text-xs font-bold">
                                <span class="text-slate-600 font-semibold">Total: {{ selectedMenu.kelompoks?.length || 0 }} Kelompok</span>
                                <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    {{ selectedMenu.kelompoks?.filter(k => k.is_menerima !== false).length || 0 }} Menerima
                                </span>
                                <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                    {{ selectedMenu.kelompoks?.filter(k => k.is_menerima === false).length || 0 }} Tidak Menerima
                                </span>
                            </div>
                        </div>
                        <div class="divide-y divide-slate-200">
                            <div v-if="!selectedMenu.kelompoks || selectedMenu.kelompoks.length === 0" class="p-6 text-center text-slate-400 text-xs">
                                Tidak ada data rincian kelompok sasaran pada Work Order ini.
                            </div>
                            <div
                                v-for="(kel, kIdx) in selectedMenu.kelompoks"
                                :key="kIdx"
                                class="p-4 hover:bg-slate-50/50 transition-colors space-y-3"
                            >
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                            {{ kIdx + 1 }}
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-black text-slate-900">{{ kel.nama_kelompok }}</h5>
                                            <span class="text-[11px] text-slate-500 font-medium">Kategori: {{ kel.kategori }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-right">
                                            <span class="text-xs font-black text-slate-900 block">{{ kel.total_penerima }} Porsi PM</span>
                                            <span class="text-[10.5px] text-slate-500 block">{{ kel.porsi_kecil }} PK • {{ kel.porsi_besar }} PB</span>
                                        </div>
                                        <Badge
                                            variant="outline"
                                            :className="kel.is_menerima ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold text-[10px]' : 'bg-slate-100 text-slate-500 border-slate-200 font-bold text-[10px]'"
                                        >
                                            {{ kel.is_menerima ? '✓ Menerima' : 'Tidak Menerima' }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Rincian Per Kelas / Jenjang -->
                                <div v-if="kel.rincian && kel.rincian.length > 0" class="bg-white rounded-lg border border-slate-200/80 p-2.5">
                                    <p class="text-[10px] uppercase font-bold text-slate-500 mb-1.5">Rincian Sasaran per Kelas / Jenjang:</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        <div
                                            v-for="(r, rIdx) in kel.rincian"
                                            :key="rIdx"
                                            class="p-2 bg-slate-50 rounded border border-slate-100 text-xs"
                                        >
                                            <div class="font-bold text-slate-800">{{ r.sub_kategori || r.nama_jenjang || ('Kelas ' + (rIdx + 1)) }}</div>
                                            <div class="text-[10.5px] text-slate-500 mt-0.5">
                                                PK: <strong>{{ r.jumlah_porsi_kecil || r.porsi_kecil || 0 }}</strong> • PB: <strong>{{ r.jumlah_porsi_besar || r.porsi_besar || 0 }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: EVALUASI NUTRISI & AKG BGN -->
                <div v-if="detailActiveTab === 'akg'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- PK AKG -->
                        <div class="p-4 bg-amber-50/40 rounded-xl border border-amber-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <h5 class="text-xs font-black uppercase tracking-wider text-amber-900 flex items-center gap-1.5">
                                    <Activity class="h-4 w-4 text-amber-600" />
                                    <span>Porsi Kecil (PK) • PAUD/TK & SD Kelas 1-3</span>
                                </h5>
                                <Badge variant="outline" className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px]">
                                    ✓ MEMENUHI AKG
                                </Badge>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div class="p-2 bg-white rounded-lg border border-amber-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Energi</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.energi_pk }} kkal</div>
                                    <span class="text-[9.5px] text-slate-400">Target: 450-550</span>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-amber-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Protein</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.protein_pk }} g</div>
                                    <span class="text-[9.5px] text-slate-400">Target: 15-22g</span>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-amber-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Lemak</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.lemak_pk }} g</div>
                                    <span class="text-[9.5px] text-slate-400">Target: 12-18g</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2 bg-white rounded-lg border border-amber-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Karbohidrat</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.karbo_pk }} g</div>
                                    <span class="text-[9.5px] text-slate-400">Target: 65-85g</span>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-amber-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Serat</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.serat_pk }} g</div>
                                    <span class="text-[9.5px] text-slate-400">Target: Min 4.0g</span>
                                </div>
                            </div>
                        </div>

                        <!-- PB AKG -->
                        <div class="p-4 bg-indigo-50/40 rounded-xl border border-indigo-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <h5 class="text-xs font-black uppercase tracking-wider text-indigo-900 flex items-center gap-1.5">
                                    <Activity class="h-4 w-4 text-indigo-600" />
                                    <span>Porsi Besar (PB) • SD 4-6, SMP, SMA, & Bumil</span>
                                </h5>
                                <Badge variant="outline" className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px]">
                                    ✓ MEMENUHI AKG
                                </Badge>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div class="p-2 bg-white rounded-lg border border-indigo-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Energi</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.energi_pb }} kkal</div>
                                    <span class="text-[9.5px] text-slate-400">Target: 650-800</span>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-indigo-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Protein</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.protein_pb }} g</div>
                                    <span class="text-[9.5px] text-slate-400">Target: 24-35g</span>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-indigo-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Lemak</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.lemak_pb }} g</div>
                                    <span class="text-[9.5px] text-slate-400">Target: 18-26g</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2 bg-white rounded-lg border border-indigo-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Karbohidrat</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.karbo_pb }} g</div>
                                    <span class="text-[9.5px] text-slate-400">Target: 85-110g</span>
                                </div>
                                <div class="p-2 bg-white rounded-lg border border-indigo-100">
                                    <span class="text-slate-500 text-[10px] uppercase font-bold block">Serat</span>
                                    <div class="font-black text-slate-900 text-sm mt-0.5">{{ selectedMenu.serat_pb }} g</div>
                                    <span class="text-[9.5px] text-slate-400">Target: Min 6.0g</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal Aksi -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-start">
                        <button
                            type="button"
                            @click="exportWorkOrderExcel(selectedMenu)"
                            class="px-3 py-2 text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        >
                            <FileSpreadsheet class="h-4 w-4" />
                            <span>Export Excel</span>
                        </button>
                        <button
                            type="button"
                            @click="exportWorkOrderWord(selectedMenu)"
                            class="px-3 py-2 text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        >
                            <FilePenLine class="h-4 w-4" />
                            <span>Export Word</span>
                        </button>
                        <button
                            type="button"
                            @click="exportWorkOrderPdf(selectedMenu)"
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
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                        >
                            Tutup
                        </button>
                        <button
                            v-if="selectedMenu.status_wo === 'Draft' || selectedMenu.status_wo === 'Ditolak' || selectedMenu.status_wo === 'Ditolak Keuangan'"
                            type="button"
                            @click="handleEditWo(selectedMenu); showDetailModal = false;"
                            class="px-4 py-2 text-xs font-bold bg-primary hover:bg-primary/90 text-white rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer"
                        >
                            <Edit3 class="h-3.5 w-3.5" />
                            <span>Buka & Edit Formulasi Menu</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- MODAL KONFIRMASI HAPUS WORK ORDER -->
        <Modal
            :show="showDeleteConfirmModal"
            max-width="md"
            @close="showDeleteConfirmModal = false"
        >
            <div v-if="menuToDelete" class="p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                        <Trash2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-slate-900">Hapus Menu & Work Order?</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1">
                    <p class="font-bold text-slate-800">{{ menuToDelete.nama }}</p>
                    <p class="text-slate-500 font-mono text-[11px]">{{ menuToDelete.id }} • {{ formatTanggalIndo(menuToDelete.tanggal) }}</p>
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 mt-1">
                        Status: {{ menuToDelete.status_wo }}
                    </span>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showDeleteConfirmModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDeleteWo"
                        :disabled="isDeleting"
                        class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}</span>
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
