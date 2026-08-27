<script setup>
import { ref, computed, watch } from "vue";
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
    Coins,
    Building2,
    Plus,
    FileText,
    ArrowUpRight,
    ArrowDownRight,
    Search,
    Filter,
    CheckCircle2,
    School,
    Users,
    PieChart,
    ShoppingCart,
    BookOpen,
    Landmark,
    Banknote,
    Package,
    Sliders,
    Building,
    FileCheck2,
    ShieldCheck,
    FileSignature,
    Printer,
    Download,
    CalendarDays,
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
    activeTab: {
        type: String,
        default: "anggaran",
    },
    summary: {
        type: Object,
        default: () => ({
            total_kelompok: 0,
            total_penerima: 0,
            total_porsi_kecil: 0,
            total_porsi_besar: 0,
            cost_porsi_kecil: 8000,
            cost_porsi_besar: 10000,
            estimasi_harian_pk: 0,
            estimasi_harian_pb: 0,
            estimasi_harian_total: 0,
            estimasi_bulanan_total: 0,
        }),
    },
});

const normalizeTab = (tab) => {
    if (tab === "daftar-po" || tab === "daftar_po") return "daftar_po";
    if (tab === "ringkasan" || tab === "anggaran") return "anggaran";
    if (tab === "transaksi" || tab === "arus_kas") return "transaksi";
    if (tab === "bku") return "bku";
    if (tab === "bp-bank" || tab === "bp_bank") return "bp_bank";
    if (tab === "bp-petty-cash" || tab === "bp_petty_cash") return "bp_petty_cash";
    if (tab === "bp-bahan-baku" || tab === "bp_bahan_baku") return "bp_bahan_baku";
    if (tab === "bp-operasional" || tab === "bp_operasional") return "bp_operasional";
    if (tab === "bp-fasilitas" || tab === "bp_fasilitas") return "bp_fasilitas";
    if (tab === "lpa") return "lpa";
    if (tab === "sptj") return "sptj";
    if (tab === "bapsd") return "bapsd";
    return tab || "anggaran";
};

const activeTab = ref(normalizeTab(props.activeTab));

watch(
    () => props.activeTab,
    (val) => {
        if (val) activeTab.value = normalizeTab(val);
    }
);

// 1. DATA TRANSAKSI
const transaksiList = ref([
    {
        id: "TRX-001",
        tanggal: "2026-08-26",
        kategori: "Bahan Baku Segar",
        uraian: "Belanja Daging Ayam Segar & Telur Ayam (Vendor Pasar)",
        tipe: "keluar",
        jumlah: 2850000,
        status: "Selesai",
        pj: "Koor. Dapur",
    },
    {
        id: "TRX-002",
        tanggal: "2026-08-25",
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
        tanggal: "2026-08-22",
        kategori: "Sayuran & Buah Segar",
        uraian: "Sayuran Bayam, Wortel, Buncis & Buah Pisang Cavendish",
        tipe: "keluar",
        jumlah: 1450000,
        status: "Selesai",
        pj: "Koor. Dapur",
    },
]);

// 2. DATA PO
const poList = ref([
    {
        id: "PO-20260825-001",
        wo_id: "WO-MBG-20260825",
        tanggal: "2026-08-25",
        menu: "Ayam Goreng Lengkuas & Sayur Bayam",
        vendor: "Koperasi Pangan Sejahtera",
        items_count: 8,
        total_nominal: 17450000,
        status_po: "Terverifikasi",
        status_bayar: "Lunas (Transfer Bank)",
    },
    {
        id: "PO-20260826-002",
        wo_id: "WO-MBG-20260826",
        tanggal: "2026-08-26",
        menu: "Ikan Kembung Bakar & Tumis Buncis",
        vendor: "Kelompok Tani & Nelayan Mandiri",
        items_count: 7,
        total_nominal: 17380000,
        status_po: "Diproses",
        status_bayar: "Menunggu Tagihan",
    },
    {
        id: "PO-20260827-003",
        wo_id: "WO-MBG-20260827",
        tanggal: "2026-08-27",
        menu: "Semur Telur & Tahu Tempe",
        vendor: "Agen Sembako Makmur Jaya",
        items_count: 6,
        total_nominal: 16890000,
        status_po: "Draft",
        status_bayar: "Belum Dibayar",
    },
]);

