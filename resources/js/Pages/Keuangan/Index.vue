<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import {
    Wallet,
    CircleDollarSign,
    Receipt,
    CreditCard,
    TrendingUp,
    TrendingDown,
    Calendar,
    Coins,
    Building2,
    Plus,
    FileText,
    ArrowUpRight,
    ArrowDownRight,
    Search,
    Filter,
    DollarSign,
    CheckCircle2,
    School,
    Users,
    PieChart,
} from "lucide-vue-next";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_kelompok: 0,
            total_penerima: 0,
            total_porsi_kecil: 0,
            total_porsi_besar: 0,
            cost_porsi_kecil: 12000,
            cost_porsi_besar: 15000,
            estimasi_harian_pk: 0,
            estimasi_harian_pb: 0,
            estimasi_harian_total: 0,
            estimasi_bulanan_total: 0,
        }),
    },
});

const activeTab = ref("ringkasan"); // 'ringkasan', 'arus_kas', 'alokasi_pos', 'per_kelompok'

// Transaksi Arus Kas Contoh (Pencatatan Keuangan Operasional)
const transaksiList = ref([
    {
        id: "TRX-001",
        tanggal: "2026-08-24",
        kategori: "Bahan Baku Segar",
        uraian: "Belanja Daging Ayam Segar & Telur Ayam (Vendor Pasar)",
        tipe: "keluar",
        jumlah: 2850000,
        status: "Selesai",
        pj: "Koor. Dapur",
    },
    {
        id: "TRX-002",
        tanggal: "2026-08-24",
        kategori: "Bahan Pokok & Sembako",
        uraian: "Pengadaan Beras Pulen Lokal & Minyak Goreng",
        tipe: "keluar",
        jumlah: 1950000,
        status: "Selesai",
        pj: "Logistik",
    },
    {
        id: "TRX-003",
        tanggal: "2026-08-23",
        kategori: "Penerimaan Dana",
        uraian: "Pencairan Dana Alokasi Operasional SPPG Tahap II",
        tipe: "masuk",
        jumlah: 45000000,
        status: "Selesai",
        pj: "Bendahara",
    },
    {
        id: "TRX-004",
        tanggal: "2026-08-23",
        kategori: "Sayuran & Buah Segar",
        uraian: "Sayuran Bayam, Wortel, Buncis & Buah Pisang Cavendish",
        tipe: "keluar",
        jumlah: 1450000,
        status: "Selesai",
        pj: "Koor. Dapur",
    },
    {
        id: "TRX-005",
        tanggal: "2026-08-22",
        kategori: "Operasional & Gas",
        uraian: "Refill Gas LPG 12kg (4 Tabung) & Listrik Dapur",
        tipe: "keluar",
        jumlah: 920000,
        status: "Selesai",
        pj: "Teknisi",
    },
    {
        id: "TRX-006",
        tanggal: "2026-08-21",
        kategori: "Kemasan & Distribusi",
        uraian: "Box Makanan Food Grade & Plastik Ramah Lingkungan",
        tipe: "keluar",
        jumlah: 850000,
        status: "Selesai",
        pj: "Logistik",
    },
]);

// Pos Alokasi Standar Belanja SPPG
const posAlokasi = [
    { nama: "Bahan Baku Segar & Lauk Pauk", persen: 45, warna: "bg-emerald-500", desc: "Daging, ikan, telur, tempe, tahu" },
    { nama: "Beras & Bahan Pokok (Sembako)", persen: 20, warna: "bg-blue-500", desc: "Beras, minyak, bumbu rempah dapur" },
    { nama: "Sayuran & Buah Segar Lokal", persen: 15, warna: "bg-amber-500", desc: "Sayur hijau, wortel, buah pisang, jeruk, pepaya" },
    { nama: "Kemasan, Kebersihan & Wadah", persen: 8, warna: "bg-indigo-500", desc: "Food container, plastik, sabun higienis" },
    { nama: "Operasional Dapur & Gas LPG", persen: 7, warna: "bg-purple-500", desc: "Bahan bakar gas, listrik, air bersih" },
    { nama: "Distribusi & Transportasi", persen: 5, warna: "bg-rose-500", desc: "Biaya BBM kurir & armada distribusi SPPG" },
];

function formatRupiah(amount) {
    return "Rp " + (Number(amount) || 0).toLocaleString("id-ID");
}
</script>

