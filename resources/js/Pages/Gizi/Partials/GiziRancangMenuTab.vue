<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";
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
    UtensilsCrossed,
    Users,
    ClipboardList,
    FileSpreadsheet,
    Package,
    ShoppingBag,
    Apple,
    Activity,
    Coins,
    Calculator,
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
    tkpiDatasets: {
        type: Object,
        default: () => ({
            fta: [],
            csv: [],
        }),
    },
    selectedSource: {
        type: String,
        default: "fta",
    },
    initialStep: {
        type: String,
        default: null,
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    activeWorkOrder: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["update-source"]);

// State Tampilan Info / Edukasi Rumus Perhitungan Gizi & Biaya (Default Hidden)
const showRumusBahan = ref(false);
const showRumusAkg = ref(false);
const showRumusCost = ref(false);

const normalizeStep = (step) => {
    if (
        step === "bahan_pangan" ||
        step === "bahan-pangan" ||
        step === "pre_order"
    )
        return "bahan_pangan";
    if (
        step === "formula_gizi" ||
        step === "formula-gizi" ||
        step === "formula"
    )
        return "formula_gizi";
    if (
        step === "order" ||
        step === "review" ||
        step === "review_pengajuan" ||
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

const buatMenuSubTabs = [
    {
        id: "work_order",
        label: "1. Perencanaan Produksi",
        icon: FileSpreadsheet,
    },
    {
        id: "bahan_pangan",
        label: "2. Bahan Pangan",
        icon: Package,
    },
    {
        id: "formula_gizi",
        label: "3. Formula Gizi",
        icon: ClipboardList,
    },
    {
        id: "order",
        label: "4. Review & Pengajuan",
        icon: ShieldCheck,
    },
];

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
    sub_menu_1: "",
    sub_menu_2: "",
    sub_menu_3: "",
    sub_menu_4: "",
    sub_menu_5: "",
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

const modalPmError = ref("");

function handleOpenModalEditPm(kelompok) {
    editingKelompok.value = kelompok;
    modalPmError.value = "";
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

    if (
        modalTotalPm.value === 0 &&
        editingKelompok.value.status_menerima !== false
    ) {
        modalPmError.value = `Total porsi untuk "${editingKelompok.value.nama_kelompok}" minimal 1 porsi. Jika tidak menerima distribusi, silakan tandai status kelompok menjadi 'Tidak Menerima'.`;
        return;
    }

    modalPmError.value = "";

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

function setTanggalHariIni() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");
    tanggalRencana.value = `${year}-${month}-${day}`;
    clearError("tanggalRencana");
}

function setTanggalBesok() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const year = tomorrow.getFullYear();
    const month = String(tomorrow.getMonth() + 1).padStart(2, "0");
    const day = String(tomorrow.getDate()).padStart(2, "0");
    tanggalRencana.value = `${year}-${month}-${day}`;
    clearError("tanggalRencana");
}

// Isi otomatis template contoh menu MBG
function handleGunakanContoh() {
    namaMenuAktif.value = "Ayam Guling Khas Bali";
    subMenuKomponen.value.sub_menu_1 = "Nasi Putih";
    subMenuKomponen.value.sub_menu_2 = "Ayam Guling";
    subMenuKomponen.value.sub_menu_3 = "Tempe Goreng";
    subMenuKomponen.value.sub_menu_4 = "Sayur Bening Bayam";
    subMenuKomponen.value.sub_menu_5 = "Buah Jeruk";

    clearError("namaMenuAktif");
    clearError("sub_menu_1");
    clearError("sub_menu_2");
    clearError("sub_menu_3");
    clearError("sub_menu_4");
    clearError("sub_menu_5");
}

// Resep Bahan Baku Baku Terpilih dari Database Resmi TKPI 2020 (Default Kosong dari 0)
const selectedBahanList = ref([]);

// Resep Bahan Pengganti / Substitusi untuk Varian Alergi (misal: Alergi Telur)
const varianAlergiTelurBahan = computed(() => {
    if (selectedBahanList.value.length === 0) return [];
    return selectedBahanList.value.filter((b) => b.alergen !== "Telur");
});

// Database Master TKPI Aktif (NutriSurvey Indo .fta / TKPI 2020 .csv)
const tkpiItems = computed(() => props.tkpiList || []);

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
    return totalKg;
}

function formatGrossWeight(kg) {
    if (kg === null || kg === undefined || kg === "" || isNaN(Number(kg)))
        return "0 kg";
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

// Combobox Selector Bahan Pangan Baku (Langkah 2)
const isComboboxOpen = ref(false);
const searchTkpiQuery = ref("");
const selectedTkpiItem = ref(null);

const filteredTkpiList = computed(() => {
    const list = props.tkpiList || [];
    if (!searchTkpiQuery.value) {
        return list.slice(0, 100);
    }
    const q = searchTkpiQuery.value.toLowerCase().trim();
    const searchTerms = q.split(/\s+/).filter(Boolean);
    return list
        .filter((item) => {
            const itemStr =
                `${item.nama || ""} ${item.kategori || ""} ${item.kategori_raw || ""} ${item.id || ""} ${item.code || ""}`.toLowerCase();
            return searchTerms.every((term) => itemStr.includes(term));
        })
        .slice(0, 100);
});

function selectTkpiItem(master) {
    if (!master) return;
    let bddValue = 100;
    if (master.bdd !== undefined && master.bdd !== null && master.bdd !== "") {
        const parsed = Number(master.bdd);
        if (!isNaN(parsed) && parsed > 0) {
            bddValue = parsed;
        }
    }

    selectedBahanList.value.push({
        id: master.id || master.code,
        code: master.code,
        tkpi_id: master.id || master.code,
        kategori: master.kategori || "Lainnya",
        nama: master.nama,
        nama_po: master.nama,
        tipe_porsi: "normal",
        jenis_alergi: "",
        gram_pk: 0,
        gram_pb: 0,
        bdd: bddValue,
        buffer: 0,
        harga_master: master.harga || master.harga_master || 0,
        harga_aktual: master.harga || master.harga_master || 0,
        alergen: master.alergen || "",
        tkpi: master,
    });

    isComboboxOpen.value = false;
    searchTkpiQuery.value = "";
    selectedTkpiItem.value = null;
}

function handleAddBahan() {
    if (selectedTkpiItem.value) {
        selectTkpiItem(selectedTkpiItem.value);
    }
}

function onGlobalWindowClick() {
    isComboboxOpen.value = false;
    isComboboxGiziOpen.value = false;
}

onMounted(() => {
    window.addEventListener("click", onGlobalWindowClick);
});

onUnmounted(() => {
    window.removeEventListener("click", onGlobalWindowClick);
});

const validationErrors = ref({});

function clearError(field) {
    if (validationErrors.value[field]) {
        delete validationErrors.value[field];
    }
}

function scrollToFirstError() {
    nextTick(() => {
        // Cari elemen dengan indikator error pertama
        const firstErrorEl = document.querySelector(
            ".border-rose-400, .border-rose-500, [data-error-target], .bg-rose-50\\/20, #error-kelompok-target",
        );
        if (firstErrorEl) {
            firstErrorEl.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
            // Fokuskan jika elemen atau child merupakan field input
            if (
                typeof firstErrorEl.focus === "function" &&
                (firstErrorEl.tagName === "INPUT" ||
                    firstErrorEl.tagName === "SELECT" ||
                    firstErrorEl.tagName === "TEXTAREA")
            ) {
                firstErrorEl.focus();
            } else {
                const innerInput = firstErrorEl.querySelector(
                    "input, select, textarea",
                );
                if (innerInput && typeof innerInput.focus === "function") {
                    innerInput.focus();
                }
            }
        }
    });
}

function validateStep1() {
    const errs = {};
    if (!tanggalRencana.value) {
        errs.tanggalRencana = "Tanggal rencana masak & distribusi wajib diisi.";
    } else {
        // Cek duplikasi tanggal (hanya boleh 1 WO per tanggal)
        const duplicateDateWo = (props.workOrdersList || []).find((w) => {
            const wTgl =
                typeof w.tanggal_distribusi === "string"
                    ? w.tanggal_distribusi.substring(0, 10)
                    : "";
            const isSameDate = wTgl === tanggalRencana.value;
            const isDifferentWo =
                w.nomor_wo !== woNo.value &&
                w.uuid !== props.activeWorkOrder?.uuid &&
                w.id !== props.activeWorkOrder?.id;
            return isSameDate && isDifferentWo;
        });
        if (duplicateDateWo) {
            errs.tanggalRencana = `Tanggal ini sudah memiliki Work Order ("${duplicateDateWo.nama_menu}"). Hanya boleh 1 menu per tanggal.`;
        }
    }
    if (!namaMenuAktif.value || !namaMenuAktif.value.trim()) {
        errs.namaMenuAktif = "Nama menu wajib diisi.";
    }
    if (
        !subMenuKomponen.value.sub_menu_1 ||
        !subMenuKomponen.value.sub_menu_1.trim()
    ) {
        errs.sub_menu_1 = "Sub Menu 1 wajib diisi.";
    }
    if (
        !subMenuKomponen.value.sub_menu_2 ||
        !subMenuKomponen.value.sub_menu_2.trim()
    ) {
        errs.sub_menu_2 = "Sub Menu 2 wajib diisi.";
    }
    if (
        !subMenuKomponen.value.sub_menu_3 ||
        !subMenuKomponen.value.sub_menu_3.trim()
    ) {
        errs.sub_menu_3 = "Sub Menu 3 wajib diisi.";
    }
    if (
        !subMenuKomponen.value.sub_menu_4 ||
        !subMenuKomponen.value.sub_menu_4.trim()
    ) {
        errs.sub_menu_4 = "Sub Menu 4 wajib diisi.";
    }
    if (
        !subMenuKomponen.value.sub_menu_5 ||
        !subMenuKomponen.value.sub_menu_5.trim()
    ) {
        errs.sub_menu_5 = "Sub Menu 5 wajib diisi.";
    }
    if (kelompokMenerimaAktif.value.length === 0) {
        errs.kelompok =
            "Minimal 1 kelompok sasaran penerima manfaat harus berstatus Menerima.";
    } else {
        const zeroReceiving = woKelompokList.value.find(
            (k) =>
                k.status_menerima !== false &&
                (Number(k.total_porsi_kecil) || 0) +
                    (Number(k.total_porsi_besar) || 0) <=
                    0,
        );
        if (zeroReceiving) {
            errs.kelompok = `Kelompok "${zeroReceiving.nama_kelompok}" berstatus Menerima tetapi memiliki 0 porsi. Wajib minimal 1 porsi atau tandai 'Tidak Menerima'.`;
        }
    }
    validationErrors.value = errs;
    const isValid = Object.keys(errs).length === 0;
    if (!isValid) {
        scrollToFirstError();
    }
    return isValid;
}

function validateStep2() {
    const isStep1Valid = validateStep1();
    if (!isStep1Valid) {
        return false;
    }
    const errs = { ...validationErrors.value };

    if (!selectedBahanList.value || selectedBahanList.value.length === 0) {
        errs.selectedBahan =
            "Wajib memilih dan menambahkan minimal 1 bahan pangan dari Database TKPI 2020.";
    }

    selectedBahanList.value.forEach((b, i) => {
        if (
            b.tipe_porsi === "alergi" &&
            (!b.jenis_alergi || !b.jenis_alergi.trim())
        ) {
            errs["bahan_" + i + "_alergi"] = "Jenis alergi wajib dipilih.";
        }
        const pkVal = Number(b.gram_pk) || 0;
        const pbVal = Number(b.gram_pb) || 0;
        if (pkVal <= 0 && pbVal <= 0) {
            errs["bahan_" + i + "_gram"] =
                "Wajib isi minimal salah satu (PK atau PB) > 0";
        }
        if (
            b.bdd === null ||
            b.bdd === undefined ||
            b.bdd === "" ||
            Number(b.bdd) <= 0
        ) {
            errs["bahan_" + i + "_bdd"] = "BDD > 0%";
        }
        if (
            b.buffer === null ||
            b.buffer === undefined ||
            b.buffer === "" ||
            Number(b.buffer) < 0
        ) {
            errs["bahan_" + i + "_buffer"] = "Wajib >= 0%";
        }
    });

    validationErrors.value = errs;
    const isValid = Object.keys(errs).length === 0;
    if (!isValid) {
        scrollToFirstError();
    }
    return isValid;
}

function handleSwitchSubTab(targetTab) {
    if (targetTab === "work_order") {
        buatMenuSubTab.value = "work_order";
        return;
    }

    // Step 1 WAJIB VALID untuk dapat berpindah ke Step 2, 3, atau 4
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        return;
    }

    if (targetTab === "bahan_pangan" || targetTab === "pre_order") {
        buatMenuSubTab.value = "bahan_pangan";
        return;
    }

    if (targetTab === "formula-gizi" || targetTab === "formula_gizi") {
        if (!validateStep2()) {
            buatMenuSubTab.value = "bahan_pangan";
            return;
        }
        if (
            !selectedGiziBahanList.value ||
            selectedGiziBahanList.value.length === 0
        ) {
            syncGiziFromBahan();
        }
        buatMenuSubTab.value = "formula_gizi";
        return;
    }

    if (
        targetTab === "order" ||
        targetTab === "pembelian_bahan" ||
        targetTab === "pembelian-bahan"
    ) {
        if (!validateStep2()) {
            buatMenuSubTab.value = "bahan_pangan";
            return;
        }
        if (!validateStep3()) {
            buatMenuSubTab.value = "formula_gizi";
            return;
        }
        buatMenuSubTab.value = "order";
        return;
    }

    buatMenuSubTab.value = normalizeStep(targetTab);
}

function handleMulaiFormulasiWo() {
    if (!validateStep1()) {
        return;
    }
    buatMenuSubTab.value = "bahan_pangan";
}

function handleRemoveBahan(index) {
    selectedBahanList.value.splice(index, 1);
    // Clear error for this index if any
    delete validationErrors.value.selectedBahan;
}

function validateStep3() {
    const isStep1Valid = validateStep1();
    if (!isStep1Valid) return false;
    const isStep2Valid = validateStep2();
    if (!isStep2Valid) return false;
    const errs = { ...validationErrors.value };

    if (
        !selectedGiziBahanList.value ||
        selectedGiziBahanList.value.length === 0
    ) {
        errs.selectedGiziBahan =
            "Minimal pilih 1 bahan makanan untuk formulasi gizi siap santap.";
    }

    selectedGiziBahanList.value.forEach((b, i) => {
        if (
            b.tipe_porsi === "alergi" &&
            (!b.jenis_alergi || !b.jenis_alergi.trim())
        ) {
            errs["gizi_bahan_" + i + "_alergi"] = "Jenis alergi wajib dipilih.";
        }
        const pkVal = Number(b.gram_pk) || 0;
        const pbVal = Number(b.gram_pb) || 0;
        if (pkVal <= 0 && pbVal <= 0) {
            errs["gizi_bahan_" + i + "_gram"] =
                "Wajib isi minimal salah satu (PK atau PB) > 0";
        }
    });

    validationErrors.value = errs;
    const isValid = Object.keys(errs).length === 0;
    if (!isValid) {
        scrollToFirstError();
    }
    return isValid;
}

function handleLanjutStep3() {
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        return;
    }
    if (!validateStep2()) {
        return;
    }
    // Auto-sync jika Step 3 masih kosong
    if (
        !selectedGiziBahanList.value ||
        selectedGiziBahanList.value.length === 0
    ) {
        syncGiziFromBahan();
    }
    buatMenuSubTab.value = "formula_gizi";
}

function handleLanjutStep4() {
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        return;
    }
    if (!validateStep2()) {
        buatMenuSubTab.value = "bahan_pangan";
        return;
    }
    if (!validateStep3()) {
        return;
    }
    buatMenuSubTab.value = "order";
}

