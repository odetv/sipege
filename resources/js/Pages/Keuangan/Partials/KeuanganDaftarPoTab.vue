<script setup>
import { ref, computed } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import {
    Receipt,
    ShieldCheck,
    Coins,
    Users,
    Activity,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Printer,
    Send,
    Edit3,
    FileText,
    Clock,
    DollarSign,
    Check,
    RotateCcw,
    Eye,
    X,
} from "lucide-vue-next";

const props = defineProps({
    poList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        required: true,
    },
    formatTanggalIndo: {
        type: Function,
        required: true,
    },
});

// State PO aktif untuk verifikasi / pembelian bahan
const selectedPo = ref(null);
const showDetailModal = ref(false);

const internalPoList = ref([
    {
        id: "PO-20260825-001",
        wo_id: "WO-MBG-20260825",
        tanggal: "2026-08-25",
        menu: "Ayam Goreng Lengkuas, Sayur Bayam Jagung Manis, Tempe Bacem & Pisang Cavendish",
        vendor: "Koperasi Pangan Nusantara SPPG",
        items_count: 6,
        total_nominal: 16839200,
        status_po: "Terverifikasi",
        status_bayar: "Lunas",
        catatan: "Harga beras dan ayam sesuai kontrak rekanan resmi SPPG.",
        items: [
            { id: "TKPI-001", nama: "Beras Giling Putih", tipe: "Normal", kategori: "Serealia", gross_kg: 198.5, harga_master: 14500, harga_aktual: 14500 },
            { id: "TKPI-042", nama: "Daging Ayam Ras Segar", tipe: "Normal", kategori: "Daging", gross_kg: 145.2, harga_master: 38000, harga_aktual: 38000 },
            { id: "TKPI-118", nama: "Tempe Kedelai Murni", tipe: "Normal", kategori: "Kacang-kacangan", gross_kg: 78.4, harga_master: 16000, harga_aktual: 16000 },
            { id: "TKPI-205", nama: "Bayam Hijau Segar", tipe: "Normal", kategori: "Sayuran", gross_kg: 62.0, harga_master: 12000, harga_aktual: 12000 },
            { id: "TKPI-210", nama: "Jagung Manis Pipil", tipe: "Normal", kategori: "Sayuran", gross_kg: 45.0, harga_master: 15000, harga_aktual: 15000 },
            { id: "TKPI-312", nama: "Pisang Cavendish Matang", tipe: "Normal", kategori: "Buah", gross_kg: 210.0, harga_master: 18000, harga_aktual: 18000 },
        ],
    },
    {
        id: "PO-20260826-002",
        wo_id: "WO-MBG-20260826",
        tanggal: "2026-08-26",
        menu: "Ikan Kembung Bakar Kecap, Tumis Buncis Wortel Tempe, Tahu Goreng & Jeruk Manis",
        vendor: "Toko Sembako Berkah Mandiri",
        items_count: 6,
        total_nominal: 17240000,
        status_po: "Siap Produksi",
        status_bayar: "Belum Bayar",
        catatan: "Menunggu verifikasi fisik barang saat penerimaan bahan baku pagi hari.",
        items: [
            { id: "TKPI-001", nama: "Beras Giling Putih", tipe: "Normal", kategori: "Serealia", gross_kg: 198.5, harga_master: 14500, harga_aktual: 14500 },
            { id: "TKPI-088", nama: "Ikan Kembung Segar", tipe: "Normal", kategori: "Ikan/Seafood", gross_kg: 138.0, harga_master: 42000, harga_aktual: 42000 },
            { id: "TKPI-115", nama: "Tahu Putih Segar", tipe: "Normal", kategori: "Kacang-kacangan", gross_kg: 85.0, harga_master: 11000, harga_aktual: 11000 },
            { id: "TKPI-202", nama: "Buncis Segar", tipe: "Normal", kategori: "Sayuran", gross_kg: 55.0, harga_master: 14000, harga_aktual: 14000 },
            { id: "TKPI-208", nama: "Wortel Segar", tipe: "Normal", kategori: "Sayuran", gross_kg: 48.0, harga_master: 13000, harga_aktual: 13000 },
            { id: "TKPI-320", nama: "Jeruk Manis", tipe: "Normal", kategori: "Buah", gross_kg: 195.0, harga_master: 20000, harga_aktual: 20000 },
        ],
    },
    {
        id: "PO-20260827-003",
        wo_id: "WO-MBG-20260827",
        tanggal: "2026-08-27",
        menu: "Semur Telur Ayam & Tahu Tempe, Sayur Sop Komplit, Kerupuk & Pepaya Potong",
        vendor: "Supplier Sayur Mayur Sejahtera",
        items_count: 5,
        total_nominal: 15450000,
        status_po: "Draft PO",
        status_bayar: "Belum Bayar",
        catatan: "Diajukan oleh Tim Gizi. Memerlukan konfirmasi ketersediaan pasokan telur ayam.",
        items: [
            { id: "TKPI-001", nama: "Beras Giling Putih", tipe: "Normal", kategori: "Serealia", gross_kg: 198.5, harga_master: 14500, harga_aktual: 14500 },
            { id: "TKPI-061", nama: "Telur Ayam Ras Segar", tipe: "Normal", kategori: "Telur", gross_kg: 115.0, harga_master: 29000, harga_aktual: 29000 },
            { id: "TKPI-115", nama: "Tahu Putih Segar", tipe: "Normal", kategori: "Kacang-kacangan", gross_kg: 80.0, harga_master: 11000, harga_aktual: 11000 },
            { id: "TKPI-222", nama: "Sayur Sop Komplit", tipe: "Normal", kategori: "Sayuran", gross_kg: 95.0, harga_master: 15000, harga_aktual: 15000 },
            { id: "TKPI-325", nama: "Pepaya California", tipe: "Normal", kategori: "Buah", gross_kg: 180.0, harga_master: 10000, harga_aktual: 10000 },
        ],
    },
]);