// 3. DATA BKU (BUKU KAS UMUM)
const bkuList = ref([
    { no_bukti: "BKU-001", tanggal: "2026-08-20", uraian: "Penerimaan Dropping Dana Bantuan Operasional SPPG BGN", debit: 95400000, kredit: 0, saldo: 95400000 },
    { no_bukti: "BKU-002", tanggal: "2026-08-22", uraian: "Penarikan Tunai untuk Kas Kecil Dapur (Petty Cash)", debit: 0, kredit: 5000000, saldo: 90400000 },
    { no_bukti: "BKU-003", tanggal: "2026-08-23", uraian: "Pembayaran Belanja Bahan Baku PO-20260825-001 (Koperasi Pangan)", debit: 0, kredit: 17450000, saldo: 72950000 },
    { no_bukti: "BKU-004", tanggal: "2026-08-24", uraian: "Pembayaran Sewa Dapur & Utilitas Listrik/Air Bulan Berjalan", debit: 0, kredit: 4500000, saldo: 68450000 },
    { no_bukti: "BKU-005", tanggal: "2026-08-25", uraian: "Pembayaran Insentif Mingguan Tenaga Masak Dapur SPPG (6 Org)", debit: 0, kredit: 3600000, saldo: 64850000 },
]);

// 4. DATA BP BANK
const bpBankList = ref([
    { no_ref: "BANK-20260820", tanggal: "2026-08-20", uraian: "Transfer Masuk Rekening Kas Negara (KPPN / BGN)", penerimaan: 95400000, pengeluaran: 0, saldo: 95400000 },
    { no_ref: "BANK-20260822", tanggal: "2026-08-22", uraian: "Tarik Tunai Operasional Kas Kecil", penerimaan: 0, pengeluaran: 5000000, saldo: 90400000 },
    { no_ref: "BANK-20260823", tanggal: "2026-08-23", uraian: "Transfer Kliring ke Vendor Koperasi Pangan Sejahtera", penerimaan: 0, pengeluaran: 17450000, saldo: 72950000 },
    { no_ref: "BANK-20260824", tanggal: "2026-08-24", uraian: "Transfer Pembayaran Sewa Dapur & Fasilitas Unit", penerimaan: 0, pengeluaran: 4500000, saldo: 68450000 },
]);

// 5. DATA BP PETTY CASH
const bpPettyCashList = ref([
    { no_voucher: "PC-001", tanggal: "2026-08-22", uraian: "Penerimaan Saldo Awal Kas Kecil (Imprest Fund)", masuk: 5000000, keluar: 0, sisa: 5000000 },
    { no_voucher: "PC-002", tanggal: "2026-08-23", uraian: "Beli Es Batu Balok Dapur & Kantong Sampah Food Grade", masuk: 0, keluar: 85000, sisa: 4915000 },
    { no_voucher: "PC-003", tanggal: "2026-08-24", uraian: "Bumbu Dapur Tambahan & Daun Pisang Pembungkus", masuk: 0, keluar: 120000, sisa: 4795000 },
    { no_voucher: "PC-004", tanggal: "2026-08-25", uraian: "Gas LPG 12kg Tambahan Darurat Masak Pagi", masuk: 0, keluar: 220000, sisa: 4575000 },
]);

