<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import {
    UtensilsCrossed,
    Users,
    ClipboardList,
    FileSpreadsheet,
    Activity,
    Coins,
    CheckCircle2,
    XCircle,
    AlertCircle,
    AlertTriangle,
    Lightbulb,
    Printer,
    Send,
    Plus,
    Trash2,
    Edit3,
    Sparkles,
    ShieldAlert,
    Clock,
    DollarSign,
    Check,
    RotateCcw,
    Layers,
    School,
    Database,
    Table,
    Search,
    Filter,
    HeartPulse,
    Calendar,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    Utensils,
    CalendarCheck,
    ShieldCheck,
    FileText,
    ArrowRight,
    UserCheck,
    UserX,
    X,
    Info,
} from "lucide-vue-next";
import Modal from "@/Components/Modal.vue";
import {
    ALERGI_OPTIONS,
    getSubKategoriByKategori,
    getJenisPorsiBySubKategori,
    sortRincianByKategori,
} from "@/Services/penerimaManfaatConfig";

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
    tkpiList: {
        type: Array,
        default: () => [],
    },
    activeTab: {
        type: String,
        default: "tkpi",
    },
    stats: {
        type: Object,
        default: () => ({
            total_kelompok: 0,
            total_penerima: 0,
            total_porsi_kecil: 0,
            total_porsi_besar: 0,
            kategori_breakdown: {},
        }),
    },
});

// 4 Sub-Menu Utama Gizi SPPG
// 'tkpi' | 'analisa-pm' | 'buat-menu' | 'kalender-menu'
const activeSubMenu = ref(props.activeTab || "tkpi");

watch(
    () => props.activeTab,
    (val) => {
        if (val) activeSubMenu.value = val;
    },
);

const subMenus = [
    {
        id: "tkpi",
        no: "1",
        title: "TKPI",
        subtitle: "Database & Pangan MBG",
        icon: Database,
        routeName: "gizi.tkpi",
    },
    {
        id: "analisa-pm",
        no: "2",
        title: "Analisa PM",
        subtitle: "Detail Sasaran & Porsi",
        icon: Users,
        routeName: "gizi.analisa-pm",
    },
    {
        id: "buat-menu",
        no: "3",
        title: "Buat Menu",
        subtitle: "Resep, AKG & Food Cost",
        icon: UtensilsCrossed,
        routeName: "gizi.buat-menu",
    },
    {
        id: "kalender-menu",
        no: "4",
        title: "Kalender Menu",
        subtitle: "Siklus Menu Harian",
        icon: CalendarDays,
        routeName: "gizi.kalender-menu",
    },
];

function selectSubMenu(menu) {
    activeSubMenu.value = menu.id;
    if (menu.routeName) {
        router.visit(route(menu.routeName), {
            preserveState: true,
            replace: true,
        });
    }
}

// Sub-Tab di dalam modul Buat Menu (Diawali dengan Step 1: Work Order)
const buatMenuSubTab = ref("work_order");
const buatMenuSubTabs = [
    {
        id: "work_order",
        label: "1. Work Order Produksi",
        icon: FileSpreadsheet,
    },
    {
        id: "pre_order",
        label: "2. Formulasi Resep & Pre-Order",
        icon: ClipboardList,
    },
    {
        id: "hasil_akg",
        label: "3. Evaluasi Standar AKG BGN",
        icon: Activity,
    },
    {
        id: "hasil_food_cost",
        label: "4. Analisis Food Cost & Plafon",
        icon: Coins,
    },
    {
        id: "order",
        label: "5. Approval Order Akuntan",
        icon: ShieldCheck,
    },
];

// State & Data Kalender Siklus Menu MBG
const kalenderBulan = ref("Agustus 2026");
const siklusAktif = ref("10 Hari");
const selectedKalenderItem = ref(null);

const jadwalMenuBulan = ref([
    {
        tanggal: "2026-08-03",
        tglNo: 3,
        hari: "Senin",
        siklusKe: 1,
        namaMenu:
            "Nasi Kuning Ayam Suwir, Telur Balado, Tempe Orek, Tumis Buncis & Pisang",
        status: "Selesai",
        kaloriPK: 485,
        kaloriPB: 640,
        costPK: 9850,
        costPB: 13950,
        komponen: [
            "Beras Putih",
            "Ayam Suwir",
            "Telur Balado",
            "Tempe Orek",
            "Buncis",
            "Pisang Raja",
        ],
    },
    {
        tanggal: "2026-08-04",
        tglNo: 4,
        hari: "Selasa",
        siklusKe: 2,
        namaMenu:
            "Nasi Uduk, Semur Telur & Tahu, Bihun Goreng Sayur, Ketimun & Jeruk",
        status: "Selesai",
        kaloriPK: 460,
        kaloriPB: 610,
        costPK: 9200,
        costPB: 13100,
        komponen: [
            "Beras Uduk",
            "Semur Telur",
            "Tahu Semur",
            "Bihun Jagung",
            "Jeruk Manis",
        ],
    },
    {
        tanggal: "2026-08-05",
        tglNo: 5,
        hari: "Rabu",
        siklusKe: 3,
        namaMenu:
            "Nasi Putih, Ikan Fillet Asam Manis, Cah Jagung Pipil Wortel & Pepaya",
        status: "Selesai",
        kaloriPK: 490,
        kaloriPB: 655,
        costPK: 9950,
        costPB: 14200,
        komponen: [
            "Beras Putih",
            "Ikan Kakap Fillet",
            "Saus Asam Manis",
            "Jagung Pipil",
            "Pepaya",
        ],
    },
    {
        tanggal: "2026-08-06",
        tglNo: 6,
        hari: "Kamis",
        siklusKe: 4,
        namaMenu:
            "Nasi Liwet Sunda, Ayam Goreng Lengkuas, Tahu Bacem, Lalapan & Melon",
        status: "Selesai",
        kaloriPK: 510,
        kaloriPB: 670,
        costPK: 10100,
        costPB: 14450,
        komponen: [
            "Beras Liwet",
            "Ayam Lengkuas",
            "Tahu Bacem",
            "Timun & Kemangi",
            "Melon",
        ],
    },
    {
        tanggal: "2026-08-07",
        tglNo: 7,
        hari: "Jumat",
        siklusKe: 5,
        namaMenu:
            "Nasi Putih, Daging Sapi Teriyaki, Sayur Sop Bola Tahu, Semangka",
        status: "Selesai",
        kaloriPK: 530,
        kaloriPB: 690,
        costPK: 10450,
        costPB: 14800,
        komponen: [
            "Beras Putih",
            "Daging Sapi",
            "Saus Teriyaki",
            "Bola Tahu",
            "Semangka Merah",
        ],
    },
    {
        tanggal: "2026-08-08",
        tglNo: 8,
        hari: "Sabtu",
        siklusKe: 6,
        namaMenu:
            "Nasi Gurih, Rolade Ayam Saus Tiram, Capcay Sayur Komplit & Pisang",
        status: "Selesai",
        kaloriPK: 475,
        kaloriPB: 630,
        costPK: 9600,
        costPB: 13600,
        komponen: [
            "Beras Gurih",
            "Rolade Ayam",
            "Saus Tiram",
            "Capcay Wortel Kembang Kol",
            "Pisang",
        ],
    },
    {
        tanggal: "2026-08-10",
        tglNo: 10,
        hari: "Senin",
        siklusKe: 7,
        namaMenu:
            "Nasi Putih, Opor Ayam Kampung, Perkedel Kentang, Sayur Lodeh & Jeruk",
        status: "Selesai",
        kaloriPK: 505,
        kaloriPB: 665,
        costPK: 10200,
        costPB: 14350,
        komponen: [
            "Beras Putih",
            "Ayam Opor",
            "Perkedel Kentang",
            "Sayur Lodeh",
            "Jeruk",
        ],
    },
    {
        tanggal: "2026-08-11",
        tglNo: 11,
        hari: "Selasa",
        siklusKe: 8,
        namaMenu:
            "Nasi Merah Campur, Ikan Kembung Bakar, Tempe Goreng Tepung, Urap Sayur & Melon",
        status: "Selesai",
        kaloriPK: 495,
        kaloriPB: 650,
        costPK: 9800,
        costPB: 13900,
        komponen: [
            "Beras Merah",
            "Ikan Kembung",
            "Tempe Goreng",
            "Urap Sayur",
            "Melon",
        ],
    },
    {
        tanggal: "2026-08-12",
        tglNo: 12,
        hari: "Rabu",
        siklusKe: 9,
        namaMenu:
            "Nasi Putih, Sate Lilit Ayam Bumbu Bali, Tumis Kacang Panjang Tauge & Pepaya",
        status: "Selesai",
        kaloriPK: 480,
        kaloriPB: 635,
        costPK: 9750,
        costPB: 13800,
        komponen: [
            "Beras Putih",
            "Sate Lilit Ayam",
            "Kacang Panjang",
            "Tauge",
            "Pepaya",
        ],
    },
    {
        tanggal: "2026-08-13",
        tglNo: 13,
        hari: "Kamis",
        siklusKe: 10,
        namaMenu:
            "Nasi Kuning Komplit, Empal Gepuk Sapi, Kering Tempe Manis, Acar & Pisang",
        status: "Selesai",
        kaloriPK: 540,
        kaloriPB: 710,
        costPK: 10500,
        costPB: 14900,
        komponen: [
            "Beras Kuning",
            "Empal Sapi",
            "Kering Tempe",
            "Acar Timun",
            "Pisang Raja",
        ],
    },
    {
        tanggal: "2026-08-25",
        tglNo: 25,
        hari: "Selasa",
        siklusKe: 2,
        namaMenu:
            "Paket Nasi Kuning Ayam Suwir, Telur Balado, Tempe Orek, Tumis Buncis Wortel & Pisang",
        status: "Aktif Hari Ini",
        kaloriPK: 485,
        kaloriPB: 640,
        costPK: 9850,
        costPB: 13950,
        komponen: [
            "Beras Putih Lokal",
            "Ayam Suwir Fillet",
            "Telur Balado",
            "Tempe Orek",
            "Buncis Wortel",
            "Pisang Raja",
        ],
    },
    {
        tanggal: "2026-08-26",
        tglNo: 26,
        hari: "Rabu",
        siklusKe: 3,
        namaMenu:
            "Nasi Putih, Ikan Fillet Asam Manis, Cah Jagung Pipil Wortel & Pepaya",
        status: "Siap Produksi",
        kaloriPK: 490,
        kaloriPB: 655,
        costPK: 9950,
        costPB: 14200,
        komponen: [
            "Beras Putih",
            "Ikan Kakap Fillet",
            "Saus Asam Manis",
            "Jagung Pipil",
            "Pepaya",
        ],
    },
    {
        tanggal: "2026-08-27",
        tglNo: 27,
        hari: "Kamis",
        siklusKe: 4,
        namaMenu:
            "Nasi Liwet Sunda, Ayam Goreng Lengkuas, Tahu Bacem, Lalapan & Melon",
        status: "Rencana",
        kaloriPK: 510,
        kaloriPB: 670,
        costPK: 10100,
        costPB: 14450,
        komponen: [
            "Beras Liwet",
            "Ayam Lengkuas",
            "Tahu Bacem",
            "Timun & Kemangi",
            "Melon",
        ],
    },
    {
        tanggal: "2026-08-28",
        tglNo: 28,
        hari: "Jumat",
        siklusKe: 5,
        namaMenu:
            "Nasi Putih, Daging Sapi Teriyaki, Sayur Sop Bola Tahu, Semangka",
        status: "Rencana",
        kaloriPK: 530,
        kaloriPB: 690,
        costPK: 10450,
        costPB: 14800,
        komponen: [
            "Beras Putih",
            "Daging Sapi",
            "Saus Teriyaki",
            "Bola Tahu",
            "Semangka Merah",
        ],
    },
    {
        tanggal: "2026-08-29",
        tglNo: 29,
        hari: "Sabtu",
        siklusKe: 6,
        namaMenu:
            "Nasi Gurih, Rolade Ayam Saus Tiram, Capcay Sayur Komplit & Pisang",
        status: "Rencana",
        kaloriPK: 475,
        kaloriPB: 630,
        costPK: 9600,
        costPB: 13600,
        komponen: [
            "Beras Gurih",
            "Rolade Ayam",
            "Saus Tiram",
            "Capcay Sayur",
            "Pisang",
        ],
    },
]);

// ==========================================
// 1. STATE WORK ORDER & PRE-ORDER (AHLI GIZI)
// ==========================================
const woNo = ref(
    "WO-MBG-" +
        new Date().toISOString().slice(0, 10).replace(/-/g, "") +
        "-001",
);
const tanggalRencana = ref(new Date().toISOString().split("T")[0]);
const namaMenuAktif = ref("");
const woStatus = ref("draft"); // 'draft' | 'in_progress' | 'completed'

function normalizeAlergiItems(data, pkAlergi = 0, pbAlergi = 0) {
    if (Array.isArray(data) && data.length > 0) {
        return data.map((item) => {
            if (typeof item === "string") {
                return {
                    jenis_alergi: item,
                    porsi_kecil: Number(pkAlergi) || 0,
                    porsi_besar: Number(pbAlergi) || 0,
                };
            }
            return {
                jenis_alergi: item.jenis_alergi || "Lainnya",
                porsi_kecil: Number(item.porsi_kecil) || 0,
                porsi_besar: Number(item.porsi_besar) || 0,
            };
        });
    }
    if (Number(pkAlergi) > 0 || Number(pbAlergi) > 0) {
        return [
            {
                jenis_alergi: "Alergi Khusus",
                porsi_kecil: Number(pkAlergi) || 0,
                porsi_besar: Number(pbAlergi) || 0,
            },
        ];
    }
    return [];
}

function normalizeKelompokForWo(k) {
    const subCats = getSubKategoriByKategori(k.kategori);
    let rincianArr = [];
    if (Array.isArray(k.rincian) && k.rincian.length > 0) {
        rincianArr = k.rincian.map((r) => ({
            id: r.id,
            sub_kategori: r.sub_kategori,
            jenis_porsi:
                r.jenis_porsi ||
                getJenisPorsiBySubKategori(r.sub_kategori, k.kategori),
            jumlah_laki_laki: Number(r.jumlah_laki_laki) || 0,
            jumlah_perempuan: Number(r.jumlah_perempuan) || 0,
            total:
                (Number(r.jumlah_laki_laki) || 0) +
                (Number(r.jumlah_perempuan) || 0),
        }));
    } else {
        rincianArr = subCats.map((sub) => {
            const jp = getJenisPorsiBySubKategori(sub, k.kategori);
            return {
                id: null,
                sub_kategori: sub,
                jenis_porsi: jp,
                jumlah_laki_laki: 0,
                jumlah_perempuan: 0,
                total: 0,
            };
        });
    }

    // Hitung total PK & PB dari rincian jika ada
    const calcPK = rincianArr
        .filter((r) => r.jenis_porsi === "Porsi Kecil")
        .reduce((sum, r) => sum + (r.jumlah_laki_laki + r.jumlah_perempuan || 0), 0);
    const calcPB = rincianArr
        .filter((r) => r.jenis_porsi === "Porsi Besar")
        .reduce((sum, r) => sum + (r.jumlah_laki_laki + r.jumlah_perempuan || 0), 0);

    const pk = calcPK > 0 ? calcPK : Number(k.total_porsi_kecil) || 0;
    const pb = calcPB > 0 ? calcPB : Number(k.total_porsi_besar) || 0;

    const normAlergi = normalizeAlergiItems(
        k.keterangan_alergi,
        k.alergi_porsi_kecil,
        k.alergi_porsi_besar,
    );
    const sumAlergiPk = normAlergi.reduce((s, a) => s + (Number(a.porsi_kecil) || 0), 0);
    const sumAlergiPb = normAlergi.reduce((s, a) => s + (Number(a.porsi_besar) || 0), 0);

    return {
        id: k.id,
        nama_kelompok: k.nama_kelompok,
        kategori: k.kategori,
        desa_kelurahan: k.desa_kelurahan,
        kecamatan: k.kecamatan,
        status_menerima:
            k.status_menerima !== undefined ? k.status_menerima : true,
        rincian: sortRincianByKategori(rincianArr, k.kategori),
        total_porsi_kecil: pk,
        total_porsi_besar: pb,
        total_penerima: pk + pb,
        alergi_porsi_kecil: sumAlergiPk > 0 ? sumAlergiPk : Number(k.alergi_porsi_kecil) || 0,
        alergi_porsi_besar: sumAlergiPb > 0 ? sumAlergiPb : Number(k.alergi_porsi_besar) || 0,
        keterangan_alergi: normAlergi,
    };
}

// Daftar Kelompok Sasaran Terjadwal untuk Work Order Ini
const woKelompokList = ref(props.kelompokList.map(normalizeKelompokForWo));

function handleResetWoKelompokList() {
    woKelompokList.value = props.kelompokList.map(normalizeKelompokForWo);
}

function handleToggleStatusMenerima(k) {
    k.status_menerima = !k.status_menerima;
}

// State & Method Modal Edit Detail PM per Sub-Sub Kategori
const showModalEditPm = ref(false);
const editingKelompok = ref(null);
const editFormRincian = ref([]);
const editFormKeteranganAlergi = ref([]);

function handleOpenModalEditPm(kelompok) {
    editingKelompok.value = kelompok;
    editFormRincian.value = JSON.parse(
        JSON.stringify(kelompok.rincian || []),
    );
    editFormKeteranganAlergi.value = JSON.parse(
        JSON.stringify(kelompok.keterangan_alergi || []),
    );
    showModalEditPm.value = true;
}

const modalTotalPk = computed(() => {
    return editFormRincian.value
        .filter((r) => r.jenis_porsi === "Porsi Kecil")
        .reduce(
            (sum, r) =>
                sum +
                ((Number(r.jumlah_laki_laki) || 0) +
                    (Number(r.jumlah_perempuan) || 0)),
            0,
        );
});

const modalTotalPb = computed(() => {
    return editFormRincian.value
        .filter((r) => r.jenis_porsi === "Porsi Besar")
        .reduce(
            (sum, r) =>
                sum +
                ((Number(r.jumlah_laki_laki) || 0) +
                    (Number(r.jumlah_perempuan) || 0)),
            0,
        );
});

const modalTotalPm = computed(() => {
    return modalTotalPk.value + modalTotalPb.value;
});

const modalTotalAlergiPk = computed(() => {
    return editFormKeteranganAlergi.value.reduce(
        (sum, item) => sum + (Math.max(0, Number(item.porsi_kecil)) || 0),
        0,
    );
});

