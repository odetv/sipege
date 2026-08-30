<script setup>
import { ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

// Partials
import KeuanganAnggaranTab from "@/Pages/Keuangan/Partials/KeuanganAnggaranTab.vue";
import KeuanganVerifikasiPoTab from "@/Pages/Keuangan/Partials/KeuanganVerifikasiPoTab.vue";
import KeuanganDaftarPoTab from "@/Pages/Keuangan/Partials/KeuanganDaftarPoTab.vue";
import KeuanganTransaksiTab from "@/Pages/Keuangan/Partials/KeuanganTransaksiTab.vue";
import KeuanganBkuTab from "@/Pages/Keuangan/Partials/KeuanganBkuTab.vue";
import KeuanganBpBankTab from "@/Pages/Keuangan/Partials/KeuanganBpBankTab.vue";
import KeuanganBpPettyCashTab from "@/Pages/Keuangan/Partials/KeuanganBpPettyCashTab.vue";
import KeuanganBpBahanBakuTab from "@/Pages/Keuangan/Partials/KeuanganBpBahanBakuTab.vue";
import KeuanganBpOperasionalTab from "@/Pages/Keuangan/Partials/KeuanganBpOperasionalTab.vue";
import KeuanganBpFasilitasTab from "@/Pages/Keuangan/Partials/KeuanganBpFasilitasTab.vue";
import KeuanganLpaTab from "@/Pages/Keuangan/Partials/KeuanganLpaTab.vue";
import KeuanganSptjTab from "@/Pages/Keuangan/Partials/KeuanganSptjTab.vue";
import KeuanganBapsdTab from "@/Pages/Keuangan/Partials/KeuanganBapsdTab.vue";
import KeuanganStokTab from "@/Pages/Keuangan/Partials/KeuanganStokTab.vue";
import KeuanganLaporanHarianTab from "@/Pages/Keuangan/Partials/KeuanganLaporanHarianTab.vue";
import KeuanganLaporanPeriodikTab from "@/Pages/Keuangan/Partials/KeuanganLaporanPeriodikTab.vue";

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
    verifikasiPoList: {
        type: Array,
        default: () => [],
    },
    poList: {
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
    if (tab === "verifikasi-po" || tab === "verifikasi_po") return "verifikasi_po";
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
    },
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

// 2. DATA PO (Diambil langsung dari props.poList dari database)

// 3. DATA BKU (BUKU KAS UMUM)
const bkuList = ref([
    {
        no_bukti: "BKU-001",
        tanggal: "2026-08-20",
        uraian: "Penerimaan Dropping Dana Bantuan Operasional SPPG BGN",
        debit: 95400000,
        kredit: 0,
        saldo: 95400000,
    },
    {
        no_bukti: "BKU-002",
        tanggal: "2026-08-22",
        uraian: "Penarikan Tunai untuk Kas Kecil Dapur (Petty Cash)",
        debit: 0,
        kredit: 5000000,
        saldo: 90400000,
    },
    {
        no_bukti: "BKU-003",
        tanggal: "2026-08-23",
        uraian: "Pembayaran Belanja Bahan Baku PO-20260825-001 (Koperasi Pangan)",
        debit: 0,
        kredit: 17450000,
        saldo: 72950000,
    },
    {
        no_bukti: "BKU-004",
        tanggal: "2026-08-24",
        uraian: "Pembayaran Sewa Dapur & Utilitas Listrik/Air Bulan Berjalan",
        debit: 0,
        kredit: 4500000,
        saldo: 68450000,
    },
    {
        no_bukti: "BKU-005",
        tanggal: "2026-08-25",
        uraian: "Pembayaran Insentif Mingguan Tenaga Masak Dapur SPPG (6 Org)",
        debit: 0,
        kredit: 3600000,
        saldo: 64850000,
    },
]);

// 4. DATA BP BANK
const bpBankList = ref([
    {
        no_ref: "BANK-20260820",
        tanggal: "2026-08-20",
        uraian: "Transfer Masuk Rekening Kas Negara (KPPN / BGN)",
        penerimaan: 95400000,
        pengeluaran: 0,
        saldo: 95400000,
    },
    {
        no_ref: "BANK-20260822",
        tanggal: "2026-08-22",
        uraian: "Tarik Tunai Operasional Kas Kecil",
        penerimaan: 0,
        pengeluaran: 5000000,
        saldo: 90400000,
    },
    {
        no_ref: "BANK-20260823",
        tanggal: "2026-08-23",
        uraian: "Transfer Kliring ke Vendor Koperasi Pangan Sejahtera",
        penerimaan: 0,
        pengeluaran: 17450000,
        saldo: 72950000,
    },
    {
        no_ref: "BANK-20260824",
        tanggal: "2026-08-24",
        uraian: "Transfer Pembayaran Sewa Dapur & Fasilitas Unit",
        penerimaan: 0,
        pengeluaran: 4500000,
        saldo: 68450000,
    },
]);

// 5. DATA BP PETTY CASH
const bpPettyCashList = ref([
    {
        no_voucher: "PC-001",
        tanggal: "2026-08-22",
        uraian: "Penerimaan Saldo Awal Kas Kecil (Imprest Fund)",
        masuk: 5000000,
        keluar: 0,
        sisa: 5000000,
    },
    {
        no_voucher: "PC-002",
        tanggal: "2026-08-23",
        uraian: "Beli Es Batu Balok Dapur & Kantong Sampah Food Grade",
        masuk: 0,
        keluar: 85000,
        sisa: 4915000,
    },
    {
        no_voucher: "PC-003",
        tanggal: "2026-08-24",
        uraian: "Bumbu Dapur Tambahan & Daun Pisang Pembungkus",
        masuk: 0,
        keluar: 120000,
        sisa: 4795000,
    },
    {
        no_voucher: "PC-004",
        tanggal: "2026-08-25",
        uraian: "Gas LPG 12kg Tambahan Darurat Masak Pagi",
        masuk: 0,
        keluar: 220000,
        sisa: 4575000,
    },
]);

// 6. DATA BP BAHAN BAKU (70%)
const bpBahanBakuList = ref([
    {
        kode: "BB-01",
        kelompok: "Bahan Pokok",
        item: "Beras Premium Lokal, Minyak Goreng, Garam Beryodium",
        realisasi: 4850000,
        pagu_pos: 6000000,
        status: "Normal",
    },
    {
        kode: "BB-02",
        kelompok: "Lauk Hewani",
        item: "Daging Ayam Broiler Segar, Ikan Kembung, Telur Ayam",
        realisasi: 14500000,
        pagu_pos: 16000000,
        status: "Normal",
    },
    {
        kode: "BB-03",
        kelompok: "Lauk Nabati",
        item: "Tempe Kedelai Lokal, Tahu Putih Organik",
        realisasi: 3200000,
        pagu_pos: 4000000,
        status: "Normal",
    },
    {
        kode: "BB-04",
        kelompok: "Sayuran Segar",
        item: "Bayam Hijau, Jagung Manis, Buncis, Wortel, Brokoli",
        realisasi: 3850000,
        pagu_pos: 4500000,
        status: "Normal",
    },
    {
        kode: "BB-05",
        kelompok: "Buah-buahan",
        item: "Pisang Cavendish, Jeruk Manis, Semangka Merah",
        realisasi: 4200000,
        pagu_pos: 5000000,
        status: "Normal",
    },
]);

// 7. DATA BP OPERASIONAL (20%)
const bpOperasionalList = ref([
    {
        kode: "OP-01",
        rincian: "Insentif Tenaga Masak & Tim Dapur SPPG",
        realisasi: 7200000,
        pagu_pos: 8000000,
    },
    {
        kode: "OP-02",
        rincian: "Bahan Bakar & Distribusi Armada MBG ke Sekolah",
        realisasi: 1850000,
        pagu_pos: 2500000,
    },
    {
        kode: "OP-03",
        rincian: "Gas LPG 50kg / 12kg & Listrik Dapur",
        realisasi: 1450000,
        pagu_pos: 2000000,
    },
    {
        kode: "OP-04",
        rincian: "Air Bersih PDAM & Pengujian Higiene Sanitasi",
        realisasi: 650000,
        pagu_pos: 1000000,
    },
]);

// 8. DATA BP FASILITAS (10%)
const bpFasilitasList = ref([
    {
        kode: "FAS-01",
        rincian: "Sewa Tempat Dapur & Area Distribusi SPPG",
        realisasi: 3500000,
        pagu_pos: 4000000,
    },
    {
        kode: "FAS-02",
        rincian:
            "Pengadaan & Perawatan Wadah Ompreng Stainless Steel Food Grade",
        realisasi: 1200000,
        pagu_pos: 1500000,
    },
    {
        kode: "FAS-03",
        rincian: "Peralatan Masak Besar & Sterilisator Uap",
        realisasi: 850000,
        pagu_pos: 1000000,
    },
]);

// 9. DATA BAPSD
const bapsdList = ref([
    {
        no_bap: "BAPSD/2026/08/001",
        tanggal: "2026-08-25",
        rekanan: "Koperasi Pangan Sejahtera",
        komoditas: "Ayam Broiler 350kg & Telur 120kg",
        nominal: 17450000,
        status: "Lengkap & Disetujui",
    },
    {
        no_bap: "BAPSD/2026/08/002",
        tanggal: "2026-08-26",
        rekanan: "Kelompok Tani & Nelayan Mandiri",
        komoditas: "Ikan Kembung Segar 380kg & Sayur 150kg",
        nominal: 17380000,
        status: "Verifikasi Berkas",
    },
]);

const posAlokasi = [
    {
        nama: "Bahan Baku Pangan (70%)",
        persen: 70,
        warna: "bg-emerald-500",
        desc: "Beras, lauk pauk segar, sayur, buah, bumbu makanan bergizi",
    },
    {
        nama: "Biaya Operasional (20%)",
        persen: 20,
        warna: "bg-amber-500",
        desc: "Insentif juru masak, bahan bakar, gas LPG, distribusi ke sekolah",
    },
    {
        nama: "Fasilitas & Sarpras (10%)",
        persen: 10,
        warna: "bg-indigo-500",
        desc: "Sewa dapur, wadah ompreng food grade, alat masak, sanitasi",
    },
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
            <!-- 1. ANGGARAN & BIAYA PER PORSI -->
            <KeuanganAnggaranTab
                v-if="activeTab === 'anggaran'"
                :summary="summary"
                :kelompok-list="kelompokList"
                :pos-alokasi="posAlokasi"
                :format-rupiah="formatRupiah"
            />

            <!-- 2. VERIFIKASI PO (TELAAH & PERSETUJUAN DARI TIM GIZI) -->
            <KeuanganVerifikasiPoTab
                v-if="activeTab === 'verifikasi_po'"
                :verifikasi-po-list="props.verifikasiPoList || []"
                :format-rupiah="formatRupiah"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 3. DAFTAR PO (PURCHASE ORDER RESMI) -->
            <KeuanganDaftarPoTab
                v-if="activeTab === 'daftar_po'"
                :po-list="poList"
                :format-rupiah="formatRupiah"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 3. TRANSAKSI -->
            <KeuanganTransaksiTab
                v-if="activeTab === 'transaksi'"
                :transaksi-list="transaksiList"
                :format-rupiah="formatRupiah"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 4. BKU (BUKU KAS UMUM) -->
            <KeuanganBkuTab
                v-if="activeTab === 'bku'"
                :bku-list="bkuList"
                :format-rupiah="formatRupiah"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 5. BP BANK -->
            <KeuanganBpBankTab
                v-if="activeTab === 'bp_bank'"
                :bp-bank-list="bpBankList"
                :format-rupiah="formatRupiah"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 6. BP PETTY CASH -->
            <KeuanganBpPettyCashTab
                v-if="activeTab === 'bp_petty_cash'"
                :bp-petty-cash-list="bpPettyCashList"
                :format-rupiah="formatRupiah"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 7. BP BAHAN BAKU -->
            <KeuanganBpBahanBakuTab
                v-if="activeTab === 'bp_bahan_baku'"
                :bp-bahan-baku-list="bpBahanBakuList"
                :format-rupiah="formatRupiah"
            />

            <!-- 8. BP OPERASIONAL -->
            <KeuanganBpOperasionalTab
                v-if="activeTab === 'bp_operasional'"
                :bp-operasional-list="bpOperasionalList"
                :format-rupiah="formatRupiah"
            />

            <!-- 9. BP FASILITAS -->
            <KeuanganBpFasilitasTab
                v-if="activeTab === 'bp_fasilitas'"
                :bp-fasilitasList="bpFasilitasList"
                :format-rupiah="formatRupiah"
            />

            <!-- 10. LPA -->
            <KeuanganLpaTab
                v-if="activeTab === 'lpa'"
                :summary="summary"
                :format-rupiah="formatRupiah"
            />

            <!-- 11. SPTJ -->
            <KeuanganSptjTab
                v-if="activeTab === 'sptj'"
                :user="user"
                :unit-sppg="unitSppg"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 12. BAPSD -->
            <KeuanganBapsdTab
                v-if="activeTab === 'bapsd'"
                :bapsd-list="bapsdList"
                :format-rupiah="formatRupiah"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 13. STOK PERSADAAN -->
            <KeuanganStokTab
                v-if="activeTab === 'stok'"
                :po-list="poList"
                :format-rupiah="formatRupiah"
            />

            <!-- 14. LAPORAN HARIAN -->
            <KeuanganLaporanHarianTab
                v-if="activeTab === 'laporan-harian' || activeTab === 'laporan_harian'"
                :po-list="poList"
                :format-rupiah="formatRupiah"
                :format-tanggal-indo="formatTanggalIndo"
            />

            <!-- 15. LAPORAN PERIODIK -->
            <KeuanganLaporanPeriodikTab
                v-if="activeTab === 'laporan-periodik' || activeTab === 'laporan_periodik'"
                :summary="summary"
                :format-rupiah="formatRupiah"
            />
        </div>
    </AppLayout>
</template>