function simpanDraftStep3() {
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        return;
    }
    if (!validateStep2()) {
        buatMenuSubTab.value = "bahan_pangan";
        return;
    }
    if (!validateStep3()) return;
    isSubmitting.value = true;
    statusPengajuanWo.value = "Draft";
    const payload = getPayload("Draft", 4);

    router.post("/gizi/work-order", payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            triggerSubmitSuccess(
                "Draft Langkah 3 (Formula Gizi Siap Santap) berhasil disimpan!",
            );
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

function handleAjukanDraftPo() {
    if (validateStep2()) {
        buatMenuSubTab.value = "order";
    }
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

const grandTotalSemuaPM = computed(() => {
    return woKelompokList.value.reduce(
        (acc, k) =>
            acc +
            (Number(k.total_porsi_kecil) || 0) +
            (Number(k.total_porsi_besar) || 0),
        0,
    );
});

const persentasePmMenerima = computed(() => {
    if (grandTotalSemuaPM.value === 0) return 0;
    const pct = (totalPM.value / grandTotalSemuaPM.value) * 100;
    return Number.isInteger(pct) ? pct : parseFloat(pct.toFixed(1));
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
        subMenuKomponen.value.sub_menu_1 || "",
        subMenuKomponen.value.sub_menu_2 || "",
        subMenuKomponen.value.sub_menu_3 || "",
        subMenuKomponen.value.sub_menu_4 || "",
        subMenuKomponen.value.sub_menu_5 || "",
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

// Opsi Alergi dengan Statistik PM & Penandaan Disabled jika 0 Porsi
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
                ? `⛔ ${opt.label} (0 Porsi - Tidak ada di PM)`
                : `✓ ${opt.label} (${total} Porsi • PK: ${pk}, PB: ${pb})`,
        };
    });
});