const activePoList = computed(() => {
    return props.poList && props.poList.length > 0 ? props.poList : internalPoList.value;
});

function openPoVerification(po) {
    // Copy for editing
    selectedPo.value = JSON.parse(JSON.stringify(po));
    if (!selectedPo.value.items || selectedPo.value.items.length === 0) {
        // Fallback sample items
        selectedPo.value.items = [
            { id: "TKPI-001", nama: "Beras Giling Putih", tipe: "Normal", kategori: "Serealia", gross_kg: 198.5, harga_master: 14500, harga_aktual: 14500 },
            { id: "TKPI-042", nama: "Daging Ayam Ras Segar", tipe: "Normal", kategori: "Daging", gross_kg: 145.2, harga_master: 38000, harga_aktual: 38000 },
            { id: "TKPI-118", nama: "Tempe Kedelai Murni", tipe: "Normal", kategori: "Kacang-kacangan", gross_kg: 78.4, harga_master: 16000, harga_aktual: 16000 },
            { id: "TKPI-205", nama: "Sayuran Segar Campur", tipe: "Normal", kategori: "Sayuran", gross_kg: 85.0, harga_master: 14000, harga_aktual: 14000 },
            { id: "TKPI-312", nama: "Buah Segar Pilihan", tipe: "Normal", kategori: "Buah", gross_kg: 190.0, harga_master: 17000, harga_aktual: 17000 },
        ];
    }
    showDetailModal.value = true;
}

const totalMasterBiaya = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => acc + (item.gross_kg * (item.harga_master || 0)), 0);
});

const totalAktualBiaya = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => acc + (item.gross_kg * (item.harga_aktual || 0)), 0);
});

function approvePo() {
    if (selectedPo.value) {
        selectedPo.value.status_po = "Terverifikasi";
        const idx = internalPoList.value.findIndex(p => p.id === selectedPo.value.id);
        if (idx !== -1) {
            internalPoList.value[idx].status_po = "Terverifikasi";
            internalPoList.value[idx].total_nominal = Math.round(totalAktualBiaya.value);
            internalPoList.value[idx].catatan = selectedPo.value.catatan;
        }
        showDetailModal.value = false;
    }
}

