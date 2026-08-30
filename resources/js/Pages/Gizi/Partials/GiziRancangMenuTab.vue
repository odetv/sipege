<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
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
        id: "pre_order",
        label: "2. Formula Gizi",
        icon: ClipboardList,
    },
    {
        id: "order",
        label: "3. Review & Pengajuan",
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

    if (modalTotalPm.value === 0 && editingKelompok.value.status_menerima !== false) {
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
    return totalKg;
}

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

const validationErrors = ref({});

function clearError(field) {
    if (validationErrors.value[field]) {
        delete validationErrors.value[field];
    }
}

function validateStep1() {
    const errs = {};
    if (!tanggalRencana.value) {
        errs.tanggalRencana = "Tanggal rencana masak & distribusi wajib diisi.";
    } else {
        // Cek duplikasi tanggal (hanya boleh 1 WO per tanggal)
        const duplicateDateWo = (props.workOrdersList || []).find((w) => {
            const wTgl = typeof w.tanggal_distribusi === 'string' ? w.tanggal_distribusi.substring(0, 10) : '';
            const isSameDate = wTgl === tanggalRencana.value;
            const isDifferentWo = w.nomor_wo !== woNo.value && w.uuid !== props.activeWorkOrder?.uuid && w.id !== props.activeWorkOrder?.id;
            return isSameDate && isDifferentWo;
        });
        if (duplicateDateWo) {
            errs.tanggalRencana = `Tanggal ini sudah memiliki Work Order ("${duplicateDateWo.nama_menu}"). Hanya boleh 1 menu per tanggal.`;
        }
    }
    if (!namaMenuAktif.value || !namaMenuAktif.value.trim()) {
        errs.namaMenuAktif = "Nama menu paket MBG wajib diisi.";
    }
    if (!subMenuKomponen.value.energi || !subMenuKomponen.value.energi.trim()) {
        errs.energi = "Komponen energi wajib diisi.";
    }
    if (!subMenuKomponen.value.protein || !subMenuKomponen.value.protein.trim()) {
        errs.protein = "Komponen protein wajib diisi.";
    }
    if (!subMenuKomponen.value.lemak || !subMenuKomponen.value.lemak.trim()) {
        errs.lemak = "Komponen lemak wajib diisi.";
    }
    if (!subMenuKomponen.value.karbohidrat || !subMenuKomponen.value.karbohidrat.trim()) {
        errs.karbohidrat = "Komponen karbohidrat wajib diisi.";
    }
    if (!subMenuKomponen.value.serat || !subMenuKomponen.value.serat.trim()) {
        errs.serat = "Komponen serat wajib diisi.";
    }
    if (kelompokMenerimaAktif.value.length === 0) {
        errs.kelompok = "Minimal 1 kelompok sasaran penerima manfaat harus berstatus Menerima.";
    } else {
        const zeroReceiving = woKelompokList.value.find(k => k.status_menerima !== false && ((Number(k.total_porsi_kecil) || 0) + (Number(k.total_porsi_besar) || 0) <= 0));
        if (zeroReceiving) {
            errs.kelompok = `Kelompok "${zeroReceiving.nama_kelompok}" berstatus Menerima tetapi memiliki 0 porsi. Wajib minimal 1 porsi atau tandai 'Tidak Menerima'.`;
        }
    }
    validationErrors.value = errs;
    return Object.keys(errs).length === 0;
}

function validateStep2() {
    const isStep1Valid = validateStep1();
    const errs = { ...validationErrors.value };

    if (!selectedBahanList.value || selectedBahanList.value.length === 0) {
        errs.selectedBahan = "Wajib memilih dan menambahkan minimal 1 bahan pangan dari Database TKPI 2020.";
    }

    selectedBahanList.value.forEach((b, i) => {
        if (b.tipe_porsi === 'alergi' && (!b.jenis_alergi || !b.jenis_alergi.trim())) {
            errs['bahan_' + i + '_alergi'] = "Jenis alergi wajib dipilih.";
        }
        const pkVal = Number(b.gram_pk) || 0;
        const pbVal = Number(b.gram_pb) || 0;
        if (pkVal <= 0 && pbVal <= 0) {
            errs['bahan_' + i + '_gram'] = "Wajib isi minimal salah satu (PK atau PB) > 0";
        }
        if (b.bdd === null || b.bdd === undefined || b.bdd === '' || Number(b.bdd) <= 0) {
            errs['bahan_' + i + '_bdd'] = "BDD > 0%";
        }
        if (b.buffer === null || b.buffer === undefined || b.buffer === '' || Number(b.buffer) < 0) {
            errs['bahan_' + i + '_buffer'] = "Wajib >= 0%";
        }
    });

    validationErrors.value = errs;
    return isStep1Valid && Object.keys(errs).length === 0;
}

function handleSwitchSubTab(targetTab) {
    if (targetTab === 'pre_order' || targetTab === 'formula-gizi' || targetTab === 'formula_gizi') {
        if (!validateStep1()) {
            return;
        }
    } else if (targetTab === 'order' || targetTab === 'pembelian_bahan' || targetTab === 'pembelian-bahan') {
        if (!validateStep2()) {
            return;
        }
    }
    buatMenuSubTab.value = normalizeStep(targetTab);
}

function handleMulaiFormulasiWo() {
    if (validateStep1()) {
        buatMenuSubTab.value = "pre_order";
    }
}

function handleRemoveBahan(index) {
    selectedBahanList.value.splice(index, 1);
    // Clear error for this index if any
    delete validationErrors.value.selectedBahan;
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
            b.tkpi && b.tkpi.energi !== undefined
                ? b.tkpi
                : tkpiItems.value.find(
                      (i) =>
                          (b.tkpi_id && (i.id === b.tkpi_id || i.code === b.tkpi_id)) ||
                          (b.id && (i.id === b.id || i.code === b.id)) ||
                          (b.code && (i.id === b.code || i.code === b.code)) ||
                          (i.nama && b.nama && i.nama.toLowerCase().trim() === b.nama.toLowerCase().trim()),
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

        // Biaya PO
        let subtotalMaster = Math.round(totalGrossKg * (b.harga_master || 0));
        if (totalGrossKg > 0 && (b.harga_master || 0) > 0 && subtotalMaster === 0) {
            subtotalMaster = Math.ceil(totalGrossKg * b.harga_master);
        }
        let subtotalAktual = Math.round(
            totalGrossKg * (b.harga_aktual || b.harga_master || 0),
        );
        if (totalGrossKg > 0 && (b.harga_aktual || b.harga_master || 0) > 0 && subtotalAktual === 0) {
            subtotalAktual = Math.ceil(totalGrossKg * (b.harga_aktual || b.harga_master));
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
    const alergiBahanList = bahanCalculations.value.filter(
        (b) => b.tipe_porsi === "alergi" && b.jenis_alergi && b.jenis_alergi.trim()
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
        const bahanNormalSafe = bahanCalculations.value.filter((b) => {
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
        nama_menu: namaMenuAktif.value || 'Menu MBG',
        siklus_ke: 1,
        status: statusStr,
        current_step: stepNumber,
        komponen_energi: subMenuKomponen.value.energi || null,
        komponen_protein: subMenuKomponen.value.protein || null,
        komponen_lemak: subMenuKomponen.value.lemak || null,
        komponen_karbohidrat: subMenuKomponen.value.karbohidrat || null,
        komponen_serat: subMenuKomponen.value.serat || null,
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
        items: (bahanCalculations.value || []).map(b => ({
            tkpi_id: b.id || b.code,
            nama: b.nama,
            nama_po: b.nama_po || b.nama,
            kategori: b.kategori,
            tipe_porsi: b.tipe_porsi || 'normal',
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
        kelompoks: woKelompokList.value.map(k => {
            const masterK = (props.kelompokList || []).find(x => x.id === k.id) || {};
            return {
                id: k.id,
                nama_kelompok: k.nama_kelompok,
                kategori: k.kategori,
                is_menerima: k.status_menerima !== false,
                total_porsi_kecil: Number(k.total_porsi_kecil) || 0,
                total_porsi_besar: Number(k.total_porsi_besar) || 0,
                total_penerima: Number(k.total_penerima) || ((Number(k.total_porsi_kecil) || 0) + (Number(k.total_porsi_besar) || 0)),
                status_alergi: k.has_alergi ? 'Ada Alergi' : 'Tidak Ada Alergi',
                rincian: Array.isArray(k.rincian) && k.rincian.length > 0 ? k.rincian : (masterK.rincian || []),
                detail_alergi: Array.isArray(k.keterangan_alergi) && k.keterangan_alergi.length > 0 ? k.keterangan_alergi : (masterK.keterangan_alergi || []),
            };
        }),
    };
}

function simpanDraftStep1() {
    if (!validateStep1()) return;
    isSubmitting.value = true;
    statusPengajuanWo.value = "Draft";
    const payload = getPayload("Draft", 1);

    router.post('/gizi/work-order', payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            triggerSubmitSuccess("Draft Langkah 1 (Perencanaan Produksi) berhasil disimpan ke Database!");
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
}

function simpanDraftStep2() {
    if (!validateStep1()) {
        buatMenuSubTab.value = 'work_order';
        return;
    }
    if (!validateStep2()) return;
    isSubmitting.value = true;
    statusPengajuanWo.value = "Draft";
    const payload = getPayload("Draft", 2);

    router.post('/gizi/work-order', payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            triggerSubmitSuccess("Draft Langkah 2 (Formulasi Gizi) berhasil disimpan ke Database!");
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
}

function simpanSebagaiDraft() {
    if (!validateStep1()) {
        buatMenuSubTab.value = 'work_order';
        return;
    }
    isSubmitting.value = true;
    statusPengajuanWo.value = "Draft";
    const payload = getPayload("Draft", 3);

    router.post('/gizi/work-order', payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            router.visit('/gizi/daftar-menu');
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
}

function ajukanKeKeuangan() {
    if (!validateStep1()) {
        buatMenuSubTab.value = 'work_order';
        return;
    }
    if (!validateStep2()) {
        buatMenuSubTab.value = 'pre_order';
        return;
    }
    isSubmitting.value = true;
    statusPengajuanWo.value = "Diajukan ke Keuangan";
    const payload = getPayload("Diajukan ke Keuangan", 3);

    router.post('/gizi/work-order', payload, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            router.visit('/gizi/daftar-menu');
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
}

// Populate from activeWorkOrder if present
watch(
    () => props.activeWorkOrder,
    (wo) => {
        if (wo) {
            woNo.value = wo.nomor_wo || woNo.value;
            tanggalRencana.value = typeof wo.tanggal_distribusi === 'string' ? wo.tanggal_distribusi.substring(0, 10) : (wo.tanggal_distribusi || tanggalRencana.value);
            namaMenuAktif.value = wo.nama_menu || namaMenuAktif.value;
            statusPengajuanWo.value = wo.status || "Draft";
            if (wo.komponen_energi || wo.komponen_protein || wo.komponen_lemak || wo.komponen_karbohidrat || wo.komponen_serat) {
                subMenuKomponen.value = {
                    energi: wo.komponen_energi || "",
                    protein: wo.komponen_protein || "",
                    lemak: wo.komponen_lemak || "",
                    karbohidrat: wo.komponen_karbohidrat || "",
                    serat: wo.komponen_serat || "",
                };
            }

            // 1. Populate selectedBahanList with full TKPI nutritional lookup
            if (wo.items && wo.items.length > 0) {
                selectedBahanList.value = wo.items.map((it) => {
                    const matchedTkpi = tkpiItems.value.find(
                        (t) =>
                            (it.tkpi_id && (t.id === it.tkpi_id || t.code === it.tkpi_id)) ||
                            (t.nama && it.nama && t.nama.toLowerCase().trim() === it.nama.toLowerCase().trim())
                    ) || {};

                    return {
                        id: matchedTkpi.id || it.tkpi_id || it.id,
                        code: matchedTkpi.code || it.tkpi_id || it.id,
                        tkpi_id: matchedTkpi.id || it.tkpi_id || it.id,
                        nama: it.nama || matchedTkpi.nama,
                        nama_po: it.nama_po || it.nama || matchedTkpi.nama,
                        kategori: it.kategori || matchedTkpi.kategori || 'Lainnya',
                        tipe_porsi: it.tipe_porsi || 'normal',
                        jenis_alergi: it.jenis_alergi || '',
                        alergen: it.alergen || matchedTkpi.alergen || '',
                        gram_pk: Number(it.gram_pk) || 0,
                        gram_pb: Number(it.gram_pb) || 0,
                        bdd: Number(it.bdd) || matchedTkpi.bdd || 100,
                        buffer: (it.buffer !== undefined && it.buffer !== null && it.buffer !== '') ? Number(it.buffer) : 0,
                        harga_master: Number(it.harga_master) || matchedTkpi.harga_master || 0,
                        harga_aktual: Number(it.harga_aktual) || it.harga_master || matchedTkpi.harga_master || 0,
                        tkpi: matchedTkpi,
                    };
                });
            }

            // 2. Populate woKelompokList with saved KPM participation and class breakdown
            if (wo.kelompoks && wo.kelompoks.length > 0) {
                woKelompokList.value = (props.kelompokList || []).map((masterK) => {
                    const savedK = wo.kelompoks.find(
                        (sk) => sk.kelompok_id === masterK.id || sk.nama_kelompok === masterK.nama_kelompok
                    );
                    const norm = normalizeKelompokForWo(masterK);
                    if (savedK) {
                        return {
                            ...norm,
                            status_menerima: savedK.is_menerima !== false,
                            total_porsi_kecil: savedK.porsi_kecil !== undefined ? Number(savedK.porsi_kecil) : norm.total_porsi_kecil,
                            total_porsi_besar: savedK.porsi_besar !== undefined ? Number(savedK.porsi_besar) : norm.total_porsi_besar,
                            total_penerima: savedK.total_penerima !== undefined ? Number(savedK.total_penerima) : norm.total_penerima,
                            status_alergi: savedK.status_alergi || norm.status_alergi,
                            rincian: Array.isArray(savedK.rincian) && savedK.rincian.length > 0 ? savedK.rincian : norm.rincian,
                            keterangan_alergi: Array.isArray(savedK.detail_alergi) && savedK.detail_alergi.length > 0 ? savedK.detail_alergi : norm.keterangan_alergi,
                        };
                    }
                    return norm;
                });
            }
        }
    },
    { immediate: true }
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
                        <strong class="uppercase font-mono">{{
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
                    <span>• Terjadwal:
                        <strong class="text-slate-200 font-black">
                            {{ woKelompokList.length }} Kelompok
                            <span class="text-[11px] font-normal text-slate-300">
                                ({{ kelompokMenerimaAktif.length }} Menerima<span v-if="woKelompokList.length - kelompokMenerimaAktif.length > 0">, {{ woKelompokList.length - kelompokMenerimaAktif.length }} Tidak Menerima</span>)
                            </span>
                        </strong>
                    </span>
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
            <!-- Banner Catatan Penolakan jika WO ini sebelumnya ditolak oleh Keuangan -->
            <div
                v-if="props.activeWorkOrder && (props.activeWorkOrder.status?.toLowerCase().includes('ditolak') || props.activeWorkOrder.catatan_keuangan)"
                class="p-4 sm:p-5 rounded-2xl bg-rose-50 border-2 border-rose-200 text-rose-900 space-y-2 shadow-xs"
            >
                <div class="flex items-center gap-2 font-black text-rose-800 text-sm">
                    <AlertCircle class="h-5 w-5 text-rose-600 shrink-0" />
                    <span>Work Order Ini Sebelumnya Ditolak oleh Keuangan</span>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-rose-200/80 text-xs font-medium text-slate-800 whitespace-pre-wrap leading-relaxed shadow-2xs">
                    <strong class="text-rose-900 block mb-1">Catatan Verifikator Keuangan:</strong>
                    {{ props.activeWorkOrder.catatan_keuangan || 'Silakan sesuaikan formula gizi atau anggaran belanja pada langkah berikutnya, kemudian ajukan kembali ke bagian keuangan.' }}
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
                            <CardDescription class="text-xs sm:text-sm mt-0.5">
                                Penetapan jadwal distribusi menu, penamaan paket
                                MBG, dan penguncian kuota Penerima Manfaat (PM)
                                resmi SPPG.
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
                                v-model="tanggalRencana" @change="clearError('tanggalRencana')"
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
                                    <Sparkles class="h-3 w-3 shrink-0" />
                                    <span class="truncate"
                                        >Gunakan Kalender</span
                                    >
                                </button>
                            </div>
                            <input
                                type="text"
                                v-model="namaMenuAktif"
                                @input="clearError('namaMenuAktif')"
                                required
                                placeholder="Contoh: Nasi Liwet Sunda, Ayam Goreng..."
                                :class="[
                                    'w-full text-xs font-bold text-slate-900 rounded-lg border p-2.5 bg-white',
                                    validationErrors.namaMenuAktif ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20' : 'border-slate-300 focus:ring-primary focus:border-primary'
                                ]"
                            />
                            <p v-if="validationErrors.namaMenuAktif" class="text-[11px] text-rose-600 font-semibold mt-1 flex items-center gap-1">
                                <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                <span>{{ validationErrors.namaMenuAktif }}</span>
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
                                        Rincian Sub Menu (Komponen Gizi MBG)
                                    </h4>
                                    <p class="text-[10.5px] text-slate-500">
                                        Input rincian nama hidangan per
                                        masing-masing komponen makronutrisi &
                                        serat pangan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"
                        >
                            <!-- Sub Menu Energi -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="validationErrors.energi ? 'border-rose-400 bg-rose-50/20' : 'border-amber-200/90'"
                            >
                                <label
                                    class="text-[11px] font-black text-amber-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-amber-500"
                                    ></span>
                                    <span>Energi <strong class="text-rose-500">*</strong></span>
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.energi" @input="clearError('energi')"
                                    placeholder="Contoh: Nasi Putih Gurih"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-amber-500 focus:border-amber-500 p-2 bg-slate-50/50"
                                />
                                <p v-if="validationErrors.energi" class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5">
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{ validationErrors.energi }}</span>
                                </p>
                            </div>

                            <!-- Sub Menu Protein -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="validationErrors.protein ? 'border-rose-400 bg-rose-50/20' : 'border-rose-200/90'"
                            >
                                <label
                                    class="text-[11px] font-black text-rose-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-rose-500"
                                    ></span>
                                    <span>Protein <strong class="text-rose-500">*</strong></span>
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.protein" @input="clearError('protein')"
                                    placeholder="Contoh: Ayam Goreng Lengkuas"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-rose-500 focus:border-rose-500 p-2 bg-slate-50/50"
                                />
                                <p v-if="validationErrors.protein" class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5">
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{ validationErrors.protein }}</span>
                                </p>
                            </div>

                            <!-- Sub Menu Lemak -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="validationErrors.lemak ? 'border-rose-400 bg-rose-50/20' : 'border-yellow-200/90'"
                            >
                                <label
                                    class="text-[11px] font-black text-yellow-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-yellow-500"
                                    ></span>
                                    <span>Lemak <strong class="text-rose-500">*</strong></span>
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.lemak" @input="clearError('lemak')"
                                    placeholder="Contoh: Tahu Bacem Goreng"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-yellow-500 focus:border-yellow-500 p-2 bg-slate-50/50"
                                />
                                <p v-if="validationErrors.lemak" class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5">
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{ validationErrors.lemak }}</span>
                                </p>
                            </div>

                            <!-- Sub Menu Karbohidrat -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="validationErrors.karbohidrat ? 'border-rose-400 bg-rose-50/20' : 'border-blue-200/90'"
                            >
                                <label
                                    class="text-[11px] font-black text-blue-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-blue-500"
                                    ></span>
                                    <span>Karbohidrat <strong class="text-rose-500">*</strong></span>
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.karbohidrat" @input="clearError('karbohidrat')"
                                    placeholder="Contoh: Nasi Liwet / Ubi"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-blue-500 focus:border-blue-500 p-2 bg-slate-50/50"
                                />
                                <p v-if="validationErrors.karbohidrat" class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5">
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{ validationErrors.karbohidrat }}</span>
                                </p>
                            </div>

                            <!-- Sub Menu Serat -->
                            <div
                                class="space-y-1 bg-white p-2.5 rounded-xl border shadow-2xs"
                                :class="validationErrors.serat ? 'border-rose-400 bg-rose-50/20' : 'border-emerald-200/90'"
                            >
                                <label
                                    class="text-[11px] font-black text-emerald-900 flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-emerald-500"
                                    ></span>
                                    <span>Serat <strong class="text-rose-500">*</strong></span>
                                </label>
                                <input
                                    type="text"
                                    v-model="subMenuKomponen.serat" @input="clearError('serat')"
                                    placeholder="Contoh: Lalapan Sayur & Melon"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:ring-emerald-500 focus:border-emerald-500 p-2 bg-slate-50/50"
                                />
                                <p v-if="validationErrors.serat" class="text-[10px] text-rose-600 font-semibold mt-1 flex items-center gap-0.5">
                                    <AlertCircle class="h-3 w-3 shrink-0" />
                                    <span>{{ validationErrors.serat }}</span>
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
                                        >Data Kuota Penerima Manfaat (PM) Fix
                                        per Tanggal Distribusi</span
                                    >
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Kuota porsi terkunci otomatis berdasarkan
                                    data rekapitulasi porsi sasaran & penerima aktif
                                    SPPG pada tanggal tersebut.
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
                                    SD 4-6, SMP, SMA, Guru, Tendik, Bumil &
                                    Busui
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
                                        Daftar Kelompok Sasaran Distribusi ({{
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
                        <div class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[650px] text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none">
                                        <th class="py-3.5 px-3 text-center">Status</th>
                                        <th class="py-3.5 px-3">Nama Kelompok Sasaran</th>
                                        <th class="py-3.5 px-3">Kategori</th>
                                        <th class="py-3.5 px-3 text-center">Porsi Kecil (PK)</th>
                                        <th class="py-3.5 px-3 text-center">Porsi Besar (PB)</th>
                                        <th class="py-3.5 px-3 text-center">Total PM</th>
                                        <th class="py-3.5 px-3">Status Alergi</th>
                                        <th class="py-3.5 px-3 text-center w-28">Aksi</th>
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
                                                    k.status_menerima !== false
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
                                                <UserX class="h-3 w-3 mr-1" />
                                                Tidak Menerima
                                            </span>
                                        </td>

                                        <!-- Nama Kelompok -->
                                        <td
                                            class="p-3 font-bold align-middle"
                                            :class="
                                                k.status_menerima === false
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
                                                k.status_menerima === false
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
                                                k.status_menerima === false
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
                                                k.status_menerima === false
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
                                                    k.keterangan_alergi.length >
                                                        0
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
                                                    {{ al.jenis_alergi }}:
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
                                                            al.porsi_kecil || 0
                                                        }}, PB:
                                                        {{
                                                            al.porsi_besar || 0
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
                                                    k.status_menerima === false
                                                        ? 'text-slate-400'
                                                        : 'text-rose-700'
                                                "
                                            >
                                                ⚠️
                                                {{
                                                    (k.alergi_porsi_kecil ||
                                                        0) +
                                                    (k.alergi_porsi_besar || 0)
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
                                        <td class="py-3 px-3 text-center align-middle">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button
                                                    type="button"
                                                    @click="handleOpenModalEditPm(k)"
                                                    class="h-8 w-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                    title="Edit Detail PM per Sub-Sub Kategori"
                                                >
                                                    <Edit3 class="h-4 w-4" />
                                                </button>

                                                <button
                                                    v-if="k.status_menerima !== false"
                                                    type="button"
                                                    @click="handleToggleStatusMenerima(k, false)"
                                                    class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                    title="Tandai TIDAK MENERIMA Menu Hari Ini"
                                                >
                                                    <UserX class="h-4 w-4" />
                                                </button>
                                                <button
                                                    v-else
                                                    type="button"
                                                    @click="handleToggleStatusMenerima(k, true)"
                                                    class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                                    title="Aktifkan Kembali Penerimaan"
                                                >
                                                    <RotateCcw class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-if="validationErrors.kelompok" class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-bold flex items-center gap-2 mt-3">
                            <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
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
                        <div v-if="modalPmError" class="p-3 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-800 font-bold flex items-center gap-2">
                            <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
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
                            <div class="flex items-center gap-2 flex-wrap">
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
                            <CardDescription class="text-xs sm:text-sm mt-0.5">
                                Penentuan gramasi bahan makanan dari TKPI 2020,
                                evaluasi real-time kecukupan gizi AKG BGN, dan
                                analisis pagu food cost.
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
                        <div class="flex items-start justify-between gap-3">
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
                                            Peringatan Alergen Menu: Terdeteksi
                                            Bahan yang Berpotensi Alergi
                                        </h4>
                                        <Badge
                                            class="bg-rose-600 text-white font-extrabold text-[10px] px-2 py-0.5"
                                        >
                                            {{
                                                analisaAlergiMenu.conflicts
                                                    .length
                                            }}
                                            Alergen Teridentifikasi
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-amber-800 mt-0.5">
                                        Nama menu
                                        <em>"{{ namaMenuAktif }}"</em>
                                        mengandung bahan yang cocok dengan data
                                        riwayat alergi siswa/penerima aktif hari
                                        ini.
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
                                    : analisaAlergiMenu.conflicts.length === 3
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
                                        analisaAlergiMenu.conflicts.length ===
                                            3 && cfIdx === 2,
                                    'md:col-span-2':
                                        analisaAlergiMenu.conflicts.length %
                                            2 !==
                                            0 &&
                                        analisaAlergiMenu.conflicts.length !==
                                            3 &&
                                        cfIdx ===
                                            analisaAlergiMenu.conflicts.length -
                                                1,
                                }"
                            >
                                <div
                                    class="flex items-center justify-between gap-2 border-b border-amber-100 pb-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="px-2 py-0.5 rounded text-[11px] font-black bg-rose-100 text-rose-800 border border-rose-200"
                                        >
                                            {{ cf.jenis_alergi }}
                                        </span>
                                        <span
                                            class="text-[11px] text-slate-500"
                                        >
                                            (Kata kunci menu:
                                            <strong>"{{ cf.keyword }}"</strong>)
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
                                        <span>Porsi Pengganti Diperlukan:</span>
                                        <strong class="text-slate-900"
                                            >PK: {{ cf.porsi_kecil }} & PB:
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
                                                    cf.kelompok_names.length
                                                }}):</span
                                            >
                                        </div>
                                        <div class="flex flex-wrap gap-1">
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
                                            Tambahkan formulasi bahan pangan
                                            <em>{{ cf.rekomendasi }}</em>
                                            dengan peruntukan porsi
                                            <strong
                                                >"Alergi:
                                                {{ cf.jenis_alergi }}"</strong
                                            >
                                            untuk {{ cf.total }} porsi.
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Case 2: Ada Porsi Alergi Aktif tapi tidak ada kata kunci bentrok langsung di judul menu (Info Pengingat) -->
                    <div
                        v-else-if="analisaAlergiMenu.totalSiswaAlergi > 0"
                        class="p-4 rounded-2xl bg-sky-50/80 border border-sky-200 shadow-xs space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
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
                                            >Pengingat Rekapitulasi Alergi PM
                                            Terjadwal ({{
                                                analisaAlergiMenu.totalSiswaAlergi
                                            }}
                                            Siswa)</span
                                        >
                                    </h4>
                                    <p class="text-xs text-sky-800 mt-0.5">
                                        Meskipun nama menu tidak menyebutkan
                                        alergen langsung, terdapat siswa aktif
                                        dengan riwayat alergi khusus. Pastikan
                                        bahan dan bumbu aman dari kontaminasi
                                        silang.
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
                            Seluruh {{ totalPM }} penerima manfaat pada kelompok
                            aktif hari ini tidak memiliki catatan alergi khusus.
                            Semua porsi dapat disiapkan dengan formulasi menu
                            standar.
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
                                <p class="text-sm font-extrabold text-blue-950">
                                    Database Standar Pangan TKPI 2020
                                </p>
                                <p class="text-xs text-blue-700 mt-0.5">
                                    Pilih dari
                                    {{ tkpiItems.length }} data komposisi pangan
                                    resmi Kemenkes RI
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
                                            isTkpiDropdownOpen = !isTkpiDropdownOpen
                                        "
                                        :class="[
                                            'w-full h-11 px-4 py-2 text-sm font-semibold rounded-xl border bg-white text-slate-900 flex items-center justify-between shadow-2xs hover:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-left transition-colors cursor-pointer',
                                            selectedBahanList.length === 0
                                                ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50/20'
                                                : isTkpiDropdownOpen ? 'ring-2 ring-primary/20 border-primary' : 'border-slate-300'
                                        ]"
                                    >
                                    <span
                                        v-if="selectedTkpiItem"
                                        class="truncate font-bold text-slate-900 flex items-center gap-2 min-w-0"
                                    >
                                        <span
                                            class="px-2 py-0.5 text-xs rounded-md bg-blue-100 text-blue-800 font-bold shrink-0"
                                        >
                                            {{ selectedTkpiItem.kategori }}
                                        </span>
                                        <span
                                            class="font-mono text-xs text-primary font-bold"
                                            >[{{ selectedTkpiItem.id }}]</span
                                        >
                                        <span class="truncate text-sm">{{
                                            selectedTkpiItem.nama
                                        }}</span>
                                    </span>
                                    <span
                                        v-else
                                        class="text-slate-400 font-medium text-sm truncate"
                                    >
                                        -- Cari & Pilih Bahan Makanan TKPI 2020
                                        --
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
                                            @click.stop="tkpiSearchText = ''"
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
                                            Tidak ada bahan makanan yang cocok
                                            dengan "<strong
                                                class="text-slate-700"
                                                >{{ tkpiSearchText }}</strong
                                            >"
                                        </div>

                                        <button
                                            v-for="item in filteredComboboxTkpiList"
                                            :key="item.id"
                                            type="button"
                                            @click.stop="selectTkpiItem(item)"
                                            :class="[
                                                'w-full text-left p-3 rounded-xl transition-all flex items-start justify-between gap-3 cursor-pointer',
                                                selectedTkpiOption === item.id
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
                                                        {{ item.kategori }}
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
                                                        {{ item.energi }}
                                                        kkal
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center gap-1 font-semibold bg-rose-50 text-rose-900 px-2 py-0.5 rounded-md border border-rose-100"
                                                    >
                                                        🥩 Prot:
                                                        {{ item.protein }}g
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center gap-1 font-semibold bg-orange-50 text-orange-900 px-2 py-0.5 rounded-md border border-orange-100"
                                                    >
                                                        🧈 Lemak:
                                                        {{ item.lemak }}g
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center gap-1 font-semibold bg-emerald-50 text-emerald-900 px-2 py-0.5 rounded-md border border-emerald-100"
                                                    >
                                                        🍚 Karbo:
                                                        {{ item.karbohidrat }}g
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center gap-1 font-semibold bg-teal-50 text-teal-900 px-2 py-0.5 rounded-md border border-teal-100"
                                                    >
                                                        🥦 Serat:
                                                        {{ item.serat }}g
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
                                                        v-if="item.alergen"
                                                        class="inline-flex items-center gap-1 font-black bg-rose-100 text-rose-800 px-2 py-0.5 rounded-md border border-rose-200"
                                                    >
                                                        ⚠️ Alergen:
                                                        {{ item.alergen }}
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
                                        <span class="text-[11px] text-slate-400"
                                            >Bisa di-scroll & diketik
                                            langsung</span
                                        >
                                    </div>
                                </div>
                                <p v-if="selectedBahanList.length === 0" class="text-xs text-rose-600 font-bold mt-1.5 flex items-center gap-1.5">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0 text-rose-600" />
                                    <span>Wajib memilih dan menambahkan minimal 1 bahan makanan dari database TKPI 2020.</span>
                                </p>
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
            <div class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none">
                                <th class="py-3.5 px-3 text-center w-10">No</th>
                                <th class="py-3.5 px-3 min-w-[180px]">Bahan Pangan (TKPI)</th>
                                <th class="py-3.5 px-3 min-w-[170px]">Nama di Belanja PO</th>
                                <th class="py-3.5 px-3">Kategori</th>
                                <th class="py-3.5 px-3 text-center min-w-[160px]">Peruntukan Porsi</th>
                                <th class="py-3.5 px-3 text-center">Gram Bersih (PK/PB)</th>
                                <th class="py-3.5 px-3 text-center">BDD (%)</th>
                                <th class="py-3.5 px-3 text-center min-w-[90px]">Buffer (%)</th>
                                <th class="py-3.5 px-3 text-right">Kg Kotor</th>
                                <th class="py-3.5 px-3 text-right">Harga Satuan</th>
                                <th class="py-3.5 px-3 text-right">Subtotal</th>
                                <th class="py-3.5 px-3 text-center w-14">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
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
                                            class="font-extrabold text-rose-800 text-sm flex items-center gap-1.5"
                                        >
                                            <AlertCircle class="h-4 w-4 text-rose-600" />
                                            <span>Wajib Memilih & Menambahkan Minimal 1 Bahan Makanan</span>
                                        </p>
                                        <p
                                            class="text-xs text-slate-400 max-w-md"
                                        >
                                            Silakan cari & pilih bahan makanan
                                            resmi dari kotak pencarian
                                            <strong
                                                >Standar Pangan TKPI
                                                2020</strong
                                            >
                                            di atas, lalu klik
                                            <strong>+ Tambah</strong>
                                            untuk mulai meracik gramasi resep.
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
                                        v-model="selectedBahanList[idx].nama_po"
                                        :placeholder="b.nama"
                                        class="w-full h-9 text-xs font-bold rounded-lg border-slate-300 px-2.5 bg-white text-slate-900 focus:ring-primary focus:border-primary shadow-2xs placeholder:text-slate-400"
                                        title="Nama bahan belanja yang akan ditampilkan di Purchase Order (PO) Akuntan"
                                    />
                                </td>
                                <!-- Kategori -->
                                <td class="p-3 text-slate-600 align-top pt-4">
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
                                                    .tipe_porsi === 'alergi'
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
                                                    .tipe_porsi === 'alergi'
                                            "
                                            class="w-full space-y-1"
                                        >
                                            <select
                                                v-model="
                                                    selectedBahanList[idx]
                                                        .jenis_alergi
                                                "
                                                required
                                                class="w-full text-[11px] font-semibold rounded-md border border-rose-300 bg-rose-50/90 p-1.5 text-rose-900 focus:ring-rose-400 focus:border-rose-400 cursor-pointer"
                                            >
                                                <option value="" disabled>
                                                    -- Pilih Jenis Alergi --
                                                </option>
                                                <option
                                                    v-for="opt in alergiOptionsWithStats"
                                                    :key="opt.value"
                                                    :value="opt.value"
                                                    :disabled="opt.disabled"
                                                    :class="
                                                        opt.disabled
                                                            ? 'text-slate-400 bg-slate-100 italic'
                                                            : 'text-slate-900 font-bold'
                                                    "
                                                >
                                                    {{ opt.labelDisplay }}
                                                </option>
                                            </select>

                                            <!-- Info Porsi Alergi di PM -->
                                            <div
                                                v-if="
                                                    selectedBahanList[idx]
                                                        .jenis_alergi
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
                                                    {{ b.totalTargetCount }}
                                                    Siswa (PK:
                                                    {{ b.targetPKCount }}, PB:
                                                    {{ b.targetPBCount }})
                                                </span>
                                                <span
                                                    v-else
                                                    class="block text-[9.5px] font-bold text-rose-800 bg-rose-100 px-1.5 py-0.5 rounded text-center leading-tight border border-rose-200"
                                                >
                                                    ⚠️ 0 Porsi Alergi di PM
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
                                                    b.alergiDampakList.length >
                                                        0
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
                                <td class="p-2 text-center align-top pt-2.5">
                                    <div class="flex flex-col items-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].gram_pk
                                            "
                                            required
                                            placeholder="0"
                                            :class="[
                                                'w-16 h-9 text-center text-xs font-bold rounded-lg border p-1',
                                                ((!selectedBahanList[idx].gram_pk || Number(selectedBahanList[idx].gram_pk) <= 0) && (!selectedBahanList[idx].gram_pb || Number(selectedBahanList[idx].gram_pb) <= 0))
                                                    ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50 text-rose-900'
                                                    : 'border-slate-300 bg-amber-50/40 text-amber-900 focus:ring-primary focus:border-primary'
                                            ]"
                                            min="0"
                                        />
                                        <span v-if="(!selectedBahanList[idx].gram_pk || Number(selectedBahanList[idx].gram_pk) <= 0) && (!selectedBahanList[idx].gram_pb || Number(selectedBahanList[idx].gram_pb) <= 0)" class="text-[9px] text-rose-600 font-bold block mt-0.5">
                                            Wajib > 0 salah satu
                                        </span>
                                        <span
                                            class="block text-[10px] font-extrabold text-amber-900 bg-amber-100/80 px-1.5 py-0.5 rounded-md mt-1.5 text-center whitespace-nowrap shadow-2xs"
                                            :class="{
                                                'opacity-50 text-slate-400 bg-slate-100':
                                                    b.targetPKCount === 0,
                                            }"
                                            title="Jumlah sasaran Porsi Kecil (PK) yang dikalikan dengan gramasi"
                                        >
                                            × {{ b.targetPKCount }} PK
                                        </span>
                                    </div>
                                </td>

                                <!-- Input Gram PB & Info Porsi -->
                                <td class="p-2 text-center align-top pt-2.5">
                                    <div class="flex flex-col items-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].gram_pb
                                            "
                                            required
                                            placeholder="0"
                                            :class="[
                                                'w-16 h-9 text-center text-xs font-bold rounded-lg border p-1',
                                                ((!selectedBahanList[idx].gram_pk || Number(selectedBahanList[idx].gram_pk) <= 0) && (!selectedBahanList[idx].gram_pb || Number(selectedBahanList[idx].gram_pb) <= 0))
                                                    ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50 text-rose-900'
                                                    : 'border-slate-300 bg-indigo-50/40 text-indigo-900 focus:ring-primary focus:border-primary'
                                            ]"
                                            min="0"
                                        />
                                        <span v-if="(!selectedBahanList[idx].gram_pk || Number(selectedBahanList[idx].gram_pk) <= 0) && (!selectedBahanList[idx].gram_pb || Number(selectedBahanList[idx].gram_pb) <= 0)" class="text-[9px] text-rose-600 font-bold block mt-0.5">
                                            Wajib > 0 salah satu
                                        </span>
                                        <span
                                            class="block text-[10px] font-extrabold text-indigo-900 bg-indigo-100/80 px-1.5 py-0.5 rounded-md mt-1.5 text-center whitespace-nowrap shadow-2xs"
                                            :class="{
                                                'opacity-50 text-slate-400 bg-slate-100':
                                                    b.targetPBCount === 0,
                                            }"
                                            title="Jumlah sasaran Porsi Besar (PB) yang dikalikan dengan gramasi"
                                        >
                                            × {{ b.targetPBCount }} PB
                                        </span>
                                    </div>
                                </td>

                                <!-- BDD (Otomatis dari TKPI & Terkunci) -->
                                <td class="p-2 text-center align-top pt-2.5">
                                    <div class="flex flex-col items-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].bdd
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
                                <td class="p-2 text-center align-top pt-2.5">
                                    <div class="flex flex-col items-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].buffer
                                            "
                                            required
                                            placeholder="0"
                                            :class="[
                                                'w-14 h-9 text-center text-xs font-bold rounded-lg border p-1',
                                                (selectedBahanList[idx].buffer === null || selectedBahanList[idx].buffer === undefined || selectedBahanList[idx].buffer === '' || Number(selectedBahanList[idx].buffer) < 0)
                                                    ? 'border-rose-400 ring-1 ring-rose-300 bg-rose-50 text-rose-900'
                                                    : 'border-slate-300 text-rose-800 bg-white focus:ring-primary focus:border-primary'
                                            ]"
                                            min="0"
                                        />
                                        <span v-if="selectedBahanList[idx].buffer === null || selectedBahanList[idx].buffer === undefined || selectedBahanList[idx].buffer === '' || Number(selectedBahanList[idx].buffer) < 0" class="text-[9px] text-rose-600 font-bold block mt-0.5">
                                            Wajib >= 0%
                                        </span>
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
                                    {{ formatGrossWeight(b.totalGrossKg) }}
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

                                <td class="py-3 px-3 text-center align-top pt-3">
                                    <button
                                        type="button"
                                        @click="handleRemoveBahan(idx)"
                                        class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer mx-auto"
                                        title="Hapus Bahan Ini"
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
                                    Total Estimasi Kebutuhan Belanja Draft PO
                                    (Harga Master):
                                </td>
                                <td class="p-3.5 text-right text-slate-900">
                                    {{
                                        formatGrossWeight(
                                            bahanCalculations.reduce((acc, i) => acc + i.totalGrossKg, 0)
                                        )
                                    }}
                                </td>
                                <td></td>
                                <td
                                    class="p-3.5 text-right text-blue-950 text-sm font-black"
                                >
                                    {{ formatRupiah(grandTotalDraftMaster) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- Real-time Evaluasi Standar AKG BGN & Analisis Food Cost & Pagu Anggaran -->
            <!-- ========================================================================= -->

            <!-- 1. Evaluasi Standar AKG BGN -->
            <div class="space-y-4 pt-2">
                <!-- Info Standar BGN Banner -->
                <div
                    class="p-4 rounded-xl bg-blue-50 border border-blue-200/80 flex items-start gap-3"
                >
                    <Activity class="h-5 w-5 text-blue-700 shrink-0 mt-0.5" />
                    <div>
                        <h4
                            class="font-extrabold text-xs sm:text-sm text-blue-950"
                        >
                            Evaluasi Kecukupan Standar Gizi AKG BGN (Badan Gizi
                            Nasional)
                        </h4>
                        <p
                            class="text-[11.5px] text-blue-800 mt-0.5 leading-relaxed"
                        >
                            Target nutrisi makan siang bergizi terhitung
                            otomatis secara real-time dari formulasi resep:
                            <strong
                                >PK (450 - 550 kkal, Protein 15 - 22g)</strong
                            >
                            dan
                            <strong
                                >PB (650 - 800 kkal, Protein 24 - 35g)</strong
                            >.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- 1. PORSI PK NORMAL -->
                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardHeader
                            className="p-3.5 sm:p-4 border-b border-slate-100 bg-amber-50/60 flex flex-row items-center justify-between"
                        >
                            <div>
                                <CardTitle
                                    class="text-sm sm:text-base font-bold text-amber-950"
                                    >Porsi PK Normal (Standar)</CardTitle
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
                                <Check class="h-3 w-3 mr-1" /> MEMENUHI AKG
                            </Badge>
                        </CardHeader>
                        <CardContent className="p-3.5 sm:p-4 space-y-2.5">
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
                    <Card className="bg-white border-slate-200 shadow-xs">
                        <CardHeader
                            className="p-3.5 sm:p-4 border-b border-slate-100 bg-indigo-50/60 flex flex-row items-center justify-between"
                        >
                            <div>
                                <CardTitle
                                    class="text-sm sm:text-base font-bold text-indigo-950"
                                    >Porsi PB Normal (Standar)</CardTitle
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
                                <Check class="h-3 w-3 mr-1" /> MEMENUHI AKG
                            </Badge>
                        </CardHeader>
                        <CardContent className="p-3.5 sm:p-4 space-y-2.5">
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
                        <Card className="bg-white border-slate-200 shadow-xs">
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
                                            >• {{ al.siswa_pk }} Siswa PK</span
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
                            <CardContent className="p-3.5 sm:p-4 space-y-2.5">
                                <!-- Baris 1: 3 Kolom (Energi, Protein, Lemak) -->
                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div
                                        class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Energi (Kalori)</span
                                        >
                                        <span
                                            class="text-sm sm:text-base font-black text-rose-950 leading-tight block mt-0.5"
                                            >{{ al.pk.energi }} kkal</span
                                        >
                                        <span
                                            class="text-[9.5px] text-slate-400 block mt-0.5"
                                            >Target: 450 - 550 kkal</span
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
                                            >{{ al.pk.protein }} gram</span
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
                                            >{{ al.pk.lemak }} gram</span
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
                                        class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Karbohidrat</span
                                        >
                                        <span
                                            class="text-sm sm:text-base font-black text-emerald-900 leading-tight block mt-0.5"
                                            >{{ al.pk.karbohidrat }} gram</span
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
                                            >{{ al.pk.serat }} gram</span
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
                        <Card className="bg-white border-slate-200 shadow-xs">
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
                                            >• {{ al.siswa_pb }} Siswa PB</span
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
                            <CardContent className="p-3.5 sm:p-4 space-y-2.5">
                                <!-- Baris 1: 3 Kolom (Energi, Protein, Lemak) -->
                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div
                                        class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Energi (Kalori)</span
                                        >
                                        <span
                                            class="text-sm sm:text-base font-black text-rose-950 leading-tight block mt-0.5"
                                            >{{ al.pb.energi }} kkal</span
                                        >
                                        <span
                                            class="text-[9.5px] text-slate-400 block mt-0.5"
                                            >Target: 650 - 800 kkal</span
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
                                            >{{ al.pb.protein }} gram</span
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
                                            >{{ al.pb.lemak }} gram</span
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
                                        class="p-2.5 rounded-xl bg-rose-50/40 border border-rose-100"
                                    >
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase block"
                                            >Karbohidrat</span
                                        >
                                        <span
                                            class="text-sm sm:text-base font-black text-emerald-900 leading-tight block mt-0.5"
                                            >{{ al.pb.karbohidrat }} gram</span
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
                                            >{{ al.pb.serat }} gram</span
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
                            Analisis Food Cost & Kepatuhan Pagu Anggaran BGN
                        </h4>
                        <p class="text-[11px] text-slate-500">
                            Monitoring biaya bahan baku per porsi terhadap batas
                            maksimal pagu MBG nasional.
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
                                        <strong class="text-slate-800 font-bold"
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
                                <span v-else class="flex items-center gap-1.5">
                                    <AlertTriangle
                                        class="h-3.5 w-3.5 text-rose-600"
                                    />
                                    <span>OVER BUDGET</span>
                                </span>
                            </Badge>
                        </CardHeader>
                        <CardContent className="p-4 sm:p-5 space-y-4">
                            <!-- Dual Stat Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                                                totalFoodCostPKNormal > 8000
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
                                                totalFoodCostPKNormal > 8000
                                                    ? 'text-rose-800'
                                                    : 'text-emerald-800'
                                            "
                                        >
                                            {{
                                                totalFoodCostPKNormal > 8000
                                                    ? "Selisih Lebih (Over)"
                                                    : "Sisa Pagu Anggaran"
                                            }}
                                        </span>
                                        <span
                                            class="px-1.5 py-0.5 rounded text-[10px] font-extrabold shadow-2xs"
                                            :class="
                                                totalFoodCostPKNormal > 8000
                                                    ? 'bg-rose-200/80 text-rose-950 border border-rose-300'
                                                    : 'bg-emerald-200/80 text-emerald-950 border border-emerald-300'
                                            "
                                        >
                                            {{
                                                totalFoodCostPKNormal > 8000
                                                    ? "Defisit"
                                                    : "Hemat"
                                            }}
                                        </span>
                                    </div>
                                    <div class="mt-2">
                                        <h4
                                            class="text-2xl sm:text-3xl font-black tracking-tight"
                                            :class="
                                                totalFoodCostPKNormal > 8000
                                                    ? 'text-rose-700'
                                                    : 'text-emerald-700'
                                            "
                                        >
                                            <span
                                                v-if="
                                                    totalFoodCostPKNormal > 8000
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
                                                totalFoodCostPKNormal > 8000
                                                    ? 'text-rose-600'
                                                    : 'text-emerald-700/80'
                                            "
                                        >
                                            {{
                                                totalFoodCostPKNormal > 8000
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
                                                (totalFoodCostPKNormal / 8000) *
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
                                                : totalFoodCostPKNormal > 6800
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
                                        ⚠️ Peringatan: Melebihi Batas Pagu PK!
                                    </p>
                                    <p
                                        class="text-[11px] text-rose-800 leading-relaxed"
                                    >
                                        Food cost PK (<strong>{{
                                            formatRupiah(totalFoodCostPKNormal)
                                        }}</strong
                                        >) melampaui pagu maksimal
                                        <strong>Rp 8.000 / porsi</strong>
                                        dengan selisih lebih
                                        <strong
                                            >+{{
                                                formatRupiah(
                                                    totalFoodCostPKNormal -
                                                        8000,
                                                )
                                            }}
                                            / porsi</strong
                                        >. Mohon sesuaikan gramasi atau pilihan
                                        bahan baku.
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
                                                8000 - totalFoodCostPKNormal,
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
                                            totalFoodCostPBNormal > 10000
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
                                        <strong class="text-slate-800 font-bold"
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
                                    v-if="totalFoodCostPBNormal <= 10000"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-emerald-500"
                                    ></span>
                                    <span>EFISIEN / AMAN</span>
                                </span>
                                <span v-else class="flex items-center gap-1.5">
                                    <AlertTriangle
                                        class="h-3.5 w-3.5 text-rose-600"
                                    />
                                    <span>OVER BUDGET</span>
                                </span>
                            </Badge>
                        </CardHeader>
                        <CardContent className="p-4 sm:p-5 space-y-4">
                            <!-- Dual Stat Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                                                totalFoodCostPBNormal > 10000
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
                                                totalFoodCostPBNormal > 10000
                                                    ? 'text-rose-800'
                                                    : 'text-emerald-800'
                                            "
                                        >
                                            {{
                                                totalFoodCostPBNormal > 10000
                                                    ? "Selisih Lebih (Over)"
                                                    : "Sisa Pagu Anggaran"
                                            }}
                                        </span>
                                        <span
                                            class="px-1.5 py-0.5 rounded text-[10px] font-extrabold shadow-2xs"
                                            :class="
                                                totalFoodCostPBNormal > 10000
                                                    ? 'bg-rose-200/80 text-rose-950 border border-rose-300'
                                                    : 'bg-emerald-200/80 text-emerald-950 border border-emerald-300'
                                            "
                                        >
                                            {{
                                                totalFoodCostPBNormal > 10000
                                                    ? "Defisit"
                                                    : "Hemat"
                                            }}
                                        </span>
                                    </div>
                                    <div class="mt-2">
                                        <h4
                                            class="text-2xl sm:text-3xl font-black tracking-tight"
                                            :class="
                                                totalFoodCostPBNormal > 10000
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
                                                totalFoodCostPBNormal > 10000
                                                    ? 'text-rose-600'
                                                    : 'text-emerald-700/80'
                                            "
                                        >
                                            {{
                                                totalFoodCostPBNormal > 10000
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
                                            totalFoodCostPBNormal > 10000
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
                                            totalFoodCostPBNormal > 10000
                                                ? 'bg-rose-500'
                                                : totalFoodCostPBNormal > 8500
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
                                        ⚠️ Peringatan: Melebihi Batas Pagu PB!
                                    </p>
                                    <p
                                        class="text-[11px] text-rose-800 leading-relaxed"
                                    >
                                        Food cost PB (<strong>{{
                                            formatRupiah(totalFoodCostPBNormal)
                                        }}</strong
                                        >) melampaui pagu maksimal
                                        <strong>Rp 10.000 / porsi</strong>
                                        dengan selisih lebih
                                        <strong
                                            >+{{
                                                formatRupiah(
                                                    totalFoodCostPBNormal -
                                                        10000,
                                                )
                                            }}
                                            / porsi</strong
                                        >. Mohon sesuaikan gramasi atau pilihan
                                        bahan baku.
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
                                                10000 - totalFoodCostPBNormal,
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
                                >Rincian Food Cost per Komponen Bahan Menu</span
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
                                    <th class="p-3 text-center w-10">No</th>
                                    <th class="p-3">Nama Bahan Baku</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-right">Gram PK</th>
                                    <th class="p-3 text-right">Food Cost PK</th>
                                    <th class="p-3 text-right">Gram PB</th>
                                    <th class="p-3 text-right">Food Cost PB</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 text-slate-800"
                            >
                                <tr
                                    v-for="(b, idx) in bahanCalculations"
                                    :key="idx"
                                    class="hover:bg-slate-50/70"
                                >
                                    <td
                                        class="p-3 text-center font-bold text-slate-500"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-3 font-bold text-slate-900">
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
                                            v-if="b.tipe_porsi === 'alergi'"
                                            class="inline-block mt-0.5 px-1.5 py-0.2 rounded bg-rose-100 text-rose-800 text-[10px] font-bold"
                                        >
                                            Alergi: {{ b.jenis_alergi }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-500">
                                        {{ b.kategori }}
                                    </td>
                                    <td class="p-3 text-right text-slate-700">
                                        {{ b.gram_pk }} g
                                    </td>
                                    <td
                                        class="p-3 text-right font-bold text-amber-800"
                                    >
                                        {{ formatRupiah(b.costPK) }}
                                    </td>
                                    <td class="p-3 text-right text-slate-700">
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
                                            v-if="totalFoodCostPKNormal > 8000"
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
                                            totalFoodCostPBNormal > 10000
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
                                            v-if="totalFoodCostPBNormal > 10000"
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
                            >Formulasi Resep, Nilai Gizi (AKG) & Food Cost Siap
                            Diajukan</span
                        >
                    </h4>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Seluruh kebutuhan gramasi, evaluasi AKG BGN, dan
                        analisis food cost telah tervalidasi. Simpan sebagai draft atau lanjutkan.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0 flex-wrap w-full sm:w-auto">
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
                        @click="handleAjukanDraftPo"
                        className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-5 h-10 flex items-center justify-center gap-2 rounded-xl shadow-xs cursor-pointer shrink-0 w-full sm:w-auto text-center"
                    >
                        <Send class="h-4 w-4 shrink-0" />
                        <span>Lanjut ke Review & Pengajuan (Langkah 3)</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
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
                                class="text-sm sm:text-base font-black font-mono text-primary mt-1"
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
                                            class="p-3 text-center font-mono font-bold text-slate-800 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{ b.gram_pk || 0 }}g /
                                            {{ b.gram_pb || 0 }}g
                                        </td>
                                        <td
                                            class="p-3 text-right font-mono font-bold text-slate-900 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{ formatGrossWeight(b.totalGrossKg) }}
                                        </td>
                                        <td
                                            class="p-3 text-right font-mono text-slate-600 align-top pt-4 whitespace-nowrap"
                                        >
                                            {{ formatRupiah(b.harga_master) }}
                                        </td>
                                        <td
                                            class="p-3 text-right font-mono font-bold text-emerald-800 align-top pt-4 whitespace-nowrap"
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
                                            class="p-3.5 text-right font-mono font-black text-slate-900 whitespace-nowrap"
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
                                            class="p-3.5 text-right font-mono font-black text-emerald-900 text-sm whitespace-nowrap"
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
                            @click="buatMenuSubTab = 'pre_order'"
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