// Kalkulasi Detail per Bahan (Gross Weight, Biaya Draft Master, Biaya Aktual Akuntan)
const bahanCalculations = computed(() => {
    return selectedBahanList.value.map((b) => {
        const tkpi =
            b.tkpi && b.tkpi.energi !== undefined
                ? b.tkpi
                : tkpiItems.value.find(
                      (i) =>
                          (b.tkpi_id &&
                              (i.id === b.tkpi_id || i.code === b.tkpi_id)) ||
                          (b.id && (i.id === b.id || i.code === b.id)) ||
                          (b.code && (i.id === b.code || i.code === b.code)) ||
                          (i.nama &&
                              b.nama &&
                              i.nama.toLowerCase().trim() ===
                                  b.nama.toLowerCase().trim()),
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

        // Kebutuhan Kotor Kg untuk PK dan PB (Presisi penuh tanpa pembulatan awal)
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
        const totalGrossKg = grossKgPK + grossKgPB;

        const netKgPK = ((Number(b.gram_pk) || 0) * targetPKCount) / 1000;
        const netKgPB = ((Number(b.gram_pb) || 0) * targetPBCount) / 1000;
        const totalNetKg = netKgPK + netKgPB;

        // Biaya PO
        let subtotalMaster = Math.round(totalGrossKg * (b.harga_master || 0));
        if (
            totalGrossKg > 0 &&
            (b.harga_master || 0) > 0 &&
            subtotalMaster === 0
        ) {
            subtotalMaster = Math.ceil(totalGrossKg * b.harga_master);
        }
        let subtotalAktual = Math.round(
            totalGrossKg * (b.harga_aktual || b.harga_master || 0),
        );
        if (
            totalGrossKg > 0 &&
            (b.harga_aktual || b.harga_master || 0) > 0 &&
            subtotalAktual === 0
        ) {
            subtotalAktual = Math.ceil(
                totalGrossKg * (b.harga_aktual || b.harga_master),
            );
        }

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
            netKgPK,
            netKgPB,
            totalNetKg,
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
// STATE & DATA FORMULA GIZI SIAP SANTAP (LANGKAH 3)
// ==========================================
const selectedGiziBahanList = ref([]);
const searchTkpiGiziQuery = ref("");
const isComboboxGiziOpen = ref(false);
const selectedTkpiGiziItem = ref(null);

const filteredGiziTkpiList = computed(() => {
    if (!searchTkpiGiziQuery.value) {
        return (props.tkpiList || []).slice(0, 100);
    }
    const q = searchTkpiGiziQuery.value.toLowerCase();
    return (props.tkpiList || [])
        .filter(
            (it) =>
                (it.nama && it.nama.toLowerCase().includes(q)) ||
                (it.kategori && it.kategori.toLowerCase().includes(q)) ||
                (it.code && String(it.code).toLowerCase().includes(q)),
        )
        .slice(0, 100);
});

function selectTkpiGiziItem(item) {
    selectedTkpiGiziItem.value = item;
    isComboboxGiziOpen.value = false;
    searchTkpiGiziQuery.value = item.nama;
    handleAddGiziBahan();
}

function handleAddGiziBahan() {
    if (!selectedTkpiGiziItem.value) return;
    const it = selectedTkpiGiziItem.value;
    const exists = selectedGiziBahanList.value.some(
        (b) => (b.id && b.id === it.id) || (b.code && b.code === it.code),
    );
    if (!exists) {
        selectedGiziBahanList.value.push({
            id: it.id || it.code,
            code: it.code,
            nama: it.nama,
            kategori: it.kategori || "Lainnya",
            tipe_porsi: "normal",
            jenis_alergi: "",
            alergen: it.alergen || "",
            gram_pk: 0,
            gram_pb: 0,
            bdd: it.bdd || 100,
            buffer: 0,
            harga_master: it.harga || it.harga_master || 0,
            tkpi: it,
        });
    }
    selectedTkpiGiziItem.value = null;
    searchTkpiGiziQuery.value = "";
}

function handleRemoveGiziBahan(index) {
    selectedGiziBahanList.value.splice(index, 1);
}

function syncGiziFromBahan() {
    if (!selectedBahanList.value || selectedBahanList.value.length === 0)
        return;
    selectedGiziBahanList.value = selectedBahanList.value.map((b) => ({
        id: b.id || b.code,
        code: b.code,
        nama: b.nama,
        kategori: b.kategori || "Lainnya",
        tipe_porsi: b.tipe_porsi || "normal",
        jenis_alergi: b.jenis_alergi || "",
        alergen: b.alergen || "",
        gram_pk: b.gram_pk || 0,
        gram_pb: b.gram_pb || 0,
        bdd: b.bdd || 100,
        buffer: b.buffer || 0,
        harga_master: b.harga_master || 0,
        tkpi:
            b.tkpi ||
            (props.tkpiList || []).find(
                (x) => x.id === b.id || x.code === b.code || x.nama === b.nama,
            ),
    }));
}

// Kalkulasi Formula Gizi Matang Siap Santap (Langkah 3)
const giziCalculations = computed(() => {
    return selectedGiziBahanList.value.map((b) => {
        const tkpi =
            b.tkpi ||
            (props.tkpiList || []).find(
                (item) =>
                    item.id === b.id ||
                    item.code === b.code ||
                    item.nama === b.nama,
            ) ||
            b;

        const isAlergi = b.tipe_porsi === "alergi";
        let targetPKCount = totalPK.value;
        let targetPBCount = totalPB.value;

        if (isAlergi && b.jenis_alergi) {
            const detailPm = findAlergiDetail(b.jenis_alergi);
            if (detailPm) {
                targetPKCount = Number(detailPm.porsi_kecil) || 0;
                targetPBCount = Number(detailPm.porsi_besar) || 0;
            } else {
                targetPKCount = 0;
                targetPBCount = 0;
            }
        }

        const netKgPK = ((Number(b.gram_pk) || 0) * targetPKCount) / 1000;
        const netKgPB = ((Number(b.gram_pb) || 0) * targetPBCount) / 1000;
        const totalNetKg = netKgPK + netKgPB;

        const nutrisiPK = calculateNutritionFromNetGram(tkpi, b.gram_pk);
        const nutrisiPB = calculateNutritionFromNetGram(tkpi, b.gram_pb);

        return {
            ...b,
            tkpi,
            isAlergi,
            targetPKCount,
            targetPBCount,
            totalTargetCount: targetPKCount + targetPBCount,
            netKgPK,
            netKgPB,
            totalNetKg,
            nutrisiPK,
            nutrisiPB,
        };
    });
});

// ==========================================
// 3. KALKULASI HASIL AKG
// ==========================================
const akgResultPKNormal = computed(() => {
    const res = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    giziCalculations.value
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
    giziCalculations.value
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
    const alergiBahanList = giziCalculations.value.filter(
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
        const bahanNormalSafe = giziCalculations.value.filter((b) => {
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
    return bahanCalculations.value
        .filter((item) => item.tipe_porsi !== "alergi")
        .reduce((acc, item) => acc + item.costPK, 0);
});
const totalFoodCostPBNormal = computed(() => {
    return bahanCalculations.value
        .filter((item) => item.tipe_porsi !== "alergi")
        .reduce((acc, item) => acc + item.costPB, 0);
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

// Food Cost per Porsi Khusus Varian Alergi (Disesuaikan dengan bahan aman + substitusi)
const activeAlergiFoodCostList = computed(() => {
    const alergiBahanList = giziCalculations.value.filter(
        (b) =>
            b.tipe_porsi === "alergi" &&
            b.jenis_alergi &&
            b.jenis_alergi.trim(),
    );
    if (alergiBahanList.length === 0) return [];

    const uniqueJenisMap = new Map();
    alergiBahanList.forEach((b) => {
        const jenis = b.jenis_alergi.trim();
        if (!uniqueJenisMap.has(jenis)) {
            uniqueJenisMap.set(jenis, []);
        }
        uniqueJenisMap.get(jenis).push(b);
    });

    const result = [];
    uniqueJenisMap.forEach((bahans, jenis) => {
        const detailPm = findAlergiDetail(jenis);
        const jmlPk = detailPm ? Number(detailPm.porsi_kecil) || 0 : 0;
        const jmlPb = detailPm ? Number(detailPm.porsi_besar) || 0 : 0;
        const jmlTotal = detailPm ? Number(detailPm.total) || 0 : 0;
        if (jmlTotal === 0) return;

        // Ambil bahan normal yang aman (tidak mengandung alergen terkait)
        const bahanNormalSafe = giziCalculations.value.filter((b) => {
            if (b.tipe_porsi !== "normal") return false;
            return !isBahanContainsAlergen(b, jenis);
        });

        const allItems = [...bahanNormalSafe, ...bahans];
        const costPK = allItems.reduce((acc, it) => acc + (it.costPK || 0), 0);
        const costPB = allItems.reduce((acc, it) => acc + (it.costPB || 0), 0);

        result.push({
            jenis_alergi: jenis,
            siswa_pk: jmlPk,
            siswa_pb: jmlPb,
            total_siswa: jmlTotal,
            cost_pk: costPK,
            cost_pb: costPB,
        });
    });

    return result;
});

function formatRupiah(num) {
    return "Rp " + (Number(num) || 0).toLocaleString("id-ID");
}

function handlePrintPo() {
    window.print();
}

// State Status Pengajuan Work Order
const statusPengajuanWo = ref("Draft");
const showSubmitSuccessAlert = ref(false);
const submitAlertMessage = ref("");

const isSubmitting = ref(false);

function triggerSubmitSuccess(msg) {
    submitAlertMessage.value = msg;
    showSubmitSuccessAlert.value = true;
    setTimeout(() => {
        showSubmitSuccessAlert.value = false;
    }, 4000);
}

function getPayload(statusStr, stepNumber = 3) {
    return {
        nomor_wo: woNo.value,
        tanggal_distribusi: tanggalRencana.value,
        nama_menu: namaMenuAktif.value || "Menu MBG",
        siklus_ke: 1,
        status: statusStr,
        database_pangan: props.selectedSource || "fta",
        current_step: stepNumber,
        sub_menu_1: subMenuKomponen.value.sub_menu_1 || null,
        sub_menu_2: subMenuKomponen.value.sub_menu_2 || null,
        sub_menu_3: subMenuKomponen.value.sub_menu_3 || null,
        sub_menu_4: subMenuKomponen.value.sub_menu_4 || null,
        sub_menu_5: subMenuKomponen.value.sub_menu_5 || null,
        total_pm: totalPM.value,
        total_pk: totalPK.value,
        total_pb: totalPB.value,
        total_alergi: totalPKAlergi.value + totalPBAlergi.value,
        total_kelompok: woKelompokList.value.length,
        akg_pk: akgResultPKNormal.value,
        akg_pb: akgResultPBNormal.value,
        food_cost_pk: totalFoodCostPKNormal.value,
        food_cost_pb: totalFoodCostPBNormal.value,
        total_anggaran_master: grandTotalDraftMaster.value,
        items: (bahanCalculations.value || []).map((b) => ({
            tkpi_id: b.id || b.code,
            nama: b.nama,
            nama_po: b.nama_po || b.nama,
            kategori: b.kategori,
            tipe_porsi: b.tipe_porsi || "normal",
            jenis_alergi: b.jenis_alergi || null,
            alergen: b.alergen || null,
            gram_pk: b.gram_pk || 0,
            gram_pb: b.gram_pb || 0,
            bdd: b.bdd || 100,
            buffer: b.buffer || 0,
            grossKgPK: b.grossKgPK || 0,
            grossKgPB: b.grossKgPB || 0,
            totalGrossKg: b.totalGrossKg || 0,
            harga_master: b.harga_master || 0,
            subtotalMaster: b.subtotalMaster || 0,
            nutrisiPK: b.nutrisiPK || null,
            nutrisiPB: b.nutrisiPB || null,
        })),
        kelompoks: woKelompokList.value.map((k) => {
            const masterK =
                (props.kelompokList || []).find((x) => x.id === k.id) || {};
            return {
                id: k.id,
                nama_kelompok: k.nama_kelompok,
                kategori: k.kategori,
                is_menerima: k.status_menerima !== false,
                total_porsi_kecil: Number(k.total_porsi_kecil) || 0,
                total_porsi_besar: Number(k.total_porsi_besar) || 0,
                total_penerima:
                    Number(k.total_penerima) ||
                    (Number(k.total_porsi_kecil) || 0) +
                        (Number(k.total_porsi_besar) || 0),
                status_alergi: k.has_alergi ? "Ada Alergi" : "Tidak Ada Alergi",
                rincian:
                    Array.isArray(k.rincian) && k.rincian.length > 0
                        ? k.rincian
                        : masterK.rincian || [],
                detail_alergi:
                    Array.isArray(k.keterangan_alergi) &&
                    k.keterangan_alergi.length > 0
                        ? k.keterangan_alergi
                        : masterK.keterangan_alergi || [],
            };
        }),
    };
}

function simpanDraftStep1() {
    if (!validateStep1()) return;
    isSubmitting.value = true;
    statusPengajuanWo.value = "Draft";
    const payload = getPayload("Draft", 1);

    router.post("/gizi/work-order", payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            triggerSubmitSuccess(
                "Draft Langkah 1 (Perencanaan Produksi) berhasil disimpan ke Database!",
            );
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

function simpanDraftStep2() {
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        return;
    }
    if (!validateStep2()) return;
    isSubmitting.value = true;
    statusPengajuanWo.value = "Draft";
    const payload = getPayload("Draft", 2);

    router.post("/gizi/work-order", payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            triggerSubmitSuccess(
                "Draft Langkah 2 (Bahan Pangan) berhasil disimpan ke Database!",
            );
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

function simpanSebagaiDraft() {
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        return;
    }
    isSubmitting.value = true;
    statusPengajuanWo.value = "Draft";
    const payload = getPayload("Draft", 3);

    router.post("/gizi/work-order", payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            router.visit("/gizi/daftar-menu");
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

function ajukanKeKeuangan() {
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        return;
    }
    if (!validateStep2()) {
        buatMenuSubTab.value = "bahan_pangan";
        return;
    }
    isSubmitting.value = true;
    statusPengajuanWo.value = "Diajukan ke Keuangan";
    const payload = getPayload("Diajukan ke Keuangan", 4);

    router.post("/gizi/work-order", payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            router.visit("/gizi/daftar-menu");
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
}

// Populate from activeWorkOrder if present
watch(
    () => props.activeWorkOrder,
    (wo) => {
        if (wo) {
            if (
                wo.database_pangan &&
                wo.database_pangan !== props.selectedSource
            ) {
                emit("update-source", wo.database_pangan);
            }
            woNo.value = wo.nomor_wo || woNo.value;
            tanggalRencana.value =
                typeof wo.tanggal_distribusi === "string"
                    ? wo.tanggal_distribusi.substring(0, 10)
                    : wo.tanggal_distribusi || tanggalRencana.value;
            namaMenuAktif.value = wo.nama_menu || namaMenuAktif.value;
            statusPengajuanWo.value = wo.status || "Draft";
            if (
                wo.sub_menu_1 ||
                wo.sub_menu_2 ||
                wo.sub_menu_3 ||
                wo.sub_menu_4 ||
                wo.sub_menu_5 ||
                wo.komponen_energi ||
                wo.komponen_protein ||
                wo.komponen_lemak ||
                wo.komponen_karbohidrat ||
                wo.komponen_serat
            ) {
                subMenuKomponen.value = {
                    sub_menu_1: wo.sub_menu_1 || wo.komponen_energi || "",
                    sub_menu_2: wo.sub_menu_2 || wo.komponen_protein || "",
                    sub_menu_3: wo.sub_menu_3 || wo.komponen_lemak || "",
                    sub_menu_4: wo.sub_menu_4 || wo.komponen_karbohidrat || "",
                    sub_menu_5: wo.sub_menu_5 || wo.komponen_serat || "",
                };
            }

            // 1. Populate selectedBahanList with full TKPI nutritional lookup
            if (wo.items && wo.items.length > 0) {
                selectedBahanList.value = wo.items.map((it) => {
                    const matchedTkpi =
                        tkpiItems.value.find(
                            (t) =>
                                (it.tkpi_id &&
                                    (t.id === it.tkpi_id ||
                                        t.code === it.tkpi_id)) ||
                                (t.nama &&
                                    it.nama &&
                                    t.nama.toLowerCase().trim() ===
                                        it.nama.toLowerCase().trim()),
                        ) || {};

                    return {
                        id: matchedTkpi.id || it.tkpi_id || it.id,
                        code: matchedTkpi.code || it.tkpi_id || it.id,
                        tkpi_id: matchedTkpi.id || it.tkpi_id || it.id,
                        nama: it.nama || matchedTkpi.nama,
                        nama_po: it.nama_po || it.nama || matchedTkpi.nama,
                        kategori:
                            it.kategori || matchedTkpi.kategori || "Lainnya",
                        tipe_porsi: it.tipe_porsi || "normal",
                        jenis_alergi: it.jenis_alergi || "",
                        alergen: it.alergen || matchedTkpi.alergen || "",
                        gram_pk: Number(it.gram_pk) || 0,
                        gram_pb: Number(it.gram_pb) || 0,
                        bdd: Number(it.bdd) || matchedTkpi.bdd || 100,
                        buffer:
                            it.buffer !== undefined &&
                            it.buffer !== null &&
                            it.buffer !== ""
                                ? Number(it.buffer)
                                : 0,
                        harga_master:
                            Number(it.harga_master) ||
                            matchedTkpi.harga_master ||
                            0,
                        harga_aktual:
                            Number(it.harga_aktual) ||
                            it.harga_master ||
                            matchedTkpi.harga_master ||
                            0,
                        tkpi: matchedTkpi,
                    };
                });
            }

            // 2. Populate woKelompokList with saved KPM participation and class breakdown
            if (wo.kelompoks && wo.kelompoks.length > 0) {
                woKelompokList.value = (props.kelompokList || []).map(
                    (masterK) => {
                        const savedK = wo.kelompoks.find(
                            (sk) =>
                                sk.kelompok_id === masterK.id ||
                                sk.nama_kelompok === masterK.nama_kelompok,
                        );
                        const norm = normalizeKelompokForWo(masterK);
                        if (savedK) {
                            return {
                                ...norm,
                                status_menerima: savedK.is_menerima !== false,
                                total_porsi_kecil:
                                    savedK.porsi_kecil !== undefined
                                        ? Number(savedK.porsi_kecil)
                                        : norm.total_porsi_kecil,
                                total_porsi_besar:
                                    savedK.porsi_besar !== undefined
                                        ? Number(savedK.porsi_besar)
                                        : norm.total_porsi_besar,
                                total_penerima:
                                    savedK.total_penerima !== undefined
                                        ? Number(savedK.total_penerima)
                                        : norm.total_penerima,
                                status_alergi:
                                    savedK.status_alergi || norm.status_alergi,
                                rincian:
                                    Array.isArray(savedK.rincian) &&
                                    savedK.rincian.length > 0
                                        ? savedK.rincian
                                        : norm.rincian,
                                keterangan_alergi:
                                    Array.isArray(savedK.detail_alergi) &&
                                    savedK.detail_alergi.length > 0
                                        ? savedK.detail_alergi
                                        : norm.keterangan_alergi,
                            };
                        }
                        return norm;
                    },
                );
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <!-- ========================================================================================= -->
    <!-- 4. SUB MENU 4: RANCANG MENU (PERENCANAAN & FORMULASI GIZI) -->
    <!-- ========================================================================================= -->
    <div class="space-y-6">
        <!-- Global Alert Feedback Sukses Simpan Draft / Ajukan -->
        <div
            v-if="showSubmitSuccessAlert"
            class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between gap-3 text-emerald-900 animate-in fade-in slide-in-from-top-2 duration-200 shadow-xs"
        >
            <div class="flex items-center gap-3">
                <div
                    class="h-9 w-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0"
                >
                    <CheckCircle2 class="h-5 w-5" />
                </div>
                <div>
                    <h4 class="text-xs sm:text-sm font-black">
                        {{ submitAlertMessage }}
                    </h4>
                    <p class="text-[11px] text-emerald-700 mt-0.5">
                        Status dokumen:
                        <strong class="uppercase">{{
                            statusPengajuanWo
                        }}</strong>
                    </p>
                </div>
            </div>
            <button
                type="button"
                @click="showSubmitSuccessAlert = false"
                class="text-emerald-500 hover:text-emerald-700 p-1 cursor-pointer"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <!-- Sub-tab pill bar for Rancang Menu -->
        <div
            class="bg-white rounded-2xl border border-slate-200/90 p-2 shadow-xs flex flex-wrap items-center gap-2 print:hidden"
        >
            <button
                v-for="sub in buatMenuSubTabs"
                :key="sub.id"
                type="button"
                @click="handleSwitchSubTab(sub.id)"
                :class="[
                    'px-4 py-2 rounded-xl text-xs font-extrabold transition-all flex items-center gap-2 cursor-pointer border',
                    buatMenuSubTab === sub.id
                        ? 'bg-primary text-white border-primary shadow-xs'
                        : 'bg-slate-50 text-slate-700 border-slate-200/70 hover:bg-slate-100 hover:text-slate-900',
                ]"
            >
                <component :is="sub.icon" class="h-3.5 w-3.5 shrink-0" />
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
                        class="px-2.5 py-0.5 text-xs font-black rounded-md bg-blue-500/20 text-blue-300 border border-blue-400/30"
                    >
                        {{ woNo }}
                    </span>
                    <span
                        class="px-2.5 py-0.5 text-xs font-bold rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center gap-1"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5" />PM Terkunci
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
                        subMenuKomponen.sub_menu_1 ||
                        subMenuKomponen.sub_menu_2 ||
                        subMenuKomponen.sub_menu_3 ||
                        subMenuKomponen.sub_menu_4 ||
                        subMenuKomponen.sub_menu_5
                    "
                    class="flex items-center gap-1.5 flex-wrap text-[11px] font-medium"
                >
                    <span
                        v-if="subMenuKomponen.sub_menu_1"
                        class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-200 border border-amber-400/30"
                    >
                        {{ subMenuKomponen.sub_menu_1 }}
                    </span>
                    <span
                        v-if="subMenuKomponen.sub_menu_2"
                        class="px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-200 border border-rose-400/30"
                    >
                        {{ subMenuKomponen.sub_menu_2 }}
                    </span>
                    <span
                        v-if="subMenuKomponen.sub_menu_3"
                        class="px-2 py-0.5 rounded-md bg-yellow-500/20 text-yellow-200 border border-yellow-400/30"
                    >
                        {{ subMenuKomponen.sub_menu_3 }}
                    </span>
                    <span
                        v-if="subMenuKomponen.sub_menu_4"
                        class="px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-200 border border-blue-400/30"
                    >
                        {{ subMenuKomponen.sub_menu_4 }}
                    </span>
                    <span
                        v-if="subMenuKomponen.sub_menu_5"
                        class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-200 border border-emerald-400/30"
                    >
                        {{ subMenuKomponen.sub_menu_5 }}
                    </span>
                </div>
                <div
                    class="flex items-center gap-3 flex-wrap text-xs text-blue-200"
                >
                    <span
                        >🎯 Total PM:
                        <strong
                            >{{ totalPM.toLocaleString("id-ID") }} Porsi</strong
                        ></span
                    >
                    <span
                        >• PK:
                        <strong
                            >{{ totalPK.toLocaleString("id-ID") }} Porsi</strong
                        ></span
                    >
                    <span
                        >• PB:
                        <strong
                            >{{ totalPB.toLocaleString("id-ID") }} Porsi</strong
                        ></span
                    >
                    <span v-if="totalPKAlergi + totalPBAlergi > 0"
                        >• Alergi:
                        <strong class="text-rose-300"
                            >{{ totalPKAlergi + totalPBAlergi }} Porsi</strong
                        ></span
                    >
                    <span
                        >• Terjadwal:
                        <strong class="text-slate-200 font-black">
                            {{ woKelompokList.length }} Kelompok
                            <span
                                class="text-[11px] font-normal text-slate-300"
                            >
                                ({{
                                    kelompokMenerimaAktif.length
                                }}
                                Menerima<span
                                    v-if="
                                        woKelompokList.length -
                                            kelompokMenerimaAktif.length >
                                        0
                                    "
                                    >,
                                    {{
                                        woKelompokList.length -
                                        kelompokMenerimaAktif.length
                                    }}
                                    Tidak Menerima</span
                                >)
                            </span>
                        </strong>
                    </span>
                </div>
            </div>
            <!-- <div class="flex items-center gap-2 shrink-0">
                <Button
                    type="button"
                    @click="buatMenuSubTab = 'work_order'"
                    className="bg-white/10 hover:bg-white/20 text-white text-xs font-bold px-3.5 h-9 flex items-center gap-1.5 border border-white/20 cursor-pointer shadow-none"
                >
                    <Edit3 class="h-3.5 w-3.5" />
                    <span>Ubah Work Order</span>
                </Button>
            </div> -->
        </div>

        <!-- ========================================================================================= -->
        <!-- Bagian 1: Work Order Produksi (Step 1) -->
        <!-- ========================================================================================= -->
        <div v-if="buatMenuSubTab === 'work_order'" class="space-y-6">
            <!-- Banner Catatan Penolakan jika WO ini sebelumnya ditolak oleh Keuangan -->
            <div
                v-if="
                    props.activeWorkOrder &&
                    (props.activeWorkOrder.status
                        ?.toLowerCase()
                        .includes('ditolak') ||
                        props.activeWorkOrder.catatan_keuangan)
                "
                class="p-4 sm:p-5 rounded-2xl bg-rose-50 border-2 border-rose-200 text-rose-900 space-y-2 shadow-xs"
            >
                <div
                    class="flex items-center gap-2 font-black text-rose-800 text-sm"
                >
                    <AlertCircle class="h-5 w-5 text-rose-600 shrink-0" />
                    <span>Work Order Ini Sebelumnya Ditolak oleh Keuangan</span>
                </div>
                <div
                    class="bg-white p-3.5 rounded-xl border border-rose-200/80 text-xs font-medium text-slate-800 whitespace-pre-wrap leading-relaxed shadow-2xs"
                >
                    <strong class="text-rose-900 block mb-1"
                        >Catatan Verifikator Keuangan:</strong
                    >
                    {{
                        props.activeWorkOrder.catatan_keuangan ||
                        "Silakan sesuaikan formula gizi atau anggaran belanja pada langkah berikutnya, kemudian ajukan kembali ke bagian keuangan."
                    }}
                </div>
            </div>
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
                            <div class="flex items-center gap-2 flex-wrap">
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
                            <!-- <CardDescription class="text-xs sm:text-sm mt-0.5">
                                Penetapan jadwal distribusi menu, penamaan paket
                                MBG, dan penguncian kuota Penerima Manfaat (PM)
                                resmi SPPG.
                            </CardDescription> -->
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
                                No. Perencanaan Produksi
                            </label>
                            <input
                                type="text"
                                :value="woNo"
                                readonly
                                disabled
                                class="w-full text-xs font-black text-slate-800 rounded-lg border-slate-200 bg-slate-100/80 p-2.5 cursor-not-allowed select-all"
                                title="Nomor Perencanaan Produksi otomatis mengacu pada tanggal distribusi kalender menu"
                            />
                        </div>

                        <!-- Kolom 2: Tanggal Distribusi Menu -->
                        <div class="space-y-1.5">
                            <div
                                class="flex items-center justify-between gap-1"
                            >
                                <label
                                    class="text-xs font-bold text-slate-700 block truncate"
                                >
                                    Tanggal Distribusi Menu
                                    <span class="text-rose-500">*</span>
                                </label>
                                <div
                                    class="flex items-center gap-1.5 shrink-0 text-[10.5px] font-bold text-primary"
                                >
                                    <button
                                        type="button"
                                        @click="setTanggalHariIni"
                                        class="hover:underline cursor-pointer"
                                        title="Pilih Tanggal Hari Ini"
                                    >
                                        Hari Ini
                                    </button>
                                    <span class="text-slate-300">•</span>
                                    <button
                                        type="button"
                                        @click="setTanggalBesok"
                                        class="hover:underline cursor-pointer"
                                        title="Pilih Tanggal Besok"
                                    >
                                        Besok
                                    </button>
                                </div>
                            </div>
                            <input
                                type="date"
                                v-model="tanggalRencana"
                                @change="clearError('tanggalRencana')"
                                required
                                :class="[
                                    'w-full text-xs font-bold rounded-lg border p-2.5 bg-white',
                                    validationErrors.tanggalRencana
                                        ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                        : 'border-slate-300 focus:ring-primary focus:border-primary',
                                ]"
                            />
                            <p
                                v-if="validationErrors.tanggalRencana"
                                class="text-[11px] text-rose-600 font-semibold mt-1 flex items-center gap-1"
                            >
                                <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                <span>{{
                                    validationErrors.tanggalRencana
                                }}</span>
                            </p>
                        </div>

                        <!-- Kolom 3: Nama Menu Produksi MBG -->
                        <div class="space-y-1.5">
                            <div
                                class="flex items-center justify-between gap-1"
                            >
                                <label
                                    class="text-xs font-bold text-slate-700 truncate"
                                >
                                    Nama Menu Produksi
                                    <span class="text-rose-500">*</span>
                                </label>
                                <button
                                    type="button"
                                    @click="handleGunakanContoh"
                                    class="text-[10.5px] font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer shrink-0 truncate max-w-[150px]"
                                    title="Gunakan Contoh Menu: Ayam Guling Khas Bali"
                                >
                                    <Sparkles
                                        class="h-3 w-3 shrink-0 text-amber-500"
                                    />
                                    <span class="truncate">Gunakan Contoh</span>
                                </button>
                            </div>
                            <input
                                type="text"
                                v-model="namaMenuAktif"
                                @input="clearError('namaMenuAktif')"
                                required
                                placeholder="Contoh: Mujair Nyat-Nyat Kintamani"
                                :class="[
                                    'w-full text-xs font-bold text-slate-900 rounded-lg border p-2.5 bg-white',
                                    validationErrors.namaMenuAktif
                                        ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                        : 'border-slate-300 focus:ring-primary focus:border-primary',
                                ]"
                            />
                            <p
                                v-if="validationErrors.namaMenuAktif"
                                class="text-[11px] text-rose-600 font-semibold mt-1 flex items-center gap-1"
                            >
                                <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                <span>{{
                                    validationErrors.namaMenuAktif
                                }}</span>
                            </p>
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
                                        Rincian Sub Menu
                                    </h4>
                                    <p class="text-[10.5px] text-slate-500">
                                        Input rincian untuk masing-masing Sub
                                        Menu 1 hingga Sub Menu 5.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"
                        >
                            <!-- Sub Menu 1 -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="
                                    validationErrors.sub_menu_1
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-amber-200/90'
                                "
                            >
                                <label
                                    class="text-[11px] font-black text-amber-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-amber-500"
                                    ></span>
                                    <span
                                        >Sub Menu 1
                                        <strong class="text-rose-500"
                                            >*</strong
                                        ></span
                                    >
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.sub_menu_1"
                                    @input="clearError('sub_menu_1')"
                                    placeholder="Karbohidrat"
                                    :class="[
                                        'w-full text-xs font-semibold rounded-lg border p-2',
                                        validationErrors.sub_menu_1
                                            ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                            : 'border-slate-200 focus:ring-amber-500 focus:border-amber-500 bg-slate-50/50',
                                    ]"
                                />
                                <p
                                    v-if="validationErrors.sub_menu_1"
                                    class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5"
                                >
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{
                                        validationErrors.sub_menu_1
                                    }}</span>
                                </p>
                            </div>

                            <!-- Sub Menu 2 -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="
                                    validationErrors.sub_menu_2
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-rose-200/90'
                                "
                            >
                                <label
                                    class="text-[11px] font-black text-rose-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-rose-500"
                                    ></span>
                                    <span
                                        >Sub Menu 2
                                        <strong class="text-rose-500"
                                            >*</strong
                                        ></span
                                    >
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.sub_menu_2"
                                    @input="clearError('sub_menu_2')"
                                    placeholder="Protein Hewani"
                                    :class="[
                                        'w-full text-xs font-semibold rounded-lg border p-2',
                                        validationErrors.sub_menu_2
                                            ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                            : 'border-slate-200 focus:ring-rose-500 focus:border-rose-500 bg-slate-50/50',
                                    ]"
                                />
                                <p
                                    v-if="validationErrors.sub_menu_2"
                                    class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5"
                                >
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{
                                        validationErrors.sub_menu_2
                                    }}</span>
                                </p>
                            </div>

                            <!-- Sub Menu 3 -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="
                                    validationErrors.sub_menu_3
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-yellow-200/90'
                                "
                            >
                                <label
                                    class="text-[11px] font-black text-yellow-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-yellow-500"
                                    ></span>
                                    <span
                                        >Sub Menu 3
                                        <strong class="text-rose-500"
                                            >*</strong
                                        ></span
                                    >
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.sub_menu_3"
                                    @input="clearError('sub_menu_3')"
                                    placeholder="Protein Nabati"
                                    :class="[
                                        'w-full text-xs font-semibold rounded-lg border p-2',
                                        validationErrors.sub_menu_3
                                            ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                            : 'border-slate-200 focus:ring-yellow-500 focus:border-yellow-500 bg-slate-50/50',
                                    ]"
                                />
                                <p
                                    v-if="validationErrors.sub_menu_3"
                                    class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5"
                                >
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{
                                        validationErrors.sub_menu_3
                                    }}</span>
                                </p>
                            </div>

                            <!-- Sub Menu 4 -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="
                                    validationErrors.sub_menu_4
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-blue-200/90'
                                "
                            >
                                <label
                                    class="text-[11px] font-black text-blue-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-blue-500"
                                    ></span>
                                    <span
                                        >Sub Menu 4
                                        <strong class="text-rose-500"
                                            >*</strong
                                        ></span
                                    >
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.sub_menu_4"
                                    @input="clearError('sub_menu_4')"
                                    placeholder="Sayur"
                                    :class="[
                                        'w-full text-xs font-semibold rounded-lg border p-2',
                                        validationErrors.sub_menu_4
                                            ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                            : 'border-slate-200 focus:ring-blue-500 focus:border-blue-500 bg-slate-50/50',
                                    ]"
                                />
                                <p
                                    v-if="validationErrors.sub_menu_4"
                                    class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5"
                                >
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{
                                        validationErrors.sub_menu_4
                                    }}</span>
                                </p>
                            </div>

                            <!-- Sub Menu 5 -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="
                                    validationErrors.sub_menu_5
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-emerald-200/90'
                                "
                            >
                                <label
                                    class="text-[11px] font-black text-emerald-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-emerald-500"
                                    ></span>
                                    <span
                                        >Sub Menu 5
                                        <strong class="text-rose-500"
                                            >*</strong
                                        ></span
                                    >
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.sub_menu_5"
                                    @input="clearError('sub_menu_5')"
                                    placeholder="Buah"
                                    :class="[
                                        'w-full text-xs font-semibold rounded-lg border p-2',
                                        validationErrors.sub_menu_5
                                            ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                            : 'border-slate-200 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50/50',
                                    ]"
                                />
                                <p
                                    v-if="validationErrors.sub_menu_5"
                                    class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5"
                                >
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{
                                        validationErrors.sub_menu_5
                                    }}</span>
                                </p>
                            </div>
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
                                        >Data Penerima Manfaat (PM) per Tanggal
                                        Distribusi</span
                                    >
                                </h4>
                                <!-- <p class="text-xs text-slate-500 mt-0.5">
                                    Kuota porsi terkunci otomatis berdasarkan
                                    data rekapitulasi porsi sasaran & penerima
                                    aktif SPPG pada tanggal tersebut.
                                </p> -->
                            </div>
                            <!-- <Badge
                                variant="outline"
                                class="bg-emerald-50 text-emerald-700 border-emerald-300 font-extrabold text-xs"
                            >
                                <CheckCircle2 class="h-3 w-3 mr-1" />
                                Terverifikasi
                            </Badge> -->
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
                                    Total PM
                                </p>
                                <h3 class="text-2xl font-black text-blue-950">
                                    {{ totalPM.toLocaleString("id-ID") }}
                                    <span
                                        class="text-xs font-semibold text-blue-700"
                                        >Porsi</span
                                    >
                                </h3>
                                <p
                                    class="text-[10.5px] text-blue-600 font-medium"
                                >
                                    {{ persentasePmMenerima }}% Kuota Distribusi
                                    Harian
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
                                <h3 class="text-2xl font-black text-amber-950">
                                    {{ totalPK.toLocaleString("id-ID") }}
                                    <span
                                        class="text-xs font-semibold text-amber-800"
                                        >Porsi</span
                                    >
                                </h3>
                                <p
                                    class="text-[10.5px] text-amber-700 font-medium"
                                >
                                    TK/RA, PAUD, SD/MI 1-3 & Balita
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
                                <h3 class="text-2xl font-black text-indigo-950">
                                    {{ totalPB.toLocaleString("id-ID") }}
                                    <span
                                        class="text-xs font-semibold text-indigo-800"
                                        >Porsi</span
                                    >
                                </h3>
                                <p
                                    class="text-[10.5px] text-indigo-700 font-medium"
                                >
                                    SD/MI 4-6, SMP/MTs, SMA/SMK/MA, Guru,
                                    Tendik, Bumil & Busui
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
                                <h3 class="text-2xl font-black text-rose-950">
                                    {{
                                        (
                                            totalPKAlergi + totalPBAlergi
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
                                        Daftar Kelompok Penerima Manfaat ({{
                                            woKelompokList.length
                                        }}
                                        Kelompok)
                                    </h5>
                                    <Badge
                                        variant="outline"
                                        class="text-[11px] font-extrabold bg-emerald-50 text-emerald-800 border-emerald-300"
                                    >
                                        <UserCheck class="h-3 w-3 mr-1" />
                                        {{ kelompokMenerimaAktif.length }}
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
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Kelompok yang dinyatakan
                                    <strong>"Tidak Menerima"</strong>
                                    kuotanya otomatis dinolkan dari perhitungan
                                    Work Order ini.
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
                            class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white"
                        >
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full min-w-[650px] text-left text-xs border-collapse"
                                >
                                    <thead>
                                        <tr
                                            class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                                        >
                                            <th
                                                class="py-3.5 px-3 text-center w-10"
                                            >
                                                No
                                            </th>
                                            <th class="py-3.5 px-3 text-center">
                                                Status
                                            </th>
                                            <th class="py-3.5 px-3">
                                                Nama Kelompok Sasaran
                                            </th>
                                            <th class="py-3.5 px-3">
                                                Kategori
                                            </th>
                                            <th class="py-3.5 px-3 text-center">
                                                Porsi Kecil (PK)
                                            </th>
                                            <th class="py-3.5 px-3 text-center">
                                                Porsi Besar (PB)
                                            </th>
                                            <th class="py-3.5 px-3 text-center">
                                                Total PM
                                            </th>
                                            <th class="py-3.5 px-3">
                                                Status Alergi
                                            </th>
                                            <th
                                                class="py-3.5 px-3 text-center w-28"
                                            >
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-slate-100 text-slate-800"
                                    >
                                        <tr
                                            v-for="(k, idx) in woKelompokList"
                                            :key="k.id"
                                            :class="[
                                                'transition-colors',
                                                k.status_menerima === false
                                                    ? 'bg-slate-50/90 text-slate-400 opacity-60 select-none'
                                                    : 'hover:bg-slate-50/60',
                                            ]"
                                        >
                                            <!-- Kolom Nomor -->
                                            <td
                                                class="p-3 text-center font-bold text-xs align-middle"
                                                :class="
                                                    k.status_menerima === false
                                                        ? 'text-slate-300'
                                                        : 'text-slate-500'
                                                "
                                            >
                                                {{ idx + 1 }}
                                            </td>

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
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-80 select-none"
                                                >
                                                    <UserX
                                                        class="h-3 w-3 mr-1 text-slate-400"
                                                    />
                                                    Tidak Menerima
                                                </span>
                                            </td>

                                            <!-- Nama Kelompok -->
                                            <td
                                                class="p-3 font-bold align-middle"
                                                :class="
                                                    k.status_menerima === false
                                                        ? 'text-slate-400 line-through opacity-70'
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
                                                    :class="[
                                                        'font-bold text-[11px]',
                                                        k.status_menerima ===
                                                        false
                                                            ? 'bg-slate-100 text-slate-400 border-slate-200 opacity-60'
                                                            : 'bg-slate-50 text-slate-700',
                                                    ]"
                                                >
                                                    {{ k.kategori }}
                                                </Badge>
                                            </td>

                                            <!-- Porsi Kecil -->
                                            <td
                                                class="p-3 text-center align-middle font-bold"
                                                :class="
                                                    k.status_menerima === false
                                                        ? 'text-slate-300 line-through font-normal'
                                                        : 'text-amber-900 bg-amber-50/20'
                                                "
                                            >
                                                {{ k.total_porsi_kecil }}
                                            </td>

                                            <!-- Porsi Besar -->
                                            <td
                                                class="p-3 text-center align-middle font-bold"
                                                :class="
                                                    k.status_menerima === false
                                                        ? 'text-slate-300 line-through font-normal'
                                                        : 'text-indigo-900 bg-indigo-50/20'
                                                "
                                            >
                                                {{ k.total_porsi_besar }}
                                            </td>

                                            <!-- Total PM -->
                                            <td
                                                class="p-3 text-center font-black text-sm align-middle"
                                                :class="
                                                    k.status_menerima === false
                                                        ? 'text-slate-300 line-through font-normal'
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
                                                                ? 'text-slate-300 line-through font-normal'
                                                                : 'text-rose-700'
                                                        "
                                                    >
                                                        ⚠️
                                                        {{ al.jenis_alergi }}:
                                                        <span
                                                            class="font-black text-rose-900 ml-0.5"
                                                            :class="
                                                                k.status_menerima ===
                                                                false
                                                                    ? 'text-slate-300 line-through'
                                                                    : ''
                                                            "
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
                                                            ? 'text-slate-300 line-through font-normal'
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
                                                    class="text-[11px] font-medium"
                                                    :class="
                                                        k.status_menerima ===
                                                        false
                                                            ? 'text-slate-300 line-through'
                                                            : 'text-emerald-700'
                                                    "
                                                >
                                                    ✓ Normal
                                                </div>
                                            </td>

                                            <!-- Aksi -->
                                            <td
                                                class="py-3 px-3 text-center align-middle"
                                            >
                                                <div
                                                    class="flex items-center justify-center gap-1.5"
                                                >
                                                    <button
                                                        type="button"
                                                        :disabled="
                                                            k.status_menerima ===
                                                            false
                                                        "
                                                        @click="
                                                            handleOpenModalEditPm(
                                                                k,
                                                            )
                                                        "
                                                        :class="[
                                                            k.status_menerima ===
                                                            false
                                                                ? 'opacity-30 cursor-not-allowed bg-slate-100 text-slate-300 border-slate-200 pointer-events-none'
                                                                : 'bg-amber-50 hover:bg-amber-100 text-amber-600 border-amber-200/80 cursor-pointer shadow-2xs',
                                                            'h-8 w-8 rounded-lg border flex items-center justify-center transition-colors',
                                                        ]"
                                                        :title="
                                                            k.status_menerima ===
                                                            false
                                                                ? 'Kelompok Tidak Menerima (Non-Aktif)'
                                                                : 'Edit Detail PM per Sub-Sub Kategori'
                                                        "
                                                    >
                                                        <Edit3
                                                            class="h-4 w-4"
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
                                                                false,
                                                            )
                                                        "
                                                        class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                        title="Tandai TIDAK MENERIMA Menu Hari Ini"
                                                    >
                                                        <UserX
                                                            class="h-4 w-4"
                                                        />
                                                    </button>
                                                    <button
                                                        v-else
                                                        type="button"
                                                        @click="
                                                            handleToggleStatusMenerima(
                                                                k,
                                                                true,
                                                            )
                                                        "
                                                        class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                        title="Aktifkan Kembali Penerimaan"
                                                    >
                                                        <RotateCcw
                                                            class="h-4 w-4"
                                                        />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div
                            v-if="validationErrors.kelompok"
                            class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-bold flex items-center gap-2 mt-3"
                        >
                            <AlertCircle
                                class="h-4 w-4 shrink-0 text-rose-600"
                            />
                            <span>{{ validationErrors.kelompok }}</span>
                        </div>
                    </div>

                    <!-- Bottom Action Button -->
                    <div
                        class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3.5"
                    >
                        <div class="text-xs text-slate-500">
                            Pastikan tanggal, nama menu, dan status penerima
                            sasaran sudah sesuai sebelum melanjutkan.
                        </div>
                        <div class="flex items-center gap-2.5 w-full sm:w-auto">
                            <Button
                                type="button"
                                @click="simpanDraftStep1"
                                :disabled="isSubmitting"
                                className="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold px-4 h-11 rounded-xl cursor-pointer w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-2xs"
                            >
                                <FileText class="h-4 w-4" />
                                <span>Simpan Draft (Langkah 1)</span>
                            </Button>
                            <Button
                                type="button"
                                @click="handleMulaiFormulasiWo"
                                className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-6 h-11 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer w-full sm:w-auto shrink-0 text-center"
                            >
                                <span>Lanjut ke Formula Gizi (Langkah 2)</span>
                                <ArrowRight class="h-4 w-4 shrink-0" />
                            </Button>
                        </div>
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
                                <h3 class="text-base font-black text-slate-900">
                                    Edit Rincian PM:
                                    {{ editingKelompok?.nama_kelompok }}
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Kategori:
                                    <strong class="text-slate-800">{{
                                        editingKelompok?.kategori
                                    }}</strong>
                                    • Wilayah:
                                    {{ editingKelompok?.desa_kelurahan }},
                                    {{ editingKelompok?.kecamatan }}
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
                        <div
                            v-if="modalPmError"
                            class="p-3 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-800 font-bold flex items-center gap-2"
                        >
                            <AlertCircle
                                class="h-4 w-4 shrink-0 text-rose-600"
                            />
                            <span>{{ modalPmError }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <h4
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                            >
                                Rincian Kuota Porsi / Penerima per Jenjang:
                            </h4>
                            <span class="text-xs text-slate-500">
                                Format input: Laki-laki (L) + Perempuan (P)
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
                                        <th class="p-3">Peruntukan Porsi</th>
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
                                        <th class="p-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 text-slate-800"
                                >
                                    <tr
                                        v-for="(r, rIdx) in editFormRincian"
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
                                                (Number(r.jumlah_laki_laki) ||
                                                    0) +
                                                (Number(r.jumlah_perempuan) ||
                                                    0)
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Input Khusus Kuota Porsi Alergi (Jenis Alergen Bersumber dari Master Data PM) -->
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
                                        >Penyesuaian Porsi Khusus Alergi
                                        (Membutuhkan Menu Substitusi)</span
                                    >
                                </h5>
                                <p class="text-[11px] text-rose-700 mt-0.5">
                                    Daftar jenis alergen bersumber dari master
                                    data
                                    <strong>Penerima Manfaat</strong>. Anda
                                    dapat menyesuaikan jumlah kuota porsi (PK /
                                    PB) untuk Work Order ini jika ada perubahan
                                    kehadiran.
                                </p>
                            </div>

                            <!-- Tabel Daftar Alergi Terdaftar -->
                            <div
                                v-if="editFormKeteranganAlergi.length > 0"
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
                                                Jenis Alergen (Master PM)
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
                                    Tidak ada riwayat alergi yang terdaftar
                                    untuk kelompok sasaran ini.
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Penambahan atau pengelolaan jenis alergen
                                    dilakukan melalui master data
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
                                    >Alergi: {{ modalGrandTotalAlergi }} Porsi
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

        <!-- ========================================================================================= -->
        <!-- Bagian 2: Bahan Pangan & Estimasi Biaya Belanja (Step 2) -->
        <!-- ========================================================================================= -->
        <div v-if="buatMenuSubTab === 'bahan_pangan'" class="space-y-6">
            <!-- Header Bahan Pangan Card -->
            <Card className="bg-white border-slate-200 shadow-xs">
                <CardHeader
                    className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                >
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <CardTitle
                                    class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                                >
                                    <Package class="h-5 w-5 text-primary" />
                                    <span
                                        >Langkah 2: Pemilihan Bahan Pangan &
                                        Estimasi Belanja (Bahan Mentah)</span
                                    >
                                </CardTitle>
                                <span
                                    class="px-2.5 py-0.5 text-xs font-extrabold rounded-md bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    Belanja Bahan Baku (PO)
                                </span>
                            </div>
                            <CardDescription
                                class="text-xs text-slate-500 mt-1"
                            >
                                Pilih bahan baku mentah dari database master
                                pangan, tentukan gramasi porsi, BDD, buffer, dan
                                estimasi biaya belanja (Food Cost).
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-4 sm:p-6 space-y-6">
                    <!-- Selector Tambah Bahan dari Master Database Pangan -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label
                                class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5"
                            >
                                <Search class="h-3.5 w-3.5 text-primary" />
                                <span>Pilih & Tambah Bahan Baku Mentah:</span>
                            </label>
                            <span
                                class="text-[11px] text-slate-500 font-medium"
                            >
                                Database:
                                <strong class="text-slate-800">{{
                                    selectedSource === "csv"
                                        ? "TKPI 2020"
                                        : "NutriSurvey FTA"
                                }}</strong>
                            </span>
                        </div>

                        <!-- Searchable Combobox Selector -->
                        <div class="relative" @click.stop>
                            <div
                                @click="isComboboxOpen = !isComboboxOpen"
                                class="w-full min-h-[46px] px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:border-primary/60 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 transition-all flex items-center justify-between gap-3 cursor-pointer shadow-2xs"
                            >
                                <div
                                    class="flex items-center gap-2.5 min-w-0 flex-1"
                                >
                                    <div
                                        class="h-7 w-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </div>
                                    <span
                                        v-if="!selectedTkpiItem"
                                        class="text-xs text-slate-400 font-normal truncate"
                                    >
                                        Ketik nama bahan atau klik untuk memilih
                                        bahan pangan...
                                    </span>
                                    <div
                                        v-else
                                        class="flex items-center gap-2 min-w-0 truncate"
                                    >
                                        <span
                                            class="text-xs font-bold text-slate-900 truncate"
                                            >{{ selectedTkpiItem.nama }}</span
                                        >
                                        <span
                                            class="text-[10.5px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 shrink-0 font-medium"
                                            >{{
                                                selectedTkpiItem.kategori
                                            }}</span
                                        >
                                    </div>
                                </div>
                                <ChevronDown
                                    class="h-4 w-4 text-slate-400 shrink-0 transition-transform"
                                    :class="{ 'rotate-180': isComboboxOpen }"
                                />
                            </div>

                            <!-- Dropdown Panel -->
                            <div
                                v-if="isComboboxOpen"
                                class="absolute z-50 left-0 right-0 top-full mt-1.5 bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                            >
                                <div
                                    class="p-2.5 border-b border-slate-100 bg-slate-50/70"
                                >
                                    <div class="relative">
                                        <Search
                                            class="h-3.5 w-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"
                                        />
                                        <input
                                            type="text"
                                            v-model="searchTkpiQuery"
                                            placeholder="Cari nama bahan pangan mentah..."
                                            class="w-full pl-8 pr-3 py-1.5 text-xs bg-white rounded-lg border border-slate-200 focus:outline-hidden focus:border-primary font-medium"
                                            autofocus
                                        />
                                    </div>
                                </div>
                                <div
                                    class="max-h-60 overflow-y-auto divide-y divide-slate-100"
                                >
                                    <div
                                        v-for="item in filteredTkpiList"
                                        :key="item.id || item.code"
                                        @click="selectTkpiItem(item)"
                                        class="p-3 hover:bg-primary/5 cursor-pointer transition-colors flex items-center justify-between gap-3 text-xs"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="font-bold text-slate-800 truncate"
                                            >
                                                {{ item.nama }}
                                            </div>
                                            <div
                                                class="text-[10.5px] text-slate-500 flex items-center gap-2 mt-0.5"
                                            >
                                                <span>{{ item.kategori }}</span>
                                                <span>•</span>
                                                <span
                                                    >BDD:
                                                    {{ item.bdd || 100 }}%</span
                                                >
                                                <span>•</span>
                                                <span
                                                    >{{
                                                        formatRupiah(
                                                            item.harga ||
                                                                item.harga_master ||
                                                                0,
                                                        )
                                                    }}
                                                    / Kg</span
                                                >
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            size="sm"
                                            className="bg-primary/10 text-primary hover:bg-primary hover:text-white h-7 px-2 text-[11px] font-bold rounded-lg shrink-0"
                                        >
                                            + Pilih
                                        </Button>
                                    </div>
                                    <div
                                        v-if="filteredTkpiList.length === 0"
                                        class="p-6 text-center text-xs text-slate-400"
                                    >
                                        Bahan tidak ditemukan.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panduan / Rumus Kebutuhan Bahan Mentah (Toggleable) -->
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 text-xs space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="font-bold text-slate-700 flex items-center gap-1.5 text-xs"
                            >
                                <HelpCircle class="h-3.5 w-3.5 text-primary" />
                                Panduan Rumus Perhitungan Kebutuhan Bahan Baku
                                Mentah
                            </span>
                            <button
                                type="button"
                                @click="showRumusBahan = !showRumusBahan"
                                class="text-[11px] text-primary font-bold hover:underline cursor-pointer"
                            >
                                {{
                                    showRumusBahan
                                        ? "Sembunyikan Rumus"
                                        : "Tampilkan Rumus"
                                }}
                            </button>
                        </div>
                        <div
                            v-if="showRumusBahan"
                            class="grid grid-cols-1 md:grid-cols-3 gap-2.5 pt-2 border-t border-slate-200 text-[11px] text-slate-600"
                        >
                            <div
                                class="p-2 bg-white rounded-lg border border-slate-200"
                            >
                                <strong class="text-slate-800 block mb-0.5"
                                    >1. Berat Bersih (Net Kg):</strong
                                >
                                <code>(Gram Bersih × Target PM) ÷ 1.000</code>
                            </div>
                            <div
                                class="p-2 bg-white rounded-lg border border-slate-200"
                            >
                                <strong class="text-slate-800 block mb-0.5"
                                    >2. Berat Kotor (Gross Kg):</strong
                                >
                                <code
                                    >(Net Gram ÷ (BDD/100)) × (1 + Buffer%) × PM
                                    ÷ 1.000</code
                                >
                            </div>
                            <div
                                class="p-2 bg-white rounded-lg border border-slate-200"
                            >
                                <strong class="text-slate-800 block mb-0.5"
                                    >3. Subtotal Biaya PO:</strong
                                >
                                <code>Gross Kg × Harga Satuan Master (Rp)</code>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Detail Bahan Baku Mentah -->
                    <div
                        class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs"
                    >
                        <div
                            class="p-3.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2"
                        >
                            <span
                                class="text-xs font-extrabold text-slate-800 uppercase tracking-wider"
                            >
                                Daftar Bahan Baku Mentah untuk Pengadaan &
                                Belanja (PO)
                            </span>
                            <span class="text-xs font-bold text-slate-500">
                                Total: {{ selectedBahanList.length }} Bahan Baku
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table
                                class="w-full min-w-[1000px] text-left text-xs border-collapse"
                            >
                                <thead>
                                    <tr
                                        class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                                    >
                                        <th class="p-3 text-center w-10">No</th>
                                        <th class="p-3 min-w-[150px]">
                                            Bahan Pangan
                                        </th>
                                        <th class="p-3 min-w-[130px]">
                                            Nama di PO
                                        </th>
                                        <th class="p-3 min-w-[100px]">
                                            Peruntukan
                                        </th>
                                        <th class="p-3 min-w-[80px]">
                                            Kategori
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[85px]"
                                        >
                                            PK (g)
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[85px]"
                                        >
                                            PB (g)
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[65px]"
                                        >
                                            BDD (%)
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[70px]"
                                        >
                                            Buffer (%)
                                        </th>
                                        <th
                                            class="p-3 text-right bg-amber-50/50 text-amber-950 min-w-[85px]"
                                        >
                                            Kg Bersih
                                        </th>
                                        <th
                                            class="p-3 text-right bg-blue-50/50 text-blue-950 min-w-[85px]"
                                        >
                                            Kg Kotor
                                        </th>
                                        <th
                                            class="p-3 text-right min-w-[105px]"
                                        >
                                            Harga / Kg
                                        </th>
                                        <th class="p-3 text-right min-w-[95px]">
                                            Subtotal
                                        </th>
                                        <th class="p-3 text-center w-12">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 text-slate-800"
                                >
                                    <tr v-if="selectedBahanList.length === 0">
                                        <td
                                            colspan="14"
                                            class="p-8 text-center text-slate-400 font-medium"
                                        >
                                            Belum ada bahan baku yang dipilih.
                                            Silakan pilih bahan melalui form di
                                            atas.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(it, idx) in bahanCalculations"
                                        :key="idx"
                                        class="hover:bg-slate-50/70 transition-colors"
                                    >
                                        <td
                                            class="p-3 text-center font-bold text-slate-400 align-top pt-4"
                                        >
                                            {{ idx + 1 }}
                                        </td>
                                        <td
                                            class="p-3 font-bold text-slate-900 align-top pt-4"
                                        >
                                            <div>{{ it.nama }}</div>
                                            <span
                                                v-if="it.alergen"
                                                class="block text-[9.5px] text-amber-700 font-normal mt-0.5"
                                            >
                                                Alergen: {{ it.alergen }}
                                            </span>
                                        </td>
                                        <td class="p-3 align-top pt-3">
                                            <input
                                                type="text"
                                                v-model="
                                                    selectedBahanList[idx]
                                                        .nama_po
                                                "
                                                :placeholder="it.nama"
                                                class="w-full px-2 py-1 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary"
                                            />
                                        </td>
                                        <td
                                            class="p-3 align-top pt-3 min-w-[150px]"
                                        >
                                            <select
                                                v-model="
                                                    selectedBahanList[idx]
                                                        .tipe_porsi
                                                "
                                                class="w-full px-2 py-1 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary font-medium"
                                            >
                                                <option value="normal">
                                                    Normal (Semua Sasaran)
                                                </option>
                                                <option value="alergi">
                                                    Khusus Porsi Alergi
                                                </option>
                                            </select>
                                            <!-- Info PM Normal -->
                                            <div
                                                v-if="
                                                    selectedBahanList[idx]
                                                        .tipe_porsi !== 'alergi'
                                                "
                                                class="mt-1 px-2 py-1 rounded-md bg-slate-100/90 text-slate-700 text-[10px] flex items-center justify-between border border-slate-200/80"
                                            >
                                                <span>Sasaran:</span>
                                                <span
                                                    class="font-bold text-slate-900"
                                                    >{{ totalPM }} Porsi (PK:
                                                    {{ totalPK }}, PB:
                                                    {{ totalPB }})</span
                                                >
                                            </div>
                                            <!-- Select & Info PM Alergi -->
                                            <div
                                                v-else
                                                class="mt-1.5 space-y-1"
                                            >
                                                <select
                                                    v-model="
                                                        selectedBahanList[idx]
                                                            .jenis_alergi
                                                    "
                                                    class="w-full px-2 py-1 text-[11px] font-bold border border-rose-300 rounded-lg bg-rose-50/60 text-rose-900 focus:outline-hidden focus:border-rose-500"
                                                >
                                                    <option value="" disabled>
                                                        -- Pilih Jenis Alergi --
                                                    </option>
                                                    <option
                                                        v-for="alOpt in rekapAlergiDetailPm"
                                                        :key="
                                                            alOpt.jenis_alergi
                                                        "
                                                        :value="
                                                            alOpt.jenis_alergi
                                                        "
                                                    >
                                                        {{ alOpt.jenis_alergi }}
                                                        ({{ alOpt.total }} Porsi
                                                        • PK:
                                                        {{ alOpt.porsi_kecil }},
                                                        PB:
                                                        {{ alOpt.porsi_besar }})
                                                    </option>
                                                    <option
                                                        v-if="
                                                            rekapAlergiDetailPm.length ===
                                                            0
                                                        "
                                                        value="Alergi Khusus"
                                                    >
                                                        Alergi Khusus
                                                    </option>
                                                </select>
                                                <div
                                                    class="px-2 py-1 rounded-md bg-rose-100/80 text-rose-900 text-[10px] flex items-center justify-between border border-rose-200"
                                                >
                                                    <span>Sasaran:</span>
                                                    <span class="font-bold"
                                                        >{{
                                                            it.totalTargetCount
                                                        }}
                                                        Porsi (PK:
                                                        {{ it.targetPKCount }},
                                                        PB:
                                                        {{
                                                            it.targetPBCount
                                                        }})</span
                                                    >
                                                </div>
                                                <p
                                                    v-if="
                                                        validationErrors[
                                                            'bahan_' +
                                                                idx +
                                                                '_alergi'
                                                        ]
                                                    "
                                                    class="text-[10px] text-rose-600 font-bold mt-0.5"
                                                >
                                                    {{
                                                        validationErrors[
                                                            "bahan_" +
                                                                idx +
                                                                "_alergi"
                                                        ]
                                                    }}
                                                </p>
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-slate-600 align-top pt-4"
                                        >
                                            {{ it.kategori }}
                                        </td>
                                        <td
                                            class="p-3 align-top pt-3 text-center"
                                        >
                                            <input
                                                type="number"
                                                step="0.1"
                                                v-model.number="
                                                    selectedBahanList[idx]
                                                        .gram_pk
                                                "
                                                placeholder="0"
                                                class="w-16 text-center px-1.5 py-1 text-xs font-bold border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary mx-auto"
                                            />
                                            <div
                                                class="text-[10px] text-slate-500 font-medium mt-1 whitespace-nowrap bg-slate-50 py-0.5 rounded border border-slate-100"
                                            >
                                                × {{ it.targetPKCount }} porsi
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 align-top pt-3 text-center"
                                        >
                                            <input
                                                type="number"
                                                step="0.1"
                                                v-model.number="
                                                    selectedBahanList[idx]
                                                        .gram_pb
                                                "
                                                placeholder="0"
                                                class="w-16 text-center px-1.5 py-1 text-xs font-bold border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary mx-auto"
                                            />
                                            <div
                                                class="text-[10px] text-slate-500 font-medium mt-1 whitespace-nowrap bg-slate-50 py-0.5 rounded border border-slate-100"
                                            >
                                                × {{ it.targetPBCount }} porsi
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-center align-top pt-4 text-slate-700"
                                        >
                                            {{ it.bdd || 100 }}%
                                        </td>
                                        <td class="p-3 align-top pt-3">
                                            <input
                                                type="number"
                                                v-model.number="
                                                    selectedBahanList[idx]
                                                        .buffer
                                                "
                                                class="w-14 text-center px-1.5 py-1 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary"
                                            />
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-amber-950 bg-amber-50/30 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{
                                                formatGrossWeight(it.totalNetKg)
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-blue-950 bg-blue-50/30 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{
                                                formatGrossWeight(
                                                    it.totalGrossKg,
                                                )
                                            }}
                                        </td>
                                        <td class="p-3 align-top pt-3">
                                            <input
                                                type="number"
                                                v-model.number="
                                                    selectedBahanList[idx]
                                                        .harga_master
                                                "
                                                class="w-24 text-right px-2 py-1 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary"
                                            />
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-emerald-800 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{
                                                formatRupiah(it.subtotalMaster)
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-center align-top pt-3.5"
                                        >
                                            <button
                                                type="button"
                                                @click="handleRemoveBahan(idx)"
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                title="Hapus Bahan"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    v-if="selectedBahanList.length > 0"
                                    class="bg-slate-50/90 font-bold border-t border-slate-200"
                                >
                                    <tr>
                                        <td
                                            colspan="9"
                                            class="p-3.5 text-right uppercase text-[11px] text-slate-600 font-extrabold"
                                        >
                                            Total Estimasi Belanja Bahan Baku
                                            (PO):
                                        </td>
                                        <td
                                            class="p-3.5 text-right font-black text-amber-950 bg-amber-100/40 whitespace-nowrap"
                                        >
                                            {{
                                                formatGrossWeight(
                                                    bahanCalculations.reduce(
                                                        (acc, it) =>
                                                            acc + it.totalNetKg,
                                                        0,
                                                    ),
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="p-3.5 text-right font-black text-blue-950 bg-blue-100/40 whitespace-nowrap"
                                        >
                                            {{
                                                formatGrossWeight(
                                                    bahanCalculations.reduce(
                                                        (acc, it) =>
                                                            acc +
                                                            it.totalGrossKg,
                                                        0,
                                                    ),
                                                )
                                            }}
                                        </td>
                                        <td></td>
                                        <td
                                            class="p-3.5 text-right font-black text-emerald-900 text-sm whitespace-nowrap"
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
                    </div>

                    <!-- Analisis Food Cost & Batas Pagu Anggaran Mentah -->
                    <div class="space-y-4 pt-2">
                        <div class="flex items-center justify-between">
                            <h4
                                class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2"
                            >
                                <Coins class="h-4 w-4 text-emerald-600" />
                                <span
                                    >Analisis Food Cost & Evaluasi Pagu Anggaran
                                    MBG</span
                                >
                            </h4>
                            <button
                                type="button"
                                @click="showRumusCost = !showRumusCost"
                                class="text-[11px] text-primary font-bold hover:underline cursor-pointer"
                            >
                                {{
                                    showRumusCost
                                        ? "Sembunyikan Rumus Cost"
                                        : "Tampilkan Rumus Cost"
                                }}
                            </button>
                        </div>

                        <div
                            v-if="showRumusCost"
                            class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs grid grid-cols-1 md:grid-cols-2 gap-2 text-slate-600"
                        >
                            <div>
                                <strong>Food Cost PK:</strong>
                                <code
                                    >Total Biaya Porsi PK ÷ Jumlah Siswa
                                    PK</code
                                >
                            </div>
                            <div>
                                <strong>Food Cost PB:</strong>
                                <code
                                    >Total Biaya Porsi PB ÷ Jumlah Siswa
                                    PB</code
                                >
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Card Food Cost PK -->
                            <div
                                class="p-4 rounded-2xl border bg-white shadow-2xs space-y-3"
                                :class="
                                    totalFoodCostPKNormal <= 8000
                                        ? 'border-emerald-200 bg-emerald-50/20'
                                        : 'border-rose-200 bg-rose-50/20'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                        >Food Cost Porsi Kecil (PK)</span
                                    >
                                    <span
                                        class="text-xs font-black px-2.5 py-0.5 rounded-lg border"
                                        :class="
                                            totalFoodCostPKNormal <= 8000
                                                ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                                                : 'bg-rose-100 text-rose-800 border-rose-300'
                                        "
                                    >
                                        {{
                                            totalFoodCostPKNormal <= 8000
                                                ? "✓ Sesuai Pagu"
                                                : "⚠ Melebihi Pagu"
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-baseline justify-between"
                                >
                                    <div
                                        class="text-2xl font-black text-slate-900"
                                    >
                                        {{
                                            formatRupiah(totalFoodCostPKNormal)
                                        }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Batas Pagu: <strong>Rp 8.000</strong>
                                    </div>
                                </div>
                                <div
                                    class="w-full bg-slate-100 h-2 rounded-full overflow-hidden"
                                >
                                    <div
                                        class="h-full transition-all"
                                        :class="
                                            totalFoodCostPKNormal <= 8000
                                                ? 'bg-emerald-500'
                                                : 'bg-rose-500'
                                        "
                                        :style="{
                                            width:
                                                Math.min(
                                                    (totalFoodCostPKNormal /
                                                        8000) *
                                                        100,
                                                    100,
                                                ) + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Card Food Cost PB -->
                            <div
                                class="p-4 rounded-2xl border bg-white shadow-2xs space-y-3"
                                :class="
                                    totalFoodCostPBNormal <= 10000
                                        ? 'border-emerald-200 bg-emerald-50/20'
                                        : 'border-rose-200 bg-rose-50/20'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                        >Food Cost Porsi Besar (PB)</span
                                    >
                                    <span
                                        class="text-xs font-black px-2.5 py-0.5 rounded-lg border"
                                        :class="
                                            totalFoodCostPBNormal <= 10000
                                                ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                                                : 'bg-rose-100 text-rose-800 border-rose-300'
                                        "
                                    >
                                        {{
                                            totalFoodCostPBNormal <= 10000
                                                ? "✓ Sesuai Pagu"
                                                : "⚠ Melebihi Pagu"
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-baseline justify-between"
                                >
                                    <div
                                        class="text-2xl font-black text-slate-900"
                                    >
                                        {{
                                            formatRupiah(totalFoodCostPBNormal)
                                        }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Batas Pagu: <strong>Rp 10.000</strong>
                                    </div>
                                </div>
                                <div
                                    class="w-full bg-slate-100 h-2 rounded-full overflow-hidden"
                                >
                                    <div
                                        class="h-full transition-all"
                                        :class="
                                            totalFoodCostPBNormal <= 10000
                                                ? 'bg-emerald-500'
                                                : 'bg-rose-500'
                                        "
                                        :style="{
                                            width:
                                                Math.min(
                                                    (totalFoodCostPBNormal /
                                                        10000) *
                                                        100,
                                                    100,
                                                ) + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Kartu Evaluasi AKG Varian Khusus Alergi (Layout & Style Sama dengan Normal) -->
                        <div
                            v-if="activeAlergiAkgList.length > 0"
                            class="space-y-6 pt-4 border-t border-slate-200"
                        >
                            <div
                                v-for="alRes in activeAlergiAkgList"
                                :key="alRes.jenis_alergi"
                                class="space-y-3 bg-rose-50/30 p-4 rounded-3xl border border-rose-200/80 shadow-2xs"
                            >
                                <div
                                    class="flex items-center justify-between flex-wrap gap-2"
                                >
                                    <h5
                                        class="text-xs font-black uppercase tracking-wider text-rose-950 flex items-center gap-2"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 text-rose-600 shrink-0"
                                        />
                                        <span
                                            >Evaluasi Standar AKG Varian Khusus
                                            Alergi:
                                            {{ alRes.jenis_alergi }}</span
                                        >
                                    </h5>
                                    <span
                                        class="px-2.5 py-0.5 rounded-lg bg-rose-100 text-rose-800 border border-rose-300 text-[11px] font-bold"
                                    >
                                        Sasaran: {{ alRes.total_siswa }} Porsi
                                        (PK: {{ alRes.jml_pk }}, PB:
                                        {{ alRes.jml_pb }})
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <!-- Card AKG Porsi Kecil (PK) Alergi -->
                                    <div
                                        class="p-4 bg-amber-50/40 rounded-2xl border border-amber-200 space-y-3 shadow-2xs"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <h6
                                                class="text-xs font-black uppercase tracking-wider text-amber-900 flex items-center gap-1.5"
                                            >
                                                <Activity
                                                    class="h-4 w-4 text-amber-600"
                                                />
                                                <span
                                                    >Porsi Kecil (PK) • Varian
                                                    {{
                                                        alRes.jenis_alergi
                                                    }}</span
                                                >
                                            </h6>
                                            <Badge
                                                variant="outline"
                                                className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px]"
                                            >
                                                ✓ MEMENUHI STANDAR AKG BGN
                                            </Badge>
                                        </div>
                                        <div
                                            class="grid grid-cols-3 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-amber-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Energi</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pk.energi }} kkal
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: 450 - 550</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-amber-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Protein</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pk.protein }} g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: 15 - 22 g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-amber-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Lemak</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pk.lemak }} g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: 12 - 18 g</span
                                                >
                                            </div>
                                        </div>
                                        <div
                                            class="grid grid-cols-2 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-amber-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Karbohidrat</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pk.karbohidrat }} g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: 65 - 85 g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-amber-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Serat</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pk.serat }} g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: Min 4.0 g</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card AKG Porsi Besar (PB) Alergi -->
                                    <div
                                        class="p-4 bg-indigo-50/40 rounded-2xl border border-indigo-200 space-y-3 shadow-2xs"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <h6
                                                class="text-xs font-black uppercase tracking-wider text-indigo-900 flex items-center gap-1.5"
                                            >
                                                <Activity
                                                    class="h-4 w-4 text-indigo-600"
                                                />
                                                <span
                                                    >Porsi Besar (PB) • Varian
                                                    {{
                                                        alRes.jenis_alergi
                                                    }}</span
                                                >
                                            </h6>
                                            <Badge
                                                variant="outline"
                                                className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px]"
                                            >
                                                ✓ MEMENUHI STANDAR AKG BGN
                                            </Badge>
                                        </div>
                                        <div
                                            class="grid grid-cols-3 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Energi</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pb.energi }} kkal
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: 650 - 800</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Protein</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pb.protein }} g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: 24 - 35 g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Lemak</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pb.lemak }} g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: 18 - 26 g</span
                                                >
                                            </div>
                                        </div>
                                        <div
                                            class="grid grid-cols-2 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Karbohidrat</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pb.karbohidrat }} g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: 85 - 110 g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                            >
                                                <span
                                                    class="text-slate-500 text-[10px] uppercase font-bold block"
                                                    >Serat</span
                                                >
                                                <div
                                                    class="font-black text-slate-900 text-sm mt-0.5"
                                                >
                                                    {{ alRes.pb.serat }} g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: Min 6.0 g</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Bottom Action Bar Step 2 -->
            <div
                class="p-4 rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm border border-slate-800"
            >
                <div>
                    <h4
                        class="text-sm font-black text-white flex items-center gap-2"
                    >
                        <CheckCircle2
                            class="h-4 w-4 text-emerald-400 shrink-0"
                        />
                        <span>Kebutuhan Bahan Pangan & Biaya Belanja Siap</span>
                    </h4>
                    <p class="text-xs text-slate-300 leading-relaxed mt-0.5">
                        Lanjutkan ke Langkah 3 untuk memformulasi gizi matang
                        dan mengevaluasi standar AKG siap santap.
                    </p>
                </div>
                <div
                    class="flex items-center gap-2.5 shrink-0 flex-wrap w-full sm:w-auto"
                >
                    <Button
                        type="button"
                        @click="buatMenuSubTab = 'work_order'"
                        className="bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-bold px-3.5 h-10 rounded-xl cursor-pointer w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-none"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        <span>Kembali ke Langkah 1</span>
                    </Button>
                    <Button
                        type="button"
                        @click="simpanDraftStep2"
                        :disabled="isSubmitting"
                        className="bg-white hover:bg-slate-100 text-slate-900 text-xs font-bold px-4 h-10 rounded-xl cursor-pointer w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-xs"
                    >
                        <FileText class="h-4 w-4 text-slate-600" />
                        <span>Simpan Draft (Langkah 2)</span>
                    </Button>
                    <Button
                        type="button"
                        @click="handleLanjutStep3"
                        className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-5 h-10 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer shrink-0 w-full sm:w-auto text-center"
                    >
                        <ChevronRight class="h-4 w-4 shrink-0" />
                        <span>Lanjut ke Formula Gizi (Langkah 3)</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- Bagian 3: Formula Gizi Siap Santap & Evaluasi Standar AKG BGN (Step 3) -->
        <!-- ========================================================================================= -->
        <div v-if="buatMenuSubTab === 'formula_gizi'" class="space-y-6">
            <!-- Header Formula Gizi Card -->
            <Card className="bg-white border-slate-200 shadow-xs">
                <CardHeader
                    className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                >
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <CardTitle
                                    class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                                >
                                    <ClipboardList
                                        class="h-5 w-5 text-primary"
                                    />
                                    <span
                                        >Langkah 3: Formulasi Menu Matang &
                                        Evaluasi Nilai Gizi AKG (Siap
                                        Santap)</span
                                    >
                                </CardTitle>
                                <span
                                    class="px-2.5 py-0.5 text-xs font-extrabold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    Evaluasi AKG BGN
                                </span>
                            </div>
                            <CardDescription
                                class="text-xs text-slate-500 mt-1"
                            >
                                Formulasi komposisi hidangan matang siap santap
                                untuk mengevaluasi pemenuhan Angka Kecukupan
                                Gizi (AKG) standar Badan Gizi Nasional (BGN).
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="syncGiziFromBahan"
                                className="text-xs font-bold border-slate-300 text-slate-700 hover:bg-slate-100 flex items-center gap-1.5 cursor-pointer shadow-2xs"
                            >
                                <RotateCcw class="h-3.5 w-3.5 text-primary" />
                                <span
                                    >Sinkronkan dari Bahan Pangan (Langkah
                                    2)</span
                                >
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-4 sm:p-6 space-y-6">
                    <!-- Selector Tambah Bahan Menu Siap Santap -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label
                                class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5"
                            >
                                <Search class="h-3.5 w-3.5 text-primary" />
                                <span
                                    >Tambah Menu / Bahan Siap Santap
                                    Tambahan:</span
                                >
                            </label>
                            <span
                                class="text-[11px] text-slate-500 font-medium"
                            >
                                Database:
                                <strong class="text-slate-800">{{
                                    selectedSource === "csv"
                                        ? "TKPI 2020"
                                        : "NutriSurvey FTA"
                                }}</strong>
                            </span>
                        </div>

                        <!-- Combobox Selector Step 3 -->
                        <div class="relative" @click.stop>
                            <div
                                @click="
                                    isComboboxGiziOpen = !isComboboxGiziOpen
                                "
                                class="w-full min-h-[46px] px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:border-primary/60 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 transition-all flex items-center justify-between gap-3 cursor-pointer shadow-2xs"
                            >
                                <div
                                    class="flex items-center gap-2.5 min-w-0 flex-1"
                                >
                                    <div
                                        class="h-7 w-7 rounded-lg bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </div>
                                    <span
                                        v-if="!selectedTkpiGiziItem"
                                        class="text-xs text-slate-400 font-normal truncate"
                                    >
                                        Ketik nama bahan/hidangan matang untuk
                                        ditambahkan ke formulasi gizi...
                                    </span>
                                    <div
                                        v-else
                                        class="flex items-center gap-2 min-w-0 truncate"
                                    >
                                        <span
                                            class="text-xs font-bold text-slate-900 truncate"
                                            >{{
                                                selectedTkpiGiziItem.nama
                                            }}</span
                                        >
                                        <span
                                            class="text-[10.5px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 shrink-0 font-medium"
                                            >{{
                                                selectedTkpiGiziItem.kategori
                                            }}</span
                                        >
                                    </div>
                                </div>
                                <ChevronDown
                                    class="h-4 w-4 text-slate-400 shrink-0 transition-transform"
                                    :class="{
                                        'rotate-180': isComboboxGiziOpen,
                                    }"
                                />
                            </div>

                            <!-- Dropdown Panel Step 3 -->
                            <div
                                v-if="isComboboxGiziOpen"
                                class="absolute z-50 left-0 right-0 top-full mt-1.5 bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                            >
                                <div
                                    class="p-2.5 border-b border-slate-100 bg-slate-50/70"
                                >
                                    <div class="relative">
                                        <Search
                                            class="h-3.5 w-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"
                                        />
                                        <input
                                            type="text"
                                            v-model="searchTkpiGiziQuery"
                                            placeholder="Cari bahan / hidangan matang..."
                                            class="w-full pl-8 pr-3 py-1.5 text-xs bg-white rounded-lg border border-slate-200 focus:outline-hidden focus:border-primary font-medium"
                                            autofocus
                                        />
                                    </div>
                                </div>
                                <div
                                    class="max-h-60 overflow-y-auto divide-y divide-slate-100"
                                >
                                    <div
                                        v-for="item in filteredGiziTkpiList"
                                        :key="item.id || item.code"
                                        @click="selectTkpiGiziItem(item)"
                                        class="p-3 hover:bg-emerald-50/40 cursor-pointer transition-colors flex items-center justify-between gap-3 text-xs"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="font-bold text-slate-800 truncate"
                                            >
                                                {{ item.nama }}
                                            </div>
                                            <div
                                                class="text-[10.5px] text-slate-500 flex items-center gap-2 mt-0.5"
                                            >
                                                <span>{{ item.kategori }}</span>
                                                <span>•</span>
                                                <span
                                                    >Energi:
                                                    {{ item.energi || 0 }}
                                                    kkal</span
                                                >
                                                <span>•</span>
                                                <span
                                                    >Protein:
                                                    {{
                                                        item.protein || 0
                                                    }}g</span
                                                >
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            size="sm"
                                            className="bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white h-7 px-2 text-[11px] font-bold rounded-lg shrink-0"
                                        >
                                            + Tambah
                                        </Button>
                                    </div>
                                    <div
                                        v-if="filteredGiziTkpiList.length === 0"
                                        class="p-6 text-center text-xs text-slate-400"
                                    >
                                        Bahan matang tidak ditemukan.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panduan Rumus AKG (Toggleable) -->
                    <div
                        class="rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 text-xs space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="font-bold text-slate-700 flex items-center gap-1.5 text-xs"
                            >
                                <HelpCircle class="h-3.5 w-3.5 text-primary" />
                                Panduan Rumus Perhitungan Nilai Gizi & Standar
                                AKG Siap Santap
                            </span>
                            <button
                                type="button"
                                @click="showRumusAkg = !showRumusAkg"
                                class="text-[11px] text-primary font-bold hover:underline cursor-pointer"
                            >
                                {{
                                    showRumusAkg
                                        ? "Sembunyikan Rumus AKG"
                                        : "Tampilkan Rumus AKG"
                                }}
                            </button>
                        </div>
                        <div
                            v-if="showRumusAkg"
                            class="grid grid-cols-1 md:grid-cols-2 gap-2.5 pt-2 border-t border-slate-200 text-[11px] text-slate-600"
                        >
                            <div
                                class="p-2 bg-white rounded-lg border border-slate-200"
                            >
                                <strong class="text-slate-800 block mb-0.5"
                                    >Rumus Nilai Gizi per Porsi:</strong
                                >
                                <code
                                    >(Gram Bersih Matang ÷ 100) × Nilai Gizi
                                    Database Master per 100g</code
                                >
                            </div>
                            <div
                                class="p-2 bg-white rounded-lg border border-slate-200"
                            >
                                <strong class="text-slate-800 block mb-0.5"
                                    >Rumus Total AKG Menu:</strong
                                >
                                <code
                                    >Σ (Nilai Gizi per Bahan) untuk seluruh
                                    komponen menu siap santap</code
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Formulasi Menu Siap Santap & Kandungan Gizi Lengkap -->
                    <div
                        class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs"
                    >
                        <div
                            class="p-3.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2"
                        >
                            <span
                                class="text-xs font-extrabold text-slate-800 uppercase tracking-wider"
                            >
                                Daftar Komposisi Hidangan Matang & Kandungan
                                Gizi Siap Santap
                            </span>
                            <span class="text-xs font-bold text-slate-500">
                                Total:
                                {{ selectedGiziBahanList.length }} Komponen
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table
                                class="w-full min-w-[1100px] text-left text-xs border-collapse"
                            >
                                <thead>
                                    <tr
                                        class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                                    >
                                        <th class="p-3 text-center w-10">No</th>
                                        <th class="p-3 min-w-[160px]">
                                            Bahan / Hidangan Matang
                                        </th>
                                        <th class="p-3 min-w-[110px]">
                                            Peruntukan
                                        </th>
                                        <th class="p-3 min-w-[90px]">
                                            Kategori
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[85px]"
                                        >
                                            PK (g)
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[85px]"
                                        >
                                            PB (g)
                                        </th>
                                        <th
                                            class="p-3 text-right bg-amber-50/50 text-amber-950 min-w-[90px]"
                                        >
                                            Kg Bersih
                                        </th>
                                        <th class="p-3 text-right min-w-[95px]">
                                            Energi (Kkal)
                                        </th>
                                        <th class="p-3 text-right min-w-[90px]">
                                            Protein (g)
                                        </th>
                                        <th class="p-3 text-right min-w-[90px]">
                                            Lemak (g)
                                        </th>
                                        <th class="p-3 text-right min-w-[90px]">
                                            Karbo (g)
                                        </th>
                                        <th class="p-3 text-right min-w-[90px]">
                                            Serat (g)
                                        </th>
                                        <th class="p-3 text-center w-12">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 text-slate-800"
                                >
                                    <tr
                                        v-if="
                                            selectedGiziBahanList.length === 0
                                        "
                                    >
                                        <td
                                            colspan="13"
                                            class="p-8 text-center text-slate-400 font-medium"
                                        >
                                            Belum ada komposisi gizi siap
                                            santap. Klik tombol "Sinkronkan dari
                                            Bahan Pangan" di atas atau tambahkan
                                            bahan baru.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(it, idx) in giziCalculations"
                                        :key="idx"
                                        class="hover:bg-slate-50/70 transition-colors"
                                    >
                                        <td
                                            class="p-3 text-center font-bold text-slate-400 align-top pt-4"
                                        >
                                            {{ idx + 1 }}
                                        </td>
                                        <td
                                            class="p-3 font-bold text-slate-900 align-top pt-4"
                                        >
                                            <div>{{ it.nama }}</div>
                                            <div
                                                class="text-[10px] text-slate-400 font-medium mt-0.5"
                                            >
                                                Master/100g:
                                                {{ it.tkpi?.energi || 0 }} kkal,
                                                {{ it.tkpi?.protein || 0 }}g
                                                Prot
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 align-top pt-3 min-w-[150px]"
                                        >
                                            <select
                                                v-model="
                                                    selectedGiziBahanList[idx]
                                                        .tipe_porsi
                                                "
                                                class="w-full px-2 py-1 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary font-medium"
                                            >
                                                <option value="normal">
                                                    Normal (Semua Sasaran)
                                                </option>
                                                <option value="alergi">
                                                    Khusus Porsi Alergi
                                                </option>
                                            </select>
                                            <!-- Info PM Normal -->
                                            <div
                                                v-if="
                                                    selectedGiziBahanList[idx]
                                                        .tipe_porsi !== 'alergi'
                                                "
                                                class="mt-1 px-2 py-1 rounded-md bg-slate-100/90 text-slate-700 text-[10px] flex items-center justify-between border border-slate-200/80"
                                            >
                                                <span>Sasaran:</span>
                                                <span
                                                    class="font-bold text-slate-900"
                                                    >{{ totalPM }} Porsi (PK:
                                                    {{ totalPK }}, PB:
                                                    {{ totalPB }})</span
                                                >
                                            </div>
                                            <!-- Select & Info PM Alergi -->
                                            <div
                                                v-else
                                                class="mt-1.5 space-y-1"
                                            >
                                                <select
                                                    v-model="
                                                        selectedGiziBahanList[
                                                            idx
                                                        ].jenis_alergi
                                                    "
                                                    class="w-full px-2 py-1 text-[11px] font-bold border border-rose-300 rounded-lg bg-rose-50/60 text-rose-900 focus:outline-hidden focus:border-rose-500"
                                                >
                                                    <option value="" disabled>
                                                        -- Pilih Jenis Alergi --
                                                    </option>
                                                    <option
                                                        v-for="alOpt in rekapAlergiDetailPm"
                                                        :key="
                                                            alOpt.jenis_alergi
                                                        "
                                                        :value="
                                                            alOpt.jenis_alergi
                                                        "
                                                    >
                                                        {{ alOpt.jenis_alergi }}
                                                        ({{ alOpt.total }} Porsi
                                                        • PK:
                                                        {{ alOpt.porsi_kecil }},
                                                        PB:
                                                        {{ alOpt.porsi_besar }})
                                                    </option>
                                                    <option
                                                        v-if="
                                                            rekapAlergiDetailPm.length ===
                                                            0
                                                        "
                                                        value="Alergi Khusus"
                                                    >
                                                        Alergi Khusus
                                                    </option>
                                                </select>
                                                <div
                                                    class="px-2 py-1 rounded-md bg-rose-100/80 text-rose-900 text-[10px] flex items-center justify-between border border-rose-200"
                                                >
                                                    <span>Sasaran:</span>
                                                    <span class="font-bold"
                                                        >{{
                                                            it.totalTargetCount
                                                        }}
                                                        Porsi (PK:
                                                        {{ it.targetPKCount }},
                                                        PB:
                                                        {{
                                                            it.targetPBCount
                                                        }})</span
                                                    >
                                                </div>
                                                <p
                                                    v-if="
                                                        validationErrors[
                                                            'gizi_bahan_' +
                                                                idx +
                                                                '_alergi'
                                                        ]
                                                    "
                                                    class="text-[10px] text-rose-600 font-bold mt-0.5"
                                                >
                                                    {{
                                                        validationErrors[
                                                            "gizi_bahan_" +
                                                                idx +
                                                                "_alergi"
                                                        ]
                                                    }}
                                                </p>
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-slate-600 align-top pt-4"
                                        >
                                            {{ it.kategori }}
                                        </td>
                                        <td
                                            class="p-3 align-top pt-3 text-center"
                                        >
                                            <input
                                                type="number"
                                                step="0.1"
                                                v-model.number="
                                                    selectedGiziBahanList[idx]
                                                        .gram_pk
                                                "
                                                placeholder="0"
                                                class="w-16 text-center px-1.5 py-1 text-xs font-bold border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary mx-auto"
                                            />
                                            <div
                                                class="text-[10px] text-slate-500 font-medium mt-1 whitespace-nowrap bg-slate-50 py-0.5 rounded border border-slate-100"
                                            >
                                                × {{ it.targetPKCount }} porsi
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 align-top pt-3 text-center"
                                        >
                                            <input
                                                type="number"
                                                step="0.1"
                                                v-model.number="
                                                    selectedGiziBahanList[idx]
                                                        .gram_pb
                                                "
                                                placeholder="0"
                                                class="w-16 text-center px-1.5 py-1 text-xs font-bold border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary mx-auto"
                                            />
                                            <div
                                                class="text-[10px] text-slate-500 font-medium mt-1 whitespace-nowrap bg-slate-50 py-0.5 rounded border border-slate-100"
                                            >
                                                × {{ it.targetPBCount }} porsi
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-amber-950 bg-amber-50/30 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{
                                                formatGrossWeight(it.totalNetKg)
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-right align-top pt-3.5 whitespace-nowrap"
                                        >
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PK:
                                                {{ it.nutrisiPK?.energi || 0 }}
                                            </div>
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PB:
                                                {{ it.nutrisiPB?.energi || 0 }}
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-right align-top pt-3.5 whitespace-nowrap"
                                        >
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PK:
                                                {{
                                                    it.nutrisiPK?.protein || 0
                                                }}g
                                            </div>
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PB:
                                                {{
                                                    it.nutrisiPB?.protein || 0
                                                }}g
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-right align-top pt-3.5 whitespace-nowrap"
                                        >
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PK:
                                                {{ it.nutrisiPK?.lemak || 0 }}g
                                            </div>
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PB:
                                                {{ it.nutrisiPB?.lemak || 0 }}g
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-right align-top pt-3.5 whitespace-nowrap"
                                        >
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PK:
                                                {{
                                                    it.nutrisiPK?.karbohidrat ||
                                                    0
                                                }}g
                                            </div>
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PB:
                                                {{
                                                    it.nutrisiPB?.karbohidrat ||
                                                    0
                                                }}g
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-right align-top pt-3.5 whitespace-nowrap"
                                        >
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PK:
                                                {{ it.nutrisiPK?.serat || 0 }}g
                                            </div>
                                            <div
                                                class="text-[10.5px] text-slate-700 font-bold"
                                            >
                                                PB:
                                                {{ it.nutrisiPB?.serat || 0 }}g
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-center align-top pt-3.5"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    handleRemoveGiziBahan(idx)
                                                "
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                title="Hapus Bahan Siap Santap"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    v-if="selectedGiziBahanList.length > 0"
                                    class="bg-slate-50/90 font-bold border-t border-slate-200"
                                >
                                    <tr>
                                        <td
                                            colspan="6"
                                            class="p-3.5 text-right uppercase text-[11px] text-slate-600 font-extrabold"
                                        >
                                            Total Akumulasi Gizi Siap Santap:
                                        </td>
                                        <td
                                            class="p-3.5 text-right font-black text-amber-950 bg-amber-100/40 whitespace-nowrap"
                                        >
                                            {{
                                                formatGrossWeight(
                                                    giziCalculations.reduce(
                                                        (acc, it) =>
                                                            acc + it.totalNetKg,
                                                        0,
                                                    ),
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="p-3.5 text-right whitespace-nowrap"
                                        >
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PK:
                                                {{ akgResultPKNormal.energi }}
                                            </div>
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PB:
                                                {{ akgResultPBNormal.energi }}
                                            </div>
                                        </td>
                                        <td
                                            class="p-3.5 text-right whitespace-nowrap"
                                        >
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PK:
                                                {{ akgResultPKNormal.protein }}g
                                            </div>
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PB:
                                                {{ akgResultPBNormal.protein }}g
                                            </div>
                                        </td>
                                        <td
                                            class="p-3.5 text-right whitespace-nowrap"
                                        >
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PK:
                                                {{ akgResultPKNormal.lemak }}g
                                            </div>
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PB:
                                                {{ akgResultPBNormal.lemak }}g
                                            </div>
                                        </td>
                                        <td
                                            class="p-3.5 text-right whitespace-nowrap"
                                        >
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PK:
                                                {{
                                                    akgResultPKNormal.karbohidrat
                                                }}g
                                            </div>
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PB:
                                                {{
                                                    akgResultPBNormal.karbohidrat
                                                }}g
                                            </div>
                                        </td>
                                        <td
                                            class="p-3.5 text-right whitespace-nowrap"
                                        >
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PK:
                                                {{ akgResultPKNormal.serat }}g
                                            </div>
                                            <div
                                                class="font-black text-slate-900"
                                            >
                                                PB:
                                                {{ akgResultPBNormal.serat }}g
                                            </div>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Real-time Evaluasi Standar AKG BGN -->
                    <div class="space-y-4 pt-2">
                        <div class="flex items-center justify-between">
                            <h4
                                class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2"
                            >
                                <Activity class="h-4 w-4 text-emerald-600" />
                                <span
                                    >Evaluasi Standar Angka Kecukupan Gizi (AKG)
                                    Badan Gizi Nasional (BGN)</span
                                >
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Card AKG Porsi Kecil (PK) -->
                            <div
                                class="p-4 bg-amber-50/40 rounded-2xl border border-amber-200 space-y-3 shadow-2xs"
                            >
                                <div class="flex items-center justify-between">
                                    <h5
                                        class="text-xs font-black uppercase tracking-wider text-amber-900 flex items-center gap-1.5"
                                    >
                                        <Activity
                                            class="h-4 w-4 text-amber-600"
                                        />
                                        <span
                                            >Porsi Kecil (PK) • PAUD/TK & SD
                                            Kelas 1-3</span
                                        >
                                    </h5>
                                    <Badge
                                        variant="outline"
                                        className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px]"
                                    >
                                        ✓ MEMENUHI STANDAR AKG BGN
                                    </Badge>
                                </div>
                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-amber-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Energi</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPKNormal.energi }} kkal
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: 450 - 550</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-amber-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Protein</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPKNormal.protein }} g
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: 15 - 22 g</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-amber-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Lemak</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPKNormal.lemak }} g
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: 12 - 18 g</span
                                        >
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-amber-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Karbohidrat</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPKNormal.karbohidrat }}
                                            g
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: 65 - 85 g</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-amber-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Serat</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPKNormal.serat }} g
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: Min 4.0 g</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Card AKG Porsi Besar (PB) -->
                            <div
                                class="p-4 bg-indigo-50/40 rounded-2xl border border-indigo-200 space-y-3 shadow-2xs"
                            >
                                <div class="flex items-center justify-between">
                                    <h5
                                        class="text-xs font-black uppercase tracking-wider text-indigo-900 flex items-center gap-1.5"
                                    >
                                        <Activity
                                            class="h-4 w-4 text-indigo-600"
                                        />
                                        <span
                                            >Porsi Besar (PB) • SD 4-6, SMP,
                                            SMA, & Bumil</span
                                        >
                                    </h5>
                                    <Badge
                                        variant="outline"
                                        className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px]"
                                    >
                                        ✓ MEMENUHI STANDAR AKG BGN
                                    </Badge>
                                </div>
                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Energi</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPBNormal.energi }} kkal
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: 650 - 800</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Protein</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPBNormal.protein }} g
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: 24 - 35 g</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Lemak</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPBNormal.lemak }} g
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: 18 - 26 g</span
                                        >
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Karbohidrat</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPBNormal.karbohidrat }}
                                            g
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: 85 - 110 g</span
                                        >
                                    </div>
                                    <div
                                        class="p-2.5 bg-white rounded-xl border border-indigo-100"
                                    >
                                        <span
                                            class="text-slate-500 text-[10px] uppercase font-bold block"
                                            >Serat</span
                                        >
                                        <div
                                            class="font-black text-slate-900 text-sm mt-0.5"
                                        >
                                            {{ akgResultPBNormal.serat }} g
                                        </div>
                                        <span
                                            class="text-[9.5px] text-slate-400"
                                            >Target: Min 6.0 g</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Bottom Action Bar Step 3 -->
            <div
                class="p-4 rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm border border-slate-800"
            >
                <div>
                    <h4
                        class="text-sm font-black text-white flex items-center gap-2"
                    >
                        <CheckCircle2
                            class="h-4 w-4 text-emerald-400 shrink-0"
                        />
                        <span
                            >Formulasi Gizi Matang Siap Santap Tervalidasi</span
                        >
                    </h4>
                    <p class="text-xs text-slate-300 leading-relaxed mt-0.5">
                        Lanjutkan ke Langkah 4 untuk mereview rangkuman dokumen
                        dan mengajukan Work Order ke Bagian Keuangan.
                    </p>
                </div>
                <div
                    class="flex items-center gap-2.5 shrink-0 flex-wrap w-full sm:w-auto"
                >
                    <Button
                        type="button"
                        @click="buatMenuSubTab = 'bahan_pangan'"
                        className="bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-bold px-3.5 h-10 rounded-xl cursor-pointer w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-none"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        <span>Kembali ke Langkah 2</span>
                    </Button>
                    <Button
                        type="button"
                        @click="simpanDraftStep3"
                        :disabled="isSubmitting"
                        className="bg-white hover:bg-slate-100 text-slate-900 text-xs font-bold px-4 h-10 rounded-xl cursor-pointer w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-xs"
                    >
                        <FileText class="h-4 w-4 text-slate-600" />
                        <span>Simpan Draft (Langkah 3)</span>
                    </Button>
                    <Button
                        type="button"
                        @click="handleLanjutStep4"
                        className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-5 h-10 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer shrink-0 w-full sm:w-auto text-center"
                    >
                        <Send class="h-4 w-4 shrink-0" />
                        <span>Lanjut ke Review & Pengajuan (Langkah 4)</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Bagian 3: Order Pembelian Bahan & Verifikasi Akuntan (Step 3) -->
        <!-- ========================================================================================= -->
        <div v-if="buatMenuSubTab === 'order'" class="space-y-6">
            <!-- Header Langkah 3: Review & Pengajuan -->
            <Card className="bg-white border-slate-200 shadow-xs">
                <CardHeader
                    className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <CardTitle
                                class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                            >
                                <FileSpreadsheet class="h-5 w-5 text-primary" />
                                <span>Review Lengkap Rancangan Menu MBG</span>
                            </CardTitle>
                            <span
                                class="px-2.5 py-0.5 text-xs font-black bg-primary/10 text-primary rounded-full"
                            >
                                Langkah 3 dari 3
                            </span>
                        </div>
                        <CardDescription class="text-xs sm:text-sm mt-0.5">
                            Tinjauan menyeluruh hasil formulasi gizi, kesesuaian
                            standar AKG, kuota sasaran, dan estimasi biaya
                            sebelum disimpan atau diajukan ke Keuangan.
                        </CardDescription>
                    </div>

                    <!-- Status Badge Dokumen -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 font-medium"
                            >Status:</span
                        >
                        <span
                            :class="[
                                'px-3 py-1 text-xs font-black rounded-lg border flex items-center gap-1.5',
                                statusPengajuanWo === 'Diajukan ke Keuangan'
                                    ? 'bg-blue-50 text-blue-700 border-blue-200'
                                    : 'bg-amber-50 text-amber-700 border-amber-200',
                            ]"
                        >
                            <Clock class="h-3.5 w-3.5" />
                            {{ statusPengajuanWo }}
                        </span>
                    </div>
                </CardHeader>
                <CardContent className="p-4 sm:p-6 space-y-6">
                    <!-- Ringkasan Info Menu & Kuota Sasaran (Grid 4 Kolom) -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <div
                            class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80"
                        >
                            <p
                                class="text-[10.5px] font-bold text-slate-500 uppercase tracking-wider"
                            >
                                No. Work Order
                            </p>
                            <p
                                class="text-sm sm:text-base font-black text-primary mt-1"
                            >
                                {{ woNo }}
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Distribusi:
                                {{ formatTanggalIndo(tanggalRencana) }}
                            </p>
                        </div>
                        <div
                            class="p-3.5 bg-amber-50/70 rounded-xl border border-amber-200/80"
                        >
                            <p
                                class="text-[10.5px] font-bold text-amber-800 uppercase tracking-wider"
                            >
                                Sasaran Porsi Kecil (PK)
                            </p>
                            <p
                                class="text-sm sm:text-base font-black text-amber-950 mt-1"
                            >
                                {{ totalPK.toLocaleString("id-ID") }} Porsi
                            </p>
                            <p class="text-[11px] text-amber-800 mt-0.5">
                                Food Cost:
                                {{ formatRupiah(totalFoodCostPKNormal) }}
                            </p>
                        </div>
                        <div
                            class="p-3.5 bg-indigo-50/70 rounded-xl border border-indigo-200/80"
                        >
                            <p
                                class="text-[10.5px] font-bold text-indigo-800 uppercase tracking-wider"
                            >
                                Sasaran Porsi Besar (PB)
                            </p>
                            <p
                                class="text-sm sm:text-base font-black text-indigo-950 mt-1"
                            >
                                {{ totalPB.toLocaleString("id-ID") }} Porsi
                            </p>
                            <p class="text-[11px] text-indigo-800 mt-0.5">
                                Food Cost:
                                {{ formatRupiah(totalFoodCostPBNormal) }}
                            </p>
                        </div>
                        <div
                            class="p-3.5 bg-emerald-50/70 rounded-xl border border-emerald-200/80"
                        >
                            <p
                                class="text-[10.5px] font-bold text-emerald-800 uppercase tracking-wider"
                            >
                                Total Anggaran Draft PO
                            </p>
                            <p
                                class="text-sm sm:text-base font-black text-emerald-950 mt-1"
                            >
                                {{ formatRupiah(grandTotalDraftMaster) }}
                            </p>
                            <p class="text-[11px] text-emerald-800 mt-0.5">
                                Total PM:
                                {{ totalPM.toLocaleString("id-ID") }} Siswa
                            </p>
                        </div>
                    </div>

                    <!-- Card Evaluasi Standar AKG -->
                    <div
                        class="bg-slate-50/70 rounded-2xl p-4 sm:p-5 border border-slate-200 space-y-3"
                    >
                        <h4
                            class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2"
                        >
                            <Activity class="h-4 w-4 text-primary" />
                            <span
                                >Hasil Evaluasi Standar Kecukupan Gizi
                                (AKG)</span
                            >
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Evaluasi PK -->
                            <div
                                class="p-3.5 bg-white rounded-xl border border-slate-200 space-y-2.5"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-slate-100 pb-2"
                                >
                                    <span
                                        class="text-xs font-bold text-slate-900"
                                        >Porsi Kecil (PAUD / SD 1-3)</span
                                    >
                                    <span
                                        class="px-2 py-0.5 text-[11px] font-black rounded-md bg-emerald-100 text-emerald-800"
                                        >Memenuhi AKG</span
                                    >
                                </div>
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs"
                                >
                                    <div
                                        class="p-2 bg-amber-50 rounded-lg border border-amber-100"
                                    >
                                        <p
                                            class="text-[10px] text-amber-700 font-bold"
                                        >
                                            Energi
                                        </p>
                                        <p
                                            class="font-black text-amber-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPKNormal.energi ||
                                                    0
                                                ).toFixed(1)
                                            }}
                                            kkal
                                        </p>
                                    </div>
                                    <div
                                        class="p-2 bg-rose-50 rounded-lg border border-rose-100"
                                    >
                                        <p
                                            class="text-[10px] text-rose-700 font-bold"
                                        >
                                            Protein
                                        </p>
                                        <p
                                            class="font-black text-rose-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPKNormal.protein ||
                                                    0
                                                ).toFixed(1)
                                            }}
                                            g
                                        </p>
                                    </div>
                                    <div
                                        class="p-2 bg-yellow-50 rounded-lg border border-yellow-100"
                                    >
                                        <p
                                            class="text-[10px] text-yellow-700 font-bold"
                                        >
                                            Lemak
                                        </p>
                                        <p
                                            class="font-black text-yellow-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPKNormal.lemak || 0
                                                ).toFixed(1)
                                            }}
                                            g
                                        </p>
                                    </div>
                                    <div
                                        class="p-2 bg-blue-50 rounded-lg border border-blue-100"
                                    >
                                        <p
                                            class="text-[10px] text-blue-700 font-bold"
                                        >
                                            Karbo
                                        </p>
                                        <p
                                            class="font-black text-blue-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPKNormal.karbohidrat ||
                                                    0
                                                ).toFixed(1)
                                            }}
                                            g
                                        </p>
                                    </div>
                                    <div
                                        class="p-2 bg-emerald-50 rounded-lg border border-emerald-100"
                                    >
                                        <p
                                            class="text-[10px] text-emerald-700 font-bold"
                                        >
                                            Serat
                                        </p>
                                        <p
                                            class="font-black text-emerald-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPKNormal.serat || 0
                                                ).toFixed(1)
                                            }}
                                            g
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Evaluasi PB -->
                            <div
                                class="p-3.5 bg-white rounded-xl border border-slate-200 space-y-2.5"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-slate-100 pb-2"
                                >
                                    <span
                                        class="text-xs font-bold text-slate-900"
                                        >Porsi Besar (SD 4-6 / SMP / SMA)</span
                                    >
                                    <span
                                        class="px-2 py-0.5 text-[11px] font-black rounded-md bg-emerald-100 text-emerald-800"
                                        >Memenuhi AKG</span
                                    >
                                </div>
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs"
                                >
                                    <div
                                        class="p-2 bg-amber-50 rounded-lg border border-amber-100"
                                    >
                                        <p
                                            class="text-[10px] text-amber-700 font-bold"
                                        >
                                            Energi
                                        </p>
                                        <p
                                            class="font-black text-amber-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPBNormal.energi ||
                                                    0
                                                ).toFixed(1)
                                            }}
                                            kkal
                                        </p>
                                    </div>
                                    <div
                                        class="p-2 bg-rose-50 rounded-lg border border-rose-100"
                                    >
                                        <p
                                            class="text-[10px] text-rose-700 font-bold"
                                        >
                                            Protein
                                        </p>
                                        <p
                                            class="font-black text-rose-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPBNormal.protein ||
                                                    0
                                                ).toFixed(1)
                                            }}
                                            g
                                        </p>
                                    </div>
                                    <div
                                        class="p-2 bg-yellow-50 rounded-lg border border-yellow-100"
                                    >
                                        <p
                                            class="text-[10px] text-yellow-700 font-bold"
                                        >
                                            Lemak
                                        </p>
                                        <p
                                            class="font-black text-yellow-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPBNormal.lemak || 0
                                                ).toFixed(1)
                                            }}
                                            g
                                        </p>
                                    </div>
                                    <div
                                        class="p-2 bg-blue-50 rounded-lg border border-blue-100"
                                    >
                                        <p
                                            class="text-[10px] text-blue-700 font-bold"
                                        >
                                            Karbo
                                        </p>
                                        <p
                                            class="font-black text-blue-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPBNormal.karbohidrat ||
                                                    0
                                                ).toFixed(1)
                                            }}
                                            g
                                        </p>
                                    </div>
                                    <div
                                        class="p-2 bg-emerald-50 rounded-lg border border-emerald-100"
                                    >
                                        <p
                                            class="text-[10px] text-emerald-700 font-bold"
                                        >
                                            Serat
                                        </p>
                                        <p
                                            class="font-black text-emerald-950 mt-0.5"
                                        >
                                            {{
                                                (
                                                    akgResultPBNormal.serat || 0
                                                ).toFixed(1)
                                            }}
                                            g
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Rekapitulasi Item Resep Bahan Baku -->
                    <div class="space-y-2">
                        <h4
                            class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2"
                        >
                            <UtensilsCrossed class="h-4 w-4 text-primary" />
                            <span>Rincian Bahan Baku & Kebutuhan Kotor</span>
                        </h4>
                        <div
                            class="border border-slate-200 rounded-xl overflow-x-auto"
                        >
                            <table
                                class="w-full min-w-[850px] text-left text-xs border-collapse"
                            >
                                <thead
                                    class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3 text-center w-10">No</th>
                                        <th class="p-3">Bahan Pangan</th>
                                        <th
                                            class="p-3 text-center min-w-[140px]"
                                        >
                                            Peruntukan Porsi
                                        </th>
                                        <th class="p-3">Kategori</th>
                                        <th class="p-3 text-center">
                                            Gramasi (PK / PB)
                                        </th>
                                        <th class="p-3 text-right">
                                            Kebutuhan Kotor (Kg)
                                        </th>
                                        <th class="p-3 text-right">
                                            Estimasi Biaya / Kg
                                        </th>
                                        <th class="p-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 text-slate-800"
                                >
                                    <tr v-if="bahanCalculations.length === 0">
                                        <td
                                            colspan="8"
                                            class="p-8 text-center text-slate-400 text-xs font-medium"
                                        >
                                            Belum ada bahan makanan yang
                                            diformulasikan.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(b, idx) in bahanCalculations"
                                        :key="idx"
                                        class="hover:bg-slate-50/70 transition-colors"
                                    >
                                        <td
                                            class="p-3 text-center align-top pt-4 font-bold text-slate-500"
                                        >
                                            {{ idx + 1 }}
                                        </td>
                                        <td
                                            class="p-3 font-bold text-slate-900 align-top pt-4"
                                        >
                                            <div>{{ b.nama_po || b.nama }}</div>
                                            <div
                                                class="text-[10.5px] text-slate-500 font-medium mt-0.5"
                                            >
                                                TKPI: {{ b.nama }}
                                            </div>
                                            <span
                                                v-if="b.alergen"
                                                class="block text-[9.5px] text-amber-700 font-normal mt-0.5"
                                            >
                                                Alergen: {{ b.alergen }}
                                            </span>
                                        </td>
                                        <td
                                            class="p-3 text-center align-top pt-3.5"
                                        >
                                            <span
                                                :class="[
                                                    'px-2.5 py-1 text-[10.5px] font-bold rounded-lg border inline-block',
                                                    b.tipe_porsi === 'alergi'
                                                        ? 'bg-rose-50 text-rose-800 border-rose-200'
                                                        : 'bg-slate-50 text-slate-700 border-slate-200',
                                                ]"
                                            >
                                                {{
                                                    b.tipe_porsi === "alergi"
                                                        ? "Alergi: " +
                                                          (b.jenis_alergi ||
                                                              "Khusus")
                                                        : "Normal (Standar)"
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="p-3 text-slate-600 align-top pt-4"
                                        >
                                            {{ b.kategori }}
                                        </td>
                                        <td
                                            class="p-3 text-center font-bold text-slate-800 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{ b.gram_pk || 0 }}g /
                                            {{ b.gram_pb || 0 }}g
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-slate-900 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{
                                                formatGrossWeight(
                                                    b.totalGrossKg,
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-right text-slate-600 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{ formatRupiah(b.harga_master) }}
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-emerald-800 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{ formatRupiah(b.subtotalMaster) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="bg-slate-50 font-bold border-t border-slate-200 text-xs"
                                >
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="p-3.5 text-right font-bold text-slate-700"
                                        >
                                            Grand Total Estimasi Biaya Belanja
                                            Bahan:
                                        </td>
                                        <td
                                            class="p-3.5 text-right font-black text-slate-900 whitespace-nowrap"
                                        >
                                            {{
                                                grandTotalDraftMaster
                                                    ? bahanCalculations
                                                          .reduce(
                                                              (s, x) =>
                                                                  s +
                                                                  x.totalGrossKg,
                                                              0,
                                                          )
                                                          .toFixed(1) + " kg"
                                                    : "-"
                                            }}
                                        </td>
                                        <td></td>
                                        <td
                                            class="p-3.5 text-right font-black text-emerald-900 text-sm whitespace-nowrap"
                                        >
                                            {{
                                                formatRupiah(
                                                    grandTotalDraftMaster,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Baris Tombol Aksi Simpan & Ajukan -->
                    <div
                        class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3"
                    >
                        <Button
                            type="button"
                            @click="buatMenuSubTab = 'formula_gizi'"
                            className="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 h-10 rounded-xl cursor-pointer w-full sm:w-auto"
                        >
                            <ChevronLeft class="h-4 w-4 mr-1.5" />
                            Kembali ke Formula Gizi (Langkah 2)
                        </Button>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <Button
                                type="button"
                                @click="simpanSebagaiDraft"
                                className="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold px-5 h-10 rounded-xl cursor-pointer w-full sm:w-auto"
                            >
                                <FileText class="h-4 w-4 mr-1.5" />
                                Simpan sebagai Draft
                            </Button>
                            <Button
                                type="button"
                                @click="ajukanKeKeuangan"
                                className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-6 h-10 rounded-xl shadow-xs cursor-pointer w-full sm:w-auto"
                            >
                                <Send class="h-4 w-4 mr-1.5" />
                                Ajukan
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