// 6. DATA BP BAHAN BAKU (70%)
const bpBahanBakuList = ref([
    { kode: "BB-01", kelompok: "Bahan Pokok", item: "Beras Premium Lokal, Minyak Goreng, Garam Beryodium", realisasi: 4850000, pagu_pos: 6000000, status: "Normal" },
    { kode: "BB-02", kelompok: "Lauk Hewani", item: "Daging Ayam Broiler Segar, Ikan Kembung, Telur Ayam", realisasi: 14500000, pagu_pos: 16000000, status: "Normal" },
    { kode: "BB-03", kelompok: "Lauk Nabati", item: "Tempe Kedelai Lokal, Tahu Putih Organik", realisasi: 3200000, pagu_pos: 4000000, status: "Normal" },
    { kode: "BB-04", kelompok: "Sayuran Segar", item: "Bayam Hijau, Jagung Manis, Buncis, Wortel, Brokoli", realisasi: 3850000, pagu_pos: 4500000, status: "Normal" },
    { kode: "BB-05", kelompok: "Buah-buahan", item: "Pisang Cavendish, Jeruk Manis, Semangka Merah", realisasi: 4200000, pagu_pos: 5000000, status: "Normal" },
]);

// 7. DATA BP OPERASIONAL (20%)
const bpOperasionalList = ref([
    { kode: "OP-01", rincian: "Insentif Tenaga Masak & Tim Dapur SPPG", realisasi: 7200000, pagu_pos: 8000000 },
    { kode: "OP-02", rincian: "Bahan Bakar & Distribusi Armada MBG ke Sekolah", realisasi: 1850000, pagu_pos: 2500000 },
    { kode: "OP-03", rincian: "Gas LPG 50kg / 12kg & Listrik Dapur", realisasi: 1450000, pagu_pos: 2000000 },
    { kode: "OP-04", rincian: "Air Bersih PDAM & Pengujian Higiene Sanitasi", realisasi: 650000, pagu_pos: 1000000 },
]);

// 8. DATA BP FASILITAS (10%)
const bpFasilitasList = ref([
    { kode: "FAS-01", rincian: "Sewa Tempat Dapur & Area Distribusi SPPG", realisasi: 3500000, pagu_pos: 4000000 },
    { kode: "FAS-02", rincian: "Pengadaan & Perawatan Wadah Ompreng Stainless Steel Food Grade", realisasi: 1200000, pagu_pos: 1500000 },
    { kode: "FAS-03", rincian: "Peralatan Masak Besar & Sterilisator Uap", realisasi: 850000, pagu_pos: 1000000 },
]);

// 9. DATA BAPSD
const bapsdList = ref([
    { no_bap: "BAPSD/2026/08/001", tanggal: "2026-08-25", rekanan: "Koperasi Pangan Sejahtera", komoditas: "Ayam Broiler 350kg & Telur 120kg", nominal: 17450000, status: "Lengkap & Disetujui" },
    { no_bap: "BAPSD/2026/08/002", tanggal: "2026-08-26", rekanan: "Kelompok Tani & Nelayan Mandiri", komoditas: "Ikan Kembung Segar 380kg & Sayur 150kg", nominal: 17380000, status: "Verifikasi Berkas" },
]);

const posAlokasi = [
    { nama: "Bahan Baku Pangan (70%)", persen: 70, warna: "bg-emerald-500", desc: "Beras, lauk pauk segar, sayur, buah, bumbu makanan bergizi" },
    { nama: "Biaya Operasional (20%)", persen: 20, warna: "bg-amber-500", desc: "Insentif juru masak, bahan bakar, gas LPG, distribusi ke sekolah" },
    { nama: "Fasilitas & Sarpras (10%)", persen: 10, warna: "bg-indigo-500", desc: "Sewa dapur, wadah ompreng food grade, alat masak, sanitasi" },
];

function formatRupiah(num) {
    if (!num) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(num);
}

function formatTanggalIndo(tgl) {
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
}
</script>