function rejectPo() {
    if (selectedPo.value) {
        selectedPo.value.status_po = "Ditolak";
        const idx = internalPoList.value.findIndex(p => p.id === selectedPo.value.id);
        if (idx !== -1) {
            internalPoList.value[idx].status_po = "Ditolak";
            internalPoList.value[idx].catatan = selectedPo.value.catatan;
        }
        showDetailModal.value = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Card 1: Tabel Utama Daftar PO -->
        <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div>
                    <CardTitle
                        className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                    >
                        <Receipt class="h-5 w-5 text-primary" />
                        <span
                            >Daftar Purchase Order (PO) & Pembelian Bahan Baku</span
                        >
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm">
                        Modul Keuangan untuk verifikasi rincian pesanan pembelian bahan baku makanan bergizi (MBG) dari formulasi tim Gizi.
                    </CardDescription>
                </div>
            </CardHeader>
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[800px] text-left text-xs border-collapse"
                >
                    <thead
                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                    >
                        <tr>
                            <th class="p-3.5">No. PO & WO</th>
                            <th class="p-3.5">Tanggal</th>
                            <th class="p-3.5">Menu Sasaran</th>
                            <th class="p-3.5">Vendor / Rekanan</th>
                            <th class="p-3.5 text-center">Items</th>
                            <th class="p-3.5 text-right">Total Nominal</th>
                            <th class="p-3.5 text-center">Status PO</th>
                            <th class="p-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="po in activePoList"
                            :key="po.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3.5 font-mono font-bold text-primary">
                                <div>{{ po.id }}</div>
                                <div
                                    class="text-[10px] text-slate-400 font-normal"
                                >
                                    {{ po.wo_id }}
                                </div>
                            </td>
                            <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                {{ formatTanggalIndo(po.tanggal) }}
                            </td>
                            <td class="p-3.5 font-semibold text-slate-900">
                                {{ po.menu }}
                            </td>
                            <td class="p-3.5 text-slate-700 font-medium">
                                {{ po.vendor }}
                            </td>
                            <td class="p-3.5 text-center font-bold">
                                {{ po.items_count || po.items?.length || 0 }} Item
                            </td>
                            <td
                                class="p-3.5 text-right font-extrabold text-emerald-800 text-xs"
                            >
                                {{ formatRupiah(po.total_nominal) }}
                            </td>
                            <td class="p-3.5 text-center">
                                <Badge
                                    variant="outline"
                                    :className="
                                        po.status_po === 'Terverifikasi'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : po.status_po === 'Siap Produksi'
                                              ? 'bg-blue-50 text-blue-700 border-blue-200'
                                              : po.status_po === 'Ditolak'
                                                ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                : 'bg-amber-50 text-amber-700 border-amber-200'
                                    "
                                >
                                    {{ po.status_po }}
                                </Badge>
                            </td>
                            <td class="p-3.5 text-center">
                                <Button
                                    type="button"
                                    size="sm"
                                    @click="openPoVerification(po)"
                                    className="bg-primary/10 hover:bg-primary text-primary hover:text-white text-xs font-bold px-3 h-8 rounded-lg transition-colors cursor-pointer"
                                >
                                    <Eye class="h-3.5 w-3.5 mr-1" />
                                    Verifikasi Belanja
                                </Button>
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
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-black text-primary px-2 py-0.5 bg-primary/10 rounded-md">
                                    {{ selectedPo.id }}
                                </span>
                                <Badge
                                    variant="outline"
                                    :className="
                                        selectedPo.status_po === 'Terverifikasi'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : selectedPo.status_po === 'Ditolak'
                                              ? 'bg-rose-50 text-rose-700 border-rose-200'
                                              : 'bg-amber-50 text-amber-700 border-amber-200'
                                    "
                                >
                                    {{ selectedPo.status_po }}
                                </Badge>
                            </div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 mt-1">
                                Verifikasi Pembelian Bahan: {{ selectedPo.menu }}
                            </h3>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-4 sm:p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Metric Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                            <p class="text-[10.5px] font-bold text-slate-500 uppercase">Total Estimasi Master (Tim Gizi)</p>
                            <h4 class="text-base font-black text-slate-900 mt-0.5">
                                {{ formatRupiah(totalMasterBiaya) }}
                            </h4>
                        </div>
                        <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl">
                            <p class="text-[10.5px] font-bold text-blue-700 uppercase">Total Realisasi Aktual (Akuntan)</p>
                            <h4 class="text-base font-black text-blue-950 mt-0.5">
                                {{ formatRupiah(totalAktualBiaya) }}
                            </h4>
                        </div>
                        <div
                            :class="[
                                'p-3.5 border rounded-xl',
                                totalAktualBiaya <= totalMasterBiaya
                                    ? 'bg-emerald-50 border-emerald-200 text-emerald-950'
                                    : 'bg-rose-50 border-rose-200 text-rose-950',
                            ]"
                        >
                            <p class="text-[10.5px] font-bold uppercase tracking-wider">
                                {{ totalAktualBiaya <= totalMasterBiaya ? 'Efisiensi Anggaran' : 'Selisih Lebih Biaya' }}
                            </p>
                            <h4 class="text-base font-black mt-0.5">
                                {{ formatRupiah(Math.abs(totalAktualBiaya - totalMasterBiaya)) }}
                            </h4>
                        </div>
                    </div>

                    <!-- Tabel Item Bahan Baku & Penyesuaian Harga -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 text-[11px] uppercase">
                                <tr>
                                    <th class="p-3 text-center w-10">No</th>
                                    <th class="p-3">Nama Bahan Baku</th>
                                    <th class="p-3 text-center">Kategori</th>
                                    <th class="p-3 text-right">Kebutuhan Kotor</th>
                                    <th class="p-3 text-right">Harga Estimasi (kg)</th>
                                    <th class="p-3 text-right">Harga Beli Aktual (kg)</th>
                                    <th class="p-3 text-right">Subtotal Aktual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(b, idx) in selectedPo.items"
                                    :key="idx"
                                    class="hover:bg-slate-50/60"
                                >
                                    <td class="p-3 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                                    <td class="p-3 font-semibold text-slate-900">
                                        {{ b.nama }}
                                        <span v-if="b.tipe === 'Alergi'" class="ml-1.5 px-1.5 py-0.5 text-[10px] bg-rose-100 text-rose-800 rounded font-bold">Alergi</span>
                                    </td>
                                    <td class="p-3 text-center text-slate-600">{{ b.kategori }}</td>
                                    <td class="p-3 text-right font-mono font-bold text-slate-800">{{ b.gross_kg }} kg</td>
                                    <td class="p-3 text-right text-slate-500">{{ formatRupiah(b.harga_master) }}</td>
                                    <td class="p-3 text-right">
                                        <input
                                            type="number"
                                            v-model.number="b.harga_aktual"
                                            class="w-28 text-right font-mono text-xs px-2.5 py-1 border border-slate-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary"
                                        />
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-emerald-800">
                                        {{ formatRupiah(b.gross_kg * (b.harga_aktual || 0)) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Catatan Akuntan -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700">Catatan Verifikasi Akuntan / Vendor:</label>
                        <textarea
                            v-model="selectedPo.catatan"
                            rows="2"
                            placeholder="Tuliskan catatan verifikasi rekanan, syarat pembayaran, atau instruksi penerimaan bahan..."
                            class="w-full text-xs p-3 border border-slate-300 rounded-xl focus:ring-1 focus:ring-primary focus:border-primary"
                        ></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer"
                    >
                        Tutup
                    </button>
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            @click="rejectPo"
                            className="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold px-4 h-9 rounded-xl cursor-pointer"
                        >
                            <XCircle class="h-4 w-4 mr-1.5" />
                            Tolak PO
                        </Button>
                        <Button
                            type="button"
                            @click="approvePo"
                            className="bg-primary hover:bg-primary/90 text-white text-xs font-bold px-5 h-9 rounded-xl shadow-xs cursor-pointer"
                        >
                            <CheckCircle2 class="h-4 w-4 mr-1.5" />
                            Setujui PO (Approve)
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
