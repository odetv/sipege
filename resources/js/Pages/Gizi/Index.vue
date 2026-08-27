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
    initialStep: {
        type: String,
        default: null,
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

// Sub-Menu Utama Gizi SPPG
// 'tkpi' | 'analisa-pm' | 'daftar-menu' | 'buat-menu' | 'kalender-menu'
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
        id: "daftar-menu",
        no: "3",
        title: "Daftar Menu",
        subtitle: "Tabel Work Order & Menu MBG",
        icon: FileSpreadsheet,
        routeName: "gizi.daftar-menu",
    },
    {
        id: "rancang-menu",
        no: "4",
        title: "Rancang Menu",
        subtitle: "Perencanaan & Formula Gizi",
        icon: UtensilsCrossed,
        routeName: "gizi.rancang-menu",
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

// Sub-Tab di dalam modul Buat Menu (Perencanaan & Formulasi Gizi)
const normalizeStep = (step) => {
    if (
        step === "pre_order" ||
        step === "formula-gizi" ||
        step === "formula_gizi"
    )
        return "pre_order";
    if (
        step === "order" ||
        step === "pembelian_bahan" ||
        step === "pembelian-bahan"
    )
        return "order";
    return "work_order";
};

const buatMenuSubTab = ref(normalizeStep(props.initialStep));

watch(
    () => props.initialStep,
    (step) => {
        if (step) buatMenuSubTab.value = normalizeStep(step);
    },
);

const buatMenuSubTabs = [
    {
        id: "work_order",
        label: "1. Perencanaan Produksi",
        icon: FileSpreadsheet,
    },
    {
        id: "pre_order",
        label: "2. Formula Gizi",
        icon: ClipboardList,
    },
    {
        id: "order",
        label: "3. Pembelian Bahan",
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
// DAFTAR MENU & REKAP WORK ORDER (TAB DAFTAR MENU)
// ==========================================
const searchDaftarMenu = ref("");
const statusFilterDaftarMenu = ref("semua");

const daftarMenuList = ref([
    {
        id: "WO-MBG-20260825",
        nama: "Ayam Goreng Lengkuas, Sayur Bayam Jagung Manis, Tempe Bacem & Pisang Cavendish",
        tanggal: "2026-08-25",
        siklus: "Hari ke-1",
        porsi_pk: 733,
        porsi_pb: 1175,
        total_porsi: 1908,
        energi_pk: 465,
        protein_pk: 16.2,
        energi_pb: 685,
        protein_pb: 24.5,
        cost_pk: 7850,
        cost_pb: 9900,
        status_akg: "memenuhi",
        status_wo: "Terkonfirmasi",
        po_no: "PO-20260825-001",
    },
    {
        id: "WO-MBG-20260826",
        nama: "Ikan Kembung Bakar Kecap, Tumis Buncis Wortel Tempe, Tahu Goreng & Jeruk Manis",
        tanggal: "2026-08-26",
        siklus: "Hari ke-2",
        porsi_pk: 733,
        porsi_pb: 1175,
        total_porsi: 1908,
        energi_pk: 470,
        protein_pk: 16.8,
        energi_pb: 690,
        protein_pb: 25.1,
        cost_pk: 7920,
        cost_pb: 9850,
        status_akg: "memenuhi",
        status_wo: "Siap Produksi",
        po_no: "PO-20260826-002",
    },
    {
        id: "WO-MBG-20260827",
        nama: "Semur Telur Ayam & Tahu Tempe, Sayur Sop Komplit, Kerupuk & Pepaya Potong",
        tanggal: "2026-08-27",
        siklus: "Hari ke-3",
        porsi_pk: 733,
        porsi_pb: 1175,
        total_porsi: 1908,
        energi_pk: 455,
        protein_pk: 15.5,
        energi_pb: 675,
        protein_pb: 23.8,
        cost_pk: 7650,
        cost_pb: 9600,
        status_akg: "memenuhi",
        status_wo: "Draft WO",
        po_no: "PO-20260827-003",
    },
    {
        id: "WO-MBG-20260828",
        nama: "Daging Sapi Cincang Saus Tiram, Capcay Sayuran Segar, Tahu Bakso & Semangka",
        tanggal: "2026-08-28",
        siklus: "Hari ke-4",
        porsi_pk: 733,
        porsi_pb: 1175,
        total_porsi: 1908,
        energi_pk: 480,
        protein_pk: 17.5,
        energi_pb: 710,
        protein_pb: 26.2,
        cost_pk: 7980,
        cost_pb: 9980,
        status_akg: "memenuhi",
        status_wo: "Draft WO",
        po_no: "PO-20260828-004",
    },
]);

const filteredDaftarMenu = computed(() => {
    return daftarMenuList.value.filter((m) => {
        const matchSearch =
            !searchDaftarMenu.value ||
            m.id.toLowerCase().includes(searchDaftarMenu.value.toLowerCase()) ||
            m.nama
                .toLowerCase()
                .includes(searchDaftarMenu.value.toLowerCase()) ||
            m.tanggal.includes(searchDaftarMenu.value);
        const matchStatus =
            statusFilterDaftarMenu.value === "semua" ||
            m.status_wo
                .toLowerCase()
                .includes(statusFilterDaftarMenu.value.toLowerCase());
        return matchSearch && matchStatus;
    });
});

// ==========================================
// 1. STATE WORK ORDER & PRE-ORDER (AHLI GIZI)
// ==========================================
const tanggalRencana = ref(new Date().toISOString().split("T")[0]);
const woNo = computed(() => {
    return (
        "WO-MBG-" +
        (tanggalRencana.value
            ? tanggalRencana.value.replace(/-/g, "")
            : new Date().toISOString().slice(0, 10).replace(/-/g, ""))
    );
});
const namaMenuAktif = ref("");
const subMenuKomponen = ref({
    energi: "",
    protein: "",
    lemak: "",
    karbohidrat: "",
    serat: "",
});
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
        .reduce(
            (sum, r) => sum + (r.jumlah_laki_laki + r.jumlah_perempuan || 0),
            0,
        );
    const calcPB = rincianArr
        .filter((r) => r.jenis_porsi === "Porsi Besar")
        .reduce(
            (sum, r) => sum + (r.jumlah_laki_laki + r.jumlah_perempuan || 0),
            0,
        );

    const pk = calcPK > 0 ? calcPK : Number(k.total_porsi_kecil) || 0;
    const pb = calcPB > 0 ? calcPB : Number(k.total_porsi_besar) || 0;

    const normAlergi = normalizeAlergiItems(
        k.keterangan_alergi,
        k.alergi_porsi_kecil,
        k.alergi_porsi_besar,
    );
    const sumAlergiPk = normAlergi.reduce(
        (s, a) => s + (Number(a.porsi_kecil) || 0),
        0,
    );
    const sumAlergiPb = normAlergi.reduce(
        (s, a) => s + (Number(a.porsi_besar) || 0),
        0,
    );

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
        alergi_porsi_kecil:
            sumAlergiPk > 0 ? sumAlergiPk : Number(k.alergi_porsi_kecil) || 0,
        alergi_porsi_besar:
            sumAlergiPb > 0 ? sumAlergiPb : Number(k.alergi_porsi_besar) || 0,
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
    editFormRincian.value = JSON.parse(JSON.stringify(kelompok.rincian || []));
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
    editingKelompok.value.keterangan_alergi =
        editFormKeteranganAlergi.value.map((a) => ({
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
        namaMenuAktif.value = item.namaMenu || "";
        if (Array.isArray(item.komponen) && item.komponen.length > 0) {
            subMenuKomponen.value.energi = item.komponen[0] || "";
            subMenuKomponen.value.karbohidrat = item.komponen[0] || "";
            subMenuKomponen.value.protein = item.komponen[1] || "";
            subMenuKomponen.value.lemak = item.komponen[2] || "";
            subMenuKomponen.value.serat =
                item.komponen.length > 4
                    ? `${item.komponen[3]}, ${item.komponen[4]}`
                    : item.komponen[3] || "";
        }
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
        alert(
            "Minimal harus ada 1 kelompok sasaran yang berstatus 'Menerima' pada Work Order!",
        );
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
        nama_po: master.nama, // Nama bahan yang ditampilkan ke PO akuntan
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
const poCatatanAkuntan = ref("");
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
        "ikan",
        "fish",
        "tuna",
        "tongkol",
        "lele",
        "bandeng",
        "gurame",
        "nila",
        "dori",
        "salmon",
        "patin",
        "seafood",
        "cumi",
        "gurita",
        "kakap",
        "tenggiri",
        "kembung",
    ],
    "Udang & Krustasea": [
        "udang",
        "shrimp",
        "prawn",
        "kepiting",
        "crab",
        "lobster",
        "rajungan",
    ],
    Telur: [
        "telur",
        "egg",
        "dadar",
        "ceplok",
        "balado telur",
        "omelet",
        "puyuh",
        "mata sapi",
    ],
    "Susu Sapi / Laktosa": [
        "susu",
        "milk",
        "keju",
        "cheese",
        "mentega",
        "butter",
        "yogurt",
        "krim",
        "cream",
    ],
    "Kacang Tanah & Pohon": [
        "kacang",
        "peanut",
        "bumbu kacang",
        "almond",
        "mete",
        "cashew",
        "pecel",
        "gado-gado",
        "saus kacang",
    ],
    "Kedelai / Soja": [
        "kedelai",
        "soy",
        "tahu",
        "tempe",
        "tauco",
        "kecap",
        "edamame",
    ],
    "Gandum / Gluten": [
        "gandum",
        "wheat",
        "gluten",
        "roti",
        "mie",
        "mi",
        "pasta",
        "spageti",
        "makaroni",
        "tepung",
    ],
    "Daging Ayam / Unggas": ["ayam", "chicken", "bebek", "unggas"],
    "Daging Sapi": [
        "sapi",
        "beef",
        "daging",
        "bakso",
        "rendang",
        "rawon",
        "empal",
    ],
    Cokelat: ["cokelat", "chocolate", "coklat", "cocoa"],
};

const REKOMENDASI_SUBSTITUSI = {
    "Ikan Laut / Seafood": "Fillet Daging Ayam, Daging Sapi, Tahu, atau Tempe",
    "Udang & Krustasea": "Daging Ayam, Daging Sapi, atau Telur",
    Telur: "Tahu Sutra, Tempe, Daging Ayam, atau Ikan",
    "Susu Sapi / Laktosa": "Susu Kedelai, Susu Almond, atau Sari Gandum Oat",
    "Kacang Tanah & Pohon": "Saus Wijen, Saus Tomat, atau Bumbu Kecap Rempah",
    "Kedelai / Soja": "Telur, Daging Ayam, Ikan, atau Kacang Merah",
    "Gandum / Gluten":
        "Nasi Putih, Jagung Pipil, Kentang, Ubi, atau Bihun Beras",
    "Daging Ayam / Unggas": "Ikan Fillet, Telur, Daging Sapi, atau Tahu",
    "Daging Sapi": "Daging Ayam, Ikan, Telur, atau Tempe",
};

// Rekapitulasi Alergi per Jenis dari seluruh kelompok sasaran aktif
const rekapAlergiDetailPm = computed(() => {
    const summary = {};
    kelompokMenerimaAktif.value.forEach((k) => {
        if (Array.isArray(k.keterangan_alergi)) {
            k.keterangan_alergi.forEach((item) => {
                const jenis =
                    typeof item === "string" ? item : item.jenis_alergi;
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
                    !summary[cleanJenis].kelompok_names.includes(
                        k.nama_kelompok,
                    )
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
    const combinedText = [
        namaMenuAktif.value || "",
        subMenuKomponen.value.energi || "",
        subMenuKomponen.value.protein || "",
        subMenuKomponen.value.lemak || "",
        subMenuKomponen.value.karbohidrat || "",
        subMenuKomponen.value.serat || "",
    ].join(" ");
    const menuLower = combinedText.toLowerCase();
    const activeAlergi = rekapAlergiDetailPm.value;
    const conflicts = [];

    activeAlergi.forEach((al) => {
        let matched = false;
        let matchedKeyword = "";

        // Cari di dictionary kata kunci alergen
        for (const [allergenName, keywords] of Object.entries(
            ALLERGEN_KEYWORDS,
        )) {
            const isRelated =
                allergenName
                    .toLowerCase()
                    .includes(al.jenis_alergi.toLowerCase()) ||
                al.jenis_alergi
                    .toLowerCase()
                    .includes(allergenName.toLowerCase());

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

// Rekapitulasi Alergi Master PM (Tab 2: Analisa PM)
const rekapAlergiMasterPm = computed(() => {
    const summary = {};
    (props.kelompokList || []).forEach((k) => {
        if (
            Array.isArray(k.keterangan_alergi) &&
            k.keterangan_alergi.length > 0
        ) {
            k.keterangan_alergi.forEach((item) => {
                const jenis =
                    typeof item === "string" ? item : item.jenis_alergi;
                if (!jenis) return;
                const cleanJenis = jenis.trim();
                if (!summary[cleanJenis]) {
                    summary[cleanJenis] = {
                        jenis_alergi: cleanJenis,
                        porsi_kecil: 0,
                        porsi_besar: 0,
                        total: 0,
                        kelompok_list: [],
                    };
                }
                const pk = Number(item.porsi_kecil) || 0;
                const pb = Number(item.porsi_besar) || 0;
                const totalItem = pk + pb;
                summary[cleanJenis].porsi_kecil += pk;
                summary[cleanJenis].porsi_besar += pb;
                summary[cleanJenis].total += totalItem;

                if (totalItem > 0) {
                    const existingKel = summary[cleanJenis].kelompok_list.find(
                        (x) => x.id === k.id,
                    );
                    if (existingKel) {
                        existingKel.porsi_kecil += pk;
                        existingKel.porsi_besar += pb;
                        existingKel.total += totalItem;
                    } else {
                        summary[cleanJenis].kelompok_list.push({
                            id: k.id,
                            nama_kelompok: k.nama_kelompok,
                            kategori: k.kategori,
                            desa_kelurahan: k.desa_kelurahan,
                            porsi_kecil: pk,
                            porsi_besar: pb,
                            total: totalItem,
                        });
                    }
                }
            });
        } else if (
            (Number(k.alergi_porsi_kecil) || 0) +
                (Number(k.alergi_porsi_besar) || 0) >
            0
        ) {
            const cleanJenis = "Alergi Khusus";
            if (!summary[cleanJenis]) {
                summary[cleanJenis] = {
                    jenis_alergi: cleanJenis,
                    porsi_kecil: 0,
                    porsi_besar: 0,
                    total: 0,
                    kelompok_list: [],
                };
            }
            const pk = Number(k.alergi_porsi_kecil) || 0;
            const pb = Number(k.alergi_porsi_besar) || 0;
            summary[cleanJenis].porsi_kecil += pk;
            summary[cleanJenis].porsi_besar += pb;
            summary[cleanJenis].total += pk + pb;
            summary[cleanJenis].kelompok_list.push({
                id: k.id,
                nama_kelompok: k.nama_kelompok,
                kategori: k.kategori,
                desa_kelurahan: k.desa_kelurahan,
                porsi_kecil: pk,
                porsi_besar: pb,
                total: pk + pb,
            });
        }
    });
    return Object.values(summary).filter((item) => item.total > 0);
});

const totalMasterPmSiswaAlergi = computed(() => {
    return rekapAlergiMasterPm.value.reduce((s, a) => s + a.total, 0);
});

// Helper Deteksi Apakah Bahan Pangan Mengandung Alergen Tertentu
function isBahanContainsAlergen(b, jenisAlergi) {
    if (!jenisAlergi) return false;
    const lowerJenis = jenisAlergi.toLowerCase().trim();
    const alergen = (
        b.alergen ||
        (b.tkpi && b.tkpi.alergen) ||
        ""
    ).toLowerCase();
    const namaBahan = (b.nama || (b.tkpi && b.tkpi.nama) || "").toLowerCase();
    const kategori = (
        b.kategori ||
        (b.tkpi && b.tkpi.kategori) ||
        ""
    ).toLowerCase();

    // 1. Telur
    if (
        lowerJenis.includes("telur") &&
        (alergen.includes("telur") ||
            namaBahan.includes("telur") ||
            kategori.includes("telur"))
    )
        return true;

    // 2. Ikan / Seafood / Udang / Kepiting / Krustasea
    if (
        (lowerJenis.includes("seafood") ||
            lowerJenis.includes("ikan") ||
            lowerJenis.includes("udang") ||
            lowerJenis.includes("kepiting") ||
            lowerJenis.includes("krustasea")) &&
        (alergen.includes("ikan") ||
            alergen.includes("seafood") ||
            alergen.includes("udang") ||
            alergen.includes("kepiting") ||
            namaBahan.includes("ikan") ||
            namaBahan.includes("udang") ||
            namaBahan.includes("seafood") ||
            namaBahan.includes("kepiting") ||
            namaBahan.includes("cumi") ||
            kategori.includes("ikan"))
    )
        return true;

    // 3. Kacang Tanah & Pohon
    if (
        lowerJenis.includes("kacang") &&
        (alergen.includes("kacang") ||
            namaBahan.includes("kacang") ||
            kategori.includes("kacang"))
    )
        return true;

    // 4. Susu Sapi & Laktosa
    if (
        lowerJenis.includes("susu") &&
        (alergen.includes("susu") ||
            alergen.includes("laktosa") ||
            namaBahan.includes("susu") ||
            namaBahan.includes("keju") ||
            kategori.includes("susu"))
    )
        return true;

    // 5. Gandum & Gluten
    if (
        (lowerJenis.includes("gandum") || lowerJenis.includes("gluten")) &&
        (alergen.includes("gandum") ||
            alergen.includes("gluten") ||
            namaBahan.includes("gandum") ||
            namaBahan.includes("mie") ||
            namaBahan.includes("pasta") ||
            namaBahan.includes("roti") ||
            namaBahan.includes("terigu"))
    )
        return true;

    // 6. Kedelai & Soja
    if (
        (lowerJenis.includes("kedelai") || lowerJenis.includes("soja")) &&
        (alergen.includes("kedelai") ||
            alergen.includes("soja") ||
            namaBahan.includes("kedelai") ||
            namaBahan.includes("tahu") ||
            namaBahan.includes("tempe"))
    )
        return true;

    // 7. Daging Ayam & Unggas
    if (
        lowerJenis.includes("ayam") &&
        (alergen.includes("ayam") ||
            namaBahan.includes("ayam") ||
            namaBahan.includes("unggas") ||
            kategori.includes("unggas"))
    )
        return true;

    // 8. Daging Sapi
    if (
        lowerJenis.includes("sapi") &&
        (alergen.includes("sapi") ||
            namaBahan.includes("sapi") ||
            namaBahan.includes("daging sapi"))
    )
        return true;

    // 9. Cokelat
    if (
        lowerJenis.includes("cokelat") &&
        (alergen.includes("cokelat") ||
            namaBahan.includes("cokelat") ||
            namaBahan.includes("coklat") ||
            namaBahan.includes("cocoa"))
    )
        return true;

    // Direct keyword check
    const cleanNoPrefix = lowerJenis.replace(/^alergi\s+/, "").trim();
    if (
        cleanNoPrefix &&
        (alergen.includes(cleanNoPrefix) || namaBahan.includes(cleanNoPrefix))
    ) {
        return true;
    }

    return false;
}

// Helper Pencocokan Detail Alergi dari Data Master Penerima Manfaat
function findAlergiDetail(jenisName) {
    if (!jenisName) return null;
    const clean = jenisName.toLowerCase().trim();
    const cleanNoPrefix = clean.replace(/^alergi\s+/, "");
    return (
        rekapAlergiDetailPm.value.find((r) => {
            const rClean = r.jenis_alergi.toLowerCase().trim();
            const rCleanNoPrefix = rClean.replace(/^alergi\s+/, "");
            return (
                rClean === clean ||
                rCleanNoPrefix === cleanNoPrefix ||
                clean.includes(rCleanNoPrefix) ||
                rClean.includes(cleanNoPrefix)
            );
        }) || null
    );
}

// Opsi Alergi dengan Statistik PM & Penandaan Disabled jika 0 Siswa
const alergiOptionsWithStats = computed(() => {
    return ALERGI_OPTIONS.map((opt) => {
        const detail = findAlergiDetail(opt.value);
        const total = detail ? Number(detail.total) || 0 : 0;
        const pk = detail ? Number(detail.porsi_kecil) || 0 : 0;
        const pb = detail ? Number(detail.porsi_besar) || 0 : 0;
        const isDisabled = total === 0;

        return {
            ...opt,
            total,
            pk,
            pb,
            disabled: isDisabled,
            labelDisplay: isDisabled
                ? `⛔ ${opt.label} (0 Siswa - Tidak ada di PM)`
                : `✓ ${opt.label} (${total} Siswa • PK: ${pk}, PB: ${pb})`,
        };
    });
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

        // Kuota sasaran porsi (Normal vs Alergi Spesifik Jenis)
        let targetPKCount = 0;
        let targetPBCount = 0;
        let alergiDampakList = [];

        if (!isAlergi) {
            // Bahan Porsi Normal: Awalnya seluruh sasaran siswa normal
            targetPKCount = totalPK.value || 0;
            targetPBCount = totalPB.value || 0;

            // Jika bahan normal ini mengandung alergen yang tercatat di PM, porsinya OTOMATIS DIKURANGI
            rekapAlergiDetailPm.value.forEach((al) => {
                if (
                    isBahanContainsAlergen(b, al.jenis_alergi) &&
                    al.total > 0
                ) {
                    const pkMinus = Number(al.porsi_kecil) || 0;
                    const pbMinus = Number(al.porsi_besar) || 0;
                    targetPKCount = Math.max(0, targetPKCount - pkMinus);
                    targetPBCount = Math.max(0, targetPBCount - pbMinus);
                    alergiDampakList.push({
                        jenis: al.jenis_alergi,
                        pk: pkMinus,
                        pb: pbMinus,
                        total: pkMinus + pbMinus,
                    });
                }
            });
        } else {
            // Bahan Porsi Alergi: Diberikan HANYA untuk siswa dengan alergi spesifik b.jenis_alergi
            const detailPm = findAlergiDetail(b.jenis_alergi);
            if (detailPm) {
                targetPKCount = Number(detailPm.porsi_kecil) || 0;
                targetPBCount = Number(detailPm.porsi_besar) || 0;
            } else {
                // Jika di master PM TIDAK ADA siswa dengan alergi ini
                targetPKCount = 0;
                targetPBCount = 0;
            }
        }

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
            nama_po: b.nama_po || b.nama,
            tkpi,
            isAlergi,
            targetPKCount,
            targetPBCount,
            totalTargetCount: targetPKCount + targetPBCount,
            alergiDampakList,
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

// AKG Varian Khusus Alergi (Dikelompokkan Spesifik per Jenis Alergi yang Dipilih di Resep)
const activeAlergiAkgList = computed(() => {
    // 1. Ambil seluruh bahan yang diatur dengan tipe_porsi === 'alergi' dan memiliki jenis_alergi yang valid
    const alergiBahanList = bahanCalculations.value.filter(
        (b) =>
            b.tipe_porsi === "alergi" &&
            b.jenis_alergi &&
            b.jenis_alergi.trim(),
    );
    if (alergiBahanList.length === 0) {
        return [];
    }

    // 2. Kumpulkan daftar unik jenis alergi
    const uniqueJenisMap = new Map();
    alergiBahanList.forEach((b) => {
        const jenis = b.jenis_alergi.trim();
        if (!uniqueJenisMap.has(jenis)) {
            uniqueJenisMap.set(jenis, []);
        }
        uniqueJenisMap.get(jenis).push(b);
    });

    // 3. Untuk setiap jenis alergi, hitung gizi PK dan PB secara real-time
    const result = [];
    uniqueJenisMap.forEach((bahans, jenis) => {
        const detailPm = findAlergiDetail(jenis);
        const jmlPk = detailPm ? Number(detailPm.porsi_kecil) || 0 : 0;
        const jmlPb = detailPm ? Number(detailPm.porsi_besar) || 0 : 0;
        const jmlTotal = detailPm ? Number(detailPm.total) || 0 : 0;

        // Jika jenis alergi ini tidak ada siswa di PM (0 siswa), jangan tampilkan card evaluasinya
        if (jmlTotal === 0) {
            return;
        }

        // Ambil bahan normal yang aman (tidak mengandung alergen terkait)
        const bahanNormalSafe = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi !== "normal") return false;
            return !isBahanContainsAlergen(b, jenis);
        });

        // Gabungkan bahan normal yang aman + bahan substitusi khusus alergi ini
        const allBahanVarian = [...bahanNormalSafe, ...bahans];

        const calcPK = {
            energi: 0,
            protein: 0,
            lemak: 0,
            karbohidrat: 0,
            serat: 0,
        };
        const calcPB = {
            energi: 0,
            protein: 0,
            lemak: 0,
            karbohidrat: 0,
            serat: 0,
        };

        allBahanVarian.forEach((b) => {
            if (b.nutrisiPK) {
                calcPK.energi += b.nutrisiPK.energi || 0;
                calcPK.protein += b.nutrisiPK.protein || 0;
                calcPK.lemak += b.nutrisiPK.lemak || 0;
                calcPK.karbohidrat += b.nutrisiPK.karbohidrat || 0;
                calcPK.serat += b.nutrisiPK.serat || 0;
            }
            if (b.nutrisiPB) {
                calcPB.energi += b.nutrisiPB.energi || 0;
                calcPB.protein += b.nutrisiPB.protein || 0;
                calcPB.lemak += b.nutrisiPB.lemak || 0;
                calcPB.karbohidrat += b.nutrisiPB.karbohidrat || 0;
                calcPB.serat += b.nutrisiPB.serat || 0;
            }
        });

        result.push({
            jenis_alergi: jenis,
            siswa_pk: jmlPk,
            siswa_pb: jmlPb,
            total_siswa: jmlTotal,
            bahan_count: bahans.length,
            pk: {
                energi: Number(calcPK.energi.toFixed(1)),
                protein: Number(calcPK.protein.toFixed(1)),
                lemak: Number(calcPK.lemak.toFixed(1)),
                karbohidrat: Number(calcPK.karbohidrat.toFixed(1)),
                serat: Number(calcPK.serat.toFixed(1)),
            },
            pb: {
                energi: Number(calcPB.energi.toFixed(1)),
                protein: Number(calcPB.protein.toFixed(1)),
                lemak: Number(calcPB.lemak.toFixed(1)),
                karbohidrat: Number(calcPB.karbohidrat.toFixed(1)),
                serat: Number(calcPB.serat.toFixed(1)),
            },
        });
    });

    return result;
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
                        <table
                            class="w-full min-w-[900px] text-left text-xs border-collapse"
                        >
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

                <!-- Card Sebaran & Pemetaan Alergi Penerima Manfaat (Hanya Tampil Jika Ada Siswa Alergi) -->
                <Card
                    v-if="rekapAlergiMasterPm.length > 0"
                    className="bg-white border-rose-200/90 shadow-xs overflow-hidden"
                >
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-rose-100 bg-rose-50/50"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <CardTitle
                                        class="text-base sm:text-lg font-black text-rose-950 flex items-center gap-2"
                                    >
                                        <HeartPulse
                                            class="h-5 w-5 text-rose-600"
                                        />
                                        <span
                                            >Sebaran & Pemetaan Siswa Alergi
                                            Terdaftar</span
                                        >
                                    </CardTitle>
                                    <Badge
                                        variant="outline"
                                        class="bg-rose-100 text-rose-800 border-rose-300 font-extrabold text-xs"
                                    >
                                        Total
                                        {{ totalMasterPmSiswaAlergi }} Siswa
                                        Alergi
                                    </Badge>
                                </div>
                                <CardDescription
                                    class="text-xs sm:text-sm text-rose-800/80 mt-0.5"
                                >
                                    Daftar jenis alergi dan rincian kelompok
                                    sasaran terdampak untuk perencanaan menu
                                    substitusi MBG.
                                </CardDescription>
                            </div>
                            <span
                                class="text-xs font-bold text-rose-900 px-3 py-1 bg-white rounded-xl border border-rose-200 shadow-2xs self-start sm:self-auto"
                            >
                                {{ rekapAlergiMasterPm.length }} Jenis Alergen
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-5">
                        <div class="flex flex-wrap gap-3.5">
                            <div
                                v-for="(al, alIdx) in rekapAlergiMasterPm"
                                :key="alIdx"
                                class="flex-1 min-w-[280px] sm:min-w-[320px] rounded-xl border border-slate-200/90 bg-slate-50/40 p-3.5 space-y-3 flex flex-col justify-start hover:border-rose-300 transition-colors shadow-2xs"
                            >
                                <!-- Header Jenis Alergen -->
                                <div
                                    class="flex items-start justify-between gap-2 border-b border-slate-200/70 pb-2.5"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-2.5 w-2.5 rounded-full bg-rose-600 shrink-0"
                                        ></span>
                                        <h5
                                            class="text-xs font-black text-slate-900"
                                        >
                                            {{ al.jenis_alergi }}
                                        </h5>
                                    </div>
                                    <Badge
                                        class="bg-rose-600 text-white font-extrabold text-[10.5px] px-2 py-0.5 shadow-2xs"
                                    >
                                        {{ al.total }} Siswa
                                    </Badge>
                                </div>

                                <!-- Rincian Porsi (PK vs PB) -->
                                <div
                                    class="flex items-center justify-between text-xs bg-white p-2 rounded-lg border border-slate-200/70"
                                >
                                    <span
                                        class="text-[11px] text-slate-500 font-medium"
                                        >Distribusi Porsi:</span
                                    >
                                    <div
                                        class="flex items-center gap-2 font-bold text-[11px]"
                                    >
                                        <span
                                            class="text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200"
                                        >
                                            PK: {{ al.porsi_kecil }}
                                        </span>
                                        <span
                                            class="text-indigo-800 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200"
                                        >
                                            PB: {{ al.porsi_besar }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Kelompok Sasaran Terdampak (Rata Atas) -->
                                <div
                                    class="space-y-1.5 flex-1 flex flex-col justify-start"
                                >
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                    >
                                        Kelompok Sasaran ({{
                                            al.kelompok_list.length
                                        }}
                                        Lokasi):
                                    </p>
                                    <div
                                        class="space-y-1 min-h-[100px] max-h-[140px] overflow-y-auto pr-1 flex-1"
                                    >
                                        <div
                                            v-for="kel in al.kelompok_list"
                                            :key="kel.id"
                                            class="p-1.5 rounded-lg bg-white border border-slate-100 flex items-center justify-between text-xs gap-2"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="font-bold text-slate-900 truncate text-[11.5px]"
                                                >
                                                    {{ kel.nama_kelompok }}
                                                </p>
                                                <p
                                                    class="text-[10px] text-slate-400"
                                                >
                                                    {{ kel.desa_kelurahan }}
                                                </p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span
                                                    class="font-black text-rose-700 text-xs"
                                                >
                                                    {{ kel.total }} Siswa
                                                </span>
                                                <span
                                                    class="block text-[9.5px] text-slate-400 font-normal"
                                                >
                                                    (PK: {{ kel.porsi_kecil }},
                                                    PB: {{ kel.porsi_besar }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

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
                        <table
                            class="w-full min-w-[700px] text-left text-xs border-collapse"
                        >
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
                                    <th class="p-3.5 text-center">Total PM</th>
                                    <th class="p-3.5">Status Alergi</th>
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
                                        class="p-3.5 text-center font-black text-slate-900 text-sm"
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
            <!-- 3. SUB MENU 3: DAFTAR MENU (TABEL WORK ORDER & MENU MBG TERENCANA) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === 'daftar-menu'" class="space-y-6">
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
                                <p
                                    class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                                >
                                    Total Menu Terencana
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-extrabold text-blue-900 mt-0.5"
                                >
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
                                <p
                                    class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                                >
                                    Total Porsi Sasaran
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-extrabold text-emerald-900 mt-0.5"
                                >
                                    {{
                                        daftarMenuList
                                            .reduce(
                                                (acc, m) => acc + m.total_porsi,
                                                0,
                                            )
                                            .toLocaleString("id-ID")
                                    }}
                                    Porsi
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
                                <p
                                    class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                                >
                                    Rata-rata PK (Pagu: Rp 8rb)
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-bold text-amber-900 mt-0.5"
                                >
                                    Rp 7.850
                                    <span
                                        class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1 py-0.5 rounded"
                                        >Hemat</span
                                    >
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
                                <p
                                    class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                                >
                                    Rata-rata PB (Pagu: Rp 10rb)
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-bold text-indigo-900 mt-0.5"
                                >
                                    Rp 9.832
                                    <span
                                        class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1 py-0.5 rounded"
                                        >Hemat</span
                                    >
                                </h3>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Card Tabel Daftar Menu -->
                <Card
                    className="bg-white border-slate-200 shadow-xs overflow-hidden"
                >
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <CardTitle
                                className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                            >
                                <FileSpreadsheet class="h-5 w-5 text-primary" />
                                <span
                                    >Daftar Menu & Rekap Work Order (WO)
                                    SPPG</span
                                >
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Seluruh formulasi menu dan work order produksi
                                makanan bergizi yang telah disusun.
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="relative w-48 sm:w-60">
                                <Search
                                    class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                                />
                                <input
                                    v-model="searchDaftarMenu"
                                    type="text"
                                    placeholder="Cari No. WO / Menu..."
                                    class="w-full pl-9 pr-3 py-1.5 rounded-lg text-xs border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                            <select
                                v-model="statusFilterDaftarMenu"
                                class="px-3 py-1.5 rounded-lg text-xs border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium"
                            >
                                <option value="semua">Semua Status</option>
                                <option value="Terkonfirmasi">
                                    Terkonfirmasi
                                </option>
                                <option value="Siap Produksi">
                                    Siap Produksi
                                </option>
                                <option value="Draft">Draft WO</option>
                            </select>
                            <Link
                                :href="route('gizi.rancang-menu')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-white hover:bg-primary/90 transition-all shadow-xs flex items-center gap-1.5 cursor-pointer"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                <span>Rancang Menu Baru</span>
                            </Link>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table
                            class="w-full min-w-[900px] text-left text-xs border-collapse"
                        >
                            <thead
                                class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                            >
                                <tr>
                                    <th class="p-3.5">No. Work Order</th>
                                    <th class="p-3.5">Tanggal Distribusi</th>
                                    <th class="p-3.5">Nama & Komposisi Menu</th>
                                    <th class="p-3.5 text-center">
                                        Porsi Sasaran
                                    </th>
                                    <th class="p-3.5 text-center">
                                        Evaluasi AKG
                                    </th>
                                    <th class="p-3.5 text-right">
                                        Food Cost PK / PB
                                    </th>
                                    <th class="p-3.5 text-center">Status</th>
                                    <th class="p-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 text-slate-800"
                            >
                                <tr
                                    v-for="menu in filteredDaftarMenu"
                                    :key="menu.id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td
                                        class="p-3.5 font-mono font-bold text-primary"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ menu.id }}</span>
                                        </div>
                                        <span
                                            class="inline-block mt-0.5 text-[10px] text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded font-semibold"
                                        >
                                            {{ menu.siklus }}
                                        </span>
                                    </td>
                                    <td
                                        class="p-3.5 font-medium text-slate-600 whitespace-nowrap"
                                    >
                                        {{ formatTanggalIndo(menu.tanggal) }}
                                    </td>
                                    <td class="p-3.5 max-w-sm">
                                        <p
                                            class="font-bold text-slate-900 leading-snug"
                                        >
                                            {{ menu.nama }}
                                        </p>
                                        <div
                                            class="flex items-center gap-2 mt-1 text-[10.5px] text-slate-500 font-medium"
                                        >
                                            <span
                                                >PK: {{ menu.energi_pk }} kkal /
                                                {{ menu.protein_pk }}g
                                                Prot</span
                                            >
                                            <span>•</span>
                                            <span
                                                >PB: {{ menu.energi_pb }} kkal /
                                                {{ menu.protein_pb }}g
                                                Prot</span
                                            >
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <span
                                            class="font-black text-slate-900 block text-xs"
                                        >
                                            {{
                                                menu.total_porsi.toLocaleString(
                                                    "id-ID",
                                                )
                                            }}
                                            PM
                                        </span>
                                        <span
                                            class="text-[10px] text-slate-500 block"
                                        >
                                            {{ menu.porsi_pk }} PK /
                                            {{ menu.porsi_pb }} PB
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <Badge
                                            variant="outline"
                                            className="bg-emerald-50 text-emerald-700 border-emerald-300 font-extrabold text-[10.5px]"
                                        >
                                            ✓ MEMENUHI AKG
                                        </Badge>
                                    </td>
                                    <td
                                        class="p-3.5 text-right whitespace-nowrap"
                                    >
                                        <div
                                            class="text-[11px] font-bold text-amber-800"
                                        >
                                            PK: {{ formatRupiah(menu.cost_pk) }}
                                        </div>
                                        <div
                                            class="text-[11px] font-bold text-indigo-800"
                                        >
                                            PB: {{ formatRupiah(menu.cost_pb) }}
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <Badge
                                            variant="outline"
                                            :className="
                                                menu.status_wo ===
                                                'Terkonfirmasi'
                                                    ? 'bg-blue-50 text-blue-700 border-blue-300 font-bold text-[10.5px]'
                                                    : menu.status_wo ===
                                                        'Siap Produksi'
                                                      ? 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold text-[10.5px]'
                                                      : 'bg-amber-50 text-amber-700 border-amber-300 font-bold text-[10.5px]'
                                            "
                                        >
                                            {{ menu.status_wo }}
                                        </Badge>
                                    </td>
                                    <td
                                        class="p-3.5 text-center whitespace-nowrap"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-1.5"
                                        >
                                            <Link
                                                :href="
                                                    route('gizi.rancang-menu')
                                                "
                                                class="px-2 py-1 rounded text-[11px] font-bold text-primary hover:bg-primary/10 border border-primary/20 transition-colors"
                                                title="Lihat / Edit Formulasi Menu"
                                            >
                                                Edit Resep
                                            </Link>
                                            <Link
                                                :href="
                                                    route('keuangan.daftar-po')
                                                "
                                                class="px-2 py-1 rounded text-[11px] font-bold text-emerald-700 hover:bg-emerald-50 border border-emerald-200 transition-colors"
                                                title="Lihat Purchase Order Terkait"
                                            >
                                                Lihat PO
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredDaftarMenu.length === 0">
                                    <td
                                        colspan="8"
                                        class="p-8 text-center text-slate-400 font-semibold"
                                    >
                                        Tidak ada menu yang sesuai dengan filter
                                        pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 4. SUB MENU 4: RANCANG MENU (PERENCANAAN & FORMULASI GIZI) -->
            <!-- ========================================================================================= -->
            <div
                v-if="
                    activeSubMenu === 'rancang-menu' ||
                    activeSubMenu === 'buat-menu'
                "
                class="space-y-6"
            >
                <!-- Sub-tab pill bar for Rancang Menu -->
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
                                <CheckCircle2 class="h-3.5 w-3.5" /> Kuota PM
                                Terkunci
                            </span>
                            <span class="text-xs text-slate-300 font-medium">
                                📅 Tanggal Distribusi:
                                <strong class="text-white">{{
                                    formatTanggalIndo(tanggalRencana)
                                }}</strong>
                            </span>
                        </div>
                        <h3
                            class="text-base sm:text-lg font-black text-white leading-snug break-words"
                        >
                            {{ namaMenuAktif || "Nama Menu Belum Diisi" }}
                        </h3>
                        <div
                            v-if="
                                subMenuKomponen.energi ||
                                subMenuKomponen.protein ||
                                subMenuKomponen.lemak ||
                                subMenuKomponen.karbohidrat ||
                                subMenuKomponen.serat
                            "
                            class="flex items-center gap-1.5 flex-wrap text-[11px] font-medium"
                        >
                            <span
                                v-if="subMenuKomponen.energi"
                                class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-200 border border-amber-400/30"
                            >
                                ⚡ Energi: {{ subMenuKomponen.energi }}
                            </span>
                            <span
                                v-if="subMenuKomponen.protein"
                                class="px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-200 border border-rose-400/30"
                            >
                                🍗 Protein: {{ subMenuKomponen.protein }}
                            </span>
                            <span
                                v-if="subMenuKomponen.lemak"
                                class="px-2 py-0.5 rounded-md bg-yellow-500/20 text-yellow-200 border border-yellow-400/30"
                            >
                                🧈 Lemak: {{ subMenuKomponen.lemak }}
                            </span>
                            <span
                                v-if="subMenuKomponen.karbohidrat"
                                class="px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-200 border border-blue-400/30"
                            >
                                🌾 Karbo: {{ subMenuKomponen.karbohidrat }}
                            </span>
                            <span
                                v-if="subMenuKomponen.serat"
                                class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-200 border border-emerald-400/30"
                            >
                                🥗 Serat: {{ subMenuKomponen.serat }}
                            </span>
                        </div>
                        <div
                            class="flex items-center gap-3 flex-wrap text-xs text-blue-200"
                        >
                            <span
                                >🎯 Total PM:
                                <strong
                                    >{{
                                        totalPM.toLocaleString("id-ID")
                                    }}
                                    Porsi</strong
                                ></span
                            >
                            <span
                                >• PK:
                                <strong
                                    >{{
                                        totalPK.toLocaleString("id-ID")
                                    }}
                                    Porsi</strong
                                ></span
                            >
                            <span
                                >• PB:
                                <strong
                                    >{{
                                        totalPB.toLocaleString("id-ID")
                                    }}
                                    Porsi</strong
                                ></span
                            >
                            <span v-if="totalPKAlergi + totalPBAlergi > 0"
                                >• Alergi:
                                <strong class="text-rose-300"
                                    >{{
                                        totalPKAlergi + totalPBAlergi
                                    }}
                                    Siswa</strong
                                ></span
                            >
                            <span
                                >• Terjadwal:
                                <strong class="text-slate-300"
                                    >{{
                                        woKelompokList.length
                                    }}
                                    Kelompok</strong
                                ></span
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
                    <Card
                        className="bg-white border-slate-200 shadow-xs overflow-hidden"
                    >
                        <CardHeader
                            className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/70"
                        >
                            <div
                                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                            >
                                <div>
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <CardTitle
                                            class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                                        >
                                            <FileSpreadsheet
                                                class="h-5 w-5 text-primary"
                                            />
                                            <span>Perencanaan Produksi</span>
                                        </CardTitle>
                                        <Badge
                                            variant="outline"
                                            class="bg-blue-50 text-blue-700 border-blue-300 font-extrabold text-xs"
                                        >
                                            Langkah 1 dari 3
                                        </Badge>
                                    </div>
                                    <CardDescription
                                        class="text-xs sm:text-sm mt-0.5"
                                    >
                                        Penetapan jadwal distribusi menu,
                                        penamaan paket MBG, dan penguncian kuota
                                        Penerima Manfaat (PM) resmi SPPG.
                                    </CardDescription>
                                </div>
                                <!-- <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        @click="handleMulaiFormulasiWo"
                                        className="bg-primary hover:bg-primary/90 text-white text-xs font-bold px-4 h-9 flex items-center gap-1.5 shadow-xs cursor-pointer"
                                    >
                                        <span
                                            >Mulai Formulasi Gizi (Langkah
                                            2)</span
                                        >
                                        <ArrowRight class="h-3.5 w-3.5" />
                                    </Button>
                                </div> -->
                            </div>
                        </CardHeader>
                        <CardContent className="p-5 sm:p-6 space-y-6">
                            <!-- Form Identitas Perencanaan Produksi (1 Baris Bagi 3) -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <!-- Kolom 1: No. Perencanaan Produksi -->
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700 block truncate"
                                    >
                                        No. Perencanaan Produksi:
                                    </label>
                                    <input
                                        type="text"
                                        :value="woNo"
                                        readonly
                                        disabled
                                        class="w-full text-xs font-mono font-black text-slate-800 rounded-lg border-slate-200 bg-slate-100/80 p-2.5 cursor-not-allowed select-all"
                                        title="Nomor Perencanaan Produksi otomatis mengacu pada tanggal distribusi kalender menu"
                                    />
                                </div>

                                <!-- Kolom 2: Tanggal Distribusi Menu -->
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700 block truncate"
                                    >
                                        Tanggal Distribusi Menu:
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        v-model="tanggalRencana"
                                        required
                                        class="w-full text-xs font-bold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2.5 bg-white"
                                    />
                                </div>

                                <!-- Kolom 3: Nama Menu Produksi MBG -->
                                <div class="space-y-1.5">
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <label
                                            class="text-xs font-bold text-slate-700 truncate"
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
                                            class="text-[10.5px] font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer shrink-0 truncate max-w-[150px]"
                                            :title="
                                                'Gunakan Rekomendasi Kalender: ' +
                                                menuSaranDariKalender.namaMenu
                                            "
                                        >
                                            <Sparkles
                                                class="h-3 w-3 shrink-0"
                                            />
                                            <span class="truncate"
                                                >Gunakan Kalender</span
                                            >
                                        </button>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="namaMenuAktif"
                                        required
                                        placeholder="Contoh: Nasi Liwet Sunda, Ayam Goreng..."
                                        class="w-full text-xs font-bold text-slate-900 rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2.5 bg-white"
                                    />
                                </div>
                            </div>

                            <!-- Rincian Sub Menu Komponen Gizi (Energi, Protein, Lemak, Karbohidrat, Serat) -->
                            <div
                                class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/90 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-6 w-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-xs"
                                        >
                                            ✦
                                        </div>
                                        <div>
                                            <h4
                                                class="text-xs font-black text-slate-900"
                                            >
                                                Rincian Sub Menu (Komponen Gizi
                                                MBG)
                                            </h4>
                                            <p
                                                class="text-[10.5px] text-slate-500"
                                            >
                                                Input rincian nama hidangan per
                                                masing-masing komponen
                                                makronutrisi & serat pangan.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"
                                >
                                    <!-- Sub Menu Energi -->
                                    <div
                                        class="space-y-1 bg-white p-2.5 rounded-xl border border-amber-200/90 shadow-2xs"
                                    >
                                        <label
                                            class="text-[11px] font-black text-amber-900 flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-amber-500"
                                            ></span>
                                            <span>Energi</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="subMenuKomponen.energi"
                                            placeholder="Contoh: Nasi Putih Gurih"
                                            class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-amber-500 focus:border-amber-500 p-2 bg-slate-50/50"
                                        />
                                    </div>

                                    <!-- Sub Menu Protein -->
                                    <div
                                        class="space-y-1 bg-white p-2.5 rounded-xl border border-rose-200/90 shadow-2xs"
                                    >
                                        <label
                                            class="text-[11px] font-black text-rose-900 flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-rose-500"
                                            ></span>
                                            <span>Protein</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="subMenuKomponen.protein"
                                            placeholder="Contoh: Ayam Goreng Lengkuas"
                                            class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-rose-500 focus:border-rose-500 p-2 bg-slate-50/50"
                                        />
                                    </div>

                                    <!-- Sub Menu Lemak -->
                                    <div
                                        class="space-y-1 bg-white p-2.5 rounded-xl border border-yellow-200/90 shadow-2xs"
                                    >
                                        <label
                                            class="text-[11px] font-black text-yellow-900 flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-yellow-500"
                                            ></span>
                                            <span>Lemak</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="subMenuKomponen.lemak"
                                            placeholder="Contoh: Tahu Bacem Goreng"
                                            class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-yellow-500 focus:border-yellow-500 p-2 bg-slate-50/50"
                                        />
                                    </div>

                                    <!-- Sub Menu Karbohidrat -->
                                    <div
                                        class="space-y-1 bg-white p-2.5 rounded-xl border border-blue-200/90 shadow-2xs"
                                    >
                                        <label
                                            class="text-[11px] font-black text-blue-900 flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-blue-500"
                                            ></span>
                                            <span>Karbohidrat</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="
                                                subMenuKomponen.karbohidrat
                                            "
                                            placeholder="Contoh: Nasi Liwet / Ubi"
                                            class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-blue-500 focus:border-blue-500 p-2 bg-slate-50/50"
                                        />
                                    </div>

                                    <!-- Sub Menu Serat -->
                                    <div
                                        class="space-y-1 bg-white p-2.5 rounded-xl border border-emerald-200/90 shadow-2xs"
                                    >
                                        <label
                                            class="text-[11px] font-black text-emerald-900 flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-emerald-500"
                                            ></span>
                                            <span>Serat</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="subMenuKomponen.serat"
                                            placeholder="Contoh: Lalapan Sayur & Melon"
                                            class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-emerald-500 focus:border-emerald-500 p-2 bg-slate-50/50"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Ringkasan Kuota PM Fix Berdasarkan Tanggal Work Order -->
                            <div
                                class="space-y-3 pt-4 border-t border-slate-200"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4
                                            class="text-sm font-black text-slate-900 flex items-center gap-2"
                                        >
                                            <Users
                                                class="h-4 w-4 text-primary"
                                            />
                                            <span
                                                >Data Kuota Penerima Manfaat
                                                (PM) Fix per Tanggal
                                                Distribusi</span
                                            >
                                        </h4>
                                        <p
                                            class="text-xs text-slate-500 mt-0.5"
                                        >
                                            Kuota porsi terkunci otomatis
                                            berdasarkan data rekapitulasi siswa
                                            & penerima aktif SPPG pada tanggal
                                            tersebut.
                                        </p>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        class="bg-emerald-50 text-emerald-700 border-emerald-300 font-extrabold text-xs"
                                    >
                                        <CheckCircle2 class="h-3 w-3 mr-1" />
                                        Terverifikasi
                                    </Badge>
                                </div>

                                <!-- 4 Metric Cards Kuota PM Fix -->
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-4 gap-3.5"
                                >
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
                                            SD 4-6, SMP, SMA, Guru, Tendik,
                                            Bumil & Busui
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
                                        <div
                                            class="flex items-center gap-2 flex-wrap"
                                        >
                                            <h5
                                                class="text-xs font-bold text-slate-800 uppercase tracking-wider"
                                            >
                                                Daftar Kelompok Sasaran
                                                Distribusi ({{
                                                    woKelompokList.length
                                                }}
                                                Kelompok)
                                            </h5>
                                            <Badge
                                                variant="outline"
                                                class="text-[11px] font-extrabold bg-emerald-50 text-emerald-800 border-emerald-300"
                                            >
                                                <UserCheck
                                                    class="h-3 w-3 mr-1"
                                                />
                                                {{
                                                    kelompokMenerimaAktif.length
                                                }}
                                                Menerima
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
                                        <p
                                            class="text-[11px] text-slate-500 mt-0.5"
                                        >
                                            Kelompok yang dinyatakan
                                            <strong>"Tidak Menerima"</strong>
                                            kuotanya otomatis dinolkan dari
                                            perhitungan Work Order ini.
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
                                            <span>Kembalikan</span>
                                        </Button>
                                    </div>
                                </div>
                                <div
                                    class="rounded-xl border border-slate-200 overflow-x-auto"
                                >
                                    <table
                                        class="w-full min-w-[650px] text-left text-xs border-collapse"
                                    >
                                        <thead
                                            class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                        >
                                            <tr>
                                                <th class="p-3 text-center">
                                                    Status
                                                </th>
                                                <th class="p-3">
                                                    Nama Kelompok Sasaran
                                                </th>
                                                <th class="p-3">Kategori</th>
                                                <th class="p-3 text-center">
                                                    Porsi Kecil (PK)
                                                </th>
                                                <th class="p-3 text-center">
                                                    Porsi Besar (PB)
                                                </th>
                                                <th class="p-3 text-center">
                                                    Total PM
                                                </th>
                                                <th class="p-3">
                                                    Status Alergi
                                                </th>
                                                <th class="p-3 text-center">
                                                    Aksi
                                                </th>
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
                                                        : 'hover:bg-slate-50/60',
                                                ]"
                                            >
                                                <!-- Status Badge -->
                                                <td
                                                    class="p-3 text-center align-middle"
                                                >
                                                    <span
                                                        v-if="
                                                            k.status_menerima !==
                                                            false
                                                        "
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"
                                                    >
                                                        <UserCheck
                                                            class="h-3 w-3 mr-1"
                                                        />
                                                        Menerima
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300"
                                                    >
                                                        <UserX
                                                            class="h-3 w-3 mr-1"
                                                        />
                                                        Tidak Menerima
                                                    </span>
                                                </td>

                                                <!-- Nama Kelompok -->
                                                <td
                                                    class="p-3 font-bold align-middle"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-500 line-through'
                                                            : 'text-slate-900'
                                                    "
                                                >
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
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-400'
                                                            : 'text-amber-900 bg-amber-50/20'
                                                    "
                                                >
                                                    {{ k.total_porsi_kecil }}
                                                </td>

                                                <!-- Porsi Besar -->
                                                <td
                                                    class="p-3 text-center align-middle font-bold"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-400'
                                                            : 'text-indigo-900 bg-indigo-50/20'
                                                    "
                                                >
                                                    {{ k.total_porsi_besar }}
                                                </td>

                                                <!-- Total PM -->
                                                <td
                                                    class="p-3 text-center font-black text-sm align-middle"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-400'
                                                            : 'text-slate-900'
                                                    "
                                                >
                                                    {{ k.total_penerima }}
                                                </td>

                                                <!-- Alergi (Detail Breakdown per Jenis) -->
                                                <td class="p-3 align-middle">
                                                    <div
                                                        v-if="
                                                            k.keterangan_alergi &&
                                                            k.keterangan_alergi
                                                                .length > 0
                                                        "
                                                        class="space-y-1"
                                                    >
                                                        <div
                                                            v-for="(
                                                                al, alIdx
                                                            ) in k.keterangan_alergi"
                                                            :key="alIdx"
                                                            class="text-[11px] font-bold"
                                                            :class="
                                                                k.status_menerima ===
                                                                false
                                                                    ? 'text-slate-400'
                                                                    : 'text-rose-700'
                                                            "
                                                        >
                                                            ⚠️
                                                            {{
                                                                al.jenis_alergi
                                                            }}:
                                                            <span
                                                                class="font-black text-rose-900 ml-0.5"
                                                            >
                                                                {{
                                                                    (Number(
                                                                        al.porsi_kecil,
                                                                    ) || 0) +
                                                                    (Number(
                                                                        al.porsi_besar,
                                                                    ) || 0)
                                                                }}
                                                            </span>
                                                            <span
                                                                class="text-[10px] text-slate-500 font-normal ml-1"
                                                            >
                                                                (PK:
                                                                {{
                                                                    al.porsi_kecil ||
                                                                    0
                                                                }}, PB:
                                                                {{
                                                                    al.porsi_besar ||
                                                                    0
                                                                }})
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-else-if="
                                                            (k.alergi_porsi_kecil ||
                                                                0) +
                                                                (k.alergi_porsi_besar ||
                                                                    0) >
                                                            0
                                                        "
                                                        class="text-[11px] font-bold"
                                                        :class="
                                                            k.status_menerima ===
                                                            false
                                                                ? 'text-slate-400'
                                                                : 'text-rose-700'
                                                        "
                                                    >
                                                        ⚠️
                                                        {{
                                                            (k.alergi_porsi_kecil ||
                                                                0) +
                                                            (k.alergi_porsi_besar ||
                                                                0)
                                                        }}
                                                        Alergi
                                                        <span
                                                            class="block text-[10px] text-slate-500 font-normal"
                                                        >
                                                            PK:
                                                            {{
                                                                k.alergi_porsi_kecil ||
                                                                0
                                                            }}
                                                            • PB:
                                                            {{
                                                                k.alergi_porsi_besar ||
                                                                0
                                                            }}
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
                                                <td
                                                    class="p-2 text-center align-middle"
                                                >
                                                    <div
                                                        class="flex items-center justify-center gap-1.5"
                                                    >
                                                        <button
                                                            type="button"
                                                            @click="
                                                                handleOpenModalEditPm(
                                                                    k,
                                                                )
                                                            "
                                                            class="p-2 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors flex items-center justify-center cursor-pointer shadow-2xs"
                                                            title="Edit Detail PM per Sub-Sub Kategori"
                                                        >
                                                            <Edit3
                                                                class="h-3.5 w-3.5"
                                                            />
                                                        </button>

                                                        <button
                                                            v-if="
                                                                k.status_menerima !==
                                                                false
                                                            "
                                                            type="button"
                                                            @click="
                                                                handleToggleStatusMenerima(
                                                                    k,
                                                                )
                                                            "
                                                            class="p-2 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors flex items-center justify-center cursor-pointer shadow-2xs"
                                                            title="Nyatakan Tidak Menerima Distribusi"
                                                        >
                                                            <UserX
                                                                class="h-3.5 w-3.5"
                                                            />
                                                        </button>

                                                        <button
                                                            v-else
                                                            type="button"
                                                            @click="
                                                                handleToggleStatusMenerima(
                                                                    k,
                                                                )
                                                            "
                                                            class="p-2 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors flex items-center justify-center cursor-pointer shadow-2xs"
                                                            title="Aktifkan Kembali Status Menerima"
                                                        >
                                                            <UserCheck
                                                                class="h-3.5 w-3.5"
                                                            />
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
                                class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3.5"
                            >
                                <div class="text-xs text-slate-500">
                                    Pastikan tanggal, nama menu, dan status
                                    penerima sasaran sudah sesuai sebelum
                                    melanjutkan.
                                </div>
                                <Button
                                    type="button"
                                    @click="handleMulaiFormulasiWo"
                                    className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-6 h-11 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer w-full sm:w-auto shrink-0 text-center"
                                >
                                    <span
                                        >Mulai Formulasi Gizi (Langkah 2)</span
                                    >
                                    <ArrowRight class="h-4 w-4 shrink-0" />
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
                            <div
                                class="flex items-start justify-between border-b border-slate-100 pb-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0"
                                    >
                                        <School class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-base font-black text-slate-900"
                                        >
                                            Edit Rincian PM:
                                            {{ editingKelompok?.nama_kelompok }}
                                        </h3>
                                        <p
                                            class="text-xs text-slate-500 mt-0.5"
                                        >
                                            Kategori:
                                            <strong class="text-slate-800">{{
                                                editingKelompok?.kategori
                                            }}</strong>
                                            • Wilayah:
                                            {{
                                                editingKelompok?.desa_kelurahan
                                            }}, {{ editingKelompok?.kecamatan }}
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
                                    <h4
                                        class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                    >
                                        Rincian Jumlah Siswa / Penerima per
                                        Jenjang:
                                    </h4>
                                    <span class="text-xs text-slate-500">
                                        Format input: Laki-laki (L) + Perempuan
                                        (P)
                                    </span>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-200 overflow-x-auto max-h-60 overflow-y-auto"
                                >
                                    <table
                                        class="w-full min-w-[500px] text-left text-xs border-collapse"
                                    >
                                        <thead
                                            class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px] sticky top-0 z-10 shadow-2xs"
                                        >
                                            <tr>
                                                <th class="p-3">
                                                    Sub-Kategori / Jenjang
                                                </th>
                                                <th class="p-3">
                                                    Peruntukan Porsi
                                                </th>
                                                <th
                                                    class="p-3 text-center min-w-[100px]"
                                                >
                                                    Laki-laki (L)
                                                </th>
                                                <th
                                                    class="p-3 text-center min-w-[100px]"
                                                >
                                                    Perempuan (P)
                                                </th>
                                                <th class="p-3 text-right">
                                                    Subtotal
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-slate-100 text-slate-800"
                                        >
                                            <tr
                                                v-for="(
                                                    r, rIdx
                                                ) in editFormRincian"
                                                :key="r.sub_kategori || rIdx"
                                                class="hover:bg-slate-50/60"
                                            >
                                                <td
                                                    class="p-3 font-bold text-slate-900"
                                                >
                                                    {{ r.sub_kategori }}
                                                </td>
                                                <td class="p-3">
                                                    <Badge
                                                        variant="outline"
                                                        :class="[
                                                            'font-extrabold text-[10px]',
                                                            r.jenis_porsi ===
                                                            'Porsi Kecil'
                                                                ? 'bg-amber-50 text-amber-800 border-amber-300'
                                                                : 'bg-indigo-50 text-indigo-800 border-indigo-300',
                                                        ]"
                                                    >
                                                        {{ r.jenis_porsi }}
                                                    </Badge>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        v-model.number="
                                                            r.jumlah_laki_laki
                                                        "
                                                        class="w-20 text-center text-xs font-bold rounded-lg border-slate-300 p-1.5 focus:ring-primary focus:border-primary"
                                                    />
                                                </td>
                                                <td class="p-2 text-center">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        v-model.number="
                                                            r.jumlah_perempuan
                                                        "
                                                        class="w-20 text-center text-xs font-bold rounded-lg border-slate-300 p-1.5 focus:ring-primary focus:border-primary"
                                                    />
                                                </td>
                                                <td
                                                    class="p-3 text-right font-black text-slate-900"
                                                >
                                                    {{
                                                        (Number(
                                                            r.jumlah_laki_laki,
                                                        ) || 0) +
                                                        (Number(
                                                            r.jumlah_perempuan,
                                                        ) || 0)
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Input Khusus Kuota Siswa Alergi (Jenis Alergen Bersumber dari Master Data PM) -->
                                <div
                                    class="p-4 rounded-xl bg-rose-50/60 border border-rose-200/80 space-y-3"
                                >
                                    <div>
                                        <h5
                                            class="text-xs font-bold text-rose-900 flex items-center gap-1.5"
                                        >
                                            <AlertCircle
                                                class="h-4 w-4 text-rose-600"
                                            />
                                            <span
                                                >Penyesuaian Jumlah Siswa Alergi
                                                (Membutuhkan Menu
                                                Substitusi)</span
                                            >
                                        </h5>
                                        <p
                                            class="text-[11px] text-rose-700 mt-0.5"
                                        >
                                            Daftar jenis alergen bersumber dari
                                            master data
                                            <strong>Penerima Manfaat</strong>.
                                            Anda dapat menyesuaikan jumlah kuota
                                            porsi (PK / PB) untuk Work Order ini
                                            jika ada perubahan kehadiran.
                                        </p>
                                    </div>

                                    <!-- Tabel Daftar Alergi Terdaftar -->
                                    <div
                                        v-if="
                                            editFormKeteranganAlergi.length > 0
                                        "
                                        class="rounded-lg border border-rose-200 bg-white overflow-x-auto"
                                    >
                                        <table
                                            class="w-full min-w-[500px] text-left text-xs border-collapse"
                                        >
                                            <thead
                                                class="bg-rose-100/60 text-rose-900 font-bold border-b border-rose-200 uppercase text-[10px]"
                                            >
                                                <tr>
                                                    <th class="p-3">
                                                        Jenis Alergen (Master
                                                        PM)
                                                    </th>
                                                    <th
                                                        class="p-3 text-center min-w-[110px]"
                                                    >
                                                        Porsi Kecil (PK)
                                                    </th>
                                                    <th
                                                        class="p-3 text-center min-w-[110px]"
                                                    >
                                                        Porsi Besar (PB)
                                                    </th>
                                                    <th class="p-3 text-right">
                                                        Subtotal Alergi
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="divide-y divide-rose-100 text-slate-800"
                                            >
                                                <tr
                                                    v-for="(
                                                        alItem, alIdx
                                                    ) in editFormKeteranganAlergi"
                                                    :key="alIdx"
                                                    class="hover:bg-rose-50/40"
                                                >
                                                    <td
                                                        class="p-3 font-bold text-slate-900 align-middle"
                                                    >
                                                        <div
                                                            class="flex items-center gap-2"
                                                        >
                                                            <span
                                                                class="h-2 w-2 rounded-full bg-rose-500 shrink-0"
                                                            ></span>
                                                            <span
                                                                class="text-xs font-black text-rose-950"
                                                                >{{
                                                                    alItem.jenis_alergi
                                                                }}</span
                                                            >
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="p-2 text-center align-middle"
                                                    >
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            :max="modalTotalPk"
                                                            v-model.number="
                                                                alItem.porsi_kecil
                                                            "
                                                            class="w-20 text-center text-xs font-bold rounded-lg border-rose-300 bg-rose-50/30 p-1.5 focus:ring-rose-400 focus:border-rose-400"
                                                        />
                                                    </td>
                                                    <td
                                                        class="p-2 text-center align-middle"
                                                    >
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            :max="modalTotalPb"
                                                            v-model.number="
                                                                alItem.porsi_besar
                                                            "
                                                            class="w-20 text-center text-xs font-bold rounded-lg border-rose-300 bg-rose-50/30 p-1.5 focus:ring-rose-400 focus:border-rose-400"
                                                        />
                                                    </td>
                                                    <td
                                                        class="p-3 text-right font-black text-rose-900 text-xs align-middle"
                                                    >
                                                        {{
                                                            (Number(
                                                                alItem.porsi_kecil,
                                                            ) || 0) +
                                                            (Number(
                                                                alItem.porsi_besar,
                                                            ) || 0)
                                                        }}
                                                        Siswa
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div
                                        v-else
                                        class="p-4 text-center text-slate-500 text-xs bg-white rounded-xl border border-dashed border-rose-200 space-y-1"
                                    >
                                        <p class="font-bold text-slate-700">
                                            Tidak ada riwayat alergi yang
                                            terdaftar untuk kelompok sasaran
                                            ini.
                                        </p>
                                        <p class="text-[11px] text-slate-500">
                                            Penambahan atau pengelolaan jenis
                                            alergen dilakukan melalui master
                                            data
                                            <strong class="text-slate-800"
                                                >Penerima Manfaat</strong
                                            >.
                                        </p>
                                    </div>
                                </div>

                                <!-- Live Summary Bar -->
                                <div
                                    class="p-3.5 rounded-xl bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 text-xs"
                                >
                                    <div>
                                        <span class="text-slate-400"
                                            >Hasil Rekapitulasi:
                                        </span>
                                        <strong class="text-white ml-1"
                                            >Total {{ modalTotalPm }} PM</strong
                                        >
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-amber-300 font-bold"
                                            >PK: {{ modalTotalPk }} Porsi</span
                                        >
                                        <span class="text-indigo-300 font-bold"
                                            >PB: {{ modalTotalPb }} Porsi</span
                                        >
                                        <span class="text-rose-300 font-bold"
                                            >Alergi:
                                            {{ modalGrandTotalAlergi }} Siswa
                                            (PK: {{ modalTotalAlergiPk }}, PB:
                                            {{ modalTotalAlergiPb }})</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div
                                class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100"
                            >
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
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <CardTitle
                                            class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                                        >
                                            <ClipboardList
                                                class="h-5 w-5 text-primary"
                                            />
                                            <span>Formulasi Gizi</span>
                                        </CardTitle>
                                        <Badge
                                            variant="outline"
                                            class="bg-blue-50 text-blue-700 border-blue-300 font-extrabold text-xs"
                                        >
                                            Langkah 2 dari 3
                                        </Badge>
                                    </div>
                                    <CardDescription
                                        class="text-xs sm:text-sm mt-0.5"
                                    >
                                        Penentuan gramasi bahan makanan dari
                                        TKPI 2020, evaluasi real-time kecukupan
                                        gizi AKG BGN, dan analisis pagu food
                                        cost.
                                    </CardDescription>
                                </div>
                                <!-- <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        @click="handleAjukanDraftPo"
                                        className="bg-primary text-white hover:bg-primary/90 text-xs font-bold px-4 h-9 flex items-center gap-1.5 shadow-xs cursor-pointer"
                                    >
                                        <Send class="h-3.5 w-3.5" />
                                        <span
                                            >Ajukan PO ke Akuntan (Langkah
                                            3)</span
                                        >
                                    </Button>
                                </div> -->
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
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs"
                                        >
                                            <AlertTriangle class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center gap-2 flex-wrap"
                                            >
                                                <h4
                                                    class="text-sm font-black text-amber-950"
                                                >
                                                    Peringatan Alergen Menu:
                                                    Terdeteksi Bahan yang
                                                    Berpotensi Alergi
                                                </h4>
                                                <Badge
                                                    class="bg-rose-600 text-white font-extrabold text-[10px] px-2 py-0.5"
                                                >
                                                    {{
                                                        analisaAlergiMenu
                                                            .conflicts.length
                                                    }}
                                                    Alergen Teridentifikasi
                                                </Badge>
                                            </div>
                                            <p
                                                class="text-xs text-amber-800 mt-0.5"
                                            >
                                                Nama menu
                                                <em>"{{ namaMenuAktif }}"</em>
                                                mengandung bahan yang cocok
                                                dengan data riwayat alergi
                                                siswa/penerima aktif hari ini.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grid Kartu Benturan Alergen & Rekomendasi Substitusi (Auto-fit tanpa space kosong) -->
                                <div
                                    class="grid gap-3 pt-1"
                                    :class="[
                                        analisaAlergiMenu.conflicts.length === 1
                                            ? 'grid-cols-1'
                                            : analisaAlergiMenu.conflicts
                                                    .length === 3
                                              ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
                                              : 'grid-cols-1 md:grid-cols-2',
                                    ]"
                                >
                                    <div
                                        v-for="(
                                            cf, cfIdx
                                        ) in analisaAlergiMenu.conflicts"
                                        :key="cfIdx"
                                        class="p-3.5 rounded-xl bg-white border border-amber-200/80 shadow-2xs space-y-2 flex flex-col justify-between transition-all"
                                        :class="{
                                            'md:col-span-2 lg:col-span-1':
                                                analisaAlergiMenu.conflicts
                                                    .length === 3 &&
                                                cfIdx === 2,
                                            'md:col-span-2':
                                                analisaAlergiMenu.conflicts
                                                    .length %
                                                    2 !==
                                                    0 &&
                                                analisaAlergiMenu.conflicts
                                                    .length !== 3 &&
                                                cfIdx ===
                                                    analisaAlergiMenu.conflicts
                                                        .length -
                                                        1,
                                        }"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-2 border-b border-amber-100 pb-2"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="px-2 py-0.5 rounded text-[11px] font-black bg-rose-100 text-rose-800 border border-rose-200"
                                                >
                                                    {{ cf.jenis_alergi }}
                                                </span>
                                                <span
                                                    class="text-[11px] text-slate-500"
                                                >
                                                    (Kata kunci menu:
                                                    <strong
                                                        >"{{
                                                            cf.keyword
                                                        }}"</strong
                                                    >)
                                                </span>
                                            </div>
                                            <span
                                                class="text-xs font-black text-rose-900 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200"
                                            >
                                                {{ cf.total }} Porsi
                                            </span>
                                        </div>

                                        <div class="text-xs space-y-1">
                                            <div
                                                class="flex items-center justify-between text-slate-600"
                                            >
                                                <span
                                                    >Porsi Pengganti
                                                    Diperlukan:</span
                                                >
                                                <strong class="text-slate-900"
                                                    >PK: {{ cf.porsi_kecil }} &
                                                    PB:
                                                    {{ cf.porsi_besar }}</strong
                                                >
                                            </div>
                                            <div
                                                class="space-y-1.5 pt-1 border-t border-amber-100/60"
                                            >
                                                <div
                                                    class="flex items-center gap-1.5 text-[10.5px] font-bold text-slate-600"
                                                >
                                                    <School
                                                        class="h-3.5 w-3.5 text-slate-400 shrink-0"
                                                    />
                                                    <span
                                                        >Kelompok Terdampak ({{
                                                            cf.kelompok_names
                                                                .length
                                                        }}):</span
                                                    >
                                                </div>
                                                <div
                                                    class="flex flex-wrap gap-1"
                                                >
                                                    <span
                                                        v-for="(
                                                            kn, knIdx
                                                        ) in cf.kelompok_names"
                                                        :key="knIdx"
                                                        class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-950 border border-amber-200/90 text-[10.5px] font-bold shadow-2xs"
                                                    >
                                                        {{ kn }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Rekomendasi Ahli Gizi -->
                                        <div
                                            class="p-2.5 rounded-lg bg-emerald-50/80 border border-emerald-200 text-emerald-950 flex items-start gap-2 text-xs"
                                        >
                                            <Lightbulb
                                                class="h-4 w-4 text-emerald-600 shrink-0 mt-0.5"
                                            />
                                            <div>
                                                <strong
                                                    class="text-emerald-900 font-bold block text-[11px]"
                                                    >Rekomendasi Bahan
                                                    Pengganti:</strong
                                                >
                                                <span
                                                    class="text-[11px] text-emerald-800 font-medium leading-relaxed"
                                                >
                                                    Tambahkan formulasi bahan
                                                    pangan
                                                    <em>{{
                                                        cf.rekomendasi
                                                    }}</em>
                                                    dengan peruntukan porsi
                                                    <strong
                                                        >"Alergi:
                                                        {{
                                                            cf.jenis_alergi
                                                        }}"</strong
                                                    >
                                                    untuk {{ cf.total }} porsi.
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Case 2: Ada Siswa Alergi Aktif tapi tidak ada kata kunci bentrok langsung di judul menu (Info Pengingat) -->
                            <div
                                v-else-if="
                                    analisaAlergiMenu.totalSiswaAlergi > 0
                                "
                                class="p-4 rounded-2xl bg-sky-50/80 border border-sky-200 shadow-xs space-y-3"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-9 w-9 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0"
                                        >
                                            <Info class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h4
                                                class="text-xs sm:text-sm font-bold text-sky-950 flex items-center gap-2"
                                            >
                                                <span
                                                    >Pengingat Rekapitulasi
                                                    Alergi PM Terjadwal ({{
                                                        analisaAlergiMenu.totalSiswaAlergi
                                                    }}
                                                    Siswa)</span
                                                >
                                            </h4>
                                            <p
                                                class="text-xs text-sky-800 mt-0.5"
                                            >
                                                Meskipun nama menu tidak
                                                menyebutkan alergen langsung,
                                                terdapat siswa aktif dengan
                                                riwayat alergi khusus. Pastikan
                                                bahan dan bumbu aman dari
                                                kontaminasi silang.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2 pt-1">
                                    <div
                                        v-for="(
                                            al, alIdx
                                        ) in analisaAlergiMenu.activeAlergiList"
                                        :key="alIdx"
                                        class="px-3 py-1.5 rounded-xl bg-white border border-sky-200 text-xs flex items-center gap-2 shadow-2xs"
                                    >
                                        <span class="font-bold text-sky-950"
                                            >{{ al.jenis_alergi }}:</span
                                        >
                                        <span class="font-black text-rose-700"
                                            >{{ al.total }} Siswa</span
                                        >
                                        <span class="text-[10px] text-slate-500"
                                            >(PK: {{ al.porsi_kecil }}, PB:
                                            {{ al.porsi_besar }})</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Case 3: Zero Alergi Terdaftar pada Kelompok Aktif (Green Safe) -->
                            <div
                                v-else
                                class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center gap-3 text-xs text-emerald-900"
                            >
                                <CheckCircle2
                                    class="h-5 w-5 text-emerald-600 shrink-0"
                                />
                                <div>
                                    <strong class="font-bold"
                                        >Status Alergen Aman:</strong
                                    >
                                    Seluruh {{ totalPM }} penerima manfaat pada
                                    kelompok aktif hari ini tidak memiliki
                                    catatan alergi khusus. Semua porsi dapat
                                    disiapkan dengan formulasi menu standar.
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
                                    class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 lg:max-w-2xl w-full"
                                >
                                    <!-- Searchable Combobox Selector (Lebar & Nyaman) -->
                                    <div
                                        class="relative flex-1 min-w-0 w-full"
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
                                        className="h-11 px-5 text-sm bg-blue-600 hover:bg-blue-700 text-white font-extrabold cursor-pointer shrink-0 rounded-xl shadow-xs w-full sm:w-auto flex items-center justify-center"
                                    >
                                        <Plus class="h-4 w-4 mr-1.5 shrink-0" />
                                        Tambah
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tabel Detail Perhitungan Gramasi & Draft PO -->
                    <Card
                        className="bg-white border-slate-200 shadow-xs overflow-hidden"
                    >
                        <!-- Tabel Bahan Pangan Terpilih & Formulasi Resep -->
                        <div class="overflow-x-auto">
                            <table
                                class="w-full min-w-[1050px] text-left text-xs border-collapse"
                            >
                                <thead
                                    class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3 text-center w-10">No</th>
                                        <th class="p-3">Bahan (TKPI 2020)</th>
                                        <th class="p-3 min-w-[170px]">
                                            Nama Bahan (PO Akuntan)
                                        </th>
                                        <th class="p-3">Kategori</th>
                                        <th
                                            class="p-3 text-center min-w-[170px]"
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
                                            colspan="13"
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
                                        <!-- No -->
                                        <td
                                            class="p-3 text-center align-top pt-4 font-bold text-slate-500"
                                        >
                                            {{ idx + 1 }}
                                        </td>

                                        <!-- Bahan (TKPI) -->
                                        <td
                                            class="p-3 font-bold text-slate-900 align-top pt-4"
                                        >
                                            <div>{{ b.nama }}</div>
                                            <span
                                                v-if="b.alergen"
                                                class="block text-[9.5px] text-amber-700 font-normal mt-0.5"
                                                >Alergen: {{ b.alergen }}</span
                                            >
                                        </td>
                                        <!-- Input Nama Bahan untuk PO Akuntan -->
                                        <td class="p-2 align-top pt-2.5">
                                            <input
                                                type="text"
                                                v-model="
                                                    selectedBahanList[idx]
                                                        .nama_po
                                                "
                                                :placeholder="b.nama"
                                                class="w-full h-9 text-xs font-bold rounded-lg border-slate-300 px-2.5 bg-white text-slate-900 focus:ring-primary focus:border-primary shadow-2xs placeholder:text-slate-400"
                                                title="Nama bahan belanja yang akan ditampilkan di Purchase Order (PO) Akuntan"
                                            />
                                        </td>
                                        <!-- Kategori -->
                                        <td
                                            class="p-3 text-slate-600 align-top pt-4"
                                        >
                                            {{ b.kategori }}
                                        </td>

                                        <!-- Kolom Peruntukan Porsi (Normal / Alergi) -->
                                        <td
                                            class="p-2 text-center align-top pt-2.5 min-w-[170px]"
                                        >
                                            <div
                                                class="flex flex-col gap-1.5 items-center"
                                            >
                                                <select
                                                    v-model="
                                                        selectedBahanList[idx]
                                                            .tipe_porsi
                                                    "
                                                    class="w-full h-9 text-xs font-bold rounded-lg border px-2 transition-colors cursor-pointer"
                                                    :class="
                                                        selectedBahanList[idx]
                                                            .tipe_porsi ===
                                                        'alergi'
                                                            ? 'border-rose-300 bg-rose-50 text-rose-800'
                                                            : 'border-slate-300 bg-white text-slate-700'
                                                    "
                                                >
                                                    <option value="normal">
                                                        Normal (Standar)
                                                    </option>
                                                    <option value="alergi">
                                                        Alergi (Substitusi)
                                                    </option>
                                                </select>

                                                <!-- Dropdown Jenis Alergi -->
                                                <div
                                                    v-if="
                                                        selectedBahanList[idx]
                                                            .tipe_porsi ===
                                                        'alergi'
                                                    "
                                                    class="w-full space-y-1"
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
                                                            -- Pilih Jenis
                                                            Alergi --
                                                        </option>
                                                        <option
                                                            v-for="opt in alergiOptionsWithStats"
                                                            :key="opt.value"
                                                            :value="opt.value"
                                                            :disabled="
                                                                opt.disabled
                                                            "
                                                            :class="
                                                                opt.disabled
                                                                    ? 'text-slate-400 bg-slate-100 italic'
                                                                    : 'text-slate-900 font-bold'
                                                            "
                                                        >
                                                            {{
                                                                opt.labelDisplay
                                                            }}
                                                        </option>
                                                    </select>

                                                    <!-- Info Siswa Alergi di PM -->
                                                    <div
                                                        v-if="
                                                            selectedBahanList[
                                                                idx
                                                            ].jenis_alergi
                                                        "
                                                    >
                                                        <span
                                                            v-if="
                                                                b.targetPKCount +
                                                                    b.targetPBCount >
                                                                0
                                                            "
                                                            class="block text-[9.5px] font-bold text-emerald-800 bg-emerald-100/80 px-1.5 py-0.5 rounded text-center leading-tight"
                                                        >
                                                            ✓
                                                            {{
                                                                b.totalTargetCount
                                                            }}
                                                            Siswa (PK:
                                                            {{
                                                                b.targetPKCount
                                                            }}, PB:
                                                            {{
                                                                b.targetPBCount
                                                            }})
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="block text-[9.5px] font-bold text-rose-800 bg-rose-100 px-1.5 py-0.5 rounded text-center leading-tight border border-rose-200"
                                                        >
                                                            ⚠️ 0 Siswa Alergi di
                                                            PM
                                                        </span>
                                                    </div>
                                                </div>
                                                <div
                                                    v-else
                                                    class="text-left w-full px-0.5 space-y-0.5"
                                                >
                                                    <span
                                                        class="block text-[9.5px] font-bold text-slate-700 text-center"
                                                    >
                                                        {{ b.totalTargetCount }}
                                                        Siswa Normal
                                                    </span>
                                                    <div
                                                        v-if="
                                                            b.alergiDampakList &&
                                                            b.alergiDampakList
                                                                .length > 0
                                                        "
                                                    >
                                                        <span
                                                            v-for="(
                                                                alD, alDIdx
                                                            ) in b.alergiDampakList"
                                                            :key="alDIdx"
                                                            class="block text-[9px] font-extrabold text-amber-900 bg-amber-100/90 border border-amber-300 px-1 py-0.5 rounded text-center leading-tight mt-0.5 shadow-2xs"
                                                            :title="`Porsi dikurangi ${alD.total} siswa (${alD.pk} PK, ${alD.pb} PB) karena alergi ${alD.jenis}`"
                                                        >
                                                            ⚠️ -{{ alD.total }}
                                                            Alergi
                                                            {{ alD.jenis }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Input Gram PK & Info Porsi -->
                                        <td
                                            class="p-2 text-center align-top pt-2.5"
                                        >
                                            <div
                                                class="flex flex-col items-center"
                                            >
                                                <input
                                                    type="number"
                                                    v-model.number="
                                                        selectedBahanList[idx]
                                                            .gram_pk
                                                    "
                                                    required
                                                    placeholder="0"
                                                    class="w-16 h-9 text-center text-xs font-bold rounded-lg border-slate-300 p-1 bg-amber-50/40 text-amber-900 focus:ring-primary focus:border-primary"
                                                    min="0"
                                                />
                                                <span
                                                    class="block text-[10px] font-extrabold text-amber-900 bg-amber-100/80 px-1.5 py-0.5 rounded-md mt-1.5 text-center whitespace-nowrap shadow-2xs"
                                                    :class="{
                                                        'opacity-50 text-slate-400 bg-slate-100':
                                                            b.targetPKCount ===
                                                            0,
                                                    }"
                                                    title="Jumlah sasaran Porsi Kecil (PK) yang dikalikan dengan gramasi"
                                                >
                                                    × {{ b.targetPKCount }} PK
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Input Gram PB & Info Porsi -->
                                        <td
                                            class="p-2 text-center align-top pt-2.5"
                                        >
                                            <div
                                                class="flex flex-col items-center"
                                            >
                                                <input
                                                    type="number"
                                                    v-model.number="
                                                        selectedBahanList[idx]
                                                            .gram_pb
                                                    "
                                                    required
                                                    placeholder="0"
                                                    class="w-16 h-9 text-center text-xs font-bold rounded-lg border-slate-300 p-1 bg-indigo-50/40 text-indigo-900 focus:ring-primary focus:border-primary"
                                                    min="0"
                                                />
                                                <span
                                                    class="block text-[10px] font-extrabold text-indigo-900 bg-indigo-100/80 px-1.5 py-0.5 rounded-md mt-1.5 text-center whitespace-nowrap shadow-2xs"
                                                    :class="{
                                                        'opacity-50 text-slate-400 bg-slate-100':
                                                            b.targetPBCount ===
                                                            0,
                                                    }"
                                                    title="Jumlah sasaran Porsi Besar (PB) yang dikalikan dengan gramasi"
                                                >
                                                    × {{ b.targetPBCount }} PB
                                                </span>
                                            </div>
                                        </td>

                                        <!-- BDD (Otomatis dari TKPI & Terkunci) -->
                                        <td
                                            class="p-2 text-center align-top pt-2.5"
                                        >
                                            <div
                                                class="flex flex-col items-center"
                                            >
                                                <input
                                                    type="number"
                                                    v-model.number="
                                                        selectedBahanList[idx]
                                                            .bdd
                                                    "
                                                    disabled
                                                    title="BDD (%) terisi otomatis sesuai standar database resmi TKPI 2020"
                                                    class="w-14 h-9 text-center text-xs font-black rounded-lg border-slate-200 p-1 bg-slate-100/90 text-slate-700 cursor-not-allowed shadow-none"
                                                />
                                                <span
                                                    class="block text-[9.5px] font-bold text-slate-400 mt-1.5 py-0.5"
                                                >
                                                    Standar
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Buffer % -->
                                        <td
                                            class="p-2 text-center align-top pt-2.5"
                                        >
                                            <div
                                                class="flex flex-col items-center"
                                            >
                                                <input
                                                    type="number"
                                                    v-model.number="
                                                        selectedBahanList[idx]
                                                            .buffer
                                                    "
                                                    required
                                                    placeholder="0"
                                                    class="w-14 h-9 text-center text-xs font-bold rounded-lg border-slate-300 p-1 text-rose-800 bg-white focus:ring-primary focus:border-primary"
                                                    min="0"
                                                />
                                                <span
                                                    class="block text-[9.5px] font-bold text-slate-400 mt-1.5 py-0.5"
                                                >
                                                    Ekstra %
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Total Kg Kotor -->
                                        <td
                                            class="p-3 text-right font-black text-slate-900 bg-slate-50/50 align-top pt-4"
                                        >
                                            {{ b.totalGrossKg }} kg
                                        </td>

                                        <!-- Harga Master -->
                                        <td
                                            class="p-3 text-right text-slate-700 font-semibold align-top pt-4"
                                        >
                                            {{ formatRupiah(b.harga_master) }}
                                            /kg
                                        </td>

                                        <!-- Subtotal Draft -->
                                        <td
                                            class="p-3 text-right font-bold text-blue-900 align-top pt-4"
                                        >
                                            {{ formatRupiah(b.subtotalMaster) }}
                                        </td>

                                        <td
                                            class="p-2 text-center align-top pt-3"
                                        >
                                            <button
                                                type="button"
                                                @click="handleRemoveBahan(idx)"
                                                class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
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
                                            colspan="9"
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

                    <!-- ========================================================================= -->
                    <!-- Real-time Evaluasi Standar AKG BGN & Analisis Food Cost & Pagu Anggaran -->
                    <!-- ========================================================================= -->

                    <!-- 1. Evaluasi Standar AKG BGN -->
                    <div class="space-y-4 pt-2">
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
                                    Evaluasi Kecukupan Standar Gizi AKG BGN
                                    (Badan Gizi Nasional)
                                </h4>
                                <p
                                    class="text-[11.5px] text-blue-800 mt-0.5 leading-relaxed"
                                >
                                    Target nutrisi makan siang bergizi terhitung
                                    otomatis secara real-time dari formulasi
                                    resep:
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- 1. PORSI PK NORMAL -->
                            <Card
                                className="bg-white border-slate-200 shadow-xs"
                            >
                                <CardHeader
                                    className="p-3.5 sm:p-4 border-b border-slate-100 bg-amber-50/60 flex flex-row items-center justify-between"
                                >
                                    <div>
                                        <CardTitle
                                            class="text-sm sm:text-base font-bold text-amber-950"
                                            >Porsi PK Normal
                                            (Standar)</CardTitle
                                        >
                                        <CardDescription class="text-[11px]"
                                            >Kelompok PAUD / TK & SD Kelas
                                            1-3</CardDescription
                                        >
                                    </div>
                                    <Badge
                                        variant="outline"
                                        className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-xs"
                                    >
                                        <Check class="h-3 w-3 mr-1" /> MEMENUHI
                                        AKG
                                    </Badge>
                                </CardHeader>
                                <CardContent
                                    className="p-3.5 sm:p-4 space-y-2.5"
                                >
                                    <!-- Baris 1: 3 Kolom (Energi, Protein, Lemak) -->
                                    <div class="grid grid-cols-3 gap-2 text-xs">
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Energi (Kalori)</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-amber-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPKNormal.energi
                                                }}
                                                kkal</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: 450 - 550 kkal</span
                                            >
                                        </div>
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Protein</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-blue-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPKNormal.protein
                                                }}
                                                gram</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: 15 - 22 g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Lemak</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-indigo-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPKNormal.lemak
                                                }}
                                                gram</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: 12 - 18 g</span
                                            >
                                        </div>
                                    </div>
                                    <!-- Baris 2: 2 Kolom (Karbohidrat, Serat) -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Karbohidrat</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-emerald-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPKNormal.karbohidrat
                                                }}
                                                gram</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: 65 - 85 g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Serat</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-teal-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPKNormal.serat
                                                }}
                                                gram</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: Min. 4.0 g</span
                                            >
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- 2. PORSI PB NORMAL -->
                            <Card
                                className="bg-white border-slate-200 shadow-xs"
                            >
                                <CardHeader
                                    className="p-3.5 sm:p-4 border-b border-slate-100 bg-indigo-50/60 flex flex-row items-center justify-between"
                                >
                                    <div>
                                        <CardTitle
                                            class="text-sm sm:text-base font-bold text-indigo-950"
                                            >Porsi PB Normal
                                            (Standar)</CardTitle
                                        >
                                        <CardDescription class="text-[11px]"
                                            >SD 4-6, SMP, SMA, & Ibu
                                            Hamil/Menyusui</CardDescription
                                        >
                                    </div>
                                    <Badge
                                        variant="outline"
                                        className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-xs"
                                    >
                                        <Check class="h-3 w-3 mr-1" /> MEMENUHI
                                        AKG
                                    </Badge>
                                </CardHeader>
                                <CardContent
                                    className="p-3.5 sm:p-4 space-y-2.5"
                                >
                                    <!-- Baris 1: 3 Kolom (Energi, Protein, Lemak) -->
                                    <div class="grid grid-cols-3 gap-2 text-xs">
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Energi (Kalori)</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-indigo-950 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPBNormal.energi
                                                }}
                                                kkal</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: 650 - 800 kkal</span
                                            >
                                        </div>
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Protein</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-blue-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPBNormal.protein
                                                }}
                                                gram</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: 24 - 35 g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Lemak</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-purple-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPBNormal.lemak
                                                }}
                                                gram</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: 18 - 26 g</span
                                            >
                                        </div>
                                    </div>
                                    <!-- Baris 2: 2 Kolom (Karbohidrat, Serat) -->
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Karbohidrat</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-emerald-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPBNormal.karbohidrat
                                                }}
                                                gram</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: 85 - 110 g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 font-bold uppercase block"
                                                >Serat</span
                                            >
                                            <span
                                                class="text-sm sm:text-base font-black text-teal-900 leading-tight block mt-0.5"
                                                >{{
                                                    akgResultPBNormal.serat
                                                }}
                                                gram</span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Target: Min. 6.0 g</span
                                            >
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- 3. KARTU SPESIFIK VARIAN ALERGI (Hanya Muncul Jika Ada Bahan Berlabel Alergi pada Resep) -->
                            <template
                                v-for="(al, alIdx) in activeAlergiAkgList"
                                :key="alIdx"
                            >
                                <!-- Card Porsi PK Alergi Spesifik -->
                                <Card
                                    className="bg-white border-slate-200 shadow-xs"
                                >
                                    <CardHeader
                                        className="p-3.5 sm:p-4 border-b border-slate-100 bg-rose-50/50 flex flex-row items-center justify-between"
                                    >
                                        <div>
                                            <CardTitle
                                                class="text-sm sm:text-base font-bold text-rose-950"
                                                >Porsi PK Alergi ({{
                                                    al.jenis_alergi
                                                }})</CardTitle
                                            >
                                            <CardDescription class="text-[11px]"
                                                >Substitusi Khusus Non-Alergen
                                                <span v-if="al.siswa_pk > 0"
                                                    >• {{ al.siswa_pk }} Siswa
                                                    PK</span
                                                ></CardDescription
                                            >
                                        </div>
                                        <Badge
                                            variant="outline"
                                            className="bg-white text-rose-800 border-rose-300 font-bold text-xs"
                                        >
                                            Varian Alergi
                                        </Badge>
                                    </CardHeader>
                                    <CardContent
                                        className="p-3.5 sm:p-4 space-y-2.5"
                                    >
                                        <!-- Baris 1: 3 Kolom (Energi, Protein, Lemak) -->
                                        <div
                                            class="grid grid-cols-3 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Energi (Kalori)</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-rose-950 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pk.energi
                                                    }}
                                                    kkal</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: 450 - 550
                                                    kkal</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Protein</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-blue-900 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pk.protein
                                                    }}
                                                    gram</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: 15 - 22 g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Lemak</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-indigo-900 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pk.lemak
                                                    }}
                                                    gram</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: 12 - 18 g</span
                                                >
                                            </div>
                                        </div>
                                        <!-- Baris 2: 2 Kolom (Karbohidrat, Serat) -->
                                        <div
                                            class="grid grid-cols-2 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Karbohidrat</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-emerald-900 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pk.karbohidrat
                                                    }}
                                                    gram</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: 65 - 85 g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Serat</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-teal-900 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pk.serat
                                                    }}
                                                    gram</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: Min. 4.0 g</span
                                                >
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Card Porsi PB Alergi Spesifik -->
                                <Card
                                    className="bg-white border-slate-200 shadow-xs"
                                >
                                    <CardHeader
                                        className="p-3.5 sm:p-4 border-b border-slate-100 bg-rose-50/50 flex flex-row items-center justify-between"
                                    >
                                        <div>
                                            <CardTitle
                                                class="text-sm sm:text-base font-bold text-rose-950"
                                                >Porsi PB Alergi ({{
                                                    al.jenis_alergi
                                                }})</CardTitle
                                            >
                                            <CardDescription class="text-[11px]"
                                                >Substitusi Khusus Non-Alergen
                                                <span v-if="al.siswa_pb > 0"
                                                    >• {{ al.siswa_pb }} Siswa
                                                    PB</span
                                                ></CardDescription
                                            >
                                        </div>
                                        <Badge
                                            variant="outline"
                                            className="bg-white text-rose-800 border-rose-300 font-bold text-xs"
                                        >
                                            Varian Alergi
                                        </Badge>
                                    </CardHeader>
                                    <CardContent
                                        className="p-3.5 sm:p-4 space-y-2.5"
                                    >
                                        <!-- Baris 1: 3 Kolom (Energi, Protein, Lemak) -->
                                        <div
                                            class="grid grid-cols-3 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Energi (Kalori)</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-rose-950 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pb.energi
                                                    }}
                                                    kkal</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: 650 - 800
                                                    kkal</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Protein</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-blue-900 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pb.protein
                                                    }}
                                                    gram</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: 24 - 35 g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Lemak</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-purple-900 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pb.lemak
                                                    }}
                                                    gram</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: 18 - 26 g</span
                                                >
                                            </div>
                                        </div>
                                        <!-- Baris 2: 2 Kolom (Karbohidrat, Serat) -->
                                        <div
                                            class="grid grid-cols-2 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Karbohidrat</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-emerald-900 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pb.karbohidrat
                                                    }}
                                                    gram</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: 85 - 110 g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 font-bold uppercase block"
                                                    >Serat</span
                                                >
                                                <span
                                                    class="text-sm sm:text-base font-black text-teal-900 leading-tight block mt-0.5"
                                                    >{{
                                                        al.pb.serat
                                                    }}
                                                    gram</span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Target: Min. 6.0 g</span
                                                >
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </template>
                        </div>
                    </div>

                    <!-- 2. Analisis Food Cost & Batas Pagu Anggaran -->
                    <div class="space-y-4 pt-2">
                        <!-- Header Food Cost -->
                        <div class="flex items-center gap-2">
                            <div
                                class="h-6 w-6 rounded-lg bg-emerald-500/10 text-emerald-700 flex items-center justify-center font-black text-xs"
                            >
                                💰
                            </div>
                            <div>
                                <h4
                                    class="text-xs sm:text-sm font-black text-slate-900"
                                >
                                    Analisis Food Cost & Kepatuhan Pagu Anggaran
                                    BGN
                                </h4>
                                <p class="text-[11px] text-slate-500">
                                    Monitoring biaya bahan baku per porsi
                                    terhadap batas maksimal pagu MBG nasional.
                                </p>
                            </div>
                        </div>

                        <!-- Card Perbandingan Pagu -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Food Cost PK (Batas Rp 8.000) -->
                            <Card
                                className="bg-white border-slate-200/90 shadow-sm transition-all rounded-2xl overflow-hidden hover:shadow-md"
                                :class="{
                                    'border-rose-300 ring-2 ring-rose-200/80 bg-rose-50/10':
                                        totalFoodCostPKNormal > 8000,
                                }"
                            >
                                <CardHeader
                                    class="p-4 sm:p-5 border-b flex flex-row items-center justify-between gap-3"
                                    :class="
                                        totalFoodCostPKNormal > 8000
                                            ? 'bg-rose-50/70 border-rose-100'
                                            : 'bg-gradient-to-r from-amber-50/70 to-orange-50/40 border-slate-100'
                                    "
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 shadow-2xs"
                                            :class="
                                                totalFoodCostPKNormal > 8000
                                                    ? 'bg-rose-100 text-rose-700 border border-rose-200'
                                                    : 'bg-white text-amber-600 border border-amber-200/80'
                                            "
                                        >
                                            <Utensils class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-[10px] font-black uppercase tracking-wider"
                                                :class="
                                                    totalFoodCostPKNormal > 8000
                                                        ? 'text-rose-800'
                                                        : 'text-amber-800/80'
                                                "
                                            >
                                                Standar BGN • Porsi Kecil
                                            </p>
                                            <CardTitle
                                                class="text-base font-black text-slate-900 mt-0.5"
                                            >
                                                Food Cost Porsi Kecil (PK)
                                            </CardTitle>
                                            <p
                                                class="text-xs text-slate-500 font-medium"
                                            >
                                                Batas Pagu:
                                                <strong
                                                    class="text-slate-800 font-bold"
                                                    >Rp 8.000 / porsi</strong
                                                >
                                            </p>
                                        </div>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        :className="
                                            totalFoodCostPKNormal <= 8000
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-xs px-2.5 py-1 shadow-2xs'
                                                : 'bg-rose-100 text-rose-800 border-rose-300 font-black text-xs px-2.5 py-1 animate-pulse shadow-2xs'
                                        "
                                    >
                                        <span
                                            v-if="totalFoodCostPKNormal <= 8000"
                                            class="flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-emerald-500"
                                            ></span>
                                            <span>EFISIEN / AMAN</span>
                                        </span>
                                        <span
                                            v-else
                                            class="flex items-center gap-1.5"
                                        >
                                            <AlertTriangle
                                                class="h-3.5 w-3.5 text-rose-600"
                                            />
                                            <span>OVER BUDGET</span>
                                        </span>
                                    </Badge>
                                </CardHeader>
                                <CardContent className="p-4 sm:p-5 space-y-4">
                                    <!-- Dual Stat Grid -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                    >
                                        <!-- Metric 1: Realisasi -->
                                        <div
                                            class="p-3.5 rounded-xl border flex flex-col justify-between transition-all"
                                            :class="
                                                totalFoodCostPKNormal > 8000
                                                    ? 'bg-rose-50/50 border-rose-200'
                                                    : 'bg-slate-50/80 border-slate-200/80'
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-1"
                                            >
                                                <span
                                                    class="text-xs font-bold text-slate-600"
                                                >
                                                    Total Food Cost PK
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-slate-500 border border-slate-200 shadow-2xs"
                                                >
                                                    PK
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h3
                                                    class="text-2xl sm:text-3xl font-black tracking-tight"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'text-rose-950'
                                                            : 'text-slate-900'
                                                    "
                                                >
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                </h3>
                                                <p
                                                    class="text-[11px] text-slate-400 font-medium mt-0.5"
                                                >
                                                    Kalkulasi menu per porsi
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Metric 2: Sisa Pagu / Selisih Lebih -->
                                        <div
                                            class="p-3.5 rounded-xl border flex flex-col justify-between transition-all"
                                            :class="
                                                totalFoodCostPKNormal > 8000
                                                    ? 'bg-gradient-to-br from-rose-50 to-red-50/60 border-rose-200 text-rose-900'
                                                    : 'bg-gradient-to-br from-emerald-50/90 to-teal-50/60 border-emerald-200/80 text-emerald-900'
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-1"
                                            >
                                                <span
                                                    class="text-xs font-bold"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'text-rose-800'
                                                            : 'text-emerald-800'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? "Selisih Lebih (Over)"
                                                            : "Sisa Pagu Anggaran"
                                                    }}
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-extrabold shadow-2xs"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'bg-rose-200/80 text-rose-950 border border-rose-300'
                                                            : 'bg-emerald-200/80 text-emerald-950 border border-emerald-300'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? "Defisit"
                                                            : "Hemat"
                                                    }}
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h4
                                                    class="text-2xl sm:text-3xl font-black tracking-tight"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'text-rose-700'
                                                            : 'text-emerald-700'
                                                    "
                                                >
                                                    <span
                                                        v-if="
                                                            totalFoodCostPKNormal >
                                                            8000
                                                        "
                                                        >+</span
                                                    >{{
                                                        formatRupiah(
                                                            Math.abs(
                                                                8000 -
                                                                    totalFoodCostPKNormal,
                                                            ),
                                                        )
                                                    }}
                                                </h4>
                                                <p
                                                    class="text-[11px] font-medium mt-0.5"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'text-rose-600'
                                                            : 'text-emerald-700/80'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? "Melebihi batas pagu BGN"
                                                            : "Tersisa dari pagu Rp 8.000"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Budget Utilization Progress Bar -->
                                    <div
                                        class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center justify-between text-xs"
                                        >
                                            <span
                                                class="font-bold text-slate-600 flex items-center gap-1.5"
                                            >
                                                <Coins
                                                    class="h-3.5 w-3.5 text-slate-400"
                                                />
                                                Utilisasi Anggaran Pagu:
                                            </span>
                                            <span
                                                class="font-black"
                                                :class="
                                                    totalFoodCostPKNormal > 8000
                                                        ? 'text-rose-700'
                                                        : 'text-slate-800'
                                                "
                                            >
                                                {{
                                                    (
                                                        (totalFoodCostPKNormal /
                                                            8000) *
                                                        100
                                                    ).toFixed(1)
                                                }}%
                                                <span
                                                    class="text-[10px] font-normal text-slate-400"
                                                >
                                                    ({{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                    / Rp 8.000)
                                                </span>
                                            </span>
                                        </div>
                                        <div
                                            class="h-2 w-full rounded-full bg-slate-200/80 overflow-hidden"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="
                                                    totalFoodCostPKNormal > 8000
                                                        ? 'bg-rose-500'
                                                        : totalFoodCostPKNormal >
                                                            6800
                                                          ? 'bg-amber-500'
                                                          : 'bg-emerald-500'
                                                "
                                                :style="{
                                                    width: `${Math.min(100, (totalFoodCostPKNormal / 8000) * 100)}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Alert Status Banner -->
                                    <div
                                        v-if="totalFoodCostPKNormal > 8000"
                                        class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-start gap-2.5 shadow-2xs"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 text-rose-600 shrink-0 mt-0.5"
                                        />
                                        <div class="space-y-0.5">
                                            <p
                                                class="font-extrabold text-rose-950 text-xs"
                                            >
                                                ⚠️ Peringatan: Melebihi Batas
                                                Pagu PK!
                                            </p>
                                            <p
                                                class="text-[11px] text-rose-800 leading-relaxed"
                                            >
                                                Food cost PK (<strong>{{
                                                    formatRupiah(
                                                        totalFoodCostPKNormal,
                                                    )
                                                }}</strong
                                                >) melampaui pagu maksimal
                                                <strong
                                                    >Rp 8.000 / porsi</strong
                                                >
                                                dengan selisih lebih
                                                <strong
                                                    >+{{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal -
                                                                8000,
                                                        )
                                                    }}
                                                    / porsi</strong
                                                >. Mohon sesuaikan gramasi atau
                                                pilihan bahan baku.
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center gap-2.5 shadow-2xs"
                                    >
                                        <CheckCircle2
                                            class="h-4 w-4 text-emerald-600 shrink-0"
                                        />
                                        <span
                                            class="text-[11px] text-emerald-900 font-medium"
                                        >
                                            Biaya bahan baku PK
                                            <strong>aman dan efisien</strong>
                                            sesuai standar pagu BGN (Hemat/Sisa:
                                            <strong
                                                >{{
                                                    formatRupiah(
                                                        8000 -
                                                            totalFoodCostPKNormal,
                                                    )
                                                }}
                                                / porsi</strong
                                            >).
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Food Cost PB (Batas Rp 10.000) -->
                            <Card
                                className="bg-white border-slate-200/90 shadow-sm transition-all rounded-2xl overflow-hidden hover:shadow-md"
                                :class="{
                                    'border-rose-300 ring-2 ring-rose-200/80 bg-rose-50/10':
                                        totalFoodCostPBNormal > 10000,
                                }"
                            >
                                <CardHeader
                                    class="p-4 sm:p-5 border-b flex flex-row items-center justify-between gap-3"
                                    :class="
                                        totalFoodCostPBNormal > 10000
                                            ? 'bg-rose-50/70 border-rose-100'
                                            : 'bg-gradient-to-r from-indigo-50/70 to-blue-50/40 border-slate-100'
                                    "
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 shadow-2xs"
                                            :class="
                                                totalFoodCostPBNormal > 10000
                                                    ? 'bg-rose-100 text-rose-700 border border-rose-200'
                                                    : 'bg-white text-indigo-600 border border-indigo-200/80'
                                            "
                                        >
                                            <UtensilsCrossed class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-[10px] font-black uppercase tracking-wider"
                                                :class="
                                                    totalFoodCostPBNormal >
                                                    10000
                                                        ? 'text-rose-800'
                                                        : 'text-indigo-800/80'
                                                "
                                            >
                                                Standar BGN • Porsi Besar
                                            </p>
                                            <CardTitle
                                                class="text-base font-black text-slate-900 mt-0.5"
                                            >
                                                Food Cost Porsi Besar (PB)
                                            </CardTitle>
                                            <p
                                                class="text-xs text-slate-500 font-medium"
                                            >
                                                Batas Pagu:
                                                <strong
                                                    class="text-slate-800 font-bold"
                                                    >Rp 10.000 / porsi</strong
                                                >
                                            </p>
                                        </div>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        :className="
                                            totalFoodCostPBNormal <= 10000
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-xs px-2.5 py-1 shadow-2xs'
                                                : 'bg-rose-100 text-rose-800 border-rose-300 font-black text-xs px-2.5 py-1 animate-pulse shadow-2xs'
                                        "
                                    >
                                        <span
                                            v-if="
                                                totalFoodCostPBNormal <= 10000
                                            "
                                            class="flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-emerald-500"
                                            ></span>
                                            <span>EFISIEN / AMAN</span>
                                        </span>
                                        <span
                                            v-else
                                            class="flex items-center gap-1.5"
                                        >
                                            <AlertTriangle
                                                class="h-3.5 w-3.5 text-rose-600"
                                            />
                                            <span>OVER BUDGET</span>
                                        </span>
                                    </Badge>
                                </CardHeader>
                                <CardContent className="p-4 sm:p-5 space-y-4">
                                    <!-- Dual Stat Grid -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                    >
                                        <!-- Metric 1: Realisasi -->
                                        <div
                                            class="p-3.5 rounded-xl border flex flex-col justify-between transition-all"
                                            :class="
                                                totalFoodCostPBNormal > 10000
                                                    ? 'bg-rose-50/50 border-rose-200'
                                                    : 'bg-slate-50/80 border-slate-200/80'
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-1"
                                            >
                                                <span
                                                    class="text-xs font-bold text-slate-600"
                                                >
                                                    Total Food Cost PB
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-slate-500 border border-slate-200 shadow-2xs"
                                                >
                                                    PB
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h3
                                                    class="text-2xl sm:text-3xl font-black tracking-tight"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'text-rose-950'
                                                            : 'text-slate-900'
                                                    "
                                                >
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                </h3>
                                                <p
                                                    class="text-[11px] text-slate-400 font-medium mt-0.5"
                                                >
                                                    Kalkulasi menu per porsi
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Metric 2: Sisa Pagu / Selisih Lebih -->
                                        <div
                                            class="p-3.5 rounded-xl border flex flex-col justify-between transition-all"
                                            :class="
                                                totalFoodCostPBNormal > 10000
                                                    ? 'bg-gradient-to-br from-rose-50 to-red-50/60 border-rose-200 text-rose-900'
                                                    : 'bg-gradient-to-br from-emerald-50/90 to-teal-50/60 border-emerald-200/80 text-emerald-900'
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-1"
                                            >
                                                <span
                                                    class="text-xs font-bold"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'text-rose-800'
                                                            : 'text-emerald-800'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? "Selisih Lebih (Over)"
                                                            : "Sisa Pagu Anggaran"
                                                    }}
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-extrabold shadow-2xs"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'bg-rose-200/80 text-rose-950 border border-rose-300'
                                                            : 'bg-emerald-200/80 text-emerald-950 border border-emerald-300'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? "Defisit"
                                                            : "Hemat"
                                                    }}
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h4
                                                    class="text-2xl sm:text-3xl font-black tracking-tight"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'text-rose-700'
                                                            : 'text-emerald-700'
                                                    "
                                                >
                                                    <span
                                                        v-if="
                                                            totalFoodCostPBNormal >
                                                            10000
                                                        "
                                                        >+</span
                                                    >{{
                                                        formatRupiah(
                                                            Math.abs(
                                                                10000 -
                                                                    totalFoodCostPBNormal,
                                                            ),
                                                        )
                                                    }}
                                                </h4>
                                                <p
                                                    class="text-[11px] font-medium mt-0.5"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'text-rose-600'
                                                            : 'text-emerald-700/80'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? "Melebihi batas pagu BGN"
                                                            : "Tersisa dari pagu Rp 10.000"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Budget Utilization Progress Bar -->
                                    <div
                                        class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center justify-between text-xs"
                                        >
                                            <span
                                                class="font-bold text-slate-600 flex items-center gap-1.5"
                                            >
                                                <Coins
                                                    class="h-3.5 w-3.5 text-slate-400"
                                                />
                                                Utilisasi Anggaran Pagu:
                                            </span>
                                            <span
                                                class="font-black"
                                                :class="
                                                    totalFoodCostPBNormal >
                                                    10000
                                                        ? 'text-rose-700'
                                                        : 'text-slate-800'
                                                "
                                            >
                                                {{
                                                    (
                                                        (totalFoodCostPBNormal /
                                                            10000) *
                                                        100
                                                    ).toFixed(1)
                                                }}%
                                                <span
                                                    class="text-[10px] font-normal text-slate-400"
                                                >
                                                    ({{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                    / Rp 10.000)
                                                </span>
                                            </span>
                                        </div>
                                        <div
                                            class="h-2 w-full rounded-full bg-slate-200/80 overflow-hidden"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="
                                                    totalFoodCostPBNormal >
                                                    10000
                                                        ? 'bg-rose-500'
                                                        : totalFoodCostPBNormal >
                                                            8500
                                                          ? 'bg-amber-500'
                                                          : 'bg-emerald-500'
                                                "
                                                :style="{
                                                    width: `${Math.min(100, (totalFoodCostPBNormal / 10000) * 100)}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Alert Status Banner -->
                                    <div
                                        v-if="totalFoodCostPBNormal > 10000"
                                        class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-start gap-2.5 shadow-2xs"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 text-rose-600 shrink-0 mt-0.5"
                                        />
                                        <div class="space-y-0.5">
                                            <p
                                                class="font-extrabold text-rose-950 text-xs"
                                            >
                                                ⚠️ Peringatan: Melebihi Batas
                                                Pagu PB!
                                            </p>
                                            <p
                                                class="text-[11px] text-rose-800 leading-relaxed"
                                            >
                                                Food cost PB (<strong>{{
                                                    formatRupiah(
                                                        totalFoodCostPBNormal,
                                                    )
                                                }}</strong
                                                >) melampaui pagu maksimal
                                                <strong
                                                    >Rp 10.000 / porsi</strong
                                                >
                                                dengan selisih lebih
                                                <strong
                                                    >+{{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal -
                                                                10000,
                                                        )
                                                    }}
                                                    / porsi</strong
                                                >. Mohon sesuaikan gramasi atau
                                                pilihan bahan baku.
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center gap-2.5 shadow-2xs"
                                    >
                                        <CheckCircle2
                                            class="h-4 w-4 text-emerald-600 shrink-0"
                                        />
                                        <span
                                            class="text-[11px] text-emerald-900 font-medium"
                                        >
                                            Biaya bahan baku PB
                                            <strong>aman dan efisien</strong>
                                            sesuai standar pagu BGN (Hemat/Sisa:
                                            <strong
                                                >{{
                                                    formatRupiah(
                                                        10000 -
                                                            totalFoodCostPBNormal,
                                                    )
                                                }}
                                                / porsi</strong
                                            >).
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Rincian Food Cost per Komponen Bahan -->
                        <Card
                            className="bg-white border-slate-200 shadow-xs overflow-hidden"
                        >
                            <CardHeader
                                className="p-3.5 sm:p-4 border-b border-slate-100 bg-slate-50/50"
                            >
                                <CardTitle
                                    class="text-sm sm:text-base font-bold text-slate-900 flex items-center gap-2"
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
                                    class="w-full min-w-[650px] text-left text-xs border-collapse"
                                >
                                    <thead
                                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                    >
                                        <tr>
                                            <th class="p-3 text-center w-10">
                                                No
                                            </th>
                                            <th class="p-3">Nama Bahan Baku</th>
                                            <th class="p-3">Kategori</th>
                                            <th class="p-3 text-right">
                                                Gram PK
                                            </th>
                                            <th class="p-3 text-right">
                                                Food Cost PK
                                            </th>
                                            <th class="p-3 text-right">
                                                Gram PB
                                            </th>
                                            <th class="p-3 text-right">
                                                Food Cost PB
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-slate-100 text-slate-800"
                                    >
                                        <tr
                                            v-for="(
                                                b, idx
                                            ) in bahanCalculations"
                                            :key="idx"
                                            class="hover:bg-slate-50/70"
                                        >
                                            <td
                                                class="p-3 text-center font-bold text-slate-500"
                                            >
                                                {{ idx + 1 }}
                                            </td>
                                            <td
                                                class="p-3 font-bold text-slate-900"
                                            >
                                                <div
                                                    class="text-slate-950 font-bold text-xs"
                                                >
                                                    {{ b.nama_po || b.nama }}
                                                </div>
                                                <span
                                                    v-if="
                                                        b.nama_po &&
                                                        b.nama_po !== b.nama
                                                    "
                                                    class="block text-[10px] text-slate-400 font-normal"
                                                >
                                                    TKPI: {{ b.nama }}
                                                </span>
                                                <span
                                                    v-if="
                                                        b.tipe_porsi ===
                                                        'alergi'
                                                    "
                                                    class="inline-block mt-0.5 px-1.5 py-0.2 rounded bg-rose-100 text-rose-800 text-[10px] font-bold"
                                                >
                                                    Alergi: {{ b.jenis_alergi }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-slate-500">
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
                                                colspan="4"
                                                class="p-3.5 uppercase tracking-wider text-slate-700"
                                            >
                                                Grand Total Food Cost per Porsi:
                                            </td>
                                            <td
                                                class="p-3.5 text-right font-black text-sm"
                                                :class="
                                                    totalFoodCostPKNormal > 8000
                                                        ? 'text-rose-700'
                                                        : 'text-amber-950'
                                                "
                                            >
                                                <div>
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                </div>
                                                <span
                                                    v-if="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                    "
                                                    class="inline-block text-[10px] text-rose-700 bg-rose-100 px-1.5 py-0.5 rounded font-extrabold mt-0.5"
                                                >
                                                    ⚠️ Over +{{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal -
                                                                8000,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-block text-[10px] text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold mt-0.5"
                                                >
                                                    ✓ Sisa
                                                    {{
                                                        formatRupiah(
                                                            8000 -
                                                                totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                </span>
                                            </td>
                                            <td></td>
                                            <td
                                                class="p-3.5 text-right font-black text-sm"
                                                :class="
                                                    totalFoodCostPBNormal >
                                                    10000
                                                        ? 'text-rose-700'
                                                        : 'text-indigo-950'
                                                "
                                            >
                                                <div>
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                </div>
                                                <span
                                                    v-if="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                    "
                                                    class="inline-block text-[10px] text-rose-700 bg-rose-100 px-1.5 py-0.5 rounded font-extrabold mt-0.5"
                                                >
                                                    ⚠️ Over +{{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal -
                                                                10000,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-block text-[10px] text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-bold mt-0.5"
                                                >
                                                    ✓ Sisa
                                                    {{
                                                        formatRupiah(
                                                            10000 -
                                                                totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </Card>
                    </div>

                    <!-- Action Bar Ajukan Draft PO ke Akuntan (Langkah 3) -->
                    <div
                        class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-slate-900 to-blue-950 text-white shadow-xs border border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    >
                        <div class="space-y-1">
                            <h4
                                class="text-sm font-black text-white flex items-center gap-2"
                            >
                                <CheckCircle2
                                    class="h-4 w-4 text-emerald-400 shrink-0"
                                />
                                <span
                                    >Formulasi Resep, Nilai Gizi (AKG) & Food
                                    Cost Siap Diajukan</span
                                >
                            </h4>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Seluruh kebutuhan gramasi, evaluasi AKG BGN, dan
                                analisis food cost telah tervalidasi. Lanjutkan
                                ke langkah pengajuan PO ke Akuntan.
                            </p>
                        </div>
                        <Button
                            type="button"
                            @click="handleAjukanDraftPo"
                            className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-5 h-10 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer shrink-0 w-full sm:w-auto text-center"
                        >
                            <Send class="h-4 w-4 shrink-0" />
                            <span>Ajukan PO ke Akuntan (Langkah 3)</span>
                        </Button>
                    </div>
                </div>

                <!-- ========================================================================================= -->
                <!-- Bagian 3: Order Pembelian Bahan & Verifikasi Akuntan (Step 3) -->
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
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <CardTitle
                                            class="text-base sm:text-lg font-bold text-slate-900"
                                        >
                                            Pembelian Bahan
                                        </CardTitle>
                                        <Badge
                                            variant="outline"
                                            class="bg-blue-50 text-blue-700 border-blue-300 font-extrabold text-xs"
                                        >
                                            Langkah 3 dari 3
                                        </Badge>
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
                                class="w-full min-w-[800px] text-left text-xs border-collapse"
                            >
                                <thead
                                    class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3 w-10 text-center">No</th>
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
                                            class="p-3 text-center text-slate-400 font-medium"
                                        >
                                            {{ idx + 1 }}
                                        </td>
                                        <td
                                            class="p-3 font-bold text-slate-900"
                                        >
                                            <div
                                                class="text-slate-950 font-black text-xs"
                                            >
                                                {{ b.nama_po || b.nama }}
                                            </div>
                                            <span
                                                class="block text-[10px] text-slate-400 font-normal mt-0.5"
                                            >
                                                TKPI: {{ b.nama }}
                                            </span>
                                            <span
                                                v-if="b.tipe_porsi === 'alergi'"
                                                class="inline-block text-[9.5px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200 mt-1"
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
                                            class="p-3 text-center text-xs font-bold"
                                        >
                                            <span
                                                v-if="
                                                    b.subtotalAktual >
                                                    b.subtotalMaster
                                                "
                                                class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold"
                                            >
                                                Lebih Mahal (+{{
                                                    formatRupiah(
                                                        b.subtotalAktual -
                                                            b.subtotalMaster,
                                                    )
                                                }})
                                            </span>
                                            <span
                                                v-else-if="
                                                    b.subtotalAktual <
                                                    b.subtotalMaster
                                                "
                                                class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold"
                                            >
                                                Lebih Murah (-{{
                                                    formatRupiah(
                                                        b.subtotalMaster -
                                                            b.subtotalAktual,
                                                    )
                                                }})
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-50 text-slate-500 border border-slate-200 text-[11px] font-medium"
                                            >
                                                Sesuai (Rp 0)
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="bg-slate-100/90 font-black text-slate-900 border-t-2 border-slate-300 text-xs"
                                >
                                    <tr>
                                        <td
                                            colspan="5"
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
                                        <td class="p-3.5 text-center text-xs">
                                            <span
                                                v-if="
                                                    grandTotalAktual >
                                                    grandTotalDraftMaster
                                                "
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 border border-rose-300 text-xs font-bold"
                                            >
                                                Lebih Mahal (+{{
                                                    formatRupiah(
                                                        grandTotalAktual -
                                                            grandTotalDraftMaster,
                                                    )
                                                }})
                                            </span>
                                            <span
                                                v-else-if="
                                                    grandTotalAktual <
                                                    grandTotalDraftMaster
                                                "
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold"
                                            >
                                                Lebih Murah (-{{
                                                    formatRupiah(
                                                        grandTotalDraftMaster -
                                                            grandTotalAktual,
                                                    )
                                                }})
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-300 text-xs font-bold"
                                            >
                                                Sesuai (Rp 0)
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </Card>

                    <!-- Real-time Analisis Food Cost & Kepatuhan Pagu Anggaran BGN untuk Akuntan -->
                    <div class="space-y-4 pt-2">
                        <!-- Header Food Cost Akuntan -->
                        <div class="flex items-center gap-2">
                            <div
                                class="h-6 w-6 rounded-lg bg-emerald-500/10 text-emerald-700 flex items-center justify-center font-black text-xs"
                            >
                                💰
                            </div>
                            <div>
                                <h4
                                    class="text-xs sm:text-sm font-black text-slate-900"
                                >
                                    Analisis Food Cost & Kepatuhan Pagu Anggaran
                                    BGN
                                </h4>
                                <p class="text-[11px] text-slate-500">
                                    Simulasi dampak koreksi harga pasar aktual
                                    terhadap kepatuhan batas pagu MBG nasional.
                                </p>
                            </div>
                        </div>

                        <!-- Card Perbandingan Pagu (Real-time dari Harga Aktual) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Food Cost PK (Batas Rp 8.000) -->
                            <Card
                                className="bg-white border-slate-200/90 shadow-sm transition-all rounded-2xl overflow-hidden hover:shadow-md"
                                :class="{
                                    'border-rose-300 ring-2 ring-rose-200/80 bg-rose-50/10':
                                        totalFoodCostPKNormal > 8000,
                                }"
                            >
                                <CardHeader
                                    class="p-4 sm:p-5 border-b flex flex-row items-center justify-between gap-3"
                                    :class="
                                        totalFoodCostPKNormal > 8000
                                            ? 'bg-rose-50/70 border-rose-100'
                                            : 'bg-gradient-to-r from-amber-50/70 to-orange-50/40 border-slate-100'
                                    "
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 shadow-2xs"
                                            :class="
                                                totalFoodCostPKNormal > 8000
                                                    ? 'bg-rose-100 text-rose-700 border border-rose-200'
                                                    : 'bg-white text-amber-600 border border-amber-200/80'
                                            "
                                        >
                                            <Utensils class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-[10px] font-black uppercase tracking-wider"
                                                :class="
                                                    totalFoodCostPKNormal > 8000
                                                        ? 'text-rose-800'
                                                        : 'text-amber-800/80'
                                                "
                                            >
                                                Standar BGN • Porsi Kecil
                                            </p>
                                            <CardTitle
                                                class="text-base font-black text-slate-900 mt-0.5"
                                            >
                                                Food Cost Porsi Kecil (PK)
                                            </CardTitle>
                                            <p
                                                class="text-xs text-slate-500 font-medium"
                                            >
                                                Batas Pagu:
                                                <strong
                                                    class="text-slate-800 font-bold"
                                                    >Rp 8.000 / porsi</strong
                                                >
                                            </p>
                                        </div>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        :className="
                                            totalFoodCostPKNormal <= 8000
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-xs px-2.5 py-1 shadow-2xs'
                                                : 'bg-rose-100 text-rose-800 border-rose-300 font-black text-xs px-2.5 py-1 animate-pulse shadow-2xs'
                                        "
                                    >
                                        <span
                                            v-if="totalFoodCostPKNormal <= 8000"
                                            class="flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-emerald-500"
                                            ></span>
                                            <span>EFISIEN / AMAN</span>
                                        </span>
                                        <span
                                            v-else
                                            class="flex items-center gap-1.5"
                                        >
                                            <AlertTriangle
                                                class="h-3.5 w-3.5 text-rose-600"
                                            />
                                            <span>OVER BUDGET</span>
                                        </span>
                                    </Badge>
                                </CardHeader>
                                <CardContent className="p-4 sm:p-5 space-y-4">
                                    <!-- Dual Stat Grid -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                    >
                                        <!-- Metric 1: Realisasi -->
                                        <div
                                            class="p-3.5 rounded-xl border flex flex-col justify-between transition-all"
                                            :class="
                                                totalFoodCostPKNormal > 8000
                                                    ? 'bg-rose-50/50 border-rose-200'
                                                    : 'bg-slate-50/80 border-slate-200/80'
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-1"
                                            >
                                                <span
                                                    class="text-xs font-bold text-slate-600"
                                                >
                                                    Total Food Cost (Realisasi)
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-slate-500 border border-slate-200 shadow-2xs"
                                                >
                                                    PK
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h3
                                                    class="text-2xl sm:text-3xl font-black tracking-tight"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'text-rose-950'
                                                            : 'text-slate-900'
                                                    "
                                                >
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                </h3>
                                                <p
                                                    class="text-[11px] text-slate-400 font-medium mt-0.5"
                                                >
                                                    Biaya riil per porsi MBG
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Metric 2: Sisa Pagu / Selisih Lebih -->
                                        <div
                                            class="p-3.5 rounded-xl border flex flex-col justify-between transition-all"
                                            :class="
                                                totalFoodCostPKNormal > 8000
                                                    ? 'bg-gradient-to-br from-rose-50 to-red-50/60 border-rose-200 text-rose-900'
                                                    : 'bg-gradient-to-br from-emerald-50/90 to-teal-50/60 border-emerald-200/80 text-emerald-900'
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-1"
                                            >
                                                <span
                                                    class="text-xs font-bold"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'text-rose-800'
                                                            : 'text-emerald-800'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? "Selisih Lebih (Over)"
                                                            : "Sisa Pagu Anggaran"
                                                    }}
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-extrabold shadow-2xs"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'bg-rose-200/80 text-rose-950 border border-rose-300'
                                                            : 'bg-emerald-200/80 text-emerald-950 border border-emerald-300'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? "Defisit"
                                                            : "Hemat"
                                                    }}
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h4
                                                    class="text-2xl sm:text-3xl font-black tracking-tight"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'text-rose-700'
                                                            : 'text-emerald-700'
                                                    "
                                                >
                                                    <span
                                                        v-if="
                                                            totalFoodCostPKNormal >
                                                            8000
                                                        "
                                                        >+</span
                                                    >{{
                                                        formatRupiah(
                                                            Math.abs(
                                                                8000 -
                                                                    totalFoodCostPKNormal,
                                                            ),
                                                        )
                                                    }}
                                                </h4>
                                                <p
                                                    class="text-[11px] font-medium mt-0.5"
                                                    :class="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? 'text-rose-600'
                                                            : 'text-emerald-700/80'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPKNormal >
                                                        8000
                                                            ? "Melebihi batas pagu BGN"
                                                            : "Tersisa dari pagu Rp 8.000"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Budget Utilization Progress Bar -->
                                    <div
                                        class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center justify-between text-xs"
                                        >
                                            <span
                                                class="font-bold text-slate-600 flex items-center gap-1.5"
                                            >
                                                <Coins
                                                    class="h-3.5 w-3.5 text-slate-400"
                                                />
                                                Utilisasi Anggaran Pagu:
                                            </span>
                                            <span
                                                class="font-black"
                                                :class="
                                                    totalFoodCostPKNormal > 8000
                                                        ? 'text-rose-700'
                                                        : 'text-slate-800'
                                                "
                                            >
                                                {{
                                                    (
                                                        (totalFoodCostPKNormal /
                                                            8000) *
                                                        100
                                                    ).toFixed(1)
                                                }}%
                                                <span
                                                    class="text-[10px] font-normal text-slate-400"
                                                >
                                                    ({{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                    / Rp 8.000)
                                                </span>
                                            </span>
                                        </div>
                                        <div
                                            class="h-2 w-full rounded-full bg-slate-200/80 overflow-hidden"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="
                                                    totalFoodCostPKNormal > 8000
                                                        ? 'bg-rose-500'
                                                        : totalFoodCostPKNormal >
                                                            6800
                                                          ? 'bg-amber-500'
                                                          : 'bg-emerald-500'
                                                "
                                                :style="{
                                                    width: `${Math.min(100, (totalFoodCostPKNormal / 8000) * 100)}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Alert Status Banner -->
                                    <div
                                        v-if="totalFoodCostPKNormal > 8000"
                                        class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-start gap-2.5 shadow-2xs"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 text-rose-600 shrink-0 mt-0.5"
                                        />
                                        <div class="space-y-0.5">
                                            <p
                                                class="font-extrabold text-rose-950 text-xs"
                                            >
                                                ⚠️ Peringatan: Melebihi Batas
                                                Pagu PK!
                                            </p>
                                            <p
                                                class="text-[11px] text-rose-800 leading-relaxed"
                                            >
                                                Food cost PK (<strong>{{
                                                    formatRupiah(
                                                        totalFoodCostPKNormal,
                                                    )
                                                }}</strong
                                                >) melampaui pagu maksimal
                                                <strong
                                                    >Rp 8.000 / porsi</strong
                                                >
                                                dengan selisih lebih
                                                <strong
                                                    >+{{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal -
                                                                8000,
                                                        )
                                                    }}
                                                    / porsi</strong
                                                >. Mohon koreksi harga negosiasi
                                                supplier atau koordinasikan
                                                dengan Ahli Gizi.
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center gap-2.5 shadow-2xs"
                                    >
                                        <CheckCircle2
                                            class="h-4 w-4 text-emerald-600 shrink-0"
                                        />
                                        <span
                                            class="text-[11px] text-emerald-900 font-medium"
                                        >
                                            Biaya bahan baku PK
                                            <strong>aman dan efisien</strong>
                                            sesuai standar pagu BGN (Hemat/Sisa:
                                            <strong
                                                >{{
                                                    formatRupiah(
                                                        8000 -
                                                            totalFoodCostPKNormal,
                                                    )
                                                }}
                                                / porsi</strong
                                            >).
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Food Cost PB (Batas Rp 10.000) -->
                            <Card
                                className="bg-white border-slate-200/90 shadow-sm transition-all rounded-2xl overflow-hidden hover:shadow-md"
                                :class="{
                                    'border-rose-300 ring-2 ring-rose-200/80 bg-rose-50/10':
                                        totalFoodCostPBNormal > 10000,
                                }"
                            >
                                <CardHeader
                                    class="p-4 sm:p-5 border-b flex flex-row items-center justify-between gap-3"
                                    :class="
                                        totalFoodCostPBNormal > 10000
                                            ? 'bg-rose-50/70 border-rose-100'
                                            : 'bg-gradient-to-r from-indigo-50/70 to-blue-50/40 border-slate-100'
                                    "
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 shadow-2xs"
                                            :class="
                                                totalFoodCostPBNormal > 10000
                                                    ? 'bg-rose-100 text-rose-700 border border-rose-200'
                                                    : 'bg-white text-indigo-600 border border-indigo-200/80'
                                            "
                                        >
                                            <UtensilsCrossed class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-[10px] font-black uppercase tracking-wider"
                                                :class="
                                                    totalFoodCostPBNormal >
                                                    10000
                                                        ? 'text-rose-800'
                                                        : 'text-indigo-800/80'
                                                "
                                            >
                                                Standar BGN • Porsi Besar
                                            </p>
                                            <CardTitle
                                                class="text-base font-black text-slate-900 mt-0.5"
                                            >
                                                Food Cost Porsi Besar (PB)
                                            </CardTitle>
                                            <p
                                                class="text-xs text-slate-500 font-medium"
                                            >
                                                Batas Pagu:
                                                <strong
                                                    class="text-slate-800 font-bold"
                                                    >Rp 10.000 / porsi</strong
                                                >
                                            </p>
                                        </div>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        :className="
                                            totalFoodCostPBNormal <= 10000
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-xs px-2.5 py-1 shadow-2xs'
                                                : 'bg-rose-100 text-rose-800 border-rose-300 font-black text-xs px-2.5 py-1 animate-pulse shadow-2xs'
                                        "
                                    >
                                        <span
                                            v-if="
                                                totalFoodCostPBNormal <= 10000
                                            "
                                            class="flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-emerald-500"
                                            ></span>
                                            <span>EFISIEN / AMAN</span>
                                        </span>
                                        <span
                                            v-else
                                            class="flex items-center gap-1.5"
                                        >
                                            <AlertTriangle
                                                class="h-3.5 w-3.5 text-rose-600"
                                            />
                                            <span>OVER BUDGET</span>
                                        </span>
                                    </Badge>
                                </CardHeader>
                                <CardContent className="p-4 sm:p-5 space-y-4">
                                    <!-- Dual Stat Grid -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                    >
                                        <!-- Metric 1: Realisasi -->
                                        <div
                                            class="p-3.5 rounded-xl border flex flex-col justify-between transition-all"
                                            :class="
                                                totalFoodCostPBNormal > 10000
                                                    ? 'bg-rose-50/50 border-rose-200'
                                                    : 'bg-slate-50/80 border-slate-200/80'
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-1"
                                            >
                                                <span
                                                    class="text-xs font-bold text-slate-600"
                                                >
                                                    Total Food Cost (Realisasi)
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-slate-500 border border-slate-200 shadow-2xs"
                                                >
                                                    PB
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h3
                                                    class="text-2xl sm:text-3xl font-black tracking-tight"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'text-rose-950'
                                                            : 'text-slate-900'
                                                    "
                                                >
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                </h3>
                                                <p
                                                    class="text-[11px] text-slate-400 font-medium mt-0.5"
                                                >
                                                    Biaya riil per porsi MBG
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Metric 2: Sisa Pagu / Selisih Lebih -->
                                        <div
                                            class="p-3.5 rounded-xl border flex flex-col justify-between transition-all"
                                            :class="
                                                totalFoodCostPBNormal > 10000
                                                    ? 'bg-gradient-to-br from-rose-50 to-red-50/60 border-rose-200 text-rose-900'
                                                    : 'bg-gradient-to-br from-emerald-50/90 to-teal-50/60 border-emerald-200/80 text-emerald-900'
                                            "
                                        >
                                            <div
                                                class="flex items-center justify-between gap-1"
                                            >
                                                <span
                                                    class="text-xs font-bold"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'text-rose-800'
                                                            : 'text-emerald-800'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? "Selisih Lebih (Over)"
                                                            : "Sisa Pagu Anggaran"
                                                    }}
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-extrabold shadow-2xs"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'bg-rose-200/80 text-rose-950 border border-rose-300'
                                                            : 'bg-emerald-200/80 text-emerald-950 border border-emerald-300'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? "Defisit"
                                                            : "Hemat"
                                                    }}
                                                </span>
                                            </div>
                                            <div class="mt-2">
                                                <h4
                                                    class="text-2xl sm:text-3xl font-black tracking-tight"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'text-rose-700'
                                                            : 'text-emerald-700'
                                                    "
                                                >
                                                    <span
                                                        v-if="
                                                            totalFoodCostPBNormal >
                                                            10000
                                                        "
                                                        >+</span
                                                    >{{
                                                        formatRupiah(
                                                            Math.abs(
                                                                10000 -
                                                                    totalFoodCostPBNormal,
                                                            ),
                                                        )
                                                    }}
                                                </h4>
                                                <p
                                                    class="text-[11px] font-medium mt-0.5"
                                                    :class="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? 'text-rose-600'
                                                            : 'text-emerald-700/80'
                                                    "
                                                >
                                                    {{
                                                        totalFoodCostPBNormal >
                                                        10000
                                                            ? "Melebihi batas pagu BGN"
                                                            : "Tersisa dari pagu Rp 10.000"
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Budget Utilization Progress Bar -->
                                    <div
                                        class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center justify-between text-xs"
                                        >
                                            <span
                                                class="font-bold text-slate-600 flex items-center gap-1.5"
                                            >
                                                <Coins
                                                    class="h-3.5 w-3.5 text-slate-400"
                                                />
                                                Utilisasi Anggaran Pagu:
                                            </span>
                                            <span
                                                class="font-black"
                                                :class="
                                                    totalFoodCostPBNormal >
                                                    10000
                                                        ? 'text-rose-700'
                                                        : 'text-slate-800'
                                                "
                                            >
                                                {{
                                                    (
                                                        (totalFoodCostPBNormal /
                                                            10000) *
                                                        100
                                                    ).toFixed(1)
                                                }}%
                                                <span
                                                    class="text-[10px] font-normal text-slate-400"
                                                >
                                                    ({{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                    / Rp 10.000)
                                                </span>
                                            </span>
                                        </div>
                                        <div
                                            class="h-2 w-full rounded-full bg-slate-200/80 overflow-hidden"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="
                                                    totalFoodCostPBNormal >
                                                    10000
                                                        ? 'bg-rose-500'
                                                        : totalFoodCostPBNormal >
                                                            8500
                                                          ? 'bg-amber-500'
                                                          : 'bg-emerald-500'
                                                "
                                                :style="{
                                                    width: `${Math.min(100, (totalFoodCostPBNormal / 10000) * 100)}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Alert Status Banner -->
                                    <div
                                        v-if="totalFoodCostPBNormal > 10000"
                                        class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-start gap-2.5 shadow-2xs"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 text-rose-600 shrink-0 mt-0.5"
                                        />
                                        <div class="space-y-0.5">
                                            <p
                                                class="font-extrabold text-rose-950 text-xs"
                                            >
                                                ⚠️ Peringatan: Melebihi Batas
                                                Pagu PB!
                                            </p>
                                            <p
                                                class="text-[11px] text-rose-800 leading-relaxed"
                                            >
                                                Food cost PB (<strong>{{
                                                    formatRupiah(
                                                        totalFoodCostPBNormal,
                                                    )
                                                }}</strong
                                                >) melampaui pagu maksimal
                                                <strong
                                                    >Rp 10.000 / porsi</strong
                                                >
                                                dengan selisih lebih
                                                <strong
                                                    >+{{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal -
                                                                10000,
                                                        )
                                                    }}
                                                    / porsi</strong
                                                >. Mohon koreksi harga negosiasi
                                                supplier atau koordinasikan
                                                dengan Ahli Gizi.
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center gap-2.5 shadow-2xs"
                                    >
                                        <CheckCircle2
                                            class="h-4 w-4 text-emerald-600 shrink-0"
                                        />
                                        <span
                                            class="text-[11px] text-emerald-900 font-medium"
                                        >
                                            Biaya bahan baku PB
                                            <strong>aman dan efisien</strong>
                                            sesuai standar pagu BGN (Hemat/Sisa:
                                            <strong
                                                >{{
                                                    formatRupiah(
                                                        10000 -
                                                            totalFoodCostPBNormal,
                                                    )
                                                }}
                                                / porsi</strong
                                            >).
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Rincian Food Cost per Komponen Bahan Menu (Realisasi Akuntan) -->
                        <Card
                            className="bg-white border-slate-200 shadow-xs overflow-hidden"
                        >
                            <CardHeader
                                className="p-3.5 sm:p-4 border-b border-slate-100 bg-slate-50/50"
                            >
                                <CardTitle
                                    class="text-sm sm:text-base font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Coins class="h-4 w-4 text-primary" />
                                    <span
                                        >Rincian Food Cost per Komponen Bahan
                                        Menu (Realisasi Akuntan)</span
                                    >
                                </CardTitle>
                            </CardHeader>
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full min-w-[650px] text-left text-xs border-collapse"
                                >
                                    <thead
                                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                    >
                                        <tr>
                                            <th class="p-3 text-center w-10">
                                                No
                                            </th>
                                            <th class="p-3">Nama Bahan Baku</th>
                                            <th class="p-3">Kategori</th>
                                            <th class="p-3 text-right">
                                                Gram PK
                                            </th>
                                            <th class="p-3 text-right">
                                                Food Cost PK
                                            </th>
                                            <th class="p-3 text-right">
                                                Gram PB
                                            </th>
                                            <th class="p-3 text-right">
                                                Food Cost PB
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-slate-100 text-slate-800"
                                    >
                                        <tr
                                            v-for="(
                                                b, idx
                                            ) in bahanCalculations"
                                            :key="idx"
                                            class="hover:bg-slate-50/70"
                                        >
                                            <td
                                                class="p-3 text-center font-bold text-slate-500"
                                            >
                                                {{ idx + 1 }}
                                            </td>
                                            <td
                                                class="p-3 font-bold text-slate-900"
                                            >
                                                <div
                                                    class="text-slate-950 font-bold text-xs"
                                                >
                                                    {{ b.nama_po || b.nama }}
                                                </div>
                                                <span
                                                    v-if="
                                                        b.nama_po &&
                                                        b.nama_po !== b.nama
                                                    "
                                                    class="block text-[10px] text-slate-400 font-normal"
                                                >
                                                    TKPI: {{ b.nama }}
                                                </span>
                                                <span
                                                    v-if="
                                                        b.tipe_porsi ===
                                                        'alergi'
                                                    "
                                                    class="inline-block mt-0.5 px-1.5 py-0.2 rounded bg-rose-100 text-rose-800 text-[10px] font-bold"
                                                >
                                                    Alergi: {{ b.jenis_alergi }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-slate-500">
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
                                                colspan="4"
                                                class="p-3.5 uppercase tracking-wider text-slate-700"
                                            >
                                                Grand Total Food Cost per Porsi:
                                            </td>
                                            <td
                                                class="p-3.5 text-right font-black text-sm"
                                                :class="
                                                    totalFoodCostPKNormal > 8000
                                                        ? 'text-rose-700'
                                                        : 'text-amber-950'
                                                "
                                            >
                                                <div>
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                </div>
                                                <span
                                                    v-if="
                                                        totalFoodCostPKNormal >
                                                        8000
                                                    "
                                                    class="inline-block text-[10px] text-rose-700 bg-rose-100 px-1.5 py-0.5 rounded font-extrabold mt-0.5"
                                                >
                                                    ⚠️ Over +{{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal -
                                                                8000,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-block text-[10px] text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-extrabold mt-0.5"
                                                >
                                                    ✓ Sisa
                                                    {{
                                                        formatRupiah(
                                                            8000 -
                                                                totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                </span>
                                            </td>
                                            <td
                                                class="p-3.5 text-slate-400"
                                            ></td>
                                            <td
                                                class="p-3.5 text-right font-black text-sm"
                                                :class="
                                                    totalFoodCostPBNormal >
                                                    10000
                                                        ? 'text-rose-700'
                                                        : 'text-indigo-950'
                                                "
                                            >
                                                <div>
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                </div>
                                                <span
                                                    v-if="
                                                        totalFoodCostPBNormal >
                                                        10000
                                                    "
                                                    class="inline-block text-[10px] text-rose-700 bg-rose-100 px-1.5 py-0.5 rounded font-extrabold mt-0.5"
                                                >
                                                    ⚠️ Over +{{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal -
                                                                10000,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-block text-[10px] text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded font-extrabold mt-0.5"
                                                >
                                                    ✓ Sisa
                                                    {{
                                                        formatRupiah(
                                                            10000 -
                                                                totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- 5. SUB MENU 5: KALENDER MENU (SIKLUS & JADWAL HARIAN MBG) -->
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