<template>
    <AppLayout
        title="Tata Kelola Keuangan SPPG"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Keuangan" />

        <div class="space-y-6">
            <!-- ========================================================================================= -->
            <!-- 1. SUB MENU 1: ANGGARAN & BIAYA PER PORSI -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'anggaran'" class="space-y-6">
                <!-- 1. Header Metrics Card (Hanya Tampil di Menu Anggaran) -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
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
                <Card className="bg-white border-slate-200 shadow-xs">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
                        <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <Coins class="h-5 w-5 text-primary" />
                            <span>Struktur Anggaran & Biaya per Porsi MBG</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm">
                            Standar pagu alokasi makanan bergizi gratis BGN berdasarkan target penerima manfaat unit SPPG.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-extrabold text-amber-900 text-sm">Porsi Kecil (PK) - PAUD/TK/SD Kelas 1-3</h4>
                                    <Badge className="bg-amber-100 text-amber-800 font-bold border-amber-200">Pagu Rp 8.000</Badge>
                                </div>
                                <p class="text-xs text-amber-800/80">Kebutuhan energi: 450 - 550 kkal • Protein: 15 - 18g</p>
                                <div class="text-xs font-bold text-slate-700">
                                    Total Sasaran: <span class="text-amber-900 text-sm">{{ summary.total_porsi_kecil }} Siswa</span>
                                </div>
                            </div>
                            <div class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/50 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-extrabold text-indigo-900 text-sm">Porsi Besar (PB) - SD Kelas 4-6 / SMP / SMA</h4>
                                    <Badge className="bg-indigo-100 text-indigo-800 font-bold border-indigo-200">Pagu Rp 10.000</Badge>
                                </div>
                                <p class="text-xs text-indigo-800/80">Kebutuhan energi: 650 - 750 kkal • Protein: 22 - 27g</p>
                                <div class="text-xs font-bold text-slate-700">
                                    Total Sasaran: <span class="text-indigo-900 text-sm">{{ summary.total_porsi_besar }} Siswa</span>
                                </div>
                            </div>
                        </div>

                        <!-- Distribusi Alokasi -->
                        <div class="space-y-3 pt-2">
                            <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Distribusi Alokasi Standar BGN (70% - 20% - 10%)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div v-for="pos in posAlokasi" :key="pos.nama" class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/60 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-800">{{ pos.nama }}</span>
                                        <span class="text-xs font-black text-primary">{{ pos.persen }}%</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 leading-snug">{{ pos.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tabel Anggaran per Kelompok -->
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
                        <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <School class="h-5 w-5 text-primary" />
                            <span>Kebutuhan Anggaran per Kelompok Sasaran</span>
                        </CardTitle>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">Nama Kelompok</th>
                                    <th class="p-3.5">Kategori</th>
                                    <th class="p-3.5">Porsi Kecil (PK)</th>
                                    <th class="p-3.5">Porsi Besar (PB)</th>
                                    <th class="p-3.5">Biaya Harian</th>
                                    <th class="p-3.5">Biaya Bulanan (20 Hari)</th>
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
                                        {{ formatRupiah(((k.total_porsi_kecil || 0) * summary.cost_porsi_kecil + (k.total_porsi_besar || 0) * summary.cost_porsi_besar) * 20) }}
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

            <!-- ========================================================================================= -->
            <!-- 2. SUB MENU 2: DAFTAR PO (PURCHASE ORDER) -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'daftar_po'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <Receipt class="h-5 w-5 text-primary" />
                                <span>Daftar Purchase Order (PO) & Realisasi Belanja Bahan</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Pesanan pembelian bahan baku makanan bergizi dari formulasi menu Work Order Ahli Gizi.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">No. PO & WO</th>
                                    <th class="p-3.5">Tanggal</th>
                                    <th class="p-3.5">Menu Sasaran</th>
                                    <th class="p-3.5">Vendor / Rekanan</th>
                                    <th class="p-3.5 text-center">Items</th>
                                    <th class="p-3.5 text-right">Total Nominal</th>
                                    <th class="p-3.5 text-center">Status PO</th>
                                    <th class="p-3.5 text-center">Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="po in poList" :key="po.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">
                                        <div>{{ po.id }}</div>
                                        <div class="text-[10px] text-slate-400 font-normal">{{ po.wo_id }}</div>
                                    </td>
                                    <td class="p-3.5 text-slate-600 whitespace-nowrap">{{ formatTanggalIndo(po.tanggal) }}</td>
                                    <td class="p-3.5 font-semibold text-slate-900">{{ po.menu }}</td>
                                    <td class="p-3.5 text-slate-700 font-medium">{{ po.vendor }}</td>
                                    <td class="p-3.5 text-center font-bold">{{ po.items_count }} Item</td>
                                    <td class="p-3.5 text-right font-extrabold text-emerald-800 text-xs">
                                        {{ formatRupiah(po.total_nominal) }}
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <Badge
                                            variant="outline"
                                            :className="po.status_po === 'Terverifikasi' ? 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold text-[10.5px]' : 'bg-amber-50 text-amber-700 border-amber-300 font-bold text-[10.5px]'"
                                        >
                                            {{ po.status_po }}
                                        </Badge>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <Badge variant="outline" className="bg-slate-100 text-slate-700 border-slate-300 text-[10.5px]">
                                            {{ po.status_bayar }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 3. SUB MENU 3: TRANSAKSI -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'transaksi'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <CreditCard class="h-5 w-5 text-primary" />
                                <span>Pencatatan Riwayat Transaksi Keuangan SPPG</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Seluruh arus kas masuk penerimaan dana dan pengeluaran operasional.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[750px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">ID Transaksi</th>
                                    <th class="p-3.5">Tanggal</th>
                                    <th class="p-3.5">Kategori</th>
                                    <th class="p-3.5">Uraian Transaksi</th>
                                    <th class="p-3.5">PJ</th>
                                    <th class="p-3.5 text-right">Nominal</th>
                                    <th class="p-3.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="trx in transaksiList" :key="trx.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">{{ trx.id }}</td>
                                    <td class="p-3.5 text-slate-600 whitespace-nowrap">{{ formatTanggalIndo(trx.tanggal) }}</td>
                                    <td class="p-3.5">
                                        <Badge variant="outline" className="font-semibold text-[10.5px]">
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

            <!-- ========================================================================================= -->
            <!-- 4. SUB MENU 4: BKU (BUKU KAS UMUM) -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'bku'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <BookOpen class="h-5 w-5 text-primary" />
                                <span>Buku Kas Umum (BKU) SPPG</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Pembukuan kas induk mencatat seluruh mutasi debit, kredit, dan saldo kumulatif.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">No. Bukti</th>
                                    <th class="p-3.5">Tanggal</th>
                                    <th class="p-3.5">Uraian Transaksi</th>
                                    <th class="p-3.5 text-right">Penerimaan (Debit)</th>
                                    <th class="p-3.5 text-right">Pengeluaran (Kredit)</th>
                                    <th class="p-3.5 text-right">Saldo Kas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="b in bkuList" :key="b.no_bukti" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">{{ b.no_bukti }}</td>
                                    <td class="p-3.5 text-slate-600 whitespace-nowrap">{{ formatTanggalIndo(b.tanggal) }}</td>
                                    <td class="p-3.5 font-semibold text-slate-900">{{ b.uraian }}</td>
                                    <td class="p-3.5 text-right font-bold text-emerald-700">{{ b.debit ? formatRupiah(b.debit) : '-' }}</td>
                                    <td class="p-3.5 text-right font-bold text-rose-700">{{ b.kredit ? formatRupiah(b.kredit) : '-' }}</td>
                                    <td class="p-3.5 text-right font-extrabold text-blue-900">{{ formatRupiah(b.saldo) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 5. SUB MENU 5: BP BANK -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'bp_bank'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <Landmark class="h-5 w-5 text-primary" />
                                <span>Buku Pembantu (BP) Bank Unit SPPG</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Rekening koran & pencatatan transaksi giro resmi SPPG BGN.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[750px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">No. Referensi</th>
                                    <th class="p-3.5">Tanggal</th>
                                    <th class="p-3.5">Keterangan Mutasi</th>
                                    <th class="p-3.5 text-right">Penerimaan</th>
                                    <th class="p-3.5 text-right">Pengeluaran</th>
                                    <th class="p-3.5 text-right">Saldo Bank</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="bk in bpBankList" :key="bk.no_ref" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">{{ bk.no_ref }}</td>
                                    <td class="p-3.5 text-slate-600 whitespace-nowrap">{{ formatTanggalIndo(bk.tanggal) }}</td>
                                    <td class="p-3.5 font-semibold text-slate-900">{{ bk.uraian }}</td>
                                    <td class="p-3.5 text-right font-bold text-emerald-700">{{ bk.penerimaan ? formatRupiah(bk.penerimaan) : '-' }}</td>
                                    <td class="p-3.5 text-right font-bold text-rose-700">{{ bk.pengeluaran ? formatRupiah(bk.pengeluaran) : '-' }}</td>
                                    <td class="p-3.5 text-right font-extrabold text-blue-900">{{ formatRupiah(bk.saldo) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 6. SUB MENU 6: BP PETTY CASH -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'bp_petty_cash'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <Banknote class="h-5 w-5 text-primary" />
                                <span>Buku Pembantu (BP) Petty Cash / Kas Kecil Dapur</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Pengeluaran operasional tunai tak terduga dapur SPPG harian.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">No. Voucher</th>
                                    <th class="p-3.5">Tanggal</th>
                                    <th class="p-3.5">Uraian Belanja Tunai</th>
                                    <th class="p-3.5 text-right">Kas Masuk</th>
                                    <th class="p-3.5 text-right">Kas Keluar</th>
                                    <th class="p-3.5 text-right">Sisa Kas Kecil</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="pc in bpPettyCashList" :key="pc.no_voucher" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">{{ pc.no_voucher }}</td>
                                    <td class="p-3.5 text-slate-600 whitespace-nowrap">{{ formatTanggalIndo(pc.tanggal) }}</td>
                                    <td class="p-3.5 font-semibold text-slate-900">{{ pc.uraian }}</td>
                                    <td class="p-3.5 text-right font-bold text-emerald-700">{{ pc.masuk ? formatRupiah(pc.masuk) : '-' }}</td>
                                    <td class="p-3.5 text-right font-bold text-rose-700">{{ pc.keluar ? formatRupiah(pc.keluar) : '-' }}</td>
                                    <td class="p-3.5 text-right font-extrabold text-amber-900">{{ formatRupiah(pc.sisa) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 7. SUB MENU 7: BP BAHAN BAKU -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'bp_bahan_baku'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
                        <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <Package class="h-5 w-5 text-primary" />
                            <span>Buku Pembantu (BP) Belanja Bahan Baku (70% Alokasi)</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm">
                            Rincian belanja bahan makanan pokok, lauk hewani/nabati, sayuran, dan buah-buahan.
                        </CardDescription>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">Kode Pos</th>
                                    <th class="p-3.5">Kelompok Bahan</th>
                                    <th class="p-3.5">Item Komoditas Pangan</th>
                                    <th class="p-3.5 text-right">Pagu Pos</th>
                                    <th class="p-3.5 text-right">Realisasi Belanja</th>
                                    <th class="p-3.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="bb in bpBahanBakuList" :key="bb.kode" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">{{ bb.kode }}</td>
                                    <td class="p-3.5 font-bold text-slate-900">{{ bb.kelompok }}</td>
                                    <td class="p-3.5 text-slate-600">{{ bb.item }}</td>
                                    <td class="p-3.5 text-right font-medium text-slate-700">{{ formatRupiah(bb.pagu_pos) }}</td>
                                    <td class="p-3.5 text-right font-extrabold text-emerald-800">{{ formatRupiah(bb.realisasi) }}</td>
                                    <td class="p-3.5 text-center">
                                        <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200 font-bold text-[10.5px]">
                                            {{ bb.status }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 8. SUB MENU 8: BP OPERASIONAL -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'bp_operasional'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
                        <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <Sliders class="h-5 w-5 text-primary" />
                            <span>Buku Pembantu (BP) Biaya Operasional (20% Alokasi)</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm">
                            Insentif juru masak, BBM armada distribusi, utilitas gas LPG, listrik, dan air bersih.
                        </CardDescription>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[650px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">Kode</th>
                                    <th class="p-3.5">Rincian Pos Operasional</th>
                                    <th class="p-3.5 text-right">Pagu Anggaran</th>
                                    <th class="p-3.5 text-right">Realisasi Pengeluaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="op in bpOperasionalList" :key="op.kode" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">{{ op.kode }}</td>
                                    <td class="p-3.5 font-bold text-slate-900">{{ op.rincian }}</td>
                                    <td class="p-3.5 text-right text-slate-700">{{ formatRupiah(op.pagu_pos) }}</td>
                                    <td class="p-3.5 text-right font-extrabold text-amber-800">{{ formatRupiah(op.realisasi) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 9. SUB MENU 9: BP FASILITAS -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'bp_fasilitas'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
                        <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <Building class="h-5 w-5 text-primary" />
                            <span>Buku Pembantu (BP) Fasilitas & Sarpras (10% Alokasi)</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm">
                            Sewa dapur, wadah ompreng food grade stainless steel, dan sterilisator peralatan makan.
                        </CardDescription>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[650px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">Kode</th>
                                    <th class="p-3.5">Rincian Pos Fasilitas</th>
                                    <th class="p-3.5 text-right">Pagu Anggaran</th>
                                    <th class="p-3.5 text-right">Realisasi Pengeluaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="fas in bpFasilitasList" :key="fas.kode" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">{{ fas.kode }}</td>
                                    <td class="p-3.5 font-bold text-slate-900">{{ fas.rincian }}</td>
                                    <td class="p-3.5 text-right text-slate-700">{{ formatRupiah(fas.pagu_pos) }}</td>
                                    <td class="p-3.5 text-right font-extrabold text-indigo-800">{{ formatRupiah(fas.realisasi) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 10. SUB MENU 10: LPA (LAPORAN PERTANGGUNGJAWABAN ANGGARAN) -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'lpa'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <FileCheck2 class="h-5 w-5 text-primary" />
                                <span>Laporan Pertanggungjawaban Anggaran (LPA) SPPG</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Rekapitulasi serapan anggaran realisasi belanja per periode operasional MBG.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60">
                                <p class="text-[11px] font-semibold text-slate-500 uppercase">Total Pagu BGN</p>
                                <h3 class="text-xl font-extrabold text-slate-900 mt-1">{{ formatRupiah(summary.estimasi_bulanan_total) }}</h3>
                            </div>
                            <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50">
                                <p class="text-[11px] font-semibold text-emerald-700 uppercase">Total Realisasi Belanja</p>
                                <h3 class="text-xl font-extrabold text-emerald-900 mt-1">{{ formatRupiah(summary.estimasi_bulanan_total * 0.85) }}</h3>
                            </div>
                            <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/50">
                                <p class="text-[11px] font-semibold text-blue-700 uppercase">Persentase Serapan</p>
                                <h3 class="text-xl font-extrabold text-blue-900 mt-1">85.4% <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">Optimal</span></h3>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 11. SUB MENU 11: SPTJ (SURAT PERNYATAAN TANGGUNG JAWAB) -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'sptj'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <ShieldCheck class="h-5 w-5 text-primary" />
                                <span>Surat Pernyataan Tanggung Jawab (SPTJ) Belanja</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Dokumen formal pertanggungjawaban mutlak atas penggunaan dana operasional SPPG.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent className="p-6 sm:p-8 space-y-6 max-w-4xl mx-auto border border-slate-200/80 rounded-2xl bg-white my-4 shadow-xs">
                        <div class="text-center space-y-1 border-b border-slate-200 pb-4">
                            <h3 class="text-base font-black text-slate-900 uppercase">SURAT PERNYATAAN TANGGUNG JAWAB BELANJA (SPTJ)</h3>
                            <p class="text-xs text-slate-500 font-mono">Nomor: SPTJ/SPPG-BGN/{{ new Date().getFullYear() }}/VIII/001</p>
                        </div>
                        <div class="text-xs sm:text-sm text-slate-700 leading-relaxed space-y-3">
                            <p>Yang bertanda tangan di bawah ini:</p>
                            <div class="pl-4 space-y-1 font-semibold text-slate-900">
                                <p>Nama: <span class="font-normal text-slate-700">{{ user.name || 'Koordinator SPPG' }}</span></p>
                                <p>Unit SPPG: <span class="font-normal text-slate-700">{{ unitSppg?.nama_unit || 'Unit Layanan SPPG' }}</span></p>
                                <p>Alamat: <span class="font-normal text-slate-700">{{ unitSppg?.alamat || 'Lokasi Dapur SPPG' }}</span></p>
                            </div>
                            <p class="pt-2">
                                Menyatakan dengan sesungguhnya bahwa seluruh belanja bahan baku, biaya operasional dapur, dan fasilitas penyediaan Makanan Bergizi Gratis (MBG) telah dilaksanakan sesuai dengan petunjuk teknis Badan Gizi Nasional (BGN) dan didukung dengan bukti pertanggungjawaban yang lengkap dan sah.
                            </p>
                        </div>
                        <div class="flex justify-end pt-6">
                            <div class="text-center space-y-8 text-xs font-bold text-slate-800">
                                <p>{{ unitSppg?.kabupaten || 'Wilayah SPPG' }}, {{ formatTanggalIndo(new Date().toISOString()) }}</p>
                                <div class="w-32 h-14 border border-dashed border-slate-300 rounded flex items-center justify-center text-[10px] text-slate-400 mx-auto">
                                    Materai Rp 10.000
                                </div>
                                <p class="underline">{{ user.name || 'Koordinator SPPG' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 12. SUB MENU 12: BAPSD (BERITA ACARA PEMBAYARAN & SERAH TERIMA) -->
            <!-- ========================================================================================= -->
            <div v-if="activeTab === 'bapsd'" class="space-y-6">
                <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                                <FileSignature class="h-5 w-5 text-primary" />
                                <span>Berita Acara Pembayaran & Serah Terima Dokumen (BAPSD)</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Berita acara serah terima penerimaan barang pangan dan tagihan rekanan vendor.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[750px] text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <tr>
                                    <th class="p-3.5">No. BAPSD</th>
                                    <th class="p-3.5">Tanggal</th>
                                    <th class="p-3.5">Rekanan / Vendor</th>
                                    <th class="p-3.5">Komoditas Barang Diserahterimakan</th>
                                    <th class="p-3.5 text-right">Nilai Pembayaran</th>
                                    <th class="p-3.5 text-center">Status Berita Acara</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr v-for="bap in bapsdList" :key="bap.no_bap" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3.5 font-mono font-bold text-primary">{{ bap.no_bap }}</td>
                                    <td class="p-3.5 text-slate-600 whitespace-nowrap">{{ formatTanggalIndo(bap.tanggal) }}</td>
                                    <td class="p-3.5 font-bold text-slate-900">{{ bap.rekanan }}</td>
                                    <td class="p-3.5 text-slate-700">{{ bap.komoditas }}</td>
                                    <td class="p-3.5 text-right font-extrabold text-emerald-800">{{ formatRupiah(bap.nominal) }}</td>
                                    <td class="p-3.5 text-center">
                                        <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-300 font-bold text-[10.5px]">
                                            {{ bap.status }}
                                        </Badge>
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