<template>
    <AppLayout
        title="Keuangan"
        subtitle="Pengelolaan Anggaran, Biaya per Porsi, dan Laporan Keuangan SPPG"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Keuangan" />

        <div class="space-y-6">
            <!-- 1. Header Metrics Card -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Estimasi Biaya Harian -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardContent className="p-4 flex items-center gap-3">
                        <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                            <CircleDollarSign class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Estimasi Belanja / Hari</p>
                            <h3 class="text-lg sm:text-xl font-extrabold text-emerald-800 mt-0.5 truncate">
                                {{ formatRupiah(summary.estimasi_harian_total) }}
                            </h3>
                        </div>
                    </CardContent>
                </Card>

                <!-- Estimasi Biaya Bulanan -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardContent className="p-4 flex items-center gap-3">
                        <div class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                            <Wallet class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Estimasi Belanja / Bulan</p>
                            <h3 class="text-lg sm:text-xl font-extrabold text-blue-800 mt-0.5 truncate">
                                {{ formatRupiah(summary.estimasi_bulanan_total) }}
                            </h3>
                        </div>
                    </CardContent>
                </Card>

                <!-- Unit Cost Porsi Kecil -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardContent className="p-4 flex items-center gap-3">
                        <div class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                            <Coins class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Porsi Kecil (PK)</p>
                            <h3 class="text-lg sm:text-xl font-bold text-amber-800 mt-0.5">
                                {{ formatRupiah(summary.cost_porsi_kecil) }} <span class="text-xs font-normal text-slate-500">/porsi</span>
                            </h3>
                        </div>
                    </CardContent>
                </Card>

                <!-- Unit Cost Porsi Besar -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardContent className="p-4 flex items-center gap-3">
                        <div class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                            <CreditCard class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Porsi Besar (PB)</p>
                            <h3 class="text-lg sm:text-xl font-bold text-indigo-800 mt-0.5">
                                {{ formatRupiah(summary.cost_porsi_besar) }} <span class="text-xs font-normal text-slate-500">/porsi</span>
                            </h3>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- 2. Tab Navigation -->
            <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto pb-px">
                <button
                    type="button"
                    @click="activeTab = 'ringkasan'"
                    :class="[
                        'px-4 py-2.5 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer',
                        activeTab === 'ringkasan'
                            ? 'border-primary text-primary bg-primary/5'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300',
                    ]"
                >
                    <TrendingUp class="h-4 w-4" />
                    <span>Ringkasan & Biaya per Porsi</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'arus_kas'"
                    :class="[
                        'px-4 py-2.5 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer',
                        activeTab === 'arus_kas'
                            ? 'border-primary text-primary bg-primary/5'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300',
                    ]"
                >
                    <Receipt class="h-4 w-4" />
                    <span>Pencatatan Arus Kas</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'alokasi_pos'"
                    :class="[
                        'px-4 py-2.5 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer',
                        activeTab === 'alokasi_pos'
                            ? 'border-primary text-primary bg-primary/5'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300',
                    ]"
                >
                    <PieChart class="h-4 w-4" />
                    <span>Alokasi Pos Belanja</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'per_kelompok'"
                    :class="[
                        'px-4 py-2.5 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer',
                        activeTab === 'per_kelompok'
                            ? 'border-primary text-primary bg-primary/5'
                            : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300',
                    ]"
                >
                    <School class="h-4 w-4" />
                    <span>Anggaran per Kelompok</span>
                </button>
            </div>

            <!-- 3. TAB 1: RINGKASAN & BIAYA PER PORSI -->
            <div v-if="activeTab === 'ringkasan'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Rincian Porsi Kecil -->
                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardHeader className="p-4 border-b border-slate-100 bg-amber-50/50">
                            <div class="flex items-center justify-between">
                                <CardTitle className="text-base font-bold text-amber-950 flex items-center gap-2">
                                    <span>Porsi Kecil (PK)</span>
                                </CardTitle>
                                <Badge variant="outline" className="bg-white text-amber-800 border-amber-300 font-bold">
                                    {{ formatRupiah(summary.cost_porsi_kecil) }} / porsi
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3">
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100">
                                <span class="text-slate-600 font-medium">Total Porsi Kecil Terdaftar:</span>
                                <span class="font-bold text-slate-900">{{ summary.total_porsi_kecil }} Porsi</span>
                            </div>
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100">
                                <span class="text-slate-600 font-medium">Alokasi Biaya Harian:</span>
                                <span class="font-bold text-amber-700">{{ formatRupiah(summary.estimasi_harian_pk) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs py-1">
                                <span class="text-slate-600 font-medium">Alokasi Biaya Bulanan (22 Hari):</span>
                                <span class="font-bold text-amber-800">{{ formatRupiah(summary.estimasi_harian_pk * 22) }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Rincian Porsi Besar -->
                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardHeader className="p-4 border-b border-slate-100 bg-indigo-50/50">
                            <div class="flex items-center justify-between">
                                <CardTitle className="text-base font-bold text-indigo-950 flex items-center gap-2">
                                    <span>Porsi Besar (PB)</span>
                                </CardTitle>
                                <Badge variant="outline" className="bg-white text-indigo-800 border-indigo-300 font-bold">
                                    {{ formatRupiah(summary.cost_porsi_besar) }} / porsi
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3">
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100">
                                <span class="text-slate-600 font-medium">Total Porsi Besar Terdaftar:</span>
                                <span class="font-bold text-slate-900">{{ summary.total_porsi_besar }} Porsi</span>
                            </div>
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100">
                                <span class="text-slate-600 font-medium">Alokasi Biaya Harian:</span>
                                <span class="font-bold text-indigo-700">{{ formatRupiah(summary.estimasi_harian_pb) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs py-1">
                                <span class="text-slate-600 font-medium">Alokasi Biaya Bulanan (22 Hari):</span>
                                <span class="font-bold text-indigo-800">{{ formatRupiah(summary.estimasi_harian_pb * 22) }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- 4. TAB 2: PENCATATAN ARUS KAS -->
            <div v-if="activeTab === 'arus_kas'" class="space-y-4">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <Receipt class="h-5 w-5 text-primary" />
                                <span>Buku Kas & Riwayat Transaksi Operasional SPPG</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Rekam jejak seluruh transaksi pengadaan bahan baku, belanja dapur, dan logistik.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">Kode TRX</th>
                                    <th class="p-3.5">Tanggal</th>
                                    <th class="p-3.5">Kategori Transaksi</th>
                                    <th class="p-3.5">Uraian / Deskripsi</th>
                                    <th class="p-3.5">Penanggung Jawab</th>
                                    <th class="p-3.5 text-right">Nominal</th>
                                    <th class="p-3.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="trx in transaksiList" :key="trx.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-slate-700">{{ trx.id }}</td>
                                    <td class="p-3.5 font-medium text-slate-600">{{ trx.tanggal }}</td>
                                    <td class="p-3.5">
                                        <Badge variant="outline" className="font-semibold text-[11px] bg-slate-50">
                                            {{ trx.kategori }}
                                        </Badge>
                                    </td>
                                    <td class="p-3.5 font-semibold text-slate-900 max-w-sm">{{ trx.uraian }}</td>
                                    <td class="p-3.5 text-slate-600">{{ trx.pj }}</td>
                                    <td class="p-3.5 text-right font-bold text-xs" :class="trx.tipe === 'masuk' ? 'text-emerald-700' : 'text-rose-700'">
                                        {{ trx.tipe === 'masuk' ? '+' : '-' }} {{ formatRupiah(trx.jumlah) }}
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200 text-[10.5px] font-bold">
                                            {{ trx.status }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- 5. TAB 3: ALOKASI POS BELANJA -->
            <div v-if="activeTab === 'alokasi_pos'" class="space-y-4">
                <Card className="bg-white border-slate-200 shadow-xs">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
                        <CardTitle className="text-base sm:text-lg font-bold text-slate-900">
                            Pedoman Alokasi Anggaran Belanja Standar SPPG
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm">
                            Distribusi persentase ideal pembagian biaya operasional penyediaan makanan bergizi.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-6 space-y-4">
                        <div class="space-y-3">
                            <div v-for="pos in posAlokasi" :key="pos.nama" class="space-y-1">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full" :class="pos.warna"></span>
                                        <span>{{ pos.nama }}</span>
                                    </div>
                                    <span>{{ pos.persen }}%</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full" :class="pos.warna" :style="{ width: pos.persen + '%' }"></div>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ pos.desc }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- 6. TAB 4: ANGGARAN PER KELOMPOK -->
            <div v-if="activeTab === 'per_kelompok'" class="space-y-4">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
                        <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <School class="h-5 w-5 text-primary" />
                            <span>Kebutuhan Anggaran per Kelompok Sasaran</span>
                        </CardTitle>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">Nama Kelompok</th>
                                    <th class="p-3.5">Kategori</th>
                                    <th class="p-3.5">Porsi Kecil (PK)</th>
                                    <th class="p-3.5">Porsi Besar (PB)</th>
                                    <th class="p-3.5">Biaya Harian</th>
                                    <th class="p-3.5">Biaya Bulanan (22 Hari)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="k in kelompokList" :key="k.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-bold text-slate-900">{{ k.nama_kelompok }}</td>
                                    <td class="p-3.5">
                                        <Badge variant="outline" className="font-semibold text-xs">
                                            {{ k.kategori }}
                                        </Badge>
                                    </td>
                                    <td class="p-3.5 font-semibold text-amber-700">{{ k.total_porsi_kecil || 0 }} PK</td>
                                    <td class="p-3.5 font-semibold text-indigo-700">{{ k.total_porsi_besar || 0 }} PB</td>
                                    <td class="p-3.5 font-bold text-emerald-800">
                                        {{ formatRupiah((k.total_porsi_kecil || 0) * summary.cost_porsi_kecil + (k.total_porsi_besar || 0) * summary.cost_porsi_besar) }}
                                    </td>
                                    <td class="p-3.5 font-bold text-slate-900">
                                        {{ formatRupiah(((k.total_porsi_kecil || 0) * summary.cost_porsi_kecil + (k.total_porsi_besar || 0) * summary.cost_porsi_besar) * 22) }}
                                    </td>
                                </tr>
                                <tr v-if="kelompokList.length === 0">
                                    <td colspan="6" class="p-8 text-center text-slate-400 font-semibold">
                                        Belum ada data kelompok penerima manfaat.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