const modalTotalAlergiPb = computed(() => {
    return editFormKeteranganAlergi.value.reduce(
        (sum, item) => sum + (Math.max(0, Number(item.porsi_besar)) || 0),
        0,
    );
});

const modalGrandTotalAlergi = computed(() => {
    return modalTotalAlergiPk.value + modalTotalAlergiPb.value;
});

function handleSimpanEditDetailPm() {
    if (!editingKelompok.value) return;

    // Update data rincian
    editingKelompok.value.rincian = editFormRincian.value.map((r) => ({
        ...r,
        jumlah_laki_laki: Math.max(0, Number(r.jumlah_laki_laki) || 0),
        jumlah_perempuan: Math.max(0, Number(r.jumlah_perempuan) || 0),
        total:
            Math.max(0, Number(r.jumlah_laki_laki) || 0) +
            Math.max(0, Number(r.jumlah_perempuan) || 0),
    }));

    // Update data rincian alergi
    editingKelompok.value.keterangan_alergi = editFormKeteranganAlergi.value.map((a) => ({
        jenis_alergi: a.jenis_alergi || "Lainnya",
        porsi_kecil: Math.max(0, Number(a.porsi_kecil) || 0),
        porsi_besar: Math.max(0, Number(a.porsi_besar) || 0),
    }));

    editingKelompok.value.total_porsi_kecil = modalTotalPk.value;
    editingKelompok.value.total_porsi_besar = modalTotalPb.value;
    editingKelompok.value.total_penerima = modalTotalPm.value;
    editingKelompok.value.alergi_porsi_kecil = modalTotalAlergiPk.value;
    editingKelompok.value.alergi_porsi_besar = modalTotalAlergiPb.value;

    showModalEditPm.value = false;
    editingKelompok.value = null;
}

function formatTanggalIndo(dateStr) {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr + "T00:00:00");
        return d.toLocaleDateString("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    } catch (e) {
        return dateStr;
    }
}

// Menu rekomendasi dari kalender siklus bulan berjalan yang sesuai tanggal atau saran
const menuSaranDariKalender = computed(() => {
    return (
        jadwalMenuBulan.value.find((j) => j.tanggal === tanggalRencana.value) ||
        null
    );
});

function handleGunakanMenuSaran(item) {
    if (item) {
        namaMenuAktif.value = item.namaMenu;
    }
}

function handleMulaiFormulasiWo() {
    if (!namaMenuAktif.value || !namaMenuAktif.value.trim()) {
        alert("Nama Menu Produksi MBG wajib diisi pada Work Order!");
        return;
    }
    if (!tanggalRencana.value) {
        alert("Tanggal Distribusi Menu wajib dipilih pada Work Order!");
        return;
    }
    const menerimaCount = woKelompokList.value.filter(
        (k) => k.status_menerima !== false,
    ).length;
    if (menerimaCount === 0) {
        alert("Minimal harus ada 1 kelompok sasaran yang berstatus 'Menerima' pada Work Order!");
        return;
    }
    woStatus.value = "in_progress";
    buatMenuSubTab.value = "pre_order";
}

// Resep Bahan Baku Baku Terpilih dari Database Resmi TKPI 2020 (Default Kosong dari 0)
const selectedBahanList = ref([]);

// Resep Bahan Pengganti / Substitusi untuk Varian Alergi (misal: Alergi Telur)
const varianAlergiTelurBahan = computed(() => {
    if (selectedBahanList.value.length === 0) return [];
    return selectedBahanList.value.filter((b) => b.alergen !== "Telur");
});

// Database Master TKPI 2020 (dari CSV backend props.tkpiList)
const tkpiItems = ref(props.tkpiList || []);

// Fungsi Kalkulasi MBG
function calculateGrossWeightKg(
    netGram,
    totalPortions,
    bddPercent,
    bufferPercent,
) {
    if (!netGram || !totalPortions || !bddPercent || bddPercent <= 0) return 0;
    const bddFactor = (bddPercent || 100) / 100;
    const bufferFactor = 1 + (bufferPercent || 0) / 100;
    const grossGramPerPortion = (netGram / bddFactor) * bufferFactor;
    const totalKg = (grossGramPerPortion * totalPortions) / 1000;
    return Number(totalKg.toFixed(2));
}

function calculateNutritionFromNetGram(itemTkpi, netGram) {
    if (!itemTkpi || !netGram) {
        return { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    }
    const factor = netGram / 100;
    return {
        energi: Number(((itemTkpi.energi || 0) * factor).toFixed(1)),
        protein: Number(((itemTkpi.protein || 0) * factor).toFixed(1)),
        lemak: Number(((itemTkpi.lemak || 0) * factor).toFixed(1)),
        karbohidrat: Number(((itemTkpi.karbohidrat || 0) * factor).toFixed(1)),
        serat: Number(((itemTkpi.serat || 0) * factor).toFixed(1)),
    };
}

function calculateItemFoodCostPerPortion(
    netGram,
    bddPercent,
    bufferPercent,
    hargaPerKg,
) {
    if (!netGram || !hargaPerKg || !bddPercent || bddPercent <= 0) return 0;
    const bddFactor = (bddPercent || 100) / 100;
    const bufferFactor = 1 + (bufferPercent || 0) / 100;
    const grossGram = (netGram / bddFactor) * bufferFactor;
    const cost = (grossGram / 1000) * hargaPerKg;
    return Math.round(cost);
}

// Combobox Selector Bahan TKPI (Searchable & Scrollable untuk 1.067 data resmi)
const isTkpiDropdownOpen = ref(false);
const tkpiSearchText = ref("");
const selectedTkpiOption = ref("");
const comboboxRef = ref(null);

const filteredComboboxTkpiList = computed(() => {
    const q = tkpiSearchText.value.toLowerCase().trim();
    if (!q) {
        return tkpiItems.value; // Tampilkan seluruh bahan dari dataset TKPI 2020
    }
    const searchTerms = q.split(/\s+/).filter(Boolean);
    return tkpiItems.value.filter((item) => {
        const itemStr =
            `${item.nama || ""} ${item.kategori || ""} ${item.kategori_raw || ""} ${item.id || ""} ${item.code || ""}`.toLowerCase();
        return searchTerms.every((term) => itemStr.includes(term));
    });
});

const selectedTkpiItem = computed(() => {
    if (!selectedTkpiOption.value) return null;
    return tkpiItems.value.find(
        (i) =>
            i.id === selectedTkpiOption.value ||
            i.code === selectedTkpiOption.value,
    );
});

function selectTkpiItem(item) {
    selectedTkpiOption.value = item.id || item.code;
    isTkpiDropdownOpen.value = false;
    tkpiSearchText.value = "";
}

function onDocumentClick(e) {
    if (comboboxRef.value && !comboboxRef.value.contains(e.target)) {
        isTkpiDropdownOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener("click", onDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener("click", onDocumentClick);
});

function handleAddBahan() {
    if (!selectedTkpiOption.value) return;
    const master = tkpiItems.value.find(
        (i) =>
            i.id === selectedTkpiOption.value ||
            i.code === selectedTkpiOption.value,
    );
    if (!master) return;

    // Nilai BDD langsung diisi otomatis dari data resmi TKPI 2020
    let bddValue = 100;
    if (master.bdd !== undefined && master.bdd !== null && master.bdd !== "") {
        const parsed = Number(master.bdd);
        if (!isNaN(parsed) && parsed > 0) {
            bddValue = parsed;
        }
    }

    selectedBahanList.value.push({
        tkpi_id: master.id || master.code,
        kategori: master.kategori,
        nama: master.nama,
        tipe_porsi: "normal", // 'normal' | 'alergi'
        jenis_alergi: "", // Diisi jika tipe_porsi === 'alergi'
        gram_pk: 0,
        gram_pb: 0,
        bdd: bddValue, // BDD otomatis dari TKPI (disabled)
        buffer: 0,
        harga_master: master.harga_master || 0,
        harga_aktual: master.harga_master || 0,
        alergen: master.alergen,
    });
    selectedTkpiOption.value = "";
    tkpiSearchText.value = "";
    isTkpiDropdownOpen.value = false;
}

function handleRemoveBahan(index) {
    selectedBahanList.value.splice(index, 1);
}

function handleAjukanDraftPo() {
    if (!namaMenuAktif.value || !namaMenuAktif.value.trim()) {
        alert("Nama Menu Produksi MBG wajib diisi!");
        return;
    }
    if (!tanggalRencana.value) {
        alert("Tanggal Rencana Masak & Distribusi wajib diisi!");
        return;
    }
    if (selectedBahanList.value.length === 0) {
        alert(
            "Harap tambahkan minimal 1 bahan makanan dari Database TKPI 2020!",
        );
        return;
    }
    for (let i = 0; i < selectedBahanList.value.length; i++) {
        const b = selectedBahanList.value[i];
        if (
            b.tipe_porsi === "alergi" &&
            (!b.jenis_alergi || !b.jenis_alergi.trim())
        ) {
            alert(
                `Untuk bahan "${b.nama}" (Porsi Alergi), jenis alergi wajib diisi/dipilih!`,
            );
            return;
        }
        if (b.gram_pk === null || b.gram_pk === undefined || b.gram_pk === "") {
            alert(
                `Gram PK untuk bahan "${b.nama}" wajib diisi (tidak boleh kosong)!`,
            );
            return;
        }
        if (b.gram_pb === null || b.gram_pb === undefined || b.gram_pb === "") {
            alert(
                `Gram PB untuk bahan "${b.nama}" wajib diisi (tidak boleh kosong)!`,
            );
            return;
        }
        if (
            b.bdd === null ||
            b.bdd === undefined ||
            b.bdd === "" ||
            Number(b.bdd) <= 0
        ) {
            alert(
                `BDD (%) untuk bahan "${b.nama}" wajib diisi dan harus lebih dari 0%!`,
            );
            return;
        }
        if (b.buffer === null || b.buffer === undefined || b.buffer === "") {
            alert(
                `Buffer (%) untuk bahan "${b.nama}" wajib diisi (tidak boleh kosong)!`,
            );
            return;
        }
    }
    buatMenuSubTab.value = "order";
}

// ==========================================
// 2. STATE ORDER (AKUNTAN)
// ==========================================
// Status: 'draft' | 'approved' | 'rejected'
const poStatus = ref("draft");
const poCatatanAkuntan = ref(
    "Harga pasar telah diverifikasi sesuai penawaran supplier lokal.",
);
const poNo = ref("PO-SPPG-" + new Date().getFullYear() + "08-001");

function approvePo() {
    poStatus.value = "approved";
}

function rejectPo() {
    poStatus.value = "rejected";
}

function resetPo() {
    poStatus.value = "draft";
}

// ==========================================
// KALKULASI REKAPITULASI MBG (DARI WORK ORDER KELOMPOK AKTIF / MENERIMA)
// ==========================================
const kelompokMenerimaAktif = computed(() => {
    return woKelompokList.value.filter((k) => k.status_menerima !== false);
});

const totalPK = computed(() => {
    return kelompokMenerimaAktif.value.reduce(
        (acc, k) => acc + (Number(k.total_porsi_kecil) || 0),
        0,
    );
});
const totalPB = computed(() => {
    return kelompokMenerimaAktif.value.reduce(
        (acc, k) => acc + (Number(k.total_porsi_besar) || 0),
        0,
    );
});
const totalPM = computed(() => {
    return totalPK.value + totalPB.value;
});

// Rekapitulasi Alergi KPM
const totalPKAlergi = computed(() => {
    return kelompokMenerimaAktif.value.reduce(
        (acc, k) => acc + (Number(k.alergi_porsi_kecil) || 0),
        0,
    );
});
const totalPBAlergi = computed(() => {
    return kelompokMenerimaAktif.value.reduce(
        (acc, k) => acc + (Number(k.alergi_porsi_besar) || 0),
        0,
    );
});

// Keyword dictionary untuk deteksi cerdas alergen dari nama menu
const ALLERGEN_KEYWORDS = {
    "Ikan Laut / Seafood": [
        "ikan", "fish", "tuna", "tongkol", "lele", "bandeng", "gurame", "nila", "dori",
        "salmon", "patin", "seafood", "cumi", "gurita", "kakap", "tenggiri", "kembung",
    ],
    "Udang & Krustasea": [
        "udang", "shrimp", "prawn", "kepiting", "crab", "lobster", "rajungan",
    ],
    "Telur": [
        "telur", "egg", "dadar", "ceplok", "balado telur", "omelet", "puyuh", "mata sapi",
    ],
    "Susu Sapi / Laktosa": [
        "susu", "milk", "keju", "cheese", "mentega", "butter", "yogurt", "krim", "cream",
    ],
    "Kacang Tanah & Pohon": [
        "kacang", "peanut", "bumbu kacang", "almond", "mete", "cashew", "pecel", "gado-gado", "saus kacang",
    ],
    "Kedelai / Soja": [
        "kedelai", "soy", "tahu", "tempe", "tauco", "kecap", "edamame",
    ],
    "Gandum / Gluten": [
        "gandum", "wheat", "gluten", "roti", "mie", "mi", "pasta", "spageti", "makaroni", "tepung",
    ],
    "Daging Ayam / Unggas": [
        "ayam", "chicken", "bebek", "unggas",
    ],
    "Daging Sapi": [
        "sapi", "beef", "daging", "bakso", "rendang", "rawon", "empal",
    ],
    "Cokelat": [
        "cokelat", "chocolate", "coklat", "cocoa",
    ],
};

const REKOMENDASI_SUBSTITUSI = {
    "Ikan Laut / Seafood": "Fillet Daging Ayam, Daging Sapi, Tahu, atau Tempe",
    "Udang & Krustasea": "Daging Ayam, Daging Sapi, atau Telur",
    "Telur": "Tahu Sutra, Tempe, Daging Ayam, atau Ikan",
    "Susu Sapi / Laktosa": "Susu Kedelai, Susu Almond, atau Sari Gandum Oat",
    "Kacang Tanah & Pohon": "Saus Wijen, Saus Tomat, atau Bumbu Kecap Rempah",
    "Kedelai / Soja": "Telur, Daging Ayam, Ikan, atau Kacang Merah",
    "Gandum / Gluten": "Nasi Putih, Jagung Pipil, Kentang, Ubi, atau Bihun Beras",
    "Daging Ayam / Unggas": "Ikan Fillet, Telur, Daging Sapi, atau Tahu",
    "Daging Sapi": "Daging Ayam, Ikan, Telur, atau Tempe",
};

// Rekapitulasi Alergi per Jenis dari seluruh kelompok sasaran aktif
const rekapAlergiDetailPm = computed(() => {
    const summary = {};
    kelompokMenerimaAktif.value.forEach((k) => {
        if (Array.isArray(k.keterangan_alergi)) {
            k.keterangan_alergi.forEach((item) => {
                const jenis = typeof item === "string" ? item : item.jenis_alergi;
                if (!jenis) return;
                const cleanJenis = jenis.trim();
                if (!summary[cleanJenis]) {
                    summary[cleanJenis] = {
                        jenis_alergi: cleanJenis,
                        porsi_kecil: 0,
                        porsi_besar: 0,
                        total: 0,
                        kelompok_names: [],
                    };
                }
                const pk = Number(item.porsi_kecil) || 0;
                const pb = Number(item.porsi_besar) || 0;
                summary[cleanJenis].porsi_kecil += pk;
                summary[cleanJenis].porsi_besar += pb;
                summary[cleanJenis].total += pk + pb;
                if (
                    pk + pb > 0 &&
                    !summary[cleanJenis].kelompok_names.includes(k.nama_kelompok)
                ) {
                    summary[cleanJenis].kelompok_names.push(k.nama_kelompok);
                }
            });
        }
    });
    return Object.values(summary).filter((item) => item.total > 0);
});

// Analisa & Rekomendasi Alergi Menu Terhadap Detail PM
const analisaAlergiMenu = computed(() => {
    const menuLower = (namaMenuAktif.value || "").toLowerCase();
    const activeAlergi = rekapAlergiDetailPm.value;
    const conflicts = [];

    activeAlergi.forEach((al) => {
        let matched = false;
        let matchedKeyword = "";

        // Cari di dictionary kata kunci alergen
        for (const [allergenName, keywords] of Object.entries(ALLERGEN_KEYWORDS)) {
            const isRelated =
                allergenName.toLowerCase().includes(al.jenis_alergi.toLowerCase()) ||
                al.jenis_alergi.toLowerCase().includes(allergenName.toLowerCase());

            if (isRelated) {
                for (const kw of keywords) {
                    const regex = new RegExp(`\\b${kw}`, "i");
                    if (regex.test(menuLower) || menuLower.includes(kw)) {
                        matched = true;
                        matchedKeyword = kw;
                        break;
                    }
                }
            }
            if (matched) break;
        }

        // Cek nama jenis alergi langsung
        if (!matched && menuLower.includes(al.jenis_alergi.toLowerCase())) {
            matched = true;
            matchedKeyword = al.jenis_alergi;
        }

        if (matched) {
            const rekomendasiBahan =
                REKOMENDASI_SUBSTITUSI[al.jenis_alergi] ||
                "Bahan pangan sumber protein/karbohidrat alternatif non-alergen";

            conflicts.push({
                ...al,
                keyword: matchedKeyword,
                rekomendasi: rekomendasiBahan,
            });
        }
    });

    const totalAlergiPm = activeAlergi.reduce((s, a) => s + a.total, 0);

    return {
        totalSiswaAlergi: totalAlergiPm,
        activeAlergiList: activeAlergi,
        conflicts: conflicts,
        hasConflicts: conflicts.length > 0,
    };
});

// Daftar opsi alergi dari data KPM dan standar untuk select dropdown bahan
const daftarAlergiKpm = computed(() => {
    const list = new Set([
        "Alergi Telur",
        "Alergi Ikan / Seafood",
        "Alergi Kacang Tanah",
        "Alergi Susu Sapi (Laktosa)",
        "Alergi Gandum / Gluten",
        "Alergi Udang / Kepiting",
        "Alergi Kedelai",
    ]);
    rekapAlergiDetailPm.value.forEach((al) => {
        list.add(al.jenis_alergi);
        list.add(`Alergi ${al.jenis_alergi}`);
    });
    return Array.from(list);
});

// Kalkulasi Detail per Bahan (Gross Weight, Biaya Draft Master, Biaya Aktual Akuntan)
const bahanCalculations = computed(() => {
    return selectedBahanList.value.map((b) => {
        const tkpi =
            tkpiItems.value.find(
                (i) => i.id === b.tkpi_id || i.code === b.tkpi_id,
            ) || {};
        const bdd = b.bdd || 100;
        const buffer = b.buffer || 0;
        const isAlergi = b.tipe_porsi === "alergi";

        // Kuota sasaran porsi (Normal vs Alergi)
        const targetPKCount = isAlergi
            ? totalPKAlergi.value || totalPK.value
            : totalPK.value;
        const targetPBCount = isAlergi
            ? totalPBAlergi.value || totalPB.value
            : totalPB.value;

        // Kebutuhan Kotor Kg untuk PK dan PB
        const grossKgPK = calculateGrossWeightKg(
            b.gram_pk,
            targetPKCount,
            bdd,
            buffer,
        );
        const grossKgPB = calculateGrossWeightKg(
            b.gram_pb,
            targetPBCount,
            bdd,
            buffer,
        );
        const totalGrossKg = Number((grossKgPK + grossKgPB).toFixed(2));

        // Biaya PO
        const subtotalMaster = Math.round(totalGrossKg * (b.harga_master || 0));
        const subtotalAktual = Math.round(
            totalGrossKg * (b.harga_aktual || b.harga_master || 0),
        );

        // Food cost per porsi
        const costPK = calculateItemFoodCostPerPortion(
            b.gram_pk,
            bdd,
            buffer,
            b.harga_aktual || b.harga_master,
        );
        const costPB = calculateItemFoodCostPerPortion(
            b.gram_pb,
            bdd,
            buffer,
            b.harga_aktual || b.harga_master,
        );

        // Nutrisi per porsi
        const nutrisiPK = calculateNutritionFromNetGram(tkpi, b.gram_pk);
        const nutrisiPB = calculateNutritionFromNetGram(tkpi, b.gram_pb);

        return {
            ...b,
            tkpi,
            isAlergi,
            targetPKCount,
            targetPBCount,
            grossKgPK,
            grossKgPB,
            totalGrossKg,
            subtotalMaster,
            subtotalAktual,
            costPK,
            costPB,
            nutrisiPK,
            nutrisiPB,
        };
    });
});

// Grand Total Biaya PO
const grandTotalDraftMaster = computed(() => {
    return bahanCalculations.value.reduce(
        (acc, item) => acc + item.subtotalMaster,
        0,
    );
});
const grandTotalAktual = computed(() => {
    return bahanCalculations.value.reduce(
        (acc, item) => acc + item.subtotalAktual,
        0,
    );
});

// ==========================================
// 3. KALKULASI HASIL AKG
// ==========================================
const akgResultPKNormal = computed(() => {
    const res = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    bahanCalculations.value
        .filter((b) => b.tipe_porsi !== "alergi")
        .forEach((b) => {
            res.energi += b.nutrisiPK.energi;
            res.protein += b.nutrisiPK.protein;
            res.lemak += b.nutrisiPK.lemak;
            res.karbohidrat += b.nutrisiPK.karbohidrat;
            res.serat += b.nutrisiPK.serat;
        });
    return {
        energi: Number(res.energi.toFixed(1)),
        protein: Number(res.protein.toFixed(1)),
        lemak: Number(res.lemak.toFixed(1)),
        karbohidrat: Number(res.karbohidrat.toFixed(1)),
        serat: Number(res.serat.toFixed(1)),
    };
});

const akgResultPBNormal = computed(() => {
    const res = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    bahanCalculations.value
        .filter((b) => b.tipe_porsi !== "alergi")
        .forEach((b) => {
            res.energi += b.nutrisiPB.energi;
            res.protein += b.nutrisiPB.protein;
            res.lemak += b.nutrisiPB.lemak;
            res.karbohidrat += b.nutrisiPB.karbohidrat;
            res.serat += b.nutrisiPB.serat;
        });
    return {
        energi: Number(res.energi.toFixed(1)),
        protein: Number(res.protein.toFixed(1)),
        lemak: Number(res.lemak.toFixed(1)),
        karbohidrat: Number(res.karbohidrat.toFixed(1)),
        serat: Number(res.serat.toFixed(1)),
    };
});

// AKG Varian Bebas Telur / Alergi
const akgResultPKAlergi = computed(() => {
    const res = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    varianAlergiTelurBahan.value.forEach((b) => {
        const tkpi = TKPI_2020_DATABASE.find((i) => i.id === b.tkpi_id) || {};
        const n = calculateNutritionFromNetGram(tkpi, b.gram_pk);
        res.energi += n.energi;
        res.protein += n.protein;
        res.lemak += n.lemak;
        res.karbohidrat += n.karbohidrat;
        res.serat += n.serat;
    });
    return {
        energi: Number(res.energi.toFixed(1)),
        protein: Number(res.protein.toFixed(1)),
        lemak: Number(res.lemak.toFixed(1)),
        karbohidrat: Number(res.karbohidrat.toFixed(1)),
        serat: Number(res.serat.toFixed(1)),
    };
});

const akgResultPBAlergi = computed(() => {
    const res = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    varianAlergiTelurBahan.value.forEach((b) => {
        const tkpi = TKPI_2020_DATABASE.find((i) => i.id === b.tkpi_id) || {};
        const n = calculateNutritionFromNetGram(tkpi, b.gram_pb);
        res.energi += n.energi;
        res.protein += n.protein;
        res.lemak += n.lemak;
        res.karbohidrat += n.karbohidrat;
        res.serat += n.serat;
    });
    return {
        energi: Number(res.energi.toFixed(1)),
        protein: Number(res.protein.toFixed(1)),
        lemak: Number(res.lemak.toFixed(1)),
        karbohidrat: Number(res.karbohidrat.toFixed(1)),
        serat: Number(res.serat.toFixed(1)),
    };
});

// ==========================================
// 4. KALKULASI HASIL FOOD COST
// ==========================================
const totalFoodCostPKNormal = computed(() => {
    return bahanCalculations.value.reduce((acc, item) => acc + item.costPK, 0);
});
const totalFoodCostPBNormal = computed(() => {
    return bahanCalculations.value.reduce((acc, item) => acc + item.costPB, 0);
});

// Food Cost Varian Alergi
const totalFoodCostPKAlergi = computed(() => {
    return varianAlergiTelurBahan.value.reduce((acc, b) => {
        const c = calculateItemFoodCostPerPortion(
            b.gram_pk,
            b.bdd,
            b.buffer,
            b.harga_aktual || b.harga_master,
        );
        return acc + c;
    }, 0);
});
const totalFoodCostPBAlergi = computed(() => {
    return varianAlergiTelurBahan.value.reduce((acc, b) => {
        const c = calculateItemFoodCostPerPortion(
            b.gram_pb,
            b.bdd,
            b.buffer,
            b.harga_aktual || b.harga_master,
        );
        return acc + c;
    }, 0);
});

function formatRupiah(num) {
    return "Rp " + (Number(num) || 0).toLocaleString("id-ID");
}

function handlePrintPo() {
    window.print();
}

// ==========================================
// 5. STATE & LOGIKA TKPI 2020 (PAGINASI & FILTER)
// ==========================================
const tkpiSearchQuery = ref("");
const tkpiCategoryFilter = ref("Semua");

const tkpiCurrentPage = ref(1);
const tkpiPerPage = ref(15);

const filteredTkpiList = computed(() => {
    return tkpiItems.value.filter((item) => {
        const matchesCategory =
            tkpiCategoryFilter.value === "Semua" ||
            item.kategori === tkpiCategoryFilter.value;
        const query = tkpiSearchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            item.nama.toLowerCase().includes(query) ||
            item.id.toLowerCase().includes(query) ||
            item.kategori.toLowerCase().includes(query);
        return matchesCategory && matchesSearch;
    });
});

const tkpiTotalPages = computed(() => {
    return Math.ceil(filteredTkpiList.value.length / tkpiPerPage.value) || 1;
});

const paginatedTkpiList = computed(() => {
    const start = (tkpiCurrentPage.value - 1) * tkpiPerPage.value;
    return filteredTkpiList.value.slice(start, start + tkpiPerPage.value);
});

function prevTkpiPage() {
    if (tkpiCurrentPage.value > 1) tkpiCurrentPage.value--;
}

function nextTkpiPage() {
    if (tkpiCurrentPage.value < tkpiTotalPages.value) tkpiCurrentPage.value++;
}

const tkpiCategoryList = computed(() => {
    const cats = new Set(tkpiItems.value.map((i) => i.kategori));
    return ["Semua", ...Array.from(cats)];
});
</script>

<template>
    <AppLayout
        title="Gizi"
        subtitle="Perhitungan Kebutuhan dan Produksi Makan Bergizi Gratis (MBG)"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Gizi" />

        <div class="space-y-6">
            <!-- TOP SUB-MENU NAVIGATOR (4 Menu Gizi) -->
            <div
                class="bg-white rounded-2xl border border-slate-200/90 p-2.5 shadow-xs print:hidden"
            >
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <button
                        v-for="menu in subMenus"
                        :key="menu.id"
                        type="button"
                        @click="selectSubMenu(menu)"
                        :class="[
                            'p-3 rounded-xl transition-all flex items-center gap-3 cursor-pointer text-left border',
                            activeSubMenu === menu.id
                                ? 'bg-primary text-white border-primary shadow-md shadow-primary/20 font-bold'
                                : 'bg-slate-50/70 hover:bg-slate-100/90 text-slate-700 border-slate-200/80 hover:border-slate-300 font-semibold',
                        ]"
                    >
                        <div
                            :class="[
                                'h-8 w-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-colors shadow-2xs',
                                activeSubMenu === menu.id
                                    ? 'bg-white text-primary font-black'
                                    : 'bg-white text-slate-700 font-black border border-slate-200',
                            ]"
                        >
                            {{ menu.no }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-xs sm:text-sm leading-snug font-extrabold truncate flex items-center gap-1.5"
                            >
                                <component
                                    :is="menu.icon"
                                    class="h-3.5 w-3.5 shrink-0 opacity-80"
                                />
                                <span>{{ menu.title }}</span>
                            </p>
                            <p
                                :class="[
                                    'text-[10.5px] leading-tight font-medium truncate mt-0.5',
                                    activeSubMenu === menu.id
                                        ? 'text-blue-100'
                                        : 'text-slate-400',
                                ]"
                            >
                                {{ menu.subtitle }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- 1. SUB MENU 1: TKPI (TABEL KOMPOSISI PANGAN INDONESIA) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === 'tkpi'" class="space-y-6">
                <!-- Header Info & Actions -->
                <Card className="bg-white border-slate-200 shadow-xs">
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                    >
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                        >
                            <div>
                                <CardTitle
                                    class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Database class="h-5 w-5 text-primary" />
                                    <span
                                        >Database Standar Komposisi Pangan
                                        Indonesia (TKPI 2020)</span
                                    >
                                </CardTitle>
                                <CardDescription
                                    class="text-xs sm:text-sm mt-0.5"
                                >
                                    Memuat
                                    <strong
                                        >{{ tkpiItems.length }} bahan
                                        makanan</strong
                                    >
                                    resmi dari file master
                                    <code>database/data/tkpi2020.csv</code>
                                    (Kemenkes RI).
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-5 space-y-4">
                        <!-- Stat Summary Grid (Sejajar 4 Card) -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5"
                        >
                            <div
                                class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 text-center flex flex-col justify-center"
                            >
                                <p
                                    class="text-[10px] font-bold text-blue-700 uppercase tracking-wider"
                                >
                                    TOTAL BAHAN TERDAFTAR
                                </p>
                                <h4
                                    class="text-xl font-black text-blue-950 mt-1"
                                >
                                    {{ tkpiItems.length }}
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >Bahan</span
                                    >
                                </h4>
                            </div>
                            <div
                                class="p-3.5 bg-emerald-50/60 rounded-xl border border-emerald-100 text-center flex flex-col justify-center"
                            >
                                <p
                                    class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider"
                                >
                                    KATEGORI PANGAN
                                </p>
                                <h4
                                    class="text-xl font-black text-emerald-950 mt-1"
                                >
                                    {{ tkpiCategoryList.length - 1 }}
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >Kelompok</span
                                    >
                                </h4>
                            </div>
                            <div
                                class="p-3.5 bg-amber-50/60 rounded-xl border border-amber-100 text-center flex flex-col justify-center"
                            >
                                <p
                                    class="text-[10px] font-bold text-amber-700 uppercase tracking-wider"
                                >
                                    RATA-RATA BDD
                                </p>
                                <h4
                                    class="text-xl font-black text-amber-950 mt-1"
                                >
                                    88.2%
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >Dapat Dimakan</span
                                    >
                                </h4>
                            </div>
                            <div
                                class="p-3.5 bg-purple-50/60 rounded-xl border border-purple-100 text-center flex flex-col justify-center"
                            >
                                <p
                                    class="text-[10px] font-bold text-purple-700 uppercase tracking-wider"
                                >
                                    SUMBER DATA
                                </p>
                                <h4
                                    class="text-base font-black text-purple-950 mt-1"
                                >
                                    tkpi2020.csv
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >({{ tkpiItems.length }} Baris)</span
                                    >
                                </h4>
                            </div>
                        </div>

                        <!-- Search & Filter Controls -->
                        <div class="pt-2 space-y-3">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                            >
                                <div class="relative w-full sm:w-80 shrink-0">
                                    <Search
                                        class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400"
                                    />
                                    <input
                                        type="text"
                                        v-model="tkpiSearchQuery"
                                        placeholder="Cari bahan makanan / kode TKPI..."
                                        class="w-full pl-9 pr-3 py-1.5 text-xs font-medium rounded-lg border-slate-300 focus:ring-primary focus:border-primary"
                                    />
                                </div>
                                <div class="text-xs text-slate-500 font-medium">
                                    Filter Kategori ({{
                                        tkpiCategoryList.length - 1
                                    }}
                                    kelompok)
                                </div>
                            </div>

                            <!-- Horizontal Category Pills dengan Gap & Padding Aman ke Scrollbar -->
                            <div
                                class="flex items-center gap-2 overflow-x-auto w-full pb-3.5 pt-1"
                            >
                                <button
                                    v-for="cat in tkpiCategoryList"
                                    :key="cat"
                                    type="button"
                                    @click="tkpiCategoryFilter = cat"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs rounded-full font-bold border transition-all cursor-pointer whitespace-nowrap shrink-0 shadow-2xs',
                                        tkpiCategoryFilter === cat
                                            ? 'bg-primary text-white border-primary shadow-xs font-extrabold'
                                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
                                    ]"
                                >
                                    {{ cat }}
                                </button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tabel Data Bahan TKPI 2020 -->
                <Card
                    className="bg-white border-slate-200 shadow-xs overflow-hidden"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead
                                class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                            >
                                <tr>
                                    <th class="p-3">Kode</th>
                                    <th class="p-3">Nama Bahan Pangan</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-right">
                                        Energi (Kkal)
                                    </th>
                                    <th class="p-3 text-right">Protein (g)</th>
                                    <th class="p-3 text-right">Lemak (g)</th>
                                    <th class="p-3 text-right">
                                        Karbohidrat (g)
                                    </th>
                                    <th class="p-3 text-right">Serat (g)</th>
                                    <th class="p-3 text-center">BDD (%)</th>
                                    <th class="p-3 text-right">Harga Master</th>
                                    <th class="p-3 text-center">Alergen</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 text-slate-800"
                            >
                                <tr
                                    v-for="item in paginatedTkpiList"
                                    :key="item.id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td
                                        class="p-3 font-mono font-bold text-slate-500 text-[11px]"
                                    >
                                        {{ item.id }}
                                    </td>
                                    <td class="p-3 font-bold text-slate-900">
                                        {{ item.nama }}
                                    </td>
                                    <td class="p-3">
                                        <Badge
                                            variant="outline"
                                            className="text-[10px] font-semibold bg-slate-50"
                                        >
                                            {{ item.kategori }}
                                        </Badge>
                                    </td>
                                    <td
                                        class="p-3 text-right font-bold text-amber-800"
                                    >
                                        {{ item.energi }}
                                    </td>
                                    <td
                                        class="p-3 text-right font-semibold text-blue-800"
                                    >
                                        {{ item.protein }}
                                    </td>
                                    <td class="p-3 text-right text-slate-700">
                                        {{ item.lemak }}
                                    </td>
                                    <td class="p-3 text-right text-slate-700">
                                        {{ item.karbohidrat }}
                                    </td>
                                    <td class="p-3 text-right text-slate-700">
                                        {{ item.serat }}
                                    </td>
                                    <td
                                        class="p-3 text-center font-bold text-emerald-800"
                                    >
                                        {{ item.bdd }}%
                                    </td>
                                    <td
                                        class="p-3 text-right font-bold text-slate-900"
                                    >
                                        {{ formatRupiah(item.harga_master) }}
                                        /kg
                                    </td>
                                    <td class="p-3 text-center">
                                        <span
                                            v-if="item.alergen"
                                            class="text-[10.5px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200"
                                        >
                                            {{ item.alergen }}
                                        </span>
                                        <span v-else class="text-slate-400"
                                            >-</span
                                        >
                                    </td>
                                </tr>
                                <tr v-if="filteredTkpiList.length === 0">
                                    <td
                                        colspan="11"
                                        class="p-8 text-center text-slate-400 font-semibold"
                                    >
                                        Tidak ada data bahan pangan yang sesuai
                                        dengan pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div
                        class="p-3 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between text-xs text-slate-600"
                    >
                        <span>
                            Menampilkan
                            <strong>{{
                                (tkpiCurrentPage - 1) * tkpiPerPage + 1
                            }}</strong>
                            -
                            <strong>{{
                                Math.min(
                                    tkpiCurrentPage * tkpiPerPage,
                                    filteredTkpiList.length,
                                )
                            }}</strong>
                            dari
                            <strong>{{ filteredTkpiList.length }}</strong> bahan
                        </span>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                @click="prevTkpiPage"
                                :disabled="tkpiCurrentPage === 1"
                                className="h-7 px-2.5 text-xs bg-white border border-slate-300 text-slate-700 hover:bg-slate-100"
                            >
                                Sebelumnya
                            </Button>
                            <span class="font-bold text-slate-800"
                                >Hal {{ tkpiCurrentPage }} /
                                {{ tkpiTotalPages }}</span
                            >
                            <Button
                                type="button"
                                @click="nextTkpiPage"
                                :disabled="tkpiCurrentPage >= tkpiTotalPages"
                                className="h-7 px-2.5 text-xs bg-white border border-slate-300 text-slate-700 hover:bg-slate-100"
                            >
                                Selanjutnya
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 2. SUB MENU 2: ANALISA PM (DETAIL SASARAN & PORSI) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === 'analisa-pm'" class="space-y-6">
                <!-- Summary Metrics -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100"
                            >
                                <School class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="text-[10.5px] font-bold text-slate-500 uppercase"
                                >
                                    Kelompok Sasaran
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-black text-slate-900 mt-0.5"
                                >
                                    {{ stats.total_kelompok }}
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >({{ stats.total_sekolah || 0 }}
                                        Sekolah /
                                        {{ stats.total_posyandu || 0 }}
                                        Posyandu)</span
                                    >
                                </h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100"
                            >
                                <UtensilsCrossed class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="text-[10.5px] font-bold text-slate-500 uppercase"
                                >
                                    Porsi Kecil (PK)
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-black text-amber-800 mt-0.5"
                                >
                                    {{ totalPK.toLocaleString("id-ID") }}
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >Porsi</span
                                    >
                                </h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100"
                            >
                                <Layers class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="text-[10.5px] font-bold text-slate-500 uppercase"
                                >
                                    Porsi Besar (PB)
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-black text-indigo-800 mt-0.5"
                                >
                                    {{ totalPB.toLocaleString("id-ID") }}
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >Porsi</span
                                    >
                                </h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100"
                            >
                                <Users class="h-5 w-5" />
                            </div>
                            <div>
                                <p
                                    class="text-[10.5px] font-bold text-slate-500 uppercase"
                                >
                                    Total PM Harian
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-black text-emerald-800 mt-0.5"
                                >
                                    {{ totalPM.toLocaleString("id-ID") }}
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >Porsi</span
                                    >
                                </h3>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Table Detail Rincian per Kelompok & Kategori -->
                <Card
                    className="bg-white border-slate-200 shadow-xs overflow-hidden"
                >
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                    >
                        <CardTitle
                            className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                        >
                            <Users class="h-5 w-5 text-primary" />
                            <span
                                >Tabel Detail Jumlah Penerima Manfaat (PM) per
                                Kategori Sasaran</span
                            >
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm">
                            Dasar kuota produksi harian MBG SPPG terklasifikasi
                            berdasarkan jenjang pendidikan dan kategori porsi.
                        </CardDescription>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead
                                class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                            >
                                <tr>
                                    <th class="p-3.5">Nama Kelompok</th>
                                    <th class="p-3.5">Kategori</th>
                                    <th class="p-3.5 text-center">Laki-Laki</th>
                                    <th class="p-3.5 text-center">Perempuan</th>
                                    <th class="p-3.5 text-center">
                                        Porsi Kecil
                                    </th>
                                    <th class="p-3.5 text-center">
                                        Porsi Besar
                                    </th>
                                    <th class="p-3.5 text-right">Total PM</th>
                                    <th class="p-3.5">
                                        Status Alergi
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 text-slate-800"
                            >
                                <tr
                                    v-for="k in kelompokList"
                                    :key="k.id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td class="p-3.5 font-bold text-slate-900">
                                        <div class="flex items-center gap-2">
                                            <span>{{ k.nama_kelompok }}</span>
                                        </div>
                                        <p
                                            class="text-[10.5px] text-slate-500 font-normal"
                                        >
                                            {{ k.desa_kelurahan }},
                                            {{ k.kecamatan }}
                                        </p>
                                    </td>
                                    <td class="p-3.5">
                                        <Badge
                                            variant="outline"
                                            className="font-bold text-xs bg-slate-50"
                                        >
                                            {{ k.kategori }}
                                        </Badge>
                                    </td>
                                    <td
                                        class="p-3.5 text-center font-semibold text-blue-700"
                                    >
                                        {{ k.total_laki_laki || 0 }}
                                    </td>
                                    <td
                                        class="p-3.5 text-center font-semibold text-rose-700"
                                    >
                                        {{ k.total_perempuan || 0 }}
                                    </td>
                                    <td
                                        class="p-3.5 text-center font-bold text-amber-800 bg-amber-50/30"
                                    >
                                        {{ k.total_porsi_kecil || 0 }}
                                    </td>
                                    <td
                                        class="p-3.5 text-center font-bold text-indigo-800 bg-indigo-50/30"
                                    >
                                        {{ k.total_porsi_besar || 0 }}
                                    </td>
                                    <td
                                        class="p-3.5 text-right font-black text-slate-900 text-sm"
                                    >
                                        {{ k.total_penerima || 0 }}
                                    </td>
                                    <td class="p-3.5">
                                        <div
                                            v-if="
                                                k.alergi_porsi_kecil > 0 ||
                                                k.alergi_porsi_besar > 0
                                            "
                                            class="space-y-1.5"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded shadow-2xs"
                                            >
                                                <HeartPulse
                                                    class="h-3 w-3 text-rose-600"
                                                />
                                                <span
                                                    >{{
                                                        (k.alergi_porsi_kecil ||
                                                            0) +
                                                        (k.alergi_porsi_besar ||
                                                            0)
                                                    }}
                                                    ({{
                                                        k.alergi_porsi_kecil ||
                                                        0
                                                    }}
                                                    PK /
                                                    {{
                                                        k.alergi_porsi_besar ||
                                                        0
                                                    }}
                                                    PB)</span
                                                >
                                            </span>
                                            <div
                                                v-if="
                                                    k.keterangan_alergi &&
                                                    k.keterangan_alergi.length >
                                                        0
                                                "
                                                class="flex items-center gap-1 flex-wrap"
                                            >
                                                <span
                                                    v-for="(
                                                        al, idx
                                                    ) in k.keterangan_alergi"
                                                    :key="idx"
                                                    class="text-[10px] font-semibold text-slate-700 bg-slate-100 border border-slate-200/60 px-1.5 py-0.5 rounded"
                                                >
                                                    {{
                                                        typeof al === "string"
                                                            ? al
                                                            : `${al.jenis_alergi}: ${(al.porsi_kecil || 0) + (al.porsi_besar || 0)}`
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded"
                                        >
                                            <CheckCircle2 class="h-3 w-3" />
                                            Standar Normal (0 Alergi)
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="kelompokList.length === 0">
                                    <td
                                        colspan="8"
                                        class="p-8 text-center text-slate-400 font-semibold"
                                    >
                                        Belum ada data kelompok penerima manfaat
                                        yang terdaftar.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot
                                class="bg-slate-100/80 font-black text-slate-900 border-t-2 border-slate-300 text-xs"
                            >
                                <tr>
                                    <td
                                        colspan="4"
                                        class="p-3.5 uppercase tracking-wider text-slate-700"
                                    >
                                        Total Produksi Porsi SPPG:
                                    </td>
                                    <td
                                        class="p-3.5 text-center text-amber-900"
                                    >
                                        {{ totalPK }}
                                    </td>
                                    <td
                                        class="p-3.5 text-center text-indigo-900"
                                    >
                                        {{ totalPB }}
                                    </td>
                                    <td
                                        class="p-3.5 text-right text-emerald-950 text-sm"
                                    >
                                        {{ totalPM }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 3. SUB MENU 3: BUAT MENU (RESEP, AKG, FOOD COST, PO) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === 'buat-menu'" class="space-y-6">
                <!-- Sub-tab pill bar for Buat Menu -->
                <div
                    class="bg-white rounded-2xl border border-slate-200/90 p-2 shadow-xs flex flex-wrap items-center gap-2 print:hidden"
                >
                    <button
                        v-for="sub in buatMenuSubTabs"
                        :key="sub.id"
                        type="button"
                        @click="buatMenuSubTab = sub.id"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-extrabold transition-all flex items-center gap-2 cursor-pointer border',
                            buatMenuSubTab === sub.id
                                ? 'bg-primary text-white border-primary shadow-xs'
                                : 'bg-slate-50 text-slate-700 border-slate-200/70 hover:bg-slate-100 hover:text-slate-900',
                        ]"
                    >
                        <component
                            :is="sub.icon"
                            class="h-3.5 w-3.5 shrink-0"
                        />
                        <span>{{ sub.label }}</span>
                    </button>
                </div>

                <!-- Sticky / Summary Banner Work Order (Tampil di Step 2, 3, 4, 5) -->
                <div
                    v-if="buatMenuSubTab !== 'work_order'"
                    class="bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 rounded-2xl p-4 sm:p-5 text-white shadow-sm border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4"
                >
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span
                                class="px-2.5 py-0.5 text-xs font-mono font-black rounded-md bg-blue-500/20 text-blue-300 border border-blue-400/30"
                            >
                                {{ woNo }}
                            </span>
                            <span
                                class="px-2.5 py-0.5 text-xs font-bold rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center gap-1"
                            >
                                <CheckCircle2 class="h-3.5 w-3.5" /> Kuota PM Terkunci
                            </span>
                            <span class="text-xs text-slate-300 font-medium">
                                📅 Tanggal Distribusi:
                                <strong class="text-white">{{
                                    formatTanggalIndo(tanggalRencana)
                                }}</strong>
                            </span>
                        </div>
                        <h3
                            class="text-base sm:text-lg font-black text-white truncate"
                        >
                            {{ namaMenuAktif || "Nama Menu Belum Diisi" }}
                        </h3>
                        <div
                            class="flex items-center gap-3 flex-wrap text-xs text-blue-200"
                        >
                            <span
                                >🎯 Total PM:
                                <strong>{{
                                    totalPM.toLocaleString("id-ID")
                                }} Porsi</strong></span
                            >
                            <span
                                >• PK:
                                <strong>{{
                                    totalPK.toLocaleString("id-ID")
                                }} Porsi</strong></span
                            >
                            <span
                                >• PB:
                                <strong>{{
                                    totalPB.toLocaleString("id-ID")
                                }} Porsi</strong></span
                            >
                            <span
                                v-if="totalPKAlergi + totalPBAlergi > 0"
                                >• Alergi:
                                <strong class="text-rose-300"
                                    >{{
                                        totalPKAlergi + totalPBAlergi
                                    }} Siswa</strong
                                ></span
                            >
                            <span
                                >• Terjadwal:
                                <strong class="text-slate-300">{{
                                    woKelompokList.length
                                }} Kelompok</strong></span
                            >
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <Button
                            type="button"
                            @click="buatMenuSubTab = 'work_order'"
                            className="bg-white/10 hover:bg-white/20 text-white text-xs font-bold px-3.5 h-9 flex items-center gap-1.5 border border-white/20 cursor-pointer shadow-none"
                        >
                            <Edit3 class="h-3.5 w-3.5" />
                            <span>Ubah Work Order</span>
                        </Button>
                    </div>
                </div>

                <!-- ========================================================================================= -->
                <!-- Bagian 1: Work Order Produksi (Step 1) -->
                <!-- ========================================================================================= -->
                <div v-if="buatMenuSubTab === 'work_order'" class="space-y-6">
                    <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
                        <CardHeader
                            className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/70"
                        >
                            <div
                                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                            >
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <CardTitle
                                            class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                                        >
                                            <FileSpreadsheet
                                                class="h-5 w-5 text-primary"
                                            />
                                            <span
                                                >Surat Perintah Kerja (Work Order) Produksi MBG</span
                                            >
                                        </CardTitle>
                                        <Badge
                                            variant="outline"
                                            class="bg-blue-50 text-blue-700 border-blue-300 font-extrabold text-xs"
                                        >
                                            Langkah 1 dari 5
                                        </Badge>
                                    </div>
                                    <CardDescription
                                        class="text-xs sm:text-sm mt-0.5"
                                    >
                                        Penetapan jadwal distribusi menu, penamaan paket MBG, dan penguncian kuota Penerima Manfaat (PM) resmi SPPG.
                                    </CardDescription>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        @click="handleMulaiFormulasiWo"
                                        className="bg-primary hover:bg-primary/90 text-white text-xs font-bold px-4 h-9 flex items-center gap-1.5 shadow-xs cursor-pointer"
                                    >
                                        <span>Mulai Formulasi Menu (Step 2)</span>
                                        <ArrowRight class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-5 sm:p-6 space-y-6">
                            <!-- Form Identitas Work Order (Ringkas) -->
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-4"
                            >
                                <!-- No. Work Order -->
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >No. Work Order (SPK):</label
                                    >
                                    <input
                                        type="text"
                                        v-model="woNo"
                                        class="w-full text-xs font-mono font-bold text-slate-900 rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2.5 bg-slate-50"
                                    />
                                </div>

                                <!-- Tanggal Distribusi (Tanggal WO) -->
                                <div class="space-y-1.5 md:col-span-2">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                    >
                                        Tanggal Distribusi Menu (Tanggal WO):
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="date"
                                            v-model="tanggalRencana"
                                            required
                                            class="w-full sm:w-64 text-xs font-bold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2.5 bg-white"
                                        />
                                        <span
                                            class="text-xs font-bold text-primary shrink-0"
                                        >
                                            📅 {{ formatTanggalIndo(tanggalRencana) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Nama Menu Produksi MBG -->
                                <div class="md:col-span-3 space-y-1.5">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <label
                                            class="text-xs font-bold text-slate-700"
                                        >
                                            Nama Menu Produksi MBG:
                                            <span class="text-rose-500">*</span>
                                        </label>
                                        <button
                                            v-if="menuSaranDariKalender"
                                            type="button"
                                            @click="
                                                handleGunakanMenuSaran(
                                                    menuSaranDariKalender,
                                                )
                                            "
                                            class="text-[11px] font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer"
                                        >
                                            <Sparkles class="h-3 w-3" />
                                            Gunakan Rekomendasi Kalender: "{{
                                                menuSaranDariKalender.namaMenu
                                            }}"
                                        </button>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="namaMenuAktif"
                                        required
                                        placeholder="Contoh: Paket Nasi Kuning Ayam Suwir, Tempe Orek, Tumis Buncis & Pisang..."
                                        class="w-full text-xs font-bold text-slate-900 rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2.5 bg-slate-50/40"
                                    />
                                </div>
                            </div>

                            <!-- Ringkasan Kuota PM Fix Berdasarkan Tanggal Work Order -->
                            <div class="space-y-3 pt-4 border-t border-slate-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4
                                            class="text-sm font-black text-slate-900 flex items-center gap-2"
                                        >
                                            <Users class="h-4 w-4 text-primary" />
                                            <span
                                                >Data Kuota Penerima Manfaat (PM) Fix per Tanggal Distribusi</span
                                            >
                                        </h4>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Kuota porsi terkunci otomatis berdasarkan data rekapitulasi siswa & penerima aktif SPPG pada tanggal tersebut.
                                        </p>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        class="bg-emerald-50 text-emerald-700 border-emerald-300 font-extrabold text-xs"
                                    >
                                        <CheckCircle2 class="h-3 w-3 mr-1" /> Kuota Terverifikasi
                                    </Badge>
                                </div>

                                <!-- 4 Metric Cards Kuota PM Fix -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                                    <!-- Total PM -->
                                    <div
                                        class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100/60 border border-blue-200/80 space-y-1 shadow-2xs"
                                    >
                                        <p
                                            class="text-[11px] font-bold text-blue-700 uppercase tracking-wider"
                                        >
                                            Total Sasaran PM Fix
                                        </p>
                                        <h3
                                            class="text-2xl font-black text-blue-950"
                                        >
                                            {{
                                                totalPM.toLocaleString("id-ID")
                                            }}
                                            <span
                                                class="text-xs font-semibold text-blue-700"
                                                >Porsi</span
                                            >
                                        </h3>
                                        <p
                                            class="text-[10.5px] text-blue-600 font-medium"
                                        >
                                            100% Kuota Distribusi Harian
                                        </p>
                                    </div>

                                    <!-- Porsi Kecil -->
                                    <div
                                        class="p-4 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100/60 border border-amber-200/80 space-y-1 shadow-2xs"
                                    >
                                        <p
                                            class="text-[11px] font-bold text-amber-800 uppercase tracking-wider"
                                        >
                                            Porsi Kecil (PK)
                                        </p>
                                        <h3
                                            class="text-2xl font-black text-amber-950"
                                        >
                                            {{
                                                totalPK.toLocaleString("id-ID")
                                            }}
                                            <span
                                                class="text-xs font-semibold text-amber-800"
                                                >Porsi</span
                                            >
                                        </h3>
                                        <p
                                            class="text-[10.5px] text-amber-700 font-medium"
                                        >
                                            TK, PAUD, SD 1-3 & Balita
                                        </p>
                                    </div>

                                    <!-- Porsi Besar -->
                                    <div
                                        class="p-4 rounded-2xl bg-gradient-to-br from-indigo-50 to-indigo-100/60 border border-indigo-200/80 space-y-1 shadow-2xs"
                                    >
                                        <p
                                            class="text-[11px] font-bold text-indigo-800 uppercase tracking-wider"
                                        >
                                            Porsi Besar (PB)
                                        </p>
                                        <h3
                                            class="text-2xl font-black text-indigo-950"
                                        >
                                            {{
                                                totalPB.toLocaleString("id-ID")
                                            }}
                                            <span
                                                class="text-xs font-semibold text-indigo-800"
                                                >Porsi</span
                                            >
                                        </h3>
                                        <p
                                            class="text-[10.5px] text-indigo-700 font-medium"
                                        >
                                            SD 4-6, SMP, SMA, Bumil & Guru
                                        </p>
                                    </div>

                                    <!-- Varian Khusus Alergi -->
                                    <div
                                        class="p-4 rounded-2xl bg-gradient-to-br from-rose-50 to-rose-100/60 border border-rose-200/80 space-y-1 shadow-2xs"
                                    >
                                        <p
                                            class="text-[11px] font-bold text-rose-800 uppercase tracking-wider"
                                        >
                                            Varian Alergi Khusus
                                        </p>
                                        <h3
                                            class="text-2xl font-black text-rose-950"
                                        >
                                            {{
                                                (
                                                    totalPKAlergi +
                                                    totalPBAlergi
                                                ).toLocaleString("id-ID")
                                            }}
                                            <span
                                                class="text-xs font-semibold text-rose-800"
                                                >Siswa</span
                                            >
                                        </h3>
                                        <p
                                            class="text-[10.5px] text-rose-700 font-medium"
                                        >
                                            {{ totalPKAlergi }} PK •
                                            {{ totalPBAlergi }} PB Membutuhkan
                                            Substitusi
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Rincian Kelompok Sasaran Terjadwal (Status Menerima & Edit Detail Sub-Kategori) -->
                            <div class="space-y-3 pt-2">
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                                >
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h5
                                                class="text-xs font-bold text-slate-800 uppercase tracking-wider"
                                            >
                                                Daftar Kelompok Sasaran Distribusi ({{
                                                    woKelompokList.length
                                                }} Kelompok)
                                            </h5>
                                            <Badge
                                                variant="outline"
                                                class="text-[11px] font-extrabold bg-emerald-50 text-emerald-800 border-emerald-300"
                                            >
                                                <UserCheck class="h-3 w-3 mr-1" />
                                                {{ kelompokMenerimaAktif.length }} Menerima
                                            </Badge>
                                            <Badge
                                                v-if="
                                                    woKelompokList.length >
                                                    kelompokMenerimaAktif.length
                                                "
                                                variant="outline"
                                                class="text-[11px] font-extrabold bg-rose-50 text-rose-800 border-rose-300"
                                            >
                                                <UserX class="h-3 w-3 mr-1" />
                                                {{
                                                    woKelompokList.length -
                                                    kelompokMenerimaAktif.length
                                                }}
                                                Tidak Menerima
                                            </Badge>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Kelompok yang dinyatakan <strong>"Tidak Menerima"</strong> kuotanya otomatis dinolkan dari perhitungan Work Order ini.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            @click="handleResetWoKelompokList"
                                            className="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3 h-8 flex items-center gap-1 cursor-pointer shadow-none border border-slate-200"
                                            title="Kembalikan semua kelompok default dari database"
                                        >
                                            <RotateCcw class="h-3.5 w-3.5" />
                                            <span>Reset ke Default</span>
                                        </Button>
                                    </div>
                                </div>
                                <div
                                    class="rounded-xl border border-slate-200 overflow-hidden"
                                >
                                    <table
                                        class="w-full text-left text-xs border-collapse"
                                    >
                                        <thead
                                            class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                        >
                                            <tr>
                                                <th class="p-3 text-center">Status</th>
                                                <th class="p-3">Nama Kelompok Sasaran</th>
                                                <th class="p-3">Kategori</th>
                                                <th class="p-3 text-center">Porsi Kecil (PK)</th>
                                                <th class="p-3 text-center">Porsi Besar (PB)</th>
                                                <th class="p-3 text-right">Total PM</th>
                                                <th class="p-3">Status Alergi</th>
                                                <th class="p-3 text-center">Aksi Distribusi</th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-slate-100 text-slate-800"
                                        >
                                            <tr
                                                v-for="k in woKelompokList"
                                                :key="k.id"
                                                :class="[
                                                    'transition-colors',
                                                    k.status_menerima === false
                                                        ? 'bg-rose-50/40 text-slate-400'
                                                        : 'hover:bg-slate-50/60'
                                                ]"
                                            >
                                                <!-- Status Badge -->
                                                <td class="p-3 text-center align-middle">
                                                    <span
                                                        v-if="k.status_menerima !== false"
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"
                                                    >
                                                        <UserCheck class="h-3 w-3 mr-1" />
                                                        Menerima
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300"
                                                    >
                                                        <UserX class="h-3 w-3 mr-1" />
                                                        Tidak Menerima
                                                    </span>
                                                </td>

                                                <!-- Nama Kelompok -->
                                                <td class="p-3 font-bold align-middle" :class="k.status_menerima === false ? 'text-slate-500 line-through' : 'text-slate-900'">
                                                    {{ k.nama_kelompok }}
                                                    <span
                                                        class="block text-[10px] text-slate-400 font-normal no-underline"
                                                    >
                                                        {{ k.desa_kelurahan }},
                                                        {{ k.kecamatan }}
                                                    </span>
                                                </td>

                                                <!-- Kategori -->
                                                <td class="p-3 align-middle">
                                                    <Badge
                                                        variant="outline"
                                                        class="font-bold text-[11px] bg-slate-50"
                                                    >
                                                        {{ k.kategori }}
                                                    </Badge>
                                                </td>

                                                <!-- Porsi Kecil -->
                                                <td
                                                    class="p-3 text-center align-middle font-bold"
                                                    :class="k.status_menerima === false ? 'text-slate-400' : 'text-amber-900 bg-amber-50/20'"
                                                >
                                                    {{ k.total_porsi_kecil }} Porsi
                                                </td>

                                                <!-- Porsi Besar -->
                                                <td
                                                    class="p-3 text-center align-middle font-bold"
                                                    :class="k.status_menerima === false ? 'text-slate-400' : 'text-indigo-900 bg-indigo-50/20'"
                                                >
                                                    {{ k.total_porsi_besar }} Porsi
                                                </td>

                                                <!-- Total PM -->
                                                <td
                                                    class="p-3 text-right font-black text-sm align-middle"
                                                    :class="k.status_menerima === false ? 'text-slate-400' : 'text-slate-900'"
                                                >
                                                    {{ k.total_penerima }} Siswa
                                                </td>

                                                <!-- Alergi (Detail Breakdown per Jenis) -->
                                                <td class="p-3 align-middle">
                                                    <div
                                                        v-if="
                                                            k.keterangan_alergi &&
                                                            k.keterangan_alergi.length > 0
                                                        "
                                                        class="space-y-1"
                                                    >
                                                        <div
                                                            v-for="(al, alIdx) in k.keterangan_alergi"
                                                            :key="alIdx"
                                                            class="text-[11px] font-bold"
                                                            :class="k.status_menerima === false ? 'text-slate-400' : 'text-rose-700'"
                                                        >
                                                            ⚠️ {{ al.jenis_alergi }}:
                                                            <span class="font-black text-rose-900 ml-0.5">
                                                                {{ (Number(al.porsi_kecil) || 0) + (Number(al.porsi_besar) || 0) }} Siswa
                                                            </span>
                                                            <span class="text-[10px] text-slate-500 font-normal ml-1">
                                                                (PK: {{ al.porsi_kecil || 0 }}, PB: {{ al.porsi_besar || 0 }})
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-else-if="
                                                            (k.alergi_porsi_kecil || 0) +
                                                                (k.alergi_porsi_besar || 0) >
                                                            0
                                                        "
                                                        class="text-[11px] font-bold"
                                                        :class="k.status_menerima === false ? 'text-slate-400' : 'text-rose-700'"
                                                    >
                                                        ⚠️ {{ (k.alergi_porsi_kecil || 0) + (k.alergi_porsi_besar || 0) }} Alergi
                                                        <span class="block text-[10px] text-slate-500 font-normal">
                                                            PK: {{ k.alergi_porsi_kecil || 0 }} • PB: {{ k.alergi_porsi_besar || 0 }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="text-[11px] text-emerald-700 font-medium"
                                                    >
                                                        ✓ Normal
                                                    </div>
                                                </td>

                                                <!-- Aksi -->
                                                <td class="p-2 text-center align-middle">
                                                    <div class="flex items-center justify-center gap-1.5">
                                                        <button
                                                            type="button"
                                                            @click="handleOpenModalEditPm(k)"
                                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors flex items-center gap-1 cursor-pointer"
                                                            title="Edit Rincian PM per Sub-Sub Kategori"
                                                        >
                                                            <Edit3 class="h-3.5 w-3.5" />
                                                            <span>Edit Detail PM</span>
                                                        </button>

                                                        <button
                                                            v-if="k.status_menerima !== false"
                                                            type="button"
                                                            @click="handleToggleStatusMenerima(k)"
                                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors flex items-center gap-1 cursor-pointer"
                                                            title="Nyatakan kelompok ini tidak menerima distribusi pada Work Order ini"
                                                        >
                                                            <UserX class="h-3.5 w-3.5" />
                                                            <span>Tidak Menerima</span>
                                                        </button>

                                                        <button
                                                            v-else
                                                            type="button"
                                                            @click="handleToggleStatusMenerima(k)"
                                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors flex items-center gap-1 cursor-pointer"
                                                            title="Aktifkan kembali kelompok ini untuk menerima distribusi"
                                                        >
                                                            <UserCheck class="h-3.5 w-3.5" />
                                                            <span>Aktifkan Menerima</span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Bottom Action Button -->
                            <div
                                class="pt-4 border-t border-slate-200 flex items-center justify-between"
                            >
                                <div class="text-xs text-slate-500">
                                    Pastikan tanggal, nama menu, dan status penerima sasaran sudah sesuai sebelum melanjutkan.
                                </div>
                                <Button
                                    type="button"
                                    @click="handleMulaiFormulasiWo"
                                    className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-6 h-11 flex items-center gap-2 rounded-xl shadow-xs cursor-pointer"
                                >
                                    <span>Lanjut ke Formulasi Menu & Pre-Order (Step 2)</span>
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Modal Edit Detail Penerima Manfaat per Sub-Sub Kategori -->
                    <Modal
                        :show="showModalEditPm"
                        @close="showModalEditPm = false"
                        maxWidth="3xl"
                    >
                        <div class="p-5 sm:p-6 space-y-5">
                            <!-- Modal Header -->
                            <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <School class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3 class="text-base font-black text-slate-900">
                                            Edit Rincian PM: {{ editingKelompok?.nama_kelompok }}
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Kategori: <strong class="text-slate-800">{{ editingKelompok?.kategori }}</strong> • Wilayah: {{ editingKelompok?.desa_kelurahan }}, {{ editingKelompok?.kecamatan }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="showModalEditPm = false"
                                    class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                                >
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- Modal Body: Tabel Sub-Sub Kategori -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Rincian Jumlah Siswa / Penerima per Jenjang:
                                    </h4>
                                    <span class="text-xs text-slate-500">
                                        Format input: Laki-laki (L) + Perempuan (P)
                                    </span>
                                </div>

                                <div class="rounded-xl border border-slate-200 overflow-hidden max-h-60 overflow-y-auto">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px] sticky top-0 z-10 shadow-2xs">
                                            <tr>
                                                <th class="p-3">Sub-Kategori / Jenjang</th>
                                                <th class="p-3">Peruntukan Porsi</th>
                                                <th class="p-3 text-center min-w-[100px]">Laki-laki (L)</th>
                                                <th class="p-3 text-center min-w-[100px]">Perempuan (P)</th>
                                                <th class="p-3 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 text-slate-800">
                                            <tr
                                                v-for="(r, rIdx) in editFormRincian"
                                                :key="r.sub_kategori || rIdx"
                                                class="hover:bg-slate-50/60"
                                            >
                                                <td class="p-3 font-bold text-slate-900">
                                                    {{ r.sub_kategori }}
                                                </td>
                                                <td class="p-3">
                                                    <Badge
                                                        variant="outline"
                                                        :class="[
                                                            'font-extrabold text-[10px]',
                                                            r.jenis_porsi === 'Porsi Kecil'
                                                                ? 'bg-amber-50 text-amber-800 border-amber-300'
                                                                : 'bg-indigo-50 text-indigo-800 border-indigo-300'
                                                        ]"
                                                    >
                                                        {{ r.jenis_porsi }}
                                                    </Badge>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        v-model.number="r.jumlah_laki_laki"
                                                        class="w-20 text-center text-xs font-bold rounded-lg border-slate-300 p-1.5 focus:ring-primary focus:border-primary"
                                                    />
                                                </td>
                                                <td class="p-2 text-center">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        v-model.number="r.jumlah_perempuan"
                                                        class="w-20 text-center text-xs font-bold rounded-lg border-slate-300 p-1.5 focus:ring-primary focus:border-primary"
                                                    />
                                                </td>
                                                <td class="p-3 text-right font-black text-slate-900">
                                                    {{ (Number(r.jumlah_laki_laki) || 0) + (Number(r.jumlah_perempuan) || 0) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Input Khusus Kuota Siswa Alergi (Jenis Alergen Bersumber dari Master Data PM) -->
                                <div class="p-4 rounded-xl bg-rose-50/60 border border-rose-200/80 space-y-3">
                                    <div>
                                        <h5 class="text-xs font-bold text-rose-900 flex items-center gap-1.5">
                                            <AlertCircle class="h-4 w-4 text-rose-600" />
                                            <span>Penyesuaian Jumlah Siswa Alergi (Membutuhkan Menu Substitusi)</span>
                                        </h5>
                                        <p class="text-[11px] text-rose-700 mt-0.5">
                                            Daftar jenis alergen bersumber dari master data <strong>Penerima Manfaat</strong>. Anda dapat menyesuaikan jumlah kuota porsi (PK / PB) untuk Work Order ini jika ada perubahan kehadiran.
                                        </p>
                                    </div>

                                    <!-- Tabel Daftar Alergi Terdaftar -->
                                    <div
                                        v-if="editFormKeteranganAlergi.length > 0"
                                        class="rounded-lg border border-rose-200 bg-white overflow-hidden"
                                    >
                                        <table class="w-full text-left text-xs border-collapse">
                                            <thead class="bg-rose-100/60 text-rose-900 font-bold border-b border-rose-200 uppercase text-[10px]">
                                                <tr>
                                                    <th class="p-3">Jenis Alergen (Master PM)</th>
                                                    <th class="p-3 text-center min-w-[110px]">Porsi Kecil (PK)</th>
                                                    <th class="p-3 text-center min-w-[110px]">Porsi Besar (PB)</th>
                                                    <th class="p-3 text-right">Subtotal Alergi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-rose-100 text-slate-800">
                                                <tr
                                                    v-for="(alItem, alIdx) in editFormKeteranganAlergi"
                                                    :key="alIdx"
                                                    class="hover:bg-rose-50/40"
                                                >
                                                    <td class="p-3 font-bold text-slate-900 align-middle">
                                                        <div class="flex items-center gap-2">
                                                            <span class="h-2 w-2 rounded-full bg-rose-500 shrink-0"></span>
                                                            <span class="text-xs font-black text-rose-950">{{ alItem.jenis_alergi }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="p-2 text-center align-middle">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            :max="modalTotalPk"
                                                            v-model.number="alItem.porsi_kecil"
                                                            class="w-20 text-center text-xs font-bold rounded-lg border-rose-300 bg-rose-50/30 p-1.5 focus:ring-rose-400 focus:border-rose-400"
                                                        />
                                                    </td>
                                                    <td class="p-2 text-center align-middle">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            :max="modalTotalPb"
                                                            v-model.number="alItem.porsi_besar"
                                                            class="w-20 text-center text-xs font-bold rounded-lg border-rose-300 bg-rose-50/30 p-1.5 focus:ring-rose-400 focus:border-rose-400"
                                                        />
                                                    </td>
                                                    <td class="p-3 text-right font-black text-rose-900 text-xs align-middle">
                                                        {{ (Number(alItem.porsi_kecil) || 0) + (Number(alItem.porsi_besar) || 0) }} Siswa
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div
                                        v-else
                                        class="p-4 text-center text-slate-500 text-xs bg-white rounded-xl border border-dashed border-rose-200 space-y-1"
                                    >
                                        <p class="font-bold text-slate-700">Tidak ada riwayat alergi yang terdaftar untuk kelompok sasaran ini.</p>
                                        <p class="text-[11px] text-slate-500">
                                            Penambahan atau pengelolaan jenis alergen dilakukan melalui master data <strong class="text-slate-800">Penerima Manfaat</strong>.
                                        </p>
                                    </div>
                                </div>

                                <!-- Live Summary Bar -->
                                <div class="p-3.5 rounded-xl bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 text-xs">
                                    <div>
                                        <span class="text-slate-400">Hasil Rekapitulasi: </span>
                                        <strong class="text-white ml-1">Total {{ modalTotalPm }} PM</strong>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-amber-300 font-bold">PK: {{ modalTotalPk }} Porsi</span>
                                        <span class="text-indigo-300 font-bold">PB: {{ modalTotalPb }} Porsi</span>
                                        <span class="text-rose-300 font-bold">Alergi: {{ modalGrandTotalAlergi }} Siswa (PK: {{ modalTotalAlergiPk }}, PB: {{ modalTotalAlergiPb }})</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="showModalEditPm = false"
                                    className="text-xs font-bold cursor-pointer"
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="button"
                                    @click="handleSimpanEditDetailPm"
                                    className="bg-primary hover:bg-primary/90 text-white text-xs font-bold flex items-center gap-1.5 cursor-pointer shadow-xs"
                                >
                                    <Check class="h-4 w-4" />
                                    <span>Simpan Perubahan</span>
                                </Button>
                            </div>
                        </div>
                    </Modal>
                </div>

                <!-- ========================================================================================= -->
                <!-- Bagian 2: Formulasi Resep & Pre-Order (Step 2) -->
                <!-- ========================================================================================= -->
                <div v-if="buatMenuSubTab === 'pre_order'" class="space-y-6">
                    <!-- Header Pre-Order Card -->
                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardHeader
                            className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                        >
                            <div
                                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                            >
                                <div>
                                    <CardTitle
                                        class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                                    >
                                        <ClipboardList
                                            class="h-5 w-5 text-primary"
                                        />
                                        <span
                                            >Formulir Pre Order & Formula
                                            Gramasi Bahan (Ahli Gizi)</span
                                        >
                                    </CardTitle>
                                    <CardDescription class="text-xs sm:text-sm">
                                        Penentuan gramasi bahan makanan per porsi, konversi ke kebutuhan kotor (BDD & Buffer %), serta pengajuan Draft PO ke Akuntan.
                                    </CardDescription>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        @click="handleAjukanDraftPo"
                                        className="bg-primary text-white hover:bg-primary/90 text-xs font-bold px-4 h-9 flex items-center gap-1.5 shadow-xs cursor-pointer"
                                    >
                                        <Send class="h-3.5 w-3.5" />
                                        <span>Ajukan Draft PO ke Akuntan</span>
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 sm:p-5 space-y-4">

                            <!-- ========================================================================= -->
                            <!-- BANNER ANALISA & REKOMENDASI ALERGI BERDASARKAN NAMA MENU & DETAIL PM -->
                            <!-- ========================================================================= -->
                            <!-- Case 1: Terdeteksi Alergen pada Judul Menu yang bentrok dengan PM Alergi (Warning Amber/Rose) -->
                            <div
                                v-if="analisaAlergiMenu.hasConflicts"
                                class="p-4 sm:p-5 rounded-2xl bg-amber-50/90 border-2 border-amber-300 shadow-xs space-y-3.5 animate-in fade-in"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                            <AlertTriangle class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="text-sm font-black text-amber-950">
                                                    Peringatan Alergen Menu: Terdeteksi Bahan yang Berpotensi Alergi
                                                </h4>
                                                <Badge class="bg-rose-600 text-white font-extrabold text-[10px] px-2 py-0.5">
                                                    {{ analisaAlergiMenu.conflicts.length }} Alergen Teridentifikasi
                                                </Badge>
                                            </div>
                                            <p class="text-xs text-amber-800 mt-0.5">
                                                Nama menu <em>"{{ namaMenuAktif }}"</em> mengandung bahan yang cocok dengan data riwayat alergi siswa/penerima aktif hari ini.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grid Kartu Benturan Alergen & Rekomendasi Substitusi -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                                    <div
                                        v-for="(cf, cfIdx) in analisaAlergiMenu.conflicts"
                                        :key="cfIdx"
                                        class="p-3.5 rounded-xl bg-white border border-amber-200/80 shadow-2xs space-y-2"
                                    >
                                        <div class="flex items-center justify-between gap-2 border-b border-amber-100 pb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[11px] font-black bg-rose-100 text-rose-800 border border-rose-200">
                                                    {{ cf.jenis_alergi }}
                                                </span>
                                                <span class="text-[11px] text-slate-500">
                                                    (Kata kunci menu: <strong>"{{ cf.keyword }}"</strong>)
                                                </span>
                                            </div>
                                            <span class="text-xs font-black text-rose-900 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200">
                                                {{ cf.total }} Siswa Kena
                                            </span>
                                        </div>

                                        <div class="text-xs space-y-1">
                                            <div class="flex items-center justify-between text-slate-600">
                                                <span>Porsi Pengganti Diperlukan:</span>
                                                <strong class="text-slate-900">PK: {{ cf.porsi_kecil }} • PB: {{ cf.porsi_besar }}</strong>
                                            </div>
                                            <div class="text-[11px] text-slate-500">
                                                Kelompok terdampak: {{ cf.kelompok_names.join(', ') || '-' }}
                                            </div>
                                        </div>

                                        <!-- Rekomendasi Ahli Gizi -->
                                        <div class="p-2.5 rounded-lg bg-emerald-50/80 border border-emerald-200 text-emerald-950 flex items-start gap-2 text-xs">
                                            <Lightbulb class="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" />
                                            <div>
                                                <strong class="text-emerald-900 font-bold block text-[11px]">Rekomendasi Bahan Pengganti:</strong>
                                                <span class="text-[11px] text-emerald-800 font-medium leading-relaxed">
                                                    Tambahkan formulasi bahan pangan <em>{{ cf.rekomendasi }}</em> dengan peruntukan porsi <strong>"Alergi: {{ cf.jenis_alergi }}"</strong> untuk {{ cf.total }} porsi.
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Case 2: Ada Siswa Alergi Aktif tapi tidak ada kata kunci bentrok langsung di judul menu (Info Pengingat) -->
                            <div
                                v-else-if="analisaAlergiMenu.totalSiswaAlergi > 0"
                                class="p-4 rounded-2xl bg-sky-50/80 border border-sky-200 shadow-xs space-y-3"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0">
                                            <Info class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h4 class="text-xs sm:text-sm font-bold text-sky-950 flex items-center gap-2">
                                                <span>Pengingat Rekapitulasi Alergi PM Terjadwal ({{ analisaAlergiMenu.totalSiswaAlergi }} Siswa)</span>
                                            </h4>
                                            <p class="text-xs text-sky-800 mt-0.5">
                                                Meskipun nama menu tidak menyebutkan alergen langsung, terdapat siswa aktif dengan riwayat alergi khusus. Pastikan bahan dan bumbu aman dari kontaminasi silang.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2 pt-1">
                                    <div
                                        v-for="(al, alIdx) in analisaAlergiMenu.activeAlergiList"
                                        :key="alIdx"
                                        class="px-3 py-1.5 rounded-xl bg-white border border-sky-200 text-xs flex items-center gap-2 shadow-2xs"
                                    >
                                        <span class="font-bold text-sky-950">{{ al.jenis_alergi }}:</span>
                                        <span class="font-black text-rose-700">{{ al.total }} Siswa</span>
                                        <span class="text-[10px] text-slate-500">(PK: {{ al.porsi_kecil }}, PB: {{ al.porsi_besar }})</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Case 3: Zero Alergi Terdaftar pada Kelompok Aktif (Green Safe) -->
                            <div
                                v-else
                                class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center gap-3 text-xs text-emerald-900"
                            >
                                <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0" />
                                <div>
                                    <strong class="font-bold">Status Alergen Aman:</strong> Seluruh {{ totalPM }} penerima manfaat pada kelompok aktif hari ini tidak memiliki catatan alergi khusus. Semua porsi dapat disiapkan dengan formulasi menu standar.
                                </div>
                            </div>

                            <!-- Selector Tambah Bahan dari TKPI 2020 (Lebar & Lengkap) -->
                            <div
                                class="p-4 rounded-2xl bg-blue-50/70 border border-blue-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs"
                                    >
                                        <Sparkles class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-sm font-extrabold text-blue-950"
                                        >
                                            Database Standar Pangan TKPI 2020
                                        </p>
                                        <p class="text-xs text-blue-700 mt-0.5">
                                            Pilih dari
                                            {{ tkpiItems.length }} data
                                            komposisi pangan resmi Kemenkes RI
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-2.5 flex-1 lg:max-w-2xl w-full"
                                >
                                    <!-- Searchable Combobox Selector (Lebar & Nyaman) -->
                                    <div
                                        class="relative flex-1 w-full"
                                        ref="comboboxRef"
                                    >
                                        <!-- Trigger Button -->
                                        <button
                                            type="button"
                                            @click="
                                                isTkpiDropdownOpen =
                                                    !isTkpiDropdownOpen
                                            "
                                            class="w-full h-11 px-4 py-2 text-sm font-semibold rounded-xl border border-slate-300 bg-white text-slate-900 flex items-center justify-between shadow-2xs hover:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-left transition-colors cursor-pointer"
                                            :class="{
                                                'ring-2 ring-primary/20 border-primary':
                                                    isTkpiDropdownOpen,
                                            }"
                                        >
                                            <span
                                                v-if="selectedTkpiItem"
                                                class="truncate font-bold text-slate-900 flex items-center gap-2 min-w-0"
                                            >
                                                <span
                                                    class="px-2 py-0.5 text-xs rounded-md bg-blue-100 text-blue-800 font-bold shrink-0"
                                                >
                                                    {{
                                                        selectedTkpiItem.kategori
                                                    }}
                                                </span>
                                                <span
                                                    class="font-mono text-xs text-primary font-bold"
                                                    >[{{
                                                        selectedTkpiItem.id
                                                    }}]</span
                                                >
                                                <span
                                                    class="truncate text-sm"
                                                    >{{
                                                        selectedTkpiItem.nama
                                                    }}</span
                                                >
                                            </span>
                                            <span
                                                v-else
                                                class="text-slate-400 font-medium text-sm truncate"
                                            >
                                                -- Cari & Pilih Bahan Makanan
                                                TKPI 2020 --
                                            </span>
                                            <ChevronDown
                                                class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 ml-2"
                                                :class="{
                                                    'rotate-180 text-primary':
                                                        isTkpiDropdownOpen,
                                                }"
                                            />
                                        </button>

                                        <!-- Dropdown Panel (Search + Scroll List) -->
                                        <div
                                            v-if="isTkpiDropdownOpen"
                                            class="absolute left-0 right-0 top-full mt-2 z-50 bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden w-full animate-in fade-in zoom-in-95 duration-100"
                                        >
                                            <!-- Search Input Box -->
                                            <div
                                                class="p-3 border-b border-slate-100 bg-slate-50/90 flex items-center gap-2.5"
                                            >
                                                <Search
                                                    class="h-4 w-4 text-slate-400 shrink-0 ml-1"
                                                />
                                                <input
                                                    type="text"
                                                    v-model="tkpiSearchText"
                                                    placeholder="Ketik nama bahan makanan (contoh: beras, ayam, telur, tempe, wortel)..."
                                                    class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-800 placeholder:text-slate-400"
                                                    autofocus
                                                    @click.stop
                                                />
                                                <button
                                                    v-if="tkpiSearchText"
                                                    type="button"
                                                    @click.stop="
                                                        tkpiSearchText = ''
                                                    "
                                                    class="text-slate-400 hover:text-slate-600 text-sm px-2 py-1 rounded cursor-pointer font-bold"
                                                >
                                                    ✕
                                                </button>
                                            </div>

                                            <!-- Scrollable List Options (Lengkap: Energi, Protein, Lemak, Karbo, Serat) -->
                                            <div
                                                class="max-h-80 overflow-y-auto divide-y divide-slate-100 p-1.5"
                                            >
                                                <div
                                                    v-if="
                                                        filteredComboboxTkpiList.length ===
                                                        0
                                                    "
                                                    class="p-6 text-center text-slate-400 text-sm font-medium"
                                                >
                                                    Tidak ada bahan makanan yang
                                                    cocok dengan "<strong
                                                        class="text-slate-700"
                                                        >{{
                                                            tkpiSearchText
                                                        }}</strong
                                                    >"
                                                </div>

                                                <button
                                                    v-for="item in filteredComboboxTkpiList"
                                                    :key="item.id"
                                                    type="button"
                                                    @click.stop="
                                                        selectTkpiItem(item)
                                                    "
                                                    :class="[
                                                        'w-full text-left p-3 rounded-xl transition-all flex items-start justify-between gap-3 cursor-pointer',
                                                        selectedTkpiOption ===
                                                        item.id
                                                            ? 'bg-blue-50/90 text-primary border border-blue-200 shadow-2xs font-bold'
                                                            : 'hover:bg-slate-50 text-slate-800 border border-transparent',
                                                    ]"
                                                >
                                                    <div
                                                        class="min-w-0 flex-1 space-y-1.5"
                                                    >
                                                        <div
                                                            class="flex items-center gap-2 flex-wrap"
                                                        >
                                                            <span
                                                                class="px-2 py-0.5 text-[11px] rounded-md bg-slate-100 text-slate-700 font-bold"
                                                            >
                                                                {{
                                                                    item.kategori
                                                                }}
                                                            </span>
                                                            <span
                                                                class="px-1.5 py-0.5 text-[11px] rounded bg-blue-50 text-primary font-mono font-bold"
                                                            >
                                                                [{{ item.id }}]
                                                            </span>
                                                            <span
                                                                class="font-extrabold text-slate-900 text-sm"
                                                            >
                                                                {{ item.nama }}
                                                            </span>
                                                        </div>

                                                        <!-- Rincian Lengkap: Energi, Protein, Lemak, Karbo, Serat, BDD & Harga -->
                                                        <div
                                                            class="flex items-center gap-2.5 flex-wrap text-xs text-slate-600 pt-0.5"
                                                        >
                                                            <span
                                                                class="inline-flex items-center gap-1 font-semibold bg-amber-50 text-amber-900 px-2 py-0.5 rounded-md border border-amber-100"
                                                            >
                                                                ⚡
                                                                {{
                                                                    item.energi
                                                                }}
                                                                kkal
                                                            </span>
                                                            <span
                                                                class="inline-flex items-center gap-1 font-semibold bg-rose-50 text-rose-900 px-2 py-0.5 rounded-md border border-rose-100"
                                                            >
                                                                🥩 Prot:
                                                                {{
                                                                    item.protein
                                                                }}g
                                                            </span>
                                                            <span
                                                                class="inline-flex items-center gap-1 font-semibold bg-orange-50 text-orange-900 px-2 py-0.5 rounded-md border border-orange-100"
                                                            >
                                                                🧈 Lemak:
                                                                {{
                                                                    item.lemak
                                                                }}g
                                                            </span>
                                                            <span
                                                                class="inline-flex items-center gap-1 font-semibold bg-emerald-50 text-emerald-900 px-2 py-0.5 rounded-md border border-emerald-100"
                                                            >
                                                                🍚 Karbo:
                                                                {{
                                                                    item.karbohidrat
                                                                }}g
                                                            </span>
                                                            <span
                                                                class="inline-flex items-center gap-1 font-semibold bg-teal-50 text-teal-900 px-2 py-0.5 rounded-md border border-teal-100"
                                                            >
                                                                🥦 Serat:
                                                                {{
                                                                    item.serat
                                                                }}g
                                                            </span>
                                                            <span
                                                                class="inline-flex items-center gap-1 font-semibold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-md"
                                                            >
                                                                🏷️ BDD:
                                                                {{ item.bdd }}%
                                                            </span>
                                                            <span
                                                                class="inline-flex items-center gap-1 font-bold text-slate-800"
                                                            >
                                                                💰
                                                                {{
                                                                    formatRupiah(
                                                                        item.harga_master,
                                                                    )
                                                                }}/kg
                                                            </span>
                                                            <span
                                                                v-if="
                                                                    item.alergen
                                                                "
                                                                class="inline-flex items-center gap-1 font-black bg-rose-100 text-rose-800 px-2 py-0.5 rounded-md border border-rose-200"
                                                            >
                                                                ⚠️ Alergen:
                                                                {{
                                                                    item.alergen
                                                                }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <Check
                                                        v-if="
                                                            selectedTkpiOption ===
                                                            item.id
                                                        "
                                                        class="h-5 w-5 text-primary shrink-0 mt-1"
                                                    />
                                                </button>
                                            </div>

                                            <!-- Footer Info -->
                                            <div
                                                class="p-3 border-t border-slate-100 bg-slate-50 text-xs text-slate-600 flex items-center justify-between"
                                            >
                                                <span
                                                    >Menampilkan
                                                    <strong>{{
                                                        filteredComboboxTkpiList.length
                                                    }}</strong>
                                                    dari
                                                    <strong>{{
                                                        tkpiItems.length
                                                    }}</strong>
                                                    bahan resmi TKPI 2020</span
                                                >
                                                <span
                                                    class="text-[11px] text-slate-400"
                                                    >Bisa di-scroll & diketik
                                                    langsung</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        @click="handleAddBahan"
                                        :disabled="!selectedTkpiOption"
                                        className="h-11 px-5 text-sm bg-blue-600 hover:bg-blue-700 text-white font-extrabold cursor-pointer shrink-0 rounded-xl shadow-xs"
                                    >
                                        <Plus class="h-4 w-4 mr-1.5" /> Tambah
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tabel Detail Perhitungan Gramasi & Draft PO -->
                    <Card
                        className="bg-white border-slate-200 shadow-xs overflow-hidden"
                    >
                        <div class="overflow-x-auto">
                            <table
                                class="w-full text-left text-xs border-collapse"
                            >
                                <thead
                                    class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3">Bahan (TKPI 2020)</th>
                                        <th class="p-3">Kategori</th>
                                        <th
                                            class="p-3 text-center min-w-[155px]"
                                        >
                                            Peruntukan Porsi
                                        </th>
                                        <th class="p-3 text-center">Gram PK</th>
                                        <th class="p-3 text-center">Gram PB</th>
                                        <th class="p-3 text-center">BDD (%)</th>
                                        <th class="p-3 text-center">
                                            Buffer (%)
                                        </th>
                                        <th class="p-3 text-right">
                                            Kebutuhan Kotor (Kg)
                                        </th>
                                        <th class="p-3 text-right">
                                            Harga Master (Awal)
                                        </th>
                                        <th class="p-3 text-right">
                                            Subtotal PO Draft
                                        </th>
                                        <th class="p-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 text-slate-800"
                                >
                                    <tr v-if="bahanCalculations.length === 0">
                                        <td
                                            colspan="11"
                                            class="p-10 text-center text-slate-400"
                                        >
                                            <div
                                                class="flex flex-col items-center justify-center gap-2.5"
                                            >
                                                <div
                                                    class="h-12 w-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400"
                                                >
                                                    <Utensils class="h-6 w-6" />
                                                </div>
                                                <p
                                                    class="font-extrabold text-slate-700 text-sm"
                                                >
                                                    Belum Ada Bahan Makanan yang
                                                    Ditambahkan
                                                </p>
                                                <p
                                                    class="text-xs text-slate-400 max-w-md"
                                                >
                                                    Silakan cari & pilih bahan
                                                    makanan resmi dari kotak
                                                    pencarian
                                                    <strong
                                                        >Standar Pangan TKPI
                                                        2020</strong
                                                    >
                                                    di atas, lalu klik
                                                    <strong>+ Tambah</strong>
                                                    untuk mulai meracik gramasi
                                                    resep.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(b, idx) in bahanCalculations"
                                        :key="idx"
                                        class="hover:bg-slate-50/70 transition-colors"
                                    >
                                        <td
                                            class="p-3 font-bold text-slate-900 align-middle"
                                        >
                                            {{ b.nama }}
                                            <span
                                                v-if="b.alergen"
                                                class="block text-[9.5px] text-amber-700 font-normal mt-0.5"
                                                >Alergen: {{ b.alergen }}</span
                                            >
                                        </td>
                                        <td
                                            class="p-3 text-slate-600 align-middle"
                                        >
                                            {{ b.kategori }}
                                        </td>

                                        <!-- Kolom Peruntukan Porsi (Normal / Alergi) -->
                                        <td
                                            class="p-2 text-center align-middle min-w-[160px]"
                                        >
                                            <div
                                                class="flex flex-col gap-1 items-center"
                                            >
                                                <select
                                                    v-model="
                                                        selectedBahanList[idx]
                                                            .tipe_porsi
                                                    "
                                                    class="w-full text-xs font-bold rounded-lg border p-1.5 transition-colors cursor-pointer"
                                                    :class="
                                                        selectedBahanList[idx]
                                                            .tipe_porsi ===
                                                        'alergi'
                                                            ? 'border-rose-300 bg-rose-50 text-rose-800'
                                                            : 'border-slate-300 bg-white text-slate-700'
                                                    "
                                                >
                                                    <option value="normal">
                                                        Normal
                                                    </option>
                                                    <option value="alergi">
                                                        Alergi
                                                    </option>
                                                </select>
                                                <div
                                                    v-if="
                                                        selectedBahanList[idx]
                                                            .tipe_porsi ===
                                                        'alergi'
                                                    "
                                                    class="w-full"
                                                >
                                                    <select
                                                        v-model="
                                                            selectedBahanList[
                                                                idx
                                                            ].jenis_alergi
                                                        "
                                                        required
                                                        class="w-full text-[11px] font-semibold rounded-md border border-rose-300 bg-rose-50/90 p-1.5 text-rose-900 focus:ring-rose-400 focus:border-rose-400 cursor-pointer"
                                                    >
                                                        <option
                                                            value=""
                                                            disabled
                                                        >
                                                            -- Pilih Alergi --
                                                        </option>
                                                        <option
                                                            v-for="opt in ALERGI_OPTIONS"
                                                            :key="opt.value"
                                                            :value="opt.value"
                                                        >
                                                            {{ opt.label }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Input Gram PK -->
                                        <td
                                            class="p-2 text-center align-middle"
                                        >
                                            <input
                                                type="number"
                                                v-model.number="
                                                    selectedBahanList[idx]
                                                        .gram_pk
                                                "
                                                required
                                                placeholder="0"
                                                class="w-16 h-8 text-center text-xs font-bold rounded-lg border-slate-300 p-1 bg-amber-50/40 text-amber-900 focus:ring-primary focus:border-primary"
                                                min="0"
                                            />
                                        </td>

                                        <!-- Input Gram PB -->
                                        <td
                                            class="p-2 text-center align-middle"
                                        >
                                            <input
                                                type="number"
                                                v-model.number="
                                                    selectedBahanList[idx]
                                                        .gram_pb
                                                "
                                                required
                                                placeholder="0"
                                                class="w-16 h-8 text-center text-xs font-bold rounded-lg border-slate-300 p-1 bg-indigo-50/40 text-indigo-900 focus:ring-primary focus:border-primary"
                                                min="0"
                                            />
                                        </td>

                                        <!-- BDD (Otomatis dari TKPI & Terkunci) -->
                                        <td
                                            class="p-2 text-center align-middle"
                                        >
                                            <input
                                                type="number"
                                                v-model.number="
                                                    selectedBahanList[idx].bdd
                                                "
                                                disabled
                                                title="BDD (%) terisi otomatis sesuai standar database resmi TKPI 2020"
                                                class="w-14 h-8 text-center text-xs font-black rounded-lg border-slate-200 p-1 bg-slate-100/90 text-slate-700 cursor-not-allowed shadow-none"
                                            />
                                        </td>

                                        <!-- Buffer % -->
                                        <td
                                            class="p-2 text-center align-middle"
                                        >
                                            <input
                                                type="number"
                                                v-model.number="
                                                    selectedBahanList[idx]
                                                        .buffer
                                                "
                                                required
                                                placeholder="0"
                                                class="w-14 h-8 text-center text-xs font-bold rounded-lg border-slate-300 p-1 text-rose-800 bg-white focus:ring-primary focus:border-primary"
                                                min="0"
                                            />
                                        </td>

                                        <!-- Total Kg Kotor -->
                                        <td
                                            class="p-3 text-right font-black text-slate-900 bg-slate-50/50 align-middle"
                                        >
                                            {{ b.totalGrossKg }} kg
                                        </td>

                                        <!-- Harga Master -->
                                        <td
                                            class="p-3 text-right text-slate-700 font-semibold align-middle"
                                        >
                                            {{
                                                formatRupiah(b.harga_master)
                                            }}
                                            /kg
                                        </td>

                                        <!-- Subtotal Draft -->
                                        <td
                                            class="p-3 text-right font-bold text-blue-900 align-middle"
                                        >
                                            {{ formatRupiah(b.subtotalMaster) }}
                                        </td>

                                        <td
                                            class="p-2 text-center align-middle"
                                        >
                                            <button
                                                type="button"
                                                @click="handleRemoveBahan(idx)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                                title="Hapus Bahan"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="bg-slate-100/90 font-black text-slate-900 border-t-2 border-slate-300 text-xs"
                                >
                                    <tr>
                                        <td
                                            colspan="7"
                                            class="p-3.5 uppercase tracking-wider text-slate-700"
                                        >
                                            Total Estimasi Kebutuhan Belanja
                                            Draft PO (Harga Master):
                                        </td>
                                        <td
                                            class="p-3.5 text-right text-slate-900"
                                        >
                                            {{
                                                bahanCalculations
                                                    .reduce(
                                                        (acc, i) =>
                                                            acc +
                                                            i.totalGrossKg,
                                                        0,
                                                    )
                                                    .toFixed(1)
                                            }}
                                            Kg
                                        </td>
                                        <td></td>
                                        <td
                                            class="p-3.5 text-right text-blue-950 text-sm font-black"
                                        >
                                            {{
                                                formatRupiah(
                                                    grandTotalDraftMaster,
                                                )
                                            }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- ========================================================================================= -->
                <!-- Bagian 4: Order & Approval Akuntan -->
                <!-- ========================================================================================= -->
                <div v-if="buatMenuSubTab === 'order'" class="space-y-6">
                    <!-- Status Bar Approval Akuntan -->
                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardHeader
                            className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                        >
                            <div
                                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <CardTitle
                                            class="text-base sm:text-lg font-bold text-slate-900"
                                        >
                                            Review & Validasi Purchase Order (PO
                                            Akuntan)
                                        </CardTitle>
                                        <Badge
                                            v-if="poStatus === 'approved'"
                                            variant="outline"
                                            className="bg-emerald-50 text-emerald-700 border-emerald-300 font-extrabold"
                                        >
                                            DISETUJUI / FINAL
                                        </Badge>
                                        <Badge
                                            v-else-if="poStatus === 'rejected'"
                                            variant="outline"
                                            className="bg-rose-50 text-rose-700 border-rose-300 font-extrabold"
                                        >
                                            DITOLAK / PERLU REVISI
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="outline"
                                            className="bg-amber-50 text-amber-700 border-amber-300 font-extrabold"
                                        >
                                            MENUNGGU APPROVAL (DRAFT)
                                        </Badge>
                                    </div>
                                    <CardDescription
                                        class="text-xs sm:text-sm mt-0.5"
                                    >
                                        No. PO: <strong>{{ poNo }}</strong> •
                                        Tanggal:
                                        <strong>{{ tanggalRencana }}</strong>
                                    </CardDescription>
                                </div>

                                <!-- Action Buttons Akuntan -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <Button
                                        v-if="poStatus === 'approved'"
                                        type="button"
                                        @click="handlePrintPo"
                                        className="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3.5 h-9 flex items-center gap-1.5 cursor-pointer shadow-xs"
                                    >
                                        <Printer class="h-3.5 w-3.5" />
                                        <span>Cetak PO Supplier</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        @click="approvePo"
                                        :disabled="poStatus === 'approved'"
                                        className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 h-9 flex items-center gap-1.5 cursor-pointer shadow-xs"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        <span>Setujui PO (Terima)</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        @click="rejectPo"
                                        :disabled="poStatus === 'rejected'"
                                        className="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-4 h-9 flex items-center gap-1.5 cursor-pointer shadow-xs"
                                    >
                                        <XCircle class="h-3.5 w-3.5" />
                                        <span>Tolak / Revisi</span>
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 sm:p-5 space-y-4">
                            <!-- Catatan Akuntan -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Catatan & Evaluasi Akuntan:</label
                                >
                                <input
                                    type="text"
                                    v-model="poCatatanAkuntan"
                                    placeholder="Masukkan catatan evaluasi harga pasar atau ketersediaan bahan..."
                                    class="w-full text-xs font-medium rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2.5"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tabel Validasi Harga Aktual Supplier -->
                    <Card
                        className="bg-white border-slate-200 shadow-xs overflow-hidden"
                    >
                        <div class="overflow-x-auto">
                            <table
                                class="w-full text-left text-xs border-collapse"
                            >
                                <thead
                                    class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3">Nama Bahan Baku</th>
                                        <th class="p-3">Kategori</th>
                                        <th class="p-3 text-right">
                                            Kebutuhan (Kg)
                                        </th>
                                        <th class="p-3 text-right">
                                            Harga Master (Ahli Gizi)
                                        </th>
                                        <th class="p-3 text-right">
                                            Harga Aktual Pasar (Realisasi)
                                        </th>
                                        <th class="p-3 text-right">
                                            Subtotal Realisasi PO
                                        </th>
                                        <th class="p-3 text-center">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 text-slate-800"
                                >
                                    <tr
                                        v-for="(b, idx) in bahanCalculations"
                                        :key="idx"
                                        class="hover:bg-slate-50/70 transition-colors"
                                    >
                                        <td
                                            class="p-3 font-bold text-slate-900"
                                        >
                                            {{ b.nama }}
                                            <span
                                                v-if="b.tipe_porsi === 'alergi'"
                                                class="block text-[10px] font-bold text-rose-700 mt-0.5"
                                            >
                                                Porsi Khusus:
                                                {{ b.jenis_alergi || "Alergi" }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-slate-600">
                                            {{ b.kategori }}
                                        </td>
                                        <td
                                            class="p-3 text-right font-black text-slate-900"
                                        >
                                            {{ b.totalGrossKg }} kg
                                        </td>
                                        <td
                                            class="p-3 text-right text-slate-500"
                                        >
                                            {{ formatRupiah(b.harga_master) }}
                                        </td>
                                        <!-- Input Harga Aktual Akuntan -->
                                        <td class="p-2 text-right">
                                            <input
                                                type="number"
                                                v-model.number="
                                                    selectedBahanList[idx]
                                                        .harga_aktual
                                                "
                                                class="w-28 text-right text-xs font-bold rounded border-slate-300 p-1.5 text-emerald-900 bg-emerald-50/40"
                                                min="0"
                                                step="500"
                                            />
                                        </td>
                                        <td
                                            class="p-3 text-right font-black text-emerald-900 text-xs"
                                        >
                                            {{ formatRupiah(b.subtotalAktual) }}
                                        </td>
                                        <td
                                            class="p-3 text-center text-[11px] font-bold"
                                            :class="
                                                b.subtotalAktual >
                                                b.subtotalMaster
                                                    ? 'text-rose-600'
                                                    : 'text-emerald-600'
                                            "
                                        >
                                            {{
                                                b.subtotalAktual >
                                                b.subtotalMaster
                                                    ? "+"
                                                    : ""
                                            }}{{
                                                formatRupiah(
                                                    b.subtotalAktual -
                                                        b.subtotalMaster,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="bg-slate-100/90 font-black text-slate-900 border-t-2 border-slate-300 text-xs"
                                >
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="p-3.5 uppercase tracking-wider text-slate-700"
                                        >
                                            Total Nilai Realisasi Purchase Order
                                            (Final):
                                        </td>
                                        <td></td>
                                        <td
                                            class="p-3.5 text-right text-emerald-950 text-sm font-black"
                                        >
                                            {{ formatRupiah(grandTotalAktual) }}
                                        </td>
                                        <td
                                            class="p-3.5 text-center text-xs"
                                            :class="
                                                grandTotalAktual >
                                                grandTotalDraftMaster
                                                    ? 'text-rose-700'
                                                    : 'text-emerald-700'
                                            "
                                        >
                                            {{
                                                grandTotalAktual >
                                                grandTotalDraftMaster
                                                    ? "+"
                                                    : ""
                                            }}{{
                                                formatRupiah(
                                                    grandTotalAktual -
                                                        grandTotalDraftMaster,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- ========================================================================================= -->
                <!-- Bagian 2: Evaluasi Standar AKG BGN -->
                <!-- ========================================================================================= -->
                <div v-if="buatMenuSubTab === 'hasil_akg'" class="space-y-6">
                    <!-- Info Standar BGN Banner -->
                    <div
                        class="p-4 rounded-xl bg-blue-50 border border-blue-200/80 flex items-start gap-3"
                    >
                        <Activity
                            class="h-5 w-5 text-blue-700 shrink-0 mt-0.5"
                        />
                        <div>
                            <h4
                                class="font-extrabold text-xs sm:text-sm text-blue-950"
                            >
                                Standar Kecukupan Gizi BGN (Badan Gizi Nasional)
                            </h4>
                            <p
                                class="text-[11.5px] text-blue-800 mt-0.5 leading-relaxed"
                            >
                                Target nutrisi makan siang bergizi:
                                <strong
                                    >PK (450 - 550 kkal, Protein 15 -
                                    22g)</strong
                                >
                                dan
                                <strong
                                    >PB (650 - 800 kkal, Protein 24 -
                                    35g)</strong
                                >.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 1. PORSI PK NORMAL -->
                        <Card className="bg-white border-slate-200 shadow-xs">
                            <CardHeader
                                className="p-4 border-b border-slate-100 bg-amber-50/60 flex flex-row items-center justify-between"
                            >
                                <div>
                                    <CardTitle
                                        className="text-base font-bold text-amber-950"
                                        >Porsi PK Normal (Standar)</CardTitle
                                    >
                                    <CardDescription className="text-xs"
                                        >Kelompok PAUD / TK & SD Kelas
                                        1-3</CardDescription
                                    >
                                </div>
                                <Badge
                                    variant="outline"
                                    className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-xs"
                                >
                                    <Check class="h-3 w-3 mr-1" /> MEMENUHI AKG
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div
                                        class="p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Energi (Kalori)</span
                                        >
                                        <span
                                            class="text-base font-black text-amber-900"
                                            >{{
                                                akgResultPKNormal.energi
                                            }}
                                            kkal</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 block mt-0.5"
                                            >Target: 450 - 550 kkal</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Protein</span
                                        >
                                        <span
                                            class="text-base font-black text-blue-900"
                                            >{{
                                                akgResultPKNormal.protein
                                            }}
                                            gram</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 block mt-0.5"
                                            >Target: 15 - 22 g</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Lemak</span
                                        >
                                        <span
                                            class="text-base font-black text-indigo-900"
                                            >{{
                                                akgResultPKNormal.lemak
                                            }}
                                            gram</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 block mt-0.5"
                                            >Target: 12 - 18 g</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Karbohidrat</span
                                        >
                                        <span
                                            class="text-base font-black text-emerald-900"
                                            >{{
                                                akgResultPKNormal.karbohidrat
                                            }}
                                            gram</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 block mt-0.5"
                                            >Target: 65 - 85 g</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="p-2 rounded bg-slate-50 border border-slate-100 text-xs flex justify-between"
                                >
                                    <span class="text-slate-600 font-semibold"
                                        >Kandungan Serat:</span
                                    >
                                    <span class="font-bold text-slate-800"
                                        >{{ akgResultPKNormal.serat }} gram
                                        (Min. 4.0g)</span
                                    >
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 2. PORSI PB NORMAL -->
                        <Card className="bg-white border-slate-200 shadow-xs">
                            <CardHeader
                                className="p-4 border-b border-slate-100 bg-indigo-50/60 flex flex-row items-center justify-between"
                            >
                                <div>
                                    <CardTitle
                                        className="text-base font-bold text-indigo-950"
                                        >Porsi PB Normal (Standar)</CardTitle
                                    >
                                    <CardDescription className="text-xs"
                                        >SD 4-6, SMP, SMA, & Ibu
                                        Hamil/Menyusui</CardDescription
                                    >
                                </div>
                                <Badge
                                    variant="outline"
                                    className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-xs"
                                >
                                    <Check class="h-3 w-3 mr-1" /> MEMENUHI AKG
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div
                                        class="p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Energi (Kalori)</span
                                        >
                                        <span
                                            class="text-base font-black text-indigo-950"
                                            >{{
                                                akgResultPBNormal.energi
                                            }}
                                            kkal</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 block mt-0.5"
                                            >Target: 650 - 800 kkal</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Protein</span
                                        >
                                        <span
                                            class="text-base font-black text-blue-900"
                                            >{{
                                                akgResultPBNormal.protein
                                            }}
                                            gram</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 block mt-0.5"
                                            >Target: 24 - 35 g</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Lemak</span
                                        >
                                        <span
                                            class="text-base font-black text-purple-900"
                                            >{{
                                                akgResultPBNormal.lemak
                                            }}
                                            gram</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 block mt-0.5"
                                            >Target: 18 - 26 g</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Karbohidrat</span
                                        >
                                        <span
                                            class="text-base font-black text-emerald-900"
                                            >{{
                                                akgResultPBNormal.karbohidrat
                                            }}
                                            gram</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-400 block mt-0.5"
                                            >Target: 85 - 110 g</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="p-2 rounded bg-slate-50 border border-slate-100 text-xs flex justify-between"
                                >
                                    <span class="text-slate-600 font-semibold"
                                        >Kandungan Serat:</span
                                    >
                                    <span class="font-bold text-slate-800"
                                        >{{ akgResultPBNormal.serat }} gram
                                        (Min. 6.0g)</span
                                    >
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 3. PORSI PK ALERGI (Substitusi Telur / Seafood) -->
                        <Card className="bg-white border-slate-200 shadow-xs">
                            <CardHeader
                                className="p-4 border-b border-slate-100 bg-rose-50/50 flex flex-row items-center justify-between"
                            >
                                <div>
                                    <CardTitle
                                        className="text-base font-bold text-rose-950"
                                        >Porsi PK Varian Alergi</CardTitle
                                    >
                                    <CardDescription className="text-xs"
                                        >Substitusi Bebas Alergen Telur &
                                        Seafood</CardDescription
                                    >
                                </div>
                                <Badge
                                    variant="outline"
                                    className="bg-white text-rose-800 border-rose-300 font-bold text-xs"
                                >
                                    Varian Khusus
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="p-2 rounded bg-slate-50">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold block"
                                            >Energi</span
                                        >
                                        <span class="font-bold text-slate-900"
                                            >{{
                                                akgResultPKAlergi.energi
                                            }}
                                            kkal</span
                                        >
                                    </div>
                                    <div class="p-2 rounded bg-slate-50">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold block"
                                            >Protein</span
                                        >
                                        <span class="font-bold text-blue-900"
                                            >{{
                                                akgResultPKAlergi.protein
                                            }}
                                            g</span
                                        >
                                    </div>
                                    <div class="p-2 rounded bg-slate-50">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold block"
                                            >Lemak</span
                                        >
                                        <span class="font-bold text-indigo-900"
                                            >{{
                                                akgResultPKAlergi.lemak
                                            }}
                                            g</span
                                        >
                                    </div>
                                    <div class="p-2 rounded bg-slate-50">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold block"
                                            >Karbohidrat</span
                                        >
                                        <span class="font-bold text-emerald-900"
                                            >{{
                                                akgResultPKAlergi.karbohidrat
                                            }}
                                            g</span
                                        >
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 4. PORSI PB ALERGI (Substitusi Telur / Seafood) -->
                        <Card className="bg-white border-slate-200 shadow-xs">
                            <CardHeader
                                className="p-4 border-b border-slate-100 bg-rose-50/50 flex flex-row items-center justify-between"
                            >
                                <div>
                                    <CardTitle
                                        className="text-base font-bold text-rose-950"
                                        >Porsi PB Varian Alergi</CardTitle
                                    >
                                    <CardDescription className="text-xs"
                                        >Substitusi Bebas Alergen Telur &
                                        Seafood</CardDescription
                                    >
                                </div>
                                <Badge
                                    variant="outline"
                                    className="bg-white text-rose-800 border-rose-300 font-bold text-xs"
                                >
                                    Varian Khusus
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="p-2 rounded bg-slate-50">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold block"
                                            >Energi</span
                                        >
                                        <span class="font-bold text-slate-900"
                                            >{{
                                                akgResultPBAlergi.energi
                                            }}
                                            kkal</span
                                        >
                                    </div>
                                    <div class="p-2 rounded bg-slate-50">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold block"
                                            >Protein</span
                                        >
                                        <span class="font-bold text-blue-900"
                                            >{{
                                                akgResultPBAlergi.protein
                                            }}
                                            g</span
                                        >
                                    </div>
                                    <div class="p-2 rounded bg-slate-50">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold block"
                                            >Lemak</span
                                        >
                                        <span class="font-bold text-indigo-900"
                                            >{{
                                                akgResultPBAlergi.lemak
                                            }}
                                            g</span
                                        >
                                    </div>
                                    <div class="p-2 rounded bg-slate-50">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold block"
                                            >Karbohidrat</span
                                        >
                                        <span class="font-bold text-emerald-900"
                                            >{{
                                                akgResultPBAlergi.karbohidrat
                                            }}
                                            g</span
                                        >
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- ========================================================================================= -->
                <!-- Bagian 3: Analisis Food Cost & Batas Plafon -->
                <!-- ========================================================================================= -->
                <div
                    v-if="buatMenuSubTab === 'hasil_food_cost'"
                    class="space-y-6"
                >
                    <!-- Card Perbandingan Plafon -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Food Cost PK -->
                        <Card className="bg-white border-slate-200 shadow-xs">
                            <CardHeader
                                className="p-4 border-b border-slate-100 bg-amber-50/60 flex flex-row items-center justify-between"
                            >
                                <div>
                                    <CardTitle
                                        className="text-base font-bold text-amber-950"
                                        >Food Cost Porsi Kecil (PK)</CardTitle
                                    >
                                    <CardDescription className="text-xs"
                                        >Batas Plafon Maksimal:
                                        <strong
                                            >Rp 8.000 / porsi</strong
                                        ></CardDescription
                                    >
                                </div>
                                <Badge
                                    variant="outline"
                                    :className="
                                        totalFoodCostPKNormal <= 8000
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-300 font-black'
                                            : 'bg-rose-50 text-rose-800 border-rose-300 font-black'
                                    "
                                >
                                    {{
                                        totalFoodCostPKNormal <= 8000
                                            ? "EFISIEN / AMAN"
                                            : "OVER BUDGET"
                                    }}
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-4 sm:p-5 space-y-4">
                                <div
                                    class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200"
                                >
                                    <div>
                                        <p
                                            class="text-xs text-slate-500 font-bold"
                                        >
                                            Total Food Cost PK Normal:
                                        </p>
                                        <h3
                                            class="text-2xl font-black text-slate-900 mt-0.5"
                                        >
                                            {{
                                                formatRupiah(
                                                    totalFoodCostPKNormal,
                                                )
                                            }}
                                        </h3>
                                    </div>
                                    <div class="text-right">
                                        <p
                                            class="text-xs text-slate-500 font-bold"
                                        >
                                            Sisa Plafon Anggaran:
                                        </p>
                                        <h4
                                            class="text-base font-extrabold"
                                            :class="
                                                8000 - totalFoodCostPKNormal >=
                                                0
                                                    ? 'text-emerald-700'
                                                    : 'text-rose-700'
                                            "
                                        >
                                            {{
                                                formatRupiah(
                                                    8000 -
                                                        totalFoodCostPKNormal,
                                                )
                                            }}
                                        </h4>
                                    </div>
                                </div>
                                <!-- Varian Alergi PK -->
                                <div
                                    class="p-2.5 rounded-lg bg-rose-50/50 border border-rose-100 text-xs flex items-center justify-between"
                                >
                                    <span class="font-bold text-rose-900"
                                        >Food Cost PK Varian Alergi:</span
                                    >
                                    <span class="font-extrabold text-slate-900"
                                        >{{
                                            formatRupiah(totalFoodCostPKAlergi)
                                        }}
                                        / porsi</span
                                    >
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Food Cost PB -->
                        <Card className="bg-white border-slate-200 shadow-xs">
                            <CardHeader
                                className="p-4 border-b border-slate-100 bg-indigo-50/60 flex flex-row items-center justify-between"
                            >
                                <div>
                                    <CardTitle
                                        className="text-base font-bold text-indigo-950"
                                        >Food Cost Porsi Besar (PB)</CardTitle
                                    >
                                    <CardDescription className="text-xs"
                                        >Batas Plafon Maksimal:
                                        <strong
                                            >Rp 10.000 / porsi</strong
                                        ></CardDescription
                                    >
                                </div>
                                <Badge
                                    variant="outline"
                                    :className="
                                        totalFoodCostPBNormal <= 10000
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-300 font-black'
                                            : 'bg-rose-50 text-rose-800 border-rose-300 font-black'
                                    "
                                >
                                    {{
                                        totalFoodCostPBNormal <= 10000
                                            ? "EFISIEN / AMAN"
                                            : "OVER BUDGET"
                                    }}
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-4 sm:p-5 space-y-4">
                                <div
                                    class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200"
                                >
                                    <div>
                                        <p
                                            class="text-xs text-slate-500 font-bold"
                                        >
                                            Total Food Cost PB Normal:
                                        </p>
                                        <h3
                                            class="text-2xl font-black text-slate-900 mt-0.5"
                                        >
                                            {{
                                                formatRupiah(
                                                    totalFoodCostPBNormal,
                                                )
                                            }}
                                        </h3>
                                    </div>
                                    <div class="text-right">
                                        <p
                                            class="text-xs text-slate-500 font-bold"
                                        >
                                            Sisa Plafon Anggaran:
                                        </p>
                                        <h4
                                            class="text-base font-extrabold"
                                            :class="
                                                10000 - totalFoodCostPBNormal >=
                                                0
                                                    ? 'text-emerald-700'
                                                    : 'text-rose-700'
                                            "
                                        >
                                            {{
                                                formatRupiah(
                                                    10000 -
                                                        totalFoodCostPBNormal,
                                                )
                                            }}
                                        </h4>
                                    </div>
                                </div>
                                <!-- Varian Alergi PB -->
                                <div
                                    class="p-2.5 rounded-lg bg-rose-50/50 border border-rose-100 text-xs flex items-center justify-between"
                                >
                                    <span class="font-bold text-rose-900"
                                        >Food Cost PB Varian Alergi:</span
                                    >
                                    <span class="font-extrabold text-slate-900"
                                        >{{
                                            formatRupiah(totalFoodCostPBAlergi)
                                        }}
                                        / porsi</span
                                    >
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Rincian Food Cost per Komponen Bahan -->
                    <Card
                        className="bg-white border-slate-200 shadow-xs overflow-hidden"
                    >
                        <CardHeader
                            className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                        >
                            <CardTitle
                                className="text-base font-bold text-slate-900 flex items-center gap-2"
                            >
                                <Coins class="h-4 w-4 text-primary" />
                                <span
                                    >Rincian Food Cost per Komponen Bahan
                                    Menu</span
                                >
                            </CardTitle>
                        </CardHeader>
                        <div class="overflow-x-auto">
                            <table
                                class="w-full text-left text-xs border-collapse"
                            >
                                <thead
                                    class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3">Nama Bahan</th>
                                        <th class="p-3">Kategori</th>
                                        <th class="p-3 text-right">Gram PK</th>
                                        <th class="p-3 text-right">
                                            Biaya PK / Porsi
                                        </th>
                                        <th class="p-3 text-right">Gram PB</th>
                                        <th class="p-3 text-right">
                                            Biaya PB / Porsi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 text-slate-800"
                                >
                                    <tr
                                        v-for="(b, idx) in bahanCalculations"
                                        :key="idx"
                                        class="hover:bg-slate-50/70 transition-colors"
                                    >
                                        <td
                                            class="p-3 font-bold text-slate-900"
                                        >
                                            {{ b.nama }}
                                        </td>
                                        <td class="p-3 text-slate-600">
                                            {{ b.kategori }}
                                        </td>
                                        <td
                                            class="p-3 text-right text-slate-700"
                                        >
                                            {{ b.gram_pk }} g
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-amber-800"
                                        >
                                            {{ formatRupiah(b.costPK) }}
                                        </td>
                                        <td
                                            class="p-3 text-right text-slate-700"
                                        >
                                            {{ b.gram_pb }} g
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-indigo-800"
                                        >
                                            {{ formatRupiah(b.costPB) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="bg-slate-100/90 font-black text-slate-900 border-t-2 border-slate-300 text-xs"
                                >
                                    <tr>
                                        <td
                                            colspan="3"
                                            class="p-3.5 uppercase tracking-wider text-slate-700"
                                        >
                                            Grand Total Food Cost per Porsi:
                                        </td>
                                        <td
                                            class="p-3.5 text-right text-amber-950 font-black text-sm"
                                        >
                                            {{
                                                formatRupiah(
                                                    totalFoodCostPKNormal,
                                                )
                                            }}
                                        </td>
                                        <td></td>
                                        <td
                                            class="p-3.5 text-right text-indigo-950 font-black text-sm"
                                        >
                                            {{
                                                formatRupiah(
                                                    totalFoodCostPBNormal,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- 4. SUB MENU 4: KALENDER MENU (SIKLUS & JADWAL HARIAN MBG) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === 'kalender-menu'" class="space-y-6">
                <!-- Header & Info Siklus -->
                <Card className="bg-white border-slate-200 shadow-xs">
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                    >
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                        >
                            <div>
                                <CardTitle
                                    class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <CalendarDays
                                        class="h-5 w-5 text-primary"
                                    />
                                    <span
                                        >Kalender Siklus Menu Harian MBG
                                        SPPG</span
                                    >
                                </CardTitle>
                                <CardDescription
                                    class="text-xs sm:text-sm mt-0.5"
                                >
                                    Jadwal rotasi siklus 10 hari menu makan
                                    bergizi gratis, status verifikasi gizi, dan
                                    target porsi produksi harian.
                                </CardDescription>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="px-3 py-1 text-xs font-extrabold rounded-lg bg-blue-50 text-blue-700 border border-blue-200"
                                >
                                    Siklus: 10 Hari Bergantian
                                </span>
                                <span
                                    class="px-3 py-1 text-xs font-extrabold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    Total 1.908 Porsi / Hari
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-5 space-y-5">
                        <!-- Bulan & Navigasi -->
                        <div
                            class="flex items-center justify-between pb-3 border-b border-slate-100"
                        >
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-900">
                                    {{ kalenderBulan }}
                                </h3>
                                <Badge
                                    variant="outline"
                                    class="bg-primary/5 text-primary border-primary/20 text-[11px] font-bold"
                                >
                                    Bulan Berjalan
                                </Badge>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Button
                                    type="button"
                                    className="h-8 px-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    className="h-8 px-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Grid Jadwal Kalender Menu (Card Grid per Tanggal) -->
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
                        >
                            <div
                                v-for="(item, idx) in jadwalMenuBulan"
                                :key="idx"
                                @click="selectedKalenderItem = item"
                                :class="[
                                    'p-4 rounded-xl border transition-all cursor-pointer flex flex-col justify-between gap-3 shadow-2xs hover:shadow-md',
                                    item.status === 'Aktif Hari Ini'
                                        ? 'bg-blue-50/70 border-primary shadow-sm ring-2 ring-primary/20'
                                        : 'bg-white border-slate-200 hover:border-primary/40',
                                ]"
                            >
                                <!-- Header Card Tanggal & Siklus -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="[
                                                'h-9 w-9 rounded-xl flex items-center justify-center font-black text-sm',
                                                item.status === 'Aktif Hari Ini'
                                                    ? 'bg-primary text-white shadow-xs'
                                                    : 'bg-slate-100 text-slate-800 font-bold',
                                            ]"
                                        >
                                            {{ item.tglNo }}
                                        </span>
                                        <div>
                                            <p
                                                class="text-xs font-bold text-slate-900 leading-tight"
                                            >
                                                {{ item.hari }},
                                                {{ item.tanggal }}
                                            </p>
                                            <p
                                                class="text-[10.5px] font-semibold text-primary"
                                            >
                                                Siklus Hari Ke-{{
                                                    item.siklusKe
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 text-[10px] font-black rounded-full border',
                                            item.status === 'Aktif Hari Ini'
                                                ? 'bg-blue-600 text-white border-blue-600 animate-pulse'
                                                : item.status ===
                                                    'Siap Produksi'
                                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                  : item.status === 'Selesai'
                                                    ? 'bg-slate-100 text-slate-700 border-slate-200'
                                                    : 'bg-amber-50 text-amber-800 border-amber-200',
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </div>

                                <!-- Nama Menu -->
                                <div class="space-y-1">
                                    <p
                                        class="text-xs font-bold text-slate-900 line-clamp-2 leading-relaxed"
                                    >
                                        {{ item.namaMenu }}
                                    </p>
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        <span
                                            v-for="(
                                                k, kidx
                                            ) in item.komponen.slice(0, 4)"
                                            :key="kidx"
                                            class="px-1.5 py-0.5 text-[10px] rounded bg-slate-100 text-slate-600 font-medium"
                                        >
                                            {{ k }}
                                        </span>
                                        <span
                                            v-if="item.komponen.length > 4"
                                            class="px-1.5 py-0.5 text-[10px] rounded bg-slate-100 text-slate-500 font-semibold"
                                        >
                                            +{{ item.komponen.length - 4 }} lagi
                                        </span>
                                    </div>
                                </div>

                                <!-- Ringkasan AKG & Food Cost -->
                                <div
                                    class="pt-2.5 border-t border-slate-100/90 grid grid-cols-2 gap-2 text-[11px]"
                                >
                                    <div class="bg-slate-50 p-2 rounded-lg">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 block uppercase"
                                            >Porsi Kecil (PK)</span
                                        >
                                        <span class="font-black text-amber-900"
                                            >{{ item.kaloriPK }} kkal</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-500 block"
                                            >{{
                                                formatRupiah(item.costPK)
                                            }}</span
                                        >
                                    </div>
                                    <div class="bg-slate-50 p-2 rounded-lg">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 block uppercase"
                                            >Porsi Besar (PB)</span
                                        >
                                        <span class="font-black text-indigo-900"
                                            >{{ item.kaloriPB }} kkal</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-500 block"
                                            >{{
                                                formatRupiah(item.costPB)
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Modal / Popup Preview Detail Menu Tanggal Terpilih -->
                <div
                    v-if="selectedKalenderItem"
                    class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                    @click.self="selectedKalenderItem = null"
                >
                    <Card
                        className="bg-white border-slate-200 max-w-lg w-full shadow-2xl animate-in zoom-in-95 duration-150"
                    >
                        <CardHeader
                            className="p-4 sm:p-5 border-b border-slate-100 flex flex-row items-center justify-between bg-slate-50/60"
                        >
                            <div>
                                <span
                                    class="text-xs font-extrabold text-primary uppercase tracking-wider"
                                >
                                    Siklus Ke-{{
                                        selectedKalenderItem.siklusKe
                                    }}
                                    • {{ selectedKalenderItem.hari }}
                                </span>
                                <CardTitle
                                    class="text-base font-bold text-slate-900 mt-0.5"
                                >
                                    {{ selectedKalenderItem.tanggal }}
                                </CardTitle>
                            </div>
                            <button
                                type="button"
                                @click="selectedKalenderItem = null"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 cursor-pointer"
                            >
                                ✕
                            </button>
                        </CardHeader>
                        <CardContent className="p-4 sm:p-5 space-y-4 text-xs">
                            <div>
                                <p
                                    class="text-[11px] font-bold text-slate-400 uppercase"
                                >
                                    Nama Paket Menu MBG
                                </p>
                                <p
                                    class="text-sm font-bold text-slate-900 mt-0.5"
                                >
                                    {{ selectedKalenderItem.namaMenu }}
                                </p>
                            </div>

                            <div>
                                <p
                                    class="text-[11px] font-bold text-slate-400 uppercase mb-1.5"
                                >
                                    Komponen Bahan Baku
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="(
                                            komp, kidx
                                        ) in selectedKalenderItem.komponen"
                                        :key="kidx"
                                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-50 text-blue-800 border border-blue-100"
                                    >
                                        {{ komp }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100"
                            >
                                <div
                                    class="p-3 bg-amber-50/70 border border-amber-100 rounded-xl space-y-1"
                                >
                                    <p
                                        class="text-[10.5px] font-bold text-amber-900 uppercase"
                                    >
                                        Porsi Kecil (PK)
                                    </p>
                                    <p
                                        class="text-sm font-black text-amber-950"
                                    >
                                        {{ selectedKalenderItem.kaloriPK }} kkal
                                    </p>
                                    <p class="text-xs font-bold text-amber-800">
                                        {{
                                            formatRupiah(
                                                selectedKalenderItem.costPK,
                                            )
                                        }}
                                        / porsi
                                    </p>
                                    <p class="text-[10px] text-amber-700">
                                        Target: 880 Porsi
                                    </p>
                                </div>
                                <div
                                    class="p-3 bg-indigo-50/70 border border-indigo-100 rounded-xl space-y-1"
                                >
                                    <p
                                        class="text-[10.5px] font-bold text-indigo-900 uppercase"
                                    >
                                        Porsi Besar (PB)
                                    </p>
                                    <p
                                        class="text-sm font-black text-indigo-950"
                                    >
                                        {{ selectedKalenderItem.kaloriPB }} kkal
                                    </p>
                                    <p
                                        class="text-xs font-bold text-indigo-800"
                                    >
                                        {{
                                            formatRupiah(
                                                selectedKalenderItem.costPB,
                                            )
                                        }}
                                        / porsi
                                    </p>
                                    <p class="text-[10px] text-indigo-700">
                                        Target: 1.028 Porsi
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100"
                            >
                                <Button
                                    type="button"
                                    @click="
                                        selectSubMenu({
                                            id: 'buat-menu',
                                            routeName: 'gizi.buat-menu',
                                        });
                                        selectedKalenderItem = null;
                                    "
                                    className="bg-primary text-white text-xs font-bold px-4 h-9 cursor-pointer"
                                >
                                    <UtensilsCrossed class="h-3.5 w-3.5 mr-1" />
                                    Buka di Formulasi Menu
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
